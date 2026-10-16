<?php
class MTN_Cafe_Booking_Install_Test extends WP_UnitTestCase {
	public function test_upgrade_path_reaches_current_schema_from_previous_versions() {
		for ( $version = 1; $version <= 12; $version++ ) {
			update_option( 'mtn_cb_db_version', (string) $version );
			MTN_CB_Install::maybe_upgrade();
			$this->assertSame( '13', (string) get_option( 'mtn_cb_db_version' ) );
		}
	}

	public function test_activation_creates_booking_finance_and_communication_tables() {
		MTN_CB_Install::activate();
		global $wpdb;
		$tables = array(
			MTN_CB_Bookings::table_name(),
			MTN_CB_Finance::ledger_table(),
			MTN_CB_Finance::settlements_table(),
			$wpdb->prefix . 'mtn_booking_events',
			$wpdb->prefix . 'mtn_cafe_invites',
			$wpdb->prefix . 'mtn_notifications',
			$wpdb->prefix . 'mtn_sms_otps',
		);
		foreach ( $tables as $table ) {
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			$this->assertSame( $table, $found, 'Missing required table: ' . $table );
		}
		$this->assertSame( '13', (string) get_option( 'mtn_cb_db_version' ) );
	}
}
