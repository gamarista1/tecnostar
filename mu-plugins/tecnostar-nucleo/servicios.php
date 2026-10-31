<?php
/**
 * Tipo de contenido Servicio (ts_servicio).
 *
 * Los servicios cuelgan de /persona-natural/<slug>/; la página índice /persona-natural/ es una página normal.
 * Metas: _ts_precio_ref (precio de referencia, texto) y _ts_icono (nombre del icono, texto).
 */

defined( 'ABSPATH' ) || exit;

const TECNOSTAR_SERVICIOS_RW_VERSION = '1';

/** Registra el tipo de contenido. */
function tecnostar_servicios_registrar() {
	register_post_type(
		'ts_servicio',
		array(
			'labels'       => array(
				'name'               => 'Servicios',
				'singular_name'      => 'Servicio',
				'menu_name'          => 'Servicios',
				'add_new'            => 'Añadir servicio',
				'add_new_item'       => 'Añadir nuevo servicio',
				'edit_item'          => 'Editar servicio',
				'new_item'           => 'Nuevo servicio',
				'view_item'          => 'Ver servicio',
				'search_items'       => 'Buscar servicios',
				'not_found'          => 'No se encontraron servicios',
				'not_found_in_trash' => 'No hay servicios en la papelera',
				'all_items'          => 'Todos los servicios',
			),
			'public'       => true,
			'has_archive'  => false,
			'rewrite'      => array(
				'slug'       => 'persona-natural',
				'with_front' => false,
			),
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-hammer',
		)
	);

	foreach ( array( '_ts_precio_ref', '_ts_icono' ) as $meta ) {
		register_post_meta(
			'ts_servicio',
			$meta,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function ( $permitido, $clave, $id ) {
					return current_user_can( 'edit_post', $id );
				},
			)
		);
	}
}
add_action( 'init', 'tecnostar_servicios_registrar' );

/** Caja de campos en el editor. */
function tecnostar_servicios_caja() {
	add_meta_box( 'tecnostar_servicio_datos', 'Datos del servicio', 'tecnostar_servicios_caja_html', 'ts_servicio', 'side' );
}
add_action( 'add_meta_boxes', 'tecnostar_servicios_caja' );

function tecnostar_servicios_caja_html( $post ) {
	wp_nonce_field( 'tecnostar_servicio_guardar', 'tecnostar_servicio_nonce' );
	$precio = get_post_meta( $post->ID, '_ts_precio_ref', true );
	$icono  = get_post_meta( $post->ID, '_ts_icono', true );
	echo '<p><label for="ts_precio_ref">Precio de referencia</label><br>';
	echo '<input type="text" class="widefat" id="ts_precio_ref" name="ts_precio_ref" value="' . esc_attr( $precio ) . '" placeholder="Desde $80.000"></p>';
	echo '<p><label for="ts_icono">Icono (nombre)</label><br>';
	echo '<input type="text" class="widefat" id="ts_icono" name="ts_icono" value="' . esc_attr( $icono ) . '"></p>';
}

function tecnostar_servicios_guardar( $post_id ) {
	if ( ! isset( $_POST['tecnostar_servicio_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tecnostar_servicio_nonce'] ) ), 'tecnostar_servicio_guardar' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array(
		'_ts_precio_ref' => 'ts_precio_ref',
		'_ts_icono'      => 'ts_icono',
	) as $meta => $campo ) {
		if ( isset( $_POST[ $campo ] ) ) {
			update_post_meta( $post_id, $meta, sanitize_text_field( wp_unslash( $_POST[ $campo ] ) ) );
		}
	}
}
add_action( 'save_post_ts_servicio', 'tecnostar_servicios_guardar' );

/** Regenera las reglas de reescritura una sola vez (opción de versión + candado atómico). */
function tecnostar_servicios_regenerar() {
	if ( get_option( 'tecnostar_servicios_rw' ) === TECNOSTAR_SERVICIOS_RW_VERSION ) {
		return;
	}
	// add_option falla si ya existe: es atómico en MySQL.
	if ( ! add_option( 'tecnostar_servicios_rw_lock', time(), '', false ) ) {
		$desde = (int) get_option( 'tecnostar_servicios_rw_lock' );
		if ( $desde && ( time() - $desde ) <= 60 ) {
			return;
		}
		delete_option( 'tecnostar_servicios_rw_lock' );
		if ( ! add_option( 'tecnostar_servicios_rw_lock', time(), '', false ) ) {
			return;
		}
	}
	flush_rewrite_rules();
	update_option( 'tecnostar_servicios_rw', TECNOSTAR_SERVICIOS_RW_VERSION, false );
	delete_option( 'tecnostar_servicios_rw_lock' );
}
add_action( 'init', 'tecnostar_servicios_regenerar', 99 );
