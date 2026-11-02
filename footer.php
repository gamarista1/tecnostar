<?php defined( 'ABSPATH' ) || exit; ?>
<?php if ( ! is_front_page() ) : ?>
</div>
<?php endif; ?>
</main>
<footer class="site-footer">
	<section class="footer-cta" aria-labelledby="footer-cta-t">
		<div class="container footer-cta__in">
			<div>
				<h2 id="footer-cta-t">¿No encuentras lo que buscas?</h2>
				<p>Si no lo tenemos, te lo conseguimos.</p>
			</div>
			<a class="btn btn--accent" href="#" data-wa-abrir="busqueda">Escríbenos y te lo cotizamos</a>
		</div>
	</section>
	<div class="container footer-main">
		<div class="footer-marca">
			<a class="footer-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/logo/logo-blanco-vertical.png' ) ); ?>" width="640" height="488" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" loading="lazy" decoding="async">
			</a>
			<p class="footer-lema">Equipos nuevos y usados certificados, diagnóstico y soporte técnico.</p>
			<?php if ( function_exists( 'tecnostar_redes_sociales' ) ) : ?>
				<ul class="footer-redes" aria-label="<?php esc_attr_e( 'Redes sociales', 'tecnostar' ); ?>">
					<?php foreach ( tecnostar_redes_sociales() as $r ) : ?>
						<li><a href="<?php echo esc_url( $r['url'] ); ?>" target="_blank" rel="noopener noreferrer" style="--c:<?php echo esc_attr( $r['c'] ); ?>" title="<?php echo esc_attr( $r['nombre'] ); ?>"><?php echo $r['svg']; // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="screen-reader-text"><?php echo esc_html( $r['nombre'] ); ?> (se abre en una pestaña nueva)</span></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<nav class="footer-col" aria-labelledby="footer-nav-t">
			<h2 class="footer-tit" id="footer-nav-t">Explora</h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => has_nav_menu( 'footer' ) ? 'footer' : 'primary',
					'container'      => false,
					'menu_class'     => 'footer-nav',
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
			?>
		</nav>
		<div class="footer-col">
			<h2 class="footer-tit">Nuestras sedes</h2>
			<ul class="footer-sedes">
				<li><strong>Bogotá</strong><span>C.C. Alta Tecnología, local 194</span></li>
				<li><strong>Tocancipá</strong><span>C.C. Milán Plaza, local 5-2</span></li>
			</ul>
			<button type="button" class="footer-wa" data-wa-abrir="">Escríbenos por WhatsApp</button>
		</div>
	</div>
	<div class="container footer-legal">
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
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
