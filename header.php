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
			<a class="icon-btn" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Carrito', 'tecnostar' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h8.6a1 1 0 0 0 1-.8L20 8H6.2"/><circle cx="9.5" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/></svg>
				<?php echo tecnostar_cart_count_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
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
<main id="contenido" class="site-main">
<?php if ( ! is_front_page() ) : ?>
<div class="container section">
<?php endif; ?>
