<?php
class MTN_Cafe_Booking_SMS_OTP_IP_Test extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		MTN_CB_Install::activate();
	}

	public function test_ip_rate_limit_blocks_eleventh_request_in_window() {
		global $wpdb;
		$ip = '198.51.100.42';
		$_SERVER['REMOTE_ADDR'] = $ip;
		$ip_hash = hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
		$now = current_time( 'mysql', true );
		for ( $i = 0; $i < 10; $i++ ) {
			$wpdb->insert( MTN_CB_SMS_OTP::table_name(), array(
				'phone' => '+155500000' . str_pad( (string) $i, 2, '0', STR_PAD_LEFT ),
				'code_hash' => str_repeat( 'f', 64 ),
				'ip_hash' => $ip_hash,
				'attempts' => 0,
				'status' => 'pending',
				'created_at' => $now,
				'expires_at' => gmdate( 'Y-m-d H:i:s', time() + 300 ),
			) );
		}
		$request = new WP_REST_Request( 'POST', '/mtn/v1/auth/otp/request' );
		$request->set_param( 'phone', '+15559999999' );
		$result = MTN_CB_SMS_OTP::request_code( $request );
		$this->assertWPError( $result );
		$this->assertSame( 'mtn_otp_rate_limited', $result->get_error_code() );
	}
}
