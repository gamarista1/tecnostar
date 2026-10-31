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

// Video del recorrido: clave de R2 (videos/recorrido-sedes.mp4) o URL https completa (tarea 10.1). Vacío: imagen de ejemplo.
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
			<?php
			$reproductor = tecnostar_video_reproductor(
				array(
					'fuente'   => $video,
					'poster'   => $img . 'recorrido.svg',
					'modo'     => 'imagen',
					'alt'      => 'Portada del video de 30 segundos con el recorrido por las sedes (imagen de ejemplo)',
					'etiqueta' => 'Recorrido por las sedes de Tecnostar',
					'boton'    => 'Ver el recorrido',
					'duracion' => '30 s',
					'pronto'   => 'Muy pronto: video de 30 segundos con el recorrido por las dos sedes.',
					'clase'    => 'ns-video',
					'wa'       => 'el video del recorrido por las sedes',
				)
			);
			echo $ej( 'nosotros-video-recorrido', 'Video de 30 s del recorrido por las dos sedes (subir a R2, tarea 10.1)', $reproductor ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
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

	<?php
	if ( function_exists( 'tecnostar_contacto_sedes' ) && tecnostar_contacto_sedes() ) :
		$f_sedes = tecnostar_contacto_sedes();
		$f_res   = tecnostar_contacto_resultado();
		$f_dat   = $f_res && ! empty( $f_res['datos'] ) ? $f_res['datos'] : array();
		$f_err   = $f_res && ! empty( $f_res['errores'] ) ? $f_res['errores'] : array();
		$f_val   = function ( $k ) use ( $f_dat ) {
			return isset( $f_dat[ $k ] ) ? (string) $f_dat[ $k ] : '';
		};
		$f_campo = function ( $k ) use ( $f_err ) {
			return isset( $f_err[ $k ] ) ? ' aria-invalid="true" aria-describedby="' . esc_attr( $k . '-err' ) . '"' : '';
		};
		$f_msg = function ( $k ) use ( $f_err ) {
			return isset( $f_err[ $k ] ) ? '<span class="ns-form__err" id="' . esc_attr( $k . '-err' ) . '">' . esc_html( $f_err[ $k ] ) . '</span>' : '';
		};
		?>
	<section id="contacto" class="section section--alt ns-contacto" aria-labelledby="ns-contacto-t">
		<div class="container ns-contacto__in">
			<div class="ns-contacto__intro">
				<h2 id="ns-contacto-t">Escríbenos</h2>
				<p>Déjanos tus datos y tu mensaje. Al enviarlo te abrimos WhatsApp con la sede que elijas y el mensaje listo.</p>
			</div>
			<form class="ns-form" method="post" action="<?php echo esc_url( get_permalink() . '#contacto' ); ?>" data-contacto data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" novalidate>
				<input type="hidden" name="ts_contacto_enviar" value="1">
				<?php wp_nonce_field( 'ts_contacto', 'ts_nonce', false ); ?>
				<div class="ns-form__trampa" aria-hidden="true">
					<label for="ts_web">No llenes este campo</label>
					<input type="text" name="ts_web" id="ts_web" tabindex="-1" autocomplete="off" value="">
				</div>
				<p class="ns-form__campo">
					<label for="ts_nombre">Nombre</label>
					<input type="text" name="ts_nombre" id="ts_nombre" required minlength="2" maxlength="80" autocomplete="name" value="<?php echo esc_attr( $f_val( 'nombre' ) ); ?>"<?php echo $f_campo( 'ts_nombre' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php echo $f_msg( 'ts_nombre' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</p>
				<p class="ns-form__campo">
					<label for="ts_celular">Celular</label>
					<input type="tel" name="ts_celular" id="ts_celular" required maxlength="20" inputmode="tel" autocomplete="tel" value="<?php echo esc_attr( $f_val( 'celular' ) ); ?>"<?php echo $f_campo( 'ts_celular' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php echo $f_msg( 'ts_celular' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</p>
				<p class="ns-form__campo">
					<label for="ts_sede">Sede</label>
					<select name="ts_sede" id="ts_sede" required<?php echo $f_campo( 'ts_sede' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<option value="">Elige una sede</option>
						<?php foreach ( $f_sedes as $sid => $snombre ) : ?>
							<option value="<?php echo esc_attr( $sid ); ?>"<?php selected( $f_val( 'sede' ), $sid ); ?>><?php echo esc_html( $snombre ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo $f_msg( 'ts_sede' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</p>
				<p class="ns-form__campo">
					<label for="ts_mensaje">Mensaje</label>
					<textarea name="ts_mensaje" id="ts_mensaje" rows="5" required maxlength="2000"<?php echo $f_campo( 'ts_mensaje' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_textarea( $f_val( 'mensaje' ) ); ?></textarea>
					<?php echo $f_msg( 'ts_mensaje' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</p>
				<p class="ns-form__nota">Usamos tus datos solo para responderte. La copia del mensaje se borra a los 90 días.</p>
				<button type="submit" class="btn btn--accent ns-form__enviar">Enviar y abrir WhatsApp</button>
				<div class="ns-form__estado" role="status" aria-live="polite" data-contacto-estado>
					<?php if ( $f_res ) : ?>
						<p class="<?php echo $f_res['ok'] ? 'is-ok' : 'is-error'; ?>"><?php echo esc_html( $f_res['mensaje'] ); ?></p>
						<?php if ( ! empty( $f_res['wa_url'] ) ) : ?>
							<a class="btn btn--accent ns-form__wa" href="<?php echo esc_url( $f_res['wa_url'] ); ?>" target="_blank" rel="noopener" data-contacto-wa>Abrir WhatsApp</a>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</form>
		</div>
	</section>
	<?php endif; ?>

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
