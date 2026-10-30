<?php
/**
 * Tema tecnostar: configuración base.
 */

defined( 'ABSPATH' ) || exit;

define( 'TECNOSTAR_VERSION', '0.3.0' );

require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/woocommerce.php';

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

add_action( 'wp_enqueue_scripts', function () {
	$css = get_template_directory_uri() . '/assets/css/';
	wp_enqueue_style( 'tecnostar-tokens', $css . 'tokens.css', array(), TECNOSTAR_VERSION );
	wp_enqueue_style( 'tecnostar-base', $css . 'base.css', array( 'tecnostar-tokens' ), TECNOSTAR_VERSION );
	wp_enqueue_style( 'tecnostar-components', $css . 'components.css', array( 'tecnostar-base' ), TECNOSTAR_VERSION );
	wp_enqueue_script( 'tecnostar-motion', get_template_directory_uri() . '/assets/js/motion.js', array(), TECNOSTAR_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
} );

// Precarga de la tipografía para evitar el salto de texto.
add_action( 'wp_head', function () {
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( get_template_directory_uri() . '/assets/fonts/outfit-latin.woff2' )
	);
}, 1 );

/**
 * Menú de respaldo: si el equipo aún no definió "Menú principal", se muestran las categorías.
 */
function tecnostar_fallback_nav() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'number'     => 6,
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
