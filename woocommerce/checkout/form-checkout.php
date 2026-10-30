<?php
/**
 * Pago en 3 pasos (Contacto, Entrega, Pago) con resumen del pedido siempre visible.
 * Es el mismo <form class="checkout"> de WooCommerce: los campos, nonces y ganchos se conservan,
 * así que Wompi, Addi y el pedido por WhatsApp siguen funcionando como siempre.
 *
 * @package tecnostar
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'Debes iniciar sesión para finalizar la compra.', 'tecnostar' ) ) );
	return;
}

$billing   = $checkout->get_checkout_fields( 'billing' );
$paso1     = array( 'billing_first_name', 'billing_last_name', 'billing_id', 'billing_email', 'billing_phone' );
$paso2     = array( 'billing_country', 'billing_state', 'billing_city', 'billing_address_1', 'billing_address_2', 'billing_postcode' );
$extras    = array_diff( array_keys( $billing ), $paso1, $paso2 );
$nombres   = array( 'Contacto', 'Entrega', 'Pago' );
$campos    = function ( array $claves ) use ( $billing, $checkout ) {
	foreach ( $claves as $clave ) {
		if ( isset( $billing[ $clave ] ) ) {
			woocommerce_form_field( $clave, $billing[ $clave ], $checkout->get_value( $clave ) );
		}
	}
};
?>
<div class="ts-co" data-co>
	<ol class="ts-co__pasos" aria-label="<?php esc_attr_e( 'Pasos del pago', 'tecnostar' ); ?>">
		<?php foreach ( $nombres as $i => $nombre ) : ?>
			<li class="ts-co__paso<?php echo 0 === $i ? ' is-actual' : ''; ?>" data-nav="<?php echo (int) $i; ?>">
				<button type="button" class="ts-co__paso-btn" data-ir="<?php echo (int) $i; ?>"<?php echo 0 === $i ? ' aria-current="step"' : ' disabled'; ?>>
					<span class="ts-co__num" aria-hidden="true"><span class="ts-co__num-n"><?php echo (int) ( $i + 1 ); ?></span><svg class="ts-co__ok" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>
					<span class="ts-co__nombre"><?php echo esc_html( $nombre ); ?></span>
				</button>
			</li>
		<?php endforeach; ?>
	</ol>

	<div class="ts-co__grid">
		<aside class="ts-resumen" aria-label="<?php esc_attr_e( 'Resumen del pedido', 'tecnostar' ); ?>">
			<button type="button" class="ts-resumen__toggle" aria-expanded="false" aria-controls="ts-resumen-cuerpo">
				<span><?php esc_html_e( 'Resumen del pedido', 'tecnostar' ); ?></span>
				<span class="ts-resumen__total" data-total><?php echo wp_kses_post( WC()->cart->get_total() ); ?></span>
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
			</button>
			<div class="ts-resumen__cuerpo" id="ts-resumen-cuerpo">
				<h2 class="ts-resumen__titulo"><?php esc_html_e( 'Tu pedido', 'tecnostar' ); ?></h2>
				<?php woocommerce_order_review(); ?>
			</div>
		</aside>

		<form name="checkout" method="post" class="checkout woocommerce-checkout ts-co__form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" novalidate aria-label="<?php esc_attr_e( 'Finalizar compra', 'tecnostar' ); ?>">

			<?php if ( $checkout->get_checkout_fields() ) : ?>
				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
				<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

				<section class="ts-paso is-activo" data-paso="0" aria-labelledby="ts-t0">
					<h2 id="ts-t0" class="ts-paso__titulo" tabindex="-1"><?php esc_html_e( '¿A quién le escribimos?', 'tecnostar' ); ?></h2>
					<p class="ts-paso__sub"><?php esc_html_e( 'Te avisamos por aquí cuando tu pedido esté en camino.', 'tecnostar' ); ?></p>
					<div class="ts-campos">
						<?php $campos( $paso1 ); ?>
						<?php $campos( $extras ); ?>
					</div>

					<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
						<div class="woocommerce-account-fields ts-cuenta">
							<?php if ( ! $checkout->is_registration_required() ) : ?>
								<p class="form-row form-row-wide create-account">
									<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
										<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e( 'Guardar mis datos para la próxima compra (crear cuenta)', 'tecnostar' ); ?></span>
									</label>
								</p>
							<?php endif; ?>
							<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>
							<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>
								<div class="create-account">
									<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
										<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
						</div>
					<?php endif; ?>

					<div class="ts-paso__acciones">
						<button type="button" class="ts-btn ts-btn--primario" data-sig><?php esc_html_e( 'Continuar', 'tecnostar' ); ?></button>
					</div>
				</section>

				<section class="ts-paso" data-paso="1" aria-labelledby="ts-t1">
					<h2 id="ts-t1" class="ts-paso__titulo" tabindex="-1"><?php esc_html_e( '¿Dónde te lo llevamos?', 'tecnostar' ); ?></h2>
					<p class="ts-recap" data-recap="0"></p>
					<div class="ts-campos">
						<?php $campos( $paso2 ); ?>
					</div>

					<h3 class="ts-subtitulo"><?php esc_html_e( 'Forma de entrega', 'tecnostar' ); ?></h3>
					<?php tecnostar_envio_html(); ?>

					<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
					<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>
					<?php foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) : ?>
						<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
					<?php endforeach; ?>
					<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>

					<div class="ts-paso__acciones">
						<button type="button" class="ts-btn ts-btn--suave" data-atras><?php esc_html_e( 'Atrás', 'tecnostar' ); ?></button>
						<button type="button" class="ts-btn ts-btn--primario" data-sig><?php esc_html_e( 'Continuar al pago', 'tecnostar' ); ?></button>
					</div>
				</section>

				<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
			<?php endif; ?>

			<section class="ts-paso" data-paso="2" aria-labelledby="ts-t2">
				<h2 id="ts-t2" class="ts-paso__titulo" tabindex="-1"><?php esc_html_e( '¿Cómo quieres pagar?', 'tecnostar' ); ?></h2>
				<p class="ts-recap" data-recap="1"></p>
				<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php
					// Algunos plugins de pago dejan etiquetas HTML sin cerrar; se equilibran para que no se desordene el resto de la página.
					ob_start();
					do_action( 'woocommerce_checkout_order_review' );
					echo force_balance_tags( ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
				<div class="ts-paso__acciones ts-paso__acciones--pago">
					<button type="button" class="ts-btn ts-btn--suave" data-atras><?php esc_html_e( 'Atrás', 'tecnostar' ); ?></button>
				</div>
			</section>
		</form>
	</div>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
