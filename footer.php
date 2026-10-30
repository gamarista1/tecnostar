<?php defined( 'ABSPATH' ) || exit; ?>
<?php if ( ! is_front_page() ) : ?>
</div>
<?php endif; ?>
</main>
<footer class="site-footer">
	<div class="container">
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
		<nav aria-label="<?php esc_attr_e( 'Pie', 'tecnostar' ); ?>">
			<?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'menu_class' => 'footer-nav', 'fallback_cb' => false ) ); ?>
		</nav>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
