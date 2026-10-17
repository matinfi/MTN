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
	wp_enqueue_style( 'mtn-cafe-style', get_stylesheet_uri(), array(), '0.3.0' );
	$accent = sanitize_hex_color( get_option( 'mtn_cb_theme_accent', '#e7a94b' ) );
	$background = sanitize_hex_color( get_option( 'mtn_cb_theme_background', '#101014' ) );
	$font = get_option( 'mtn_cb_theme_font', 'Tahoma, Arial, sans-serif' );
	$allowed_fonts = array( 'Tahoma, Arial, sans-serif', 'Arial, sans-serif', 'system-ui, sans-serif' );
	if ( ! in_array( $font, $allowed_fonts, true ) ) $font = 'Tahoma, Arial, sans-serif';
	$density = 'compact' === get_option( 'mtn_cb_theme_density', 'comfortable' ) ? 'compact' : 'comfortable';
	$gap = 'compact' === $density ? '.65rem' : '1rem';
	$card_padding = 'compact' === $density ? '.85rem' : '1.25rem';
	$section_space = 'compact' === $density ? '2rem' : '3rem';
	$css = ':root{--mtn-accent:' . ( $accent ? $accent : '#e7a94b' ) . ';--mtn-bg:' . ( $background ? $background : '#101014' ) . ';--mtn-font:' . $font . ';--mtn-card-gap:' . $gap . ';--mtn-card-padding:' . $card_padding . ';--mtn-section-space:' . $section_space . '}body{font-family:var(--mtn-font)}.cards{gap:var(--mtn-card-gap)}.card{padding:var(--mtn-card-padding)}.section{padding:var(--mtn-section-space) 0}';
	wp_add_inline_style( 'mtn-cafe-style', $css );
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
