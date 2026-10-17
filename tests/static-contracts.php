<?php
/**
 * Lightweight source-contract regression checks; these are not integration tests.
 *
 * @package MTN_Cafe_Booking
 */

$root = dirname( __DIR__ );
$rest_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-rest.php';
$bookings_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-bookings.php';
$content_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-content.php';
$install_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-install.php';
$finance_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-finance.php';
$notifications_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-notifications.php';
$invites_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-team-invites.php';
$sms_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-sms-otp.php';
$recurrence_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-event-recurrence.php';
$event_cancel_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-event-cancellation.php';
$statements_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-statements.php';
$shortcodes_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-shortcodes.php';
$otp_js_path = $root . '/wp-content/plugins/mtn-cafe-booking/assets/otp-login.js';
$theme_header_path = $root . '/wp-content/themes/mtn-cafe-theme/header.php';
$theme_style_path = $root . '/wp-content/themes/mtn-cafe-theme/style.css';
$theme_polish_path = $root . '/wp-content/themes/mtn-cafe-theme/assets/ui-polish.css';
$theme_functions_path = $root . '/wp-content/themes/mtn-cafe-theme/functions.php';
$theme_home_path = $root . '/wp-content/themes/mtn-cafe-theme/front-page.php';
$cafe_actions_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-cafe-actions.php';
$cooperation_path = $root . '/wp-content/plugins/mtn-cafe-booking/includes/class-mtn-cb-cooperation.php';
$app_shell_path = $root . '/wp-content/themes/mtn-cafe-theme/assets/app-shell.js';
$home_search_path = $root . '/wp-content/themes/mtn-cafe-theme/assets/home-search.js';
$rest = file_get_contents( $rest_path );
$bookings = file_get_contents( $bookings_path );
$content = file_get_contents( $content_path );
$install = file_get_contents( $install_path );
$finance = file_get_contents( $finance_path );
$notifications = file_get_contents( $notifications_path );
$invites = file_get_contents( $invites_path );
$sms = file_get_contents( $sms_path );
$recurrence = file_get_contents( $recurrence_path );
$event_cancel = file_get_contents( $event_cancel_path );
$statements = file_get_contents( $statements_path );
$shortcodes = file_get_contents( $shortcodes_path );
$otp_js = file_get_contents( $otp_js_path );
$theme_header = file_get_contents( $theme_header_path );
$theme_style = file_get_contents( $theme_style_path );
$theme_polish = file_get_contents( $theme_polish_path );
$theme_functions = file_get_contents( $theme_functions_path );
$theme_home = file_get_contents( $theme_home_path );
$cafe_actions = file_get_contents( $cafe_actions_path );
$cooperation = file_get_contents( $cooperation_path );
$app_shell = file_get_contents( $app_shell_path );
$home_search = file_get_contents( $home_search_path );

foreach ( array( $rest, $bookings, $content, $install, $finance, $notifications, $invites, $sms, $recurrence, $event_cancel, $statements, $shortcodes, $otp_js, $theme_header, $theme_style, $cafe_actions, $cooperation, $app_shell, $home_search, $theme_polish, $theme_functions, $theme_home ) as $source ) {
	if ( false === $source ) {
		fwrite( STDERR, "Unable to read a required source file.\n" );
		exit( 1 );
	}
}

$route_count = substr_count( $rest, '(?P<id>' );
if ( 10 !== $route_count ) {
	fwrite( STDERR, "Expected 10 booking routes with an ID pattern; found {$route_count}.\n" );
	exit( 1 );
}

// Four consecutive source backslashes become two at runtime and break the regex.
$overescaped_pattern = str_repeat( chr( 92 ), 4 ) . 'd+';
if ( false !== strpos( $rest, $overescaped_pattern ) ) {
	fwrite( STDERR, "An over-escaped booking route pattern was found.\n" );
	exit( 1 );
}

if ( false === strpos( $bookings, "get_option( 'mtn_cb_customer_cancel_cutoff_minutes', 0 )" ) ||
	false === strpos( $bookings, "'cancelled_by_customer' === " . '$next_status' ) ) {
	fwrite( STDERR, "Customer cancellation cutoff enforcement is missing.\n" );
	exit( 1 );
}

if ( false === strpos( $content, "if ( current_user_can( 'manage_options' ) )" ) ||
	false === strpos( $content, '$keys[] = \'mtn_cafe_owner_id\';' ) ||
	false === strpos( $content, 'public static function user_can_manage_cafe' ) ||
	false === strpos( $content, 'mtn_cafe_manager_ids' ) ) {
	fwrite( STDERR, "Cafe owner assignment privilege guard is missing.\n" );
	exit( 1 );
}

if ( false === strpos( $install, "const DB_VERSION = '13';" ) ||
	false === strpos( $install, 'commission_trigger_status varchar(20)' ) ||
	false === strpos( $install, 'commission_reverse_on_cancellation tinyint(1)' ) ||
	false === strpos( $install, 'commission_reverse_on_no_show tinyint(1)' ) ||
	false === strpos( $install, 'commission_reverse_on_event_cancel tinyint(1)' ) ||
	false === strpos( $install, 'mtn_cafe_invites' ) ||
	false === strpos( $install, 'mtn_notifications' ) ||
	false === strpos( $install, 'mtn_sms_otps' ) ||
	false === strpos( $sms, 'MTN_CB_TWILIO_ACCOUNT_SID' ) ||
	false === strpos( $sms, 'wp_set_auth_cookie' ) ||
	false === strpos( $sms, 'mtn_sms_login' ) ||
	false === strpos( $sms, 'public static function request_code' ) ||
	false === strpos( $sms, 'public static function verify_code' ) ||
	false === strpos( $sms, 'mtn_cb_otp_' ) ||
	false === strpos( $sms, "status = 'superseded'" ) ||
	false === strpos( $sms, 'attempts >= 4' ) ||
	false === strpos( $sms, 'MTN_CB_TWILIO_AUTH_TOKEN' ) ||
	false === strpos( $recurrence, 'public static function generate_occurrences' ) ||
	false === strpos( $event_cancel, 'commission_reverse_on_event_cancel' ) ||
	false === strpos( $content, 'mtn_event_recurrence_until' ) ||
	false === strpos( $recurrence, "'post_status' => 'draft'" ) ||
	false === strpos( $statements, 'public static function generate_monthly_statements' ) ||
	false === strpos( $statements, 'public static function calculate_overdue' ) ||

	false === strpos( $notifications, 'public static function process_queue' ) ||
	false === strpos( $notifications, 'next_attempt_at' ) ||
	false === strpos( $invites, 'public static function accept_from_form' ) ||
	false === strpos( $invites, 'hash( \'sha256\', $token )' ) ||
	false === strpos( $install, 'checkin_token_hash char(64)' ) ||
	false === strpos( $bookings, "'commission_trigger_status' => $commission_trigger_status" ) ||
	false === strpos( $bookings, "'commission_reverse_on_cancellation' => $commission_reverse_on_cancellation" ) ||
	false === strpos( $bookings, 'public static function issue_checkin_token' ) ||
	false === strpos( $bookings, 'public static function check_in_with_token' ) ||
	false === strpos( $finance, '$booking->commission_trigger_status' ) ||
	false === strpos( $finance, 'public static function reverse_commission' ) ||
	false === strpos( $finance, 'گزارش تطبیق مانده کافه‌ها' ) ||
	false === strpos( $finance, 'SUM(l.amount) AS outstanding' ) ||
	false === strpos( $rest, "'/cafe/check-in'" ) ||
	false === strpos( $rest, 'MTN_CB_WhatsApp::manual_link' ) ||
	false === strpos( $rest, "'whatsapp_url'" ) ) {
	fwrite( STDERR, "Commission/check-in schema or behavior contract is missing.\n" );
	exit( 1 );
}

if ( false === strpos( $theme_header, 'class="skip-link"' ) || false === strpos( $theme_header, 'id="main-content" tabindex="-1"' ) || false === strpos( $theme_style, '.skip-link:focus' ) || false === strpos( $theme_style, 'prefers-reduced-motion:reduce' ) || 1 !== substr_count( $shortcodes, "add_shortcode( 'mtn_cafe_invite_accept'" ) || false === strpos( $otp_js, 'codeField.hidden = false' ) ) {
	fwrite( STDERR, "Accessibility or front-end registration regression detected.\n" );
	exit( 1 );
}

if ( false === strpos( $rest, "'artists' => $artists" ) ||
	false === strpos( $home_search, "data.artists" ) ||
	false === strpos( $theme_home, 'option value="artist"' ) ||
	false === strpos( $theme_functions, 'filemtime( $polish_file )' ) ||
	false === strpos( $theme_polish, '.mtn-booking-status[data-state="success"]' ) ||
	false !== strpos( $shortcodes, "add_shortcode( 'mtn_cafe_dashboard'" ) ||
	false === strpos( $cafe_actions, 'hash_hmac' ) ||
	false === strpos( $cafe_actions, 'mtn_cb_booking_created' ) ||
	false === strpos( $cafe_actions, 'check_admin_referer' ) ||
	false === strpos( $cooperation, 'mtn_cooperation_submit' ) ||
	false === strpos( $cooperation, "'mtn_artist'" ) ||
	false === strpos( $app_shell, 'is-sidebar-collapsed' ) ||
	false === strpos( $home_search, 'catalogUrl' ) ) {
	fwrite( STDERR, "Redesign, secure cafe decisions, or cooperation contracts are missing.\\n" );
	exit( 1 );
}

fwrite( STDOUT, "Static source-contract checks passed. These checks do not replace WordPress integration or concurrency tests.\n" );
