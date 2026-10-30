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
			'cat' => array(
				'nombre'       => 'Tecnostar (CAT)',
				'departamento' => 'CO-DC',
				'detalles' => array(
					'Lugar'     => 'Centro Comercial de Alta Tecnología (CAT)',
					'Dirección' => 'Calle 77 # 15-58',
					'Acceso'    => 'Entrada 3, locales 1-94 / 1-116',
					'Localidad' => 'Chapinero',
					'Barrio'    => 'El Lago Gaitán',
				),
				'mapa'     => 'Tecnostar Ingeniería Electrónica, Cl. 77 #15-58, Chapinero, Bogotá, Colombia',
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
	function ( $rates, $package ) {
		// Las tiendas del departamento del cliente van primero; las demás siguen disponibles para quien viaja a retirar.
		$estado  = isset( $package['destination']['state'] ) ? $package['destination']['state'] : '';
		$tiendas = tecnostar_tiendas();
		uasort(
			$tiendas,
			function ( $a, $b ) use ( $estado ) {
				return ( ( isset( $b['departamento'] ) && $b['departamento'] === $estado ) <=> ( isset( $a['departamento'] ) && $a['departamento'] === $estado ) );
			}
		);
		foreach ( $tiendas as $slug => $t ) {
			$id   = 'ts_recoger:' . $slug;
			$rate = new WC_Shipping_Rate( $id, 'Recoger en ' . $t['nombre'], 0, array(), 'ts_recoger' );
			$rate->add_meta_data( 'Tienda', $t['nombre'] . ' · ' . $t['detalles']['Dirección'] . ', ' . $t['detalles']['Localidad'] );
			$rates[ $id ] = $rate;
		}
		return $rates;
	},
	100,
	2
);

// Presentación de la opción: nombre, datos de la tienda y botón para ver el mapa.
add_filter(
	'woocommerce_cart_shipping_method_full_label',
	function ( $label, $method ) {
		if ( 0 !== strpos( $method->get_id(), 'ts_recoger:' ) ) {
			return $label;
		}
		$slug   = substr( $method->get_id(), strlen( 'ts_recoger:' ) );
		$tienda = tecnostar_tiendas();
		if ( ! isset( $tienda[ $slug ] ) ) {
			return $label;
		}
		$t    = $tienda[ $slug ];
		$html = '<span class="ts-envio__nombre">' . esc_html( $t['nombre'] ) . ' <b class="ts-envio__gratis">Recoger gratis</b></span><dl class="ts-envio__datos">';
		foreach ( $t['detalles'] as $k => $v ) {
			$html .= '<div><dt>' . esc_html( $k ) . '</dt><dd>' . esc_html( $v ) . '</dd></div>';
		}
		$html .= '</dl>';
		if ( ! empty( $t['mapa'] ) ) {
			$html .= '<a href="#mapa" class="ts-envio__mapa" role="button" data-mapa="' . esc_attr( $slug ) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/></svg>Ver en el mapa</a>';
		}
		return $html;
	},
	10,
	2
);

// Ventana con el mapa de Google (el mapa se carga solo al abrirla).
add_action(
	'wp_footer',
	function () {
		if ( ! is_checkout() || is_order_received_page() ) {
			return;
		}
		$mapas = array();
		foreach ( tecnostar_tiendas() as $slug => $t ) {
			if ( ! empty( $t['mapa'] ) ) {
				$mapas[ $slug ] = array(
					'nombre' => $t['nombre'],
					'src'    => 'https://www.google.com/maps?q=' . rawurlencode( $t['mapa'] ) . '&output=embed',
					'abrir'  => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $t['mapa'] ),
				);
			}
		}
		?>
		<dialog class="ts-mapa" data-mapa-modal aria-labelledby="ts-mapa-t" data-mapas="<?php echo esc_attr( wp_json_encode( $mapas ) ); ?>">
			<div class="ts-mapa__cab">
				<h2 id="ts-mapa-t"></h2>
				<button type="button" class="ts-mapa__x" data-mapa-cerrar aria-label="<?php esc_attr_e( 'Cerrar mapa', 'tecnostar' ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
			</div>
			<div class="ts-mapa__cuerpo"><iframe title="<?php esc_attr_e( 'Mapa de la tienda', 'tecnostar' ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>
			<a class="ts-mapa__abrir" href="#" target="_blank" rel="noopener"><?php esc_html_e( 'Abrir en Google Maps', 'tecnostar' ); ?></a>
		</dialog>
		<?php
	}
);

/** Transportadoras para el resto del país (fuera de Bogotá). Se ajusta con el filtro `tecnostar_transportadoras`. */
function tecnostar_transportadoras() {
	return apply_filters(
		'tecnostar_transportadoras',
		array(
			'servientrega'    => array(
				'nombre' => 'Servientrega',
				'logo'   => 'servientrega.webp',
				'costo'  => 0,
				'nota'   => 'Flete por cobrar: lo pagas a la transportadora al recibir',
			),
			'interrapidisimo' => array(
				'nombre' => 'Inter Rapidísimo',
				'logo'   => 'interrapidisimo.webp',
				'costo'  => 0,
				'nota'   => 'Flete por cobrar: lo pagas a la transportadora al recibir',
			),
		)
	);
}

// Fuera de Distrito Capital el cliente elige transportadora.
add_filter(
	'woocommerce_package_rates',
	function ( $rates, $package ) {
		$estado = isset( $package['destination']['state'] ) ? $package['destination']['state'] : '';
		if ( '' === $estado || 'CO-DC' === $estado ) {
			return $rates;
		}
		foreach ( tecnostar_transportadoras() as $slug => $t ) {
			$id           = 'ts_transportadora:' . $slug;
			$rates[ $id ] = new WC_Shipping_Rate( $id, $t['nombre'], (float) $t['costo'], array(), 'ts_transportadora' );
		}
		return $rates;
	},
	99,
	2
);

add_filter(
	'woocommerce_cart_shipping_method_full_label',
	function ( $label, $method ) {
		if ( 0 !== strpos( $method->get_id(), 'ts_transportadora:' ) ) {
			return $label;
		}
		$slug = substr( $method->get_id(), strlen( 'ts_transportadora:' ) );
		$lista = tecnostar_transportadoras();
		if ( ! isset( $lista[ $slug ] ) ) {
			return $label;
		}
		$t    = $lista[ $slug ];
		$logo = get_template_directory_uri() . '/assets/img/' . $t['logo'];
		return '<img class="ts-envio__logo" src="' . esc_url( $logo ) . '" width="48" height="48" alt="" loading="lazy" /><span class="ts-envio__texto"><span class="ts-envio__nombre">' . esc_html( $t['nombre'] ) . '</span>' . ( $t['nota'] ? '<small class="ts-envio__sub">' . esc_html( $t['nota'] ) . '</small>' : '' ) . '</span>';
	},
	10,
	2
);

// Si el cliente recoge en tienda, la dirección no es obligatoria.
add_filter(
	'woocommerce_checkout_fields',
	function ( $fields ) {
		$esta_enviando = isset( $_GET['wc-ajax'] ) && 'checkout' === $_GET['wc-ajax']; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $esta_enviando || empty( $_POST['shipping_method'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $fields;
		}
		$elegido = (array) wp_unslash( $_POST['shipping_method'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$primero = reset( $elegido );
		if ( is_string( $primero ) && 0 === strpos( $primero, 'ts_recoger:' ) ) {
			foreach ( array( 'billing_state', 'billing_city', 'billing_address_1', 'billing_postcode' ) as $k ) {
				if ( isset( $fields['billing'][ $k ] ) ) {
					$fields['billing'][ $k ]['required'] = false;
				}
			}
		}
		return $fields;
	},
	100
);
