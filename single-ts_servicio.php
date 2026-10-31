<?php
/**
 * Plantilla de un servicio (ts_servicio): explicación, precio de referencia si existe y botón de cotizar.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$precio_ref = get_post_meta( get_the_ID(), '_ts_precio_ref', true );
	$icono      = get_post_meta( get_the_ID(), '_ts_icono', true );
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'ts-servicio container' ); ?>>
		<p class="ts-servicio__volver"><a href="<?php echo esc_url( home_url( '/persona-natural/' ) ); ?>">&larr; Todos los servicios</a></p>
		<header class="ts-servicio__cab">
			<span class="pn-card__ico"><?php echo tecnostar_icono_servicio( (string) $icono ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<h1><?php the_title(); ?></h1>
		</header>
		<div class="ts-servicio__contenido">
			<?php the_content(); ?>
		</div>
		<?php if ( $precio_ref ) : ?>
			<p class="ts-servicio__precio"><?php echo esc_html( $precio_ref ); ?></p>
		<?php endif; ?>
		<a class="btn btn--accent ts-servicio__cotizar" href="<?php echo esc_url( function_exists( 'tecnostar_wa_url' ) ? tecnostar_wa_url( 'bogota', 'Hola, quiero cotizar: ' . get_the_title() ) : '#cotizar' ); ?>" data-wa-abrir="<?php echo esc_attr( 'servicio:' . get_post_field( 'post_name', get_the_ID() ) ); ?>">Cotizar</a>
	</article>
	<?php
endwhile;

get_footer();
