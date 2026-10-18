<?php
/**
 * MTN Cafe theme setup.
 *
 * @package MTN_Cafe_Theme
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/theme-settings.php';
require_once get_template_directory() . '/inc/pwa.php';

function mtn_cafe_theme_setup() {
	load_theme_textdomain( 'mtn-cafe-theme', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 120, 'width' => 320, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	register_nav_menus( array( 'primary' => __( 'منوی اصلی', 'mtn-cafe-theme' ) ) );
}
add_action( 'after_setup_theme', 'mtn_cafe_theme_setup' );

function mtn_cafe_enqueue_assets() {
	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();
	$style_file = $theme_dir . '/style.css';
	$polish_file = $theme_dir . '/assets/ui-polish.css';
	$app_file = $theme_dir . '/assets/app-shell.js';
	wp_enqueue_style( 'mtn-cafe-style', get_stylesheet_uri(), array(), file_exists( $style_file ) ? (string) filemtime( $style_file ) : '0.10.0' );
	wp_enqueue_style( 'mtn-cafe-ui-polish', $theme_uri . '/assets/ui-polish.css', array( 'mtn-cafe-style' ), file_exists( $polish_file ) ? (string) filemtime( $polish_file ) : '0.10.0' );
	wp_enqueue_script( 'mtn-cafe-app', $theme_uri . '/assets/app-shell.js', array(), file_exists( $app_file ) ? (string) filemtime( $app_file ) : '0.10.0', true );
	wp_localize_script( 'mtn-cafe-app', 'MTNApp', array(
		'pwa'      => mtn_cafe_pwa_enabled(),
		'swUrl'    => esc_url_raw( mtn_cafe_pwa_url( 'sw' ) ),
		'scope'    => esc_url_raw( home_url( '/' ) ),
		'messages' => array(
			'offline'   => 'اتصال اینترنت قطع شد.',
			'online'    => 'اتصال برقرار شد.',
			'installed' => 'برنامه روی دستگاه شما نصب شد.',
		),
	) );
	if ( is_front_page() ) {
		$search_file = $theme_dir . '/assets/home-search.js';
		wp_enqueue_script( 'mtn-cafe-home-search', $theme_uri . '/assets/home-search.js', array(), file_exists( $search_file ) ? (string) filemtime( $search_file ) : '0.10.0', true );
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

/**
 * Preload the Persian font so text does not reflow after first paint.
 */
function mtn_cafe_preload_font() {
	if ( 'vazirmatn' !== mtn_cafe_theme_option_value( 'mtn_cafe_theme_font' ) ) {
		return;
	}
	printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_template_directory_uri() . '/assets/fonts/vazirmatn-arabic-wght-normal.woff2' ) );
}
add_action( 'wp_head', 'mtn_cafe_preload_font', 2 );

/**
 * Style WordPress menu links like the built-in sidebar links.
 *
 * @param array   $atts Link attributes.
 * @param WP_Post $item Menu item.
 * @return array
 */
function mtn_cafe_menu_link_attributes( $atts, $item ) {
	$classes = isset( $atts['class'] ) ? $atts['class'] . ' ' : '';
	$classes .= 'mtn-nav-link';
	if ( ! empty( $item->current ) || in_array( 'current-menu-item', (array) $item->classes, true ) ) {
		$classes .= ' is-active';
		$atts['aria-current'] = 'page';
	}
	$atts['class'] = $classes;
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'mtn_cafe_menu_link_attributes', 10, 2 );

/**
 * Keep the content width sensible for embeds and blocks.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 850;
}
