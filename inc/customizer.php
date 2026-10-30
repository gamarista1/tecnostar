<?php
/**
 * Textos editables del hero de la home (Personalizar > Portada).
 * El equipo cambia titular, texto y botón sin tocar código.
 */

defined( 'ABSPATH' ) || exit;

function tecnostar_hero_defaults() {
	return array(
		'hero_title'     => 'Laptops, equipos gamer y accesorios con despacho inmediato en Colombia',
		'hero_text'      => 'Tecnología que sí funciona, al mejor precio, con envío express en Bogotá y a todo el país.',
		'hero_cta_label' => 'Ver la tienda',
		'hero_cta_url'   => '',
	);
}

add_action( 'customize_register', function ( WP_Customize_Manager $wp_customize ) {
	$wp_customize->add_section(
		'tecnostar_home',
		array(
			'title'    => __( 'Portada', 'tecnostar' ),
			'priority' => 30,
		)
	);

	$fields = array(
		'hero_title'     => array( __( 'Titular', 'tecnostar' ), 'text', 'sanitize_text_field' ),
		'hero_text'      => array( __( 'Texto', 'tecnostar' ), 'textarea', 'sanitize_textarea_field' ),
		'hero_cta_label' => array( __( 'Texto del botón', 'tecnostar' ), 'text', 'sanitize_text_field' ),
		'hero_cta_url'   => array( __( 'Enlace del botón (vacío = tienda)', 'tecnostar' ), 'url', 'esc_url_raw' ),
	);
	$defaults = tecnostar_hero_defaults();

	foreach ( $fields as $id => $field ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $defaults[ $id ],
				'sanitize_callback' => $field[2],
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			$id,
			array(
				'label'   => $field[0],
				'section' => 'tecnostar_home',
				'type'    => $field[1],
			)
		);
	}
} );

function tecnostar_hero( $key ) {
	$defaults = tecnostar_hero_defaults();
	return get_theme_mod( $key, $defaults[ $key ] );
}
