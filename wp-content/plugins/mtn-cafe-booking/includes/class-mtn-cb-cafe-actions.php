<?php
/**
 * Secure, expiring cafe approval links; replaces the full cafe dashboard.
 *
 * @package MTN_Cafe_Booking
 */
defined( 'ABSPATH' ) || exit;

final class MTN_CB_Cafe_Actions {
	const LINK_TTL = 172800;

	public static function init() {
		add_action( 'mtn_cb_booking_created', array( __CLASS__, 'notify_cafe' ), 20, 1 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_decision' ) );
		add_action( 'admin_post_nopriv_mtn_cb_cafe_decision', array( __CLASS__, 'handle_decision' ) );
		add_action( 'admin_post_mtn_cb_cafe_decision', array( __CLASS__, 'handle_decision' ) );
	}

	private static function signature( $booking_id, $decision, $expires ) {
		return hash_hmac( 'sha256', absint( $booking_id ) . '|' . sanitize_key( $decision ) . '|' . absint( $expires ), wp_salt( 'auth' ) );
	}

	private static function valid_link( $booking_id, $decision, $expires, $signature ) {
		if ( ! absint( $booking_id ) || ! in_array( $decision, array( 'confirm', 'reject' ), true ) || absint( $expires ) < time() || absint( $expires ) > time() + self::LINK_TTL + 300 ) {
			return false;
		}
		return is_string( $signature ) && hash_equals( self::signature( $booking_id, $decision, $expires ), $signature );
	}

	private static function decision_url( $booking_id, $decision, $expires ) {
		return add_query_arg( array(
			'mtn_cafe_decision' => 1,
			'booking_id' => absint( $booking_id ),
			'decision' => sanitize_key( $decision ),
			'expires' => absint( $expires ),
			'sig' => self::signature( $booking_id, $decision, $expires ),
		), home_url( '/' ) );
	}

	public static function notify_cafe( $booking ) {
		if ( empty( $booking->id ) || 'pending_cafe' !== $booking->status || ! class_exists( 'MTN_CB_Notifications' ) ) {
			return;
		}
		$cafe_email = sanitize_email( get_post_meta( absint( $booking->cafe_id ), 'mtn_cafe_email', true ) );
		if ( ! is_email( $cafe_email ) ) {
			return;
		}
		$expires = time() + self::LINK_TTL;
		$event_name = get_the_title( absint( $booking->event_id ) );
		$cafe_name = get_the_title( absint( $booking->cafe_id ) );
		$confirm = self::decision_url( $booking->id, 'confirm', $expires );
		$reject = self::decision_url( $booking->id, 'reject', $expires );
		$subject = sprintf( 'درخواست رزرو جدید %s', sanitize_text_field( $booking->booking_code ) );
		$message = sprintf(
			"یک درخواست رزرو جدید برای %s ثبت شده است.\n\nکافه: %s\nبرنامه: %s\nتعداد نفرات: %d\nکد رزرو: %s\n\nبرای بررسی، یکی از لینک‌های زیر را باز کنید. بازکردن لینک به‌تنهایی تغییری در رزرو ایجاد نمی‌کند؛ تصمیم در صفحه تأیید ثبت می‌شود.\n\nتأیید: %s\nرد درخواست: %s\n\nاین لینک‌ها پس از ۴۸ ساعت منقضی می‌شوند. اگر ایمیل به فرد اشتباه رسیده است، آن را نادیده بگیرید.",
			sanitize_text_field( $event_name ),
			sanitize_text_field( $cafe_name ),
			sanitize_text_field( $event_name ),
			absint( $booking->party_size ),
			sanitize_text_field( $booking->booking_code ),
			$confirm,
			$reject
		);
		MTN_CB_Notifications::enqueue_email( 'cafe_decision_' . absint( $booking->id ), absint( $booking->id ), $cafe_email, $subject, $message );
	}

	public static function maybe_render_decision() {
		if ( empty( $_GET['mtn_cafe_decision'] ) ) {
			return;
		}
		$booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;
		$decision = isset( $_GET['decision'] ) ? sanitize_key( wp_unslash( $_GET['decision'] ) ) : '';
		$expires = isset( $_GET['expires'] ) ? absint( $_GET['expires'] ) : 0;
		$signature = isset( $_GET['sig'] ) ? sanitize_text_field( wp_unslash( $_GET['sig'] ) ) : '';
		if ( ! self::valid_link( $booking_id, $decision, $expires, $signature ) ) {
			self::render_message( 'لینک نامعتبر یا منقضی شده است. از مدیر سایت بخواهید درخواست رزرو را بررسی کند.', true );
		}
		$booking = MTN_CB_Bookings::get_by_id( $booking_id );
		if ( ! $booking || 'pending_cafe' !== $booking->status ) {
			self::render_message( 'این رزرو قبلاً تعیین‌تکلیف شده یا دیگر در دسترس نیست.', true );
		}
		get_header();
		?>
		<section class="mtn-booking-panel mtn-content">
			<p class="eyebrow">پاسخ به درخواست رزرو</p>
			<h1><?php echo 'confirm' === $decision ? 'تأیید درخواست رزرو' : 'رد درخواست رزرو'; ?></h1>
			<p>کد رزرو: <strong><?php echo esc_html( $booking->booking_code ); ?></strong></p>
			<p>کافه: <?php echo esc_html( get_the_title( $booking->cafe_id ) ); ?> · برنامه: <?php echo esc_html( get_the_title( $booking->event_id ) ); ?> · تعداد نفرات: <?php echo esc_html( (string) $booking->party_size ); ?></p>
			<p class="mtn-booking-disclaimer">این پیوند فقط برای همین رزرو و تا زمان انقضای درج‌شده معتبر است. بازکردن لینک به‌تنهایی رزرو را تغییر نمی‌دهد.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mtn-booking-form">
				<input type="hidden" name="action" value="mtn_cb_cafe_decision">
				<input type="hidden" name="booking_id" value="<?php echo esc_attr( (string) $booking_id ); ?>">
				<input type="hidden" name="decision" value="<?php echo esc_attr( $decision ); ?>">
				<input type="hidden" name="expires" value="<?php echo esc_attr( (string) $expires ); ?>">
				<input type="hidden" name="sig" value="<?php echo esc_attr( $signature ); ?>">
				<?php wp_nonce_field( 'mtn_cb_cafe_decision_' . $booking_id . '_' . $decision . '_' . $expires ); ?>
				<?php if ( 'confirm' === $decision && 'cafe_assigns_table' === get_option( 'mtn_cb_booking_mode', 'customer_selects_table' ) ) : ?>
					<label for="mtn-cafe-decision-table">میز موردنظر برای تخصیص</label>
					<select id="mtn-cafe-decision-table" name="table_id" required>
						<option value="">انتخاب میز</option>
						<?php foreach ( MTN_CB_Bookings::get_assignable_tables( $booking_id ) as $table ) : ?>
							<option value="<?php echo esc_attr( (string) $table['id'] ); ?>"><?php echo esc_html( $table['name'] . ' — ظرفیت ' . $table['capacity'] ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
				<label for="mtn-cafe-decision-note">یادداشت (اختیاری)</label>
				<textarea id="mtn-cafe-decision-note" name="reason" rows="3" maxlength="1000"></textarea>
				<button type="submit" class="button"><?php echo 'confirm' === $decision ? 'ثبت تأیید رزرو' : 'ثبت رد رزرو'; ?></button>
			</form>
		</section>
		<?php
		get_footer();
		exit;
	}

	public static function handle_decision() {
		$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
		$decision = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';
		$expires = isset( $_POST['expires'] ) ? absint( $_POST['expires'] ) : 0;
		$signature = isset( $_POST['sig'] ) ? sanitize_text_field( wp_unslash( $_POST['sig'] ) ) : '';
		if ( ! self::valid_link( $booking_id, $decision, $expires, $signature ) ) {
			self::render_message( 'لینک نامعتبر یا منقضی شده است. هیچ تغییری اعمال نشد.', true );
		}
		check_admin_referer( 'mtn_cb_cafe_decision_' . $booking_id . '_' . $decision . '_' . $expires );
		$booking = MTN_CB_Bookings::get_by_id( $booking_id );
		if ( ! $booking || 'pending_cafe' !== $booking->status ) {
			self::render_message( 'رزرو قبلاً تغییر کرده است؛ هیچ اقدام دیگری انجام نشد.', true );
		}
		$reason = isset( $_POST['reason'] ) && is_scalar( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
		if ( 'confirm' === $decision ) {
			if ( 'cafe_assigns_table' === get_option( 'mtn_cb_booking_mode', 'customer_selects_table' ) ) {
				$table_id = isset( $_POST['table_id'] ) ? absint( $_POST['table_id'] ) : 0;
				if ( ! $table_id ) {
					self::render_message( 'برای تأیید، باید میز تخصیص داده شود.', true );
				}
				$assigned = MTN_CB_Bookings::assign_table( $booking_id, $table_id, 0 );
				if ( is_wp_error( $assigned ) ) {
					self::render_message( $assigned->get_error_message(), true );
				}
			}
			$result = MTN_CB_Bookings::transition( $booking_id, 'confirmed', 0, 'secure_cafe_link_confirmed' . ( $reason ? ': ' . $reason : '' ) );
		} else {
			$result = MTN_CB_Bookings::transition( $booking_id, 'rejected', 0, 'secure_cafe_link_rejected' . ( $reason ? ': ' . $reason : '' ) );
		}
		if ( is_wp_error( $result ) ) {
			self::render_message( $result->get_error_message(), true );
		}
		self::render_message( 'تصمیم شما ثبت شد. وضعیت رزرو با موفقیت به‌روزرسانی شد.', false );
	}

	private static function render_message( $message, $error ) {
		status_header( $error ? 400 : 200 );
		nocache_headers();
		get_header();
		echo '<section class="mtn-booking-panel mtn-content" role="status"><h1>' . esc_html( $error ? 'امکان انجام درخواست نیست' : 'درخواست ثبت شد' ) . '</h1><p>' . esc_html( $message ) . '</p><a class="button" href="' . esc_url( home_url( '/' ) ) . '">بازگشت به سایت</a></section>';
		get_footer();
		exit;
	}
}
