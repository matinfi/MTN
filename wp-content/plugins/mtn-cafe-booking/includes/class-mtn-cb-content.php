<?php
/**
 * Public cafe and event content types.
 *
 * @package MTN_Cafe_Booking
 */

defined( 'ABSPATH' ) || exit;

final class MTN_CB_Content {
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_types' ) );
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_mtn_cafe', array( __CLASS__, 'save_cafe_meta' ) );
		add_action( 'save_post_mtn_event', array( __CLASS__, 'save_event_meta' ) );
		add_action( 'save_post_mtn_table', array( __CLASS__, 'save_table_meta' ) );
	}

	public static function register_types() {
		register_post_type(
			'mtn_cafe',
			array(
				'labels' => array( 'name' => __( 'کافه‌ها', 'mtn-cafe-booking' ), 'singular_name' => __( 'کافه', 'mtn-cafe-booking' ), 'add_new_item' => __( 'افزودن کافه', 'mtn-cafe-booking' ) ),
				'public' => true,
				'has_archive' => true,
				'show_in_rest' => true,
				'menu_icon' => 'dashicons-store',
				'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'rewrite' => array( 'slug' => 'cafes' ),
			)
		);
		register_post_type(
			'mtn_event',
			array(
				'labels' => array( 'name' => __( 'رویدادها و برنامه‌ها', 'mtn-cafe-booking' ), 'singular_name' => __( 'رویداد', 'mtn-cafe-booking' ), 'add_new_item' => __( 'افزودن رویداد', 'mtn-cafe-booking' ) ),
				'public' => true,
				'has_archive' => true,
				'show_in_rest' => true,
				'menu_icon' => 'dashicons-calendar-alt',
				'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'rewrite' => array( 'slug' => 'events' ),
			)
		);
		register_post_type(
			'mtn_table',
			array(
				'labels' => array( 'name' => __( 'میزها', 'mtn-cafe-booking' ), 'singular_name' => __( 'میز', 'mtn-cafe-booking' ) ),
				'public' => false,
				'show_ui' => true,
				'show_in_rest' => false,
				'menu_icon' => 'dashicons-screenoptions',
				'supports' => array( 'title' ),
			)
		);
	}

	public static function register_meta_boxes() {
		add_meta_box( 'mtn-cafe-details', __( 'اطلاعات کافه', 'mtn-cafe-booking' ), array( __CLASS__, 'render_cafe_meta_box' ), 'mtn_cafe', 'normal', 'high' );
		add_meta_box( 'mtn-event-details', __( 'جزئیات رویداد و ظرفیت', 'mtn-cafe-booking' ), array( __CLASS__, 'render_event_meta_box' ), 'mtn_event', 'normal', 'high' );
		add_meta_box( 'mtn-table-details', __( 'مشخصات میز', 'mtn-cafe-booking' ), array( __CLASS__, 'render_table_meta_box' ), 'mtn_table', 'normal', 'high' );
	}

	private static function field( $post_id, $key, $label, $type = 'text', $help = '' ) {
		$value = get_post_meta( $post_id, $key, true );
		echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';
		if ( 'textarea' === $type ) {
			echo '<textarea class="widefat" rows="3" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( $value ) . '</textarea>';
		} elseif ( 'checkbox' === $type ) {
			echo '<input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="1" ' . checked( (bool) $value, true, false ) . '> ' . esc_html__( 'فعال', 'mtn-cafe-booking' );
		} else {
			$input_type = in_array( $type, array( 'number', 'datetime-local', 'date', 'time', 'tel', 'url' ), true ) ? $type : 'text';
			$step = 'number' === $input_type ? ' step="1" min="0"' : '';
			echo '<input class="widefat" type="' . esc_attr( $input_type ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '"' . $step . '>';
		}
		if ( $help ) {
			echo '<span class="description">' . esc_html( $help ) . '</span>';
		}
		echo '</p>';
	}

	private static function meta_box_nonce() {
		wp_nonce_field( 'mtn_cb_save_listing', 'mtn_cb_listing_nonce' );
	}

	public static function render_cafe_meta_box( $post ) {
		self::meta_box_nonce();
		self::field( $post->ID, 'mtn_cafe_owner_id', __( 'شناسه کاربری مدیر کافه (اختیاری)', 'mtn-cafe-booking' ), 'number' );
		self::field( $post->ID, 'mtn_cafe_city', __( 'شهر', 'mtn-cafe-booking' ) );
		self::field( $post->ID, 'mtn_cafe_address', __( 'نشانی', 'mtn-cafe-booking' ), 'textarea' );
		self::field( $post->ID, 'mtn_cafe_phone', __( 'تلفن تماس', 'mtn-cafe-booking' ), 'tel' );
		self::field( $post->ID, 'mtn_cafe_whatsapp', __( 'شماره واتساپ با کد کشور', 'mtn-cafe-booking' ), 'tel' );
		self::field( $post->ID, 'mtn_cafe_music_genre', __( 'سبک موسیقی', 'mtn-cafe-booking' ) );
	}

	public static function render_event_meta_box( $post ) {
		self::meta_box_nonce();
		self::field( $post->ID, 'mtn_cafe_id', __( 'شناسه کافه منتشرشده', 'mtn-cafe-booking' ), 'number', __( 'شناسه نوشته کافه‌ای که این رویداد در آن برگزار می‌شود.', 'mtn-cafe-booking' ) );
		self::field( $post->ID, 'mtn_event_capacity', __( 'ظرفیت کل نفرات (۰ = بدون سقف تنظیم‌شده)', 'mtn-cafe-booking' ), 'number' );
		self::field( $post->ID, 'mtn_min_party_size', __( 'حداقل نفرات هر رزرو', 'mtn-cafe-booking' ), 'number' );
		self::field( $post->ID, 'mtn_max_party_size', __( 'حداکثر نفرات هر رزرو', 'mtn-cafe-booking' ), 'number' );
		self::field( $post->ID, 'mtn_event_date', __( 'تاریخ برنامه', 'mtn-cafe-booking' ), 'date' );
		self::field( $post->ID, 'mtn_event_start_time', __( 'ساعت شروع', 'mtn-cafe-booking' ), 'time' );
		self::field( $post->ID, 'mtn_event_end_time', __( 'ساعت پایان', 'mtn-cafe-booking' ), 'time' );
	}

	public static function render_table_meta_box( $post ) {
		self::meta_box_nonce();
		self::field( $post->ID, 'mtn_cafe_id', __( 'شناسه کافه', 'mtn-cafe-booking' ), 'number' );
		self::field( $post->ID, 'mtn_table_capacity', __( 'ظرفیت میز (نفر)', 'mtn-cafe-booking' ), 'number' );
		self::field( $post->ID, 'mtn_table_active', __( 'میز قابل رزرو است؟', 'mtn-cafe-booking' ), 'checkbox' );
	}

	private static function save_fields( $post_id, $keys, $numeric = array(), $checkboxes = array() ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['mtn_cb_listing_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mtn_cb_listing_nonce'] ) ), 'mtn_cb_save_listing' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( $keys as $key ) {
			if ( in_array( $key, $checkboxes, true ) ) {
				update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) ? 1 : 0 );
			} elseif ( isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ) {
				$value = wp_unslash( $_POST[ $key ] );
				if ( in_array( $key, $numeric, true ) ) {
					update_post_meta( $post_id, $key, absint( $value ) );
				} else {
					update_post_meta( $post_id, $key, sanitize_text_field( $value ) );
				}
			}
		}
	}

	public static function save_cafe_meta( $post_id ) {
		self::save_fields( $post_id, array( 'mtn_cafe_owner_id', 'mtn_cafe_city', 'mtn_cafe_address', 'mtn_cafe_phone', 'mtn_cafe_whatsapp', 'mtn_cafe_music_genre' ), array( 'mtn_cafe_owner_id' ) );
	}

	public static function save_event_meta( $post_id ) {
		self::save_fields( $post_id, array( 'mtn_cafe_id', 'mtn_event_capacity', 'mtn_min_party_size', 'mtn_max_party_size', 'mtn_event_date', 'mtn_event_start_time', 'mtn_event_end_time' ), array( 'mtn_cafe_id', 'mtn_event_capacity', 'mtn_min_party_size', 'mtn_max_party_size' ) );
	}

	public static function save_table_meta( $post_id ) {
		self::save_fields( $post_id, array( 'mtn_cafe_id', 'mtn_table_capacity', 'mtn_table_active' ), array( 'mtn_cafe_id', 'mtn_table_capacity' ), array( 'mtn_table_active' ) );
	}

	public static function register_meta() {
		$common = array(
			'single' => true,
			'type' => 'integer',
			'sanitize_callback' => 'absint',
			'auth_callback' => static function () { return current_user_can( 'edit_posts' ); },
			'show_in_rest' => true,
		);
		register_post_meta( 'mtn_event', 'mtn_cafe_id', $common );
		register_post_meta( 'mtn_event', 'mtn_event_capacity', $common );
		register_post_meta( 'mtn_event', 'mtn_min_party_size', $common );
		register_post_meta( 'mtn_event', 'mtn_max_party_size', $common );
		foreach ( array( 'mtn_cafe_city', 'mtn_cafe_address', 'mtn_cafe_phone', 'mtn_cafe_whatsapp', 'mtn_cafe_music_genre' ) as $text_meta ) {
			register_post_meta( 'mtn_cafe', $text_meta, array( 'single' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'show_in_rest' => true, 'auth_callback' => static function () { return current_user_can( 'edit_posts' ); } ) );
		}
		foreach ( array( 'mtn_event_date', 'mtn_event_start_time', 'mtn_event_end_time' ) as $text_meta ) {
			register_post_meta( 'mtn_event', $text_meta, array( 'single' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'show_in_rest' => true, 'auth_callback' => static function () { return current_user_can( 'edit_posts' ); } ) );
		}
		$private_owner_meta = $common;
		$private_owner_meta['show_in_rest'] = false;
		register_post_meta( 'mtn_cafe', 'mtn_cafe_owner_id', $private_owner_meta );
		register_post_meta( 'mtn_table', 'mtn_cafe_id', $common );
		register_post_meta( 'mtn_table', 'mtn_table_capacity', $common );
		register_post_meta( 'mtn_table', 'mtn_table_active', array_merge( $common, array( 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean' ) ) );
	}
}
