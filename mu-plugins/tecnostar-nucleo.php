<?php
/**
 * Plugin Name: Tecnostar Núcleo
 * Description: Cargador del plugin propio de Tecnostar. Carga los archivos de la carpeta tecnostar-nucleo/ (registro de pendientes y los módulos que se vayan sumando).
 * Version: 0.1.0
 *
 * Un mu-plugin solo carga archivos de primer nivel; por eso este archivo hace de cargador.
 * RESCATE: renombrar este archivo a tecnostar-nucleo.php.off por cPanel (wp-content/mu-plugins) para desactivarlo.
 */

defined( 'ABSPATH' ) || exit;

/** Módulos del núcleo, en orden de carga. Cada nuevo archivo de tecnostar-nucleo/ se añade aquí. */
foreach ( array( 'pendientes.php', 'productos.php', 'servicios.php', 'whatsapp.php', 'seo.php', 'datos-estructurados.php', 'contacto.php', 'video-r2.php' ) as $tecnostar_nucleo_archivo ) {
	$tecnostar_nucleo_ruta = __DIR__ . '/tecnostar-nucleo/' . $tecnostar_nucleo_archivo;
	if ( file_exists( $tecnostar_nucleo_ruta ) ) {
		require_once $tecnostar_nucleo_ruta;
	}
}
unset( $tecnostar_nucleo_archivo, $tecnostar_nucleo_ruta );
