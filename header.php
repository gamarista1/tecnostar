<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#contenido"><?php esc_html_e( 'Saltar al contenido', 'tecnostar' ); ?></a>
<?php /* Div con role=banner (no <header>): el script de Addi inserta su banner tras el primer <header>; ver front-page.php. */ ?>
<div class="site-header" role="banner">
	<div class="container site-header__bar">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img
				src="<?php echo esc_url( get_theme_file_uri( 'assets/img/logo/logo-horizontal.png' ) ); ?>"
				srcset="<?php echo esc_url( get_theme_file_uri( 'assets/img/logo/logo-horizontal.png' ) ); ?> 425w, <?php echo esc_url( get_theme_file_uri( 'assets/img/logo/logo-horizontal@2x.png' ) ); ?> 849w"
				sizes="(min-width: 52rem) 224px, 150px"
				width="425" height="114"
				alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
				fetchpriority="high" decoding="async">
		</a>

		<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
			<a class="icon-btn" data-mc-abrir href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Carrito', 'tecnostar' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h8.6a1 1 0 0 0 1-.8L20 8H6.2"/><circle cx="9.5" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/></svg>
				<?php echo tecnostar_cart_count_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</a>
		<?php endif; ?>

		<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
			<?php /* Mismo HTML para todos (cacheable): el contenido de la tarjeta se pide al abrirla (inc/cuenta.php). Sin JS es un enlace a Mi cuenta. */ ?>
			<a class="icon-btn" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" data-cuenta-abrir aria-label="<?php esc_attr_e( 'Mi cuenta', 'tecnostar' ); ?>" aria-expanded="false" aria-haspopup="dialog" aria-controls="ts-cuenta">
				<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20.5c1.4-3.8 4.4-5.8 8-5.8s6.6 2 8 5.8"/></svg>
			</a>
		<?php endif; ?>

		<button class="icon-btn icon-btn--menu" type="button" aria-expanded="false" aria-controls="nav-panel" aria-label="<?php esc_attr_e( 'Abrir menú', 'tecnostar' ); ?>" data-nav-toggle>
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
		</button>

		<div class="nav-panel" id="nav-panel" data-nav-panel>
			<div>
				<nav aria-label="<?php esc_attr_e( 'Principal', 'tecnostar' ); ?>">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => false,
							'menu_class'     => 'nav',
							'fallback_cb'    => 'tecnostar_fallback_nav',
						)
					);
					?>
				</nav>
			</div>
		</div>
	</div>
</div>
<?php /* Fuera de la cabecera: su backdrop-filter haría de bloque contenedor del position: fixed. */ ?>
<div id="ts-cuenta" class="ts-cuenta" role="dialog" aria-label="<?php esc_attr_e( 'Mi cuenta', 'tecnostar' ); ?>" tabindex="-1" hidden></div>
<main id="contenido" class="site-main">
<?php if ( ! is_front_page() ) : ?>
<div class="container section">
<?php endif; ?>
