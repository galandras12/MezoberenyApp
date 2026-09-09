<?php
/**
 * Egy bejegyzés – app nézet, oldalsáv nélkül.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'mb-entry' ); ?>>
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="mb-entry__thumb"><?php the_post_thumbnail( 'mbapp-wide' ); ?></div>
		<?php endif; ?>

		<div class="mb-entry__inner">
			<h1 class="entry-title"><?php the_title(); ?></h1>

			<div class="mb-entry__meta"><?php mbapp_theme_posted_on(); ?></div>

			<div class="mb-entry__content">
				<?php
				the_content();

				wp_link_pages(
					array(
						'before' => '<div class="mb-pagination">',
						'after'  => '</div>',
					)
				);
				?>
			</div>
		</div>
	</article>

	<?php
	$prev = get_previous_post();
	$next = get_next_post();

	if ( $prev || $next ) :
		?>
		<nav class="mb-postnav" aria-label="<?php esc_attr_e( 'Bejegyzések közti navigáció', 'mbapp-theme' ); ?>">
			<?php if ( $prev ) : ?>
				<a class="mb-postnav__link" href="<?php echo esc_url( get_permalink( $prev ) ); ?>">
					<span class="mb-postnav__label"><?php esc_html_e( 'Előző', 'mbapp-theme' ); ?></span>
					<span class="mb-postnav__title"><?php echo esc_html( get_the_title( $prev ) ); ?></span>
				</a>
			<?php endif; ?>

			<?php if ( $next ) : ?>
				<a class="mb-postnav__link mb-postnav__link--next" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
					<span class="mb-postnav__label"><?php esc_html_e( 'Következő', 'mbapp-theme' ); ?></span>
					<span class="mb-postnav__title"><?php echo esc_html( get_the_title( $next ) ); ?></span>
				</a>
			<?php endif; ?>
		</nav>
		<?php
	endif;

	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
endwhile;

get_footer();
