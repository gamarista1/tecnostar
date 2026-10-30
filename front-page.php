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
