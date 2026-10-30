<?php
/**
 * Totales del carrito. El envío no se muestra aquí: se define en el pago, cuando ya se sabe si se entrega o se recoge.
 *
 * @package tecnostar
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cart_totals">
	<?php do_action( 'woocommerce_before_cart_totals' ); ?>

	<h2><?php esc_html_e( 'Resumen', 'tecnostar' ); ?></h2>

	<table cellspacing="0" class="shop_table shop_table_responsive">
		<tr class="cart-subtotal">
			<th><?php esc_html_e( 'Subtotal', 'tecnostar' ); ?></th>
			<td data-title="<?php esc_attr_e( 'Subtotal', 'tecnostar' ); ?>"><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
				<td data-title="<?php echo esc_attr( wc_cart_totals_coupon_label( $coupon, false ) ); ?>"><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th><?php echo esc_html( $fee->name ); ?></th>
				<td data-title="<?php echo esc_attr( $fee->name ); ?>"><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<tr class="ts-ca__envio-fila">
			<th><?php esc_html_e( 'Envío', 'tecnostar' ); ?></th>
			<td><?php esc_html_e( 'Lo eliges en el pago', 'tecnostar' ); ?></td>
		</tr>

		<tr class="order-total">
			<th><?php esc_html_e( 'Total', 'tecnostar' ); ?></th>
			<td data-title="<?php esc_attr_e( 'Total', 'tecnostar' ); ?>"><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>
	</table>

	<div class="wc-proceed-to-checkout">
		<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
	</div>

	<?php do_action( 'woocommerce_after_cart_totals' ); ?>
</div>
