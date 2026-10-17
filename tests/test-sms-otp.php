<?php
if ( ! defined( 'MTN_CB_TWILIO_ACCOUNT_SID' ) ) {
	define( 'MTN_CB_TWILIO_ACCOUNT_SID', 'AC_TEST_ONLY' );
}
if ( ! defined( 'MTN_CB_TWILIO_AUTH_TOKEN' ) ) {
	define( 'MTN_CB_TWILIO_AUTH_TOKEN', 'test-token-not-a-secret' );
}
if ( ! defined( 'MTN_CB_TWILIO_FROM' ) ) {
	define( 'MTN_CB_TWILIO_FROM', '+15555550100' );
}

class MTN_Cafe_Booking_SMS_OTP_Test extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		MTN_CB_Install::activate();
	}

	private function make_request( $phone, $code = null ) {
		$request = new WP_REST_Request( 'POST', '/mtn/v1/auth/otp/verify' );
		$request->set_param( 'phone', $phone );
		if ( null !== $code ) {
			$request->set_param( 'code', $code );
		}
		return $request;
	}

	public function test_phone_rate_limit_blocks_fourth_request_in_window() {
		global $wpdb;
		$phone = '+905551234567';
		$now = current_time( 'mysql', true );
		for ( $i = 0; $i < 3; $i++ ) {
			$wpdb->insert( MTN_CB_SMS_OTP::table_name(), array(
				'phone' => $phone,
				'code_hash' => str_repeat( 'a', 64 ),
				'ip_hash' => str_repeat( 'b', 64 ),
				'attempts' => 0,
				'status' => 'pending',
				'created_at' => $now,
				'expires_at' => gmdate( 'Y-m-d H:i:s', time() + 300 ),
			) );
		}
		$request = new WP_REST_Request( 'POST', '/mtn/v1/auth/otp/request' );
		$request->set_param( 'phone', $phone );
		$result = MTN_CB_SMS_OTP::request_code( $request );
		$this->assertWPError( $result );
		$this->assertSame( 'mtn_otp_rate_limited', $result->get_error_code() );
	}

	public function test_wrong_code_locks_after_five_attempts() {
		global $wpdb;
		$phone = '+905551234568';
		$now = current_time( 'mysql', true );
		$wpdb->insert( MTN_CB_SMS_OTP::table_name(), array(
			'phone' => $phone,
			'code_hash' => hash_hmac( 'sha256', $phone . '|123456', wp_salt( 'auth' ) ),
			'ip_hash' => str_repeat( 'c', 64 ),
			'attempts' => 0,
			'status' => 'sent',
			'created_at' => $now,
			'expires_at' => gmdate( 'Y-m-d H:i:s', time() + 300 ),
		) );
		for ( $i = 0; $i < 5; $i++ ) {
			$result = MTN_CB_SMS_OTP::verify_code( $this->make_request( $phone, '000000' ) );
			$this->assertWPError( $result );
		}
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT attempts, status FROM ' . MTN_CB_SMS_OTP::table_name() . ' WHERE phone = %s ORDER BY id DESC LIMIT 1', $phone ) );
		$this->assertSame( 5, (int) $row->attempts );
		$this->assertSame( 'locked', $row->status );
	}
	public function test_resending_invalidates_previous_active_code() {
		global $wpdb;
		$phone = '+905551234569';
		$now_ts = time();
		$wpdb->insert( MTN_CB_SMS_OTP::table_name(), array(
			'phone' => $phone, 'code_hash' => str_repeat( 'd', 64 ), 'ip_hash' => str_repeat( 'e', 64 ),
			'attempts' => 0, 'status' => 'sent', 'created_at' => gmdate( 'Y-m-d H:i:s', $now_ts - 120 ),
			'expires_at' => gmdate( 'Y-m-d H:i:s', $now_ts + 180 ), 'consumed_at' => null,
		) );
		$old_id = (int) $wpdb->insert_id;
		$request = new WP_REST_Request( 'POST', '/mtn/v1/auth/otp/request' );
		$request->set_param( 'phone', $phone );
		$result = MTN_CB_SMS_OTP::request_code( $request );
		$this->assertNotWPError( $result );
		$status = $wpdb->get_var( $wpdb->prepare( 'SELECT status FROM ' . MTN_CB_SMS_OTP::table_name() . ' WHERE id = %d', $old_id ) );
		$this->assertSame( 'superseded', $status );
	}

	public function test_invalid_otp_format_is_rejected_before_lookup() {
		$result = MTN_CB_SMS_OTP::verify_code( $this->make_request( 'not-a-phone', '12ab' ) );
		$this->assertWPError( $result );
		$this->assertSame( 'mtn_otp_invalid', $result->get_error_code() );
	}


	public function test_valid_code_authenticates_registered_phone_once_and_replay_fails() {
		global $wpdb;
		$phone = '+905551234570';
		$user_id = self::factory()->user->create();
		update_user_meta( $user_id, 'mtn_cb_phone', $phone );
		$captured_code = '';
		$mock_twilio = static function ( $preempt, $args ) use ( &$captured_code ) {
			if ( isset( $args['body']['Body'] ) && preg_match( '/([0-9]{6})/', $args['body']['Body'], $matches ) ) {
				$captured_code = $matches[1];
			}
			return array(
				'headers' => array(),
				'body' => '{}',
				'response' => array( 'code' => 201, 'message' => 'Created' ),
				'cookies' => array(),
				'filename' => null,
			);
		};
		add_filter( 'pre_http_request', $mock_twilio, 10, 3 );
		try {
			$request = new WP_REST_Request( 'POST', '/mtn/v1/auth/otp/request' );
			$request->set_param( 'phone', $phone );
			$requested = MTN_CB_SMS_OTP::request_code( $request );
			$this->assertNotWPError( $requested );
			$this->assertSame( 6, strlen( $captured_code ), 'The mocked provider should capture the generated OTP without exposing it in the API response.' );
		} finally {
			remove_filter( 'pre_http_request', $mock_twilio, 10 );
		}
		wp_set_current_user( 0 );
		$verify = MTN_CB_SMS_OTP::verify_code( $this->make_request( $phone, $captured_code ) );
		$this->assertNotWPError( $verify );
		$this->assertSame( $user_id, get_current_user_id() );
		$status = $wpdb->get_var( $wpdb->prepare( 'SELECT status FROM ' . MTN_CB_SMS_OTP::table_name() . ' WHERE phone = %s ORDER BY id DESC LIMIT 1', $phone ) );
		$this->assertSame( 'consumed', $status );
		wp_set_current_user( 0 );
		$replay = MTN_CB_SMS_OTP::verify_code( $this->make_request( $phone, $captured_code ) );
		$this->assertWPError( $replay );
		$this->assertSame( 'mtn_otp_expired', $replay->get_error_code() );
		$this->assertSame( 0, get_current_user_id(), 'Replaying a consumed code must not authenticate the user.' );
	}
}
