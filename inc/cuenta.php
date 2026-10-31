<?php
/**
 * Widget "Mi cuenta" de la cabecera.
 *
 * El botón de la cabecera es el mismo HTML para todos (cacheable). El contenido de la tarjeta se pide
 * al abrirla (admin-ajax, acción ts_cuenta_panel, sin caché) y cambia según haya sesión o no.
 * Ingresar, crear cuenta y recuperar contraseña se envían por AJAX (POST, nonce propio) y responden
 * JSON { ok, mensaje, html? }. Límite de intentos por IP con transients.
 */

defined( 'ABSPATH' ) || exit;

/** HTML de la tarjeta para el usuario actual (o visitante). */
function tecnostar_cuenta_html() {
	if ( ! function_exists( 'wc_get_account_endpoint_url' ) ) {
		return '';
	}
	$usuario = is_user_logged_in() ? wp_get_current_user() : null;
	ob_start();
	get_template_part(
		'template-parts/cuenta',
		null,
		array(
			'usuario' => $usuario,
			'nonce'   => wp_create_nonce( 'ts_cuenta' ),
		)
	);
	return (string) ob_get_clean();
}

/** Cabeceras para que ni el navegador ni LiteSpeed guarden la respuesta. */
function tecnostar_cuenta_sin_cache() {
	nocache_headers();
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private' );
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
}

/** Respuesta JSON uniforme. */
function tecnostar_cuenta_responder( $ok, $mensaje, $html = null, $estado = 200 ) {
	tecnostar_cuenta_sin_cache();
	$datos = array(
		'ok'      => (bool) $ok,
		'mensaje' => (string) $mensaje,
	);
	if ( null !== $html ) {
		$datos['html'] = $html;
	}
	wp_send_json( $datos, $estado );
}

/** Clave del contador por IP (hash, no se guarda la IP). */
function tecnostar_cuenta_clave( $tipo ) {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return 'ts_cta_' . $tipo . '_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 24 );
}

/** ¿Ya agotó los intentos de este tipo en la ventana actual? */
function tecnostar_cuenta_agotado( $tipo, $max ) {
	$d = get_transient( tecnostar_cuenta_clave( $tipo ) );
	return is_array( $d ) && (int) $d['n'] >= $max && (int) $d['fin'] > time();
}

/** Suma un intento; la ventana empieza con el primero. */
function tecnostar_cuenta_sumar( $tipo, $ventana ) {
	$clave = tecnostar_cuenta_clave( $tipo );
	$d     = get_transient( $clave );
	if ( ! is_array( $d ) || (int) $d['fin'] <= time() ) {
		$d = array(
			'n'   => 0,
			'fin' => time() + $ventana,
		);
	}
	$d['n'] = (int) $d['n'] + 1;
	set_transient( $clave, $d, max( 1, (int) $d['fin'] - time() ) );
}

function tecnostar_cuenta_demasiados() {
	tecnostar_cuenta_responder( false, __( 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.', 'tecnostar' ), null, 429 );
}

/**
 * Comprueba el nonce del widget. Si caducó, responde 403 con la tarjeta fresca (trae nonce nuevo)
 * (con nonce nuevo) y el cliente vuelve a enviar.
 */
function tecnostar_cuenta_verificar_nonce() {
	if ( ! check_ajax_referer( 'ts_cuenta', 'nonce', false ) ) {
		tecnostar_cuenta_responder( false, __( 'La página llevaba mucho tiempo abierta. Inténtalo de nuevo.', 'tecnostar' ), tecnostar_cuenta_html(), 403 );
	}
}

/**
 * Tras crear la cookie de sesión en esta misma petición, WordPress aún no la "ve": se copia a $_COOKIE
 * para que los nonces (del widget y de Salir) salgan ligados a la sesión nueva.
 */
function tecnostar_cuenta_cookie_inmediata( $cookie ) {
	$_COOKIE[ LOGGED_IN_COOKIE ] = $cookie;
}

// ---- Panel (GET) -----------------------------------------------------------------
function tecnostar_cuenta_ajax_panel() {
	tecnostar_cuenta_sin_cache();
	header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
	echo tecnostar_cuenta_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- plantilla ya escapada.
	wp_die();
}
add_action( 'wp_ajax_ts_cuenta_panel', 'tecnostar_cuenta_ajax_panel' );
add_action( 'wp_ajax_nopriv_ts_cuenta_panel', 'tecnostar_cuenta_ajax_panel' );

// ---- Ingresar ----------------------------------------------------------------------
function tecnostar_cuenta_ajax_login() {
	tecnostar_cuenta_verificar_nonce();
	if ( is_user_logged_in() ) {
		tecnostar_cuenta_responder( true, __( 'Ya tienes la sesión abierta.', 'tecnostar' ), tecnostar_cuenta_html() );
	}
	if ( tecnostar_cuenta_agotado( 'login', 5 ) ) {
		tecnostar_cuenta_demasiados();
	}

	$login = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ), false ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- verificado arriba.
	$clave = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput -- la contraseña no se sanea.
	if ( '' === trim( $login ) || '' === $clave ) {
		tecnostar_cuenta_responder( false, __( 'Escribe tu correo o usuario y tu contraseña.', 'tecnostar' ), null, 400 );
	}

	$credenciales = apply_filters(
		'woocommerce_login_credentials',
		array(
			'user_login'    => trim( $login ),
			'user_password' => $clave,
			'remember'      => ! empty( $_POST['rememberme'] ), // phpcs:ignore WordPress.Security.NonceVerification
		)
	);

	add_action( 'set_logged_in_cookie', 'tecnostar_cuenta_cookie_inmediata' );
	$usuario = wp_signon( $credenciales, is_ssl() );
	remove_action( 'set_logged_in_cookie', 'tecnostar_cuenta_cookie_inmediata' );

	if ( is_wp_error( $usuario ) ) {
		tecnostar_cuenta_sumar( 'login', 10 * MINUTE_IN_SECONDS );
		// Mensaje único: no revela si el correo o el usuario existen.
		tecnostar_cuenta_responder( false, __( 'El correo, el usuario o la contraseña no coinciden.', 'tecnostar' ), null, 401 );
	}

	wp_set_current_user( $usuario->ID );
	tecnostar_cuenta_responder( true, __( 'Listo, ya ingresaste.', 'tecnostar' ), tecnostar_cuenta_html() );
}
add_action( 'wp_ajax_nopriv_ts_cuenta_login', 'tecnostar_cuenta_ajax_login' );
add_action( 'wp_ajax_ts_cuenta_login', 'tecnostar_cuenta_ajax_login' );

// ---- Crear cuenta -------------------------------------------------------------------
function tecnostar_cuenta_ajax_registro() {
	tecnostar_cuenta_verificar_nonce();
	if ( is_user_logged_in() ) {
		tecnostar_cuenta_responder( true, __( 'Ya tienes la sesión abierta.', 'tecnostar' ), tecnostar_cuenta_html() );
	}
	if ( 'yes' !== get_option( 'woocommerce_enable_myaccount_registration' ) || ! function_exists( 'wc_create_new_customer' ) ) {
		tecnostar_cuenta_responder( false, __( 'El registro no está disponible en este momento.', 'tecnostar' ), null, 403 );
	}
	if ( tecnostar_cuenta_agotado( 'registro', 3 ) ) {
		tecnostar_cuenta_demasiados();
	}
	tecnostar_cuenta_sumar( 'registro', HOUR_IN_SECONDS );

	// phpcs:disable WordPress.Security.NonceVerification -- verificado arriba.
	if ( ! empty( $_POST['ts_web'] ) ) {
		// Campo trampa lleno: es un robot. Respuesta neutra.
		tecnostar_cuenta_responder( false, __( 'No pudimos crear la cuenta. Inténtalo de nuevo.', 'tecnostar' ), null, 400 );
	}
	$correo  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$usuario = ( 'no' === get_option( 'woocommerce_registration_generate_username' ) && isset( $_POST['username'] ) ) ? wc_clean( wp_unslash( $_POST['username'] ) ) : '';
	$clave   = ( 'no' === get_option( 'woocommerce_registration_generate_password' ) && isset( $_POST['password'] ) ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	// phpcs:enable

	if ( ! is_email( $correo ) ) {
		tecnostar_cuenta_responder( false, __( 'Escribe un correo electrónico válido.', 'tecnostar' ), null, 400 );
	}

	$errores = apply_filters( 'woocommerce_process_registration_errors', new WP_Error(), $usuario, $clave, $correo );
	if ( is_wp_error( $errores ) && $errores->has_errors() ) {
		tecnostar_cuenta_responder( false, wp_strip_all_tags( $errores->get_error_message() ), null, 400 );
	}

	$id = wc_create_new_customer( $correo, $usuario, $clave, array( 'source' => 'tecnostar-cuenta' ) );
	if ( is_wp_error( $id ) ) {
		$codigo  = $id->get_error_code();
		$mensaje = 'registration-error-email-exists' === $codigo || 'registration-error-username-exists' === $codigo
			? __( 'No pudimos crear la cuenta con esos datos. Si ya tienes cuenta, ingresa o recupera tu contraseña.', 'tecnostar' )
			: wp_strip_all_tags( $id->get_error_message() );
		tecnostar_cuenta_responder( false, $mensaje, null, 400 );
	}

	$aviso = 'yes' === get_option( 'woocommerce_registration_generate_password' )
		? __( 'Cuenta creada. Te enviamos un enlace al correo para crear tu contraseña.', 'tecnostar' )
		: __( 'Cuenta creada. ¡Bienvenido!', 'tecnostar' );

	if ( apply_filters( 'woocommerce_registration_auth_new_customer', true, $id ) ) {
		add_action( 'set_logged_in_cookie', 'tecnostar_cuenta_cookie_inmediata' );
		wc_set_customer_auth_cookie( $id );
		remove_action( 'set_logged_in_cookie', 'tecnostar_cuenta_cookie_inmediata' );
	}
	tecnostar_cuenta_responder( true, $aviso, tecnostar_cuenta_html() );
}
add_action( 'wp_ajax_nopriv_ts_cuenta_registro', 'tecnostar_cuenta_ajax_registro' );
add_action( 'wp_ajax_ts_cuenta_registro', 'tecnostar_cuenta_ajax_registro' );

// ---- Recuperar contraseña -----------------------------------------------------------
function tecnostar_cuenta_ajax_recuperar() {
	tecnostar_cuenta_verificar_nonce();
	if ( tecnostar_cuenta_agotado( 'recuperar', 5 ) ) {
		tecnostar_cuenta_demasiados();
	}
	$login = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( '' === $login ) {
		tecnostar_cuenta_responder( false, __( 'Escribe tu correo o usuario.', 'tecnostar' ), null, 400 );
	}
	tecnostar_cuenta_sumar( 'recuperar', HOUR_IN_SECONDS );

	// Flujo de WooCommerce (su correo y su enlace de restablecer); si no hay sesión de WC, el de WordPress.
	if ( class_exists( 'WC_Shortcode_My_Account' ) && function_exists( 'WC' ) && WC()->session ) {
		$_POST['user_login'] = $login;
		WC_Shortcode_My_Account::retrieve_password();
		wc_clear_notices(); // Sus avisos dirían si el usuario existe; no se muestran.
	} elseif ( function_exists( 'retrieve_password' ) ) {
		retrieve_password( $login );
	}
	tecnostar_cuenta_responder( true, __( 'Si el correo existe, te enviamos instrucciones. Revisa también la carpeta de spam.', 'tecnostar' ) );
}
add_action( 'wp_ajax_nopriv_ts_cuenta_recuperar', 'tecnostar_cuenta_ajax_recuperar' );
add_action( 'wp_ajax_ts_cuenta_recuperar', 'tecnostar_cuenta_ajax_recuperar' );
