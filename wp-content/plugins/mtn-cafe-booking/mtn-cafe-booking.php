<?php
/**
 * Plugin Name: MTN Cafe Booking
 * Description: Reservation foundation for cafes, live music and night events.
 * Version: 0.13.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Text Domain: mtn-cafe-booking
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'MTN_CB_VERSION', '0.13.0' );
define( 'MTN_CB_FILE', __FILE__ );
define( 'MTN_CB_PATH', plugin_dir_path( __FILE__ ) );
define( 'MTN_CB_URL', plugin_dir_url( __FILE__ ) );

require_once MTN_CB_PATH . 'includes/class-mtn-cb-install.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-bookings.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-content.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-rest.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-settings.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-whatsapp.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-shortcodes.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-admin.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-finance.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-notifications.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-team-invites.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-event-cancellation.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-event-recurrence.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-sms-otp.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-statements.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-cafe-actions.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-cooperation.php';

/**
 * Load the shared admin design layer only on MTN-owned admin screens.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function mtn_cb_enqueue_admin_design( $hook_suffix ) {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$screen_id = $screen && isset( $screen->id ) ? (string) $screen->id : '';
	$is_mtn_screen = false !== strpos( $screen_id, 'mtn-cb' ) || false !== strpos( $screen_id, 'mtn-cafe-booking' );
	if ( ! $is_mtn_screen ) {
		return;
	}
	$relative_path = 'assets/admin-design-system.css';
	$file_path = MTN_CB_PATH . $relative_path;
	$version = file_exists( $file_path ) ? (string) filemtime( $file_path ) : MTN_CB_VERSION;
	wp_enqueue_style( 'mtn-cb-admin-design-system', MTN_CB_URL . $relative_path, array(), $version );
	if ( isset( $_GET['page'] ) && 'mtn-cafe-booking' === sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
		$wizard_path = MTN_CB_PATH . 'assets/settings-wizard.js';
		wp_enqueue_script( 'mtn-cb-settings-wizard', MTN_CB_URL . 'assets/settings-wizard.js', array(), file_exists( $wizard_path ) ? (string) filemtime( $wizard_path ) : MTN_CB_VERSION, true );
	}
}
add_action( 'admin_enqueue_scripts', 'mtn_cb_enqueue_admin_design' );

register_activation_hook( __FILE__, array( 'MTN_CB_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MTN_CB_Install', 'deactivate' ) );
add_action( 'init', array( 'MTN_CB_Install', 'ensure_default_pages' ), 20 );

add_filter(
	'cron_schedules',
	static function ( $schedules ) {
		$schedules['mtn_cb_five_minutes'] = array(
			'interval' => 300,
			'display' => __( 'هر پنج دقیقه (MTN)', 'mtn-cafe-booking' ),
		);
		return $schedules;
	}
);

add_action( 'mtn_cb_expire_pending_bookings', array( 'MTN_CB_Bookings', 'expire_pending_bookings' ) );

add_action(
	'plugins_loaded',
	static function () {
		MTN_CB_Install::maybe_upgrade();
		MTN_CB_Content::init();
		MTN_CB_Settings::init();
		MTN_CB_Rest::init();
		MTN_CB_Shortcodes::init();
		MTN_CB_Admin::init();
		MTN_CB_Finance::init();
		MTN_CB_Notifications::init();
		MTN_CB_Team_Invites::init();
		MTN_CB_Event_Cancellation::init();
		MTN_CB_Event_Recurrence::init();
		MTN_CB_SMS_OTP::init();
		MTN_CB_Statements::init();
		MTN_CB_Cafe_Actions::init();
		MTN_CB_Cooperation::init();
	}
);
