<?php
/**
 * Public artist profiles and moderated cooperation requests.
 *
 * @package MTN_Cafe_Booking
 */
defined( 'ABSPATH' ) || exit;

final class MTN_CB_Cooperation {
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_types' ) );
		add_shortcode( 'mtn_cooperation_form', array( __CLASS__, 'form' ) );
		add_action( 'admin_post_nopriv_mtn_cooperation_submit', array( __CLASS__, 'submit' ) );
		add_action( 'admin_post_mtn_cooperation_submit', array( __CLASS__, 'submit' ) );
		add_action( 'add_meta_boxes_mtn_artist', array( __CLASS__, 'artist_meta_box' ) );
		add_action( 'save_post_mtn_artist', array( __CLASS__, 'save_artist_meta' ) );
	}

	public static function register_types() {
		register_post_type( 'mtn_artist', array(
			'labels' => array( 'name' => 'خواننده‌ها و دی‌جی‌ها', 'singular_name' => 'هنرمند', 'add_new_item' => 'افزودن پروفایل هنرمند', 'edit_item' => 'ویرایش پروفایل هنرمند' ),
			'public' => true, 'has_archive' => true, 'rewrite' => array( 'slug' => 'artists' ),
			'menu_icon' => 'dashicons-format-audio', 'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'show_in_rest' => true, 'capability_type' => 'post', 'map_meta_cap' => true,
		) );
		register_post_type( 'mtn_cooperation_request', array(
			'labels' => array( 'name' => 'درخواست‌های همکاری', 'singular_name' => 'درخواست همکاری', 'edit_item' => 'بررسی درخواست همکاری' ),
			'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-email-alt',
			'supports' => array( 'title', 'editor', 'custom-fields' ), 'capability_type' => 'post', 'map_meta_cap' => true,
		) );
	}

	public static function artist_meta_box() {
		add_meta_box( 'mtn-artist-profile', 'اطلاعات پروفایل هنرمند', array( __CLASS__, 'render_artist_meta' ), 'mtn_artist', 'normal', 'high' );
	}

	public static function render_artist_meta( $post ) {
		wp_nonce_field( 'mtn_artist_meta_' . $post->ID, 'mtn_artist_meta_nonce' );
		$genre = get_post_meta( $post->ID, 'mtn_artist_genre', true );
		$kind = get_post_meta( $post->ID, 'mtn_artist_kind', true );
		?>
		<p><label for="mtn-artist-kind">نوع هنرمند</label><select id="mtn-artist-kind" name="mtn_artist_kind"><option value="singer" <?php selected( $kind, 'singer' ); ?>>خواننده</option><option value="dj" <?php selected( $kind, 'dj' ); ?>>دی‌جی</option><option value="band" <?php selected( $kind, 'band' ); ?>>گروه موسیقی</option></select></p>
		<p><label for="mtn-artist-genre">سبک موسیقی</label><input class="widefat" id="mtn-artist-genre" name="mtn_artist_genre" value="<?php echo esc_attr( $genre ); ?>" maxlength="120"></p>
		<p class="description">معرفی هنرمند را در محتوای اصلی بنویسید. فقط پروفایل‌های منتشرشده در آرشیو عمومی دیده می‌شوند.</p>
		<?php
	}

	public static function save_artist_meta( $post_id ) {
		if ( ! isset( $_POST['mtn_artist_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mtn_artist_meta_nonce'] ) ), 'mtn_artist_meta_' . $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		$kind = isset( $_POST['mtn_artist_kind'] ) ? sanitize_key( wp_unslash( $_POST['mtn_artist_kind'] ) ) : 'singer';
		update_post_meta( $post_id, 'mtn_artist_kind', in_array( $kind, array( 'singer', 'dj', 'band' ), true ) ? $kind : 'singer' );
		$genre = isset( $_POST['mtn_artist_genre'] ) ? sanitize_text_field( wp_unslash( $_POST['mtn_artist_genre'] ) ) : '';
		update_post_meta( $post_id, 'mtn_artist_genre', $genre );
	}

	public static function form() {
		$notice = isset( $_GET['cooperation'] ) ? sanitize_key( wp_unslash( $_GET['cooperation'] ) ) : '';
		ob_start();
		?>
		<section class="mtn-booking-panel mtn-content">
			<p class="eyebrow">همکاری با MTN</p><h2>درخواست همکاری</h2>
			<p class="muted">اگر صاحب کافه یا رستوران هستید یا به‌عنوان خواننده، دی‌جی یا گروه موسیقی فعالیت می‌کنید، درخواست خود را بفرستید. انتشار اطلاعات پس از بررسی مدیر انجام می‌شود.</p>
			<?php if ( 'received' === $notice ) : ?><p class="mtn-booking-status" role="status">درخواست شما ثبت شد و پس از بررسی پیگیری می‌شود.</p><?php elseif ( 'invalid' === $notice ) : ?><p class="mtn-booking-status" role="alert">اطلاعات کامل نیست یا ارسال بیش از حد مجاز بوده است. کمی بعد دوباره تلاش کنید.</p><?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mtn-booking-form">
				<input type="hidden" name="action" value="mtn_cooperation_submit"><?php wp_nonce_field( 'mtn_cooperation_submit', 'mtn_cooperation_nonce' ); ?>
				<p class="mtn-cooperation-trap" aria-hidden="true"><label>این قسمت را خالی بگذارید<input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
				<div class="mtn-form-grid">
					<p><label for="mtn-coop-type">نوع همکاری</label><select id="mtn-coop-type" name="request_type" required><option value="">انتخاب کنید</option><option value="cafe">کافه</option><option value="restaurant">رستوران</option><option value="singer">خواننده</option><option value="dj">دی‌جی</option><option value="band">گروه موسیقی</option></select></p>
					<p><label for="mtn-coop-name">نام / نام مجموعه</label><input id="mtn-coop-name" name="contact_name" required maxlength="120"></p>
					<p><label for="mtn-coop-phone">شماره تماس</label><input id="mtn-coop-phone" name="phone" type="tel" required maxlength="32" autocomplete="tel"></p>
					<p><label for="mtn-coop-email">ایمیل (اختیاری)</label><input id="mtn-coop-email" name="email" type="email" maxlength="190" autocomplete="email"></p>
				</div>
				<p><label for="mtn-coop-details">توضیحات</label><textarea id="mtn-coop-details" name="details" rows="5" required maxlength="3000"></textarea></p>
				<button class="button" type="submit">ارسال درخواست</button>
			</form>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function submit() {
		if ( ! isset( $_POST['mtn_cooperation_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mtn_cooperation_nonce'] ) ), 'mtn_cooperation_submit' ) ) {
			self::redirect( 'invalid' );
		}
		if ( ! empty( $_POST['website'] ) ) {
			self::redirect( 'received' );
		}
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$rate_key = 'mtn_coop_' . hash( 'sha256', $ip );
		if ( get_transient( $rate_key ) ) {
			self::redirect( 'invalid' );
		}
		$type = isset( $_POST['request_type'] ) ? sanitize_key( wp_unslash( $_POST['request_type'] ) ) : '';
		$types = array( 'cafe', 'restaurant', 'singer', 'dj', 'band' );
		$name = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$details = isset( $_POST['details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['details'] ) ) : '';
		if ( ! in_array( $type, $types, true ) || '' === $name || '' === $phone || '' === $details || ( '' !== $email && ! is_email( $email ) ) ) {
			self::redirect( 'invalid' );
		}
		$title = sprintf( '[%s] %s', $type, $name );
		$id = wp_insert_post( array( 'post_type' => 'mtn_cooperation_request', 'post_status' => 'private', 'post_title' => $title, 'post_content' => $details, 'meta_input' => array( 'mtn_cooperation_type' => $type, 'mtn_contact_name' => $name, 'mtn_contact_phone' => $phone, 'mtn_contact_email' => $email, 'mtn_request_status' => 'new' ) ), true );
		if ( is_wp_error( $id ) ) {
			self::redirect( 'invalid' );
		}
		set_transient( $rate_key, 1, 5 * MINUTE_IN_SECONDS );
		self::redirect( 'received' );
	}

	private static function redirect( $status ) {
		$referer = wp_get_referer();
		$target = $referer ? $referer : home_url( '/cooperation/' );
		wp_safe_redirect( add_query_arg( 'cooperation', sanitize_key( $status ), $target ) );
		exit;
	}
}
