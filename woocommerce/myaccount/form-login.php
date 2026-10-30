<?php
/**
 * Acceso de clientes (/acceso): una sola tarjeta que voltea entre "Acceder" y "Crear cuenta".
 * Basada en myaccount/form-login.php de WooCommerce; conserva sus campos, nonces y ganchos.
 */
defined( 'ABSPATH' ) || exit;

$registro_activo = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
// Si el envío que falló fue el de registro, la tarjeta vuelve a mostrar ese lado.
$voltear = $registro_activo && isset( $_POST['register'] ); // phpcs:ignore WordPress.Security.NonceVerification

do_action( 'woocommerce_before_customer_login_form' );
?>
<div class="acceso" data-acceso>
	<div class="acceso__card<?php echo $voltear ? ' is-flipped' : ''; ?>" data-card>

		<section class="acceso__face acceso__face--front" aria-labelledby="acceso-titulo-entrar" data-face="entrar">
			<p class="acceso__eyebrow"><?php esc_html_e( 'Tu cuenta', 'tecnostar' ); ?></p>
			<h2 class="acceso__title" id="acceso-titulo-entrar"><?php esc_html_e( 'Hola de nuevo', 'tecnostar' ); ?></h2>

			<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>
				<?php do_action( 'woocommerce_login_form_start' ); ?>

				<p class="woocommerce-form-row form-row form-row-wide">
					<label for="username"><?php esc_html_e( 'Usuario o correo electrónico', 'tecnostar' ); ?> <span class="required" aria-hidden="true">*</span></label>
					<input type="text" class="woocommerce-Input input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification ?>" required aria-required="true" />
				</p>
				<p class="woocommerce-form-row form-row form-row-wide">
					<label for="password"><?php esc_html_e( 'Contraseña', 'tecnostar' ); ?> <span class="required" aria-hidden="true">*</span></label>
					<input class="woocommerce-Input input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
				</p>

				<?php do_action( 'woocommerce_login_form' ); ?>

				<p class="form-row acceso__row">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
						<span><?php esc_html_e( 'Recuérdame', 'tecnostar' ); ?></span>
					</label>
					<a class="acceso__link" href="<?php echo esc_url( wc_lostpassword_url() ); ?>"><?php esc_html_e( '¿Olvidaste tu contraseña?', 'tecnostar' ); ?></a>
				</p>

				<p class="form-row">
					<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
					<button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Acceder', 'tecnostar' ); ?>"><?php esc_html_e( 'Acceder', 'tecnostar' ); ?></button>
				</p>

				<?php do_action( 'woocommerce_login_form_end' ); ?>
			</form>

			<?php if ( $registro_activo ) : ?>
				<p class="acceso__switch">
					<?php esc_html_e( '¿Aún no tienes cuenta?', 'tecnostar' ); ?>
					<button type="button" class="acceso__flip" data-flip="registro"><?php esc_html_e( 'Crea la tuya', 'tecnostar' ); ?></button>
				</p>
			<?php endif; ?>
		</section>

		<?php if ( $registro_activo ) : ?>
			<section class="acceso__face acceso__face--back" aria-labelledby="acceso-titulo-registro" data-face="registro">
				<p class="acceso__eyebrow"><?php esc_html_e( 'Nuevo en Tecnostar', 'tecnostar' ); ?></p>
				<h2 class="acceso__title" id="acceso-titulo-registro"><?php esc_html_e( 'Crea tu cuenta', 'tecnostar' ); ?></h2>

				<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> novalidate>
					<?php do_action( 'woocommerce_register_form_start' ); ?>

					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
						<p class="woocommerce-form-row form-row form-row-wide">
							<label for="reg_username"><?php esc_html_e( 'Nombre de usuario', 'tecnostar' ); ?> <span class="required" aria-hidden="true">*</span></label>
							<input type="text" class="woocommerce-Input input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification ?>" required aria-required="true" />
						</p>
					<?php endif; ?>

					<p class="woocommerce-form-row form-row form-row-wide">
						<label for="reg_email"><?php esc_html_e( 'Correo electrónico', 'tecnostar' ); ?> <span class="required" aria-hidden="true">*</span></label>
						<input type="email" class="woocommerce-Input input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) && is_string( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification ?>" required aria-required="true" />
					</p>

					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
						<p class="woocommerce-form-row form-row form-row-wide">
							<label for="reg_password"><?php esc_html_e( 'Contraseña', 'tecnostar' ); ?> <span class="required" aria-hidden="true">*</span></label>
							<input type="password" class="woocommerce-Input input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
						</p>
					<?php else : ?>
						<p class="acceso__note"><?php esc_html_e( 'Te enviaremos un enlace a tu correo para crear la contraseña.', 'tecnostar' ); ?></p>
					<?php endif; ?>

					<?php do_action( 'woocommerce_register_form' ); ?>

					<p class="form-row">
						<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
						<button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Crear cuenta', 'tecnostar' ); ?>"><?php esc_html_e( 'Crear cuenta', 'tecnostar' ); ?></button>
					</p>

					<?php do_action( 'woocommerce_register_form_end' ); ?>
				</form>

				<p class="acceso__switch">
					<?php esc_html_e( '¿Ya tienes cuenta?', 'tecnostar' ); ?>
					<button type="button" class="acceso__flip" data-flip="entrar"><?php esc_html_e( 'Accede', 'tecnostar' ); ?></button>
				</p>
			</section>
		<?php endif; ?>

	</div>
</div>
<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
