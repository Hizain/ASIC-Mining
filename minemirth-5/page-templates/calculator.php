<?php
/**
 * Template Name: Mining Calculator
 * Template Post Type: page
 *
 * Kept at the same path as earlier versions so existing calculator pages keep working.
 * It now shows the live profitability calculator (or the page's chosen tool).
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$mm_tool = mm_page_tool_id();
	mm_render_tool_page( $mm_tool ? $mm_tool : 'profit' );
endwhile;

get_footer();
