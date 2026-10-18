<?php
/**
 * Administrator booking operations.
 *
 * @package MTN_Cafe_Booking
 */

defined( 'ABSPATH' ) || exit;

final class MTN_CB_Admin {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_mtn_cb_booking_action', array( __CLASS__, 'handle_booking_action' ) );
	}

	public static function menu() {
		add_menu_page(
			__( 'مدیریت رزروها', 'mtn-cafe-booking' ),
			__( 'رزروهای MTN', 'mtn-cafe-booking' ),
			'manage_options',
			'mtn-cb-bookings',
			array( __CLASS__, 'render_bookings' ),
			'dashicons-calendar-alt',
			26
		);
	}

	public static function handle_booking_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی کافی ندارید.', 'mtn-cafe-booking' ), '', array( 'response' => 403 ) );
		}
		$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
		check_admin_referer( 'mtn_cb_admin_booking_' . $booking_id );
		$next = isset( $_POST['next_status'] ) ? sanitize_key( wp_unslash( $_POST['next_status'] ) ) : '';
		$reason = isset( $_POST['reason'] ) && is_scalar( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
		$allowed = array( 'confirmed', 'rejected', 'cancelled_by_admin', 'completed', 'no_show' );
		if ( ! $booking_id || ! in_array( $next, $allowed, true ) ) {
			self::redirect_with_notice( 'invalid' );
		}
		$result = MTN_CB_Bookings::transition( $booking_id, $next, get_current_user_id(), $reason );
		self::redirect_with_notice( is_wp_error( $result ) ? 'failed' : 'updated' );
	}

	private static function redirect_with_notice( $notice ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mtn-cb-bookings', 'mtn_notice' => $notice ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function render_bookings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		global $wpdb;
		$table = MTN_CB_Bookings::table_name();
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$valid_statuses = MTN_CB_Bookings::STATUSES;
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			$status = '';
		}
		$sql = 'SELECT b.*, cafe.post_title AS cafe_title, ev.post_title AS event_title FROM ' . $table . ' b LEFT JOIN ' . $wpdb->posts . ' cafe ON cafe.ID = b.cafe_id LEFT JOIN ' . $wpdb->posts . ' ev ON ev.ID = b.event_id';
		if ( $status ) {
			$sql .= $wpdb->prepare( ' WHERE b.status = %s', $status );
		}
		$sql .= ' ORDER BY b.created_at DESC LIMIT 200';
		$bookings = $wpdb->get_results( $sql );
		$summary_rows = $wpdb->get_results( 'SELECT status, COUNT(*) AS total FROM ' . $table . ' GROUP BY status' );
		$summary = array( 'all' => 0, 'pending_cafe' => 0, 'confirmed' => 0, 'completed' => 0 );
		foreach ( $summary_rows as $summary_row ) {
			$summary['all'] += (int) $summary_row->total;
			if ( isset( $summary[ $summary_row->status ] ) ) { $summary[ $summary_row->status ] = (int) $summary_row->total; }
		}
		$cooperation_statuses = wp_count_posts( 'mtn_cooperation_request' );
		$cooperation_count = ( is_object( $cooperation_statuses ) && isset( $cooperation_statuses->private ) ) ? (int) $cooperation_statuses->private : 0;
		$labels = array(
			'pending_cafe' => 'در انتظار تأیید',
			'confirmed' => 'تأیید شده',
			'rejected' => 'رد شده',
			'cancelled_by_customer' => 'لغو توسط مشتری',
			'cancelled_by_cafe' => 'لغو توسط کافه',
			'cancelled_by_admin' => 'لغو توسط مدیر',
			'expired' => 'منقضی شده',
			'completed' => 'تکمیل شده',
			'no_show' => 'عدم مراجعه',
		);
		?>
		<div class="wrap mtn-admin-dashboard">
			<div class="mtn-admin-heading"><div><p class="mtn-admin-kicker">MTN · CONTROL CENTER</p><h1><?php esc_html_e( 'مدیریت رزروها', 'mtn-cafe-booking' ); ?></h1><p><?php esc_html_e( 'نمای کلی فعالیت‌ها و دسترسی سریع به درخواست‌های مهم پلتفرم.', 'mtn-cafe-booking' ); ?></p></div><a class="button button-primary mtn-admin-inbox-link" href="<?php echo esc_url( admin_url( 'edit.php?post_type=mtn_cooperation_request' ) ); ?>"><span class="dashicons dashicons-email-alt" aria-hidden="true"></span><?php echo esc_html( sprintf( 'درخواست‌های همکاری (%d)', $cooperation_count ) ); ?></a></div>
			<div class="mtn-admin-metrics" aria-label="خلاصه رزروها">
				<div class="mtn-admin-metric"><span>کل رزروها</span><strong><?php echo (int) $summary['all']; ?></strong><small>تمام وضعیت‌ها</small></div>
				<div class="mtn-admin-metric is-pending"><span>در انتظار تأیید</span><strong><?php echo (int) $summary['pending_cafe']; ?></strong><small>نیازمند پیگیری</small></div>
				<div class="mtn-admin-metric is-confirmed"><span>تأیید شده</span><strong><?php echo (int) $summary['confirmed']; ?></strong><small>رزروهای فعال</small></div>
				<div class="mtn-admin-metric is-complete"><span>تکمیل شده</span><strong><?php echo (int) $summary['completed']; ?></strong><small>رزروهای پایان‌یافته</small></div>
			</div>
			<p class="mtn-admin-notice-line"><?php esc_html_e( 'این صفحه فعلاً مخصوص مدیران وردپرس است؛ دسترسی مدیران کافه تا تکمیل مجوزهای مالکیت فعال نیست.', 'mtn-cafe-booking' ); ?></p>
			<?php if ( isset( $_GET['mtn_notice'] ) ) : ?>
				<div class="notice <?php echo 'updated' === sanitize_key( wp_unslash( $_GET['mtn_notice'] ) ) ? 'notice-success' : 'notice-warning'; ?> is-dismissible"><p><?php
					$notice = sanitize_key( wp_unslash( $_GET['mtn_notice'] ) );
					echo esc_html( 'updated' === $notice ? 'وضعیت رزرو به‌روزرسانی شد.' : ( 'invalid' === $notice ? 'درخواست معتبر نیست.' : 'تغییر وضعیت انجام نشد؛ وضعیت فعلی رزرو را بررسی کنید.' ) );
				?></p></div>
			<?php endif; ?>
			<form method="get" style="margin:1rem 0">
				<input type="hidden" name="page" value="mtn-cb-bookings">
				<label for="mtn-booking-status"><?php esc_html_e( 'فیلتر وضعیت', 'mtn-cafe-booking' ); ?></label>
				<select id="mtn-booking-status" name="status"><option value=""><?php esc_html_e( 'همه وضعیت‌ها', 'mtn-cafe-booking' ); ?></option>
					<?php foreach ( $valid_statuses as $item ) : ?><option value="<?php echo esc_attr( $item ); ?>" <?php selected( $status, $item ); ?>><?php echo esc_html( isset( $labels[ $item ] ) ? $labels[ $item ] : $item ); ?></option><?php endforeach; ?>
				</select>
				<?php submit_button( __( 'اعمال فیلتر', 'mtn-cafe-booking' ), 'secondary', '', false ); ?>
			</form>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'کد پیگیری', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'کافه / برنامه', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'زمان (UTC)', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'نفرات', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'وضعیت', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'عملیات', 'mtn-cafe-booking' ); ?></th></tr></thead>
				<tbody>
				<?php if ( $bookings ) : foreach ( $bookings as $booking ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $booking->booking_code ); ?></strong><br><small>#<?php echo (int) $booking->id; ?></small></td>
						<td><?php echo esc_html( $booking->cafe_title ? $booking->cafe_title : '—' ); ?><br><small><?php echo esc_html( $booking->event_title ? $booking->event_title : '—' ); ?></small></td>
						<td><?php echo esc_html( $booking->booking_at ); ?></td>
						<td><?php echo (int) $booking->party_size; ?></td>
						<td><?php echo esc_html( isset( $labels[ $booking->status ] ) ? $labels[ $booking->status ] : $booking->status ); ?></td>
						<td>
							<?php if ( in_array( $booking->status, array( 'pending_cafe', 'confirmed' ), true ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:.35rem;align-items:center;flex-wrap:wrap">
									<input type="hidden" name="action" value="mtn_cb_booking_action">
									<input type="hidden" name="booking_id" value="<?php echo (int) $booking->id; ?>">
									<?php wp_nonce_field( 'mtn_cb_admin_booking_' . (int) $booking->id ); ?>
									<?php if ( 'pending_cafe' === $booking->status ) : ?><button class="button button-primary" name="next_status" value="confirmed"><?php esc_html_e( 'تأیید', 'mtn-cafe-booking' ); ?></button><button class="button" name="next_status" value="rejected"><?php esc_html_e( 'رد', 'mtn-cafe-booking' ); ?></button><?php endif; ?>
									<?php if ( 'confirmed' === $booking->status ) : ?><button class="button button-primary" name="next_status" value="completed"><?php esc_html_e( 'تکمیل شد', 'mtn-cafe-booking' ); ?></button><button class="button" name="next_status" value="no_show"><?php esc_html_e( 'عدم مراجعه', 'mtn-cafe-booking' ); ?></button><?php endif; ?>
									<button class="button" name="next_status" value="cancelled_by_admin" onclick="return confirm('<?php echo esc_js( __( 'از لغو این رزرو مطمئن هستید؟', 'mtn-cafe-booking' ) ); ?>')"><?php esc_html_e( 'لغو', 'mtn-cafe-booking' ); ?></button>
									<input type="text" name="reason" placeholder="<?php esc_attr_e( 'دلیل (اختیاری)', 'mtn-cafe-booking' ); ?>" aria-label="<?php esc_attr_e( 'دلیل تغییر وضعیت', 'mtn-cafe-booking' ); ?>">
								</form>
							<?php else : ?>—<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; else : ?><tr><td colspan="6"><?php esc_html_e( 'رزروی برای نمایش وجود ندارد.', 'mtn-cafe-booking' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
