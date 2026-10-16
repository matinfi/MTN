<?php
/**
 * Append-only commission ledger and manual settlement records.
 *
 * @package MTN_Cafe_Booking
 */

defined( 'ABSPATH' ) || exit;

final class MTN_CB_Finance {
	public static function init() {
		add_action( 'mtn_cb_booking_status_changed', array( __CLASS__, 'on_booking_status_changed' ), 10, 4 );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_mtn_cb_record_settlement', array( __CLASS__, 'record_settlement' ) );
		add_action( 'admin_post_mtn_cb_export_ledger', array( __CLASS__, 'export_ledger' ) );
	}

	public static function ledger_table() {
		global $wpdb;
		return $wpdb->prefix . 'mtn_ledger';
	}

	public static function settlements_table() {
		global $wpdb;
		return $wpdb->prefix . 'mtn_settlements';
	}

	public static function on_booking_status_changed( $booking, $next_status, $actor_id = 0, $reason = '' ) {
		if ( 'completed' !== $next_status || empty( $booking->id ) ) {
			return;
		}
		self::accrue_commission( absint( $booking->id ), absint( $actor_id ) );
	}

	public static function accrue_commission( $booking_id, $actor_id = 0 ) {
		global $wpdb;
		$booking = MTN_CB_Bookings::get_by_id( $booking_id );
		if ( ! $booking || 'completed' !== $booking->status ) {
			return new WP_Error( 'mtn_commission_not_eligible', __( 'کارمزد فقط برای رزرو تکمیل‌شده محاسبه می‌شود.', 'mtn-cafe-booking' ) );
		}
		$entry_key = 'commission_booking_' . absint( $booking_id );
		$existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::ledger_table() . ' WHERE entry_key = %s', $entry_key ) );
		if ( $existing ) {
			return true;
		}
		$amount = (float) $booking->commission_amount;
		$amount = round( max( 0, $amount ), 2 );
		if ( $amount <= 0 ) {
			return true;
		}
		$currency = $booking->commission_currency ? sanitize_text_field( $booking->commission_currency ) : sanitize_text_field( get_option( 'mtn_cb_currency', 'TRY' ) );
		$inserted = $wpdb->insert(
			self::ledger_table(),
			array(
				'entry_key' => $entry_key,
				'booking_id' => absint( $booking_id ),
				'cafe_id' => absint( $booking->cafe_id ),
				'entry_type' => 'commission_accrued',
				'amount' => number_format( $amount, 2, '.', '' ),
				'currency' => $currency,
				'reference' => $booking->booking_code,
				'note' => __( 'کارمزد ثابت رزرو تکمیل‌شده', 'mtn-cafe-booking' ),
				'actor_id' => absint( $actor_id ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);
		if ( false === $inserted ) {
			$existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::ledger_table() . ' WHERE entry_key = %s', $entry_key ) );
			return $existing ? true : new WP_Error( 'mtn_ledger_write_failed', __( 'ثبت کارمزد در دفتر مالی انجام نشد.', 'mtn-cafe-booking' ) );
		}
		$wpdb->update(
			MTN_CB_Bookings::table_name(),
			array( 'commission_amount' => number_format( $amount, 2, '.', '' ), 'commission_rate' => '0.0000', 'commission_currency' => $currency, 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => absint( $booking_id ) ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		do_action( 'mtn_cb_commission_accrued', $booking, $amount, $currency );
		return true;
	}

	public static function get_balance( $cafe_id ) {
		global $wpdb;
		return (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(amount), 0) FROM ' . self::ledger_table() . ' WHERE cafe_id = %d', absint( $cafe_id ) ) );
	}

	public static function menu() {
		add_submenu_page( 'mtn-cb-bookings', __( 'دفتر مالی و تسویه', 'mtn-cafe-booking' ), __( 'دفتر مالی', 'mtn-cafe-booking' ), 'manage_options', 'mtn-cb-finance', array( __CLASS__, 'render' ) );
	}

	public static function record_settlement() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی کافی ندارید.', 'mtn-cafe-booking' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'mtn_cb_record_settlement' );
		$cafe_id = isset( $_POST['cafe_id'] ) ? absint( $_POST['cafe_id'] ) : 0;
		$raw_amount = isset( $_POST['amount'] ) && is_scalar( $_POST['amount'] ) ? wp_unslash( $_POST['amount'] ) : '';
		$amount = is_numeric( $raw_amount ) ? round( (float) $raw_amount, 2 ) : 0;
		$reference = isset( $_POST['reference'] ) && is_scalar( $_POST['reference'] ) ? sanitize_text_field( wp_unslash( $_POST['reference'] ) ) : '';
		$note = isset( $_POST['note'] ) && is_scalar( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
		$cafe = get_post( $cafe_id );
		if ( ! $cafe || 'mtn_cafe' !== $cafe->post_type || 'publish' !== $cafe->post_status || $amount <= 0 ) {
			self::redirect( 'invalid' );
		}
		$balance = self::get_balance( $cafe_id );
		if ( $amount > $balance + 0.001 ) {
			self::redirect( 'exceeds_balance' );
		}
		global $wpdb;
		$currency = sanitize_text_field( get_option( 'mtn_cb_currency', 'TRY' ) );
		$now = current_time( 'mysql', true );
		$inserted = $wpdb->insert(
			self::settlements_table(),
			array( 'cafe_id' => $cafe_id, 'amount' => number_format( $amount, 2, '.', '' ), 'currency' => $currency, 'reference' => $reference, 'note' => $note, 'status' => 'paid', 'actor_id' => get_current_user_id(), 'created_at' => $now, 'paid_at' => $now ),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
		if ( false === $inserted ) {
			self::redirect( 'failed' );
		}
		$settlement_id = (int) $wpdb->insert_id;
		$ledger_inserted = $wpdb->insert(
			self::ledger_table(),
			array(
				'entry_key' => 'settlement_received_' . $settlement_id,
				'booking_id' => 0,
				'cafe_id' => $cafe_id,
				'entry_type' => 'settlement_received',
				'amount' => number_format( -$amount, 2, '.', '' ),
				'currency' => $currency,
				'reference' => $reference ? $reference : 'settlement-' . $settlement_id,
				'note' => $note,
				'actor_id' => get_current_user_id(),
				'created_at' => $now,
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);
		if ( false === $ledger_inserted ) {
			$wpdb->delete( self::settlements_table(), array( 'id' => $settlement_id ), array( '%d' ) );
			self::redirect( 'failed' );
		}
		self::redirect( 'settled' );
	}

	private static function redirect( $notice ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mtn-cb-finance', 'mtn_finance_notice' => $notice ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function export_ledger() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی کافی ندارید.', 'mtn-cafe-booking' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'mtn_cb_export_ledger' );
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT l.*, cafe.post_title AS cafe_title FROM ' . self::ledger_table() . ' l LEFT JOIN ' . $wpdb->posts . ' cafe ON cafe.ID = l.cafe_id ORDER BY l.id DESC LIMIT 10000', ARRAY_A );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=mtn-ledger-' . gmdate( 'Y-m-d' ) . '.csv' );
		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			exit;
		}
		fwrite( $output, "\xEF\xBB\xBF" );
		fputcsv( $output, array( 'id', 'created_at_utc', 'cafe', 'booking_id', 'entry_type', 'amount', 'currency', 'reference', 'note', 'actor_id' ) );
		foreach ( $rows as $row ) {
			fputcsv( $output, array( $row['id'], $row['created_at'], $row['cafe_title'], $row['booking_id'], $row['entry_type'], $row['amount'], $row['currency'], $row['reference'], $row['note'], $row['actor_id'] ) );
		}
		fclose( $output );
		exit;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		global $wpdb;
		$cafes = get_posts( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
		$entries = $wpdb->get_results( 'SELECT l.*, cafe.post_title AS cafe_title FROM ' . self::ledger_table() . ' l LEFT JOIN ' . $wpdb->posts . ' cafe ON cafe.ID = l.cafe_id ORDER BY l.id DESC LIMIT 200' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'دفتر مالی و تسویه', 'mtn-cafe-booking' ); ?></h1>
			<p><?php esc_html_e( 'کارمزد فقط هنگام تکمیل رزرو ثبت می‌شود. پرداخت ثبت‌شده در این صفحه یک ورودی منفی و مستقل در دفتر مالی ایجاد می‌کند؛ این سامانه پول را دریافت نمی‌کند.', 'mtn-cafe-booking' ); ?></p>
			<?php if ( isset( $_GET['mtn_finance_notice'] ) ) : ?>
				<?php $notice = sanitize_key( wp_unslash( $_GET['mtn_finance_notice'] ) ); ?>
				<div class="notice <?php echo 'settled' === $notice ? 'notice-success' : 'notice-warning'; ?> is-dismissible"><p><?php
					$messages = array( 'settled' => 'تسویه در دفتر ثبت شد.', 'invalid' => 'کافه یا مبلغ معتبر نیست.', 'exceeds_balance' => 'مبلغ تسویه از مانده حساب بیشتر است.', 'failed' => 'ثبت تسویه انجام نشد.' );
					echo esc_html( isset( $messages[ $notice ] ) ? $messages[ $notice ] : 'درخواست پردازش شد.' );
				?></p></div>
			<?php endif; ?>
			<h2><?php esc_html_e( 'ثبت تسویه دریافت‌شده', 'mtn-cafe-booking' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mtn_cb_record_settlement">
				<?php wp_nonce_field( 'mtn_cb_record_settlement' ); ?>
				<table class="form-table"><tbody>
					<tr><th><label for="mtn-settlement-cafe"><?php esc_html_e( 'کافه', 'mtn-cafe-booking' ); ?></label></th><td><select id="mtn-settlement-cafe" name="cafe_id" required><option value=""><?php esc_html_e( 'انتخاب کافه', 'mtn-cafe-booking' ); ?></option><?php foreach ( $cafes as $cafe ) : ?><option value="<?php echo (int) $cafe->ID; ?>"><?php echo esc_html( get_the_title( $cafe ) . ' — مانده: ' . number_format_i18n( self::get_balance( $cafe->ID ), 2 ) ); ?></option><?php endforeach; ?></select></td></tr>
					<tr><th><label for="mtn-settlement-amount"><?php esc_html_e( 'مبلغ', 'mtn-cafe-booking' ); ?></label></th><td><input id="mtn-settlement-amount" type="number" min="0.01" step="0.01" name="amount" required> <?php echo esc_html( get_option( 'mtn_cb_currency', 'TRY' ) ); ?></td></tr>
					<tr><th><label for="mtn-settlement-reference"><?php esc_html_e( 'شناسه رسید / مرجع', 'mtn-cafe-booking' ); ?></label></th><td><input class="regular-text" id="mtn-settlement-reference" name="reference" maxlength="100"></td></tr>
					<tr><th><label for="mtn-settlement-note"><?php esc_html_e( 'توضیح', 'mtn-cafe-booking' ); ?></label></th><td><textarea class="large-text" rows="2" id="mtn-settlement-note" name="note"></textarea></td></tr>
				</tbody></table>
				<?php submit_button( __( 'ثبت تسویه دریافت‌شده', 'mtn-cafe-booking' ) ); ?>
			</form>
			<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mtn_cb_export_ledger' ), 'mtn_cb_export_ledger' ) ); ?>"><?php esc_html_e( 'خروجی CSV دفتر مالی', 'mtn-cafe-booking' ); ?></a></p>
			<h2><?php esc_html_e( 'آخرین گردش‌های دفتر', 'mtn-cafe-booking' ); ?></h2>
			<table class="widefat striped"><thead><tr><th>ID</th><th><?php esc_html_e( 'زمان (UTC)', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'کافه', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'رزرو', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'نوع', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'مبلغ', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'مرجع', 'mtn-cafe-booking' ); ?></th></tr></thead><tbody>
			<?php if ( $entries ) : foreach ( $entries as $entry ) : ?><tr><td><?php echo (int) $entry->id; ?></td><td><?php echo esc_html( $entry->created_at ); ?></td><td><?php echo esc_html( $entry->cafe_title ? $entry->cafe_title : '—' ); ?></td><td><?php echo (int) $entry->booking_id; ?></td><td><?php echo esc_html( $entry->entry_type ); ?></td><td><?php echo esc_html( number_format_i18n( (float) $entry->amount, 2 ) . ' ' . $entry->currency ); ?></td><td><?php echo esc_html( $entry->reference ); ?></td></tr><?php endforeach; else : ?><tr><td colspan="7"><?php esc_html_e( 'هنوز گردش مالی ثبت نشده است.', 'mtn-cafe-booking' ); ?></td></tr><?php endif; ?>
			</tbody></table>
		</div>
		<?php
	}
}
