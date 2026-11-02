<?php
/**
 * Redes sociales de Tecnostar. Una sola fuente para el arco flotante y el pie de página.
 */
defined( 'ABSPATH' ) || exit;

/**
 * @return array<int,array{nombre:string,url:string,c:string,svg:string}> c = color de marca de la red.
 */
function tecnostar_redes_sociales(): array {
	$ico = static function ( string $contenido, string $extra = 'fill="currentColor"' ): string {
		return '<svg width="24" height="24" viewBox="0 0 24 24" ' . $extra . ' aria-hidden="true" focusable="false">' . $contenido . '</svg>';
	};
	return array(
		array(
			'nombre' => 'Instagram',
			'url'    => 'https://www.instagram.com/tecnostar.ing/',
			'c'      => '#c13584',
			'svg'    => $ico( '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="1" fill="currentColor" stroke="none"/>', 'fill="none" stroke="currentColor" stroke-width="2"' ),
		),
		array(
			'nombre' => 'Facebook',
			'url'    => 'https://www.facebook.com/tecnostar.ing/',
			'c'      => '#1877f2',
			'svg'    => $ico( '<path d="M13.500 21v-8h2.700l.4-3.200h-3.100V7.800c0-.9.300-1.500 1.600-1.500h1.700V3.400c-.3 0-1.300-.1-2.400-.1-2.400 0-4.100 1.500-4.100 4.200v2.300H7.600V13h2.700v8h3.200Z"/>' ),
		),
		array(
			'nombre' => 'LinkedIn',
			'url'    => 'https://www.linkedin.com/in/tecnostar-ingenier%C3%ADa-electr%C3%B3nica-profesional-sas-13b57b441/',
			'c'      => '#0a66c2',
			'svg'    => $ico( '<path d="M5 9h3.500v11H5V9Zm1.800-5.500a2 2 0 1 1 0 4 2 2 0 0 1 0-4ZM10.500 9h3.300v1.500c.5-.9 1.700-1.800 3.400-1.800 3.500 0 4.100 2.300 4.100 5.300V20h-3.500v-5.300c0-1.300 0-2.900-1.800-2.900s-2 1.400-2 2.800V20h-3.500V9Z"/>' ),
		),
		array(
			'nombre' => 'TikTok',
			'url'    => 'https://www.tiktok.com/@tecnostar.ing',
			'c'      => '#111111',
			'svg'    => $ico( '<path d="M16.600 3c.3 2.400 1.700 3.900 4 4.100v3.200c-1.500 0-2.900-.5-4-1.300v6.300a5.600 5.600 0 1 1-5.600-5.600c.3 0 .7 0 1 .1v3.300a2.400 2.400 0 1 0 1.400 2.200V3h3.200Z"/>' ),
		),
	);
}
