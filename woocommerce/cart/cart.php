<?php
/**
 * Carrito con la marca. Sigue siendo el formulario de WooCommerce: cantidades, cupón y totales son los suyos.
 *
 * @package tecnostar
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );
?>
<div class="ts-ca">
	<form class="woocommerce-cart-form ts-ca__form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
		<?php do_action( 'woocommerce_before_cart_table' ); ?>
		<ul class="ts-ca__lista">
			<?php
			do_action( 'woocommerce_before_cart_contents' );
			foreach ( WC()->cart->get_cart() as $key => $item ) {
				$producto = apply_filters( 'woocommerce_cart_item_product', $item['data'], $item, $key );
				if ( ! $producto || ! $producto->exists() || $item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $item, $key ) ) {
					continue;
				}
				$enlace = apply_filters( 'woocommerce_cart_item_permalink', $producto->is_visible() ? $producto->get_permalink( $item ) : '', $item, $key );
				$nombre = wp_strip_all_tags( apply_filters( 'woocommerce_cart_item_name', $producto->get_name(), $item, $key ) );
				?>
				<li class="ts-ca__item woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $item, $key ) ); ?>">
					<a class="ts-ca__img" href="<?php echo esc_url( $enlace ); ?>" tabindex="-1" aria-hidden="true"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_thumbnail', $producto->get_image( 'woocommerce_thumbnail' ), $item, $key ) ); ?></a>
					<div class="ts-ca__info">
						<a class="ts-ca__nombre" href="<?php echo esc_url( $enlace ); ?>"><?php echo esc_html( $nombre ); ?></a>
						<?php echo wc_get_formatted_cart_item_data( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php
						if ( $producto->backorders_require_notification() && $producto->is_on_backorder( $item['quantity'] ) ) {
							echo '<p class="ts-ca__aviso">' . esc_html__( 'Disponible bajo pedido', 'tecnostar' ) . '</p>';
						}
						?>
						<span class="ts-ca__precio"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $producto ), $item, $key ) ); ?></span>
						<div class="ts-ca__cant" role="group" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: producto */ __( 'Cantidad de %s', 'tecnostar' ), $nombre ) ); ?>">
							<?php
							if ( $producto->is_sold_individually() ) {
								printf( '<input type="hidden" name="cart[%s][qty]" value="1" /><span class="ts-ca__unico">1</span>', esc_attr( $key ) );
							} else {
								$max = $producto->get_max_purchase_quantity();
								?>
								<button type="button" data-ca-menos aria-label="<?php esc_attr_e( 'Quitar una unidad', 'tecnostar' ); ?>">−</button>
								<input type="number" inputmode="numeric" class="input-text qty text" name="cart[<?php echo esc_attr( $key ); ?>][qty]" value="<?php echo (int) $item['quantity']; ?>" min="0" <?php echo $max > 0 ? 'max="' . (int) $max . '"' : ''; ?> step="1" aria-label="<?php esc_attr_e( 'Cantidad', 'tecnostar' ); ?>" />
								<button type="button" data-ca-mas aria-label="<?php esc_attr_e( 'Agregar una unidad', 'tecnostar' ); ?>">+</button>
								<?php
							}
							?>
						</div>
					</div>
					<div class="ts-ca__lado">
						<span class="ts-ca__linea"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $producto, $item['quantity'] ), $item, $key ) ); ?></span>
						<a class="ts-ca__quitar remove" href="<?php echo esc_url( wc_get_cart_remove_url( $key ) ); ?>" data-product_id="<?php echo esc_attr( $producto->get_id() ); ?>" data-product_sku="<?php echo esc_attr( $producto->get_sku() ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: producto */ __( 'Quitar %s del carrito', 'tecnostar' ), $nombre ) ); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14M10 7V5h4v2M7 7l1 12h8l1-12"/></svg>
						</a>
					</div>
				</li>
				<?php
			}
			do_action( 'woocommerce_cart_contents' );
			?>
		</ul>

		<?php if ( wc_coupons_enabled() ) : ?>
			<div class="ts-ca__cupon coupon">
				<label for="coupon_code"><?php esc_html_e( '¿Tienes un cupón?', 'tecnostar' ); ?></label>
				<div class="ts-ca__cupon-fila">
					<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Código del cupón', 'tecnostar' ); ?>" autocapitalize="characters" />
					<button type="submit" class="button ts-ca__aplicar" name="apply_coupon" value="<?php esc_attr_e( 'Aplicar cupón', 'woocommerce' ); ?>"><?php esc_html_e( 'Aplicar', 'tecnostar' ); ?></button>
				</div>
				<?php do_action( 'woocommerce_cart_coupon' ); ?>
			</div>
		<?php endif; ?>

		<?php do_action( 'woocommerce_cart_actions' ); ?>
		<button type="submit" class="button ts-ca__actualizar" name="update_cart" value="<?php esc_attr_e( 'Actualizar carrito', 'woocommerce' ); ?>" hidden><?php esc_html_e( 'Actualizar carrito', 'tecnostar' ); ?></button>
		<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
		<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		<?php do_action( 'woocommerce_after_cart_table' ); ?>
		<a class="ts-ca__seguir" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">← <?php esc_html_e( 'Seguir comprando', 'tecnostar' ); ?></a>
	</form>

	<aside class="ts-ca__resumen">
		<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>
		<div class="cart-collaterals">
			<?php do_action( 'woocommerce_cart_collaterals' ); ?>
		</div>
	</aside>
</div>
<?php do_action( 'woocommerce_after_cart' ); ?>
