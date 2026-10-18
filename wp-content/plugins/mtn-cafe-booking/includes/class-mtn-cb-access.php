<?php
/**
 * Front-end authentication and WordPress dashboard access control.
 *
 * - Only administrators (capability `manage_options`) may use wp-admin.
 * - Customers sign in, register and recover passwords on a themed front-end page.
 * - wp-login.php is kept only for the technical actions WordPress needs (logout,
 *   password-reset links, post passwords); its sign-in and register screens are
 *   redirected to the front-end page.
 *
 * Recovery switches (documented in docs/DEPLOYMENT.md):
 * - define( 'MTN_CB_DISABLE_AUTH_REDIRECT', true ) in wp-config.php stops redirecting wp-login.php.
 * - define( 'MTN_CB_ALLOW_STAFF_ADMIN', true ) in wp-config.php lets every logged-in user keep wp-admin access.
 * - wp-login.php?mtn-admin=1 opens the classic WordPress sign-in form for administrators.
 *
 * @package MTN_Cafe_Booking
 */

defined( 'ABSPATH' ) || exit;

final class MTN_CB_Access {
	const PAGES_VERSION = '1';

	/**
	 * wp-login.php actions that must keep working (they never show the sign-in form).
	 *
	 * @var string[]
	 */
	private static $passthrough_actions = array( 'logout', 'lostpassword', 'retrievepassword', 'resetpass', 'rp', 'postpass', 'confirmaction', 'confirm_admin_email' );

	public static function init() {
		add_filter( 'login_url', array( __CLASS__, 'filter_login_url' ), 10, 3 );
		add_filter( 'register_url', array( __CLASS__, 'filter_register_url' ) );
		add_action( 'login_init', array( __CLASS__, 'redirect_wp_login' ), 1 );
		add_action( 'admin_init', array( __CLASS__, 'restrict_dashboard' ), 1 );
		add_filter( 'show_admin_bar', array( __CLASS__, 'filter_admin_bar' ), 100 );
		add_filter( 'login_redirect', array( __CLASS__, 'filter_login_redirect' ), 99, 3 );
		add_filter( 'pre_option_users_can_register', array( __CLASS__, 'filter_core_registration' ) );
		add_action( 'login_enqueue_scripts', array( __CLASS__, 'login_screen_style' ) );
		add_filter( 'login_headerurl', array( __CLASS__, 'login_header_url' ) );
		add_filter( 'login_headertext', array( __CLASS__, 'login_header_text' ) );

		add_shortcode( 'mtn_auth', array( __CLASS__, 'auth_shortcode' ) );
		add_shortcode( 'mtn_account_settings', array( __CLASS__, 'account_settings_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );

		add_action( 'admin_post_nopriv_mtn_cb_login', array( __CLASS__, 'handle_login' ) );
		add_action( 'admin_post_mtn_cb_login', array( __CLASS__, 'handle_login' ) );
		add_action( 'admin_post_nopriv_mtn_cb_register', array( __CLASS__, 'handle_register' ) );
		add_action( 'admin_post_mtn_cb_register', array( __CLASS__, 'handle_register' ) );
		add_action( 'admin_post_nopriv_mtn_cb_forgot', array( __CLASS__, 'handle_forgot' ) );
		add_action( 'admin_post_mtn_cb_forgot', array( __CLASS__, 'handle_forgot' ) );
		add_action( 'admin_post_mtn_cb_account', array( __CLASS__, 'handle_account' ) );

		add_filter( 'rest_endpoints', array( __CLASS__, 'hide_user_endpoints' ) );
		add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'hide_users_sitemap' ), 10, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'block_author_archives' ), 0 );
		add_action( 'init', array( __CLASS__, 'ensure_pages' ), 21 );
	}

	/* ------------------------------------------------------------------ *
	 * Page URLs
	 * ------------------------------------------------------------------ */

	private static function auth_page_id() {
		$id = absint( get_option( 'mtn_cb_auth_page_id', 0 ) );
		return $id && 'publish' === get_post_status( $id ) ? $id : 0;
	}

	public static function auth_url( $mode = 'login', array $args = array() ) {
		$id = self::auth_page_id();
		if ( ! $id ) {
			return '';
		}
		$url = get_permalink( $id );
		if ( ! $url ) {
			return '';
		}
		if ( 'login' !== $mode ) {
			$args['mode'] = $mode;
		}
		return $args ? add_query_arg( $args, $url ) : $url;
	}

	public static function account_url() {
		$page = get_page_by_path( 'my-account', OBJECT, 'page' );
		return $page && 'publish' === $page->post_status ? get_permalink( $page ) : home_url( '/' );
	}

	public static function filter_login_url( $login_url, $redirect = '', $force_reauth = false ) {
		unset( $force_reauth );
		if ( self::auth_redirect_disabled() ) {
			return $login_url;
		}
		$args = array();
		if ( $redirect ) {
			$args['redirect_to'] = rawurlencode( $redirect );
		}
		$url = self::auth_url( 'login', $args );
		return $url ? $url : $login_url;
	}

	public static function filter_register_url( $register_url ) {
		if ( self::auth_redirect_disabled() ) {
			return $register_url;
		}
		$url = self::auth_url( 'register' );
		return $url ? $url : $register_url;
	}

	private static function auth_redirect_disabled() {
		return ( defined( 'MTN_CB_DISABLE_AUTH_REDIRECT' ) && MTN_CB_DISABLE_AUTH_REDIRECT ) || ! self::auth_page_id();
	}

	/**
	 * Registration is owned by the front-end form (and its own on/off switch).
	 * Switching off WordPress' built-in registration stops accounts being created by
	 * posting directly to wp-login.php?action=register.
	 *
	 * @param mixed $pre Short-circuit value (false when untouched).
	 * @return mixed
	 */
	public static function filter_core_registration( $pre ) {
		return self::auth_redirect_disabled() ? $pre : '0';
	}

	/**
	 * Forms on the public auth page are protected with a nonce.
	 *
	 * @param string $mode Form to return to on failure.
	 * @param array  $flash Values to keep in the form.
	 */
	private static function require_nonce( $mode, array $flash = array() ) {
		$nonce = self::posted( '_mtn_auth_nonce' );
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'mtn_cb_auth' ) ) {
			self::back( $mode, 'nonce', $flash );
		}
	}

	/* ------------------------------------------------------------------ *
	 * wp-login.php and wp-admin protection
	 * ------------------------------------------------------------------ */

	/**
	 * Send the classic sign-in and registration screens to the front-end page.
	 */
	public static function redirect_wp_login() {
		if ( self::auth_redirect_disabled() ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['mtn-admin'] ) || isset( $_REQUEST['interim-login'] ) ) {
			return;
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( 'GET' !== $method && 'HEAD' !== $method ) {
			return;
		}
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login';
		if ( 'lostpassword' === $action || 'retrievepassword' === $action ) {
			$forgot = self::auth_url( 'forgot' );
			if ( $forgot ) {
				wp_safe_redirect( $forgot );
				exit;
			}
		}
		if ( in_array( $action, self::$passthrough_actions, true ) ) {
			return;
		}
		$args = array();
		if ( isset( $_GET['redirect_to'] ) ) {
			$args['redirect_to'] = rawurlencode( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) );
		}
		if ( 'register' === $action ) {
			$target = self::auth_url( 'register', $args );
		} elseif ( isset( $_GET['checkemail'] ) ) {
			$target = self::auth_url( 'login', array( 'notice' => 'reset_sent' ) );
		} elseif ( isset( $_GET['password'] ) && 'changed' === sanitize_key( wp_unslash( $_GET['password'] ) ) ) {
			$target = self::auth_url( 'login', array( 'notice' => 'password_changed' ) );
		} elseif ( isset( $_GET['loggedout'] ) ) {
			$target = self::auth_url( 'login', array( 'notice' => 'logged_out' ) );
		} else {
			$target = self::auth_url( 'login', $args );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( $target ) {
			wp_safe_redirect( $target );
			exit;
		}
	}

	/**
	 * Keep every non-administrator out of the WordPress dashboard.
	 */
	public static function restrict_dashboard() {
		if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'MTN_CB_ALLOW_STAFF_ADMIN' ) && MTN_CB_ALLOW_STAFF_ADMIN ) ) {
			return;
		}
		// admin-post.php carries the public form handlers of this plugin (including for logged-out visitors).
		$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : '';
		if ( 'admin-post.php' === $script ) {
			return;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		$user = wp_get_current_user();
		if ( apply_filters( 'mtn_cb_allow_wpadmin_access', false, $user ) ) {
			return;
		}
		wp_safe_redirect( self::account_url() );
		exit;
	}

	public static function filter_admin_bar( $show ) {
		if ( ! current_user_can( 'manage_options' ) && ! apply_filters( 'mtn_cb_allow_wpadmin_access', false, wp_get_current_user() ) ) {
			return false;
		}
		return $show;
	}

	/**
	 * After a classic wp-login.php sign-in, customers never land in wp-admin.
	 *
	 * @param string           $redirect_to Requested redirect.
	 * @param string           $requested   Raw requested redirect.
	 * @param WP_User|WP_Error $user        Signed-in user or error.
	 * @return string
	 */
	public static function filter_login_redirect( $redirect_to, $requested, $user ) {
		unset( $requested );
		if ( $user instanceof WP_User && ! user_can( $user, 'manage_options' ) ) {
			return self::safe_redirect_for_customer( $redirect_to );
		}
		return $redirect_to;
	}

	private static function safe_redirect_for_customer( $candidate ) {
		$default = self::account_url();
		$candidate = $candidate ? wp_validate_redirect( $candidate, $default ) : $default;
		if ( 0 === strpos( $candidate, admin_url() ) || false !== strpos( $candidate, 'wp-login.php' ) ) {
			return $default;
		}
		return $candidate;
	}

	/* ------------------------------------------------------------------ *
	 * Privacy hardening (user enumeration)
	 * ------------------------------------------------------------------ */

	public static function hide_user_endpoints( $endpoints ) {
		if ( ! current_user_can( 'list_users' ) ) {
			foreach ( array_keys( $endpoints ) as $route ) {
				if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
					unset( $endpoints[ $route ] );
				}
			}
		}
		return $endpoints;
	}

	public static function hide_users_sitemap( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	}

	public static function block_author_archives() {
		if ( is_author() && ! current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}

	/* ------------------------------------------------------------------ *
	 * Classic wp-login.php appearance (used by password-reset links)
	 * ------------------------------------------------------------------ */

	private static function theme_color( $key, $fallback ) {
		if ( function_exists( 'mtn_cafe_theme_option_value' ) ) {
			$value = sanitize_hex_color( mtn_cafe_theme_option_value( $key ) );
			if ( $value ) {
				return $value;
			}
		}
		return $fallback;
	}

	public static function login_screen_style() {
		$bg = self::theme_color( 'mtn_cafe_theme_bg', '#101014' );
		$surface = self::theme_color( 'mtn_cafe_theme_surface', '#1b1b22' );
		$text = self::theme_color( 'mtn_cafe_theme_text', '#f7f4ef' );
		$accent = self::theme_color( 'mtn_cafe_theme_accent', '#e7a94b' );
		$css = sprintf(
			'body.login{background:%1$s;color:%3$s;font-family:Tahoma,Arial,sans-serif}.login h1 a{background:none;width:auto;height:auto;text-indent:0;font-size:1.6rem;font-weight:800;color:%4$s;line-height:1.6}.login form{background:%2$s;border:1px solid rgba(255,255,255,.12);border-radius:18px;box-shadow:none;color:%3$s}.login label,.login #nav a,.login #backtoblog a,.login .message,.login .privacy-policy-page-link a{color:%3$s}.login input[type=text],.login input[type=password],.login input[type=email]{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.25);color:%3$s;border-radius:10px;min-height:44px}.login .button-primary{background:%4$s;border-color:%4$s;color:#17120b;font-weight:800;border-radius:999px;min-height:42px;box-shadow:none;text-shadow:none}.login .message,.login .success,.login #login_error{background:%2$s;border-inline-start-color:%4$s;color:%3$s}.login .button.wp-hide-pw{color:%3$s}',
			$bg,
			$surface,
			$text,
			$accent
		);
		wp_register_style( 'mtn-cb-login-screen', false, array(), MTN_CB_VERSION );
		wp_enqueue_style( 'mtn-cb-login-screen' );
		wp_add_inline_style( 'mtn-cb-login-screen', $css );
	}

	public static function login_header_url() {
		return home_url( '/' );
	}

	public static function login_header_text() {
		return get_option( 'mtn_cb_brand_name', get_bloginfo( 'name' ) );
	}

	/* ------------------------------------------------------------------ *
	 * Pages
	 * ------------------------------------------------------------------ */

	public static function ensure_pages() {
		if ( self::PAGES_VERSION === (string) get_option( 'mtn_cb_auth_pages_version', '0' ) && self::auth_page_id() ) {
			return;
		}
		$page = get_page_by_path( 'login', OBJECT, 'page' );
		if ( $page && false === strpos( (string) $page->post_content, '[mtn_auth' ) ) {
			// A different page already owns /login/; use a distinct slug rather than overwrite it.
			$page = get_page_by_path( 'mtn-login', OBJECT, 'page' );
			$slug = 'mtn-login';
		} else {
			$slug = 'login';
		}
		if ( ! $page ) {
			$id = wp_insert_post( array(
				'post_type' => 'page',
				'post_status' => 'publish',
				'post_title' => 'ورود و ثبت‌نام',
				'post_name' => $slug,
				'post_content' => '[mtn_auth]',
				'comment_status' => 'closed',
			) );
		} else {
			$id = $page->ID;
			if ( 'publish' !== $page->post_status ) {
				wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
			}
		}
		if ( $id && ! is_wp_error( $id ) ) {
			update_option( 'mtn_cb_auth_page_id', absint( $id ) );
			update_option( 'mtn_cb_auth_pages_version', self::PAGES_VERSION );
		}
		$account = get_page_by_path( 'my-account', OBJECT, 'page' );
		if ( $account && false === strpos( (string) $account->post_content, '[mtn_account_settings]' ) ) {
			wp_update_post( array( 'ID' => $account->ID, 'post_content' => $account->post_content . "\n\n[mtn_account_settings]" ) );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Assets
	 * ------------------------------------------------------------------ */

	public static function register_assets() {
		$file = MTN_CB_PATH . 'assets/auth.css';
		wp_register_style( 'mtn-cb-auth', MTN_CB_URL . 'assets/auth.css', array(), file_exists( $file ) ? (string) filemtime( $file ) : MTN_CB_VERSION );
		$js = MTN_CB_PATH . 'assets/auth.js';
		wp_register_script( 'mtn-cb-auth', MTN_CB_URL . 'assets/auth.js', array(), file_exists( $js ) ? (string) filemtime( $js ) : MTN_CB_VERSION, true );
	}

	/* ------------------------------------------------------------------ *
	 * Messages
	 * ------------------------------------------------------------------ */

	private static function messages() {
		return array(
			'invalid' => 'ایمیل/نام کاربری یا گذرواژه درست نیست.',
			'throttled' => 'تلاش‌های ناموفق زیاد بوده است؛ چند دقیقه بعد دوباره تلاش کنید.',
			'required' => 'لطفاً همه فیلدهای الزامی را کامل کنید.',
			'name' => 'نام باید بین ۲ تا ۶۰ نویسه باشد.',
			'email' => 'نشانی ایمیل معتبر نیست.',
			'email_exists' => 'این ایمیل قبلاً ثبت شده است؛ وارد شوید یا گذرواژه را بازیابی کنید.',
			'weak' => 'گذرواژه باید دست‌کم ۸ نویسه باشد.',
			'mismatch' => 'گذرواژه و تکرار آن یکسان نیستند.',
			'closed' => 'ثبت‌نام در حال حاضر غیرفعال است.',
			'spam' => 'درخواست پذیرفته نشد؛ دوباره تلاش کنید.',
			'failed' => 'عملیات انجام نشد؛ کمی بعد دوباره تلاش کنید.',
			'nonce' => 'نشست شما منقضی شده است؛ صفحه را تازه‌سازی کنید.',
			'bad_password' => 'گذرواژه فعلی درست نیست.',
			'reset_sent' => 'اگر حسابی با این مشخصات وجود داشته باشد، پیوند بازیابی گذرواژه به ایمیل آن ارسال می‌شود.',
			'password_changed' => 'گذرواژه با موفقیت تغییر کرد؛ اکنون وارد شوید.',
			'logged_out' => 'با موفقیت از حساب خارج شدید.',
			'saved' => 'تغییرات ذخیره شد.',
		);
	}

	/* ------------------------------------------------------------------ *
	 * Throttling helpers
	 * ------------------------------------------------------------------ */

	private static function client_hash() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ip = apply_filters( 'mtn_cb_client_ip', $ip );
		return substr( hash_hmac( 'sha256', (string) $ip, wp_salt( 'auth' ) ), 0, 24 );
	}

	private static function bucket_count( $bucket ) {
		return absint( get_transient( 'mtn_cb_t_' . $bucket ) );
	}

	private static function bucket_hit( $bucket, $ttl ) {
		$key = 'mtn_cb_t_' . $bucket;
		$count = absint( get_transient( $key ) ) + 1;
		set_transient( $key, $count, $ttl );
		return $count;
	}

	private static function bucket_clear( $bucket ) {
		delete_transient( 'mtn_cb_t_' . $bucket );
	}

	/* ------------------------------------------------------------------ *
	 * Redirect + flash helpers
	 * ------------------------------------------------------------------ */

	private static function posted( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? wp_unslash( (string) $_POST[ $key ] ) : '';
	}

	private static function requested_redirect() {
		$raw = self::posted( 'redirect_to' );
		return $raw ? esc_url_raw( $raw ) : '';
	}

	private static function back( $mode, $code, array $flash = array() ) {
		$args = array( 'auth_error' => $code );
		$redirect = self::requested_redirect();
		if ( $redirect ) {
			$args['redirect_to'] = rawurlencode( $redirect );
		}
		if ( $flash ) {
			$token = wp_generate_password( 14, false, false );
			set_transient( 'mtn_cb_flash_' . $token, $flash, 2 * MINUTE_IN_SECONDS );
			$args['flash'] = $token;
		}
		$url = self::auth_url( $mode, $args );
		wp_safe_redirect( $url ? $url : home_url( '/' ) );
		exit;
	}

	private static function flash() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = isset( $_GET['flash'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', sanitize_text_field( wp_unslash( $_GET['flash'] ) ) ) : '';
		if ( ! $token ) {
			return array();
		}
		$data = get_transient( 'mtn_cb_flash_' . $token );
		delete_transient( 'mtn_cb_flash_' . $token );
		return is_array( $data ) ? $data : array();
	}

	/* ------------------------------------------------------------------ *
	 * Handlers
	 * ------------------------------------------------------------------ */

	public static function handle_login() {
		if ( is_user_logged_in() ) {
			wp_safe_redirect( current_user_can( 'manage_options' ) ? admin_url() : self::account_url() );
			exit;
		}
		$identifier = trim( sanitize_text_field( self::posted( 'log' ) ) );
		$password = self::posted( 'pwd' );
		self::require_nonce( 'login', array( 'log' => $identifier ) );
		if ( '' === $identifier || '' === $password ) {
			self::back( 'login', 'required', array( 'log' => $identifier ) );
		}
		$ip_bucket = 'login_ip_' . self::client_hash();
		$user_bucket = 'login_user_' . substr( hash( 'sha256', strtolower( $identifier ) ), 0, 24 );
		if ( self::bucket_count( $ip_bucket ) >= 20 || self::bucket_count( $user_bucket ) >= 8 ) {
			self::back( 'login', 'throttled', array( 'log' => $identifier ) );
		}
		$user = wp_signon( array(
			'user_login' => $identifier,
			'user_password' => $password,
			'remember' => '1' === self::posted( 'rememberme' ),
		), is_ssl() );
		if ( is_wp_error( $user ) ) {
			self::bucket_hit( $ip_bucket, 15 * MINUTE_IN_SECONDS );
			self::bucket_hit( $user_bucket, 15 * MINUTE_IN_SECONDS );
			self::back( 'login', 'invalid', array( 'log' => $identifier ) );
		}
		self::bucket_clear( $user_bucket );
		$requested = self::requested_redirect();
		if ( user_can( $user, 'manage_options' ) ) {
			$target = $requested ? wp_validate_redirect( $requested, admin_url() ) : admin_url();
		} else {
			$target = self::safe_redirect_for_customer( $requested );
		}
		wp_safe_redirect( $target );
		exit;
	}

	public static function handle_register() {
		if ( is_user_logged_in() ) {
			wp_safe_redirect( self::account_url() );
			exit;
		}
		if ( '1' !== (string) get_option( 'mtn_cb_allow_registration', '1' ) ) {
			self::back( 'login', 'closed' );
		}
		$name = trim( sanitize_text_field( self::posted( 'full_name' ) ) );
		$email = trim( sanitize_email( self::posted( 'email' ) ) );
		$password = self::posted( 'pwd' );
		$confirm = self::posted( 'pwd2' );
		$flash = array( 'full_name' => $name, 'email' => $email );
		if ( '' !== self::posted( 'mtn_website' ) ) {
			self::back( 'register', 'spam' );
		}
		self::require_nonce( 'register', $flash );
		if ( '' === $name || '' === $email || '' === $password ) {
			self::back( 'register', 'required', $flash );
		}
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $name ) : strlen( $name );
		if ( $length < 2 || $length > 60 ) {
			self::back( 'register', 'name', $flash );
		}
		if ( ! is_email( $email ) ) {
			self::back( 'register', 'email', $flash );
		}
		if ( strlen( $password ) < 8 ) {
			self::back( 'register', 'weak', $flash );
		}
		if ( ! hash_equals( $password, $confirm ) ) {
			self::back( 'register', 'mismatch', $flash );
		}
		$ip_bucket = 'register_ip_' . self::client_hash();
		if ( self::bucket_count( $ip_bucket ) >= 5 ) {
			self::back( 'register', 'throttled', $flash );
		}
		self::bucket_hit( $ip_bucket, HOUR_IN_SECONDS );
		if ( email_exists( $email ) ) {
			self::back( 'register', 'email_exists', $flash );
		}
		$base = sanitize_user( strstr( $email, '@', true ), true );
		$base = $base ? substr( $base, 0, 40 ) : 'user';
		$login = $base;
		$attempt = 0;
		while ( username_exists( $login ) && $attempt < 20 ) {
			$login = $base . wp_rand( 100, 99999 );
			++$attempt;
		}
		if ( username_exists( $login ) ) {
			self::back( 'register', 'failed', $flash );
		}
		$user_id = wp_insert_user( array(
			'user_login' => $login,
			'user_email' => $email,
			'user_pass' => $password,
			'display_name' => $name,
			'nickname' => $name,
			'first_name' => $name,
			'role' => 'subscriber',
		) );
		if ( is_wp_error( $user_id ) ) {
			self::back( 'register', 'failed', $flash );
		}
		$user = get_user_by( 'id', $user_id );
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true, is_ssl() );
		if ( $user ) {
			do_action( 'wp_login', $user->user_login, $user );
		}
		do_action( 'mtn_cb_customer_registered', $user_id );
		wp_safe_redirect( self::safe_redirect_for_customer( self::requested_redirect() ) );
		exit;
	}

	public static function handle_forgot() {
		$identifier = trim( sanitize_text_field( self::posted( 'user_login' ) ) );
		self::require_nonce( 'forgot' );
		if ( '' === $identifier ) {
			self::back( 'forgot', 'required' );
		}
		$ip_bucket = 'forgot_ip_' . self::client_hash();
		if ( self::bucket_count( $ip_bucket ) >= 5 ) {
			self::back( 'forgot', 'throttled' );
		}
		self::bucket_hit( $ip_bucket, 15 * MINUTE_IN_SECONDS );
		// retrieve_password() reads the identifier from the request; the response is always generic.
		$_POST['user_login'] = $identifier;
		retrieve_password( $identifier );
		$url = self::auth_url( 'login', array( 'notice' => 'reset_sent' ) );
		wp_safe_redirect( $url ? $url : home_url( '/' ) );
		exit;
	}

	public static function handle_account() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( self::account_url() ) );
			exit;
		}
		$back = self::account_url();
		$nonce = self::posted( '_mtn_account_nonce' );
		if ( ! wp_verify_nonce( $nonce, 'mtn_cb_account' ) ) {
			wp_safe_redirect( add_query_arg( 'account_error', 'nonce', $back ) );
			exit;
		}
		$user = wp_get_current_user();
		$update = array( 'ID' => $user->ID );
		$name = trim( sanitize_text_field( self::posted( 'display_name' ) ) );
		if ( '' !== $name ) {
			$length = function_exists( 'mb_strlen' ) ? mb_strlen( $name ) : strlen( $name );
			if ( $length < 2 || $length > 60 ) {
				wp_safe_redirect( add_query_arg( 'account_error', 'name', $back ) );
				exit;
			}
			$update['display_name'] = $name;
			$update['nickname'] = $name;
		}
		$new = self::posted( 'new_pwd' );
		$password_changed = false;
		if ( '' !== $new ) {
			if ( ! wp_check_password( self::posted( 'current_pwd' ), $user->user_pass, $user->ID ) ) {
				wp_safe_redirect( add_query_arg( 'account_error', 'bad_password', $back ) );
				exit;
			}
			if ( strlen( $new ) < 8 ) {
				wp_safe_redirect( add_query_arg( 'account_error', 'weak', $back ) );
				exit;
			}
			if ( ! hash_equals( $new, self::posted( 'new_pwd2' ) ) ) {
				wp_safe_redirect( add_query_arg( 'account_error', 'mismatch', $back ) );
				exit;
			}
			$update['user_pass'] = $new;
			$password_changed = true;
		}
		$result = wp_update_user( $update );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'account_error', 'failed', $back ) );
			exit;
		}
		if ( $password_changed ) {
			// Changing the password destroys sessions; keep this browser signed in.
			wp_set_auth_cookie( $user->ID, true, is_ssl() );
		}
		wp_safe_redirect( add_query_arg( 'account_notice', 'saved', $back ) );
		exit;
	}

	/* ------------------------------------------------------------------ *
	 * Rendering
	 * ------------------------------------------------------------------ */

	private static function otp_ready() {
		return defined( 'MTN_CB_TWILIO_ACCOUNT_SID' ) && defined( 'MTN_CB_TWILIO_AUTH_TOKEN' ) && defined( 'MTN_CB_TWILIO_FROM' )
			&& MTN_CB_TWILIO_ACCOUNT_SID && MTN_CB_TWILIO_AUTH_TOKEN && MTN_CB_TWILIO_FROM;
	}

	private static function message_box( $code, $type ) {
		$messages = self::messages();
		if ( ! $code || ! isset( $messages[ $code ] ) ) {
			return '';
		}
		return '<div class="mtn-auth-message is-' . esc_attr( $type ) . '" role="' . ( 'error' === $type ? 'alert' : 'status' ) . '">' . esc_html( $messages[ $code ] ) . '</div>';
	}

	public static function auth_shortcode( $atts = array() ) {
		$atts = shortcode_atts( array( 'mode' => '' ), $atts, 'mtn_auth' );
		wp_enqueue_style( 'mtn-cb-auth' );
		wp_enqueue_script( 'mtn-cb-auth' );
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			ob_start();
			?>
			<section class="mtn-auth-card">
				<h2><?php echo esc_html( sprintf( 'سلام %s', $user->display_name ) ); ?></h2>
				<p class="mtn-auth-lead">شما وارد حساب خود شده‌اید.</p>
				<div class="mtn-auth-links">
					<a class="button" href="<?php echo esc_url( self::account_url() ); ?>">رزروهای من</a>
					<?php if ( current_user_can( 'manage_options' ) ) : ?><a class="button mtn-auth-secondary" href="<?php echo esc_url( admin_url() ); ?>">پیشخوان مدیریت</a><?php endif; ?>
					<a class="button mtn-auth-secondary" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">خروج از حساب</a>
				</div>
			</section>
			<?php
			return ob_get_clean();
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$mode = $atts['mode'] ? sanitize_key( $atts['mode'] ) : ( isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : 'login' );
		$mode = in_array( $mode, array( 'login', 'register', 'forgot' ), true ) ? $mode : 'login';
		$error = isset( $_GET['auth_error'] ) ? sanitize_key( wp_unslash( $_GET['auth_error'] ) ) : '';
		$notice = isset( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : '';
		$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$redirect_to = $redirect_to ? wp_validate_redirect( $redirect_to, '' ) : '';
		$flash = self::flash();
		$allow_registration = '1' === (string) get_option( 'mtn_cb_allow_registration', '1' );
		if ( 'register' === $mode && ! $allow_registration ) {
			$mode = 'login';
		}
		$tab_args = $redirect_to ? array( 'redirect_to' => rawurlencode( $redirect_to ) ) : array();
		$action = esc_url( admin_url( 'admin-post.php' ) );
		ob_start();
		?>
		<section class="mtn-auth-card" data-mtn-auth data-mode="<?php echo esc_attr( $mode ); ?>">
			<?php if ( 'forgot' !== $mode ) : ?>
			<nav class="mtn-auth-tabs" aria-label="ورود یا ثبت‌نام">
				<a href="<?php echo esc_url( self::auth_url( 'login', $tab_args ) ); ?>" <?php echo 'login' === $mode ? 'aria-current="page" class="is-active"' : ''; ?>>ورود</a>
				<?php if ( $allow_registration ) : ?><a href="<?php echo esc_url( self::auth_url( 'register', $tab_args ) ); ?>" <?php echo 'register' === $mode ? 'aria-current="page" class="is-active"' : ''; ?>>ثبت‌نام</a><?php endif; ?>
			</nav>
			<?php endif; ?>
			<?php echo self::message_box( $notice, 'success' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo self::message_box( $error, 'error' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php if ( 'login' === $mode ) : ?>
				<h2>ورود به حساب</h2>
				<p class="mtn-auth-lead">برای ثبت و پیگیری رزروها وارد شوید.</p>
				<form class="mtn-auth-form" method="post" action="<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
					<input type="hidden" name="action" value="mtn_cb_login">
						<?php wp_nonce_field( 'mtn_cb_auth', '_mtn_auth_nonce' ); ?>
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
					<p><label for="mtn-auth-log">ایمیل یا نام کاربری</label><input id="mtn-auth-log" name="log" type="text" autocomplete="username" required value="<?php echo esc_attr( isset( $flash['log'] ) ? $flash['log'] : '' ); ?>"></p>
					<p><label for="mtn-auth-pwd">گذرواژه</label><span class="mtn-auth-password"><input id="mtn-auth-pwd" name="pwd" type="password" autocomplete="current-password" required><button class="mtn-auth-reveal" type="button" data-mtn-reveal aria-controls="mtn-auth-pwd" aria-pressed="false">نمایش</button></span></p>
					<p class="mtn-auth-row"><label class="mtn-auth-check"><input type="checkbox" name="rememberme" value="1"> مرا به خاطر بسپار</label><a href="<?php echo esc_url( self::auth_url( 'forgot' ) ); ?>">گذرواژه را فراموش کرده‌اید؟</a></p>
					<p><button class="button mtn-auth-submit" type="submit">ورود</button></p>
				</form>
				<?php if ( self::otp_ready() ) : ?>
					<div class="mtn-auth-divider"><span>یا</span></div>
					<?php echo do_shortcode( '[mtn_sms_login]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
				<?php if ( $allow_registration ) : ?><p class="mtn-auth-foot">حساب ندارید؟ <a href="<?php echo esc_url( self::auth_url( 'register', $tab_args ) ); ?>">ثبت‌نام کنید</a></p><?php endif; ?>
			<?php elseif ( 'register' === $mode ) : ?>
				<h2>ساخت حساب جدید</h2>
				<p class="mtn-auth-lead">با چند فیلد ساده حساب بسازید و رزرو کنید.</p>
				<form class="mtn-auth-form" method="post" action="<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
					<input type="hidden" name="action" value="mtn_cb_register">
						<?php wp_nonce_field( 'mtn_cb_auth', '_mtn_auth_nonce' ); ?>
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
					<p class="mtn-auth-trap" aria-hidden="true"><label for="mtn-auth-website">وب‌سایت</label><input id="mtn-auth-website" name="mtn_website" type="text" tabindex="-1" autocomplete="off"></p>
					<p><label for="mtn-auth-name">نام و نام خانوادگی</label><input id="mtn-auth-name" name="full_name" type="text" autocomplete="name" minlength="2" maxlength="60" required value="<?php echo esc_attr( isset( $flash['full_name'] ) ? $flash['full_name'] : '' ); ?>"></p>
					<p><label for="mtn-auth-email">ایمیل</label><input id="mtn-auth-email" name="email" type="email" autocomplete="email" dir="ltr" required value="<?php echo esc_attr( isset( $flash['email'] ) ? $flash['email'] : '' ); ?>"></p>
					<p><label for="mtn-auth-new">گذرواژه (دست‌کم ۸ نویسه)</label><span class="mtn-auth-password"><input id="mtn-auth-new" name="pwd" type="password" autocomplete="new-password" minlength="8" required><button class="mtn-auth-reveal" type="button" data-mtn-reveal aria-controls="mtn-auth-new" aria-pressed="false">نمایش</button></span></p>
					<p><label for="mtn-auth-new2">تکرار گذرواژه</label><input id="mtn-auth-new2" name="pwd2" type="password" autocomplete="new-password" minlength="8" required></p>
					<p><button class="button mtn-auth-submit" type="submit">ثبت‌نام و ورود</button></p>
				</form>
				<p class="mtn-auth-foot">قبلاً ثبت‌نام کرده‌اید؟ <a href="<?php echo esc_url( self::auth_url( 'login', $tab_args ) ); ?>">وارد شوید</a></p>
			<?php else : ?>
				<h2>بازیابی گذرواژه</h2>
				<p class="mtn-auth-lead">ایمیل یا نام کاربری خود را وارد کنید تا پیوند بازیابی برایتان ارسال شود.</p>
				<form class="mtn-auth-form" method="post" action="<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
					<input type="hidden" name="action" value="mtn_cb_forgot">
						<?php wp_nonce_field( 'mtn_cb_auth', '_mtn_auth_nonce' ); ?>
					<p><label for="mtn-auth-forgot">ایمیل یا نام کاربری</label><input id="mtn-auth-forgot" name="user_login" type="text" autocomplete="username" required></p>
					<p><button class="button mtn-auth-submit" type="submit">ارسال پیوند بازیابی</button></p>
				</form>
				<p class="mtn-auth-foot"><a href="<?php echo esc_url( self::auth_url( 'login' ) ); ?>">بازگشت به ورود</a></p>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function account_settings_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		wp_enqueue_style( 'mtn-cb-auth' );
		wp_enqueue_script( 'mtn-cb-auth' );
		$user = wp_get_current_user();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$error = isset( $_GET['account_error'] ) ? sanitize_key( wp_unslash( $_GET['account_error'] ) ) : '';
		$notice = isset( $_GET['account_notice'] ) ? sanitize_key( wp_unslash( $_GET['account_notice'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		ob_start();
		?>
		<section class="mtn-auth-card mtn-account-settings" aria-labelledby="mtn-account-title">
			<h2 id="mtn-account-title">تنظیمات حساب</h2>
			<?php echo self::message_box( $notice, 'success' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo self::message_box( $error, 'error' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<form class="mtn-auth-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mtn_cb_account">
				<?php wp_nonce_field( 'mtn_cb_account', '_mtn_account_nonce' ); ?>
				<p><label for="mtn-acc-name">نام نمایشی</label><input id="mtn-acc-name" name="display_name" type="text" maxlength="60" value="<?php echo esc_attr( $user->display_name ); ?>"></p>
				<p><label for="mtn-acc-email">ایمیل (برای تغییر با پشتیبانی تماس بگیرید)</label><input id="mtn-acc-email" type="email" dir="ltr" value="<?php echo esc_attr( $user->user_email ); ?>" readonly></p>
				<fieldset class="mtn-auth-fieldset"><legend>تغییر گذرواژه (اختیاری)</legend>
					<p><label for="mtn-acc-cur">گذرواژه فعلی</label><input id="mtn-acc-cur" name="current_pwd" type="password" autocomplete="current-password"></p>
					<p><label for="mtn-acc-new">گذرواژه جدید</label><input id="mtn-acc-new" name="new_pwd" type="password" autocomplete="new-password" minlength="8"></p>
					<p><label for="mtn-acc-new2">تکرار گذرواژه جدید</label><input id="mtn-acc-new2" name="new_pwd2" type="password" autocomplete="new-password" minlength="8"></p>
				</fieldset>
				<p><button class="button mtn-auth-submit" type="submit">ذخیره تغییرات</button> <a class="button mtn-auth-secondary" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">خروج از حساب</a></p>
			</form>
		</section>
		<?php
		return ob_get_clean();
	}
}
