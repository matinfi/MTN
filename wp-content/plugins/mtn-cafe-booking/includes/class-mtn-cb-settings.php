<?php
/**
 * Plugin settings.
 *
 * @package MTN_Cafe_Booking
 */

defined( 'ABSPATH' ) || exit;

final class MTN_CB_Settings {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	public static function menu() {
		add_options_page(
			__( 'تنظیمات رزرو کافه', 'mtn-cafe-booking' ),
			__( 'رزرو کافه MTN', 'mtn-cafe-booking' ),
			'manage_options',
			'mtn-cafe-booking',
			array( __CLASS__, 'render' )
		);
	}

	public static function register() {
		register_setting( 'mtn_cb_settings', 'mtn_cb_brand_name', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => get_bloginfo( 'name' ) ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_currency', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_currency' ), 'default' => 'TRY' ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_default_commission', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_amount' ), 'default' => '0' ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_commission_trigger_status', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_commission_trigger' ), 'default' => 'confirmed' ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_reverse_commission_on_cancellation', array( 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => false ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_reverse_commission_on_no_show', array( 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => false ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_reverse_commission_on_event_cancel', array( 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => true ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_booking_expiry_minutes', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_minutes' ), 'default' => 30 ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_checkin_token_minutes', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_checkin_minutes' ), 'default' => 15 ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_booking_duration_minutes', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_duration' ), 'default' => 120 ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_customer_cancel_cutoff_minutes', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_cancel_cutoff' ), 'default' => 0 ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_statement_due_days', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_statement_due_days' ), 'default' => 7 ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_booking_mode', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_mode' ), 'default' => 'cafe_assigns_table' ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_deposit_amount', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_deposit_amount' ), 'default' => '100.00' ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_bank_account_name', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_bank_iban', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_iban' ), 'default' => '' ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_bank_instructions', array( 'sanitize_callback' => 'sanitize_textarea_field', 'default' => '' ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_support_whatsapp', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_whatsapp' ), 'default' => '' ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_theme_accent', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_theme_accent' ), 'default' => '#e7a94b' ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_theme_background', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_theme_background' ), 'default' => '#101014' ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_theme_font', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_theme_font' ), 'default' => 'Tahoma, Arial, sans-serif' ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_theme_density', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_theme_density' ), 'default' => 'comfortable' ) );
	}

	public static function sanitize_currency( $value ) {
		$value = strtoupper( sanitize_text_field( $value ) );
		if ( ! preg_match( '/^[A-Z]{3,8}$/', $value ) ) {
			return 'TRY';
		}
		$current = strtoupper( sanitize_text_field( get_option( 'mtn_cb_currency', 'TRY' ) ) );
		if ( $value !== $current ) {
			global $wpdb;
			$bookings_exist = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . MTN_CB_Bookings::table_name() );
			$ledger_exists = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . MTN_CB_Finance::ledger_table() );
			if ( $bookings_exist > 0 || $ledger_exists > 0 ) {
				return $current;
			}
		}
		return $value;
	}

	public static function sanitize_deposit_amount( $value ) {
		// V1 policy is a fixed 100 TRY deposit; do not allow a currency mismatch.
		return '100.00';
	}

	public static function sanitize_iban( $value ) {
		$value = strtoupper( preg_replace( '/\\s+/', '', sanitize_text_field( $value ) ) );
		return preg_match( '/^[A-Z]{2}[0-9A-Z]{13,32}$/', $value ) ? $value : '';
	}

	public static function sanitize_whatsapp( $value ) {
		return preg_replace( '/[^0-9]/', '', (string) $value );
	}

	public static function sanitize_theme_accent( $value ) {
		$color = sanitize_hex_color( $value );
		return $color ? $color : '#e7a94b';
	}

	public static function sanitize_theme_background( $value ) {
		$color = sanitize_hex_color( $value );
		return $color ? $color : '#101014';
	}

	public static function sanitize_theme_font( $value ) {
		$allowed = array( 'Tahoma, Arial, sans-serif', 'Arial, sans-serif', 'system-ui, sans-serif' );
		return in_array( $value, $allowed, true ) ? $value : 'Tahoma, Arial, sans-serif';
	}

	public static function sanitize_theme_density( $value ) {
		return in_array( $value, array( 'comfortable', 'compact' ), true ) ? $value : 'comfortable';
	}

	public static function sanitize_amount( $value ) {
		return is_numeric( $value ) && (float) $value >= 0 ? number_format( min( 9999999999.99, (float) $value ), 2, '.', '' ) : '0.00';
	}

	public static function sanitize_minutes( $value ) {
		return min( 1440, max( 5, absint( $value ) ) );
	}

	public static function sanitize_checkin_minutes( $value ) {
		return min( 60, max( 5, absint( $value ) ) );
	}

	public static function sanitize_duration( $value ) {
		return min( 720, max( 30, absint( $value ) ) );
	}

	public static function sanitize_commission_trigger( $value ) {
		return in_array( $value, array( 'confirmed', 'completed' ), true ) ? $value : 'confirmed';
	}

	public static function sanitize_cancel_cutoff( $value ) {
		return min( 10080, max( 0, absint( $value ) ) );
	}

	public static function sanitize_statement_due_days( $value ) {
		return min( 90, max( 1, absint( $value ) ) );
	}

	public static function sanitize_mode( $value ) {
		return in_array( $value, array( 'customer_selects_table', 'cafe_assigns_table' ), true ) ? $value : 'customer_selects_table';
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'تنظیمات رزرو کافه MTN', 'mtn-cafe-booking' ); ?></h1>
			<p><?php esc_html_e( 'در نسخه اول پرداخت اینترنتی نداریم. مشتری ۱۰۰ لیر را با حواله بانکی/کارت‌به‌کارت واریز می‌کند؛ مدیر سایت پس از تطبیق واقعی گردش حساب، پرداخت را دستی تأیید می‌کند. این پیش‌پرداخت در صورت لغو مشتری مسترد نمی‌شود. تا ثبت حساب مقصد، رزروها نباید منتشر شوند.', 'mtn-cafe-booking' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'mtn_cb_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><label for="mtn_cb_brand_name"><?php esc_html_e( 'نام برند', 'mtn-cafe-booking' ); ?></label></th><td><input class="regular-text" id="mtn_cb_brand_name" name="mtn_cb_brand_name" value="<?php echo esc_attr( get_option( 'mtn_cb_brand_name', get_bloginfo( 'name' ) ) ); ?>"></td></tr>
					<tr><th><label for="mtn_cb_theme_accent"><?php esc_html_e( 'رنگ تأکیدی قالب', 'mtn-cafe-booking' ); ?></label></th><td><input type="color" id="mtn_cb_theme_accent" name="mtn_cb_theme_accent" value="<?php echo esc_attr( get_option( 'mtn_cb_theme_accent', '#e7a94b' ) ); ?>"></td></tr>
					<tr><th><label for="mtn_cb_theme_background"><?php esc_html_e( 'رنگ پس‌زمینه قالب', 'mtn-cafe-booking' ); ?></label></th><td><input type="color" id="mtn_cb_theme_background" name="mtn_cb_theme_background" value="<?php echo esc_attr( get_option( 'mtn_cb_theme_background', '#101014' ) ); ?>"></td></tr>
					<tr><th><label for="mtn_cb_theme_font"><?php esc_html_e( 'تایپوگرافی', 'mtn-cafe-booking' ); ?></label></th><td><select id="mtn_cb_theme_font" name="mtn_cb_theme_font"><?php foreach ( array( 'Tahoma, Arial, sans-serif' => 'Tahoma', 'Arial, sans-serif' => 'Arial', 'system-ui, sans-serif' => 'System UI' ) as $font_value => $font_label ) : ?><option value="<?php echo esc_attr( $font_value ); ?>" <?php selected( get_option( 'mtn_cb_theme_font', 'Tahoma, Arial, sans-serif' ), $font_value ); ?>><?php echo esc_html( $font_label ); ?></option><?php endforeach; ?></select></td></tr>
					<tr><th><label for="mtn_cb_theme_density"><?php esc_html_e( 'تراکم چیدمان', 'mtn-cafe-booking' ); ?></label></th><td><select id="mtn_cb_theme_density" name="mtn_cb_theme_density"><option value="comfortable" <?php selected( get_option( 'mtn_cb_theme_density', 'comfortable' ), 'comfortable' ); ?>><?php esc_html_e( 'راحت', 'mtn-cafe-booking' ); ?></option><option value="compact" <?php selected( get_option( 'mtn_cb_theme_density', 'comfortable' ), 'compact' ); ?>><?php esc_html_e( 'فشرده', 'mtn-cafe-booking' ); ?></option></select><p class="description"><?php esc_html_e( 'لوگو از مسیر نمایش ← سفارشی‌سازی ← هویت سایت قابل تغییر است.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_currency"><?php esc_html_e( 'واحد پول', 'mtn-cafe-booking' ); ?></label></th><td><input class="regular-text" id="mtn_cb_currency" name="mtn_cb_currency" value="<?php echo esc_attr( get_option( 'mtn_cb_currency', 'TRY' ) ); ?>" maxlength="8"><p class="description"><?php esc_html_e( 'برای جلوگیری از ترکیب ارزها در دفتر مالی، پس از ایجاد اولین رزرو تغییر ارز قفل می‌شود.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_commission_trigger_status"><?php esc_html_e( 'زمان ایجاد کارمزد', 'mtn-cafe-booking' ); ?></label></th><td><select id="mtn_cb_commission_trigger_status" name="mtn_cb_commission_trigger_status"><option value="confirmed" <?php selected( get_option( 'mtn_cb_commission_trigger_status', 'confirmed' ), 'confirmed' ); ?>><?php esc_html_e( 'پس از تأیید کافه (پیش‌فرض)', 'mtn-cafe-booking' ); ?></option><option value="completed" <?php selected( get_option( 'mtn_cb_commission_trigger_status', 'confirmed' ), 'completed' ); ?>><?php esc_html_e( 'پس از تکمیل رزرو', 'mtn-cafe-booking' ); ?></option></select><p class="description"><?php esc_html_e( 'این سیاست هنگام ایجاد هر رزرو ثبت می‌شود و تغییر بعدی فقط روی رزروهای جدید اثر دارد.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_default_commission"><?php esc_html_e( 'کارمزد ثابت هر رزرو واجد شرایط', 'mtn-cafe-booking' ); ?></label></th><td><input type="number" min="0" step="0.01" id="mtn_cb_default_commission" name="mtn_cb_default_commission" value="<?php echo esc_attr( get_option( 'mtn_cb_default_commission', '0' ) ); ?>"></td></tr>
					<tr><th><?php esc_html_e( 'اصلاح کارمزد در لغو', 'mtn-cafe-booking' ); ?></th><td><input type="hidden" name="mtn_cb_reverse_commission_on_cancellation" value="0"><label><input type="checkbox" name="mtn_cb_reverse_commission_on_cancellation" value="1" <?php checked( (bool) get_option( 'mtn_cb_reverse_commission_on_cancellation', false ) ); ?>> <?php esc_html_e( 'در صورت لغو پس از ایجاد کارمزد، سند برگشت خودکار ثبت شود.', 'mtn-cafe-booking' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'اصلاح کارمزد در عدم حضور', 'mtn-cafe-booking' ); ?></th><td><input type="hidden" name="mtn_cb_reverse_commission_on_no_show" value="0"><label><input type="checkbox" name="mtn_cb_reverse_commission_on_no_show" value="1" <?php checked( (bool) get_option( 'mtn_cb_reverse_commission_on_no_show', false ) ); ?>> <?php esc_html_e( 'در صورت عدم حضور پس از ایجاد کارمزد، سند برگشت خودکار ثبت شود.', 'mtn-cafe-booking' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'اصلاح کارمزد در لغو رویداد', 'mtn-cafe-booking' ); ?></th><td><input type="hidden" name="mtn_cb_reverse_commission_on_event_cancel" value="0"><label><input type="checkbox" name="mtn_cb_reverse_commission_on_event_cancel" value="1" <?php checked( (bool) get_option( 'mtn_cb_reverse_commission_on_event_cancel', true ) ); ?>> <?php esc_html_e( 'با لغو یا لغو انتشار رویداد، کارمزد ثبت‌شدهٔ رزروهای آن برگشت بخورد.', 'mtn-cafe-booking' ); ?></label></td></tr>
					<tr><th><label for="mtn_cb_booking_expiry_minutes"><?php esc_html_e( 'مهلت تأیید رزرو (دقیقه)', 'mtn-cafe-booking' ); ?></label></th><td><input type="number" min="5" max="1440" id="mtn_cb_booking_expiry_minutes" name="mtn_cb_booking_expiry_minutes" value="<?php echo esc_attr( get_option( 'mtn_cb_booking_expiry_minutes', 30 ) ); ?>"></td></tr>
					<tr><th><label for="mtn_cb_checkin_token_minutes"><?php esc_html_e( 'اعتبار کد اعلام حضور (دقیقه)', 'mtn-cafe-booking' ); ?></label></th><td><input type="number" min="5" max="60" id="mtn_cb_checkin_token_minutes" name="mtn_cb_checkin_token_minutes" value="<?php echo esc_attr( get_option( 'mtn_cb_checkin_token_minutes', 15 ) ); ?>"><p class="description"><?php esc_html_e( 'کد اعلام حضور یک‌بارمصرف است و پس از این مدت منقضی می‌شود.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_booking_duration_minutes"><?php esc_html_e( 'مدت اشغال میز برای هر رزرو (دقیقه)', 'mtn-cafe-booking' ); ?></label></th><td><input type="number" min="30" max="720" id="mtn_cb_booking_duration_minutes" name="mtn_cb_booking_duration_minutes" value="<?php echo esc_attr( get_option( 'mtn_cb_booking_duration_minutes', 120 ) ); ?>"><p class="description"><?php esc_html_e( 'برای بررسی هم‌پوشانی رزروهای میز استفاده می‌شود.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_customer_cancel_cutoff_minutes"><?php esc_html_e( 'قانون لغو رزرو', 'mtn-cafe-booking' ); ?></label></th><td><input type="number" id="mtn_cb_customer_cancel_cutoff_minutes" value="180" disabled><p class="description"><?php esc_html_e( 'لغو فقط تا ۳ ساعت پس از ایجاد رزرو مجاز است. پس از آن لغو از حساب مشتری مسدود می‌شود. پیش‌پرداخت ۱۰۰ لیر در هیچ لغو مشتری مسترد نمی‌شود.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_statement_due_days"><?php esc_html_e( 'مهلت پرداخت کارمزد (روز پس از پایان ماه ثبت)', 'mtn-cafe-booking' ); ?></label></th><td><input type="number" min="1" max="90" id="mtn_cb_statement_due_days" name="mtn_cb_statement_due_days" value="<?php echo esc_attr( get_option( 'mtn_cb_statement_due_days', 7 ) ); ?>"></td></tr>
					<tr><th><?php esc_html_e( 'پیش‌پرداخت رزرو', 'mtn-cafe-booking' ); ?></th><td><strong>۱۰۰ لیر ترکیه (TRY)</strong><input type="hidden" name="mtn_cb_deposit_amount" value="100.00"><p class="description"><?php esc_html_e( 'مبلغ نسخه اول ثابت است. پرداخت آنلاین درگاه ندارد و تنها پس از بررسی گردش حساب توسط مدیر ثبت می‌شود.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_bank_account_name"><?php esc_html_e( 'نام صاحب حساب مقصد', 'mtn-cafe-booking' ); ?></label></th><td><input class="regular-text" id="mtn_cb_bank_account_name" name="mtn_cb_bank_account_name" value="<?php echo esc_attr( get_option( 'mtn_cb_bank_account_name', '' ) ); ?>"></td></tr>
					<tr><th><label for="mtn_cb_bank_iban"><?php esc_html_e( 'شماره IBAN حساب مقصد', 'mtn-cafe-booking' ); ?></label></th><td><input class="regular-text" dir="ltr" id="mtn_cb_bank_iban" name="mtn_cb_bank_iban" value="<?php echo esc_attr( get_option( 'mtn_cb_bank_iban', '' ) ); ?>" maxlength="34"><p class="description"><?php esc_html_e( 'حساب بانکی تأییدشده سایت را وارد کنید. برای انتشار عمومی، صحت مالکیت حساب را بررسی کنید.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_bank_instructions"><?php esc_html_e( 'راهنمای انتقال وجه', 'mtn-cafe-booking' ); ?></label></th><td><textarea class="large-text" rows="3" id="mtn_cb_bank_instructions" name="mtn_cb_bank_instructions"><?php echo esc_textarea( get_option( 'mtn_cb_bank_instructions', '' ) ); ?></textarea></td></tr>
					<tr><th><label for="mtn_cb_support_whatsapp"><?php esc_html_e( 'واتساپ پشتیبانی', 'mtn-cafe-booking' ); ?></label></th><td><input class="regular-text" dir="ltr" id="mtn_cb_support_whatsapp" name="mtn_cb_support_whatsapp" value="<?php echo esc_attr( get_option( 'mtn_cb_support_whatsapp', '' ) ); ?>"><p class="description"><?php esc_html_e( 'شماره بین‌المللی فقط با ارقام؛ لینک چت دستی ساخته می‌شود و ربات یا ارسال خودکار پیام بدون اتصال رسمی فعال نیست.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_booking_mode"><?php esc_html_e( 'روش انتخاب میز', 'mtn-cafe-booking' ); ?></label></th><td><select id="mtn_cb_booking_mode" name="mtn_cb_booking_mode"><option value="customer_selects_table" <?php selected( get_option( 'mtn_cb_booking_mode' ), 'customer_selects_table' ); ?>><?php esc_html_e( 'انتخاب توسط مشتری', 'mtn-cafe-booking' ); ?></option><option value="cafe_assigns_table" <?php selected( get_option( 'mtn_cb_booking_mode' ), 'cafe_assigns_table' ); ?>><?php esc_html_e( 'تخصیص توسط کافه', 'mtn-cafe-booking' ); ?></option></select></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
