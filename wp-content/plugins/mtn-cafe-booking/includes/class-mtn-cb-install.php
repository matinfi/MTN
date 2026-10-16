<?php
/**
 * Database lifecycle.
 *
 * @package MTN_Cafe_Booking
 */

defined( 'ABSPATH' ) || exit;

final class MTN_CB_Install {
	const DB_VERSION = '3';

	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = $wpdb->prefix . 'mtn_bookings';
		$collate         = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_code varchar(32) NOT NULL,
			idempotency_key varchar(64) NULL DEFAULT NULL,
			user_id bigint(20) unsigned NOT NULL,
			cafe_id bigint(20) unsigned NOT NULL DEFAULT 0,
			event_id bigint(20) unsigned NOT NULL DEFAULT 0,
			table_id bigint(20) unsigned NOT NULL DEFAULT 0,
			party_size smallint(5) unsigned NOT NULL DEFAULT 1,
			booking_at datetime NOT NULL,
			status varchar(32) NOT NULL DEFAULT 'pending_cafe',
			cafe_note text NULL,
			customer_note text NULL,
			whatsapp_action_status varchar(32) NOT NULL DEFAULT 'manual_required',
			checkin_status varchar(32) NOT NULL DEFAULT 'not_checked_in',
			commission_amount decimal(12,2) NOT NULL DEFAULT 0.00,
			commission_rate decimal(12,4) NOT NULL DEFAULT 0.0000,
			commission_currency varchar(8) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY booking_code (booking_code),
			UNIQUE KEY user_idempotency (user_id,idempotency_key),
			KEY user_status (user_id,status),
			KEY cafe_date (cafe_id,booking_at),
			KEY cafe_table_date (cafe_id,table_id,booking_at,status),
			KEY event_date (event_id,booking_at)
		) {$collate};";

		dbDelta( $sql );
		update_option( 'mtn_cb_db_version', self::DB_VERSION );
		add_option( 'mtn_cb_brand_name', get_bloginfo( 'name' ) );
		add_option( 'mtn_cb_currency', 'TRY' );
		add_option( 'mtn_cb_default_commission', '0' );
		add_option( 'mtn_cb_booking_expiry_minutes', 30 );
		add_option( 'mtn_cb_booking_duration_minutes', 120 );
		add_option( 'mtn_cb_booking_mode', 'customer_selects_table' );
	}

	public static function maybe_upgrade() {
		if ( (string) get_option( 'mtn_cb_db_version', '0' ) !== self::DB_VERSION ) {
			self::activate();
		}
	}

	public static function deactivate() {
		// Preserve all booking and finance data on deactivation.
	}
}
