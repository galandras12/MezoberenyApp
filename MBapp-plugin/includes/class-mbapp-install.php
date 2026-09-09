<?php
/**
 * Telepítés / eltávolítás.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Telepítő.
 */
class MBapp_Install {

	/**
	 * Aktiváláskor fut.
	 */
	public static function activate() {
		self::install();

		// Bejegyzéstípusok regisztrálása, hogy a permalinkek működjenek.
		$post_types = new MBapp_Post_Types();
		$post_types->register();

		self::create_events_page();

		MBapp_Cron::schedule_all();

		update_option( 'mbapp_db_version', MBAPP_PLUGIN_VERSION );

		flush_rewrite_rules();
	}

	/**
	 * Kikapcsoláskor fut.
	 */
	public static function deactivate() {
		MBapp_Cron::unschedule_all();
		flush_rewrite_rules();
	}

	/**
	 * Adatbázis és alapbeállítások létrehozása.
	 */
	public static function install() {
		self::create_log_table();

		foreach ( array_keys( MBapp_Settings::OPTIONS ) as $group ) {
			$option = MBapp_Settings::OPTIONS[ $group ];

			if ( false === get_option( $option, false ) ) {
				add_option( $option, MBapp_Settings::defaults( $group ) );
			}
		}
	}

	/**
	 * Napló tábla neve.
	 *
	 * @return string
	 */
	public static function log_table() {
		global $wpdb;

		return $wpdb->prefix . 'mbapp_news_log';
	}

	/**
	 * Napló tábla létrehozása / frissítése.
	 */
	public static function create_log_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::log_table();
		$collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			run_id VARCHAR(32) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			status VARCHAR(20) NOT NULL DEFAULT 'info',
			post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			title TEXT NULL,
			source_url TEXT NULL,
			image_url TEXT NULL,
			source_date DATETIME NULL,
			message TEXT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY run_id (run_id),
			KEY created_at (created_at),
			KEY post_id (post_id)
		) {$collate};";

		dbDelta( $sql );
	}

	/**
	 * "Események" oldal létrehozása a shortcode-dal.
	 *
	 * @return int Az oldal azonosítója.
	 */
	public static function create_events_page() {
		$existing = (int) MBapp_Settings::get( 'events', 'page_id', 0 );

		if ( $existing && 'publish' === get_post_status( $existing ) ) {
			return $existing;
		}

		// Van már ilyen nevű oldal?
		$page = get_page_by_path( 'esemenyek' );

		if ( ! $page ) {
			$found = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => array( 'publish', 'draft', 'pending' ),
					's'              => '[mbapp_events',
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);

			if ( ! empty( $found ) ) {
				$page = get_post( $found[0] );
			}
		}

		if ( $page ) {
			$page_id = $page->ID;

			if ( false === strpos( $page->post_content, '[mbapp_events' ) ) {
				wp_update_post(
					array(
						'ID'           => $page_id,
						'post_content' => trim( $page->post_content . "\n\n[mbapp_events]" ),
					)
				);
			}
		} else {
			$page_id = wp_insert_post(
				array(
					'post_title'   => __( 'Események', 'mbapp' ),
					'post_name'    => 'esemenyek',
					'post_content' => '[mbapp_events]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
				)
			);

			if ( is_wp_error( $page_id ) ) {
				return 0;
			}
		}

		$settings            = MBapp_Settings::all( 'events' );
		$settings['page_id'] = (int) $page_id;
		MBapp_Settings::save( 'events', $settings );

		// A téma is elérje.
		update_option( 'mbapp_events_page_id', (int) $page_id );

		return (int) $page_id;
	}
}
