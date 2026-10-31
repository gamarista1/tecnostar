<?php
/**
 * Tarjeta de sede de la home. Recibe por $args: id (canal de WhatsApp), nombre, direccion, horario, ciudad.
 * Dirección, horario y mapas son de ejemplo hasta que la clienta los confirme (se marcan con tecnostar_ejemplo).
 */
defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	isset( $args ) ? $args : array(),
	array( 'id' => '', 'nombre' => '', 'ciudad' => '', 'direccion' => '', 'horario' => '' )
);
$ej = function ( $id, $html ) {
	return function_exists( 'tecnostar_ejemplo' ) ? tecnostar_ejemplo( $id, $html ) : $html;
};
$wa = function_exists( 'tecnostar_wa_url' ) ? tecnostar_wa_url( $a['id'], 'Hola, quiero información de la sede ' . $a['nombre'] ) : '#';
$q = rawurlencode( $a['direccion'] . ', ' . $a['ciudad'] . ', Colombia' );
?>
<article class="sede" data-reveal>
	<h3 class="sede__nombre"><?php echo esc_html( $a['nombre'] ); ?></h3>
	<p class="sede__dato"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><span><?php echo $ej( 'home-sede-' . $a['id'] . '-direccion', esc_html( $a['direccion'] ) ); ?></span></p>
	<p class="sede__dato"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span><?php echo $ej( 'home-sede-' . $a['id'] . '-horario', esc_html( $a['horario'] ) ); ?></span></p>
	<div class="sede__acciones">
		<a class="btn btn--accent" href="<?php echo esc_url( $wa ); ?>" data-wa-sede="<?php echo esc_attr( $a['id'] ); ?>" data-wa-abrir="<?php echo esc_attr( 'sede:' . $a['id'] ); ?>">Escríbenos</a>
		<?php echo $ej( 'home-sede-' . $a['id'] . '-mapas', '<a class="sede__enlace" href="' . esc_url( 'https://www.google.com/maps/search/?api=1&query=' . $q ) . '" target="_blank" rel="noopener">Google Maps</a> <a class="sede__enlace" href="' . esc_url( 'https://waze.com/ul?q=' . $q . '&navigate=yes' ) . '" target="_blank" rel="noopener">Waze</a>' ); ?>
	</div>
</article>
