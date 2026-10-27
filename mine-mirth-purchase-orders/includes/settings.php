<?php
/**
 * Admin: settings (company details on the PDF, logo, colours, numbering, default texts, statuses).
 *
 * @package MineMirthPurchaseOrders
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_post_mmpo_settings',
	static function (): void {
		if ( ! current_user_can( mmpo_cap() ) || ! check_admin_referer( 'mmpo_settings' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-purchase-orders' ), 403 );
		}
		$in   = wp_unslash( $_POST['mmpo'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cleaned below.
		$in   = is_array( $in ) ? $in : array();
		$def  = mmpo_default_settings();
		$text = static fn( string $k ) => sanitize_text_field( (string) ( $in[ $k ] ?? '' ) );
		$area = static fn( string $k ) => sanitize_textarea_field( (string) ( $in[ $k ] ?? '' ) );
		$hex  = static fn( string $k ) => sanitize_hex_color( (string) ( $in[ $k ] ?? '' ) ) ? (string) $in[ $k ] : $def[ $k ];
		$out  = array(
			'company'       => $text( 'company' ),
			'address'       => $area( 'address' ),
			'email'         => sanitize_email( (string) ( $in['email'] ?? '' ) ),
			'phone'         => $text( 'phone' ),
			'website'       => $text( 'website' ),
			'logo'          => in_array( $in['logo'] ?? '', array( 'mark', 'site', 'custom' ), true ) ? (string) $in['logo'] : 'mark',
			'logo_id'       => absint( $in['logo_id'] ?? 0 ),
			'header'        => 'light' === ( $in['header'] ?? '' ) ? 'light' : 'dark',
			'primary'       => $hex( 'primary' ),
			'accent'        => $hex( 'accent' ),
			'highlight'     => $hex( 'highlight' ),
			'paper'         => 'a4' === ( $in['paper'] ?? '' ) ? 'a4' : 'letter',
			'prefix'        => preg_replace( '/[^A-Za-z0-9\-_\/#.]/', '', (string) ( $in['prefix'] ?? '' ) ),
			'digits'        => max( 1, min( 8, absint( $in['digits'] ?? 4 ) ) ),
			'doc'           => isset( mmpo_doc_types()[ $in['doc'] ?? '' ] ) ? (string) $in['doc'] : 'po',
			'currency'      => strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) ( $in['currency'] ?? 'USD' ) ), 0, 5 ) ),
			'due_days'      => min( 365, absint( $in['due_days'] ?? 0 ) ),
			'payment_terms' => $text( 'payment_terms' ),
			'payment'       => $area( 'payment' ),
			'terms'         => $area( 'terms' ),
			'footer'        => $text( 'footer' ),
			'statuses'      => array(),
		);
		if ( '' === $out['currency'] ) {
			$out['currency'] = 'USD';
		}
		foreach ( (array) ( $in['statuses'] ?? array() ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = sanitize_text_field( (string) ( $row['label'] ?? '' ) );
			if ( '' === $label ) {
				continue;
			}
			$key = sanitize_key( (string) ( $row['key'] ?? '' ) );
			$key = '' !== $key ? $key : sanitize_key( sanitize_title( $label ) );
			if ( '' === $key || isset( $out['statuses'][ $key ] ) ) {
				continue;
			}
			$out['statuses'][ $key ] = array(
				'label' => $label,
				'color' => sanitize_hex_color( (string) ( $row['color'] ?? '' ) ) ? (string) $row['color'] : '#64748b',
				'stamp' => sanitize_text_field( (string) ( $row['stamp'] ?? '' ) ),
			);
		}
		if ( ! $out['statuses'] ) {
			$out['statuses'] = mmpo_default_statuses();
		}
		$next = absint( $_POST['next_number'] ?? 0 );
		if ( $next ) {
			update_option( 'mmpo_next_number', $next, false );
		}
		update_option( 'mmpo_settings', $out, false );
		wp_safe_redirect( add_query_arg( array( 'page' => 'mmpo-settings', 'msg' => 'saved' ), admin_url( 'admin.php' ) ) );
		exit;
	}
);

/**
 * A sample purchase order to preview the PDF design from Settings.
 */
add_action(
	'admin_post_mmpo_sample',
	static function (): void {
		if ( ! current_user_can( mmpo_cap() ) || ! check_admin_referer( 'mmpo_sample' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-purchase-orders' ), 403 );
		}
		$d           = mmpo_blank();
		$d['number'] = mmpo_peek_number();
		$d['ship']   = array_merge(
			$d['ship'],
			array(
				'name'      => __( 'Sample Customer', 'mine-mirth-purchase-orders' ),
				'company'   => __( 'Sample Mining Co.', 'mine-mirth-purchase-orders' ),
				'address_1' => __( '100 Example Street', 'mine-mirth-purchase-orders' ),
				'city'      => __( 'Example City', 'mine-mirth-purchase-orders' ),
				'state'     => 'TX',
				'postcode'  => '00000',
				'country'   => 'US',
				'email'     => 'customer@example.com',
			)
		);
		$d['items']    = array(
			array( 'product_id' => 0, 'name' => __( 'Sample ASIC miner', 'mine-mirth-purchase-orders' ), 'sku' => 'SAMPLE-01', 'desc' => __( 'This is a preview of your PDF design, not a real order.', 'mine-mirth-purchase-orders' ), 'qty' => 2, 'price' => 3000 ),
			array( 'product_id' => 0, 'name' => __( 'Power supply', 'mine-mirth-purchase-orders' ), 'sku' => '', 'desc' => '', 'qty' => 2, 'price' => 150 ),
		);
		$d['shipping']  = array( 'label' => __( 'Shipping', 'mine-mirth-purchase-orders' ), 'amount' => 120 );
		$d['taxes']     = array( array( 'label' => __( 'Sales tax', 'mine-mirth-purchase-orders' ), 'type' => 'percent', 'value' => 8 ) );
		$d['status']    = 'pending';
		if ( '' !== mmpo_pdf_problem() ) {
			wp_die( esc_html( mmpo_pdf_problem() ) );
		}
		$pdf = mmpo_pdf( $d );
		while ( ob_get_level() ) {
			ob_end_clean();
		}
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="Mine-Mirth-sample.pdf"' );
		header( 'Content-Length: ' . strlen( $pdf ) );
		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput -- binary PDF.
		exit;
	}
);

function mmpo_settings_page(): void {
	if ( ! current_user_can( mmpo_cap() ) ) {
		return;
	}
	$s      = mmpo_settings();
	$logo   = (int) $s['logo_id'] ? wp_get_attachment_image_url( (int) $s['logo_id'], 'medium' ) : '';
	$theme  = array(
		'address' => (string) mmpo_theme_opt( 'contact_address', '' ),
		'email'   => (string) mmpo_theme_opt( 'contact_email', '' ),
		'phone'   => (string) mmpo_theme_opt( 'contact_phone', '' ),
	);
	$name   = static fn( string $k ) => 'mmpo[' . $k . ']';
	$val    = static fn( string $k ) => esc_attr( (string) ( $s[ $k ] ?? '' ) );
	$site   = (int) get_theme_mod( 'custom_logo' ) || (int) mmpo_theme_opt( 'logo_dark', 0 );
	?>
	<div class="wrap mmpo-admin mmpo-settings">
		<h1 class="mmpo-title"><?php esc_html_e( 'Purchase order settings', 'mine-mirth-purchase-orders' ); ?></h1>
		<p class="mmpo-lead"><?php esc_html_e( 'What every purchase order PDF shows: your company details at the top, your logo and colours, numbering and the default texts. Changes apply to new PDFs, including ones you download again for older purchase orders.', 'mine-mirth-purchase-orders' ); ?></p>
		<?php
		if ( 'saved' === sanitize_key( wp_unslash( $_GET['msg'] ?? '' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'mine-mirth-purchase-orders' ) . ' <a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mmpo_sample' ), 'mmpo_sample' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Preview a sample PDF', 'mine-mirth-purchase-orders' ) . '</a></p></div>';
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mmpo_settings">
			<?php wp_nonce_field( 'mmpo_settings' ); ?>
			<div class="mmpo-layout mmpo-layout--settings">
				<div class="mmpo-main">
					<section class="mmpo-card">
						<header class="mmpo-card__head">
							<h2><?php esc_html_e( 'Company details on the PDF', 'mine-mirth-purchase-orders' ); ?></h2>
							<p class="mmpo-sub"><?php esc_html_e( 'Printed at the top of every purchase order. There is no ship-from address: only these details.', 'mine-mirth-purchase-orders' ); ?></p>
						</header>
						<div class="mmpo-fields">
							<label class="mmpo-f"><span><?php esc_html_e( 'Company name', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" name="<?php echo esc_attr( $name( 'company' ) ); ?>" value="<?php echo $val( 'company' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
							<label class="mmpo-f"><span><?php esc_html_e( 'Oregon address', 'mine-mirth-purchase-orders' ); ?> <i><?php esc_html_e( 'one line per row', 'mine-mirth-purchase-orders' ); ?></i></span>
								<textarea class="mmpo-input" rows="3" name="<?php echo esc_attr( $name( 'address' ) ); ?>" placeholder="<?php esc_attr_e( "Street address, suite\nCity, OR ZIP", 'mine-mirth-purchase-orders' ); ?>"><?php echo esc_textarea( (string) $s['address'] ); ?></textarea>
								<?php if ( '' !== $theme['address'] && $theme['address'] !== $s['address'] ) : ?>
									<button type="button" class="button-link" data-mmpo-fill="<?php echo esc_attr( $theme['address'] ); ?>"><?php esc_html_e( 'Use the address from your website’s Contact settings', 'mine-mirth-purchase-orders' ); ?></button>
								<?php endif; ?>
							</label>
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Email', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" type="email" name="<?php echo esc_attr( $name( 'email' ) ); ?>" value="<?php echo $val( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>">
								<?php if ( '' !== $theme['email'] && $theme['email'] !== $s['email'] ) : ?>
									<button type="button" class="button-link" data-mmpo-fill="<?php echo esc_attr( $theme['email'] ); ?>"><?php echo esc_html( sprintf( /* translators: %s: email */ __( 'Use %s', 'mine-mirth-purchase-orders' ), $theme['email'] ) ); ?></button>
								<?php endif; ?>
							</label>
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Phone', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" name="<?php echo esc_attr( $name( 'phone' ) ); ?>" value="<?php echo $val( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" placeholder="+1 (503) …">
								<?php if ( '' !== $theme['phone'] && $theme['phone'] !== $s['phone'] ) : ?>
									<button type="button" class="button-link" data-mmpo-fill="<?php echo esc_attr( $theme['phone'] ); ?>"><?php echo esc_html( sprintf( /* translators: %s: phone */ __( 'Use %s', 'mine-mirth-purchase-orders' ), $theme['phone'] ) ); ?></button>
								<?php endif; ?>
							</label>
							<label class="mmpo-f"><span><?php esc_html_e( 'Website', 'mine-mirth-purchase-orders' ); ?> <i><?php esc_html_e( 'optional, leave empty to show only address, email and phone', 'mine-mirth-purchase-orders' ); ?></i></span><input class="mmpo-input" name="<?php echo esc_attr( $name( 'website' ) ); ?>" value="<?php echo $val( 'website' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" placeholder="minemirth.com"></label>
						</div>
					</section>

					<section class="mmpo-card">
						<header class="mmpo-card__head"><h2><?php esc_html_e( 'Branding', 'mine-mirth-purchase-orders' ); ?></h2></header>
						<div class="mmpo-fields">
							<fieldset class="mmpo-f">
								<span><?php esc_html_e( 'Logo', 'mine-mirth-purchase-orders' ); ?></span>
								<label class="mmpo-check"><input type="radio" name="<?php echo esc_attr( $name( 'logo' ) ); ?>" value="mark" <?php checked( $s['logo'], 'mark' ); ?>> <?php esc_html_e( 'Mine Mirth mark and name, like the website header', 'mine-mirth-purchase-orders' ); ?></label>
								<label class="mmpo-check"><input type="radio" name="<?php echo esc_attr( $name( 'logo' ) ); ?>" value="site" <?php checked( $s['logo'], 'site' ); ?> <?php disabled( ! $site ); ?>> <?php esc_html_e( 'My website logo (the dark-mode version on the dark header)', 'mine-mirth-purchase-orders' ); ?><?php echo $site ? '' : ' <i>(' . esc_html__( 'none uploaded', 'mine-mirth-purchase-orders' ) . ')</i>'; ?></label>
								<label class="mmpo-check"><input type="radio" name="<?php echo esc_attr( $name( 'logo' ) ); ?>" value="custom" <?php checked( $s['logo'], 'custom' ); ?> data-mmpo-logo-custom> <?php esc_html_e( 'A logo just for purchase orders (PNG or JPG)', 'mine-mirth-purchase-orders' ); ?></label>
								<div class="mmpo-logo" data-mmpo-logo>
									<input type="hidden" name="<?php echo esc_attr( $name( 'logo_id' ) ); ?>" value="<?php echo (int) $s['logo_id']; ?>" data-mmpo-logo-id>
									<span class="mmpo-logo__img" data-mmpo-logo-img><?php echo $logo ? '<img src="' . esc_url( $logo ) . '" alt="">' : ''; ?></span>
									<button type="button" class="button" data-mmpo-logo-pick><?php esc_html_e( 'Choose logo', 'mine-mirth-purchase-orders' ); ?></button>
									<button type="button" class="button-link" data-mmpo-logo-clear <?php echo $logo ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'mine-mirth-purchase-orders' ); ?></button>
								</div>
							</fieldset>
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Header', 'mine-mirth-purchase-orders' ); ?></span>
								<select class="mmpo-input" name="<?php echo esc_attr( $name( 'header' ) ); ?>">
									<option value="dark" <?php selected( $s['header'], 'dark' ); ?>><?php esc_html_e( 'Dark, like the website', 'mine-mirth-purchase-orders' ); ?></option>
									<option value="light" <?php selected( $s['header'], 'light' ); ?>><?php esc_html_e( 'Light, saves printer ink', 'mine-mirth-purchase-orders' ); ?></option>
								</select>
							</label>
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Paper size', 'mine-mirth-purchase-orders' ); ?></span>
								<select class="mmpo-input" name="<?php echo esc_attr( $name( 'paper' ) ); ?>">
									<option value="letter" <?php selected( $s['paper'], 'letter' ); ?>><?php esc_html_e( 'US Letter', 'mine-mirth-purchase-orders' ); ?></option>
									<option value="a4" <?php selected( $s['paper'], 'a4' ); ?>>A4</option>
								</select>
							</label>
							<div class="mmpo-f mmpo-colors">
								<span><?php esc_html_e( 'Colours (from your website)', 'mine-mirth-purchase-orders' ); ?></span>
								<?php foreach ( array( 'highlight' => __( 'Highlight', 'mine-mirth-purchase-orders' ), 'primary' => __( 'Brand blue', 'mine-mirth-purchase-orders' ), 'accent' => __( 'Gradient partner', 'mine-mirth-purchase-orders' ) ) as $k => $label ) : ?>
									<label><input type="color" name="<?php echo esc_attr( $name( $k ) ); ?>" value="<?php echo $val( $k ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"> <?php echo esc_html( $label ); ?></label>
								<?php endforeach; ?>
							</div>
							<label class="mmpo-f"><span><?php esc_html_e( 'Footer line', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" name="<?php echo esc_attr( $name( 'footer' ) ); ?>" value="<?php echo $val( 'footer' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
						</div>
					</section>

					<section class="mmpo-card">
						<header class="mmpo-card__head">
							<h2><?php esc_html_e( 'Statuses', 'mine-mirth-purchase-orders' ); ?></h2>
							<p class="mmpo-sub"><?php esc_html_e( 'Rename them, change colours, or add your own. A stamp prints a large mark on the PDF, for example PAID.', 'mine-mirth-purchase-orders' ); ?></p>
						</header>
						<div class="mmpo-statuslist" data-mmpo-statuses>
							<div class="mmpo-statuslist__head"><span><?php esc_html_e( 'Colour', 'mine-mirth-purchase-orders' ); ?></span><span><?php esc_html_e( 'Name', 'mine-mirth-purchase-orders' ); ?></span><span><?php esc_html_e( 'Stamp on the PDF', 'mine-mirth-purchase-orders' ); ?></span><span></span></div>
							<?php
							$i = 0;
							foreach ( mmpo_statuses() as $key => $st ) :
								?>
								<div class="mmpo-statusrow">
									<input type="hidden" name="mmpo[statuses][<?php echo (int) $i; ?>][key]" value="<?php echo esc_attr( $key ); ?>">
									<input type="color" name="mmpo[statuses][<?php echo (int) $i; ?>][color]" value="<?php echo esc_attr( $st['color'] ); ?>" aria-label="<?php esc_attr_e( 'Colour', 'mine-mirth-purchase-orders' ); ?>">
									<input class="mmpo-input" name="mmpo[statuses][<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $st['label'] ); ?>" aria-label="<?php esc_attr_e( 'Name', 'mine-mirth-purchase-orders' ); ?>">
									<input class="mmpo-input" name="mmpo[statuses][<?php echo (int) $i; ?>][stamp]" value="<?php echo esc_attr( $st['stamp'] ); ?>" placeholder="<?php esc_attr_e( 'none', 'mine-mirth-purchase-orders' ); ?>" aria-label="<?php esc_attr_e( 'Stamp', 'mine-mirth-purchase-orders' ); ?>">
									<button type="button" class="mmpo-remove" data-mmpo-remove-status aria-label="<?php esc_attr_e( 'Remove', 'mine-mirth-purchase-orders' ); ?>">×</button>
								</div>
								<?php
								++$i;
							endforeach;
							?>
						</div>
						<div class="mmpo-add">
							<button type="button" class="button" data-mmpo-add-status data-next="<?php echo (int) $i; ?>"><span aria-hidden="true">+</span> <?php esc_html_e( 'Add a status', 'mine-mirth-purchase-orders' ); ?></button>
							<span class="mmpo-sub"><?php esc_html_e( 'Purchase orders keep their status when you rename it. Removing a status does not change orders that use it.', 'mine-mirth-purchase-orders' ); ?></span>
						</div>
					</section>
				</div>

				<aside class="mmpo-side">
					<section class="mmpo-card">
						<header class="mmpo-card__head"><h2><?php esc_html_e( 'Numbering', 'mine-mirth-purchase-orders' ); ?></h2></header>
						<div class="mmpo-fields">
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Prefix', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input mmpo-mono" name="<?php echo esc_attr( $name( 'prefix' ) ); ?>" value="<?php echo $val( 'prefix' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Digits', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" type="number" min="1" max="8" name="<?php echo esc_attr( $name( 'digits' ) ); ?>" value="<?php echo $val( 'digits' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
							<label class="mmpo-f"><span><?php esc_html_e( 'Next number', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" type="number" min="1" name="next_number" value="<?php echo (int) get_option( 'mmpo_next_number', 1001 ); ?>"></label>
							<p class="mmpo-sub"><?php echo esc_html( sprintf( /* translators: %s: number */ __( 'The next purchase order will be %s.', 'mine-mirth-purchase-orders' ), mmpo_peek_number() ) ); ?></p>
						</div>
					</section>
					<section class="mmpo-card">
						<header class="mmpo-card__head"><h2><?php esc_html_e( 'New purchase orders start with', 'mine-mirth-purchase-orders' ); ?></h2></header>
						<div class="mmpo-fields">
							<label class="mmpo-f"><span><?php esc_html_e( 'Title on the PDF', 'mine-mirth-purchase-orders' ); ?></span>
								<select class="mmpo-input" name="<?php echo esc_attr( $name( 'doc' ) ); ?>">
									<?php foreach ( mmpo_doc_types() as $k => $label ) : ?>
										<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['doc'], $k ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Currency', 'mine-mirth-purchase-orders' ); ?></span>
								<select class="mmpo-input" name="<?php echo esc_attr( $name( 'currency' ) ); ?>">
									<?php foreach ( array_keys( mmpo_currencies() ) as $code ) : ?>
										<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $s['currency'], $code ); ?>><?php echo esc_html( $code ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Payment due after', 'mine-mirth-purchase-orders' ); ?></span><span class="mmpo-unit"><input class="mmpo-input" type="number" min="0" max="365" name="<?php echo esc_attr( $name( 'due_days' ) ); ?>" value="<?php echo $val( 'due_days' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"><b><?php esc_html_e( 'days', 'mine-mirth-purchase-orders' ); ?></b></span></label>
							<label class="mmpo-f"><span><?php esc_html_e( 'Payment terms', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" name="<?php echo esc_attr( $name( 'payment_terms' ) ); ?>" value="<?php echo $val( 'payment_terms' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
							<label class="mmpo-f"><span><?php esc_html_e( 'How to pay', 'mine-mirth-purchase-orders' ); ?></span><textarea class="mmpo-input" rows="4" name="<?php echo esc_attr( $name( 'payment' ) ); ?>"><?php echo esc_textarea( (string) $s['payment'] ); ?></textarea></label>
							<label class="mmpo-f"><span><?php esc_html_e( 'Terms', 'mine-mirth-purchase-orders' ); ?></span><textarea class="mmpo-input" rows="4" name="<?php echo esc_attr( $name( 'terms' ) ); ?>"><?php echo esc_textarea( (string) $s['terms'] ); ?></textarea></label>
						</div>
					</section>
					<div class="mmpo-side__actions">
						<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save settings', 'mine-mirth-purchase-orders' ); ?></button>
						<a class="button button-large" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mmpo_sample' ), 'mmpo_sample' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview a sample PDF', 'mine-mirth-purchase-orders' ); ?></a>
					</div>
				</aside>
			</div>
			<template id="mmpo-status-template">
				<div class="mmpo-statusrow">
					<input type="hidden" name="mmpo[statuses][__i__][key]" value="">
					<input type="color" name="mmpo[statuses][__i__][color]" value="#35a8f6">
					<input class="mmpo-input" name="mmpo[statuses][__i__][label]" value="" placeholder="<?php esc_attr_e( 'Status name', 'mine-mirth-purchase-orders' ); ?>">
					<input class="mmpo-input" name="mmpo[statuses][__i__][stamp]" value="" placeholder="<?php esc_attr_e( 'none', 'mine-mirth-purchase-orders' ); ?>">
					<button type="button" class="mmpo-remove" data-mmpo-remove-status>×</button>
				</div>
			</template>
		</form>
	</div>
	<?php
}
