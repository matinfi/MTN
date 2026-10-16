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
		$private_owner_meta = $common;
		$private_owner_meta['show_in_rest'] = false;
		register_post_meta( 'mtn_cafe', 'mtn_cafe_owner_id', $private_owner_meta );
		register_post_meta( 'mtn_table', 'mtn_cafe_id', $common );
		register_post_meta( 'mtn_table', 'mtn_table_capacity', $common );
		register_post_meta( 'mtn_table', 'mtn_table_active', array_merge( $common, array( 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean' ) ) );
	}
}
