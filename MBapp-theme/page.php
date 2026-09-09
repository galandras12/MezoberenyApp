<?php
/**
 * Egy oldal.
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

	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
endwhile;

get_footer();
