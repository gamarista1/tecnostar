<?php
/**
 * URLs prefirmadas de Cloudflare R2 (API S3, AWS Signature Version 4 por query string).
 * Lógica pura, sin dependencias de WordPress ni librerías externas (se prueba por consola contra el
 * vector oficial de AWS). Solo define funciones; no ejecuta nada al cargarse.
 */

/** Clave válida de un video en el bucket: videos/<nombre>.mp4, sin subcarpetas, sin "..", en minúsculas. */
function tecnostar_r2_clave_valida( string $clave ): bool {
	return 1 === preg_match( '#^videos/[a-z0-9][a-z0-9._-]{0,120}\.mp4$#D', $clave ) && false === strpos( $clave, '..' );
}

/** Codifica una ruta para la petición canónica de SigV4: cada segmento con RFC 3986, las barras se conservan. */
function tecnostar_sigv4_codificar_ruta( string $ruta ): string {
	return implode( '/', array_map( 'rawurlencode', explode( '/', $ruta ) ) );
}

/**
 * Firma una URL con SigV4 en la query string (UNSIGNED-PAYLOAD, solo la cabecera host firmada).
 *
 * @param string $host      Host sin esquema, por ejemplo <account>.r2.cloudflarestorage.com.
 * @param string $ruta      Ruta sin codificar que empieza por "/", por ejemplo /media/videos/x.mp4.
 * @param string $region    "auto" en R2.
 * @param string $servicio  "s3".
 * @param string $access    Access Key ID.
 * @param string $secret    Secret Access Key.
 * @param int    $ahora     Marca de tiempo Unix de la firma.
 * @param int    $caducidad Segundos de validez (1 a 604800).
 * @param string $metodo    Método HTTP.
 */
function tecnostar_sigv4_prefirmar( string $host, string $ruta, string $region, string $servicio, string $access, string $secret, int $ahora, int $caducidad, string $metodo = 'GET' ): string {
	$caducidad = max( 1, min( 604800, $caducidad ) );
	$fecha     = gmdate( 'Ymd', $ahora );
	$fecha_hora = gmdate( 'Ymd\THis\Z', $ahora );
	$alcance   = $fecha . '/' . $region . '/' . $servicio . '/aws4_request';
	$host      = strtolower( $host );
	$uri       = tecnostar_sigv4_codificar_ruta( $ruta );

	$query = array(
		'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
		'X-Amz-Credential'    => $access . '/' . $alcance,
		'X-Amz-Date'          => $fecha_hora,
		'X-Amz-Expires'       => (string) $caducidad,
		'X-Amz-SignedHeaders' => 'host',
	);
	ksort( $query, SORT_STRING );
	$partes = array();
	foreach ( $query as $k => $v ) {
		$partes[] = rawurlencode( $k ) . '=' . rawurlencode( $v );
	}
	$query_canonica = implode( '&', $partes );

	$peticion = $metodo . "\n" . $uri . "\n" . $query_canonica . "\nhost:" . $host . "\n\nhost\nUNSIGNED-PAYLOAD";
	$a_firmar = "AWS4-HMAC-SHA256\n" . $fecha_hora . "\n" . $alcance . "\n" . hash( 'sha256', $peticion );

	$k_fecha    = hash_hmac( 'sha256', $fecha, 'AWS4' . $secret, true );
	$k_region   = hash_hmac( 'sha256', $region, $k_fecha, true );
	$k_servicio = hash_hmac( 'sha256', $servicio, $k_region, true );
	$k_firma    = hash_hmac( 'sha256', 'aws4_request', $k_servicio, true );
	$firma      = hash_hmac( 'sha256', $a_firmar, $k_firma );

	return 'https://' . $host . $uri . '?' . $query_canonica . '&X-Amz-Signature=' . $firma;
}

/**
 * URL prefirmada de un objeto del bucket de R2, estilo ruta (/<bucket>/<clave>), región auto, servicio s3.
 * Devuelve '' si la clave o la configuración no son válidas.
 *
 * @param string   $clave     Clave del objeto (videos/xxx.mp4).
 * @param int      $caducidad Segundos de validez.
 * @param array    $config    account_id, access_key_id, secret_access_key, bucket.
 * @param int|null $ahora     Marca de tiempo (para pruebas); por defecto, time().
 */
function tecnostar_r2_url_prefirmada( string $clave, int $caducidad, array $config, ?int $ahora = null ): string {
	if ( ! tecnostar_r2_clave_valida( $clave ) || ! tecnostar_r2_config_valida( $config ) ) {
		return '';
	}
	return tecnostar_sigv4_prefirmar(
		strtolower( $config['account_id'] ) . '.r2.cloudflarestorage.com',
		'/' . $config['bucket'] . '/' . $clave,
		'auto',
		's3',
		$config['access_key_id'],
		$config['secret_access_key'],
		null === $ahora ? time() : $ahora,
		$caducidad
	);
}

/** Comprueba la forma de la configuración (sin revelar valores). */
function tecnostar_r2_config_valida( $config ): bool {
	if ( ! is_array( $config ) ) {
		return false;
	}
	foreach ( array( 'account_id', 'access_key_id', 'secret_access_key', 'bucket' ) as $k ) {
		if ( ! isset( $config[ $k ] ) || ! is_string( $config[ $k ] ) || '' === trim( $config[ $k ] ) ) {
			return false;
		}
	}
	return 1 === preg_match( '/^[a-f0-9]{32}$/i', $config['account_id'] )
		&& 1 === preg_match( '/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/', $config['bucket'] );
}
