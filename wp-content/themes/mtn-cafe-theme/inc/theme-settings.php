<?php
/** MTN theme appearance settings. @package MTN_Cafe_Theme */
defined( 'ABSPATH' ) || exit;
function mtn_cafe_register_theme_settings() {
 register_setting( 'mtn_cafe_theme_settings', 'mtn_cafe_theme_accent', array( 'type'=>'string', 'sanitize_callback'=>'sanitize_hex_color', 'default'=>'#e7a94b' ) );
 register_setting( 'mtn_cafe_theme_settings', 'mtn_cafe_theme_default_mode', array( 'type'=>'string', 'sanitize_callback'=>static function($v){return in_array($v,array('dark','light'),true)?$v:'dark';}, 'default'=>'dark' ) );
 register_setting( 'mtn_cafe_theme_settings', 'mtn_cafe_theme_sidebar_style', array( 'type'=>'string', 'sanitize_callback'=>static function($v){return in_array($v,array('solid','glass'),true)?$v:'solid';}, 'default'=>'solid' ) );
 register_setting( 'mtn_cafe_theme_settings', 'mtn_cafe_theme_radius', array( 'type'=>'integer', 'sanitize_callback'=>static function($v){return min(28,max(8,absint($v)));}, 'default'=>18 ) );
 register_setting( 'mtn_cafe_theme_settings', 'mtn_cafe_theme_header_tagline', array( 'type'=>'string', 'sanitize_callback'=>'sanitize_text_field', 'default'=>'کشف تجربه‌های شبانه' ) );
}
add_action('admin_init','mtn_cafe_register_theme_settings');
function mtn_cafe_theme_settings_menu(){ add_theme_page(__('تنظیمات قالب MTN','mtn-cafe-theme'),__('تنظیمات قالب','mtn-cafe-theme'),'edit_theme_options','mtn-cafe-theme-settings','mtn_cafe_render_theme_settings'); }
add_action('admin_menu','mtn_cafe_theme_settings_menu');
function mtn_cafe_render_theme_settings(){
 if(!current_user_can('edit_theme_options')) return;
 $accent=get_option('mtn_cafe_theme_accent','#e7a94b'); $mode=get_option('mtn_cafe_theme_default_mode','dark'); $sidebar=get_option('mtn_cafe_theme_sidebar_style','solid'); $radius=absint(get_option('mtn_cafe_theme_radius',18)); $tagline=get_option('mtn_cafe_theme_header_tagline','کشف تجربه‌های شبانه'); ?>
 <div class="wrap mtn-theme-admin"><h1><?php esc_html_e('تنظیمات قالب MTN','mtn-cafe-theme'); ?></h1><p><?php esc_html_e('رنگ‌ها، حالت نمایش، سایدبار و ظاهر کلی سایت را از این بخش مدیریت کنید.','mtn-cafe-theme'); ?></p>
 <form method="post" action="options.php"><?php settings_fields('mtn_cafe_theme_settings'); ?>
 <div class="mtn-theme-settings-grid">
 <section class="mtn-theme-settings-card"><h2>رنگ و حالت نمایش</h2><table class="form-table" role="presentation">
 <tr><th><label for="mtn_cafe_theme_accent">رنگ اصلی قالب</label></th><td><input type="color" id="mtn_cafe_theme_accent" name="mtn_cafe_theme_accent" value="<?php echo esc_attr(sanitize_hex_color($accent)?$accent:'#e7a94b'); ?>"><p class="description">رنگ تأکیدی لینک‌ها، دکمه‌ها و وضعیت فعال.</p></td></tr>
 <tr><th><label for="mtn_cafe_theme_default_mode">پوسته پیش‌فرض</label></th><td><select id="mtn_cafe_theme_default_mode" name="mtn_cafe_theme_default_mode"><option value="dark" <?php selected($mode,'dark'); ?>>تاریک</option><option value="light" <?php selected($mode,'light'); ?>>روشن</option></select><p class="description">انتخاب ذخیره‌شده بازدیدکننده بر این مقدار اولویت دارد.</p></td></tr>
 <tr><th><label for="mtn_cafe_theme_radius">گردی گوشه کارت‌ها</label></th><td><input type="number" min="8" max="28" id="mtn_cafe_theme_radius" name="mtn_cafe_theme_radius" value="<?php echo esc_attr($radius>=8&&$radius<=28?$radius:18); ?>"> پیکسل (۸ تا ۲۸)</td></tr>
 </table></section>
 <section class="mtn-theme-settings-card"><h2>سایدبار و سربرگ</h2><table class="form-table" role="presentation">
 <tr><th><label for="mtn_cafe_theme_sidebar_style">سبک سایدبار</label></th><td><select id="mtn_cafe_theme_sidebar_style" name="mtn_cafe_theme_sidebar_style"><option value="solid" <?php selected($sidebar,'solid'); ?>>یکپارچه و ساده</option><option value="glass" <?php selected($sidebar,'glass'); ?>>شیشه‌ای با محوشدگی</option></select><p class="description">ظاهر نوار کناری سایت را تغییر می‌دهد.</p></td></tr>
 <tr><th><label for="mtn_cafe_theme_header_tagline">متن کوچک سربرگ</label></th><td><input class="regular-text" type="text" maxlength="80" id="mtn_cafe_theme_header_tagline" name="mtn_cafe_theme_header_tagline" value="<?php echo esc_attr($tagline); ?>"><p class="description">متنی که بالای عنوان صفحه نمایش داده می‌شود.</p></td></tr>
 </table></section></div><?php submit_button(__('ذخیره تنظیمات قالب','mtn-cafe-theme')); ?></form></div>
 <style>.mtn-theme-settings-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr));gap:18px;max-width:1100px;margin-top:24px}.mtn-theme-settings-card{background:#fff;border:1px solid #dcdcde;border-radius:14px;padding:8px 22px 20px}.mtn-theme-settings-card h2{font-size:16px;padding-bottom:12px;border-bottom:1px solid #eee}@media(max-width:782px){.mtn-theme-settings-card{padding-inline:14px}.mtn-theme-settings-card .form-table th,.mtn-theme-settings-card .form-table td{display:block;width:100%;padding:8px 0}}</style>
 <?php
}
function mtn_cafe_theme_custom_tokens(){
 $accent=get_option('mtn_cafe_theme_accent','#e7a94b'); if(!is_string($accent)||!sanitize_hex_color($accent))$accent='#e7a94b';
 $radius=min(28,max(8,absint(get_option('mtn_cafe_theme_radius',18))?:18));
 printf('<style id="mtn-cafe-theme-tokens">:root{--mtn-accent:%1$s;--mtn-radius:%2$dpx}</style>',esc_html($accent),$radius);
 if('glass'===get_option('mtn_cafe_theme_sidebar_style','solid')) echo '<style id="mtn-cafe-sidebar-style">.mtn-sidebar{background:rgba(18,20,26,.78);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px)}:root[data-mtn-theme="light"] .mtn-sidebar{background:rgba(255,255,255,.82)}</style>';
}
add_action('wp_head','mtn_cafe_theme_custom_tokens',20);
