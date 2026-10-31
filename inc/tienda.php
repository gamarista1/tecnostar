<?php
/**
 * Tienda: sellos de grado en la tarjeta de producto y en la ficha; buscador y filtros con URL propia.
 * El Grado sale del atributo global pa_grado (ver mu-plugins/tecnostar-nucleo/productos.php).
 * Sin Grado, no hay sello.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** HTML del sello del producto, o cadena vacía si no tiene Grado. El texto lleva el significado; el color solo refuerza. */
function tecnostar_sello_html( int $product_id, string $contexto = 'tarjeta' ): string {
	if ( ! function_exists( 'tecnostar_grado' ) ) {
		return '';
	}
	$grado = tecnostar_grado( $product_id );
	if ( 'A' === $grado ) {
		$clase = 'a';
		$texto = 'Grado A · Nuevo · Garantía 1 año';
	} elseif ( 'B' === $grado ) {
		$clase = 'b';
		$texto = 'Grado B · Usado certificado · Garantía 6 meses';
	} else {
		return '';
	}
	$icono = 'a' === $clase
		? '<svg class="ts-sello__ico" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><path d="M8 1.5l5.5 2v4c0 3.3-2.3 5.7-5.5 7-3.2-1.3-5.5-3.7-5.5-7v-4z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M5.5 8l1.8 1.8L10.7 6.4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
		: '<svg class="ts-sello__ico" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><path d="M8 1.5l5.5 2v4c0 3.3-2.3 5.7-5.5 7-3.2-1.3-5.5-3.7-5.5-7v-4z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M5.5 8h5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
	$html = sprintf(
		'<span class="ts-sello ts-sello--%1$s ts-sello--%2$s" data-sello="%1$s">%3$s<span class="ts-sello__txt">%4$s</span></span>',
		esc_attr( $clase ),
		esc_attr( $contexto ),
		$icono,
		esc_html( $texto )
	);
	if ( 'ficha' === $contexto && 'b' === $clase ) {
		$html .= '<p class="ts-sello-nota" data-sello-nota>Rendimiento 100%, solo detalles estéticos leves.</p>';
	}
	return $html;
}

// Tarjeta (listados, home, relacionados): justo después de la imagen.
add_action( 'woocommerce_before_shop_loop_item_title', function () {
	echo tecnostar_sello_html( get_the_ID(), 'tarjeta' ); // phpcs:ignore WordPress.Security.EscapeOutput
}, 11 );

// Ficha: bajo el título.
add_action( 'woocommerce_single_product_summary', function () {
	$html = tecnostar_sello_html( get_the_ID(), 'ficha' );
	if ( $html ) {
		echo '<div class="ts-sello-wrap">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}, 6 );

// Estilos solo en tienda, categorías, ficha y portada; el JS de filtros, solo en los listados;
// el de la ficha (tooltips; el video va en inc/video.php), solo en la ficha; el de la tarjeta "te lo conseguimos", solo en tienda y categorías.
add_action( 'wp_enqueue_scripts', function () {
	$js = get_template_directory_uri() . '/assets/js/';
	if ( is_front_page() || is_shop() || is_product_taxonomy() || is_product() || tecnostar_es_busqueda_productos() ) {
		wp_enqueue_style( 'tecnostar-tienda', get_template_directory_uri() . '/assets/css/tienda.css', array( 'tecnostar-components' ), tecnostar_asset_ver( '/assets/css/tienda.css' ) );
	}
	if ( tecnostar_es_listado_tienda() ) {
		wp_enqueue_script( 'tecnostar-tienda', $js . 'tienda.js', array(), tecnostar_asset_ver( '/assets/js/tienda.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
	if ( is_product() ) {
		wp_enqueue_script( 'tecnostar-ficha', $js . 'ficha.js', array(), tecnostar_asset_ver( '/assets/js/ficha.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
	if ( tecnostar_consigue_visible() ) {
		wp_enqueue_script( 'tecnostar-consigue', $js . 'consigue.js', array(), tecnostar_asset_ver( '/assets/js/consigue.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
}, 20 );

/* ------------------------------------------------------------------
 * Ficha de producto: sello sobre la foto, características (icono + valor
 * con tooltip), 4 fotos, video corto con carga al tocar y checklist.
 * El carrito, el checkout y el formulario de compra no cambian: solo el texto del botón.
 * ------------------------------------------------------------------ */

if ( ! defined( 'TECNOSTAR_META_VIDEO' ) ) {
	define( 'TECNOSTAR_META_VIDEO', '_ts_video' ); // Normalmente lo define mu-plugins/tecnostar-nucleo/productos.php.
}

/** Video de ejemplo mientras no haya videos en R2 (CC0, MDN). Se registra como pendiente. */
const TECNOSTAR_VIDEO_EJEMPLO = 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4';

/** Columna de la foto: abre un bloque propio con el sello encima de la foto principal. */
add_action( 'woocommerce_before_single_product_summary', function () {
	echo '<div class="ts-pdp-media" data-ts-media>';
	$sello = tecnostar_sello_html( get_the_ID(), 'foto' );
	if ( $sello ) {
		// El sello de la columna de información ya lo lee el lector de pantalla; este es visual.
		echo '<div class="ts-pdp-media__sello" aria-hidden="true">' . $sello . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}, 19 );

/** Bajo la foto: características, fotos que faltan (ejemplo marcado), video y cierre del bloque. */
add_action( 'woocommerce_before_single_product_summary', function () {
	$id = get_the_ID();
	get_template_part( 'template-parts/producto-meta', null, array( 'product_id' => $id ) );
	if ( function_exists( 'tecnostar_grado' ) && tecnostar_grado( $id ) ) {
		echo tecnostar_fotos_faltantes_html( $id ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo tecnostar_video_html( $id ); // phpcs:ignore WordPress.Security.EscapeOutput
	} elseif ( get_post_meta( $id, TECNOSTAR_META_VIDEO, true ) ) {
		echo tecnostar_video_html( $id ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>';
}, 21 );

/** Número de fotos del producto (principal + galería). */
function tecnostar_fotos_total( int $product_id ): int {
	$producto = wc_get_product( $product_id );
	if ( ! $producto ) {
		return 0;
	}
	return ( $producto->get_image_id() ? 1 : 0 ) + count( array_filter( $producto->get_gallery_image_ids() ) );
}

/**
 * Si el producto tiene menos de 4 fotos, completa con espacios marcados como ejemplo (no se inventan fotos).
 * Solo en productos con Grado (el catálogo nuevo); el pendiente queda en Herramientas > Pendientes.
 */
function tecnostar_fotos_faltantes_html( int $product_id ): string {
	$faltan = 4 - tecnostar_fotos_total( $product_id );
	if ( $faltan <= 0 || ! function_exists( 'tecnostar_ejemplo' ) ) {
		return '';
	}
	tecnostar_pendiente( 'ficha-fotos', 'Cada producto necesita 4 fotos reales (frente, teclado, laterales y puertos).', 'Ficha de producto > Galería' );
	$html = '';
	for ( $i = 0; $i < $faltan; $i++ ) {
		$html .= '<li class="ts-fotos-faltan__item" data-foto-ejemplo><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="M21 16l-5-5-8 8"/></svg><span>Foto por cargar</span></li>';
	}
	return tecnostar_ejemplo( 'ficha-fotos', '<ul class="ts-fotos-faltan" aria-label="Fotos por cargar">' . $html . '</ul>' );
}

/**
 * Video corto con carga al tocar (template-parts/video.php + assets/js/video.js): el <video> no tiene src hasta
 * que el cliente pulsa el botón; hasta entonces solo se ve el póster (la foto principal, ya descargada).
 * _ts_video puede ser una clave de R2 (URL prefirmada al tocar) o una URL https completa.
 */
function tecnostar_video_html( int $product_id ): string {
	$url     = (string) get_post_meta( $product_id, TECNOSTAR_META_VIDEO, true );
	$ejemplo = '' === $url;
	if ( $ejemplo ) {
		if ( ! function_exists( 'tecnostar_ejemplo' ) ) {
			return '';
		}
		$url = TECNOSTAR_VIDEO_EJEMPLO;
		tecnostar_pendiente( 'ficha-video', 'Video de 15 s por producto (equipo encendido y conectado), alojado en R2. Hoy se muestra un video de ejemplo.', 'Ficha de producto > Video' );
	}
	$html = tecnostar_video_reproductor(
		array(
			'fuente'   => $url, // Clave de R2 (videos/xxx.mp4) o URL https.
			'poster'   => (string) wp_get_attachment_image_url( (int) get_post_thumbnail_id( $product_id ), 'woocommerce_single' ),
			'modo'     => 'video',
			'etiqueta' => 'Video de prueba del equipo',
			'boton'    => 'Ver video de prueba',
			'duracion' => '15 s',
			'pie'      => 'Así lo probamos: encendido y conectado.',
			'pronto'   => 'Muy pronto: video del equipo encendido y conectado.',
			'wa'       => 'el video de prueba de este equipo',
		)
	);
	return $ejemplo ? tecnostar_ejemplo( 'ficha-video', $html ) : $html;
}

/** Checklist del producto (cargador, batería, factura, garantía): solo lo marcado en el producto. */
add_action( 'woocommerce_single_product_summary', function () {
	if ( ! function_exists( 'tecnostar_campos_casillas' ) ) {
		return;
	}
	$id    = get_the_ID();
	$items = '';
	foreach ( tecnostar_campos_casillas() as $meta => $etiqueta ) {
		if ( 'yes' === get_post_meta( $id, $meta, true ) ) {
			$items .= '<li data-chk="' . esc_attr( $meta ) . '"><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M3.5 8.5l3 3 6-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' . esc_html( $etiqueta ) . '</li>';
		}
	}
	if ( $items ) {
		echo '<div class="ts-checklist" data-ts-checklist><p class="ts-checklist__t">Lo que recibes</p><ul>' . $items . '</ul></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}, 25 );

/** Botón de compra de la ficha: mismo formulario y mismo añadir al carrito; solo cambia el texto. */
add_filter( 'woocommerce_product_single_add_to_cart_text', function () {
	return 'Ver precio y comprar';
} );

/* ------------------------------------------------------------------
 * Tarjeta "te lo conseguimos": solo en la tienda y en categorías de producto.
 * Aparece a los 15 s o al 50 % de scroll (assets/js/consigue.js).
 * ------------------------------------------------------------------ */

function tecnostar_consigue_visible(): bool {
	if ( is_admin() || ! ( is_shop() || is_product_category() ) ) {
		return false;
	}
	return ! ( is_cart() || is_checkout() || is_front_page() );
}

add_action( 'wp_footer', function () {
	if ( ! tecnostar_consigue_visible() ) {
		return;
	}
	?>
	<aside class="ts-consigue" data-ts-consigue aria-labelledby="ts-consigue-t" hidden>
		<p class="ts-consigue__t" id="ts-consigue-t">¿No encuentras lo que buscas?</p>
		<p class="ts-consigue__s">Si no lo tenemos, te lo conseguimos.</p>
		<button type="button" class="ts-consigue__btn" data-wa-abrir="te-lo-conseguimos">Escríbenos y te lo cotizamos</button>
		<button type="button" class="ts-consigue__x" data-ts-consigue-cerrar aria-label="Cerrar aviso"><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
	</aside>
	<?php
}, 5 );

/* ------------------------------------------------------------------
 * Buscador y filtros con URL propia: marca, grado, uso y presupuesto.
 * Cada combinación es una URL GET normal (/tienda/?marca=dell&grado=b);
 * assets/js/tienda.js la aplica sin recargar pidiendo esa misma URL.
 * ------------------------------------------------------------------ */

/** Parámetro de la URL => taxonomía del atributo global. */
function tecnostar_filtros_taxonomias(): array {
	return array(
		'marca' => 'pa_marca',
		'grado' => 'pa_grado',
		'uso'   => 'pa_uso',
	);
}

/** Rangos de presupuesto iniciales, calculados con los quintiles de precio del catálogo del staging (31 oct 2026). */
function tecnostar_rangos_presupuesto_iniciales(): array {
	return array(
		array( 'slug' => 'menos-60000', 'etiqueta' => 'Menos de $60.000', 'min' => 0, 'max' => 60000 ),
		array( 'slug' => '60000-100000', 'etiqueta' => '$60.000 a $100.000', 'min' => 60000, 'max' => 100000 ),
		array( 'slug' => '100000-170000', 'etiqueta' => '$100.000 a $170.000', 'min' => 100000, 'max' => 170000 ),
		array( 'slug' => '170000-300000', 'etiqueta' => '$170.000 a $300.000', 'min' => 170000, 'max' => 300000 ),
		array( 'slug' => 'desde-300000', 'etiqueta' => '$300.000 o más', 'min' => 300000, 'max' => 0 ),
	);
}

/**
 * Rangos de presupuesto (opción tecnostar_rangos_presupuesto). Cada rango: slug, etiqueta, min (incluido) y max (excluido; 0 = sin tope).
 * Si la opción no existe, se crea con los valores iniciales.
 */
function tecnostar_rangos_presupuesto(): array {
	$rangos = get_option( 'tecnostar_rangos_presupuesto' );
	if ( false === $rangos ) {
		$rangos = tecnostar_rangos_presupuesto_iniciales();
		add_option( 'tecnostar_rangos_presupuesto', $rangos, '', true );
	}
	$limpios = array();
	foreach ( (array) $rangos as $r ) {
		if ( is_array( $r ) && ! empty( $r['slug'] ) && isset( $r['etiqueta'], $r['min'], $r['max'] ) ) {
			$limpios[ sanitize_title( $r['slug'] ) ] = array(
				'etiqueta' => (string) $r['etiqueta'],
				'min'      => (float) $r['min'],
				'max'      => (float) $r['max'],
			);
		}
	}
	return $limpios;
}

/** Búsqueda restringida a productos (?s=...&post_type=product). */
function tecnostar_es_busqueda_productos( $q = null ): bool {
	$q = $q ? $q : $GLOBALS['wp_query'];
	return $q && $q->is_search() && 'product' === $q->get( 'post_type' );
}

/** Listado de productos: tienda, categorías/etiquetas/atributos o búsqueda de productos. */
function tecnostar_es_listado_tienda( $q = null ): bool {
	$q = $q ? $q : $GLOBALS['wp_query'];
	if ( ! $q || $q->is_singular() ) {
		return false;
	}
	return $q->is_post_type_archive( 'product' ) || $q->is_tax( get_object_taxonomies( 'product' ) ) || tecnostar_es_busqueda_productos( $q );
}

/** Filtros pedidos en la URL (parámetro => slug saneado). El presupuesto solo cuenta si el rango existe. */
function tecnostar_filtros_activos(): array {
	static $activos = null;
	if ( null !== $activos ) {
		return $activos;
	}
	$activos = array();
	foreach ( array( 'marca', 'grado', 'uso', 'presupuesto' ) as $param ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filtros públicos de solo lectura.
		if ( isset( $_GET[ $param ] ) && is_string( $_GET[ $param ] ) ) {
			$valor = sanitize_title( wp_unslash( $_GET[ $param ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( '' !== $valor ) {
				$activos[ $param ] = $valor;
			}
		}
	}
	if ( isset( $activos['presupuesto'] ) && ! isset( tecnostar_rangos_presupuesto()[ $activos['presupuesto'] ] ) ) {
		unset( $activos['presupuesto'] );
	}
	return $activos;
}

/** Aplica los filtros a la consulta principal del listado (después de WooCommerce, que actúa en la prioridad 10). */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! tecnostar_es_listado_tienda( $q ) ) {
		return;
	}
	$filtros = tecnostar_filtros_activos();
	if ( ! $filtros ) {
		return;
	}
	$tax = $q->get( 'tax_query' );
	$tax = is_array( $tax ) ? $tax : array();
	foreach ( tecnostar_filtros_taxonomias() as $param => $taxonomia ) {
		if ( isset( $filtros[ $param ] ) ) {
			$tax[] = array(
				'taxonomy' => $taxonomia,
				'field'    => 'slug',
				'terms'    => array( $filtros[ $param ] ),
			);
		}
	}
	if ( count( $tax ) > 1 ) {
		$tax['relation'] = 'AND';
	}
	$q->set( 'tax_query', $tax );

	if ( isset( $filtros['presupuesto'] ) ) {
		$rango = tecnostar_rangos_presupuesto()[ $filtros['presupuesto'] ];
		$meta  = $q->get( 'meta_query' );
		$meta  = is_array( $meta ) ? $meta : array();
		$meta[] = array( 'key' => '_price', 'value' => $rango['min'], 'compare' => '>=', 'type' => 'DECIMAL(12,2)' );
		if ( $rango['max'] > 0 ) {
			$meta[] = array( 'key' => '_price', 'value' => $rango['max'], 'compare' => '<', 'type' => 'DECIMAL(12,2)' );
		}
		$q->set( 'meta_query', $meta );
	}
}, 20 );

/** URL base del listado actual, sin parámetros ni paginación. */
function tecnostar_tienda_base_url(): string {
	if ( is_product_taxonomy() ) {
		$enlace = get_term_link( get_queried_object() );
		if ( ! is_wp_error( $enlace ) ) {
			return (string) $enlace;
		}
	}
	if ( tecnostar_es_busqueda_productos() ) {
		return home_url( '/' );
	}
	return (string) get_permalink( wc_get_page_id( 'shop' ) );
}

/**
 * URL del listado con los parámetros actuales más $cambios (null quita un parámetro). Orden fijo: s y post_type primero.
 */
function tecnostar_filtro_url( array $cambios = array() ): string {
	$args = array();
	if ( tecnostar_es_busqueda_productos() ) {
		$args['s']         = get_search_query( false );
		$args['post_type'] = 'product';
	}
	$args = array_merge( $args, tecnostar_filtros_activos() );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['orderby'] ) && is_string( $_GET['orderby'] ) ) {
		$args['orderby'] = sanitize_key( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	foreach ( $cambios as $clave => $valor ) {
		$args[ $clave ] = $valor;
	}
	$orden = array( 's', 'post_type', 'marca', 'grado', 'uso', 'presupuesto', 'orderby' );
	$final = array();
	foreach ( $orden as $clave ) {
		if ( isset( $args[ $clave ] ) && '' !== (string) $args[ $clave ] ) {
			$final[ $clave ] = rawurlencode( (string) $args[ $clave ] );
		}
	}
	return add_query_arg( $final, tecnostar_tienda_base_url() );
}

/** Opciones de cada grupo de filtro: parámetro => [titulo, opciones slug => etiqueta]. */
function tecnostar_filtros_grupos(): array {
	$grupos = array();
	$titulos = array(
		'marca' => 'Marca',
		'grado' => 'Grado',
	);
	foreach ( $titulos as $param => $titulo ) {
		$grupos[ $param ] = array( 'titulo' => $titulo, 'opciones' => tecnostar_filtro_terminos( tecnostar_filtros_taxonomias()[ $param ] ) );
	}
	$presupuesto = array();
	foreach ( tecnostar_rangos_presupuesto() as $slug => $r ) {
		$presupuesto[ $slug ] = $r['etiqueta'];
	}
	$grupos['presupuesto'] = array( 'titulo' => 'Presupuesto', 'opciones' => $presupuesto );
	$grupos['uso']         = array( 'titulo' => 'Uso', 'opciones' => tecnostar_filtro_terminos( 'pa_uso' ) );
	return array_filter( $grupos, function ( $g ) {
		return ! empty( $g['opciones'] );
	} );
}

/** Términos con productos de un atributo (slug => nombre), en el orden definido en WooCommerce. Sin productos no se ofrecen: evita callejones sin salida. */
function tecnostar_filtro_terminos( string $taxonomia ): array {
	if ( ! taxonomy_exists( $taxonomia ) ) {
		return array();
	}
	$terminos = get_terms( array( 'taxonomy' => $taxonomia, 'hide_empty' => true, 'orderby' => 'menu_order' ) );
	if ( is_wp_error( $terminos ) ) {
		return array();
	}
	$salida = array();
	foreach ( $terminos as $t ) {
		$salida[ $t->slug ] = $t->name;
	}
	return $salida;
}

/** Abre el bloque de la tienda (buscador, filtros y resultados) tras la descripción del archivo. */
add_action( 'woocommerce_archive_description', function () {
	if ( ! tecnostar_es_listado_tienda() ) {
		return;
	}
	$GLOBALS['tecnostar_tienda_abierta'] = true;
	echo '<div class="ts-tienda" data-ts-tienda>';
	get_template_part( 'template-parts/tienda-filtros' );
	echo '<div id="ts-resultados" class="ts-resultados" tabindex="-1" data-ts-resultados>';
}, 50 );

/** Cierra el bloque abierto (con o sin productos). */
function tecnostar_tienda_cerrar() {
	if ( empty( $GLOBALS['tecnostar_tienda_abierta'] ) ) {
		return;
	}
	$GLOBALS['tecnostar_tienda_abierta'] = false;
	echo '</div></div><p class="screen-reader-text" role="status" aria-live="polite" data-ts-estado></p>';
}
add_action( 'woocommerce_after_shop_loop', 'tecnostar_tienda_cerrar', 99 );
add_action( 'woocommerce_no_products_found', 'tecnostar_tienda_cerrar', 99 );

/** Sin resultados con filtros o búsqueda: mensaje claro y enlace para limpiar. */
add_action( 'woocommerce_no_products_found', function () {
	if ( ! tecnostar_es_listado_tienda() ) {
		return;
	}
	$filtros = tecnostar_filtros_activos();
	$busca   = tecnostar_es_busqueda_productos() ? get_search_query( false ) : '';
	if ( ! $filtros && '' === $busca ) {
		return;
	}
	remove_action( 'woocommerce_no_products_found', 'wc_no_products_found' );
	if ( $filtros ) {
		$texto  = '' !== $busca
			? sprintf( 'No encontramos productos para «%s» con esos filtros.', $busca )
			: 'No encontramos productos con esos filtros.';
		$enlace = tecnostar_filtro_url( array_fill_keys( array_keys( $filtros ), null ) );
		$accion = 'Quitar filtros';
	} else {
		$texto  = sprintf( 'No encontramos productos para «%s».', $busca );
		$enlace = (string) get_permalink( wc_get_page_id( 'shop' ) );
		$accion = 'Ver toda la tienda';
	}
	printf(
		'<div class="ts-vacio" data-ts-vacio><p class="ts-vacio__t">%1$s</p><p class="ts-vacio__s">Prueba con otra combinación o escríbenos: si no lo tenemos, te lo conseguimos.</p><a class="ts-vacio__a" href="%2$s" data-ts-filtro data-ts-clave="limpiar">%3$s</a></div>',
		esc_html( $texto ),
		esc_url( $enlace ),
		esc_html( $accion )
	);
}, 5 );
