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

/** Atiende /tsi con el formulario de wp-login.php. Va en wp_loaded: WordPress ya terminó de arrancar. */
add_action(
	'wp_loaded',
	function () {
		global $pagenow, $error, $interim_login, $action, $user_login;

		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		if ( tecnostar_acceso_slug( 'admin' ) === tecnostar_acceso_ruta() ) {
			$pagenow = 'wp-login.php';
			require_once ABSPATH . 'wp-login.php';
			exit;
		}
	},
	9
);

/** Cierra la puerta a las rutas por defecto (wp-login.php y wp-admin para visitantes). */
add_action(
	'plugins_loaded',
	function () {
		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		$ruta = tecnostar_acceso_ruta();

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

/** Pantalla de /tsi: solo usuario, contraseña y entrar. Sin enlaces extra ni selector de idioma. */
add_filter( 'lost_password_html_link', '__return_empty_string' );
add_filter( 'login_site_html_link', '__return_empty_string' );
add_filter( 'login_display_language_dropdown', '__return_false' );
add_action(
	'login_init',
	function () {
		add_filter( 'the_privacy_policy_link', '__return_empty_string' );
	}
);
add_filter(
	'login_title',
	function () {
		return 'Acceso · ' . get_bloginfo( 'name' );
	}
);

/** Todo el sistema en español: se respeta es_CO si está instalado; si no, es_ES. El inglés no se ofrece. */
add_filter(
	'locale',
	function () {
		$sitio = (string) get_option( 'WPLANG' );
		return 0 === strpos( $sitio, 'es_' ) ? $sitio : 'es_ES';
	},
	99
);
add_filter(
	'pre_determine_locale',
	function () {
		$sitio = (string) get_option( 'WPLANG' );
		return 0 === strpos( $sitio, 'es_' ) ? $sitio : 'es_ES';
	},
	99
);

/** Aspecto y microinteracciones de la pantalla de /tsi (solo transform y opacity en el movimiento). */
add_action(
	'login_enqueue_scripts',
	function () {
		$logo = get_theme_file_uri( 'assets/img/logo/logo-horizontal@2x.png' );
		$font = get_theme_file_uri( 'assets/fonts/outfit-latin.woff2' );
		$css  = '@font-face{font-family:Outfit;src:url(' . esc_url( $font ) . ') format("woff2");font-weight:100 900;font-display:swap}'
			. ':root{--ts-azul:#37409a;--ts-azul-2:#2e367f;--ts-marino:#0b3954;--ts-ease:cubic-bezier(.22,1,.36,1)}'
			. 'body.login{display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1.5rem 1rem;box-sizing:border-box;'
			. 'background:radial-gradient(60rem 40rem at 50% -10%,#fff 0,#ecf7fb 60%);font-family:Outfit,system-ui,sans-serif;color:var(--ts-marino)}'
			. 'body.login #login{width:min(100%,24rem);padding:0;margin:0}'
			. 'body.login h1{margin:0 0 1.5rem}'
			. 'body.login h1 a{background:url(' . esc_url( $logo ) . ') center/contain no-repeat;width:100%;height:4.5rem;margin:0;'
			. 'animation:ts-entra .6s var(--ts-ease) backwards;transition:transform 250ms var(--ts-ease)}'
			. 'body.login h1 a:hover{transform:scale(1.05)}body.login h1 a:active{transform:scale(.98)}'
			. 'body.login h1 a:focus{outline:0;box-shadow:none}body.login h1 a:focus-visible{outline:2px solid var(--ts-azul);outline-offset:4px;border-radius:.5rem}'
			. 'body.login form{margin:0;border:0;border-radius:1.25rem;box-shadow:0 12px 40px rgb(11 57 84/.14);padding:1.75rem;background:#fff;'
			. 'animation:ts-entra .6s var(--ts-ease) .08s both}'
			. 'body.login label{font-weight:500;transition:color 150ms}'
			. 'body.login form p:focus-within>label,body.login .user-pass-wrap:focus-within>label{color:var(--ts-azul)}'
			. 'body.login input[type=text],body.login input[type=password]{min-height:3rem;border:1px solid #c9d6dc;border-radius:.75rem;font-size:1rem;'
			. 'transition:border-color 150ms,box-shadow 150ms}'
			. 'body.login input:focus{border-color:var(--ts-azul);box-shadow:0 0 0 4px rgb(55 64 154/.18);outline:0}'
			. 'body.login input[type=checkbox]{accent-color:var(--ts-azul)}'
			. 'body.login .button.wp-hide-pw{color:var(--ts-azul);transition:transform 100ms}body.login .button.wp-hide-pw:active{transform:scale(.9)}'
			. 'body.login .forgetmenot{margin-top:.25rem}'
			. 'body.login p.submit{margin-top:1.25rem;float:none}'
			. 'body.login .button-primary{float:none;width:100%;min-height:3rem;border:0;border-radius:999px;background:var(--ts-azul);color:#fff;font:600 1rem Outfit,system-ui,sans-serif;'
			. 'cursor:pointer;transition:transform 100ms var(--ts-ease),background-color 150ms,box-shadow 150ms;box-shadow:0 6px 16px rgb(55 64 154/.28)}'
			. 'body.login .button-primary:hover{background:var(--ts-azul-2)}body.login .button-primary:active{transform:scale(.97)}'
			. 'body.login .button-primary.is-loading{pointer-events:none;background:linear-gradient(110deg,var(--ts-azul) 30%,#5560c4 50%,var(--ts-azul) 70%) 0 0/200% 100%;animation:ts-carga 1s linear infinite}'
			. 'body.login #loginform.is-error{animation:ts-sacude .4s var(--ts-ease)}'
			. 'body.login .message,body.login #login_error{border-radius:.75rem;border-left-width:4px;margin:0 0 1rem;box-shadow:none;animation:ts-entra .4s var(--ts-ease) both}'
			. 'body.login #nav,body.login #backtoblog,body.login .privacy-policy-page-link,body.login .language-switcher{display:none}'
			. '@keyframes ts-entra{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}'
			. '@keyframes ts-sacude{20%{transform:translateX(-8px)}40%{transform:translateX(7px)}60%{transform:translateX(-5px)}80%{transform:translateX(3px)}}'
			. '@keyframes ts-carga{to{background-position:-200% 0}}'
			. '@media (prefers-reduced-motion:reduce){body.login h1 a,body.login form,body.login .message,body.login #login_error,body.login #loginform.is-error,body.login .button-primary.is-loading{animation:none}'
			. 'body.login .button-primary,body.login .button.wp-hide-pw,body.login h1 a{transition:none}}'
			. '@media (prefers-contrast:more){body.login form{box-shadow:none;border:2px solid var(--ts-marino)}}';
		wp_register_style( 'tecnostar-acceso', false, array(), '0.2.0' );
		wp_enqueue_style( 'tecnostar-acceso' );
		wp_add_inline_style( 'tecnostar-acceso', $css );
	}
);
add_filter(
	'login_headerurl',
	function () {
		return home_url( '/' );
	}
);
add_filter(
	'login_headertext',
	function () {
		return get_bloginfo( 'name' );
	}
);

/** Al enviar: el botón muestra "Entrando…"; si hay error, el formulario se sacude. */
add_action(
	'login_footer',
	function () {
		?>
<script>
(function(){
	var f=document.getElementById('loginform'),b=document.getElementById('wp-submit');
	if(!f||!b)return;
	if(document.getElementById('login_error')){f.classList.add('is-error');}
	f.addEventListener('submit',function(){setTimeout(function(){b.classList.add('is-loading');b.value='Entrando…';},0);});
})();
</script>
		<?php
	}
);
