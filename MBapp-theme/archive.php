<?php
/**
 * Archívum.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<header class="mb-section__head">
	<h1 class="mb-section__title"><?php the_archive_title(); ?></h1>
</header>

<?php
$description = get_the_archive_description();
if ( $description ) :
	?>
	<div class="mb-card" style="margin-bottom:var(--mb-space-4)">
		<div class="mb-card__body"><?php echo wp_kses_post( $description ); ?></div>
	</div>
<?php endif; ?>

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
