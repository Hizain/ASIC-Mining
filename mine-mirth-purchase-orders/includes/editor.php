<?php
/**
 * Admin: create and edit a purchase order.
 *
 * @package MineMirthPurchaseOrders
 */

defined( 'ABSPATH' ) || exit;

/**
 * Save from the editor. Normal posts redirect back; "Save & view PDF" posts in the background and get JSON.
 */
add_action(
	'admin_post_mmpo_save',
	static function (): void {
		$ajax = ! empty( $_POST['ajax'] );
		if ( ! current_user_can( mmpo_cap() ) || ! check_admin_referer( 'mmpo_save', 'mmpo_nonce' ) ) {
			if ( $ajax ) {
				wp_send_json_error( array( 'message' => __( 'Your session expired. Reload the page and try again.', 'mine-mirth-purchase-orders' ) ), 403 );
			}
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-purchase-orders' ), 403 );
		}
		$id  = absint( $_POST['id'] ?? 0 );
		$old = $id ? mmpo_get( $id ) : null;
		if ( $id && ! $old ) {
			$id = 0;
		}
		$old = $old ? $old : mmpo_blank();
		$in  = $_POST; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cleaned field by field in mmpo_sanitize().
		$d   = mmpo_sanitize( $in, $old );
		$dup = '' !== $d['number'] ? mmpo_number_in_use( $d['number'], $id ) : 0;
		$id  = mmpo_save( $d, $id );
		if ( ! $id ) {
			if ( $ajax ) {
				wp_send_json_error( array( 'message' => __( 'The purchase order could not be saved.', 'mine-mirth-purchase-orders' ) ), 500 );
			}
			wp_die( esc_html__( 'The purchase order could not be saved.', 'mine-mirth-purchase-orders' ) );
		}
		$saved = mmpo_get( $id );
		if ( $ajax ) {
			wp_send_json_success(
				array(
					'id'       => $id,
					'number'   => $saved['number'],
					'edit'     => mmpo_edit_url( $id ),
					'pdf'      => mmpo_pdf_url( $id ),
					'download' => mmpo_pdf_url( $id, true ),
					'history'  => mmpo_history_html( $saved ),
					'dup'      => $dup ? (string) get_post_meta( $dup, '_mmpo_number', true ) : '',
				)
			);
		}
		$args = array( 'msg' => 'saved' );
		if ( $dup ) {
			$args['dup'] = $dup;
		}
		$next = sanitize_key( wp_unslash( $_POST['then'] ?? '' ) );
		if ( 'new' === $next ) {
			wp_safe_redirect( add_query_arg( 'msg', 'saved_prev', mmpo_edit_url() ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( $args, mmpo_edit_url( $id ) ) );
		exit;
	}
);

/**
 * Status history list (HTML).
 */
function mmpo_history_html( array $d ): string {
	if ( ! $d['history'] ) {
		return '<p class="mmpo-sub">' . esc_html__( 'Changes to the status are listed here.', 'mine-mirth-purchase-orders' ) . '</p>';
	}
	$out = '<ol class="mmpo-history">';
	foreach ( array_reverse( $d['history'] ) as $h ) {
		$st   = mmpo_status( (string) ( $h['s'] ?? '' ) );
		$user = ! empty( $h['u'] ) ? get_userdata( (int) $h['u'] ) : null;
		$out .= '<li style="--c:' . esc_attr( $st['color'] ) . '"><strong>' . esc_html( $st['label'] ) . '</strong>';
		$out .= '<span>' . esc_html( wp_date( 'M j, Y g:i a', (int) ( $h['t'] ?? 0 ) ) . ( $user ? ' · ' . $user->display_name : '' ) ) . '</span>';
		if ( ! empty( $h['n'] ) ) {
			$out .= '<em>' . esc_html( (string) $h['n'] ) . '</em>';
		}
		$out .= '</li>';
	}
	return $out . '</ol>';
}

/**
 * Country options for the ship-to address.
 */
function mmpo_countries(): array {
	if ( function_exists( 'WC' ) && WC()->countries ) {
		$out = array();
		foreach ( WC()->countries->get_countries() as $code => $name ) {
			$out[ $code ] = (string) preg_replace( '/\s*\([A-Z]{2,3}\)$/', '', html_entity_decode( (string) $name, ENT_QUOTES, 'UTF-8' ) );
		}
		return $out;
	}
	return array( 'US' => 'United States', 'CA' => 'Canada', 'MX' => 'Mexico', 'GB' => 'United Kingdom', 'AE' => 'United Arab Emirates', 'PK' => 'Pakistan', 'DE' => 'Germany', 'KZ' => 'Kazakhstan', 'PY' => 'Paraguay', 'ET' => 'Ethiopia' );
}

/**
 * States for countries that have a list (WooCommerce), as code => name.
 */
function mmpo_states(): array {
	if ( ! function_exists( 'WC' ) || ! WC()->countries ) {
		return array();
	}
	$out = array();
	foreach ( (array) WC()->countries->get_states() as $country => $states ) {
		if ( is_array( $states ) && $states ) {
			foreach ( $states as $code => $name ) {
				$out[ $country ][ $code ] = html_entity_decode( (string) $name, ENT_QUOTES, 'UTF-8' );
			}
		}
	}
	return $out;
}

function mmpo_edit_page(): void {
	if ( ! current_user_can( mmpo_cap() ) ) {
		return;
	}
	$id = absint( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
	$d  = $id ? mmpo_get( $id ) : null;
	if ( $id && ! $d ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'Purchase order not found', 'mine-mirth-purchase-orders' ) . '</h1><p><a href="' . esc_url( admin_url( 'admin.php?page=mmpo' ) ) . '">' . esc_html__( 'Back to purchase orders', 'mine-mirth-purchase-orders' ) . '</a></p></div>';
		return;
	}
	$new      = ! $d;
	$d        = $d ? $d : mmpo_blank();
	$t        = mmpo_totals( $d );
	$statuses = mmpo_statuses();
	$s        = mmpo_settings();
	$msg      = sanitize_key( wp_unslash( $_GET['msg'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$dup      = absint( $_GET['dup'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
	$f        = static fn( string $k ) => esc_attr( (string) ( $d[ $k ] ?? '' ) );
	$items    = $d['items'] ? $d['items'] : array( array( 'product_id' => 0, 'name' => '', 'sku' => '', 'desc' => '', 'qty' => 1, 'price' => '' ) );
	$doc      = mmpo_doc_types()[ $d['doc'] ] ?? '';

	$config = array(
		'ajax'       => admin_url( 'admin-ajax.php' ),
		'post'       => admin_url( 'admin-post.php' ),
		'nonce'      => wp_create_nonce( 'mmpo_edit' ),
		'currencies' => mmpo_currencies(),
		'statuses'   => $statuses,
		'states'     => mmpo_states(),
		'woo'        => function_exists( 'wc_get_product' ),
		'i18n'       => array(
			'saving'      => __( 'Saving…', 'mine-mirth-purchase-orders' ),
			'saved'       => __( 'Saved', 'mine-mirth-purchase-orders' ),
			'failed'      => __( 'Could not save. Check your connection and try again.', 'mine-mirth-purchase-orders' ),
			'searching'   => __( 'Searching…', 'mine-mirth-purchase-orders' ),
			'noProducts'  => __( 'No products found. Keep typing to add it as a custom item.', 'mine-mirth-purchase-orders' ),
			'noCustomers' => __( 'Nothing found. Type the address below.', 'mine-mirth-purchase-orders' ),
			'catalog'     => __( 'Catalog price', 'mine-mirth-purchase-orders' ),
			'custom'      => __( 'Custom item', 'mine-mirth-purchase-orders' ),
			'paidFull'    => __( 'Amount paid set to the full total.', 'mine-mirth-purchase-orders' ),
			'leave'       => __( 'You have unsaved changes.', 'mine-mirth-purchase-orders' ),
			'remove'      => __( 'Remove', 'mine-mirth-purchase-orders' ),
			'dup'         => __( 'Another purchase order already uses number %s.', 'mine-mirth-purchase-orders' ),
			'popup'       => __( 'Your browser blocked the new tab. The PDF opens here instead.', 'mine-mirth-purchase-orders' ),
			'due'         => __( 'Payment due', 'mine-mirth-purchase-orders' ),
			'valid'       => __( 'Valid until', 'mine-mirth-purchase-orders' ),
		),
	);
	?>
	<div class="wrap mmpo-admin mmpo-edit">
		<div class="mmpo-topbar">
			<div class="mmpo-topbar__title">
				<a class="mmpo-back" href="<?php echo esc_url( admin_url( 'admin.php?page=mmpo' ) ); ?>" aria-label="<?php esc_attr_e( 'Back to purchase orders', 'mine-mirth-purchase-orders' ); ?>">←</a>
				<h1 class="mmpo-title">
					<span data-mmpo-doc><?php echo esc_html( $doc ); ?></span>
					<span class="mmpo-mono" data-mmpo-number><?php echo esc_html( $new ? mmpo_peek_number() : $d['number'] ); ?></span>
				</h1>
				<span data-mmpo-pill><?php echo mmpo_status_pill( $d['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="mmpo-savestate" data-mmpo-state></span>
			</div>
			<div class="mmpo-topbar__actions">
				<button type="button" class="button" data-mmpo-open="pdf"><?php esc_html_e( 'Save & view PDF', 'mine-mirth-purchase-orders' ); ?></button>
				<button type="button" class="button" data-mmpo-open="download"><?php esc_html_e( 'Download PDF', 'mine-mirth-purchase-orders' ); ?></button>
				<button type="submit" form="mmpo-form" class="button button-primary button-hero mmpo-save"><?php esc_html_e( 'Save', 'mine-mirth-purchase-orders' ); ?></button>
			</div>
		</div>
		<hr class="wp-header-end">
		<?php
		mmpo_setup_notice();
		if ( 'saved' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Purchase order saved.', 'mine-mirth-purchase-orders' ) . ' <a href="' . esc_url( mmpo_pdf_url( (int) $d['id'] ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View PDF', 'mine-mirth-purchase-orders' ) . '</a></p></div>';
		} elseif ( 'copied' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Copied as a new draft with today’s date. Change what you need and save.', 'mine-mirth-purchase-orders' ) . '</p></div>';
		} elseif ( 'saved_prev' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved. Here is a new purchase order.', 'mine-mirth-purchase-orders' ) . '</p></div>';
		}
		if ( $dup ) {
			/* translators: %s: number */
			echo '<div class="notice notice-warning"><p>' . esc_html( sprintf( __( 'Another purchase order already uses number %s. Change the number if this was not on purpose.', 'mine-mirth-purchase-orders' ), $d['number'] ) ) . '</p></div>';
		}
		?>
		<div class="notice notice-warning mmpo-dup-js" hidden><p></p></div>

		<form id="mmpo-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off" novalidate>
			<input type="hidden" name="action" value="mmpo_save">
			<input type="hidden" name="id" value="<?php echo (int) ( $d['id'] ?? 0 ); ?>">
			<?php wp_nonce_field( 'mmpo_save', 'mmpo_nonce' ); ?>
			<script type="application/json" id="mmpo-config"><?php echo wp_json_encode( $config ); ?></script>

			<div class="mmpo-layout">
				<div class="mmpo-main">

					<section class="mmpo-card">
						<header class="mmpo-card__head">
							<h2><?php esc_html_e( 'Ship to', 'mine-mirth-purchase-orders' ); ?></h2>
							<div class="mmpo-finder">
								<input type="search" class="mmpo-input" data-mmpo-customer placeholder="<?php esc_attr_e( 'Fill from a past order or customer: name, email, company…', 'mine-mirth-purchase-orders' ); ?>">
								<div class="mmpo-suggest" data-mmpo-customer-list hidden></div>
							</div>
						</header>
						<div class="mmpo-fields">
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Full name', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" name="ship[name]" value="<?php echo esc_attr( $d['ship']['name'] ); ?>" autocomplete="off"></label>
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Company', 'mine-mirth-purchase-orders' ); ?> <i><?php esc_html_e( 'optional', 'mine-mirth-purchase-orders' ); ?></i></span><input class="mmpo-input" name="ship[company]" value="<?php echo esc_attr( $d['ship']['company'] ); ?>"></label>
							<label class="mmpo-f"><span><?php esc_html_e( 'Street address', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" name="ship[address_1]" value="<?php echo esc_attr( $d['ship']['address_1'] ); ?>"></label>
							<label class="mmpo-f"><span><?php esc_html_e( 'Apartment, suite, unit, dock', 'mine-mirth-purchase-orders' ); ?> <i><?php esc_html_e( 'optional', 'mine-mirth-purchase-orders' ); ?></i></span><input class="mmpo-input" name="ship[address_2]" value="<?php echo esc_attr( $d['ship']['address_2'] ); ?>"></label>
							<label class="mmpo-f mmpo-f--third"><span><?php esc_html_e( 'City', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" name="ship[city]" value="<?php echo esc_attr( $d['ship']['city'] ); ?>"></label>
							<label class="mmpo-f mmpo-f--third"><span><?php esc_html_e( 'State / province', 'mine-mirth-purchase-orders' ); ?></span>
								<span class="mmpo-state" data-mmpo-state-wrap><input class="mmpo-input" name="ship[state]" value="<?php echo esc_attr( $d['ship']['state'] ); ?>" data-mmpo-state></span>
							</label>
							<label class="mmpo-f mmpo-f--third"><span><?php esc_html_e( 'ZIP / postal code', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" name="ship[postcode]" value="<?php echo esc_attr( $d['ship']['postcode'] ); ?>"></label>
							<label class="mmpo-f mmpo-f--third"><span><?php esc_html_e( 'Country', 'mine-mirth-purchase-orders' ); ?></span>
								<select class="mmpo-input" name="ship[country]" data-mmpo-country>
									<option value=""><?php esc_html_e( 'Choose…', 'mine-mirth-purchase-orders' ); ?></option>
									<?php foreach ( mmpo_countries() as $code => $name ) : ?>
										<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $d['ship']['country'], $code ); ?>><?php echo esc_html( $name ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="mmpo-f mmpo-f--third"><span><?php esc_html_e( 'Phone', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" name="ship[phone]" value="<?php echo esc_attr( $d['ship']['phone'] ); ?>" inputmode="tel"></label>
							<label class="mmpo-f mmpo-f--third"><span><?php esc_html_e( 'Email', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" type="email" name="ship[email]" value="<?php echo esc_attr( $d['ship']['email'] ); ?>"></label>
						</div>
					</section>

					<section class="mmpo-card">
						<header class="mmpo-card__head">
							<h2><?php esc_html_e( 'Products', 'mine-mirth-purchase-orders' ); ?></h2>
							<p class="mmpo-sub"><?php echo function_exists( 'wc_get_product' ) ? esc_html__( 'Start typing to pick from your shop, or type anything for a custom item. Prices can be changed for this order.', 'mine-mirth-purchase-orders' ) : esc_html__( 'Type each item, its quantity and price.', 'mine-mirth-purchase-orders' ); ?></p>
						</header>
						<div class="mmpo-items" data-mmpo-items>
							<div class="mmpo-items__head" aria-hidden="true">
								<span><?php esc_html_e( 'Product', 'mine-mirth-purchase-orders' ); ?></span>
								<span><?php esc_html_e( 'Qty', 'mine-mirth-purchase-orders' ); ?></span>
								<span><?php esc_html_e( 'Unit price', 'mine-mirth-purchase-orders' ); ?></span>
								<span><?php esc_html_e( 'Amount', 'mine-mirth-purchase-orders' ); ?></span>
								<span></span>
							</div>
							<?php foreach ( $items as $i => $item ) : ?>
								<?php mmpo_item_row( (string) $i, $item, $d['currency'] ); ?>
							<?php endforeach; ?>
						</div>
						<template id="mmpo-item-template"><?php mmpo_item_row( '__i__', array( 'product_id' => 0, 'name' => '', 'sku' => '', 'desc' => '', 'qty' => 1, 'price' => '' ), $d['currency'] ); ?></template>
						<div class="mmpo-add">
							<button type="button" class="button" data-mmpo-add="item"><span aria-hidden="true">+</span> <?php esc_html_e( 'Add product', 'mine-mirth-purchase-orders' ); ?></button>
						</div>
					</section>

					<section class="mmpo-card">
						<header class="mmpo-card__head">
							<h2><?php esc_html_e( 'Shipping, fees and taxes', 'mine-mirth-purchase-orders' ); ?></h2>
							<p class="mmpo-sub"><?php esc_html_e( 'Percentages are worked out from the products subtotal. Use a negative amount for a discount.', 'mine-mirth-purchase-orders' ); ?></p>
						</header>
						<div class="mmpo-charges">
							<div class="mmpo-charge">
								<span class="mmpo-charge__kind"><?php esc_html_e( 'Shipping', 'mine-mirth-purchase-orders' ); ?></span>
								<input class="mmpo-input" name="shipping[label]" value="<?php echo esc_attr( $d['shipping']['label'] ); ?>" placeholder="<?php esc_attr_e( 'Shipping (carrier, service)', 'mine-mirth-purchase-orders' ); ?>">
								<span class="mmpo-charge__type"></span>
								<span class="mmpo-money"><b data-mmpo-sym></b><input class="mmpo-input" name="shipping[amount]" value="<?php echo esc_attr( $d['shipping']['amount'] ? (string) $d['shipping']['amount'] : '' ); ?>" inputmode="decimal" placeholder="0.00" data-mmpo-calc></span>
								<span class="mmpo-charge__amount" data-mmpo-out="shipping"><?php echo esc_html( mmpo_money( $t['shipping'], $d['currency'] ) ); ?></span>
								<span></span>
							</div>
							<div data-mmpo-list="fees">
								<?php foreach ( $d['fees'] as $i => $fee ) : ?>
									<?php mmpo_charge_row( 'fees', (string) $i, $fee ); ?>
								<?php endforeach; ?>
							</div>
							<div data-mmpo-list="taxes">
								<?php foreach ( $d['taxes'] as $i => $tax ) : ?>
									<?php mmpo_charge_row( 'taxes', (string) $i, $tax ); ?>
								<?php endforeach; ?>
							</div>
							<template id="mmpo-fees-template"><?php mmpo_charge_row( 'fees', '__i__', array( 'label' => '', 'type' => 'fixed', 'value' => '' ) ); ?></template>
							<template id="mmpo-taxes-template"><?php mmpo_charge_row( 'taxes', '__i__', array( 'label' => '', 'type' => 'percent', 'value' => '' ) ); ?></template>
						</div>
						<div class="mmpo-add">
							<button type="button" class="button" data-mmpo-add="fees"><span aria-hidden="true">+</span> <?php esc_html_e( 'Add a fee or discount', 'mine-mirth-purchase-orders' ); ?></button>
							<button type="button" class="button" data-mmpo-add="taxes"><span aria-hidden="true">+</span> <?php esc_html_e( 'Add a tax', 'mine-mirth-purchase-orders' ); ?></button>
							<label class="mmpo-check"><input type="checkbox" name="tax_all" value="1" <?php checked( ! empty( $d['tax_all'] ) ); ?> data-mmpo-calc> <?php esc_html_e( 'Also tax shipping and fees', 'mine-mirth-purchase-orders' ); ?></label>
						</div>
					</section>

					<section class="mmpo-card">
						<header class="mmpo-card__head"><h2><?php esc_html_e( 'Notes on the PDF', 'mine-mirth-purchase-orders' ); ?></h2></header>
						<div class="mmpo-fields">
							<label class="mmpo-f"><span><?php esc_html_e( 'Notes for the customer', 'mine-mirth-purchase-orders' ); ?> <i><?php esc_html_e( 'optional', 'mine-mirth-purchase-orders' ); ?></i></span><textarea class="mmpo-input" name="notes" rows="3" placeholder="<?php esc_attr_e( 'Delivery details, lead time, warranty…', 'mine-mirth-purchase-orders' ); ?>"><?php echo esc_textarea( $d['notes'] ); ?></textarea></label>
							<label class="mmpo-f"><span><?php esc_html_e( 'How to pay', 'mine-mirth-purchase-orders' ); ?></span><textarea class="mmpo-input" name="payment" rows="3"><?php echo esc_textarea( $d['payment'] ); ?></textarea></label>
							<label class="mmpo-f"><span><?php esc_html_e( 'Terms', 'mine-mirth-purchase-orders' ); ?></span><textarea class="mmpo-input" name="terms" rows="3"><?php echo esc_textarea( $d['terms'] ); ?></textarea></label>
							<label class="mmpo-f mmpo-f--private"><span><?php esc_html_e( 'Private note', 'mine-mirth-purchase-orders' ); ?> <i><?php esc_html_e( 'only you see this, never on the PDF', 'mine-mirth-purchase-orders' ); ?></i></span><textarea class="mmpo-input" name="internal" rows="2"><?php echo esc_textarea( $d['internal'] ); ?></textarea></label>
						</div>
					</section>
				</div>

				<aside class="mmpo-side">
					<section class="mmpo-card mmpo-card--status">
						<header class="mmpo-card__head"><h2><?php esc_html_e( 'Status', 'mine-mirth-purchase-orders' ); ?></h2></header>
						<div class="mmpo-statuses" role="radiogroup" aria-label="<?php esc_attr_e( 'Status', 'mine-mirth-purchase-orders' ); ?>">
							<?php foreach ( $statuses as $key => $st ) : ?>
								<label class="mmpo-status" style="--c:<?php echo esc_attr( $st['color'] ); ?>">
									<input type="radio" name="status" value="<?php echo esc_attr( $key ); ?>" <?php checked( $d['status'], $key ); ?> data-mmpo-status>
									<span><i></i><?php echo esc_html( $st['label'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
						<label class="mmpo-f"><span><?php esc_html_e( 'Note for the history', 'mine-mirth-purchase-orders' ); ?> <i><?php esc_html_e( 'optional', 'mine-mirth-purchase-orders' ); ?></i></span><input class="mmpo-input" name="status_note" value="" placeholder="<?php esc_attr_e( 'Wire received, picked up by UPS…', 'mine-mirth-purchase-orders' ); ?>"></label>
						<label class="mmpo-check"><input type="checkbox" name="show_status" value="1" <?php checked( ! empty( $d['show_status'] ) ); ?>> <?php esc_html_e( 'Show the status on the PDF', 'mine-mirth-purchase-orders' ); ?></label>
						<details class="mmpo-history-wrap" <?php echo $d['history'] ? '' : 'hidden'; ?>>
							<summary><?php esc_html_e( 'History', 'mine-mirth-purchase-orders' ); ?></summary>
							<div data-mmpo-history><?php echo mmpo_history_html( $d ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
						</details>
					</section>

					<section class="mmpo-card mmpo-card--totals">
						<header class="mmpo-card__head"><h2><?php esc_html_e( 'Totals', 'mine-mirth-purchase-orders' ); ?></h2></header>
						<dl class="mmpo-totals" data-mmpo-totals>
							<div><dt><?php esc_html_e( 'Products', 'mine-mirth-purchase-orders' ); ?></dt><dd data-mmpo-out="subtotal"><?php echo esc_html( mmpo_money( $t['subtotal'], $d['currency'] ) ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Shipping', 'mine-mirth-purchase-orders' ); ?></dt><dd data-mmpo-out="shipping"><?php echo esc_html( mmpo_money( $t['shipping'], $d['currency'] ) ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Fees', 'mine-mirth-purchase-orders' ); ?></dt><dd data-mmpo-out="fees"><?php echo esc_html( mmpo_money( $t['fee_total'], $d['currency'] ) ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Taxes', 'mine-mirth-purchase-orders' ); ?></dt><dd data-mmpo-out="taxes"><?php echo esc_html( mmpo_money( $t['tax_total'], $d['currency'] ) ); ?></dd></div>
							<div class="is-total"><dt><?php esc_html_e( 'Total', 'mine-mirth-purchase-orders' ); ?></dt><dd data-mmpo-out="total"><?php echo esc_html( mmpo_money( $t['total'], $d['currency'] ) ); ?></dd></div>
						</dl>
						<div class="mmpo-paid">
							<label class="mmpo-f"><span><?php esc_html_e( 'Amount paid', 'mine-mirth-purchase-orders' ); ?></span>
								<span class="mmpo-money"><b data-mmpo-sym></b><input class="mmpo-input" name="paid" value="<?php echo esc_attr( $d['paid'] ? (string) $d['paid'] : '' ); ?>" inputmode="decimal" placeholder="0.00" data-mmpo-calc data-mmpo-paid></span>
							</label>
							<button type="button" class="button-link" data-mmpo-paidfull><?php esc_html_e( 'Paid in full', 'mine-mirth-purchase-orders' ); ?></button>
							<label class="mmpo-f"><span><?php esc_html_e( 'Payment note', 'mine-mirth-purchase-orders' ); ?> <i><?php esc_html_e( 'optional, on the PDF', 'mine-mirth-purchase-orders' ); ?></i></span><input class="mmpo-input" name="paid_note" value="<?php echo esc_attr( $d['paid_note'] ); ?>" placeholder="<?php esc_attr_e( 'Wire, Oct 27', 'mine-mirth-purchase-orders' ); ?>"></label>
						</div>
						<div class="mmpo-balance"><span><?php esc_html_e( 'Balance due', 'mine-mirth-purchase-orders' ); ?></span><strong data-mmpo-out="balance"><?php echo esc_html( mmpo_money( $t['balance'], $d['currency'] ) ); ?></strong></div>
					</section>

					<section class="mmpo-card">
						<header class="mmpo-card__head"><h2><?php esc_html_e( 'Details', 'mine-mirth-purchase-orders' ); ?></h2></header>
						<div class="mmpo-fields">
							<label class="mmpo-f"><span><?php esc_html_e( 'Title on the PDF', 'mine-mirth-purchase-orders' ); ?></span>
								<select class="mmpo-input" name="doc" data-mmpo-docsel>
									<?php foreach ( mmpo_doc_types() as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $d['doc'], $key ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Number', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input mmpo-mono" name="number" value="<?php echo $f( 'number' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" placeholder="<?php echo esc_attr( mmpo_peek_number() ); ?>" data-mmpo-numin></label>
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Currency', 'mine-mirth-purchase-orders' ); ?></span>
								<select class="mmpo-input" name="currency" data-mmpo-currency data-mmpo-calc>
									<?php
									$codes = array_keys( mmpo_currencies() );
									if ( ! in_array( $d['currency'], $codes, true ) ) {
										$codes[] = $d['currency'];
									}
									foreach ( $codes as $code ) :
										?>
										<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $d['currency'], $code ); ?>><?php echo esc_html( $code ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Date', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" type="date" name="date" value="<?php echo $f( 'date' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
							<label class="mmpo-f mmpo-f--half"><span data-mmpo-duelabel><?php echo 'quote' === $d['doc'] ? esc_html__( 'Valid until', 'mine-mirth-purchase-orders' ) : esc_html__( 'Payment due', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" type="date" name="due" value="<?php echo $f( 'due' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
							<label class="mmpo-f"><span><?php esc_html_e( 'Payment terms', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" name="payment_terms" value="<?php echo $f( 'payment_terms' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" list="mmpo-terms-list"></label>
							<datalist id="mmpo-terms-list">
								<?php foreach ( array( __( 'Payment in full before shipping', 'mine-mirth-purchase-orders' ), __( 'Due on receipt', 'mine-mirth-purchase-orders' ), __( '50% deposit, balance before shipping', 'mine-mirth-purchase-orders' ), __( 'Net 7', 'mine-mirth-purchase-orders' ), __( 'Net 15', 'mine-mirth-purchase-orders' ), __( 'Net 30', 'mine-mirth-purchase-orders' ) ) as $term ) : ?>
									<option value="<?php echo esc_attr( $term ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
							<label class="mmpo-f"><span><?php esc_html_e( 'Customer reference', 'mine-mirth-purchase-orders' ); ?> <i><?php esc_html_e( 'their PO number, optional', 'mine-mirth-purchase-orders' ); ?></i></span><input class="mmpo-input" name="reference" value="<?php echo $f( 'reference' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
						</div>
					</section>

					<section class="mmpo-card">
						<header class="mmpo-card__head"><h2><?php esc_html_e( 'Shipment tracking', 'mine-mirth-purchase-orders' ); ?></h2></header>
						<div class="mmpo-fields">
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Carrier', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" name="tracking[carrier]" value="<?php echo esc_attr( $d['tracking']['carrier'] ); ?>" list="mmpo-carriers" placeholder="UPS"></label>
							<label class="mmpo-f mmpo-f--half"><span><?php esc_html_e( 'Tracking number', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input mmpo-mono" name="tracking[number]" value="<?php echo esc_attr( $d['tracking']['number'] ); ?>"></label>
							<datalist id="mmpo-carriers"><option value="UPS"></option><option value="FedEx"></option><option value="USPS"></option><option value="DHL"></option><option value="Freight (LTL)"></option><option value="Pickup"></option></datalist>
							<p class="mmpo-sub"><?php esc_html_e( 'Shown on the PDF when filled in.', 'mine-mirth-purchase-orders' ); ?></p>
						</div>
					</section>

					<div class="mmpo-side__actions">
						<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save', 'mine-mirth-purchase-orders' ); ?></button>
						<button type="submit" class="button button-large" name="then" value="new"><?php esc_html_e( 'Save & start a new one', 'mine-mirth-purchase-orders' ); ?></button>
						<?php if ( ! $new ) : ?>
							<div class="mmpo-side__links">
								<a href="<?php echo esc_url( mmpo_action_url( 'duplicate', (int) $d['id'] ) ); ?>"><?php esc_html_e( 'Duplicate', 'mine-mirth-purchase-orders' ); ?></a>
								<a class="mmpo-danger" href="<?php echo esc_url( mmpo_action_url( 'trash', (int) $d['id'] ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Move this purchase order to the Trash?', 'mine-mirth-purchase-orders' ) ); ?>')"><?php esc_html_e( 'Move to Trash', 'mine-mirth-purchase-orders' ); ?></a>
							</div>
						<?php endif; ?>
					</div>
				</aside>
			</div>
		</form>
	</div>
	<?php
}

/**
 * One product row in the editor.
 */
function mmpo_item_row( string $i, array $item, string $currency ): void {
	$n     = 'items[' . $i . ']';
	$price = '' === $item['price'] ? '' : (string) $item['price'];
	?>
	<div class="mmpo-item" data-mmpo-item>
		<div class="mmpo-item__main">
			<div class="mmpo-pick">
				<input class="mmpo-input mmpo-item__name" name="<?php echo esc_attr( $n ); ?>[name]" value="<?php echo esc_attr( $item['name'] ); ?>" placeholder="<?php esc_attr_e( 'Search your products or type an item', 'mine-mirth-purchase-orders' ); ?>" data-mmpo-product autocomplete="off" aria-label="<?php esc_attr_e( 'Product', 'mine-mirth-purchase-orders' ); ?>">
				<div class="mmpo-suggest" hidden></div>
			</div>
			<input type="hidden" name="<?php echo esc_attr( $n ); ?>[product_id]" value="<?php echo (int) $item['product_id']; ?>" data-mmpo-pid>
			<div class="mmpo-item__meta">
				<input class="mmpo-input mmpo-mono" name="<?php echo esc_attr( $n ); ?>[sku]" value="<?php echo esc_attr( $item['sku'] ); ?>" placeholder="<?php esc_attr_e( 'SKU', 'mine-mirth-purchase-orders' ); ?>" aria-label="<?php esc_attr_e( 'SKU', 'mine-mirth-purchase-orders' ); ?>" data-mmpo-sku>
				<textarea class="mmpo-input" name="<?php echo esc_attr( $n ); ?>[desc]" rows="1" placeholder="<?php esc_attr_e( 'Description: specs, serial numbers, condition (optional)', 'mine-mirth-purchase-orders' ); ?>" aria-label="<?php esc_attr_e( 'Description', 'mine-mirth-purchase-orders' ); ?>" data-mmpo-desc><?php echo esc_textarea( $item['desc'] ); ?></textarea>
			</div>
			<div class="mmpo-item__hint" data-mmpo-hint><?php echo $item['product_id'] ? esc_html__( 'From your shop', 'mine-mirth-purchase-orders' ) : ''; ?></div>
		</div>
		<label class="mmpo-item__qty"><span class="screen-reader-text"><?php esc_html_e( 'Quantity', 'mine-mirth-purchase-orders' ); ?></span><input class="mmpo-input" type="number" min="0" step="any" name="<?php echo esc_attr( $n ); ?>[qty]" value="<?php echo esc_attr( rtrim( rtrim( number_format( (float) $item['qty'], 2, '.', '' ), '0' ), '.' ) ); ?>" data-mmpo-calc data-mmpo-qty></label>
		<label class="mmpo-item__price"><span class="screen-reader-text"><?php esc_html_e( 'Unit price', 'mine-mirth-purchase-orders' ); ?></span><span class="mmpo-money"><b data-mmpo-sym></b><input class="mmpo-input" name="<?php echo esc_attr( $n ); ?>[price]" value="<?php echo esc_attr( $price ); ?>" inputmode="decimal" placeholder="0.00" data-mmpo-calc data-mmpo-price></span></label>
		<div class="mmpo-item__amount" data-mmpo-line><?php echo esc_html( mmpo_money( (float) $item['qty'] * (float) $item['price'], $currency ) ); ?></div>
		<button type="button" class="mmpo-remove" data-mmpo-remove aria-label="<?php esc_attr_e( 'Remove this product', 'mine-mirth-purchase-orders' ); ?>">×</button>
	</div>
	<?php
}

/**
 * One fee or tax row in the editor.
 */
function mmpo_charge_row( string $list, string $i, array $row ): void {
	$n = $list . '[' . $i . ']';
	?>
	<div class="mmpo-charge" data-mmpo-charge="<?php echo esc_attr( $list ); ?>">
		<span class="mmpo-charge__kind"><?php echo 'fees' === $list ? esc_html__( 'Fee', 'mine-mirth-purchase-orders' ) : esc_html__( 'Tax', 'mine-mirth-purchase-orders' ); ?></span>
		<input class="mmpo-input" name="<?php echo esc_attr( $n ); ?>[label]" value="<?php echo esc_attr( (string) $row['label'] ); ?>" placeholder="<?php echo 'fees' === $list ? esc_attr__( 'Card processing, wire fee, discount…', 'mine-mirth-purchase-orders' ) : esc_attr__( 'Sales tax, VAT…', 'mine-mirth-purchase-orders' ); ?>">
		<select class="mmpo-input mmpo-charge__type" name="<?php echo esc_attr( $n ); ?>[type]" data-mmpo-calc aria-label="<?php esc_attr_e( 'Amount or percent', 'mine-mirth-purchase-orders' ); ?>">
			<option value="fixed" <?php selected( $row['type'], 'fixed' ); ?>><?php esc_html_e( 'Amount', 'mine-mirth-purchase-orders' ); ?></option>
			<option value="percent" <?php selected( $row['type'], 'percent' ); ?>><?php esc_html_e( 'Percent', 'mine-mirth-purchase-orders' ); ?></option>
		</select>
		<span class="mmpo-money"><b data-mmpo-unit></b><input class="mmpo-input" name="<?php echo esc_attr( $n ); ?>[value]" value="<?php echo esc_attr( '' === $row['value'] ? '' : (string) $row['value'] ); ?>" inputmode="decimal" placeholder="0" data-mmpo-calc></span>
		<span class="mmpo-charge__amount" data-mmpo-charge-out></span>
		<button type="button" class="mmpo-remove" data-mmpo-remove aria-label="<?php esc_attr_e( 'Remove', 'mine-mirth-purchase-orders' ); ?>">×</button>
	</div>
	<?php
}
