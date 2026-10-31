<?php
/**
 * Características del producto (procesador, RAM, almacenamiento, pantalla, batería) como icono + valor.
 * Recibe el ID por el argumento de get_template_part( ..., array( 'product_id' => ID ) ) o por set_query_var( 'product_id', ... ).
 * Tooltip con el mismo patrón que el de Wompi (.ts-tip / .ts-tip__globo): hover, foco de teclado y toque
 * (assets/js/ficha.js alterna .is-abierto al tocar; Escape cierra). Estilos en assets/css/tienda.css.
 */

defined( 'ABSPATH' ) || exit;

$product_id = isset( $args['product_id'] ) ? (int) $args['product_id'] : (int) get_query_var( 'product_id' );
if ( ! $product_id || ! function_exists( 'tecnostar_campos_texto' ) ) {
	return;
}

$svg_ini = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">';
$iconos  = array(
	'_ts_procesador'     => $svg_ini . '<rect x="6" y="6" width="12" height="12" rx="1.5"/><rect x="9.5" y="9.5" width="5" height="5"/><path d="M9 2v4M15 2v4M9 18v4M15 18v4M2 9h4M2 15h4M18 9h4M18 15h4"/></svg>',
	'_ts_ram'            => $svg_ini . '<rect x="2" y="7" width="20" height="9" rx="1.5"/><path d="M6 16v3M10 16v3M14 16v3M18 16v3M6 10.5v2M10 10.5v2M14 10.5v2M18 10.5v2"/></svg>',
	'_ts_almacenamiento' => $svg_ini . '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 14h18"/><circle cx="17" cy="16.5" r=".6" fill="currentColor"/></svg>',
	'_ts_pantalla'       => $svg_ini . '<rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8M12 16v4"/></svg>',
	'_ts_bateria'        => $svg_ini . '<rect x="2" y="8" width="17" height="8" rx="1.5"/><path d="M22 11v2M6 11v2M9.5 11v2"/></svg>',
);

$items = array();
foreach ( tecnostar_campos_texto() as $meta => $etiqueta ) {
	$valor = trim( (string) get_post_meta( $product_id, $meta, true ) );
	if ( '' !== $valor && isset( $iconos[ $meta ] ) ) {
		$items[] = array( 'meta' => $meta, 'etiqueta' => $etiqueta, 'valor' => $valor );
	}
}
if ( empty( $items ) ) {
	return;
}
?>
<ul class="ts-meta" aria-label="Características" data-ts-meta>
	<?php foreach ( $items as $i => $item ) : ?>
		<?php $tip_id = 'ts-tip-' . $product_id . '-' . $i; ?>
		<li class="ts-meta__item">
			<button type="button" class="ts-tip ts-meta__btn" data-tip data-meta="<?php echo esc_attr( $item['meta'] ); ?>" aria-label="<?php echo esc_attr( $item['etiqueta'] . ': ' . $item['valor'] ); ?>" aria-describedby="<?php echo esc_attr( $tip_id ); ?>">
				<span class="ts-meta__icono"><?php echo $iconos[ $item['meta'] ]; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG fijo del tema. ?></span>
				<span class="ts-meta__valor"><?php echo esc_html( $item['valor'] ); ?></span>
				<span id="<?php echo esc_attr( $tip_id ); ?>" class="ts-tip__globo" role="tooltip"><?php echo esc_html( $item['etiqueta'] ); ?></span>
			</button>
		</li>
	<?php endforeach; ?>
</ul>
