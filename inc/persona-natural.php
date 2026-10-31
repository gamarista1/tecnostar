<?php
/**
 * Persona Natural y servicios: estilos compartidos de páginas (paginas.css), iconos y utilidades de las tarjetas.
 * Un servicio "tiene página" cuando está publicado y su contenido no está vacío; si no, su tarjeta abre WhatsApp.
 */

defined( 'ABSPATH' ) || exit;

/** True en el índice de Persona Natural y en las páginas de servicio. */
function tecnostar_es_pagina_servicios(): bool {
	return is_page_template( 'page-templates/persona-natural.php' ) || is_singular( 'ts_servicio' );
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( tecnostar_es_pagina_servicios() ) {
			wp_enqueue_style( 'tecnostar-paginas', get_template_directory_uri() . '/assets/css/paginas.css', array( 'tecnostar-components' ), tecnostar_asset_ver( '/assets/css/paginas.css' ) );
		}
	}
);

/** ¿El servicio tiene página propia? */
function tecnostar_servicio_tiene_pagina( $post ): bool {
	$post = get_post( $post );
	return $post && 'publish' === $post->post_status && '' !== trim( wp_strip_all_tags( $post->post_content ) );
}

/** Un servicio sin contenido no tiene página: su URL lleva al índice. */
add_action(
	'template_redirect',
	function () {
		if ( is_singular( 'ts_servicio' ) && ! tecnostar_servicio_tiene_pagina( get_queried_object() ) && ! current_user_can( 'edit_posts' ) ) {
			wp_safe_redirect( home_url( '/persona-natural/' ), 302 );
			exit;
		}
	}
);

/** Iconos de línea (SVG en línea, sin peticiones). Clave = meta _ts_icono. */
function tecnostar_icono_servicio( string $clave ): string {
	$trazos = array(
		'windows'   => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
		'repuestos' => '<circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/>',
		'bateria'   => '<rect x="3" y="8" width="16" height="8" rx="2"/><path d="M21 11v2M7 12h4"/>',
		'celular'   => '<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/>',
		'board'     => '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 2v2M15 2v2M9 20v2M15 20v2M2 9h2M2 15h2M20 9h2M20 15h2"/>',
		'camara'    => '<path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="3.5"/>',
	);
	$t = isset( $trazos[ $clave ] ) ? $trazos[ $clave ] : '<path d="M14.5 5.5a4 4 0 0 0-5 5L4 16v4h4l5.5-5.5a4 4 0 0 0 5-5l-2.5 2.5-2-.5-.5-2z"/>';
	return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $t . '</svg>';
}
