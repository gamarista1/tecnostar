<?php
/**
 * SEO por página: título y descripción propios, canonical limpio en la tienda y robots.txt.
 *
 * Metas: _ts_seo_titulo y _ts_seo_desc (páginas, productos y servicios).
 * El tema (inc/seo.php) lee la descripción mediante el filtro tecnostar_meta_description.
 */

defined( 'ABSPATH' ) || exit;

/** Tipos de contenido con campos SEO. */
function tecnostar_seo_tipos() {
	return array( 'page', 'product', 'ts_servicio' );
}

/** Registra las metas. */
function tecnostar_seo_registrar_metas() {
	foreach ( tecnostar_seo_tipos() as $tipo ) {
		foreach ( array( '_ts_seo_titulo', '_ts_seo_desc' ) as $meta ) {
			register_post_meta(
				$tipo,
				$meta,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => function ( $permitido, $clave, $id ) {
						return current_user_can( 'edit_post', $id );
					},
				)
			);
		}
	}
}
add_action( 'init', 'tecnostar_seo_registrar_metas' );

/** Caja SEO en el editor clásico. */
add_action(
	'add_meta_boxes',
	function () {
		foreach ( tecnostar_seo_tipos() as $tipo ) {
			add_meta_box( 'tecnostar_seo', 'SEO', 'tecnostar_seo_caja', $tipo, 'normal', 'default' );
		}
	}
);

function tecnostar_seo_caja( $post ) {
	wp_nonce_field( 'tecnostar_seo_guardar', 'tecnostar_seo_nonce' );
	$titulo = (string) get_post_meta( $post->ID, '_ts_seo_titulo', true );
	$desc   = (string) get_post_meta( $post->ID, '_ts_seo_desc', true );
	$url    = (string) get_permalink( $post );
	?>
	<p>
		<label for="ts_seo_titulo"><strong>Título SEO</strong></label>
		<input type="text" id="ts_seo_titulo" name="ts_seo_titulo" value="<?php echo esc_attr( $titulo ); ?>" class="widefat" maxlength="120">
		<span class="description"><span id="ts_seo_titulo_n">0</span> caracteres (ideal: hasta 60)</span>
	</p>
	<p>
		<label for="ts_seo_desc"><strong>Descripción SEO</strong></label>
		<textarea id="ts_seo_desc" name="ts_seo_desc" rows="3" class="widefat" maxlength="300"><?php echo esc_textarea( $desc ); ?></textarea>
		<span class="description"><span id="ts_seo_desc_n">0</span> caracteres (ideal: hasta 158)</span>
	</p>
	<div style="border:1px solid #dcdcde;padding:10px 12px;background:#fff;max-width:600px">
		<div style="color:#202124;font-size:12px"><?php echo esc_html( $url ); ?></div>
		<div id="ts_seo_vp_t" style="color:#1a0dab;font-size:18px;line-height:1.3"></div>
		<div id="ts_seo_vp_d" style="color:#4d5156;font-size:13px;line-height:1.4"></div>
	</div>
	<script>
	(function () {
		var t = document.getElementById('ts_seo_titulo'), d = document.getElementById('ts_seo_desc');
		var nt = document.getElementById('ts_seo_titulo_n'), nd = document.getElementById('ts_seo_desc_n');
		var vt = document.getElementById('ts_seo_vp_t'), vd = document.getElementById('ts_seo_vp_d');
		var titPost = document.getElementById('title');
		function cortar(s, n) { return s.length > n ? s.slice(0, n - 1) + '…' : s; }
		function act() {
			nt.textContent = t.value.length;
			nd.textContent = d.value.length;
			vt.textContent = cortar(t.value || (titPost ? titPost.value : ''), 60);
			vd.textContent = cortar(d.value, 158);
		}
		t.addEventListener('input', act);
		d.addEventListener('input', act);
		if (titPost) { titPost.addEventListener('input', act); }
		act();
	})();
	</script>
	<?php
}

/** Guarda los campos del editor clásico. */
add_action(
	'save_post',
	function ( $post_id ) {
		if ( ! isset( $_POST['tecnostar_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tecnostar_seo_nonce'] ) ), 'tecnostar_seo_guardar' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( array(
			'_ts_seo_titulo' => 'ts_seo_titulo',
			'_ts_seo_desc'   => 'ts_seo_desc',
		) as $meta => $campo ) {
			$valor = isset( $_POST[ $campo ] ) ? sanitize_text_field( wp_unslash( $_POST[ $campo ] ) ) : '';
			if ( '' === $valor ) {
				delete_post_meta( $post_id, $meta );
			} else {
				update_post_meta( $post_id, $meta, $valor );
			}
		}
	}
);

/** Valor de una meta SEO del singular actual, o cadena vacía. */
function tecnostar_seo_meta_actual( $meta ) {
	if ( ! is_singular( tecnostar_seo_tipos() ) ) {
		return '';
	}
	$id = get_queried_object_id();
	return $id ? trim( (string) get_post_meta( $id, $meta, true ) ) : '';
}

/** Título propio (prioridad mayor que la del tema, que solo actúa en la portada). */
add_filter(
	'pre_get_document_title',
	function ( $title ) {
		if ( is_front_page() ) {
			return $title;
		}
		$propio = tecnostar_seo_meta_actual( '_ts_seo_titulo' );
		return '' !== $propio ? $propio : $title;
	},
	20
);

/** Descripción propia para tecnostar_meta_description() del tema. */
add_filter(
	'tecnostar_meta_description',
	function ( $texto ) {
		if ( is_front_page() ) {
			return $texto;
		}
		$propia = tecnostar_seo_meta_actual( '_ts_seo_desc' );
		return '' !== $propia ? $propia : $texto;
	}
);

/**
 * Canonical del archivo de tienda y de categorías/atributos de producto: sin parámetros de filtro.
 * WordPress solo imprime rel=canonical en singulares; aquí se imprime para estos archivos.
 */
add_action(
	'wp_head',
	function () {
		if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_taxonomy() ) ) {
			return;
		}
		if ( is_shop() ) {
			$base = get_permalink( wc_get_page_id( 'shop' ) );
		} else {
			$base = get_term_link( get_queried_object() );
		}
		if ( ! $base || is_wp_error( $base ) ) {
			return;
		}
		$base   = strtok( (string) $base, '?' );
		$pagina = (int) get_query_var( 'paged' );
		if ( $pagina > 1 ) {
			$base = trailingslashit( $base ) . 'page/' . $pagina . '/';
		}
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $base ) );
	},
	1
);

/** robots.txt: bloquea filtros, búsqueda y áreas privadas; no bloquea el catálogo. */
add_filter(
	'robots_txt',
	function ( $salida ) {
		$reglas = array(
			'/*?marca=',
			'/*?grado=',
			'/*?uso=',
			'/*?presupuesto=',
			'/*?filter_',
			'/*?s=',
			// El mismo filtro detrás de otro parámetro (?s=...&marca=..., ?orderby=...&grado=...).
			'/*&marca=',
			'/*&grado=',
			'/*&uso=',
			'/*&presupuesto=',
		);
		$rutas = array(
			'cart'      => '/carrito/',
			'checkout'  => '/finalizar-compra/',
			'myaccount' => '/mi-cuenta/',
		);
		foreach ( $rutas as $pagina => $defecto ) {
			$ruta = $defecto;
			if ( function_exists( 'wc_get_page_permalink' ) ) {
				$enlace = wc_get_page_permalink( $pagina );
				$parte  = $enlace ? wp_parse_url( $enlace, PHP_URL_PATH ) : '';
				if ( $parte && '/' !== $parte ) {
					$ruta = trailingslashit( $parte );
				}
			}
			$reglas[] = $ruta;
		}
		$reglas[] = '/acceso/';

		// Se retiran las líneas Sitemap existentes (las de WordPress) y se añade una sola al final.
		$salida = preg_replace( '/^\s*Sitemap:.*$/mi', '', (string) $salida );
		$salida = rtrim( $salida ) . "\n";
		foreach ( array_unique( $reglas ) as $regla ) {
			$linea = 'Disallow: ' . $regla;
			if ( false === strpos( $salida, $linea . "\n" ) ) {
				$salida .= $linea . "\n";
			}
		}
		return $salida . "\nSitemap: " . home_url( '/wp-sitemap.xml' ) . "\n";
	},
	99
);
