<?php
/**
 * Testreszabható kezdőlap.
 *
 * A kezdőlap blokkokból áll: fejléc (hero), események, hírek, egyedi HTML
 * és oldal tartalma. A sorrend és minden blokk beállítása az admin
 * felületen szerkeszthető.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kezdőlap.
 */
class MBapp_Front_Page {

	/**
	 * Aktív-e a bővítmény kezdőlapja.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$settings = MBapp_Settings::all( 'home' );

		return ! empty( $settings['enabled'] ) && ! empty( $settings['blocks'] );
	}

	/**
	 * Van-e bekapcsolt, teljes szélességű blokk a kezdőlapon.
	 *
	 * @return bool
	 */
	public static function has_full_width_block() {
		$settings = MBapp_Settings::all( 'home' );

		if ( empty( $settings['blocks'] ) || ! is_array( $settings['blocks'] ) ) {
			return false;
		}

		foreach ( $settings['blocks'] as $block ) {
			if ( empty( $block['enabled'] ) ) {
				continue;
			}

			if ( isset( $block['width'] ) && 'full' === $block['width'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * A kezdőlap kirajzolása.
	 */
	public static function render() {
		$settings = MBapp_Settings::all( 'home' );

		if ( empty( $settings['blocks'] ) || ! is_array( $settings['blocks'] ) ) {
			return;
		}

		wp_enqueue_style( 'mbapp' );
		wp_enqueue_script( 'mbapp' );

		echo '<div class="mbapp mbapp-home">';

		foreach ( $settings['blocks'] as $block ) {
			if ( empty( $block['enabled'] ) || empty( $block['type'] ) ) {
				continue;
			}

			echo self::render_block( $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</div>';
	}

	/**
	 * Egy blokk HTML-je.
	 *
	 * @param array $block Blokk.
	 * @return string
	 */
	public static function render_block( array $block ) {
		switch ( $block['type'] ) {
			case 'hero':
				return self::render_hero( $block );

			case 'events':
				return self::render_events( $block );

			case 'news':
				return self::render_news( $block );

			case 'html':
				return self::render_html( $block );

			case 'page':
				return self::render_page( $block );
		}

		return '';
	}

	/**
	 * Szekció fejléc (cím + opcionális link).
	 *
	 * @param string $title Cím.
	 * @param string $url   Link.
	 * @param string $label Link felirata.
	 * @return string
	 */
	private static function section_head( $title, $url = '', $label = '' ) {
		if ( ! $title && ! $url ) {
			return '';
		}

		$out = '<div class="mbapp-home__head">';

		if ( $title ) {
			$out .= '<h2 class="mbapp-home__title">' . esc_html( $title ) . '</h2>';
		}

		if ( $url ) {
			$out .= sprintf(
				'<a class="mbapp-home__link" href="%1$s">%2$s <span aria-hidden="true">&rarr;</span></a>',
				esc_url( $url ),
				esc_html( $label ? $label : __( 'Összes', 'mbapp' ) )
			);
		}

		return $out . '</div>';
	}

	/**
	 * Fejléc (hero) blokk.
	 *
	 * @param array $block Blokk.
	 * @return string
	 */
	private static function render_hero( array $block ) {
		$block = self::hero_defaults( $block );

		$title    = '' !== trim( (string) $block['title'] ) ? $block['title'] : get_bloginfo( 'name' );
		$subtitle = (string) $block['subtitle'];
		$images   = self::hero_images( $block );

		$classes = array(
			'mbapp-hero',
			'mbapp-hero--' . $block['height'],
			'mbapp-hero--' . $block['align'],
			'mbapp-hero--shape-' . $block['shape'],
			'mbapp-hero--' . $block['width'],
		);

		if ( $images ) {
			$classes[] = 'mbapp-hero--image';
		}

		if ( count( $images ) > 1 ) {
			$classes[] = 'mbapp-hero--slideshow';
		}

		$attrs = '';

		if ( count( $images ) > 1 ) {
			$attrs = sprintf(
				' data-mbapp-hero data-interval="%1$d" data-autoplay="%2$s"',
				max( 2, min( 30, (int) $block['interval'] ) ) * 1000,
				! empty( $block['autoplay'] ) ? '1' : '0'
			);
		}

		$out = sprintf(
			'<section class="%1$s"%2$s>',
			esc_attr( implode( ' ', $classes ) ),
			$attrs
		);

		/* --- Médiaréteg: egy kép vagy diavetítés --- */
		if ( $images ) {
			$out .= '<div class="mbapp-hero__media" data-mbapp-hero-slides>';

			foreach ( $images as $index => $url ) {
				$out .= sprintf(
					'<div class="mbapp-hero__slide%1$s" style="background-image:url(%2$s)"></div>',
					0 === $index ? ' is-active' : '',
					esc_url( $url )
				);
			}

			$out .= '</div>';

			// A sötétítés külön réteg, így a diák válthatnak alatta.
			$overlay = max( 0, min( 90, (int) $block['overlay'] ) ) / 100;

			$out .= sprintf(
				'<div class="mbapp-hero__overlay" style="--mbapp-hero-overlay:%s"></div>',
				esc_attr( (string) $overlay )
			);
		}

		/* --- Szöveg --- */
		$out .= '<div class="mbapp-hero__inner">';
		$out .= '<h1 class="mbapp-hero__title">' . esc_html( $title ) . '</h1>';

		if ( $subtitle ) {
			$out .= '<p class="mbapp-hero__subtitle">' . esc_html( $subtitle ) . '</p>';
		}

		if ( '' !== trim( (string) $block['button_label'] ) && '' !== trim( (string) $block['button_url'] ) ) {
			$out .= sprintf(
				'<p class="mbapp-hero__actions"><a class="mbapp-btn mbapp-hero__btn" href="%1$s">%2$s</a></p>',
				esc_url( self::resolve_url( $block['button_url'] ) ),
				esc_html( $block['button_label'] )
			);
		}

		$out .= '</div>';

		/* --- Diavetítés vezérlők --- */
		if ( count( $images ) > 1 ) {
			if ( ! empty( $block['arrows'] ) ) {
				$out .= '<button type="button" class="mbapp-hero__nav mbapp-hero__nav--prev" data-mbapp-hero-prev aria-label="'
					. esc_attr__( 'Előző kép', 'mbapp' ) . '">&#8249;</button>';
				$out .= '<button type="button" class="mbapp-hero__nav mbapp-hero__nav--next" data-mbapp-hero-next aria-label="'
					. esc_attr__( 'Következő kép', 'mbapp' ) . '">&#8250;</button>';
			}

			if ( ! empty( $block['dots'] ) ) {
				$out .= '<div class="mbapp-hero__dots" data-mbapp-hero-dots>';

				foreach ( array_keys( $images ) as $index ) {
					$out .= sprintf(
						'<button type="button" class="mbapp-hero__dot%1$s" data-index="%2$d" aria-label="%3$s"></button>',
						0 === $index ? ' is-active' : '',
						$index,
						esc_attr(
							sprintf(
								/* translators: %d: a kép sorszáma */
								__( '%d. kép megjelenítése', 'mbapp' ),
								$index + 1
							)
						)
					);
				}

				$out .= '</div>';
			}
		}

		$out .= '</section>';

		return $out;
	}

	/**
	 * A fejléc blokk mezőinek kiegészítése alapértékekkel.
	 *
	 * A korábbi mentésekben még a `show_image` jelző döntött a háttérképről,
	 * ezért abból vezetjük le a médiamódot, ha az még nincs elmentve.
	 *
	 * @param array $block Blokk.
	 * @return array
	 */
	public static function hero_defaults( array $block ) {
		$block = wp_parse_args(
			$block,
			array(
				'title'        => '',
				'subtitle'     => '',
				'media'        => '',
				'show_image'   => 0,
				'image'        => 0,
				'images'       => '',
				'interval'     => 6,
				'autoplay'     => 1,
				'dots'         => 1,
				'arrows'       => 0,
				'width'        => 'container',
				'shape'        => 'rounded',
				'height'       => 'normal',
				'overlay'      => 45,
				'align'        => 'left',
				'button_label' => '',
				'button_url'   => '',
			)
		);

		if ( '' === $block['media'] ) {
			$block['media'] = ( ! empty( $block['show_image'] ) && ! empty( $block['image'] ) ) ? 'image' : 'none';
		}

		return $block;
	}

	/**
	 * A fejléc blokkhoz tartozó képek URL-jei.
	 *
	 * @param array $block Blokk (már kiegészítve).
	 * @return array
	 */
	public static function hero_images( array $block ) {
		$urls = array();

		if ( 'image' === $block['media'] ) {
			$url = self::attachment_url( $block['image'] );

			if ( $url ) {
				$urls[] = $url;
			}
		} elseif ( 'slideshow' === $block['media'] ) {
			// A diavetítés kizárólag médiatári azonosítókkal dolgozik, így a
			// hibás bejegyzések nem kerülnek ki a kimenetbe.
			foreach ( explode( ',', (string) $block['images'] ) as $id ) {
				$id = absint( trim( $id ) );

				if ( ! $id ) {
					continue;
				}

				$url = self::attachment_url( $id );

				if ( $url ) {
					$urls[] = $url;
				}
			}
		}

		return array_values( $urls );
	}

	/**
	 * Csatolmány URL-je az azonosítóból (vagy közvetlenül megadott cím).
	 *
	 * @param mixed $image Azonosító vagy URL.
	 * @return string
	 */
	private static function attachment_url( $image ) {
		if ( is_numeric( $image ) ) {
			$id = (int) $image;

			if ( ! $id ) {
				return '';
			}

			$url = wp_get_attachment_image_url( $id, 'full' );

			return $url ? $url : '';
		}

		return esc_url_raw( (string) $image );
	}

	/**
	 * Események blokk.
	 *
	 * @param array $block Blokk.
	 * @return string
	 */
	private static function render_events( array $block ) {
		$link = '';

		if ( ! empty( $block['link'] ) ) {
			$page_id = (int) MBapp_Settings::get( 'events', 'page_id', 0 );

			if ( $page_id ) {
				$link = (string) get_permalink( $page_id );
			}
		}

		$shortcode = sprintf(
			'[mbapp_events limit="%1$d" layout="%2$s" past="%3$s" loadmore="%4$s"]',
			max( 1, (int) ( $block['limit'] ?? 3 ) ),
			in_array( $block['layout'] ?? '', array( 'grid', 'list' ), true ) ? $block['layout'] : 'grid',
			'yes' === ( $block['past'] ?? 'no' ) ? 'yes' : 'no',
			'yes' === ( $block['loadmore'] ?? 'no' ) ? 'yes' : 'no'
		);

		return '<section class="mbapp-home__section">'
			. self::section_head( $block['title'] ?? '', $link )
			. do_shortcode( $shortcode )
			. '</section>';
	}

	/**
	 * Hírek blokk.
	 *
	 * @param array $block Blokk.
	 * @return string
	 */
	private static function render_news( array $block ) {
		$link = '';

		if ( ! empty( $block['link'] ) ) {
			$archive = get_post_type_archive_link( MBAPP_CPT_NEWS );

			if ( $archive ) {
				$link = $archive;
			}
		}

		$shortcode = sprintf(
			'[mbapp_news limit="%1$d" layout="%2$s"]',
			max( 1, (int) ( $block['limit'] ?? 6 ) ),
			in_array( $block['layout'] ?? '', array( 'grid', 'list' ), true ) ? $block['layout'] : 'grid'
		);

		return '<section class="mbapp-home__section">'
			. self::section_head( $block['title'] ?? '', $link )
			. do_shortcode( $shortcode )
			. '</section>';
	}

	/**
	 * Egyedi HTML blokk.
	 *
	 * @param array $block Blokk.
	 * @return string
	 */
	private static function render_html( array $block ) {
		$content = isset( $block['content'] ) ? (string) $block['content'] : '';

		if ( '' === trim( $content ) ) {
			return '';
		}

		// A tartalom mentéskor már át lett engedve a wp_kses_post szűrőn.
		if ( ! empty( $block['run_shortcodes'] ) ) {
			$content = do_shortcode( $content );
		}

		$classes = array( 'mbapp-home__section', 'mbapp-home__html' );

		if ( ! empty( $block['boxed'] ) ) {
			$classes[] = 'mbapp-home__html--boxed';
		}

		return sprintf( '<section class="%s">', esc_attr( implode( ' ', $classes ) ) )
			. self::section_head( $block['title'] ?? '' )
			. '<div class="mbapp-home__htmlbody">' . $content . '</div>'
			. '</section>';
	}

	/**
	 * Egy meglévő oldal tartalma.
	 *
	 * @param array $block Blokk.
	 * @return string
	 */
	private static function render_page( array $block ) {
		$page_id = (int) ( $block['page_id'] ?? 0 );

		if ( ! $page_id ) {
			return '';
		}

		$page = get_post( $page_id );

		if ( ! $page || 'publish' !== $page->post_status ) {
			return '';
		}

		$content = apply_filters( 'the_content', $page->post_content );

		$classes = array( 'mbapp-home__section', 'mbapp-home__html' );

		if ( ! empty( $block['boxed'] ) ) {
			$classes[] = 'mbapp-home__html--boxed';
		}

		$title = ! empty( $block['title'] ) ? $block['title'] : '';

		return sprintf( '<section class="%s">', esc_attr( implode( ' ', $classes ) ) )
			. self::section_head( $title )
			. '<div class="mbapp-home__htmlbody">' . $content . '</div>'
			. '</section>';
	}

	/**
	 * Relatív útvonal teljes URL-lé alakítása.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private static function resolve_url( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url ) {
			return home_url( '/' );
		}

		if ( preg_match( '~^(https?://|//|mailto:|tel:|\#)~i', $url ) ) {
			return $url;
		}

		return home_url( '/' . ltrim( $url, '/' ) );
	}
}

/**
 * Body osztály, ha teljes szélességű fejléc van a kezdőlapon.
 *
 * Erre azért van szükség, mert a széltől szélig érő blokk kilóg a tartalom
 * hasábjából, és a vízszintes görgetősávot le kell vágni.
 *
 * @param array $classes Osztályok.
 * @return array
 */
function mbapp_front_page_body_class( $classes ) {
	if ( ! is_front_page() || ! MBapp_Front_Page::is_enabled() ) {
		return $classes;
	}

	if ( MBapp_Front_Page::has_full_width_block() ) {
		$classes[] = 'mbapp-has-fullwidth';
	}

	return $classes;
}
add_filter( 'body_class', 'mbapp_front_page_body_class' );

/**
 * A téma ezt hívja meg a kezdőlap kirajzolásához.
 *
 * @return bool Igaz, ha a bővítmény kirajzolta a kezdőlapot.
 */
function mbapp_render_front_page() {
	if ( ! MBapp_Front_Page::is_enabled() ) {
		return false;
	}

	MBapp_Front_Page::render();

	return true;
}
