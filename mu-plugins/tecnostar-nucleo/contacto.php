<?php
/**
 * Formulario de contacto de Nosotros / Contáctenos.
 *
 * - Tipo privado `ts_contacto`: copia de cada mensaje, visible solo en el administrador (sin URL pública,
 *   sin REST pública, sin sitemap ni búsqueda). Se borra a los 90 días con un evento programado diario.
 * - Envío: `admin-ajax.php?action=ts_contacto` (POST, nonce `ts_contacto`) responde JSON { ok, wa_url, mensaje, errores? }.
 *   Sin JavaScript el formulario hace POST a la misma página y la plantilla muestra el enlace a WhatsApp como botón.
 * - Antispam: campo oculto `ts_web` (si llega lleno no se guarda nada) y 3 envíos por IP por hora (la IP no se guarda:
 *   se usa un HMAC como clave del contador, igual que inc/cuenta.php del tema).
 * - Aviso por correo con wp_mail al ajuste `tecnostar_correo_contacto` (Ajustes > Generales). Mientras no exista, no se
 *   envía y queda registrado como pendiente. Los datos personales nunca se escriben en logs.
 */

defined( 'ABSPATH' ) || exit;

const TECNOSTAR_CONTACTO_MAX_MENSAJE = 2000;
const TECNOSTAR_CONTACTO_MAX_ENVIOS  = 3;
const TECNOSTAR_CONTACTO_DIAS        = 90;

/** Tipo de contenido privado. */
add_action(
	'init',
	function () {
		register_post_type(
			'ts_contacto',
			array(
				'labels'              => array(
					'name'          => 'Contactos',
					'singular_name' => 'Contacto',
					'menu_name'     => 'Contactos web',
					'all_items'     => 'Mensajes recibidos',
					'edit_item'     => 'Ver contacto',
					'search_items'  => 'Buscar contactos',
					'not_found'     => 'No hay mensajes.',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'menu_icon'           => 'dashicons-email-alt',
				'supports'            => array( 'title', 'editor' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			)
		);

		if ( ! is_email( (string) get_option( 'tecnostar_correo_contacto', '' ) ) && function_exists( 'tecnostar_pendiente' ) ) {
			tecnostar_pendiente( 'contacto-correo', 'Correo del equipo que recibe los avisos del formulario de contacto (y SMTP del buzón del cPanel)', 'Ajustes > Generales > Correo de contactos' );
		}
	}
);

/** Fuera del sitemap aunque algún día cambie su visibilidad. */
add_filter(
	'wp_sitemaps_post_types',
	function ( $tipos ) {
		unset( $tipos['ts_contacto'] );
		return $tipos;
	}
);

/** Sedes del formulario: id del canal de WhatsApp => nombre visible. Solo las que tienen número. */
function tecnostar_contacto_sedes(): array {
	$sedes = array( 'bogota' => 'Bogotá', 'tocancipa' => 'Tocancipá' );
	if ( ! function_exists( 'tecnostar_wa_url' ) ) {
		return array();
	}
	return array_filter(
		$sedes,
		function ( $id ) {
			return '' !== tecnostar_wa_url( $id, 'x' );
		},
		ARRAY_FILTER_USE_KEY
	);
}

/** Mensaje de WhatsApp armado con los datos del formulario. Función pura. */
function tecnostar_contacto_texto_wa( string $nombre, string $celular, string $mensaje ): string {
	return 'Hola, soy ' . $nombre . ' (' . $celular . '): ' . $mensaje;
}

/**
 * Valida y normaliza lo enviado. Función pura salvo por la lista de sedes que recibe.
 *
 * @return array{datos: array, errores: array}
 */
function tecnostar_contacto_validar( array $in, array $sedes ): array {
	$nombre  = isset( $in['ts_nombre'] ) ? trim( sanitize_text_field( (string) $in['ts_nombre'] ) ) : '';
	$celular = isset( $in['ts_celular'] ) ? trim( preg_replace( '/[^\d+ ]+/', '', (string) $in['ts_celular'] ) ) : '';
	$sede    = isset( $in['ts_sede'] ) ? sanitize_key( (string) $in['ts_sede'] ) : '';
	// Los navegadores envían los saltos del textarea como \r\n: se normalizan antes de contar.
	$mensaje = isset( $in['ts_mensaje'] ) ? trim( sanitize_textarea_field( str_replace( array( "\r\n", "\r" ), "\n", (string) $in['ts_mensaje'] ) ) ) : '';
	$errores = array();

	$ln = mb_strlen( $nombre, 'UTF-8' );
	if ( $ln < 2 || $ln > 80 ) {
		$errores['ts_nombre'] = 'Escribe tu nombre (entre 2 y 80 caracteres).';
	}
	$digitos = strlen( preg_replace( '/\D+/', '', $celular ) );
	if ( $digitos < 7 || $digitos > 15 ) {
		$errores['ts_celular'] = 'Escribe un número de celular válido (solo números).';
	}
	if ( ! isset( $sedes[ $sede ] ) ) {
		$errores['ts_sede'] = 'Elige la sede que te queda mejor.';
	}
	$lm = mb_strlen( $mensaje, 'UTF-8' );
	if ( $lm < 1 ) {
		$errores['ts_mensaje'] = 'Cuéntanos en qué te podemos ayudar.';
	} elseif ( $lm > TECNOSTAR_CONTACTO_MAX_MENSAJE ) {
		$errores['ts_mensaje'] = 'El mensaje puede tener hasta 2.000 caracteres.';
	}

	return array(
		'datos'   => compact( 'nombre', 'celular', 'sede', 'mensaje' ),
		'errores' => $errores,
	);
}

/**
 * IP normalizada para el contador. Función pura.
 * - IPv4 mapeada en IPv6 (::ffff:1.2.3.4, la que puede llegar por HTTP/3) cuenta como la IPv4.
 * - IPv6 se agrupa por su /64 (las direcciones temporales cambian dentro de la misma red).
 */
function tecnostar_contacto_ip_normalizada( string $ip ): string {
	$ip = trim( $ip );
	if ( 0 === stripos( $ip, '::ffff:' ) && filter_var( substr( $ip, 7 ), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		return substr( $ip, 7 );
	}
	if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
		$bin = inet_pton( $ip );
		if ( false !== $bin ) {
			return inet_ntop( substr( $bin, 0, 8 ) . str_repeat( "\0", 8 ) ) . '/64';
		}
	}
	return $ip;
}

/** Clave del contador por IP (HMAC; la IP no se guarda). */
function tecnostar_contacto_clave(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return 'ts_contacto_' . substr( hash_hmac( 'sha256', tecnostar_contacto_ip_normalizada( $ip ), wp_salt( 'nonce' ) ), 0, 24 );
}

function tecnostar_contacto_agotado(): bool {
	$d = get_transient( tecnostar_contacto_clave() );
	return is_array( $d ) && (int) $d['n'] >= TECNOSTAR_CONTACTO_MAX_ENVIOS && (int) $d['fin'] > time();
}

/** Suma un envío; la ventana de una hora empieza con el primero. */
function tecnostar_contacto_sumar(): void {
	$clave = tecnostar_contacto_clave();
	$d     = get_transient( $clave );
	if ( ! is_array( $d ) || (int) $d['fin'] <= time() ) {
		$d = array( 'n' => 0, 'fin' => time() + HOUR_IN_SECONDS );
	}
	$d['n'] = (int) $d['n'] + 1;
	set_transient( $clave, $d, max( 1, (int) $d['fin'] - time() ) );
}

/** Aviso por correo. Sin ajuste de correo no se envía (queda como pendiente). */
function tecnostar_contacto_avisar( int $id, array $d, string $sede_nombre ): void {
	$para = (string) get_option( 'tecnostar_correo_contacto', '' );
	if ( ! is_email( $para ) ) {
		return;
	}
	$cuerpo = "Nuevo mensaje desde el formulario de contacto de la web.\n\n"
		. 'Nombre: ' . $d['nombre'] . "\n"
		. 'Celular: ' . $d['celular'] . "\n"
		. 'Sede: ' . $sede_nombre . "\n\n"
		. "Mensaje:\n" . $d['mensaje'] . "\n\n"
		. 'Ver en el administrador: ' . admin_url( 'post.php?post=' . $id . '&action=edit' ) . "\n"
		. 'La copia se borra sola a los ' . TECNOSTAR_CONTACTO_DIAS . " días.\n";
	wp_mail( $para, 'Nuevo contacto web: sede ' . $sede_nombre, $cuerpo );
}

/**
 * Procesa un envío (ya con el nonce comprobado). No imprime nada.
 *
 * @return array{estado:int, ok:bool, mensaje:string, wa_url:string, errores:array, datos:array}
 */
function tecnostar_contacto_procesar( array $in ): array {
	$r = array( 'estado' => 200, 'ok' => false, 'mensaje' => '', 'wa_url' => '', 'errores' => array(), 'datos' => array() );

	// Campo trampa lleno: se responde como si nada, sin guardar ni contar.
	if ( isset( $in['ts_web'] ) && '' !== trim( (string) $in['ts_web'] ) ) {
		$r['ok']      = true;
		$r['mensaje'] = 'Gracias, recibimos tu mensaje.';
		return $r;
	}
	if ( tecnostar_contacto_agotado() ) {
		$r['estado']  = 429;
		$r['mensaje'] = 'Ya recibimos varios mensajes desde tu conexión. Espera una hora o escríbenos directo por WhatsApp.';
		return $r;
	}

	$sedes = tecnostar_contacto_sedes();
	$v     = tecnostar_contacto_validar( $in, $sedes );
	$d     = $v['datos'];
	if ( $v['errores'] ) {
		$r['estado']  = 400;
		$r['mensaje'] = 'Revisa los campos marcados.';
		$r['errores'] = $v['errores'];
		$r['datos']   = $d;
		return $r;
	}

	// Para visitantes WordPress pasa kses al guardar y convierte "&" en "&amp;". El texto ya viene sin etiquetas
	// (sanitize_*_field), así que se guarda tal cual: se quitan esos filtros solo durante esta inserción.
	$kses = array();
	foreach ( array( 'content_save_pre' => 'wp_filter_post_kses', 'title_save_pre' => 'wp_filter_kses' ) as $gancho => $fn ) {
		$prio = has_filter( $gancho, $fn );
		if ( false !== $prio ) {
			$kses[ $gancho ] = array( $fn, $prio );
			remove_filter( $gancho, $fn, $prio );
		}
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'ts_contacto',
			'post_status'  => 'private',
			'post_title'   => $d['nombre'] . ' · ' . $sedes[ $d['sede'] ],
			'post_content' => $d['mensaje'],
			'post_author'  => 0,
			'meta_input'   => array(
				'_ts_nombre'  => $d['nombre'],
				'_ts_celular' => $d['celular'],
				'_ts_sede'    => $d['sede'],
			),
		),
		true
	);
	foreach ( $kses as $gancho => $f ) {
		add_filter( $gancho, $f[0], $f[1] );
	}
	if ( is_wp_error( $id ) || ! $id ) {
		$r['estado']  = 500;
		$r['mensaje'] = 'No pudimos guardar tu mensaje. Escríbenos directo por WhatsApp.';
		return $r;
	}
	tecnostar_contacto_sumar();
	tecnostar_contacto_avisar( (int) $id, $d, $sedes[ $d['sede'] ] );

	$r['ok']      = true;
	$r['mensaje'] = 'Listo, recibimos tu mensaje para la sede ' . $sedes[ $d['sede'] ] . '. Envíalo también por WhatsApp para atenderte más rápido.';
	$r['wa_url']  = tecnostar_wa_url( $d['sede'], tecnostar_contacto_texto_wa( $d['nombre'], $d['celular'], $d['mensaje'] ) );
	return $r;
}

function tecnostar_contacto_sin_cache(): void {
	nocache_headers();
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private' );
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
}

/** AJAX (con y sin sesión). */
function tecnostar_contacto_ajax(): void {
	tecnostar_contacto_sin_cache();
	if ( ! check_ajax_referer( 'ts_contacto', 'ts_nonce', false ) ) {
		// Página abierta mucho tiempo o servida desde caché: se devuelve un nonce nuevo y el cliente reintenta una vez.
		wp_send_json( array( 'ok' => false, 'mensaje' => 'La página llevaba mucho tiempo abierta. Inténtalo de nuevo.', 'nonce' => wp_create_nonce( 'ts_contacto' ) ), 403 );
	}
	$r = tecnostar_contacto_procesar( wp_unslash( $_POST ) );
	$salida = array( 'ok' => $r['ok'], 'wa_url' => $r['wa_url'], 'mensaje' => $r['mensaje'] );
	if ( $r['errores'] ) {
		$salida['errores'] = $r['errores'];
	}
	wp_send_json( $salida, $r['estado'] );
}
add_action( 'wp_ajax_ts_contacto', 'tecnostar_contacto_ajax' );
add_action( 'wp_ajax_nopriv_ts_contacto', 'tecnostar_contacto_ajax' );

/** Resultado del envío sin JavaScript (POST a la misma página), para que lo pinte la plantilla. */
function tecnostar_contacto_resultado( $nuevo = null ) {
	static $r = null;
	if ( null !== $nuevo ) {
		$r = $nuevo;
	}
	return $r;
}

add_action(
	'template_redirect',
	function () {
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || empty( $_POST['ts_contacto_enviar'] ) || ! is_page() ) { // phpcs:ignore WordPress.Security
			return;
		}
		tecnostar_contacto_sin_cache();
		$nonce = isset( $_POST['ts_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ts_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ts_contacto' ) ) {
			$r = array( 'estado' => 403, 'ok' => false, 'mensaje' => 'La página llevaba mucho tiempo abierta. Vuelve a enviar el formulario.', 'wa_url' => '', 'errores' => array(), 'datos' => array() );
		} else {
			$r = tecnostar_contacto_procesar( wp_unslash( $_POST ) );
		}
		status_header( $r['estado'] );
		tecnostar_contacto_resultado( $r );
	},
	5
);

/** Borrado a los 90 días: evento diario. */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'tecnostar_contacto_limpiar' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'tecnostar_contacto_limpiar' );
		}
	}
);
add_action(
	'tecnostar_contacto_limpiar',
	function () {
		$viejos = get_posts(
			array(
				'post_type'        => 'ts_contacto',
				'post_status'      => 'any',
				'posts_per_page'   => 200,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'date_query'       => array( array( 'before' => TECNOSTAR_CONTACTO_DIAS . ' days ago', 'column' => 'post_date_gmt' ) ),
			)
		);
		foreach ( $viejos as $id ) {
			wp_delete_post( (int) $id, true );
		}
	}
);

/** Ajustes > Generales: correo que recibe los avisos. */
add_action(
	'admin_init',
	function () {
		register_setting( 'general', 'tecnostar_correo_contacto', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_email', 'default' => '' ) );
		add_settings_field(
			'tecnostar_correo_contacto',
			'Correo de contactos',
			function () {
				echo '<input type="email" class="regular-text" name="tecnostar_correo_contacto" id="tecnostar_correo_contacto" value="' . esc_attr( (string) get_option( 'tecnostar_correo_contacto', '' ) ) . '">';
				echo '<p class="description">Recibe un aviso por cada mensaje del formulario de Nosotros / Contáctenos. Vacío: no se envía correo.</p>';
			},
			'general'
		);
	}
);

/** Columnas del listado en el administrador. */
add_filter(
	'manage_ts_contacto_posts_columns',
	function ( $c ) {
		return array( 'cb' => $c['cb'], 'title' => 'Nombre y sede', 'ts_celular' => 'Celular', 'date' => 'Fecha' );
	}
);
add_action(
	'manage_ts_contacto_posts_custom_column',
	function ( $col, $id ) {
		if ( 'ts_celular' === $col ) {
			echo esc_html( (string) get_post_meta( $id, '_ts_celular', true ) );
		}
	},
	10,
	2
);

/**
 * Rutas solo para administradores (pruebas y mantenimiento): listar ids por texto, borrar uno y
 * reiniciar el límite de envíos. No hay lectura pública.
 */
add_action(
	'rest_api_init',
	function () {
		$admin = function () {
			return current_user_can( 'manage_options' );
		};
		register_rest_route(
			'tecnostar/v1',
			'/contactos',
			array(
				'methods'             => 'GET',
				'permission_callback' => $admin,
				'args'                => array( 'buscar' => array( 'type' => 'string', 'required' => true ) ),
				'callback'            => function ( WP_REST_Request $req ) {
					$ids = get_posts(
						array(
							'post_type'      => 'ts_contacto',
							'post_status'    => 'any',
							's'              => (string) $req['buscar'],
							'posts_per_page' => 50,
							'fields'         => 'ids',
							'no_found_rows'  => true,
						)
					);
					return array_map(
						function ( $id ) {
							return array( 'id' => (int) $id, 'sede' => (string) get_post_meta( $id, '_ts_sede', true ), 'largo' => mb_strlen( (string) get_post_field( 'post_content', $id ), 'UTF-8' ) );
						},
						$ids
					);
				},
			)
		);
		register_rest_route(
			'tecnostar/v1',
			'/contactos/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'permission_callback' => $admin,
				'callback'            => function ( WP_REST_Request $req ) {
					$id = (int) $req['id'];
					if ( 'ts_contacto' !== get_post_type( $id ) ) {
						return new WP_Error( 'ts_no_es_contacto', 'No es un contacto.', array( 'status' => 404 ) );
					}
					return array( 'borrado' => (bool) wp_delete_post( $id, true ) );
				},
			)
		);
		register_rest_route(
			'tecnostar/v1',
			'/contactos/limite',
			array(
				'methods'             => 'GET',
				'permission_callback' => $admin,
				// Estado de los contadores (sin IP: solo envíos y fin de la ventana).
				'callback'            => function () {
					global $wpdb;
					$filas = $wpdb->get_col( "SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_ts\\_contacto\\_%'" ); // phpcs:ignore WordPress.DB
					return array_map(
						function ( $v ) {
							$d = maybe_unserialize( $v );
							return is_array( $d ) ? array( 'n' => (int) $d['n'], 'fin' => (int) $d['fin'] ) : null;
						},
						$filas
					);
				},
			)
		);
		register_rest_route(
			'tecnostar/v1',
			'/contactos/limite',
			array(
				'methods'             => 'DELETE',
				'permission_callback' => $admin,
				// Reinicia el límite de TODAS las conexiones (el navegador y el script de prueba pueden salir por IPv4 e IPv6).
				'callback'            => function () {
					global $wpdb;
					delete_transient( tecnostar_contacto_clave() );
					$n = $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_ts\\_contacto\\_%' OR option_name LIKE '\\_transient\\_timeout\\_ts\\_contacto\\_%'" ); // phpcs:ignore WordPress.DB
					return array( 'reiniciado' => true, 'filas' => (int) $n );
				},
			)
		);
	}
);
