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
<header class="site-header">
	<div class="container">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">Tecnostar<span class="brand__dot">.</span></a>
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
</header>
<main id="contenido" class="site-main">
<?php if ( ! is_front_page() ) : ?>
<div class="container section">
<?php endif; ?>
