<?php
/**
 * Videos desde Cloudflare R2 por la API S3, sin subdominio público ni DNS.
 *
 * - El bucket es privado. El HTML (que se cachea) solo lleva la CLAVE del video (videos/xxx.mp4); al tocar
 *   "reproducir", assets/js/video.js pide `admin-ajax.php?action=ts_video&clave=...` y recibe { url } con una
 *   URL prefirmada de 1 hora. Así ninguna página cacheada lleva una URL que caduca.
 * - Quién firma: la API Node (servicios/ del repo, GET /video/firmar), que tiene las claves de R2 en sus variables
 *   de entorno (cPanel > Setup Node.js App). WordPress la llama servidor a servidor y NO guarda claves.
 *   URL base: constante TECNOSTAR_VIDEO_API u opción tecnostar_video_api (por defecto https://api.tecnostaringenieria.com).
 * - Respaldo de menor prioridad (no se amplía): firma local con variables R2_* del PHP o el archivo fuera de
 *   public_html TECNOSTAR_R2_ARCHIVO. Si nada responde, el reproductor muestra un mensaje con enlace a WhatsApp
 *   y, si la API dice que no tiene configuración, queda el pendiente `r2-configuracion`.
 * - Límite: 60 peticiones válidas por IP y hora (contador en la tabla de opciones, sin caché de objetos, igual
 *   que contacto.php; la IP no se guarda, solo un HMAC).
 * La firma (SigV4) vive en video-r2-firma.php, lógica pura probada contra el vector oficial de AWS.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/video-r2-firma.php';

/** Archivo de configuración fuera de public_html. */
if ( ! defined( 'TECNOSTAR_R2_ARCHIVO' ) ) {
	define( 'TECNOSTAR_R2_ARCHIVO', '/home/tecnosta/.tecnostar-r2.php' );
}
const TECNOSTAR_R2_CADUCIDAD       = 3600;
const TECNOSTAR_VIDEO_MAX_POR_HORA = 60;
const TECNOSTAR_VIDEO_PREFIJO_LIM  = 'ts_video_lim_';

/**
 * Configuración de R2 o null. Primero variables de entorno (R2_ACCOUNT_ID, R2_ACCESS_KEY_ID,
 * R2_SECRET_ACCESS_KEY, R2_BUCKET); si faltan, el archivo TECNOSTAR_R2_ARCHIVO. Nunca se imprime.
 *
 * @return array{config: array|null, origen: string}
 */
function tecnostar_r2_config_leer(): array {
	static $r = null;
	if ( null !== $r ) {
		return $r;
	}
	$env = array(
		'account_id'        => (string) getenv( 'R2_ACCOUNT_ID' ),
		'access_key_id'     => (string) getenv( 'R2_ACCESS_KEY_ID' ),
		'secret_access_key' => (string) getenv( 'R2_SECRET_ACCESS_KEY' ),
		'bucket'            => (string) ( getenv( 'R2_BUCKET' ) ?: 'media' ),
	);
	if ( tecnostar_r2_config_valida( $env ) ) {
		$r = array( 'config' => $env, 'origen' => 'entorno' );
		return $r;
	}
	$archivo = TECNOSTAR_R2_ARCHIVO;
	if ( @is_file( $archivo ) && @is_readable( $archivo ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors -- open_basedir u otros avisos no deben romper la página.
		$cfg = ( static function ( $f ) {
			return include $f;
		} )( $archivo );
		if ( is_array( $cfg ) ) {
			$cfg = array_map( 'strval', array_intersect_key( $cfg, array_flip( array( 'account_id', 'access_key_id', 'secret_access_key', 'bucket' ) ) ) ) + array( 'bucket' => 'media' );
			if ( tecnostar_r2_config_valida( $cfg ) ) {
				$r = array( 'config' => $cfg, 'origen' => 'archivo' );
				return $r;
			}
		}
	}
	$r = array( 'config' => null, 'origen' => '' );
	return $r;
}

function tecnostar_r2_configurado(): bool {
	return null !== tecnostar_r2_config_leer()['config'];
}

/** Registra el pendiente de configuración (una sola vez; el registro no duplica). */
function tecnostar_r2_pendiente(): void {
	if ( function_exists( 'tecnostar_pendiente' ) ) {
		tecnostar_pendiente( 'r2-configuracion', 'Configurar el acceso a Cloudflare R2 (archivo /home/tecnosta/.tecnostar-r2.php) para que se vean los videos', 'cPanel > Administrador de archivos' );
	}
}

/** Fuente de video válida: clave de R2 (videos/xxx.mp4) o URL https completa. */
function tecnostar_video_fuente_normalizar( string $valor ): string {
	$valor = trim( $valor );
	if ( tecnostar_r2_clave_valida( $valor ) ) {
		return $valor;
	}
	$url = esc_url_raw( $valor, array( 'https' ) );
	return ( '' !== $url && 0 === strpos( $url, 'https://' ) ) ? $url : '';
}

/* ---------------- Límite por IP ---------------- */

function tecnostar_video_clave_limite(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( function_exists( 'tecnostar_contacto_ip_normalizada' ) ) {
		$ip = tecnostar_contacto_ip_normalizada( $ip );
	}
	return TECNOSTAR_VIDEO_PREFIJO_LIM . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 24 );
}

/** Cuenta una petición y dice si aún está dentro del límite. Lee y escribe directo en la base (sin caché). */
function tecnostar_video_limite_consumir(): bool {
	global $wpdb;
	$clave = tecnostar_video_clave_limite();
	$v     = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $clave ) ); // phpcs:ignore WordPress.DB
	$d     = $v ? json_decode( $v, true ) : null;
	$d     = is_array( $d ) && isset( $d['n'], $d['fin'] ) ? array( 'n' => (int) $d['n'], 'fin' => (int) $d['fin'] ) : array( 'n' => 0, 'fin' => 0 );
	if ( $d['fin'] <= time() ) {
		$d = array( 'n' => 0, 'fin' => time() + HOUR_IN_SECONDS );
	}
	if ( $d['n'] >= TECNOSTAR_VIDEO_MAX_POR_HORA ) {
		return false;
	}
	$d['n']++;
	$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no') ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)", $clave, wp_json_encode( $d ) ) ); // phpcs:ignore WordPress.DB
	return true;
}

/** Borra contadores (todos o solo vencidos). */
function tecnostar_video_contadores_borrar( bool $todos ): int {
	global $wpdb;
	$filas = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( TECNOSTAR_VIDEO_PREFIJO_LIM ) . '%' ) ); // phpcs:ignore WordPress.DB
	$n     = 0;
	foreach ( $filas as $f ) {
		$d = json_decode( $f->option_value, true );
		if ( $todos || ! is_array( $d ) || (int) $d['fin'] <= time() ) {
			$n += (int) $wpdb->delete( $wpdb->options, array( 'option_name' => $f->option_name ) ); // phpcs:ignore WordPress.DB
		}
	}
	return $n;
}

/* ---------------- Punto de entrada admin-ajax ---------------- */

function tecnostar_video_ajax(): void {
	nocache_headers();
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private' );
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
	header( 'X-Robots-Tag: noindex' );

	if ( 'GET' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) { // phpcs:ignore WordPress.Security
		wp_send_json( array( 'error' => 'metodo' ), 405 );
	}
	$clave = isset( $_GET['clave'] ) && is_string( $_GET['clave'] ) ? wp_unslash( $_GET['clave'] ) : ''; // phpcs:ignore WordPress.Security
	if ( ! tecnostar_r2_clave_valida( $clave ) ) {
		wp_send_json( array( 'error' => 'clave' ), 400 );
	}
	if ( ! tecnostar_video_limite_consumir() ) {
		header( 'Retry-After: 3600' );
		wp_send_json( array( 'error' => 'limite' ), 429 );
	}
	// 1) La API Node (dueña de las claves de R2, en sus variables de entorno).
	$api = tecnostar_video_pedir_api( $clave );
	if ( 'ok' === $api['estado'] ) {
		wp_send_json( array( 'url' => $api['url'], 'expira' => $api['expira'] ), 200 );
	}
	// 2) Respaldo de menor prioridad: firma local si el servidor tiene R2_* o el archivo fuera de public_html.
	$cfg = tecnostar_r2_config_leer()['config'];
	if ( null !== $cfg ) {
		$url = tecnostar_r2_url_prefirmada( $clave, TECNOSTAR_R2_CADUCIDAD, $cfg );
		if ( '' !== $url ) {
			wp_send_json( array( 'url' => $url, 'expira' => time() + TECNOSTAR_R2_CADUCIDAD ), 200 );
		}
	}
	if ( 'sin-configuracion' === $api['estado'] ) {
		tecnostar_r2_pendiente();
		wp_send_json( array( 'error' => 'sin-configuracion' ), 503 );
	}
	wp_send_json( array( 'error' => 'api' ), 502 );
}

/** URL base de la API Node: constante TECNOSTAR_VIDEO_API, opción tecnostar_video_api o https://api.tecnostaringenieria.com. */
function tecnostar_video_api_base(): string {
	$base = defined( 'TECNOSTAR_VIDEO_API' ) ? (string) TECNOSTAR_VIDEO_API : (string) get_option( 'tecnostar_video_api', 'https://api.tecnostaringenieria.com' );
	$base = esc_url_raw( trim( $base ), array( 'https' ) );
	return '' !== $base ? untrailingslashit( $base ) : 'https://api.tecnostaringenieria.com';
}

/**
 * Pide la URL prefirmada a la API Node (servidor a servidor, 5 s; el navegador no habla con la API: sin CORS).
 * Manda un identificador opaco del visitante (HMAC de su IP) para que el límite de la API sea por visitante.
 *
 * @return array{estado: string, url?: string, expira?: int} estado: ok | sin-configuracion | error
 */
function tecnostar_video_pedir_api( string $clave ): array {
	$r = wp_remote_get(
		tecnostar_video_api_base() . '/video/firmar?clave=' . rawurlencode( $clave ),
		array(
			'timeout'     => 5,
			'redirection' => 0,
			'headers'     => array(
				'Accept'              => 'application/json',
				'X-Tecnostar-Cliente' => substr( tecnostar_video_clave_limite(), strlen( TECNOSTAR_VIDEO_PREFIJO_LIM ) ),
			),
		)
	);
	if ( is_wp_error( $r ) ) {
		return array( 'estado' => 'error' );
	}
	$codigo = (int) wp_remote_retrieve_response_code( $r );
	$d      = json_decode( (string) wp_remote_retrieve_body( $r ), true );
	if ( 503 === $codigo && is_array( $d ) && isset( $d['error'] ) && 'sin-configuracion' === $d['error'] ) {
		return array( 'estado' => 'sin-configuracion' );
	}
	$url = 200 === $codigo && is_array( $d ) && isset( $d['url'] ) && is_string( $d['url'] ) ? $d['url'] : '';
	// Solo se acepta una URL de R2 firmada para esa misma clave.
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	$ruta = (string) wp_parse_url( $url, PHP_URL_PATH );
	if ( 0 !== strpos( $url, 'https://' ) || ! preg_match( '/^[a-f0-9]{32}\.r2\.cloudflarestorage\.com$/', $host ) || ! preg_match( '#^/[a-z0-9-]+/' . preg_quote( $clave, '#' ) . '$#', $ruta ) || false === strpos( $url, 'X-Amz-Signature=' ) ) {
		return array( 'estado' => 'error' );
	}
	return array( 'estado' => 'ok', 'url' => $url, 'expira' => isset( $d['expira'] ) ? (int) $d['expira'] : time() + TECNOSTAR_R2_CADUCIDAD );
}
add_action( 'wp_ajax_ts_video', 'tecnostar_video_ajax' );
add_action( 'wp_ajax_nopriv_ts_video', 'tecnostar_video_ajax' );

/** Limpieza diaria de contadores vencidos. */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'tecnostar_video_limpiar' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'tecnostar_video_limpiar' );
		}
	}
);
add_action(
	'tecnostar_video_limpiar',
	function () {
		tecnostar_video_contadores_borrar( false );
	}
);

/**
 * Rutas solo para administradores: estado de la configuración (sin valores) y reinicio del límite.
 * GET  /wp-json/tecnostar/v1/video/estado  -> { configurado, origen }
 * DELETE /wp-json/tecnostar/v1/video/limite -> borra todos los contadores.
 */
add_action(
	'rest_api_init',
	function () {
		$admin = function () {
			return current_user_can( 'manage_options' );
		};
		register_rest_route(
			'tecnostar/v1',
			'/video/estado',
			array(
				'methods'             => 'GET',
				'permission_callback' => $admin,
				'callback'            => function () {
					$r = tecnostar_r2_config_leer();
					return array( 'api' => tecnostar_video_api_base(), 'respaldo_local' => null !== $r['config'], 'origen_respaldo' => $r['origen'], 'caducidad' => TECNOSTAR_R2_CADUCIDAD, 'limite_hora' => TECNOSTAR_VIDEO_MAX_POR_HORA );
				},
			)
		);
		register_rest_route(
			'tecnostar/v1',
			'/video/limite',
			array(
				'methods'             => 'DELETE',
				'permission_callback' => $admin,
				'callback'            => function () {
					return array( 'reiniciado' => true, 'filas' => tecnostar_video_contadores_borrar( true ) );
				},
			)
		);
	}
);
