<?php
/**
 * Blog sidebar.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_active_sidebar( 'sidebar-blog' ) ) {
	return;
}
?>
<aside class="mm-sidebar" aria-label="<?php esc_attr_e( 'Sidebar', 'minemirth' ); ?>">
	<?php dynamic_sidebar( 'sidebar-blog' ); ?>
</aside>
