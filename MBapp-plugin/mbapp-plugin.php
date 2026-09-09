<?php
/**
 * Plugin Name:       MBapp Plugin
 * Plugin URI:        https://mezobereny.hu/
 * Description:       Automatikus hírbeolvasó (képpel, szöveggel, címmel, dátummal, naplózva), shortcode alapú eseménykezelő visszaszámlálással, és admin felületről állítható lebegő menü az MBapp webapphoz.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            galandras12
 * Author URI:        https://github.com/galandras12
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mbapp
 * Domain Path:       /languages
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

define( 'MBAPP_PLUGIN_VERSION', '1.1.0' );
define( 'MBAPP_PLUGIN_FILE', __FILE__ );
define( 'MBAPP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MBAPP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MBAPP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/** Egyedi bejegyzéstípusok azonosítói. */
define( 'MBAPP_CPT_NEWS', 'mbapp_news' );
define( 'MBAPP_CPT_EVENT', 'mbapp_event' );

/** Cron horgok. */
define( 'MBAPP_CRON_IMPORT', 'mbapp_news_import_event' );
define( 'MBAPP_CRON_CLEANUP', 'mbapp_events_cleanup_event' );

require_once MBAPP_PLUGIN_DIR . 'includes/helpers.php';
require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-settings.php';
require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-install.php';
require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-logger.php';
require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-post-types.php';
require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-selector.php';
require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-source-detector.php';
require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-news-importer.php';
require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-events.php';
require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-shortcodes.php';
require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-front-page.php';
require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-floating-menu.php';
require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-ajax.php';
require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-cron.php';

if ( is_admin() ) {
	require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-admin.php';
	require_once MBAPP_PLUGIN_DIR . 'includes/class-mbapp-log-table.php';
}

/**
 * A bővítmény fő osztálya.
 */
final class MBapp_Plugin {

	/**
	 * Egyetlen példány.
	 *
	 * @var MBapp_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Alkomponensek.
	 *
	 * @var array
	 */
	private $components = array();

	/**
	 * Példány lekérése.
	 *
	 * @return MBapp_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Konstruktor.
	 */
	private function __construct() {
		$this->components['post_types']    = new MBapp_Post_Types();
		$this->components['events']        = new MBapp_Events();
		$this->components['shortcodes']    = new MBapp_Shortcodes();
		$this->components['floating_menu'] = new MBapp_Floating_Menu();
		$this->components['ajax']          = new MBapp_Ajax();
		$this->components['cron']          = new MBapp_Cron();

		if ( is_admin() ) {
			$this->components['admin'] = new MBapp_Admin();
		}

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade' ) );
	}

	/**
	 * Komponens lekérése.
	 *
	 * @param string $key Kulcs.
	 * @return object|null
	 */
	public function get( $key ) {
		return isset( $this->components[ $key ] ) ? $this->components[ $key ] : null;
	}

	/**
	 * Fordítások betöltése.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'mbapp', false, dirname( MBAPP_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Frontend eszközök.
	 */
	public function enqueue_assets() {
		wp_register_style(
			'mbapp',
			MBAPP_PLUGIN_URL . 'assets/css/mbapp.css',
			array(),
			MBAPP_PLUGIN_VERSION
		);

		wp_register_script(
			'mbapp',
			MBAPP_PLUGIN_URL . 'assets/js/mbapp.js',
			array(),
			MBAPP_PLUGIN_VERSION,
			true
		);

		$menu = MBapp_Settings::all( 'menu' );

		wp_localize_script(
			'mbapp',
			'MBApp',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mbapp_public' ),
				'home'    => home_url( '/' ),
				'nav'     => array(
					'enabled'     => ! empty( $menu['ajax_nav'] ) && ! empty( $menu['enabled'] ),
					'scope'       => $menu['ajax_scope'],
					'animation'   => $menu['anim_type'],
					'duration'    => (int) $menu['anim_duration'],
					'easing'      => self::easing_value( $menu['anim_easing'] ),
					'progressBar' => ! empty( $menu['progress_bar'] ),
					'container'   => '[data-mbapp-view], #mb-content, main',
				),
				'i18n'    => array(
					'loading' => __( 'Betöltés…', 'mbapp' ),
					'more'    => __( 'További', 'mbapp' ),
					'error'   => __( 'Nem sikerült betölteni. Próbáld újra!', 'mbapp' ),
					'noMore'  => __( 'Nincs több esemény', 'mbapp' ),
				),
			)
		);

		// A stílust mindig betöltjük: a shortcode-ok és a lebegő menü a
		// törzsben, illetve a láblécben rajzolódnak ki, amikor a fejléc
		// stíluslapjai már kiíródtak.
		wp_enqueue_style( 'mbapp' );
		wp_enqueue_script( 'mbapp' );
	}

	/**
	 * Az időzítési görbe CSS értéke.
	 *
	 * @param string $key Kulcs.
	 * @return string
	 */
	public static function easing_value( $key ) {
		$map = array(
			'ease-out'    => 'cubic-bezier(.22, .61, .36, 1)',
			'ease-in-out' => 'cubic-bezier(.65, .05, .36, 1)',
			'spring'      => 'cubic-bezier(.34, 1.56, .64, 1)',
			'linear'      => 'linear',
		);

		return isset( $map[ $key ] ) ? $map[ $key ] : $map['ease-out'];
	}

	/**
	 * Verziófrissítés kezelése (pl. új adatbázis oszlopok).
	 */
	public function maybe_upgrade() {
		$stored = get_option( 'mbapp_db_version', '0' );

		if ( version_compare( $stored, MBAPP_PLUGIN_VERSION, '<' ) ) {
			MBapp_Install::install();
			update_option( 'mbapp_db_version', MBAPP_PLUGIN_VERSION );
		}
	}
}

/**
 * Rövidítés a fő példányhoz.
 *
 * @return MBapp_Plugin
 */
function mbapp() {
	return MBapp_Plugin::instance();
}

register_activation_hook( __FILE__, array( 'MBapp_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MBapp_Install', 'deactivate' ) );

mbapp();
