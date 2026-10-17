<?php
class MTN_Cafe_Booking_V1_Features_Test extends WP_UnitTestCase {
	public function test_customer_can_save_and_remove_only_published_events() {
		$user_id = self::factory()->user->create();
		$event_id = self::factory()->post->create( array( 'post_type' => 'mtn_event', 'post_status' => 'publish' ) );
		wp_set_current_user( $user_id );
		$request = new WP_REST_Request( 'POST', '/mtn/v1/my/favorites/' . $event_id );
		$request->set_param( 'event_id', $event_id );
		$added = MTN_CB_Rest::add_favorite( $request );
		$this->assertSame( true, $added->get_data()['saved'] );
		$this->assertContains( $event_id, get_user_meta( $user_id, 'mtn_cb_favorite_event_ids', true ) );
		$removed = MTN_CB_Rest::remove_favorite( $request );
		$this->assertSame( false, $removed->get_data()['saved'] );
		$this->assertNotContains( $event_id, get_user_meta( $user_id, 'mtn_cb_favorite_event_ids', true ) );
	}

	public function test_cancellation_is_rejected_after_three_hours_from_creation() {
		global $wpdb;
		MTN_CB_Install::activate();
		MTN_CB_Content::register_types();
		update_option( 'mtn_cb_booking_mode', 'cafe_assigns_table' );
		$cafe_id = self::factory()->post->create( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish' ) );
		$event_id = self::factory()->post->create( array( 'post_type' => 'mtn_event', 'post_status' => 'publish' ) );
		$date = new DateTimeImmutable( 'tomorrow', wp_timezone() );
		update_post_meta( $event_id, 'mtn_cafe_id', $cafe_id );
		update_post_meta( $event_id, 'mtn_event_date', $date->format( 'Y-m-d' ) );
		update_post_meta( $event_id, 'mtn_event_start_time', '18:00' );
		update_post_meta( $event_id, 'mtn_event_end_time', '23:00' );
		update_post_meta( $event_id, 'mtn_event_capacity', 10 );
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );
		$booking = MTN_CB_Bookings::create( array( 'cafe_id' => $cafe_id, 'event_id' => $event_id, 'party_size' => 2, 'booking_at' => $date->setTime( 20, 0 )->format( 'Y-m-d H:i:s' ) ) );
		$this->assertNotWPError( $booking );
		$wpdb->update( MTN_CB_Bookings::table_name(), array( 'created_at' => gmdate( 'Y-m-d H:i:s', time() - 4 * HOUR_IN_SECONDS ) ), array( 'id' => $booking->id ), array( '%s' ), array( '%d' ) );
		$result = MTN_CB_Bookings::transition( $booking->id, 'cancelled_by_customer', $user_id, 'late_cancel_test' );
		$this->assertWPError( $result );
		$this->assertSame( 'mtn_cancellation_cutoff', $result->get_error_code() );
	}
}
