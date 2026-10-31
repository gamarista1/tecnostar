<?php defined( 'ABSPATH' ) || exit; ?>
<?php if ( ! is_front_page() ) : ?>
</div>
<?php endif; ?>
</main>
<footer class="site-footer">
	<div class="container">
		<a class="footer-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/logo/logo-blanco-vertical.png' ) ); ?>" width="640" height="488" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" loading="lazy" decoding="async">
		</a>
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
		<nav aria-label="<?php esc_attr_e( 'Pie', 'tecnostar' ); ?>">
			<?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'menu_class' => 'footer-nav', 'fallback_cb' => false ) ); ?>
		</nav>
	</div>
</footer>
<?php
if ( function_exists( 'tecnostar_wa_visible' ) && tecnostar_wa_visible() ) {
	get_template_part( 'template-parts/wa-boton' );
}
?>
<?php wp_footer(); ?>
</body>
</html>
