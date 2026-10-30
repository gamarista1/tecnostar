<?php
/**
 * Carrito y pedido recibido con la marca: estilos y comportamiento propios.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

// Sin ventas cruzadas del tema anterior: el carrito se enfoca en pagar.
add_action(
	'init',
	function () {
		remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$cart = is_cart();
		$ok   = is_wc_endpoint_url( 'order-received' );
		if ( ! $cart && ! $ok ) {
			return;
		}
		$css = get_template_directory_uri() . '/assets/css/';
		wp_enqueue_style( 'tecnostar-cart', $css . 'cart.css', array( 'tecnostar-components' ), tecnostar_asset_ver( '/assets/css/cart.css' ) );
		wp_enqueue_script( 'tecnostar-cart', get_template_directory_uri() . '/assets/js/cart.js', array( 'jquery' ), tecnostar_asset_ver( '/assets/js/cart.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	},
	20
);
