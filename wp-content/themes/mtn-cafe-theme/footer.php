</main>
<footer class="site-footer">
	<div class="container">
		<p><?php echo esc_html( get_option( 'mtn_cb_brand_name', get_bloginfo( 'name' ) ) ); ?> — <?php esc_html_e( 'کشف کافه‌ها و برنامه‌های شبانه', 'mtn-cafe-theme' ); ?></p>
		<p><?php esc_html_e( 'در نسخه اول پرداخت اینترنتی در سایت انجام نمی‌شود؛ هزینه‌های احتمالی طبق سیاست کافه تسویه می‌شوند.', 'mtn-cafe-theme' ); ?></p>
	</div>
</footer>
<?php $mtn_whatsapp_number = preg_replace( '/[^0-9]/', '', (string) get_option( 'mtn_cb_whatsapp_number', '' ) ); ?>
<?php if ( strlen( $mtn_whatsapp_number ) >= 8 ) : ?>
	<a class="mtn-whatsapp-float" href="<?php echo esc_url( 'https://wa.me/' . $mtn_whatsapp_number . '?text=' . rawurlencode( 'سلام، برای رزرو کافه و برنامه‌های زنده راهنمایی می‌خواهم.' ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="گفت‌وگو با پشتیبانی در واتساپ"><span aria-hidden="true">◉</span><span>گفت‌وگو با ما</span></a>
<?php endif; ?>
  </div><!-- .mtn-app-main -->
</div><!-- .mtn-app-shell -->
<?php wp_footer(); ?>
</body>
</html>
