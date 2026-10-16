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
			'/cafe/bookings/(?P<id>\\d+)/cancel',
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
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		return current_user_can( 'mtn_manage_cafes' ) ? true : new WP_Error( 'mtn_forbidden', __( 'دسترسی مدیر کافه لازم است.', 'mtn-cafe-booking' ), array( 'status' => 403 ) );
	}

	public static function can_manage_booking_request( $request ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		if ( ! current_user_can( 'mtn_manage_cafes' ) || ! ( $request instanceof WP_REST_Request ) ) {
			return new WP_Error( 'mtn_forbidden', __( 'دسترسی مدیر کافه لازم است.', 'mtn-cafe-booking' ), array( 'status' => 403 ) );
		}
		$booking = MTN_CB_Bookings::get_by_id( $request['id'] );
		if ( ! $booking || absint( get_post_meta( $booking->cafe_id, 'mtn_cafe_owner_id', true ) ) !== get_current_user_id() ) {
			return new WP_Error( 'mtn_forbidden', __( 'این رزرو متعلق به کافه تحت مدیریت شما نیست.', 'mtn-cafe-booking' ), array( 'status' => 403 ) );
		}
		return true;
	}

	public static function cafe_bookings() {
		global $wpdb;
		$table = MTN_CB_Bookings::table_name();
		$sql = 'SELECT b.*, cafe.post_title AS cafe_title, event.post_title AS event_title FROM ' . $table . ' b LEFT JOIN ' . $wpdb->posts . ' cafe ON cafe.ID = b.cafe_id LEFT JOIN ' . $wpdb->posts . ' event ON event.ID = b.event_id';
		if ( ! current_user_can( 'manage_options' ) ) {
			$sql .= $wpdb->prepare( ' INNER JOIN ' . $wpdb->postmeta . " owner_meta ON owner_meta.post_id = b.cafe_id AND owner_meta.meta_key = 'mtn_cafe_owner_id' AND owner_meta.meta_value = %s", (string) get_current_user_id() );
		}
		$sql .= ' ORDER BY b.created_at DESC LIMIT 200';
		$rows = $wpdb->get_results( $sql );
		return rest_ensure_response( array_map( static function ( $booking ) {
			$data = self::public_booking( $booking );
			$data['cafe_name'] = $booking->cafe_title ? $booking->cafe_title : '';
			$data['event_name'] = $booking->event_title ? $booking->event_title : '';
			return $data;
		}, $rows ) );
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
			$cafes[] = array( 'id' => (int) $cafe->ID, 'name' => get_the_title( $cafe ), 'url' => get_permalink( $cafe ), 'description' => wp_strip_all_tags( get_the_excerpt( $cafe ) ) );
		}
		$event_posts = get_posts( array(
			'post_type' => 'mtn_event',
			'post_status' => 'publish',
			'posts_per_page' => 200,
			'orderby' => 'date',
			'order' => 'DESC',
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

	public static function availability( WP_REST_Request $request ) {
		$result = MTN_CB_Bookings::get_availability( $request->get_params() );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public static function my_bookings() {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . MTN_CB_Bookings::table_name() . ' WHERE user_id = %d ORDER BY created_at DESC LIMIT 100', get_current_user_id() ) );
		return rest_ensure_response( array_map( array( __CLASS__, 'public_booking' ), $rows ) );
	}

	public static function create_booking( WP_REST_Request $request ) {
		$result = MTN_CB_Bookings::create( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new WP_REST_Response( self::public_booking( $result ), 201 );
	}

	public static function get_booking( WP_REST_Request $request ) {
		$booking = MTN_CB_Bookings::get_for_user( $request['id'], get_current_user_id() );
		if ( ! $booking && current_user_can( 'manage_options' ) ) {
			$booking = MTN_CB_Bookings::get_by_id( $request['id'] );
		}
		if ( ! $booking ) {
			return new WP_Error( 'mtn_booking_not_found', __( 'رزرو پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( self::public_booking( $booking ) );
	}

	public static function cancel_booking( WP_REST_Request $request ) {
		$booking = MTN_CB_Bookings::get_for_user( $request['id'], get_current_user_id() );
		if ( ! $booking ) {
			return new WP_Error( 'mtn_booking_not_found', __( 'رزرو پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		$result = MTN_CB_Bookings::transition( $booking->id, 'cancelled_by_customer', get_current_user_id(), 'customer_cancelled' );
		return is_wp_error( $result ) ? $result : rest_ensure_response( self::public_booking( $result ) );
	}

	public static function confirm_booking( WP_REST_Request $request ) {
		$booking = MTN_CB_Bookings::get_by_id( $request['id'] );
		if ( ! $booking ) {
			return new WP_Error( 'mtn_booking_not_found', __( 'رزرو پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		$result = MTN_CB_Bookings::transition( $booking->id, 'confirmed', get_current_user_id(), 'confirmed_by_cafe_admin' );
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

	private static function public_booking( $booking ) {
		return array(
			'id'                     => (int) $booking->id,
			'booking_code'           => $booking->booking_code,
			'cafe_id'                => (int) $booking->cafe_id,
			'event_id'               => (int) $booking->event_id,
			'table_id'               => (int) $booking->table_id,
			'party_size'             => (int) $booking->party_size,
			'booking_at'             => $booking->booking_at,
			'status'                 => $booking->status,
			'whatsapp_action_status' => $booking->whatsapp_action_status,
			'checkin_status'          => $booking->checkin_status,
			'created_at'              => $booking->created_at,
		);
	}
}
