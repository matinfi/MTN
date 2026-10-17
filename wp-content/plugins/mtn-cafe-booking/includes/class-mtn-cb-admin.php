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
			__( 'داشبورد MTN', 'mtn-cafe-booking' ),
			__( 'MTN', 'mtn-cafe-booking' ),
			'manage_options',
			'mtn-cb-dashboard',
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-calendar-alt',
			26
		);
		add_submenu_page( 'mtn-cb-dashboard', __( 'داشبورد', 'mtn-cafe-booking' ), __( 'داشبورد', 'mtn-cafe-booking' ), 'manage_options', 'mtn-cb-dashboard', array( __CLASS__, 'render_dashboard' ) );
		add_submenu_page( 'mtn-cb-dashboard', __( 'مدیریت رزروها', 'mtn-cafe-booking' ), __( 'رزروها', 'mtn-cafe-booking' ), 'manage_options', 'mtn-cb-bookings', array( __CLASS__, 'render_bookings' ) );
	}

	public static function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		global $wpdb;
		$bookings_table = MTN_CB_Bookings::table_name();
		$total_bookings = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings_table}" );
		$pending_bookings = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bookings_table} WHERE status = %s", 'pending_cafe' ) );
		$pending_deposits = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bookings_table} WHERE deposit_status = %s", 'under_review' ) );
		$published_cafes = (int) wp_count_posts( 'mtn_cafe' )->publish;
		$published_events = (int) wp_count_posts( 'mtn_event' )->publish;
		$pending_applications = (int) wp_count_posts( 'mtn_partner_application' )->pending;
		$bank_configured = '' !== trim( (string) get_option( 'mtn_cb_bank_account_name', '' ) ) && '' !== trim( (string) get_option( 'mtn_cb_bank_iban', '' ) );
		?>
		<div class="wrap mtn-admin-dashboard">
			<style>
				.mtn-admin-shell{display:grid;grid-template-columns:230px minmax(0,1fr);gap:20px;margin-top:18px;color:#f7f4ef}
				.mtn-admin-side{background:#17171e;border:1px solid #302e38;border-radius:14px;padding:18px;min-width:0}
				.mtn-admin-side nav{display:grid;gap:8px}.mtn-admin-side a{display:block;padding:10px 12px;border-radius:8px;color:#f7f4ef;text-decoration:none}.mtn-admin-side a:hover,.mtn-admin-side a:focus{background:#302e38;color:#f4bb5e}
				.mtn-admin-main{min-width:0;background:#101014;border-radius:14px;padding:clamp(14px,2vw,24px)}
				.mtn-admin-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}
				.mtn-admin-card{background:#1b1b22;border:1px solid #302e38;border-radius:14px;padding:18px;min-width:0}
				.mtn-admin-card strong{display:block;font-size:1.8rem;line-height:1.3;margin:.4rem 0;color:#f4bb5e}
				.mtn-admin-notice{padding:12px 14px;border-radius:10px;background:#33271a;border:1px solid #7b5c2d;margin:14px 0}
				.mtn-admin-toggle{display:inline-flex;margin-bottom:12px}
				.mtn-admin-side[data-collapsed="true"]{width:72px;padding:10px}.mtn-admin-side[data-collapsed="true"] .mtn-admin-nav-label{display:none}
				@media(max-width:782px){.mtn-admin-shell{grid-template-columns:1fr}.mtn-admin-toggle{display:inline-flex}.mtn-admin-side[data-mobile-open="false"]{display:none}.mtn-admin-side[data-mobile-open="true"]{display:block}.mtn-admin-side[data-collapsed="true"]{width:auto}.mtn-admin-side[data-collapsed="true"] .mtn-admin-nav-label{display:inline}}
			</style>
			<h1><?php esc_html_e( 'داشبورد مدیریت MTN', 'mtn-cafe-booking' ); ?></h1>
			<p><?php esc_html_e( 'مدیریت متمرکز برنامه‌ها، رزروها، درخواست‌های همکاری و پیش‌پرداخت‌ها.', 'mtn-cafe-booking' ); ?></p>
			<div class="mtn-admin-shell">
				<aside class="mtn-admin-side" data-collapsed="false" data-mobile-open="true">
					<button type="button" class="button mtn-admin-toggle" aria-expanded="true" aria-controls="mtn-admin-nav">نمایش/بستن منو</button>
					<nav id="mtn-admin-nav" aria-label="ناوبری داشبورد MTN">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=mtn-cb-dashboard' ) ); ?>"><span class="mtn-admin-nav-label">نمای کلی</span></a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=mtn-cb-bookings' ) ); ?>"><span class="mtn-admin-nav-label">رزروها و پیش‌پرداخت</span></a>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mtn_cafe' ) ); ?>"><span class="mtn-admin-nav-label">کافه‌ها و رستوران‌ها</span></a>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mtn_event' ) ); ?>"><span class="mtn-admin-nav-label">برنامه‌ها و تأیید انتشار</span></a>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mtn_artist' ) ); ?>"><span class="mtn-admin-nav-label">هنرمندان</span></a>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mtn_partner_application' ) ); ?>"><span class="mtn-admin-nav-label">درخواست‌های همکاری</span></a>
						<a href="<?php echo esc_url( admin_url( 'options-general.php?page=mtn-cafe-booking' ) ); ?>"><span class="mtn-admin-nav-label">تنظیمات و بانک</span></a>
					</nav>
				</aside>
				<main class="mtn-admin-main">
					<?php if ( ! $bank_configured ) : ?><div class="mtn-admin-notice" role="alert"><strong><?php esc_html_e( 'دریافت رزرو مسدود است.', 'mtn-cafe-booking' ); ?></strong> <?php esc_html_e( 'نام صاحب حساب و IBAN معتبر را در تنظیمات وارد کنید؛ بدون حساب مقصد، ثبت رزرو از سمت سرور رد می‌شود.', 'mtn-cafe-booking' ); ?> <a href="<?php echo esc_url( admin_url( 'options-general.php?page=mtn-cafe-booking' ) ); ?>"><?php esc_html_e( 'رفتن به تنظیمات', 'mtn-cafe-booking' ); ?></a></div><?php endif; ?>
					<div class="mtn-admin-cards">
						<div class="mtn-admin-card"><span><?php esc_html_e( 'کل رزروها', 'mtn-cafe-booking' ); ?></span><strong><?php echo (int) $total_bookings; ?></strong><a href="<?php echo esc_url( admin_url( 'admin.php?page=mtn-cb-bookings' ) ); ?>"><?php esc_html_e( 'مشاهده رزروها', 'mtn-cafe-booking' ); ?></a></div>
						<div class="mtn-admin-card"><span><?php esc_html_e( 'در انتظار تصمیم مدیر', 'mtn-cafe-booking' ); ?></span><strong><?php echo (int) $pending_bookings; ?></strong><a href="<?php echo esc_url( admin_url( 'admin.php?page=mtn-cb-bookings&status=pending_cafe' ) ); ?>"><?php esc_html_e( 'بررسی درخواست‌ها', 'mtn-cafe-booking' ); ?></a></div>
						<div class="mtn-admin-card"><span><?php esc_html_e( 'انتقال‌های در انتظار تطبیق', 'mtn-cafe-booking' ); ?></span><strong><?php echo (int) $pending_deposits; ?></strong><a href="<?php echo esc_url( admin_url( 'admin.php?page=mtn-cb-bookings' ) ); ?>"><?php esc_html_e( 'تطبیق رسیدها', 'mtn-cafe-booking' ); ?></a></div>
						<div class="mtn-admin-card"><span><?php esc_html_e( 'کافه/رستوران منتشرشده', 'mtn-cafe-booking' ); ?></span><strong><?php echo (int) $published_cafes; ?></strong><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mtn_cafe' ) ); ?>"><?php esc_html_e( 'مدیریت مکان‌ها', 'mtn-cafe-booking' ); ?></a></div>
						<div class="mtn-admin-card"><span><?php esc_html_e( 'برنامه منتشرشده', 'mtn-cafe-booking' ); ?></span><strong><?php echo (int) $published_events; ?></strong><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mtn_event' ) ); ?>"><?php esc_html_e( 'مدیریت برنامه‌ها', 'mtn-cafe-booking' ); ?></a></div>
						<div class="mtn-admin-card"><span><?php esc_html_e( 'درخواست همکاری در صف', 'mtn-cafe-booking' ); ?></span><strong><?php echo (int) $pending_applications; ?></strong><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mtn_partner_application' ) ); ?>"><?php esc_html_e( 'بررسی همکاری‌ها', 'mtn-cafe-booking' ); ?></a></div>
					</div>
				</main>
			</div>
			<script>
				(function(){var shell=document.querySelector('.mtn-admin-shell');if(!shell)return;var side=shell.querySelector('.mtn-admin-side');var toggle=shell.querySelector('.mtn-admin-toggle');if(!toggle)return;toggle.addEventListener('click',function(){if(window.matchMedia('(max-width: 782px)').matches){var open=side.getAttribute('data-mobile-open')==='true';side.setAttribute('data-mobile-open',open?'false':'true');shell.style.gridTemplateColumns='';toggle.setAttribute('aria-expanded',open?'false':'true');}else{var collapsed=side.getAttribute('data-collapsed')==='true';side.setAttribute('data-collapsed',collapsed?'false':'true');shell.style.gridTemplateColumns=collapsed?'230px minmax(0,1fr)':'72px minmax(0,1fr)';toggle.setAttribute('aria-expanded',collapsed?'true':'false');}});})();
			</script>
		</div>
		<?php
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
		<div class="wrap">
			<h1><?php esc_html_e( 'مدیریت رزروها', 'mtn-cafe-booking' ); ?></h1>
			<p><?php esc_html_e( 'این صفحه فعلاً مخصوص مدیران وردپرس است؛ دسترسی مدیران کافه تا تکمیل مجوزهای مالکیت فعال نیست.', 'mtn-cafe-booking' ); ?></p>
			<?php if ( isset( $_GET['mtn_notice'] ) ) : ?>
				<div class="notice <?php echo 'updated' === sanitize_key( wp_unslash( $_GET['mtn_notice'] ) ) ? 'notice-success' : 'notice-warning'; ?> is-dismissible"><p><?php
					$notice = sanitize_key( wp_unslash( $_GET['mtn_notice'] ) );
					echo esc_html( 'updated' === $notice ? 'وضعیت رزرو به‌روزرسانی شد.' : ( 0 === strpos( $notice, 'deposit_' ) ? ( 'deposit_updated' === $notice ? 'وضعیت پیش‌پرداخت به‌روزرسانی شد.' : 'تأیید پیش‌پرداخت انجام نشد؛ وضعیت را بررسی کنید.' ) : ( 'invalid' === $notice ? 'درخواست معتبر نیست.' : 'تغییر وضعیت انجام نشد؛ وضعیت فعلی رزرو را بررسی کنید.' ) ) );
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
				<thead><tr><th><?php esc_html_e( 'کد پیگیری', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'کافه / برنامه', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'زمان (UTC)', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'نفرات', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'وضعیت', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'پیش‌پرداخت ۱۰۰ لیر', 'mtn-cafe-booking' ); ?></th><th><?php esc_html_e( 'عملیات', 'mtn-cafe-booking' ); ?></th></tr></thead>
				<tbody>
				<?php if ( $bookings ) : foreach ( $bookings as $booking ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $booking->booking_code ); ?></strong><br><small>#<?php echo (int) $booking->id; ?></small></td>
						<td><?php echo esc_html( $booking->cafe_title ? $booking->cafe_title : '—' ); ?><br><small><?php echo esc_html( $booking->event_title ? $booking->event_title : '—' ); ?></small></td>
						<td><?php echo esc_html( $booking->booking_at ); ?></td>
						<td><?php echo (int) $booking->party_size; ?></td>
						<td><?php echo esc_html( isset( $labels[ $booking->status ] ) ? $labels[ $booking->status ] : $booking->status ); ?></td>
						<td><?php $deposit_labels = array( 'awaiting_transfer' => 'در انتظار انتقال وجه', 'under_review' => 'در انتظار تطبیق بانکی', 'verified' => 'واریز تأیید شد', 'rejected' => 'واریز تأیید نشد' ); echo esc_html( isset( $deposit_labels[ $booking->deposit_status ] ) ? $deposit_labels[ $booking->deposit_status ] : $booking->deposit_status ); ?><br><small><?php echo esc_html( number_format_i18n( (float) $booking->deposit_amount, 2 ) . ' ' . $booking->deposit_currency ); ?><?php if ( $booking->deposit_reference ) : ?><br><?php echo esc_html( 'پیگیری: ' . $booking->deposit_reference ); ?><?php endif; ?></small><?php if ( 'under_review' === $booking->deposit_status ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.4rem"><input type="hidden" name="action" value="mtn_cb_deposit_action"><input type="hidden" name="booking_id" value="<?php echo (int) $booking->id; ?>"><?php wp_nonce_field( 'mtn_cb_deposit_' . (int) $booking->id ); ?><button class="button button-primary" name="deposit_decision" value="verified"><?php esc_html_e( 'تطبیق شد', 'mtn-cafe-booking' ); ?></button><button class="button" name="deposit_decision" value="rejected"><?php esc_html_e( 'رد رسید', 'mtn-cafe-booking' ); ?></button></form><?php endif; ?></td>
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
				<?php endforeach; else : ?><tr><td colspan="7"><?php esc_html_e( 'رزروی برای نمایش وجود ندارد.', 'mtn-cafe-booking' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
