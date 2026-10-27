<?php
/**
 * Mine Mirth theme bootstrap.
 *
 * Every feature lives in /inc. This file only defines constants and loads
 * those files in dependency order.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

define( 'MM_VERSION', '5.0.0' );
define( 'MM_DIR', get_template_directory() );
define( 'MM_URI', get_template_directory_uri() );

$mm_includes = array(
	'inc/options.php',
	'inc/helpers.php',
	'inc/icons.php',
	'inc/setup.php',
	'inc/assets.php',
	'inc/dynamic-css.php',
	'inc/nav-walker.php',
	'inc/template-tags.php',
	'inc/blog.php',
	'inc/customizer/sanitize.php',
	'inc/customizer/customizer.php',
	'inc/modules/market-data.php',
	'inc/modules/market-tools.php',
	'inc/modules/hosting.php',
	'inc/modules/calculator.php',
	'inc/modules/newsletter.php',
	'inc/modules/promos.php',
	'inc/modules/schema.php',
	'inc/modules/discovery.php',
	'inc/tools/tools.php',
	'inc/admin/admin.php',
);

if ( class_exists( 'WooCommerce' ) ) {
	$mm_includes[] = 'inc/woocommerce/woocommerce.php';
}

foreach ( $mm_includes as $mm_file ) {
	require_once MM_DIR . '/' . $mm_file;
}
unset( $mm_includes, $mm_file );
