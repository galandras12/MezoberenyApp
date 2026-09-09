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
				'enabled'     => 1,
				'position'    => 'bottom',
				'show_labels' => 1,
				'autohide'    => 1,
				'style'       => 'glass',
				'hide_on'     => 'none',
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
