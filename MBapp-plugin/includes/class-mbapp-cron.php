<?php
/**
 * Ütemezett feladatok.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cron kezelő.
 */
class MBapp_Cron {

	/**
	 * Konstruktor.
	 */
	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'schedules' ) ); // phpcs:ignore WordPress.WP.CronInterval
		add_action( MBAPP_CRON_IMPORT, array( $this, 'run_import' ) );
		add_action( MBAPP_CRON_CLEANUP, array( $this, 'run_cleanup' ) );
		add_action( 'init', array( $this, 'ensure_scheduled' ) );
	}

	/**
	 * Egyedi időzítések.
	 *
	 * @param array $schedules Időzítések.
	 * @return array
	 */
	public function schedules( $schedules ) {
		$schedules['mbapp_15min'] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => __( 'MBapp – 15 percenként', 'mbapp' ),
		);

		$schedules['mbapp_30min'] = array(
			'interval' => 30 * MINUTE_IN_SECONDS,
			'display'  => __( 'MBapp – fél óránként', 'mbapp' ),
		);

		$schedules['mbapp_hourly'] = array(
			'interval' => HOUR_IN_SECONDS,
			'display'  => __( 'MBapp – óránként', 'mbapp' ),
		);

		$schedules['mbapp_3hours'] = array(
			'interval' => 3 * HOUR_IN_SECONDS,
			'display'  => __( 'MBapp – 3 óránként', 'mbapp' ),
		);

		$schedules['mbapp_6hours'] = array(
			'interval' => 6 * HOUR_IN_SECONDS,
			'display'  => __( 'MBapp – 6 óránként', 'mbapp' ),
		);

		$schedules['mbapp_daily'] = array(
			'interval' => DAY_IN_SECONDS,
			'display'  => __( 'MBapp – naponta', 'mbapp' ),
		);

		return $schedules;
	}

	/**
	 * Az ütemezések meglétének biztosítása.
	 */
	public function ensure_scheduled() {
		if ( ! wp_next_scheduled( MBAPP_CRON_CLEANUP ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'mbapp_daily', MBAPP_CRON_CLEANUP );
		}

		$interval = MBapp_Settings::get( 'news', 'interval', 'mbapp_hourly' );
		$enabled  = (bool) MBapp_Settings::get( 'news', 'enabled', 1 );
		$next     = wp_next_scheduled( MBAPP_CRON_IMPORT );

		if ( ! $enabled || 'mbapp_manual' === $interval ) {
			if ( $next ) {
				wp_unschedule_event( $next, MBAPP_CRON_IMPORT );
			}

			return;
		}

		if ( ! $next ) {
			wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, $interval, MBAPP_CRON_IMPORT );

			return;
		}

		// Ha változott az időzítés, újraütemezünk.
		$event = wp_get_scheduled_event( MBAPP_CRON_IMPORT );

		if ( $event && isset( $event->schedule ) && $event->schedule !== $interval ) {
			wp_unschedule_event( $next, MBAPP_CRON_IMPORT );
			wp_schedule_event( time() + MINUTE_IN_SECONDS, $interval, MBAPP_CRON_IMPORT );
		}
	}

	/**
	 * Import futtatása.
	 */
	public function run_import() {
		if ( ! MBapp_Settings::get( 'news', 'enabled', 1 ) ) {
			return;
		}

		$importer = new MBapp_News_Importer();
		$importer->run( false );
	}

	/**
	 * Lejárt események takarítása.
	 */
	public function run_cleanup() {
		$count = MBapp_Events::cleanup();

		if ( $count ) {
			MBapp_Logger::add(
				array(
					'status'  => 'info',
					'message' => sprintf(
						/* translators: %d: események száma */
						__( '%d lejárt esemény automatikusan eltávolítva.', 'mbapp' ),
						$count
					),
				)
			);
		}
	}

	/**
	 * Minden ütemezés beállítása (aktiváláskor).
	 */
	public static function schedule_all() {
		if ( ! wp_next_scheduled( MBAPP_CRON_CLEANUP ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'mbapp_daily', MBAPP_CRON_CLEANUP );
		}

		$interval = MBapp_Settings::get( 'news', 'interval', 'mbapp_hourly' );

		if ( 'mbapp_manual' !== $interval && ! wp_next_scheduled( MBAPP_CRON_IMPORT ) ) {
			wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, $interval, MBAPP_CRON_IMPORT );
		}
	}

	/**
	 * Ütemezések törlése (kikapcsoláskor).
	 */
	public static function unschedule_all() {
		foreach ( array( MBAPP_CRON_IMPORT, MBAPP_CRON_CLEANUP ) as $hook ) {
			$timestamp = wp_next_scheduled( $hook );

			while ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
				$timestamp = wp_next_scheduled( $hook );
			}
		}
	}

	/**
	 * Az importálás következő futásának ideje.
	 *
	 * @return int
	 */
	public static function next_import() {
		return (int) wp_next_scheduled( MBAPP_CRON_IMPORT );
	}
}
