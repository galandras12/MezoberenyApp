<?php
/**
 * Beállítások kezelése.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Beállítás olvasó / író.
 */
class MBapp_Settings {

	/**
	 * Beállításcsoport -> option név.
	 */
	const OPTIONS = array(
		'news'   => 'mbapp_news_settings',
		'events' => 'mbapp_events_settings',
		'menu'   => 'mbapp_menu_settings',
		'home'   => 'mbapp_home_settings',
	);

	/**
	 * Alapértelmezések.
	 *
	 * @param string $group Csoport.
	 * @return array
	 */
	public static function defaults( $group ) {
		$defaults = array(
			'news'   => array(
				'enabled'            => 1,
				'source_url'         => 'https://mezobereny.hu/s/hirek',
				'source_type'        => 'auto',
				'item_selector'      => 'article',
				'title_selector'     => 'h2 a, h3 a, h2, h3, .title',
				'link_selector'      => 'a',
				'image_selector'     => 'img',
				'excerpt_selector'   => 'p',
				'date_selector'      => 'time, .date',
				'content_selector'   => 'article, .entry-content, .content, main',
				'max_items'          => 10,
				'fetch_full_content' => 1,
				'import_images'      => 1,
				'post_status'        => 'publish',
				'interval'           => 'mbapp_hourly',
				'append_source'      => 1,
				'source_label'       => __( 'Pontos részleteket a Mezobereny.hu oldalon olvashatod', 'mbapp' ),
				'source_site_name'   => 'Mezobereny.hu',
				'request_timeout'    => 20,
				'user_agent'         => '',
				'log_retention_days' => 90,
			),
			'events' => array(
				'layout'           => 'grid',
				'per_page'         => 25,
				'past_days'        => 7,
				'auto_delete'      => 1,
				'delete_mode'      => 'trash',
				'require_source'   => 1,
				'default_source'   => 'https://mezobereny.hu/',
				'source_label'     => __( 'Pontos részleteket a Mezobereny.hu oldalon olvashatod', 'mbapp' ),
				'show_countdown'   => 1,
				'show_past'        => 1,
				'page_id'          => 0,
				'date_format'      => 'Y. F j. H:i',
			),
			'menu'   => array(
				'enabled'       => 1,
				'position'      => 'bottom',
				'show_labels'   => 1,
				'autohide'      => 1,
				'style'         => 'glass',
				'hide_on'       => 'none',
				// AJAX navigáció és animációk.
				'ajax_nav'      => 1,
				'ajax_scope'    => 'menu',
				'anim_type'     => 'slide',
				'anim_duration' => 280,
				'anim_easing'   => 'ease-out',
				'progress_bar'  => 1,
				'tap_effect'    => 'ripple',
				'dock_anim'     => 'slide-up',
				'items'       => array(
					array(
						'label'     => __( 'Kezdőlap', 'mbapp' ),
						'icon_type' => 'builtin',
						'icon'      => 'home',
						'url'       => '/',
						'target'    => '_self',
						'highlight' => 0,
						'visible'   => 'all',
					),
					array(
						'label'     => __( 'Hírek', 'mbapp' ),
						'icon_type' => 'builtin',
						'icon'      => 'news',
						'url'       => '/hirek/',
						'target'    => '_self',
						'highlight' => 0,
						'visible'   => 'all',
					),
					array(
						'label'     => __( 'Események', 'mbapp' ),
						'icon_type' => 'builtin',
						'icon'      => 'calendar',
						'url'       => '/esemenyek/',
						'target'    => '_self',
						'highlight' => 1,
						'visible'   => 'all',
					),
					array(
						'label'     => __( 'Térkép', 'mbapp' ),
						'icon_type' => 'builtin',
						'icon'      => 'map',
						'url'       => '/terkep/',
						'target'    => '_self',
						'highlight' => 0,
						'visible'   => 'all',
					),
					array(
						'label'     => __( 'Kapcsolat', 'mbapp' ),
						'icon_type' => 'builtin',
						'icon'      => 'phone',
						'url'       => '/kapcsolat/',
						'target'    => '_self',
						'highlight' => 0,
						'visible'   => 'all',
					),
				),
			),
			'home'   => array(
				'enabled' => 1,
				'blocks'  => array(
					array(
						'type'         => 'hero',
						'enabled'      => 1,
						'title'        => '',
						'subtitle'     => '',
						'media'        => 'none',
						'show_image'   => 0,
						'image'        => 0,
						'images'       => '',
						'interval'     => 6,
						'autoplay'     => 1,
						'dots'         => 1,
						'arrows'       => 0,
						'width'        => 'container',
						'shape'        => 'rounded',
						'height'       => 'normal',
						'overlay'      => 45,
						'align'        => 'left',
						'button_label' => '',
						'button_url'   => '',
					),
					array(
						'type'     => 'events',
						'enabled'  => 1,
						'title'    => __( 'Közelgő események', 'mbapp' ),
						'limit'    => 3,
						'layout'   => 'grid',
						'past'     => 'no',
						'loadmore' => 'no',
						'link'     => 1,
					),
					array(
						'type'    => 'news',
						'enabled' => 1,
						'title'   => __( 'Friss hírek', 'mbapp' ),
						'limit'   => 6,
						'layout'  => 'grid',
						'link'    => 1,
					),
				),
			),
		);

		return isset( $defaults[ $group ] ) ? $defaults[ $group ] : array();
	}

	/**
	 * Teljes csoport lekérése (alapértelmezésekkel kiegészítve).
	 *
	 * @param string $group Csoport.
	 * @return array
	 */
	public static function all( $group ) {
		if ( ! isset( self::OPTIONS[ $group ] ) ) {
			return array();
		}

		$stored = get_option( self::OPTIONS[ $group ], array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( self::defaults( $group ), $stored );
	}

	/**
	 * Egy beállítás lekérése.
	 *
	 * @param string $group   Csoport.
	 * @param string $key     Kulcs.
	 * @param mixed  $default Alapérték, ha nincs.
	 * @return mixed
	 */
	public static function get( $group, $key, $default = null ) {
		$all = self::all( $group );

		if ( array_key_exists( $key, $all ) && '' !== $all[ $key ] && null !== $all[ $key ] ) {
			return $all[ $key ];
		}

		return $default;
	}

	/**
	 * Beállítások mentése.
	 *
	 * @param string $group  Csoport.
	 * @param array  $values Értékek.
	 * @return bool
	 */
	public static function save( $group, array $values ) {
		if ( ! isset( self::OPTIONS[ $group ] ) ) {
			return false;
		}

		return update_option( self::OPTIONS[ $group ], $values );
	}

	/**
	 * A kezdőlapon elhelyezhető blokktípusok.
	 *
	 * @return array
	 */
	public static function block_types() {
		return array(
			'hero'   => __( 'Fejléc (hero) – cím, alcím, háttérkép, gomb', 'mbapp' ),
			'events' => __( 'Események – a közelgő programok', 'mbapp' ),
			'news'   => __( 'Hírek – a legfrissebb cikkek', 'mbapp' ),
			'html'   => __( 'Egyedi tartalom – saját HTML vagy shortcode', 'mbapp' ),
			'page'   => __( 'Oldal tartalma – egy meglévő oldal szövege', 'mbapp' ),
		);
	}

	/**
	 * A fejléc blokk médiamódjai.
	 *
	 * @return array
	 */
	public static function hero_media_modes() {
		return array(
			'none'      => __( 'Nincs kép – színátmenetes háttér', 'mbapp' ),
			'image'     => __( 'Egy háttérkép', 'mbapp' ),
			'slideshow' => __( 'Diavetítés több képpel', 'mbapp' ),
		);
	}

	/**
	 * A fejléc blokk szélessége.
	 *
	 * @return array
	 */
	public static function hero_widths() {
		return array(
			'container' => __( 'A tartalom szélességében (lekerekített kártya)', 'mbapp' ),
			'full'      => __( 'Teljes szélesség – széltől szélig', 'mbapp' ),
		);
	}

	/**
	 * A fejléc blokk sarkai.
	 *
	 * @return array
	 */
	public static function hero_shapes() {
		return array(
			'rounded' => __( 'Erősen lekerekített', 'mbapp' ),
			'soft'    => __( 'Enyhén lekerekített', 'mbapp' ),
			'square'  => __( 'Szögletes', 'mbapp' ),
		);
	}

	/**
	 * A fejléc blokk magasságai.
	 *
	 * @return array
	 */
	public static function hero_heights() {
		return array(
			'compact' => __( 'Alacsony', 'mbapp' ),
			'normal'  => __( 'Közepes', 'mbapp' ),
			'tall'    => __( 'Magas', 'mbapp' ),
			'hero'    => __( 'Nagyon magas', 'mbapp' ),
			'screen'  => __( 'Majdnem teljes képernyő', 'mbapp' ),
		);
	}

	/**
	 * Elérhető oldalátmenet animációk.
	 *
	 * @return array
	 */
	public static function animations() {
		return array(
			'none'     => __( 'Nincs animáció', 'mbapp' ),
			'fade'     => __( 'Áttűnés', 'mbapp' ),
			'slide'    => __( 'Oldalirányú csúsztatás (app érzet)', 'mbapp' ),
			'slide-up' => __( 'Felfelé csúsztatás', 'mbapp' ),
			'scale'    => __( 'Nagyítás', 'mbapp' ),
		);
	}

	/**
	 * Elérhető időzítési görbék.
	 *
	 * @return array
	 */
	public static function easings() {
		return array(
			'ease-out'    => __( 'Lágy lassítás (ease-out)', 'mbapp' ),
			'ease-in-out' => __( 'Lágy indítás és lassítás', 'mbapp' ),
			'spring'      => __( 'Rugós (kicsit túllendül)', 'mbapp' ),
			'linear'      => __( 'Egyenletes', 'mbapp' ),
		);
	}

	/**
	 * A gombnyomás visszajelzései.
	 *
	 * @return array
	 */
	public static function tap_effects() {
		return array(
			'ripple' => __( 'Hullám (ripple)', 'mbapp' ),
			'scale'  => __( 'Benyomódás', 'mbapp' ),
			'none'   => __( 'Nincs', 'mbapp' ),
		);
	}

	/**
	 * A lebegő menü megjelenési animációja.
	 *
	 * @return array
	 */
	public static function dock_animations() {
		return array(
			'slide-up' => __( 'Alulról felcsúszik', 'mbapp' ),
			'fade'     => __( 'Áttűnik', 'mbapp' ),
			'none'     => __( 'Azonnal megjelenik', 'mbapp' ),
		);
	}

	/**
	 * Elérhető cron időzítések.
	 *
	 * @return array
	 */
	public static function intervals() {
		return array(
			'mbapp_15min'   => __( '15 percenként', 'mbapp' ),
			'mbapp_30min'   => __( 'Fél óránként', 'mbapp' ),
			'mbapp_hourly'  => __( 'Óránként', 'mbapp' ),
			'mbapp_3hours'  => __( '3 óránként', 'mbapp' ),
			'mbapp_6hours'  => __( '6 óránként', 'mbapp' ),
			'mbapp_daily'   => __( 'Naponta', 'mbapp' ),
			'mbapp_manual'  => __( 'Csak kézi indítással', 'mbapp' ),
		);
	}
}
