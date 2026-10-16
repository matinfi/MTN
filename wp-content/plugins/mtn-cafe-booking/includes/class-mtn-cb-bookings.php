<?php
/**
 * Booking persistence, availability checks and state machine.
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

	const CAPACITY_STATUSES = array( 'pending_cafe', 'confirmed' );

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'mtn_bookings';
	}

	/**
	 * Serialize reservation writes per cafe, then validate and insert while the
	 * advisory lock is held. This protects checks against concurrent requests
	 * on MySQL/MariaDB connections that support GET_LOCK().
	 */
	public static function create( $args ) {
		global $wpdb;

		$cafe_id = isset( $args['cafe_id'] ) ? absint( $args['cafe_id'] ) : 0;
		if ( ! $cafe_id ) {
			return new WP_Error( 'mtn_invalid_booking', __( 'اطلاعات رزرو معتبر نیست.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}

		$lock_name = 'mtn_cb_cafe_' . $cafe_id;
		$locked    = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_name ) );
		if ( '1' !== (string) $locked ) {
			return new WP_Error( 'mtn_booking_busy', __( 'سامانه در حال پردازش رزروهای این کافه است؛ چند لحظه دیگر تلاش کنید.', 'mtn-cafe-booking' ), array( 'status' => 503 ) );
		}

		try {
			return self::create_locked( $args );
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
		}
	}

	private static function create_locked( $args ) {
		global $wpdb;

		$user_id    = get_current_user_id();
		$party      = isset( $args['party_size'] ) ? absint( $args['party_size'] ) : 0;
		$event_id   = isset( $args['event_id'] ) ? absint( $args['event_id'] ) : 0;
		$cafe_id    = isset( $args['cafe_id'] ) ? absint( $args['cafe_id'] ) : 0;
		$table_id   = isset( $args['table_id'] ) ? absint( $args['table_id'] ) : 0;
		$booking_at = isset( $args['booking_at'] ) ? sanitize_text_field( $args['booking_at'] ) : '';
		$idempotency_key = isset( $args['idempotency_key'] ) ? sanitize_text_field( $args['idempotency_key'] ) : '';
		$idempotency_key = '' === $idempotency_key ? null : $idempotency_key;
		$customer_note = isset( $args['customer_note'] ) ? sanitize_textarea_field( $args['customer_note'] ) : '';

		if ( ! $user_id || $party < 1 || $party > 100 || ! $event_id || ! $cafe_id ) {
			return new WP_Error( 'mtn_invalid_booking', __( 'اطلاعات رزرو معتبر نیست.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}

		if ( null !== $idempotency_key && ! preg_match( '/^[A-Za-z0-9._:-]{8,64}$/', $idempotency_key ) ) {
			return new WP_Error( 'mtn_invalid_idempotency_key', __( 'کلید جلوگیری از ثبت تکراری معتبر نیست.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}

		$local_date = self::parse_local_datetime( $booking_at );
		if ( ! $local_date ) {
			return new WP_Error( 'mtn_invalid_datetime', __( 'زمان رزرو باید به قالب YYYY-MM-DD HH:MM:SS و بر اساس منطقه زمانی سایت باشد.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}
		$utc_date       = $local_date->setTimezone( new DateTimeZone( 'UTC' ) );
		$booking_at_utc = $utc_date->format( 'Y-m-d H:i:s' );

		$cafe  = get_post( $cafe_id );
		$event = get_post( $event_id );
		if ( ! $cafe || 'mtn_cafe' !== $cafe->post_type || 'publish' !== $cafe->post_status || ! $event || 'mtn_event' !== $event->post_type || 'publish' !== $event->post_status ) {
			return new WP_Error( 'mtn_unavailable_listing', __( 'کافه یا رویداد فعال و قابل رزرو نیست.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}
		if ( absint( get_post_meta( $event_id, 'mtn_cafe_id', true ) ) !== $cafe_id ) {
			return new WP_Error( 'mtn_event_cafe_mismatch', __( 'رویداد به این کافه تعلق ندارد.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}
		$schedule_check = self::validate_event_schedule( $event_id, $local_date );
		if ( is_wp_error( $schedule_check ) ) {
			return $schedule_check;
		}

		$min_party = absint( get_post_meta( $event_id, 'mtn_min_party_size', true ) );
		$max_party = absint( get_post_meta( $event_id, 'mtn_max_party_size', true ) );
		$max_party = $max_party ? $max_party : 100;
		if ( ( $min_party && $party < $min_party ) || $party > $max_party ) {
			return new WP_Error( 'mtn_party_size_invalid', __( 'تعداد نفرات خارج از محدوده مجاز رویداد است.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}

		if ( 'cafe_assigns_table' === get_option( 'mtn_cb_booking_mode', 'customer_selects_table' ) ) {
			$table_id = 0;
		} elseif ( $table_id ) {
			$table = get_post( $table_id );
			$table_capacity = absint( get_post_meta( $table_id, 'mtn_table_capacity', true ) );
			if ( ! $table || 'mtn_table' !== $table->post_type || 'publish' !== $table->post_status || absint( get_post_meta( $table_id, 'mtn_cafe_id', true ) ) !== $cafe_id || ! $table_capacity || $party > $table_capacity || ! get_post_meta( $table_id, 'mtn_table_active', true ) ) {
				return new WP_Error( 'mtn_table_unavailable', __( 'میز انتخاب‌شده معتبر یا مناسب این رزرو نیست.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
			}
		} else {
			return new WP_Error( 'mtn_table_required', __( 'یک میز معتبر انتخاب کنید.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}

		// A repeated key returns the original booking only when the request payload matches.
		if ( null !== $idempotency_key ) {
			$existing = self::get_by_idempotency_key( $user_id, $idempotency_key );
			if ( $existing ) {
				if ( self::matches_request( $existing, $cafe_id, $event_id, $table_id, $party, $booking_at_utc, $customer_note ) ) {
					return $existing;
				}
				return new WP_Error( 'mtn_idempotency_conflict', __( 'این کلید قبلاً برای درخواست رزرو دیگری استفاده شده است.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
			}
		}

		if ( $utc_date->getTimestamp() <= time() ) {
			return new WP_Error( 'mtn_booking_in_past', __( 'زمان رزرو باید در آینده باشد.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}

		// Release stale pending holds while the per-cafe advisory lock is held.
		self::expire_pending_for_cafe_locked( $cafe_id );

		$duration = absint( get_option( 'mtn_cb_booking_duration_minutes', 120 ) );
		$duration = min( 720, max( 30, $duration ) );
		$start_ts = $utc_date->getTimestamp();
		$end_ts   = $start_ts + ( $duration * MINUTE_IN_SECONDS );
		$window_start = gmdate( 'Y-m-d H:i:s', $start_ts - ( $duration * MINUTE_IN_SECONDS ) );
		$window_end   = gmdate( 'Y-m-d H:i:s', $end_ts );

		// Event capacity is a total headcount for this event record. A value of
		// zero means that the operator has not configured a capacity limit.
		$event_capacity = absint( get_post_meta( $event_id, 'mtn_event_capacity', true ) );
		if ( $event_capacity ) {
			$booked_people = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COALESCE(SUM(party_size), 0) FROM ' . self::table_name() . " WHERE event_id = %d AND status IN ('pending_cafe','confirmed')",
					$event_id
				)
			);
			if ( $booked_people + $party > $event_capacity ) {
				return new WP_Error( 'mtn_event_capacity_reached', __( 'ظرفیت این رویداد برای تعداد نفرات درخواستی کافی نیست.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
			}
		}

		// A table cannot have two active reservations whose time windows overlap.
		if ( $table_id ) {
			$conflict = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT id FROM ' . self::table_name() . " WHERE cafe_id = %d AND table_id = %d AND status IN ('pending_cafe','confirmed') AND booking_at > %s AND booking_at < %s LIMIT 1",
					$cafe_id,
					$table_id,
					$window_start,
					$window_end
				)
			);
			if ( $conflict ) {
				return new WP_Error( 'mtn_table_time_conflict', __( 'این میز در بازه زمانی انتخاب‌شده رزرو فعال دیگری دارد.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
			}
		}

		$now  = current_time( 'mysql', true );
		$code = self::generate_code();
		$commission_snapshot = number_format( max( 0, (float) get_option( 'mtn_cb_default_commission', '0' ) ), 2, '.', '' );
		$commission_currency = sanitize_text_field( get_option( 'mtn_cb_currency', 'TRY' ) );

		$inserted = $wpdb->insert(
			self::table_name(),
			array(
				'booking_code'          => $code,
				'idempotency_key'       => $idempotency_key,
				'user_id'               => $user_id,
				'cafe_id'                => $cafe_id,
				'event_id'               => $event_id,
				'table_id'               => $table_id,
				'party_size'             => $party,
				'booking_at'             => $booking_at_utc,
				'status'                 => 'pending_cafe',
				'customer_note'          => isset( $args['customer_note'] ) ? sanitize_textarea_field( $args['customer_note'] ) : '',
				'whatsapp_action_status' => 'manual_required',
				'checkin_status'         => 'not_checked_in',
				'commission_amount'      => $commission_snapshot,
				'commission_currency'    => $commission_currency,
				'created_at'             => $now,
				'updated_at'             => $now,
			),
			array( '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			if ( null !== $idempotency_key ) {
				$existing = self::get_by_idempotency_key( $user_id, $idempotency_key );
				if ( $existing && self::matches_request( $existing, $cafe_id, $event_id, $table_id, $party, $booking_at_utc, $customer_note ) ) {
					return $existing;
				}
				if ( $existing ) {
					return new WP_Error( 'mtn_idempotency_conflict', __( 'این کلید قبلاً برای درخواست رزرو دیگری استفاده شده است.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
				}
			}
			return new WP_Error( 'mtn_booking_failed', __( 'ثبت رزرو انجام نشد. دوباره تلاش کنید.', 'mtn-cafe-booking' ), array( 'status' => 500 ) );
		}

		$booking_id = (int) $wpdb->insert_id;
		self::record_event( $booking_id, $user_id, 'booking_created', '', 'pending_cafe', '' );
		return self::get_by_id( $booking_id );
	}

	/**
	 * Return an advisory availability snapshot for the public booking wizard.
	 * Creation rechecks all rules under the cafe lock; this response is never a hold.
	 */
	private static function expire_pending_for_cafe_locked( $cafe_id ) {
		global $wpdb;
		$expiry_minutes = min( 1440, max( 5, absint( get_option( 'mtn_cb_booking_expiry_minutes', 30 ) ) ) );
		$expiry_before = gmdate( 'Y-m-d H:i:s', time() - ( $expiry_minutes * MINUTE_IN_SECONDS ) );
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM " . self::table_name() . " WHERE cafe_id = %d AND status = 'pending_cafe' AND created_at < %s ORDER BY id ASC LIMIT 500",
			absint( $cafe_id ),
			$expiry_before
		) );
		$expired = 0;
		foreach ( $ids as $booking_id ) {
			$updated = $wpdb->update(
				self::table_name(),
				array( 'status' => 'expired', 'updated_at' => current_time( 'mysql', true ) ),
				array( 'id' => absint( $booking_id ), 'status' => 'pending_cafe' ),
				array( '%s', '%s' ),
				array( '%d', '%s' )
			);
			if ( 1 === $updated ) {
				++$expired;
				self::record_event( $booking_id, 0, 'booking_expired', 'pending_cafe', 'expired', 'approval_timeout' );
			}
		}
		if ( $expired ) {
			do_action( 'mtn_cb_pending_bookings_expired', absint( $cafe_id ), $expired );
		}
		return $expired;
	}

	public static function expire_pending_bookings() {
		global $wpdb;
		$expiry_minutes = min( 1440, max( 5, absint( get_option( 'mtn_cb_booking_expiry_minutes', 30 ) ) ) );
		$expiry_before = gmdate( 'Y-m-d H:i:s', time() - ( $expiry_minutes * MINUTE_IN_SECONDS ) );
		$cafe_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT cafe_id FROM " . self::table_name() . " WHERE status = 'pending_cafe' AND created_at < %s LIMIT 500",
			$expiry_before
		) );
		foreach ( $cafe_ids as $cafe_id ) {
			$lock_name = 'mtn_cb_cafe_' . absint( $cafe_id );
			$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 2)', $lock_name ) );
			if ( '1' !== (string) $locked ) {
				continue;
			}
			try {
				self::expire_pending_for_cafe_locked( $cafe_id );
			} finally {
				$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
			}
		}
	}

	public static function get_availability( $args ) {
		global $wpdb;

		$cafe_id    = isset( $args['cafe_id'] ) ? absint( $args['cafe_id'] ) : 0;
		$event_id   = isset( $args['event_id'] ) ? absint( $args['event_id'] ) : 0;
		$party      = isset( $args['party_size'] ) ? absint( $args['party_size'] ) : 0;
		$booking_at = isset( $args['booking_at'] ) ? sanitize_text_field( $args['booking_at'] ) : '';
		$date       = self::parse_local_datetime( $booking_at );

		if ( ! $cafe_id || ! $event_id || $party < 1 || $party > 100 || ! $date ) {
			return new WP_Error( 'mtn_invalid_availability_query', __( 'پارامترهای بررسی ظرفیت معتبر نیستند.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}
		$utc_date = $date->setTimezone( new DateTimeZone( 'UTC' ) );
		if ( $utc_date->getTimestamp() <= time() ) {
			return new WP_Error( 'mtn_booking_in_past', __( 'زمان انتخابی باید در آینده باشد.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}
		$cafe  = get_post( $cafe_id );
		$event = get_post( $event_id );
		if ( ! $cafe || 'mtn_cafe' !== $cafe->post_type || 'publish' !== $cafe->post_status || ! $event || 'mtn_event' !== $event->post_type || 'publish' !== $event->post_status || absint( get_post_meta( $event_id, 'mtn_cafe_id', true ) ) !== $cafe_id ) {
			return new WP_Error( 'mtn_unavailable_listing', __( 'کافه یا رویداد قابل رزرو نیست.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		$schedule_check = self::validate_event_schedule( $event_id, $date );
		if ( is_wp_error( $schedule_check ) ) {
			return $schedule_check;
		}
		$expiry_minutes = min( 1440, max( 5, absint( get_option( 'mtn_cb_booking_expiry_minutes', 30 ) ) ) );
		$expiry_before = gmdate( 'Y-m-d H:i:s', time() - ( $expiry_minutes * MINUTE_IN_SECONDS ) );

		$min_party = absint( get_post_meta( $event_id, 'mtn_min_party_size', true ) );
		$max_party = absint( get_post_meta( $event_id, 'mtn_max_party_size', true ) );
		$max_party = $max_party ? $max_party : 100;
		if ( ( $min_party && $party < $min_party ) || $party > $max_party ) {
			return new WP_Error( 'mtn_party_size_invalid', __( 'تعداد نفرات خارج از محدوده مجاز رویداد است.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}

		$duration = min( 720, max( 30, absint( get_option( 'mtn_cb_booking_duration_minutes', 120 ) ) ) );
		$start_ts = $utc_date->getTimestamp();
		$window_start = gmdate( 'Y-m-d H:i:s', $start_ts - ( $duration * MINUTE_IN_SECONDS ) );
		$window_end = gmdate( 'Y-m-d H:i:s', $start_ts + ( $duration * MINUTE_IN_SECONDS ) );
		$event_capacity = absint( get_post_meta( $event_id, 'mtn_event_capacity', true ) );
		$booked_people = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COALESCE(SUM(party_size), 0) FROM " . self::table_name() . " WHERE event_id = %d AND (status = 'confirmed' OR (status = 'pending_cafe' AND created_at >= %s))",
			$event_id,
			$expiry_before
		) );
		$remaining = $event_capacity ? max( 0, $event_capacity - $booked_people ) : null;
		$mode = get_option( 'mtn_cb_booking_mode', 'customer_selects_table' );
		$tables = array();

		if ( null === $remaining || $remaining >= $party ) {
			$table_ids = get_posts( array(
				'post_type' => 'mtn_table',
				'post_status' => 'publish',
				'posts_per_page' => 200,
				'fields' => 'ids',
				'orderby' => 'title',
				'order' => 'ASC',
				'meta_query' => array(
					'relation' => 'AND',
					array( 'key' => 'mtn_cafe_id', 'value' => $cafe_id, 'compare' => '=', 'type' => 'NUMERIC' ),
					array( 'key' => 'mtn_table_active', 'value' => '1', 'compare' => '=' ),
					array( 'key' => 'mtn_table_capacity', 'value' => $party, 'compare' => '>=', 'type' => 'NUMERIC' ),
				),
			) );
			foreach ( $table_ids as $table_id ) {
				$conflict = $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM " . self::table_name() . " WHERE cafe_id = %d AND table_id = %d AND (status = 'confirmed' OR (status = 'pending_cafe' AND created_at >= %s)) AND booking_at > %s AND booking_at < %s LIMIT 1",
					$cafe_id, $table_id, $expiry_before, $window_start, $window_end
				) );
				if ( ! $conflict ) {
					$tables[] = array(
						'id' => (int) $table_id,
						'name' => get_the_title( $table_id ),
						'capacity' => absint( get_post_meta( $table_id, 'mtn_table_capacity', true ) ),
					);
				}
			}
		}

		$has_available_table = ! empty( $tables );
		if ( 'cafe_assigns_table' === $mode ) {
			$tables = array();
		}

		return array(
			'available' => ( null === $remaining || $remaining >= $party ) && $has_available_table,
			'cafe_id' => $cafe_id,
			'event_id' => $event_id,
			'party_size' => $party,
			'remaining_event_capacity' => $remaining,
			'booking_duration_minutes' => $duration,
			'table_selection_required' => 'cafe_assigns_table' !== $mode,
			'tables' => $tables,
			'note' => __( 'این نتیجه صرفاً پیش‌نمایش ظرفیت است؛ ظرفیت نهایی هنگام ثبت رزرو دوباره بررسی می‌شود.', 'mtn-cafe-booking' ),
		);
	}

	public static function get_assignable_tables( $booking_id ) {
		global $wpdb;
		$booking = self::get_by_id( $booking_id );
		if ( ! $booking || ! in_array( $booking->status, self::CAPACITY_STATUSES, true ) ) {
			return array();
		}
		$duration = min( 720, max( 30, absint( get_option( 'mtn_cb_booking_duration_minutes', 120 ) ) ) );
		$start_ts = strtotime( $booking->booking_at . ' UTC' );
		$window_start = gmdate( 'Y-m-d H:i:s', $start_ts - ( $duration * MINUTE_IN_SECONDS ) );
		$window_end = gmdate( 'Y-m-d H:i:s', $start_ts + ( $duration * MINUTE_IN_SECONDS ) );
		$expiry_minutes = min( 1440, max( 5, absint( get_option( 'mtn_cb_booking_expiry_minutes', 30 ) ) ) );
		$expiry_before = gmdate( 'Y-m-d H:i:s', time() - ( $expiry_minutes * MINUTE_IN_SECONDS ) );
		$table_ids = get_posts( array(
			'post_type' => 'mtn_table',
			'post_status' => 'publish',
			'posts_per_page' => 200,
			'fields' => 'ids',
			'orderby' => 'title',
			'order' => 'ASC',
			'meta_query' => array(
				'relation' => 'AND',
				array( 'key' => 'mtn_cafe_id', 'value' => absint( $booking->cafe_id ), 'compare' => '=', 'type' => 'NUMERIC' ),
				array( 'key' => 'mtn_table_active', 'value' => '1', 'compare' => '=' ),
				array( 'key' => 'mtn_table_capacity', 'value' => absint( $booking->party_size ), 'compare' => '>=', 'type' => 'NUMERIC' ),
			),
		) );
		$available = array();
		foreach ( $table_ids as $table_id ) {
			$conflict = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM " . self::table_name() . " WHERE cafe_id = %d AND table_id = %d AND id <> %d AND (status = 'confirmed' OR (status = 'pending_cafe' AND created_at >= %s)) AND booking_at > %s AND booking_at < %s LIMIT 1",
				absint( $booking->cafe_id ), absint( $table_id ), absint( $booking->id ), $expiry_before, $window_start, $window_end
			) );
			if ( ! $conflict ) {
				$available[] = array( 'id' => (int) $table_id, 'name' => get_the_title( $table_id ), 'capacity' => absint( get_post_meta( $table_id, 'mtn_table_capacity', true ) ) );
			}
		}
		return $available;
	}

	public static function assign_table( $booking_id, $table_id, $actor_id = 0 ) {
		global $wpdb;
		$booking = self::get_by_id( $booking_id );
		if ( ! $booking || ! in_array( $booking->status, self::CAPACITY_STATUSES, true ) ) {
			return new WP_Error( 'mtn_booking_not_assignable', __( 'این رزرو در وضعیت فعلی قابل تخصیص میز نیست.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
		}
		$cafe_id = absint( $booking->cafe_id );
		$lock_name = 'mtn_cb_cafe_' . $cafe_id;
		$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_name ) );
		if ( '1' !== (string) $locked ) {
			return new WP_Error( 'mtn_booking_busy', __( 'سامانه در حال پردازش رزروهای این کافه است؛ دوباره تلاش کنید.', 'mtn-cafe-booking' ), array( 'status' => 503 ) );
		}
		try {
			$booking = self::get_by_id( $booking_id );
			if ( ! $booking || ! in_array( $booking->status, self::CAPACITY_STATUSES, true ) ) {
				return new WP_Error( 'mtn_booking_not_assignable', __( 'این رزرو دیگر قابل تخصیص میز نیست.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
			}
			$table = get_post( $table_id );
			$capacity = absint( get_post_meta( $table_id, 'mtn_table_capacity', true ) );
			if ( ! $table || 'mtn_table' !== $table->post_type || 'publish' !== $table->post_status || absint( get_post_meta( $table_id, 'mtn_cafe_id', true ) ) !== $cafe_id || ! $capacity || $capacity < (int) $booking->party_size || ! get_post_meta( $table_id, 'mtn_table_active', true ) ) {
				return new WP_Error( 'mtn_table_unavailable', __( 'میز انتخاب‌شده فعال نیست یا ظرفیت کافی ندارد.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
			}
			$available = self::get_assignable_tables( $booking_id );
			$available_ids = array_map( 'absint', wp_list_pluck( $available, 'id' ) );
			if ( ! in_array( absint( $table_id ), $available_ids, true ) ) {
				return new WP_Error( 'mtn_table_time_conflict', __( 'این میز برای زمان رزرو در دسترس نیست.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
			}
			$updated = $wpdb->update(
				self::table_name(),
				array( 'table_id' => absint( $table_id ), 'updated_at' => current_time( 'mysql', true ) ),
				array( 'id' => absint( $booking_id ), 'status' => $booking->status ),
				array( '%d', '%s' ),
				array( '%d', '%s' )
			);
			if ( 1 !== $updated ) {
				return new WP_Error( 'mtn_booking_update_conflict', __( 'رزرو هم‌زمان تغییر کرده است؛ دوباره تلاش کنید.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
			}
			self::record_event( $booking->id, $actor_id, 'table_assigned', $booking->status, $booking->status, 'table_id=' . absint( $table_id ) );
			do_action( 'mtn_cb_booking_table_assigned', $booking, absint( $table_id ), absint( $actor_id ) );
			return self::get_by_id( $booking_id );
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
		}
	}

	public static function record_event( $booking_id, $actor_id, $event_type, $from_status = '', $to_status = '', $details = '' ) {
		global $wpdb;
		return $wpdb->insert(
			$wpdb->prefix . 'mtn_booking_events',
			array(
				'booking_id' => absint( $booking_id ),
				'actor_id' => absint( $actor_id ),
				'event_type' => sanitize_key( $event_type ),
				'from_status' => sanitize_key( $from_status ),
				'to_status' => sanitize_key( $to_status ),
				'details' => sanitize_textarea_field( $details ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	public static function get_history( $booking_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT id, booking_id, actor_id, event_type, from_status, to_status, details, created_at FROM ' . $wpdb->prefix . 'mtn_booking_events WHERE booking_id = %d ORDER BY id ASC', absint( $booking_id ) ) );
	}

	public static function get_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE id = %d', absint( $id ) ) );
	}

	private static function get_by_idempotency_key( $user_id, $key ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table_name() . ' WHERE user_id = %d AND idempotency_key = %s LIMIT 1',
				absint( $user_id ),
				$key
			)
		);
	}

	private static function matches_request( $booking, $cafe_id, $event_id, $table_id, $party, $booking_at_utc, $customer_note ) {
		return (int) $booking->cafe_id === (int) $cafe_id
			&& (int) $booking->event_id === (int) $event_id
			&& (int) $booking->table_id === (int) $table_id
			&& (int) $booking->party_size === (int) $party
			&& $booking->booking_at === $booking_at_utc
			&& (string) $booking->customer_note === (string) $customer_note;
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
		if ( 'completed' === $next_status && 'cafe_assigns_table' === get_option( 'mtn_cb_booking_mode', 'customer_selects_table' ) && ! absint( $booking->table_id ) ) {
			return new WP_Error( 'mtn_table_not_assigned', __( 'پیش از تکمیل رزرو، میز را تخصیص دهید.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
		}

		$update_data = array(
			'status' => $next_status,
			'updated_at' => current_time( 'mysql', true ),
		);
		$update_formats = array( '%s', '%s' );
		if ( '' !== trim( (string) $reason ) && in_array( $next_status, array( 'rejected', 'cancelled_by_cafe', 'cancelled_by_admin', 'no_show' ), true ) ) {
			$update_data['cafe_note'] = sanitize_textarea_field( $reason );
			$update_formats[] = '%s';
		}
		$updated = $wpdb->update(
			self::table_name(),
			$update_data,
			array( 'id' => absint( $id ), 'status' => $booking->status ),
			$update_formats,
			array( '%d', '%s' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'mtn_transition_conflict', __( 'رزرو هم‌زمان تغییر کرده است؛ صفحه را تازه‌سازی کنید.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
		}
		self::record_event( $booking->id, $actor_id, 'status_changed', $booking->status, $next_status, $reason );
		do_action( 'mtn_cb_booking_status_changed', $booking, $next_status, absint( $actor_id ), sanitize_textarea_field( $reason ) );
		return self::get_by_id( $id );
	}

	private static function generate_code() {
		global $wpdb;
		for ( $i = 0; $i < 5; $i++ ) {
			$code  = strtoupper( wp_generate_password( 12, false, false ) );
			$found = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table_name() . ' WHERE booking_code = %s', $code ) );
			if ( ! $found ) {
				return $code;
			}
		}
		return strtoupper( bin2hex( random_bytes( 12 ) ) );
	}

	private static function validate_event_schedule( $event_id, $local_date ) {
		$event_day = sanitize_text_field( get_post_meta( $event_id, 'mtn_event_date', true ) );
		$start = sanitize_text_field( get_post_meta( $event_id, 'mtn_event_start_time', true ) );
		$end = sanitize_text_field( get_post_meta( $event_id, 'mtn_event_end_time', true ) );
		if ( $event_day ) {
			$event_date_object = DateTimeImmutable::createFromFormat( '!Y-m-d', $event_day, wp_timezone() );
			$event_date_errors = DateTimeImmutable::getLastErrors();
			if ( ! preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', $event_day ) || ! $event_date_object || ( is_array( $event_date_errors ) && ( $event_date_errors['warning_count'] || $event_date_errors['error_count'] ) ) || $event_date_object->format( 'Y-m-d' ) !== $event_day ) {
				return new WP_Error( 'mtn_event_schedule_invalid', __( 'تاریخ برنامه در تنظیمات کافه معتبر نیست.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
			}
		}
		$time = $local_date->format( 'H:i:s' );
		$start = $start ? ( 5 === strlen( $start ) ? $start . ':00' : $start ) : '';
		$end = $end ? ( 5 === strlen( $end ) ? $end . ':00' : $end ) : '';
		if ( ! $event_day || ! $start ) {
			return new WP_Error( 'mtn_event_schedule_missing', __( 'تاریخ و ساعت شروع برنامه را پیش از فعال‌کردن رزرو تنظیم کنید.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}
		if ( $local_date->format( 'Y-m-d' ) !== $event_day ) {
			$next_day = DateTimeImmutable::createFromFormat( '!Y-m-d', $event_day, wp_timezone() );
			$next_day = $next_day ? $next_day->modify( '+1 day' )->format( 'Y-m-d' ) : '';
			$overnight_event = $start && $end && $end < $start;
			if ( ! ( $overnight_event && $local_date->format( 'Y-m-d' ) === $next_day && $time < $end ) ) {
				return new WP_Error( 'mtn_event_date_mismatch', __( 'رزرو باید در تاریخ ثبت‌شده برای این برنامه باشد.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
			}
		}
		if ( ( $start && ! preg_match( '/^(?:[01]\\d|2[0-3]):[0-5]\\d:[0-5]\\d$/', $start ) ) || ( $end && ! preg_match( '/^(?:[01]\\d|2[0-3]):[0-5]\\d:[0-5]\\d$/', $end ) ) ) {
			return new WP_Error( 'mtn_event_schedule_invalid', __( 'ساعت برنامه در تنظیمات کافه معتبر نیست.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}
		$overnight = $start && $end && $end < $start;
		if ( $overnight ) {
			if ( $time < $start && $time >= $end ) {
				return new WP_Error( 'mtn_event_outside_hours', __( 'زمان رزرو خارج از ساعت برنامه است.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
			}
		} else {
			if ( $start && $time < $start ) {
				return new WP_Error( 'mtn_event_outside_hours', __( 'زمان رزرو پیش از شروع برنامه است.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
			}
			if ( $end && $time >= $end ) {
				return new WP_Error( 'mtn_event_outside_hours', __( 'زمان رزرو پس از پایان برنامه است.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
			}
		}
		return true;
	}

	private static function parse_local_datetime( $value ) {
		if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value ) ) {
			return false;
		}
		$date   = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $value, wp_timezone() );
		$errors = DateTimeImmutable::getLastErrors();
		if ( ! $date || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) || $date->format( 'Y-m-d H:i:s' ) !== $value ) {
			return false;
		}
		return $date;
	}
}
