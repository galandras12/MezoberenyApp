<?php
/**
 * Eseménykezelés: mezők, mentés, lekérdezések, takarítás.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Események.
 */
class MBapp_Events {

	const META_START     = '_mbapp_event_start';
	const META_START_TS  = '_mbapp_event_start_ts';
	const META_END       = '_mbapp_event_end';
	const META_END_TS    = '_mbapp_event_end_ts';
	const META_ALL_DAY   = '_mbapp_event_all_day';
	const META_LOCATION  = '_mbapp_event_location';
	const META_SOURCE    = '_mbapp_event_source_url';
	const META_LINK      = '_mbapp_event_url';

	/**
	 * Konstruktor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . MBAPP_CPT_EVENT, array( $this, 'save_meta' ), 10, 2 );

		add_filter( 'manage_' . MBAPP_CPT_EVENT . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . MBAPP_CPT_EVENT . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . MBAPP_CPT_EVENT . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'admin_order' ) );

		add_action( 'admin_notices', array( $this, 'source_notice' ) );

		// A forrás gomb megjelenítése az esemény és a hír oldalán.
		add_filter( 'the_content', array( $this, 'append_source_to_content' ), 20 );
	}

	/* =====================================================================
	 * Szerkesztő felület
	 * ===================================================================== */

	/**
	 * Metadoboz hozzáadása.
	 */
	public function add_meta_box() {
		add_meta_box(
			'mbapp_event_details',
			__( 'Esemény adatai', 'mbapp' ),
			array( $this, 'render_meta_box' ),
			MBAPP_CPT_EVENT,
			'normal',
			'high'
		);
	}

	/**
	 * Metadoboz megjelenítése.
	 *
	 * @param WP_Post $post Bejegyzés.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'mbapp_event_meta', 'mbapp_event_nonce' );

		$data     = self::get_event_data( $post->ID );
		$required = (bool) MBapp_Settings::get( 'events', 'require_source', 1 );
		$default  = MBapp_Settings::get( 'events', 'default_source', '' );

		$start = $data['start_ts'] ? wp_date( 'Y-m-d\TH:i', $data['start_ts'] ) : '';
		$end   = $data['end_ts'] && $data['end_ts'] !== $data['start_ts'] ? wp_date( 'Y-m-d\TH:i', $data['end_ts'] ) : '';
		?>
		<div class="mbapp-metabox">
			<p class="mbapp-metabox__row">
				<label for="mbapp_event_start"><strong><?php esc_html_e( 'Kezdés időpontja', 'mbapp' ); ?></strong> <span class="mbapp-req">*</span></label><br>
				<input type="datetime-local" id="mbapp_event_start" name="mbapp_event_start"
					value="<?php echo esc_attr( $start ); ?>" required class="widefat">
				<span class="description"><?php esc_html_e( 'Ez alapján rendezzük az eseményeket és számoljuk a hátralévő napokat.', 'mbapp' ); ?></span>
			</p>

			<p class="mbapp-metabox__row">
				<label for="mbapp_event_end"><strong><?php esc_html_e( 'Befejezés időpontja', 'mbapp' ); ?></strong></label><br>
				<input type="datetime-local" id="mbapp_event_end" name="mbapp_event_end"
					value="<?php echo esc_attr( $end ); ?>" class="widefat">
				<span class="description"><?php esc_html_e( 'Nem kötelező. Ha üresen hagyod, a kezdés időpontját használjuk.', 'mbapp' ); ?></span>
			</p>

			<p class="mbapp-metabox__row">
				<label>
					<input type="checkbox" name="mbapp_event_all_day" value="1" <?php checked( $data['all_day'], 1 ); ?>>
					<?php esc_html_e( 'Egész napos esemény (ne mutassuk az órát)', 'mbapp' ); ?>
				</label>
			</p>

			<p class="mbapp-metabox__row">
				<label for="mbapp_event_location"><strong><?php esc_html_e( 'Helyszín', 'mbapp' ); ?></strong></label><br>
				<input type="text" id="mbapp_event_location" name="mbapp_event_location"
					value="<?php echo esc_attr( $data['location'] ); ?>" class="widefat"
					placeholder="<?php esc_attr_e( 'pl. Mezőberény, Kossuth tér', 'mbapp' ); ?>">
			</p>

			<p class="mbapp-metabox__row">
				<label for="mbapp_event_source"><strong><?php esc_html_e( 'Forrás URL', 'mbapp' ); ?></strong>
					<?php if ( $required ) : ?><span class="mbapp-req">*</span><?php endif; ?>
				</label><br>
				<input type="url" id="mbapp_event_source" name="mbapp_event_source"
					value="<?php echo esc_attr( $data['source_url'] ? $data['source_url'] : $default ); ?>"
					class="widefat" <?php echo $required ? 'required' : ''; ?>
					placeholder="https://mezobereny.hu/…">
				<span class="description">
					<?php
					printf(
						/* translators: %s: gomb felirata */
						esc_html__( 'Az esemény végére automatikusan bekerül a gomb: „%s”.', 'mbapp' ),
						esc_html( MBapp_Settings::get( 'events', 'source_label', '' ) )
					);
					?>
				</span>
			</p>

			<p class="mbapp-metabox__row">
				<label for="mbapp_event_link"><strong><?php esc_html_e( 'További link (nem kötelező)', 'mbapp' ); ?></strong></label><br>
				<input type="url" id="mbapp_event_link" name="mbapp_event_link"
					value="<?php echo esc_attr( $data['link'] ); ?>" class="widefat"
					placeholder="<?php esc_attr_e( 'pl. jegyvásárlás vagy Facebook esemény', 'mbapp' ); ?>">
			</p>

			<p class="description">
				<?php esc_html_e( 'A borítóképet a jobb oldali „Kiemelt kép” dobozban tudod beállítani, a leírást pedig a szerkesztőben.', 'mbapp' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Mezők mentése.
	 *
	 * @param int     $post_id Bejegyzés azonosító.
	 * @param WP_Post $post    Bejegyzés.
	 */
	public function save_meta( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['mbapp_event_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mbapp_event_nonce'] ) ), 'mbapp_event_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$start_raw = isset( $_POST['mbapp_event_start'] ) ? sanitize_text_field( wp_unslash( $_POST['mbapp_event_start'] ) ) : '';
		$end_raw   = isset( $_POST['mbapp_event_end'] ) ? sanitize_text_field( wp_unslash( $_POST['mbapp_event_end'] ) ) : '';

		$start_ts = mbapp_local_to_timestamp( $start_raw );
		$end_ts   = mbapp_local_to_timestamp( $end_raw );

		if ( ! $end_ts || $end_ts < $start_ts ) {
			$end_ts = $start_ts;
		}

		update_post_meta( $post_id, self::META_START, $start_ts ? wp_date( 'Y-m-d H:i:s', $start_ts ) : '' );
		update_post_meta( $post_id, self::META_START_TS, $start_ts );
		update_post_meta( $post_id, self::META_END, $end_ts ? wp_date( 'Y-m-d H:i:s', $end_ts ) : '' );
		update_post_meta( $post_id, self::META_END_TS, $end_ts );

		update_post_meta( $post_id, self::META_ALL_DAY, isset( $_POST['mbapp_event_all_day'] ) ? 1 : 0 );

		update_post_meta(
			$post_id,
			self::META_LOCATION,
			isset( $_POST['mbapp_event_location'] ) ? sanitize_text_field( wp_unslash( $_POST['mbapp_event_location'] ) ) : ''
		);

		update_post_meta(
			$post_id,
			self::META_LINK,
			isset( $_POST['mbapp_event_link'] ) ? esc_url_raw( wp_unslash( $_POST['mbapp_event_link'] ) ) : ''
		);

		$source = isset( $_POST['mbapp_event_source'] ) ? esc_url_raw( wp_unslash( $_POST['mbapp_event_source'] ) ) : '';

		// A forrás megadása kötelező lehet – ilyenkor az alapértelmezett forrásra esünk vissza.
		if ( ! $source && MBapp_Settings::get( 'events', 'require_source', 1 ) ) {
			$source = esc_url_raw( (string) MBapp_Settings::get( 'events', 'default_source', '' ) );

			if ( ! $source && 'publish' === $post->post_status ) {
				// Nem publikálhatunk forrás nélkül: visszaállítjuk piszkozatra.
				remove_action( 'save_post_' . MBAPP_CPT_EVENT, array( $this, 'save_meta' ), 10 );

				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_status' => 'draft',
					)
				);

				add_action( 'save_post_' . MBAPP_CPT_EVENT, array( $this, 'save_meta' ), 10, 2 );

				set_transient( 'mbapp_source_missing_' . get_current_user_id(), 1, 60 );
			}
		}

		update_post_meta( $post_id, self::META_SOURCE, $source );
	}

	/**
	 * Figyelmeztetés hiányzó forrás esetén.
	 */
	public function source_notice() {
		$key = 'mbapp_source_missing_' . get_current_user_id();

		if ( ! get_transient( $key ) ) {
			return;
		}

		delete_transient( $key );
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<strong><?php esc_html_e( 'MBapp:', 'mbapp' ); ?></strong>
				<?php esc_html_e( 'Az eseményhez kötelező forrás URL-t megadni, ezért az esemény piszkozatként lett elmentve. Add meg a forrást, majd publikáld újra.', 'mbapp' ); ?>
			</p>
		</div>
		<?php
	}

	/* =====================================================================
	 * Lista oszlopok
	 * ===================================================================== */

	/**
	 * Oszlopok.
	 *
	 * @param array $columns Oszlopok.
	 * @return array
	 */
	public function columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['mbapp_start']     = __( 'Kezdés', 'mbapp' );
				$new['mbapp_countdown'] = __( 'Hátralévő idő', 'mbapp' );
				$new['mbapp_source']    = __( 'Forrás', 'mbapp' );
			}
		}

		return $new;
	}

	/**
	 * Oszlop tartalma.
	 *
	 * @param string $column  Oszlop.
	 * @param int    $post_id Bejegyzés.
	 */
	public function column_content( $column, $post_id ) {
		$data = self::get_event_data( $post_id );

		switch ( $column ) {
			case 'mbapp_start':
				echo $data['start_ts']
					? esc_html( mbapp_format_timestamp( $data['start_ts'] ) )
					: '<span style="color:#b32d2e">' . esc_html__( 'nincs megadva', 'mbapp' ) . '</span>';
				break;

			case 'mbapp_countdown':
				if ( ! $data['start_ts'] ) {
					echo '—';
					break;
				}

				$is_past = $data['end_ts'] < mbapp_now();
				printf(
					'<span style="%s">%s</span>',
					$is_past ? 'opacity:.6' : 'font-weight:600',
					esc_html( mbapp_countdown_label( $data['start_ts'] ) )
				);
				break;

			case 'mbapp_source':
				if ( $data['source_url'] ) {
					printf(
						'<a href="%1$s" target="_blank" rel="noopener">%2$s</a>',
						esc_url( $data['source_url'] ),
						esc_html( wp_parse_url( $data['source_url'], PHP_URL_HOST ) )
					);
				} else {
					echo '<span style="color:#b32d2e">' . esc_html__( 'hiányzik', 'mbapp' ) . '</span>';
				}
				break;
		}
	}

	/**
	 * Rendezhető oszlopok.
	 *
	 * @param array $columns Oszlopok.
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$columns['mbapp_start'] = 'mbapp_start';

		return $columns;
	}

	/**
	 * Admin listában kezdés szerinti rendezés.
	 *
	 * @param WP_Query $query Lekérdezés.
	 */
	public function admin_order( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( MBAPP_CPT_EVENT !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( ! $orderby || 'mbapp_start' === $orderby ) {
			$query->set( 'meta_key', self::META_START_TS );
			$query->set( 'orderby', 'meta_value_num' );

			if ( ! $query->get( 'order' ) ) {
				$query->set( 'order', 'ASC' );
			}
		}
	}

	/* =====================================================================
	 * Adatok
	 * ===================================================================== */

	/**
	 * Egy esemény adatai.
	 *
	 * @param int $post_id Bejegyzés.
	 * @return array
	 */
	public static function get_event_data( $post_id ) {
		$start_ts = (int) get_post_meta( $post_id, self::META_START_TS, true );
		$end_ts   = (int) get_post_meta( $post_id, self::META_END_TS, true );

		if ( ! $end_ts ) {
			$end_ts = $start_ts;
		}

		$source = (string) get_post_meta( $post_id, self::META_SOURCE, true );

		if ( ! $source ) {
			$source = (string) MBapp_Settings::get( 'events', 'default_source', '' );
		}

		return array(
			'start_ts'   => $start_ts,
			'end_ts'     => $end_ts,
			'all_day'    => (int) get_post_meta( $post_id, self::META_ALL_DAY, true ),
			'location'   => (string) get_post_meta( $post_id, self::META_LOCATION, true ),
			'source_url' => $source,
			'link'       => (string) get_post_meta( $post_id, self::META_LINK, true ),
			'is_past'    => $end_ts > 0 && $end_ts < mbapp_now(),
			'days'       => $start_ts ? mbapp_days_until( $start_ts ) : 0,
		);
	}

	/**
	 * Időpont formázott szövege.
	 *
	 * @param array $data Esemény adatok.
	 * @return string
	 */
	public static function format_range( array $data ) {
		if ( ! $data['start_ts'] ) {
			return '';
		}

		$format = $data['all_day'] ? 'Y. F j.' : MBapp_Settings::get( 'events', 'date_format', 'Y. F j. H:i' );
		$text   = mbapp_format_timestamp( $data['start_ts'], $format );

		if ( $data['end_ts'] && $data['end_ts'] > $data['start_ts'] ) {
			$same_day = wp_date( 'Y-m-d', $data['start_ts'] ) === wp_date( 'Y-m-d', $data['end_ts'] );
			$end_fmt  = $same_day ? ( $data['all_day'] ? '' : 'H:i' ) : $format;

			if ( $end_fmt ) {
				$text .= ' – ' . mbapp_format_timestamp( $data['end_ts'], $end_fmt );
			}
		}

		return $text;
	}

	/**
	 * Közelgő események lekérdezése.
	 *
	 * @param array $args Paraméterek.
	 * @return WP_Query
	 */
	public static function query_upcoming( array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'per_page' => 25,
				'page'     => 1,
				'category' => '',
			)
		);

		$query_args = array(
			'post_type'      => MBAPP_CPT_EVENT,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $args['per_page'],
			'paged'          => max( 1, (int) $args['page'] ),
			'meta_key'       => self::META_START_TS, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => array(
				array(
					'key'     => self::META_END_TS,
					'value'   => mbapp_now(),
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			),
		);

		if ( $args['category'] ) {
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'mbapp_event_category',
					'field'    => is_numeric( $args['category'] ) ? 'term_id' : 'slug',
					'terms'    => $args['category'],
				),
			);
		}

		return new WP_Query( $query_args );
	}

	/**
	 * Nemrég véget ért események lekérdezése.
	 *
	 * @param array $args Paraméterek.
	 * @return WP_Query
	 */
	public static function query_past( array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'per_page' => 25,
				'days'     => (int) MBapp_Settings::get( 'events', 'past_days', 7 ),
				'category' => '',
			)
		);

		$now    = mbapp_now();
		$cutoff = $now - ( max( 1, (int) $args['days'] ) * DAY_IN_SECONDS );

		$query_args = array(
			'post_type'      => MBAPP_CPT_EVENT,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $args['per_page'],
			'meta_key'       => self::META_START_TS, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => self::META_END_TS,
					'value'   => $now,
					'compare' => '<',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => self::META_END_TS,
					'value'   => $cutoff,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			),
		);

		if ( $args['category'] ) {
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'mbapp_event_category',
					'field'    => is_numeric( $args['category'] ) ? 'term_id' : 'slug',
					'terms'    => $args['category'],
				),
			);
		}

		return new WP_Query( $query_args );
	}

	/**
	 * Lejárt események automatikus törlése.
	 *
	 * A beállított türelmi idő (alapból 7 nap) letelte után.
	 *
	 * @return int A feldolgozott események száma.
	 */
	public static function cleanup() {
		if ( ! MBapp_Settings::get( 'events', 'auto_delete', 1 ) ) {
			return 0;
		}

		$days   = max( 1, (int) MBapp_Settings::get( 'events', 'past_days', 7 ) );
		$cutoff = mbapp_now() - ( $days * DAY_IN_SECONDS );

		$posts = get_posts(
			array(
				'post_type'      => MBAPP_CPT_EVENT,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => array(
					array(
						'key'     => self::META_END_TS,
						'value'   => $cutoff,
						'compare' => '<',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => self::META_END_TS,
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		if ( empty( $posts ) ) {
			return 0;
		}

		$mode = MBapp_Settings::get( 'events', 'delete_mode', 'trash' );

		foreach ( $posts as $post_id ) {
			if ( 'delete' === $mode ) {
				wp_delete_post( $post_id, true );
			} else {
				wp_trash_post( $post_id );
			}
		}

		return count( $posts );
	}

	/* =====================================================================
	 * Megjelenítés
	 * ===================================================================== */

	/**
	 * Forrás gomb hozzáfűzése az esemény és a hír tartalmához.
	 *
	 * Csak akkor, ha még nincs benne (az importáló beírja a tartalomba).
	 *
	 * @param string $content Tartalom.
	 * @return string
	 */
	public function append_source_to_content( $content ) {
		if ( ! is_singular( array( MBAPP_CPT_EVENT, MBAPP_CPT_NEWS ) ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		if ( false !== strpos( $content, 'mbapp-source__btn' ) ) {
			return $content;
		}

		$post_id = get_the_ID();

		if ( MBAPP_CPT_EVENT === get_post_type( $post_id ) ) {
			$data = self::get_event_data( $post_id );
			$url  = $data['source_url'];
			$group = 'events';
		} else {
			$url   = (string) get_post_meta( $post_id, '_mbapp_source_url', true );
			$group = 'news';
		}

		if ( ! $url ) {
			return $content;
		}

		return $content . mbapp_source_button( $url, '', $group );
	}
}
