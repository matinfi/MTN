<?php
/**
 * Manual WhatsApp message helpers. This does not send messages automatically.
 *
 * @package MTN_Cafe_Booking
 */

defined( 'ABSPATH' ) || exit;

final class MTN_CB_WhatsApp {
	/**
	 * Build a manual WhatsApp URL for a booking.
	 *
	 * @param object $booking Booking row.
	 * @param string $recipient E.164 phone number, digits and optional leading plus.
	 * @return string|WP_Error
	 */
	public static function manual_link( $booking, $recipient = '' ) {
		if ( empty( $booking->booking_code ) ) {
			return new WP_Error( 'mtn_missing_booking', __( 'اطلاعات رزرو معتبر نیست.', 'mtn-cafe-booking' ) );
		}
		$brand = get_option( 'mtn_cb_brand_name', get_bloginfo( 'name' ) );
		$text  = sprintf(
			/* translators: 1: brand name, 2: booking code, 3: status. */
			__( "تأییدیه درخواست رزرو از %1$s\nکد رزرو: %2$s\nوضعیت: %3$s\nاین پیام به معنی پرداخت آنلاین یا تأیید نهایی کافه نیست.", 'mtn-cafe-booking' ),
			$brand,
			$booking->booking_code,
			$booking->status
		);
		$phone = preg_replace( '/[^0-9]/', '', $recipient );
		$url   = 'https://wa.me/' . $phone . '?text=' . rawurlencode( $text );
		return esc_url_raw( $url );
	}
}
