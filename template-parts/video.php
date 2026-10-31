<?php
/**
 * Reproductor de video diferido: póster con botón; nada del video se descarga antes del toque.
 *
 * Argumentos ($args):
 * - fuente:   clave de R2 (videos/xxx.mp4) o URL https completa del MP4. Vacía: póster con el aviso "pronto".
 * - poster:   URL de la imagen de portada.
 * - modo:     'video' (un <video preload="none"> con póster, como en la ficha) o 'imagen' (un <img loading="lazy">;
 *             el <video> se crea al tocar, como en Nosotros). Por defecto 'video'.
 * - alt:      texto alternativo del póster en modo imagen.
 * - etiqueta: nombre accesible del video.
 * - boton:    texto del botón. duracion: texto pequeño junto al botón, por ejemplo "15 s".
 * - pie:      leyenda opcional. pronto: texto cuando no hay video. clase: clases extra del contenedor.
 * - wa:       contexto del mensaje de WhatsApp si el video no carga.
 *
 * Con una clave de R2, assets/js/video.js pide la URL prefirmada a admin-ajax (ts_video, que a su vez la pide a la
 * API Node). Si no llega (API caída o sin claves), el reproductor muestra un mensaje con enlace a WhatsApp.
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	isset( $args ) ? $args : array(),
	array(
		'fuente'   => '',
		'poster'   => '',
		'modo'     => 'video',
		'alt'      => '',
		'etiqueta' => 'Video',
		'boton'    => 'Ver video',
		'duracion' => '',
		'pie'      => '',
		'pronto'   => 'Muy pronto podrás ver el video aquí.',
		'clase'    => '',
		'wa'       => 'el video',
	)
);

$fuente  = function_exists( 'tecnostar_video_fuente_normalizar' ) ? tecnostar_video_fuente_normalizar( (string) $a['fuente'] ) : esc_url_raw( (string) $a['fuente'], array( 'https' ) );
$es_r2   = '' !== $fuente && function_exists( 'tecnostar_r2_clave_valida' ) && tecnostar_r2_clave_valida( $fuente );
$activo  = '' !== $fuente;
$imagen  = 'imagen' === $a['modo'];

$wa = '';
if ( function_exists( 'tecnostar_canales_whatsapp' ) ) {
	$canales = tecnostar_canales_whatsapp();
	if ( $canales ) {
		$wa = tecnostar_wa_armar_url( $canales[0]['numero'], 'Hola, no pude ver ' . $a['wa'] . ' en la web: ' . ( is_singular() ? get_permalink() : home_url( '/' ) ) );
	}
}

$atrs = 'class="' . esc_attr( trim( 'ts-video ' . ( $imagen ? 'ts-video--imagen ' : '' ) . $a['clase'] ) ) . '"';
if ( $activo ) {
	$atrs .= ' data-ts-video';
	$atrs .= $es_r2 ? ' data-clave="' . esc_attr( $fuente ) . '" data-ajax="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '"' : ' data-src="' . esc_url( $fuente ) . '"';
	$atrs .= ' data-etiqueta="' . esc_attr( $a['etiqueta'] ) . '"';
	if ( $wa ) {
		$atrs .= ' data-wa-url="' . esc_url( $wa ) . '" data-wa-contexto="' . esc_attr( $a['wa'] ) . '"';
	}
}

ob_start();
?>
<figure <?php echo $atrs; // phpcs:ignore WordPress.Security.EscapeOutput -- armado y escapado arriba. ?>>
	<?php if ( $imagen ) : ?>
		<img class="ts-video__poster" src="<?php echo esc_url( $a['poster'] ); ?>" width="1280" height="720" loading="lazy" decoding="async" alt="<?php echo esc_attr( $a['alt'] ); ?>">
	<?php else : ?>
		<video class="ts-video__v" preload="none" muted playsinline<?php echo $a['poster'] ? ' poster="' . esc_url( $a['poster'] ) . '"' : ''; ?><?php echo ( $activo && ! $es_r2 ) ? ' data-src="' . esc_url( $fuente ) . '"' : ''; ?> width="640" height="360" aria-label="<?php echo esc_attr( $a['etiqueta'] ); ?>"></video>
	<?php endif; ?>
	<?php if ( $activo ) : ?>
		<button type="button" class="ts-video__play" data-ts-video-play><svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false"><path d="M8 5.5v13l11-6.5z" fill="currentColor"/></svg><span><?php echo esc_html( $a['boton'] ); ?><?php echo $a['duracion'] ? ' <small>(' . esc_html( $a['duracion'] ) . ')</small>' : ''; ?></span></button>
		<p class="ts-video__estado" data-ts-video-estado role="status" aria-live="polite"></p>
		<?php if ( ! $es_r2 ) : ?>
			<noscript><a href="<?php echo esc_url( $fuente ); ?>"><?php echo esc_html( $a['boton'] ); ?></a></noscript>
		<?php endif; ?>
	<?php else : ?>
		<p class="ts-video__pronto"><?php echo esc_html( $a['pronto'] ); ?></p>
	<?php endif; ?>
	<?php if ( $a['pie'] ) : ?>
		<figcaption class="ts-video__cap"><?php echo esc_html( $a['pie'] ); ?></figcaption>
	<?php endif; ?>
</figure>
<?php
echo (string) ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput -- escapado arriba.
