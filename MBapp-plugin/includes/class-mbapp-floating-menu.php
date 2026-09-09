<?php
/**
 * Lebegő menü (dock) megjelenítése.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lebegő menü.
 */
class MBapp_Floating_Menu {

	/**
	 * Konstruktor.
	 */
	public function __construct() {
		// Ha a téma nem hívja meg a mbapp_render_dock() függvényt, a láblécbe tesszük.
		add_action( 'wp_footer', array( $this, 'maybe_render_in_footer' ), 20 );
	}

	/**
	 * Már kirajzoltuk-e.
	 *
	 * @var bool
	 */
	private static $rendered = false;

	/**
	 * Láblécbe illesztés, ha a téma nem gondoskodott róla.
	 */
	public function maybe_render_in_footer() {
		if ( self::$rendered ) {
			return;
		}

		self::render();
	}

	/**
	 * Menü kirajzolása.
	 */
	public static function render() {
		if ( self::$rendered ) {
			return;
		}

		$settings = MBapp_Settings::all( 'menu' );

		if ( empty( $settings['enabled'] ) ) {
			self::$rendered = true;

			return;
		}

		$items = self::visible_items( $settings );

		if ( empty( $items ) ) {
			self::$rendered = true;

			return;
		}

		self::$rendered = true;

		wp_enqueue_style( 'mbapp' );
		wp_enqueue_script( 'mbapp' );

		$classes = array( 'mb-dock', 'mbapp-dock', 'mbapp-dock--' . $settings['style'] );

		if ( 'bottom' !== $settings['position'] ) {
			$classes[] = 'mb-dock--' . $settings['position'];
		}

		if ( empty( $settings['show_labels'] ) ) {
			$classes[] = 'mb-dock--no-labels';
		}

		$current = self::current_url();
		?>
		<nav class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-autohide="<?php echo empty( $settings['autohide'] ) ? '0' : '1'; ?>"
			aria-label="<?php esc_attr_e( 'Alkalmazás menü', 'mbapp' ); ?>">
			<?php
			foreach ( $items as $item ) {
				$url = self::resolve_url( $item['url'] );

				$item_classes = array( 'mb-dock__item', 'mbapp-dock__item' );

				if ( ! empty( $item['highlight'] ) ) {
					$item_classes[] = 'mb-dock__item--fab';
				}

				if ( ! empty( $item['visible'] ) && 'all' !== $item['visible'] ) {
					$item_classes[] = 'mbapp-dock__item--' . $item['visible'];
				}

				$is_active = $url && untrailingslashit( $url ) === untrailingslashit( $current );

				if ( $is_active ) {
					$item_classes[] = 'is-active';
				}

				printf(
					'<a class="%1$s" href="%2$s"%3$s%4$s><span class="mb-dock__icon mbapp-dock__icon">%5$s</span><span class="mb-dock__label">%6$s</span></a>',
					esc_attr( implode( ' ', $item_classes ) ),
					esc_url( $url ),
					'_blank' === $item['target'] ? ' target="_blank" rel="noopener noreferrer"' : '',
					$is_active ? ' aria-current="page"' : '',
					self::icon_html( $item ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_html( $item['label'] )
				);
			}
			?>
		</nav>
		<?php
	}

	/**
	 * Látható menüelemek.
	 *
	 * @param array $settings Beállítások.
	 * @return array
	 */
	private static function visible_items( array $settings ) {
		$items = isset( $settings['items'] ) && is_array( $settings['items'] ) ? $settings['items'] : array();
		$out   = array();

		foreach ( $items as $item ) {
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

			if ( empty( $item['enabled'] ) ) {
				continue;
			}

			if ( '' === trim( (string) $item['url'] ) && '' === trim( (string) $item['label'] ) ) {
				continue;
			}

			$out[] = $item;
		}

		return $out;
	}

	/**
	 * Ikon HTML-je a típus szerint.
	 *
	 * @param array $item Menüelem.
	 * @return string
	 */
	private static function icon_html( array $item ) {
		$type = isset( $item['icon_type'] ) ? $item['icon_type'] : 'builtin';
		$icon = isset( $item['icon'] ) ? (string) $item['icon'] : '';

		switch ( $type ) {
			case 'emoji':
				return '<span class="mbapp-dock__emoji">' . esc_html( $icon ) . '</span>';

			case 'dashicon':
				$slug = sanitize_html_class( str_replace( 'dashicons-', '', $icon ) );

				return '<span class="dashicons dashicons-' . esc_attr( $slug ) . '"></span>';

			case 'image':
				$url = esc_url( $icon );

				if ( ! $url ) {
					return mbapp_icon( 'info' );
				}

				return '<img src="' . $url . '" alt="" loading="lazy" decoding="async">';

			case 'builtin':
			default:
				return mbapp_icon( $icon ? $icon : 'info' );
		}
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

	/**
	 * Az aktuális oldal URL-je.
	 *
	 * @return string
	 */
	private static function current_url() {
		if ( is_front_page() ) {
			return home_url( '/' );
		}

		$id = get_queried_object_id();

		if ( $id && is_singular() ) {
			return (string) get_permalink( $id );
		}

		if ( $id && ( is_category() || is_tag() || is_tax() ) ) {
			$link = get_term_link( $id );

			return is_wp_error( $link ) ? '' : (string) $link;
		}

		if ( is_post_type_archive() ) {
			return (string) get_post_type_archive_link( get_query_var( 'post_type' ) );
		}

		return '';
	}

	/**
	 * Dashicons betöltése, ha a menü használja.
	 */
	public static function maybe_enqueue_dashicons() {
		$settings = MBapp_Settings::all( 'menu' );

		foreach ( (array) $settings['items'] as $item ) {
			if ( isset( $item['icon_type'] ) && 'dashicon' === $item['icon_type'] ) {
				wp_enqueue_style( 'dashicons' );

				return;
			}
		}
	}
}

/**
 * A téma ezt hívja meg a lebegő menü kirajzolásához.
 */
function mbapp_render_dock() {
	MBapp_Floating_Menu::render();
}

add_action( 'wp_enqueue_scripts', array( 'MBapp_Floating_Menu', 'maybe_enqueue_dashicons' ) );
