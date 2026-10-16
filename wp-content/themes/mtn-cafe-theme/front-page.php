<?php get_header(); ?>
<section class="hero">
	<div class="container">
		<p class="eyebrow"><?php esc_html_e( 'شب تو، انتخاب تو', 'mtn-cafe-theme' ); ?></p>
		<h1><?php esc_html_e( 'برای موسیقی زنده، کافه بعدی‌ات را پیدا کن.', 'mtn-cafe-theme' ); ?></h1>
		<p><?php esc_html_e( 'برنامه‌های شبانه را کشف کن، میز مناسب را انتخاب کن و درخواست رزروت را برای تأیید کافه بفرست.', 'mtn-cafe-theme' ); ?></p>
		<a class="button" href="<?php echo esc_url( home_url( '/cafes/' ) ); ?>"><?php esc_html_e( 'کشف کافه‌ها', 'mtn-cafe-theme' ); ?></a>
	</div>
</section>
<section class="section container">
	<div class="section-heading"><div><p class="eyebrow"><?php esc_html_e( 'شروع برنامه‌ریزی', 'mtn-cafe-theme' ); ?></p><h2><?php esc_html_e( 'امشب کجا بریم؟', 'mtn-cafe-theme' ); ?></h2></div></div>
	<div class="cards">
		<article class="card"><h3><?php esc_html_e( 'موزیک زنده', 'mtn-cafe-theme' ); ?></h3><p class="muted"><?php esc_html_e( 'اجراهای زنده و شب‌های موسیقی را پیدا کن.', 'mtn-cafe-theme' ); ?></p></article>
		<article class="card"><h3><?php esc_html_e( 'میز مناسب', 'mtn-cafe-theme' ); ?></h3><p class="muted"><?php esc_html_e( 'با توجه به تعداد نفرات، درخواست رزرو ثبت کن.', 'mtn-cafe-theme' ); ?></p></article>
		<article class="card"><h3><?php esc_html_e( 'تأیید کافه', 'mtn-cafe-theme' ); ?></h3><p class="muted"><?php esc_html_e( 'رزرو تا زمان پاسخ کافه در انتظار تأیید می‌ماند.', 'mtn-cafe-theme' ); ?></p></article>
	</div>
</section>
<section class="section container">
	<div class="notice"><strong><?php esc_html_e( 'شفافیت رزرو', 'mtn-cafe-theme' ); ?></strong><p><?php esc_html_e( 'ثبت درخواست به معنی تأیید نهایی نیست و هیچ پرداخت اینترنتی در سایت انجام نمی‌شود. اتصال واقعی پیامک و واتساپ باید جداگانه پیکربندی شود.', 'mtn-cafe-theme' ); ?></p></div>
</section>
<?php get_footer(); ?>
