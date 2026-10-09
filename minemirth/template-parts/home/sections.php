<?php
/**
 * Render enabled homepage sections in their saved order.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

foreach ( mm_home_sections_enabled() as $mm_section ) {
	get_template_part( 'template-parts/home/' . $mm_section );
}
