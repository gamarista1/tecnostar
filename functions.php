<?php
/**
 * Tema tecnostar: configuración base.
 */

defined( 'ABSPATH' ) || exit;

define( 'TECNOSTAR_VERSION', '0.4.0' );

require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/woocommerce.php';
require_once get_template_directory() . '/inc/tienda.php';
require_once get_template_directory() . '/inc/seo.php';
require_once get_template_directory() . '/inc/checkout.php';
require_once get_template_directory() . '/inc/minicart.php';
require_once get_template_directory() . '/inc/cart.php';
require_once get_template_directory() . '/inc/envio.php';
require_once get_template_directory() . '/inc/envio-bogota.php';
require_once get_template_directory() . '/inc/llms-txt.php';
require_once get_template_directory() . '/inc/cuenta.php';
require_once get_template_directory() . '/inc/rendimiento.php';
require_once get_template_directory() . '/inc/persona-natural.php';
require_once get_template_directory() . '/inc/nosotros.php';

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'tecnostar', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	register_nav_menus(
		array(
			'primary' => __( 'Menú principal', 'tecnostar' ),
			'footer'  => __( 'Menú del pie', 'tecnostar' ),
		)
	);
} );

/**
 * Versión de un recurso del tema = fecha de modificación del archivo, para que
 * navegadores y cachés lo recarguen en cuanto cambie.
 */
function tecnostar_asset_ver( $relative_path ) {
	$file = get_template_directory() . $relative_path;
	return file_exists( $file ) ? (string) filemtime( $file ) : TECNOSTAR_VERSION;
}

/** El botón de WhatsApp aparece en todo el sitio menos en carrito y checkout, y solo si hay canales. */
function tecnostar_wa_visible() {
	if ( ! function_exists( 'tecnostar_canales_whatsapp' ) || ! tecnostar_canales_whatsapp() ) {
		return false;
	}
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
		return false;
	}
	return true;
}

add_action( 'wp_enqueue_scripts', function () {
	$css = get_template_directory_uri() . '/assets/css/';
	wp_enqueue_style( 'tecnostar-tokens', $css . 'tokens.css', array(), tecnostar_asset_ver( '/assets/css/tokens.css' ) );
	wp_enqueue_style( 'tecnostar-base', $css . 'base.css', array( 'tecnostar-tokens' ), tecnostar_asset_ver( '/assets/css/base.css' ) );
	wp_enqueue_style( 'tecnostar-components', $css . 'components.css', array( 'tecnostar-base' ), tecnostar_asset_ver( '/assets/css/components.css' ) );
	wp_enqueue_script( 'tecnostar-motion', get_template_directory_uri() . '/assets/js/motion.js', array(), tecnostar_asset_ver( '/assets/js/motion.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );

	// Widget Mi cuenta (cabecera): CSS y JS mínimos; el contenido de la tarjeta llega por AJAX al abrirla.
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		wp_enqueue_style( 'tecnostar-cuenta', $css . 'cuenta.css', array( 'tecnostar-components' ), tecnostar_asset_ver( '/assets/css/cuenta.css' ) );
		wp_enqueue_script( 'tecnostar-cuenta', get_template_directory_uri() . '/assets/js/cuenta.js', array(), tecnostar_asset_ver( '/assets/js/cuenta.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'tecnostar-cuenta', 'window.tsCuenta=' . wp_json_encode( array( 'ajax' => admin_url( 'admin-ajax.php' ) ) ) . ';', 'before' );
	}

	// Menú radial de WhatsApp (no se carga en carrito ni checkout).
	if ( tecnostar_wa_visible() ) {
		wp_enqueue_style( 'tecnostar-whatsapp', $css . 'whatsapp.css', array( 'tecnostar-tokens' ), tecnostar_asset_ver( '/assets/css/whatsapp.css' ) );
		wp_enqueue_script( 'tecnostar-whatsapp', get_template_directory_uri() . '/assets/js/whatsapp.js', array(), tecnostar_asset_ver( '/assets/js/whatsapp.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

	// Portada: banner motion (estilos y cargador de islas; GSAP se descarga solo al acercarse el banner).
	if ( is_front_page() ) {
		wp_enqueue_style( 'tecnostar-banner-motion', $css . 'banner-motion.css', array( 'tecnostar-components' ), tecnostar_asset_ver( '/assets/css/banner-motion.css' ) );
		wp_enqueue_style( 'tecnostar-home', $css . 'home.css', array( 'tecnostar-components' ), tecnostar_asset_ver( '/assets/css/home.css' ) );
		wp_enqueue_script( 'tecnostar-islas', get_template_directory_uri() . '/assets/js/islas/cargador.js', array(), tecnostar_asset_ver( '/assets/js/islas/cargador.js' ), array( 'in_footer' => true ) );
	}

	// Acceso de clientes: estilos y volteo solo en esa pantalla y solo para visitantes.
	if ( function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in() ) {
		wp_enqueue_style( 'tecnostar-acceso', $css . 'acceso.css', array( 'tecnostar-components' ), tecnostar_asset_ver( '/assets/css/acceso.css' ) );
		wp_enqueue_script( 'tecnostar-acceso', get_template_directory_uri() . '/assets/js/acceso.js', array(), tecnostar_asset_ver( '/assets/js/acceso.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
} );

// Precarga de la tipografía para evitar el salto de texto.
add_action( 'wp_head', function () {
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( get_template_directory_uri() . '/assets/fonts/outfit-latin.woff2' )
	);
}, 1 );

/**
 * Contador del carrito en la cabecera (se actualiza solo vía fragmentos de WooCommerce).
 */
function tecnostar_cart_count_html() {
	$count = function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
	return sprintf( '<span class="cart-count" data-count="%1$d">%2$s</span>', $count, $count ? esc_html( $count ) : '' );
}

add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$fragments['span.cart-count'] = tecnostar_cart_count_html();
	return $fragments;
} );

/**
 * Menú de respaldo: si el equipo aún no definió "Menú principal", se muestran las categorías.
 */
function tecnostar_fallback_nav() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'number'     => 8,
			'exclude'    => (int) get_option( 'default_product_cat' ),
		)
	);
	if ( is_wp_error( $terms ) || ! $terms ) {
		return;
	}
	echo '<ul class="nav">';
	foreach ( $terms as $term ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( get_term_link( $term ) ), esc_html( $term->name ) );
	}
	echo '</ul>';
}

// El cargador de islas es un módulo ES (importa su código bajo demanda).
add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( 'tecnostar-islas' === $handle ) {
		$tag = str_replace( '<script ', '<script type="module" ', $tag );
	}
	return $tag;
}, 10, 2 );
