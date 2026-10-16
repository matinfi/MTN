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
	public function test_event_capacity_is_enforced_across_serialized_booking_requests() {
		MTN_CB_Install::activate();
		MTN_CB_Content::register_types();
		update_option( 'mtn_cb_booking_mode', 'cafe_assigns_table' );
		$cafe_id = self::factory()->post->create( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish' ) );
		$event_id = self::factory()->post->create( array( 'post_type' => 'mtn_event', 'post_status' => 'publish' ) );
		$event_date = new DateTimeImmutable( 'tomorrow', wp_timezone() );
		update_post_meta( $event_id, 'mtn_cafe_id', $cafe_id );
		update_post_meta( $event_id, 'mtn_event_date', $event_date->format( 'Y-m-d' ) );
		update_post_meta( $event_id, 'mtn_event_start_time', '18:00' );
		update_post_meta( $event_id, 'mtn_event_end_time', '23:00' );
		update_post_meta( $event_id, 'mtn_event_capacity', 3 );
		update_post_meta( $event_id, 'mtn_max_party_size', 10 );
		$booking_at = $event_date->setTime( 20, 0 )->format( 'Y-m-d H:i:s' );
		$first_user = self::factory()->user->create();
		$second_user = self::factory()->user->create();
		wp_set_current_user( $first_user );
		$first = MTN_CB_Bookings::create( array( 'cafe_id' => $cafe_id, 'event_id' => $event_id, 'party_size' => 2, 'booking_at' => $booking_at ) );
		$this->assertNotWPError( $first, 'The first booking should fit within event capacity.' );
		wp_set_current_user( $second_user );
		$second = MTN_CB_Bookings::create( array( 'cafe_id' => $cafe_id, 'event_id' => $event_id, 'party_size' => 2, 'booking_at' => $booking_at ) );
		$this->assertWPError( $second );
		$this->assertSame( 'mtn_event_capacity_reached', $second->get_error_code() );
	}

}
