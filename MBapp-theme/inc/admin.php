<?php
/**
 * A téma saját szerkesztő felülete: Megjelenés → MBapp téma.
 *
 * Ugyanabba a „theme mod” tárolóba ír, mint a Testreszabó, csak részletesebb
 * felületet ad a fejléc képhez, a favikonhoz, az egyedi szövegekhez és a
 * diavetítéshez. A mentés AJAX-szal történik: az oldal nem töltődik újra.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Téma admin.
 */
class MBapp_Theme_Admin {

	const PAGE  = 'mbapp-theme-editor';
	const NONCE = 'mbapp_theme_editor';

	/**
	 * Bekötés.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_mbapp_theme_save', array( __CLASS__, 'handle_post' ) );
		add_action( 'wp_ajax_mbapp_theme_save', array( __CLASS__, 'handle_ajax' ) );
	}

	/**
	 * Menüpont.
	 */
	public static function menu() {
		add_theme_page(
			__( 'MBapp téma szerkesztő', 'mbapp-theme' ),
			__( 'MBapp téma', 'mbapp-theme' ),
			'edit_theme_options',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Eszközök.
	 *
	 * @param string $hook Aktuális oldal.
	 */
	public static function assets( $hook ) {
		if ( 'appearance_page_' . self::PAGE !== $hook ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'mbapp-theme-admin',
			MBAPP_THEME_URI . '/assets/css/admin.css',
			array(),
			MBAPP_THEME_VERSION
		);

		wp_enqueue_script(
			'mbapp-theme-admin',
			MBAPP_THEME_URI . '/assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			MBAPP_THEME_VERSION,
			true
		);

		wp_localize_script(
			'mbapp-theme-admin',
			'MBAppThemeAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE ),
				'i18n'    => array(
					'saving'  => __( 'Mentés…', 'mbapp-theme' ),
					'saved'   => __( 'Elmentve', 'mbapp-theme' ),
					'error'   => __( 'A mentés nem sikerült.', 'mbapp-theme' ),
					'confirm' => __( 'Biztosan törlöd?', 'mbapp-theme' ),
					'pick'    => __( 'Kép kiválasztása', 'mbapp-theme' ),
					'use'     => __( 'Kiválasztom', 'mbapp-theme' ),
				),
			)
		);
	}

	/* =====================================================================
	 * Mentés
	 * ===================================================================== */

	/**
	 * Hagyományos űrlapbeküldés (JavaScript nélkül).
	 */
	public static function handle_post() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez.', 'mbapp-theme' ) );
		}

		check_admin_referer( self::NONCE );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$input = isset( $_POST['mbapp'] ) ? (array) wp_unslash( $_POST['mbapp'] ) : array();

		self::save( $input );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE,
					'updated' => 1,
				),
				admin_url( 'themes.php' )
			)
		);
		exit;
	}

	/**
	 * AJAX mentés.
	 */
	public static function handle_ajax() {
		check_ajax_referer( self::NONCE, 'nonce' );

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Nincs jogosultságod ehhez.', 'mbapp-theme' ) ), 403 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$input = isset( $_POST['mbapp'] ) ? (array) wp_unslash( $_POST['mbapp'] ) : array();

		self::save( $input );

		wp_send_json_success( array( 'message' => __( 'Elmentve', 'mbapp-theme' ) ) );
	}

	/**
	 * Beállítások fertőtlenítése és mentése.
	 *
	 * @param array $input Nyers értékek.
	 */
	private static function save( array $input ) {
		$checkboxes = array(
			'appbar_sticky',
			'appbar_blur',
			'show_tagline',
			'show_search',
			'show_back',
			'show_footer',
			'header_image_enabled',
			'header_image_rounded',
			'favicon_in_appbar',
			'slider_enabled',
			'slider_autoplay',
			'slider_arrows',
			'slider_dots',
			'slider_require_image',
			'slider_show_excerpt',
		);

		foreach ( $checkboxes as $key ) {
			mbapp_theme_set_option( $key, ! empty( $input[ $key ] ) );
		}

		/* Fejléc */
		mbapp_theme_set_option( 'appbar_size', self::pick( $input, 'appbar_size', array( 'compact', 'normal', 'large' ), 'normal' ) );
		mbapp_theme_set_option( 'logo_size', min( 64, max( 24, absint( $input['logo_size'] ?? 36 ) ) ) );
		mbapp_theme_set_option( 'app_width', self::pick( $input, 'app_width', array( 'phone', 'compact', 'wide' ), 'compact' ) );

		/* Fejléc kép */
		mbapp_theme_set_option( 'header_image', absint( $input['header_image'] ?? 0 ) );
		mbapp_theme_set_option( 'header_image_height', self::pick( $input, 'header_image_height', array( 'compact', 'normal', 'tall', 'hero' ), 'normal' ) );
		mbapp_theme_set_option( 'header_image_position', self::pick( $input, 'header_image_position', array( 'top', 'center', 'bottom' ), 'center' ) );
		mbapp_theme_set_option( 'header_image_overlay', min( 90, max( 0, absint( $input['header_image_overlay'] ?? 35 ) ) ) );
		mbapp_theme_set_option( 'header_image_scope', self::pick( $input, 'header_image_scope', array_keys( mbapp_theme_scopes() ), 'front' ) );
		mbapp_theme_set_option( 'header_image_title', sanitize_text_field( (string) ( $input['header_image_title'] ?? '' ) ) );
		mbapp_theme_set_option( 'header_image_subtitle', sanitize_text_field( (string) ( $input['header_image_subtitle'] ?? '' ) ) );

		/* Favicon */
		mbapp_theme_set_option( 'favicon', absint( $input['favicon'] ?? 0 ) );
		mbapp_theme_set_option( 'favicon_shape', self::pick( $input, 'favicon_shape', array( 'circle', 'rounded', 'square' ), 'rounded' ) );

		/* Diavetítés */
		mbapp_theme_set_option( 'slider_source', self::pick( $input, 'slider_source', array( 'news', 'post', 'sticky' ), 'news' ) );
		mbapp_theme_set_option( 'slider_count', min( 20, max( 1, absint( $input['slider_count'] ?? 5 ) ) ) );
		mbapp_theme_set_option( 'slider_scope', self::pick( $input, 'slider_scope', array_keys( mbapp_theme_scopes() ), 'front' ) );
		mbapp_theme_set_option( 'slider_height', self::pick( $input, 'slider_height', array( 'compact', 'normal', 'tall' ), 'normal' ) );
		mbapp_theme_set_option( 'slider_interval', min( 30, max( 2, absint( $input['slider_interval'] ?? 5 ) ) ) );
		mbapp_theme_set_option( 'slider_category', sanitize_text_field( (string) ( $input['slider_category'] ?? '' ) ) );

		/* Lábléc */
		mbapp_theme_set_option( 'footer_text', wp_kses_post( (string) ( $input['footer_text'] ?? '' ) ) );

		/* Egyedi szövegek */
		$texts     = isset( $input['texts'] ) && is_array( $input['texts'] ) ? $input['texts'] : array();
		$positions = array_keys( mbapp_theme_text_positions() );
		$styles    = array_keys( mbapp_theme_text_styles() );
		$scopes    = array_keys( mbapp_theme_scopes() );
		$clean     = array();

		foreach ( $texts as $text ) {
			$title   = sanitize_text_field( (string) ( $text['title'] ?? '' ) );
			$content = (string) ( $text['content'] ?? '' );

			if ( '' === trim( $title ) && '' === trim( $content ) ) {
				continue;
			}

			$clean[] = array(
				'enabled'  => ! empty( $text['enabled'] ) ? 1 : 0,
				'title'    => $title,
				// Ugyanaz a szabály, mint a bejegyzéseknél.
				'content'  => current_user_can( 'unfiltered_html' ) ? $content : wp_kses_post( $content ),
				'position' => in_array( $text['position'] ?? '', $positions, true ) ? $text['position'] : 'header',
				'style'    => in_array( $text['style'] ?? '', $styles, true ) ? $text['style'] : 'card',
				'scope'    => in_array( $text['scope'] ?? '', $scopes, true ) ? $text['scope'] : 'all',
				'icon'     => sanitize_text_field( (string) ( $text['icon'] ?? '' ) ),
			);
		}

		mbapp_theme_set_option( 'texts', $clean );
	}

	/**
	 * Érték választása egy megengedett listából.
	 *
	 * @param array  $input   Bemenet.
	 * @param string $key     Kulcs.
	 * @param array  $allowed Megengedett értékek.
	 * @param string $default Alapérték.
	 * @return string
	 */
	private static function pick( array $input, $key, array $allowed, $default ) {
		$value = isset( $input[ $key ] ) ? (string) $input[ $key ] : '';

		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	/* =====================================================================
	 * Nézet
	 * ===================================================================== */

	/**
	 * A szerkesztő oldal.
	 */
	public static function render() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		$image_id   = (int) mbapp_theme_get_option( 'header_image' );
		$favicon_id = (int) mbapp_theme_get_option( 'favicon' );
		$texts      = mbapp_theme_get_option( 'texts' );
		$texts      = is_array( $texts ) ? $texts : array();
		?>
		<div class="wrap mbappt">
			<h1><?php esc_html_e( 'MBapp téma szerkesztő', 'mbapp-theme' ); ?></h1>

			<?php if ( ! empty( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'A beállítások elmentve.', 'mbapp-theme' ); ?></p></div>
			<?php endif; ?>

			<p class="description">
				<?php esc_html_e( 'Itt szabhatod személyre a felület fejlécét, a favikont, az egyedi szövegeket és a bejegyzésekből épülő diavetítést. A mentés az oldal újratöltése nélkül történik.', 'mbapp-theme' ); ?>
			</p>

			<h2 class="nav-tab-wrapper mbappt-tabs">
				<a href="#mbappt-header" class="nav-tab nav-tab-active"><?php esc_html_e( 'Fejléc', 'mbapp-theme' ); ?></a>
				<a href="#mbappt-image" class="nav-tab"><?php esc_html_e( 'Fejléc kép', 'mbapp-theme' ); ?></a>
				<a href="#mbappt-favicon" class="nav-tab"><?php esc_html_e( 'Favicon', 'mbapp-theme' ); ?></a>
				<a href="#mbappt-slider" class="nav-tab"><?php esc_html_e( 'Diavetítés', 'mbapp-theme' ); ?></a>
				<a href="#mbappt-texts" class="nav-tab"><?php esc_html_e( 'Egyedi szövegek', 'mbapp-theme' ); ?></a>
			</h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mbappt-form">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="action" value="mbapp_theme_save">

				<?php
				self::panel_header();
				self::panel_image( $image_id );
				self::panel_favicon( $favicon_id );
				self::panel_slider();
				self::panel_texts( $texts );
				?>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Beállítások mentése', 'mbapp-theme' ); ?></button>
				</p>
			</form>

			<script type="text/html" id="tmpl-mbappt-text">
				<?php self::text_row( '__INDEX__', array() ); ?>
			</script>
		</div>
		<?php
	}

	/**
	 * Fejléc panel.
	 */
	private static function panel_header() {
		?>
		<div class="mbappt-panel is-active" id="mbappt-header">
			<h2><?php esc_html_e( 'Fejléc (app bar)', 'mbapp-theme' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Fejléc mérete', 'mbapp-theme' ); ?></th>
					<td>
						<fieldset class="mbappt-choices">
							<?php
							$sizes = array(
								'compact' => array( __( 'Alacsony', 'mbapp-theme' ), '50 px' ),
								'normal'  => array( __( 'Közepes', 'mbapp-theme' ), '58 px' ),
								'large'   => array( __( 'Magas', 'mbapp-theme' ), '70 px' ),
							);

							$current = mbapp_theme_get_option( 'appbar_size' );

							foreach ( $sizes as $key => $meta ) :
								?>
								<label class="mbappt-choice">
									<input type="radio" name="mbapp[appbar_size]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current, $key ); ?>>
									<span class="mbappt-choice__bar mbappt-choice__bar--<?php echo esc_attr( $key ); ?>"></span>
									<strong><?php echo esc_html( $meta[0] ); ?></strong>
									<em><?php echo esc_html( $meta[1] ); ?></em>
								</label>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mbappt-logo-size"><?php esc_html_e( 'Logó / ikon mérete', 'mbapp-theme' ); ?></label></th>
					<td>
						<input type="number" id="mbappt-logo-size" name="mbapp[logo_size]" class="small-text"
							min="24" max="64" step="2" value="<?php echo esc_attr( mbapp_theme_get_option( 'logo_size' ) ); ?>"> px
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mbappt-width"><?php esc_html_e( 'Tartalom szélessége', 'mbapp-theme' ); ?></label></th>
					<td>
						<select id="mbappt-width" name="mbapp[app_width]">
							<?php
							$widths = array(
								'phone'   => __( 'Telefon (560 px) – leginkább app hatás', 'mbapp-theme' ),
								'compact' => __( 'Kompakt (720 px) – ajánlott', 'mbapp-theme' ),
								'wide'    => __( 'Széles (1080 px)', 'mbapp-theme' ),
							);

							$current = mbapp_theme_get_option( 'app_width' );

							foreach ( $widths as $key => $label ) :
								?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Viselkedés', 'mbapp-theme' ); ?></th>
					<td>
						<?php
						$toggles = array(
							'appbar_sticky' => __( 'A fejléc görgetéskor a képernyő tetején marad', 'mbapp-theme' ),
							'appbar_blur'   => __( 'Áttetsző, elmosott háttér (üveg hatás)', 'mbapp-theme' ),
							'show_tagline'  => __( 'Mottó megjelenítése a cím alatt', 'mbapp-theme' ),
							'show_search'   => __( 'Kereső gomb megjelenítése', 'mbapp-theme' ),
							'show_back'     => __( 'Vissza gomb a belső oldalakon', 'mbapp-theme' ),
							'show_footer'   => __( 'Lábléc megjelenítése (az appoknak általában nincs)', 'mbapp-theme' ),
						);

						foreach ( $toggles as $key => $label ) :
							?>
							<label class="mbappt-toggle">
								<input type="checkbox" name="mbapp[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( (bool) mbapp_theme_get_option( $key ), true ); ?>>
								<?php echo esc_html( $label ); ?>
							</label>
						<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mbappt-footer-text"><?php esc_html_e( 'Lábléc szöveg', 'mbapp-theme' ); ?></label></th>
					<td>
						<textarea id="mbappt-footer-text" name="mbapp[footer_text]" rows="3" class="large-text"><?php echo esc_textarea( mbapp_theme_get_option( 'footer_text' ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Csak akkor látszik, ha a lábléc be van kapcsolva.', 'mbapp-theme' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Fejléc kép panel.
	 *
	 * @param int $image_id Kép azonosító.
	 */
	private static function panel_image( $image_id ) {
		?>
		<div class="mbappt-panel" id="mbappt-image">
			<h2><?php esc_html_e( 'Fejléc kép', 'mbapp-theme' ); ?></h2>

			<p class="description">
				<?php esc_html_e( 'A fejléc alatt megjelenő banner. Nem keverendő össze a kezdőlap hero blokkjával, amit az MBapp Plugin kezel – a fejléc kép több oldalon is megjelenhet.', 'mbapp-theme' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Fejléc kép', 'mbapp-theme' ); ?></th>
					<td>
						<label class="mbappt-toggle">
							<input type="checkbox" name="mbapp[header_image_enabled]" value="1" <?php checked( (bool) mbapp_theme_get_option( 'header_image_enabled' ), true ); ?>>
							<?php esc_html_e( 'Fejléc kép megjelenítése', 'mbapp-theme' ); ?>
						</label>

						<?php self::media_field( 'header_image', $image_id, 'mbapp-wide' ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mbappt-img-height"><?php esc_html_e( 'Magasság', 'mbapp-theme' ); ?></label></th>
					<td>
						<select id="mbappt-img-height" name="mbapp[header_image_height]">
							<?php
							$heights = array(
								'compact' => __( 'Alacsony (160 px)', 'mbapp-theme' ),
								'normal'  => __( 'Közepes (240 px)', 'mbapp-theme' ),
								'tall'    => __( 'Magas (340 px)', 'mbapp-theme' ),
								'hero'    => __( 'Nagyon magas (460 px)', 'mbapp-theme' ),
							);

							$current = mbapp_theme_get_option( 'header_image_height' );

							foreach ( $heights as $key => $label ) :
								?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mbappt-img-position"><?php esc_html_e( 'Kép igazítása', 'mbapp-theme' ); ?></label></th>
					<td>
						<select id="mbappt-img-position" name="mbapp[header_image_position]">
							<?php
							$positions = array(
								'top'    => __( 'Felső rész', 'mbapp-theme' ),
								'center' => __( 'Középre', 'mbapp-theme' ),
								'bottom' => __( 'Alsó rész', 'mbapp-theme' ),
							);

							$current = mbapp_theme_get_option( 'header_image_position' );

							foreach ( $positions as $key => $label ) :
								?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Melyik része látszódjon a képnek, ha nem fér ki teljesen.', 'mbapp-theme' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mbappt-img-overlay"><?php esc_html_e( 'Sötétítés', 'mbapp-theme' ); ?></label></th>
					<td>
						<input type="number" id="mbappt-img-overlay" name="mbapp[header_image_overlay]" class="small-text"
							min="0" max="90" step="5" value="<?php echo esc_attr( mbapp_theme_get_option( 'header_image_overlay' ) ); ?>"> %
						<p class="description"><?php esc_html_e( 'Segít, hogy a ráírt szöveg olvasható maradjon.', 'mbapp-theme' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mbappt-img-scope"><?php esc_html_e( 'Hol jelenjen meg', 'mbapp-theme' ); ?></label></th>
					<td>
						<select id="mbappt-img-scope" name="mbapp[header_image_scope]">
							<?php
							$current = mbapp_theme_get_option( 'header_image_scope' );

							foreach ( mbapp_theme_scopes() as $key => $label ) :
								?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mbappt-img-title"><?php esc_html_e( 'Képre írt szöveg', 'mbapp-theme' ); ?></label></th>
					<td>
						<input type="text" id="mbappt-img-title" name="mbapp[header_image_title]" class="regular-text"
							value="<?php echo esc_attr( mbapp_theme_get_option( 'header_image_title' ) ); ?>"
							placeholder="<?php esc_attr_e( 'Cím (nem kötelező)', 'mbapp-theme' ); ?>">
						<br><br>
						<input type="text" name="mbapp[header_image_subtitle]" class="regular-text"
							value="<?php echo esc_attr( mbapp_theme_get_option( 'header_image_subtitle' ) ); ?>"
							placeholder="<?php esc_attr_e( 'Alcím (nem kötelező)', 'mbapp-theme' ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Sarkok', 'mbapp-theme' ); ?></th>
					<td>
						<label class="mbappt-toggle">
							<input type="checkbox" name="mbapp[header_image_rounded]" value="1" <?php checked( (bool) mbapp_theme_get_option( 'header_image_rounded' ), true ); ?>>
							<?php esc_html_e( 'Lekerekített sarkok (app kártya hatás)', 'mbapp-theme' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Favicon panel.
	 *
	 * @param int $favicon_id Kép azonosító.
	 */
	private static function panel_favicon( $favicon_id ) {
		?>
		<div class="mbappt-panel" id="mbappt-favicon">
			<h2><?php esc_html_e( 'Favicon', 'mbapp-theme' ); ?></h2>

			<p class="description">
				<?php esc_html_e( 'Négyzetes kép, legalább 512×512 képpont. Ez lesz a böngészőfül ikonja, a telefon kezdőképernyőjére kitett alkalmazás ikonja, és megjelenhet a fejlécben is. Ha üresen hagyod, a WordPress beállításaiban megadott webhely ikon marad érvényben.', 'mbapp-theme' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Ikon', 'mbapp-theme' ); ?></th>
					<td><?php self::media_field( 'favicon', $favicon_id, array( 128, 128 ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Megjelenése a fejlécben', 'mbapp-theme' ); ?></th>
					<td>
						<label class="mbappt-toggle">
							<input type="checkbox" name="mbapp[favicon_in_appbar]" value="1" <?php checked( (bool) mbapp_theme_get_option( 'favicon_in_appbar' ), true ); ?>>
							<?php esc_html_e( 'Az ikon jelenjen meg a fejlécben a webhely neve mellett', 'mbapp-theme' ); ?>
						</label>

						<fieldset class="mbappt-choices" style="margin-top:12px">
							<?php
							$shapes = array(
								'circle'  => __( 'Kör', 'mbapp-theme' ),
								'rounded' => __( 'Lekerekített', 'mbapp-theme' ),
								'square'  => __( 'Szögletes', 'mbapp-theme' ),
							);

							$current = mbapp_theme_get_option( 'favicon_shape' );

							foreach ( $shapes as $key => $label ) :
								?>
								<label class="mbappt-choice">
									<input type="radio" name="mbapp[favicon_shape]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current, $key ); ?>>
									<span class="mbappt-choice__shape mbappt-choice__shape--<?php echo esc_attr( $key ); ?>"></span>
									<strong><?php echo esc_html( $label ); ?></strong>
								</label>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Diavetítés panel.
	 */
	private static function panel_slider() {
		?>
		<div class="mbappt-panel" id="mbappt-slider">
			<h2><?php esc_html_e( 'Diavetítés a bejegyzésekből', 'mbapp-theme' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Diavetítés', 'mbapp-theme' ); ?></th>
					<td>
						<label class="mbappt-toggle">
							<input type="checkbox" name="mbapp[slider_enabled]" value="1" <?php checked( (bool) mbapp_theme_get_option( 'slider_enabled' ), true ); ?>>
							<?php esc_html_e( 'Diavetítés bekapcsolása', 'mbapp-theme' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mbappt-slider-source"><?php esc_html_e( 'Miből épüljön', 'mbapp-theme' ); ?></label></th>
					<td>
						<select id="mbappt-slider-source" name="mbapp[slider_source]">
							<?php
							$sources = array(
								'news'   => __( 'Hírek (MBapp Plugin)', 'mbapp-theme' ),
								'post'   => __( 'Bejegyzések', 'mbapp-theme' ),
								'sticky' => __( 'Kiemelt (sticky) bejegyzések', 'mbapp-theme' ),
							);

							$current = mbapp_theme_get_option( 'slider_source' );

							foreach ( $sources as $key => $label ) :
								?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Ha a Hírek nincsenek telepítve, automatikusan a bejegyzésekből dolgozik.', 'mbapp-theme' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mbappt-slider-count"><?php esc_html_e( 'Diák száma', 'mbapp-theme' ); ?></label></th>
					<td>
						<input type="number" id="mbappt-slider-count" name="mbapp[slider_count]" class="small-text"
							min="1" max="20" value="<?php echo esc_attr( mbapp_theme_get_option( 'slider_count' ) ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mbappt-slider-category"><?php esc_html_e( 'Kategória szűrő', 'mbapp-theme' ); ?></label></th>
					<td>
						<input type="text" id="mbappt-slider-category" name="mbapp[slider_category]" class="regular-text"
							value="<?php echo esc_attr( mbapp_theme_get_option( 'slider_category' ) ); ?>"
							placeholder="<?php esc_attr_e( 'kategória azonosító vagy slug (nem kötelező)', 'mbapp-theme' ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mbappt-slider-height"><?php esc_html_e( 'Magasság', 'mbapp-theme' ); ?></label></th>
					<td>
						<select id="mbappt-slider-height" name="mbapp[slider_height]">
							<?php
							$heights = array(
								'compact' => __( 'Alacsony (200 px)', 'mbapp-theme' ),
								'normal'  => __( 'Közepes (280 px)', 'mbapp-theme' ),
								'tall'    => __( 'Magas (380 px)', 'mbapp-theme' ),
							);

							$current = mbapp_theme_get_option( 'slider_height' );

							foreach ( $heights as $key => $label ) :
								?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mbappt-slider-scope"><?php esc_html_e( 'Hol jelenjen meg', 'mbapp-theme' ); ?></label></th>
					<td>
						<select id="mbappt-slider-scope" name="mbapp[slider_scope]">
							<?php
							$current = mbapp_theme_get_option( 'slider_scope' );

							foreach ( mbapp_theme_scopes() as $key => $label ) :
								?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Léptetés', 'mbapp-theme' ); ?></th>
					<td>
						<label class="mbappt-toggle">
							<input type="checkbox" name="mbapp[slider_autoplay]" value="1" <?php checked( (bool) mbapp_theme_get_option( 'slider_autoplay' ), true ); ?>>
							<?php esc_html_e( 'Automatikus léptetés', 'mbapp-theme' ); ?>
						</label>

						<p style="margin-top:8px">
							<label for="mbappt-slider-interval"><?php esc_html_e( 'Váltás', 'mbapp-theme' ); ?></label>
							<input type="number" id="mbappt-slider-interval" name="mbapp[slider_interval]" class="small-text"
								min="2" max="30" value="<?php echo esc_attr( mbapp_theme_get_option( 'slider_interval' ) ); ?>">
							<?php esc_html_e( 'másodpercenként', 'mbapp-theme' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Megjelenés', 'mbapp-theme' ); ?></th>
					<td>
						<?php
						$toggles = array(
							'slider_arrows'        => __( 'Nyilak megjelenítése', 'mbapp-theme' ),
							'slider_dots'          => __( 'Pöttyök megjelenítése', 'mbapp-theme' ),
							'slider_require_image' => __( 'Csak olyan bejegyzés, amelyhez van kiemelt kép', 'mbapp-theme' ),
							'slider_show_excerpt'  => __( 'Rövid bevezető szöveg is látszódjon', 'mbapp-theme' ),
						);

						foreach ( $toggles as $key => $label ) :
							?>
							<label class="mbappt-toggle">
								<input type="checkbox" name="mbapp[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( (bool) mbapp_theme_get_option( $key ), true ); ?>>
								<?php echo esc_html( $label ); ?>
							</label>
						<?php endforeach; ?>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Egyedi szövegek panel.
	 *
	 * @param array $texts Szövegek.
	 */
	private static function panel_texts( array $texts ) {
		?>
		<div class="mbappt-panel" id="mbappt-texts">
			<h2><?php esc_html_e( 'Egyedi szövegek', 'mbapp-theme' ); ?></h2>

			<p class="description">
				<?php esc_html_e( 'Saját szövegblokkok, amiket a tartalom elé vagy mögé illeszthetsz – például nyitvatartás, ünnepi közlemény vagy rövid bemutatkozás. Bármennyi lehet belőlük, a sorrend húzással állítható.', 'mbapp-theme' ); ?>
			</p>

			<div id="mbappt-texts-list" class="mbappt-items">
				<?php
				$index = 0;

				foreach ( $texts as $text ) {
					self::text_row( $index, $text );
					$index++;
				}
				?>
			</div>

			<p>
				<button type="button" class="button" id="mbappt-add-text"><?php esc_html_e( '+ Új szövegblokk', 'mbapp-theme' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Egy szövegblokk sora.
	 *
	 * @param int|string $index Index.
	 * @param array      $text  Szöveg.
	 */
	private static function text_row( $index, array $text ) {
		$text = wp_parse_args( $text, mbapp_theme_text_defaults() );
		$name = 'mbapp[texts][' . $index . ']';
		?>
		<div class="mbappt-item" data-index="<?php echo esc_attr( $index ); ?>">
			<div class="mbappt-item__handle" title="<?php esc_attr_e( 'Húzd a sorrend módosításához', 'mbapp-theme' ); ?>">⠿</div>

			<div class="mbappt-item__fields">
				<label class="mbappt-field">
					<span><?php esc_html_e( 'Cím', 'mbapp-theme' ); ?></span>
					<input type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $text['title'] ); ?>">
				</label>

				<label class="mbappt-field mbappt-field--sm">
					<span><?php esc_html_e( 'Ikon (emoji)', 'mbapp-theme' ); ?></span>
					<input type="text" name="<?php echo esc_attr( $name ); ?>[icon]" value="<?php echo esc_attr( $text['icon'] ); ?>" placeholder="ℹ️">
				</label>

				<label class="mbappt-field mbappt-field--full">
					<span><?php esc_html_e( 'Szöveg', 'mbapp-theme' ); ?></span>
					<textarea name="<?php echo esc_attr( $name ); ?>[content]" rows="4"><?php echo esc_textarea( $text['content'] ); ?></textarea>
				</label>

				<label class="mbappt-field mbappt-field--sm">
					<span><?php esc_html_e( 'Hol', 'mbapp-theme' ); ?></span>
					<select name="<?php echo esc_attr( $name ); ?>[position]">
						<?php foreach ( mbapp_theme_text_positions() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $text['position'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>

				<label class="mbappt-field mbappt-field--sm">
					<span><?php esc_html_e( 'Stílus', 'mbapp-theme' ); ?></span>
					<select name="<?php echo esc_attr( $name ); ?>[style]">
						<?php foreach ( mbapp_theme_text_styles() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $text['style'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>

				<label class="mbappt-field mbappt-field--sm">
					<span><?php esc_html_e( 'Mely oldalakon', 'mbapp-theme' ); ?></span>
					<select name="<?php echo esc_attr( $name ); ?>[scope]">
						<?php foreach ( mbapp_theme_scopes() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $text['scope'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>

				<div class="mbappt-item__checks">
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $text['enabled'], 1 ); ?>>
						<?php esc_html_e( 'Megjelenik', 'mbapp-theme' ); ?>
					</label>
				</div>
			</div>

			<button type="button" class="button-link mbappt-item__remove" aria-label="<?php esc_attr_e( 'Szövegblokk törlése', 'mbapp-theme' ); ?>">✕</button>
		</div>
		<?php
	}

	/**
	 * Médiatár választó mező.
	 *
	 * @param string       $key   Beállítás kulcsa.
	 * @param int          $id    Csatolmány azonosító.
	 * @param string|array $size  Előnézeti méret.
	 */
	private static function media_field( $key, $id, $size = 'medium' ) {
		$url = $id ? wp_get_attachment_image_url( $id, $size ) : '';
		?>
		<div class="mbappt-media">
			<span class="mbappt-media__thumb">
				<?php if ( $url ) : ?>
					<img src="<?php echo esc_url( $url ); ?>" alt="">
				<?php endif; ?>
			</span>

			<input type="hidden" class="mbappt-media__id" name="mbapp[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $id ); ?>">

			<span class="mbappt-media__buttons">
				<button type="button" class="button mbappt-media__pick"><?php esc_html_e( 'Kép kiválasztása', 'mbapp-theme' ); ?></button>
				<button type="button" class="button-link mbappt-media__clear"><?php esc_html_e( 'Eltávolítás', 'mbapp-theme' ); ?></button>
			</span>
		</div>
		<?php
	}
}

MBapp_Theme_Admin::init();
