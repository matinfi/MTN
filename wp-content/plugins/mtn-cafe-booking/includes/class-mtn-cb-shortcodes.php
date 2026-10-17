<?php
/**
 * Front-end booking wizard and customer account shortcodes.
 *
 * @package MTN_Cafe_Booking
 */

defined( 'ABSPATH' ) || exit;

final class MTN_CB_Shortcodes {
	public static function init() {
		add_shortcode( 'mtn_booking_form', array( __CLASS__, 'booking_form' ) );
		add_shortcode( 'mtn_my_bookings', array( __CLASS__, 'my_bookings' ) );
		add_shortcode( 'mtn_cafe_dashboard', array( __CLASS__, 'cafe_dashboard' ) );
		add_shortcode( 'mtn_support_whatsapp', array( __CLASS__, 'support_whatsapp' ) );
		add_shortcode( 'mtn_cafe_invite_accept', array( 'MTN_CB_Team_Invites', 'accept_form' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_assets() {
		wp_register_script(
			'mtn-cb-wizard',
			MTN_CB_URL . 'assets/booking-wizard.js',
			array(),
			MTN_CB_VERSION,
			true
		);
	}

	private static function enqueue_assets() {
		wp_enqueue_script( 'mtn-cb-wizard' );
		wp_localize_script(
			'mtn-cb-wizard',
			'MTNCB',
			array(
				'restUrl' => esc_url_raw( rest_url( 'mtn/v1/' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'loggedIn' => is_user_logged_in(),
				'loginUrl' => wp_login_url( get_permalink() ),
				'catalogError' => __( 'بارگذاری فهرست با خطا روبه‌رو شد. صفحه را تازه‌سازی کنید.', 'mtn-cafe-booking' ),
				'loginRequired' => __( 'برای ثبت نهایی رزرو ابتدا وارد حساب شوید.', 'mtn-cafe-booking' ),
				'genericError' => __( 'درخواست انجام نشد. دوباره تلاش کنید.', 'mtn-cafe-booking' ),
				'depositAmount' => '100.00',
				'depositCurrency' => 'TRY',
				'bankAccountName' => sanitize_text_field( get_option( 'mtn_cb_bank_account_name', '' ) ),
				'bankIban' => sanitize_text_field( get_option( 'mtn_cb_bank_iban', '' ) ),
				'bankInstructions' => sanitize_textarea_field( get_option( 'mtn_cb_bank_instructions', '' ) ),
				'supportWhatsApp' => preg_replace( '/[^0-9]/', '', (string) get_option( 'mtn_cb_support_whatsapp', '' ) ),
			)
		);
	}

	public static function booking_form( $atts = array() ) {
		self::enqueue_assets();
		$atts = shortcode_atts( array( 'cafe_id' => 0, 'event_id' => 0 ), $atts, 'mtn_booking_form' );
		$cafe_id = absint( $atts['cafe_id'] ? $atts['cafe_id'] : ( isset( $_GET['cafe_id'] ) ? wp_unslash( $_GET['cafe_id'] ) : 0 ) );
		$event_id = absint( $atts['event_id'] ? $atts['event_id'] : ( isset( $_GET['event_id'] ) ? wp_unslash( $_GET['event_id'] ) : 0 ) );
		$id = wp_unique_id( 'mtn-booking-' );
		ob_start();
		?>
		<section class="mtn-booking-panel" data-mtn-booking data-default-cafe="<?php echo esc_attr( $cafe_id ); ?>" data-default-event="<?php echo esc_attr( $event_id ); ?>">
			<div class="mtn-booking-intro">
				<p class="eyebrow"><?php esc_html_e( 'رزرو آنلاین', 'mtn-cafe-booking' ); ?></p>
				<h2><?php esc_html_e( 'میزت را برای یک شب خوب رزرو کن', 'mtn-cafe-booking' ); ?></h2>
				<p class="muted"><?php esc_html_e( 'ابتدا ظرفیت را بررسی کن. پیش‌پرداخت رزرو ۱۰۰ لیر است و با حواله بانکی پرداخت می‌شود؛ پرداخت پس از بررسی مدیر سایت تأیید خواهد شد.', 'mtn-cafe-booking' ); ?></p>
			</div>
			<form class="mtn-booking-form" id="<?php echo esc_attr( $id ); ?>">
				<div class="mtn-form-grid">
					<p><label for="<?php echo esc_attr( $id . '-cafe' ); ?>"><?php esc_html_e( 'کافه', 'mtn-cafe-booking' ); ?></label><select id="<?php echo esc_attr( $id . '-cafe' ); ?>" name="cafe_id" required><option value=""><?php esc_html_e( 'در حال بارگذاری…', 'mtn-cafe-booking' ); ?></option></select></p>
					<p><label for="<?php echo esc_attr( $id . '-event' ); ?>"><?php esc_html_e( 'برنامه / رویداد', 'mtn-cafe-booking' ); ?></label><select id="<?php echo esc_attr( $id . '-event' ); ?>" name="event_id" required disabled><option value=""><?php esc_html_e( 'ابتدا کافه را انتخاب کنید', 'mtn-cafe-booking' ); ?></option></select></p>
					<p><label for="<?php echo esc_attr( $id . '-party' ); ?>"><?php esc_html_e( 'تعداد نفرات', 'mtn-cafe-booking' ); ?></label><input id="<?php echo esc_attr( $id . '-party' ); ?>" name="party_size" type="number" min="1" max="100" value="2" required></p>
					<p><label for="<?php echo esc_attr( $id . '-date' ); ?>"><?php esc_html_e( 'تاریخ و ساعت (منطقه زمانی سایت)', 'mtn-cafe-booking' ); ?></label><input id="<?php echo esc_attr( $id . '-date' ); ?>" name="booking_at" type="datetime-local" step="60" required></p>
					<p class="mtn-table-field" hidden><label for="<?php echo esc_attr( $id . '-table' ); ?>"><?php esc_html_e( 'میز', 'mtn-cafe-booking' ); ?></label><select id="<?php echo esc_attr( $id . '-table' ); ?>" name="table_id"><option value=""><?php esc_html_e( 'ابتدا ظرفیت را بررسی کنید', 'mtn-cafe-booking' ); ?></option></select></p>
				</div>
				<p><label for="<?php echo esc_attr( $id . '-note' ); ?>"><?php esc_html_e( 'توضیحات (اختیاری)', 'mtn-cafe-booking' ); ?></label><textarea id="<?php echo esc_attr( $id . '-note' ); ?>" name="customer_note" rows="3" maxlength="1000"></textarea></p>
				<div class="mtn-booking-actions"><button type="button" class="button mtn-check-availability"><?php esc_html_e( 'بررسی ظرفیت', 'mtn-cafe-booking' ); ?></button><button type="submit" class="button mtn-submit-booking" disabled><?php esc_html_e( 'ثبت درخواست رزرو', 'mtn-cafe-booking' ); ?></button></div>
				<p class="mtn-booking-status" role="status" aria-live="polite"></p>
				<p class="mtn-booking-login" hidden></p>
			</form>
			<p class="mtn-booking-disclaimer"><?php esc_html_e( 'لغو رزرو فقط تا ۳ ساعت پس از ثبت درخواست ممکن است. پیش‌پرداخت ۱۰۰ لیر در صورت لغو مشتری قابل استرداد نیست. باقی مبلغ در محل کافه پرداخت می‌شود. پرداخت آنلاین در نسخه اول فعال نیست؛ شماره پیگیری انتقال وجه باید برای بررسی مدیر ثبت شود.', 'mtn-cafe-booking' ); ?></p>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function support_whatsapp() {
		$phone = preg_replace( '/[^0-9]/', '', (string) get_option( 'mtn_cb_support_whatsapp', '' ) );
		if ( '' === $phone ) {
			return current_user_can( 'manage_options' ) ? '<p>' . esc_html__( 'برای فعال‌سازی پشتیبانی واتساپ، شماره را در تنظیمات رزرو MTN وارد کنید.', 'mtn-cafe-booking' ) . '</p>' : '';
		}
		$url = 'https://wa.me/' . $phone . '?text=' . rawurlencode( 'سلام، برای همکاری با پلتفرم MTN به راهنمایی پشتیبانی نیاز دارم.' );
		return '<a class="button mtn-support-whatsapp" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'گفت‌وگو با پشتیبانی در واتساپ', 'mtn-cafe-booking' ) . '</a><p class="muted">' . esc_html__( 'این لینک گفت‌وگوی مستقیم را باز می‌کند؛ ربات یا ارسال خودکار پیام بدون اتصال رسمی واتساپ فعال نیست.', 'mtn-cafe-booking' ) . '</p>';
	}

	public static function cafe_dashboard() {
		self::enqueue_assets();
		if ( ! is_user_logged_in() ) {
			return '<section class="mtn-booking-panel"><p>' . esc_html__( 'برای مدیریت رزروها وارد حساب مدیر کافه شوید.', 'mtn-cafe-booking' ) . '</p><a class="button" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'ورود به حساب', 'mtn-cafe-booking' ) . '</a></section>';
		}
		if ( ! current_user_can( 'mtn_manage_cafes' ) && ! current_user_can( 'manage_options' ) ) {
			return '<section class="mtn-booking-panel"><p>' . esc_html__( 'این حساب به پنل مدیریت کافه دسترسی ندارد.', 'mtn-cafe-booking' ) . '</p></section>';
		}
		return '<section class="mtn-booking-panel" data-mtn-cafe-dashboard><h2>' . esc_html__( 'مدیریت رزروهای کافه', 'mtn-cafe-booking' ) . '</h2><p class="mtn-booking-status" role="status" aria-live="polite">' . esc_html__( 'در حال دریافت رزروها…', 'mtn-cafe-booking' ) . '</p><form class="mtn-checkin-form"><label for="mtn-checkin-token">' . esc_html__( 'کد یک‌بارمصرف اعلام حضور مشتری', 'mtn-cafe-booking' ) . '</label><div class="mtn-booking-actions"><input id="mtn-checkin-token" name="token" type="text" inputmode="text" autocomplete="off" pattern="[a-fA-F0-9]{64}" maxlength="64" required><button type="submit" class="button">' . esc_html__( 'ثبت حضور', 'mtn-cafe-booking' ) . '</button></div></form><div class="mtn-cafe-booking-list"></div></section>';
	}

	public static function my_bookings() {
		self::enqueue_assets();
		if ( ! is_user_logged_in() ) {
			return '<section class="mtn-booking-panel"><p>' . esc_html__( 'برای مشاهده رزروهای خود وارد حساب شوید.', 'mtn-cafe-booking' ) . '</p><a class="button" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'ورود به حساب', 'mtn-cafe-booking' ) . '</a></section>';
		}
		return '<section class="mtn-booking-panel" data-mtn-my-bookings><h2>' . esc_html__( 'رزروهای من', 'mtn-cafe-booking' ) . '</h2><p class="mtn-booking-status" role="status" aria-live="polite">' . esc_html__( 'در حال دریافت رزروها…', 'mtn-cafe-booking' ) . '</p><div class="mtn-booking-list"></div></section>';
	}
}
