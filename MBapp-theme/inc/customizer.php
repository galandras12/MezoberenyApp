<?php
/**
 * Testreszabó beállítások.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Testreszabó vezérlők regisztrálása.
 *
 * @param WP_Customize_Manager $wp_customize Testreszabó.
 */
function mbapp_theme_customize_register( $wp_customize ) {
	$wp_customize->add_panel(
		'mbapp_panel',
		array(
			'title'       => __( 'MBapp felület', 'mbapp-theme' ),
			'description' => __( 'A fejléc kép, a favicon, az egyedi szövegek és a diavetítés részletes beállításait a Megjelenés → MBapp téma menüpontban találod.', 'mbapp-theme' ),
			'priority'    => 20,
		)
	);

	/* ---------------------------------------------------------------
	 * Színek
	 * --------------------------------------------------------------- */
	$wp_customize->add_section(
		'mbapp_colors',
		array(
			'title' => __( 'Színek', 'mbapp-theme' ),
			'panel' => 'mbapp_panel',
		)
	);

	$wp_customize->add_setting(
		'mbapp_accent_color',
		array(
			'default'           => '#1f6feb',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'mbapp_accent_color',
			array(
				'label'   => __( 'Akcentus szín', 'mbapp-theme' ),
				'section' => 'mbapp_colors',
			)
		)
	);

	$wp_customize->add_setting(
		'mbapp_accent_color_2',
		array(
			'default'           => '#17b3a3',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'mbapp_accent_color_2',
			array(
				'label'       => __( 'Másodlagos akcentus szín', 'mbapp-theme' ),
				'description' => __( 'Az átmenetekhez és a kiemelt gombhoz használjuk.', 'mbapp-theme' ),
				'section'     => 'mbapp_colors',
			)
		)
	);

	$wp_customize->add_setting(
		'mbapp_default_scheme',
		array(
			'default'           => 'system',
			'sanitize_callback' => 'mbapp_theme_sanitize_scheme',
		)
	);
	$wp_customize->add_control(
		'mbapp_default_scheme',
		array(
			'label'       => __( 'Alapértelmezett téma', 'mbapp-theme' ),
			'description' => __( 'Melyik legyen az alap, ha a látogató még nem választott. A kapcsoló ettől függetlenül mindig működik.', 'mbapp-theme' ),
			'section'     => 'mbapp_colors',
			'type'        => 'select',
			'choices'     => array(
				'system' => __( 'Rendszerbeállítás szerint', 'mbapp-theme' ),
				'light'  => __( 'Világos', 'mbapp-theme' ),
				'dark'   => __( 'Sötét', 'mbapp-theme' ),
			),
		)
	);

	/* ---------------------------------------------------------------
	 * Fejléc
	 * --------------------------------------------------------------- */
	$wp_customize->add_section(
		'mbapp_header',
		array(
			'title' => __( 'Fejléc', 'mbapp-theme' ),
			'panel' => 'mbapp_panel',
		)
	);

	$wp_customize->add_setting(
		'mbapp_app_width',
		array(
			'default'           => 'compact',
			'sanitize_callback' => 'mbapp_theme_sanitize_width',
		)
	);
	$wp_customize->add_control(
		'mbapp_app_width',
		array(
			'label'       => __( 'Tartalom szélessége', 'mbapp-theme' ),
			'description' => __( 'Az app felület nagyobb kijelzőn is egy középre igazított hasáb marad, mint egy telefonos alkalmazás.', 'mbapp-theme' ),
			'section'     => 'mbapp_header',
			'type'        => 'select',
			'choices'     => array(
				'phone'   => __( 'Telefon (560 px) – leginkább app hatás', 'mbapp-theme' ),
				'compact' => __( 'Kompakt (720 px) – ajánlott', 'mbapp-theme' ),
				'wide'    => __( 'Széles (1080 px)', 'mbapp-theme' ),
			),
		)
	);

	$wp_customize->add_setting(
		'mbapp_show_back',
		array(
			'default'           => true,
			'sanitize_callback' => 'mbapp_theme_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mbapp_show_back',
		array(
			'label'       => __( 'Vissza gomb a fejlécben', 'mbapp-theme' ),
			'description' => __( 'A kezdőlapon kívül minden oldalon megjelenik, mint egy mobilalkalmazásban.', 'mbapp-theme' ),
			'section'     => 'mbapp_header',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'mbapp_show_tagline',
		array(
			'default'           => true,
			'sanitize_callback' => 'mbapp_theme_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mbapp_show_tagline',
		array(
			'label'   => __( 'Mottó megjelenítése a fejlécben', 'mbapp-theme' ),
			'section' => 'mbapp_header',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'mbapp_show_search',
		array(
			'default'           => true,
			'sanitize_callback' => 'mbapp_theme_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mbapp_show_search',
		array(
			'label'   => __( 'Kereső gomb megjelenítése', 'mbapp-theme' ),
			'section' => 'mbapp_header',
			'type'    => 'checkbox',
		)
	);

	/* ---------------------------------------------------------------
	 * Lebegő menü (tartalék)
	 * --------------------------------------------------------------- */
	$wp_customize->add_section(
		'mbapp_dock',
		array(
			'title'       => __( 'Lebegő menü', 'mbapp-theme' ),
			'panel'       => 'mbapp_panel',
			'description' => __( 'Ha az MBapp Plugin aktív, a lebegő menüt a bővítmény admin felületén állíthatod be. Itt csak a tartalék menü viselkedése módosítható.', 'mbapp-theme' ),
		)
	);

	$wp_customize->add_setting(
		'mbapp_dock_autohide',
		array(
			'default'           => true,
			'sanitize_callback' => 'mbapp_theme_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mbapp_dock_autohide',
		array(
			'label'   => __( 'Lefelé görgetéskor rejtse el a menüt', 'mbapp-theme' ),
			'section' => 'mbapp_dock',
			'type'    => 'checkbox',
		)
	);

	/* ---------------------------------------------------------------
	 * Lábléc
	 * --------------------------------------------------------------- */
	$wp_customize->add_section(
		'mbapp_footer',
		array(
			'title' => __( 'Lábléc', 'mbapp-theme' ),
			'panel' => 'mbapp_panel',
		)
	);

	$wp_customize->add_setting(
		'mbapp_show_footer',
		array(
			'default'           => false,
			'sanitize_callback' => 'mbapp_theme_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'mbapp_show_footer',
		array(
			'label'       => __( 'Lábléc megjelenítése', 'mbapp-theme' ),
			'description' => __( 'Alapból kikapcsolva: a mobilalkalmazásoknak nincs láblécük. Bekapcsolva megjelenik a lábléc menü, a widgetek, a szerzői jogi sor és a téma váltó gomb.', 'mbapp-theme' ),
			'section'     => 'mbapp_footer',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'mbapp_footer_text',
		array(
			'default'           => '',
			'sanitize_callback' => 'wp_kses_post',
		)
	);
	$wp_customize->add_control(
		'mbapp_footer_text',
		array(
			'label'       => __( 'Lábléc szöveg', 'mbapp-theme' ),
			'description' => __( 'Csak akkor látszik, ha a lábléc be van kapcsolva.', 'mbapp-theme' ),
			'section'     => 'mbapp_footer',
			'type'        => 'textarea',
		)
	);
}
add_action( 'customize_register', 'mbapp_theme_customize_register' );

/**
 * Jelölőnégyzet fertőtlenítése.
 *
 * @param mixed $value Érték.
 * @return bool
 */
function mbapp_theme_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * Tartalomszélesség fertőtlenítése.
 *
 * @param string $value Érték.
 * @return string
 */
function mbapp_theme_sanitize_width( $value ) {
	return in_array( $value, array( 'phone', 'compact', 'wide' ), true ) ? $value : 'compact';
}

/**
 * Színséma fertőtlenítése.
 *
 * @param string $value Érték.
 * @return string
 */
function mbapp_theme_sanitize_scheme( $value ) {
	return in_array( $value, array( 'system', 'light', 'dark' ), true ) ? $value : 'system';
}
