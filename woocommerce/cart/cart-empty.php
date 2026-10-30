<?php
/**
 * Carrito vacío con la marca.
 *
 * @package tecnostar
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_cart_is_empty' );
?>
<div class="ts-ca-vacio">
	<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h8.6a1 1 0 0 0 1-.8L20 8H6.2"/><circle cx="9.5" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/></svg>
	<h2><?php esc_html_e( 'Tu carrito está vacío', 'tecnostar' ); ?></h2>
	<p><?php esc_html_e( 'Cuando agregues productos, aquí verás tu pedido listo para pagar.', 'tecnostar' ); ?></p>
	<a class="ts-btn ts-btn--primario" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Ir a la tienda', 'tecnostar' ); ?></a>
</div>
