<?php
/**
 * Ajustes de WooCommerce: la lógica de carrito, envío y pagos sigue en WooCommerce y sus plugins.
 * Aquí solo se mejora la presentación.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * Beneficios bajo el botón de compra de la ficha (mensajes comerciales de reglas-envio-y-pagos.md).
 */
add_action( 'woocommerce_single_product_summary', function () {
	?>
	<ul class="trust" aria-label="<?php esc_attr_e( 'Beneficios de compra', 'tecnostar' ); ?>">
		<li><strong>Envío gratis en Bogotá</strong> en compras desde $200.000</li>
		<li><strong>Recoge en la tienda</strong> sin costo de envío</li>
		<li><strong>Paga como prefieras:</strong> Wompi, Addi, Sistecrédito y más</li>
	</ul>
	<?php
}, 35 );

// Menos ruido en la ficha: se oculta la pestaña de valoraciones mientras no haya ninguna.
add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
	if ( isset( $tabs['reviews'] ) && 0 === (int) get_comments_number() ) {
		unset( $tabs['reviews'] );
	}
	return $tabs;
} );

// Sin migas de pan de WooCommerce sueltas encima de la cabecera: se mantienen, pero dentro del contenedor.
add_filter( 'woocommerce_breadcrumb_defaults', function ( $args ) {
	$args['wrap_before'] = '<nav class="woocommerce-breadcrumb" aria-label="' . esc_attr__( 'Ruta de navegación', 'tecnostar' ) . '">';
	return $args;
} );

// Ficha de producto: foto, información y pestañas en un bloque propio; así la columna sticky se suelta donde terminan las pestañas, antes de los relacionados.
add_action( 'woocommerce_before_single_product_summary', function () { echo '<div class="ts-pdp-top">'; }, 1 );
add_action( 'woocommerce_after_single_product_summary', function () { echo '</div>'; }, 11 );
