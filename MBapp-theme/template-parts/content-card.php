<?php
/**
 * Kártya nézetű bejegyzés.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'mb-card' ); ?>>
	<?php mbapp_theme_card_media(); ?>

	<div class="mb-card__body">
		<div class="mb-card__meta"><?php mbapp_theme_posted_on(); ?></div>

		<h2 class="mb-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<p class="mb-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?></p>

		<div class="mb-card__foot">
			<a class="mb-btn mb-btn--ghost" href="<?php the_permalink(); ?>">
				<?php esc_html_e( 'Elolvasom', 'mbapp-theme' ); ?>
				<?php mbapp_theme_the_icon( 'arrow', 16 ); ?>
			</a>
		</div>
	</div>
</article>
