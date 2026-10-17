<?php
class MTN_Cafe_Booking_Lifecycle_Test extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		MTN_CB_Install::activate();
		MTN_CB_Content::register_types();
		update_option( 'mtn_cb_booking_mode', 'customer_selects_table' );
		update_option( 'mtn_cb_default_commission', '12.50' );
		update_option( 'mtn_cb_currency', 'TRY' );
		update_option( 'mtn_cb_commission_trigger_status', 'confirmed' );
		update_option( 'mtn_cb_reverse_commission_on_cancellation', true );
	}

	public function test_booking_confirmation_cancellation_and_commission_reversal_lifecycle() {
		global $wpdb;
		$cafe_id = self::factory()->post->create( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish' ) );
		$event_id = self::factory()->post->create( array( 'post_type' => 'mtn_event', 'post_status' => 'publish' ) );
		$table_id = self::factory()->post->create( array( 'post_type' => 'mtn_table', 'post_status' => 'publish' ) );
		$event_date = new DateTimeImmutable( 'tomorrow', wp_timezone() );
		update_post_meta( $event_id, 'mtn_cafe_id', $cafe_id );
		update_post_meta( $event_id, 'mtn_event_date', $event_date->format( 'Y-m-d' ) );
		update_post_meta( $event_id, 'mtn_event_start_time', '18:00' );
		update_post_meta( $event_id, 'mtn_event_end_time', '23:00' );
		update_post_meta( $event_id, 'mtn_event_capacity', 10 );
		update_post_meta( $event_id, 'mtn_max_party_size', 10 );
		update_post_meta( $table_id, 'mtn_cafe_id', $cafe_id );
		update_post_meta( $table_id, 'mtn_table_capacity', 4 );
		update_post_meta( $table_id, 'mtn_table_active', 1 );
		$customer_id = self::factory()->user->create();
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $customer_id );
		$booking = MTN_CB_Bookings::create( array(
			'cafe_id' => $cafe_id,
			'event_id' => $event_id,
			'table_id' => $table_id,
			'party_size' => 2,
			'booking_at' => $event_date->setTime( 20, 0 )->format( 'Y-m-d H:i:s' ),
		) );
		$this->assertNotWPError( $booking );
		$this->assertSame( 'pending_cafe', $booking->status );
		$confirmed = MTN_CB_Bookings::transition( $booking->id, 'confirmed', $admin_id, 'lifecycle_test_confirmed' );
		$this->assertNotWPError( $confirmed );
		$this->assertSame( 'confirmed', $confirmed->status );
		$accrued = $wpdb->get_var( $wpdb->prepare( 'SELECT amount FROM ' . MTN_CB_Finance::ledger_table() . ' WHERE entry_key = %s', 'commission_booking_' . absint( $booking->id ) ) );
		$this->assertSame( '12.50', $accrued );
		$cancelled = MTN_CB_Bookings::transition( $booking->id, 'cancelled_by_customer', $customer_id, 'lifecycle_test_cancelled' );
		$this->assertNotWPError( $cancelled );
		$this->assertSame( 'cancelled_by_customer', $cancelled->status );
		$reversal = $wpdb->get_var( $wpdb->prepare( 'SELECT amount FROM ' . MTN_CB_Finance::ledger_table() . ' WHERE entry_key = %s', 'commission_reversal_booking_' . absint( $booking->id ) ) );
		$this->assertSame( '-12.50', $reversal );
		$this->assertSame( 0.0, MTN_CB_Finance::get_balance( $cafe_id ) );
		$invalid = MTN_CB_Bookings::transition( $booking->id, 'confirmed', $admin_id, 'invalid_replay' );
		$this->assertWPError( $invalid );
		$this->assertSame( 'mtn_transition_not_allowed', $invalid->get_error_code() );
	}

	public function test_idempotency_replays_matching_payload_and_is_scoped_to_customer() {
		$cafe_id = self::factory()->post->create( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish' ) );
		$event_id = self::factory()->post->create( array( 'post_type' => 'mtn_event', 'post_status' => 'publish' ) );
		$event_date = new DateTimeImmutable( 'tomorrow', wp_timezone() );
		update_post_meta( $event_id, 'mtn_cafe_id', $cafe_id );
		update_post_meta( $event_id, 'mtn_event_date', $event_date->format( 'Y-m-d' ) );
		update_post_meta( $event_id, 'mtn_event_start_time', '18:00' );
		update_post_meta( $event_id, 'mtn_event_end_time', '23:00' );
		update_post_meta( $event_id, 'mtn_event_capacity', 20 );
		update_post_meta( $event_id, 'mtn_max_party_size', 10 );
		$first_table = self::factory()->post->create( array( 'post_type' => 'mtn_table', 'post_status' => 'publish' ) );
		$second_table = self::factory()->post->create( array( 'post_type' => 'mtn_table', 'post_status' => 'publish' ) );
		foreach ( array( $first_table, $second_table ) as $table_id ) {
			update_post_meta( $table_id, 'mtn_cafe_id', $cafe_id );
			update_post_meta( $table_id, 'mtn_table_capacity', 4 );
			update_post_meta( $table_id, 'mtn_table_active', 1 );
		}
		$booking_at = $event_date->setTime( 20, 0 )->format( 'Y-m-d H:i:s' );
		$first_user = self::factory()->user->create();
		$second_user = self::factory()->user->create();
		$args = array(
			'cafe_id' => $cafe_id,
			'event_id' => $event_id,
			'party_size' => 2,
			'booking_at' => $booking_at,
			'table_id' => $first_table,
			'idempotency_key' => 'lifecycle-key-001',
		);
		wp_set_current_user( $first_user );
		$first = MTN_CB_Bookings::create( $args );
		$this->assertNotWPError( $first );
		$replay = MTN_CB_Bookings::create( $args );
		$this->assertNotWPError( $replay );
		$this->assertSame( (int) $first->id, (int) $replay->id, 'A matching replay must return the original booking.' );
		$mismatched = $args;
		$mismatched['party_size'] = 3;
		$conflict = MTN_CB_Bookings::create( $mismatched );
		$this->assertWPError( $conflict );
		$this->assertSame( 'mtn_idempotency_conflict', $conflict->get_error_code() );
		wp_set_current_user( $second_user );
		$other_args = $args;
		$other_args['table_id'] = $second_table;
		$other_customer = MTN_CB_Bookings::create( $other_args );
		$this->assertNotWPError( $other_customer );
		$this->assertNotSame( (int) $first->id, (int) $other_customer->id, 'Idempotency keys must not collide across customers.' );
	}

	public function test_overlapping_reservations_cannot_claim_the_same_table() {
		$cafe_id = self::factory()->post->create( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish' ) );
		$event_id = self::factory()->post->create( array( 'post_type' => 'mtn_event', 'post_status' => 'publish' ) );
		$table_id = self::factory()->post->create( array( 'post_type' => 'mtn_table', 'post_status' => 'publish' ) );
		$event_date = new DateTimeImmutable( 'tomorrow', wp_timezone() );
		update_post_meta( $event_id, 'mtn_cafe_id', $cafe_id );
		update_post_meta( $event_id, 'mtn_event_date', $event_date->format( 'Y-m-d' ) );
		update_post_meta( $event_id, 'mtn_event_start_time', '18:00' );
		update_post_meta( $event_id, 'mtn_event_end_time', '23:00' );
		update_post_meta( $event_id, 'mtn_event_capacity', 10 );
		update_post_meta( $event_id, 'mtn_max_party_size', 4 );
		update_post_meta( $table_id, 'mtn_cafe_id', $cafe_id );
		update_post_meta( $table_id, 'mtn_table_capacity', 4 );
		update_post_meta( $table_id, 'mtn_table_active', 1 );
		$args = array(
			'cafe_id' => $cafe_id,
			'event_id' => $event_id,
			'table_id' => $table_id,
			'party_size' => 2,
			'booking_at' => $event_date->setTime( 20, 0 )->format( 'Y-m-d H:i:s' ),
		);
		wp_set_current_user( self::factory()->user->create() );
		$first = MTN_CB_Bookings::create( $args );
		$this->assertNotWPError( $first );
		wp_set_current_user( self::factory()->user->create() );
		$second = MTN_CB_Bookings::create( $args );
		$this->assertWPError( $second );
		$this->assertSame( 'mtn_table_time_conflict', $second->get_error_code() );
	}
}
