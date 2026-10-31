<?php
/**
 * Rendimiento del front: quita lo que no se usa y aplaza lo que no hace falta al cargar.
 * Cada bloque es independiente; para revertir uno basta con borrarlo.
 */

defined( 'ABSPATH' ) || exit;

/*
 * 1. Emojis de WordPress: el detector crea un worker y un canvas en cada página (~0,5 s de hilo
 *    principal en móvil). Los navegadores actuales ya pintan los emojis por su cuenta.
 */
add_action( 'init', function () {
	// Según la versión, WordPress lo engancha en la cabecera o en el pie y con prioridades distintas.
	foreach ( array( 'wp_head', 'wp_footer', 'wp_print_footer_scripts' ) as $gancho ) {
		foreach ( array( 'print_emoji_detection_script', 'wp_print_emoji_detection_script' ) as $funcion ) {
			$prioridad = has_action( $gancho, $funcion );
			if ( false !== $prioridad ) {
				remove_action( $gancho, $funcion, $prioridad );
			}
		}
	}
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
} );

/*
 * 2. Atribución de pedidos de WooCommerce (sourcebuster + order-attribution, ~0,4 s de hilo principal
 *    en móvil): fuera de carrito, checkout y cuenta se descarga tras la primera interacción o 8 s
 *    después de cargar. Lee la URL y el referente de la página actual, así que sigue registrando el
 *    origen de la visita. En carrito, checkout y cuenta (donde rellena campos del formulario) no cambia.
 */
function tecnostar_atribucion_diferida() {
	if ( is_admin() || ! function_exists( 'is_checkout' ) ) {
		return false;
	}
	return ! ( is_cart() || is_checkout() || is_account_page() );
}

add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( in_array( $handle, array( 'sourcebuster-js', 'wc-order-attribution' ), true ) && tecnostar_atribucion_diferida() ) {
		$tag = preg_replace( '/(<script[^>]*\bid=["\']' . preg_quote( $handle, '/' ) . '-js["\'][^>]*?)\ssrc=/', '$1 data-ts-src=', $tag );
	}
	return $tag;
}, 10, 2 );

add_action( 'wp_footer', function () {
	if ( ! tecnostar_atribucion_diferida() ) {
		return;
	}
	?>
<script id="tecnostar-diferidos">
(function () {
	var ev = ['pointerdown', 'keydown', 'touchstart', 'scroll'], hecho = false;
	function cargar() {
		if (hecho) return;
		hecho = true;
		ev.forEach(function (e) { removeEventListener(e, cargar, { passive: true }); });
		var lista = [].slice.call(document.querySelectorAll('script[data-ts-src]'));
		(function siguiente() {
			var s = lista.shift();
			if (!s) return;
			var n = document.createElement('script');
			n.src = s.getAttribute('data-ts-src');
			n.onload = n.onerror = siguiente;
			s.parentNode.insertBefore(n, s.nextSibling);
		})();
	}
	ev.forEach(function (e) { addEventListener(e, cargar, { passive: true }); });
	addEventListener('load', function () { setTimeout(cargar, 8000); });
})();
</script>
	<?php
}, 100 );
