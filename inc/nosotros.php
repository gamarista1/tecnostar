<?php
/**
 * Nosotros / Contáctenos: estilos y script solo en esa plantilla.
 * Reutiliza paginas.css (hero) y suma contacto.css (capítulos, sedes, video, formulario) y contacto.js
 * (mapa y video diferidos, envío del formulario sin recargar).
 */

defined( 'ABSPATH' ) || exit;

/** True en la plantilla Nosotros. */
function tecnostar_es_nosotros(): bool {
	return is_page_template( 'page-templates/nosotros.php' );
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! tecnostar_es_nosotros() ) {
			return;
		}
		$css = get_template_directory_uri() . '/assets/css/';
		wp_enqueue_style( 'tecnostar-paginas', $css . 'paginas.css', array( 'tecnostar-components' ), tecnostar_asset_ver( '/assets/css/paginas.css' ) );
		wp_enqueue_style( 'tecnostar-contacto', $css . 'contacto.css', array( 'tecnostar-paginas' ), tecnostar_asset_ver( '/assets/css/contacto.css' ) );
		wp_enqueue_script( 'tecnostar-contacto', get_template_directory_uri() . '/assets/js/contacto.js', array(), tecnostar_asset_ver( '/assets/js/contacto.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
);
