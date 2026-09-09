<?php
/**
 * Import napló.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Naplózó.
 */
class MBapp_Logger {

	/**
	 * Státuszok és címkéik.
	 *
	 * @return array
	 */
	public static function statuses() {
		return array(
			'imported'  => __( 'Beimportálva', 'mbapp' ),
			'updated'   => __( 'Frissítve', 'mbapp' ),
			'duplicate' => __( 'Már létezett', 'mbapp' ),
			'skipped'   => __( 'Kihagyva', 'mbapp' ),
			'error'     => __( 'Hiba', 'mbapp' ),
			'info'      => __( 'Információ', 'mbapp' ),
		);
	}

	/**
	 * Bejegyzés hozzáadása a naplóhoz.
	 *
	 * @param array $data Adatok.
	 * @return int A napló sor azonosítója.
	 */
	public static function add( array $data ) {
		global $wpdb;

		$defaults = array(
			'run_id'      => '',
			'status'      => 'info',
			'post_id'     => 0,
			'title'       => '',
			'source_url'  => '',
			'image_url'   => '',
			'source_date' => null,
			'message'     => '',
		);

		$row = wp_parse_args( $data, $defaults );

		$insert = array(
			'run_id'     => substr( (string) $row['run_id'], 0, 32 ),
			'created_at' => current_time( 'mysql' ),
			'status'     => substr( (string) $row['status'], 0, 20 ),
			'post_id'    => (int) $row['post_id'],
			'title'      => (string) $row['title'],
			'source_url' => (string) $row['source_url'],
			'image_url'  => (string) $row['image_url'],
			'message'    => (string) $row['message'],
		);

		$format = array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' );

		if ( ! empty( $row['source_date'] ) ) {
			$insert['source_date'] = $row['source_date'];
			$format[]              = '%s';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( MBapp_Install::log_table(), $insert, $format );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Naplósorok lekérdezése.
	 *
	 * @param array $args Szűrők.
	 * @return array
	 */
	public static function query( array $args = array() ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'status'   => '',
				'search'   => '',
				'run_id'   => '',
				'per_page' => 25,
				'page'     => 1,
				'orderby'  => 'created_at',
				'order'    => 'DESC',
			)
		);

		$table  = MBapp_Install::log_table();
		$where  = 'WHERE 1=1';
		$params = array();

		if ( $args['status'] ) {
			$where   .= ' AND status = %s';
			$params[] = $args['status'];
		}

		if ( $args['run_id'] ) {
			$where   .= ' AND run_id = %s';
			$params[] = $args['run_id'];
		}

		if ( $args['search'] ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where   .= ' AND (title LIKE %s OR source_url LIKE %s OR message LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$allowed_orderby = array( 'id', 'created_at', 'status', 'title' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$per_page = max( 1, (int) $args['per_page'] );
		$offset   = max( 0, ( (int) $args['page'] - 1 ) * $per_page );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
		$total     = (int) ( $params
			? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) )
			: $wpdb->get_var( $count_sql ) );

		$sql         = "SELECT * FROM {$table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$query_param = array_merge( $params, array( $per_page, $offset ) );
		$items       = $wpdb->get_results( $wpdb->prepare( $sql, $query_param ) );
		// phpcs:enable

		return array(
			'items' => $items ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Státuszonkénti darabszám.
	 *
	 * @return array
	 */
	public static function counts() {
		global $wpdb;

		$table = MBapp_Install::log_table();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status" );

		$counts = array( 'all' => 0 );

		foreach ( (array) $rows as $row ) {
			$counts[ $row->status ] = (int) $row->total;
			$counts['all']         += (int) $row->total;
		}

		return $counts;
	}

	/**
	 * Az utolsó futás azonosítója.
	 *
	 * @return string
	 */
	public static function last_run_id() {
		global $wpdb;

		$table = MBapp_Install::log_table();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return (string) $wpdb->get_var( "SELECT run_id FROM {$table} ORDER BY id DESC LIMIT 1" );
	}

	/**
	 * Sorok törlése azonosító alapján.
	 *
	 * @param array $ids Azonosítók.
	 * @return int Törölt sorok száma.
	 */
	public static function delete( array $ids ) {
		global $wpdb;

		$ids = array_filter( array_map( 'absint', $ids ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		$table        = MBapp_Install::log_table();
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", $ids ) );
	}

	/**
	 * Teljes napló ürítése.
	 *
	 * @return int
	 */
	public static function clear() {
		global $wpdb;

		$table = MBapp_Install::log_table();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->query( "DELETE FROM {$table}" );
	}

	/**
	 * Régi naplósorok törlése.
	 *
	 * @param int $days Ennyi napnál régebbi sorok törlése.
	 * @return int
	 */
	public static function prune( $days ) {
		global $wpdb;

		$days = (int) $days;

		if ( $days < 1 ) {
			return 0;
		}

		$table  = MBapp_Install::log_table();
		$cutoff = gmdate( 'Y-m-d H:i:s', mbapp_now() - ( $days * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) );
	}
}
