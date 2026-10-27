<?php
/**
 * Checkout progress steps.
 *
 * @package MineMirth
 *
 * @var array $args current: cart|details|payment|done
 */

defined( 'ABSPATH' ) || exit;

$mm_current = $args['current'] ?? 'details';
$mm_steps   = array(
	'cart'    => array( __( 'Cart', 'minemirth' ), function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '' ),
	'details' => array( __( 'Details', 'minemirth' ), '' ),
	'payment' => array( __( 'Payment', 'minemirth' ), '' ),
	'done'    => array( __( 'Confirmation', 'minemirth' ), '' ),
);
$mm_keys    = array_keys( $mm_steps );
$mm_index   = (int) array_search( $mm_current, $mm_keys, true );
?>
<ol class="mm-steps" data-mm-steps aria-label="<?php esc_attr_e( 'Checkout progress', 'minemirth' ); ?>">
	<?php foreach ( $mm_keys as $mm_i => $mm_key ) : ?>
		<?php
		list( $mm_label, $mm_link ) = $mm_steps[ $mm_key ];
		$mm_state                    = $mm_i < $mm_index ? 'is-done' : ( $mm_i === $mm_index ? 'is-current' : '' );
		?>
		<li class="mm-step <?php echo esc_attr( $mm_state ); ?>" data-mm-step="<?php echo esc_attr( $mm_key ); ?>"<?php echo $mm_i === $mm_index ? ' aria-current="step"' : ''; ?>>
			<span class="mm-step__dot" aria-hidden="true"><span class="mm-step__num"><?php echo esc_html( (string) ( $mm_i + 1 ) ); ?></span><?php echo mm_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<?php if ( $mm_link && $mm_i < $mm_index ) : ?>
				<a class="mm-step__label" href="<?php echo esc_url( $mm_link ); ?>"><?php echo esc_html( $mm_label ); ?></a>
			<?php else : ?>
				<span class="mm-step__label"><?php echo esc_html( $mm_label ); ?></span>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ol>
