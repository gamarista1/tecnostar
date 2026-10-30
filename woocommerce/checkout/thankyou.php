<?php
/**
 * Pedido recibido con la marca. Los datos de pago de cada pasarela se siguen mostrando con sus propios hooks.
 *
 * @package tecnostar
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="ts-ok woocommerce-order">
	<?php
	if ( $order ) :
		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		$fallido = $order->has_status( 'failed' );
		?>
		<header class="ts-ok__hero<?php echo $fallido ? ' is-fallido' : ''; ?>">
			<span class="ts-ok__sello" aria-hidden="true">
				<?php if ( $fallido ) : ?>
					<svg viewBox="0 0 24 24"><path d="M7 7l10 10M17 7L7 17"/></svg>
				<?php else : ?>
					<svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
				<?php endif; ?>
			</span>
			<h2 class="ts-ok__titulo" tabindex="-1">
				<?php echo $fallido ? esc_html__( 'No pudimos procesar el pago', 'tecnostar' ) : esc_html__( '¡Gracias! Recibimos tu pedido', 'tecnostar' ); ?>
			</h2>
			<?php if ( $fallido ) : ?>
				<p><?php esc_html_e( 'Tu banco no aceptó la transacción. Puedes intentarlo de nuevo o elegir otro medio de pago.', 'tecnostar' ); ?></p>
				<p class="ts-ok__acciones">
					<a class="ts-btn ts-btn--primario" href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>"><?php esc_html_e( 'Pagar de nuevo', 'tecnostar' ); ?></a>
					<?php if ( is_user_logged_in() ) : ?>
						<a class="ts-btn ts-btn--suave" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Mi cuenta', 'tecnostar' ); ?></a>
					<?php endif; ?>
				</p>
			<?php else : ?>
				<p><?php echo esc_html( sprintf( /* translators: %s: correo */ __( 'Te enviamos la confirmación a %s. Te avisaremos cuando salga tu pedido.', 'tecnostar' ), $order->get_billing_email() ) ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( ! $fallido ) : ?>
			<ul class="ts-ok__datos">
				<li><span><?php esc_html_e( 'Pedido', 'tecnostar' ); ?></span><strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong></li>
				<li><span><?php esc_html_e( 'Fecha', 'tecnostar' ); ?></span><strong><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></strong></li>
				<li><span><?php esc_html_e( 'Total', 'tecnostar' ); ?></span><strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong></li>
				<?php if ( $order->get_payment_method_title() ) : ?>
					<li><span><?php esc_html_e( 'Pago', 'tecnostar' ); ?></span><strong><?php echo esc_html( wp_strip_all_tags( $order->get_payment_method_title() ) ); ?></strong></li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>

		<div class="ts-ok__detalle">
			<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
			<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
		</div>
		<p class="ts-ok__pie"><a class="ts-btn ts-btn--suave" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Seguir comprando', 'tecnostar' ); ?></a></p>
	<?php else : ?>
		<header class="ts-ok__hero">
			<span class="ts-ok__sello" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>
			<h2 class="ts-ok__titulo" tabindex="-1"><?php esc_html_e( '¡Gracias! Recibimos tu pedido', 'tecnostar' ); ?></h2>
		</header>
	<?php endif; ?>
</div>
