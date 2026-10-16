<?php
class MTN_Cafe_Booking_Notification_Test extends WP_UnitTestCase {
	public function test_email_queue_deduplicates_repeated_requests() {
		MTN_CB_Install::activate();
		global $wpdb;
		$key = 'notice_' . strtolower( wp_generate_password( 20, false, false ) );
		$first = MTN_CB_Notifications::enqueue_email( $key, 0, 'mtn-test@example.org', 'Test notification', 'Test message' );
		$second = MTN_CB_Notifications::enqueue_email( $key, 0, 'mtn-test@example.org', 'Test notification', 'Test message' );
		$this->assertTrue( true === $first );
		$this->assertTrue( true === $second );
		$count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . MTN_CB_Notifications::table_name() . ' WHERE dedupe_key = %s', $key ) );
		$this->assertSame( 1, $count );
	}
}
