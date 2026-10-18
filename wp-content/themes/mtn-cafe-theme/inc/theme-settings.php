<?php
/** Configurable appearance and homepage controls for MTN Cafe. @package MTN_Cafe_Theme */
defined( 'ABSPATH' ) || exit;

function mtn_cafe_theme_setting_definitions() {
 return array(
  'mtn_cafe_theme_accent' => array('string','#e7a94b','sanitize_hex_color'),
  'mtn_cafe_theme_bg' => array('string','#101014','sanitize_hex_color'),
  'mtn_cafe_theme_surface' => array('string','#1b1b22','sanitize_hex_color'),
  'mtn_cafe_theme_text' => array('string','#f7f4ef','sanitize_hex_color'),
  'mtn_cafe_theme_muted' => array('string','#b8b4bf','sanitize_hex_color'),
  'mtn_cafe_theme_default_mode' => array('string','dark',static function($v){return in_array($v,array('dark','light'),true)?$v:'dark';}),
  'mtn_cafe_theme_sidebar_style' => array('string','solid',static function($v){return in_array($v,array('solid','glass'),true)?$v:'solid';}),
  'mtn_cafe_theme_radius' => array('integer',18,static function($v){return min(28,max(8,absint($v)));}),
  'mtn_cafe_theme_header_tagline' => array('string','کشف تجربه‌های شبانه','sanitize_text_field'),
  'mtn_cafe_theme_brand_subtitle' => array('string','کافه و موسیقی','sanitize_text_field'),
  'mtn_cafe_theme_hero_eyebrow' => array('string','شب تو، انتخاب تو','sanitize_text_field'),
  'mtn_cafe_theme_hero_title' => array('string','برای موسیقی زنده، کافه بعدی‌ات را پیدا کن.','sanitize_text_field'),
  'mtn_cafe_theme_hero_description' => array('string','برنامه‌های شبانه را کشف کن، ظرفیت را بررسی کن و درخواست رزروت را برای تأیید کافه بفرست.','sanitize_textarea_field'),
  'mtn_cafe_theme_hero_primary_label' => array('string','کشف کافه‌ها','sanitize_text_field'),
  'mtn_cafe_theme_hero_primary_url' => array('string','/cafes/','esc_url_raw'),
  'mtn_cafe_theme_hero_secondary_label' => array('string','رزرو میز','sanitize_text_field'),
  'mtn_cafe_theme_hero_secondary_url' => array('string','/book/','esc_url_raw'),
  'mtn_cafe_theme_show_search' => array('boolean',true,'rest_sanitize_boolean'),
  'mtn_cafe_theme_show_events' => array('boolean',true,'rest_sanitize_boolean'),
  'mtn_cafe_theme_show_cafes' => array('boolean',true,'rest_sanitize_boolean'),
  'mtn_cafe_theme_show_benefits' => array('boolean',true,'rest_sanitize_boolean'),
  'mtn_cafe_theme_home_items' => array('integer',3,static function($v){return min(12,max(1,absint($v)));}),
  'mtn_cafe_theme_footer_description' => array('string','کشف کافه‌ها و برنامه‌های شبانه','sanitize_textarea_field'),
  'mtn_cafe_theme_footer_notice' => array('string','در نسخه اول پرداخت اینترنتی در سایت انجام نمی‌شود؛ هزینه‌های احتمالی طبق سیاست کافه تسویه می‌شوند.','sanitize_textarea_field'),
 );
}
function mtn_cafe_register_theme_settings() {
 foreach (mtn_cafe_theme_setting_definitions() as $name=>$def) {
  register_setting('mtn_cafe_theme_settings',$name,array('type'=>$def[0],'default'=>$def[1],'sanitize_callback'=>$def[2],'show_in_rest'=>false));
 }
}
add_action('admin_init','mtn_cafe_register_theme_settings');

function mtn_cafe_theme_settings_menu() {
 add_theme_page(__('تنظیمات قالب MTN','mtn-cafe-theme'),__('تنظیمات قالب','mtn-cafe-theme'),'edit_theme_options','mtn-cafe-theme-settings','mtn_cafe_render_theme_settings');
}
add_action('admin_menu','mtn_cafe_theme_settings_menu');

function mtn_cafe_theme_option_value($key) {
 $defs=mtn_cafe_theme_setting_definitions();
 return get_option($key,isset($defs[$key])?$defs[$key][1]:'');
}
function mtn_cafe_theme_text_field($key,$label,$description='',$type='text') {
 $value=mtn_cafe_theme_option_value($key);
 echo '<tr><th><label for="'.esc_attr($key).'">'.esc_html($label).'</label></th><td>';
 if ('textarea'===$type) {
  echo '<textarea class="large-text" rows="3" id="'.esc_attr($key).'" name="'.esc_attr($key).'">'.esc_textarea($value).'</textarea>';
 } elseif ('color'===$type) {
  $color=sanitize_hex_color($value);
  echo '<input type="color" id="'.esc_attr($key).'" name="'.esc_attr($key).'" value="'.esc_attr($color?$color:'#e7a94b').'">';
 } elseif ('number'===$type) {
  echo '<input type="number" min="1" max="12" id="'.esc_attr($key).'" name="'.esc_attr($key).'" value="'.esc_attr($value).'">';
 } else {
  echo '<input class="regular-text" type="'.esc_attr($type).'" id="'.esc_attr($key).'" name="'.esc_attr($key).'" value="'.esc_attr($value).'">';
 }
 if($description) echo '<p class="description">'.esc_html($description).'</p>';
 echo '</td></tr>';
}
function mtn_cafe_theme_checkbox($key,$label,$description='') {
 echo '<tr><th scope="row">'.esc_html($label).'</th><td><label><input type="checkbox" name="'.esc_attr($key).'" value="1" '.checked((bool)mtn_cafe_theme_option_value($key),true,false).'> نمایش این بخش</label>';
 if($description) echo '<p class="description">'.esc_html($description).'</p>';
 echo '</td></tr>';
}
function mtn_cafe_render_theme_settings() {
 if(!current_user_can('edit_theme_options')) return;
 $mode=mtn_cafe_theme_option_value('mtn_cafe_theme_default_mode');
 $sidebar=mtn_cafe_theme_option_value('mtn_cafe_theme_sidebar_style');
 $radius=absint(mtn_cafe_theme_option_value('mtn_cafe_theme_radius'));
 ?>
 <div class="wrap mtn-theme-admin"><h1>تنظیمات کامل قالب MTN</h1>
 <p>متن‌ها، رنگ‌ها و بخش‌های صفحه اصلی را از اینجا مدیریت کنید. برای تغییر لوگو و عنوان اصلی سایت از «نمایش ← سفارشی‌سازی/هویت سایت» وردپرس استفاده کنید.</p>
 <form method="post" action="options.php"><?php settings_fields('mtn_cafe_theme_settings'); ?>
 <div class="mtn-theme-settings-grid">
 <section class="mtn-theme-settings-card"><h2>۱. رنگ‌ها و ظاهر</h2><table class="form-table" role="presentation">
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_accent','رنگ اصلی','دکمه‌ها، لینک‌ها و بخش فعال.','color'); ?>
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_bg','رنگ پس‌زمینه','رنگ اصلی پشت محتوای سایت.','color'); ?>
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_surface','رنگ کارت‌ها','پس‌زمینه کارت‌ها و پنل‌ها.','color'); ?>
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_text','رنگ متن','رنگ متن اصلی در پوسته تاریک.','color'); ?>
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_muted','رنگ متن فرعی','رنگ توضیحات و متن‌های کم‌اهمیت.','color'); ?>
 <tr><th><label for="mtn_cafe_theme_default_mode">پوسته پیش‌فرض</label></th><td><select id="mtn_cafe_theme_default_mode" name="mtn_cafe_theme_default_mode"><option value="dark" <?php selected($mode,'dark'); ?>>تاریک</option><option value="light" <?php selected($mode,'light'); ?>>روشن</option></select><p class="description">انتخاب ذخیره‌شده بازدیدکننده بر این مقدار اولویت دارد.</p></td></tr>
 <tr><th><label for="mtn_cafe_theme_radius">گردی گوشه‌ها</label></th><td><input type="number" min="8" max="28" id="mtn_cafe_theme_radius" name="mtn_cafe_theme_radius" value="<?php echo esc_attr($radius); ?>"> پیکسل</td></tr>
 </table></section>
 <section class="mtn-theme-settings-card"><h2>۲. سربرگ و سایدبار</h2><table class="form-table" role="presentation">
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_header_tagline','متن کوچک سربرگ'); ?>
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_brand_subtitle','زیرعنوان برند در سایدبار'); ?>
 <tr><th><label for="mtn_cafe_theme_sidebar_style">سبک سایدبار</label></th><td><select id="mtn_cafe_theme_sidebar_style" name="mtn_cafe_theme_sidebar_style"><option value="solid" <?php selected($sidebar,'solid'); ?>>یکپارچه و ساده</option><option value="glass" <?php selected($sidebar,'glass'); ?>>شیشه‌ای با محوشدگی</option></select></td></tr>
 </table><p class="description">برای لوگوی تصویری، از تنظیمات هویت سایت وردپرس استفاده کنید.</p></section>
 <section class="mtn-theme-settings-card"><h2>۳. محتوای صفحه اصلی</h2><table class="form-table" role="presentation">
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_hero_eyebrow','متن بالای عنوان'); ?>
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_hero_title','عنوان اصلی صفحه'); ?>
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_hero_description','توضیح صفحه اصلی','','textarea'); ?>
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_hero_primary_label','متن دکمه اول'); ?>
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_hero_primary_url','پیوند دکمه اول','مسیر داخلی مثل /cafes/ یا نشانی کامل.','url'); ?>
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_hero_secondary_label','متن دکمه دوم'); ?>
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_hero_secondary_url','پیوند دکمه دوم','مسیر داخلی مثل /book/ یا نشانی کامل.','url'); ?>
 <?php mtn_cafe_theme_checkbox('mtn_cafe_theme_show_search','نمایش جست‌وجو'); ?>
 <?php mtn_cafe_theme_checkbox('mtn_cafe_theme_show_events','نمایش برنامه‌های آینده'); ?>
 <?php mtn_cafe_theme_checkbox('mtn_cafe_theme_show_cafes','نمایش کافه‌ها'); ?>
 <?php mtn_cafe_theme_checkbox('mtn_cafe_theme_show_benefits','نمایش بخش مزیت‌ها و توضیح رزرو'); ?>
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_home_items','تعداد کارت‌ها در هر بخش','بین ۱ تا ۱۲ کارت.','number'); ?>
 </table></section>
 <section class="mtn-theme-settings-card"><h2>۴. پابرگ سایت</h2><table class="form-table" role="presentation">
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_footer_description','متن معرفی پابرگ','','textarea'); ?>
 <?php mtn_cafe_theme_text_field('mtn_cafe_theme_footer_notice','توضیح و اطلاعیه پابرگ','','textarea'); ?>
 </table></section>
 </div><?php submit_button('ذخیره تنظیمات قالب'); ?></form></div>
 <style>.mtn-theme-settings-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr));gap:18px;max-width:1200px;margin-top:24px}.mtn-theme-settings-card{background:#fff;border:1px solid #dcdcde;border-radius:14px;padding:8px 22px 20px}.mtn-theme-settings-card h2{font-size:16px;padding-bottom:12px;border-bottom:1px solid #eee}.mtn-theme-settings-card input[type=color]{width:64px;height:38px;padding:3px}@media(max-width:782px){.mtn-theme-settings-card{padding-inline:14px}.mtn-theme-settings-card .form-table th,.mtn-theme-settings-card .form-table td{display:block;width:100%;padding:8px 0}}</style>
 <?php
}
function mtn_cafe_theme_custom_tokens() {
 $keys=array('mtn_cafe_theme_accent'=>'#e7a94b','mtn_cafe_theme_bg'=>'#101014','mtn_cafe_theme_surface'=>'#1b1b22','mtn_cafe_theme_text'=>'#f7f4ef','mtn_cafe_theme_muted'=>'#b8b4bf');
 $tokens=array();
 foreach($keys as $key=>$fallback){$value=sanitize_hex_color(mtn_cafe_theme_option_value($key));$tokens[]='--'.str_replace('mtn_cafe_theme_','mtn-',$key).':'.($value?$value:$fallback);}
 $radius=min(28,max(8,absint(mtn_cafe_theme_option_value('mtn_cafe_theme_radius'))?:18));
 printf('<style id="mtn-cafe-theme-tokens">:root{%1$s;--mtn-radius:%2$dpx}</style>',esc_html(implode(';',$tokens)),$radius);
 if('glass'===mtn_cafe_theme_option_value('mtn_cafe_theme_sidebar_style')) echo '<style id="mtn-cafe-sidebar-style">.mtn-sidebar{background:rgba(18,20,26,.78);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px)}:root[data-mtn-theme="light"] .mtn-sidebar{background:rgba(255,255,255,.82)}</style>';
}
add_action('wp_head','mtn_cafe_theme_custom_tokens',20);
