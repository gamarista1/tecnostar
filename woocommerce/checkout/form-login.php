<?php
/**
 * Acceso dentro del pago: "¿Ya eres cliente?" abre una tarjeta que voltea entre "Acceder" y "Recuperar contraseña".
 * Todo ocurre en la misma página; sin JavaScript los formularios siguen funcionando con los envíos normales de WooCommerce.
 *
 * @package tecnostar
 */

defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() || 'no' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
	return;
}
?>
<div class="ts-login" data-login>
	<button type="button" class="ts-login__toggle" aria-expanded="false" aria-controls="ts-login-cuerpo" data-login-toggle>
		<span><?php esc_html_e( '¿Ya eres cliente?', 'tecnostar' ); ?> <strong><?php esc_html_e( 'Accede aquí', 'tecnostar' ); ?></strong></span>
		<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
	</button>

	<div class="ts-login__cuerpo" id="ts-login-cuerpo">
		<div class="ts-login__interior">
			<div class="ts-login__card" data-login-card>

				<section class="ts-login__face" data-login-face="entrar" aria-labelledby="ts-login-t1">
					<h2 class="ts-login__titulo" id="ts-login-t1"><?php esc_html_e( 'Hola de nuevo', 'tecnostar' ); ?></h2>
					<p class="ts-login__nota"><?php esc_html_e( 'Accede y completamos tus datos por ti.', 'tecnostar' ); ?></p>
					<form class="ts-login__form" method="post" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" data-login-form="entrar" novalidate>
						<p class="ts-login__aviso" role="alert" hidden></p>
						<p class="ts-login__campo">
							<label for="ts-login-usuario"><?php esc_html_e( 'Usuario o correo electrónico', 'tecnostar' ); ?></label>
							<input type="text" name="username" id="ts-login-usuario" autocomplete="username" required />
						</p>
						<p class="ts-login__campo">
							<label for="ts-login-clave"><?php esc_html_e( 'Contraseña', 'tecnostar' ); ?></label>
							<input type="password" name="password" id="ts-login-clave" autocomplete="current-password" required />
						</p>
						<p class="ts-login__fila">
							<label class="ts-login__recordar"><input type="checkbox" name="rememberme" value="forever" /> <span><?php esc_html_e( 'Recuérdame', 'tecnostar' ); ?></span></label>
							<button type="button" class="ts-login__enlace" data-login-voltear="olvide"><?php esc_html_e( '¿Olvidaste tu contraseña?', 'tecnostar' ); ?></button>
						</p>
						<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
						<input type="hidden" name="redirect" value="<?php echo esc_url( wc_get_checkout_url() ); ?>" />
						<button type="submit" class="ts-login__btn" name="login" value="<?php esc_attr_e( 'Acceder', 'tecnostar' ); ?>"><?php esc_html_e( 'Acceder', 'tecnostar' ); ?></button>
					</form>
				</section>

				<section class="ts-login__face ts-login__face--back" data-login-face="olvide" aria-labelledby="ts-login-t2">
					<h2 class="ts-login__titulo" id="ts-login-t2"><?php esc_html_e( 'Recupera tu contraseña', 'tecnostar' ); ?></h2>
					<p class="ts-login__nota"><?php esc_html_e( 'Escribe tu usuario o correo y te enviamos un enlace para crear una nueva.', 'tecnostar' ); ?></p>
					<form class="ts-login__form" method="post" action="<?php echo esc_url( wc_lostpassword_url() ); ?>" data-login-form="olvide" novalidate>
						<p class="ts-login__aviso" role="alert" hidden></p>
						<p class="ts-login__campo">
							<label for="ts-login-recuperar"><?php esc_html_e( 'Usuario o correo electrónico', 'tecnostar' ); ?></label>
							<input type="text" name="user_login" id="ts-login-recuperar" autocomplete="username" required />
						</p>
						<input type="hidden" name="wc_reset_password" value="true" />
						<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>
						<button type="submit" class="ts-login__btn"><?php esc_html_e( 'Enviar enlace', 'tecnostar' ); ?></button>
					</form>
					<p class="ts-login__pie"><button type="button" class="ts-login__enlace" data-login-voltear="entrar">← <?php esc_html_e( 'Volver a acceder', 'tecnostar' ); ?></button></p>
				</section>

			</div>
		</div>
	</div>
</div>
