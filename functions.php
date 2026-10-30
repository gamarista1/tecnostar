<?php
/**
 * Tema tecnostar: configuración base.
 */

defined( 'ABSPATH' ) || exit;

define( 'TECNOSTAR_VERSION', '0.4.0' );

require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/woocommerce.php';
require_once get_template_directory() . '/inc/seo.php';
require_once get_template_directory() . '/inc/llms-txt.php';

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

add_action( 'wp_enqueue_scripts', function () {
	$css = get_template_directory_uri() . '/assets/css/';
	wp_enqueue_style( 'tecnostar-tokens', $css . 'tokens.css', array(), tecnostar_asset_ver( '/assets/css/tokens.css' ) );
	wp_enqueue_style( 'tecnostar-base', $css . 'base.css', array( 'tecnostar-tokens' ), tecnostar_asset_ver( '/assets/css/base.css' ) );
	wp_enqueue_style( 'tecnostar-components', $css . 'components.css', array( 'tecnostar-base' ), tecnostar_asset_ver( '/assets/css/components.css' ) );
	wp_enqueue_script( 'tecnostar-motion', get_template_directory_uri() . '/assets/js/motion.js', array(), tecnostar_asset_ver( '/assets/js/motion.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
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
