<?php
/**
 * Template Name: Nosotros
 *
 * Nosotros / Contáctenos: historia en cinco capítulos, video del recorrido por las sedes (carga diferida),
 * las dos sedes con mapa diferido y botones de ruta, redes sociales y formulario de contacto.
 * Historia, fotos, video, coordenadas y redes son ejemplo marcado hasta que la clienta entregue lo real.
 *
 * @package tecnostar
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ej = function ( $id, $descripcion, $html ) {
	if ( function_exists( 'tecnostar_pendiente' ) ) {
		tecnostar_pendiente( $id, $descripcion, 'Nosotros' );
	}
	return function_exists( 'tecnostar_ejemplo' ) ? tecnostar_ejemplo( $id, $html ) : $html;
};

$img = get_theme_file_uri( 'assets/img/nosotros/' );

// Capítulos de ejemplo. El texto real sale del cuestionario de 8 preguntas que respondió (o responderá) la clienta.
$capitulos = array(
	array( 'El origen', 'Todo empezó con un pequeño taller de reparación y las ganas de dar un servicio técnico honesto, explicando cada arreglo con palabras sencillas.' ),
	array( 'Los primeros clientes', 'Los vecinos y los primeros negocios de la zona llegaron por recomendación. Cada equipo que salía funcionando nos traía uno nuevo.' ),
	array( 'Empresa formal, con factura y garantía', 'Dimos el paso a empresa formal: factura electrónica en cada compra y servicio, y garantía por escrito en todo lo que entregamos.' ),
	array( 'Dos sedes y el Centro de Experiencia', 'Abrimos sede en Bogotá y en Tocancipá, y preparamos un Centro de Experiencia para que toques, pruebes y te conectes antes de comprar.' ),
	array( 'Hoy', 'Atendemos personas y empresas con soporte técnico, equipos nuevos y usados certificados, y la misma cercanía del primer día.' ),
);

$sedes = array(
	array( 'id' => 'bogota', 'nombre' => 'Bogotá', 'ciudad' => 'Bogotá', 'direccion' => 'C.C. Alta Tecnología, local 194', 'horario' => 'Lunes a sábado, 9:30 a. m. a 6:00 p. m.', 'lat' => 4.665870, 'lng' => -74.057620 ),
	array( 'id' => 'tocancipa', 'nombre' => 'Tocancipá', 'ciudad' => 'Tocancipá', 'direccion' => 'C.C. Milán Plaza, local 5-2', 'horario' => 'Lunes a sábado, 10:00 a. m. a 7:00 p. m.', 'lat' => 4.965000, 'lng' => -73.913000 ),
);

$redes = array(
	array( 'Instagram', 'https://www.instagram.com/tecnostaringenieria/' ),
	array( 'Facebook', 'https://www.facebook.com/tecnostaringenieria/' ),
	array( 'TikTok', 'https://www.tiktok.com/@tecnostaringenieria' ),
);

// Video del recorrido: URL desde R2 cuando exista (tarea 10.1). Mientras tanto, imagen de ejemplo.
$video = (string) apply_filters( 'tecnostar_video_recorrido', '' );
?>
<div class="pn ns">
	<section class="pn-hero">
		<div class="container pn-hero__in">
			<p class="pn-hero__eyebrow"><?php echo esc_html( get_the_title() ); ?></p>
			<h1 class="pn-hero__t">Conoce Tecnostar y visítanos en nuestras sedes</h1>
			<p class="em-hero__sub">Una empresa formal, con factura y garantía, y dos sedes donde te atendemos en persona.</p>
			<a class="btn btn--accent" href="#sedes">Ver sedes y horarios</a>
		</div>
	</section>

	<section class="section ns-historia" aria-labelledby="ns-historia-t">
		<div class="container">
			<div class="section__head"><h2 id="ns-historia-t">Nuestra historia</h2></div>
			<?php ob_start(); ?>
			<ol class="ns-capitulos">
				<?php foreach ( $capitulos as $i => $c ) : ?>
					<li class="ns-cap">
						<figure class="ns-cap__f">
							<img src="<?php echo esc_url( $img . 'capitulo-' . ( $i + 1 ) . '.svg' ); ?>" width="640" height="420" loading="lazy" decoding="async" alt="<?php echo esc_attr( 'Foto de ejemplo del capítulo ' . ( $i + 1 ) . ': ' . $c[0] ); ?>">
						</figure>
						<div class="ns-cap__txt">
							<p class="ns-cap__n">Capítulo <?php echo (int) ( $i + 1 ); ?></p>
							<h3 class="ns-cap__t"><?php echo esc_html( $c[0] ); ?></h3>
							<p class="ns-cap__d"><?php echo esc_html( $c[1] ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
			<?php echo $ej( 'nosotros-historia', 'Texto real de los 5 capítulos de la historia (cuestionario de 8 preguntas) y sus fotos reales', ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>

	<section class="section section--alt ns-recorrido" aria-labelledby="ns-recorrido-t">
		<div class="container">
			<div class="section__head"><h2 id="ns-recorrido-t">Recorre nuestras sedes</h2></div>
			<?php ob_start(); ?>
			<div class="ns-video"<?php echo $video ? ' data-video="' . esc_url( $video ) . '"' : ''; ?>>
				<img src="<?php echo esc_url( $img . 'recorrido.svg' ); ?>" width="1280" height="720" loading="lazy" decoding="async" alt="Portada del video de 30 segundos con el recorrido por las sedes (imagen de ejemplo)">
				<?php if ( $video ) : ?>
					<button type="button" class="ns-video__play" data-video-play>Ver el recorrido (30 s)</button>
				<?php else : ?>
					<p class="ns-video__pronto">Muy pronto: video de 30 segundos con el recorrido por las dos sedes.</p>
				<?php endif; ?>
			</div>
			<?php echo $ej( 'nosotros-video-recorrido', 'Video de 30 s del recorrido por las dos sedes (subir a R2, tarea 10.1)', ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>

	<section id="sedes" class="section ns-sedes" aria-labelledby="ns-sedes-t">
		<div class="container">
			<div class="section__head"><h2 id="ns-sedes-t">Nuestras sedes</h2></div>
			<div class="ns-sedes__grid">
				<?php
				foreach ( $sedes as $s ) {
					get_template_part( 'template-parts/sede', null, array_merge( $s, array( 'detalle' => true ) ) );
				}
				?>
			</div>
		</div>
	</section>

	<section class="section ns-redes" aria-labelledby="ns-redes-t">
		<div class="container">
			<div class="section__head"><h2 id="ns-redes-t">Síguenos</h2></div>
			<?php ob_start(); ?>
			<ul class="ns-redes__lista">
				<?php foreach ( $redes as $r ) : ?>
					<li><a href="<?php echo esc_url( $r[1] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $r[0] ); ?><span class="screen-reader-text"> (se abre en una pestaña nueva)</span></a></li>
				<?php endforeach; ?>
			</ul>
			<?php echo $ej( 'nosotros-redes', 'Enlaces reales de las redes sociales (Instagram, Facebook, TikTok)', ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>
	<?php the_content(); ?>
</div>
<?php
get_footer();
