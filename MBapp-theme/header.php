<?php
/**
 * Fejléc.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#mb-content"><?php esc_html_e( 'Ugrás a tartalomra', 'mbapp-theme' ); ?></a>

<div class="mb-app">

	<header class="mb-appbar" role="banner">
		<div class="mb-appbar__inner">
			<?php mbapp_theme_brand(); ?>

			<span class="mb-appbar__spacer"></span>

			<div class="mb-appbar__actions">
				<?php if ( mbapp_theme_get_option( 'show_search', true ) ) : ?>
					<button type="button"
						class="mb-iconbtn mb-search-toggle"
						aria-expanded="false"
						aria-controls="mb-searchpanel"
						aria-label="<?php esc_attr_e( 'Keresés', 'mbapp-theme' ); ?>">
						<?php mbapp_theme_the_icon( 'search' ); ?>
					</button>
				<?php endif; ?>

				<button type="button"
					class="mb-iconbtn mb-theme-toggle"
					aria-pressed="false"
					aria-label="<?php esc_attr_e( 'Sötét téma bekapcsolása', 'mbapp-theme' ); ?>">
					<span class="mb-theme-toggle__icon mb-theme-toggle__icon--sun"><?php mbapp_theme_the_icon( 'sun' ); ?></span>
					<span class="mb-theme-toggle__icon mb-theme-toggle__icon--moon"><?php mbapp_theme_the_icon( 'moon' ); ?></span>
				</button>
			</div>
		</div>

		<?php if ( mbapp_theme_get_option( 'show_search', true ) ) : ?>
			<div class="mb-searchpanel" id="mb-searchpanel">
				<div class="mb-searchpanel__inner">
					<?php get_search_form(); ?>
				</div>
			</div>
		<?php endif; ?>
	</header>

	<main class="mb-main" id="mb-content" role="main">
		<div class="mb-container">
