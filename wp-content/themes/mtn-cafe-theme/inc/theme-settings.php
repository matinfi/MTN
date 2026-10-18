<?php
/**
 * Complete, tabbed theme settings for MTN Cafe.
 *
 * @package MTN_Cafe_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every theme option: name => array( type, default, sanitize callback ).
 *
 * @return array
 */
function mtn_cafe_theme_setting_definitions() {
	$text = 'sanitize_text_field';
	$area = 'sanitize_textarea_field';
	$bool = 'rest_sanitize_boolean';
	$url  = 'esc_url_raw';
	return array(
		// Brand.
		'mtn_cafe_theme_brand_name'           => array( 'string', '', $text ),
		'mtn_cafe_theme_brand_mark'           => array( 'string', 'M', 'mtn_cafe_theme_sanitize_mark' ),
		'mtn_cafe_theme_brand_subtitle'       => array( 'string', 'کافه و موسیقی', $text ),
		'mtn_cafe_theme_header_tagline'       => array( 'string', 'کشف تجربه‌های شبانه', $text ),
		'mtn_cafe_theme_meta_description'     => array( 'string', 'کشف کافه‌های دارای موسیقی زنده، بررسی ظرفیت و ثبت درخواست رزرو میز.', $area ),
		// Appearance.
		'mtn_cafe_theme_accent'               => array( 'string', '#e7a94b', 'sanitize_hex_color' ),
		'mtn_cafe_theme_bg'                   => array( 'string', '#101014', 'sanitize_hex_color' ),
		'mtn_cafe_theme_surface'              => array( 'string', '#1b1b22', 'sanitize_hex_color' ),
		'mtn_cafe_theme_text'                 => array( 'string', '#f7f4ef', 'sanitize_hex_color' ),
		'mtn_cafe_theme_muted'                => array( 'string', '#b8b4bf', 'sanitize_hex_color' ),
		'mtn_cafe_theme_default_mode'         => array( 'string', 'dark', 'mtn_cafe_theme_sanitize_mode' ),
		'mtn_cafe_theme_radius'               => array( 'integer', 18, 'mtn_cafe_theme_sanitize_radius' ),
		'mtn_cafe_theme_font'                 => array( 'string', 'vazirmatn', 'mtn_cafe_theme_sanitize_font' ),
		'mtn_cafe_theme_base_size'            => array( 'integer', 16, 'mtn_cafe_theme_sanitize_base_size' ),
		// Header and sidebar.
		'mtn_cafe_theme_sidebar_style'        => array( 'string', 'solid', 'mtn_cafe_theme_sanitize_sidebar' ),
		'mtn_cafe_theme_show_theme_toggle'    => array( 'boolean', true, $bool ),
		'mtn_cafe_theme_show_account_button'  => array( 'boolean', true, $bool ),
		'mtn_cafe_theme_show_whatsapp'        => array( 'boolean', true, $bool ),
		// Home page.
		'mtn_cafe_theme_hero_eyebrow'         => array( 'string', 'شب تو، انتخاب تو', $text ),
		'mtn_cafe_theme_hero_title'           => array( 'string', 'برای موسیقی زنده، کافه بعدی‌ات را پیدا کن.', $text ),
		'mtn_cafe_theme_hero_description'     => array( 'string', 'برنامه‌های شبانه را کشف کن، ظرفیت را بررسی کن و درخواست رزروت را برای تأیید کافه بفرست.', $area ),
		'mtn_cafe_theme_hero_image'           => array( 'string', '', $url ),
		'mtn_cafe_theme_hero_primary_label'   => array( 'string', 'کشف کافه‌ها', $text ),
		'mtn_cafe_theme_hero_primary_url'     => array( 'string', '/cafes/', $url ),
		'mtn_cafe_theme_hero_secondary_label' => array( 'string', 'رزرو میز', $text ),
		'mtn_cafe_theme_hero_secondary_url'   => array( 'string', '/book/', $url ),
		'mtn_cafe_theme_show_search'          => array( 'boolean', true, $bool ),
		'mtn_cafe_theme_show_events'          => array( 'boolean', true, $bool ),
		'mtn_cafe_theme_show_cafes'           => array( 'boolean', true, $bool ),
		'mtn_cafe_theme_show_benefits'        => array( 'boolean', true, $bool ),
		'mtn_cafe_theme_home_items'           => array( 'integer', 3, 'mtn_cafe_theme_sanitize_items' ),
		'mtn_cafe_theme_benefit_1_title'      => array( 'string', 'موزیک زنده', $text ),
		'mtn_cafe_theme_benefit_1_text'       => array( 'string', 'اجراهای زنده و شب‌های موسیقی را پیدا کن.', $area ),
		'mtn_cafe_theme_benefit_2_title'      => array( 'string', 'ظرفیت شفاف', $text ),
		'mtn_cafe_theme_benefit_2_text'       => array( 'string', 'پیش از ثبت، ظرفیت و میزهای قابل رزرو بررسی می‌شوند.', $area ),
		'mtn_cafe_theme_benefit_3_title'      => array( 'string', 'تأیید کافه', $text ),
		'mtn_cafe_theme_benefit_3_text'       => array( 'string', 'رزرو تا زمان پاسخ کافه در انتظار تأیید می‌ماند.', $area ),
		'mtn_cafe_theme_notice_title'         => array( 'string', 'شفافیت رزرو', $text ),
		'mtn_cafe_theme_notice_text'          => array( 'string', 'ثبت درخواست به معنی تأیید نهایی نیست و هیچ پرداخت اینترنتی در سایت انجام نمی‌شود.', $area ),
		// Web app.
		'mtn_cafe_theme_enable_pwa'           => array( 'boolean', true, $bool ),
		'mtn_cafe_theme_show_bottom_nav'      => array( 'boolean', true, $bool ),
		// Home page: how it works, FAQ and cooperation call to action.
		'mtn_cafe_theme_show_howto'           => array( 'boolean', true, $bool ),
		'mtn_cafe_theme_howto_title'          => array( 'string', 'رزرو در سه قدم', $text ),
		'mtn_cafe_theme_howto_1_title'        => array( 'string', 'کافه و برنامه را انتخاب کن', $text ),
		'mtn_cafe_theme_howto_1_text'         => array( 'string', 'با جست‌وجو یا فیلتر، شب موردنظرت را پیدا کن.', $area ),
		'mtn_cafe_theme_howto_2_title'        => array( 'string', 'درخواست رزرو بفرست', $text ),
		'mtn_cafe_theme_howto_2_text'         => array( 'string', 'تاریخ، تعداد نفرات و میز را مشخص کن و درخواستت را ثبت کن.', $area ),
		'mtn_cafe_theme_howto_3_title'        => array( 'string', 'منتظر تأیید کافه باش', $text ),
		'mtn_cafe_theme_howto_3_text'         => array( 'string', 'بعد از تأیید، کد رزرو و راهنمای ورود را در حسابت می‌بینی.', $area ),
		'mtn_cafe_theme_show_faq'             => array( 'boolean', true, $bool ),
		'mtn_cafe_theme_faq_title'            => array( 'string', 'پرسش‌های متداول', $text ),
		'mtn_cafe_theme_faq_1_q'              => array( 'string', 'آیا برای رزرو باید پولی پرداخت کنم؟', $text ),
		'mtn_cafe_theme_faq_1_a'              => array( 'string', 'خیر. در سایت هیچ پرداخت اینترنتی انجام نمی‌شود؛ هزینه‌های احتمالی طبق سیاست هر کافه در خود کافه تسویه می‌شوند.', $area ),
		'mtn_cafe_theme_faq_2_q'              => array( 'string', 'رزرو من چه زمانی قطعی می‌شود؟', $text ),
		'mtn_cafe_theme_faq_2_a'              => array( 'string', 'درخواست تا زمان پاسخ کافه در انتظار تأیید می‌ماند. پس از تأیید، وضعیت رزرو در حساب شما به «تأییدشده» تغییر می‌کند.', $area ),
		'mtn_cafe_theme_faq_3_q'              => array( 'string', 'چطور رزرو را لغو کنم؟', $text ),
		'mtn_cafe_theme_faq_3_a'              => array( 'string', 'از صفحه «رزروهای من» می‌توانید تا پیش از مهلت لغو، رزرو را لغو کنید. مهلت لغو در جزئیات رزرو نوشته شده است.', $area ),
		'mtn_cafe_theme_faq_4_q'              => array( 'string', 'چگونه حضورم را اعلام کنم؟', $text ),
		'mtn_cafe_theme_faq_4_a'              => array( 'string', 'هنگام رسیدن به کافه، از لینک امن اعلام حضور در صفحه رزرو استفاده کنید یا کد رزرو را به کارکنان کافه نشان دهید.', $area ),
		'mtn_cafe_theme_show_cooperation'     => array( 'boolean', true, $bool ),
		'mtn_cafe_theme_cooperation_title'    => array( 'string', 'کافه، رستوران یا هنرمند هستید؟', $text ),
		'mtn_cafe_theme_cooperation_text'     => array( 'string', 'درخواست همکاری بفرستید تا پس از بررسی، در فهرست ما منتشر شوید.', $area ),
		'mtn_cafe_theme_cooperation_label'    => array( 'string', 'درخواست همکاری', $text ),
		// Footer and contact.
		'mtn_cafe_theme_footer_description'   => array( 'string', 'کشف کافه‌ها و برنامه‌های شبانه', $area ),
		'mtn_cafe_theme_footer_notice'        => array( 'string', 'در نسخه اول پرداخت اینترنتی در سایت انجام نمی‌شود؛ هزینه‌های احتمالی طبق سیاست کافه تسویه می‌شوند.', $area ),
		'mtn_cafe_theme_footer_copyright'     => array( 'string', '', $text ),
		'mtn_cafe_theme_contact_phone'        => array( 'string', '', $text ),
		'mtn_cafe_theme_contact_email'        => array( 'string', '', 'sanitize_email' ),
		'mtn_cafe_theme_contact_address'      => array( 'string', '', $area ),
		'mtn_cafe_theme_social_instagram'     => array( 'string', '', $url ),
		'mtn_cafe_theme_social_telegram'      => array( 'string', '', $url ),
		'mtn_cafe_theme_social_x'             => array( 'string', '', $url ),
		'mtn_cafe_theme_social_youtube'       => array( 'string', '', $url ),
		// Advanced.
		'mtn_cafe_theme_custom_css'           => array( 'string', '', 'mtn_cafe_theme_sanitize_css' ),
	);
}

function mtn_cafe_theme_sanitize_mark( $value ) {
	$value = trim( sanitize_text_field( $value ) );
	$value = function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 2 ) : substr( $value, 0, 2 );
	return '' === $value ? 'M' : $value;
}
function mtn_cafe_theme_sanitize_mode( $value ) {
	return in_array( $value, array( 'dark', 'light' ), true ) ? $value : 'dark';
}
function mtn_cafe_theme_sanitize_radius( $value ) {
	return min( 28, max( 8, absint( $value ) ) );
}
function mtn_cafe_theme_sanitize_font( $value ) {
	return in_array( $value, array( 'vazirmatn', 'system' ), true ) ? $value : 'vazirmatn';
}
function mtn_cafe_theme_sanitize_base_size( $value ) {
	return min( 20, max( 14, absint( $value ) ) );
}
function mtn_cafe_theme_sanitize_sidebar( $value ) {
	return in_array( $value, array( 'solid', 'glass' ), true ) ? $value : 'solid';
}
function mtn_cafe_theme_sanitize_items( $value ) {
	return min( 12, max( 1, absint( $value ) ) );
}
function mtn_cafe_theme_sanitize_css( $value ) {
	$value = wp_strip_all_tags( (string) $value );
	$value = str_replace( '<', '', $value );
	$value = preg_replace( '/@import|expression\s*\(|javascript\s*:|behavior\s*:|-moz-binding/i', '', $value );
	return trim( substr( (string) $value, 0, 20000 ) );
}

function mtn_cafe_register_theme_settings() {
	foreach ( mtn_cafe_theme_setting_definitions() as $name => $def ) {
		register_setting( 'mtn_cafe_theme_settings', $name, array(
			'type'              => $def[0],
			'default'           => $def[1],
			'sanitize_callback' => $def[2],
			'show_in_rest'      => false,
		) );
	}
}
add_action( 'admin_init', 'mtn_cafe_register_theme_settings' );

function mtn_cafe_theme_settings_menu() {
	add_theme_page( __( 'تنظیمات قالب MTN', 'mtn-cafe-theme' ), __( 'تنظیمات قالب', 'mtn-cafe-theme' ), 'edit_theme_options', 'mtn-cafe-theme-settings', 'mtn_cafe_render_theme_settings' );
}
add_action( 'admin_menu', 'mtn_cafe_theme_settings_menu' );

function mtn_cafe_theme_option_value( $key ) {
	$defs = mtn_cafe_theme_setting_definitions();
	return get_option( $key, isset( $defs[ $key ] ) ? $defs[ $key ][1] : '' );
}

/**
 * Brand name: theme setting, then plugin brand, then site title.
 */
function mtn_cafe_theme_brand_name() {
	$name = trim( (string) mtn_cafe_theme_option_value( 'mtn_cafe_theme_brand_name' ) );
	if ( '' !== $name ) {
		return $name;
	}
	return (string) get_option( 'mtn_cb_brand_name', get_bloginfo( 'name' ) );
}

/**
 * Resolve a stored link: absolute URL, or an internal path relative to the site root.
 */
function mtn_cafe_theme_link( $key, $fallback = '/' ) {
	$value = trim( (string) mtn_cafe_theme_option_value( $key ) );
	if ( '' === $value ) {
		$value = $fallback;
	}
	return 0 === strpos( $value, 'http' ) ? $value : home_url( '/' . ltrim( $value, '/' ) );
}

/* ---------------------------------------------------------------------- *
 * Contrast helpers (WCAG 2.x) so unreadable colour choices are flagged.
 * ---------------------------------------------------------------------- */
function mtn_cafe_theme_luminance( $hex ) {
	$hex = ltrim( (string) sanitize_hex_color( $hex ), '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return 0.0;
	}
	$channels = array();
	foreach ( array( 0, 2, 4 ) as $offset ) {
		$c = hexdec( substr( $hex, $offset, 2 ) ) / 255;
		$channels[] = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
	}
	return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
}
function mtn_cafe_theme_contrast( $a, $b ) {
	$la = mtn_cafe_theme_luminance( $a );
	$lb = mtn_cafe_theme_luminance( $b );
	$hi = max( $la, $lb );
	$lo = min( $la, $lb );
	return ( $hi + 0.05 ) / ( $lo + 0.05 );
}
function mtn_cafe_theme_contrast_warnings() {
	$bg      = mtn_cafe_theme_option_value( 'mtn_cafe_theme_bg' );
	$surface = mtn_cafe_theme_option_value( 'mtn_cafe_theme_surface' );
	$text    = mtn_cafe_theme_option_value( 'mtn_cafe_theme_text' );
	$muted   = mtn_cafe_theme_option_value( 'mtn_cafe_theme_muted' );
	$accent  = mtn_cafe_theme_option_value( 'mtn_cafe_theme_accent' );
	$checks  = array(
		array( $text, $bg, 4.5, 'رنگ متن روی رنگ پس‌زمینه' ),
		array( $text, $surface, 4.5, 'رنگ متن روی کارت‌ها' ),
		array( $muted, $surface, 4.5, 'رنگ متن فرعی روی کارت‌ها' ),
		array( $accent, $bg, 3, 'رنگ اصلی روی پس‌زمینه' ),
		array( '#17120b', $accent, 4.5, 'متن دکمه‌ها روی رنگ اصلی' ),
	);
	$warnings = array();
	foreach ( $checks as $check ) {
		$ratio = mtn_cafe_theme_contrast( $check[0], $check[1] );
		if ( $ratio < $check[2] ) {
			$warnings[] = sprintf( '%1$s: نسبت کنتراست %2$s است و باید دست‌کم %3$s باشد.', $check[3], number_format_i18n( $ratio, 1 ), number_format_i18n( $check[2], 1 ) );
		}
	}
	return $warnings;
}

/* ---------------------------------------------------------------------- *
 * Settings screen
 * ---------------------------------------------------------------------- */
function mtn_cafe_theme_settings_sections() {
	return array(
		'brand'      => array(
			'label'  => 'هویت برند',
			'icon'   => '◆',
			'fields' => array(
				'mtn_cafe_theme_brand_name'       => array( 'نام برند', 'text', 'اگر خالی بماند، نام برند افزونه یا عنوان سایت نمایش داده می‌شود.' ),
				'mtn_cafe_theme_brand_mark'       => array( 'حرف نشان برند', 'text', 'یک یا دو نویسه که وقتی لوگو بارگذاری نشده در سایدبار دیده می‌شود. برای لوگوی تصویری از «نمایش ← سفارشی‌سازی ← هویت سایت» استفاده کنید.' ),
				'mtn_cafe_theme_brand_subtitle'   => array( 'زیرعنوان برند در سایدبار', 'text', '' ),
				'mtn_cafe_theme_header_tagline'   => array( 'متن کوچک سربرگ', 'text', '' ),
				'mtn_cafe_theme_meta_description' => array( 'توضیح متا صفحه اصلی', 'textarea', 'برای موتورهای جست‌وجو؛ حدود ۱۲۰ تا ۱۶۰ نویسه.' ),
			),
		),
		'appearance' => array(
			'label'  => 'رنگ‌ها و ظاهر',
			'icon'   => '◐',
			'fields' => array(
				'mtn_cafe_theme_accent'       => array( 'رنگ اصلی', 'color', 'دکمه‌ها، لینک‌ها و بخش فعال.' ),
				'mtn_cafe_theme_bg'           => array( 'رنگ پس‌زمینه', 'color', 'رنگ پشت محتوای سایت.' ),
				'mtn_cafe_theme_surface'      => array( 'رنگ کارت‌ها', 'color', 'پس‌زمینه کارت‌ها و پنل‌ها.' ),
				'mtn_cafe_theme_text'         => array( 'رنگ متن', 'color', 'رنگ متن اصلی در پوسته تاریک.' ),
				'mtn_cafe_theme_muted'        => array( 'رنگ متن فرعی', 'color', 'رنگ توضیحات و متن‌های کم‌اهمیت.' ),
				'mtn_cafe_theme_default_mode' => array( 'پوسته پیش‌فرض', 'select', 'انتخاب ذخیره‌شده بازدیدکننده بر این مقدار اولویت دارد.', array( 'dark' => 'تاریک', 'light' => 'روشن' ) ),
				'mtn_cafe_theme_font'         => array( 'فونت سایت', 'select', 'وزیرمتن همراه قالب ارائه می‌شود و نیازی به اینترنت خارجی ندارد.', array( 'vazirmatn' => 'وزیرمتن (پیشنهادی)', 'system' => 'فونت سیستم (سریع‌تر)' ) ),
				'mtn_cafe_theme_base_size'    => array( 'اندازه متن پایه', 'number', 'بین ۱۴ تا ۲۰ پیکسل.', array( 'min' => 14, 'max' => 20, 'unit' => 'پیکسل' ) ),
				'mtn_cafe_theme_radius'       => array( 'گردی گوشه‌ها', 'number', 'بین ۸ تا ۲۸ پیکسل.', array( 'min' => 8, 'max' => 28, 'unit' => 'پیکسل' ) ),
			),
		),
		'header'     => array(
			'label'  => 'سربرگ و سایدبار',
			'icon'   => '☰',
			'fields' => array(
				'mtn_cafe_theme_sidebar_style'       => array( 'سبک سایدبار', 'select', '', array( 'solid' => 'یکپارچه و ساده', 'glass' => 'شیشه‌ای با محوشدگی' ) ),
				'mtn_cafe_theme_show_theme_toggle'   => array( 'دکمه تغییر پوسته', 'checkbox', 'نمایش دکمه روشن/تاریک در سربرگ.' ),
				'mtn_cafe_theme_show_account_button' => array( 'دکمه حساب کاربری', 'checkbox', 'نمایش دکمه ورود/حساب در سربرگ.' ),
				'mtn_cafe_theme_show_whatsapp'       => array( 'دکمه شناور واتساپ', 'checkbox', 'در صورت ثبت شماره واتساپ در تنظیمات افزونه رزرو نمایش داده می‌شود.' ),
					'mtn_cafe_theme_show_bottom_nav'    => array( 'نوار ناوبری پایین موبایل', 'checkbox', 'نوار پنج‌دکمه‌ای شبیه اپلیکیشن در پایین صفحه گوشی.' ),
					'mtn_cafe_theme_enable_pwa'         => array( 'قابلیت نصب روی گوشی (وب‌اپ)', 'checkbox', 'امکان افزودن سایت به صفحه اصلی گوشی، صفحه آفلاین و ظاهر تمام‌صفحه. نیازمند HTTPS.' ),
			),
			'note'   => 'منوی سایدبار از «نمایش ← فهرست‌ها» و جایگاه «منوی اصلی» ساخته می‌شود؛ اگر منویی تعیین نشود، منوی پیش‌فرض نمایش داده می‌شود.',
		),
		'home'       => array(
			'label'  => 'صفحه اصلی',
			'icon'   => '⌂',
			'fields' => array(
				'mtn_cafe_theme_hero_eyebrow'         => array( 'متن بالای عنوان', 'text', '' ),
				'mtn_cafe_theme_hero_title'           => array( 'عنوان اصلی صفحه', 'text', '' ),
				'mtn_cafe_theme_hero_description'     => array( 'توضیح صفحه اصلی', 'textarea', '' ),
				'mtn_cafe_theme_hero_image'           => array( 'تصویر پس‌زمینه بخش اصلی', 'url', 'نشانی تصویر (اختیاری). تصویر را در «رسانه» بارگذاری کنید و نشانی آن را اینجا بگذارید.' ),
				'mtn_cafe_theme_hero_primary_label'   => array( 'متن دکمه اول', 'text', '' ),
				'mtn_cafe_theme_hero_primary_url'     => array( 'پیوند دکمه اول', 'url', 'مسیر داخلی مثل /cafes/ یا نشانی کامل.' ),
				'mtn_cafe_theme_hero_secondary_label' => array( 'متن دکمه دوم', 'text', '' ),
				'mtn_cafe_theme_hero_secondary_url'   => array( 'پیوند دکمه دوم', 'url', 'مسیر داخلی مثل /book/ یا نشانی کامل.' ),
				'mtn_cafe_theme_show_search'          => array( 'جست‌وجو', 'checkbox', 'نمایش بخش جست‌وجو.' ),
				'mtn_cafe_theme_show_events'          => array( 'برنامه‌های آینده', 'checkbox', 'نمایش بخش برنامه‌ها.' ),
				'mtn_cafe_theme_show_cafes'           => array( 'کافه‌ها', 'checkbox', 'نمایش بخش کافه‌ها.' ),
				'mtn_cafe_theme_show_benefits'        => array( 'مزیت‌ها و اطلاعیه رزرو', 'checkbox', 'نمایش کارت‌های مزیت و اطلاعیه.' ),
				'mtn_cafe_theme_home_items'           => array( 'تعداد کارت‌ها در هر بخش', 'number', 'بین ۱ تا ۱۲ کارت.', array( 'min' => 1, 'max' => 12, 'unit' => 'کارت' ) ),
				'mtn_cafe_theme_benefit_1_title'      => array( 'مزیت ۱ — عنوان', 'text', '' ),
				'mtn_cafe_theme_benefit_1_text'       => array( 'مزیت ۱ — متن', 'textarea', '' ),
				'mtn_cafe_theme_benefit_2_title'      => array( 'مزیت ۲ — عنوان', 'text', '' ),
				'mtn_cafe_theme_benefit_2_text'       => array( 'مزیت ۲ — متن', 'textarea', '' ),
				'mtn_cafe_theme_benefit_3_title'      => array( 'مزیت ۳ — عنوان', 'text', '' ),
				'mtn_cafe_theme_benefit_3_text'       => array( 'مزیت ۳ — متن', 'textarea', '' ),
				'mtn_cafe_theme_notice_title'         => array( 'اطلاعیه — عنوان', 'text', '' ),
				'mtn_cafe_theme_notice_text'          => array( 'اطلاعیه — متن', 'textarea', '' ),
					'mtn_cafe_theme_show_howto'           => array( 'بخش نحوه رزرو', 'checkbox', 'نمایش سه قدم رزرو.' ),
					'mtn_cafe_theme_howto_title'          => array( 'نحوه رزرو — عنوان', 'text', '' ),
					'mtn_cafe_theme_howto_1_title'        => array( 'قدم 1 — عنوان', 'text', '' ),
					'mtn_cafe_theme_howto_1_text'         => array( 'قدم 1 — متن', 'textarea', '' ),
					'mtn_cafe_theme_howto_2_title'        => array( 'قدم 2 — عنوان', 'text', '' ),
					'mtn_cafe_theme_howto_2_text'         => array( 'قدم 2 — متن', 'textarea', '' ),
					'mtn_cafe_theme_howto_3_title'        => array( 'قدم 3 — عنوان', 'text', '' ),
					'mtn_cafe_theme_howto_3_text'         => array( 'قدم 3 — متن', 'textarea', '' ),
					'mtn_cafe_theme_show_faq'             => array( 'بخش پرسش‌های متداول', 'checkbox', 'سؤال‌های بدون جواب یا بدون عنوان نمایش داده نمی‌شوند.' ),
					'mtn_cafe_theme_faq_title'            => array( 'پرسش‌های متداول — عنوان', 'text', '' ),
					'mtn_cafe_theme_faq_1_q'              => array( 'سؤال 1', 'text', '' ),
					'mtn_cafe_theme_faq_1_a'              => array( 'پاسخ 1', 'textarea', '' ),
					'mtn_cafe_theme_faq_2_q'              => array( 'سؤال 2', 'text', '' ),
					'mtn_cafe_theme_faq_2_a'              => array( 'پاسخ 2', 'textarea', '' ),
					'mtn_cafe_theme_faq_3_q'              => array( 'سؤال 3', 'text', '' ),
					'mtn_cafe_theme_faq_3_a'              => array( 'پاسخ 3', 'textarea', '' ),
					'mtn_cafe_theme_faq_4_q'              => array( 'سؤال 4', 'text', '' ),
					'mtn_cafe_theme_faq_4_a'              => array( 'پاسخ 4', 'textarea', '' ),
					'mtn_cafe_theme_show_cooperation'     => array( 'بخش دعوت به همکاری', 'checkbox', 'پیوند به صفحه درخواست همکاری.' ),
					'mtn_cafe_theme_cooperation_title'    => array( 'همکاری — عنوان', 'text', '' ),
					'mtn_cafe_theme_cooperation_text'     => array( 'همکاری — متن', 'textarea', '' ),
					'mtn_cafe_theme_cooperation_label'    => array( 'همکاری — متن دکمه', 'text', '' ),
			),
		),
		'footer'     => array(
			'label'  => 'پابرگ و تماس',
			'icon'   => '☎',
			'fields' => array(
				'mtn_cafe_theme_footer_description' => array( 'متن معرفی پابرگ', 'textarea', '' ),
				'mtn_cafe_theme_footer_notice'      => array( 'توضیح و اطلاعیه پابرگ', 'textarea', '' ),
				'mtn_cafe_theme_footer_copyright'   => array( 'متن حق نشر', 'text', 'اگر خالی بماند، «© سال نام برند» نمایش داده می‌شود.' ),
				'mtn_cafe_theme_contact_phone'      => array( 'تلفن تماس', 'text', '' ),
				'mtn_cafe_theme_contact_email'      => array( 'ایمیل تماس', 'email', '' ),
				'mtn_cafe_theme_contact_address'    => array( 'نشانی', 'textarea', '' ),
				'mtn_cafe_theme_social_instagram'   => array( 'اینستاگرام', 'url', 'نشانی کامل صفحه.' ),
				'mtn_cafe_theme_social_telegram'    => array( 'تلگرام', 'url', 'نشانی کامل کانال یا گروه.' ),
				'mtn_cafe_theme_social_x'           => array( 'ایکس (توییتر)', 'url', 'نشانی کامل صفحه.' ),
				'mtn_cafe_theme_social_youtube'     => array( 'یوتیوب', 'url', 'نشانی کامل کانال.' ),
			),
		),
		'advanced'   => array(
			'label'  => 'پیشرفته',
			'icon'   => '⚙',
			'fields' => array(
				'mtn_cafe_theme_custom_css' => array( 'CSS سفارشی', 'code', 'فقط مدیران با دسترسی ویرایش پوسته می‌توانند این بخش را تغییر دهند. تگ‌ها و @import حذف می‌شوند.' ),
			),
		),
	);
}

function mtn_cafe_theme_render_field( $key, $field ) {
	list( $label, $type, $description ) = $field;
	$extra = isset( $field[3] ) ? $field[3] : array();
	$value = mtn_cafe_theme_option_value( $key );
	echo '<tr><th scope="row">';
	if ( 'checkbox' === $type ) {
		echo esc_html( $label );
	} else {
		echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
	}
	echo '</th><td>';
	switch ( $type ) {
		case 'textarea':
			echo '<textarea class="large-text" rows="3" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( $value ) . '</textarea>';
			break;
		case 'code':
			echo '<textarea class="large-text code" dir="ltr" rows="10" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" spellcheck="false">' . esc_textarea( $value ) . '</textarea>';
			break;
		case 'color':
			$color = sanitize_hex_color( $value );
			echo '<input type="color" data-mtn-color id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $color ? $color : '#e7a94b' ) . '"> <code dir="ltr" data-mtn-color-code>' . esc_html( $color ? $color : '' ) . '</code>';
			break;
		case 'number':
			echo '<input type="number" min="' . esc_attr( isset( $extra['min'] ) ? $extra['min'] : 0 ) . '" max="' . esc_attr( isset( $extra['max'] ) ? $extra['max'] : 100 ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
			if ( ! empty( $extra['unit'] ) ) {
				echo ' ' . esc_html( $extra['unit'] );
			}
			break;
		case 'select':
			echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
			foreach ( $extra as $option_value => $option_label ) {
				echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( (string) $value, (string) $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
			}
			echo '</select>';
			break;
		case 'checkbox':
			echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="0"><label><input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="1" ' . checked( (bool) $value, true, false ) . '> فعال</label>';
			break;
		case 'url':
			echo '<input class="regular-text" dir="ltr" type="text" inputmode="url" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
			break;
		case 'email':
			echo '<input class="regular-text" dir="ltr" type="email" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
			break;
		default:
			echo '<input class="regular-text" type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
	}
	if ( $description ) {
		echo '<p class="description">' . esc_html( $description ) . '</p>';
	}
	echo '</td></tr>';
}

function mtn_cafe_render_theme_settings() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$sections = mtn_cafe_theme_settings_sections();
	$warnings = mtn_cafe_theme_contrast_warnings();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$was_reset = isset( $_GET['mtn_reset'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['mtn_reset'] ) );
	?>
	<div class="wrap mtn-theme-admin">
		<h1>تنظیمات قالب MTN</h1>
		<p class="mtn-theme-admin-lead">ظاهر، متن‌ها، صفحه اصلی، پابرگ و تماس سایت را از اینجا مدیریت کنید. تغییرات پس از ذخیره روی کل سایت اعمال می‌شود.</p>
		<?php if ( $was_reset ) : ?><div class="notice notice-success is-dismissible"><p>تنظیمات قالب به مقادیر پیش‌فرض بازگشت.</p></div><?php endif; ?>
		<?php if ( $warnings ) : ?>
			<div class="notice notice-warning"><p><strong>هشدار خوانایی:</strong> رنگ‌های انتخابی ممکن است برای بعضی کاربران خوانا نباشند.</p><ul style="list-style:disc;padding-inline-start:1.5rem"><?php foreach ( $warnings as $warning ) : ?><li><?php echo esc_html( $warning ); ?></li><?php endforeach; ?></ul></div>
		<?php endif; ?>
		<form method="post" action="options.php" data-mtn-theme-settings>
			<?php settings_fields( 'mtn_cafe_theme_settings' ); ?>
			<div class="mtn-theme-layout">
				<nav class="mtn-theme-tabs" aria-label="بخش‌های تنظیمات" role="tablist">
					<?php $first = true; foreach ( $sections as $id => $section ) : ?>
						<button type="button" role="tab" class="mtn-theme-tab<?php echo $first ? ' is-active' : ''; ?>" id="mtn-tab-<?php echo esc_attr( $id ); ?>" aria-controls="mtn-panel-<?php echo esc_attr( $id ); ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>" data-mtn-tab="<?php echo esc_attr( $id ); ?>"><span aria-hidden="true"><?php echo esc_html( $section['icon'] ); ?></span> <?php echo esc_html( $section['label'] ); ?></button>
					<?php $first = false; endforeach; ?>
				</nav>
				<div class="mtn-theme-panels">
					<?php $first = true; foreach ( $sections as $id => $section ) : ?>
						<section class="mtn-theme-panel" role="tabpanel" id="mtn-panel-<?php echo esc_attr( $id ); ?>" aria-labelledby="mtn-tab-<?php echo esc_attr( $id ); ?>" data-mtn-panel="<?php echo esc_attr( $id ); ?>" <?php echo $first ? '' : 'hidden'; ?>>
							<h2><?php echo esc_html( $section['label'] ); ?></h2>
							<?php if ( ! empty( $section['note'] ) ) : ?><p class="description"><?php echo esc_html( $section['note'] ); ?></p><?php endif; ?>
							<table class="form-table" role="presentation"><?php foreach ( $section['fields'] as $key => $field ) { mtn_cafe_theme_render_field( $key, $field ); } ?></table>
							<?php if ( 'appearance' === $id ) : ?>
								<div class="mtn-theme-preview" data-mtn-preview aria-label="پیش‌نمایش رنگ‌ها">
									<div class="mtn-theme-preview-card"><strong>نمونه عنوان کارت</strong><span>این یک متن فرعی برای بررسی خوانایی است.</span><b class="mtn-theme-preview-button">دکمه نمونه</b></div>
								</div>
							<?php endif; ?>
						</section>
					<?php $first = false; endforeach; ?>
				</div>
			</div>
			<?php submit_button( 'ذخیره تنظیمات قالب' ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mtn-theme-reset" onsubmit="return confirm('همه تنظیمات قالب به مقدار پیش‌فرض برگردد؟');">
			<input type="hidden" name="action" value="mtn_cafe_theme_reset">
			<?php wp_nonce_field( 'mtn_cafe_theme_reset', '_mtn_reset_nonce' ); ?>
			<button type="submit" class="button button-link-delete">بازنشانی همه تنظیمات به پیش‌فرض</button>
		</form>
	</div>
	<?php
}

function mtn_cafe_theme_handle_reset() {
	if ( ! current_user_can( 'edit_theme_options' ) || ! isset( $_POST['_mtn_reset_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_mtn_reset_nonce'] ) ), 'mtn_cafe_theme_reset' ) ) {
		wp_die( esc_html__( 'اجازه انجام این عملیات را ندارید.', 'mtn-cafe-theme' ), '', array( 'response' => 403 ) );
	}
	foreach ( array_keys( mtn_cafe_theme_setting_definitions() ) as $name ) {
		delete_option( $name );
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'mtn-cafe-theme-settings', 'mtn_reset' => '1' ), admin_url( 'themes.php' ) ) );
	exit;
}
add_action( 'admin_post_mtn_cafe_theme_reset', 'mtn_cafe_theme_handle_reset' );

function mtn_cafe_theme_admin_assets( $hook_suffix ) {
	if ( 'appearance_page_mtn-cafe-theme-settings' !== $hook_suffix ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	$css = $dir . '/assets/admin-settings.css';
	$js  = $dir . '/assets/admin-settings.js';
	wp_enqueue_style( 'mtn-cafe-admin-settings', $uri . '/assets/admin-settings.css', array(), file_exists( $css ) ? (string) filemtime( $css ) : '0.9.0' );
	wp_enqueue_script( 'mtn-cafe-admin-settings', $uri . '/assets/admin-settings.js', array(), file_exists( $js ) ? (string) filemtime( $js ) : '0.9.0', true );
}
add_action( 'admin_enqueue_scripts', 'mtn_cafe_theme_admin_assets' );

/* ---------------------------------------------------------------------- *
 * Front-end output
 * ---------------------------------------------------------------------- */
function mtn_cafe_theme_custom_tokens() {
	$keys   = array(
		'mtn_cafe_theme_accent'  => '#e7a94b',
		'mtn_cafe_theme_bg'      => '#101014',
		'mtn_cafe_theme_surface' => '#1b1b22',
		'mtn_cafe_theme_text'    => '#f7f4ef',
		'mtn_cafe_theme_muted'   => '#b8b4bf',
	);
	$tokens = array();
	foreach ( $keys as $key => $fallback ) {
		$value    = sanitize_hex_color( mtn_cafe_theme_option_value( $key ) );
		$tokens[] = '--' . str_replace( 'mtn_cafe_theme_', 'mtn-', $key ) . ':' . ( $value ? $value : $fallback );
	}
	$radius = min( 28, max( 8, absint( mtn_cafe_theme_option_value( 'mtn_cafe_theme_radius' ) ) ?: 18 ) );
	$size   = min( 20, max( 14, absint( mtn_cafe_theme_option_value( 'mtn_cafe_theme_base_size' ) ) ?: 16 ) );
	$accent = sanitize_hex_color( mtn_cafe_theme_option_value( 'mtn_cafe_theme_accent' ) );
	$font = 'vazirmatn' === mtn_cafe_theme_option_value( 'mtn_cafe_theme_font' ) ? "Vazirmatn,Tahoma,Arial,sans-serif" : 'Tahoma,Arial,sans-serif';
	printf(
		'<style id="mtn-cafe-theme-tokens">:root{%1$s;--mtn-radius:%2$dpx;--mtn-base-size:%3$dpx;--mtn-font:%5$s}:root[data-mtn-theme="light"]{--mtn-accent:%4$s}</style>' . "\n",
		esc_html( implode( ';', $tokens ) ),
		(int) $radius,
		(int) $size,
		esc_html( $accent ? $accent : '#e7a94b' ),
		esc_html( $font )
	);
	if ( 'glass' === mtn_cafe_theme_option_value( 'mtn_cafe_theme_sidebar_style' ) ) {
		echo '<style id="mtn-cafe-sidebar-style">.mtn-sidebar{background:rgba(18,20,26,.78);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px)}:root[data-mtn-theme="light"] .mtn-sidebar{background:rgba(255,255,255,.82)}</style>' . "\n";
	}
	$hero = esc_url( mtn_cafe_theme_option_value( 'mtn_cafe_theme_hero_image' ), array( 'http', 'https' ) );
	if ( $hero ) {
		printf( '<style id="mtn-cafe-hero-image">.hero{background-image:linear-gradient(135deg,rgba(13,14,18,.82),rgba(13,14,18,.55)),url("%s");background-size:cover;background-position:center}</style>' . "\n", esc_url( $hero ) );
	}
	$css = trim( (string) mtn_cafe_theme_option_value( 'mtn_cafe_theme_custom_css' ) );
	if ( '' !== $css ) {
		echo '<style id="mtn-cafe-custom-css">' . mtn_cafe_theme_sanitize_css( $css ) . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized: tags stripped.
	}
}
add_action( 'wp_head', 'mtn_cafe_theme_custom_tokens', 20 );

function mtn_cafe_theme_meta_description() {
	if ( ! is_front_page() ) {
		return;
	}
	$description = trim( (string) mtn_cafe_theme_option_value( 'mtn_cafe_theme_meta_description' ) );
	if ( '' !== $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'mtn_cafe_theme_meta_description', 1 );
