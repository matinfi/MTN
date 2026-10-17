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
		register_taxonomy(
			'mtn_cafe_location',
			array( 'mtn_cafe' ),
			array(
				'labels' => array(
					'name' => __( 'موقعیت جغرافیایی', 'mtn-cafe-booking' ),
					'singular_name' => __( 'موقعیت', 'mtn-cafe-booking' ),
					'parent_item' => __( 'موقعیت والد', 'mtn-cafe-booking' ),
					'parent_item_colon' => __( 'موقعیت والد:', 'mtn-cafe-booking' ),
					'add_new_item' => __( 'افزودن موقعیت', 'mtn-cafe-booking' ),
					'new_item_name' => __( 'نام موقعیت', 'mtn-cafe-booking' ),
				),
				'public' => true,
				'hierarchical' => true,
				'show_ui' => true,
				'show_admin_column' => true,
				'show_in_rest' => true,
				'rewrite' => array( 'slug' => 'cafe-location' ),
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
			'mtn_artist',
			array(
				'labels' => array( 'name' => __( 'هنرمندان', 'mtn-cafe-booking' ), 'singular_name' => __( 'هنرمند', 'mtn-cafe-booking' ), 'add_new_item' => __( 'افزودن هنرمند', 'mtn-cafe-booking' ) ),
				'public' => true, 'has_archive' => true, 'show_in_rest' => true,
				'menu_icon' => 'dashicons-microphone',
				'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'rewrite' => array( 'slug' => 'artists' ),
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
		} elseif ( 'cafe_select' === $type ) {
			$cafes = get_posts( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
			echo '<select class="widefat" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '"><option value="0">' . esc_html__( 'انتخاب کافه', 'mtn-cafe-booking' ) . '</option>';
			foreach ( $cafes as $cafe ) {
				echo '<option value="' . (int) $cafe->ID . '" ' . selected( absint( $value ), (int) $cafe->ID, false ) . '>' . esc_html( get_the_title( $cafe ) ) . ' (#' . (int) $cafe->ID . ')</option>';
			}
			echo '</select>';
		} elseif ( 'user_multi_select' === $type ) {
			$selected_ids = is_array( $value ) ? array_map( 'absint', $value ) : array();
			$users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC', 'fields' => array( 'ID', 'display_name' ), 'number' => 500 ) );
			echo '<select class="widefat" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '[]" multiple size="8">';
			foreach ( $users as $user ) {
				echo '<option value="' . (int) $user->ID . '" ' . selected( in_array( (int) $user->ID, $selected_ids, true ), true, false ) . '>' . esc_html( $user->display_name ) . ' (#' . (int) $user->ID . ')</option>';
			}
			echo '</select><span class="description">' . esc_html__( 'برای انتخاب چند نفر، کلید Ctrl یا Command را نگه دارید.', 'mtn-cafe-booking' ) . '</span>';
		} elseif ( 'user_select' === $type ) {
			$users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC', 'fields' => array( 'ID', 'display_name' ), 'number' => 500 ) );
			echo '<select class="widefat" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '"><option value="0">' . esc_html__( 'بدون مالک مشخص', 'mtn-cafe-booking' ) . '</option>';
			foreach ( $users as $user ) {
				echo '<option value="' . (int) $user->ID . '" ' . selected( absint( $value ), (int) $user->ID, false ) . '>' . esc_html( $user->display_name ) . ' (#' . (int) $user->ID . ')</option>';
			}
			echo '</select>';
		} elseif ( 'checkbox' === $type ) {
			echo '<input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="1" ' . checked( (bool) $value, true, false ) . '> ' . esc_html__( 'فعال', 'mtn-cafe-booking' );
		} else {
			$input_type = in_array( $type, array( 'number', 'decimal', 'datetime-local', 'date', 'time', 'tel', 'url', 'email' ), true ) ? $type : 'text';
			$step = 'number' === $input_type ? ' step="1" min="0"' : ( 'decimal' === $input_type ? ' step="0.01" min="0"' : '' );
			if ( 'decimal' === $input_type ) { $input_type = 'number'; }
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
		if ( current_user_can( 'manage_options' ) ) {
			self::field( $post->ID, 'mtn_cafe_owner_id', __( 'مالک کافه', 'mtn-cafe-booking' ), 'user_select' );
			self::field( $post->ID, 'mtn_cafe_manager_ids', __( 'مدیران و کارکنان مجاز کافه', 'mtn-cafe-booking' ), 'user_multi_select' );
		} else {
			$owner_id = absint( get_post_meta( $post->ID, 'mtn_cafe_owner_id', true ) );
			$owner = $owner_id ? get_user_by( 'id', $owner_id ) : false;
			echo '<p><strong>' . esc_html__( 'مالک کافه', 'mtn-cafe-booking' ) . ':</strong> ' . esc_html( $owner ? $owner->display_name : __( 'بدون مالک مشخص', 'mtn-cafe-booking' ) ) . '</p>';
		}
		self::field( $post->ID, 'mtn_cafe_city', __( 'شهر', 'mtn-cafe-booking' ) );
		self::field( $post->ID, 'mtn_cafe_address', __( 'نشانی', 'mtn-cafe-booking' ), 'textarea' );
		self::field( $post->ID, 'mtn_cafe_phone', __( 'تلفن تماس', 'mtn-cafe-booking' ), 'tel' );
		self::field( $post->ID, 'mtn_cafe_email', __( 'ایمیل دریافت صورت‌حساب', 'mtn-cafe-booking' ), 'email' );
		self::field( $post->ID, 'mtn_cafe_whatsapp', __( 'شماره واتساپ با کد کشور', 'mtn-cafe-booking' ), 'tel' );
		self::field( $post->ID, 'mtn_cafe_music_genre', __( 'سبک موسیقی', 'mtn-cafe-booking' ) );
		self::field( $post->ID, 'mtn_cafe_type', __( 'نوع مکان (کافه/رستوران)', 'mtn-cafe-booking' ) );
		self::field( $post->ID, 'mtn_cafe_latitude', __( 'عرض جغرافیایی (Latitude)', 'mtn-cafe-booking' ), 'text', 'برای نمایش فاصله و نقشه، مقدار اعشاری معتبر وارد کنید.' );
		self::field( $post->ID, 'mtn_cafe_longitude', __( 'طول جغرافیایی (Longitude)', 'mtn-cafe-booking' ), 'text', 'برای نمایش فاصله و نقشه، مقدار اعشاری معتبر وارد کنید.' );
	}

	public static function render_event_meta_box( $post ) {
		self::meta_box_nonce();
		self::field( $post->ID, 'mtn_cafe_id', __( 'کافه میزبان', 'mtn-cafe-booking' ), 'cafe_select' );
		self::field( $post->ID, 'mtn_event_capacity', __( 'ظرفیت کل نفرات (۰ = بدون سقف تنظیم‌شده)', 'mtn-cafe-booking' ), 'number' );
		self::field( $post->ID, 'mtn_min_party_size', __( 'حداقل نفرات هر رزرو', 'mtn-cafe-booking' ), 'number' );
		self::field( $post->ID, 'mtn_max_party_size', __( 'حداکثر نفرات هر رزرو', 'mtn-cafe-booking' ), 'number' );
		self::field( $post->ID, 'mtn_event_date', __( 'تاریخ برنامه', 'mtn-cafe-booking' ), 'date' );
		self::field( $post->ID, 'mtn_event_start_time', __( 'ساعت شروع', 'mtn-cafe-booking' ), 'time' );
		self::field( $post->ID, 'mtn_event_end_time', __( 'ساعت پایان', 'mtn-cafe-booking' ), 'time' );
		self::field( $post->ID, 'mtn_event_artist', __( 'خواننده / هنرمند', 'mtn-cafe-booking' ) );
		self::field( $post->ID, 'mtn_event_price', __( 'قیمت ورودی هر نفر (لیر؛ ۰ یعنی رایگان/نامشخص)', 'mtn-cafe-booking' ), 'decimal' );
		$recurrence = get_post_meta( $post->ID, 'mtn_event_recurrence', true );
		if ( ! in_array( $recurrence, array( 'weekly', 'monthly' ), true ) ) {
			$recurrence = 'none';
		}
		echo '<p><label for="mtn_event_recurrence"><strong>' . esc_html__( 'تکرار رویداد', 'mtn-cafe-booking' ) . '</strong></label><br><select class="widefat" id="mtn_event_recurrence" name="mtn_event_recurrence">';
		foreach ( array( 'none' => __( 'بدون تکرار', 'mtn-cafe-booking' ), 'weekly' => __( 'هفتگی', 'mtn-cafe-booking' ), 'monthly' => __( 'ماهانه', 'mtn-cafe-booking' ) ) as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $recurrence, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select><span class="description">' . esc_html__( 'رخدادهای بعدی به‌صورت پیش‌نویس ساخته می‌شوند تا مدیر تاریخ و محتوا را بازبینی و منتشر کند.', 'mtn-cafe-booking' ) . '</span></p>';
		self::field( $post->ID, 'mtn_event_recurrence_until', __( 'تاریخ پایان تکرار', 'mtn-cafe-booking' ), 'date' );
	}

	public static function render_table_meta_box( $post ) {
		self::meta_box_nonce();
		self::field( $post->ID, 'mtn_cafe_id', __( 'کافه میزبان', 'mtn-cafe-booking' ), 'cafe_select' );
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
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! isset( $_POST['mtn_cb_listing_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mtn_cb_listing_nonce'] ) ), 'mtn_cb_save_listing' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		// Only administrators may assign cafe ownership. Editors who can edit
		// public cafe content must not be able to grant users manager access.
		$keys = array( 'mtn_cafe_city', 'mtn_cafe_address', 'mtn_cafe_phone', 'mtn_cafe_email', 'mtn_cafe_whatsapp', 'mtn_cafe_music_genre', 'mtn_cafe_type' );
		if ( current_user_can( 'manage_options' ) ) {
			$keys[] = 'mtn_cafe_owner_id';
		}
		self::save_fields( $post_id, $keys, array( 'mtn_cafe_owner_id' ) );
		if ( isset( $_POST['mtn_cb_listing_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mtn_cb_listing_nonce'] ) ), 'mtn_cb_save_listing' ) && current_user_can( 'edit_post', $post_id ) && ! ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) && ! wp_is_post_revision( $post_id ) ) {
			foreach ( array( 'mtn_cafe_latitude' => array( -90, 90 ), 'mtn_cafe_longitude' => array( -180, 180 ) ) as $coordinate => $bounds ) {
				if ( isset( $_POST[ $coordinate ] ) && is_scalar( $_POST[ $coordinate ] ) ) {
					$value = trim( (string) wp_unslash( $_POST[ $coordinate ] ) );
					$valid = is_numeric( $value ) && (float) $value >= $bounds[0] && (float) $value <= $bounds[1];
					update_post_meta( $post_id, $coordinate, $valid ? (string) (float) $value : '' );
				}
			}
		}
		if ( current_user_can( 'manage_options' ) ) {
			$raw_manager_ids = isset( $_POST['mtn_cafe_manager_ids'] ) && is_array( $_POST['mtn_cafe_manager_ids'] ) ? wp_unslash( $_POST['mtn_cafe_manager_ids'] ) : array();
			$manager_ids = array_values( array_unique( array_filter( array_map( 'absint', $raw_manager_ids ) ) ) );
			update_post_meta( $post_id, 'mtn_cafe_manager_ids', $manager_ids );
			$owner_id = absint( get_post_meta( $post_id, 'mtn_cafe_owner_id', true ) );
			$assigned_ids = array_unique( array_merge( $manager_ids, $owner_id ? array( $owner_id ) : array() ) );
			foreach ( $assigned_ids as $assigned_id ) {
				$assigned_user = get_user_by( 'id', $assigned_id );
				if ( $assigned_user && ! in_array( 'administrator', (array) $assigned_user->roles, true ) ) {
					$assigned_user->add_role( 'mtn_cafe_manager' );
				}
			}
		}
	}

	public static function save_event_meta( $post_id ) {
		self::save_fields( $post_id, array( 'mtn_cafe_id', 'mtn_event_capacity', 'mtn_min_party_size', 'mtn_max_party_size', 'mtn_event_date', 'mtn_event_start_time', 'mtn_event_end_time', 'mtn_event_recurrence_until', 'mtn_event_artist' ), array( 'mtn_cafe_id', 'mtn_event_capacity', 'mtn_min_party_size', 'mtn_max_party_size' ) );
		if ( isset( $_POST['mtn_event_price'] ) && is_scalar( $_POST['mtn_event_price'] ) && current_user_can( 'edit_post', $post_id ) && isset( $_POST['mtn_cb_listing_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mtn_cb_listing_nonce'] ) ), 'mtn_cb_save_listing' ) && ! ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) && ! wp_is_post_revision( $post_id ) ) {
			$price = sanitize_text_field( wp_unslash( $_POST['mtn_event_price'] ) );
			update_post_meta( $post_id, 'mtn_event_price', is_numeric( $price ) && (float) $price >= 0 ? number_format( min( 999999999.99, (float) $price ), 2, '.', '' ) : '0.00' );
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! isset( $_POST['mtn_cb_listing_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mtn_cb_listing_nonce'] ) ), 'mtn_cb_save_listing' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$recurrence = isset( $_POST['mtn_event_recurrence'] ) && is_scalar( $_POST['mtn_event_recurrence'] ) ? sanitize_key( wp_unslash( $_POST['mtn_event_recurrence'] ) ) : 'none';
		if ( ! in_array( $recurrence, array( 'none', 'weekly', 'monthly' ), true ) ) {
			$recurrence = 'none';
		}
		update_post_meta( $post_id, 'mtn_event_recurrence', $recurrence );
	}

	public static function save_table_meta( $post_id ) {
		self::save_fields( $post_id, array( 'mtn_cafe_id', 'mtn_table_capacity', 'mtn_table_active' ), array( 'mtn_cafe_id', 'mtn_table_capacity' ), array( 'mtn_table_active' ) );
	}

	public static function user_can_manage_cafe( $cafe_id, $user_id ) {
		$cafe_id = absint( $cafe_id );
		$user_id = absint( $user_id );
		if ( ! $cafe_id || ! $user_id ) {
			return false;
		}
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}
		if ( absint( get_post_meta( $cafe_id, 'mtn_cafe_owner_id', true ) ) === $user_id ) {
			return true;
		}
		$manager_ids = get_post_meta( $cafe_id, 'mtn_cafe_manager_ids', true );
		return is_array( $manager_ids ) && in_array( $user_id, array_map( 'absint', $manager_ids ), true );
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
		foreach ( array( 'mtn_cafe_city', 'mtn_cafe_address', 'mtn_cafe_phone', 'mtn_cafe_whatsapp', 'mtn_cafe_music_genre', 'mtn_cafe_type', 'mtn_cafe_latitude', 'mtn_cafe_longitude' ) as $text_meta ) {
			register_post_meta( 'mtn_cafe', $text_meta, array( 'single' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'show_in_rest' => true, 'auth_callback' => static function () { return current_user_can( 'edit_posts' ); } ) );
		}
		foreach ( array( 'mtn_event_date', 'mtn_event_start_time', 'mtn_event_end_time', 'mtn_event_artist', 'mtn_event_price' ) as $text_meta ) {
			register_post_meta( 'mtn_event', $text_meta, array( 'single' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'show_in_rest' => true, 'auth_callback' => static function () { return current_user_can( 'edit_posts' ); } ) );
		}
		$private_owner_meta = $common;
		$private_owner_meta['show_in_rest'] = false;
		register_post_meta( 'mtn_cafe', 'mtn_cafe_owner_id', $private_owner_meta );
		register_post_meta( 'mtn_cafe', 'mtn_cafe_manager_ids', array( 'single' => true, 'type' => 'array', 'sanitize_callback' => static function ( $value ) { return is_array( $value ) ? array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) ) : array(); }, 'show_in_rest' => false, 'auth_callback' => static function () { return current_user_can( 'manage_options' ); } ) );
		register_post_meta( 'mtn_table', 'mtn_cafe_id', $common );
		register_post_meta( 'mtn_table', 'mtn_table_capacity', $common );
		register_post_meta( 'mtn_table', 'mtn_table_active', array_merge( $common, array( 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean' ) ) );
	}
}
