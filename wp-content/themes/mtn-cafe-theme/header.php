<!doctype html>
<html <?php language_attributes(); ?> data-mtn-default-theme="<?php echo esc_attr( mtn_cafe_theme_option_value( 'mtn_cafe_theme_default_mode' ) ); ?>">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>(function(){try{var t=localStorage.getItem("mtn-color-theme");if(t!=="light"&&t!=="dark"){t="<?php echo esc_js( mtn_cafe_theme_option_value( 'mtn_cafe_theme_default_mode' ) ); ?>";}if(t==="light"||t==="dark"){document.documentElement.setAttribute("data-mtn-theme",t);}}catch(e){}})();</script>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
$mtn_brand  = mtn_cafe_theme_brand_name();
$mtn_mark   = mtn_cafe_theme_option_value( 'mtn_cafe_theme_brand_mark' );
$mtn_logged = is_user_logged_in();
$mtn_login  = wp_login_url();
?>
<a class="skip-link" href="#main-content"><?php esc_html_e( 'پرش به محتوای اصلی', 'mtn-cafe-theme' ); ?></a>
<div class="mtn-app-shell" data-mtn-app-shell>
  <aside class="mtn-sidebar" id="mtn-sidebar" aria-label="<?php esc_attr_e( 'ناوبری اصلی', 'mtn-cafe-theme' ); ?>">
    <a class="mtn-sidebar-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'صفحه اصلی %s', 'mtn-cafe-theme' ), $mtn_brand ) ); ?>">
      <?php if ( has_custom_logo() ) { the_custom_logo(); } else { ?><span class="mtn-brand-mark" aria-hidden="true"><?php echo esc_html( $mtn_mark ); ?></span><span class="mtn-brand-copy"><strong><?php echo esc_html( $mtn_brand ); ?></strong><small><?php echo esc_html( mtn_cafe_theme_option_value( 'mtn_cafe_theme_brand_subtitle' ) ); ?></small></span><?php } ?>
    </a>
    <nav class="mtn-side-nav">
      <span class="mtn-nav-label"><?php esc_html_e( 'منوی اصلی', 'mtn-cafe-theme' ); ?></span>
      <?php if ( has_nav_menu( 'primary' ) ) : ?>
        <?php
        wp_nav_menu( array(
          'theme_location' => 'primary',
          'container'      => false,
          'menu_class'     => 'mtn-menu',
          'items_wrap'     => '<ul class="%2$s">%3$s</ul>',
          'link_before'    => '<span class="mtn-nav-icon" aria-hidden="true">•</span><span class="mtn-nav-text">',
          'link_after'     => '</span>',
          'depth'          => 1,
          'fallback_cb'    => false,
        ) );
        ?>
      <?php else : ?>
      <a class="mtn-nav-link <?php echo is_front_page() ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'خانه', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">⌂</span><span class="mtn-nav-text">خانه</span></a>
      <a class="mtn-nav-link <?php echo is_post_type_archive( 'mtn_cafe' ) || is_singular( 'mtn_cafe' ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/cafes/' ) ); ?>" aria-label="<?php esc_attr_e( 'کشف کافه‌ها', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">⌕</span><span class="mtn-nav-text">کشف کافه‌ها</span></a>
      <a class="mtn-nav-link <?php echo is_post_type_archive( 'mtn_event' ) || is_singular( 'mtn_event' ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/events/' ) ); ?>" aria-label="<?php esc_attr_e( 'برنامه‌ها', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">♫</span><span class="mtn-nav-text">برنامه‌های زنده</span></a>
      <a class="mtn-nav-link <?php echo is_page( 'book' ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/book/' ) ); ?>" aria-label="<?php esc_attr_e( 'رزرو میز', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">▦</span><span class="mtn-nav-text">رزرو میز</span></a>
      <a class="mtn-nav-link <?php echo is_page( 'my-account' ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>" aria-label="<?php esc_attr_e( 'رزروهای من', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">◎</span><span class="mtn-nav-text">رزروهای من</span></a>
      <a class="mtn-nav-link <?php echo is_page( 'cooperation' ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/cooperation/' ) ); ?>" aria-label="<?php esc_attr_e( 'درخواست همکاری', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">＋</span><span class="mtn-nav-text">درخواست همکاری</span></a>
      <a class="mtn-nav-link <?php echo is_post_type_archive( 'mtn_artist' ) || is_singular( 'mtn_artist' ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/artists/' ) ); ?>" aria-label="<?php esc_attr_e( 'خواننده‌ها و دی‌جی‌ها', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">♪</span><span class="mtn-nav-text">خواننده‌ها و دی‌جی‌ها</span></a>
      <?php endif; ?>
      <?php if ( current_user_can( 'manage_options' ) ) : ?><a class="mtn-nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=mtn-cb-bookings' ) ); ?>" aria-label="<?php esc_attr_e( 'مدیریت مرکزی', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">▤</span><span class="mtn-nav-text">مدیریت مرکزی</span></a><?php endif; ?>
    </nav>
    <div class="mtn-sidebar-bottom">
      <?php if ( $mtn_logged ) : ?><a class="mtn-nav-link" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" aria-label="<?php esc_attr_e( 'خروج', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">↪</span><span class="mtn-nav-text">خروج از حساب</span></a><?php else : ?><a class="mtn-nav-link" href="<?php echo esc_url( $mtn_login ); ?>" aria-label="<?php esc_attr_e( 'ورود', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">⇥</span><span class="mtn-nav-text">ورود / ثبت‌نام</span></a><?php endif; ?>
      <button class="mtn-sidebar-toggle" type="button" data-mtn-sidebar-toggle aria-controls="mtn-sidebar" aria-expanded="true"><span class="mtn-nav-icon" aria-hidden="true">⇤</span><span class="mtn-nav-text">جمع کردن منو</span></button>
    </div>
  </aside>
  <div class="mtn-app-main">
    <header class="site-header">
      <button class="mtn-mobile-menu" type="button" data-mtn-mobile-toggle aria-controls="mtn-sidebar" aria-expanded="false" aria-label="<?php esc_attr_e( 'باز کردن منو', 'mtn-cafe-theme' ); ?>">☰</button>
      <div class="mtn-header-title"><span class="mtn-header-kicker"><?php echo esc_html( mtn_cafe_theme_option_value( 'mtn_cafe_theme_header_tagline' ) ); ?></span><strong><?php echo esc_html( is_front_page() ? $mtn_brand : wp_get_document_title() ); ?></strong></div>
      <div class="mtn-header-actions">
        <?php if ( mtn_cafe_pwa_enabled() ) : ?><button class="mtn-install-button" type="button" data-mtn-install hidden aria-label="نصب برنامه روی دستگاه"><span aria-hidden="true">⤓</span><span class="mtn-install-label">نصب برنامه</span></button><?php endif; ?>
        <?php if ( mtn_cafe_theme_option_value( 'mtn_cafe_theme_show_theme_toggle' ) ) : ?><button class="mtn-theme-toggle" type="button" data-mtn-theme-toggle aria-label="فعال‌سازی پوسته روشن" title="تغییر پوسته"><span aria-hidden="true">◐</span><span class="mtn-theme-toggle-label">پوسته روشن</span></button><?php endif; ?>
        <?php if ( mtn_cafe_theme_option_value( 'mtn_cafe_theme_show_account_button' ) ) : ?><a class="mtn-header-account" href="<?php echo esc_url( $mtn_logged ? home_url( '/my-account/' ) : $mtn_login ); ?>"><?php echo $mtn_logged ? 'حساب کاربری' : 'ورود / ثبت‌نام'; ?></a><?php endif; ?>
      </div>
    </header>
    <main id="main-content" tabindex="-1">
