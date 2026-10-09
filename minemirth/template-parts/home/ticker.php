<?php
/**
 * Live market ticker (seamless marquee).
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_m       = mm_market_display();
$mm_labels  = array(
	'price'      => __( 'BTC', 'minemirth' ),
	'change'     => __( '24h', 'minemirth' ),
	'hashrate'   => __( 'Hashrate', 'minemirth' ),
	'difficulty' => __( 'Difficulty', 'minemirth' ),
	'height'     => __( 'Block height', 'minemirth' ),
	'fees'       => __( 'Avg. fees / block', 'minemirth' ),
	'adjustment' => __( 'Next difficulty adj.', 'minemirth' ),
	'halving'    => __( 'Blocks to halving', 'minemirth' ),
);
$mm_metrics = mm_opt( 'market_enable' ) ? array_intersect( mm_list( strtolower( (string) mm_opt( 'ticker_metrics' ) ) ), array_keys( $mm_labels ) ) : array();
$mm_custom  = mm_list( mm_opt( 'ticker_custom' ), '|' );

if ( ! $mm_metrics && ! $mm_custom ) {
	return;
}

ob_start();
foreach ( $mm_metrics as $mm_key ) {
	$mm_class = 'change' === $mm_key ? ' mm-change ' . $mm_m['change_class'] : '';
	printf(
		'<li class="mm-ticker__item"><span class="mm-ticker__label">%1$s</span><strong class="mm-ticker__value%2$s" data-mm-m="%3$s">%4$s</strong></li>',
		esc_html( $mm_labels[ $mm_key ] ),
		esc_attr( $mm_class ),
		esc_attr( $mm_key ),
		esc_html( $mm_m[ $mm_key ] ?? '—' )
	);
}
foreach ( $mm_custom as $mm_msg ) {
	echo '<li class="mm-ticker__item mm-ticker__item--msg">' . mm_icon( 'sparkles' ) . '<span>' . esc_html( $mm_msg ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
}
$mm_items = (string) ob_get_clean();
?>
<section id="mm-section-ticker" class="mm-ticker" data-mm-market aria-label="<?php esc_attr_e( 'Live Bitcoin network data', 'minemirth' ); ?>">
	<div class="mm-ticker__badge"><span class="mm-live-dot" aria-hidden="true"></span><?php esc_html_e( 'Live', 'minemirth' ); ?></div>
	<div class="mm-ticker__viewport">
		<div class="mm-ticker__track">
			<ul class="mm-ticker__list"><?php echo $mm_items; // phpcs:ignore WordPress.Security.EscapeOutput ?></ul>
			<ul class="mm-ticker__list" aria-hidden="true"><?php echo $mm_items; // phpcs:ignore WordPress.Security.EscapeOutput ?></ul>
		</div>
	</div>
</section>
