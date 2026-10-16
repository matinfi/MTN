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
$rest = file_get_contents( $rest_path );
$bookings = file_get_contents( $bookings_path );
$content = file_get_contents( $content_path );

foreach ( array( $rest, $bookings, $content ) as $source ) {
	if ( false === $source ) {
		fwrite( STDERR, "Unable to read a required source file.\n" );
		exit( 1 );
	}
}

$route_count = preg_match_all( "/'[^']*\\(\\?P<id>[^']*\\)'/", $rest );
if ( 9 !== $route_count ) {
	fwrite( STDERR, "Expected 9 booking routes with an ID pattern; found {$route_count}.\n" );
	exit( 1 );
}

// PHP single-quoted route strings must contain one backslash before d+.
// Two source backslashes become one literal backslash at runtime and break the regex.
if ( false !== strpos( $rest, '\\\\d+' ) ) {
	fwrite( STDERR, "An over-escaped booking route pattern was found.\n" );
	exit( 1 );
}

if ( false === strpos( $bookings, "get_option( 'mtn_cb_customer_cancel_cutoff_minutes', 0 )" ) ||
	false === strpos( $bookings, "'cancelled_by_customer' === \\$next_status" ) ) {
	fwrite( STDERR, "Customer cancellation cutoff enforcement is missing.\n" );
	exit( 1 );
}

if ( false === strpos( $content, "if ( current_user_can( 'manage_options' ) )" ) ||
	false === strpos( $content, '$keys[] = \'mtn_cafe_owner_id\';' ) ) {
	fwrite( STDERR, "Cafe owner assignment privilege guard is missing.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Static source-contract checks passed. These checks do not replace WordPress integration or concurrency tests.\n" );
