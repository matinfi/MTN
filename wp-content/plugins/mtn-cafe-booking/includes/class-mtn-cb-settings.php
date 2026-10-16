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
		register_setting( 'mtn_cb_settings', 'mtn_cb_booking_expiry_minutes', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_minutes' ), 'default' => 30 ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_checkin_token_minutes', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_checkin_minutes' ), 'default' => 15 ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_booking_duration_minutes', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_duration' ), 'default' => 120 ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_customer_cancel_cutoff_minutes', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_cancel_cutoff' ), 'default' => 0 ) );
		register_setting( 'mtn_cb_settings', 'mtn_cb_booking_mode', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_mode' ), 'default' => 'customer_selects_table' ) );
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
			<p><?php esc_html_e( 'پرداخت آنلاین مشتری در نسخه اول غیرفعال است. کارمزد ثبت‌شده به معنی وجه وصول‌شده نیست.', 'mtn-cafe-booking' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'mtn_cb_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><label for="mtn_cb_brand_name"><?php esc_html_e( 'نام برند', 'mtn-cafe-booking' ); ?></label></th><td><input class="regular-text" id="mtn_cb_brand_name" name="mtn_cb_brand_name" value="<?php echo esc_attr( get_option( 'mtn_cb_brand_name', get_bloginfo( 'name' ) ) ); ?>"></td></tr>
					<tr><th><label for="mtn_cb_currency"><?php esc_html_e( 'واحد پول', 'mtn-cafe-booking' ); ?></label></th><td><input class="regular-text" id="mtn_cb_currency" name="mtn_cb_currency" value="<?php echo esc_attr( get_option( 'mtn_cb_currency', 'TRY' ) ); ?>" maxlength="8"><p class="description"><?php esc_html_e( 'برای جلوگیری از ترکیب ارزها در دفتر مالی، پس از ایجاد اولین رزرو تغییر ارز قفل می‌شود.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_commission_trigger_status"><?php esc_html_e( 'زمان ایجاد کارمزد', 'mtn-cafe-booking' ); ?></label></th><td><select id="mtn_cb_commission_trigger_status" name="mtn_cb_commission_trigger_status"><option value="confirmed" <?php selected( get_option( 'mtn_cb_commission_trigger_status', 'confirmed' ), 'confirmed' ); ?>><?php esc_html_e( 'پس از تأیید کافه (پیش‌فرض)', 'mtn-cafe-booking' ); ?></option><option value="completed" <?php selected( get_option( 'mtn_cb_commission_trigger_status', 'confirmed' ), 'completed' ); ?>><?php esc_html_e( 'پس از تکمیل رزرو', 'mtn-cafe-booking' ); ?></option></select><p class="description"><?php esc_html_e( 'این سیاست هنگام ایجاد هر رزرو ثبت می‌شود و تغییر بعدی فقط روی رزروهای جدید اثر دارد.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_default_commission"><?php esc_html_e( 'کارمزد ثابت هر رزرو واجد شرایط', 'mtn-cafe-booking' ); ?></label></th><td><input type="number" min="0" step="0.01" id="mtn_cb_default_commission" name="mtn_cb_default_commission" value="<?php echo esc_attr( get_option( 'mtn_cb_default_commission', '0' ) ); ?>"></td></tr>
					<tr><th><?php esc_html_e( 'اصلاح کارمزد در لغو', 'mtn-cafe-booking' ); ?></th><td><input type="hidden" name="mtn_cb_reverse_commission_on_cancellation" value="0"><label><input type="checkbox" name="mtn_cb_reverse_commission_on_cancellation" value="1" <?php checked( (bool) get_option( 'mtn_cb_reverse_commission_on_cancellation', false ) ); ?>> <?php esc_html_e( 'در صورت لغو پس از ایجاد کارمزد، سند برگشت خودکار ثبت شود.', 'mtn-cafe-booking' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'اصلاح کارمزد در عدم حضور', 'mtn-cafe-booking' ); ?></th><td><input type="hidden" name="mtn_cb_reverse_commission_on_no_show" value="0"><label><input type="checkbox" name="mtn_cb_reverse_commission_on_no_show" value="1" <?php checked( (bool) get_option( 'mtn_cb_reverse_commission_on_no_show', false ) ); ?>> <?php esc_html_e( 'در صورت عدم حضور پس از ایجاد کارمزد، سند برگشت خودکار ثبت شود.', 'mtn-cafe-booking' ); ?></label></td></tr>
					<tr><th><label for="mtn_cb_booking_expiry_minutes"><?php esc_html_e( 'مهلت تأیید رزرو (دقیقه)', 'mtn-cafe-booking' ); ?></label></th><td><input type="number" min="5" max="1440" id="mtn_cb_booking_expiry_minutes" name="mtn_cb_booking_expiry_minutes" value="<?php echo esc_attr( get_option( 'mtn_cb_booking_expiry_minutes', 30 ) ); ?>"></td></tr>
					<tr><th><label for="mtn_cb_checkin_token_minutes"><?php esc_html_e( 'اعتبار کد اعلام حضور (دقیقه)', 'mtn-cafe-booking' ); ?></label></th><td><input type="number" min="5" max="60" id="mtn_cb_checkin_token_minutes" name="mtn_cb_checkin_token_minutes" value="<?php echo esc_attr( get_option( 'mtn_cb_checkin_token_minutes', 15 ) ); ?>"><p class="description"><?php esc_html_e( 'کد اعلام حضور یک‌بارمصرف است و پس از این مدت منقضی می‌شود.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_booking_duration_minutes"><?php esc_html_e( 'مدت اشغال میز برای هر رزرو (دقیقه)', 'mtn-cafe-booking' ); ?></label></th><td><input type="number" min="30" max="720" id="mtn_cb_booking_duration_minutes" name="mtn_cb_booking_duration_minutes" value="<?php echo esc_attr( get_option( 'mtn_cb_booking_duration_minutes', 120 ) ); ?>"><p class="description"><?php esc_html_e( 'برای بررسی هم‌پوشانی رزروهای میز استفاده می‌شود.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_customer_cancel_cutoff_minutes"><?php esc_html_e( 'حداقل فاصله لغو توسط مشتری تا شروع رزرو (دقیقه)', 'mtn-cafe-booking' ); ?></label></th><td><input type="number" min="0" max="10080" id="mtn_cb_customer_cancel_cutoff_minutes" name="mtn_cb_customer_cancel_cutoff_minutes" value="<?php echo esc_attr( get_option( 'mtn_cb_customer_cancel_cutoff_minutes', 0 ) ); ?>"><p class="description"><?php esc_html_e( 'صفر یعنی لغو تا پیش از زمان شروع رزرو مجاز است؛ حداکثر مقدار قابل تنظیم ۷ روز است.', 'mtn-cafe-booking' ); ?></p></td></tr>
					<tr><th><label for="mtn_cb_booking_mode"><?php esc_html_e( 'روش انتخاب میز', 'mtn-cafe-booking' ); ?></label></th><td><select id="mtn_cb_booking_mode" name="mtn_cb_booking_mode"><option value="customer_selects_table" <?php selected( get_option( 'mtn_cb_booking_mode' ), 'customer_selects_table' ); ?>><?php esc_html_e( 'انتخاب توسط مشتری', 'mtn-cafe-booking' ); ?></option><option value="cafe_assigns_table" <?php selected( get_option( 'mtn_cb_booking_mode' ), 'cafe_assigns_table' ); ?>><?php esc_html_e( 'تخصیص توسط کافه', 'mtn-cafe-booking' ); ?></option></select></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
