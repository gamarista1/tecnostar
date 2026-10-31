<?php
/**
 * Canales de WhatsApp configurables (Ajustes > WhatsApp).
 *
 * Opción `tecnostar_wa_canales`: array( 'canales' => id => array( nombre, numero, orden, activo ), 'esquina' => string ).
 * La lógica pura del enlace vive en whatsapp-url.php.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/whatsapp-url.php';

/** Esquinas válidas para el botón flotante. */
function tecnostar_wa_esquinas_validas(): array {
	return array( 'inferior-derecha', 'inferior-izquierda', 'superior-derecha', 'superior-izquierda' );
}

/** Valores por defecto de la opción. */
function tecnostar_wa_defaults(): array {
	return array(
		'canales' => array(
			'bogota'    => array( 'nombre' => 'Sede Bogotá', 'numero' => '573503335394', 'orden' => 1, 'activo' => true ),
			'tocancipa' => array( 'nombre' => 'Sede Tocancipá', 'numero' => '573104719817', 'orden' => 2, 'activo' => true ),
			'pqrs'      => array( 'nombre' => 'PQRS / Redes sociales', 'numero' => '', 'orden' => 3, 'activo' => true ),
		),
		'esquina' => 'inferior-derecha',
	);
}

/** Opción completa mezclada con los valores por defecto. */
function tecnostar_wa_opcion(): array {
	$def   = tecnostar_wa_defaults();
	$guard = get_option( 'tecnostar_wa_canales', array() );
	if ( ! is_array( $guard ) ) {
		$guard = array();
	}
	$canales = $def['canales'];
	if ( isset( $guard['canales'] ) && is_array( $guard['canales'] ) ) {
		foreach ( $canales as $id => $c ) {
			if ( isset( $guard['canales'][ $id ] ) && is_array( $guard['canales'][ $id ] ) ) {
				$canales[ $id ] = array_merge( $c, $guard['canales'][ $id ] );
			}
		}
	}
	$esquina = ( isset( $guard['esquina'] ) && in_array( $guard['esquina'], tecnostar_wa_esquinas_validas(), true ) ) ? $guard['esquina'] : $def['esquina'];
	return array( 'canales' => $canales, 'esquina' => $esquina );
}

/** Canales activos con número, ordenados por `orden`. Cada uno: id, nombre, numero. */
function tecnostar_canales_whatsapp(): array {
	$opc = tecnostar_wa_opcion();
	$out = array();
	foreach ( $opc['canales'] as $id => $c ) {
		$numero = preg_replace( '/\D+/', '', (string) $c['numero'] );
		if ( empty( $c['activo'] ) || '' === $numero ) {
			continue;
		}
		$out[] = array( 'id' => $id, 'nombre' => (string) $c['nombre'], 'numero' => $numero, 'orden' => (int) $c['orden'] );
	}
	usort(
		$out,
		function ( $a, $b ) {
			return $a['orden'] <=> $b['orden'];
		}
	);
	return array_map(
		function ( $c ) {
			unset( $c['orden'] );
			return $c;
		},
		$out
	);
}

/** Enlace de WhatsApp de un canal; cadena vacía si no existe o no tiene número. */
function tecnostar_wa_url( string $canal_id, string $mensaje ): string {
	foreach ( tecnostar_canales_whatsapp() as $c ) {
		if ( $c['id'] === $canal_id ) {
			return tecnostar_wa_armar_url( $c['numero'], $mensaje );
		}
	}
	return '';
}

/** Esquina del botón flotante. */
function tecnostar_wa_esquina(): string {
	return tecnostar_wa_opcion()['esquina'];
}

/** PQRS como pendiente mientras su número esté vacío. Las sedes no son pendientes. */
add_action(
	'init',
	function () {
		$opc = tecnostar_wa_opcion();
		if ( '' === preg_replace( '/\D+/', '', (string) $opc['canales']['pqrs']['numero'] ) && function_exists( 'tecnostar_pendiente' ) ) {
			tecnostar_pendiente( 'wa-pqrs-numero', 'Número de WhatsApp de PQRS / Redes sociales', 'Ajustes > WhatsApp' );
		}
	}
);

/** Página de ajustes. */
add_action(
	'admin_menu',
	function () {
		add_options_page( 'WhatsApp', 'WhatsApp', 'manage_options', 'tecnostar-whatsapp', 'tecnostar_wa_pagina_ajustes' );
	}
);

/** Sanea lo enviado por el formulario (ya sin barras). */
function tecnostar_wa_sanear( array $in ): array {
	$def   = tecnostar_wa_defaults();
	$sal   = array( 'canales' => array(), 'esquina' => $def['esquina'] );
	$envia = isset( $in['canales'] ) && is_array( $in['canales'] ) ? $in['canales'] : array();
	foreach ( $def['canales'] as $id => $c ) {
		$f      = isset( $envia[ $id ] ) && is_array( $envia[ $id ] ) ? $envia[ $id ] : array();
		$nombre = isset( $f['nombre'] ) ? sanitize_text_field( (string) $f['nombre'] ) : '';
		$sal['canales'][ $id ] = array(
			'nombre' => '' !== $nombre ? $nombre : $c['nombre'],
			'numero' => isset( $f['numero'] ) ? preg_replace( '/\D+/', '', (string) $f['numero'] ) : '',
			'orden'  => isset( $f['orden'] ) ? max( 1, (int) $f['orden'] ) : $c['orden'],
			'activo' => ! empty( $f['activo'] ),
		);
	}
	if ( isset( $in['esquina'] ) && in_array( $in['esquina'], tecnostar_wa_esquinas_validas(), true ) ) {
		$sal['esquina'] = $in['esquina'];
	}
	return $sal;
}

function tecnostar_wa_pagina_ajustes(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_POST['tecnostar_wa_guardar'] ) ) {
		check_admin_referer( 'tecnostar_wa_guardar', 'tecnostar_wa_nonce' );
		update_option( 'tecnostar_wa_canales', tecnostar_wa_sanear( wp_unslash( $_POST ) ), false );
		echo '<div class="notice notice-success"><p>Cambios guardados.</p></div>';
	}
	$opc = tecnostar_wa_opcion();
	echo '<div class="wrap"><h1>WhatsApp</h1><form method="post">';
	wp_nonce_field( 'tecnostar_wa_guardar', 'tecnostar_wa_nonce' );
	echo '<p>Escriba los números con prefijo de país y solo dígitos (por ejemplo 573503335394). Un canal sin número no se muestra en el sitio.</p>';
	echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>Canal</th><th>Nombre</th><th>Número</th><th>Orden</th><th>Activo</th></tr></thead><tbody>';
	foreach ( $opc['canales'] as $id => $c ) {
		$n = 'canales[' . $id . ']';
		echo '<tr><td>' . esc_html( $id ) . '</td>';
		echo '<td><input type="text" name="' . esc_attr( $n ) . '[nombre]" value="' . esc_attr( $c['nombre'] ) . '" class="regular-text"></td>';
		echo '<td><input type="text" inputmode="numeric" name="' . esc_attr( $n ) . '[numero]" value="' . esc_attr( $c['numero'] ) . '"></td>';
		echo '<td><input type="number" min="1" name="' . esc_attr( $n ) . '[orden]" value="' . esc_attr( (string) $c['orden'] ) . '" class="small-text"></td>';
		echo '<td><input type="checkbox" name="' . esc_attr( $n ) . '[activo]" value="1"' . checked( ! empty( $c['activo'] ), true, false ) . '></td></tr>';
	}
	echo '</tbody></table><p><label>Esquina del botón flotante: <select name="esquina">';
	foreach ( tecnostar_wa_esquinas_validas() as $e ) {
		echo '<option value="' . esc_attr( $e ) . '"' . selected( $opc['esquina'], $e, false ) . '>' . esc_html( str_replace( '-', ' ', $e ) ) . '</option>';
	}
	echo '</select></label></p>';
	submit_button( 'Guardar cambios', 'primary', 'tecnostar_wa_guardar' );
	echo '</form></div>';
}
