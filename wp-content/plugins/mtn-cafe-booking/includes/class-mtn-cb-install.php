<?php
/**
 * Database lifecycle.
 *
 * @package MTN_Cafe_Booking
 */

defined( 'ABSPATH' ) || exit;

final class MTN_CB_Install {
	const DB_VERSION = '13';

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
			checkin_token_hash char(64) NOT NULL DEFAULT '',
			checkin_token_expires_at datetime NULL,
			commission_amount decimal(12,2) NOT NULL DEFAULT 0.00,
			commission_rate decimal(12,4) NOT NULL DEFAULT 0.0000,
			commission_currency varchar(8) NOT NULL DEFAULT '',
			commission_trigger_status varchar(20) NOT NULL DEFAULT 'confirmed',
			commission_reverse_on_cancellation tinyint(1) NOT NULL DEFAULT 0,
			commission_reverse_on_no_show tinyint(1) NOT NULL DEFAULT 0,
			commission_reverse_on_event_cancel tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY booking_code (booking_code),
			UNIQUE KEY user_idempotency (user_id,idempotency_key),
			KEY user_status (user_id,status),
			KEY cafe_date (cafe_id,booking_at),
			KEY cafe_table_date (cafe_id,table_id,booking_at,status),
			KEY event_date (event_id,booking_at),
			KEY checkin_token_hash (checkin_token_hash)
		) {$collate};";

		dbDelta( $sql );

		$ledger_table = $wpdb->prefix . 'mtn_ledger';
		$ledger_sql = "CREATE TABLE {$ledger_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entry_key varchar(100) NOT NULL,
			booking_id bigint(20) unsigned NOT NULL DEFAULT 0,
			cafe_id bigint(20) unsigned NOT NULL DEFAULT 0,
			entry_type varchar(32) NOT NULL,
			amount decimal(12,2) NOT NULL DEFAULT 0.00,
			currency varchar(8) NOT NULL DEFAULT '',
			reference varchar(100) NOT NULL DEFAULT '',
			note text NULL,
			actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY entry_key (entry_key),
			KEY cafe_date (cafe_id,created_at),
			KEY booking_id (booking_id),
			KEY entry_type (entry_type)
		) {$collate};";

		$settlements_table = $wpdb->prefix . 'mtn_settlements';
		$settlements_sql = "CREATE TABLE {$settlements_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			request_key varchar(64) NULL DEFAULT NULL,
			cafe_id bigint(20) unsigned NOT NULL,
			amount decimal(12,2) NOT NULL DEFAULT 0.00,
			currency varchar(8) NOT NULL DEFAULT '',
			reference varchar(100) NOT NULL DEFAULT '',
			note text NULL,
			status varchar(20) NOT NULL DEFAULT 'paid',
			actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			paid_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY request_key (request_key),
			KEY cafe_date (cafe_id,created_at),
			KEY status (status)
		) {$collate};";

		dbDelta( $ledger_sql );
		dbDelta( $settlements_sql );

		$audit_table = $wpdb->prefix . 'mtn_booking_events';
		$audit_sql = "CREATE TABLE {$audit_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_id bigint(20) unsigned NOT NULL,
			actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			event_type varchar(32) NOT NULL,
			from_status varchar(32) NOT NULL DEFAULT '',
			to_status varchar(32) NOT NULL DEFAULT '',
			details text NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY booking_date (booking_id,created_at),
			KEY actor_date (actor_id,created_at),
			KEY event_type (event_type)
		) {$collate};";
		dbDelta( $audit_sql );
		$invites_table = $wpdb->prefix . 'mtn_cafe_invites';
		$invites_sql = "CREATE TABLE {$invites_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			cafe_id bigint(20) unsigned NOT NULL,
			email varchar(190) NOT NULL,
			token_hash char(64) NOT NULL,
			invited_by bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'pending',
			expires_at datetime NOT NULL,
			accepted_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			accepted_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY token_hash (token_hash),
			KEY cafe_email (cafe_id,email),
			KEY status_expiry (status,expires_at)
		) {$collate};";
		$notifications_table = $wpdb->prefix . 'mtn_notifications';
		$notifications_sql = "CREATE TABLE {$notifications_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			dedupe_key varchar(100) NOT NULL,
			booking_id bigint(20) unsigned NOT NULL DEFAULT 0,
			channel varchar(20) NOT NULL DEFAULT 'email',
			recipient varchar(190) NOT NULL,
			subject varchar(190) NOT NULL DEFAULT '',
			message longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'queued',
			attempts smallint(5) unsigned NOT NULL DEFAULT 0,
			next_attempt_at datetime NOT NULL,
			last_error text NULL,
			created_at datetime NOT NULL,
			sent_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY dedupe_key (dedupe_key),
			KEY status_next_attempt (status,next_attempt_at),
			KEY booking_id (booking_id)
		) {$collate};";
		dbDelta( $invites_sql );
		dbDelta( $notifications_sql );
		$otp_table = $wpdb->prefix . 'mtn_sms_otps';
		$otp_sql = "CREATE TABLE {$otp_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			phone varchar(20) NOT NULL,
			code_hash char(64) NOT NULL,
			ip_hash char(64) NOT NULL DEFAULT '',
			attempts smallint(5) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL,
			expires_at datetime NOT NULL,
			consumed_at datetime NULL,
			PRIMARY KEY  (id),
			KEY phone_created (phone,created_at),
			KEY ip_created (ip_hash,created_at),
			KEY status_expiry (status,expires_at)
		) {$collate};";
		dbDelta( $otp_sql );
		update_option( 'mtn_cb_db_version', self::DB_VERSION );
		if ( ! wp_next_scheduled( 'mtn_cb_expire_pending_bookings' ) ) {
			wp_schedule_event( time() + 60, 'mtn_cb_five_minutes', 'mtn_cb_expire_pending_bookings' );
		}
		if ( class_exists( 'MTN_CB_Content' ) ) {
			MTN_CB_Content::register_types();
			flush_rewrite_rules();
		}
		self::ensure_roles();
		self::ensure_default_pages();
		add_option( 'mtn_cb_brand_name', get_bloginfo( 'name' ) );
		add_option( 'mtn_cb_currency', 'TRY' );
		add_option( 'mtn_cb_default_commission', '0' );
		add_option( 'mtn_cb_commission_trigger_status', 'confirmed' );
		add_option( 'mtn_cb_reverse_commission_on_cancellation', false );
		add_option( 'mtn_cb_reverse_commission_on_no_show', false );
		add_option( 'mtn_cb_reverse_commission_on_event_cancel', true );
		add_option( 'mtn_cb_booking_expiry_minutes', 30 );
		add_option( 'mtn_cb_checkin_token_minutes', 15 );
		add_option( 'mtn_cb_booking_duration_minutes', 120 );
		add_option( 'mtn_cb_customer_cancel_cutoff_minutes', 0 );
		add_option( 'mtn_cb_statement_due_days', 7 );
		add_option( 'mtn_cb_booking_mode', 'customer_selects_table' );
	}

	public static function ensure_roles() {
		$role = get_role( 'mtn_cafe_manager' );
		if ( ! $role ) {
			add_role( 'mtn_cafe_manager', 'مدیر کافه', array( 'read' => true, 'mtn_manage_cafes' => true ) );
			$role = get_role( 'mtn_cafe_manager' );
		}
		if ( $role ) {
			$role->add_cap( 'read' );
			$role->add_cap( 'mtn_manage_cafes' );
		}
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'mtn_manage_cafes' );
		}
	}

	public static function ensure_default_pages() {
		if ( '4' === (string) get_option( 'mtn_cb_default_pages_version', '0' ) ) {
			return;
		}
		$pages = array(
			'book' => array( 'title' => 'رزرو کافه', 'content' => '[mtn_booking_form]' ),
			'my-account' => array( 'title' => 'رزروهای من', 'content' => '[mtn_my_bookings]' ),
			'cafe-dashboard' => array( 'title' => 'مدیریت کافه', 'content' => '[mtn_cafe_dashboard]' ),
		);
		foreach ( $pages as $slug => $page ) {
			if ( ! get_page_by_path( $slug, OBJECT, 'page' ) ) {
				wp_insert_post( array(
					'post_type' => 'page',
					'post_status' => 'publish',
					'post_title' => $page['title'],
					'post_name' => $slug,
					'post_content' => $page['content'],
					'post_author' => get_current_user_id(),
				) );
			}
		}
		update_option( 'mtn_cb_default_pages_created', 1 );
		$account_page = get_page_by_path( 'my-account', OBJECT, 'page' );
		if ( $account_page ) {
			$account_content = $account_page->post_content;
			foreach ( array( '[mtn_cafe_invite_accept]', '[mtn_sms_login]' ) as $shortcode ) {
				if ( false === strpos( $account_content, $shortcode ) ) {
					$account_content .= "\n\n" . $shortcode;
				}
			}
			if ( $account_content !== $account_page->post_content ) {
				wp_update_post( array( 'ID' => $account_page->ID, 'post_content' => $account_content ) );
			}
		}
		update_option( 'mtn_cb_default_pages_version', '4' );
	}

	public static function maybe_upgrade() {
		if ( (string) get_option( 'mtn_cb_db_version', '0' ) !== self::DB_VERSION ) {
			self::activate();
		}
		self::ensure_roles();
		self::ensure_default_pages();
	}

	public static function deactivate() {
		// Preserve all booking and finance data on deactivation.
		wp_clear_scheduled_hook( 'mtn_cb_expire_pending_bookings' );
		wp_clear_scheduled_hook( 'mtn_cb_process_notifications' );
		wp_clear_scheduled_hook( 'mtn_cb_generate_recurring_events' );
		wp_clear_scheduled_hook( 'mtn_cb_generate_monthly_statements' );
		flush_rewrite_rules();
	}
}
