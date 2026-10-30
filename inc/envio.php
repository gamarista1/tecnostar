<?php
/**
 * Entrega: el cliente elige su departamento (sin valor por defecto) y siempre puede recoger en una tienda.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** Tiendas donde se puede recoger. Se amplía con el filtro `tecnostar_tiendas`. */
function tecnostar_tiendas() {
	return apply_filters(
		'tecnostar_tiendas',
		array(
			'chapinero-cat' => array(
				'nombre'    => 'Tecnostar Chapinero (CAT)',
				'direccion' => 'Calle 77 # 15-58, Entrada 3, Centro de Alta Tecnología, Local 1-94 / 1-116, Bogotá',
			),
		)
	);
}

// Un cliente nuevo no parte con "Distrito Capital": solo el país, y elige su departamento.
add_filter(
	'woocommerce_customer_default_location',
	function ( $ubicacion ) {
		$partes = explode( ':', (string) $ubicacion );
		return $partes[0];
	}
);

// Recoger en tienda: una opción por tienda, gratis, disponible en cualquier departamento.
add_filter(
	'woocommerce_package_rates',
	function ( $rates ) {
		foreach ( tecnostar_tiendas() as $slug => $t ) {
			$id   = 'ts_recoger:' . $slug;
			$rate = new WC_Shipping_Rate( $id, 'Recoger en ' . $t['nombre'], 0, array(), 'ts_recoger' );
			$rate->add_meta_data( 'Tienda', $t['nombre'] . ' · ' . $t['direccion'] );
			$rates[ $id ] = $rate;
		}
		return $rates;
	},
	100
);

// Debajo del nombre de la opción se muestra la dirección de la tienda.
add_filter(
	'woocommerce_cart_shipping_method_full_label',
	function ( $label, $method ) {
		if ( 0 !== strpos( $method->get_id(), 'ts_recoger:' ) ) {
			return $label;
		}
		$slug = substr( $method->get_id(), strlen( 'ts_recoger:' ) );
		$t    = tecnostar_tiendas();
		$dir  = isset( $t[ $slug ] ) ? $t[ $slug ]['direccion'] : '';
		return '<span class="ts-envio__nombre">' . esc_html( $method->get_label() ) . ' <b class="ts-envio__gratis">Gratis</b></span>' . ( $dir ? '<small class="ts-envio__sub">' . esc_html( $dir ) . '</small>' : '' );
	},
	10,
	2
);
