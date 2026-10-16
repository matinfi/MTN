<?php
class MTN_Cafe_Booking_Permission_Test extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		MTN_CB_Install::activate();
		MTN_CB_Content::register_types();
	}
	public function test_team_member_is_scoped_to_assigned_cafe() {
		$user_id = self::factory()->user->create( array( 'role' => 'mtn_cafe_manager' ) );
		$first_cafe = self::factory()->post->create( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish' ) );
		$second_cafe = self::factory()->post->create( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish' ) );
		update_post_meta( $first_cafe, 'mtn_cafe_manager_ids', array( $user_id ) );
		$this->assertTrue( MTN_CB_Content::user_can_manage_cafe( $first_cafe, $user_id ) );
		$this->assertFalse( MTN_CB_Content::user_can_manage_cafe( $second_cafe, $user_id ) );
	}
	public function test_customer_cannot_read_another_customers_booking() {
		global $wpdb;
		$owner_id = self::factory()->user->create();
		$other_id = self::factory()->user->create();
		$now = current_time( 'mysql', true );
		$wpdb->insert( MTN_CB_Bookings::table_name(), array(
			'booking_code' => 'PRIVACYTEST01',
			'user_id' => $owner_id,
			'cafe_id' => 0,
			'event_id' => 0,
			'table_id' => 0,
			'party_size' => 2,
			'booking_at' => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
			'status' => 'confirmed',
			'customer_note' => '',
			'whatsapp_action_status' => 'manual_required',
			'checkin_status' => 'not_checked_in',
			'checkin_token_hash' => '',
			'commission_amount' => '0.00',
			'commission_rate' => '0.0000',
			'commission_currency' => 'TRY',
			'commission_trigger_status' => 'confirmed',
			'commission_reverse_on_cancellation' => 0,
			'commission_reverse_on_no_show' => 0,
			'commission_reverse_on_event_cancel' => 1,
			'created_at' => $now,
			'updated_at' => $now,
		) );
		$booking_id = (int) $wpdb->insert_id;
		wp_set_current_user( $other_id );
		$request = new WP_REST_Request( 'GET', '/mtn/v1/bookings/' . $booking_id );
		$request->set_param( 'id', $booking_id );
		$result = MTN_CB_Rest::get_booking( $request );
		$this->assertWPError( $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );

		$history_request = new WP_REST_Request( 'GET', '/mtn/v1/bookings/' . $booking_id . '/history' );
		$history_request->set_param( 'id', $booking_id );
		$history = MTN_CB_Rest::booking_history( $history_request );
		$this->assertWPError( $history );
		$this->assertSame( 404, $history->get_error_data()['status'] );

		$token_request = new WP_REST_Request( 'POST', '/mtn/v1/bookings/' . $booking_id . '/checkin-token' );
		$token_request->set_param( 'id', $booking_id );
		$token = MTN_CB_Rest::issue_checkin_token( $token_request );
		$this->assertWPError( $token );
	}

	public function test_anonymous_user_cannot_read_customer_bookings() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/mtn/v1/my/bookings' );
		$result = rest_get_server()->dispatch( $request );
		$this->assertSame( 401, $result->get_status() );
	}

	public function test_cafe_manager_cannot_manage_booking_for_unassigned_cafe() {
		$user_id = self::factory()->user->create( array( 'role' => 'mtn_cafe_manager' ) );
		$cafe_id = self::factory()->post->create( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish' ) );
		wp_set_current_user( $user_id );
		$request = new WP_REST_Request( 'POST', '/mtn/v1/cafe/bookings/999999/confirm' );
		$request->set_param( 'id', 999999 );
		$result = MTN_CB_Rest::can_manage_booking_request( $request );
		$this->assertWPError( $result );
		$this->assertSame( 403, $result->get_error_data()['status'] );
	}

}
