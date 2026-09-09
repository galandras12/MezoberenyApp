<?php
/**
 * Lista nézetű bejegyzés.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'mb-listitem' ); ?>>
	<div class="mb-listitem__media">
		<?php if ( has_post_thumbnail() ) : ?>
			<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
				<?php the_post_thumbnail( 'mbapp-thumb', array( 'loading' => 'lazy' ) ); ?>
			</a>
		<?php endif; ?>
	</div>

	<div class="mb-listitem__body">
		<div class="mb-card__meta"><?php mbapp_theme_posted_on(); ?></div>

		<h2 class="mb-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<p class="mb-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20, '…' ) ); ?></p>
	</div>
</article>
