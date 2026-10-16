<?php
class MTN_Cafe_Booking_Settings_Test extends WP_UnitTestCase {
	public function test_commission_trigger_is_limited_to_supported_states() {
		$this->assertSame( 'confirmed', MTN_CB_Settings::sanitize_commission_trigger( 'invalid' ) );
		$this->assertSame( 'completed', MTN_CB_Settings::sanitize_commission_trigger( 'completed' ) );
	}
	public function test_statement_due_days_are_bounded() {
		$this->assertSame( 1, MTN_CB_Settings::sanitize_statement_due_days( 0 ) );
		$this->assertSame( 90, MTN_CB_Settings::sanitize_statement_due_days( 200 ) );
	}
	public function test_checkin_expiry_is_bounded() {
		$this->assertSame( 5, MTN_CB_Settings::sanitize_checkin_minutes( 0 ) );
		$this->assertSame( 60, MTN_CB_Settings::sanitize_checkin_minutes( 999 ) );
	}
}
