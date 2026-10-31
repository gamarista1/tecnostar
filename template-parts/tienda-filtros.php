<?php
/**
 * Buscador y filtros de la tienda. Sin JavaScript todo funciona: el buscador es un formulario GET
 * y cada opción de filtro es un enlace normal a su URL. assets/js/tienda.js los aplica sin recargar.
 */

defined( 'ABSPATH' ) || exit;

$tecnostar_activos = tecnostar_filtros_activos();
$tecnostar_busca   = tecnostar_es_busqueda_productos() ? get_search_query( false ) : '';
?>
<form class="ts-buscar" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-ts-buscar>
	<label class="screen-reader-text" for="ts-buscar-q">Buscar productos</label>
	<input class="ts-buscar__campo" type="search" id="ts-buscar-q" name="s" value="<?php echo esc_attr( $tecnostar_busca ); ?>" placeholder="¿Qué buscas? Ej: Dell i5, Macbook..." autocomplete="off" enterkeyhint="search">
	<input type="hidden" name="post_type" value="product">
	<?php foreach ( $tecnostar_activos as $tecnostar_param => $tecnostar_valor ) : ?>
		<input type="hidden" name="<?php echo esc_attr( $tecnostar_param ); ?>" value="<?php echo esc_attr( $tecnostar_valor ); ?>">
	<?php endforeach; ?>
	<button class="ts-buscar__btn" type="submit">
		<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 20l-4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
		<span>Buscar</span>
	</button>
</form>

<details class="ts-filtros" id="ts-filtros" data-ts-filtros>
	<summary class="ts-filtros__resumen" data-ts-resumen>
		Filtros<?php echo $tecnostar_activos ? ' <span class="ts-filtros__n">(' . count( $tecnostar_activos ) . ')</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</summary>
	<div class="ts-filtros__cuerpo" data-ts-grupos>
		<?php foreach ( tecnostar_filtros_grupos() as $tecnostar_param => $tecnostar_grupo ) : ?>
			<div class="ts-filtro" role="group" aria-labelledby="ts-f-<?php echo esc_attr( $tecnostar_param ); ?>">
				<p class="ts-filtro__titulo" id="ts-f-<?php echo esc_attr( $tecnostar_param ); ?>"><?php echo esc_html( $tecnostar_grupo['titulo'] ); ?></p>
				<ul class="ts-filtro__lista">
					<?php
					foreach ( $tecnostar_grupo['opciones'] as $tecnostar_slug => $tecnostar_etiqueta ) :
						$tecnostar_activo = isset( $tecnostar_activos[ $tecnostar_param ] ) && $tecnostar_activos[ $tecnostar_param ] === (string) $tecnostar_slug;
						$tecnostar_url    = tecnostar_filtro_url( array( $tecnostar_param => $tecnostar_activo ? null : $tecnostar_slug ) );
						?>
						<li><a class="ts-chip<?php echo $tecnostar_activo ? ' is-activo' : ''; ?>" href="<?php echo esc_url( $tecnostar_url ); ?>" rel="nofollow" data-ts-filtro data-ts-clave="<?php echo esc_attr( $tecnostar_param . ':' . $tecnostar_slug ); ?>"<?php echo $tecnostar_activo ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $tecnostar_etiqueta ); ?><?php if ( $tecnostar_activo ) : ?><span class="screen-reader-text"> (activo; quitar)</span><?php endif; ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
		<?php if ( $tecnostar_activos ) : ?>
			<p class="ts-filtros__limpiar"><a href="<?php echo esc_url( tecnostar_filtro_url( array_fill_keys( array_keys( $tecnostar_activos ), null ) ) ); ?>" data-ts-filtro data-ts-clave="limpiar">Quitar filtros</a></p>
		<?php endif; ?>
	</div>
</details>
<script>(function(d){if(d&&window.matchMedia&&matchMedia('(min-width: 56.25rem)').matches){d.open=true;}})(document.getElementById('ts-filtros'));</script>
