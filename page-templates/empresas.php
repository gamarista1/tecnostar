<?php
/**
 * Template Name: Empresas
 *
 * Hero de TI externo, qué incluye la mensualidad y tabla comparativa de planes sin precios.
 * Cada plan abre el menú de WhatsApp con "Cotizar el plan <nombre>". Los textos que aún no tenemos van como ejemplo marcado.
 *
 * @package tecnostar
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ej = function ( $id, $descripcion, $html ) {
	if ( function_exists( 'tecnostar_pendiente' ) ) {
		tecnostar_pendiente( $id, $descripcion, 'Empresas' );
	}
	return function_exists( 'tecnostar_ejemplo' ) ? tecnostar_ejemplo( $id, $html ) : $html;
};

$incluye = array(
	array( 'Mesa de ayuda', 'Atención de lunes a sábado para resolver los problemas del día a día de tu equipo.' ),
	array( 'Visitas mensuales programadas', 'Un técnico va a tu empresa cada mes, con agenda acordada.' ),
	array( 'Mantenimiento por lotes', 'Limpieza y puesta a punto de varios equipos en una misma visita.' ),
	array( 'Alquiler y dotación de equipos', 'Equipos listos para tu personal, sin comprarlos de una vez.' ),
	array( 'Facturación electrónica con contrato', 'Un contrato claro y factura electrónica cada mes.' ),
);

// Cuerpo de cada plan: solo "equipos" es dato firme. El resto es ejemplo hasta que el equipo lo defina.
$planes = array(
	array( 'Emprendedor', '5', 'Hasta 5 equipos', 'Una visita al mes', 'Por consulta' ),
	array( 'Pyme', '15', 'Hasta 15 equipos', 'Una o dos visitas al mes', 'Por lotes' ),
	array( 'Corporativo', '20+', '20+ equipos', 'Visitas a la medida', 'Por lotes y sedes' ),
);
$ld_url = 'https://www.linkedin.com/company/tecnostar-ingenieria-electronica';
?>
<div class="pn em">
	<section class="pn-hero">
		<div class="container pn-hero__in">
			<p class="pn-hero__eyebrow"><?php echo esc_html( get_the_title() ); ?></p>
			<h1 class="pn-hero__t">Tu departamento de TI externo. Soporte como outsourcing</h1>
			<p class="em-hero__sub">Nos encargamos de la tecnología de tu empresa para que tú te ocupes de tu negocio.</p>
			<?php
			echo $ej( // phpcs:ignore WordPress.Security.EscapeOutput
				'empresas-linkedin',
				'URL real del LinkedIn de Tecnostar',
				'<a class="em-linkedin" href="' . esc_url( $ld_url ) . '" target="_blank" rel="noopener noreferrer">Conócenos en LinkedIn<span class="screen-reader-text"> (se abre en una pestaña nueva)</span></a>'
			);
			?>
		</div>
	</section>

	<section class="section" aria-labelledby="em-incluye-t">
		<div class="container">
			<div class="section__head"><h2 id="em-incluye-t">Incluido en la mensualidad</h2></div>
			<ul class="em-incluye">
				<?php foreach ( $incluye as $i ) : ?>
					<li><strong><?php echo esc_html( $i[0] ); ?></strong><span><?php echo esc_html( $i[1] ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<section class="section section--alt" aria-labelledby="em-planes-t">
		<div class="container">
			<div class="section__head"><h2 id="em-planes-t">Elige el plan para tu empresa</h2></div>
			<div class="em-tabla">
				<table>
					<caption>Planes de soporte para empresas según la cantidad de equipos. El valor se cotiza con cada empresa.</caption>
					<thead>
						<tr>
							<th scope="col">Plan</th>
							<th scope="col">Equipos</th>
							<th scope="col">Visitas</th>
							<th scope="col">Mantenimiento</th>
							<th scope="col"><span class="screen-reader-text">Cotizar</span></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $planes as $p ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( $p[0] ); ?></th>
								<td data-label="Equipos"><?php echo esc_html( $p[2] ); ?></td>
								<td data-label="Visitas"><?php echo $ej( 'empresas-plan-' . sanitize_key( $p[0] ) . '-visitas', 'Visitas incluidas del plan ' . $p[0], esc_html( $p[3] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td data-label="Mantenimiento"><?php echo $ej( 'empresas-plan-' . sanitize_key( $p[0] ) . '-mantenimiento', 'Mantenimiento del plan ' . $p[0], esc_html( $p[4] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td class="em-tabla__cta"><a class="btn btn--accent" href="<?php echo esc_url( function_exists( 'tecnostar_wa_url' ) ? tecnostar_wa_url( 'bogota', 'Hola, quiero cotizar el plan ' . $p[0] ) : '#' ); ?>" data-wa-abrir="<?php echo esc_attr( 'Cotizar el plan ' . $p[0] ); ?>" aria-label="<?php echo esc_attr( 'Cotizar este plan: ' . $p[0] ); ?>">Cotizar este plan</a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</section>
	<?php the_content(); ?>
</div>
<?php
get_footer();
