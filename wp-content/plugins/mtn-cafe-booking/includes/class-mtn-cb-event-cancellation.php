<?php
/**
 * Reverse snapshotted commissions when an event is unpublished or cancelled.
 *
 * @package MTN_Cafe_Booking
 */
defined( 'ABSPATH' ) || exit;

final class MTN_CB_Event_Cancellation {
	public static function init() {
		add_action( 'transition_post_status', array( __CLASS__, 'handle_status_change' ), 20, 3 );
	}

	public static function handle_status_change( $new_status, $old_status, $post ) {
		if ( ! $post || 'mtn_event' !== $post->post_type || 'publish' !== $old_status || ! in_array( $new_status, array( 'draft', 'trash', 'private' ), true ) ) {
			return;
		}
		global $wpdb;
		$bookings = $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . MTN_CB_Bookings::table_name() . ' WHERE event_id = %d AND commission_reverse_on_event_cancel = 1',
			absint( $post->ID )
		) );
		foreach ( $bookings as $booking ) {
			MTN_CB_Finance::reverse_commission( absint( $booking->id ), get_current_user_id(), 'event_cancelled', 'رویداد از انتشار خارج یا لغو شد.' );
			MTN_CB_Bookings::record_event( absint( $booking->id ), get_current_user_id(), 'event_cancelled', $booking->status, $booking->status, 'event_id=' . absint( $post->ID ) );
		}
	}
}
