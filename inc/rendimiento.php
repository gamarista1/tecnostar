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

/*
 * 3. Portada: jQuery al pie. En la home ningún script de la cabecera ni del contenido lo usa, así que
 *    deja de bloquear el primer pintado. En el resto de páginas no se toca.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_front_page() ) {
		return;
	}
	// Un script de cabecera que dependa de jQuery lo devolvería a la cabecera: se bajan también (ya iban con defer).
	$ws       = wp_scripts();
	$usa_jq   = function ( $h, $visto = array() ) use ( &$usa_jq, $ws ) {
		if ( in_array( $h, array( 'jquery', 'jquery-core', 'jquery-migrate' ), true ) ) {
			return true;
		}
		if ( isset( $visto[ $h ] ) || empty( $ws->registered[ $h ] ) ) {
			return false;
		}
		$visto[ $h ] = true;
		foreach ( (array) $ws->registered[ $h ]->deps as $d ) {
			if ( $usa_jq( $d, $visto ) ) {
				return true;
			}
		}
		return false;
	};
	foreach ( array_keys( $ws->registered ) as $h ) {
		if ( $usa_jq( $h ) ) {
			$ws->add_data( $h, 'group', 1 );
		}
	}
	foreach ( array( 'jquery', 'jquery-core', 'jquery-migrate' ) as $h ) {
		$ws->add_data( $h, 'group', 1 );
	}
}, 100 );

/*
 * 4. Portada: banner de Addi. Su script de arranque (S3) se imprime sin defer en el cuerpo y detiene el
 *    análisis del HTML 1-2,5 s mientras se descarga; después encadena 3 scripts de cdn.addi.com y al
 *    final la imagen, que es el LCP real de la home. Al diferirlo, el HTML no espera y la imagen del banner
 *    ya no compite con el LCP propio de la página.
 *    Carga diferida: el script se inserta tras el evento load + 3,5 s, o antes si el visitante interactúa.
 *    Solo en la home: en producto y checkout el widget de Addi queda como lo entrega el plugin.
 */
add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( 'widget-addi' !== $handle || ! is_front_page() || false !== strpos( $tag, 'data-addi-diferido' ) ) {
		return $tag;
	}
	// Prioridad alta: el plugin añade sus data-* en otro filtro. El script los lee de su propia etiqueta al ejecutarse.
	if ( ! preg_match( '/\bsrc=["\']([^"\']+)["\']/', $tag, $src ) ) {
		return $tag;
	}
	preg_match_all( '/\s(data-[a-z-]+)=(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $tag, $m, PREG_SET_ORDER );
	$attrs = array();
	foreach ( $m as $a ) {
		$attrs[ $a[1] ] = isset( $a[4] ) ? $a[4] : ( isset( $a[3] ) && '' !== $a[3] ? $a[3] : $a[2] );
	}
	$cfg = wp_json_encode( array( 'src' => $src[1], 'attrs' => $attrs ) );
	return '<script data-addi-diferido>(function(c){var n=0;function ir(){if(n++)return;["pointerdown","keydown","scroll","touchstart"].forEach(function(e){removeEventListener(e,ir)});var s=document.createElement("script");s.id="widget-addi-js";s.src=c.src;for(var k in c.attrs)s.setAttribute(k,c.attrs[k]);document.body.appendChild(s);var sl=document.querySelector(".hc__slide--addi");if(sl){var alt=function(b){var k=0,t=setInterval(function(){var r=b.shadowRoot;if(r){var l=r.querySelector("img.addi-home-banner-02-content-container__logo");if(l&&!l.hasAttribute("alt"))l.alt="Addi";var c=r.querySelector("img.addi-close-button");if(c&&!c.hasAttribute("alt"))c.alt="Cerrar"}if(++k>20)clearInterval(t)},300)};var f=function(){var b=sl.querySelector("addi-home-banner.hydrated");if(b){alt(b);setTimeout(function(){sl.classList.add("is-listo")},350);o.disconnect()}};var o=new MutationObserver(f);o.observe(sl,{childList:true,subtree:true,attributes:true,attributeFilter:["class"]});f()}}'
		. '["pointerdown","keydown","scroll","touchstart"].forEach(function(e){addEventListener(e,ir,{once:true,passive:true})});'
		. 'addEventListener("load",function(){setTimeout(ir,3500)})})(' . $cfg . ')</script>' . "\n";
}, PHP_INT_MAX, 2 );

