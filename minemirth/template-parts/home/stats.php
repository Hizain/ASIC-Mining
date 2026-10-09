<?php
/**
 * Stats: live network values or custom numbers.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_m     = mm_market_display();
$mm_stats = array();
for ( $mm_i = 1; $mm_i <= 4; $mm_i++ ) {
	$mm_value = trim( (string) mm_opt( "stat_{$mm_i}_value" ) );
	if ( '' === $mm_value ) {
		continue;
	}
	$mm_live = '';
	if ( 0 === strpos( $mm_value, 'live:' ) ) {
		$mm_live  = substr( $mm_value, 5 );
		$mm_value = $mm_m[ $mm_live ] ?? '—';
	}
	$mm_stats[] = array(
		'value' => $mm_value,
		'live'  => $mm_live,
		'label' => (string) mm_opt( "stat_{$mm_i}_label" ),
	);
}
if ( ! $mm_stats ) {
	return;
}
?>
<section id="mm-section-stats" class="mm-section mm-section--stats" data-mm-market>
	<div class="mm-container">
		<?php mm_section_heading( 'stats', array( 'align' => 'center' ) ); ?>
		<div class="mm-stats">
			<?php foreach ( $mm_stats as $mm_index => $mm_stat ) : ?>
				<div class="mm-stat mm-card" data-mm-reveal style="--d:<?php echo esc_attr( (string) $mm_index ); ?>">
					<?php if ( $mm_stat['live'] ) : ?>
						<strong class="mm-stat__value" data-mm-m="<?php echo esc_attr( $mm_stat['live'] ); ?>"><?php echo esc_html( $mm_stat['value'] ); ?></strong>
					<?php else : ?>
						<strong class="mm-stat__value" data-mm-count="<?php echo esc_attr( $mm_stat['value'] ); ?>"><?php echo esc_html( $mm_stat['value'] ); ?></strong>
					<?php endif; ?>
					<span class="mm-stat__label"><?php echo esc_html( $mm_stat['label'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
