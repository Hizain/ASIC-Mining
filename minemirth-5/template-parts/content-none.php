<?php
/**
 * Empty state.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="mm-plans-empty" style="padding:56px 24px">
	<p style="font-size:1.1rem;color:var(--mm-text);font-weight:600;margin-bottom:8px">
		<?php echo is_search() ? esc_html__( 'Nothing matched that search.', 'minemirth' ) : esc_html__( 'Nothing published here yet.', 'minemirth' ); ?>
	</p>
	<p><?php esc_html_e( 'Try different keywords, or browse the shop and guides.', 'minemirth' ); ?></p>
	<div class="mm-btn-row mm-btn-row--center" style="margin-top:20px">
		<?php
		echo mm_button( __( 'Shop miners', 'minemirth' ), '{shop}', 'primary', array( 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo mm_button( __( 'Read guides', 'minemirth' ), '{blog}', 'ghost' ); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
	</div>
</div>
