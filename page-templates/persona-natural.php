<?php
/**
 * Template Name: Persona Natural
 *
 * Índice de servicios para personas: hero, listado (tipo ts_servicio), antes/después y diagnóstico gratis.
 * Servicio con página (publicado y con contenido) enlaza a /persona-natural/<slug>/; sin página abre WhatsApp con su nombre.
 *
 * @package tecnostar
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ej = function ( $id, $html ) {
	return function_exists( 'tecnostar_ejemplo' ) ? tecnostar_ejemplo( $id, $html ) : $html;
};

$servicios = new WP_Query(
	array(
		'post_type'      => 'ts_servicio',
		'post_status'    => 'publish',
		'posts_per_page' => 24,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'no_found_rows'  => true,
	)
);
?>
<div class="pn">
	<section class="pn-hero">
		<div class="container pn-hero__in">
			<p class="pn-hero__eyebrow"><?php echo esc_html( get_the_title() ); ?></p>
			<h1 class="pn-hero__t">Realizamos diagnóstico a tu equipo y te sugerimos lo ideal para que siga apto. ¡Saca el máximo provecho a tu equipo!</h1>
			<p class="pn-hero__precio"><strong>Repotenciación desde $130.000</strong></p>
			<a class="btn btn--accent" href="#diagnostico" data-wa-abrir="Cotizar diagnóstico gratis">Cotizar Diagnóstico Gratis</a>
		</div>
	</section>

	<section class="section pn-servicios" aria-labelledby="pn-servicios-t">
		<div class="container">
			<div class="section__head"><h2 id="pn-servicios-t">Nuestros servicios</h2></div>
			<?php ob_start(); ?>
			<ul class="pn-grid">
				<?php
				while ( $servicios->have_posts() ) :
					$servicios->the_post();
					$id     = get_the_ID();
					$titulo = get_the_title();
					$precio = get_post_meta( $id, '_ts_precio_ref', true );
					$icono  = get_post_meta( $id, '_ts_icono', true );
					$desc   = has_excerpt() ? get_the_excerpt() : '';
					$pagina = tecnostar_servicio_tiene_pagina( $id );
					$wa     = function_exists( 'tecnostar_wa_url' ) ? tecnostar_wa_url( 'bogota', 'Hola, quiero cotizar: ' . $titulo ) : '#';
					?>
					<li class="pn-card">
						<span class="pn-card__ico"><?php echo tecnostar_icono_servicio( (string) $icono ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<h3 class="pn-card__t"><?php echo esc_html( $titulo ); ?></h3>
						<?php if ( $desc ) : ?>
							<p class="pn-card__d"><?php echo esc_html( $desc ); ?></p>
						<?php endif; ?>
						<?php if ( $precio ) : ?>
							<p class="pn-card__p"><?php echo esc_html( $precio ); ?></p>
						<?php endif; ?>
						<?php if ( $pagina ) : ?>
							<a class="pn-card__a" href="<?php echo esc_url( get_permalink() ); ?>">Ver el servicio<span class="screen-reader-text">: <?php echo esc_html( $titulo ); ?></span></a>
						<?php else : ?>
							<a class="pn-card__a" href="<?php echo esc_url( $wa ); ?>" data-wa-abrir="<?php echo esc_attr( 'Cotizar ' . $titulo ); ?>">Cotizar por WhatsApp<span class="screen-reader-text">: <?php echo esc_html( $titulo ); ?></span></a>
						<?php endif; ?>
					</li>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</ul>
			<?php echo $ej( 'persona-natural-servicios', ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>

	<section class="section section--alt pn-ad" aria-labelledby="pn-ad-t">
		<div class="container">
			<div class="section__head"><h2 id="pn-ad-t">Antes y después</h2></div>
			<?php
			$img = get_theme_file_uri( 'assets/img/persona-natural/' );
			ob_start();
			?>
			<div class="pn-ad__par">
				<figure class="pn-ad__f">
					<img src="<?php echo esc_url( $img . 'antes.svg' ); ?>" width="640" height="400" loading="lazy" decoding="async" alt="Equipo antes del servicio (foto de ejemplo)">
					<figcaption>Antes</figcaption>
				</figure>
				<figure class="pn-ad__f">
					<img src="<?php echo esc_url( $img . 'despues.svg' ); ?>" width="640" height="400" loading="lazy" decoding="async" alt="Equipo después del servicio (foto de ejemplo)">
					<figcaption>Después</figcaption>
				</figure>
			</div>
			<?php echo $ej( 'persona-natural-antes-despues', ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>

	<section id="diagnostico" class="section pn-cta" aria-labelledby="pn-cta-t">
		<div class="container pn-cta__in">
			<h2 id="pn-cta-t">¿No sabes qué necesita tu equipo?</h2>
			<p>Lo revisamos sin costo y te decimos qué conviene hacerle.</p>
			<a class="btn btn--accent" href="<?php echo esc_url( function_exists( 'tecnostar_wa_url' ) ? tecnostar_wa_url( 'bogota', 'Hola, quiero cotizar un diagnóstico gratis' ) : '#' ); ?>" data-wa-abrir="Cotizar diagnóstico gratis">Cotizar Diagnóstico Gratis</a>
		</div>
	</section>
	<?php the_content(); ?>
</div>
<?php
get_footer();
