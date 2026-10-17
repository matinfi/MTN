<?php
defined( 'ABSPATH' ) || exit;

final class MTN_CB_Applications {
	const TYPE = 'mtn_application';
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_type' ) );
		add_shortcode( 'mtn_partner_application', array( __CLASS__, 'form' ) );
		add_shortcode( 'mtn_my_applications', array( __CLASS__, 'my_applications' ) );
		add_action( 'admin_post_mtn_cb_submit_application', array( __CLASS__, 'submit' ) );
		add_action( 'admin_post_nopriv_mtn_cb_submit_application', array( __CLASS__, 'submit' ) );
		add_action( 'save_post_' . self::TYPE, array( __CLASS__, 'sync_review_status' ), 20, 3 );
		add_filter( 'manage_' . self::TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::TYPE . '_posts_custom_column', array( __CLASS__, 'column_value' ), 10, 2 );
	}
	public static function register_type() {
		register_post_type( self::TYPE, array(
			'labels' => array( 'name' => 'درخواست‌های همکاری', 'singular_name' => 'درخواست همکاری', 'menu_name' => 'درخواست‌های همکاری' ),
			'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'show_in_rest' => false,
			'supports' => array( 'title', 'editor', 'author' ), 'capability_type' => 'post', 'map_meta_cap' => true, 'menu_icon' => 'dashicons-groups',
		) );
	}
	public static function form() {
		$notice = isset( $_GET['mtn_application'] ) ? sanitize_key( wp_unslash( $_GET['mtn_application'] ) ) : '';
		ob_start(); ?>
		<section class="mtn-booking-panel"><h2>درخواست همکاری با MTN</h2>
		<p>درخواست‌های کافه، رستوران و هنرمند توسط مدیر سایت بررسی می‌شوند. ارسال فرم به معنی تأیید یا انتشار برنامه نیست. برای پیگیری در حساب کاربری، پیش از ثبت درخواست وارد شوید.</p>
		<?php if ( ! is_user_logged_in() ) : ?><p><a class="button" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">ورود برای ثبت و پیگیری درخواست</a></p><?php endif; ?>
		<?php if ( 'sent' === $notice ) : ?><p role="status">درخواست ثبت شد؛ وضعیت را در پنل کاربری پیگیری کنید.</p><?php elseif ( 'error' === $notice ) : ?><p role="alert">ثبت درخواست انجام نشد؛ اطلاعات را بررسی کنید.</p><?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mtn-form-grid">
		<input type="hidden" name="action" value="mtn_cb_submit_application"><?php wp_nonce_field( 'mtn_cb_submit_application', 'mtn_application_nonce' ); ?>
		<p><label for="mtn-app-type">نوع همکاری</label><select id="mtn-app-type" name="application_type" required><option value="cafe">کافه</option><option value="restaurant">رستوران</option><option value="artist">خواننده / هنرمند</option></select></p>
		<p><label for="mtn-app-name">نام شخص یا مجموعه</label><input id="mtn-app-name" name="applicant_name" maxlength="160" required></p>
		<p><label for="mtn-app-email">ایمیل تماس</label><input id="mtn-app-email" name="applicant_email" type="email" maxlength="190" required></p>
		<p><label for="mtn-app-phone">شماره تماس</label><input id="mtn-app-phone" name="applicant_phone" type="tel" maxlength="40" required></p>
		<p><label for="mtn-app-city">شهر</label><input id="mtn-app-city" name="applicant_city" maxlength="100" required></p>
		<p><label for="mtn-app-message">معرفی و توضیحات</label><textarea id="mtn-app-message" name="applicant_message" rows="4" maxlength="3000" required></textarea></p>
		<p><button type="submit" class="button button-primary">ارسال درخواست</button></p></form><?php echo do_shortcode( '[mtn_support_whatsapp]' ); ?></section>
		<?php return ob_get_clean();
	}
	public static function submit() {
		if ( ! is_user_logged_in() ) self::redirect( 'error' );
		if ( ! isset( $_POST['mtn_application_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mtn_application_nonce'] ) ), 'mtn_cb_submit_application' ) ) wp_die( esc_html__( 'درخواست امنیتی معتبر نیست.', 'mtn-cafe-booking' ), '', array( 'response' => 403 ) );
		$type = isset( $_POST['application_type'] ) ? sanitize_key( wp_unslash( $_POST['application_type'] ) ) : '';
		$name = isset( $_POST['applicant_name'] ) ? sanitize_text_field( wp_unslash( $_POST['applicant_name'] ) ) : '';
		$email = isset( $_POST['applicant_email'] ) ? sanitize_email( wp_unslash( $_POST['applicant_email'] ) ) : '';
		$phone = isset( $_POST['applicant_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['applicant_phone'] ) ) : '';
		$city = isset( $_POST['applicant_city'] ) ? sanitize_text_field( wp_unslash( $_POST['applicant_city'] ) ) : '';
		$message = isset( $_POST['applicant_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['applicant_message'] ) ) : '';
		if ( ! in_array( $type, array( 'cafe', 'restaurant', 'artist' ), true ) || '' === $name || ! is_email( $email ) || '' === $phone || '' === $city || '' === $message ) self::redirect( 'error' );
		$labels = array( 'cafe' => 'کافه', 'restaurant' => 'رستوران', 'artist' => 'هنرمند' );
		$id = wp_insert_post( array( 'post_type' => self::TYPE, 'post_status' => 'pending', 'post_title' => $labels[ $type ] . ' — ' . $name, 'post_content' => $message, 'post_author' => get_current_user_id() ), true );
		if ( is_wp_error( $id ) ) self::redirect( 'error' );
		update_post_meta( $id, '_mtn_application_type', $type ); update_post_meta( $id, '_mtn_applicant_name', $name );
		update_post_meta( $id, '_mtn_applicant_email', $email ); update_post_meta( $id, '_mtn_applicant_phone', $phone );
		update_post_meta( $id, '_mtn_applicant_city', $city ); update_post_meta( $id, '_mtn_application_review_status', 'pending_review' );
		self::redirect( 'sent' );
	}
	private static function redirect( $result ) {
		$target = wp_get_referer(); if ( ! $target ) $target = home_url( '/cooperation/' );
		wp_safe_redirect( add_query_arg( 'mtn_application', $result, $target ) ); exit;
	}
	public static function my_applications() {
		if ( ! is_user_logged_in() ) return '<section class="mtn-booking-panel"><p>برای پیگیری درخواست‌ها ابتدا وارد حساب شوید.</p><a class="button" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">ورود به حساب</a></section>';
		$items = get_posts( array( 'post_type' => self::TYPE, 'post_status' => array( 'pending', 'draft', 'publish', 'private' ), 'author' => get_current_user_id(), 'numberposts' => 50, 'orderby' => 'date', 'order' => 'DESC' ) );
		$labels = array( 'pending' => 'در انتظار بررسی مدیر', 'draft' => 'نیازمند تکمیل', 'publish' => 'پذیرفته‌شده', 'private' => 'بسته‌شده' );
		$out = '<section class="mtn-booking-panel"><h2>درخواست‌های همکاری من</h2>';
		if ( ! $items ) return $out . '<p>هنوز درخواستی ثبت نکرده‌اید.</p><a class="button" href="' . esc_url( home_url( '/cooperation/' ) ) . '">ثبت درخواست همکاری</a></section>';
		$out .= '<ul>'; foreach ( $items as $item ) $out .= '<li><strong>' . esc_html( get_the_title( $item ) ) . '</strong> — ' . esc_html( isset( $labels[ $item->post_status ] ) ? $labels[ $item->post_status ] : 'در انتظار بررسی مدیر' ) . ' — ' . esc_html( get_the_date( '', $item ) ) . '</li>';
		return $out . '</ul></section>';
	}
	public static function sync_review_status( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || self::TYPE !== $post->post_type ) return;
		$statuses = array( 'pending' => 'pending_review', 'draft' => 'needs_information', 'publish' => 'approved', 'private' => 'closed' );
		update_post_meta( $post_id, '_mtn_application_review_status', isset( $statuses[ $post->post_status ] ) ? $statuses[ $post->post_status ] : 'pending_review' );
	}

	public static function columns( $columns ) { $columns['mtn_applicant_type'] = 'نوع'; $columns['mtn_applicant_contact'] = 'اطلاعات تماس'; $columns['mtn_application_review'] = 'وضعیت بررسی'; return $columns; }
	public static function column_value( $column, $post_id ) {
		if ( 'mtn_applicant_type' === $column ) { $types = array( 'cafe' => 'کافه', 'restaurant' => 'رستوران', 'artist' => 'هنرمند' ); $type = get_post_meta( $post_id, '_mtn_application_type', true ); echo esc_html( isset( $types[ $type ] ) ? $types[ $type ] : '—' ); }
		elseif ( 'mtn_applicant_contact' === $column ) echo esc_html( get_post_meta( $post_id, '_mtn_applicant_name', true ) . ' · ' . get_post_meta( $post_id, '_mtn_applicant_email', true ) . ' · ' . get_post_meta( $post_id, '_mtn_applicant_phone', true ) );
		elseif ( 'mtn_application_review' === $column ) echo esc_html( get_post_meta( $post_id, '_mtn_application_review_status', true ) ?: 'pending_review' );
	}
}
