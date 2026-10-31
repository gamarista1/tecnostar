<?php
/**
 * Armado de enlaces de WhatsApp: lógica pura, sin dependencias de WordPress (se prueba por consola).
 * Solo define funciones; no ejecuta nada al cargarse.
 */

/**
 * Arma https://wa.me/<número>?text=<mensaje>.
 * El número se limpia a dígitos; el mensaje se normaliza a \n y se recorta a 900 caracteres (no bytes).
 */
function tecnostar_wa_armar_url( string $numero, string $mensaje ): string {
	$numero  = preg_replace( '/\D+/', '', $numero );
	$mensaje = str_replace( array( "\r\n", "\r" ), "\n", $mensaje );
	// Descarta bytes UTF-8 inválidos antes de recortar por caracteres.
	$limpio = @iconv( 'UTF-8', 'UTF-8//IGNORE', $mensaje );
	if ( false !== $limpio ) {
		$mensaje = $limpio;
	}
	if ( mb_strlen( $mensaje, 'UTF-8' ) > 900 ) {
		$mensaje = mb_substr( $mensaje, 0, 900, 'UTF-8' );
	}
	return 'https://wa.me/' . $numero . '?text=' . rawurlencode( $mensaje );
}
