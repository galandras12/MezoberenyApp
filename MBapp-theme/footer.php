<?php
/**
 * Lábléc.
 *
 * @package MBapp_Theme
 */

defined( 'ABSPATH' ) || exit;
?>
		</div><!-- .mb-container -->
	</main><!-- .mb-main -->

	<?php if ( mbapp_theme_get_option( 'show_footer', false ) ) : ?>
	<footer class="mb-footer" role="contentinfo">
		<div class="mb-container">
			<div class="mb-footer__grid">
				<div>
					<?php mbapp_theme_brand(); ?>
					<?php
					$footer_text = mbapp_theme_get_option( 'footer_text', '' );
					if ( $footer_text ) :
						?>
						<div class="mb-footer__text"><?php echo wp_kses_post( wpautop( $footer_text ) ); ?></div>
					<?php endif; ?>
				</div>

				<?php if ( has_nav_menu( 'footer' ) || has_nav_menu( 'primary' ) ) : ?>
					<nav aria-label="<?php esc_attr_e( 'Lábléc menü', 'mbapp-theme' ); ?>">
						<h2 class="widget-title"><?php esc_html_e( 'Menü', 'mbapp-theme' ); ?></h2>
						<?php
						wp_nav_menu(
							array(
								'theme_location' => has_nav_menu( 'footer' ) ? 'footer' : 'primary',
								'menu_class'     => 'mb-footer__menu',
								'container'      => false,
								'depth'          => 1,
								'fallback_cb'    => false,
							)
						);
						?>
					</nav>
				<?php endif; ?>

				<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
					<div><?php dynamic_sidebar( 'footer-1' ); ?></div>
				<?php endif; ?>
			</div>

			<div class="mb-footer__bottom">
				<span>
					&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>
				</span>
				<button type="button" class="mb-btn mb-btn--ghost mb-theme-toggle">
					<span class="mb-theme-toggle__icon mb-theme-toggle__icon--sun"><?php mbapp_theme_the_icon( 'sun', 18 ); ?></span>
					<span class="mb-theme-toggle__icon mb-theme-toggle__icon--moon"><?php mbapp_theme_the_icon( 'moon', 18 ); ?></span>
					<?php esc_html_e( 'Téma váltása', 'mbapp-theme' ); ?>
				</button>
			</div>
		</div>
	</footer>
	<?php endif; ?>

	<?php mbapp_theme_dock(); ?>

</div><!-- .mb-app -->

<?php wp_footer(); ?>
</body>
</html>
