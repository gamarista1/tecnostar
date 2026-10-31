<?php
/**
 * Rendimiento del front: quita lo que no se usa y aplaza lo que no hace falta al cargar.
 * Cada bloque es independiente; para revertir uno basta con borrarlo.
 */

defined( 'ABSPATH' ) || exit;

/*
 * 1. Emojis de WordPress: el detector crea un worker y un canvas en cada página (~0,5 s de hilo
 *    principal en móvil). Los navegadores actuales ya pintan los emojis por su cuenta.
 */
add_action( 'init', function () {
	// Según la versión, WordPress lo engancha en la cabecera o en el pie y con prioridades distintas.
	foreach ( array( 'wp_head', 'wp_footer', 'wp_print_footer_scripts' ) as $gancho ) {
		foreach ( array( 'print_emoji_detection_script', 'wp_print_emoji_detection_script' ) as $funcion ) {
			$prioridad = has_action( $gancho, $funcion );
			if ( false !== $prioridad ) {
				remove_action( $gancho, $funcion, $prioridad );
			}
		}
	}
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
} );
