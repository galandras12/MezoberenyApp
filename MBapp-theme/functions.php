<?php
/**
 * MBapp Theme – téma betöltő.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

define( 'MBAPP_THEME_VERSION', '1.0.0' );
define( 'MBAPP_THEME_DIR', get_template_directory() );
define( 'MBAPP_THEME_URI', get_template_directory_uri() );

require_once MBAPP_THEME_DIR . '/inc/theme-support.php';
require_once MBAPP_THEME_DIR . '/inc/template-tags.php';
require_once MBAPP_THEME_DIR . '/inc/customizer.php';

/**
 * Stíluslapok és szkriptek betöltése.
 */
function mbapp_theme_assets() {
	wp_enqueue_style(
		'mbapp-theme',
		MBAPP_THEME_URI . '/assets/css/app.css',
		array(),
		MBAPP_THEME_VERSION
	);

	// A style.css csak a téma fejlécét tartalmazza, de a WordPress elvárja.
	wp_enqueue_style( 'mbapp-theme-style', get_stylesheet_uri(), array( 'mbapp-theme' ), MBAPP_THEME_VERSION );

	wp_enqueue_script(
		'mbapp-theme',
		MBAPP_THEME_URI . '/assets/js/app.js',
		array(),
		MBAPP_THEME_VERSION,
		true
	);

	wp_localize_script(
		'mbapp-theme',
		'MBAppTheme',
		array(
			'storageKey'     => 'mbapp-color-scheme',
			'defaultScheme'  => mbapp_theme_get_option( 'default_scheme', 'system' ),
			'i18n'       => array(
				'toDark'  => __( 'Sötét téma bekapcsolása', 'mbapp-theme' ),
				'toLight' => __( 'Világos téma bekapcsolása', 'mbapp-theme' ),
			),
		)
	);

	// Egyedi akcentus szín a testreszabóból.
	$accent   = mbapp_theme_get_option( 'accent_color', '#1f6feb' );
	$accent_2 = mbapp_theme_get_option( 'accent_color_2', '#17b3a3' );

	$widths = array(
		'phone'   => '560px',
		'compact' => '720px',
		'wide'    => '1080px',
	);

	$width_key = mbapp_theme_get_option( 'app_width', 'compact' );
	$width     = isset( $widths[ $width_key ] ) ? $widths[ $width_key ] : $widths['compact'];

	$custom = sprintf(
		':root{--mb-accent:%1$s;--mb-accent-2:%2$s;--mb-accent-contrast:%3$s;--mb-content-max:%4$s;}',
		esc_attr( $accent ),
		esc_attr( $accent_2 ),
		esc_attr( mbapp_theme_contrast_color( $accent ) ),
		esc_attr( $width )
	);

	wp_add_inline_style( 'mbapp-theme', $custom );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'mbapp_theme_assets' );

/**
 * A szerkesztőben is használjuk az akcentus színt.
 */
function mbapp_theme_editor_assets() {
	add_editor_style( 'assets/css/app.css' );
}
add_action( 'after_setup_theme', 'mbapp_theme_editor_assets' );

/**
 * Villogásmentes témabetöltés: a body kirajzolása előtt beállítjuk a sémát.
 */
function mbapp_theme_no_flash_script() {
	$default = mbapp_theme_get_option( 'default_scheme', 'system' );
	?>
	<script>
	(function () {
		var fallback = <?php echo wp_json_encode( $default ); ?>;
		try {
			var s = window.localStorage.getItem('mbapp-color-scheme');
			if (s !== 'dark' && s !== 'light') {
				s = (fallback === 'dark' || fallback === 'light')
					? fallback
					: ((window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light');
			}
			document.documentElement.setAttribute('data-theme', s);
		} catch (e) {}
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'mbapp_theme_no_flash_script', 1 );

/**
 * Widget területek.
 */
function mbapp_theme_widgets_init() {
	// Oldalsáv szándékosan nincs: az app felület minden oldalon egyhasábos.
	register_sidebar(
		array(
			'name'          => __( 'Lábléc', 'mbapp-theme' ),
			'id'            => 'footer-1',
			'description'   => __( 'A lábléc widget területe.', 'mbapp-theme' ),
			'before_widget' => '<section id="%1$s" class="mb-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'mbapp_theme_widgets_init' );

/**
 * Kivonat hossza és vége.
 */
function mbapp_theme_excerpt_length() {
	return 24;
}
add_filter( 'excerpt_length', 'mbapp_theme_excerpt_length' );

function mbapp_theme_excerpt_more() {
	return '…';
}
add_filter( 'excerpt_more', 'mbapp_theme_excerpt_more' );

/**
 * Body osztályok.
 *
 * @param array $classes Osztályok.
 * @return array
 */
function mbapp_theme_body_classes( $classes ) {
	$classes[] = 'mb-body';
	$classes[] = 'mb-app-shell';
	$classes[] = 'mb-width-' . mbapp_theme_get_option( 'app_width', 'compact' );

	if ( ! mbapp_theme_get_option( 'show_footer', false ) ) {
		$classes[] = 'mb-no-footer';
	}

	if ( mbapp_theme_has_plugin() ) {
		$classes[] = 'mb-has-plugin';
	}

	return $classes;
}
add_filter( 'body_class', 'mbapp_theme_body_classes' );

/**
 * A bővítmény jelen van-e.
 *
 * @return bool
 */
function mbapp_theme_has_plugin() {
	return defined( 'MBAPP_PLUGIN_VERSION' );
}

/**
 * Kontraszt szín számítása (fehér vagy sötét szöveg az akcentus felett).
 *
 * @param string $hex HEX szín.
 * @return string
 */
function mbapp_theme_contrast_color( $hex ) {
	$hex = ltrim( (string) $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return '#ffffff';
	}

	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	// Relatív fényesség (ITU-R BT.601).
	$luma = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;

	return $luma > 0.62 ? '#111820' : '#ffffff';
}
