<?php
/**
 * Shortcode-ok.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode kezelő.
 */
class MBapp_Shortcodes {

	/**
	 * Konstruktor.
	 */
	public function __construct() {
		add_shortcode( 'mbapp_events', array( $this, 'events' ) );
		add_shortcode( 'mbapp_news', array( $this, 'news' ) );
		add_shortcode( 'mbapp_source', array( $this, 'source' ) );
	}

	/**
	 * Sablonrészlet betöltése.
	 *
	 * A téma felülírhatja: /mbapp/<név>.php
	 *
	 * @param string $name Sablon neve.
	 * @param array  $vars Változók.
	 * @return string
	 */
	public static function template( $name, array $vars = array() ) {
		$file = locate_template( array( 'mbapp/' . $name . '.php' ) );

		if ( ! $file ) {
			$file = MBAPP_PLUGIN_DIR . 'templates/' . $name . '.php';
		}

		if ( ! file_exists( $file ) ) {
			return '';
		}

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $vars, EXTR_SKIP );

		ob_start();
		include $file;

		return (string) ob_get_clean();
	}

	/* =====================================================================
	 * [mbapp_events]
	 * ===================================================================== */

	/**
	 * Események megjelenítése.
	 *
	 * Támogatott attribútumok:
	 *  layout   – list | grid (alapból az admin beállítás)
	 *  limit    – hány esemény jelenjen meg elsőre (alapból 25)
	 *  past     – yes | no – a véget ért események listája
	 *  loadmore – yes | no – „További” gomb
	 *  category – esemény kategória azonosító vagy slug
	 *  title    – a lista fölé kiírt cím
	 *
	 * @param array $atts Attribútumok.
	 * @return string
	 */
	public function events( $atts ) {
		$settings = MBapp_Settings::all( 'events' );

		$atts = shortcode_atts(
			array(
				'layout'   => $settings['layout'],
				'limit'    => (int) $settings['per_page'],
				'past'     => $settings['show_past'] ? 'yes' : 'no',
				'loadmore' => 'yes',
				'category' => '',
				'title'    => '',
			),
			$atts,
			'mbapp_events'
		);

		$layout   = in_array( $atts['layout'], array( 'list', 'grid' ), true ) ? $atts['layout'] : 'grid';
		$per_page = max( 1, (int) $atts['limit'] );
		$category = sanitize_text_field( $atts['category'] );

		wp_enqueue_style( 'mbapp' );
		wp_enqueue_script( 'mbapp' );

		$query = MBapp_Events::query_upcoming(
			array(
				'per_page' => $per_page,
				'page'     => 1,
				'category' => $category,
			)
		);

		$has_more = $query->max_num_pages > 1;

		ob_start();
		?>
		<div class="mbapp mbapp-events mbapp-events--<?php echo esc_attr( $layout ); ?>"
			data-layout="<?php echo esc_attr( $layout ); ?>"
			data-per-page="<?php echo esc_attr( $per_page ); ?>"
			data-category="<?php echo esc_attr( $category ); ?>"
			data-page="1">

			<?php if ( $atts['title'] ) : ?>
				<h2 class="mbapp-events__title"><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( $query->have_posts() ) : ?>
				<div class="mbapp-events__items" data-mbapp-items>
					<?php
					while ( $query->have_posts() ) {
						$query->the_post();
						echo self::render_event( get_the_ID(), $layout, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					wp_reset_postdata();
					?>
				</div>

				<?php if ( 'yes' === $atts['loadmore'] && $has_more ) : ?>
					<div class="mbapp-events__more">
						<button type="button" class="mbapp-btn mbapp-loadmore" data-mbapp-loadmore>
							<?php esc_html_e( 'További', 'mbapp' ); ?>
						</button>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<div class="mbapp-empty">
					<div class="mbapp-empty__icon">📅</div>
					<p><?php esc_html_e( 'Jelenleg nincs meghirdetett esemény. Nézz vissza később!', 'mbapp' ); ?></p>
				</div>
			<?php endif; ?>

			<?php
			if ( 'yes' === $atts['past'] ) {
				echo self::render_past_events( $layout, $category ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Véget ért események blokkja.
	 *
	 * @param string $layout   Elrendezés.
	 * @param string $category Kategória.
	 * @return string
	 */
	public static function render_past_events( $layout, $category = '' ) {
		$days = (int) MBapp_Settings::get( 'events', 'past_days', 7 );

		$query = MBapp_Events::query_past(
			array(
				'per_page' => 50,
				'days'     => $days,
				'category' => $category,
			)
		);

		if ( ! $query->have_posts() ) {
			return '';
		}

		ob_start();
		?>
		<section class="mbapp-events__past">
			<h3 class="mbapp-events__past-title">
				<?php esc_html_e( 'Véget ért események', 'mbapp' ); ?>
				<span class="mbapp-events__past-note">
					<?php
					printf(
						/* translators: %d: napok száma */
						esc_html( _n( 'még %d napig látható', 'még %d napig láthatók', $days, 'mbapp' ) ),
						(int) $days
					);
					?>
				</span>
			</h3>

			<div class="mbapp-events__items mbapp-events__items--past">
				<?php
				while ( $query->have_posts() ) {
					$query->the_post();
					echo self::render_event( get_the_ID(), $layout, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				wp_reset_postdata();
				?>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Egy esemény kártyája.
	 *
	 * @param int    $post_id Bejegyzés.
	 * @param string $layout  Elrendezés.
	 * @param bool   $is_past Véget ért-e.
	 * @return string
	 */
	public static function render_event( $post_id, $layout = 'grid', $is_past = false ) {
		$data = MBapp_Events::get_event_data( $post_id );

		return self::template(
			'event-card',
			array(
				'post_id' => $post_id,
				'data'    => $data,
				'layout'  => $layout,
				'is_past' => (bool) $is_past,
			)
		);
	}

	/* =====================================================================
	 * [mbapp_news]
	 * ===================================================================== */

	/**
	 * Hírek megjelenítése.
	 *
	 * @param array $atts Attribútumok.
	 * @return string
	 */
	public function news( $atts ) {
		$atts = shortcode_atts(
			array(
				'layout'   => 'grid',
				'limit'    => 12,
				'category' => '',
				'title'    => '',
			),
			$atts,
			'mbapp_news'
		);

		wp_enqueue_style( 'mbapp' );
		wp_enqueue_script( 'mbapp' );

		$layout = in_array( $atts['layout'], array( 'list', 'grid' ), true ) ? $atts['layout'] : 'grid';

		$query_args = array(
			'post_type'      => MBAPP_CPT_NEWS,
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, (int) $atts['limit'] ),
			'no_found_rows'  => true,
		);

		if ( $atts['category'] ) {
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'mbapp_news_category',
					'field'    => is_numeric( $atts['category'] ) ? 'term_id' : 'slug',
					'terms'    => sanitize_text_field( $atts['category'] ),
				),
			);
		}

		$query = new WP_Query( $query_args );

		if ( ! $query->have_posts() ) {
			return '<div class="mbapp mbapp-empty"><div class="mbapp-empty__icon">📰</div><p>'
				. esc_html__( 'Jelenleg nincs megjeleníthető hír.', 'mbapp' )
				. '</p></div>';
		}

		ob_start();
		?>
		<div class="mbapp mbapp-news mbapp-news--<?php echo esc_attr( $layout ); ?>">
			<?php if ( $atts['title'] ) : ?>
				<h2 class="mbapp-events__title"><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php endif; ?>

			<div class="mbapp-news__items">
				<?php
				while ( $query->have_posts() ) {
					$query->the_post();

					echo self::template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'news-card',
						array(
							'post_id' => get_the_ID(),
							'layout'  => $layout,
						)
					);
				}
				wp_reset_postdata();
				?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/* =====================================================================
	 * [mbapp_source]
	 * ===================================================================== */

	/**
	 * Forrás gomb kézi beszúrása.
	 *
	 * @param array $atts Attribútumok.
	 * @return string
	 */
	public function source( $atts ) {
		$atts = shortcode_atts(
			array(
				'url'   => '',
				'label' => '',
				'group' => 'news',
			),
			$atts,
			'mbapp_source'
		);

		$url = $atts['url'];

		if ( ! $url && is_singular() ) {
			$post_id = get_the_ID();

			$url = MBAPP_CPT_EVENT === get_post_type( $post_id )
				? MBapp_Events::get_event_data( $post_id )['source_url']
				: (string) get_post_meta( $post_id, '_mbapp_source_url', true );
		}

		if ( ! $url ) {
			$url = (string) MBapp_Settings::get( 'events', 'default_source', '' );
		}

		wp_enqueue_style( 'mbapp' );

		return mbapp_source_button( $url, $atts['label'], $atts['group'] );
	}
}
