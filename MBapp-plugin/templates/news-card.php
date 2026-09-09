<?php
/**
 * Hír kártya.
 *
 * A téma felülírhatja: /mbapp/news-card.php
 *
 * @var int    $post_id Bejegyzés azonosító.
 * @var string $layout  'grid' vagy 'list'.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

$mbapp_permalink = get_permalink( $post_id );
$mbapp_source    = (string) get_post_meta( $post_id, '_mbapp_source_url', true );
?>
<article class="mbapp-card mbapp-card--<?php echo esc_attr( $layout ); ?> mbapp-news-card">

	<?php if ( has_post_thumbnail( $post_id ) ) : ?>
		<a class="mbapp-card__media" href="<?php echo esc_url( $mbapp_permalink ); ?>" tabindex="-1" aria-hidden="true">
			<?php
			echo get_the_post_thumbnail(
				$post_id,
				'list' === $layout ? 'medium' : 'large',
				array( 'loading' => 'lazy' )
			);
			?>
		</a>
	<?php endif; ?>

	<div class="mbapp-card__body">
		<div class="mbapp-card__meta">
			<span class="mbapp-badge">
				<?php echo mbapp_icon( 'clock', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo esc_html( get_the_date( '', $post_id ) ); ?>
			</span>
		</div>

		<h3 class="mbapp-card__title">
			<a href="<?php echo esc_url( $mbapp_permalink ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
		</h3>

		<?php
		$mbapp_excerpt = has_excerpt( $post_id )
			? get_the_excerpt( $post_id )
			: mbapp_make_excerpt( get_post_field( 'post_content', $post_id ), 26 );

		if ( $mbapp_excerpt ) :
			?>
			<p class="mbapp-card__excerpt"><?php echo esc_html( $mbapp_excerpt ); ?></p>
		<?php endif; ?>

		<div class="mbapp-card__foot">
			<a class="mbapp-btn mbapp-btn--ghost" href="<?php echo esc_url( $mbapp_permalink ); ?>">
				<?php esc_html_e( 'Elolvasom', 'mbapp' ); ?>
			</a>
		</div>

		<?php
		// Kötelező forrás gomb minden hír végén.
		echo mbapp_source_button( $mbapp_source, '', 'news' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</article>
