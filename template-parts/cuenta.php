<?php
/**
 * Tarjeta del widget "Mi cuenta" (se entrega por admin-ajax al abrir el desplegable).
 *
 * @var array $args { usuario: WP_User|null, nonce: string }
 */

defined( 'ABSPATH' ) || exit;

$usuario  = isset( $args['usuario'] ) && $args['usuario'] instanceof WP_User && $args['usuario']->exists() ? $args['usuario'] : null;
$nonce    = isset( $args['nonce'] ) ? (string) $args['nonce'] : '';
$registro = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$cerrar   = '<button type="button" class="ts-cuenta__cerrar" data-cuenta-cerrar aria-label="' . esc_attr__( 'Cerrar', 'tecnostar' ) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>';
?>
<div class="ts-cuenta__tarjeta" data-cuenta-tarjeta data-nonce="<?php echo esc_attr( $nonce ); ?>" data-estado="<?php echo $usuario ? 'cliente' : 'visitante'; ?>">
	<?php echo $cerrar; // phpcs:ignore WordPress.Security.EscapeOutput ?>

<?php if ( $usuario ) : ?>
	<?php
	$nombre = trim( (string) $usuario->first_name );
	$nombre = '' !== $nombre ? $nombre : $usuario->display_name;
	?>
	<section class="ts-cuenta__cara" data-cara="menu" aria-labelledby="ts-cuenta-t-menu">
		<p class="ts-cuenta__eyebrow"><?php esc_html_e( 'Tu cuenta', 'tecnostar' ); ?></p>
		<h2 class="ts-cuenta__titulo" id="ts-cuenta-t-menu">
			<?php
			/* translators: %s: nombre del cliente. */
			echo esc_html( sprintf( __( 'Hola, %s', 'tecnostar' ), $nombre ) );
			?>
		</h2>
		<p class="ts-cuenta__aviso" data-cuenta-aviso aria-live="polite"></p>
		<ul class="ts-cuenta__menu">
			<li><a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'Mis pedidos', 'tecnostar' ); ?></a></li>
			<li><a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>"><?php esc_html_e( 'Direcciones', 'tecnostar' ); ?></a></li>
			<li><a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>"><?php esc_html_e( 'Detalles de la cuenta', 'tecnostar' ); ?></a></li>
			<li class="ts-cuenta__salir"><a href="<?php echo esc_url( wc_logout_url() ); ?>"><?php esc_html_e( 'Salir', 'tecnostar' ); ?></a></li>
		</ul>
	</section>

<?php else : ?>
	<section class="ts-cuenta__cara" data-cara="entrar" aria-labelledby="ts-cuenta-t-entrar">
		<p class="ts-cuenta__eyebrow"><?php esc_html_e( 'Tu cuenta', 'tecnostar' ); ?></p>
		<h2 class="ts-cuenta__titulo" id="ts-cuenta-t-entrar"><?php esc_html_e( 'Ingresa', 'tecnostar' ); ?></h2>
		<form data-cuenta-form="login" novalidate>
			<p class="ts-cuenta__campo">
				<label for="ts-cuenta-usuario"><?php esc_html_e( 'Correo o usuario', 'tecnostar' ); ?></label>
				<input type="text" name="username" id="ts-cuenta-usuario" autocomplete="username" required>
			</p>
			<p class="ts-cuenta__campo">
				<label for="ts-cuenta-clave"><?php esc_html_e( 'Contraseña', 'tecnostar' ); ?></label>
				<input type="password" name="password" id="ts-cuenta-clave" autocomplete="current-password" required>
			</p>
			<p class="ts-cuenta__fila">
				<label class="ts-cuenta__check"><input type="checkbox" name="rememberme" value="forever" checked> <?php esc_html_e( 'Recordarme', 'tecnostar' ); ?></label>
				<button type="button" class="ts-cuenta__link" data-cuenta-ir="recuperar"><?php esc_html_e( '¿Olvidaste tu contraseña?', 'tecnostar' ); ?></button>
			</p>
			<p class="ts-cuenta__aviso" data-cuenta-aviso aria-live="polite"></p>
			<button type="submit" class="ts-cuenta__btn"><?php esc_html_e( 'Ingresar', 'tecnostar' ); ?></button>
		</form>
		<?php if ( $registro ) : ?>
			<p class="ts-cuenta__switch"><?php esc_html_e( '¿Aún no tienes cuenta?', 'tecnostar' ); ?> <button type="button" class="ts-cuenta__link" data-cuenta-ir="crear"><?php esc_html_e( 'Crea la tuya', 'tecnostar' ); ?></button></p>
		<?php endif; ?>
	</section>

	<?php if ( $registro ) : ?>
	<section class="ts-cuenta__cara" data-cara="crear" aria-labelledby="ts-cuenta-t-crear" hidden>
		<p class="ts-cuenta__eyebrow"><?php esc_html_e( 'Nuevo en Tecnostar', 'tecnostar' ); ?></p>
		<h2 class="ts-cuenta__titulo" id="ts-cuenta-t-crear"><?php esc_html_e( 'Crea tu cuenta', 'tecnostar' ); ?></h2>
		<form data-cuenta-form="registro" novalidate>
			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
			<p class="ts-cuenta__campo">
				<label for="ts-cuenta-reg-usuario"><?php esc_html_e( 'Nombre de usuario', 'tecnostar' ); ?></label>
				<input type="text" name="username" id="ts-cuenta-reg-usuario" autocomplete="username" required>
			</p>
			<?php endif; ?>
			<p class="ts-cuenta__campo">
				<label for="ts-cuenta-reg-correo"><?php esc_html_e( 'Correo electrónico', 'tecnostar' ); ?></label>
				<input type="email" name="email" id="ts-cuenta-reg-correo" autocomplete="email" required>
			</p>
			<div class="ts-cuenta__hp" aria-hidden="true"><label for="ts-cuenta-web">Web</label><input type="text" name="ts_web" id="ts-cuenta-web" tabindex="-1" autocomplete="off"></div>
			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
			<p class="ts-cuenta__campo">
				<label for="ts-cuenta-reg-clave"><?php esc_html_e( 'Contraseña', 'tecnostar' ); ?></label>
				<input type="password" name="password" id="ts-cuenta-reg-clave" autocomplete="new-password" required>
			</p>
			<?php else : ?>
			<p class="ts-cuenta__nota"><?php esc_html_e( 'Te enviaremos un enlace al correo para crear la contraseña.', 'tecnostar' ); ?></p>
			<?php endif; ?>
			<?php
			if ( function_exists( 'wc_registration_privacy_policy_text' ) ) {
				wc_registration_privacy_policy_text();
			}
			?>
			<p class="ts-cuenta__aviso" data-cuenta-aviso aria-live="polite"></p>
			<button type="submit" class="ts-cuenta__btn"><?php esc_html_e( 'Crear cuenta', 'tecnostar' ); ?></button>
		</form>
		<p class="ts-cuenta__switch"><?php esc_html_e( '¿Ya tienes cuenta?', 'tecnostar' ); ?> <button type="button" class="ts-cuenta__link" data-cuenta-ir="entrar"><?php esc_html_e( 'Ingresa', 'tecnostar' ); ?></button></p>
	</section>
	<?php endif; ?>

	<section class="ts-cuenta__cara" data-cara="recuperar" aria-labelledby="ts-cuenta-t-recuperar" hidden>
		<p class="ts-cuenta__eyebrow"><?php esc_html_e( 'Recuperar acceso', 'tecnostar' ); ?></p>
		<h2 class="ts-cuenta__titulo" id="ts-cuenta-t-recuperar"><?php esc_html_e( '¿Olvidaste tu contraseña?', 'tecnostar' ); ?></h2>
		<form data-cuenta-form="recuperar" novalidate>
			<p class="ts-cuenta__nota"><?php esc_html_e( 'Escribe tu correo o usuario y te enviaremos un enlace para crear una nueva.', 'tecnostar' ); ?></p>
			<p class="ts-cuenta__campo">
				<label for="ts-cuenta-rec"><?php esc_html_e( 'Correo o usuario', 'tecnostar' ); ?></label>
				<input type="text" name="user_login" id="ts-cuenta-rec" autocomplete="username" required>
			</p>
			<p class="ts-cuenta__aviso" data-cuenta-aviso aria-live="polite"></p>
			<button type="submit" class="ts-cuenta__btn"><?php esc_html_e( 'Enviar enlace', 'tecnostar' ); ?></button>
		</form>
		<p class="ts-cuenta__switch"><button type="button" class="ts-cuenta__link" data-cuenta-ir="entrar"><?php esc_html_e( 'Volver a ingresar', 'tecnostar' ); ?></button></p>
	</section>
<?php endif; ?>
</div>
