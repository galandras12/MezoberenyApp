<?php
/**
 * Keresési találatok.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<header class="mb-section__head">
	<h1 class="mb-section__title">
		<?php
		printf(
			/* translators: %s: keresőkifejezés */
			esc_html__( 'Találatok erre: %s', 'mbapp-theme' ),
			'<em>' . esc_html( get_search_query() ) . '</em>'
		);
		?>
	</h1>
</header>

<div style="margin-bottom:var(--mb-space-5)"><?php get_search_form(); ?></div>

<?php if ( have_posts() ) : ?>
	<div class="mb-list">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', 'list' );
		endwhile;
		?>
	</div>

	<?php mbapp_theme_pagination(); ?>
<?php else : ?>
	<?php get_template_part( 'template-parts/content', 'none' ); ?>
<?php endif; ?>

<?php
get_footer();
