<?php
/**
 * Home de la tienda.
 */
defined( 'ABSPATH' ) || exit;
get_header();

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
?>

<?php /* H1 de la página: se conserva para SEO y lectores de pantalla; el banner de portada anterior se retiró. */ ?>
<h1 class="screen-reader-text"><?php echo esc_html( tecnostar_hero( 'hero_title' ) ); ?></h1>

<?php
// Banner motion: misma altura que el de Addi (280 px). Las escenas 2-4 y la navegación solo se muestran con JS.
$escenas = array(
	array( 'fx' => 'orbita', 'img' => 'icon-social', 't' => 'Comunidad TecnoStar', 'd' => 'Síguenos y descubre unboxings, tips y ofertas exclusivas cada día.' ),
	array( 'fx' => 'veloz', 'img' => 'icon-delivery', 't' => 'Entrega Express en Bogotá', 'd' => 'Despachos rápidos en Bogotá y envíos seguros a cada rincón de Colombia.' ),
	array( 'fx' => 'ping', 'img' => 'icon-payment', 't' => 'Medios de pago', 'd' => 'Recibimos débito, crédito, PSE y Nequi. Financia tus compras con Addi y Sistecrédito.' ),
	array( 'fx' => 'chispa', 'img' => 'icon-ship', 't' => 'Despacho inmediato', 'd' => 'Disponemos de la mercancía en nuestros almacenes y tienda.' ),
);
$total = count( $escenas );
?>
<?php /* Carrusel único de la portada: 1 = Addi, 2 = video intro con las 4 razones. */ ?>
<section class="hc" aria-roledescription="<?php esc_attr_e( 'carrusel', 'tecnostar' ); ?>" aria-label="<?php esc_attr_e( 'Destacados de Tecnostar', 'tecnostar' ); ?>" data-isla="banner-motion">
	<div class="hc__slide hc__slide--addi">
		<?php /* Ancla del banner de Addi: su script lo inserta justo después de este <header>. */ ?>
		<header class="addi-anchor" aria-hidden="true"></header>
	</div>
	<div class="hc__slide hc__slide--motion">
	<div class="bm" aria-label="<?php esc_attr_e( 'Por qué comprar en Tecnostar', 'tecnostar' ); ?>">
	<div class="bm__bg" aria-hidden="true">
		<?php foreach ( $escenas as $i => $e ) : ?>
			<span class="bm__fondo bm__fondo--<?php echo (int) $i; ?><?php echo 0 === $i ? ' is-on' : ''; ?>"></span>
		<?php endforeach; ?>
		<span class="bm__blob bm__blob--a"></span><span class="bm__blob bm__blob--b"></span>
	</div>
	<div class="bm__intro" aria-hidden="true">
		<div class="bm__logo"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/logo/logo-horizontal@2x.png' ) ); ?>" width="849" height="227" alt="" fetchpriority="low" decoding="async"><span class="bm__brillo"></span></div>
	</div>
	<div class="container bm__stage" aria-live="off">
		<?php foreach ( $escenas as $i => $e ) : ?>
			<article class="bm__scene<?php echo 0 === $i ? ' is-active' : ''; ?>" data-fx="<?php echo esc_attr( $e['fx'] ); ?>" role="group" aria-roledescription="<?php esc_attr_e( 'diapositiva', 'tecnostar' ); ?>" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' / ' . $total ); ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>>
				<div class="bm__text">
					<h2 class="bm__title"><?php echo esc_html( $e['t'] ); ?></h2>
					<p class="bm__desc"><?php echo esc_html( $e['d'] ); ?></p>
				</div>
				<div class="bm__art" aria-hidden="true">
					<span class="bm__fx"><i></i><i></i><i></i></span>
					<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/banner/' . $e['img'] . '.webp' ) ); ?>" width="150" height="150" alt=""<?php echo 0 === $i ? ' fetchpriority="high"' : ' loading="lazy"'; ?> decoding="async">
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	</div>
	</div>
	<span class="bm__wipe bm__wipe--a" aria-hidden="true"></span><span class="bm__wipe bm__wipe--b" aria-hidden="true"></span>
		<div class="bm__nav" hidden>
			<button class="bm__paso" type="button" aria-label="<?php esc_attr_e( 'Ir a: Addi', 'tecnostar' ); ?>"><span class="bm__pista"><span class="bm__relleno"></span></span></button>
			<button class="bm__paso" type="button" aria-label="<?php esc_attr_e( 'Ir a: Por qué comprar en Tecnostar', 'tecnostar' ); ?>"><span class="bm__pista"><span class="bm__relleno"></span></span></button>
			<button class="bm__pausa" type="button" aria-pressed="false" aria-label="<?php esc_attr_e( 'Pausar animación', 'tecnostar' ); ?>">
			<svg class="i-pausa" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>
			<svg class="i-play" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4.5v15l13-7.5z"/></svg>
		</button>
	</div>
</section>

<?php
// Tres caminos: cada uno lleva a su página (Persona Natural, Empresas, Tienda).
$caminos = array(
	array(
		'url' => home_url( '/persona-natural/' ),
		't'   => 'Persona Natural',
		'd'   => 'Diagnóstico, repotenciación y reparación de tu equipo.',
		'svg' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/>',
	),
	array(
		'url' => home_url( '/empresas/' ),
		't'   => 'Empresas',
		'd'   => 'Tu departamento de TI externo, con soporte mensual.',
		'svg' => '<rect x="5" y="3" width="14" height="18" rx="1.5"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M10 21v-4h4v4"/>',
	),
	array(
		'url' => $shop_url,
		't'   => 'Tienda',
		'd'   => 'Equipos nuevos y usados certificados, con garantía.',
		'svg' => '<path d="M4 8h16l-1.5 12h-13zM8.5 8V6.5a3.5 3.5 0 0 1 7 0V8"/>',
	),
);
?>
<section class="section caminos" aria-labelledby="caminos-title">
	<div class="container">
		<h2 id="caminos-title" class="screen-reader-text">Elige tu camino</h2>
		<ul class="caminos__lista">
			<?php foreach ( $caminos as $c ) : ?>
				<li data-reveal>
					<a class="camino" href="<?php echo esc_url( $c['url'] ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true"><?php echo $c['svg']; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG fijo del tema. ?></svg>
						<span class="camino__t"><?php echo esc_html( $c['t'] ); ?></span>
						<span class="camino__d"><?php echo esc_html( $c['d'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
	<?php
	$destacados = function_exists( 'wc_get_featured_product_ids' ) ? wc_get_featured_product_ids() : array();
	$filtro     = $destacados ? 'visibility="featured"' : 'orderby="date" order="DESC"';
	?>
<section class="section section--alt" aria-labelledby="dest-title">
	<div class="container">
		<div class="section__head">
			<div><h2 id="dest-title" data-reveal>Productos destacados</h2><p class="section__sub" data-reveal>Equipos listos para despachar, con garantía.</p></div>
			<a href="<?php echo esc_url( $shop_url ); ?>" data-reveal>Ver toda la tienda</a>
		</div>
		<?php echo do_shortcode( '[products limit="4" columns="4" ' . $filtro . ']' ); ?>
	</div>
</section>
<?php endif; ?>

<section class="section sedes" aria-labelledby="sedes-title">
	<div class="container">
		<div class="section__head"><div><h2 id="sedes-title" data-reveal>Visítanos en nuestras sedes</h2><p class="section__sub" data-reveal>Tócalo, pruébalo y llévate el equipo ideal.</p></div></div>
		<div class="sedes__grid">
			<?php
			get_template_part( 'template-parts/sede', null, array( 'id' => 'bogota', 'nombre' => 'Bogotá', 'ciudad' => 'Bogotá', 'direccion' => 'C.C. Alta Tecnología, local 194', 'horario' => 'Lunes a sábado, 9:30 a. m. a 6:00 p. m.' ) );
			get_template_part( 'template-parts/sede', null, array( 'id' => 'tocancipa', 'nombre' => 'Tocancipá', 'ciudad' => 'Tocancipá', 'direccion' => 'C.C. Milán Plaza, local 5-2', 'horario' => 'Lunes a sábado, 10:00 a. m. a 7:00 p. m.' ) );
			?>
		</div>
	</div>
</section>

<section class="banda" aria-labelledby="banda-title">
	<div class="container banda__in" data-reveal>
		<div>
			<h2 id="banda-title">¿No encuentras lo que buscas?</h2>
			<p>Si no lo tenemos, te lo conseguimos.</p>
		</div>
		<a class="btn btn--accent" href="#" data-wa-abrir="busqueda">Escríbenos y te lo cotizamos</a>
	</div>
</section>

<?php
get_footer();
