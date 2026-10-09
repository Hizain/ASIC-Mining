<?php
/**
 * Process steps with a scroll-linked progress line.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_steps = array();
for ( $mm_i = 1; $mm_i <= 4; $mm_i++ ) {
	$mm_title = (string) mm_opt( "step_{$mm_i}_title" );
	if ( '' !== $mm_title ) {
		$mm_steps[] = array( $mm_title, (string) mm_opt( "step_{$mm_i}_text" ) );
	}
}
if ( ! $mm_steps ) {
	return;
}
?>
<section id="mm-section-process" class="mm-section mm-section--process">
	<div class="mm-container">
		<?php mm_section_heading( 'process', array( 'align' => 'center' ) ); ?>
		<div class="mm-process-wrap" data-mm-progress style="--n:<?php echo esc_attr( (string) count( $mm_steps ) ); ?>">
		<span class="mm-process__line" aria-hidden="true"><span></span></span>
		<ol class="mm-process">
			<?php foreach ( $mm_steps as $mm_index => $mm_step ) : ?>
				<li class="mm-process__step" data-mm-reveal style="--d:<?php echo esc_attr( (string) $mm_index ); ?>">
					<span class="mm-process__num"><?php echo esc_html( str_pad( (string) ( $mm_index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
					<h3><?php echo esc_html( $mm_step[0] ); ?></h3>
					<?php if ( $mm_step[1] ) : ?>
						<p><?php echo wp_kses_post( $mm_step[1] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
		</div>
	</div>
</section>
