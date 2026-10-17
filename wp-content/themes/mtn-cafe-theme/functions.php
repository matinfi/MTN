<?php
/**
 * MTN Cafe theme setup.
 *
 * @package MTN_Cafe_Theme
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/theme-settings.php';

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
	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();
	$style_file = $theme_dir . '/style.css';
	$polish_file = $theme_dir . '/assets/ui-polish.css';
	$app_file = $theme_dir . '/assets/app-shell.js';
	wp_enqueue_style( 'mtn-cafe-style', get_stylesheet_uri(), array(), file_exists( $style_file ) ? (string) filemtime( $style_file ) : '0.3.1' );
	wp_enqueue_style( 'mtn-cafe-ui-polish', $theme_uri . '/assets/ui-polish.css', array( 'mtn-cafe-style' ), file_exists( $polish_file ) ? (string) filemtime( $polish_file ) : '0.3.1' );
	wp_enqueue_script( 'mtn-cafe-app', $theme_uri . '/assets/app-shell.js', array(), file_exists( $app_file ) ? (string) filemtime( $app_file ) : '0.3.1', true );
	if ( is_front_page() ) {
		$search_file = $theme_dir . '/assets/home-search.js';
		wp_enqueue_script( 'mtn-cafe-home-search', $theme_uri . '/assets/home-search.js', array(), file_exists( $search_file ) ? (string) filemtime( $search_file ) : '0.3.1', true );
		wp_localize_script( 'mtn-cafe-home-search', 'MTNHomeSearch', array( 'catalogUrl' => esc_url_raw( rest_url( 'mtn/v1/catalog' ) ) ) );
	}
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
