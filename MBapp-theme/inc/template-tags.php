<?php
/**
 * Sablon segédfüggvények.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Beépített SVG ikonok.
 *
 * @param string $name Ikon neve.
 * @param int    $size Méret képpontban.
 * @return string
 */
function mbapp_theme_icon( $name, $size = 22 ) {
	$paths = array(
		'sun'      => '<circle cx="12" cy="12" r="4.2"/><path d="M12 2v2.4M12 19.6V22M4.2 4.2l1.7 1.7M18.1 18.1l1.7 1.7M2 12h2.4M19.6 12H22M4.2 19.8l1.7-1.7M18.1 5.9l1.7-1.7"/>',
		'moon'     => '<path d="M20.5 14.2A8.6 8.6 0 0 1 9.8 3.5a8.6 8.6 0 1 0 10.7 10.7z"/>',
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/>',
		'home'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.5V20h13V9.5"/>',
		'news'     => '<path d="M4 5h13v14H4z"/><path d="M17 9h3v8.5a1.5 1.5 0 0 1-3 0z"/><path d="M7 9h7M7 12.5h7M7 16h4"/>',
		'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
		'map'      => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
		'phone'    => '<path d="M6.5 3.5h3l1.6 4-2 1.5a12 12 0 0 0 5.9 5.9l1.5-2 4 1.6v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4.5 5.7a2 2 0 0 1 2-2.2z"/>',
		'user'     => '<circle cx="12" cy="8.2" r="3.8"/><path d="M4.8 20.2a7.2 7.2 0 0 1 14.4 0"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'back'     => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
		'external' => '<path d="M14 4h6v6"/><path d="M20 4l-8.5 8.5"/><path d="M18 14v5.5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 19.5v-11A1.5 1.5 0 0 1 5.5 7H11"/>',
		'clock'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 1.8"/>',
		'info'     => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5M12 7.8v.2"/>',
		'star'     => '<path d="m12 3.8 2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L3.6 9.9l5.8-.8z"/>',
		'plus'     => '<path d="M12 5v14M5 12h14"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		$name = 'info';
	}

	return sprintf(
		'<svg class="mb-icon mb-icon--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		(int) $size,
		$paths[ $name ]
	);
}

/**
 * Ikon kiírása.
 *
 * @param string $name Ikon neve.
 * @param int    $size Méret.
 */
function mbapp_theme_the_icon( $name, $size = 22 ) {
	echo mbapp_theme_icon( $name, $size ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * A vissza gomb célja, ha nincs böngésző-előzmény.
 *
 * @return string
 */
function mbapp_theme_back_url() {
	if ( is_singular() && ! is_front_page() ) {
		$post_type = get_post_type();

		$archive = get_post_type_archive_link( $post_type );

		if ( $archive ) {
			return $archive;
		}
	}

	return home_url( '/' );
}

/**
 * Márka blokk (logó vagy monogram + cím).
 */
function mbapp_theme_brand() {
	$name = get_bloginfo( 'name' );
	?>
	<?php
	$favicon_id  = (int) mbapp_theme_get_option( 'favicon' );
	$favicon_url = ( $favicon_id && mbapp_theme_get_option( 'favicon_in_appbar' ) )
		? wp_get_attachment_image_url( $favicon_id, array( 96, 96 ) )
		: '';
	?>
	<a class="mb-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
		<?php if ( $favicon_url ) : ?>
			<span class="mb-brand__logo mb-brand__logo--icon">
				<img src="<?php echo esc_url( $favicon_url ); ?>" alt="" width="96" height="96" decoding="async">
			</span>
		<?php elseif ( has_custom_logo() ) : ?>
			<span class="mb-brand__logo"><?php the_custom_logo(); ?></span>
		<?php else : ?>
			<span class="mb-brand__mark" aria-hidden="true"><?php echo esc_html( mbapp_theme_initials( $name ) ); ?></span>
		<?php endif; ?>

		<span class="mb-brand__text">
			<span class="mb-brand__title"><?php echo esc_html( $name ); ?></span>
			<?php
			$tagline = get_bloginfo( 'description', 'display' );
			if ( $tagline && mbapp_theme_get_option( 'show_tagline', true ) ) :
				?>
				<span class="mb-brand__tagline"><?php echo esc_html( $tagline ); ?></span>
			<?php endif; ?>
		</span>
	</a>
	<?php
}

/**
 * Monogram képzése.
 *
 * @param string $text Szöveg.
 * @return string
 */
function mbapp_theme_initials( $text ) {
	$text  = trim( wp_strip_all_tags( (string) $text ) );
	$words = preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );

	if ( empty( $words ) ) {
		return 'MB';
	}

	if ( 1 === count( $words ) ) {
		return mb_strtoupper( mb_substr( $words[0], 0, 2 ) );
	}

	return mb_strtoupper( mb_substr( $words[0], 0, 1 ) . mb_substr( $words[1], 0, 1 ) );
}

/**
 * Bejegyzés metaadatai.
 */
function mbapp_theme_posted_on() {
	printf(
		'<span class="mb-chip">%1$s <time datetime="%2$s">%3$s</time></span>',
		mbapp_theme_icon( 'clock', 14 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date() )
	);

	$cats = get_the_category();
	if ( ! empty( $cats ) ) {
		printf(
			'<a class="mb-chip mb-chip--accent" href="%1$s">%2$s</a>',
			esc_url( get_category_link( $cats[0]->term_id ) ),
			esc_html( $cats[0]->name )
		);
	}
}

/**
 * Lapozás.
 */
function mbapp_theme_pagination() {
	$links = paginate_links(
		array(
			'type'      => 'array',
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
		)
	);

	if ( empty( $links ) ) {
		return;
	}

	echo '<nav class="mb-pagination" aria-label="' . esc_attr__( 'Lapozás', 'mbapp-theme' ) . '">';
	foreach ( $links as $link ) {
		echo wp_kses_post( $link );
	}
	echo '</nav>';
}

/**
 * Lebegő menü kiírása.
 *
 * Ha az MBapp Plugin aktív, ő rendereli (a `mbapp_render_dock` függvényen keresztül).
 * Egyébként a `dock` menühelyre regisztrált menüből épül tartalék menü.
 */
function mbapp_theme_dock() {
	if ( function_exists( 'mbapp_render_dock' ) ) {
		mbapp_render_dock();
		return;
	}

	if ( ! has_nav_menu( 'dock' ) ) {
		return;
	}

	$items = wp_get_nav_menu_items( get_nav_menu_locations()['dock'] );

	if ( empty( $items ) ) {
		return;
	}

	$icons    = array( 'home', 'news', 'calendar', 'map', 'user' );
	$autohide = mbapp_theme_get_option( 'dock_autohide', true ) ? '1' : '0';
	?>
	<nav class="mb-dock" data-autohide="<?php echo esc_attr( $autohide ); ?>" aria-label="<?php esc_attr_e( 'Alkalmazás menü', 'mbapp-theme' ); ?>">
		<?php
		$i = 0;
		foreach ( array_slice( $items, 0, 5 ) as $item ) {
			$icon = $icons[ $i % count( $icons ) ];
			printf(
				'<a class="mb-dock__item" href="%1$s"><span class="mb-dock__icon">%2$s</span><span class="mb-dock__label">%3$s</span></a>',
				esc_url( $item->url ),
				mbapp_theme_icon( $icon ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $item->title )
			);
			$i++;
		}
		?>
	</nav>
	<?php
}

/**
 * Kártya kép megjelenítése tartalék helykitöltővel.
 *
 * @param string $size Képméret.
 */
function mbapp_theme_card_media( $size = 'mbapp-card' ) {
	if ( ! has_post_thumbnail() ) {
		return;
	}
	?>
	<a class="mb-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php the_post_thumbnail( $size, array( 'loading' => 'lazy' ) ); ?>
	</a>
	<?php
}
