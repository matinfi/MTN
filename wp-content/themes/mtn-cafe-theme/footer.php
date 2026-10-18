</main>
<?php
$mtn_brand     = mtn_cafe_theme_brand_name();
$mtn_phone     = trim( (string) mtn_cafe_theme_option_value( 'mtn_cafe_theme_contact_phone' ) );
$mtn_email     = trim( (string) mtn_cafe_theme_option_value( 'mtn_cafe_theme_contact_email' ) );
$mtn_address   = trim( (string) mtn_cafe_theme_option_value( 'mtn_cafe_theme_contact_address' ) );
$mtn_copyright = trim( (string) mtn_cafe_theme_option_value( 'mtn_cafe_theme_footer_copyright' ) );
$mtn_socials   = array(
	'mtn_cafe_theme_social_instagram' => 'اینستاگرام',
	'mtn_cafe_theme_social_telegram'  => 'تلگرام',
	'mtn_cafe_theme_social_x'         => 'ایکس',
	'mtn_cafe_theme_social_youtube'   => 'یوتیوب',
);
?>
<footer class="site-footer">
	<div class="container mtn-footer-grid">
		<div class="mtn-footer-about">
			<p><strong><?php echo esc_html( $mtn_brand ); ?></strong> — <?php echo esc_html( mtn_cafe_theme_option_value( 'mtn_cafe_theme_footer_description' ) ); ?></p>
			<p class="mtn-footer-notice"><?php echo esc_html( mtn_cafe_theme_option_value( 'mtn_cafe_theme_footer_notice' ) ); ?></p>
		</div>
		<?php if ( $mtn_phone || $mtn_email || $mtn_address ) : ?>
		<address class="mtn-footer-contact">
			<?php if ( $mtn_phone ) : ?><p><span>تماس:</span> <a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $mtn_phone ) ); ?>" dir="ltr"><?php echo esc_html( $mtn_phone ); ?></a></p><?php endif; ?>
			<?php if ( $mtn_email ) : ?><p><span>ایمیل:</span> <a href="<?php echo esc_url( 'mailto:' . $mtn_email ); ?>" dir="ltr"><?php echo esc_html( $mtn_email ); ?></a></p><?php endif; ?>
			<?php if ( $mtn_address ) : ?><p><span>نشانی:</span> <?php echo esc_html( $mtn_address ); ?></p><?php endif; ?>
		</address>
		<?php endif; ?>
		<?php
		$mtn_social_links = array();
		foreach ( $mtn_socials as $mtn_key => $mtn_label ) {
			$mtn_url = esc_url( mtn_cafe_theme_option_value( $mtn_key ), array( 'http', 'https' ) );
			if ( $mtn_url ) {
				$mtn_social_links[] = '<li><a href="' . esc_url( $mtn_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $mtn_label ) . '</a></li>';
			}
		}
		if ( $mtn_social_links ) :
			?>
		<ul class="mtn-footer-social" aria-label="<?php esc_attr_e( 'شبکه‌های اجتماعی', 'mtn-cafe-theme' ); ?>"><?php echo implode( '', $mtn_social_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each link is escaped above. ?></ul>
		<?php endif; ?>
	</div>
	<?php
	$mtn_legal_pages = array( 'terms' => 'قوانین', 'privacy' => 'حریم خصوصی', 'cancellation-policy' => 'لغو و عدم حضور', 'booking-rules' => 'قوانین رزرو', 'about' => 'درباره ما', 'contact' => 'تماس با ما' );
	$mtn_legal_links = array();
	foreach ( $mtn_legal_pages as $mtn_slug => $mtn_label ) {
		$mtn_legal_page = get_page_by_path( $mtn_slug, OBJECT, 'page' );
		if ( $mtn_legal_page && 'publish' === $mtn_legal_page->post_status ) {
			$mtn_legal_links[] = '<li><a href="' . esc_url( get_permalink( $mtn_legal_page ) ) . '">' . esc_html( $mtn_label ) . '</a></li>';
		}
	}
	if ( $mtn_legal_links ) : ?>
	<div class="container"><ul class="mtn-footer-legal" aria-label="<?php esc_attr_e( 'صفحات راهنما و قوانین', 'mtn-cafe-theme' ); ?>"><?php echo implode( '', $mtn_legal_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each link is escaped above. ?></ul></div>
	<?php endif; ?>
	<div class="container mtn-footer-bottom">
		<p><?php echo esc_html( '' !== $mtn_copyright ? $mtn_copyright : sprintf( '© %1$s %2$s', wp_date( 'Y' ), $mtn_brand ) ); ?></p>
	</div>
</footer>
<?php if ( mtn_cafe_theme_option_value( 'mtn_cafe_theme_show_bottom_nav' ) ) :
	$mtn_tabs = array(
		array( 'label' => 'خانه', 'icon' => '⌂', 'url' => home_url( '/' ), 'active' => is_front_page() ),
		array( 'label' => 'کافه‌ها', 'icon' => '⌕', 'url' => home_url( '/cafes/' ), 'active' => is_post_type_archive( 'mtn_cafe' ) || is_singular( 'mtn_cafe' ) ),
		array( 'label' => 'رزرو', 'icon' => '▦', 'url' => home_url( '/book/' ), 'active' => is_page( 'book' ), 'primary' => true ),
		array( 'label' => 'برنامه‌ها', 'icon' => '♫', 'url' => home_url( '/events/' ), 'active' => is_post_type_archive( 'mtn_event' ) || is_singular( 'mtn_event' ) ),
		array( 'label' => is_user_logged_in() ? 'حساب من' : 'ورود', 'icon' => '◎', 'url' => is_user_logged_in() ? home_url( '/my-account/' ) : wp_login_url(), 'active' => is_page( array( 'my-account', 'login', 'mtn-login' ) ) ),
	);
	?>
<nav class="mtn-bottom-nav" aria-label="<?php esc_attr_e( 'ناوبری سریع', 'mtn-cafe-theme' ); ?>">
	<?php foreach ( $mtn_tabs as $mtn_tab ) : ?>
		<a class="mtn-tab<?php echo ! empty( $mtn_tab['primary'] ) ? ' is-primary' : ''; ?><?php echo $mtn_tab['active'] ? ' is-active' : ''; ?>" href="<?php echo esc_url( $mtn_tab['url'] ); ?>"<?php echo $mtn_tab['active'] ? ' aria-current="page"' : ''; ?>><span class="mtn-tab-icon" aria-hidden="true"><?php echo esc_html( $mtn_tab['icon'] ); ?></span><span class="mtn-tab-label"><?php echo esc_html( $mtn_tab['label'] ); ?></span></a>
	<?php endforeach; ?>
</nav>
<?php endif; ?>
<div class="mtn-toast-region" data-mtn-toasts role="status" aria-live="polite" aria-atomic="false"></div>
<?php $mtn_whatsapp_number = preg_replace( '/[^0-9]/', '', (string) get_option( 'mtn_cb_whatsapp_number', '' ) ); ?>
<?php if ( mtn_cafe_theme_option_value( 'mtn_cafe_theme_show_whatsapp' ) && strlen( $mtn_whatsapp_number ) >= 8 ) : ?>
	<a class="mtn-whatsapp-float" href="<?php echo esc_url( 'https://wa.me/' . $mtn_whatsapp_number . '?text=' . rawurlencode( 'سلام، برای رزرو کافه و برنامه‌های زنده راهنمایی می‌خواهم.' ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="گفت‌وگو با پشتیبانی در واتساپ"><span aria-hidden="true">◉</span><span>گفت‌وگو با ما</span></a>
<?php endif; ?>
  </div><!-- .mtn-app-main -->
</div><!-- .mtn-app-shell -->
<?php wp_footer(); ?>
</body>
</html>
