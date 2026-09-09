<?php
/**
 * Felület elemek: fejléc kép, favicon, egyedi szövegek, diavetítés.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

/* =============================================================================
 * Favicon
 * ========================================================================== */

/**
 * Favicon kiírása a fejlécbe.
 *
 * Ha nincs egyedi favicon beállítva, a WordPress saját webhely ikonja marad
 * érvényben – nem írjuk felül.
 */
function mbapp_theme_favicon() {
	$id = (int) mbapp_theme_get_option( 'favicon' );

	if ( ! $id ) {
		return;
	}

	$url = wp_get_attachment_image_url( $id, 'full' );

	if ( ! $url ) {
		return;
	}

	$small  = wp_get_attachment_image_url( $id, array( 64, 64 ) );
	$medium = wp_get_attachment_image_url( $id, array( 180, 180 ) );

	printf( '<link rel="icon" href="%s" sizes="any">' . "\n", esc_url( $small ? $small : $url ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( $medium ? $medium : $url ) );
	printf( '<link rel="shortcut icon" href="%s">' . "\n", esc_url( $small ? $small : $url ) );
}
add_action( 'wp_head', 'mbapp_theme_favicon', 3 );

/**
 * A WordPress alap webhely ikonjának elnyomása, ha van sajátunk.
 *
 * @param string $url Ikon URL.
 * @return string
 */
function mbapp_theme_override_site_icon( $url ) {
	$id = (int) mbapp_theme_get_option( 'favicon' );

	if ( ! $id ) {
		return $url;
	}

	$custom = wp_get_attachment_image_url( $id, array( 512, 512 ) );

	return $custom ? $custom : $url;
}
add_filter( 'get_site_icon_url', 'mbapp_theme_override_site_icon' );

/* =============================================================================
 * Fejléc kép (banner)
 * ========================================================================== */

/**
 * Megjelenjen-e a fejléc kép ezen az oldalon.
 *
 * @return bool
 */
function mbapp_theme_has_header_image() {
	if ( ! mbapp_theme_get_option( 'header_image_enabled' ) ) {
		return false;
	}

	if ( ! (int) mbapp_theme_get_option( 'header_image' ) ) {
		return false;
	}

	return mbapp_theme_scope_matches( mbapp_theme_get_option( 'header_image_scope' ) );
}

/**
 * Fejléc kép kirajzolása.
 */
function mbapp_theme_header_image() {
	if ( ! mbapp_theme_has_header_image() ) {
		return;
	}

	$id  = (int) mbapp_theme_get_option( 'header_image' );
	$url = wp_get_attachment_image_url( $id, 'mbapp-wide' );

	if ( ! $url ) {
		$url = wp_get_attachment_image_url( $id, 'full' );
	}

	if ( ! $url ) {
		return;
	}

	$overlay  = max( 0, min( 90, (int) mbapp_theme_get_option( 'header_image_overlay' ) ) ) / 100;
	$title    = (string) mbapp_theme_get_option( 'header_image_title' );
	$subtitle = (string) mbapp_theme_get_option( 'header_image_subtitle' );

	$classes = array(
		'mb-headerimg',
		'mb-headerimg--' . mbapp_theme_get_option( 'header_image_position' ),
	);

	if ( mbapp_theme_get_option( 'header_image_rounded' ) ) {
		$classes[] = 'mb-headerimg--rounded';
	}

	if ( ! $title && ! $subtitle ) {
		$classes[] = 'mb-headerimg--bare';
	}

	printf(
		'<div class="%1$s" style="--mb-headerimg-h:%2$dpx;--mb-headerimg-overlay:%3$s;background-image:url(%4$s)">',
		esc_attr( implode( ' ', $classes ) ),
		esc_attr( mbapp_theme_header_image_height() ),
		esc_attr( (string) $overlay ),
		esc_url( $url )
	);

	if ( $title || $subtitle ) {
		echo '<div class="mb-headerimg__inner">';

		if ( $title ) {
			printf( '<p class="mb-headerimg__title">%s</p>', esc_html( $title ) );
		}

		if ( $subtitle ) {
			printf( '<p class="mb-headerimg__subtitle">%s</p>', esc_html( $subtitle ) );
		}

		echo '</div>';
	}

	echo '</div>';
}

/* =============================================================================
 * Egyedi szövegblokkok
 * ========================================================================== */

/**
 * Egyedi szövegblokkok kirajzolása egy adott helyen.
 *
 * @param string $position 'header' vagy 'footer'.
 */
function mbapp_theme_custom_texts( $position = 'header' ) {
	$texts = mbapp_theme_get_option( 'texts' );

	if ( empty( $texts ) || ! is_array( $texts ) ) {
		return;
	}

	foreach ( $texts as $text ) {
		$text = wp_parse_args( $text, mbapp_theme_text_defaults() );

		if ( empty( $text['enabled'] ) || $position !== $text['position'] ) {
			continue;
		}

		if ( ! mbapp_theme_scope_matches( $text['scope'] ) ) {
			continue;
		}

		$content = trim( (string) $text['content'] );

		if ( '' === $content && '' === trim( (string) $text['title'] ) ) {
			continue;
		}

		printf(
			'<section class="mb-customtext mb-customtext--%s">',
			esc_attr( $text['style'] )
		);

		if ( $text['icon'] ) {
			printf( '<span class="mb-customtext__icon" aria-hidden="true">%s</span>', esc_html( $text['icon'] ) );
		}

		echo '<div class="mb-customtext__body">';

		if ( $text['title'] ) {
			printf( '<h2 class="mb-customtext__title">%s</h2>', esc_html( $text['title'] ) );
		}

		if ( $content ) {
			// A tartalom mentéskor már át lett szűrve.
			echo '<div class="mb-customtext__content">' . do_shortcode( wpautop( $content ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</div></section>';
	}
}

/* =============================================================================
 * Diavetítés a bejegyzésekből
 * ========================================================================== */

/**
 * Megjelenjen-e a diavetítés ezen az oldalon.
 *
 * @return bool
 */
function mbapp_theme_has_slider() {
	if ( ! mbapp_theme_get_option( 'slider_enabled' ) ) {
		return false;
	}

	return mbapp_theme_scope_matches( mbapp_theme_get_option( 'slider_scope' ) );
}

/**
 * A diavetítéshez tartozó bejegyzések lekérdezése.
 *
 * @return WP_Query
 */
function mbapp_theme_slider_query() {
	$source = mbapp_theme_get_option( 'slider_source' );
	$count  = max( 1, min( 20, (int) mbapp_theme_get_option( 'slider_count' ) ) );

	$post_type = 'post';

	if ( 'news' === $source && post_type_exists( 'mbapp_news' ) ) {
		$post_type = 'mbapp_news';
	}

	$args = array(
		'post_type'           => $post_type,
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	if ( 'sticky' === $source ) {
		$sticky = get_option( 'sticky_posts' );

		if ( empty( $sticky ) ) {
			return new WP_Query( array( 'post__in' => array( 0 ) ) );
		}

		$args['post_type'] = 'post';
		$args['post__in']  = $sticky;
		$args['orderby']   = 'post__in';
	}

	if ( mbapp_theme_get_option( 'slider_require_image' ) ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$args['meta_query'] = array(
			array(
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS',
			),
		);
	}

	$category = trim( (string) mbapp_theme_get_option( 'slider_category' ) );

	if ( $category ) {
		$taxonomy = ( 'mbapp_news' === $post_type ) ? 'mbapp_news_category' : 'category';

		if ( taxonomy_exists( $taxonomy ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$args['tax_query'] = array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => is_numeric( $category ) ? 'term_id' : 'slug',
					'terms'    => $category,
				),
			);
		}
	}

	return new WP_Query( $args );
}

/**
 * Diavetítés kirajzolása.
 */
function mbapp_theme_slider() {
	if ( ! mbapp_theme_has_slider() ) {
		return;
	}

	$query = mbapp_theme_slider_query();

	if ( ! $query->have_posts() ) {
		wp_reset_postdata();

		return;
	}

	$autoplay = mbapp_theme_get_option( 'slider_autoplay' ) ? '1' : '0';
	$interval = max( 2, min( 30, (int) mbapp_theme_get_option( 'slider_interval' ) ) );
	$excerpt  = (bool) mbapp_theme_get_option( 'slider_show_excerpt' );
	$slides   = array();
	?>
	<section class="mb-slider"
		style="--mb-slider-h:<?php echo esc_attr( mbapp_theme_slider_height() ); ?>px"
		data-autoplay="<?php echo esc_attr( $autoplay ); ?>"
		data-interval="<?php echo esc_attr( $interval * 1000 ); ?>"
		aria-roledescription="carousel"
		aria-label="<?php esc_attr_e( 'Kiemelt bejegyzések', 'mbapp-theme' ); ?>">

		<div class="mb-slider__track" data-mb-slider-track>
			<?php
			$index = 0;

			while ( $query->have_posts() ) :
				$query->the_post();
				$index++;
				$slides[] = get_the_title();
				?>
				<article class="mb-slider__slide"
					role="group"
					aria-roledescription="<?php esc_attr_e( 'dia', 'mbapp-theme' ); ?>"
					aria-label="<?php echo esc_attr( sprintf( '%1$d / %2$d', $index, $query->post_count ) ); ?>">

					<a class="mb-slider__link" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'mbapp-wide', array( 'loading' => 'lazy', 'class' => 'mb-slider__img' ) ); ?>
						<?php endif; ?>

						<div class="mb-slider__body">
							<?php
							// A hírek saját kategóriát használnak, a bejegyzések a beépítettet.
							$taxonomy = ( 'mbapp_news' === get_post_type() ) ? 'mbapp_news_category' : 'category';
							$terms    = taxonomy_exists( $taxonomy ) ? get_the_terms( get_the_ID(), $taxonomy ) : array();

							if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) :
								?>
								<span class="mb-slider__badge"><?php echo esc_html( $terms[0]->name ); ?></span>
							<?php endif; ?>

							<h2 class="mb-slider__title"><?php the_title(); ?></h2>

							<?php if ( $excerpt ) : ?>
								<p class="mb-slider__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18, '…' ) ); ?></p>
							<?php endif; ?>

							<span class="mb-slider__date"><?php echo esc_html( get_the_date() ); ?></span>
						</div>
					</a>
				</article>
				<?php
			endwhile;

			wp_reset_postdata();
			?>
		</div>

		<?php if ( mbapp_theme_get_option( 'slider_arrows' ) && count( $slides ) > 1 ) : ?>
			<button type="button" class="mb-slider__nav mb-slider__nav--prev" data-mb-slider-prev
				aria-label="<?php esc_attr_e( 'Előző dia', 'mbapp-theme' ); ?>">
				<?php mbapp_theme_the_icon( 'back', 20 ); ?>
			</button>
			<button type="button" class="mb-slider__nav mb-slider__nav--next" data-mb-slider-next
				aria-label="<?php esc_attr_e( 'Következő dia', 'mbapp-theme' ); ?>">
				<?php mbapp_theme_the_icon( 'arrow', 20 ); ?>
			</button>
		<?php endif; ?>

		<?php if ( mbapp_theme_get_option( 'slider_dots' ) && count( $slides ) > 1 ) : ?>
			<div class="mb-slider__dots" data-mb-slider-dots>
				<?php foreach ( $slides as $i => $slide_title ) : ?>
					<button type="button"
						class="mb-slider__dot<?php echo 0 === $i ? ' is-active' : ''; ?>"
						data-index="<?php echo esc_attr( $i ); ?>"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %s: bejegyzés címe */ __( 'Ugrás ide: %s', 'mbapp-theme' ), $slide_title ) ); ?>"></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
	<?php
}

/* =============================================================================
 * A tartalom fölé / alá kerülő elemek
 * ========================================================================== */

/**
 * A fejléc alatt megjelenő elemek (fejléc kép, diavetítés, szövegek).
 */
function mbapp_theme_before_content() {
	mbapp_theme_header_image();
	mbapp_theme_slider();
	mbapp_theme_custom_texts( 'header' );
}

/**
 * A tartalom után megjelenő elemek.
 */
function mbapp_theme_after_content() {
	mbapp_theme_custom_texts( 'footer' );
}
