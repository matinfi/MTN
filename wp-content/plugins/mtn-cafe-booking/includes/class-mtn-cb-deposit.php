<?php
/**
 * Manual, bank-transfer deposit workflow for v1 (no payment gateway).
 *
 * @package MTN_Cafe_Booking
 */

defined( 'ABSPATH' ) || exit;

final class MTN_CB_Deposit {
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'admin_post_mtn_cb_deposit_action', array( __CLASS__, 'admin_action' ) );
	}

	public static function register_routes() {
		register_rest_route( 'mtn/v1', '/bookings/(?P<id>\\d+)/deposit-reference', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'submit_reference' ),
			'permission_callback' => static function () {
				return is_user_logged_in() ? true : new WP_Error( 'mtn_auth_required', __( 'برای ثبت رسید انتقال وجه وارد حساب شوید.', 'mtn-cafe-booking' ), array( 'status' => 401 ) );
			},
			'args' => array( 'reference' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ) ),
		) );
	}

	public static function submit_reference( WP_REST_Request $request ) {
		global $wpdb;
		$id = absint( $request['id'] );
		$reference = trim( (string) $request->get_param( 'reference' ) );
		if ( strlen( $reference ) < 4 || strlen( $reference ) > 100 ) {
			return new WP_Error( 'mtn_invalid_transfer_reference', __( 'شماره پیگیری انتقال باید بین ۴ تا ۱۰۰ نویسه باشد.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}
		$booking = MTN_CB_Bookings::get_for_user( $id, get_current_user_id() );
		if ( ! $booking ) {
			return new WP_Error( 'mtn_booking_not_found', __( 'رزرو پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		if ( ! in_array( $booking->status, array( 'pending_cafe', 'confirmed' ), true ) || in_array( $booking->deposit_status, array( 'verified', 'under_review' ), true ) ) {
			return new WP_Error( 'mtn_deposit_state', __( 'برای این رزرو امکان ثبت یا تغییر شماره پیگیری وجود ندارد.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
		}
		$updated = $wpdb->update( MTN_CB_Bookings::table_name(), array(
			'deposit_status' => 'under_review',
			'deposit_reference' => $reference,
			'updated_at' => current_time( 'mysql', true ),
		), array( 'id' => $id, 'user_id' => get_current_user_id() ), array( '%s', '%s', '%s' ), array( '%d', '%d' ) );
		if ( false === $updated || 1 !== $updated ) {
			return new WP_Error( 'mtn_deposit_update_failed', __( 'ثبت شماره پیگیری انجام نشد؛ دوباره تلاش کنید.', 'mtn-cafe-booking' ), array( 'status' => 500 ) );
		}
		MTN_CB_Bookings::record_event( $id, get_current_user_id(), 'deposit_reference_submitted', $booking->deposit_status, 'under_review', 'Customer submitted bank transfer reference; manual bank reconciliation required.' );
		return rest_ensure_response( array( 'booking_id' => $id, 'deposit_status' => 'under_review', 'message' => __( 'شماره پیگیری ثبت شد. تا بررسی گردش حساب توسط مدیر سایت، پرداخت تأییدشده محسوب نمی‌شود.', 'mtn-cafe-booking' ) ) );
	}

	public static function admin_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی کافی ندارید.', 'mtn-cafe-booking' ), '', array( 'response' => 403 ) );
		}
		$id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
		check_admin_referer( 'mtn_cb_deposit_' . $id );
		$decision = isset( $_POST['deposit_decision'] ) ? sanitize_key( wp_unslash( $_POST['deposit_decision'] ) ) : '';
		if ( ! $id || ! in_array( $decision, array( 'verified', 'rejected' ), true ) ) {
			self::redirect( 'invalid' );
		}
		global $wpdb;
		$booking = MTN_CB_Bookings::get_by_id( $id );
		if ( ! $booking || 'under_review' !== $booking->deposit_status ) {
			self::redirect( 'failed' );
		}
		$data = array(
			'deposit_status' => $decision,
			'deposit_verified_at' => 'verified' === $decision ? current_time( 'mysql', true ) : null,
			'updated_at' => current_time( 'mysql', true ),
		);
		$updated = $wpdb->update( MTN_CB_Bookings::table_name(), $data, array( 'id' => $id, 'deposit_status' => 'under_review' ), array( '%s', '%s', '%s' ), array( '%d', '%s' ) );
		if ( 1 !== $updated ) {
			self::redirect( 'failed' );
		}
		MTN_CB_Bookings::record_event( $id, get_current_user_id(), 'deposit_' . $decision, 'under_review', $decision, 'Manual bank statement reconciliation by site administrator.' );
		self::redirect( 'updated' );
	}

	private static function redirect( $notice ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mtn-cb-bookings', 'mtn_notice' => 'deposit_' . $notice ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
