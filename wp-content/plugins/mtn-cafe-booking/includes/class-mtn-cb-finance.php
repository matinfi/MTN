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
		add_action( 'admin_post_mtn_cb_finance_adjustment', array( __CLASS__, 'record_adjustment' ) );
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
		if ( empty( $booking->id ) ) {
			return;
		}
		$trigger_status = isset( $booking->commission_trigger_status ) && in_array( $booking->commission_trigger_status, array( 'confirmed', 'completed' ), true ) ? $booking->commission_trigger_status : 'confirmed';
		if ( $trigger_status === $next_status ) {
			self::accrue_commission( absint( $booking->id ), absint( $actor_id ) );
		}
		$cancellation_statuses = array( 'cancelled_by_customer', 'cancelled_by_cafe', 'cancelled_by_admin' );
		$reverse_for_cancellation = in_array( $next_status, $cancellation_statuses, true ) && ! empty( $booking->commission_reverse_on_cancellation );
		$reverse_for_no_show = 'no_show' === $next_status && ! empty( $booking->commission_reverse_on_no_show );
		if ( $reverse_for_cancellation || $reverse_for_no_show ) {
			self::reverse_commission( absint( $booking->id ), absint( $actor_id ), $next_status, $reason );
		}
	}

	public static function accrue_commission( $booking_id, $actor_id = 0 ) {
		global $wpdb;
		$booking = MTN_CB_Bookings::get_by_id( $booking_id );
		$trigger_status = $booking && isset( $booking->commission_trigger_status ) && in_array( $booking->commission_trigger_status, array( 'confirmed', 'completed' ), true ) ? $booking->commission_trigger_status : 'confirmed';
		if ( ! $booking || $trigger_status !== $booking->status ) {
			return new WP_Error( 'mtn_commission_not_eligible', __( 'رزرو هنوز به وضعیت مشمول کارمزد نرسیده است.', 'mtn-cafe-booking' ) );
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
				'note' => __( 'کارمزد ثابت رزرو واجد شرایط', 'mtn-cafe-booking' ),
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

	public static function reverse_commission( $booking_id, $actor_id, $status, $reason = '' ) {
		global $wpdb;
		$booking = MTN_CB_Bookings::get_by_id( $booking_id );
		if ( ! $booking ) {
			return new WP_Error( 'mtn_commission_booking_missing', __( 'رزرو برای اصلاح کارمزد پیدا نشد.', 'mtn-cafe-booking' ) );
		}
		$entry_key = 'commission_booking_' . absint( $booking_id );
		$commission = $wpdb->get_row( $wpdb->prepare( 'SELECT amount, currency FROM ' . self::ledger_table() . ' WHERE entry_key = %s', $entry_key ) );
		if ( ! $commission || (float) $commission->amount <= 0 ) {
			return true;
		}
		$reversal_key = 'commission_reversal_booking_' . absint( $booking_id );
		$existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::ledger_table() . ' WHERE entry_key = %s', $reversal_key ) );
		if ( $existing ) {
			return true;
		}
		$details = sprintf( 'برگشت خودکار کارمزد به دلیل وضعیت %1$s%2$s', sanitize_key( $status ), '' !== trim( (string) $reason ) ? ': ' . sanitize_textarea_field( $reason ) : '' );
		$inserted = $wpdb->insert(
			self::ledger_table(),
			array(
				'entry_key' => $reversal_key,
				'booking_id' => absint( $booking_id ),
				'cafe_id' => absint( $booking->cafe_id ),
				'entry_type' => 'commission_reversal',
				'amount' => number_format( -1 * abs( (float) $commission->amount ), 2, '.', '' ),
				'currency' => sanitize_text_field( $commission->currency ),
				'reference' => $booking->booking_code,
				'note' => $details,
				'actor_id' => absint( $actor_id ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);
		if ( false === $inserted ) {
			$existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::ledger_table() . ' WHERE entry_key = %s', $reversal_key ) );
			return $existing ? true : new WP_Error( 'mtn_ledger_reversal_failed', __( 'ثبت برگشت کارمزد انجام نشد.', 'mtn-cafe-booking' ) );
		}
		do_action( 'mtn_cb_commission_reversed', $booking, $commission, $status, absint( $actor_id ) );
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
		$request_key = isset( $_POST['request_key'] ) && is_scalar( $_POST['request_key'] ) ? sanitize_text_field( wp_unslash( $_POST['request_key'] ) ) : '';
		$raw_amount = isset( $_POST['amount'] ) && is_scalar( $_POST['amount'] ) ? wp_unslash( $_POST['amount'] ) : '';
		$amount = is_numeric( $raw_amount ) && abs( (float) $raw_amount ) <= 9999999999.99 ? round( (float) $raw_amount, 2 ) : 0;
		$reference = isset( $_POST['reference'] ) && is_scalar( $_POST['reference'] ) ? sanitize_text_field( wp_unslash( $_POST['reference'] ) ) : '';
		$note = isset( $_POST['note'] ) && is_scalar( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
		$cafe = get_post( $cafe_id );
		if ( ! $cafe || 'mtn_cafe' !== $cafe->post_type || 'publish' !== $cafe->post_status || $amount <= 0 || ! preg_match( '/^[A-Za-z0-9._:-]{8,64}$/', $request_key ) ) {
			self::redirect( 'invalid' );
		}
		global $wpdb;
		$lock_name = 'mtn_cb_finance_' . $cafe_id;
		$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_name ) );
		if ( '1' !== (string) $locked ) {
			self::redirect( 'busy' );
		}
		$existing_settlement = $wpdb->get_row( $wpdb->prepare( 'SELECT id, cafe_id, amount, reference, note FROM ' . self::settlements_table() . ' WHERE request_key = %s', $request_key ) );
		if ( $existing_settlement ) {
			$same_request = (int) $existing_settlement->cafe_id === $cafe_id
				&& abs( (float) $existing_settlement->amount - $amount ) < 0.001
				&& (string) $existing_settlement->reference === $reference
				&& (string) $existing_settlement->note === $note;
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
			self::redirect( $same_request ? 'settled' : 'invalid' );
		}
		$balance = self::get_balance( $cafe_id );
		if ( $amount > $balance + 0.001 ) {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
			self::redirect( 'exceeds_balance' );
		}
		$currency = sanitize_text_field( get_option( 'mtn_cb_currency', 'TRY' ) );
		$now = current_time( 'mysql', true );
		$inserted = $wpdb->insert(
			self::settlements_table(),
			array( 'request_key' => $request_key, 'cafe_id' => $cafe_id, 'amount' => number_format( $amount, 2, '.', '' ), 'currency' => $currency, 'reference' => $reference, 'note' => $note, 'status' => 'paid', 'actor_id' => get_current_user_id(), 'created_at' => $now, 'paid_at' => $now ),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
		if ( false === $inserted ) {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
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
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
			self::redirect( 'failed' );
		}
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
		self::redirect( 'settled' );
	}

	public static function record_adjustment() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی کافی ندارید.', 'mtn-cafe-booking' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'mtn_cb_finance_adjustment' );
		$cafe_id = isset( $_POST['cafe_id'] ) ? absint( $_POST['cafe_id'] ) : 0;
		$request_key = isset( $_POST['request_key'] ) && is_scalar( $_POST['request_key'] ) ? sanitize_text_field( wp_unslash( $_POST['request_key'] ) ) : '';
		$raw_amount = isset( $_POST['amount'] ) && is_scalar( $_POST['amount'] ) ? wp_unslash( $_POST['amount'] ) : '';
		$amount = is_numeric( $raw_amount ) && abs( (float) $raw_amount ) <= 9999999999.99 ? round( (float) $raw_amount, 2 ) : 0;
		$reference = isset( $_POST['reference'] ) && is_scalar( $_POST['reference'] ) ? sanitize_text_field( wp_unslash( $_POST['reference'] ) ) : '';
		$note = isset( $_POST['note'] ) && is_scalar( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
		$cafe = get_post( $cafe_id );
		if ( ! $cafe || 'mtn_cafe' !== $cafe->post_type || 'publish' !== $cafe->post_status || 0.0 === $amount || '' === $note || ! preg_match( '/^[A-Za-z0-9._:-]{8,64}$/', $request_key ) ) {
			self::redirect( 'invalid_adjustment' );
		}
		global $wpdb;
		$lock_name = 'mtn_cb_finance_' . $cafe_id;
		$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_name ) );
		if ( '1' !== (string) $locked ) {
			self::redirect( 'busy' );
		}
		$entry_key = 'adjustment_' . $request_key;
		$existing = $wpdb->get_row( $wpdb->prepare( 'SELECT cafe_id, amount, reference, note FROM ' . self::ledger_table() . ' WHERE entry_key = %s', $entry_key ) );
		if ( $existing ) {
			$same_request = (int) $existing->cafe_id === $cafe_id && abs( (float) $existing->amount - $amount ) < 0.001 && (string) $existing->reference === $reference && (string) $existing->note === $note;
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
			self::redirect( $same_request ? 'adjusted' : 'invalid_adjustment' );
		}
		$inserted = $wpdb->insert(
			self::ledger_table(),
			array(
				'entry_key' => $entry_key,
				'booking_id' => 0,
				'cafe_id' => $cafe_id,
				'entry_type' => 'manual_adjustment',
				'amount' => number_format( $amount, 2, '.', '' ),
				'currency' => sanitize_text_field( get_option( 'mtn_cb_currency', 'TRY' ) ),
				'reference' => $reference,
				'note' => $note,
				'actor_id' => get_current_user_id(),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
		self::redirect( false === $inserted ? 'failed' : 'adjusted' );
	}

	private static function redirect( $notice ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mtn-cb-finance', 'mtn_finance_notice' => $notice ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private static function csv_text( $value ) {
		$value = (string) $value;
		if ( preg_match( '/^[=+@\\t\\r]/', $value ) || ( isset( $value[0] ) && '-' === $value[0] ) ) {
			return "'" . $value;
		}
		return $value;
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
			fputcsv( $output, array( $row['id'], $row['created_at'], self::csv_text( $row['cafe_title'] ), $row['booking_id'], self::csv_text( $row['entry_type'] ), $row['amount'], self::csv_text( $row['currency'] ), self::csv_text( $row['reference'] ), self::csv_text( $row['note'] ), $row['actor_id'] ) );
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
		$finance_summary = $wpdb->get_results(
			'SELECT cafe.ID AS cafe_id, cafe.post_title AS cafe_title, l.currency, '
			. "SUM(CASE WHEN l.entry_type = 'commission_accrued' THEN l.amount ELSE 0 END) AS accrued, "
			. "SUM(CASE WHEN l.entry_type = 'commission_reversal' THEN l.amount ELSE 0 END) AS reversed, "
			. "SUM(CASE WHEN l.entry_type = 'settlement_received' THEN -l.amount ELSE 0 END) AS settled, "
			. "SUM(CASE WHEN l.entry_type = 'manual_adjustment' THEN l.amount ELSE 0 END) AS adjustments, "
			. 'SUM(l.amount) AS outstanding '
			. 'FROM ' . self::ledger_table() . ' l INNER JOIN ' . $wpdb->posts . " cafe ON cafe.ID = l.cafe_id AND cafe.post_type = 'mtn_cafe' "
			. "WHERE cafe.post_status = 'publish' GROUP BY cafe.ID, cafe.post_title, l.currency ORDER BY cafe.post_title ASC"
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'دفتر مالی و تسویه', 'mtn-cafe-booking' ); ?></h1>
			<p><?php esc_html_e( 'کارمزد طبق وضعیت محرک ذخیره‌شده در هر رزرو ثبت می‌شود (تأیید کافه به‌صورت پیش‌فرض، یا تکمیل در صورت انتخاب مدیر). ثبت تسویه فقط یک رویداد حسابداری دستی است و به معنی دریافت خودکار پول توسط سامانه نیست.', 'mtn-cafe-booking' ); ?></p>
			<?php if ( isset( $_GET['mtn_finance_notice'] ) ) : ?>
				<?php $notice = sanitize_key( wp_unslash( $_GET['mtn_finance_notice'] ) ); ?>
				<div class="notice <?php echo 'settled' === $notice ? 'notice-success' : 'notice-warning'; ?> is-dismissible"><p><?php
					$messages = array( 'settled' => 'تسویه در دفتر ثبت شد.', 'invalid' => 'کافه یا مبلغ معتبر نیست.', 'exceeds_balance' => 'مبلغ تسویه از مانده حساب بیشتر است.', 'failed' => 'ثبت تسویه انجام نشد.', 'busy' => 'دفتر مالی مشغول است؛ دوباره تلاش کنید.', 'adjusted' => 'اصلاحیه به دفتر اضافه شد.', 'invalid_adjustment' => 'اطلاعات اصلاحیه معتبر نیست؛ دلیل را وارد کنید.' );
					echo esc_html( isset( $messages[ $notice ] ) ? $messages[ $notice ] : 'درخواست پردازش شد.' );
				?></p></div>
			<?php endif; ?>
			<h2><?php esc_html_e( 'گزارش تطبیق مانده کافه‌ها', 'mtn-cafe-booking' ); ?></h2>
			<p><?php esc_html_e( 'گزارش از جمع دفتر ثبت‌شده محاسبه می‌شود: کارمزد ناخالص، برگشت کارمزد، تسویه‌های ثبت‌شده، اصلاحیه‌ها و مانده خالص. این گزارش جایگزین تطبیق با صورت‌حساب بانکی نیست. به‌دلیل نبود مهلت سررسید قراردادی برای هر کافه، مبلغ معوق به‌طور خودکار محاسبه نمی‌شود.', 'mtn-cafe-booking' ); ?></p>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'کافه', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'ارز', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'کارمزد ثبت‌شده', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'برگشت کارمزد', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'تسویه ثبت‌شده', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'اصلاحیه خالص', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'مانده خالص', 'mtn-cafe-booking' ); ?></th></tr></thead><tbody>
			<?php if ( $finance_summary ) : foreach ( $finance_summary as $summary ) : ?><tr><td><?php echo esc_html( $summary->cafe_title ); ?></td><td><?php echo esc_html( $summary->currency ); ?></td><td><?php echo esc_html( number_format_i18n( (float) $summary->accrued, 2 ) ); ?></td><td><?php echo esc_html( number_format_i18n( (float) $summary->reversed, 2 ) ); ?></td><td><?php echo esc_html( number_format_i18n( (float) $summary->settled, 2 ) ); ?></td><td><?php echo esc_html( number_format_i18n( (float) $summary->adjustments, 2 ) ); ?></td><td><strong><?php echo esc_html( number_format_i18n( (float) $summary->outstanding, 2 ) ); ?></strong></td></tr><?php endforeach; else : ?><tr><td colspan="7"><?php esc_html_e( 'هنوز گردش مالی برای کافه‌های منتشرشده ثبت نشده است.', 'mtn-cafe-booking' ); ?></td></tr><?php endif; ?>
			</tbody></table>
			<h2><?php esc_html_e( 'ثبت تسویه دریافت‌شده', 'mtn-cafe-booking' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mtn_cb_record_settlement">
				<input type="hidden" name="request_key" value="<?php echo esc_attr( wp_generate_uuid4() ); ?>">
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
			<h2><?php esc_html_e( 'ثبت اصلاحیه دفتر (غیرقابل‌ویرایش)', 'mtn-cafe-booking' ); ?></h2>
			<p><?php esc_html_e( 'مبلغ مثبت بدهی کافه را افزایش می‌دهد؛ مبلغ منفی برای اصلاح یا برگشت ثبت قبلی است. دلیل ثبت الزامی است.', 'mtn-cafe-booking' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mtn_cb_finance_adjustment">
				<input type="hidden" name="request_key" value="<?php echo esc_attr( wp_generate_uuid4() ); ?>">
				<?php wp_nonce_field( 'mtn_cb_finance_adjustment' ); ?>
				<table class="form-table"><tbody>
					<tr><th><label for="mtn-adjust-cafe"><?php esc_html_e( 'کافه', 'mtn-cafe-booking' ); ?></label></th><td><select id="mtn-adjust-cafe" name="cafe_id" required><option value=""><?php esc_html_e( 'انتخاب کافه', 'mtn-cafe-booking' ); ?></option><?php foreach ( $cafes as $cafe ) : ?><option value="<?php echo (int) $cafe->ID; ?>"><?php echo esc_html( get_the_title( $cafe ) ); ?></option><?php endforeach; ?></select></td></tr>
					<tr><th><label for="mtn-adjust-amount"><?php esc_html_e( 'مبلغ مثبت یا منفی', 'mtn-cafe-booking' ); ?></label></th><td><input id="mtn-adjust-amount" type="number" step="0.01" name="amount" required> <?php echo esc_html( get_option( 'mtn_cb_currency', 'TRY' ) ); ?></td></tr>
					<tr><th><label for="mtn-adjust-reference"><?php esc_html_e( 'مرجع', 'mtn-cafe-booking' ); ?></label></th><td><input class="regular-text" id="mtn-adjust-reference" name="reference" maxlength="100"></td></tr>
					<tr><th><label for="mtn-adjust-note"><?php esc_html_e( 'دلیل اصلاحیه (الزامی)', 'mtn-cafe-booking' ); ?></label></th><td><textarea class="large-text" rows="2" id="mtn-adjust-note" name="note" required></textarea></td></tr>
				</tbody></table>
				<?php submit_button( __( 'ثبت اصلاحیه جدید', 'mtn-cafe-booking' ) ); ?>
			</form>
			<h2><?php esc_html_e( 'آخرین گردش‌های دفتر', 'mtn-cafe-booking' ); ?></h2>
			<table class="widefat striped"><thead><tr><th>ID</th><th><?php esc_html_e( 'زمان (UTC)', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'کافه', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'رزرو', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'نوع', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'مبلغ', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'مرجع', 'mtn-cafe-booking' ); ?></th></tr></thead><tbody>
			<?php if ( $entries ) : foreach ( $entries as $entry ) : ?><tr><td><?php echo (int) $entry->id; ?></td><td><?php echo esc_html( $entry->created_at ); ?></td><td><?php echo esc_html( $entry->cafe_title ? $entry->cafe_title : '—' ); ?></td><td><?php echo (int) $entry->booking_id; ?></td><td><?php echo esc_html( $entry->entry_type ); ?></td><td><?php echo esc_html( number_format_i18n( (float) $entry->amount, 2 ) . ' ' . $entry->currency ); ?></td><td><?php echo esc_html( $entry->reference ); ?></td></tr><?php endforeach; else : ?><tr><td colspan="7"><?php esc_html_e( 'هنوز گردش مالی ثبت نشده است.', 'mtn-cafe-booking' ); ?></td></tr><?php endif; ?>
			</tbody></table>
		</div>
		<?php
	}
}
