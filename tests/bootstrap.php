<?php
/**
 * WordPress integration-test bootstrap.
 */
$wp_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $wp_tests_dir ) {
	$wp_tests_dir = '/tmp/wordpress-tests-lib';
}
require_once $wp_tests_dir . '/includes/functions.php';
tests_add_filter( 'muplugins_loaded', static function () {
	require dirname( __DIR__ ) . '/wp-content/plugins/mtn-cafe-booking/mtn-cafe-booking.php';
} );
require $wp_tests_dir . '/includes/bootstrap.php';
