<?php
/**
 * Reproductor de video diferido (template-parts/video.php + assets/js/video.js + assets/css/video.css).
 * Se usa en la ficha de producto y en Nosotros. El video no se descarga hasta que el cliente lo toca; si la
 * fuente es una clave de R2 (videos/xxx.mp4), el JS pide una URL prefirmada a admin-ajax (ts_video, en el núcleo).
 */

defined( 'ABSPATH' ) || exit;

/** Páginas que usan el reproductor. */
function tecnostar_video_en_pagina(): bool {
	return ( function_exists( 'is_product' ) && is_product() ) || ( function_exists( 'tecnostar_es_nosotros' ) && tecnostar_es_nosotros() );
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! tecnostar_video_en_pagina() ) {
			return;
		}
		wp_enqueue_style( 'tecnostar-video', get_template_directory_uri() . '/assets/css/video.css', array( 'tecnostar-components' ), tecnostar_asset_ver( '/assets/css/video.css' ) );
		wp_enqueue_script( 'tecnostar-video', get_template_directory_uri() . '/assets/js/video.js', array(), tecnostar_asset_ver( '/assets/js/video.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	},
	20
);

/** Devuelve el HTML del reproductor (mismos argumentos que template-parts/video.php). */
function tecnostar_video_reproductor( array $args ): string {
	ob_start();
	get_template_part( 'template-parts/video', null, $args );
	return (string) ob_get_clean();
}
