<?php
/**
 * Téma beállítások: alapértékek és olvasó.
 *
 * A beállítások a WordPress „theme mod” tárolójában élnek `mbapp_` előtaggal,
 * így a Testreszabó és a téma saját szerkesztő felülete ugyanazt az adatot
 * írja és olvassa.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Minden téma beállítás alapértéke.
 *
 * @return array
 */
function mbapp_theme_defaults() {
	return array(
		/* Színek és alap megjelenés */
		'accent_color'           => '#1f6feb',
		'accent_color_2'         => '#17b3a3',
		'default_scheme'         => 'system',
		'app_width'              => 'compact',

		/* Fejléc (app bar) */
		'appbar_size'            => 'normal',
		'appbar_sticky'          => true,
		'appbar_blur'            => true,
		'logo_size'              => 36,
		'show_tagline'           => true,
		'show_search'            => true,
		'show_back'              => true,

		/* Fejléc kép (banner) */
		'header_image_enabled'   => false,
		'header_image'           => 0,
		'header_image_height'    => 'normal',
		'header_image_position'  => 'center',
		'header_image_overlay'   => 35,
		'header_image_scope'     => 'front',
		'header_image_title'     => '',
		'header_image_subtitle'  => '',
		'header_image_rounded'   => true,

		/* Favicon */
		'favicon'                => 0,
		'favicon_shape'          => 'rounded',
		'favicon_in_appbar'      => true,

		/* Diavetítés a bejegyzésekből */
		'slider_enabled'         => false,
		'slider_source'          => 'news',
		'slider_count'           => 5,
		'slider_scope'           => 'front',
		'slider_height'          => 'normal',
		'slider_autoplay'        => true,
		'slider_interval'        => 5,
		'slider_arrows'          => true,
		'slider_dots'            => true,
		'slider_require_image'   => true,
		'slider_show_excerpt'    => false,
		'slider_category'        => '',

		/* Egyedi szövegblokkok */
		'texts'                  => array(),

		/* Lábléc */
		'show_footer'            => false,
		'footer_text'            => '',

		/* Lebegő menü (tartalék, ha a bővítmény nincs bekapcsolva) */
		'dock_autohide'          => true,
	);
}

/**
 * Egy téma beállítás értéke.
 *
 * @param string $key     Kulcs (a `mbapp_` előtag nélkül).
 * @param mixed  $default Alapérték; ha nincs megadva, a központi alapértéket használjuk.
 * @return mixed
 */
function mbapp_theme_get_option( $key, $default = null ) {
	if ( null === $default ) {
		$defaults = mbapp_theme_defaults();
		$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	}

	return get_theme_mod( 'mbapp_' . $key, $default );
}

/**
 * Egy téma beállítás mentése.
 *
 * @param string $key   Kulcs.
 * @param mixed  $value Érték.
 */
function mbapp_theme_set_option( $key, $value ) {
	set_theme_mod( 'mbapp_' . $key, $value );
}

/**
 * Az app bar magassága képpontban.
 *
 * @return int
 */
function mbapp_theme_appbar_height() {
	$sizes = array(
		'compact' => 50,
		'normal'  => 58,
		'large'   => 70,
	);

	$size = mbapp_theme_get_option( 'appbar_size' );

	return isset( $sizes[ $size ] ) ? $sizes[ $size ] : $sizes['normal'];
}

/**
 * A fejléc kép magassága képpontban.
 *
 * @return int
 */
function mbapp_theme_header_image_height() {
	$sizes = array(
		'compact' => 160,
		'normal'  => 240,
		'tall'    => 340,
		'hero'    => 460,
	);

	$size = mbapp_theme_get_option( 'header_image_height' );

	return isset( $sizes[ $size ] ) ? $sizes[ $size ] : $sizes['normal'];
}

/**
 * A diavetítés magassága képpontban.
 *
 * @return int
 */
function mbapp_theme_slider_height() {
	$sizes = array(
		'compact' => 200,
		'normal'  => 280,
		'tall'    => 380,
	);

	$size = mbapp_theme_get_option( 'slider_height' );

	return isset( $sizes[ $size ] ) ? $sizes[ $size ] : $sizes['normal'];
}

/**
 * Megjelenjen-e egy elem az aktuális oldalon a hatókör alapján.
 *
 * @param string $scope 'all', 'front' vagy 'inner'.
 * @return bool
 */
function mbapp_theme_scope_matches( $scope ) {
	if ( 'all' === $scope ) {
		return true;
	}

	if ( 'front' === $scope ) {
		return is_front_page();
	}

	if ( 'inner' === $scope ) {
		return ! is_front_page();
	}

	return false;
}

/**
 * A választható hatókörök.
 *
 * @return array
 */
function mbapp_theme_scopes() {
	return array(
		'front' => __( 'Csak a kezdőlapon', 'mbapp-theme' ),
		'all'   => __( 'Minden oldalon', 'mbapp-theme' ),
		'inner' => __( 'Csak a belső oldalakon', 'mbapp-theme' ),
	);
}

/**
 * Az egyedi szövegblokkok helyei.
 *
 * @return array
 */
function mbapp_theme_text_positions() {
	return array(
		'header' => __( 'A tartalom előtt (a fejléc alatt)', 'mbapp-theme' ),
		'footer' => __( 'A tartalom után', 'mbapp-theme' ),
	);
}

/**
 * Az egyedi szövegblokkok stílusai.
 *
 * @return array
 */
function mbapp_theme_text_styles() {
	return array(
		'plain'  => __( 'Egyszerű szöveg', 'mbapp-theme' ),
		'card'   => __( 'Kártya', 'mbapp-theme' ),
		'info'   => __( 'Információ (kék)', 'mbapp-theme' ),
		'notice' => __( 'Figyelmeztetés (sárga)', 'mbapp-theme' ),
		'accent' => __( 'Kiemelt (akcentus szín)', 'mbapp-theme' ),
	);
}

/**
 * Az egyedi szövegblokkok alapértelmezett mezői.
 *
 * @return array
 */
function mbapp_theme_text_defaults() {
	return array(
		'enabled'  => 1,
		'title'    => '',
		'content'  => '',
		'position' => 'header',
		'style'    => 'card',
		'scope'    => 'all',
		'icon'     => '',
	);
}
