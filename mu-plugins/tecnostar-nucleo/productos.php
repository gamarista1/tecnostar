<?php
/**
 * Atributos globales de producto: Marca, Grado y Uso.
 *
 * Crea los atributos de WooCommerce (pa_marca, pa_grado, pa_uso) y sus términos iniciales.
 * Es idempotente: solo crea lo que no existe.
 */

defined( 'ABSPATH' ) || exit;

/** Definición de atributos y términos iniciales (slug => nombre visible). */
function tecnostar_atributos_definicion() {
	return array(
		'marca' => array(
			'nombre'   => 'Marca',
			'terminos' => array(
				'dell'   => 'Dell',
				'hp'     => 'HP',
				'lenovo' => 'Lenovo',
				'asus'   => 'Asus',
				'apple'  => 'Apple',
			),
		),
		'grado' => array(
			'nombre'   => 'Grado',
			'terminos' => array(
				'a' => 'Grado A · Nuevo',
				'b' => 'Grado B · Usado certificado',
			),
		),
		'uso'   => array(
			'nombre'   => 'Uso',
			'terminos' => array(
				'oficina' => 'Oficina',
				'gamer'   => 'Gamer',
				'estudio' => 'Estudio',
				'diseno'  => 'Diseño',
			),
		),
	);
}

const TECNOSTAR_ATRIBUTOS_VERSION = '1';

/** True si en esta petición se obtuvo el candado de creación. */
function tecnostar_atributos_candado( $accion = 'estado' ) {
	static $propio = false;
	if ( 'tomar' === $accion ) {
		if ( get_option( 'tecnostar_atributos_v' ) === TECNOSTAR_ATRIBUTOS_VERSION ) {
			return false;
		}
		// add_option falla si ya existe: es atómico en MySQL.
		if ( ! add_option( 'tecnostar_atributos_lock', time(), '', false ) ) {
			$desde = (int) get_option( 'tecnostar_atributos_lock' );
			if ( $desde && ( time() - $desde ) <= 60 ) {
				return false;
			}
			// Candado vencido: se retira y se intenta de nuevo.
			delete_option( 'tecnostar_atributos_lock' );
			if ( ! add_option( 'tecnostar_atributos_lock', time(), '', false ) ) {
				return false;
			}
		}
		$propio = true;
	} elseif ( 'soltar' === $accion ) {
		delete_option( 'tecnostar_atributos_lock' );
		$propio = false;
	}
	return $propio;
}

/** Crea los atributos globales que falten. WooCommerce registra sus taxonomías en init (prioridad 5). */
function tecnostar_crear_atributos() {
	if ( ! function_exists( 'wc_create_attribute' ) || ! function_exists( 'wc_attribute_taxonomy_id_by_name' ) ) {
		return;
	}
	if ( ! tecnostar_atributos_candado( 'tomar' ) ) {
		return;
	}
	$creado = false;
	foreach ( tecnostar_atributos_definicion() as $slug => $def ) {
		if ( wc_attribute_taxonomy_id_by_name( $slug ) ) {
			continue;
		}
		$id = wc_create_attribute(
			array(
				'name'         => $def['nombre'],
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
		if ( ! is_wp_error( $id ) ) {
			$creado = true;
		}
	}
	if ( $creado ) {
		delete_transient( 'wc_attribute_taxonomies' );
		wp_cache_delete( 'notoptions', 'options' );
	}
}
add_action( 'init', 'tecnostar_crear_atributos', 15 );

/** Inserta los términos que falten, cuando las taxonomías ya existen. */
function tecnostar_crear_terminos_atributos() {
	if ( ! tecnostar_atributos_candado() ) {
		return;
	}
	$completo = true;
	foreach ( tecnostar_atributos_definicion() as $slug => $def ) {
		$taxonomia = 'pa_' . $slug;
		if ( ! taxonomy_exists( $taxonomia ) ) {
			// Recién creado en esta misma petición: registrar la taxonomía a mano.
			if ( function_exists( 'wc_attribute_taxonomy_id_by_name' ) && wc_attribute_taxonomy_id_by_name( $slug ) ) {
				register_taxonomy( $taxonomia, array( 'product' ), array( 'hierarchical' => false, 'public' => false ) );
			} else {
				$completo = false;
				continue;
			}
		}
		foreach ( $def['terminos'] as $term_slug => $nombre ) {
			if ( term_exists( $term_slug, $taxonomia ) ) {
				continue;
			}
			$res = wp_insert_term( $nombre, $taxonomia, array( 'slug' => $term_slug ) );
			if ( is_wp_error( $res ) ) {
				$completo = false;
			}
		}
	}
	if ( $completo ) {
		update_option( 'tecnostar_atributos_v', TECNOSTAR_ATRIBUTOS_VERSION, true );
	}
	tecnostar_atributos_candado( 'soltar' );
}
add_action( 'init', 'tecnostar_crear_terminos_atributos', 20 );

/* ------------------------------------------------------------------
 * Grado de producto, campos de características y validación.
 * ------------------------------------------------------------------ */

/** Devuelve 'A', 'B' o null según los términos de pa_grado del producto. */
function tecnostar_grado( int $product_id ): ?string {
	if ( ! taxonomy_exists( 'pa_grado' ) ) {
		return null;
	}
	$terminos = wp_get_post_terms( $product_id, 'pa_grado', array( 'fields' => 'slugs' ) );
	if ( is_wp_error( $terminos ) || empty( $terminos ) ) {
		return null;
	}
	if ( in_array( 'a', $terminos, true ) ) {
		return 'A';
	}
	if ( in_array( 'b', $terminos, true ) ) {
		return 'B';
	}
	return null;
}

/** Campos de texto de características (meta => etiqueta). */
function tecnostar_campos_texto() {
	return array(
		'_ts_procesador'     => 'Procesador',
		'_ts_ram'            => 'Memoria RAM',
		'_ts_almacenamiento' => 'Almacenamiento',
		'_ts_pantalla'       => 'Pantalla',
		'_ts_bateria'        => 'Batería',
	);
}

/** Casillas de verificación (meta => etiqueta). */
function tecnostar_campos_casillas() {
	return array(
		'_ts_chk_cargador' => 'Incluye cargador original',
		'_ts_chk_bateria'  => 'Batería verificada',
		'_ts_chk_factura'  => 'Incluye factura',
		'_ts_chk_garantia' => 'Incluye garantía',
	);
}

/** Pinta los campos en la pestaña General del producto. */
function tecnostar_campos_producto_mostrar() {
	echo '<div class="options_group">';
	wp_nonce_field( 'tecnostar_campos_producto', 'tecnostar_campos_nonce' );
	foreach ( tecnostar_campos_texto() as $meta => $etiqueta ) {
		woocommerce_wp_text_input(
			array(
				'id'    => $meta,
				'label' => $etiqueta,
			)
		);
	}
	foreach ( tecnostar_campos_casillas() as $meta => $etiqueta ) {
		woocommerce_wp_checkbox(
			array(
				'id'    => $meta,
				'label' => $etiqueta,
			)
		);
	}
	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'tecnostar_campos_producto_mostrar' );

/** Guarda los campos con nonce, capacidad y saneado. */
function tecnostar_campos_producto_guardar( $post_id ) {
	if ( ! isset( $_POST['tecnostar_campos_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tecnostar_campos_nonce'] ) ), 'tecnostar_campos_producto' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_product', $post_id ) ) {
		return;
	}
	foreach ( array_keys( tecnostar_campos_texto() ) as $meta ) {
		$valor = isset( $_POST[ $meta ] ) ? sanitize_text_field( wp_unslash( $_POST[ $meta ] ) ) : '';
		if ( '' === $valor ) {
			delete_post_meta( $post_id, $meta );
		} else {
			update_post_meta( $post_id, $meta, $valor );
		}
	}
	foreach ( array_keys( tecnostar_campos_casillas() ) as $meta ) {
		if ( isset( $_POST[ $meta ] ) ) {
			update_post_meta( $post_id, $meta, 'yes' );
		} else {
			delete_post_meta( $post_id, $meta );
		}
	}
}
add_action( 'woocommerce_process_product_meta', 'tecnostar_campos_producto_guardar' );

/** True si el objeto producto (aún sin guardar) trae al menos un término de pa_grado. */
function tecnostar_objeto_tiene_grado( $producto ) {
	foreach ( $producto->get_attributes() as $clave => $atributo ) {
		if ( 'pa_grado' === $clave && is_object( $atributo ) && method_exists( $atributo, 'get_options' ) ) {
			$opciones = array_filter( (array) $atributo->get_options() );
			if ( ! empty( $opciones ) ) {
				return true;
			}
		}
	}
	return false;
}

/** REST: rechaza publicar sin Grado. */
function tecnostar_rest_exigir_grado( $producto, $request ) {
	$id     = (int) $producto->get_id();
	$previo = $id ? get_post_status( $id ) : false;
	// Solo la transición a publicado: lo ya publicado se edita libremente.
	if ( 'publish' === $producto->get_status() && 'publish' !== $previo && ! tecnostar_objeto_tiene_grado( $producto ) ) {
		return new WP_Error(
			'tecnostar_grado_requerido',
			'Un producto sin Grado (A o B) no se puede publicar. Guárdalo como borrador o asigna el atributo Grado.',
			array( 'status' => 400 )
		);
	}
	return $producto;
}
add_filter( 'woocommerce_rest_pre_insert_product_object', 'tecnostar_rest_exigir_grado', 10, 2 );

/** Estado previo de cada producto en esta petición (lo fija core antes de guardar). */
function tecnostar_estado_previo( $nuevo = null, $viejo = null, $post = null ) {
	static $mapa = array();
	if ( $post instanceof WP_Post && 'product' === $post->post_type ) {
		$mapa[ $post->ID ] = $viejo;
	}
	return $mapa;
}
add_action( 'transition_post_status', 'tecnostar_estado_previo', 10, 3 );

/** Administrador: al pasar a publicado sin Grado vuelve a borrador y avisa; lo ya publicado se guarda tal cual. */
function tecnostar_admin_exigir_grado( $producto ) {
	if ( 'publish' !== $producto->get_status() || tecnostar_objeto_tiene_grado( $producto ) ) {
		return;
	}
	$mapa   = tecnostar_estado_previo();
	$previo = $mapa[ $producto->get_id() ] ?? null;
	if ( 'publish' === $previo ) {
		set_transient( 'tecnostar_aviso_grado_' . get_current_user_id(), 'info', 120 );
		return;
	}
	$producto->set_status( 'draft' );
	set_transient( 'tecnostar_aviso_grado_' . get_current_user_id(), 'error', 120 );
}
add_action( 'woocommerce_admin_process_product_object', 'tecnostar_admin_exigir_grado', 99 );

function tecnostar_admin_aviso_grado() {
	$clave = 'tecnostar_aviso_grado_' . get_current_user_id();
	$tipo  = get_transient( $clave );
	if ( ! $tipo ) {
		return;
	}
	delete_transient( $clave );
	if ( 'info' === $tipo ) {
		echo '<div class="notice notice-warning is-dismissible"><p>Este producto no tiene Grado; asígnalo cuando puedas.</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p><strong>Falta el Grado.</strong> El producto se guardó como borrador: asigna el atributo Grado (A o B) para poder publicarlo.</p></div>';
	}
}
add_action( 'admin_notices', 'tecnostar_admin_aviso_grado' );
