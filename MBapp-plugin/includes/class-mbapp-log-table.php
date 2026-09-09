<?php
/**
 * Import napló táblázat.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Napló lista.
 */
class MBapp_Log_Table extends WP_List_Table {

	/**
	 * Státuszonkénti darabszám.
	 *
	 * @var array
	 */
	private $counts = array();

	/**
	 * Konstruktor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'mbapp_log',
				'plural'   => 'mbapp_logs',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Oszlopok.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox">',
			'created_at'  => __( 'Időpont', 'mbapp' ),
			'status'      => __( 'Állapot', 'mbapp' ),
			'title'       => __( 'Cikk', 'mbapp' ),
			'image'       => __( 'Kép', 'mbapp' ),
			'source_url'  => __( 'Forrás', 'mbapp' ),
			'message'     => __( 'Megjegyzés', 'mbapp' ),
		);
	}

	/**
	 * Rendezhető oszlopok.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'created_at' => array( 'created_at', true ),
			'status'     => array( 'status', false ),
			'title'      => array( 'title', false ),
		);
	}

	/**
	 * Nézetek (státusz szűrők).
	 *
	 * @return array
	 */
	protected function get_views() {
		$counts  = $this->counts;
		$current = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$base    = admin_url( 'admin.php?page=mbapp-log' );

		$views = array(
			'all' => sprintf(
				'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$d)</span></a>',
				esc_url( $base ),
				'' === $current ? 'current' : '',
				esc_html__( 'Összes', 'mbapp' ),
				isset( $counts['all'] ) ? (int) $counts['all'] : 0
			),
		);

		foreach ( MBapp_Logger::statuses() as $key => $label ) {
			if ( empty( $counts[ $key ] ) ) {
				continue;
			}

			$views[ $key ] = sprintf(
				'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$d)</span></a>',
				esc_url( add_query_arg( 'status', $key, $base ) ),
				$current === $key ? 'current' : '',
				esc_html( $label ),
				(int) $counts[ $key ]
			);
		}

		return $views;
	}

	/**
	 * Tömeges műveletek.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'delete' => __( 'Törlés', 'mbapp' ),
		);
	}

	/**
	 * Tömeges művelet feldolgozása.
	 */
	private function process_bulk_action() {
		if ( 'delete' !== $this->current_action() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ids = isset( $_REQUEST['log'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['log'] ) ) : array();

		if ( $ids ) {
			MBapp_Logger::delete( $ids );
		}
	}

	/**
	 * Elemek előkészítése.
	 */
	public function prepare_items() {
		$this->counts = MBapp_Logger::counts();

		$this->process_bulk_action();

		$per_page = 25;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$args = array(
			'status'   => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
			'search'   => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'run_id'   => isset( $_GET['run_id'] ) ? sanitize_text_field( wp_unslash( $_GET['run_id'] ) ) : '',
			'orderby'  => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at',
			'order'    => isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'desc',
			'page'     => $this->get_pagenum(),
			'per_page' => $per_page,
		);
		// phpcs:enable

		$result = MBapp_Logger::query( $args );

		$this->items = $result['items'];

		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $result['total'] / $per_page ),
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
	}

	/**
	 * Jelölőnégyzet oszlop.
	 *
	 * @param object $item Sor.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="log[]" value="%d">', (int) $item->id );
	}

	/**
	 * Időpont.
	 *
	 * @param object $item Sor.
	 * @return string
	 */
	public function column_created_at( $item ) {
		$delete_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'             => 'mbapp-log',
					'mbapp_delete_log' => (int) $item->id,
				),
				admin_url( 'admin.php' )
			),
			'mbapp_delete_log_' . (int) $item->id
		);

		$actions = array(
			'run'    => sprintf(
				'<a href="%s">%s</a>',
				esc_url( add_query_arg( array( 'page' => 'mbapp-log', 'run_id' => $item->run_id ), admin_url( 'admin.php' ) ) ),
				esc_html__( 'Ez a futás', 'mbapp' )
			),
			'delete' => sprintf(
				'<a href="%s" class="submitdelete">%s</a>',
				esc_url( $delete_url ),
				esc_html__( 'Törlés', 'mbapp' )
			),
		);

		return sprintf(
			'<strong>%1$s</strong>%2$s',
			esc_html( mysql2date( 'Y-m-d H:i:s', $item->created_at ) ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Állapot.
	 *
	 * @param object $item Sor.
	 * @return string
	 */
	public function column_status( $item ) {
		$statuses = MBapp_Logger::statuses();
		$label    = isset( $statuses[ $item->status ] ) ? $statuses[ $item->status ] : $item->status;

		return sprintf(
			'<span class="mbapp-pill mbapp-pill--%1$s">%2$s</span>',
			esc_attr( $item->status ),
			esc_html( $label )
		);
	}

	/**
	 * Cikk címe.
	 *
	 * @param object $item Sor.
	 * @return string
	 */
	public function column_title( $item ) {
		$title = $item->title ? $item->title : '—';

		if ( $item->post_id && get_post( $item->post_id ) ) {
			return sprintf(
				'<a href="%1$s"><strong>%2$s</strong></a><br><span class="description">#%3$d</span>',
				esc_url( get_edit_post_link( $item->post_id ) ),
				esc_html( $title ),
				(int) $item->post_id
			);
		}

		return esc_html( $title );
	}

	/**
	 * Kép.
	 *
	 * @param object $item Sor.
	 * @return string
	 */
	public function column_image( $item ) {
		if ( $item->post_id && has_post_thumbnail( $item->post_id ) ) {
			return get_the_post_thumbnail( $item->post_id, array( 60, 60 ), array( 'class' => 'mbapp-log-thumb' ) );
		}

		if ( $item->image_url ) {
			return sprintf(
				'<a href="%1$s" target="_blank" rel="noopener">%2$s</a>',
				esc_url( $item->image_url ),
				esc_html__( 'kép', 'mbapp' )
			);
		}

		return '—';
	}

	/**
	 * Forrás.
	 *
	 * @param object $item Sor.
	 * @return string
	 */
	public function column_source_url( $item ) {
		if ( ! $item->source_url ) {
			return '—';
		}

		return sprintf(
			'<a href="%1$s" target="_blank" rel="noopener">%2$s</a>',
			esc_url( $item->source_url ),
			esc_html( wp_trim_words( str_replace( array( 'https://', 'http://' ), '', $item->source_url ), 8, '…' ) )
		);
	}

	/**
	 * Megjegyzés.
	 *
	 * @param object $item Sor.
	 * @return string
	 */
	public function column_message( $item ) {
		$out = esc_html( (string) $item->message );

		if ( $item->source_date ) {
			$out .= sprintf(
				'<br><span class="description">%s %s</span>',
				esc_html__( 'Eredeti dátum:', 'mbapp' ),
				esc_html( mysql2date( 'Y-m-d H:i', $item->source_date ) )
			);
		}

		return $out;
	}

	/**
	 * Alapértelmezett oszlop.
	 *
	 * @param object $item   Sor.
	 * @param string $column Oszlop.
	 * @return string
	 */
	public function column_default( $item, $column ) {
		return isset( $item->{$column} ) ? esc_html( $item->{$column} ) : '';
	}

	/**
	 * Üres állapot.
	 */
	public function no_items() {
		esc_html_e( 'Még nincs naplóbejegyzés. Indíts egy importot a Hírbeolvasó oldalon!', 'mbapp' );
	}
}
