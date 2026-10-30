<?php
/**
 * Plantilla envolvente de WooCommerce. Las plantillas por pantalla se añaden en /woocommerce/.
 */
defined( 'ABSPATH' ) || exit;
get_header();
woocommerce_content();
get_footer();
