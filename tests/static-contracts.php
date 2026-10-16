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
$rest = file_get_contents( $rest_path );
$bookings = file_get_contents( $bookings_path );
$content = file_get_contents( $content_path );
$install = file_get_contents( $install_path );
$finance = file_get_contents( $finance_path );

foreach ( array( $rest, $bookings, $content, $install, $finance ) as $source ) {
	if ( false === $source ) {
		fwrite( STDERR, "Unable to read a required source file.\n" );
		exit( 1 );
	}
}

$route_count = preg_match_all( "/'[^']*\\(\\?P<id>[^']*\\)'/", $rest );
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
	false === strpos( $content, '$keys[] = \'mtn_cafe_owner_id\';' ) ) {
	fwrite( STDERR, "Cafe owner assignment privilege guard is missing.\n" );
	exit( 1 );
}

if ( false === strpos( $install, "const DB_VERSION = '10';" ) ||
	false === strpos( $install, 'commission_trigger_status varchar(20)' ) ||
	false === strpos( $install, 'checkin_token_hash char(64)' ) ||
	false === strpos( $bookings, "'commission_trigger_status' => $commission_trigger_status" ) ||
	false === strpos( $bookings, 'public static function issue_checkin_token' ) ||
	false === strpos( $bookings, 'public static function check_in_with_token' ) ||
	false === strpos( $finance, '$booking->commission_trigger_status' ) ||
	false === strpos( $rest, "'/cafe/check-in'" ) ) {
	fwrite( STDERR, "Commission/check-in schema or behavior contract is missing.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Static source-contract checks passed. These checks do not replace WordPress integration or concurrency tests.\n" );
