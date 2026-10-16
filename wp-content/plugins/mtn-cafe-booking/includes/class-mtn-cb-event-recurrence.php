<?php
/**
 * Creates draft occurrences for recurring events so an administrator can review and publish each date.
 *
 * @package MTN_Cafe_Booking
 */
defined( 'ABSPATH' ) || exit;

final class MTN_CB_Event_Recurrence {
	public static function init() {
		add_action( 'mtn_cb_generate_recurring_events', array( __CLASS__, 'generate_occurrences' ) );
		if ( ! wp_next_scheduled( 'mtn_cb_generate_recurring_events' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mtn_cb_generate_recurring_events' );
		}
	}

	private static function next_date( $date, $frequency, $monthly_day ) {
		if ( 'weekly' === $frequency ) {
			return $date->modify( '+7 days' );
		}
		$month_start = $date->modify( 'first day of next month' );
		$day = min( $monthly_day, (int) $month_start->format( 't' ) );
		return $month_start->setDate( (int) $month_start->format( 'Y' ), (int) $month_start->format( 'n' ), $day );
	}

	public static function generate_occurrences() {
		$timezone = wp_timezone();
		$today = new DateTimeImmutable( 'today', $timezone );
		$horizon = $today->modify( '+30 days' );
		$sources = get_posts( array(
			'post_type' => 'mtn_event',
			'post_status' => 'publish',
			'posts_per_page' => 500,
			'meta_query' => array(
				array( 'key' => 'mtn_event_recurrence', 'value' => array( 'weekly', 'monthly' ), 'compare' => 'IN' ),
			),
		) );
		foreach ( $sources as $source ) {
			$frequency = get_post_meta( $source->ID, 'mtn_event_recurrence', true );
			$base_date = get_post_meta( $source->ID, 'mtn_event_date', true );
			$until_raw = get_post_meta( $source->ID, 'mtn_event_recurrence_until', true );
			if ( ! in_array( $frequency, array( 'weekly', 'monthly' ), true ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $base_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $until_raw ) ) {
				continue;
			}
			$until = DateTimeImmutable::createFromFormat( '!Y-m-d', $until_raw, $timezone );
			$next = DateTimeImmutable::createFromFormat( '!Y-m-d', $base_date, $timezone );
			if ( ! $until || ! $next || $until < $next ) {
				continue;
			}
			$monthly_day = (int) $next->format( 'j' );
			$next = self::next_date( $next, $frequency, $monthly_day );
			for ( $skip = 0; $skip < 5000 && $next <= $today; $skip++ ) {
				$next = self::next_date( $next, $frequency, $monthly_day );
			}
			for ( $count = 0; $count < 60; $count++ ) {
				if ( $next > $until || $next > $horizon ) {
					break;
				}
				$date = $next->format( 'Y-m-d' );
				$next = self::next_date( $next, $frequency, $monthly_day );
				$exists = get_posts( array(
					'post_type' => 'mtn_event',
					'post_status' => array( 'publish', 'draft', 'pending', 'private', 'trash' ),
					'posts_per_page' => 1,
					'fields' => 'ids',
					'meta_query' => array(
						array( 'key' => 'mtn_event_recurrence_parent', 'value' => absint( $source->ID ), 'compare' => '=' ),
						array( 'key' => 'mtn_event_recurrence_date', 'value' => $date, 'compare' => '=' ),
					),
				) );
				if ( $exists ) {
					continue;
				}
				$clone_id = wp_insert_post( array(
					'post_type' => 'mtn_event',
					'post_status' => 'draft',
					'post_title' => $source->post_title . ' — ' . $date,
					'post_content' => $source->post_content,
					'post_excerpt' => $source->post_excerpt,
					'post_author' => absint( $source->post_author ),
				), true );
				if ( is_wp_error( $clone_id ) ) {
					continue;
				}
				foreach ( array( 'mtn_cafe_id', 'mtn_event_capacity', 'mtn_min_party_size', 'mtn_max_party_size', 'mtn_event_start_time', 'mtn_event_end_time' ) as $key ) {
					update_post_meta( $clone_id, $key, get_post_meta( $source->ID, $key, true ) );
				}
				update_post_meta( $clone_id, 'mtn_event_date', $date );
				update_post_meta( $clone_id, 'mtn_event_recurrence', 'none' );
				update_post_meta( $clone_id, 'mtn_event_recurrence_parent', absint( $source->ID ) );
				update_post_meta( $clone_id, 'mtn_event_recurrence_date', $date );
				$thumbnail_id = get_post_thumbnail_id( $source->ID );
				if ( $thumbnail_id ) {
					set_post_thumbnail( $clone_id, $thumbnail_id );
				}
				do_action( 'mtn_cb_recurring_event_created', $clone_id, $source->ID, $date );
			}
		}
	}

	public static function clear_schedule() {
		wp_clear_scheduled_hook( 'mtn_cb_generate_recurring_events' );
	}
}
