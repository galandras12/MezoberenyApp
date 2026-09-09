<?php
/**
 * Eltávolítás.
 *
 * Csak akkor töröl adatot, ha a felhasználó ezt kifejezetten kérte
 * (mbapp_delete_data beállítás). Alapból mindent megőrzünk.
 *
 * @package MBapp_Plugin
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! get_option( 'mbapp_delete_data' ) ) {
	return;
}

global $wpdb;

// Beállítások.
$options = array(
	'mbapp_news_settings',
	'mbapp_events_settings',
	'mbapp_menu_settings',
	'mbapp_news_last_run',
	'mbapp_events_page_id',
	'mbapp_db_version',
	'mbapp_delete_data',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Napló tábla.
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mbapp_news_log" );

// Bejegyzések.
$post_ids = get_posts(
	array(
		'post_type'      => array( 'mbapp_news', 'mbapp_event' ),
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $post_ids as $post_id ) {
	wp_delete_post( $post_id, true );
}

// Ütemezések.
foreach ( array( 'mbapp_news_import_event', 'mbapp_events_cleanup_event' ) as $hook ) {
	$timestamp = wp_next_scheduled( $hook );

	while ( $timestamp ) {
		wp_unschedule_event( $timestamp, $hook );
		$timestamp = wp_next_scheduled( $hook );
	}
}
