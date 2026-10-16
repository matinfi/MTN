<?php
class MTN_Cafe_Booking_Concurrency_Test extends WP_UnitTestCase {
	public function test_competing_database_connection_blocks_booking_lock() {
		global $wpdb;
		MTN_CB_Install::activate();
		$cafe_id = self::factory()->post->create( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish' ) );
		$connection = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
		$this->assertSame( 0, $connection->connect_errno, 'Second database connection must be available for lock test.' );
		$lock_name = 'mtn_cb_cafe_' . absint( $cafe_id );
		$lock_result = $connection->query( "SELECT GET_LOCK('" . $lock_name . "', 0)" );
		$this->assertNotFalse( $lock_result );
		$this->assertSame( 1, (int) $lock_result->fetch_row()[0] );
		$before = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . MTN_CB_Bookings::table_name() . ' WHERE cafe_id = %d', $cafe_id ) );
		$result = MTN_CB_Bookings::create( array( 'cafe_id' => $cafe_id ) );
		$this->assertWPError( $result );
		$this->assertSame( 503, $result->get_error_data()['status'] );
		$after = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . MTN_CB_Bookings::table_name() . ' WHERE cafe_id = %d', $cafe_id ) );
		$this->assertSame( $before, $after, 'Lock contention must not insert a partial booking.' );
		$connection->query( "SELECT RELEASE_LOCK('" . $lock_name . "')" );
		$connection->close();
	}
}
