<?php
/**
 * Versioned REST API.
 *
 * @package MTN_Cafe_Booking
 */

defined( 'ABSPATH' ) || exit;

final class MTN_CB_Rest {
	const NS = 'mtn/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			self::NS,
			'/bookings',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_booking' ),
				'permission_callback' => static function () {
					return is_user_logged_in() ? true : new WP_Error( 'mtn_auth_required', __( 'برای ثبت رزرو وارد حساب شوید.', 'mtn-cafe-booking' ), array( 'status' => 401 ) );
				},
				'args'                => array(
					'cafe_id'       => array( 'required' => true, 'sanitize_callback' => 'absint' ),
					'event_id'      => array( 'required' => true, 'sanitize_callback' => 'absint' ),
					'party_size'    => array( 'required' => true, 'sanitize_callback' => 'absint' ),
					'booking_at'    => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
					'table_id'      => array( 'required' => false, 'default' => 0, 'sanitize_callback' => 'absint' ),
					'customer_note' => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
					'idempotency_key' => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/bookings/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_booking' ),
				'permission_callback' => static function () {
					return is_user_logged_in() ? true : new WP_Error( 'mtn_auth_required', __( 'برای مشاهده رزرو وارد حساب شوید.', 'mtn-cafe-booking' ), array( 'status' => 401 ) );
				},
				'args'                => array( 'id' => array( 'sanitize_callback' => 'absint' ) ),
			)
		);

		register_rest_route(
			self::NS,
			'/bookings/(?P<id>\d+)/history',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( __CLASS__, 'booking_history' ),
				'permission_callback' => static function () {
					return is_user_logged_in() ? true : new WP_Error( 'mtn_auth_required', __( 'برای مشاهده تاریخچه وارد حساب شوید.', 'mtn-cafe-booking' ), array( 'status' => 401 ) );
				},
				'args' => array( 'id' => array( 'sanitize_callback' => 'absint' ) ),
			)
		);

		register_rest_route(
			self::NS,
			'/bookings/(?P<id>\d+)/checkin-token',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( __CLASS__, 'issue_checkin_token' ),
				'permission_callback' => static function () {
					return is_user_logged_in() ? true : new WP_Error( 'mtn_auth_required', __( 'برای دریافت کد اعلام حضور وارد حساب شوید.', 'mtn-cafe-booking' ), array( 'status' => 401 ) );
				},
				'args' => array( 'id' => array( 'sanitize_callback' => 'absint' ) ),
			)
		);

		register_rest_route(
			self::NS,
			'/cafe/check-in',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( __CLASS__, 'check_in_customer' ),
				'permission_callback' => array( __CLASS__, 'can_access_cafe_dashboard' ),
				'args' => array( 'token' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ) ),
			)
		);

		register_rest_route(
			self::NS,
			'/bookings/(?P<id>\d+)/cancel',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'cancel_booking' ),
				'permission_callback' => static function () {
					return is_user_logged_in() ? true : new WP_Error( 'mtn_auth_required', __( 'برای لغو رزرو وارد حساب شوید.', 'mtn-cafe-booking' ), array( 'status' => 401 ) );
				},
				'args'                => array( 'id' => array( 'sanitize_callback' => 'absint' ) ),
			)
		);

		register_rest_route(
			self::NS,
			'/cafe/bookings/(?P<id>\d+)/confirm',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'confirm_booking' ),
				'permission_callback' => array( __CLASS__, 'can_manage_booking_request' ),
				'args'                => array( 'id' => array( 'sanitize_callback' => 'absint' ) ),
			)
		);

		register_rest_route(
			self::NS,
			'/cafe/bookings/(?P<id>\d+)/complete',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( __CLASS__, 'complete_booking' ),
				'permission_callback' => array( __CLASS__, 'can_manage_booking_request' ),
				'args' => array( 'id' => array( 'sanitize_callback' => 'absint' ) ),
			)
		);

		register_rest_route(
			self::NS,
			'/cafe/bookings/(?P<id>\d+)/no-show',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( __CLASS__, 'mark_no_show' ),
				'permission_callback' => array( __CLASS__, 'can_manage_booking_request' ),
				'args' => array( 'id' => array( 'sanitize_callback' => 'absint' ) ),
			)
		);

		register_rest_route(
			self::NS,
			'/cafe/bookings/(?P<id>\d+)/assign-table',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( __CLASS__, 'assign_table' ),
				'permission_callback' => array( __CLASS__, 'can_manage_booking_request' ),
				'args' => array(
					'id' => array( 'sanitize_callback' => 'absint' ),
					'table_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/cafe/bookings/(?P<id>\d+)/cancel',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( __CLASS__, 'cancel_by_cafe' ),
				'permission_callback' => array( __CLASS__, 'can_manage_booking_request' ),
				'args' => array(
					'id' => array( 'sanitize_callback' => 'absint' ),
					'reason' => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/cafe/bookings/(?P<id>\d+)/reject',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'reject_booking' ),
				'permission_callback' => array( __CLASS__, 'can_manage_booking_request' ),
				'args'                => array(
					'id'     => array( 'sanitize_callback' => 'absint' ),
					'reason' => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/cafe/bookings',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( __CLASS__, 'cafe_bookings' ),
				'permission_callback' => array( __CLASS__, 'can_access_cafe_dashboard' ),
			)
		);

		register_rest_route(
			self::NS,
			'/catalog',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( __CLASS__, 'catalog' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NS,
			'/events',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( __CLASS__, 'events_search' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NS,
			'/availability',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( __CLASS__, 'availability' ),
				'permission_callback' => '__return_true',
				'args' => array(
					'cafe_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
					'event_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
					'party_size' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
					'booking_at' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);

		register_rest_route( self::NS, '/my/favorites', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( __CLASS__, 'my_favorites' ),
			'permission_callback' => static function () { return is_user_logged_in() ? true : new WP_Error( 'mtn_auth_required', __( 'برای مشاهده علاقه‌مندی‌ها وارد حساب شوید.', 'mtn-cafe-booking' ), array( 'status' => 401 ) ); },
		) );
		register_rest_route( self::NS, '/my/favorites/(?P<event_id>\\d+)', array(
			array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'add_favorite' ), 'permission_callback' => static function () { return is_user_logged_in() ? true : new WP_Error( 'mtn_auth_required', __( 'برای ذخیره برنامه وارد حساب شوید.', 'mtn-cafe-booking' ), array( 'status' => 401 ) ); }, 'args' => array( 'event_id' => array( 'sanitize_callback' => 'absint' ) ) ),
			array( 'methods' => WP_REST_Server::DELETABLE, 'callback' => array( __CLASS__, 'remove_favorite' ), 'permission_callback' => static function () { return is_user_logged_in() ? true : new WP_Error( 'mtn_auth_required', __( 'برای ویرایش علاقه‌مندی‌ها وارد حساب شوید.', 'mtn-cafe-booking' ), array( 'status' => 401 ) ); }, 'args' => array( 'event_id' => array( 'sanitize_callback' => 'absint' ) ) ),
		) );

		register_rest_route(
			self::NS,
			'/my/bookings',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( __CLASS__, 'my_bookings' ),
				'permission_callback' => static function () {
					return is_user_logged_in() ? true : new WP_Error( 'mtn_auth_required', __( 'برای مشاهده رزروها وارد حساب شوید.', 'mtn-cafe-booking' ), array( 'status' => 401 ) );
				},
			)
		);

		register_rest_route(
			self::NS,
			'/health',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static function () {
					return rest_ensure_response( array( 'status' => 'ok', 'version' => MTN_CB_VERSION ) );
				},
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function can_access_cafe_dashboard() {
		return current_user_can( 'manage_options' ) ? true : new WP_Error( 'mtn_forbidden', __( 'در نسخه اول، مدیریت رزروها فقط از پنل مرکزی مدیر سایت انجام می‌شود.', 'mtn-cafe-booking' ), array( 'status' => 403 ) );
	}

	public static function can_manage_booking_request( $request ) {
		return current_user_can( 'manage_options' ) ? true : new WP_Error( 'mtn_forbidden', __( 'در نسخه اول، مدیریت رزروها فقط از پنل مرکزی مدیر سایت انجام می‌شود.', 'mtn-cafe-booking' ), array( 'status' => 403 ) );
	}

	public static function cafe_bookings() {
		global $wpdb;
		$table = MTN_CB_Bookings::table_name();
		$sql = 'SELECT DISTINCT b.*, cafe.post_title AS cafe_title, ev.post_title AS event_title FROM ' . $table . ' b LEFT JOIN ' . $wpdb->posts . ' cafe ON cafe.ID = b.cafe_id LEFT JOIN ' . $wpdb->posts . ' ev ON ev.ID = b.event_id LEFT JOIN ' . $wpdb->postmeta . " owner_meta ON owner_meta.post_id = b.cafe_id AND owner_meta.meta_key = 'mtn_cafe_owner_id' LEFT JOIN " . $wpdb->postmeta . " team_meta ON team_meta.post_id = b.cafe_id AND team_meta.meta_key = 'mtn_cafe_manager_ids'";
		if ( ! current_user_can( 'manage_options' ) ) {
			$user_id = get_current_user_id();
			$team_pattern = '%' . $wpdb->esc_like( 'i:' . $user_id . ';' ) . '%';
			$sql .= $wpdb->prepare( ' WHERE (owner_meta.meta_value = %s OR team_meta.meta_value LIKE %s)', (string) $user_id, $team_pattern );
		}
		$sql .= ' ORDER BY b.created_at DESC LIMIT 100';
		$rows = $wpdb->get_results( $sql );
		return rest_ensure_response( array_map( static function ( $booking ) {
			$data = self::public_booking( $booking );
			$data['cafe_name'] = $booking->cafe_title ? $booking->cafe_title : '';
			$data['event_name'] = $booking->event_title ? $booking->event_title : '';
			$data['table_name'] = $booking->table_id ? get_the_title( $booking->table_id ) : '';
			$data['available_tables'] = ! $booking->table_id && in_array( $booking->status, MTN_CB_Bookings::CAPACITY_STATUSES, true ) ? MTN_CB_Bookings::get_assignable_tables( $booking->id ) : array();
			return $data;
		}, $rows ) );
	}

	private static function cafe_location_path( $cafe_id ) {
		$terms = wp_get_object_terms( absint( $cafe_id ), 'mtn_cafe_location' );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}
		$term = reset( $terms );
		$ancestor_ids = array_reverse( get_ancestors( $term->term_id, 'mtn_cafe_location', 'taxonomy' ) );
		$path = array();
		foreach ( $ancestor_ids as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'mtn_cafe_location' );
			if ( $ancestor && ! is_wp_error( $ancestor ) ) {
				$path[] = $ancestor->name;
			}
		}
		$path[] = $term->name;
		return array_values( array_map( 'sanitize_text_field', $path ) );
	}

	public static function catalog() {
		$cafe_posts = get_posts( array(
			'post_type' => 'mtn_cafe',
			'post_status' => 'publish',
			'posts_per_page' => 100,
			'orderby' => 'title',
			'order' => 'ASC',
		) );
		$cafes = array();
		foreach ( $cafe_posts as $cafe ) {
			$cafes[] = array( 'id' => (int) $cafe->ID, 'name' => get_the_title( $cafe ), 'url' => get_permalink( $cafe ), 'description' => wp_strip_all_tags( get_the_excerpt( $cafe ) ), 'location_path' => self::cafe_location_path( $cafe->ID ) );
		}
		$event_posts = get_posts( array(
			'post_type' => 'mtn_event',
			'post_status' => 'publish',
			'posts_per_page' => 200,
			'meta_key' => 'mtn_event_date',
			'orderby' => 'meta_value',
			'order' => 'ASC',
			'meta_query' => array(
				'relation' => 'AND',
				array( 'key' => 'mtn_event_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ),
				array( 'key' => 'mtn_event_start_time', 'value' => '', 'compare' => '!=' ),
			),
		) );
		$events = array();
		foreach ( $event_posts as $event ) {
			$cafe_id = absint( get_post_meta( $event->ID, 'mtn_cafe_id', true ) );
			$cafe = get_post( $cafe_id );
			if ( ! $cafe || 'mtn_cafe' !== $cafe->post_type || 'publish' !== $cafe->post_status ) {
				continue;
			}
			$events[] = array(
				'id' => (int) $event->ID,
				'name' => get_the_title( $event ),
				'cafe_id' => $cafe_id,
				'cafe_name' => get_the_title( $cafe ),
				'url' => get_permalink( $event ),
				'description' => wp_strip_all_tags( get_the_excerpt( $event ) ),
				'min_party_size' => absint( get_post_meta( $event->ID, 'mtn_min_party_size', true ) ),
				'max_party_size' => absint( get_post_meta( $event->ID, 'mtn_max_party_size', true ) ) ?: 100,
				'event_date' => sanitize_text_field( get_post_meta( $event->ID, 'mtn_event_date', true ) ),
				'event_start_time' => sanitize_text_field( get_post_meta( $event->ID, 'mtn_event_start_time', true ) ),
				'event_end_time' => sanitize_text_field( get_post_meta( $event->ID, 'mtn_event_end_time', true ) ),
			);
		}
		return rest_ensure_response( array( 'cafes' => $cafes, 'events' => $events, 'timezone' => wp_timezone_string(), 'booking_mode' => get_option( 'mtn_cb_booking_mode', 'customer_selects_table' ) ) );
	}

	public static function events_search( WP_REST_Request $request ) {
		global $wpdb;
		$page = max( 1, absint( $request->get_param( 'page' ) ) );
		$per_page = min( 24, max( 1, absint( $request->get_param( 'per_page' ) ) ?: 12 ) );
		$search = sanitize_text_field( (string) $request->get_param( 'q' ) );
		$city_filter = sanitize_text_field( (string) $request->get_param( 'city' ) );
		$genre_filter = sanitize_text_field( (string) $request->get_param( 'genre' ) );
		$type_filter = sanitize_text_field( (string) $request->get_param( 'venue_type' ) );
		$artist_filter = sanitize_text_field( (string) $request->get_param( 'artist' ) );
		$date_from = sanitize_text_field( (string) $request->get_param( 'date_from' ) );
		$date_to = sanitize_text_field( (string) $request->get_param( 'date_to' ) );
		$min_price = null !== $request->get_param( 'min_price' ) && '' !== $request->get_param( 'min_price' ) ? max( 0, (float) $request->get_param( 'min_price' ) ) : null;
		$max_price = null !== $request->get_param( 'max_price' ) && '' !== $request->get_param( 'max_price' ) ? max( 0, (float) $request->get_param( 'max_price' ) ) : null;
		$lat_raw = $request->get_param( 'lat' );
		$lng_raw = $request->get_param( 'lng' );
		$lat = is_numeric( $lat_raw ) && (float) $lat_raw >= -90 && (float) $lat_raw <= 90 ? (float) $lat_raw : null;
		$lng = is_numeric( $lng_raw ) && (float) $lng_raw >= -180 && (float) $lng_raw <= 180 ? (float) $lng_raw : null;
		$sort = sanitize_key( (string) $request->get_param( 'sort' ) );
		$posts = get_posts( array(
			'post_type' => 'mtn_event', 'post_status' => 'publish', 'posts_per_page' => 500,
			'meta_key' => 'mtn_event_date', 'orderby' => 'meta_value', 'order' => 'ASC',
			'meta_query' => array( 'relation' => 'AND',
				array( 'key' => 'mtn_event_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ),
				array( 'key' => 'mtn_event_start_time', 'value' => '', 'compare' => '!=' ),
			),
		) );
		$popular_counts = array();
		if ( 'popular' === $sort ) {
			$count_rows = $wpdb->get_results( "SELECT event_id, COUNT(*) AS total FROM " . MTN_CB_Bookings::table_name() . " WHERE status IN ('pending_cafe','confirmed','completed','no_show') GROUP BY event_id" );
			foreach ( $count_rows as $count_row ) $popular_counts[ absint( $count_row->event_id ) ] = (int) $count_row->total;
		}
		$items = array();
		foreach ( $posts as $event ) {
			$cafe_id = absint( get_post_meta( $event->ID, 'mtn_cafe_id', true ) );
			$cafe = get_post( $cafe_id );
			if ( ! $cafe || 'mtn_cafe' !== $cafe->post_type || 'publish' !== $cafe->post_status ) continue;
			$artist = sanitize_text_field( get_post_meta( $event->ID, 'mtn_event_artist', true ) );
			$city = sanitize_text_field( get_post_meta( $cafe_id, 'mtn_cafe_city', true ) );
			$address = sanitize_text_field( get_post_meta( $cafe_id, 'mtn_cafe_address', true ) );
			$location_terms = self::cafe_location_path( $cafe_id );
			$location_search = is_array( $location_terms ) ? implode( ' ', $location_terms ) : (string) $location_terms;
			$genre = sanitize_text_field( get_post_meta( $cafe_id, 'mtn_cafe_music_genre', true ) );
			$venue_type = sanitize_text_field( get_post_meta( $cafe_id, 'mtn_cafe_type', true ) );
			$price = (float) get_post_meta( $event->ID, 'mtn_event_price', true );
			$date = sanitize_text_field( get_post_meta( $event->ID, 'mtn_event_date', true ) );
			if ( $search && false === stripos( get_the_title( $event ) . ' ' . get_the_title( $cafe ) . ' ' . $artist . ' ' . $city . ' ' . $address . ' ' . $location_search, $search ) ) continue;
			if ( $city_filter && false === stripos( $city, $city_filter ) ) continue;
			if ( $genre_filter && false === stripos( $genre, $genre_filter ) ) continue;
			if ( $type_filter && $venue_type !== $type_filter ) continue;
			if ( $artist_filter && false === stripos( $artist, $artist_filter ) ) continue;
			if ( $date_from && $date < $date_from ) continue;
			if ( $date_to && $date > $date_to ) continue;
			if ( null !== $min_price && $price < $min_price ) continue;
			if ( null !== $max_price && $price > $max_price ) continue;
			$clat = get_post_meta( $cafe_id, 'mtn_cafe_latitude', true );
			$clng = get_post_meta( $cafe_id, 'mtn_cafe_longitude', true );
			$distance = null;
			if ( null !== $lat && null !== $lng && is_numeric( $clat ) && is_numeric( $clng ) ) {
				$rad = M_PI / 180;
				$a = sin( ( ( (float) $clat - $lat ) * $rad ) / 2 ) ** 2 + cos( $lat * $rad ) * cos( (float) $clat * $rad ) * sin( ( ( (float) $clng - $lng ) * $rad ) / 2 ) ** 2;
				$distance = 6371 * 2 * atan2( sqrt( $a ), sqrt( max( 0, 1 - $a ) ) );
			}
			$booking_count = isset( $popular_counts[ $event->ID ] ) ? $popular_counts[ $event->ID ] : 0;
			$items[] = array(
				'id' => (int) $event->ID, 'name' => get_the_title( $event ), 'url' => get_permalink( $event ),
				'description' => wp_strip_all_tags( get_the_excerpt( $event ) ), 'cafe_id' => $cafe_id, 'cafe_name' => get_the_title( $cafe ),
				'city' => $city, 'address' => $address, 'location_path' => $location_terms, 'genre' => $genre, 'venue_type' => $venue_type, 'artist' => $artist, 'price' => $price,
				'event_date' => $date, 'event_start_time' => get_post_meta( $event->ID, 'mtn_event_start_time', true ),
				'image' => get_the_post_thumbnail_url( $event, 'large' ) ?: '', 'latitude' => is_numeric( $clat ) ? (float) $clat : null,
				'longitude' => is_numeric( $clng ) ? (float) $clng : null, 'distance_km' => null === $distance ? null : round( $distance, 1 ),
				'booking_count' => $booking_count,
			);
		}
		if ( 'popular' === $sort ) usort( $items, static function ( $a, $b ) { return $b['booking_count'] <=> $a['booking_count']; } );
		elseif ( 'distance' === $sort && null !== $lat && null !== $lng ) usort( $items, static function ( $a, $b ) { return ( $a['distance_km'] ?? PHP_FLOAT_MAX ) <=> ( $b['distance_km'] ?? PHP_FLOAT_MAX ); } );
		$total = count( $items );
		$items = array_slice( $items, ( $page - 1 ) * $per_page, $per_page );
		return rest_ensure_response( array( 'items' => $items, 'page' => $page, 'per_page' => $per_page, 'total' => $total, 'has_more' => $page * $per_page < $total ) );
	}

	public static function availability( WP_REST_Request $request ) {
		$result = MTN_CB_Bookings::get_availability( $request->get_params() );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public static function my_favorites() {
		$ids = get_user_meta( get_current_user_id(), 'mtn_cb_favorite_event_ids', true );
		return rest_ensure_response( is_array( $ids ) ? array_values( array_filter( array_map( 'absint', $ids ) ) ) : array() );
	}

	public static function add_favorite( WP_REST_Request $request ) {
		$event_id = absint( $request['event_id'] );
		$event = get_post( $event_id );
		if ( ! $event || 'mtn_event' !== $event->post_type || 'publish' !== $event->post_status ) return new WP_Error( 'mtn_event_not_found', __( 'برنامه پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		$ids = get_user_meta( get_current_user_id(), 'mtn_cb_favorite_event_ids', true );
		$ids = is_array( $ids ) ? array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) ) : array();
		if ( ! in_array( $event_id, $ids, true ) ) { $ids[] = $event_id; update_user_meta( get_current_user_id(), 'mtn_cb_favorite_event_ids', $ids ); }
		return rest_ensure_response( array( 'event_id' => $event_id, 'saved' => true ) );
	}

	public static function remove_favorite( WP_REST_Request $request ) {
		$event_id = absint( $request['event_id'] );
		$ids = get_user_meta( get_current_user_id(), 'mtn_cb_favorite_event_ids', true );
		$ids = is_array( $ids ) ? array_values( array_filter( array_map( 'absint', $ids ) ) ) : array();
		$ids = array_values( array_diff( $ids, array( $event_id ) ) );
		update_user_meta( get_current_user_id(), 'mtn_cb_favorite_event_ids', $ids );
		return rest_ensure_response( array( 'event_id' => $event_id, 'saved' => false ) );
	}

	public static function my_bookings() {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . MTN_CB_Bookings::table_name() . ' WHERE user_id = %d ORDER BY created_at DESC LIMIT 100', get_current_user_id() ) );
		return rest_ensure_response( array_map( static function ( $booking ) { return self::public_booking( $booking, true ); }, $rows ) );
	}

	public static function create_booking( WP_REST_Request $request ) {
		$result = MTN_CB_Bookings::create( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new WP_REST_Response( self::public_booking( $result, true ), 201 );
	}

	public static function get_booking( WP_REST_Request $request ) {
		$booking = MTN_CB_Bookings::get_for_user( $request['id'], get_current_user_id() );
		$is_customer = (bool) $booking;
		if ( ! $booking && current_user_can( 'manage_options' ) ) {
			$booking = MTN_CB_Bookings::get_by_id( $request['id'] );
		}
		if ( ! $booking ) {
			return new WP_Error( 'mtn_booking_not_found', __( 'رزرو پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( self::public_booking( $booking, $is_customer ) );
	}

	public static function booking_history( WP_REST_Request $request ) {
		$booking = MTN_CB_Bookings::get_by_id( $request['id'] );
		if ( ! $booking ) {
			return new WP_Error( 'mtn_booking_not_found', __( 'رزرو پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		$is_customer = (int) $booking->user_id === get_current_user_id();
		if ( ! $is_customer && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'mtn_booking_not_found', __( 'رزرو پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		$events = MTN_CB_Bookings::get_history( $booking->id );
		return rest_ensure_response( array_map( static function ( $event ) {
			return array(
				'event_type' => $event->event_type,
				'from_status' => $event->from_status,
				'to_status' => $event->to_status,
				'details' => $event->details,
				'created_at' => $event->created_at,
			);
		}, $events ) );
	}

	public static function cancel_booking( WP_REST_Request $request ) {
		$booking = MTN_CB_Bookings::get_for_user( $request['id'], get_current_user_id() );
		if ( ! $booking ) {
			return new WP_Error( 'mtn_booking_not_found', __( 'رزرو پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		$result = MTN_CB_Bookings::transition( $booking->id, 'cancelled_by_customer', get_current_user_id(), 'customer_cancelled' );
		return is_wp_error( $result ) ? $result : rest_ensure_response( self::public_booking( $result, true ) );
	}

	public static function confirm_booking( WP_REST_Request $request ) {
		$booking = MTN_CB_Bookings::get_by_id( $request['id'] );
		if ( ! $booking ) {
			return new WP_Error( 'mtn_booking_not_found', __( 'رزرو پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		$result = MTN_CB_Bookings::transition( $booking->id, 'confirmed', get_current_user_id(), 'confirmed_by_cafe_admin' );
		return is_wp_error( $result ) ? $result : rest_ensure_response( self::public_booking( $result ) );
	}

	public static function complete_booking( WP_REST_Request $request ) {
		$booking = MTN_CB_Bookings::transition( $request['id'], 'completed', get_current_user_id(), 'marked_completed_by_cafe' );
		return is_wp_error( $booking ) ? $booking : rest_ensure_response( self::public_booking( $booking ) );
	}

	public static function mark_no_show( WP_REST_Request $request ) {
		$booking = MTN_CB_Bookings::transition( $request['id'], 'no_show', get_current_user_id(), 'marked_no_show_by_cafe' );
		return is_wp_error( $booking ) ? $booking : rest_ensure_response( self::public_booking( $booking ) );
	}

	public static function assign_table( WP_REST_Request $request ) {
		$result = MTN_CB_Bookings::assign_table( $request['id'], $request->get_param( 'table_id' ), get_current_user_id() );
		return is_wp_error( $result ) ? $result : rest_ensure_response( self::public_booking( $result ) );
	}

	public static function cancel_by_cafe( WP_REST_Request $request ) {
		$booking = MTN_CB_Bookings::get_by_id( $request['id'] );
		if ( ! $booking ) {
			return new WP_Error( 'mtn_booking_not_found', __( 'رزرو پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		$reason = $request->get_param( 'reason' );
		$result = MTN_CB_Bookings::transition( $booking->id, 'cancelled_by_cafe', get_current_user_id(), is_string( $reason ) ? $reason : '' );
		return is_wp_error( $result ) ? $result : rest_ensure_response( self::public_booking( $result ) );
	}

	public static function reject_booking( WP_REST_Request $request ) {
		$booking = MTN_CB_Bookings::get_by_id( $request['id'] );
		if ( ! $booking ) {
			return new WP_Error( 'mtn_booking_not_found', __( 'رزرو پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		$reason = $request->get_param( 'reason' );
		$result = MTN_CB_Bookings::transition( $booking->id, 'rejected', get_current_user_id(), is_string( $reason ) ? $reason : '' );
		return is_wp_error( $result ) ? $result : rest_ensure_response( self::public_booking( $result ) );
	}

	public static function issue_checkin_token( WP_REST_Request $request ) {
		$result = MTN_CB_Bookings::issue_checkin_token( absint( $request['id'] ), get_current_user_id() );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public static function check_in_customer( WP_REST_Request $request ) {
		$result = MTN_CB_Bookings::check_in_with_token( $request->get_param( 'token' ), get_current_user_id() );
		return is_wp_error( $result ) ? $result : rest_ensure_response( self::public_booking( $result ) );
	}

	private static function public_booking( $booking, $include_whatsapp = false ) {
		$whatsapp_url = '';
		if ( $include_whatsapp ) {
			$whatsapp_link = MTN_CB_WhatsApp::manual_link( $booking, get_post_meta( $booking->cafe_id, 'mtn_cafe_whatsapp', true ) );
			$whatsapp_url = is_wp_error( $whatsapp_link ) ? '' : $whatsapp_link;
		}
		return array(
			'id'                     => (int) $booking->id,
			'booking_code'           => $booking->booking_code,
			'cafe_id'                => (int) $booking->cafe_id,
			'event_id'               => (int) $booking->event_id,
			'table_id'               => (int) $booking->table_id,
			'party_size'             => (int) $booking->party_size,
			'booking_at'             => $booking->booking_at,
			'status'                 => $booking->status,
			'deposit_amount'         => isset( $booking->deposit_amount ) ? (float) $booking->deposit_amount : 100.00,
			'deposit_currency'       => isset( $booking->deposit_currency ) ? $booking->deposit_currency : 'TRY',
			'deposit_status'         => isset( $booking->deposit_status ) ? $booking->deposit_status : 'awaiting_transfer',
			'deposit_reference'      => isset( $booking->deposit_reference ) ? $booking->deposit_reference : '',
			'bank_transfer'          => $include_whatsapp ? array(
				'account_name' => sanitize_text_field( get_option( 'mtn_cb_bank_account_name', '' ) ),
				'iban' => sanitize_text_field( get_option( 'mtn_cb_bank_iban', '' ) ),
				'instructions' => sanitize_textarea_field( get_option( 'mtn_cb_bank_instructions', '' ) ),
				'non_refundable' => true,
				'cancellation_window_hours' => 3,
			) : null,
			'whatsapp_action_status' => $booking->whatsapp_action_status,
			'whatsapp_url'            => $whatsapp_url,
			'checkin_status'          => $booking->checkin_status,
			'created_at'              => $booking->created_at,
		);
	}
}
