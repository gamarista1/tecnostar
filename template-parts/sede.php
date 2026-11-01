<?php
/**
 * Tarjeta de sede. Recibe por $args: id (canal de WhatsApp), nombre, direccion, horario, ciudad.
 * En la home es un resumen. Con 'detalle' => true (página Nosotros) suma el mapa diferido (imagen + "Ver mapa
 * interactivo": el iframe de Google solo se crea tras el clic, en contacto.js) y los botones "Cómo llegar" y Waze.
 * Para el detalle recibe además lat y lng (coordenadas de ejemplo hasta que la clienta las confirme).
 * Dirección, horario, mapas y coordenadas son de ejemplo hasta que la clienta los confirme (se marcan con tecnostar_ejemplo).
 */
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	isset( $args ) ? $args : array(),
	array( 'id' => '', 'nombre' => '', 'ciudad' => '', 'direccion' => '', 'horario' => '', 'detalle' => false, 'lat' => '', 'lng' => '' )
);
$ej = function ( $id, $html ) {
	return function_exists( 'tecnostar_ejemplo' ) ? tecnostar_ejemplo( $id, $html ) : $html;
};
$wa = function_exists( 'tecnostar_wa_url' ) ? tecnostar_wa_url( $a['id'], 'Hola, quiero información de la sede ' . $a['nombre'] ) : '#';
$q  = rawurlencode( $a['direccion'] . ', ' . $a['ciudad'] . ', Colombia' );

if ( $a['detalle'] ) :
	$pre  = 'nosotros-sede-' . $a['id'];
	$ll   = sprintf( '%.6F,%.6F', (float) $a['lat'], (float) $a['lng'] );
	$dir  = 'https://www.google.com/maps/dir/?api=1&destination=' . $q;
	$waze = 'https://waze.com/ul?ll=' . $ll . '&navigate=yes';
	$ver  = 'https://www.google.com/maps/search/?api=1&query=' . $q;
	$emb  = 'https://maps.google.com/maps?q=' . $ll . '&z=16&output=embed';
	if ( function_exists( 'tecnostar_pendiente' ) ) {
		tecnostar_pendiente( $pre . '-coordenadas', 'Coordenadas reales de la sede ' . $a['nombre'] . ' (Waze y mapa usan unas de ejemplo)', 'Nosotros > Sedes' );
	}
	?>
<article class="sede sede--detalle" aria-labelledby="<?php echo esc_attr( $pre ); ?>-t">
	<div class="sede__cuerpo">
		<h3 class="sede__nombre" id="<?php echo esc_attr( $pre ); ?>-t">Sede <?php echo esc_html( $a['nombre'] ); ?></h3>
		<p class="sede__dato"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><span><?php echo $ej( $pre . '-direccion', esc_html( $a['direccion'] . ', ' . $a['ciudad'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></p>
		<p class="sede__dato"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span><?php echo $ej( $pre . '-horario', esc_html( $a['horario'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></p>
		<div class="sede__acciones">
			<a class="btn btn--accent" href="<?php echo esc_url( $dir ); ?>" target="_blank" rel="noopener" data-ruta="google">Cómo llegar<span class="screen-reader-text"> a la sede <?php echo esc_html( $a['nombre'] ); ?> con Google Maps (se abre en una pestaña nueva)</span></a>
			<a class="btn sede__waze" href="<?php echo esc_url( $waze ); ?>" target="_blank" rel="noopener" data-ruta="waze">Waze<span class="screen-reader-text">: ir a la sede <?php echo esc_html( $a['nombre'] ); ?> (se abre en una pestaña nueva)</span></a>
			<a class="sede__enlace" href="<?php echo esc_url( $wa ); ?>" data-wa-sede="<?php echo esc_attr( $a['id'] ); ?>" data-wa-abrir="<?php echo esc_attr( 'sede:' . $a['id'] ); ?>">Escríbenos por WhatsApp<span class="screen-reader-text"> a la sede <?php echo esc_html( $a['nombre'] ); ?></span></a>
		</div>
	</div>
	<?php
	ob_start();
	?>
	<div class="sede__mapa" data-mapa="<?php echo esc_url( $emb ); ?>" data-mapa-titulo="<?php echo esc_attr( 'Mapa de la sede ' . $a['nombre'] ); ?>">
		<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/nosotros/mapa.svg' ) ); ?>" width="640" height="360" loading="lazy" decoding="async" alt="<?php echo esc_attr( 'Ubicación aproximada de la sede ' . $a['nombre'] . ' (imagen de ejemplo)' ); ?>">
		<a class="sede__mapa-btn" href="<?php echo esc_url( $ver ); ?>" target="_blank" rel="noopener" data-mapa-abrir>Ver mapa interactivo<span class="screen-reader-text"> de la sede <?php echo esc_html( $a['nombre'] ); ?></span></a>
	</div>
	<?php
	echo $ej( $pre . '-mapa', ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput
	?>
</article>
	<?php
	return;
endif;
?>
<article class="sede" data-reveal>
	<h3 class="sede__nombre"><?php echo esc_html( $a['nombre'] ); ?></h3>
	<p class="sede__dato"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><span><?php echo $ej( 'home-sede-' . $a['id'] . '-direccion', esc_html( $a['direccion'] ) ); ?></span></p>
	<p class="sede__dato"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span><?php echo $ej( 'home-sede-' . $a['id'] . '-horario', esc_html( $a['horario'] ) ); ?></span></p>
	<div class="sede__acciones">
		<a class="btn btn--accent" href="<?php echo esc_url( $wa ); ?>" data-wa-sede="<?php echo esc_attr( $a['id'] ); ?>" data-wa-abrir="<?php echo esc_attr( 'sede:' . $a['id'] ); ?>">Escríbenos<span class="screen-reader-text"> por WhatsApp a la sede <?php echo esc_html( $a['nombre'] ); ?></span></a>
		<?php echo $ej( 'home-sede-' . $a['id'] . '-mapas', '<a class="sede__enlace" href="' . esc_url( 'https://www.google.com/maps/search/?api=1&query=' . $q ) . '" target="_blank" rel="noopener">Google Maps</a> <a class="sede__enlace" href="' . esc_url( 'https://waze.com/ul?q=' . $q . '&navigate=yes' ) . '" target="_blank" rel="noopener">Waze</a>' ); ?>
	</div>
</article>
