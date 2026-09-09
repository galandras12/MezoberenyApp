<?php
/**
 * Téma támogatások és menük.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Téma beállítása.
 */
function mbapp_theme_setup() {
	load_theme_textdomain( 'mbapp-theme', MBAPP_THEME_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );

	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 120,
			'width'       => 120,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_image_size( 'mbapp-card', 800, 450, true );
	add_image_size( 'mbapp-thumb', 320, 320, true );
	add_image_size( 'mbapp-wide', 1400, 700, true );

	register_nav_menus(
		array(
			'primary' => __( 'Fő menü (app bar / lábléc)', 'mbapp-theme' ),
			'dock'    => __( 'Lebegő menü – tartalék (ha az MBapp Plugin nincs bekapcsolva)', 'mbapp-theme' ),
			'footer'  => __( 'Lábléc menü', 'mbapp-theme' ),
		)
	);

	// Szerkesztői színpaletta.
	add_theme_support(
		'editor-color-palette',
		array(
			array(
				'name'  => __( 'Akcentus', 'mbapp-theme' ),
				'slug'  => 'mb-accent',
				'color' => mbapp_theme_get_option( 'accent_color', '#1f6feb' ),
			),
			array(
				'name'  => __( 'Másodlagos akcentus', 'mbapp-theme' ),
				'slug'  => 'mb-accent-2',
				'color' => mbapp_theme_get_option( 'accent_color_2', '#17b3a3' ),
			),
			array(
				'name'  => __( 'Szöveg', 'mbapp-theme' ),
				'slug'  => 'mb-text',
				'color' => '#131922',
			),
			array(
				'name'  => __( 'Halvány', 'mbapp-theme' ),
				'slug'  => 'mb-muted',
				'color' => '#78859a',
			),
		)
	);

	// A tartalom szélessége.
	if ( ! isset( $GLOBALS['content_width'] ) ) {
		$GLOBALS['content_width'] = 1080;
	}
}
add_action( 'after_setup_theme', 'mbapp_theme_setup' );

/**
 * PWA-szerű meta címkék.
 */
function mbapp_theme_meta_tags() {
	echo '<meta name="theme-color" content="#eef1f6">' . "\n";
	echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
	echo '<meta name="format-detection" content="telephone=no">' . "\n";
}
add_action( 'wp_head', 'mbapp_theme_meta_tags', 2 );
