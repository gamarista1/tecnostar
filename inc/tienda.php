<?php
/**
 * Tienda: sellos de grado en la tarjeta de producto y en la ficha.
 * El Grado sale del atributo global pa_grado (ver mu-plugins/tecnostar-nucleo/productos.php).
 * Sin Grado, no hay sello.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** HTML del sello del producto, o cadena vacía si no tiene Grado. El texto lleva el significado; el color solo refuerza. */
function tecnostar_sello_html( int $product_id, string $contexto = 'tarjeta' ): string {
	if ( ! function_exists( 'tecnostar_grado' ) ) {
		return '';
	}
	$grado = tecnostar_grado( $product_id );
	if ( 'A' === $grado ) {
		$clase = 'a';
		$texto = 'Grado A · Nuevo · Garantía 1 año';
	} elseif ( 'B' === $grado ) {
		$clase = 'b';
		$texto = 'Grado B · Usado certificado · Garantía 6 meses';
	} else {
		return '';
	}
	$icono = 'a' === $clase
		? '<svg class="ts-sello__ico" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><path d="M8 1.5l5.5 2v4c0 3.3-2.3 5.7-5.5 7-3.2-1.3-5.5-3.7-5.5-7v-4z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M5.5 8l1.8 1.8L10.7 6.4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
		: '<svg class="ts-sello__ico" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><path d="M8 1.5l5.5 2v4c0 3.3-2.3 5.7-5.5 7-3.2-1.3-5.5-3.7-5.5-7v-4z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M5.5 8h5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
	$html = sprintf(
		'<span class="ts-sello ts-sello--%1$s ts-sello--%2$s" data-sello="%1$s">%3$s<span class="ts-sello__txt">%4$s</span></span>',
		esc_attr( $clase ),
		esc_attr( $contexto ),
		$icono,
		esc_html( $texto )
	);
	if ( 'ficha' === $contexto && 'b' === $clase ) {
		$html .= '<p class="ts-sello-nota" data-sello-nota>Rendimiento 100%, solo detalles estéticos leves.</p>';
	}
	return $html;
}

// Tarjeta (listados, home, relacionados): justo después de la imagen.
add_action( 'woocommerce_before_shop_loop_item_title', function () {
	echo tecnostar_sello_html( get_the_ID(), 'tarjeta' ); // phpcs:ignore WordPress.Security.EscapeOutput
}, 11 );

// Ficha: bajo el título.
add_action( 'woocommerce_single_product_summary', function () {
	$html = tecnostar_sello_html( get_the_ID(), 'ficha' );
	if ( $html ) {
		echo '<div class="ts-sello-wrap">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}, 6 );

// Estilos solo en tienda, categorías, ficha y portada.
add_action( 'wp_enqueue_scripts', function () {
	if ( is_front_page() || is_shop() || is_product_taxonomy() || is_product() ) {
		wp_enqueue_style( 'tecnostar-tienda', get_template_directory_uri() . '/assets/css/tienda.css', array( 'tecnostar-components' ), tecnostar_asset_ver( '/assets/css/tienda.css' ) );
	}
}, 20 );
