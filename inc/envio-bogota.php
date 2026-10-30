<?php
/**
 * Lógica exclusiva de Bogotá: departamento "D.C. Bogotá", localidad en lugar de ciudad y domicilio gratis desde un mínimo.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** Costo del domicilio en Bogotá cuando la compra no llega al mínimo de envío gratis. Valor provisional: se ajusta con el filtro. */
function tecnostar_envio_bogota_costo() {
	return (float) apply_filters( 'tecnostar_envio_bogota_costo', 10000 );
}

// El departamento se llama "D.C. Bogotá": el Distrito Capital es solo Bogotá.
add_filter(
	'woocommerce_states',
	function ( $estados ) {
		if ( isset( $estados['CO']['CO-DC'] ) ) {
			$estados['CO']['CO-DC'] = 'D.C. Bogotá';
		}
		return $estados;
	}
);

/** Las 20 localidades de Bogotá. */
function tecnostar_localidades() {
	return array( 'Usaquén', 'Chapinero', 'Santa Fe', 'San Cristóbal', 'Usme', 'Tunjuelito', 'Bosa', 'Kennedy', 'Fontibón', 'Engativá', 'Suba', 'Barrios Unidos', 'Teusaquillo', 'Los Mártires', 'Antonio Nariño', 'Puente Aranda', 'La Candelaria', 'Rafael Uribe Uribe', 'Ciudad Bolívar', 'Sumapaz' );
}

// Campo "Localidad": solo se usa cuando el departamento es D.C. Bogotá.
add_filter(
	'woocommerce_checkout_fields',
	function ( $campos ) {
		$lista                                  = tecnostar_localidades();
		$campos['billing']['billing_localidad'] = array(
			'type'     => 'select',
			'label'    => 'Localidad',
			'required' => false,
			'class'    => array( 'form-row-wide' ),
			'options'  => array( '' => 'Selecciona tu localidad' ) + array_combine( $lista, $lista ),
			'priority' => 66,
		);
		return $campos;
	},
	101
);

// En Bogotá la ciudad se sobreentiende.
add_filter(
	'woocommerce_checkout_posted_data',
	function ( $datos ) {
		if ( isset( $datos['billing_state'] ) && 'CO-DC' === $datos['billing_state'] ) {
			$datos['billing_city'] = 'Bogotá D.C.';
		}
		return $datos;
	}
);

add_action(
	'woocommerce_after_checkout_validation',
	function ( $datos, $errores ) {
		$metodo = isset( $datos['shipping_method'][0] ) ? (string) $datos['shipping_method'][0] : '';
		if ( isset( $datos['billing_state'] ) && 'CO-DC' === $datos['billing_state'] && 0 !== strpos( $metodo, 'ts_recoger:' ) && empty( $datos['billing_localidad'] ) ) {
			$errores->add( 'billing_localidad_required', 'Elige tu <strong>localidad</strong> de Bogotá.', array( 'id' => 'billing_localidad' ) );
		}
	},
	10,
	2
);

// La localidad queda en la dirección del pedido (la ven el equipo y la transportadora).
add_action(
	'woocommerce_checkout_create_order',
	function ( $pedido, $datos ) {
		if ( ! empty( $datos['billing_localidad'] ) && isset( $datos['billing_state'] ) && 'CO-DC' === $datos['billing_state'] ) {
			$pedido->set_billing_address_2( trim( $pedido->get_billing_address_2() . ' · Localidad ' . $datos['billing_localidad'], ' ·' ) );
		}
	},
	10,
	2
);

// Bogotá: domicilio gratis desde el mínimo; antes, con costo. Sustituye cualquier otra tarifa del plugin para Bogotá.
add_filter(
	'woocommerce_package_rates',
	function ( $rates, $package ) {
		$estado = isset( $package['destination']['state'] ) ? $package['destination']['state'] : '';
		if ( 'CO-DC' !== $estado ) {
			return $rates;
		}
		foreach ( array_keys( $rates ) as $id ) {
			if ( 0 !== strpos( $id, 'ts_' ) ) {
				unset( $rates[ $id ] );
			}
		}
		$gratis = (float) $package['contents_cost'] >= tecnostar_envio_gratis_desde();
		$id     = 'ts_bogota:domicilio';
		return array( $id => new WC_Shipping_Rate( $id, 'Domicilio en Bogotá', $gratis ? 0 : tecnostar_envio_bogota_costo(), array(), 'ts_bogota' ) ) + $rates;
	},
	98,
	2
);

add_filter(
	'woocommerce_cart_shipping_method_full_label',
	function ( $label, $metodo ) {
		if ( 'ts_bogota:domicilio' !== $metodo->get_id() ) {
			return $label;
		}
		$meta = tecnostar_envio_gratis_desde();
		if ( (float) $metodo->get_cost() <= 0 ) {
			return '<span class="ts-envio__nombre">Domicilio en Bogotá <b class="ts-envio__gratis">Gratis</b></span><small class="ts-envio__sub">Compras desde ' . wp_strip_all_tags( wc_price( $meta ) ) . '</small>';
		}
		$falta = max( 0, $meta - (float) WC()->cart->get_displayed_subtotal() );
		return '<span class="ts-envio__nombre">Domicilio en Bogotá · ' . wp_strip_all_tags( wc_price( $metodo->get_cost() ) ) . '</span><small class="ts-envio__sub">Te faltan ' . wp_strip_all_tags( wc_price( $falta ) ) . ' para el domicilio gratis</small>';
	},
	9,
	2
);

/** Texto del envío en el resumen del pago. */
function tecnostar_envio_texto() {
	$elegidos = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods' ) : array();
	$id       = isset( $elegidos[0] ) ? (string) $elegidos[0] : '';
	if ( 0 === strpos( $id, 'ts_recoger:' ) ) {
		return 'Retiro en tienda · Gratis';
	}
	if ( 0 === strpos( $id, 'ts_transportadora:' ) ) {
		return 'Por cobrar al recibir';
	}
	return WC()->cart->get_cart_shipping_total();
}

// El carrito no habla de envío: aún no se sabe dónde se entrega. El envío se define en el pago.
add_filter(
	'woocommerce_cart_needs_shipping',
	function ( $necesita ) {
		return is_cart() ? false : $necesita;
	}
);
