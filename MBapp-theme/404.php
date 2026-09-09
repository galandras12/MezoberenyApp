<?php
/**
 * 404 – nem található.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="mb-empty">
	<div class="mb-empty__icon">🧭</div>
	<h1><?php esc_html_e( 'Ez az oldal nem található', 'mbapp-theme' ); ?></h1>
	<p><?php esc_html_e( 'Lehet, hogy elköltözött, vagy elgépelted a címet. Próbálj keresni!', 'mbapp-theme' ); ?></p>

	<div style="max-width:420px;margin:var(--mb-space-5) auto 0"><?php get_search_form(); ?></div>

	<p style="margin-top:var(--mb-space-5)">
		<a class="mb-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Vissza a kezdőlapra', 'mbapp-theme' ); ?></a>
	</p>
</section>

<?php
get_footer();
