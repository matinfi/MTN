<?php
/**
 * Plugin Name: MTN Cafe Booking
 * Description: Reservation foundation for cafes, live music and night events.
 * Version: 0.7.1
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Text Domain: mtn-cafe-booking
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'MTN_CB_VERSION', '0.7.1' );
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
require_once MTN_CB_PATH . 'includes/class-mtn-cb-notifications.php';
require_once MTN_CB_PATH . 'includes/class-mtn-cb-team-invites.php';

register_activation_hook( __FILE__, array( 'MTN_CB_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MTN_CB_Install', 'deactivate' ) );

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
		MTN_CB_Notifications::init();
		MTN_CB_Team_Invites::init();
	}
);
