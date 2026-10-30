<?php
/**
 * SEO básico en el tema (antes en el snippet #2 de Code Snippets).
 * Solo buenas prácticas; Search Console y descubrimiento por LLM quedan para una sesión posterior.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta descripción por tipo de página.
 */
function tecnostar_meta_description() {
	$default = 'Tienda en línea de computación y electrónica en Colombia: laptops, equipos gamer, componentes, accesorios y servicio técnico especializado. TecnoStar Ingeniería Electrónica.';
	$text    = '';

	if ( is_front_page() ) {
		$text = (string) get_theme_mod( 'seo_description', $default );
	} elseif ( is_singular( 'product' ) ) {
		$product = wc_get_product( get_the_ID() );
		$text    = $product ? $product->get_short_description() : '';
		if ( ! $text ) {
			$text = get_the_excerpt();
		}
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$text = term_description();
	} elseif ( is_singular() ) {
		$text = get_the_excerpt();
	}

	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $text ) ) );
	if ( '' === $text ) {
		$text = $default;
	}
	return mb_strimwidth( $text, 0, 158, '…' );
}

add_action(
	'wp_head',
	function () {
		if ( is_404() || is_search() ) {
			return;
		}
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( tecnostar_meta_description() ) );
	},
	2
);
