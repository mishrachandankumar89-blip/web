<?php
/**
 * Theme supports, menus, assets.
 *
 * @package avenza
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register theme features.
 */
function avenza_setup() {
	load_theme_textdomain( 'avenza', AVENZA_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 260,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_image_size( 'avenza-card', 800, 600, true );
	add_image_size( 'avenza-wide', 1600, 900, true );

	register_nav_menus(
		array(
			'primary' => __( 'Primary menu', 'avenza' ),
			'footer'  => __( 'Footer company menu', 'avenza' ),
			'legal'   => __( 'Footer legal menu', 'avenza' ),
		)
	);

	add_editor_style( 'assets/css/editor.css' );
}
add_action( 'after_setup_theme', 'avenza_setup' );

/**
 * Content width for embeds.
 */
function avenza_content_width() {
	$GLOBALS['content_width'] = 760;
}
add_action( 'after_setup_theme', 'avenza_content_width', 0 );

/**
 * Styles and scripts.
 */
function avenza_assets() {
	$ver = AVENZA_VERSION;
	wp_enqueue_style( 'avenza-main', AVENZA_URI . '/assets/css/main.css', array(), $ver );
	wp_enqueue_script( 'avenza-theme', AVENZA_URI . '/assets/js/theme.js', array(), $ver, array( 'strategy' => 'defer', 'in_footer' => true ) );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'avenza_assets' );

/**
 * Preload the two fonts used above the fold so headings do not shift.
 * The @font-face rules themselves come from theme.json.
 */
function avenza_preload_fonts() {
	$fonts = array( 'cormorant-garamond-normal-latin.woff2', 'manrope-normal-latin.woff2' );
	foreach ( $fonts as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( AVENZA_URI . '/assets/fonts/' . $font )
		);
	}
	echo '<meta name="theme-color" content="#0A3A26">' . "\n";
}
add_action( 'wp_head', 'avenza_preload_fonts', 2 );

/**
 * Drop the emoji detection script and styles; the theme ships no emoji.
 */
function avenza_disable_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'avenza_disable_emoji' );

/**
 * Body classes used by the stylesheet.
 *
 * @param array $classes Classes.
 * @return array
 */
function avenza_body_class( $classes ) {
	if ( is_singular() && avenza_has_hero() ) {
		$classes[] = 'avz-has-hero';
	}
	if ( is_front_page() ) {
		$classes[] = 'avz-front';
	}
	return $classes;
}
add_filter( 'body_class', 'avenza_body_class' );

/**
 * Shorter excerpts for cards.
 *
 * @return int
 */
function avenza_excerpt_length() {
	return 26;
}
add_filter( 'excerpt_length', 'avenza_excerpt_length' );

/**
 * Plain ellipsis after trimmed excerpts.
 *
 * @return string
 */
function avenza_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'avenza_excerpt_more' );

/**
 * Open external links in content in a new tab with safe rel values.
 *
 * @param string $content Content.
 * @return string
 */
function avenza_external_links( $content ) {
	if ( is_admin() || false === strpos( $content, 'href=' ) ) {
		return $content;
	}
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	return preg_replace_callback(
		'/<a\s([^>]*?)href="(https?:\/\/[^"]+)"([^>]*)>/i',
		function ( $m ) use ( $host ) {
			$link_host = wp_parse_url( $m[2], PHP_URL_HOST );
			if ( ! $link_host || $link_host === $host || false !== stripos( $m[0], 'target=' ) ) {
				return $m[0];
			}
			return '<a ' . $m[1] . 'href="' . $m[2] . '"' . $m[3] . ' target="_blank" rel="noopener">';
		},
		$content
	);
}
add_filter( 'the_content', 'avenza_external_links', 20 );

/**
 * Brand favicon until a Site Icon is chosen in the Customizer.
 */
function avenza_fallback_favicon() {
	if ( has_site_icon() ) {
		return;
	}
	echo '<link rel="icon" href="' . esc_url( avenza_asset_img( 'brand/favicon.png' ) ) . '" type="image/png">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( avenza_asset_img( 'brand/favicon.png' ) ) . '">' . "\n";
}
add_action( 'wp_head', 'avenza_fallback_favicon', 4 );
add_action( 'admin_head', 'avenza_fallback_favicon' );
