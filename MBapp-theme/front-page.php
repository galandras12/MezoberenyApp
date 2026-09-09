<?php
/**
 * Kezdőlap – app stílusú indítófelület.
 *
 * Ha statikus kezdőlapot állítottál be, annak a tartalma jelenik meg,
 * alatta a legfrissebb hírekkel és a közelgő eseményekkel.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$has_static_front = 'page' === get_option( 'show_on_front' ) && is_page();
?>

<?php if ( $has_static_front ) : ?>
	<?php
	while ( have_posts() ) :
		the_post();

		$content = trim( get_the_content() );
		if ( $content ) :
			?>
			<article class="mb-entry mb-section">
				<div class="mb-entry__inner">
					<div class="mb-entry__content"><?php the_content(); ?></div>
				</div>
			</article>
			<?php
		else :
			?>
			<section class="mb-hero">
				<h1><?php the_title(); ?></h1>
				<p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
			</section>
			<?php
		endif;
	endwhile;
	?>
<?php else : ?>
	<section class="mb-hero">
		<h1><?php bloginfo( 'name' ); ?></h1>
		<p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
	</section>
<?php endif; ?>

<?php
// Közelgő események – csak ha az MBapp Plugin aktív.
if ( shortcode_exists( 'mbapp_events' ) ) :
	?>
	<section class="mb-section">
		<div class="mb-section__head">
			<h2 class="mb-section__title"><?php esc_html_e( 'Közelgő események', 'mbapp-theme' ); ?></h2>
			<?php
			$events_page = get_option( 'mbapp_events_page_id' );
			if ( $events_page ) :
				?>
				<a class="mb-section__link" href="<?php echo esc_url( get_permalink( $events_page ) ); ?>">
					<?php esc_html_e( 'Összes', 'mbapp-theme' ); ?> &rarr;
				</a>
			<?php endif; ?>
		</div>
		<?php echo do_shortcode( '[mbapp_events limit="3" layout="grid" past="no" loadmore="no"]' ); ?>
	</section>
	<?php
endif;
?>

<?php
// Legfrissebb hírek.
$news_type = post_type_exists( 'mbapp_news' ) ? 'mbapp_news' : 'post';

$latest = new WP_Query(
	array(
		'post_type'           => $news_type,
		'posts_per_page'      => 6,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( $latest->have_posts() ) :
	?>
	<section class="mb-section">
		<div class="mb-section__head">
			<h2 class="mb-section__title"><?php esc_html_e( 'Friss hírek', 'mbapp-theme' ); ?></h2>
			<a class="mb-section__link" href="<?php echo esc_url( get_post_type_archive_link( $news_type ) ? get_post_type_archive_link( $news_type ) : home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Összes', 'mbapp-theme' ); ?> &rarr;
			</a>
		</div>

		<div class="mb-grid mb-grid--3">
			<?php
			while ( $latest->have_posts() ) :
				$latest->the_post();
				get_template_part( 'template-parts/content', 'card' );
			endwhile;
			?>
		</div>
	</section>
	<?php
	wp_reset_postdata();
endif;

get_footer();
