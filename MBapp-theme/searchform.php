<?php
/**
 * Kereső űrlap.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

$mbapp_search_id = 'mb-search-' . wp_unique_id();
?>
<form role="search" method="get" class="mb-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $mbapp_search_id ); ?>">
		<?php esc_html_e( 'Keresés', 'mbapp-theme' ); ?>
	</label>
	<input type="search"
		id="<?php echo esc_attr( $mbapp_search_id ); ?>"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'Keresés a tartalmak között…', 'mbapp-theme' ); ?>">
	<button type="submit" class="mb-btn"><?php esc_html_e( 'Keresés', 'mbapp-theme' ); ?></button>
</form>
