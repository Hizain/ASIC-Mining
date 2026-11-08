<?php
/**
 * Settings page: WooCommerce › Product page.
 *
 * @package MineMirthProductPage
 */

defined( 'ABSPATH' ) || exit;

function mmpp_cap(): string {
	return (string) apply_filters( 'mmpp_capability', 'manage_woocommerce' );
}

add_action(
	'admin_menu',
	static function (): void {
		add_submenu_page( 'woocommerce', __( 'Product page', 'mine-mirth-product-page' ), __( 'Product page', 'mine-mirth-product-page' ), mmpp_cap(), 'mmpp', 'mmpp_settings_page' );
	},
	60
);

add_filter(
	'plugin_action_links_' . plugin_basename( MMPP_FILE ),
	static function ( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=mmpp' ) ) . '">' . esc_html__( 'Settings', 'mine-mirth-product-page' ) . '</a>' );
		return $links;
	}
);

add_action(
	'admin_post_mmpp_save',
	static function (): void {
		if ( ! current_user_can( mmpp_cap() ) || ! check_admin_referer( 'mmpp_save' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-product-page' ), 403 );
		}
		$in = (array) wp_unslash( $_POST['mmpp'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field below.
		if ( ! empty( $_POST['mmpp_reset'] ) ) {
			delete_option( 'mmpp_settings' );
			wp_safe_redirect( admin_url( 'admin.php?page=mmpp&saved=reset' ) );
			exit;
		}
		$d = mmpp_defaults();
		$s = array();
		foreach ( mmpp_choices() as $k => $allowed ) {
			$v       = (string) ( $in[ $k ] ?? '' );
			$s[ $k ] = in_array( $v, $allowed, true ) ? $v : $d[ $k ];
		}
		foreach ( array( 'gallery', 'counter', 'zoom', 'lightbox', 'loop', 'desc', 'desc_heading', 'tables', 'clean', 'demote_h1', 'toc', 'collapse' ) as $k ) {
			$s[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
		}
		$s['thumbs_count'] = max( 3, min( 8, absint( $in['thumbs_count'] ?? 5 ) ) );
		$s['zoom_level']   = max( 1.5, min( 4, round( (float) ( $in['zoom_level'] ?? 2 ) * 2 ) / 2 ) );
		$s['autoplay']     = min( 30, absint( $in['autoplay'] ?? 0 ) );
		$s['toc_min']      = max( 2, min( 10, absint( $in['toc_min'] ?? 3 ) ) );
		$s['collapse_h']   = max( 240, min( 3000, absint( $in['collapse_h'] ?? 640 ) ) );
		$s['tab_title']    = sanitize_text_field( (string) ( $in['tab_title'] ?? '' ) );
		update_option( 'mmpp_settings', $s, true );
		wp_safe_redirect( admin_url( 'admin.php?page=mmpp&saved=1' ) );
		exit;
	}
);

/** A product to preview the settings on. */
function mmpp_sample_product_url(): string {
	$ids = wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'orderby' => 'date', 'order' => 'DESC', 'return' => 'ids' ) );
	return $ids ? (string) get_permalink( (int) $ids[0] ) : '';
}

function mmpp_settings_page(): void {
	if ( ! current_user_can( mmpp_cap() ) ) {
		return;
	}
	$s   = mmpp_settings( true );
	$sel = static function ( string $k, array $labels ) use ( $s ): string {
		$out = '<select name="mmpp[' . esc_attr( $k ) . ']">';
		foreach ( mmpp_choices()[ $k ] as $v ) {
			$out .= '<option value="' . esc_attr( $v ) . '"' . selected( (string) $s[ $k ], $v, false ) . '>' . esc_html( $labels[ $v ] ?? $v ) . '</option>';
		}
		return $out . '</select>';
	};
	$chk = static fn( string $k, string $label, string $help = '' ) => '<label class="mmpp-check"><input type="checkbox" name="mmpp[' . esc_attr( $k ) . ']" value="1" ' . checked( ! empty( $s[ $k ] ), true, false ) . '> <span>' . esc_html( $label ) . ( $help ? ' <i>' . esc_html( $help ) . '</i>' : '' ) . '</span></label>';
	$num = static fn( string $k, $min, $max, $step = 1 ) => '<input type="number" class="small-text" name="mmpp[' . esc_attr( $k ) . ']" value="' . esc_attr( (string) $s[ $k ] ) . '" min="' . esc_attr( (string) $min ) . '" max="' . esc_attr( (string) $max ) . '" step="' . esc_attr( (string) $step ) . '">';
	$preview = mmpp_sample_product_url();
	?>
	<div class="wrap mmpp-admin">
		<style>
			.mmpp-admin .mmpp-card { max-width: 980px; margin: 18px 0; padding: 4px 22px 10px; background: #fff; border: 1px solid #dcdcde; border-radius: 12px; }
			.mmpp-admin .mmpp-card h2 { margin: 18px 0 4px; }
			.mmpp-admin .mmpp-card > p.description { margin: 0 0 6px; }
			.mmpp-check { display: flex; align-items: flex-start; gap: 8px; margin: 0 0 8px; }
			.mmpp-check > input { margin-top: 2px; flex-shrink: 0; }
			.mmpp-check i, .mmpp-admin th i { font-style: normal; color: #646970; font-weight: 400; }
			.mmpp-inline { display: flex; flex-wrap: wrap; gap: 8px 18px; align-items: center; }
		</style>
		<h1><?php esc_html_e( 'Product page', 'mine-mirth-product-page' ); ?>
			<?php if ( $preview ) : ?>
				<a class="page-title-action" href="<?php echo esc_url( $preview ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View a product ↗', 'mine-mirth-product-page' ); ?></a>
			<?php endif; ?>
		</h1>
		<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php echo 'reset' === $_GET['saved'] ? esc_html__( 'Back to the recommended settings.', 'mine-mirth-product-page' ) : esc_html__( 'Saved. Reload a product page to see the change.', 'mine-mirth-product-page' ); // phpcs:ignore WordPress.Security.NonceVerification ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mmpp_save">
			<?php wp_nonce_field( 'mmpp_save' ); ?>

			<div class="mmpp-card">
				<h2><?php esc_html_e( 'Image gallery', 'mine-mirth-product-page' ); ?></h2>
				<p class="description"><?php esc_html_e( 'The main image with a thumbnail carousel under it. Visitors click a thumbnail or the arrows, swipe on phones, use the arrow keys, hover to zoom and open any image full screen. Images come from the product\'s "Product image" and "Product gallery" boxes.', 'mine-mirth-product-page' ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php esc_html_e( 'Gallery', 'mine-mirth-product-page' ); ?></th><td><?php echo $chk( 'gallery', __( 'Use this gallery on product pages', 'mine-mirth-product-page' ), __( '(untick to go back to WooCommerce\'s gallery)', 'mine-mirth-product-page' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Image shape', 'mine-mirth-product-page' ); ?></th><td class="mmpp-inline">
						<?php
						echo $sel( 'ratio', array( '1/1' => __( 'Square (1:1)', 'mine-mirth-product-page' ), '4/3' => __( 'Landscape 4:3', 'mine-mirth-product-page' ), '3/2' => __( 'Landscape 3:2', 'mine-mirth-product-page' ), '16/10' => __( 'Wide 16:10', 'mine-mirth-product-page' ), '16/9' => __( 'Wide 16:9', 'mine-mirth-product-page' ), '3/4' => __( 'Portrait 3:4', 'mine-mirth-product-page' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						echo $sel( 'fit', array( 'contain' => __( 'Show the whole image', 'mine-mirth-product-page' ), 'cover' => __( 'Fill the frame (may crop edges)', 'mine-mirth-product-page' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Frame', 'mine-mirth-product-page' ); ?></th><td class="mmpp-inline">
						<label><?php esc_html_e( 'Background', 'mine-mirth-product-page' ); ?> <?php echo $sel( 'background', array( 'glow' => __( 'Soft blue glow (theme)', 'mine-mirth-product-page' ), 'surface' => __( 'Plain card', 'mine-mirth-product-page' ), 'white' => __( 'White (for photos on white)', 'mine-mirth-product-page' ), 'none' => __( 'None', 'mine-mirth-product-page' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
						<label><?php esc_html_e( 'Corners', 'mine-mirth-product-page' ); ?> <?php echo $sel( 'radius', array( 'theme' => __( 'Rounded (theme)', 'mine-mirth-product-page' ), 'small' => __( 'Slightly rounded', 'mine-mirth-product-page' ), 'none' => __( 'Square', 'mine-mirth-product-page' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Thumbnails', 'mine-mirth-product-page' ); ?></th><td class="mmpp-inline">
						<?php echo $sel( 'thumbs', array( 'below' => __( 'Below the image', 'mine-mirth-product-page' ), 'left' => __( 'Left of the image (desktop)', 'mine-mirth-product-page' ), 'none' => __( 'Hidden', 'mine-mirth-product-page' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<label><?php esc_html_e( 'Visible at once', 'mine-mirth-product-page' ); ?> <?php echo $num( 'thumbs_count', 3, 8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
						<label><?php esc_html_e( 'On phones', 'mine-mirth-product-page' ); ?> <?php echo $sel( 'mobile_nav', array( 'thumbs' => __( 'Thumbnails', 'mine-mirth-product-page' ), 'dots' => __( 'Dots (more room)', 'mine-mirth-product-page' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Arrows on the image', 'mine-mirth-product-page' ); ?></th><td class="mmpp-inline">
						<?php echo $sel( 'arrows', array( 'hover' => __( 'Show on hover', 'mine-mirth-product-page' ), 'always' => __( 'Always show', 'mine-mirth-product-page' ), 'never' => __( 'Never', 'mine-mirth-product-page' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo $chk( 'counter', __( 'Image counter (2 / 6)', 'mine-mirth-product-page' ) ) . $chk( 'loop', __( 'After the last image, start again', 'mine-mirth-product-page' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Zoom', 'mine-mirth-product-page' ); ?></th><td class="mmpp-inline">
						<?php echo $chk( 'zoom', __( 'Zoom in when the mouse is over the image', 'mine-mirth-product-page' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<label><?php esc_html_e( 'Zoom', 'mine-mirth-product-page' ); ?> <?php echo $num( 'zoom_level', 1.5, 4, 0.5 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> ×</label>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Full screen', 'mine-mirth-product-page' ); ?></th><td><?php echo $chk( 'lightbox', __( 'Click an image to view it full screen (swipe, arrows, Esc to close)', 'mine-mirth-product-page' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Autoplay', 'mine-mirth-product-page' ); ?></th><td><label><?php esc_html_e( 'Next image every', 'mine-mirth-product-page' ); ?> <?php echo $num( 'autoplay', 0, 30 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'seconds (0 = off). Pauses while the visitor hovers and stops once they use the gallery.', 'mine-mirth-product-page' ); ?></label></td></tr>
				</table>
			</div>

			<div class="mmpp-card">
				<h2><?php esc_html_e( 'Description', 'mine-mirth-product-page' ); ?></h2>
				<p class="description"><?php esc_html_e( 'How the Description tab reads. Your product texts are not changed; this only changes how they are shown.', 'mine-mirth-product-page' ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php esc_html_e( 'Description styling', 'mine-mirth-product-page' ); ?></th><td><?php echo $chk( 'desc', __( 'Use the readable description design', 'mine-mirth-product-page' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Text', 'mine-mirth-product-page' ); ?></th><td class="mmpp-inline">
						<label><?php esc_html_e( 'Size', 'mine-mirth-product-page' ); ?> <?php echo $sel( 'text_size', array( 'sm' => __( 'Small', 'mine-mirth-product-page' ), 'md' => __( 'Medium', 'mine-mirth-product-page' ), 'lg' => __( 'Large', 'mine-mirth-product-page' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
						<label><?php esc_html_e( 'Line spacing', 'mine-mirth-product-page' ); ?> <?php echo $sel( 'line_height', array( '1.6' => __( 'Tight', 'mine-mirth-product-page' ), '1.75' => __( 'Comfortable', 'mine-mirth-product-page' ), '1.9' => __( 'Airy', 'mine-mirth-product-page' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
						<label><?php esc_html_e( 'Line length', 'mine-mirth-product-page' ); ?> <?php echo $sel( 'width', array( 'narrow' => __( 'Narrow', 'mine-mirth-product-page' ), 'comfortable' => __( 'Comfortable', 'mine-mirth-product-page' ), 'full' => __( 'Full width', 'mine-mirth-product-page' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Headings', 'mine-mirth-product-page' ); ?></th><td class="mmpp-inline">
						<label><?php esc_html_e( 'Size', 'mine-mirth-product-page' ); ?> <?php echo $sel( 'headings', array( 'compact' => __( 'Compact', 'mine-mirth-product-page' ), 'balanced' => __( 'Balanced', 'mine-mirth-product-page' ), 'large' => __( 'Large', 'mine-mirth-product-page' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
						<label><?php esc_html_e( 'Style', 'mine-mirth-product-page' ); ?> <?php echo $sel( 'heading_style', array( 'bar' => __( 'Blue bar on the left', 'mine-mirth-product-page' ), 'line' => __( 'Underlined', 'mine-mirth-product-page' ), 'plain' => __( 'Plain', 'mine-mirth-product-page' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
						<?php echo $chk( 'demote_h1', __( 'Show the biggest headings (H1) at section size', 'mine-mirth-product-page' ), __( '(also better for Google: the product name stays the only H1)', 'mine-mirth-product-page' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Lists and tables', 'mine-mirth-product-page' ); ?></th><td class="mmpp-inline">
						<label><?php esc_html_e( 'Bullets', 'mine-mirth-product-page' ); ?> <?php echo $sel( 'lists', array( 'check' => __( 'Check marks, numbered circles', 'mine-mirth-product-page' ), 'dot' => __( 'Blue dots', 'mine-mirth-product-page' ), 'plain' => __( 'Plain', 'mine-mirth-product-page' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
						<?php echo $chk( 'tables', __( 'Spec-sheet style tables (striped rows, labels in grey)', 'mine-mirth-product-page' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Pasted text', 'mine-mirth-product-page' ); ?></th><td><?php echo $chk( 'clean', __( 'Ignore fonts, sizes and colours pasted from other websites', 'mine-mirth-product-page' ), __( '(keeps bold, links, lists, tables and alignment)', 'mine-mirth-product-page' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Long descriptions', 'mine-mirth-product-page' ); ?></th><td>
						<?php echo $chk( 'toc', __( 'Show an "On this page" bar with links to the sections', 'mine-mirth-product-page' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<p class="mmpp-inline"><label><?php esc_html_e( 'when there are at least', 'mine-mirth-product-page' ); ?> <?php echo $num( 'toc_min', 2, 10 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'sections', 'mine-mirth-product-page' ); ?></label></p>
						<?php echo $chk( 'collapse', __( 'Shorten long descriptions with a "Show the full description" button', 'mine-mirth-product-page' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<p class="mmpp-inline"><label><?php esc_html_e( 'Show the first', 'mine-mirth-product-page' ); ?> <?php echo $num( 'collapse_h', 240, 3000, 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'pixels', 'mine-mirth-product-page' ); ?></label></p>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Tab', 'mine-mirth-product-page' ); ?></th><td>
						<p class="mmpp-inline"><label><?php esc_html_e( 'Tab name', 'mine-mirth-product-page' ); ?> <input type="text" name="mmpp[tab_title]" value="<?php echo esc_attr( (string) $s['tab_title'] ); ?>" placeholder="<?php esc_attr_e( 'Description', 'mine-mirth-product-page' ); ?>" class="regular-text" style="width:220px"></label></p>
						<?php echo $chk( 'desc_heading', __( 'Repeat the tab name as a heading above the text', 'mine-mirth-product-page' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</td></tr>
				</table>
			</div>

			<p class="submit">
				<?php submit_button( __( 'Save', 'mine-mirth-product-page' ), 'primary', 'submit', false ); ?>
				<button type="submit" name="mmpp_reset" value="1" class="button" onclick="return confirm('<?php echo esc_js( __( 'Go back to the recommended settings?', 'mine-mirth-product-page' ) ); ?>');"><?php esc_html_e( 'Reset to recommended', 'mine-mirth-product-page' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}
