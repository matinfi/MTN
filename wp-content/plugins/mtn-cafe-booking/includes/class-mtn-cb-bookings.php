<?php
/**
 * Booking persistence and state machine.
 *
 * @package MTN_Cafe_Booking
 */

defined( 'ABSPATH' ) || exit;

final class MTN_CB_Bookings {
	const STATUSES = array(
		'pending_cafe',
		'confirmed',
		'rejected',
		'cancelled_by_customer',
		'cancelled_by_cafe',
		'cancelled_by_admin',
		'expired',
		'completed',
		'no_show',
	);

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'mtn_bookings';
	}

	public static function create( $args ) {
		global $wpdb;

		$user_id   = get_current_user_id();
		$party     = isset( $args['party_size'] ) ? absint( $args['party_size'] ) : 0;
		$event_id  = isset( $args['event_id'] ) ? absint( $args['event_id'] ) : 0;
		$cafe_id   = isset( $args['cafe_id'] ) ? absint( $args['cafe_id'] ) : 0;
		$table_id  = isset( $args['table_id'] ) ? absint( $args['table_id'] ) : 0;
		$booking_at = isset( $args['booking_at'] ) ? sanitize_text_field( $args['booking_at'] ) : '';

		if ( ! $user_id || $party < 1 || $party > 100 || ! $event_id || ! $cafe_id || ! self::valid_datetime( $booking_at ) ) {
			return new WP_Error( 'mtn_invalid_booking', __( 'اطلاعات رزرو معتبر نیست.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}

		$now  = current_time( 'mysql', true );
		$code = self::generate_code();

		$inserted = $wpdb->insert(
			self::table_name(),
			array(
				'booking_code'           => $code,
				'user_id'                => $user_id,
				'cafe_id'                => $cafe_id,
				'event_id'               => $event_id,
				'table_id'               => $table_id,
				'party_size'             => $party,
				'booking_at'             => gmdate( 'Y-m-d H:i:s', strtotime( $booking_at . ' UTC' ) ),
				'status'                 => 'pending_cafe',
				'customer_note'          => isset( $args['customer_note'] ) ? sanitize_textarea_field( $args['customer_note'] ) : '',
				'whatsapp_action_status' => 'manual_required',
				'checkin_status'          => 'not_checked_in',
				'created_at'              => $now,
				'updated_at'              => $now,
			),
			array( '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'mtn_booking_failed', __( 'ثبت رزرو انجام نشد. دوباره تلاش کنید.', 'mtn-cafe-booking' ), array( 'status' => 500 ) );
		}

		return self::get_by_id( $wpdb->insert_id );
	}

	public static function get_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE id = %d', absint( $id ) )
		);
	}

	public static function get_for_user( $id, $user_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table_name() . ' WHERE id = %d AND user_id = %d',
				absint( $id ),
				absint( $user_id )
			)
		);
	}

	public static function transition( $id, $next_status, $actor_id = 0, $reason = '' ) {
		global $wpdb;

		$allowed = array(
			'pending_cafe' => array( 'confirmed', 'rejected', 'cancelled_by_customer', 'cancelled_by_cafe', 'cancelled_by_admin', 'expired' ),
			'confirmed'    => array( 'completed', 'no_show', 'cancelled_by_customer', 'cancelled_by_cafe', 'cancelled_by_admin' ),
		);

		$booking = self::get_by_id( $id );
		if ( ! $booking || ! in_array( $next_status, self::STATUSES, true ) ) {
			return new WP_Error( 'mtn_invalid_transition', __( 'تغییر وضعیت معتبر نیست.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}
		if ( ! isset( $allowed[ $booking->status ] ) || ! in_array( $next_status, $allowed[ $booking->status ], true ) ) {
			return new WP_Error( 'mtn_transition_not_allowed', __( 'این تغییر وضعیت مجاز نیست.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
		}

		$updated = $wpdb->update(
			self::table_name(),
			array( 'status' => $next_status, 'cafe_note' => sanitize_textarea_field( $reason ), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => absint( $id ), 'status' => $booking->status ),
			array( '%s', '%s', '%s' ),
			array( '%d', '%s' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'mtn_transition_conflict', __( 'رزرو هم‌زمان تغییر کرده است؛ صفحه را تازه‌سازی کنید.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
		}
		do_action( 'mtn_cb_booking_status_changed', $booking, $next_status, absint( $actor_id ), sanitize_textarea_field( $reason ) );
		return self::get_by_id( $id );
	}

	private static function generate_code() {
		global $wpdb;
		for ( $i = 0; $i < 5; $i++ ) {
			$code = strtoupper( wp_generate_password( 12, false, false ) );
			$found = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table_name() . ' WHERE booking_code = %s', $code ) );
			if ( ! $found ) {
				return $code;
			}
		}
		return strtoupper( bin2hex( random_bytes( 12 ) ) );
	}

	private static function valid_datetime( $value ) {
		$date = DateTime::createFromFormat( 'Y-m-d H:i:s', $value );
		return $date && $date->format( 'Y-m-d H:i:s' ) === $value;
	}
}
