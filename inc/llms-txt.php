<?php
/**
 * /llms.txt dinámico: resumen del sitio para asistentes de IA.
 * Antes vivía como snippet #5 en Code Snippets.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		if ( '/llms.txt' !== untrailingslashit( (string) $path ) ) {
			return;
		}

		$lines   = array();
		$lines[] = '# ' . get_bloginfo( 'name' );
		$lines[] = '';
		$lines[] = '> Tienda en línea de computación y electrónica en Colombia: laptops, equipos gamer, componentes, accesorios y servicio técnico especializado.';
		$lines[] = '';
		$lines[] = '## Sitio';
		$lines[] = '- [Inicio](' . home_url( '/' ) . ')';

		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$lines[] = '- [Tienda](' . wc_get_page_permalink( 'shop' ) . '): catálogo completo de productos';
			$lines[] = '- [Mi cuenta](' . wc_get_page_permalink( 'myaccount' ) . ')';
		}

		$terms = taxonomy_exists( 'product_cat' ) ? get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => 40,
				'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			)
		) : array();

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$lines[] = '';
			$lines[] = '## Categorías de productos';
			foreach ( $terms as $term ) {
				$lines[] = '- [' . $term->name . '](' . get_term_link( $term ) . ')';
			}
		}

		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Cache-Control: public, max-age=3600' );
		echo implode( "\n", $lines ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	},
	20
);
