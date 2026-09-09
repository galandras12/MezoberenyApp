<?php
/**
 * AJAX végpontok.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * AJAX kezelő.
 */
class MBapp_Ajax {

	/**
	 * Konstruktor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_mbapp_load_events', array( $this, 'load_events' ) );
		add_action( 'wp_ajax_nopriv_mbapp_load_events', array( $this, 'load_events' ) );

		add_action( 'wp_ajax_mbapp_run_import', array( $this, 'run_import' ) );
		add_action( 'wp_ajax_mbapp_preview_source', array( $this, 'preview_source' ) );
	}

	/**
	 * További események betöltése.
	 */
	public function load_events() {
		check_ajax_referer( 'mbapp_public', 'nonce' );

		$page     = isset( $_POST['page'] ) ? max( 2, absint( $_POST['page'] ) ) : 2;
		$per_page = isset( $_POST['per_page'] ) ? max( 1, absint( $_POST['per_page'] ) ) : 25;
		$layout   = isset( $_POST['layout'] ) ? sanitize_key( wp_unslash( $_POST['layout'] ) ) : 'grid';
		$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';

		if ( ! in_array( $layout, array( 'grid', 'list' ), true ) ) {
			$layout = 'grid';
		}

		$query = MBapp_Events::query_upcoming(
			array(
				'per_page' => $per_page,
				'page'     => $page,
				'category' => $category,
			)
		);

		$html = '';

		while ( $query->have_posts() ) {
			$query->the_post();
			$html .= MBapp_Shortcodes::render_event( get_the_ID(), $layout, false );
		}

		wp_reset_postdata();

		wp_send_json_success(
			array(
				'html'     => $html,
				'page'     => $page,
				'has_more' => $page < (int) $query->max_num_pages,
			)
		);
	}

	/**
	 * Hírimport indítása az admin felületről.
	 */
	public function run_import() {
		check_ajax_referer( 'mbapp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Nincs jogosultságod ehhez.', 'mbapp' ) ), 403 );
		}

		$importer = new MBapp_News_Importer();
		$summary  = $importer->run( true );

		wp_send_json_success( $summary );
	}

	/**
	 * Forrás próbalekérése (szelektorok hangolásához).
	 */
	public function preview_source() {
		check_ajax_referer( 'mbapp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Nincs jogosultságod ehhez.', 'mbapp' ) ), 403 );
		}

		$overrides = array();

		$fields = array(
			'source_url',
			'source_type',
			'item_selector',
			'title_selector',
			'link_selector',
			'image_selector',
			'excerpt_selector',
			'date_selector',
		);

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$overrides[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			}
		}

		if ( isset( $overrides['source_url'] ) ) {
			$overrides['source_url'] = esc_url_raw( $overrides['source_url'] );
		}

		// A próbalekéréshez nem töltjük le a teljes cikkeket.
		$overrides['fetch_full_content'] = 0;

		$importer = new MBapp_News_Importer( $overrides );
		$items    = $importer->preview( isset( $overrides['source_url'] ) ? $overrides['source_url'] : '' );

		if ( is_wp_error( $items ) ) {
			wp_send_json_error( array( 'message' => $items->get_error_message() ) );
		}

		$rows = array();

		foreach ( $items as $item ) {
			$rows[] = array(
				'title'   => $item['title'],
				'link'    => $item['link'],
				'image'   => $item['image'],
				'excerpt' => $item['excerpt'],
				'date'    => $item['date'] ? mbapp_format_timestamp( $item['date'], 'Y-m-d H:i' ) : '',
			);
		}

		wp_send_json_success(
			array(
				'count' => count( $rows ),
				'items' => $rows,
			)
		);
	}
}
