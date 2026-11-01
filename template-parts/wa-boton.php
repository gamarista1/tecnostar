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
$redes = array(
	array( 'nombre' => 'Instagram', 'url' => 'https://www.instagram.com/tecnostar.ing/', 'c' => '#c13584', 'ct' => '#fff', 'svg' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="1" fill="currentColor" stroke="none"/></svg>' ),
	array( 'nombre' => 'Facebook', 'url' => 'https://www.facebook.com/tecnostar.ing/', 'c' => '#1877f2', 'ct' => '#fff', 'svg' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M13.500 21v-8h2.700l.4-3.200h-3.100V7.800c0-.9.300-1.500 1.600-1.500h1.700V3.400c-.3 0-1.300-.1-2.400-.1-2.400 0-4.100 1.500-4.100 4.200v2.300H7.600V13h2.700v8h3.200Z"/></svg>' ),
	array( 'nombre' => 'LinkedIn', 'url' => 'https://www.linkedin.com/in/tecnostar-ingenier%C3%ADa-electr%C3%B3nica-profesional-sas-13b57b441/', 'c' => '#0a66c2', 'ct' => '#fff', 'svg' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M5 9h3.500v11H5V9Zm1.800-5.500a2 2 0 1 1 0 4 2 2 0 0 1 0-4ZM10.500 9h3.300v1.500c.5-.9 1.700-1.800 3.400-1.800 3.500 0 4.100 2.300 4.100 5.300V20h-3.500v-5.300c0-1.300 0-2.900-1.800-2.900s-2 1.400-2 2.800V20h-3.500V9Z"/></svg>' ),
);
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
