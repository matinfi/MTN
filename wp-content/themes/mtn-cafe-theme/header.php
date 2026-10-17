<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>(function(){try{var t=localStorage.getItem("mtn-color-theme");if(t==="light"||t==="dark"){document.documentElement.setAttribute("data-mtn-theme",t);}}catch(e){}})();</script>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main-content"><?php esc_html_e( 'پرش به محتوای اصلی', 'mtn-cafe-theme' ); ?></a>
<div class="mtn-app-shell" data-mtn-app-shell>
  <aside class="mtn-sidebar" id="mtn-sidebar" aria-label="<?php esc_attr_e( 'ناوبری اصلی', 'mtn-cafe-theme' ); ?>">
    <a class="mtn-sidebar-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'صفحه اصلی MTN کافه', 'mtn-cafe-theme' ); ?>">
      <?php if ( has_custom_logo() ) { the_custom_logo(); } else { ?><span class="mtn-brand-mark" aria-hidden="true">M</span><span class="mtn-brand-copy"><strong>MTN</strong><small>کافه و موسیقی</small></span><?php } ?>
    </a>
    <nav class="mtn-side-nav">
      <span class="mtn-nav-label"><?php esc_html_e( 'منوی اصلی', 'mtn-cafe-theme' ); ?></span>
      <a class="mtn-nav-link <?php echo is_front_page() ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'خانه', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">⌂</span><span class="mtn-nav-text">خانه</span></a>
      <a class="mtn-nav-link <?php echo is_post_type_archive( 'mtn_cafe' ) || is_singular( 'mtn_cafe' ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/cafes/' ) ); ?>" aria-label="<?php esc_attr_e( 'کشف کافه‌ها', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">⌕</span><span class="mtn-nav-text">کشف کافه‌ها</span></a>
      <a class="mtn-nav-link <?php echo is_post_type_archive( 'mtn_event' ) || is_singular( 'mtn_event' ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/events/' ) ); ?>" aria-label="<?php esc_attr_e( 'برنامه‌ها', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">♫</span><span class="mtn-nav-text">برنامه‌های زنده</span></a>
      <a class="mtn-nav-link" href="<?php echo esc_url( home_url( '/book/' ) ); ?>" aria-label="<?php esc_attr_e( 'رزرو میز', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">▦</span><span class="mtn-nav-text">رزرو میز</span></a>
      <a class="mtn-nav-link <?php echo is_page( 'my-account' ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>" aria-label="<?php esc_attr_e( 'رزروهای من', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">◎</span><span class="mtn-nav-text">رزروهای من</span></a>
      <a class="mtn-nav-link" href="<?php echo esc_url( home_url( '/cooperation/' ) ); ?>" aria-label="<?php esc_attr_e( 'درخواست همکاری', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">＋</span><span class="mtn-nav-text">درخواست همکاری</span></a>
      <a class="mtn-nav-link" href="<?php echo esc_url( home_url( '/artists/' ) ); ?>" aria-label="خواننده‌ها و دی‌جی‌ها"><span class="mtn-nav-icon" aria-hidden="true">♫</span><span class="mtn-nav-text">خواننده‌ها و دی‌جی‌ها</span></a>
      <?php if ( current_user_can( 'manage_options' ) ) : ?><a class="mtn-nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=mtn-cb-bookings' ) ); ?>" aria-label="<?php esc_attr_e( 'مدیریت مرکزی', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">▤</span><span class="mtn-nav-text">مدیریت مرکزی</span></a><?php endif; ?>
    </nav>
    <div class="mtn-sidebar-bottom">
      <?php if ( is_user_logged_in() ) : ?><a class="mtn-nav-link" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" aria-label="<?php esc_attr_e( 'خروج', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">↪</span><span class="mtn-nav-text">خروج از حساب</span></a><?php else : ?><a class="mtn-nav-link" href="<?php echo esc_url( wp_login_url() ); ?>" aria-label="<?php esc_attr_e( 'ورود', 'mtn-cafe-theme' ); ?>"><span class="mtn-nav-icon" aria-hidden="true">⇥</span><span class="mtn-nav-text">ورود / ثبت‌نام</span></a><?php endif; ?>
      <button class="mtn-sidebar-toggle" type="button" data-mtn-sidebar-toggle aria-controls="mtn-sidebar" aria-expanded="true"><span class="mtn-nav-icon" aria-hidden="true">⇤</span><span class="mtn-nav-text">جمع کردن منو</span></button>
    </div>
  </aside>
  <div class="mtn-app-main">
    <header class="site-header">
      <button class="mtn-mobile-menu" type="button" data-mtn-mobile-toggle aria-controls="mtn-sidebar" aria-expanded="false" aria-label="<?php esc_attr_e( 'باز کردن منو', 'mtn-cafe-theme' ); ?>">☰</button>
      <div class="mtn-header-title"><span class="mtn-header-kicker">کشف تجربه‌های شبانه</span><strong><?php echo esc_html( is_front_page() ? 'شب بعدی‌ات را پیدا کن' : wp_get_document_title() ); ?></strong></div>
      <div class="mtn-header-actions">
        <a class="mtn-header-account" href="<?php echo esc_url( is_user_logged_in() ? home_url( '/my-account/' ) : wp_login_url() ); ?>"><?php echo is_user_logged_in() ? 'حساب کاربری' : 'ورود / ثبت‌نام'; ?></a>
      </div>
    </header>
    <main id="main-content" tabindex="-1">
