<?php
/**
 * Pago en pasos: campos, formas de envío como fragmento propio y carga de estilos/JS.
 * La lógica de pedidos y pagos sigue siendo de WooCommerce y de los plugins de pago.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * Campos del pago: menos campos, etiquetas claras y atributos para el autocompletado y el teclado del celular.
 */
add_filter(
	'woocommerce_checkout_fields',
	function ( $fields ) {
		unset( $fields['billing']['billing_company'], $fields['shipping']['shipping_company'] );

		$cfg = array(
			'billing_first_name' => array( 'Nombre', 'Ej. Ana', 'given-name', '' ),
			'billing_last_name'  => array( 'Apellido', 'Ej. Pérez', 'family-name', '' ),
			'billing_id'         => array( 'Cédula o NIT', 'Solo números', 'off', 'numeric' ),
			'billing_email'      => array( 'Correo electrónico', 'tucorreo@ejemplo.com', 'email', 'email' ),
			'billing_phone'      => array( 'Celular (WhatsApp)', '300 123 4567', 'tel', 'tel' ),
			'billing_state'      => array( 'Departamento', 'Selecciona tu departamento', 'address-level1', '' ),
			'billing_city'       => array( 'Ciudad', 'Ej. Bogotá', 'address-level2', '' ),
			'billing_address_1'  => array( 'Dirección', 'Ej. Cra 7 #12-30', 'address-line1', '' ),
			'billing_address_2'  => array( 'Apartamento, torre o referencias', 'Ej. Apto 402, portería', 'address-line2', '' ),
		);
		foreach ( $cfg as $key => $c ) {
			if ( ! isset( $fields['billing'][ $key ] ) ) {
				continue;
			}
			$fields['billing'][ $key ]['label']        = $c[0];
			$fields['billing'][ $key ]['placeholder']  = $c[1];
			$fields['billing'][ $key ]['autocomplete'] = $c[2];
			if ( $c[3] ) {
				$fields['billing'][ $key ]['custom_attributes']['inputmode'] = $c[3];
			}
		}
		if ( isset( $fields['billing']['billing_id'] ) ) {
			$fields['billing']['billing_id']['required'] = true;
		}
		if ( isset( $fields['billing']['billing_postcode'] ) ) {
			$fields['billing']['billing_postcode']['required'] = false;
		}
		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = 'Notas para la entrega';
		$fields['billing']['billing_phone']['custom_attributes']['maxlength'] = '12';
			$fields['order']['order_comments']['placeholder'] = 'Ej. Dejar con el portero';
		}
		return $fields;
	},
	99
);

// Envío a la misma dirección de facturación: no se ofrece "enviar a otra dirección".
add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );

// El resumen del pedido va en la columna lateral (ver form-checkout.php); en #order_review solo queda el pago.
add_action(
	'init',
	function () {
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
	}
);

/**
 * Formas de envío para el paso 2. Se renderizan como fragmento para que se actualicen al cambiar la ciudad.
 */
function tecnostar_envio_html() {
	echo '<div class="ts-envio" aria-live="polite">';
	if ( WC()->cart && WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) {
		echo '<table class="ts-envio__tabla"><tbody>';
		wc_cart_totals_shipping_html();
		echo '</tbody></table>';
	} else {
		echo '<p class="ts-envio__vacio">Elige tu departamento y escribe tu dirección para ver cómo recibir tu pedido.</p>';
	}
	echo '</div>';
}

add_filter(
	'woocommerce_update_order_review_fragments',
	function ( $fragments ) {
		ob_start();
		tecnostar_envio_html();
		$fragments['.ts-envio'] = ob_get_clean();
		return $fragments;
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_checkout() || is_order_received_page() ) {
			return;
		}
		$css = get_template_directory_uri() . '/assets/css/';
		wp_enqueue_style( 'tecnostar-checkout', $css . 'checkout.css', array( 'tecnostar-components' ), tecnostar_asset_ver( '/assets/css/checkout.css' ) );
		wp_enqueue_script( 'tecnostar-checkout', get_template_directory_uri() . '/assets/js/checkout.js', array( 'jquery' ), tecnostar_asset_ver( '/assets/js/checkout.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	},
	20
);

// Acceso dentro del pago (solo para visitantes): tarjeta con volteo y envío sin recargar.
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_checkout() || is_order_received_page() || is_user_logged_in() ) {
			return;
		}
		wp_enqueue_script( 'tecnostar-checkout-login', get_template_directory_uri() . '/assets/js/checkout-login.js', array(), tecnostar_asset_ver( '/assets/js/checkout-login.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	},
	20
);
