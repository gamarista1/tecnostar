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
?>
<div class="ts-wa ts-wa--<?php echo esc_attr( $esquina ); ?>" data-wa>
	<ul class="ts-wa__lista" id="ts-wa-lista">
		<?php
		foreach ( $canales as $i => $c ) :
			$ini = mb_strtoupper( mb_substr( trim( preg_replace( '/^sede\s+/iu', '', $c['nombre'] ) ), 0, 1 ) );
			?>
			<li style="--k:<?php echo esc_attr( $n > 1 ? $i / ( $n - 1 ) : 0 ); ?>">
				<a class="ts-wa__item" href="<?php echo esc_url( tecnostar_wa_url( $c['id'], $saludo ) ); ?>" data-wa-canal="<?php echo esc_attr( $c['id'] ); ?>" data-wa-num="<?php echo esc_attr( $c['numero'] ); ?>" target="_blank" rel="noopener" tabindex="-1">
					<span class="ts-wa__circ" aria-hidden="true"><?php echo esc_html( $ini ); ?></span>
					<span class="ts-wa__etq"><?php echo esc_html( $c['nombre'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<a class="ts-wa__fab" href="<?php echo esc_url( tecnostar_wa_url( $canales[0]['id'], $saludo ) ); ?>" data-wa-fab target="_blank" rel="noopener" aria-controls="ts-wa-lista" aria-label="<?php esc_attr_e( 'Escríbenos por WhatsApp', 'tecnostar' ); ?>">
		<svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.4A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.200Zm4.5-6.100c-.2-.1-1.500-.7-1.700-.8-.2-.1-.4-.1-.6.100l-.8 1c-.1.200-.3.200-.5.100a6.700 6.700 0 0 1-3.300-2.900c-.2-.4.200-.4.700-1.300.1-.2 0-.3 0-.5l-.8-1.800c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.100-.7.300-.2.300-.9.900-.9 2.200s.9 2.500 1.100 2.700c.1.200 1.800 2.800 4.500 3.900 1.700.7 2.300.7 3.100.6.500-.1 1.500-.6 1.700-1.200.2-.6.2-1.100.2-1.200-.1-.1-.3-.2-.6-.3Z"/></svg>
	</a>
</div>
