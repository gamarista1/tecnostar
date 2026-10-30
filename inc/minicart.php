<?php
/**
 * Mini carrito lateral: panel que se desliza al agregar un producto o al pulsar el carrito de la cabecera.
 * Solo es presentación: el carrito sigue siendo el de WooCommerce.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** Compra mínima (en pesos) para el envío gratis en Bogotá. Se puede ajustar con el filtro. */
function tecnostar_envio_gratis_desde() {
	return (float) apply_filters( 'tecnostar_envio_gratis_desde', 200000 );
}

function tecnostar_minicart_cuerpo() {
	$cart = WC()->cart;
	ob_start();
	echo '<div class="ts-mc__cuerpo" aria-live="polite">';

	if ( ! $cart || $cart->is_empty() ) {
		?>
		<div class="ts-mc__vacio">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h8.6a1 1 0 0 0 1-.8L20 8H6.2"/><circle cx="9.5" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/></svg>
			<p><strong><?php esc_html_e( 'Tu carrito está vacío', 'tecnostar' ); ?></strong></p>
			<p><?php esc_html_e( 'Agrega productos y aquí verás tu pedido.', 'tecnostar' ); ?></p>
			<a class="ts-btn ts-btn--primario" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Ver la tienda', 'tecnostar' ); ?></a>
		</div>
		<?php
	} else {
		$meta     = tecnostar_envio_gratis_desde();
		$subtotal = (float) $cart->get_displayed_subtotal();
		$falta    = max( 0, $meta - $subtotal );
		$pct      = $meta > 0 ? min( 100, round( $subtotal / $meta * 100 ) ) : 100;
		?>
		<div class="ts-mc__envio<?php echo $falta <= 0 ? ' is-listo' : ''; ?>">
			<p>
				<?php
				if ( $falta <= 0 ) {
					echo '<strong>' . esc_html__( '¡Tienes envío gratis en Bogotá!', 'tecnostar' ) . '</strong>';
				} else {
					echo wp_kses_post( sprintf( /* translators: %s: monto que falta */ __( 'Te faltan <strong>%s</strong> para el envío gratis en Bogotá', 'tecnostar' ), wc_price( $falta ) ) );
				}
				?>
			</p>
			<div class="ts-mc__barra" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $pct; ?>"><span style="transform:scaleX(<?php echo esc_attr( $pct / 100 ); ?>)"></span></div>
		</div>

		<ul class="ts-mc__lista">
			<?php
			foreach ( $cart->get_cart() as $key => $item ) {
				$producto = apply_filters( 'woocommerce_cart_item_product', $item['data'], $item, $key );
				if ( ! $producto || ! $producto->exists() || $item['quantity'] <= 0 || ! apply_filters( 'woocommerce_widget_cart_item_visible', true, $item, $key ) ) {
					continue;
				}
				$enlace = apply_filters( 'woocommerce_cart_item_permalink', $producto->is_visible() ? $producto->get_permalink( $item ) : '', $item, $key );
				$nombre = wp_strip_all_tags( apply_filters( 'woocommerce_cart_item_name', $producto->get_name(), $item, $key ) );
				$max    = $producto->get_max_purchase_quantity();
				?>
				<li class="ts-mc__item" data-key="<?php echo esc_attr( $key ); ?>">
					<a class="ts-mc__img" href="<?php echo esc_url( $enlace ); ?>" tabindex="-1" aria-hidden="true"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_thumbnail', $producto->get_image( 'woocommerce_thumbnail' ), $item, $key ) ); ?></a>
					<div class="ts-mc__info">
						<a class="ts-mc__nombre" href="<?php echo esc_url( $enlace ); ?>"><?php echo esc_html( $nombre ); ?></a>
						<span class="ts-mc__precio"><?php echo wp_kses_post( $cart->get_product_price( $producto ) ); ?></span>
						<div class="ts-mc__cant" role="group" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: producto */ __( 'Cantidad de %s', 'tecnostar' ), $nombre ) ); ?>">
							<button type="button" data-mc-menos aria-label="<?php esc_attr_e( 'Quitar una unidad', 'tecnostar' ); ?>">−</button>
							<output data-mc-n-item><?php echo (int) $item['quantity']; ?></output>
							<button type="button" data-mc-mas data-max="<?php echo (int) $max; ?>" aria-label="<?php esc_attr_e( 'Agregar una unidad', 'tecnostar' ); ?>"<?php echo ( $max > 0 && $item['quantity'] >= $max ) ? ' disabled' : ''; ?>>+</button>
						</div>
					</div>
					<div class="ts-mc__lado">
						<span class="ts-mc__linea"><?php echo wp_kses_post( $cart->get_product_subtotal( $producto, $item['quantity'] ) ); ?></span>
						<button type="button" class="ts-mc__quitar" data-mc-quitar aria-label="<?php echo esc_attr( sprintf( /* translators: %s: producto */ __( 'Quitar %s del carrito', 'tecnostar' ), $nombre ) ); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14M10 7V5h4v2M7 7l1 12h8l1-12"/></svg>
						</button>
					</div>
				</li>
				<?php
			}
			?>
		</ul>

		<div class="ts-mc__pie">
			<div class="ts-mc__sub"><span><?php esc_html_e( 'Subtotal', 'tecnostar' ); ?></span><strong><?php echo wp_kses_post( $cart->get_cart_subtotal() ); ?></strong></div>
			<p class="ts-mc__nota"><?php esc_html_e( 'El envío y los medios de pago se eligen en el siguiente paso.', 'tecnostar' ); ?></p>
			<a class="ts-btn ts-btn--primario ts-mc__ir" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Ir a pagar', 'tecnostar' ); ?></a>
			<div class="ts-mc__enlaces"><button type="button" data-mc-cerrar><?php esc_html_e( 'Seguir comprando', 'tecnostar' ); ?></button><a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Ver carrito completo', 'tecnostar' ); ?></a></div>
		</div>
		<?php
	}
	echo '</div>';
	return ob_get_clean();
}

add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $fragments ) {
		$fragments['div.ts-mc__cuerpo'] = tecnostar_minicart_cuerpo();
		return $fragments;
	}
);

/** Marco del panel: una sola vez por página, fuera del carrito y del pago. */
add_action(
	'wp_footer',
	function () {
		if ( is_cart() || is_checkout() ) {
			return;
		}
		?>
		<div class="ts-mc" data-mc hidden>
			<div class="ts-mc__velo" data-mc-cerrar></div>
			<aside class="ts-mc__panel" role="dialog" aria-modal="true" aria-labelledby="ts-mc-t" tabindex="-1">
				<header class="ts-mc__cab">
					<h2 id="ts-mc-t"><?php esc_html_e( 'Tu carrito', 'tecnostar' ); ?></h2>
					<button type="button" class="ts-mc__x" data-mc-cerrar aria-label="<?php esc_attr_e( 'Cerrar carrito', 'tecnostar' ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
				</header>
				<?php echo tecnostar_minicart_cuerpo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</aside>
		</div>
		<?php
	},
	5
);

/** Cambiar cantidad o quitar (cantidad 0) desde el panel. */
add_action(
	'wc_ajax_ts_mc_cantidad',
	function () {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ts_mc' ) ) {
			wp_send_json_error( array( 'mensaje' => 'nonce' ), 403 );
		}
		$key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$qty = isset( $_POST['qty'] ) ? max( 0, (int) $_POST['qty'] ) : 0;
		$cart = WC()->cart;
		$item = $cart ? $cart->get_cart_item( $key ) : null;
		if ( ! $item ) {
			wp_send_json_error( array( 'mensaje' => 'item' ), 404 );
		}
		if ( $qty > 0 ) {
			$max = $item['data']->get_max_purchase_quantity();
			if ( $max > 0 ) {
				$qty = min( $qty, $max );
			}
			$cart->set_quantity( $key, $qty, true );
		} else {
			$cart->remove_cart_item( $key );
		}
		wp_send_json_success(
			array(
				'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ),
				'cart_hash' => $cart->get_cart_hash(),
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( is_cart() || is_checkout() ) {
			return;
		}
		$css = get_template_directory_uri() . '/assets/css/';
		wp_enqueue_style( 'tecnostar-minicart', $css . 'minicart.css', array( 'tecnostar-components' ), tecnostar_asset_ver( '/assets/css/minicart.css' ) );
		wp_enqueue_script( 'tecnostar-minicart', get_template_directory_uri() . '/assets/js/minicart.js', array( 'jquery' ), tecnostar_asset_ver( '/assets/js/minicart.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_localize_script(
			'tecnostar-minicart',
			'tsMiniCart',
			array(
				'ajax'  => esc_url_raw( add_query_arg( 'wc-ajax', '%%endpoint%%', home_url( '/' ) ) ),
				'nonce' => wp_create_nonce( 'ts_mc' ),
			)
		);
	},
	20
);
