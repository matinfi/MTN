<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main-content"><?php esc_html_e( 'پرش به محتوای اصلی', 'mtn-cafe-theme' ); ?></a>
<header class="site-header">
	<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'صفحه اصلی', 'mtn-cafe-theme' ); ?>">
		<?php if ( has_custom_logo() ) { the_custom_logo(); } else { ?><span>MTN</span> Cafe<?php } ?>
	</a>
	<nav class="nav-links" aria-label="<?php esc_attr_e( 'منوی اصلی', 'mtn-cafe-theme' ); ?>">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'خانه', 'mtn-cafe-theme' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/cafes/' ) ); ?>"><?php esc_html_e( 'کشف کافه‌ها', 'mtn-cafe-theme' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/events/' ) ); ?>"><?php esc_html_e( 'برنامه‌ها', 'mtn-cafe-theme' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/book/' ) ); ?>"><?php esc_html_e( 'رزرو میز', 'mtn-cafe-theme' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>"><?php esc_html_e( 'رزروهای من', 'mtn-cafe-theme' ); ?></a>
		<?php if ( current_user_can( 'mtn_manage_cafes' ) || current_user_can( 'manage_options' ) ) : ?><a href="<?php echo esc_url( home_url( '/cafe-dashboard/' ) ); ?>"><?php esc_html_e( 'پنل کافه', 'mtn-cafe-theme' ); ?></a><?php endif; ?>
		<?php if ( is_user_logged_in() ) : ?><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'خروج', 'mtn-cafe-theme' ); ?></a><?php else : ?><a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'ورود', 'mtn-cafe-theme' ); ?></a><?php endif; ?>
	</nav>
</header>
<main id="main-content" tabindex="-1">
