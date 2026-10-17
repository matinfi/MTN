<?php
/**
 * Independent MTN theme settings page.
 *
 * @package MTN_Cafe_Theme
 */
defined( 'ABSPATH' ) || exit;

function mtn_cafe_register_theme_settings() {
	register_setting( 'mtn_cafe_theme_settings', 'mtn_cafe_theme_accent', array(
		'type' => 'string',
		'sanitize_callback' => 'sanitize_hex_color',
		'default' => '#e7a94b',
	) );
	register_setting( 'mtn_cafe_theme_settings', 'mtn_cafe_theme_default_mode', array(
		'type' => 'string',
		'sanitize_callback' => static function ( $value ) {
			return in_array( $value, array( 'dark', 'light' ), true ) ? $value : 'dark';
		},
		'default' => 'dark',
	) );
}
add_action( 'admin_init', 'mtn_cafe_register_theme_settings' );

function mtn_cafe_theme_settings_menu() {
	add_theme_page(
		__( 'تنظیمات قالب MTN', 'mtn-cafe-theme' ),
		__( 'تنظیمات قالب', 'mtn-cafe-theme' ),
		'edit_theme_options',
		'mtn-cafe-theme-settings',
		'mtn_cafe_render_theme_settings'
	);
}
add_action( 'admin_menu', 'mtn_cafe_theme_settings_menu' );

function mtn_cafe_render_theme_settings() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	?>
	<div class="wrap mtn-theme-admin">
		<h1><?php esc_html_e( 'تنظیمات قالب MTN', 'mtn-cafe-theme' ); ?></h1>
		<p><?php esc_html_e( 'تنظیمات ظاهری قالب مستقل از تنظیمات رزرو و مالی افزونه مدیریت می‌شوند.', 'mtn-cafe-theme' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'mtn_cafe_theme_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="mtn_cafe_theme_accent"><?php esc_html_e( 'رنگ اصلی قالب', 'mtn-cafe-theme' ); ?></label></th>
					<td><input type="color" id="mtn_cafe_theme_accent" name="mtn_cafe_theme_accent" value="<?php echo esc_attr( get_option( 'mtn_cafe_theme_accent', '#e7a94b' ) ); ?>"><p class="description"><?php esc_html_e( 'برای دکمه‌ها، لینک‌ها و وضعیت فعال استفاده می‌شود.', 'mtn-cafe-theme' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="mtn_cafe_theme_default_mode"><?php esc_html_e( 'پوسته پیش‌فرض بازدیدکنندگان', 'mtn-cafe-theme' ); ?></label></th>
					<td><select id="mtn_cafe_theme_default_mode" name="mtn_cafe_theme_default_mode"><option value="dark" <?php selected( get_option( 'mtn_cafe_theme_default_mode', 'dark' ), 'dark' ); ?>><?php esc_html_e( 'تاریک', 'mtn-cafe-theme' ); ?></option><option value="light" <?php selected( get_option( 'mtn_cafe_theme_default_mode', 'dark' ), 'light' ); ?>><?php esc_html_e( 'روشن', 'mtn-cafe-theme' ); ?></option></select><p class="description"><?php esc_html_e( 'انتخاب ذخیره‌شده هر بازدیدکننده بر این مقدار اولویت دارد.', 'mtn-cafe-theme' ); ?></p></td>
				</tr>
			</table>
			<?php submit_button( __( 'ذخیره تنظیمات قالب', 'mtn-cafe-theme' ) ); ?>
		</form>
	</div>
	<?php
}

function mtn_cafe_theme_custom_tokens() {
	$accent = get_option( 'mtn_cafe_theme_accent', '#e7a94b' );
	if ( ! is_string( $accent ) || ! sanitize_hex_color( $accent ) ) {
		$accent = '#e7a94b';
	}
	printf( '<style id="mtn-cafe-theme-tokens">:root{--mtn-accent:%1$s}</style>', esc_html( $accent ) );
}
add_action( 'wp_head', 'mtn_cafe_theme_custom_tokens', 20 );
