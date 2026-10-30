<?php
/**
 * Tema tecnostar: configuración base.
 */

defined( 'ABSPATH' ) || exit;

define( 'TECNOSTAR_VERSION', '0.1.0' );

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
	$uri = get_template_directory_uri() . '/assets/css/';
	wp_enqueue_style( 'tecnostar-tokens', $uri . 'tokens.css', array(), TECNOSTAR_VERSION );
	wp_enqueue_style( 'tecnostar-base', $uri . 'base.css', array( 'tecnostar-tokens' ), TECNOSTAR_VERSION );
} );
