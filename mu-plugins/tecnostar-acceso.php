<?php
/**
 * Plugin Name: Tecnostar Acceso
 * Description: Ruta de acceso propia para administradores (/tsi) y para clientes (/acceso). Oculta wp-login.php y wp-admin a visitantes.
 * Version: 0.1.0
 *
 * RESCATE: si algo falla y no se puede entrar, renombrar este archivo a tecnostar-acceso.php.off por cPanel
 * (wp-content/mu-plugins). WordPress vuelve a su login normal en /wp-login.php.
 */

defined( 'ABSPATH' ) || exit;

/** Rutas (sin barras). Se pueden cambiar con los filtros. */
function tecnostar_acceso_slug( $quien ) {
	$slugs = array(
		'admin'   => 'tsi',
		'cliente' => 'acceso',
		'alias'   => 'acceder',
	);
	return apply_filters( 'tecnostar_acceso_slug_' . $quien, $slugs[ $quien ] );
}

/** Ruta pedida, relativa a la raíz del sitio y sin barras: "tsi", "wp-admin/index.php", etc. */
function tecnostar_acceso_ruta() {
	static $ruta = null;
	if ( null === $ruta ) {
		$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH );
		$base = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		$ruta = trim( rawurldecode( $path ), '/' );
		if ( '' !== $base && 0 === strpos( $ruta, $base ) ) {
			$ruta = trim( substr( $ruta, strlen( $base ) ), '/' );
		}
	}
	return $ruta;
}

/** ¿Trae cookie de sesión? Solo descarta a los anónimos; la validez real la comprueba WordPress después. */
function tecnostar_acceso_hay_cookie() {
	foreach ( array_keys( $_COOKIE ) as $nombre ) {
		if ( 0 === strpos( $nombre, 'wordpress_logged_in_' ) ) {
			return true;
		}
	}
	return false;
}

function tecnostar_acceso_404() {
	status_header( 404 );
	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	echo '<!doctype html><meta charset="utf-8"><title>No encontrado</title><h1>404</h1><p>No encontrado.</p>';
	exit;
}

/** Atiende /tsi con el formulario de wp-login.php y cierra la puerta a las rutas por defecto. */
add_action(
	'plugins_loaded',
	function () {
		global $pagenow, $error, $interim_login, $action, $user_login;

		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		$ruta = tecnostar_acceso_ruta();

		if ( tecnostar_acceso_slug( 'admin' ) === $ruta ) {
			$pagenow = 'wp-login.php';
			require_once ABSPATH . 'wp-login.php';
			exit;
		}

		if ( 'wp-login.php' === $ruta || 'wp-register.php' === $ruta || 'wp-signup.php' === $ruta ) {
			tecnostar_acceso_404();
		}

		if ( 0 === strpos( $ruta, 'wp-admin' ) && ! tecnostar_acceso_hay_cookie() ) {
			$permitidas = array( 'wp-admin/admin-ajax.php', 'wp-admin/admin-post.php', 'wp-admin/load-scripts.php', 'wp-admin/load-styles.php' );
			if ( ! in_array( $ruta, $permitidas, true ) ) {
				tecnostar_acceso_404();
			}
		}
	},
	9999
);

/** URLs que WordPress genera hacia wp-login.php pasan a /tsi (recuperar clave, cerrar sesión, entradas protegidas). */
function tecnostar_acceso_reemplazar( $url ) {
	return is_string( $url ) ? str_replace( 'wp-login.php', tecnostar_acceso_slug( 'admin' ), $url ) : $url;
}
add_filter( 'site_url', 'tecnostar_acceso_reemplazar', 20 );
add_filter( 'network_site_url', 'tecnostar_acceso_reemplazar', 20 );
add_filter( 'wp_redirect', 'tecnostar_acceso_reemplazar', 20 );

/** Cuando WordPress pide iniciar sesión, la puerta pública es /acceso. */
add_filter(
	'login_url',
	function () {
		return home_url( '/' . tecnostar_acceso_slug( 'cliente' ) . '/' );
	},
	30
);

/** Quien no es personal del sitio y entra por /tsi termina en su cuenta. */
add_filter(
	'login_redirect',
	function ( $destino, $pedido, $usuario ) {
		if ( $usuario instanceof WP_User && ! user_can( $usuario, 'edit_posts' ) ) {
			return home_url( '/' . tecnostar_acceso_slug( 'cliente' ) . '/' );
		}
		return $destino;
	},
	20,
	3
);

/** Clientes no pasan al panel de administración. */
add_action(
	'admin_init',
	function () {
		if ( wp_doing_ajax() || current_user_can( 'edit_posts' ) ) {
			return;
		}
		$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : '';
		if ( 'admin-post.php' === $script ) {
			return;
		}
		wp_safe_redirect( home_url( '/' . tecnostar_acceso_slug( 'cliente' ) . '/' ) );
		exit;
	},
	1
);

/** La página "Mi cuenta" de WooCommerce se llama /acceso (se ajusta una sola vez). /acceder redirige. */
add_action(
	'init',
	function () {
		if ( ! function_exists( 'wc_get_page_id' ) || get_option( 'tecnostar_acceso_slug_listo' ) === tecnostar_acceso_slug( 'cliente' ) ) {
			return;
		}
		$id = wc_get_page_id( 'myaccount' );
		if ( $id > 0 && get_post( $id ) ) {
			wp_update_post(
				array(
					'ID'        => $id,
					'post_name' => tecnostar_acceso_slug( 'cliente' ),
				)
			);
			update_option( 'tecnostar_acceso_slug_listo', tecnostar_acceso_slug( 'cliente' ), false );
		}
	},
	20
);

add_action(
	'template_redirect',
	function () {
		if ( tecnostar_acceso_slug( 'alias' ) === tecnostar_acceso_ruta() ) {
			wp_safe_redirect( home_url( '/' . tecnostar_acceso_slug( 'cliente' ) . '/' ), 301 );
			exit;
		}
	}
);

/** Aspecto del formulario de /tsi con la marca. */
add_action(
	'login_enqueue_scripts',
	function () {
		$logo = get_theme_file_uri( 'assets/img/logo/logo-horizontal.png' );
		$font = get_theme_file_uri( 'assets/fonts/outfit-latin.woff2' );
		$css  = '@font-face{font-family:Outfit;src:url(' . esc_url( $font ) . ') format("woff2");font-weight:100 900;font-display:swap}'
			. 'body.login{background:#ecf7fb;font-family:Outfit,system-ui,sans-serif;color:#0b3954}'
			. 'body.login h1 a{background:url(' . esc_url( $logo ) . ') center/contain no-repeat;width:220px;height:60px}'
			. 'body.login form{border:0;border-radius:16px;box-shadow:0 8px 30px rgb(11 57 84/.12);padding:1.5rem}'
			. 'body.login input[type=text],body.login input[type=password]{border-radius:10px;border-color:#c9d6dc;min-height:2.75rem}'
			. 'body.login input:focus{border-color:#37409a;box-shadow:0 0 0 3px rgb(55 64 154/.2)}'
			. 'body.login .button-primary{background:#37409a;border:0;border-radius:999px;min-height:2.75rem;padding:0 1.5rem;font-weight:600;transition:transform 100ms}'
			. 'body.login .button-primary:hover{background:#2e367f}body.login .button-primary:active{transform:scale(.97)}'
			. 'body.login #nav a,body.login #backtoblog a{color:#37409a}'
			. '@media (prefers-reduced-motion:reduce){body.login .button-primary{transition:none}}';
		wp_register_style( 'tecnostar-acceso', false, array(), '0.1.0' );
		wp_enqueue_style( 'tecnostar-acceso' );
		wp_add_inline_style( 'tecnostar-acceso', $css );
	}
);
add_filter( 'login_headerurl', 'home_url' );
add_filter(
	'login_headertext',
	function () {
		return get_bloginfo( 'name' );
	}
);
