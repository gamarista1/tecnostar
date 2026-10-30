<?php
/**
 * Home de la tienda.
 */
defined( 'ABSPATH' ) || exit;
get_header();

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$cta_url  = tecnostar_hero( 'hero_cta_url' ) ?: $shop_url;
?>

<section class="hero" aria-labelledby="hero-title">
	<div class="container">
		<div class="hero__inner">
			<h1 id="hero-title" data-reveal><?php echo esc_html( tecnostar_hero( 'hero_title' ) ); ?></h1>
			<p data-reveal><?php echo esc_html( tecnostar_hero( 'hero_text' ) ); ?></p>
			<div class="hero__actions" data-reveal>
				<a class="btn btn--accent" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( tecnostar_hero( 'hero_cta_label' ) ); ?></a>
			</div>
		</div>
	</div>
</section>

<section class="benefits" aria-label="<?php esc_attr_e( 'Beneficios', 'tecnostar' ); ?>">
	<div class="container">
		<ul>
			<li data-reveal><strong>Envío gratis en Bogotá</strong><span>En compras desde $200.000</span></li>
			<li data-reveal><strong>Recoge en la tienda</strong><span>Sin costo de envío</span></li>
			<li data-reveal><strong>Paga como prefieras</strong><span>Wompi, Addi, Sistecrédito y más</span></li>
			<li data-reveal><strong>Compra por WhatsApp</strong><span>Te atendemos al instante</span></li>
		</ul>
	</div>
</section>

<?php /* Ancla del banner de Addi: su script lo inserta justo después de este <header>. */ ?>
<header class="addi-anchor" aria-hidden="true"></header>

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
<section class="bm" aria-roledescription="<?php esc_attr_e( 'carrusel', 'tecnostar' ); ?>" aria-label="<?php esc_attr_e( 'Por qué comprar en Tecnostar', 'tecnostar' ); ?>" data-isla="banner-motion">
	<div class="bm__bg" aria-hidden="true">
		<?php foreach ( $escenas as $i => $e ) : ?>
			<span class="bm__fondo bm__fondo--<?php echo (int) $i; ?><?php echo 0 === $i ? ' is-on' : ''; ?>"></span>
		<?php endforeach; ?>
		<span class="bm__blob bm__blob--a"></span><span class="bm__blob bm__blob--b"></span>
	</div>
	<div class="container bm__stage" aria-live="off">
		<?php foreach ( $escenas as $i => $e ) : ?>
			<article class="bm__scene<?php echo 0 === $i ? ' is-active' : ''; ?>" data-fx="<?php echo esc_attr( $e['fx'] ); ?>" role="group" aria-roledescription="<?php esc_attr_e( 'diapositiva', 'tecnostar' ); ?>" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' / ' . $total ); ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>>
				<div class="bm__text">
					<p class="bm__n"><span><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span> / <?php echo esc_html( sprintf( '%02d', $total ) ); ?></p>
					<h2 class="bm__title"><?php echo esc_html( $e['t'] ); ?></h2>
					<p class="bm__desc"><?php echo esc_html( $e['d'] ); ?></p>
				</div>
				<div class="bm__art" aria-hidden="true">
					<span class="bm__fx"><i></i><i></i><i></i></span>
					<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/banner/' . $e['img'] . '.webp' ) ); ?>" width="150" height="150" alt=""<?php echo 0 === $i ? ' fetchpriority="low"' : ' loading="lazy"'; ?> decoding="async">
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<div class="bm__nav" hidden>
		<?php foreach ( $escenas as $i => $e ) : ?>
			<button class="bm__paso" type="button" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: título de la diapositiva */ __( 'Ir a: %s', 'tecnostar' ), $e['t'] ) ); ?>"><span class="bm__pista"><span class="bm__relleno"></span></span></button>
		<?php endforeach; ?>
		<button class="bm__pausa" type="button" aria-pressed="false" aria-label="<?php esc_attr_e( 'Pausar animación', 'tecnostar' ); ?>">
			<svg class="i-pausa" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>
			<svg class="i-play" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4.5v15l13-7.5z"/></svg>
		</button>
	</div>
</section>

<?php
$cats = function_exists( 'wc_get_page_permalink' ) ? get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
		'exclude'    => (int) get_option( 'default_product_cat' ),
	)
) : array();
if ( $cats && ! is_wp_error( $cats ) ) :
	?>
<section class="section" aria-labelledby="cats-title">
	<div class="container">
		<div class="section__head"><h2 id="cats-title" data-reveal>Compra por categoría</h2></div>
		<ul class="cats">
			<?php foreach ( $cats as $cat ) : ?>
				<?php $thumb = (int) get_term_meta( $cat->term_id, 'thumbnail_id', true ); ?>
				<li data-reveal>
					<a class="cat" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
						<?php
						if ( $thumb ) {
							echo wp_get_attachment_image( $thumb, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) );
						}
						?>
						<span><?php echo esc_html( $cat->name ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; ?>

<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
<section class="section" aria-labelledby="new-title">
	<div class="container">
		<div class="section__head">
			<h2 id="new-title" data-reveal>Lo más nuevo</h2>
			<a href="<?php echo esc_url( $shop_url ); ?>" data-reveal>Ver todo</a>
		</div>
		<?php echo do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC"]' ); ?>
	</div>
</section>
<?php endif; ?>

<?php
get_footer();
