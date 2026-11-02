<?php
/**
 * Botón flotante de WhatsApp con menú radial de canales. Se imprime una vez, en footer.php.
 * Sin JavaScript el botón es un enlace a la primera sede; con JS abre el arco de canales.
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'tecnostar_canales_whatsapp' ) || ! function_exists( 'tecnostar_wa_url' ) ) {
	return;
}
$canales = tecnostar_canales_whatsapp();
if ( ! $canales ) {
	return;
}
$esquina = function_exists( 'tecnostar_wa_esquina' ) ? tecnostar_wa_esquina() : 'inferior-derecha';
$saludo  = 'Hola, quiero información.';
$n       = count( $canales );
$wa_path = 'M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.4A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.200Zm4.5-6.100c-.2-.1-1.500-.7-1.700-.8-.2-.1-.4-.1-.6.100l-.8 1c-.1.200-.3.200-.5.100a6.700 6.700 0 0 1-3.300-2.900c-.2-.4.200-.4.700-1.300.1-.2 0-.3 0-.5l-.8-1.800c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.100-.7.300-.2.300-.9.900-.9 2.200s.9 2.500 1.100 2.700c.1.200 1.800 2.800 4.500 3.900 1.700.7 2.300.7 3.100.6.500-.1 1.500-.6 1.700-1.200.2-.6.2-1.100.2-1.200-.1-.1-.3-.2-.6-.3Z';
$wa_ico  = static function ( int $px ) use ( $wa_path ): string {
	return '<svg width="' . $px . '" height="' . $px . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="' . $wa_path . '"/></svg>';
};
$redes = function_exists( 'tecnostar_redes_sociales' ) ? tecnostar_redes_sociales() : array();
?>
<div class="ts-wa ts-wa--<?php echo esc_attr( $esquina ); ?>" data-wa>
	<ul class="ts-wa__lista ts-wa__lista--redes" id="ts-wa-lista">
		<?php foreach ( $redes as $j => $r ) : ?>
			<li style="--k:<?php echo esc_attr( $j / ( count( $redes ) - 1 ) ); ?>">
				<a class="ts-wa__item ts-wa__item--red" href="<?php echo esc_url( $r['url'] ); ?>" target="_blank" rel="noopener noreferrer" tabindex="-1" title="<?php echo esc_attr( $r['nombre'] ); ?>" style="--c:<?php echo esc_attr( $r['c'] ); ?>">
					<span class="ts-wa__circ"><?php echo $r['svg']; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="screen-reader-text"><?php echo esc_html( $r['nombre'] ); ?> (se abre en una pestaña nueva)</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<div class="ts-wa__pre" id="ts-wa-pre" role="dialog" aria-labelledby="ts-wa-pre-t" hidden>
		<div class="ts-wa__pre-cab">
			<div><strong id="ts-wa-pre-t">Escríbenos por WhatsApp</strong><span>Cuéntanos con quién y para qué</span></div>
			<button type="button" class="ts-wa__pre-x" data-pre-cerrar aria-label="Cerrar">&times;</button>
		</div>
		<div class="ts-wa__pre-cuerpo" data-pre-cuerpo aria-live="polite"></div>
		<div class="ts-wa__pre-pie">
			<div class="ts-wa__chips" data-pre-chips></div>
			<form class="ts-wa__form" data-pre-form hidden>
				<label class="screen-reader-text" for="ts-wa-pre-nom">Tu nombre</label>
				<input id="ts-wa-pre-nom" type="text" maxlength="60" autocomplete="name" placeholder="Escribe tu nombre…" data-pre-nombre required>
				<label class="screen-reader-text" for="ts-wa-pre-msg">Tu mensaje</label>
				<textarea id="ts-wa-pre-msg" rows="3" maxlength="800" placeholder="Escribe brevemente lo que necesitas…" required></textarea>
				<div class="ts-wa__form-fila">
					<button type="button" class="ts-wa__atras" data-pre-atras>Volver</button>
					<button type="submit" class="ts-wa__enviar">Enviar por WhatsApp</button>
				</div>
				<p class="ts-wa__nota">Se abrirá WhatsApp con tu mensaje listo para enviar.</p>
			</form>
		</div>
		<?php foreach ( $canales as $c ) : ?>
			<span hidden data-pre-canal="<?php echo esc_attr( $c['id'] ); ?>" data-wa-num="<?php echo esc_attr( $c['numero'] ); ?>" data-nombre="<?php echo esc_attr( 'pqrs' === $c['id'] ? 'PQRS' : $c['nombre'] ); ?>"></span>
		<?php endforeach; ?>
	</div>
	<a class="ts-wa__fab" href="<?php echo esc_url( tecnostar_wa_url( $canales[0]['id'], $saludo ) ); ?>" data-wa-fab target="_blank" rel="noopener" aria-controls="ts-wa-pre" aria-label="<?php esc_attr_e( 'Escríbenos por WhatsApp', 'tecnostar' ); ?>">
		<?php echo $wa_ico( 28 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</a>
</div>
