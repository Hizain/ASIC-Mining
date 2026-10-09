<?php
/**
 * Manufacturer wordmarks (link to store search or a custom URL).
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_brands = array();
foreach ( mm_lines( mm_opt( 'brands_list' ) ) as $mm_line ) {
	$mm_parts = array_map( 'trim', explode( '|', $mm_line, 2 ) );
	$mm_url   = isset( $mm_parts[1] ) && '' !== $mm_parts[1] ? mm_url( $mm_parts[1] ) : add_query_arg(
		array_filter(
			array(
				's'         => $mm_parts[0],
				'post_type' => mm_is_woo() ? 'product' : '',
			)
		),
		home_url( '/' )
	);
	$mm_brands[] = array( $mm_parts[0], $mm_url );
}
if ( ! $mm_brands ) {
	return;
}
?>
<section id="mm-section-brands" class="mm-section mm-section--brands">
	<div class="mm-container">
		<?php mm_section_heading( 'brands', array( 'align' => 'center' ) ); ?>
	</div>
	<div class="mm-brands" data-mm-reveal>
		<div class="mm-brands__track">
			<?php for ( $mm_pass = 0; $mm_pass < 2; $mm_pass++ ) : ?>
				<ul class="mm-brands__list"<?php echo $mm_pass ? ' aria-hidden="true"' : ''; ?>>
					<?php foreach ( $mm_brands as $mm_brand ) : ?>
						<li><a href="<?php echo esc_url( $mm_brand[1] ); ?>"<?php echo $mm_pass ? ' tabindex="-1"' : ''; ?>><?php echo esc_html( $mm_brand[0] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endfor; ?>
		</div>
	</div>
</section>
