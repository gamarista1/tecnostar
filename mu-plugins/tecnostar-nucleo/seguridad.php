<?php
/**
 * Encabezados de seguridad de las páginas del sitio.
 *
 * - X-Frame-Options y frame-ancestors: solo el propio sitio puede incrustar sus páginas (evita clickjacking).
 * - COOP same-origin-allow-popups: aísla la ventana del sitio pero deja abrir las ventanas de pago y de WhatsApp.
 * - HSTS: el navegador usa siempre https (un año, sin includeSubDomains ni preload: api.* y otros subdominios no se tocan).
 *
 * Sin CSP completa a propósito: el sitio carga scripts de Addi, Wompi, Google y WhatsApp, y una política estricta
 * sin medirla antes puede romper el pago. Mejor medirla primero en modo "solo informe".
 */

defined( 'ABSPATH' ) || exit;

add_action( 'send_headers', function () {
	if ( headers_sent() ) {
		return;
	}
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( "Content-Security-Policy: frame-ancestors 'self'" );
	header( 'Cross-Origin-Opener-Policy: same-origin-allow-popups' );
	header( 'X-Content-Type-Options: nosniff' );
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000' );
	}
} );
