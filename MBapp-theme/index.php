<?php
/**
 * Fő sablon.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="mb-layout <?php echo is_active_sidebar( 'sidebar-1' ) ? 'mb-layout--sidebar' : ''; ?>">
	<div>
		<?php if ( is_home() && ! is_front_page() ) : ?>
			<header class="mb-section__head">
				<h1 class="mb-section__title"><?php single_post_title(); ?></h1>
			</header>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<div class="mb-grid mb-grid--2">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', 'card' );
				endwhile;
				?>
			</div>

			<?php mbapp_theme_pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>

	<?php get_sidebar(); ?>
</div>

<?php
get_footer();
