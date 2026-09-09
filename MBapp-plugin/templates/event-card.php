<?php
/**
 * Esemény kártya.
 *
 * A téma felülírhatja: /mbapp/event-card.php
 *
 * @var int    $post_id Bejegyzés azonosító.
 * @var array  $data    Esemény adatai.
 * @var string $layout  'grid' vagy 'list'.
 * @var bool   $is_past Véget ért esemény-e.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

$mbapp_classes = array( 'mbapp-card', 'mbapp-card--' . $layout, 'mbapp-event' );

if ( $is_past ) {
	$mbapp_classes[] = 'mbapp-event--past';
}

$mbapp_days      = (int) $data['days'];
$mbapp_countdown = mbapp_countdown_label( $data['start_ts'] );
$mbapp_when      = MBapp_Events::format_range( $data );
$mbapp_permalink = get_permalink( $post_id );
$mbapp_show_cd   = (bool) MBapp_Settings::get( 'events', 'show_countdown', 1 );
?>
<article class="<?php echo esc_attr( implode( ' ', $mbapp_classes ) ); ?>"
	data-days="<?php echo esc_attr( $mbapp_days ); ?>"
	data-start="<?php echo esc_attr( $data['start_ts'] ); ?>">

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
			<?php if ( $data['start_ts'] && ( $mbapp_show_cd || $is_past ) ) : ?>
				<span class="mbapp-badge <?php echo $is_past ? 'mbapp-badge--muted' : 'mbapp-badge--accent'; ?>">
					<?php echo mbapp_icon( 'clock', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo esc_html( $is_past ? __( 'Véget ért', 'mbapp' ) : $mbapp_countdown ); ?>
				</span>
			<?php endif; ?>

			<?php if ( $mbapp_when ) : ?>
				<span class="mbapp-badge"><?php echo esc_html( $mbapp_when ); ?></span>
			<?php endif; ?>

			<?php if ( $data['location'] ) : ?>
				<span class="mbapp-badge">
					<?php echo mbapp_icon( 'pin', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo esc_html( $data['location'] ); ?>
				</span>
			<?php endif; ?>
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
				<?php esc_html_e( 'Részletek', 'mbapp' ); ?>
			</a>

			<?php if ( $data['link'] ) : ?>
				<a class="mbapp-btn mbapp-btn--ghost" href="<?php echo esc_url( $data['link'] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo mbapp_icon( 'ticket', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'További információ', 'mbapp' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php
		// Kötelező forrás gomb minden esemény végén.
		echo mbapp_source_button( $data['source_url'], '', 'events' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</article>
