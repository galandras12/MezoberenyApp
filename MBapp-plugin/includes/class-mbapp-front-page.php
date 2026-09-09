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
		$title    = ! empty( $block['title'] ) ? $block['title'] : get_bloginfo( 'name' );
		$subtitle = isset( $block['subtitle'] ) ? $block['subtitle'] : '';

		$classes = array(
			'mbapp-hero',
			'mbapp-hero--' . ( isset( $block['height'] ) ? $block['height'] : 'normal' ),
			'mbapp-hero--' . ( isset( $block['align'] ) ? $block['align'] : 'left' ),
		);

		$style = '';

		// A háttérkép külön kapcsolható ki-be.
		$image = '';

		if ( ! empty( $block['show_image'] ) && ! empty( $block['image'] ) ) {
			$image = is_numeric( $block['image'] )
				? (string) wp_get_attachment_image_url( (int) $block['image'], 'full' )
				: (string) $block['image'];
		}

		if ( $image ) {
			$classes[] = 'mbapp-hero--image';
			$overlay   = isset( $block['overlay'] ) ? max( 0, min( 90, (int) $block['overlay'] ) ) / 100 : 0.45;

			$style = sprintf(
				' style="background-image:linear-gradient(rgba(6,10,16,%1$s),rgba(6,10,16,%1$s)),url(%2$s)"',
				esc_attr( (string) $overlay ),
				esc_url( $image )
			);
		}

		$out = sprintf( '<section class="%1$s"%2$s>', esc_attr( implode( ' ', $classes ) ), $style );

		$out .= '<div class="mbapp-hero__inner">';
		$out .= '<h1 class="mbapp-hero__title">' . esc_html( $title ) . '</h1>';

		if ( $subtitle ) {
			$out .= '<p class="mbapp-hero__subtitle">' . esc_html( $subtitle ) . '</p>';
		}

		if ( ! empty( $block['button_label'] ) && ! empty( $block['button_url'] ) ) {
			$out .= sprintf(
				'<p class="mbapp-hero__actions"><a class="mbapp-btn mbapp-hero__btn" href="%1$s">%2$s</a></p>',
				esc_url( self::resolve_url( $block['button_url'] ) ),
				esc_html( $block['button_label'] )
			);
		}

		$out .= '</div></section>';

		return $out;
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
