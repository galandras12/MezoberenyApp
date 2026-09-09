<?php
/**
 * Admin felület.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin.
 */
class MBapp_Admin {

	/**
	 * Konstruktor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menus' ) );
		add_action( 'admin_init', array( $this, 'handle_forms' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . MBAPP_PLUGIN_BASENAME, array( $this, 'action_links' ) );
		add_action( 'wp_ajax_mbapp_save_settings', array( $this, 'ajax_save' ) );
	}

	/**
	 * Menük.
	 */
	public function menus() {
		add_menu_page(
			__( 'MBapp', 'mbapp' ),
			__( 'MBapp', 'mbapp' ),
			'manage_options',
			'mbapp',
			array( $this, 'render_dashboard' ),
			'dashicons-smartphone',
			26
		);

		add_submenu_page(
			'mbapp',
			__( 'Áttekintés', 'mbapp' ),
			__( 'Áttekintés', 'mbapp' ),
			'manage_options',
			'mbapp',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'mbapp',
			__( 'Hírbeolvasó', 'mbapp' ),
			__( 'Hírbeolvasó', 'mbapp' ),
			'manage_options',
			'mbapp-news',
			array( $this, 'render_news' )
		);

		add_submenu_page(
			'mbapp',
			__( 'Import napló', 'mbapp' ),
			__( 'Import napló', 'mbapp' ),
			'manage_options',
			'mbapp-log',
			array( $this, 'render_log' )
		);

		add_submenu_page(
			'mbapp',
			__( 'Esemény beállítások', 'mbapp' ),
			__( 'Esemény beállítások', 'mbapp' ),
			'manage_options',
			'mbapp-events',
			array( $this, 'render_events' )
		);

		add_submenu_page(
			'mbapp',
			__( 'Lebegő menü', 'mbapp' ),
			__( 'Lebegő menü', 'mbapp' ),
			'manage_options',
			'mbapp-menu',
			array( $this, 'render_menu' )
		);
	}

	/**
	 * Gyors linkek a bővítmények listájában.
	 *
	 * @param array $links Linkek.
	 * @return array
	 */
	public function action_links( $links ) {
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=mbapp' ) ),
				esc_html__( 'Beállítások', 'mbapp' )
			)
		);

		return $links;
	}

	/**
	 * Admin eszközök.
	 *
	 * @param string $hook Az aktuális oldal.
	 */
	public function enqueue( $hook ) {
		$is_mbapp_page = false !== strpos( $hook, 'mbapp' );
		$screen        = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && in_array( $screen->post_type, array( MBAPP_CPT_EVENT, MBAPP_CPT_NEWS ), true ) ) {
			$is_mbapp_page = true;
		}

		if ( ! $is_mbapp_page ) {
			return;
		}

		wp_enqueue_style(
			'mbapp-admin',
			MBAPP_PLUGIN_URL . 'assets/css/mbapp-admin.css',
			array(),
			MBAPP_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'mbapp-admin',
			MBAPP_PLUGIN_URL . 'assets/js/mbapp-admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			MBAPP_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'mbapp-admin',
			'MBAppAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mbapp_admin' ),
				'icons'   => array_keys( mbapp_icon_choices() ),
				'i18n'    => array(
					'running'   => __( 'Import fut…', 'mbapp' ),
					'testing'   => __( 'Lekérés folyamatban…', 'mbapp' ),
					'error'     => __( 'Hiba történt.', 'mbapp' ),
					'confirm'   => __( 'Biztosan törlöd?', 'mbapp' ),
					'noResults' => __( 'A megadott szelektorokkal nem találtunk hírt. Próbálj más szelektort!', 'mbapp' ),
					'found'     => __( 'Talált elem:', 'mbapp' ),
					'detecting' => __( 'Felderítés folyamatban… ez akár fél percig is tarthat.', 'mbapp' ),
					'apply'     => __( 'Ezt használom', 'mbapp' ),
					'applied'   => __( 'A szelektorok kitöltve. Ellenőrizd a „Próbalekérés” gombbal, majd mentsd el!', 'mbapp' ),
					'useFeed'   => __( 'Beállítom forrásnak', 'mbapp' ),
					'saving'    => __( 'Mentés…', 'mbapp' ),
					'saved'     => __( 'Elmentve', 'mbapp' ),
					'saveError' => __( 'A mentés nem sikerült.', 'mbapp' ),
				),
			)
		);
	}

	/* =====================================================================
	 * Űrlapok
	 * ===================================================================== */

	/**
	 * Beküldött űrlapok feldolgozása.
	 */
	public function handle_forms() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Az AJAX mentésnek saját végpontja van, itt nincs dolgunk.
		if ( wp_doing_ajax() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['mbapp_action'] ) ) {
			$this->handle_log_actions();

			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['mbapp_action'] ) );

		if ( ! isset( $_POST['mbapp_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mbapp_nonce'] ) ), 'mbapp_' . $action ) ) {
			wp_die( esc_html__( 'Érvénytelen kérés. Töltsd újra az oldalt.', 'mbapp' ) );
		}

		switch ( $action ) {
			case 'save_news':
				$this->save_news_settings();
				break;

			case 'save_events':
				$this->save_events_settings();
				break;

			case 'save_menu':
				$this->save_menu_settings();
				break;

			case 'run_import':
				$importer = new MBapp_News_Importer();
				$summary  = $importer->run( true );

				$this->redirect(
					'mbapp-news',
					array(
						'mbapp_msg' => 'imported',
						'imported'  => (int) $summary['imported'],
						'dupes'     => (int) $summary['duplicate'],
						'errors'    => (int) $summary['errors'],
					)
				);
				break;

			case 'clear_log':
				MBapp_Logger::clear();
				$this->redirect( 'mbapp-log', array( 'mbapp_msg' => 'log_cleared' ) );
				break;

			case 'create_page':
				MBapp_Install::create_events_page();
				$this->redirect( 'mbapp-events', array( 'mbapp_msg' => 'page_created' ) );
				break;

			case 'cleanup_events':
				$count = MBapp_Events::cleanup();
				$this->redirect(
					'mbapp-events',
					array(
						'mbapp_msg' => 'cleaned',
						'count'     => (int) $count,
					)
				);
				break;
		}
	}

	/**
	 * Napló sor törlése linkkel.
	 */
	private function handle_log_actions() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['mbapp_delete_log'] ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}

		$id = absint( $_GET['mbapp_delete_log'] );

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'mbapp_delete_log_' . $id ) ) {
			return;
		}

		MBapp_Logger::delete( array( $id ) );
		$this->redirect( 'mbapp-log', array( 'mbapp_msg' => 'log_deleted' ) );
	}

	/**
	 * Átirányítás.
	 *
	 * @param string $page Oldal.
	 * @param array  $args Paraméterek.
	 */
	private function redirect( $page, array $args = array() ) {
		$url = add_query_arg(
			array_merge( array( 'page' => $page ), $args ),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Hírbeolvasó beállítások mentése.
	 */
	private function save_news_settings() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$input = isset( $_POST['mbapp_news'] ) ? (array) wp_unslash( $_POST['mbapp_news'] ) : array();
		// phpcs:enable

		$clean = $this->sanitize_news( $input );

		MBapp_Settings::save( 'news', $clean );
		$this->apply_news_schedule( $clean );

		$this->redirect( 'mbapp-news', array( 'mbapp_msg' => 'saved' ) );
	}

	/**
	 * Hírbeolvasó beállítások fertőtlenítése.
	 *
	 * @param array $input Nyers értékek.
	 * @return array
	 */
	private function sanitize_news( array $input ) {
		$defaults = MBapp_Settings::defaults( 'news' );
		$clean    = array();

		$clean['enabled']            = ! empty( $input['enabled'] ) ? 1 : 0;
		$clean['source_url']         = esc_url_raw( trim( (string) ( $input['source_url'] ?? '' ) ) );
		$clean['source_type']        = in_array( $input['source_type'] ?? '', array( 'auto', 'rss', 'html', 'jsonld' ), true )
			? $input['source_type']
			: 'auto';

		foreach ( array( 'item_selector', 'title_selector', 'link_selector', 'image_selector', 'excerpt_selector', 'date_selector', 'content_selector' ) as $key ) {
			$clean[ $key ] = sanitize_text_field( (string) ( $input[ $key ] ?? $defaults[ $key ] ) );
		}

		$clean['max_items']          = min( 100, max( 1, absint( $input['max_items'] ?? 10 ) ) );
		$clean['fetch_full_content'] = ! empty( $input['fetch_full_content'] ) ? 1 : 0;
		$clean['import_images']      = ! empty( $input['import_images'] ) ? 1 : 0;
		$clean['append_source']      = ! empty( $input['append_source'] ) ? 1 : 0;

		$clean['post_status'] = in_array( $input['post_status'] ?? '', array( 'publish', 'draft', 'pending' ), true )
			? $input['post_status']
			: 'publish';

		$clean['interval'] = array_key_exists( $input['interval'] ?? '', MBapp_Settings::intervals() )
			? $input['interval']
			: 'mbapp_hourly';

		$clean['source_label']       = sanitize_text_field( (string) ( $input['source_label'] ?? $defaults['source_label'] ) );
		$clean['source_site_name']   = sanitize_text_field( (string) ( $input['source_site_name'] ?? '' ) );
		$clean['request_timeout']    = min( 120, max( 5, absint( $input['request_timeout'] ?? 20 ) ) );
		$clean['user_agent']         = sanitize_text_field( (string) ( $input['user_agent'] ?? '' ) );
		$clean['log_retention_days'] = min( 3650, max( 1, absint( $input['log_retention_days'] ?? 90 ) ) );

		return $clean;
	}

	/**
	 * Az importálás ütemezésének igazítása a mentett beállításhoz.
	 *
	 * @param array $clean Mentett beállítások.
	 */
	private function apply_news_schedule( array $clean ) {
		$timestamp = wp_next_scheduled( MBAPP_CRON_IMPORT );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, MBAPP_CRON_IMPORT );
		}

		if ( $clean['enabled'] && 'mbapp_manual' !== $clean['interval'] ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, $clean['interval'], MBAPP_CRON_IMPORT );
		}
	}

	/**
	 * Esemény beállítások mentése.
	 */
	private function save_events_settings() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$input = isset( $_POST['mbapp_events'] ) ? (array) wp_unslash( $_POST['mbapp_events'] ) : array();
		// phpcs:enable

		$clean = $this->sanitize_events( $input );

		MBapp_Settings::save( 'events', $clean );
		update_option( 'mbapp_events_page_id', $clean['page_id'] );

		$this->redirect( 'mbapp-events', array( 'mbapp_msg' => 'saved' ) );
	}

	/**
	 * Esemény beállítások fertőtlenítése.
	 *
	 * @param array $input Nyers értékek.
	 * @return array
	 */
	private function sanitize_events( array $input ) {
		$defaults = MBapp_Settings::defaults( 'events' );
		$clean    = array();

		$clean['layout']         = in_array( $input['layout'] ?? '', array( 'list', 'grid' ), true ) ? $input['layout'] : 'grid';
		$clean['per_page']       = min( 100, max( 1, absint( $input['per_page'] ?? 25 ) ) );
		$clean['past_days']      = min( 90, max( 1, absint( $input['past_days'] ?? 7 ) ) );
		$clean['auto_delete']    = ! empty( $input['auto_delete'] ) ? 1 : 0;
		$clean['delete_mode']    = in_array( $input['delete_mode'] ?? '', array( 'trash', 'delete' ), true ) ? $input['delete_mode'] : 'trash';
		$clean['require_source'] = ! empty( $input['require_source'] ) ? 1 : 0;
		$clean['show_countdown'] = ! empty( $input['show_countdown'] ) ? 1 : 0;
		$clean['show_past']      = ! empty( $input['show_past'] ) ? 1 : 0;
		$clean['default_source'] = esc_url_raw( trim( (string) ( $input['default_source'] ?? '' ) ) );
		$clean['source_label']   = sanitize_text_field( (string) ( $input['source_label'] ?? $defaults['source_label'] ) );
		$clean['date_format']    = sanitize_text_field( (string) ( $input['date_format'] ?? $defaults['date_format'] ) );
		$clean['page_id']        = absint( $input['page_id'] ?? 0 );

		return $clean;
	}

	/**
	 * Lebegő menü beállítások mentése.
	 */
	private function save_menu_settings() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$input = isset( $_POST['mbapp_menu'] ) ? (array) wp_unslash( $_POST['mbapp_menu'] ) : array();
		// phpcs:enable

		MBapp_Settings::save( 'menu', $this->sanitize_menu( $input ) );

		$this->redirect( 'mbapp-menu', array( 'mbapp_msg' => 'saved' ) );
	}

	/**
	 * Lebegő menü beállítások fertőtlenítése.
	 *
	 * @param array $input Nyers értékek.
	 * @return array
	 */
	private function sanitize_menu( array $input ) {
		$clean = array();

		$clean['enabled']     = ! empty( $input['enabled'] ) ? 1 : 0;
		$clean['position']    = in_array( $input['position'] ?? '', array( 'bottom', 'top', 'left', 'right' ), true )
			? $input['position']
			: 'bottom';
		$clean['show_labels'] = ! empty( $input['show_labels'] ) ? 1 : 0;
		$clean['autohide']    = ! empty( $input['autohide'] ) ? 1 : 0;
		$clean['style']       = in_array( $input['style'] ?? '', array( 'glass', 'solid' ), true ) ? $input['style'] : 'glass';

		// AJAX navigáció és animációk.
		$clean['ajax_nav']      = ! empty( $input['ajax_nav'] ) ? 1 : 0;
		$clean['ajax_scope']    = in_array( $input['ajax_scope'] ?? '', array( 'menu', 'all' ), true ) ? $input['ajax_scope'] : 'menu';
		$clean['anim_type']     = array_key_exists( $input['anim_type'] ?? '', MBapp_Settings::animations() ) ? $input['anim_type'] : 'slide';
		$clean['anim_duration'] = min( 900, max( 80, absint( $input['anim_duration'] ?? 280 ) ) );
		$clean['anim_easing']   = array_key_exists( $input['anim_easing'] ?? '', MBapp_Settings::easings() ) ? $input['anim_easing'] : 'ease-out';
		$clean['progress_bar']  = ! empty( $input['progress_bar'] ) ? 1 : 0;
		$clean['tap_effect']    = array_key_exists( $input['tap_effect'] ?? '', MBapp_Settings::tap_effects() ) ? $input['tap_effect'] : 'ripple';
		$clean['dock_anim']     = array_key_exists( $input['dock_anim'] ?? '', MBapp_Settings::dock_animations() ) ? $input['dock_anim'] : 'slide-up';

		$items    = isset( $input['items'] ) && is_array( $input['items'] ) ? $input['items'] : array();
		$icon_set = array_keys( mbapp_icon_choices() );
		$clean_items = array();

		foreach ( $items as $item ) {
			$label = sanitize_text_field( (string) ( $item['label'] ?? '' ) );
			$url   = trim( (string) ( $item['url'] ?? '' ) );

			if ( '' === $label && '' === $url ) {
				continue;
			}

			$icon_type = in_array( $item['icon_type'] ?? '', array( 'builtin', 'emoji', 'dashicon', 'image' ), true )
				? $item['icon_type']
				: 'builtin';

			// Beépített ikonnál a legördülő, egyébként a szabad szöveges mező értéke.
			$icon = 'builtin' === $icon_type
				? (string) ( $item['icon'] ?? '' )
				: (string) ( $item['icon_custom'] ?? '' );

			if ( 'builtin' === $icon_type ) {
				$icon = in_array( $icon, $icon_set, true ) ? $icon : 'info';
			} elseif ( 'image' === $icon_type ) {
				$icon = esc_url_raw( $icon );
			} else {
				$icon = sanitize_text_field( $icon );
			}

			// A belső hivatkozásokat relatívan is elfogadjuk.
			$url = preg_match( '~^(https?://|//|mailto:|tel:|\#)~i', $url )
				? esc_url_raw( $url )
				: sanitize_text_field( $url );

			$clean_items[] = array(
				'label'     => $label,
				'icon_type' => $icon_type,
				'icon'      => $icon,
				'url'       => $url,
				'target'    => '_blank' === ( $item['target'] ?? '' ) ? '_blank' : '_self',
				'highlight' => ! empty( $item['highlight'] ) ? 1 : 0,
				'visible'   => in_array( $item['visible'] ?? '', array( 'all', 'mobile', 'desktop' ), true )
					? $item['visible']
					: 'all',
				'enabled'   => ! empty( $item['enabled'] ) ? 1 : 0,
			);
		}

		$clean['items'] = $clean_items;

		return $clean;
	}

	/**
	 * AJAX mentés: az oldal nem töltődik újra, nem ugrik a tetejére.
	 */
	public function ajax_save() {
		check_ajax_referer( 'mbapp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Nincs jogosultságod ehhez.', 'mbapp' ) ), 403 );
		}

		$group = isset( $_POST['group'] ) ? sanitize_key( wp_unslash( $_POST['group'] ) ) : '';

		switch ( $group ) {
			case 'news':
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$input = isset( $_POST['mbapp_news'] ) ? (array) wp_unslash( $_POST['mbapp_news'] ) : array();
				$clean = $this->sanitize_news( $input );

				MBapp_Settings::save( 'news', $clean );
				$this->apply_news_schedule( $clean );
				break;

			case 'events':
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$input = isset( $_POST['mbapp_events'] ) ? (array) wp_unslash( $_POST['mbapp_events'] ) : array();
				$clean = $this->sanitize_events( $input );

				MBapp_Settings::save( 'events', $clean );
				update_option( 'mbapp_events_page_id', $clean['page_id'] );
				break;

			case 'menu':
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$input = isset( $_POST['mbapp_menu'] ) ? (array) wp_unslash( $_POST['mbapp_menu'] ) : array();
				$clean = $this->sanitize_menu( $input );

				MBapp_Settings::save( 'menu', $clean );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Ismeretlen beállításcsoport.', 'mbapp' ) ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Elmentve', 'mbapp' ),
				'time'    => wp_date( 'H:i:s' ),
			)
		);
	}

	/* =====================================================================
	 * Nézetek
	 * ===================================================================== */

	/**
	 * Visszajelző üzenetek.
	 */
	private function notices() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['mbapp_msg'] ) ) {
			return;
		}

		$msg = sanitize_key( wp_unslash( $_GET['mbapp_msg'] ) );

		switch ( $msg ) {
			case 'saved':
				$text = __( 'A beállítások elmentve.', 'mbapp' );
				break;

			case 'imported':
				$text = sprintf(
					/* translators: 1: új hírek, 2: már meglévők, 3: hibák */
					__( 'Az import lefutott. Új hír: %1$d, már megvolt: %2$d, hiba: %3$d.', 'mbapp' ),
					absint( $_GET['imported'] ?? 0 ),
					absint( $_GET['dupes'] ?? 0 ),
					absint( $_GET['errors'] ?? 0 )
				);
				break;

			case 'log_cleared':
				$text = __( 'A napló kiürítve.', 'mbapp' );
				break;

			case 'log_deleted':
				$text = __( 'A naplósor törölve.', 'mbapp' );
				break;

			case 'page_created':
				$text = __( 'Az „Események” oldal létrejött a shortcode-dal.', 'mbapp' );
				break;

			case 'cleaned':
				$text = sprintf(
					/* translators: %d: események száma */
					__( '%d lejárt esemény eltávolítva.', 'mbapp' ),
					absint( $_GET['count'] ?? 0 )
				);
				break;

			default:
				return;
		}
		// phpcs:enable

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( $text )
		);
	}

	/**
	 * Áttekintő oldal.
	 */
	public function render_dashboard() {
		$last_run = get_option( 'mbapp_news_last_run', array() );
		$counts   = MBapp_Logger::counts();
		$next     = MBapp_Cron::next_import();

		$upcoming = MBapp_Events::query_upcoming( array( 'per_page' => 1 ) );
		$news     = wp_count_posts( MBAPP_CPT_NEWS );
		$page_id  = (int) MBapp_Settings::get( 'events', 'page_id', 0 );
		?>
		<div class="wrap mbapp-admin">
			<h1><?php esc_html_e( 'MBapp – áttekintés', 'mbapp' ); ?></h1>
			<?php $this->notices(); ?>

			<div class="mbapp-cards">
				<div class="mbapp-adminbox">
					<h2><?php esc_html_e( 'Hírek', 'mbapp' ); ?></h2>
					<p class="mbapp-stat"><?php echo esc_html( isset( $news->publish ) ? (int) $news->publish : 0 ); ?></p>
					<p class="description"><?php esc_html_e( 'publikált hír az oldalon', 'mbapp' ); ?></p>
					<p>
						<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . MBAPP_CPT_NEWS ) ); ?>">
							<?php esc_html_e( 'Hírek kezelése', 'mbapp' ); ?>
						</a>
					</p>
				</div>

				<div class="mbapp-adminbox">
					<h2><?php esc_html_e( 'Közelgő események', 'mbapp' ); ?></h2>
					<p class="mbapp-stat"><?php echo esc_html( (int) $upcoming->found_posts ); ?></p>
					<p class="description"><?php esc_html_e( 'esemény vár még ránk', 'mbapp' ); ?></p>
					<p>
						<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . MBAPP_CPT_EVENT ) ); ?>">
							<?php esc_html_e( 'Események kezelése', 'mbapp' ); ?>
						</a>
					</p>
				</div>

				<div class="mbapp-adminbox">
					<h2><?php esc_html_e( 'Import napló', 'mbapp' ); ?></h2>
					<p class="mbapp-stat"><?php echo esc_html( isset( $counts['imported'] ) ? (int) $counts['imported'] : 0 ); ?></p>
					<p class="description"><?php esc_html_e( 'sikeresen beolvasott cikk összesen', 'mbapp' ); ?></p>
					<p>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mbapp-log' ) ); ?>">
							<?php esc_html_e( 'Napló megnyitása', 'mbapp' ); ?>
						</a>
					</p>
				</div>
			</div>

			<div class="mbapp-adminbox">
				<h2><?php esc_html_e( 'Automatikus hírbeolvasás', 'mbapp' ); ?></h2>
				<table class="widefat striped">
					<tbody>
						<tr>
							<th><?php esc_html_e( 'Állapot', 'mbapp' ); ?></th>
							<td>
								<?php if ( MBapp_Settings::get( 'news', 'enabled', 1 ) ) : ?>
									<span class="mbapp-pill mbapp-pill--ok"><?php esc_html_e( 'Bekapcsolva', 'mbapp' ); ?></span>
								<?php else : ?>
									<span class="mbapp-pill mbapp-pill--off"><?php esc_html_e( 'Kikapcsolva', 'mbapp' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Forrás', 'mbapp' ); ?></th>
							<td><code><?php echo esc_html( MBapp_Settings::get( 'news', 'source_url', '' ) ); ?></code></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Utolsó futás', 'mbapp' ); ?></th>
							<td>
								<?php
								if ( ! empty( $last_run['time'] ) ) {
									echo esc_html( mbapp_format_timestamp( (int) $last_run['time'], 'Y-m-d H:i' ) );

									if ( ! empty( $last_run['summary']['message'] ) ) {
										echo ' – ' . esc_html( $last_run['summary']['message'] );
									}
								} else {
									esc_html_e( 'Még nem futott.', 'mbapp' );
								}
								?>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Következő futás', 'mbapp' ); ?></th>
							<td>
								<?php
								echo $next
									? esc_html( mbapp_format_timestamp( $next, 'Y-m-d H:i' ) )
									: esc_html__( 'Nincs ütemezve (csak kézi indítás).', 'mbapp' );
								?>
							</td>
						</tr>
					</tbody>
				</table>

				<form method="post" style="margin-top:12px">
					<?php wp_nonce_field( 'mbapp_run_import', 'mbapp_nonce' ); ?>
					<input type="hidden" name="mbapp_action" value="run_import">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Import indítása most', 'mbapp' ); ?></button>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mbapp-news' ) ); ?>">
						<?php esc_html_e( 'Beállítások', 'mbapp' ); ?>
					</a>
				</form>
			</div>

			<div class="mbapp-adminbox">
				<h2><?php esc_html_e( 'Használat', 'mbapp' ); ?></h2>
				<p><?php esc_html_e( 'Az események megjelenítéséhez illeszd be ezt a shortcode-ot bármelyik oldalba:', 'mbapp' ); ?></p>
				<p><code>[mbapp_events]</code></p>
				<p><?php esc_html_e( 'Néhány további példa:', 'mbapp' ); ?></p>
				<ul class="mbapp-list">
					<li><code>[mbapp_events layout="list" limit="25"]</code> – <?php esc_html_e( 'lista nézet, 25 elem', 'mbapp' ); ?></li>
					<li><code>[mbapp_events layout="grid" past="no"]</code> – <?php esc_html_e( 'rács nézet, véget ért események nélkül', 'mbapp' ); ?></li>
					<li><code>[mbapp_news limit="6"]</code> – <?php esc_html_e( 'a 6 legfrissebb hír', 'mbapp' ); ?></li>
					<li><code>[mbapp_source url="https://mezobereny.hu/"]</code> – <?php esc_html_e( 'forrás gomb kézzel', 'mbapp' ); ?></li>
				</ul>

				<?php if ( $page_id ) : ?>
					<p>
						<?php esc_html_e( 'Az események oldal:', 'mbapp' ); ?>
						<a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>" target="_blank" rel="noopener">
							<?php echo esc_html( get_the_title( $page_id ) ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Hírbeolvasó beállítások.
	 */
	public function render_news() {
		$s = MBapp_Settings::all( 'news' );
		?>
		<div class="wrap mbapp-admin">
			<h1><?php esc_html_e( 'Hírbeolvasó', 'mbapp' ); ?></h1>
			<?php $this->notices(); ?>

			<p class="description">
				<?php esc_html_e( 'A bővítmény a megadott forrásoldalról olvassa be a híreket – címmel, szöveggel, képpel és dátummal együtt –, majd a tartalom végére illeszti a forrás gombot. Minden lépés naplózásra kerül.', 'mbapp' ); ?>
			</p>

			<form method="post" class="mbapp-form" data-mbapp-group="news">
				<?php wp_nonce_field( 'mbapp_save_news', 'mbapp_nonce' ); ?>
				<input type="hidden" name="mbapp_action" value="save_news">

				<h2 class="title"><?php esc_html_e( 'Forrás', 'mbapp' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Automatikus beolvasás', 'mbapp' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mbapp_news[enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?>>
								<?php esc_html_e( 'Bekapcsolva', 'mbapp' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_source_url"><?php esc_html_e( 'Forrás URL', 'mbapp' ); ?></label></th>
						<td>
							<input type="url" id="mbapp_source_url" name="mbapp_news[source_url]" class="regular-text code"
								value="<?php echo esc_attr( $s['source_url'] ); ?>" required>
							<p class="description"><?php esc_html_e( 'Például a hírek listaoldala vagy egy RSS csatorna címe.', 'mbapp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_source_type"><?php esc_html_e( 'Forrás típusa', 'mbapp' ); ?></label></th>
						<td>
							<select id="mbapp_source_type" name="mbapp_news[source_type]">
								<option value="auto" <?php selected( $s['source_type'], 'auto' ); ?>><?php esc_html_e( 'Automatikus felismerés (ajánlott)', 'mbapp' ); ?></option>
								<option value="rss" <?php selected( $s['source_type'], 'rss' ); ?>><?php esc_html_e( 'RSS / Atom csatorna', 'mbapp' ); ?></option>
								<option value="html" <?php selected( $s['source_type'], 'html' ); ?>><?php esc_html_e( 'HTML oldal (szelektorokkal)', 'mbapp' ); ?></option>
								<option value="jsonld" <?php selected( $s['source_type'], 'jsonld' ); ?>><?php esc_html_e( 'JSON-LD strukturált adat', 'mbapp' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Automatikus módban először RSS csatornát keresünk, majd a HTML szelektorokat próbáljuk, végül a JSON-LD strukturált adatot.', 'mbapp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_interval"><?php esc_html_e( 'Gyakoriság', 'mbapp' ); ?></label></th>
						<td>
							<select id="mbapp_interval" name="mbapp_news[interval]">
								<?php foreach ( MBapp_Settings::intervals() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['interval'], $key ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_max_items"><?php esc_html_e( 'Egy futásban legfeljebb', 'mbapp' ); ?></label></th>
						<td>
							<input type="number" id="mbapp_max_items" name="mbapp_news[max_items]" min="1" max="100" class="small-text"
								value="<?php echo esc_attr( $s['max_items'] ); ?>">
							<?php esc_html_e( 'hír', 'mbapp' ); ?>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Forrás felderítése', 'mbapp' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Ha nem tudod, milyen szelektort kell megadni, indítsd el a felderítést: a bővítmény letölti az oldalt, megnézi van-e RSS csatornája vagy JSON-LD adata, és megkeresi az ismétlődő hírblokkokat. A javaslatra kattintva a szelektorok automatikusan kitöltődnek.', 'mbapp' ); ?>
				</p>
				<p>
					<button type="button" class="button button-secondary" id="mbapp-detect">
						<?php esc_html_e( 'Szerkezet felismerése', 'mbapp' ); ?>
					</button>
					<span id="mbapp-detect-status"></span>
				</p>
				<div id="mbapp-detect-result"></div>

				<h2 class="title"><?php esc_html_e( 'HTML szelektorok', 'mbapp' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'CSS szelektorokat adhatsz meg (pl. article, .hir-lista .item, h2 a). Csak akkor számítanak, ha HTML oldalból olvassuk a híreket. A „Próbalekérés” gombbal azonnal ellenőrizheted, mit talál a beolvasó.', 'mbapp' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<?php
					$selector_fields = array(
						'item_selector'    => array( __( 'Hír elem', 'mbapp' ), __( 'Egy hír listaeleme, ebből indul minden más keresés.', 'mbapp' ) ),
						'title_selector'   => array( __( 'Cím', 'mbapp' ), __( 'A hír címe a listaelemen belül.', 'mbapp' ) ),
						'link_selector'    => array( __( 'Hivatkozás', 'mbapp' ), __( 'A cikk oldalára mutató link (href).', 'mbapp' ) ),
						'image_selector'   => array( __( 'Kép', 'mbapp' ), __( 'A borítókép (src, data-src vagy srcset).', 'mbapp' ) ),
						'excerpt_selector' => array( __( 'Kivonat', 'mbapp' ), __( 'Rövid bevezető szöveg.', 'mbapp' ) ),
						'date_selector'    => array( __( 'Dátum', 'mbapp' ), __( 'A megjelenés dátuma (time[datetime] vagy szöveg).', 'mbapp' ) ),
						'content_selector' => array( __( 'Teljes cikk törzse', 'mbapp' ), __( 'A cikk saját oldalán a tartalom befoglaló eleme.', 'mbapp' ) ),
					);

					foreach ( $selector_fields as $key => $meta ) :
						?>
						<tr>
							<th scope="row"><label for="mbapp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $meta[0] ); ?></label></th>
							<td>
								<input type="text" id="mbapp_<?php echo esc_attr( $key ); ?>" name="mbapp_news[<?php echo esc_attr( $key ); ?>]"
									class="regular-text code mbapp-selector" data-field="<?php echo esc_attr( $key ); ?>"
									value="<?php echo esc_attr( $s[ $key ] ); ?>">
								<p class="description"><?php echo esc_html( $meta[1] ); ?></p>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<p>
					<button type="button" class="button" id="mbapp-preview"><?php esc_html_e( 'Próbalekérés', 'mbapp' ); ?></button>
					<span id="mbapp-preview-status"></span>
				</p>
				<div id="mbapp-preview-result"></div>

				<h2 class="title"><?php esc_html_e( 'Import beállítások', 'mbapp' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Teljes cikk', 'mbapp' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mbapp_news[fetch_full_content]" value="1" <?php checked( $s['fetch_full_content'], 1 ); ?>>
								<?php esc_html_e( 'A cikk saját oldaláról töltse le a teljes szöveget', 'mbapp' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Képek', 'mbapp' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mbapp_news[import_images]" value="1" <?php checked( $s['import_images'], 1 ); ?>>
								<?php esc_html_e( 'A képet töltse le a médiatárba és állítsa be kiemelt képnek', 'mbapp' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_post_status"><?php esc_html_e( 'Új hírek állapota', 'mbapp' ); ?></label></th>
						<td>
							<select id="mbapp_post_status" name="mbapp_news[post_status]">
								<option value="publish" <?php selected( $s['post_status'], 'publish' ); ?>><?php esc_html_e( 'Azonnal publikálva', 'mbapp' ); ?></option>
								<option value="draft" <?php selected( $s['post_status'], 'draft' ); ?>><?php esc_html_e( 'Piszkozat (kézi jóváhagyással)', 'mbapp' ); ?></option>
								<option value="pending" <?php selected( $s['post_status'], 'pending' ); ?>><?php esc_html_e( 'Jóváhagyásra vár', 'mbapp' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Forrás gomb', 'mbapp' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mbapp_news[append_source]" value="1" <?php checked( $s['append_source'], 1 ); ?>>
								<?php esc_html_e( 'A tartalom végére illessze be a forrásra mutató gombot', 'mbapp' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_source_label"><?php esc_html_e( 'Gomb felirata', 'mbapp' ); ?></label></th>
						<td>
							<input type="text" id="mbapp_source_label" name="mbapp_news[source_label]" class="large-text"
								value="<?php echo esc_attr( $s['source_label'] ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_source_site_name"><?php esc_html_e( 'Forrás oldal neve', 'mbapp' ); ?></label></th>
						<td>
							<input type="text" id="mbapp_source_site_name" name="mbapp_news[source_site_name]" class="regular-text"
								value="<?php echo esc_attr( $s['source_site_name'] ); ?>">
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Haladó', 'mbapp' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="mbapp_request_timeout"><?php esc_html_e( 'Időtúllépés', 'mbapp' ); ?></label></th>
						<td>
							<input type="number" id="mbapp_request_timeout" name="mbapp_news[request_timeout]" min="5" max="120" class="small-text"
								value="<?php echo esc_attr( $s['request_timeout'] ); ?>"> <?php esc_html_e( 'másodperc', 'mbapp' ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_user_agent"><?php esc_html_e( 'Egyedi User-Agent', 'mbapp' ); ?></label></th>
						<td>
							<input type="text" id="mbapp_user_agent" name="mbapp_news[user_agent]" class="large-text code"
								value="<?php echo esc_attr( $s['user_agent'] ); ?>"
								placeholder="<?php esc_attr_e( 'üresen hagyva az alapértelmezettet használjuk', 'mbapp' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_log_retention_days"><?php esc_html_e( 'Napló megőrzése', 'mbapp' ); ?></label></th>
						<td>
							<input type="number" id="mbapp_log_retention_days" name="mbapp_news[log_retention_days]" min="1" max="3650" class="small-text"
								value="<?php echo esc_attr( $s['log_retention_days'] ); ?>"> <?php esc_html_e( 'napig', 'mbapp' ); ?>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Beállítások mentése', 'mbapp' ) ); ?>
			</form>

			<form method="post">
				<?php wp_nonce_field( 'mbapp_run_import', 'mbapp_nonce' ); ?>
				<input type="hidden" name="mbapp_action" value="run_import">
				<button type="submit" class="button button-secondary"><?php esc_html_e( 'Import indítása most', 'mbapp' ); ?></button>
			</form>
		</div>
		<?php
	}

	/**
	 * Napló oldal.
	 */
	public function render_log() {
		$table = new MBapp_Log_Table();
		$table->prepare_items();
		?>
		<div class="wrap mbapp-admin">
			<h1><?php esc_html_e( 'Import napló', 'mbapp' ); ?></h1>
			<?php $this->notices(); ?>

			<p class="description">
				<?php esc_html_e( 'Itt követheted nyomon, hogy mely cikkek kerültek be, mikor, milyen képpel és milyen forrásból.', 'mbapp' ); ?>
			</p>

			<form method="get">
				<input type="hidden" name="page" value="mbapp-log">
				<?php $table->search_box( __( 'Keresés a naplóban', 'mbapp' ), 'mbapp-log-search' ); ?>
			</form>

			<form method="get">
				<input type="hidden" name="page" value="mbapp-log">
				<?php
				$table->views();
				$table->display();
				?>
			</form>

			<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Biztosan törlöd a teljes naplót?', 'mbapp' ) ); ?>');">
				<?php wp_nonce_field( 'mbapp_clear_log', 'mbapp_nonce' ); ?>
				<input type="hidden" name="mbapp_action" value="clear_log">
				<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Teljes napló ürítése', 'mbapp' ); ?></button>
			</form>
		</div>
		<?php
	}

	/**
	 * Esemény beállítások.
	 */
	public function render_events() {
		$s = MBapp_Settings::all( 'events' );
		?>
		<div class="wrap mbapp-admin">
			<h1><?php esc_html_e( 'Esemény beállítások', 'mbapp' ); ?></h1>
			<?php $this->notices(); ?>

			<form method="post" class="mbapp-form" data-mbapp-group="events">
				<?php wp_nonce_field( 'mbapp_save_events', 'mbapp_nonce' ); ?>
				<input type="hidden" name="mbapp_action" value="save_events">

				<h2 class="title"><?php esc_html_e( 'Megjelenés', 'mbapp' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Alapértelmezett elrendezés', 'mbapp' ); ?></th>
						<td>
							<fieldset class="mbapp-layoutpick">
								<label class="mbapp-layoutpick__opt">
									<input type="radio" name="mbapp_events[layout]" value="grid" <?php checked( $s['layout'], 'grid' ); ?>>
									<span class="mbapp-layoutpick__preview mbapp-layoutpick__preview--grid" aria-hidden="true">
										<span></span><span></span><span></span><span></span>
									</span>
									<strong><?php esc_html_e( 'Rács (grid)', 'mbapp' ); ?></strong>
								</label>

								<label class="mbapp-layoutpick__opt">
									<input type="radio" name="mbapp_events[layout]" value="list" <?php checked( $s['layout'], 'list' ); ?>>
									<span class="mbapp-layoutpick__preview mbapp-layoutpick__preview--list" aria-hidden="true">
										<span></span><span></span><span></span>
									</span>
									<strong><?php esc_html_e( 'Felsorolás (lista)', 'mbapp' ); ?></strong>
								</label>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'A shortcode-ban felül lehet írni: [mbapp_events layout="list"].', 'mbapp' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_per_page"><?php esc_html_e( 'Elsőre megjelenő események', 'mbapp' ); ?></label></th>
						<td>
							<input type="number" id="mbapp_per_page" name="mbapp_events[per_page]" min="1" max="100" class="small-text"
								value="<?php echo esc_attr( $s['per_page'] ); ?>">
							<p class="description"><?php esc_html_e( 'A „További” gombbal ennyivel többet tölt be a látogató.', 'mbapp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Visszaszámláló', 'mbapp' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mbapp_events[show_countdown]" value="1" <?php checked( $s['show_countdown'], 1 ); ?>>
								<?php esc_html_e( 'Mutassa, hány nap van hátra a következő eseményig', 'mbapp' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_date_format"><?php esc_html_e( 'Dátum formátum', 'mbapp' ); ?></label></th>
						<td>
							<input type="text" id="mbapp_date_format" name="mbapp_events[date_format]" class="regular-text code"
								value="<?php echo esc_attr( $s['date_format'] ); ?>">
							<p class="description">
								<?php
								printf(
									/* translators: %s: dátum minta */
									esc_html__( 'Így fog kinézni: %s', 'mbapp' ),
									esc_html( wp_date( $s['date_format'] ) )
								);
								?>
							</p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Véget ért események', 'mbapp' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Külön lista', 'mbapp' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mbapp_events[show_past]" value="1" <?php checked( $s['show_past'], 1 ); ?>>
								<?php esc_html_e( 'A már lezajlott események kiszürkítve, külön listában jelenjenek meg', 'mbapp' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_past_days"><?php esc_html_e( 'Meddig maradjanak', 'mbapp' ); ?></label></th>
						<td>
							<input type="number" id="mbapp_past_days" name="mbapp_events[past_days]" min="1" max="90" class="small-text"
								value="<?php echo esc_attr( $s['past_days'] ); ?>"> <?php esc_html_e( 'napig', 'mbapp' ); ?>
							<p class="description"><?php esc_html_e( 'Ennyi idő után az esemény automatikusan eltűnik a listából.', 'mbapp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Automatikus törlés', 'mbapp' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mbapp_events[auto_delete]" value="1" <?php checked( $s['auto_delete'], 1 ); ?>>
								<?php esc_html_e( 'A türelmi idő lejárta után törölje az eseményeket', 'mbapp' ); ?>
							</label>
							<p style="margin-top:8px">
								<select name="mbapp_events[delete_mode]">
									<option value="trash" <?php selected( $s['delete_mode'], 'trash' ); ?>><?php esc_html_e( 'Lomtárba helyezés (visszaállítható)', 'mbapp' ); ?></option>
									<option value="delete" <?php selected( $s['delete_mode'], 'delete' ); ?>><?php esc_html_e( 'Végleges törlés', 'mbapp' ); ?></option>
								</select>
							</p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Forrás', 'mbapp' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Kötelező forrás', 'mbapp' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mbapp_events[require_source]" value="1" <?php checked( $s['require_source'], 1 ); ?>>
								<?php esc_html_e( 'Esemény csak forrás URL megadásával publikálható', 'mbapp' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_default_source"><?php esc_html_e( 'Alapértelmezett forrás', 'mbapp' ); ?></label></th>
						<td>
							<input type="url" id="mbapp_default_source" name="mbapp_events[default_source]" class="regular-text code"
								value="<?php echo esc_attr( $s['default_source'] ); ?>">
							<p class="description"><?php esc_html_e( 'Ha egy eseménynél nincs megadva forrás, ezt használjuk.', 'mbapp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_events_source_label"><?php esc_html_e( 'Gomb felirata', 'mbapp' ); ?></label></th>
						<td>
							<input type="text" id="mbapp_events_source_label" name="mbapp_events[source_label]" class="large-text"
								value="<?php echo esc_attr( $s['source_label'] ); ?>">
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Események oldal', 'mbapp' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="mbapp_page_id"><?php esc_html_e( 'Melyik oldal', 'mbapp' ); ?></label></th>
						<td>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => 'mbapp_events[page_id]',
									'id'                => 'mbapp_page_id',
									'selected'          => (int) $s['page_id'],
									'show_option_none'  => __( '— nincs kiválasztva —', 'mbapp' ),
									'option_none_value' => 0,
								)
							);
							?>
							<p class="description"><?php esc_html_e( 'Erre az oldalra mutat a téma „Összes esemény” linkje.', 'mbapp' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Beállítások mentése', 'mbapp' ) ); ?>
			</form>

			<div class="mbapp-adminbox">
				<h2><?php esc_html_e( 'Karbantartás', 'mbapp' ); ?></h2>
				<div class="mbapp-actions">
				<form method="post" style="display:inline-block;margin-right:8px">
					<?php wp_nonce_field( 'mbapp_create_page', 'mbapp_nonce' ); ?>
					<input type="hidden" name="mbapp_action" value="create_page">
					<button type="submit" class="button"><?php esc_html_e( '„Események” oldal létrehozása', 'mbapp' ); ?></button>
				</form>

				<form method="post" style="display:inline-block">
					<?php wp_nonce_field( 'mbapp_cleanup_events', 'mbapp_nonce' ); ?>
					<input type="hidden" name="mbapp_action" value="cleanup_events">
					<button type="submit" class="button"><?php esc_html_e( 'Lejárt események takarítása most', 'mbapp' ); ?></button>
				</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Lebegő menü beállítások.
	 */
	public function render_menu() {
		$s     = MBapp_Settings::all( 'menu' );
		$items = ! empty( $s['items'] ) ? $s['items'] : array();
		?>
		<div class="wrap mbapp-admin">
			<h1><?php esc_html_e( 'Lebegő menü', 'mbapp' ); ?></h1>
			<?php $this->notices(); ?>

			<p class="description">
				<?php esc_html_e( 'Itt állítod be, hogyan néz ki a webapp lebegő menüje: milyen gombok legyenek benne, milyen ikonnal és milyen URL-re mutassanak.', 'mbapp' ); ?>
			</p>

			<form method="post" class="mbapp-form" id="mbapp-menu-form" data-mbapp-group="menu">
				<?php wp_nonce_field( 'mbapp_save_menu', 'mbapp_nonce' ); ?>
				<input type="hidden" name="mbapp_action" value="save_menu">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Lebegő menü', 'mbapp' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mbapp_menu[enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?>>
								<?php esc_html_e( 'Megjelenítés az oldalon', 'mbapp' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_menu_position"><?php esc_html_e( 'Pozíció', 'mbapp' ); ?></label></th>
						<td>
							<select id="mbapp_menu_position" name="mbapp_menu[position]">
								<option value="bottom" <?php selected( $s['position'], 'bottom' ); ?>><?php esc_html_e( 'Alul (mobil app stílus)', 'mbapp' ); ?></option>
								<option value="top" <?php selected( $s['position'], 'top' ); ?>><?php esc_html_e( 'Felül', 'mbapp' ); ?></option>
								<option value="left" <?php selected( $s['position'], 'left' ); ?>><?php esc_html_e( 'Bal oldalon', 'mbapp' ); ?></option>
								<option value="right" <?php selected( $s['position'], 'right' ); ?>><?php esc_html_e( 'Jobb oldalon', 'mbapp' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_menu_style"><?php esc_html_e( 'Stílus', 'mbapp' ); ?></label></th>
						<td>
							<select id="mbapp_menu_style" name="mbapp_menu[style]">
								<option value="glass" <?php selected( $s['style'], 'glass' ); ?>><?php esc_html_e( 'Áttetsző (üveg hatás)', 'mbapp' ); ?></option>
								<option value="solid" <?php selected( $s['style'], 'solid' ); ?>><?php esc_html_e( 'Tömör háttér', 'mbapp' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Feliratok', 'mbapp' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mbapp_menu[show_labels]" value="1" <?php checked( $s['show_labels'], 1 ); ?>>
								<?php esc_html_e( 'Az ikon alatt jelenjen meg a felirat is', 'mbapp' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Görgetés', 'mbapp' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mbapp_menu[autohide]" value="1" <?php checked( $s['autohide'], 1 ); ?>>
								<?php esc_html_e( 'Lefelé görgetéskor rejtse el a menüt (mobilon)', 'mbapp' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'AJAX navigáció és animációk', 'mbapp' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Bekapcsolva az oldalak újratöltés nélkül, animálva váltanak – ettől viselkedik a felület igazi mobilalkalmazásként.', 'mbapp' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'AJAX oldalváltás', 'mbapp' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mbapp_menu[ajax_nav]" value="1" <?php checked( $s['ajax_nav'], 1 ); ?>>
								<?php esc_html_e( 'Az oldalak újratöltés nélkül töltődjenek be', 'mbapp' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Ha a böngésző nem támogatja, vagy hiba történik, a felület automatikusan a hagyományos oldalbetöltésre vált vissza.', 'mbapp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_ajax_scope"><?php esc_html_e( 'Mire vonatkozzon', 'mbapp' ); ?></label></th>
						<td>
							<select id="mbapp_ajax_scope" name="mbapp_menu[ajax_scope]">
								<option value="menu" <?php selected( $s['ajax_scope'], 'menu' ); ?>><?php esc_html_e( 'Csak a lebegő menü gombjaira', 'mbapp' ); ?></option>
								<option value="all" <?php selected( $s['ajax_scope'], 'all' ); ?>><?php esc_html_e( 'Minden oldalon belüli hivatkozásra', 'mbapp' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_anim_type"><?php esc_html_e( 'Oldalátmenet', 'mbapp' ); ?></label></th>
						<td>
							<select id="mbapp_anim_type" name="mbapp_menu[anim_type]" class="mbapp-anim-control">
								<?php foreach ( MBapp_Settings::animations() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['anim_type'], $key ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_anim_duration"><?php esc_html_e( 'Animáció hossza', 'mbapp' ); ?></label></th>
						<td>
							<input type="number" id="mbapp_anim_duration" name="mbapp_menu[anim_duration]" class="small-text mbapp-anim-control"
								min="80" max="900" step="10" value="<?php echo esc_attr( $s['anim_duration'] ); ?>">
							<?php esc_html_e( 'ezredmásodperc', 'mbapp' ); ?>
							<p class="description"><?php esc_html_e( '200–350 ezredmásodperc között érzi a legtöbb ember gyorsnak és mégis simának.', 'mbapp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_anim_easing"><?php esc_html_e( 'Időzítési görbe', 'mbapp' ); ?></label></th>
						<td>
							<select id="mbapp_anim_easing" name="mbapp_menu[anim_easing]" class="mbapp-anim-control">
								<?php foreach ( MBapp_Settings::easings() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['anim_easing'], $key ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Próba', 'mbapp' ); ?></th>
						<td>
							<div class="mbapp-anim-demo">
								<div class="mbapp-anim-demo__screen">
									<div class="mbapp-anim-demo__page" id="mbapp-anim-demo-page">
										<span class="mbapp-anim-demo__bar"></span>
										<span class="mbapp-anim-demo__bar mbapp-anim-demo__bar--short"></span>
										<span class="mbapp-anim-demo__block"></span>
									</div>
								</div>
								<button type="button" class="button" id="mbapp-anim-preview"><?php esc_html_e( 'Animáció lejátszása', 'mbapp' ); ?></button>
							</div>
							<p class="description"><?php esc_html_e( 'Így fog váltani a tartalom a beállított értékekkel.', 'mbapp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Betöltésjelző', 'mbapp' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mbapp_menu[progress_bar]" value="1" <?php checked( $s['progress_bar'], 1 ); ?>>
								<?php esc_html_e( 'Vékony folyamatjelző csík a képernyő tetején betöltés közben', 'mbapp' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_tap_effect"><?php esc_html_e( 'Gombnyomás visszajelzése', 'mbapp' ); ?></label></th>
						<td>
							<select id="mbapp_tap_effect" name="mbapp_menu[tap_effect]">
								<?php foreach ( MBapp_Settings::tap_effects() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['tap_effect'], $key ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mbapp_dock_anim"><?php esc_html_e( 'A menü megjelenése', 'mbapp' ); ?></label></th>
						<td>
							<select id="mbapp_dock_anim" name="mbapp_menu[dock_anim]">
								<?php foreach ( MBapp_Settings::dock_animations() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['dock_anim'], $key ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Menüpontok', 'mbapp' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Az URL lehet teljes cím (https://…) vagy az oldalon belüli útvonal (pl. /esemenyek/). A kiemelt gomb középen, nagyobb méretben jelenik meg.', 'mbapp' ); ?>
				</p>

				<div id="mbapp-menu-items" class="mbapp-items">
					<?php
					$index = 0;

					foreach ( $items as $item ) {
						$this->render_menu_item_row( $index, $item );
						$index++;
					}
					?>
				</div>

				<p>
					<button type="button" class="button" id="mbapp-add-item"><?php esc_html_e( '+ Új menüpont', 'mbapp' ); ?></button>
				</p>

				<?php submit_button( __( 'Menü mentése', 'mbapp' ) ); ?>
			</form>

			<script type="text/html" id="tmpl-mbapp-menu-item">
				<?php $this->render_menu_item_row( '__INDEX__', array() ); ?>
			</script>
		</div>
		<?php
	}

	/**
	 * Egy menüpont sor.
	 *
	 * @param int|string $index Index.
	 * @param array      $item  Menüpont.
	 */
	private function render_menu_item_row( $index, array $item ) {
		$item = wp_parse_args(
			$item,
			array(
				'label'     => '',
				'icon_type' => 'builtin',
				'icon'      => 'info',
				'url'       => '',
				'target'    => '_self',
				'highlight' => 0,
				'visible'   => 'all',
				'enabled'   => 1,
			)
		);

		$name = 'mbapp_menu[items][' . $index . ']';
		?>
		<div class="mbapp-item" data-index="<?php echo esc_attr( $index ); ?>">
			<div class="mbapp-item__handle" title="<?php esc_attr_e( 'Húzd a sorrend módosításához', 'mbapp' ); ?>">⠿</div>

			<div class="mbapp-item__preview">
				<span class="mbapp-item__icon"><?php echo mbapp_icon( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</div>

			<div class="mbapp-item__fields">
				<label class="mbapp-field">
					<span><?php esc_html_e( 'Felirat', 'mbapp' ); ?></span>
					<input type="text" name="<?php echo esc_attr( $name ); ?>[label]" value="<?php echo esc_attr( $item['label'] ); ?>">
				</label>

				<label class="mbapp-field">
					<span><?php esc_html_e( 'URL', 'mbapp' ); ?></span>
					<input type="text" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_attr( $item['url'] ); ?>"
						placeholder="/esemenyek/">
				</label>

				<label class="mbapp-field mbapp-field--sm">
					<span><?php esc_html_e( 'Ikon típusa', 'mbapp' ); ?></span>
					<select name="<?php echo esc_attr( $name ); ?>[icon_type]" class="mbapp-icon-type">
						<option value="builtin" <?php selected( $item['icon_type'], 'builtin' ); ?>><?php esc_html_e( 'Beépített', 'mbapp' ); ?></option>
						<option value="emoji" <?php selected( $item['icon_type'], 'emoji' ); ?>><?php esc_html_e( 'Emoji', 'mbapp' ); ?></option>
						<option value="dashicon" <?php selected( $item['icon_type'], 'dashicon' ); ?>><?php esc_html_e( 'Dashicon', 'mbapp' ); ?></option>
						<option value="image" <?php selected( $item['icon_type'], 'image' ); ?>><?php esc_html_e( 'Kép URL', 'mbapp' ); ?></option>
					</select>
				</label>

				<label class="mbapp-field mbapp-field--sm mbapp-icon-builtin" <?php echo 'builtin' === $item['icon_type'] ? '' : 'style="display:none"'; ?>>
					<span><?php esc_html_e( 'Ikon', 'mbapp' ); ?></span>
					<select name="<?php echo esc_attr( $name ); ?>[icon]" class="mbapp-icon-select">
						<?php foreach ( mbapp_icon_choices() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $item['icon'], $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>

				<label class="mbapp-field mbapp-field--sm mbapp-icon-custom" <?php echo 'builtin' === $item['icon_type'] ? 'style="display:none"' : ''; ?>>
					<span><?php esc_html_e( 'Ikon értéke', 'mbapp' ); ?></span>
					<input type="text" name="<?php echo esc_attr( $name ); ?>[icon_custom]" value="<?php echo 'builtin' === $item['icon_type'] ? '' : esc_attr( $item['icon'] ); ?>"
						placeholder="🎉 / dashicons-tickets / https://…">
				</label>

				<label class="mbapp-field mbapp-field--sm">
					<span><?php esc_html_e( 'Megnyitás', 'mbapp' ); ?></span>
					<select name="<?php echo esc_attr( $name ); ?>[target]">
						<option value="_self" <?php selected( $item['target'], '_self' ); ?>><?php esc_html_e( 'Ugyanabban az ablakban', 'mbapp' ); ?></option>
						<option value="_blank" <?php selected( $item['target'], '_blank' ); ?>><?php esc_html_e( 'Új lapon', 'mbapp' ); ?></option>
					</select>
				</label>

				<label class="mbapp-field mbapp-field--sm">
					<span><?php esc_html_e( 'Megjelenés', 'mbapp' ); ?></span>
					<select name="<?php echo esc_attr( $name ); ?>[visible]">
						<option value="all" <?php selected( $item['visible'], 'all' ); ?>><?php esc_html_e( 'Minden eszközön', 'mbapp' ); ?></option>
						<option value="mobile" <?php selected( $item['visible'], 'mobile' ); ?>><?php esc_html_e( 'Csak mobilon', 'mbapp' ); ?></option>
						<option value="desktop" <?php selected( $item['visible'], 'desktop' ); ?>><?php esc_html_e( 'Csak nagyobb kijelzőn', 'mbapp' ); ?></option>
					</select>
				</label>

				<div class="mbapp-item__checks">
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[highlight]" value="1" <?php checked( $item['highlight'], 1 ); ?>>
						<?php esc_html_e( 'Kiemelt (középső) gomb', 'mbapp' ); ?>
					</label>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $item['enabled'], 1 ); ?>>
						<?php esc_html_e( 'Aktív', 'mbapp' ); ?>
					</label>
				</div>
			</div>

			<button type="button" class="button-link mbapp-item__remove" aria-label="<?php esc_attr_e( 'Menüpont törlése', 'mbapp' ); ?>">✕</button>
		</div>
		<?php
	}
}
