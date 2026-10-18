<?php get_header(); ?>
<section class="section container mtn-content mtn-404">
	<p class="eyebrow"><?php esc_html_e( 'خطای ۴۰۴', 'mtn-cafe-theme' ); ?></p>
	<h1><?php esc_html_e( 'این صفحه پیدا نشد', 'mtn-cafe-theme' ); ?></h1>
	<p class="muted"><?php esc_html_e( 'ممکن است نشانی تغییر کرده یا صفحه حذف شده باشد. از جست‌وجو یا پیوندهای زیر ادامه بدهید.', 'mtn-cafe-theme' ); ?></p>
	<?php get_search_form(); ?>
	<p class="mtn-404-links">
		<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'صفحه اصلی', 'mtn-cafe-theme' ); ?></a>
		<a class="button mtn-button-secondary" href="<?php echo esc_url( home_url( '/cafes/' ) ); ?>"><?php esc_html_e( 'کشف کافه‌ها', 'mtn-cafe-theme' ); ?></a>
		<a class="button mtn-button-secondary" href="<?php echo esc_url( home_url( '/events/' ) ); ?>"><?php esc_html_e( 'برنامه‌های زنده', 'mtn-cafe-theme' ); ?></a>
	</p>
</section>
<?php get_footer(); ?>
