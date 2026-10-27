<?php
/**
 * Template Name: Mining Tool
 * Template Post Type: page
 *
 * Shows the tool chosen in the page's "_mm_tool" field (the site builder sets it),
 * or the tool whose address matches the page slug.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	mm_render_tool_page( mm_page_tool_id() );
endwhile;

get_footer();
