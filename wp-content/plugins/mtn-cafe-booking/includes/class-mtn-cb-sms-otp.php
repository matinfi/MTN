<?php
/**
 * Twilio SMS one-time-password sign-in with expiry, resend limits and attempt throttling.
 * Configure MTN_CB_TWILIO_ACCOUNT_SID, MTN_CB_TWILIO_AUTH_TOKEN and MTN_CB_TWILIO_FROM in wp-config.php.
 *
 * @package MTN_Cafe_Booking
 */
defined( 'ABSPATH' ) || exit;

final class MTN_CB_SMS_OTP {
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_shortcode( 'mtn_sms_login', array( __CLASS__, 'login_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'show_user_profile', array( __CLASS__, 'render_phone_field' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'render_phone_field' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'save_phone_field' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_phone_field' ) );
	}

	public static function register_assets() {
		wp_register_script( 'mtn-cb-otp-login', MTN_CB_URL . 'assets/otp-login.js', array(), MTN_CB_VERSION, true );
	}

	public static function login_shortcode() {
		if ( is_user_logged_in() ) {
			return '';
		}
		wp_enqueue_script( 'mtn-cb-otp-login' );
		wp_localize_script( 'mtn-cb-otp-login', 'MTNOTP', array(
			'restUrl' => esc_url_raw( rest_url( 'mtn/v1/' ) ),
			'redirectUrl' => esc_url_raw( home_url( '/my-account/' ) ),
			'sending' => __( 'در حال درخواست کد پیامکی…', 'mtn-cafe-booking' ),
			'requested' => __( 'اگر شماره واجد شرایط باشد، پیامک ارسال می‌شود.', 'mtn-cafe-booking' ),
			'verifying' => __( 'در حال بررسی کد…', 'mtn-cafe-booking' ),
			'success' => __( 'ورود موفق بود؛ در حال انتقال…', 'mtn-cafe-booking' ),
			'error' => __( 'درخواست انجام نشد؛ کمی بعد دوباره تلاش کنید.', 'mtn-cafe-booking' ),
		) );
		ob_start();
		?>
		<section class="mtn-booking-panel" data-mtn-otp-login>
			<h2><?php esc_html_e( 'ورود با پیامک', 'mtn-cafe-booking' ); ?></h2>
			<form>
				<p><label for="mtn-otp-phone"><?php esc_html_e( 'شماره تلفن با کد کشور', 'mtn-cafe-booking' ); ?></label><input id="mtn-otp-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="+905xxxxxxxxx" pattern="\+[1-9][0-9]{7,14}" maxlength="16" required></p>
				<p><label for="mtn-otp-code"><?php esc_html_e( 'کد شش‌رقمی پیامک‌شده', 'mtn-cafe-booking' ); ?></label><input id="mtn-otp-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" hidden></p>
				<div class="mtn-booking-actions"><button class="button" type="button" data-otp-request><?php esc_html_e( 'ارسال کد', 'mtn-cafe-booking' ); ?></button><button class="button" type="submit" data-otp-verify hidden><?php esc_html_e( 'تأیید و ورود', 'mtn-cafe-booking' ); ?></button></div>
				<p role="status" aria-live="polite"></p>
			</form>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'mtn_sms_otps';
	}

	public static function register_routes() {
		register_rest_route( 'mtn/v1', '/auth/otp/request', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'request_code' ),
			'permission_callback' => '__return_true',
			'args' => array( 'phone' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ) ),
		) );
		register_rest_route( 'mtn/v1', '/auth/otp/verify', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'verify_code' ),
			'permission_callback' => '__return_true',
			'args' => array(
				'phone' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'code' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
			),
		) );
	}

	private static function valid_phone( $phone ) {
		return is_string( $phone ) && (bool) preg_match( '/^\+[1-9][0-9]{7,14}$/', $phone );
	}

	private static function provider_ready() {
		return defined( 'MTN_CB_TWILIO_ACCOUNT_SID' ) && defined( 'MTN_CB_TWILIO_AUTH_TOKEN' ) && defined( 'MTN_CB_TWILIO_FROM' )
			&& MTN_CB_TWILIO_ACCOUNT_SID && MTN_CB_TWILIO_AUTH_TOKEN && MTN_CB_TWILIO_FROM;
	}

	public static function request_code( $request ) {
		global $wpdb;
		$phone = sanitize_text_field( $request['phone'] );
		if ( ! self::valid_phone( $phone ) ) {
			return new WP_Error( 'mtn_otp_phone_invalid', __( 'شماره باید با قالب بین‌المللی مانند +905xxxxxxxxx وارد شود.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}
		if ( ! self::provider_ready() ) {
			return new WP_Error( 'mtn_otp_provider_unconfigured', __( 'سرویس پیامک پیکربندی نشده است.', 'mtn-cafe-booking' ), array( 'status' => 503 ) );
		}
		$now_ts = time();
		$now = gmdate( 'Y-m-d H:i:s', $now_ts );
		$window = gmdate( 'Y-m-d H:i:s', $now_ts - ( 15 * MINUTE_IN_SECONDS ) );
		$phone_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table_name() . ' WHERE phone = %s AND created_at >= %s', $phone, $window ) );
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ip_hash = hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
		$ip_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table_name() . ' WHERE ip_hash = %s AND created_at >= %s', $ip_hash, $window ) );
		$last = $wpdb->get_row( $wpdb->prepare( 'SELECT created_at FROM ' . self::table_name() . ' WHERE phone = %s ORDER BY id DESC LIMIT 1', $phone ) );
		if ( $phone_count >= 3 || $ip_count >= 10 || ( $last && strtotime( $last->created_at . ' UTC' ) > $now_ts - 60 ) ) {
			return new WP_Error( 'mtn_otp_rate_limited', __( 'درخواست پیامک بیش از حد مجاز است؛ کمی بعد دوباره تلاش کنید.', 'mtn-cafe-booking' ), array( 'status' => 429 ) );
		}
		$user_ids = get_users( array( 'meta_key' => 'mtn_cb_phone', 'meta_value' => $phone, 'number' => 1, 'fields' => 'ids' ) );
		$code = (string) random_int( 100000, 999999 );
		$inserted = $wpdb->insert( self::table_name(), array(
			'phone' => $phone,
			'code_hash' => hash_hmac( 'sha256', $phone . '|' . $code, wp_salt( 'auth' ) ),
			'ip_hash' => $ip_hash,
			'attempts' => 0,
			'status' => 'pending',
			'created_at' => $now,
			'expires_at' => gmdate( 'Y-m-d H:i:s', $now_ts + ( 5 * MINUTE_IN_SECONDS ) ),
			'consumed_at' => null,
		), array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' ) );
		if ( false === $inserted ) {
			return new WP_Error( 'mtn_otp_store_failed', __( 'ثبت درخواست پیامک انجام نشد.', 'mtn-cafe-booking' ), array( 'status' => 500 ) );
		}
		if ( empty( $user_ids ) ) {
			$wpdb->update( self::table_name(), array( 'status' => 'not_registered' ), array( 'id' => absint( $wpdb->insert_id ) ), array( '%s' ), array( '%d' ) );
			// Avoid account enumeration; do not send a code to an unregistered phone.
			return rest_ensure_response( array( 'status' => 'accepted', 'message' => __( 'اگر شماره واجد شرایط باشد، پیامک ارسال می‌شود.', 'mtn-cafe-booking' ) ) );
		}
		$url = sprintf( 'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', rawurlencode( MTN_CB_TWILIO_ACCOUNT_SID ) );
		$response = wp_remote_post( $url, array(
			'timeout' => 10,
			'headers' => array( 'Authorization' => 'Basic ' . base64_encode( MTN_CB_TWILIO_ACCOUNT_SID . ':' . MTN_CB_TWILIO_AUTH_TOKEN ) ),
			'body' => array( 'To' => $phone, 'From' => MTN_CB_TWILIO_FROM, 'Body' => sprintf( 'Your MTN verification code is %s. It expires in 5 minutes.', $code ) ),
		) );
		$success = ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) >= 200 && (int) wp_remote_retrieve_response_code( $response ) < 300;
		$wpdb->update( self::table_name(), array( 'status' => $success ? 'sent' : 'delivery_failed' ), array( 'id' => absint( $wpdb->insert_id ) ), array( '%s' ), array( '%d' ) );
		if ( ! $success ) {
			return rest_ensure_response( array( 'status' => 'accepted', 'message' => __( 'اگر شماره واجد شرایط باشد، پیامک ارسال می‌شود.', 'mtn-cafe-booking' ) ) );
		}
		return rest_ensure_response( array( 'status' => 'accepted', 'message' => __( 'اگر شماره واجد شرایط باشد، پیامک ارسال می‌شود.', 'mtn-cafe-booking' ) ) );
	}

	public static function verify_code( $request ) {
		global $wpdb;
		$phone = sanitize_text_field( $request['phone'] );
		$code = sanitize_text_field( $request['code'] );
		if ( ! self::valid_phone( $phone ) || ! preg_match( '/^[0-9]{6}$/', $code ) ) {
			return new WP_Error( 'mtn_otp_invalid', __( 'شماره یا کد تأیید معتبر نیست.', 'mtn-cafe-booking' ), array( 'status' => 400 ) );
		}
		$now = current_time( 'mysql', true );
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . " WHERE phone = %s AND status = 'sent' AND consumed_at IS NULL AND expires_at >= %s ORDER BY id DESC LIMIT 1", $phone, $now ) );
		if ( ! $row || absint( $row->attempts ) >= 5 ) {
			return new WP_Error( 'mtn_otp_expired', __( 'کد تأیید منقضی شده یا وجود ندارد.', 'mtn-cafe-booking' ), array( 'status' => 401 ) );
		}
		$attempts = absint( $row->attempts ) + 1;
		$matches = hash_equals( $row->code_hash, hash_hmac( 'sha256', $phone . '|' . $code, wp_salt( 'auth' ) ) );
		if ( ! $matches ) {
			$wpdb->update( self::table_name(), array( 'attempts' => $attempts, 'status' => $attempts >= 5 ? 'locked' : 'sent' ), array( 'id' => absint( $row->id ), 'status' => 'sent', 'consumed_at' => null ), array( '%d', '%s' ), array( '%d', '%s', '%s' ) );
			return new WP_Error( 'mtn_otp_wrong_code', __( 'کد تأیید صحیح نیست.', 'mtn-cafe-booking' ), array( 'status' => 401 ) );
		}
		$consumed = $wpdb->update( self::table_name(), array( 'status' => 'consumed', 'attempts' => $attempts, 'consumed_at' => $now ), array( 'id' => absint( $row->id ), 'status' => 'sent', 'consumed_at' => null ), array( '%s', '%d', '%s' ), array( '%d', '%s', '%s' ) );
		if ( 1 !== $consumed ) {
			return new WP_Error( 'mtn_otp_replayed', __( 'این کد قبلاً استفاده شده است.', 'mtn-cafe-booking' ), array( 'status' => 409 ) );
		}
		$user = get_users( array( 'meta_key' => 'mtn_cb_phone', 'meta_value' => $phone, 'number' => 1 ) );
		if ( empty( $user ) ) {
			return new WP_Error( 'mtn_otp_account_missing', __( 'برای این شماره حساب فعالی پیدا نشد.', 'mtn-cafe-booking' ), array( 'status' => 404 ) );
		}
		$user = $user[0];
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true, is_ssl() );
		do_action( 'wp_login', $user->user_login, $user );
		return rest_ensure_response( array( 'status' => 'verified', 'logged_in' => true, 'redirect' => home_url( '/my-account/' ) ) );
	}

	public static function render_phone_field( $user ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_nonce_field( 'mtn_cb_phone_' . absint( $user->ID ), 'mtn_cb_phone_nonce' );
		echo '<h2>' . esc_html__( 'شماره ورود با پیامک MTN', 'mtn-cafe-booking' ) . '</h2><table class="form-table"><tr><th><label for="mtn_cb_phone">' . esc_html__( 'شماره بین‌المللی', 'mtn-cafe-booking' ) . '</label></th><td><input class="regular-text" id="mtn_cb_phone" name="mtn_cb_phone" value="' . esc_attr( get_user_meta( $user->ID, 'mtn_cb_phone', true ) ) . '" placeholder="+905xxxxxxxxx"><p class="description">' . esc_html__( 'فقط مدیر سامانه می‌تواند شماره متصل به حساب را تعیین کند.', 'mtn-cafe-booking' ) . '</p></td></tr></table>';
	}

	public static function save_phone_field( $user_id ) {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['mtn_cb_phone_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mtn_cb_phone_nonce'] ) ), 'mtn_cb_phone_' . absint( $user_id ) ) ) {
			return;
		}
		$phone = isset( $_POST['mtn_cb_phone'] ) && is_scalar( $_POST['mtn_cb_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['mtn_cb_phone'] ) ) : '';
		if ( '' === $phone ) {
			delete_user_meta( $user_id, 'mtn_cb_phone' );
		} elseif ( self::valid_phone( $phone ) ) {
			update_user_meta( $user_id, 'mtn_cb_phone', $phone );
		}
	}
}
