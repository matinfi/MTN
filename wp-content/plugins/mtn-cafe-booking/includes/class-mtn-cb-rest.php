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
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' ) || current_user_can( 'mtn_manage_cafes' );
				},
				'args'                => array( 'id' => array( 'sanitize_callback' => 'absint' ) ),
			)
		);

		register_rest_route(
			self::NS,
			'/cafe/bookings/(?P<id>\d+)/reject',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'reject_booking' ),
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' ) || current_user_can( 'mtn_manage_cafes' );
				},
				'args'                => array(
					'id'     => array( 'sanitize_callback' => 'absint' ),
					'reason' => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
				),
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
		// Until cafe ownership records are implemented, only administrators may confirm.
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'mtn_forbidden', __( 'مدیریت کافه برای این حساب هنوز پیکربندی نشده است.', 'mtn-cafe-booking' ), array( 'status' => 403 ) );
		}
		$result = MTN_CB_Bookings::transition( $booking->id, 'confirmed', get_current_user_id(), 'confirmed_by_cafe_admin' );
		return is_wp_error( $result ) ? $result : rest_ensure_response( self::public_booking( $result ) );
	}

	public static function reject_booking( WP_REST_Request $request ) {
		$booking = MTN_CB_Bookings::get_by_id( $request['id'] );
		if ( ! $booking ) {
			return new WP_Error( 'mtn_booking_not_found', __( 'رزرو پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		// Until cafe ownership records are implemented, only administrators may reject.
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'mtn_forbidden', __( 'مدیریت کافه برای این حساب هنوز پیکربندی نشده است.', 'mtn-cafe-booking' ), array( 'status' => 403 ) );
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
