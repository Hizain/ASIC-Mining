<?php
/**
 * Site header.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_initial_mode = 'dark' === mm_opt( 'mode_default' ) ? 'dark' : 'light';
?><!doctype html>
<html <?php language_attributes(); ?> data-theme="<?php echo esc_attr( $mm_initial_mode ); ?>">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" media="(prefers-color-scheme: light)" content="<?php echo esc_attr( (string) mm_opt( 'light_bg' ) ); ?>">
<meta name="theme-color" media="(prefers-color-scheme: dark)" content="<?php echo esc_attr( (string) mm_opt( 'dark_bg' ) ); ?>">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="mm-skip-link" href="#mm-main"><?php esc_html_e( 'Skip to content', 'minemirth' ); ?></a>

<?php if ( mm_opt( 'fx_page_loader' ) && ! is_customize_preview() ) : ?>
	<div class="mm-loader" data-mm-loader aria-hidden="true">
		<div class="mm-loader__logo"><?php echo mm_logo( 'loader' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<div class="mm-loader__bar"><span></span></div>
	</div>
<?php endif; ?>

<div id="mm-page" class="mm-page">
<?php
if ( mm_is_checkout_focus() ) {
	get_template_part( 'template-parts/header/checkout' );
} else {
	get_template_part( 'template-parts/header/announcement' );
	get_template_part( 'template-parts/header/masthead' );
}
