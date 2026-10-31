<?php
/**
 * Registro de pendientes: contenido de ejemplo que falta reemplazar por el real.
 *
 * Opción `tecnostar_pendientes`: array de id => array( 'descripcion' => string, 'donde' => string, 'resuelto' => bool ).
 */

defined( 'ABSPATH' ) || exit;

/** Lee el registro completo. */
function tecnostar_pendientes_todos() {
	$todos = get_option( 'tecnostar_pendientes', array() );
	return is_array( $todos ) ? $todos : array();
}

/**
 * Registra un ejemplo por reemplazar. Si ya existe, no lo toca (conserva el estado "resuelto").
 *
 * @param string $id          Identificador estable, por ejemplo "home-banner-1".
 * @param string $descripcion Qué falta (en palabras del equipo).
 * @param string $donde       Dónde está, por ejemplo "Home > Carrusel".
 */
function tecnostar_pendiente( string $id, string $descripcion, string $donde ): void {
	$id = sanitize_key( $id );
	if ( '' === $id ) {
		return;
	}
	$todos = tecnostar_pendientes_todos();
	if ( isset( $todos[ $id ] ) ) {
		return;
	}
	$todos[ $id ] = array(
		'descripcion' => sanitize_text_field( $descripcion ),
		'donde'       => sanitize_text_field( $donde ),
		'resuelto'    => false,
	);
	update_option( 'tecnostar_pendientes', $todos, false );
}

/**
 * Envuelve un HTML de ejemplo y registra el pendiente. El HTML lo arma el tema, ya escapado.
 *
 * @return string HTML dentro de <span class="es-ejemplo" data-pendiente="id">.
 */
function tecnostar_ejemplo( string $id, string $html ): string {
	$id = sanitize_key( $id );
	tecnostar_pendiente( $id, $id, 'Sin ubicación indicada' );
	return '<span class="es-ejemplo" data-pendiente="' . esc_attr( $id ) . '">' . $html . '</span>';
}

/** Clase is-editor en <body> solo para quienes pueden editar. */
add_filter(
	'body_class',
	function ( $clases ) {
		if ( current_user_can( 'edit_posts' ) ) {
			$clases[] = 'is-editor';
		}
		return $clases;
	}
);

/** Contorno discontinuo del ejemplo, visible solo con is-editor. */
add_action(
	'wp_head',
	function () {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		echo '<style id="tecnostar-es-ejemplo">body.is-editor .es-ejemplo{outline:2px dashed #ffb128;outline-offset:2px}</style>' . "\n";
	}
);

/** Página Herramientas > Pendientes. */
add_action(
	'admin_menu',
	function () {
		add_management_page( 'Pendientes', 'Pendientes', 'manage_options', 'tecnostar-pendientes', 'tecnostar_pendientes_pagina' );
	}
);

/** Marcar como resuelto (POST con nonce, capacidad manage_options). */
add_action(
	'admin_post_tecnostar_resolver_pendiente',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permiso para hacer esto.', 'default' ), 403 );
		}
		$id = isset( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : '';
		check_admin_referer( 'tecnostar_resolver_' . $id );
		$todos = tecnostar_pendientes_todos();
		if ( '' !== $id && isset( $todos[ $id ] ) ) {
			$todos[ $id ]['resuelto'] = true;
			update_option( 'tecnostar_pendientes', $todos, false );
		}
		wp_safe_redirect( admin_url( 'tools.php?page=tecnostar-pendientes' ) );
		exit;
	}
);

function tecnostar_pendientes_pagina() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permiso para ver esta página.', 'default' ), 403 );
	}
	$todos = tecnostar_pendientes_todos();
	echo '<div class="wrap"><h1>Pendientes</h1>';
	echo '<p>Contenido de ejemplo que falta reemplazar por el real.</p>';
	if ( ! $todos ) {
		echo '<p>No hay pendientes registrados.</p></div>';
		return;
	}
	echo '<table class="widefat striped"><thead><tr><th>Identificador</th><th>Qué falta</th><th>Dónde</th><th>Estado</th></tr></thead><tbody>';
	foreach ( $todos as $id => $p ) {
		$resuelto = ! empty( $p['resuelto'] );
		echo '<tr><td><code>' . esc_html( $id ) . '</code></td>';
		echo '<td>' . esc_html( isset( $p['descripcion'] ) ? $p['descripcion'] : '' ) . '</td>';
		echo '<td>' . esc_html( isset( $p['donde'] ) ? $p['donde'] : '' ) . '</td><td>';
		if ( $resuelto ) {
			echo 'Resuelto';
		} else {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			echo '<input type="hidden" name="action" value="tecnostar_resolver_pendiente">';
			echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
			wp_nonce_field( 'tecnostar_resolver_' . $id );
			echo '<button type="submit" class="button">Marcar como resuelto</button></form>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table></div>';
}
