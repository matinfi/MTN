<?php
/**
 * Idempotent email notification queue with bounded retries and message history.
 *
 * @package MTN_Cafe_Booking
 */
defined( 'ABSPATH' ) || exit;

final class MTN_CB_Notifications {
	public static function init() {
		add_action( 'mtn_cb_booking_status_changed', array( __CLASS__, 'queue_booking_status' ), 20, 4 );
		add_action( 'mtn_cb_process_notifications', array( __CLASS__, 'process_queue' ) );
		if ( ! wp_next_scheduled( 'mtn_cb_process_notifications' ) ) {
			wp_schedule_event( time() + 90, 'mtn_cb_five_minutes', 'mtn_cb_process_notifications' );
		}
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'mtn_notifications';
	}

	public static function enqueue_email( $dedupe_key, $booking_id, $recipient, $subject, $message ) {
		global $wpdb;
		$recipient = sanitize_email( $recipient );
		if ( ! is_email( $recipient ) || ! preg_match( '/^[A-Za-z0-9._:-]{8,100}$/', $dedupe_key ) ) {
			return new WP_Error( 'mtn_notification_invalid', __( 'نشانی یا شناسه اعلان معتبر نیست.', 'mtn-cafe-booking' ) );
		}
		$now = current_time( 'mysql', true );
		$inserted = $wpdb->query( $wpdb->prepare(
			'INSERT IGNORE INTO ' . self::table_name() . ' (dedupe_key, booking_id, channel, recipient, subject, message, status, attempts, next_attempt_at, last_error, created_at) VALUES (%s, %d, %s, %s, %s, %s, %s, %d, %s, %s, %s)',
			$dedupe_key,
			absint( $booking_id ),
			'email',
			$recipient,
			sanitize_text_field( $subject ),
			sanitize_textarea_field( $message ),
			'queued',
			0,
			$now,
			'',
			$now
		) );
		if ( false === $inserted ) {
			$existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table_name() . ' WHERE dedupe_key = %s', $dedupe_key ) );
			return $existing ? true : new WP_Error( 'mtn_notification_enqueue_failed', __( 'صف اعلان ثبت نشد.', 'mtn-cafe-booking' ) );
		}
		return true;
	}

	public static function queue_booking_status( $booking, $next_status, $actor_id = 0, $reason = '' ) {
		if ( empty( $booking->id ) || empty( $booking->user_id ) ) {
			return;
		}
		$user = get_user_by( 'id', absint( $booking->user_id ) );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return;
		}
		$labels = array(
			'pending_cafe' => 'در انتظار تأیید کافه',
			'confirmed' => 'تأیید شده',
			'rejected' => 'رد شده',
			'cancelled_by_customer' => 'لغو توسط مشتری',
			'cancelled_by_cafe' => 'لغو توسط کافه',
			'cancelled_by_admin' => 'لغو توسط مدیر',
			'expired' => 'منقضی شده',
			'completed' => 'تکمیل شده',
			'no_show' => 'عدم مراجعه',
		);
		$status_label = isset( $labels[ $next_status ] ) ? $labels[ $next_status ] : sanitize_key( $next_status );
		$subject = sprintf( 'به‌روزرسانی رزرو %s', sanitize_text_field( $booking->booking_code ) );
		$message = sprintf(
			"وضعیت رزرو شما تغییر کرد.\n\nکد رزرو: %s\nوضعیت: %s\n",
			sanitize_text_field( $booking->booking_code ),
			$status_label
		);
		if ( '' !== trim( (string) $reason ) ) {
			$message .= "\nتوضیح: " . sanitize_textarea_field( $reason ) . "\n";
		}
		self::enqueue_email( 'booking_' . absint( $booking->id ) . '_status_' . sanitize_key( $next_status ), $booking->id, $user->user_email, $subject, $message );
	}

	public static function process_queue() {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$items = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM " . self::table_name() . " WHERE status = 'queued' AND next_attempt_at <= %s ORDER BY id ASC LIMIT 25",
			$now
		) );
		foreach ( $items as $item ) {
			$lock_name = 'mtn_cb_notification_' . absint( $item->id );
			if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $lock_name ) ) ) {
				continue;
			}
			try {
				$current = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE id = %d', absint( $item->id ) ) );
				if ( ! $current || 'queued' !== $current->status || $current->next_attempt_at > $now ) {
					continue;
				}
				$attempts = absint( $current->attempts ) + 1;
				$sent = 'email' === $current->channel && wp_mail( $current->recipient, $current->subject, $current->message );
				if ( $sent ) {
					$wpdb->update( self::table_name(), array( 'status' => 'sent', 'attempts' => $attempts, 'sent_at' => $now, 'last_error' => '' ), array( 'id' => absint( $current->id ), 'status' => 'queued' ), array( '%s', '%d', '%s', '%s' ), array( '%d', '%s' ) );
					continue;
				}
				$dead = $attempts >= 5;
				$delay = min( DAY_IN_SECONDS, 60 * ( 2 ** min( 10, $attempts ) ) );
				$wpdb->update(
					self::table_name(),
					array(
						'status' => $dead ? 'failed' : 'queued',
						'attempts' => $attempts,
						'next_attempt_at' => gmdate( 'Y-m-d H:i:s', time() + $delay ),
						'last_error' => 'wp_mail returned false',
					),
					array( 'id' => absint( $current->id ), 'status' => 'queued' ),
					array( '%s', '%d', '%s', '%s' ),
					array( '%d', '%s' )
				);
			} finally {
				$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
			}
		}
	}
}
