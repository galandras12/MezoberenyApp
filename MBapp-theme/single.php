<?php
/**
 * Egy bejegyzés.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="mb-layout <?php echo is_active_sidebar( 'sidebar-1' ) ? 'mb-layout--sidebar' : ''; ?>">
	<div>
		<?php
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
				<nav class="mb-grid mb-grid--2" style="margin-top:var(--mb-space-5)" aria-label="<?php esc_attr_e( 'Bejegyzések közti navigáció', 'mbapp-theme' ); ?>">
					<?php if ( $prev ) : ?>
						<a class="mb-card" href="<?php echo esc_url( get_permalink( $prev ) ); ?>">
							<span class="mb-card__body">
								<span class="mb-card__meta"><?php esc_html_e( 'Előző', 'mbapp-theme' ); ?></span>
								<span class="mb-card__title"><?php echo esc_html( get_the_title( $prev ) ); ?></span>
							</span>
						</a>
					<?php endif; ?>
					<?php if ( $next ) : ?>
						<a class="mb-card" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
							<span class="mb-card__body">
								<span class="mb-card__meta"><?php esc_html_e( 'Következő', 'mbapp-theme' ); ?></span>
								<span class="mb-card__title"><?php echo esc_html( get_the_title( $next ) ); ?></span>
							</span>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		endwhile;
		?>
	</div>

	<?php get_sidebar(); ?>
</div>

<?php
get_footer();
