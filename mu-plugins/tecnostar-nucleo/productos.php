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

/** Crea los atributos globales que falten. WooCommerce registra sus taxonomías en init (prioridad 5). */
function tecnostar_crear_atributos() {
	if ( ! function_exists( 'wc_create_attribute' ) || ! function_exists( 'wc_attribute_taxonomy_id_by_name' ) ) {
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
	foreach ( tecnostar_atributos_definicion() as $slug => $def ) {
		$taxonomia = 'pa_' . $slug;
		if ( ! taxonomy_exists( $taxonomia ) ) {
			// Recién creado en esta misma petición: registrar la taxonomía a mano.
			if ( function_exists( 'wc_attribute_taxonomy_id_by_name' ) && wc_attribute_taxonomy_id_by_name( $slug ) ) {
				register_taxonomy( $taxonomia, array( 'product' ), array( 'hierarchical' => false, 'public' => false ) );
			} else {
				continue;
			}
		}
		foreach ( $def['terminos'] as $term_slug => $nombre ) {
			if ( term_exists( $term_slug, $taxonomia ) ) {
				continue;
			}
			wp_insert_term( $nombre, $taxonomia, array( 'slug' => $term_slug ) );
		}
	}
}
add_action( 'init', 'tecnostar_crear_terminos_atributos', 20 );
