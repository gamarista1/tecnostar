<?php
/**
 * Datos estructurados (JSON-LD) y sitemap: LocalBusiness de las dos sedes, Product con garantía por Grado, Service de los servicios y BreadcrumbList.
 *
 * Sitemap: se usa el nativo de WordPress (/wp-sitemap.xml). WordPress lo desactiva mientras la opción
 * "blog_public" sea 0 (sitio desindexado, como el staging); en producción queda activo. Aquí solo se
 * delimita su contenido: páginas, servicios, productos y categorías de producto, sin carrito, finalizar
 * compra, mi cuenta ni acceso. Las URL con filtros (?marca=, ?grado=...) no entran porque el sitemap
 * lista solo permalinks.
 */

defined( 'ABSPATH' ) || exit;

/** Sedes con los datos del spec (§6.5). Función pura. */
function tecnostar_ld_sedes(): array {
	return array(
		array(
			'name'      => 'Tecnostar Ingeniería Electrónica - Bogotá',
			'street'    => 'C.C. Alta Tecnología, local 194',
			'locality'  => 'Bogotá',
			'telephone' => '+57 3503335394',
			'abre'      => '09:30',
			'cierra'    => '18:00',
		),
		array(
			'name'      => 'Tecnostar Ingeniería Electrónica - Tocancipá',
			'street'    => 'C.C. Milán Plaza, local 5-2',
			'locality'  => 'Tocancipá',
			'telephone' => '+57 3104719817',
			'abre'      => '10:00',
			'cierra'    => '19:00',
		),
	);
}

/** Objetos LocalBusiness de las sedes. Función pura (recibe la URL del sitio). */
function tecnostar_ld_local_business( string $url ): array {
	$salida = array();
	foreach ( tecnostar_ld_sedes() as $s ) {
		$salida[] = array(
			'@context'                  => 'https://schema.org',
			'@type'                     => 'LocalBusiness',
			'name'                      => $s['name'],
			'url'                       => $url,
			'telephone'                 => $s['telephone'],
			'address'                   => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $s['street'],
				'addressLocality' => $s['locality'],
				'addressCountry'  => 'CO',
			),
			'openingHoursSpecification' => array(
				array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ),
					'opens'     => $s['abre'],
					'closes'    => $s['cierra'],
				),
			),
		);
	}
	return $salida;
}

/** Garantía según Grado: A = 1 año, B = 6 meses, otro = sin garantía. Función pura. */
function tecnostar_ld_garantia( ?string $grado ): ?array {
	if ( 'A' === $grado ) {
		$meses = 12;
	} elseif ( 'B' === $grado ) {
		$meses = 6;
	} else {
		return null;
	}
	return array(
		'@type'              => 'WarrantyPromise',
		'durationOfWarranty' => array(
			'@type'    => 'QuantitativeValue',
			'value'    => $meses,
			'unitCode' => 'MON',
		),
	);
}

/** Lista BreadcrumbList a partir de pares [nombre, url]. Función pura. */
function tecnostar_ld_breadcrumb( array $items ): array {
	$lista = array();
	foreach ( array_values( $items ) as $i => $it ) {
		$lista[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $it[0],
			'item'     => $it[1],
		);
	}
	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $lista,
	);
}

/** Service de una página de servicio (ts_servicio). Función pura: recibe nombre, descripción, URL y URL del sitio. */
function tecnostar_ld_servicio( string $nombre, string $descripcion, string $url, string $sitio ): array {
	$datos = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Service',
		'name'        => $nombre,
		'url'         => $url,
		'provider'    => array(
			'@type' => 'LocalBusiness',
			'name'  => 'Tecnostar Ingeniería Electrónica',
			'url'   => $sitio,
		),
		'areaServed'  => array(
			'@type' => 'Country',
			'name'  => 'Colombia',
		),
	);
	if ( '' !== $descripcion ) {
		$datos['description'] = $descripcion;
	}
	return $datos;
}

/** Imprime un bloque JSON-LD. */
function tecnostar_ld_imprimir( array $datos ): void {
	echo '<script type="application/ld+json">' . wp_json_encode( $datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

/** Product de la ficha. */
function tecnostar_ld_producto( WC_Product $p ): array {
	$datos  = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Product',
		'name'     => wp_strip_all_tags( $p->get_name() ),
		'url'      => get_permalink( $p->get_id() ),
	);
	$imagen = wp_get_attachment_image_url( $p->get_image_id(), 'full' );
	if ( $imagen ) {
		$datos['image'] = $imagen;
	}
	if ( $p->get_sku() ) {
		$datos['sku'] = $p->get_sku();
	}
	$oferta = array(
		'@type'         => 'Offer',
		'priceCurrency' => 'COP',
		'availability'  => $p->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
		'url'           => get_permalink( $p->get_id() ),
	);
	if ( '' !== (string) $p->get_price() ) {
		$oferta['price'] = (string) wc_format_decimal( $p->get_price(), 0 );
	}
	$garantia = function_exists( 'tecnostar_grado' ) ? tecnostar_ld_garantia( tecnostar_grado( $p->get_id() ) ) : null;
	if ( $garantia ) {
		$oferta['warranty'] = $garantia;
	}
	$datos['offers'] = $oferta;
	return $datos;
}

/** Migas de pan de la vista actual, o array vacío si no aplica (portada). */
function tecnostar_ld_migas(): array {
	if ( is_front_page() ) {
		return array();
	}
	$migas  = array( array( 'Inicio', home_url( '/' ) ) );
	$tienda = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;

	if ( function_exists( 'is_product' ) && is_product() ) {
		if ( $tienda > 0 ) {
			$migas[] = array( get_the_title( $tienda ), get_permalink( $tienda ) );
		}
		$cats = get_the_terms( get_queried_object_id(), 'product_cat' );
		if ( $cats && ! is_wp_error( $cats ) ) {
			$cat = reset( $cats );
			foreach ( array_reverse( get_ancestors( $cat->term_id, 'product_cat', 'taxonomy' ) ) as $id ) {
				$t       = get_term( $id, 'product_cat' );
				$migas[] = array( $t->name, get_term_link( $t ) );
			}
			$migas[] = array( $cat->name, get_term_link( $cat ) );
		}
		$migas[] = array( get_the_title(), get_permalink() );
	} elseif ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		if ( $tienda > 0 ) {
			$migas[] = array( get_the_title( $tienda ), get_permalink( $tienda ) );
		}
		$t = get_queried_object();
		foreach ( array_reverse( get_ancestors( $t->term_id, $t->taxonomy, 'taxonomy' ) ) as $id ) {
			$a       = get_term( $id, $t->taxonomy );
			$migas[] = array( $a->name, get_term_link( $a ) );
		}
		$migas[] = array( $t->name, get_term_link( $t ) );
	} elseif ( is_singular() ) {
		foreach ( array_reverse( get_post_ancestors( get_queried_object_id() ) ) as $id ) {
			$migas[] = array( get_the_title( $id ), get_permalink( $id ) );
		}
		$migas[] = array( get_the_title(), get_permalink() );
	} else {
		return array();
	}
	return $migas;
}

add_action(
	'wp_head',
	function () {
		if ( is_page( 'nosotros' ) || is_front_page() ) {
			foreach ( tecnostar_ld_local_business( home_url( '/' ) ) as $sede ) {
				tecnostar_ld_imprimir( $sede );
			}
		}
		if ( function_exists( 'is_product' ) && is_product() ) {
			$p = wc_get_product( get_queried_object_id() );
			if ( $p ) {
				tecnostar_ld_imprimir( tecnostar_ld_producto( $p ) );
			}
		}
		if ( is_singular( 'ts_servicio' ) ) {
			$desc = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', get_queried_object_id() ) ), 40, '' );
			tecnostar_ld_imprimir( tecnostar_ld_servicio( get_the_title( get_queried_object_id() ), $desc, get_permalink( get_queried_object_id() ), home_url( '/' ) ) );
		}
		$migas = tecnostar_ld_migas();
		if ( count( $migas ) > 1 ) {
			tecnostar_ld_imprimir( tecnostar_ld_breadcrumb( $migas ) );
		}
	},
	5
);

/** Evita el JSON-LD de Product y de migas que WooCommerce imprime por su cuenta (quedaría duplicado). */
add_filter( 'woocommerce_structured_data_product', '__return_empty_array' );
add_action(
	'wp',
	function () {
		if ( function_exists( 'WC' ) && WC()->structured_data ) {
			remove_action( 'woocommerce_breadcrumb', array( WC()->structured_data, 'generate_breadcrumblist_data' ), 10 );
		}
	}
);

/** IDs de páginas que no deben estar en el sitemap. */
function tecnostar_sitemap_excluidas(): array {
	$ids = array();
	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $pagina ) {
			$id = (int) wc_get_page_id( $pagina );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
	}
	$acceso = get_page_by_path( 'acceso' );
	if ( $acceso ) {
		$ids[] = (int) $acceso->ID;
	}
	return $ids;
}

add_filter(
	'wp_sitemaps_posts_query_args',
	function ( $args, $tipo ) {
		if ( 'page' === $tipo ) {
			$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), tecnostar_sitemap_excluidas() );
		}
		return $args;
	},
	10,
	2
);

/** Solo páginas, servicios y productos; sin entradas ni adjuntos; solo categorías de producto; sin usuarios. */
add_filter(
	'wp_sitemaps_post_types',
	function ( $tipos ) {
		return array_intersect_key( $tipos, array_flip( array( 'page', 'ts_servicio', 'product' ) ) );
	}
);
add_filter(
	'wp_sitemaps_taxonomies',
	function ( $taxonomias ) {
		return array_intersect_key( $taxonomias, array_flip( array( 'product_cat' ) ) );
	}
);
add_filter(
	'wp_sitemaps_add_provider',
	function ( $proveedor, $nombre ) {
		return 'users' === $nombre ? false : $proveedor;
	},
	10,
	2
);
