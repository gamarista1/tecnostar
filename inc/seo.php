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
	$default = 'Laptops, equipos gamer, componentes y accesorios con envío express en Bogotá y a toda Colombia. Paga con tarjeta, PSE, Nequi, Addi o Sistecrédito.';
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

/**
 * Título de la portada: el nombre de la marca ya dice "Electrónica", así que no se repite en el eslogan.
 */
add_filter(
	'pre_get_document_title',
	function ( $title ) {
		return is_front_page() ? 'TecnoStar Ingeniería Electrónica | Laptops y equipos gamer en Colombia' : $title;
	}
);

/**
 * Open Graph y Twitter Card: título, descripción e imagen 1200×630 con el logo completo.
 */
function tecnostar_social_meta() {
	$titulo = trim( wp_strip_all_tags( wp_specialchars_decode( wp_get_document_title() ) ) );
	$desc   = tecnostar_meta_description();
	$url    = home_url( '/' );
	$tipo   = 'website';
	$imagen = get_theme_file_uri( 'assets/img/og/og-tecnostar.jpg' );
	$medidas = true;

	if ( is_singular() ) {
		$url = (string) get_permalink();
		if ( is_singular( 'product' ) ) {
			$tipo = 'product';
		}
		$thumb = get_post_thumbnail_id();
		if ( $thumb ) {
			$src = wp_get_attachment_image_url( $thumb, 'large' );
			if ( $src ) {
				$imagen  = $src;
				$medidas = false;
			}
		}
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$enlace = get_term_link( get_queried_object() );
		if ( ! is_wp_error( $enlace ) ) {
			$url = $enlace;
		}
	}

	$meta = array(
		'og:type'        => $tipo,
		'og:site_name'   => 'TecnoStar Ingeniería Electrónica',
		'og:locale'      => 'es_CO',
		'og:title'       => $titulo,
		'og:description' => $desc,
		'og:url'         => $url,
		'og:image'       => $imagen,
	);
	if ( $medidas ) {
		$meta['og:image:width']  = '1200';
		$meta['og:image:height'] = '630';
		$meta['og:image:alt']    = 'Logo de TecnoStar Ingeniería Electrónica';
	}
	foreach ( $meta as $clave => $valor ) {
		printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $clave ), esc_attr( $valor ) );
	}
	echo '<meta name="twitter:card" content="' . ( $medidas ? 'summary_large_image' : 'summary' ) . '">' . "\n";
}

add_action(
	'wp_head',
	function () {
		if ( is_404() || is_search() ) {
			return;
		}
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( tecnostar_meta_description() ) );
		tecnostar_social_meta();
	},
	2
);
