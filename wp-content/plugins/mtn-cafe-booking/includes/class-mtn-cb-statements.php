<?php
/**
 * Periodic cafe statements and due-date-aware overdue balance calculation.
 *
 * @package MTN_Cafe_Booking
 */
defined( 'ABSPATH' ) || exit;

final class MTN_CB_Statements {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'mtn_cb_generate_monthly_statements', array( __CLASS__, 'generate_monthly_statements' ) );
		if ( ! wp_next_scheduled( 'mtn_cb_generate_monthly_statements' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mtn_cb_generate_monthly_statements' );
		}
	}

	public static function menu() {
		add_submenu_page( 'mtn-cb-bookings', __( 'صورت‌حساب کافه‌ها', 'mtn-cafe-booking' ), __( 'صورت‌حساب کافه‌ها', 'mtn-cafe-booking' ), 'manage_options', 'mtn-cb-statements', array( __CLASS__, 'render' ) );
	}

	private static function due_days() {
		return min( 90, max( 1, absint( get_option( 'mtn_cb_statement_due_days', 7 ) ) ) );
	}

	private static function due_timestamp( $created_at ) {
		try {
			$date = ( new DateTimeImmutable( $created_at, new DateTimeZone( 'UTC' ) ) )->setTimezone( wp_timezone() );
		} catch ( Exception $exception ) {
			return time();
		}
		$end_of_month = $date->modify( 'first day of next month' )->modify( '-1 second' );
		return $end_of_month->modify( '+' . self::due_days() . ' days' )->getTimestamp();
	}

	public static function calculate_overdue( $cafe_id, $currency, $as_of = null ) {
		global $wpdb;
		$as_of = null === $as_of ? time() : absint( $as_of );
		$entries = $wpdb->get_results( $wpdb->prepare(
			'SELECT id, booking_id, entry_type, amount, created_at FROM ' . MTN_CB_Finance::ledger_table() . ' WHERE cafe_id = %d AND currency = %s ORDER BY created_at ASC, id ASC',
			absint( $cafe_id ),
			sanitize_text_field( $currency )
		) );
		$buckets = array();
		foreach ( $entries as $entry ) {
			$amount = round( (float) $entry->amount, 2 );
			if ( $amount > 0 && in_array( $entry->entry_type, array( 'commission_accrued', 'manual_adjustment' ), true ) ) {
				$buckets[] = array(
					'booking_id' => absint( $entry->booking_id ),
					'remaining' => $amount,
					'due_at' => self::due_timestamp( $entry->created_at ),
					'created_at' => $entry->created_at,
				);
				continue;
			}
			if ( $amount >= 0 ) {
				continue;
			}
			$credit = abs( $amount );
			if ( 'commission_reversal' === $entry->entry_type && absint( $entry->booking_id ) ) {
				foreach ( $buckets as &$bucket ) {
					if ( $credit <= 0 ) {
						break;
					}
					if ( $bucket['booking_id'] === absint( $entry->booking_id ) && $bucket['remaining'] > 0 ) {
						$applied = min( $bucket['remaining'], $credit );
						$bucket['remaining'] = round( $bucket['remaining'] - $applied, 2 );
						$credit = round( $credit - $applied, 2 );
					}
				}
				unset( $bucket );
			}
			foreach ( $buckets as &$bucket ) {
				if ( $credit <= 0 ) {
					break;
				}
				if ( $bucket['remaining'] > 0 ) {
					$applied = min( $bucket['remaining'], $credit );
					$bucket['remaining'] = round( $bucket['remaining'] - $applied, 2 );
					$credit = round( $credit - $applied, 2 );
				}
			}
			unset( $bucket );
		}
		$overdue = 0.0;
		foreach ( $buckets as $bucket ) {
			if ( $bucket['due_at'] < $as_of ) {
				$overdue += (float) $bucket['remaining'];
			}
		}
		return round( max( 0, $overdue ), 2 );
	}

	private static function period_summary( $cafe_id, $currency, $start, $end ) {
		global $wpdb;
		$ledger = MTN_CB_Finance::ledger_table();
		$period = $wpdb->get_row( $wpdb->prepare(
			"SELECT
				SUM(CASE WHEN entry_type = 'commission_accrued' THEN amount ELSE 0 END) AS accrued,
				SUM(CASE WHEN entry_type = 'commission_reversal' THEN amount ELSE 0 END) AS reversals,
				SUM(CASE WHEN entry_type = 'settlement_received' THEN -amount ELSE 0 END) AS settlements,
				SUM(CASE WHEN entry_type = 'manual_adjustment' THEN amount ELSE 0 END) AS adjustments
			FROM {$ledger} WHERE cafe_id = %d AND currency = %s AND created_at >= %s AND created_at < %s",
			absint( $cafe_id ), sanitize_text_field( $currency ), $start, $end
		) );
		$closing = (float) $wpdb->get_var( $wpdb->prepare(
			"SELECT COALESCE(SUM(amount),0) FROM {$ledger} WHERE cafe_id = %d AND currency = %s AND created_at < %s",
			absint( $cafe_id ), sanitize_text_field( $currency ), $end
		) );
		return array(
			'accrued' => (float) $period->accrued,
			'reversals' => (float) $period->reversals,
			'settlements' => (float) $period->settlements,
			'adjustments' => (float) $period->adjustments,
			'closing' => $closing,
		);
	}

	public static function generate_monthly_statements() {
		global $wpdb;
		$now = new DateTimeImmutable( 'first day of this month 00:00:00', wp_timezone() );
		$period_start = $now->modify( '-1 month' );
		$period_end = $now;
		$start_utc = $period_start->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		$end_utc = $period_end->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		$period_key = $period_start->format( 'Ym' );
		$cafes = get_posts( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish', 'posts_per_page' => 500 ) );
		foreach ( $cafes as $cafe ) {
			$email = sanitize_email( get_post_meta( $cafe->ID, 'mtn_cafe_email', true ) );
			if ( ! is_email( $email ) ) {
				continue;
			}
			$currencies = $wpdb->get_col( $wpdb->prepare(
				'SELECT DISTINCT currency FROM ' . MTN_CB_Finance::ledger_table() . ' WHERE cafe_id = %d AND created_at < %s',
				absint( $cafe->ID ), $end_utc
			) );
			foreach ( $currencies as $currency ) {
				$key = 'statement_cafe_' . absint( $cafe->ID ) . '_' . $period_key . '_' . sanitize_key( $currency );
				$exists = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . MTN_CB_Notifications::table_name() . ' WHERE dedupe_key = %s', $key ) );
				if ( $exists ) {
					continue;
				}
				$summary = self::period_summary( $cafe->ID, $currency, $start_utc, $end_utc );
				$due_at = $period_end->modify( '-1 day' )->modify( '+' . self::due_days() . ' days' )->format( 'Y-m-d' );
				$overdue = self::calculate_overdue( $cafe->ID, $currency );
				$body = sprintf(
					"صورت‌حساب ماهانه کافه: %s\nدوره: %s تا %s\nارز: %s\nکارمزد ثبت‌شده: %s\nبرگشت کارمزد: %s\nتسویه‌های ثبت‌شده: %s\nاصلاحیه خالص: %s\nمانده دفتر در پایان دوره: %s\nمانده معوق بر اساس مهلت %d روزه: %s\nتاریخ سررسید این صورت‌حساب: %s\n\nاین گزارش از دفتر داخلی محاسبه شده و جایگزین تطبیق با رسید بانکی نیست.",
					$cafe->post_title, $period_start->format( 'Y-m-d' ), $period_end->modify( '-1 day' )->format( 'Y-m-d' ), $currency,
					number_format( $summary['accrued'], 2, '.', '' ), number_format( $summary['reversals'], 2, '.', '' ),
					number_format( $summary['settlements'], 2, '.', '' ), number_format( $summary['adjustments'], 2, '.', '' ),
					number_format( $summary['closing'], 2, '.', '' ), self::due_days(), number_format( $overdue, 2, '.', '' ), $due_at
				);
				MTN_CB_Notifications::enqueue_email( $key, 0, $email, sprintf( 'صورت‌حساب ماهانه %s — %s', $cafe->post_title, $period_start->format( 'Y-m' ) ), $body );
			}
		}
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$cafes = get_posts( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish', 'posts_per_page' => 500, 'orderby' => 'title', 'order' => 'ASC' ) );
		global $wpdb;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'صورت‌حساب و مانده معوق کافه‌ها', 'mtn-cafe-booking' ); ?></h1>
			<p><?php echo esc_html( sprintf( __( 'سررسید کارمزدها پایان ماه ثبت به‌علاوه %d روز است. صورت‌حساب ماه قبل به صف ایمیل افزوده می‌شود؛ ارسال واقعی به پیکربندی ایمیل سایت وابسته است.', 'mtn-cafe-booking' ), self::due_days() ) ); ?></p>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'کافه', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'ایمیل صورت‌حساب', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'ارز', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'مانده دفتر', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'معوق', 'mtn-cafe-booking' ); ?></th></tr></thead><tbody>
			<?php foreach ( $cafes as $cafe ) :
				$currencies = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT currency FROM ' . MTN_CB_Finance::ledger_table() . ' WHERE cafe_id = %d', absint( $cafe->ID ) ) );
				if ( empty( $currencies ) ) : ?><tr><td><?php echo esc_html( $cafe->post_title ); ?></td><td><?php echo esc_html( get_post_meta( $cafe->ID, 'mtn_cafe_email', true ) ); ?></td><td colspan="3"><?php esc_html_e( 'گردش مالی ثبت نشده است.', 'mtn-cafe-booking' ); ?></td></tr><?php endif;
				foreach ( $currencies as $currency ) : ?>
				<tr><td><?php echo esc_html( $cafe->post_title ); ?></td><td><?php echo esc_html( get_post_meta( $cafe->ID, 'mtn_cafe_email', true ) ); ?></td><td><?php echo esc_html( $currency ); ?></td><td><?php echo esc_html( number_format_i18n( $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(amount),0) FROM ' . MTN_CB_Finance::ledger_table() . ' WHERE cafe_id = %d AND currency = %s', absint( $cafe->ID ), $currency ) ), 2 ) ); ?></td><td><strong><?php echo esc_html( number_format_i18n( self::calculate_overdue( $cafe->ID, $currency ), 2 ) ); ?></strong></td></tr>
				<?php endforeach; endforeach; ?>
			</tbody></table>
		</div>
		<?php
	}
}
