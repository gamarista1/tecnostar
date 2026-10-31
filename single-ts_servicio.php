<?php
/**
 * Plantilla de un servicio (ts_servicio).
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$precio_ref = get_post_meta( get_the_ID(), '_ts_precio_ref', true );
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'ts-servicio' ); ?>>
		<h1><?php the_title(); ?></h1>
		<div class="ts-servicio__contenido">
			<?php the_content(); ?>
		</div>
		<?php if ( $precio_ref ) : ?>
			<p class="ts-servicio__precio"><?php echo esc_html( $precio_ref ); ?></p>
		<?php endif; ?>
		<a class="button ts-servicio__cotizar" href="#cotizar" data-wa-abrir="<?php echo esc_attr( 'servicio:' . get_post_field( 'post_name', get_the_ID() ) ); ?>">Cotizar</a>
	</article>
	<?php
endwhile;

get_footer();
