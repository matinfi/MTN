<?php
/**
 * WordPress integration-test bootstrap.
 */
$polyfills_autoload = dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
if ( file_exists( $polyfills_autoload ) ) {
	require_once $polyfills_autoload;
}

$wp_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $wp_tests_dir ) {
	$wp_tests_dir = '/tmp/wordpress-tests-lib';
}
require_once $wp_tests_dir . '/includes/functions.php';
$plugin_path = getenv( 'MTN_PLUGIN_PATH' );
if ( ! $plugin_path ) {
	$plugin_path = dirname( __DIR__ ) . '/wp-content/plugins/mtn-cafe-booking/mtn-cafe-booking.php';
}
tests_add_filter( 'muplugins_loaded', static function () use ( $plugin_path ) {
	require $plugin_path;
} );
require $wp_tests_dir . '/includes/bootstrap.php';

// Test-only bank details allow booking-flow tests to exercise the manual deposit guard.
update_option( 'mtn_cb_bank_account_name', 'MTN Test Account' );
update_option( 'mtn_cb_bank_iban', 'TR330006100519786457841326' );
