<?php
/**
 * Egyedi bejegyzéstípusok.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hírek és események bejegyzéstípusok.
 */
class MBapp_Post_Types {

	/**
	 * Konstruktor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Regisztráció.
	 */
	public function register() {
		$this->register_news();
		$this->register_events();
		$this->register_taxonomies();
	}

	/**
	 * Hírek.
	 */
	private function register_news() {
		register_post_type(
			MBAPP_CPT_NEWS,
			array(
				'labels'             => array(
					'name'               => __( 'Hírek', 'mbapp' ),
					'singular_name'      => __( 'Hír', 'mbapp' ),
					'add_new'            => __( 'Új hír', 'mbapp' ),
					'add_new_item'       => __( 'Új hír hozzáadása', 'mbapp' ),
					'edit_item'          => __( 'Hír szerkesztése', 'mbapp' ),
					'new_item'           => __( 'Új hír', 'mbapp' ),
					'view_item'          => __( 'Hír megtekintése', 'mbapp' ),
					'search_items'       => __( 'Hírek keresése', 'mbapp' ),
					'not_found'          => __( 'Nem található hír', 'mbapp' ),
					'not_found_in_trash' => __( 'A lomtárban nincs hír', 'mbapp' ),
					'all_items'          => __( 'Összes hír', 'mbapp' ),
					'menu_name'          => __( 'Hírek', 'mbapp' ),
				),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => 'mbapp',
				'show_in_rest'       => true,
				'has_archive'        => 'hirek',
				'rewrite'            => array(
					'slug'       => 'hirek',
					'with_front' => false,
				),
				'menu_icon'          => 'dashicons-megaphone',
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
				'taxonomies'         => array( 'mbapp_news_category' ),
			)
		);
	}

	/**
	 * Események.
	 */
	private function register_events() {
		register_post_type(
			MBAPP_CPT_EVENT,
			array(
				'labels'             => array(
					'name'               => __( 'Események', 'mbapp' ),
					'singular_name'      => __( 'Esemény', 'mbapp' ),
					'add_new'            => __( 'Új esemény', 'mbapp' ),
					'add_new_item'       => __( 'Új esemény hozzáadása', 'mbapp' ),
					'edit_item'          => __( 'Esemény szerkesztése', 'mbapp' ),
					'new_item'           => __( 'Új esemény', 'mbapp' ),
					'view_item'          => __( 'Esemény megtekintése', 'mbapp' ),
					'search_items'       => __( 'Események keresése', 'mbapp' ),
					'not_found'          => __( 'Nem található esemény', 'mbapp' ),
					'not_found_in_trash' => __( 'A lomtárban nincs esemény', 'mbapp' ),
					'all_items'          => __( 'Összes esemény', 'mbapp' ),
					'menu_name'          => __( 'Események', 'mbapp' ),
				),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => 'mbapp',
				'show_in_rest'       => true,
				'has_archive'        => false,
				'rewrite'            => array(
					'slug'       => 'esemeny',
					'with_front' => false,
				),
				'menu_icon'          => 'dashicons-calendar-alt',
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
				'taxonomies'         => array( 'mbapp_event_category' ),
			)
		);
	}

	/**
	 * Kategóriák.
	 */
	private function register_taxonomies() {
		register_taxonomy(
			'mbapp_news_category',
			array( MBAPP_CPT_NEWS ),
			array(
				'labels'            => array(
					'name'          => __( 'Hír kategóriák', 'mbapp' ),
					'singular_name' => __( 'Hír kategória', 'mbapp' ),
					'menu_name'     => __( 'Hír kategóriák', 'mbapp' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'hir-kategoria' ),
			)
		);

		register_taxonomy(
			'mbapp_event_category',
			array( MBAPP_CPT_EVENT ),
			array(
				'labels'            => array(
					'name'          => __( 'Esemény kategóriák', 'mbapp' ),
					'singular_name' => __( 'Esemény kategória', 'mbapp' ),
					'menu_name'     => __( 'Esemény kategóriák', 'mbapp' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'esemeny-kategoria' ),
			)
		);
	}
}
