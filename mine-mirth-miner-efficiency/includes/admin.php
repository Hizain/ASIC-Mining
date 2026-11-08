<?php
/**
 * Admin: WooCommerce › Miner efficiency (settings + a table of every miner),
 * and an "Efficiency" box on each product's edit screen.
 *
 * @package MineMirthMinerEfficiency
 */

defined( 'ABSPATH' ) || exit;

function mmeff_cap(): string {
	return (string) apply_filters( 'mmeff_capability', 'manage_woocommerce' );
}

add_action(
	'admin_menu',
	static function (): void {
		add_submenu_page( 'woocommerce', __( 'Miner efficiency', 'mine-mirth-miner-efficiency' ), __( 'Miner efficiency', 'mine-mirth-miner-efficiency' ), mmeff_cap(), 'mmeff', 'mmeff_page' );
	},
	61
);

add_filter(
	'plugin_action_links_' . plugin_basename( MMEFF_FILE ),
	static function ( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=mmeff' ) ) . '">' . esc_html__( 'Settings', 'mine-mirth-miner-efficiency' ) . '</a>' );
		return $links;
	}
);

/** The unit choices for a select box. */
function mmeff_unit_select( string $name, string $current, string $first_label ): string {
	$out = '<select name="' . esc_attr( $name ) . '"><option value="">' . esc_html( $first_label ) . '</option>';
	foreach ( array_keys( mmeff_units() ) as $u ) {
		$out .= '<option value="' . esc_attr( $u ) . '"' . selected( $current, $u, false ) . '>' . esc_html( $u ) . '</option>';
	}
	return $out . '</select>';
}

/** Same figure written differently ("23.0 J/TH" and "23 J/TH")? */
function mmeff_same( string $a, string $b ): bool {
	$n = static fn( string $v ) => preg_match( '/^([\d.,]+)\s*(\S+)$/', trim( $v ), $m ) ? array( round( (float) str_replace( ',', '', $m[1] ), 1 ), $m[2] ) : array( $v, '' );
	return $n( $a ) === $n( $b );
}

/** Save one product's efficiency fields from an array of posted values. */
function mmeff_save_product( int $id, array $row, bool $has_on = true ): void {
	if ( $has_on ) {
		update_post_meta( $id, '_mmeff_on', empty( $row['on'] ) ? 'no' : 'yes' );
	}
	$unit = sanitize_text_field( (string) ( $row['unit'] ?? '' ) );
	update_post_meta( $id, '_mmeff_unit', isset( mmeff_units()[ $unit ] ) ? $unit : '' );
	foreach ( array( 'hash' => '_mmeff_hash', 'watts' => '_mmeff_watts', 'text' => '_mmeff_text' ) as $k => $meta ) {
		$v = trim( sanitize_text_field( (string) ( $row[ $k ] ?? '' ) ) );
		if ( '' === $v ) {
			delete_post_meta( $id, $meta );
		} else {
			update_post_meta( $id, $meta, $v );
		}
	}
}

/* ------------------------------------------------------------------ Settings page */

add_action(
	'admin_post_mmeff_save',
	static function (): void {
		if ( ! current_user_can( mmeff_cap() ) || ! check_admin_referer( 'mmeff_save' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-miner-efficiency' ), 403 );
		}
		$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=mmeff' );
		if ( isset( $_POST['mmeff'] ) ) {
			$in = (array) wp_unslash( $_POST['mmeff'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized below.
			$s  = mmeff_settings( true );
			foreach ( array( 'enabled', 'auto', 'table', 'cards' ) as $k ) {
				$s[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
			}
			$s['decimals'] = in_array( (string) ( $in['decimals'] ?? 'auto' ), array( 'auto', '0', '1', '2', '3' ), true ) ? (string) $in['decimals'] : 'auto';
			foreach ( array_keys( mmeff_algorithms() ) as $algo ) {
				$u                    = (string) ( $in['units'][ $algo ] ?? '' );
				$s['units'][ $algo ] = 'listed' === $u || isset( mmeff_units()[ $u ] ) ? $u : mmeff_defaults()['units'][ $algo ];
			}
			update_option( 'mmeff_settings', $s, true );
		}
		$rows = (array) wp_unslash( $_POST['row'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in mmeff_save_product().
		foreach ( $rows as $id => $row ) {
			$id = absint( $id );
			if ( $id && 'product' === get_post_type( $id ) && current_user_can( 'edit_post', $id ) ) {
				mmeff_save_product( $id, (array) $row );
			}
		}
		mmeff_flush();
		wp_safe_redirect( add_query_arg( 'saved', 1, $back ) );
		exit;
	}
);

function mmeff_page(): void {
	if ( ! current_user_can( mmeff_cap() ) ) {
		return;
	}
	$s      = mmeff_settings( true );
	$all    = ! empty( $_GET['all'] ); // phpcs:ignore WordPress.Security.NonceVerification
	$paged  = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$per    = 50;
	$ids    = wc_get_products( array( 'limit' => -1, 'status' => array( 'publish', 'private', 'draft', 'pending' ), 'orderby' => 'title', 'order' => 'ASC', 'return' => 'ids' ) );
	$rows   = array();
	foreach ( $ids as $id ) {
		$p = wc_get_product( $id );
		if ( ! $p || $p->is_type( 'variation' ) ) {
			continue;
		}
		$info = mmeff_info( $p );
		if ( ! $all && $info['hs'] <= 0 && '' === $info['custom'] ) {
			continue; // Not a miner (no hashrate): hidden unless "Show all products".
		}
		$rows[] = array( $p, $info );
	}
	$total = count( $rows );
	$rows  = array_slice( $rows, ( $paged - 1 ) * $per, $per );
	$algos = mmeff_algorithms();
	?>
	<div class="wrap mmeff">
		<style>
			.mmeff .mmeff-card { max-width: 1180px; margin: 18px 0; padding: 4px 22px 14px; background: #fff; border: 1px solid #dcdcde; border-radius: 12px; }
			.mmeff .mmeff-card h2 { margin: 18px 0 6px; }
			.mmeff-check { display: flex; align-items: flex-start; gap: 8px; margin: 0 0 8px; }
			.mmeff-check > input { margin-top: 2px; }
			.mmeff-check i, .mmeff i.mmeff-muted { font-style: normal; color: #646970; }
			.mmeff-units { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 8px 18px; }
			.mmeff-units label { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
			.mmeff-table { width: 100%; border-collapse: collapse; }
			.mmeff-table th, .mmeff-table td { padding: 9px 8px; border-bottom: 1px solid #f0f0f1; text-align: left; vertical-align: middle; }
			.mmeff-table thead th { font-weight: 600; border-bottom: 2px solid #dcdcde; }
			.mmeff-table input[type=text] { width: 100%; min-width: 80px; }
			.mmeff-table select { max-width: 150px; }
			.mmeff-was { color: #b32d2e; text-decoration: line-through; white-space: nowrap; }
			.mmeff-was.is-same { color: #646970; text-decoration: none; }
			.mmeff-now { font-weight: 600; color: #007017; white-space: nowrap; }
			.mmeff-off td:not(:first-child) { opacity: .55; }
			.mmeff-tools { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; margin: 10px 0; }
			.mmeff-tools form { display: flex; gap: 6px; align-items: center; }
		</style>
		<h1><?php esc_html_e( 'Miner efficiency', 'mine-mirth-miner-efficiency' ); ?></h1>
		<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved. The shop and product pages now show these values.', 'mine-mirth-miner-efficiency' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mmeff_save">
			<?php wp_nonce_field( 'mmeff_save' ); ?>

			<div class="mmeff-card">
				<h2><?php esc_html_e( 'How efficiency is shown', 'mine-mirth-miner-efficiency' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Efficiency is the miner\'s power draw divided by its hashrate. Each algorithm is shown in its own unit, so a Scrypt miner reads 0.21 J/MH instead of 210,000 J/TH.', 'mine-mirth-miner-efficiency' ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php esc_html_e( 'Correct efficiency', 'mine-mirth-miner-efficiency' ); ?></th><td>
						<label class="mmeff-check"><input type="checkbox" name="mmeff[enabled]" value="1" <?php checked( $s['enabled'] ); ?>> <span><?php esc_html_e( 'Show corrected efficiency in the shop', 'mine-mirth-miner-efficiency' ); ?> <i><?php esc_html_e( '(untick to show the theme\'s own figures everywhere)', 'mine-mirth-miner-efficiency' ); ?></i></span></label>
						<label class="mmeff-check"><input type="checkbox" name="mmeff[auto]" value="1" <?php checked( $s['auto'] ); ?>> <span><?php esc_html_e( 'Switch it on for new products automatically', 'mine-mirth-miner-efficiency' ); ?></span></label>
						<label class="mmeff-check"><input type="checkbox" name="mmeff[cards]" value="1" <?php checked( $s['cards'] ); ?>> <span><?php esc_html_e( 'Show efficiency on shop cards', 'mine-mirth-miner-efficiency' ); ?> <i><?php esc_html_e( '(cards have room for three specs: efficiency then takes the place of the algorithm)', 'mine-mirth-miner-efficiency' ); ?></i></span></label>
						<label class="mmeff-check"><input type="checkbox" name="mmeff[table]" value="1" <?php checked( $s['table'] ); ?>> <span><?php esc_html_e( 'Also list it in the product\'s "Additional information" tab', 'mine-mirth-miner-efficiency' ); ?></span></label>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Unit for each algorithm', 'mine-mirth-miner-efficiency' ); ?></th><td>
						<div class="mmeff-units">
							<?php foreach ( $algos as $k => $label ) : ?>
								<label><span><?php echo esc_html( $label ); ?></span>
									<select name="mmeff[units][<?php echo esc_attr( $k ); ?>]">
										<option value="listed" <?php selected( $s['units'][ $k ], 'listed' ); ?>><?php esc_html_e( 'As the hashrate is listed', 'mine-mirth-miner-efficiency' ); ?></option>
										<?php foreach ( array_keys( mmeff_units() ) as $u ) : ?>
											<option value="<?php echo esc_attr( $u ); ?>" <?php selected( $s['units'][ $k ], $u ); ?>><?php echo esc_html( $u ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
							<?php endforeach; ?>
						</div>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Decimals', 'mine-mirth-miner-efficiency' ); ?></th><td>
						<select name="mmeff[decimals]">
							<?php foreach ( array( 'auto' => __( 'Automatic (13.5, 150, 0.21)', 'mine-mirth-miner-efficiency' ), '0' => '0', '1' => '1', '2' => '2', '3' => '3' ) as $k => $label ) : ?>
								<option value="<?php echo esc_attr( (string) $k ); ?>" <?php selected( (string) $s['decimals'], (string) $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td></tr>
				</table>
			</div>

			<div class="mmeff-card">
				<h2><?php esc_html_e( 'Your miners', 'mine-mirth-miner-efficiency' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Tick or untick each product. Leave the boxes empty to use what the product lists; fill one in to override it for efficiency only (the product\'s own hashrate and power stay as they are). "Exact text" replaces the calculation, e.g. 13.5 J/TH.', 'mine-mirth-miner-efficiency' ); ?></p>
				<div class="mmeff-tools">
					<span>
						<button type="button" class="button" onclick="document.querySelectorAll('.mmeff-on').forEach(function(c){c.checked=true;})"><?php esc_html_e( 'Tick all on this page', 'mine-mirth-miner-efficiency' ); ?></button>
						<button type="button" class="button" onclick="document.querySelectorAll('.mmeff-on').forEach(function(c){c.checked=false;})"><?php esc_html_e( 'Untick all', 'mine-mirth-miner-efficiency' ); ?></button>
					</span>
					<span>
						<?php
						/* translators: %d: number of products */
						echo esc_html( sprintf( _n( '%d product', '%d products', $total, 'mine-mirth-miner-efficiency' ), $total ) );
						?>
						· <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'mmeff', 'all' => $all ? false : 1, 'paged' => false ), admin_url( 'admin.php' ) ) ); ?>"><?php echo $all ? esc_html__( 'Show miners only', 'mine-mirth-miner-efficiency' ) : esc_html__( 'Show all products', 'mine-mirth-miner-efficiency' ); ?></a>
					</span>
				</div>
				<table class="mmeff-table">
					<thead><tr>
						<th style="width:34px"><?php esc_html_e( 'On', 'mine-mirth-miner-efficiency' ); ?></th>
						<th><?php esc_html_e( 'Product', 'mine-mirth-miner-efficiency' ); ?></th>
						<th><?php esc_html_e( 'Hashrate', 'mine-mirth-miner-efficiency' ); ?></th>
						<th><?php esc_html_e( 'Power', 'mine-mirth-miner-efficiency' ); ?></th>
						<th><?php esc_html_e( 'Unit', 'mine-mirth-miner-efficiency' ); ?></th>
						<th><?php esc_html_e( 'Exact text', 'mine-mirth-miner-efficiency' ); ?></th>
						<th><?php esc_html_e( 'Theme showed', 'mine-mirth-miner-efficiency' ); ?></th>
						<th><?php esc_html_e( 'Shows now', 'mine-mirth-miner-efficiency' ); ?></th>
					</tr></thead>
					<tbody>
						<?php if ( ! $rows ) : ?>
							<tr><td colspan="8"><?php esc_html_e( 'No miners found. Products need a "Hashrate" and a "Power" attribute (or fill them in here).', 'mine-mirth-miner-efficiency' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $rows as list( $p, $info ) ) : ?>
							<?php
							$id   = (int) $p->get_id();
							$name = 'row[' . $id . ']';
							?>
							<tr class="<?php echo $info['on'] ? '' : 'mmeff-off'; ?>">
								<td><input type="checkbox" class="mmeff-on" name="<?php echo esc_attr( $name ); ?>[on]" value="1" <?php checked( $info['on'] ); ?> aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product */ __( 'Correct efficiency for %s', 'mine-mirth-miner-efficiency' ), $p->get_name() ) ); ?>"></td>
								<td><a href="<?php echo esc_url( (string) get_edit_post_link( $id ) ); ?>"><strong><?php echo esc_html( $p->get_name() ); ?></strong></a><br><i class="mmeff-muted"><?php echo esc_html( $algos[ $info['algo'] ] ?? '' ); ?></i></td>
								<td><input type="text" name="<?php echo esc_attr( $name ); ?>[hash]" value="<?php echo esc_attr( (string) get_post_meta( $id, '_mmeff_hash', true ) ); ?>" placeholder="<?php echo esc_attr( $info['det_hash'] ? $info['det_hash'] : '16 GH/s' ); ?>"></td>
								<td><input type="text" name="<?php echo esc_attr( $name ); ?>[watts]" value="<?php echo esc_attr( (string) get_post_meta( $id, '_mmeff_watts', true ) ); ?>" placeholder="<?php echo esc_attr( $info['det_watts'] ? $info['det_watts'] : '3360 W' ); ?>"></td>
								<td><?php echo mmeff_unit_select( $name . '[unit]', (string) get_post_meta( $id, '_mmeff_unit', true ), sprintf( /* translators: %s: unit */ __( 'Default (%s)', 'mine-mirth-miner-efficiency' ), 'listed' === $s['units'][ $info['algo'] ] ? __( 'as listed', 'mine-mirth-miner-efficiency' ) : $s['units'][ $info['algo'] ] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td><input type="text" name="<?php echo esc_attr( $name ); ?>[text]" value="<?php echo esc_attr( $info['custom'] ); ?>" placeholder="<?php esc_attr_e( 'optional', 'mine-mirth-miner-efficiency' ); ?>"></td>
								<td><span class="mmeff-was<?php echo mmeff_same( $info['theme'], $info['text'] ) ? ' is-same' : ''; ?>"><?php echo esc_html( $info['theme'] ? $info['theme'] : '—' ); ?></span></td>
								<td><span class="mmeff-now"><?php echo esc_html( $info['on'] && $info['text'] ? $info['text'] : '—' ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php
				$pages = (int) ceil( $total / $per );
				if ( $pages > 1 ) {
					echo '<p class="mmeff-tools">' . wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => $pages ) ) ) . '</p>';
				}
				?>
			</div>
			<?php submit_button( __( 'Save', 'mine-mirth-miner-efficiency' ) ); ?>
		</form>
	</div>
	<?php
}

/* ------------------------------------------------------------------ Product edit screen */

add_action(
	'add_meta_boxes_product',
	static function (): void {
		add_meta_box( 'mmeff_box', __( 'Efficiency', 'mine-mirth-miner-efficiency' ), 'mmeff_box', 'product', 'side', 'default' );
	}
);

function mmeff_box( WP_Post $post ): void {
	$p = wc_get_product( $post->ID );
	if ( ! $p ) {
		return;
	}
	wp_nonce_field( 'mmeff_box', 'mmeff_box_nonce' );
	$info = mmeff_info( $p );
	$s    = mmeff_settings();
	$def  = 'listed' === ( $s['units'][ $info['algo'] ] ?? 'listed' ) ? __( 'as listed', 'mine-mirth-miner-efficiency' ) : $s['units'][ $info['algo'] ];
	?>
	<p><label><input type="checkbox" name="mmeff_row[on]" value="1" <?php checked( $info['on'] ); ?>> <?php esc_html_e( 'Show the corrected efficiency', 'mine-mirth-miner-efficiency' ); ?></label></p>
	<p>
		<?php if ( $info['on'] && $info['text'] ) : ?>
			<?php esc_html_e( 'Shows:', 'mine-mirth-miner-efficiency' ); ?> <strong style="color:#007017"><?php echo esc_html( $info['text'] ); ?></strong>
			<?php if ( $info['theme'] && ! mmeff_same( $info['theme'], $info['text'] ) ) : ?>
				<br><span style="color:#646970"><?php esc_html_e( 'Instead of:', 'mine-mirth-miner-efficiency' ); ?> <s><?php echo esc_html( $info['theme'] ); ?></s></span>
			<?php endif; ?>
		<?php elseif ( $info['hs'] <= 0 ) : ?>
			<span style="color:#646970"><?php esc_html_e( 'No hashrate found: add a "Hashrate" attribute or fill it in below.', 'mine-mirth-miner-efficiency' ); ?></span>
		<?php endif; ?>
	</p>
	<p><label><?php esc_html_e( 'Hashrate', 'mine-mirth-miner-efficiency' ); ?><br><input type="text" name="mmeff_row[hash]" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, '_mmeff_hash', true ) ); ?>" placeholder="<?php echo esc_attr( $info['det_hash'] ? $info['det_hash'] : '16 GH/s' ); ?>" style="width:100%"></label></p>
	<p><label><?php esc_html_e( 'Power', 'mine-mirth-miner-efficiency' ); ?><br><input type="text" name="mmeff_row[watts]" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, '_mmeff_watts', true ) ); ?>" placeholder="<?php echo esc_attr( $info['det_watts'] ? $info['det_watts'] : '3360 W' ); ?>" style="width:100%"></label></p>
	<p><label><?php esc_html_e( 'Unit', 'mine-mirth-miner-efficiency' ); ?><br><?php echo mmeff_unit_select( 'mmeff_row[unit]', (string) get_post_meta( $post->ID, '_mmeff_unit', true ), sprintf( /* translators: %s: unit */ __( 'Default (%s)', 'mine-mirth-miner-efficiency' ), $def ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label></p>
	<p><label><?php esc_html_e( 'Exact text (optional)', 'mine-mirth-miner-efficiency' ); ?><br><input type="text" name="mmeff_row[text]" value="<?php echo esc_attr( $info['custom'] ); ?>" placeholder="13.5 J/TH" style="width:100%"></label></p>
	<p class="description"><?php esc_html_e( 'Empty boxes use the product\'s Hashrate and Power attributes. These overrides are for efficiency only.', 'mine-mirth-miner-efficiency' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=mmeff' ) ); ?>"><?php esc_html_e( 'All miners', 'mine-mirth-miner-efficiency' ); ?></a></p>
	<?php
}

add_action(
	'save_post_product',
	static function ( int $post_id ): void {
		if ( ! isset( $_POST['mmeff_box_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mmeff_box_nonce'] ) ), 'mmeff_box' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		mmeff_save_product( $post_id, (array) wp_unslash( $_POST['mmeff_row'] ?? array() ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in mmeff_save_product().
		mmeff_flush();
	},
	20
);
