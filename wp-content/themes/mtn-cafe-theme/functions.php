<?php
/**
 * MTN Cafe theme setup.
 *
 * @package MTN_Cafe_Theme
 */

defined( 'ABSPATH' ) || exit;

function mtn_cafe_theme_setup() {
	load_theme_textdomain( 'mtn-cafe-theme', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 120, 'width' => 320, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	register_nav_menus( array( 'primary' => __( 'منوی اصلی', 'mtn-cafe-theme' ) ) );
}
add_action( 'after_setup_theme', 'mtn_cafe_theme_setup' );

function mtn_cafe_enqueue_assets() {
	wp_enqueue_style( 'mtn-cafe-style', get_stylesheet_uri(), array(), '0.2.0' );
}
add_action( 'wp_enqueue_scripts', 'mtn_cafe_enqueue_assets' );

function mtn_cafe_event_archive_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'mtn_event' ) ) {
		return;
	}
	$query->set( 'meta_key', 'mtn_event_date' );
	$query->set( 'orderby', 'meta_value' );
	$query->set( 'order', 'ASC' );
	$query->set( 'meta_query', array(
		array(
			'key' => 'mtn_event_date',
			'value' => current_time( 'Y-m-d' ),
			'compare' => '>=',
			'type' => 'DATE',
		),
		array(
			'key' => 'mtn_event_start_time',
			'value' => '',
			'compare' => '!=',
		),
	) );
}
add_action( 'pre_get_posts', 'mtn_cafe_event_archive_query' );
