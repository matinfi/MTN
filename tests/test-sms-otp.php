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
}
