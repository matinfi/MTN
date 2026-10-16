<?php
/**
 * Expiring cafe team invitations.
 *
 * @package MTN_Cafe_Booking
 */
defined( 'ABSPATH' ) || exit;

final class MTN_CB_Team_Invites {
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'admin_post_mtn_cb_accept_invite', array( __CLASS__, 'accept_from_form' ) );
		add_shortcode( 'mtn_cafe_invite_accept', array( __CLASS__, 'accept_form' ) );
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'mtn_cafe_invites';
	}

	public static function register_routes() {
		register_rest_route( 'mtn/v1', '/admin/cafes/(?P<cafe_id>\d+)/invite', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'create_invite' ),
			'permission_callback' => static function () {
				return current_user_can( 'manage_options' ) ? true : new WP_Error( 'mtn_invite_forbidden', __( 'فقط مدیر سامانه می‌تواند دعوت ارسال کند.', 'mtn-cafe-booking' ), array( 'status' => 403 ) );
			},
			'args' => array( 'cafe_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ), 'email' => array( 'required' => true, 'sanitize_callback' => 'sanitize_email' ) ),
		) );
	}

	public static function create_invite( $request ) {
		global $wpdb;
		$cafe_id = absint( $request['cafe_id'] );
		$email = sanitize_email( $request['email'] );
		$cafe = get_post( $cafe_id );
		if ( ! $cafe || 'mtn_cafe' !== $cafe->post_type || 'publish' !== $cafe->post_status || ! is_email( $email ) ) {
			return new WP_Error( 'mtn_invite_invalid', __( 'کافه یا ایمیل معتبر نیست.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}
		$user = get_user_by( 'email', $email );
		if ( $user && MTN_CB_Content::user_can_manage_cafe( $cafe_id, $user->ID ) ) {
			return new WP_Error( 'mtn_invite_already_member', __( 'این کاربر هم‌اکنون عضو تیم کافه است.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
		}
		$token = bin2hex( random_bytes( 32 ) );
		$expires = gmdate( 'Y-m-d H:i:s', time() + ( 2 * DAY_IN_SECONDS ) );
		$wpdb->delete( self::table_name(), array( 'cafe_id' => $cafe_id, 'email' => $email, 'status' => 'pending' ), array( '%d', '%s', '%s' ) );
		$inserted = $wpdb->insert( self::table_name(), array(
			'cafe_id' => $cafe_id,
			'email' => $email,
			'token_hash' => hash( 'sha256', $token ),
			'invited_by' => get_current_user_id(),
			'status' => 'pending',
			'expires_at' => $expires,
			'created_at' => current_time( 'mysql', true ),
		), array( '%d', '%s', '%s', '%d', '%s', '%s', '%s' ) );
		if ( false === $inserted ) {
			return new WP_Error( 'mtn_invite_save_failed', __( 'ذخیره دعوت انجام نشد.', 'mtn-cafe-booking' ), array( 'status' => 500 ) );
		}
		$account_page = get_page_by_path( 'my-account' );
		$target = $account_page ? get_permalink( $account_page ) : home_url( '/' );
		$accept_url = add_query_arg( 'mtn_invite', rawurlencode( $token ), $target );
		$login_url = wp_login_url( $accept_url );
		$sent = wp_mail( $email, sprintf( 'دعوت عضویت در تیم %s', $cafe->post_title ), sprintf( "برای پذیرش دعوت عضویت در تیم کافه «%s» وارد حساب کاربری‌ای شوید که ایمیل آن %s است و پیوند زیر را باز کنید. این دعوت تا %s UTC اعتبار دارد.\n\n%s", $cafe->post_title, $email, $expires, $login_url ) );
		if ( ! $sent ) {
			$wpdb->update( self::table_name(), array( 'status' => 'mail_failed' ), array( 'id' => absint( $wpdb->insert_id ) ), array( '%s' ), array( '%d' ) );
			return new WP_Error( 'mtn_invite_mail_failed', __( 'دعوت ذخیره شد اما ارسال ایمیل شکست خورد؛ ارسال ایمیل سایت را بررسی کنید.', 'mtn-cafe-booking' ), array( 'status' => 502 ) );
		}
		return rest_ensure_response( array( 'status' => 'sent', 'expires_at' => $expires . ' UTC', 'email' => $email ) );
	}

	public static function accept_form() {
		$token = isset( $_GET['mtn_invite'] ) && is_scalar( $_GET['mtn_invite'] ) ? sanitize_text_field( wp_unslash( $_GET['mtn_invite'] ) ) : '';
		if ( ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
			return '<p>' . esc_html__( 'پیوند دعوت معتبر نیست یا وجود ندارد.', 'mtn-cafe-booking' ) . '</p>';
		}
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'برای پذیرش دعوت ابتدا وارد حسابی شوید که ایمیل دعوت به آن ارسال شده است.', 'mtn-cafe-booking' ) . ' <a href="' . esc_url( wp_login_url( add_query_arg( 'mtn_invite', rawurlencode( $token ), get_permalink() ) ) ) . '">' . esc_html__( 'ورود', 'mtn-cafe-booking' ) . '</a></p>';
		}
		return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="mtn_cb_accept_invite"><input type="hidden" name="token" value="' . esc_attr( $token ) . '">' . wp_nonce_field( 'mtn_cb_accept_invite', '_wpnonce', true, false ) . '<p>' . esc_html__( 'با پذیرش دعوت، حساب شما به تیم کافه افزوده می‌شود.', 'mtn-cafe-booking' ) . '</p><button class="button" type="submit">' . esc_html__( 'پذیرش دعوت', 'mtn-cafe-booking' ) . '</button></form>';
	}

	public static function accept_from_form() {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		check_admin_referer( 'mtn_cb_accept_invite' );
		$token = isset( $_POST['token'] ) && is_scalar( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		if ( ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
			wp_die( esc_html__( 'پیوند دعوت معتبر نیست.', 'mtn-cafe-booking' ), '', array( 'response' => 400 ) );
		}
		global $wpdb;
		$invite = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE token_hash = %s LIMIT 1', hash( 'sha256', $token ) ) );
		$user = wp_get_current_user();
		$now = current_time( 'mysql', true );
		if ( ! $invite || 'pending' !== $invite->status || $invite->expires_at < $now || ! is_email( $user->user_email ) || strtolower( $user->user_email ) !== strtolower( $invite->email ) ) {
			wp_die( esc_html__( 'دعوت منقضی شده، قبلاً استفاده شده یا با ایمیل حساب شما مطابقت ندارد.', 'mtn-cafe-booking' ), '', array( 'response' => 403 ) );
		}
		$lock = 'mtn_cb_invite_' . absint( $invite->id );
		if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
			wp_die( esc_html__( 'سامانه مشغول است؛ دوباره تلاش کنید.', 'mtn-cafe-booking' ), '', array( 'response' => 503 ) );
		}
		try {
			$current = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE id = %d', absint( $invite->id ) ) );
			if ( ! $current || 'pending' !== $current->status || $current->expires_at < $now ) {
				wp_die( esc_html__( 'دعوت دیگر قابل استفاده نیست.', 'mtn-cafe-booking' ), '', array( 'response' => 409 ) );
			}
			$ids = array_map( 'absint', (array) get_post_meta( absint( $current->cafe_id ), 'mtn_cafe_manager_ids', true ) );
			if ( ! in_array( absint( $user->ID ), $ids, true ) ) {
				$ids[] = absint( $user->ID );
				update_post_meta( absint( $current->cafe_id ), 'mtn_cafe_manager_ids', array_values( array_unique( $ids ) ) );
			}
			$user->add_role( 'mtn_cafe_manager' );
			$updated = $wpdb->update( self::table_name(), array( 'status' => 'accepted', 'accepted_user_id' => absint( $user->ID ), 'accepted_at' => $now ), array( 'id' => absint( $current->id ), 'status' => 'pending' ), array( '%s', '%d', '%s' ), array( '%d', '%s' ) );
			if ( 1 !== $updated ) {
				wp_die( esc_html__( 'دعوت هم‌زمان تغییر کرده است.', 'mtn-cafe-booking' ), '', array( 'response' => 409 ) );
			}
			MTN_CB_Bookings::record_event( 0, $user->ID, 'team_invite_accepted', '', '', 'cafe_id=' . absint( $current->cafe_id ) );
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
		wp_safe_redirect( add_query_arg( 'mtn_invite', 'accepted', home_url( '/cafe-dashboard/' ) ) );
		exit;
	}
}
