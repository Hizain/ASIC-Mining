<?php
/**
 * Subscription invoice PDF in Mine Mirth branding: company details, the client and their client
 * number, the plan with its image, the lines Stripe billed, discounts, tax, payments and refunds.
 *
 * @package MineMirthSubscriptions
 */

defined( 'ABSPATH' ) || exit;

function mms_shade( string $hex, float $amount ): string {
	$c = MMS_PDF::rgb( $hex );
	foreach ( $c as $i => $v ) {
		$c[ $i ] = $amount >= 0 ? $v + ( 1 - $v ) * $amount : $v * ( 1 + $amount );
	}
	return sprintf( '#%02x%02x%02x', (int) round( $c[0] * 255 ), (int) round( $c[1] * 255 ), (int) round( $c[2] * 255 ) );
}

/**
 * Build the PDF for an invoice row.
 */
function mms_invoice_pdf( array $inv ): string {
	$s       = mms_settings();
	$b       = mms_brand();
	$pdf     = new MMS_PDF( 'letter' );
	$W       = $pdf->width();
	$H       = $pdf->height();
	$M       = 40.0;
	$R       = $W - $M;
	$cur     = (string) $inv['currency'];
	$money   = static fn( int $v ) => mms_money( $v, $cur );
	$cust    = mms_get( 'customers', 'id', (int) $inv['customer_id'] );
	$sub     = '' !== $inv['subscription'] ? mms_get( 'subscriptions', 'stripe_id', $inv['subscription'] ) : null;
	$lines   = mms_json( $inv['line_items'] ?? '' );
	$ink     = '#0a1626';
	$muted   = '#506279';
	$faint   = '#8a9bb0';
	$line    = '#e2e9f1';
	$navy    = '#071221';
	$hi      = $b['highlight'];
	$primary = $b['primary'];
	$accent  = $b['accent'];
	$link    = mms_shade( $accent, -0.1 );
	$number  = '' !== $inv['number'] ? (string) $inv['number'] : (string) $inv['stripe_id'];
	$company = trim( (string) $s['company'] );
	$addr    = array_values( array_filter( array_map( 'trim', preg_split( "/\r\n|\r|\n/", (string) $s['address'] ) ) ) );
	$contact = array_values( array_filter( array( trim( (string) $s['email'] ), trim( (string) $s['phone'] ) ) ) );
	$paid    = 'paid' === $inv['status'];
	$refund  = (int) $inv['amount_refunded'];
	$status  = $paid ? ( $refund >= (int) $inv['amount_paid'] && $refund > 0 ? 'refunded' : ( $refund > 0 ? 'partially_refunded' : 'paid' ) ) : (string) $inv['status'];
	$st      = mms_statuses()[ $status ] ?? array( ucfirst( $status ), '#64748b' );

	$pdf->set_info( array( 'Title' => 'Invoice ' . $number, 'Author' => $company, 'Creator' => $company ) );
	$pdf->add_page();

	/* Header */
	$head_h = max( 150, 108 + 12.5 * ( count( $addr ) + ( $contact ? 1 : 0 ) ) );
	$pdf->gradient_rect( 0, 0, $W, $head_h, array( '#040a13', $navy, '#0c2442' ), 0, array( 0.5 ) );
	$pdf->save();
	$pdf->clip( 0, 0, $W, $head_h );
	$pdf->alpha( 0.16 );
	$pdf->circle( $W - 40, 8, 150, $primary );
	$pdf->alpha( 0.08 );
	$pdf->circle( 120, -60, 120, $accent );
	$pdf->restore();
	$pdf->gradient_rect( 0, $head_h - 3, $W, 3, array( $hi, $primary, $accent ), 0, array( 0.45 ), false );
	$pdf->gradient_rect( $M, 34, 38, 38, array( $hi, $primary, $accent ), 11.4, array( 0.45 ) );
	$pdf->text( $M + 19, 34 + 19 + 7.6, 'M', 'sora-bold', 21, '#ffffff', array( 'align' => 'center' ) );
	$pdf->text( $M + 49, 34 + 19 + 6.6, $b['name'], 'sora-bold', 18, '#ffffff', array( 'spacing' => -0.2 ) );
	$y = 98;
	if ( '' !== $company ) {
		$pdf->text( $M, $y, $company, 'inter-semibold', 9.5, '#ffffff' );
		$y += 13;
	}
	foreach ( $addr as $a ) {
		$pdf->text( $M, $y, $a, 'inter-regular', 9, '#a9bacd' );
		$y += 12.5;
	}
	if ( $contact ) {
		$pdf->text( $M, $y, implode( '   ·   ', $contact ), 'inter-regular', 9, '#dbe7f3' );
	}
	$pdf->text( $R, 62, 'INVOICE', 'sora-bold', 21, '#ffffff', array( 'align' => 'right', 'spacing' => 1.4 ) );
	$pdf->text( $R, 82, $number, 'mono-medium', 11, $hi, array( 'align' => 'right', 'spacing' => 0.4 ) );
	$label = mb_strtoupper( $st[0] );
	$tw    = $pdf->text_width( $label, 'inter-semibold', 7.5, 0.8 ) + 28;
	$pdf->rect( $R - $tw, 96, $tw, 19, $st[1], '', 9.5 );
	$pdf->circle( $R - $tw + 11, 105.5, 2.6, '#ffffff' );
	$pdf->text( $R - $tw + 18, 108.4, $label, 'inter-semibold', 7.5, '#ffffff', array( 'spacing' => 0.8 ) );

	/* Bill to */
	$top = $head_h + 26;
	$y   = $top;
	$pdf->text( $M, $y, 'BILL TO', 'inter-semibold', 7.5, $link, array( 'spacing' => 1.4 ) );
	$y += 19;
	if ( $cust ) {
		$name = '' !== $cust['name'] ? $cust['name'] : $cust['email'];
		$pdf->text( $M, $y, $pdf->fit( (string) $name, 'sora-semibold', 13, 268 ), 'sora-semibold', 13, $ink );
		$y += 17;
		// Client number badge.
		$cn = sprintf( 'Client %s', $cust['client_no'] );
		$cw = $pdf->text_width( $cn, 'mono-medium', 8.5 ) + 18;
		$pdf->rect( $M, $y - 11, $cw, 17, '#eaf4fe', '', 8.5 );
		$pdf->text( $M + 9, $y + 1, $cn, 'mono-medium', 8.5, $link );
		$y += 20;
		$address = mms_json( $cust['address'] );
		$alines  = array_values( array_filter( array( $address['line1'] ?? '', $address['line2'] ?? '', trim( ( $address['city'] ?? '' ) . ( ! empty( $address['state'] ) ? ', ' . $address['state'] : '' ) . ' ' . ( $address['postal_code'] ?? '' ) ), $address['country'] ?? '' ) ) );
		foreach ( array_merge( $alines, array_filter( array( $cust['email'], $cust['phone'] ) ) ) as $l ) {
			$pdf->text( $M, $y, $pdf->fit( (string) $l, 'inter-regular', 9.5, 268 ), 'inter-regular', 9.5, $muted );
			$y += 13.5;
		}
	}
	$left_end = $y;

	/* Amount card and details */
	$cx = $W - $M - 228;
	$cw = 228;
	$y  = $top - 12;
	$pdf->gradient_rect( $cx, $y, $cw, 66, array( '#06101d', '#0d2744' ), 12 );
	$pdf->save();
	$pdf->clip( $cx, $y, $cw, 66, 12 );
	$pdf->alpha( 0.22 );
	$pdf->circle( $cx + $cw - 10, $y + 6, 46, $primary );
	$pdf->restore();
	$big_label = $paid ? 'AMOUNT PAID' : 'AMOUNT DUE';
	$big       = $paid ? (int) $inv['amount_paid'] : (int) $inv['amount_due'];
	$pdf->text( $cx + 16, $y + 21, $big_label, 'inter-semibold', 7.5, $hi, array( 'spacing' => 1.3 ) );
	$pdf->text( $cx + 16, $y + 48, $money( $big ), 'sora-bold', 21, '#ffffff' );
	if ( $paid && $inv['paid_at'] ) {
		$pdf->text( $cx + $cw - 16, $y + 21, 'on ' . mms_date( (int) $inv['paid_at'] ), 'inter-regular', 8, '#a9bacd', array( 'align' => 'right' ) );
	}
	$rows = array( array( 'Invoice date', mms_date( (int) $inv['created'] ) ) );
	if ( $inv['period_start'] && $inv['period_end'] && $inv['period_end'] > $inv['period_start'] ) {
		$rows[] = array( 'Service period', mms_date( (int) $inv['period_start'] ) . ' to ' . mms_date( (int) $inv['period_end'] ) );
	}
	if ( $sub ) {
		$rows[] = array( 'Plan', $sub['plan_name'] . ( (int) $sub['quantity'] > 1 ? ' × ' . (int) $sub['quantity'] : '' ) );
		if ( in_array( $sub['status'], array( 'active', 'trialing', 'past_due' ), true ) ) {
			$rows[] = array( 'Next billing', mms_date( (int) $sub['period_end'] ) );
		}
	}
	if ( $cust ) {
		$rows[] = array( 'Client number', (string) $cust['client_no'] );
	}
	$y += 72;
	foreach ( $rows as $r ) {
		$y += 18;
		$pdf->text( $cx + 2, $y, $r[0], 'inter-regular', 8.8, $muted );
		$pdf->text( $cx + $cw - 2, $y, $pdf->fit( $r[1], 'inter-semibold', 8.8, $cw - 92 ), 'inter-semibold', 8.8, $ink, array( 'align' => 'right' ) );
		$pdf->line( $cx, $y + 7, $cx + $cw, $y + 7, $line, 0.6 );
	}
	$right_end = $y + 7;

	/* Lines */
	$c_name = $M + 14;
	$c_qty  = $W - $M - 186;
	$c_unit = $W - $M - 96;
	$c_amt  = $R - 12;
	$y      = max( $left_end, $right_end ) + 24;
	$pdf->rect( $M, $y, $W - 2 * $M, 26, '#edf3f9', '', 8 );
	foreach ( array( array( $c_name, 'DESCRIPTION', 'left' ), array( $c_qty, 'QTY', 'right' ), array( $c_unit, 'UNIT PRICE', 'right' ), array( $c_amt, 'AMOUNT', 'right' ) ) as $h ) {
		$pdf->text( $h[0], $y + 16.5, $h[1], 'inter-semibold', 7.2, $muted, array( 'spacing' => 1, 'align' => $h[2] ) );
	}
	$y     += 26;
	$images = array();
	foreach ( $lines as $l ) {
		$plan_id = $l['price'] ? mms_plan_by_stripe( (string) $l['price'], (string) $l['product'] ) : 0;
		$img     = null;
		if ( $plan_id && get_post_thumbnail_id( $plan_id ) ) {
			$file = get_attached_file( (int) get_post_thumbnail_id( $plan_id ) );
			$img  = $file ? $pdf->load_image( $file ) : null;
		}
		$name_x = $c_name + ( $img ? 56 : 0 );
		$name_w = $c_qty - 50 - $name_x;
		$desc   = $pdf->wrap( (string) $l['description'], 'inter-semibold', 9.8, $name_w );
		$sub_t  = $l['start'] && $l['end'] && $l['end'] > $l['start'] ? mms_date( (int) $l['start'] ) . ' to ' . mms_date( (int) $l['end'] ) : '';
		$h      = max( $img ? 54 : 0, 16 + 13 * count( $desc ) + ( $sub_t ? 12 : 0 ) ) + 10;
		if ( $y + $h > $H - 220 ) {
			$pdf->add_page();
			$y = 50;
		}
		if ( $img ) {
			$iw = 46;
			$ih = min( 46, $iw * $img[2] / max( 1, $img[1] ) );
			$pdf->save();
			$pdf->clip( $c_name, $y + 10, $iw, 46, 8 );
			$pdf->image( $img[0], $c_name, $y + 10 + ( 46 - $ih ) / 2, $iw, $ih );
			$pdf->restore();
		}
		$ry = $y + 24;
		foreach ( $desc as $k => $d ) {
			$pdf->text( $name_x, $ry + 13 * $k, $d, 'inter-semibold', 9.8, $ink );
		}
		if ( $sub_t ) {
			$pdf->text( $name_x, $ry + 13 * count( $desc ) - 0.5, $sub_t, 'inter-regular', 8.4, $muted );
		}
		$pdf->text( $c_qty, $ry, (string) max( 1, (int) $l['quantity'] ), 'inter-regular', 9.6, $ink, array( 'align' => 'right' ) );
		$pdf->text( $c_unit, $ry, $l['unit'] ? $money( (int) $l['unit'] ) : '', 'inter-regular', 9.6, $ink, array( 'align' => 'right' ) );
		$pdf->text( $c_amt, $ry, $money( (int) $l['amount'] ), 'inter-semibold', 9.6, (int) $l['amount'] < 0 ? '#0f9f6e' : $ink, array( 'align' => 'right' ) );
		$y += $h;
		$pdf->line( $M + 8, $y, $R - 8, $y, $line, 0.6 );
	}

	/* Totals */
	$tw   = 236;
	$tx   = $R - $tw;
	$ty   = $y + 12;
	$rows = array( array( 'Subtotal', (int) $inv['subtotal'], $ink ) );
	if ( (int) $inv['discount'] ) {
		$rows[] = array( 'Discount', -(int) $inv['discount'], '#0f9f6e' );
	}
	if ( (int) $inv['tax'] ) {
		$rows[] = array( 'Tax', (int) $inv['tax'], $ink );
	}
	foreach ( $rows as $r ) {
		$ty += 19;
		$pdf->text( $tx + 4, $ty, $r[0], 'inter-regular', 9.4, $muted );
		$pdf->text( $R - 4, $ty, $money( $r[1] ), 'inter-semibold', 9.4, $r[2], array( 'align' => 'right' ) );
	}
	$ty += 10;
	$pdf->gradient_rect( $tx, $ty, $tw, 38, array( '#06101d', '#0d2744' ), 10 );
	$pdf->gradient_rect( $tx + 14, $ty + 10, 3, 18, array( $hi, $accent ), 1.5 );
	$pdf->text( $tx + 26, $ty + 23.5, 'Total', 'inter-semibold', 10, '#c6d4e3' );
	$pdf->text( $R - 14, $ty + 25, $money( (int) $inv['total'] ), 'sora-bold', 16, '#ffffff', array( 'align' => 'right' ) );
	$ty += 38;
	$after = array();
	if ( (int) $inv['amount_paid'] ) {
		$after[] = array( 'Amount paid', -(int) $inv['amount_paid'], '#0f9f6e' );
	}
	if ( $refund ) {
		$after[] = array( 'Refunded', $refund, '#a855f7' );
	}
	$after[] = array( 'Balance due', (int) $inv['amount_due'] - (int) $inv['amount_paid'], $ink );
	foreach ( $after as $r ) {
		$ty += 19;
		$bold = 'Balance due' === $r[0];
		$pdf->text( $tx + 4, $ty, $r[0], $bold ? 'inter-semibold' : 'inter-regular', $bold ? 10 : 9.4, $bold ? $ink : $r[2] );
		$pdf->text( $R - 4, $ty, $money( max( $bold ? 0 : PHP_INT_MIN, $r[1] ) ), $bold ? 'sora-bold' : 'inter-semibold', $bold ? 11 : 9.4, $r[2], array( 'align' => 'right' ) );
	}

	/* Note beside the totals */
	$nw = $tx - $M - 28;
	$ny = $y + 20;
	$pdf->rect( $M, $ny, $nw, 88, '#f4f8fc', $line, 10, 0.6 );
	$pdf->gradient_rect( $M, $ny + 12, 3, 16, array( $hi, $accent ), 1.5 );
	$pdf->text( $M + 14, $ny + 23, 'YOUR SUBSCRIPTION', 'inter-semibold', 7.4, $link, array( 'spacing' => 1.2 ) );
	$note = $paid
		? 'Paid by card through Stripe. Update your card, download past invoices or change your plan from the "Manage subscription" link in your emails, or contact us with your client number.'
		: 'Payment is collected automatically from your card on file. If it fails we will email you a secure link to pay. Questions? Contact us with your client number.';
	$ly = $ny + 23;
	foreach ( $pdf->wrap( $note, 'inter-regular', 8.6, $nw - 28 ) as $l ) {
		$ly += 12.4;
		$pdf->text( $M + 14, $ly, $l, 'inter-regular', 8.6, $muted );
	}

	/* Stamp */
	if ( in_array( $status, array( 'paid', 'refunded', 'void' ), true ) ) {
		$stamp = 'paid' === $status ? 'PAID' : mb_strtoupper( $st[0] );
		$sx    = $M + 120;
		$sy    = max( $left_end, $right_end ) - 10;
		$sw    = $pdf->text_width( $stamp, 'sora-bold', 30, 3 ) + 36;
		$pdf->set_page( 0 );
		$pdf->save();
		$pdf->rotate( 12, $sx + 140, $sy );
		$pdf->alpha( 0.13 );
		$pdf->rect( $sx + 140 - $sw / 2, $sy - 25, $sw, 50, '', $st[1], 12, 3 );
		$pdf->text( $sx + 140, $sy + 11, $stamp, 'sora-bold', 30, $st[1], array( 'align' => 'center', 'spacing' => 3 ) );
		$pdf->restore();
		$pdf->set_page( $pdf->page_count() - 1 );
	}

	/* Footer */
	$count = $pdf->page_count();
	for ( $p = 0; $p < $count; $p++ ) {
		$pdf->set_page( $p );
		$fy = $H - 34;
		$pdf->line( $M, $fy - 14, $R, $fy - 14, $line, 0.6 );
		$foot = trim( (string) $s['invoice_footer'] );
		$fx   = $M;
		if ( '' !== $foot ) {
			$fx += $pdf->text( $M, $fy, $pdf->fit( $foot, 'inter-semibold', 8, 250 ), 'inter-semibold', 8, $ink ) + 10;
		}
		$right = $number . '   ·   Page ' . ( $p + 1 ) . ' of ' . $count;
		$pdf->text( $R, $fy, $right, 'inter-regular', 8, $faint, array( 'align' => 'right' ) );
		$mid   = trim( $company . ( $contact ? '  ·  ' . implode( '  ·  ', $contact ) : '' ) );
		$avail = $R - $pdf->text_width( $right, 'inter-regular', 8 ) - 16 - $fx;
		if ( $avail > 80 ) {
			$pdf->text( $fx, $fy, $pdf->fit( $mid, 'inter-regular', 8, $avail ), 'inter-regular', 8, $faint );
		}
	}
	return $pdf->output();
}

function mms_pdf_filename( array $inv ): string {
	return sanitize_file_name( 'Mine-Mirth-Invoice-' . ( '' !== $inv['number'] ? $inv['number'] : $inv['stripe_id'] ) ) . '.pdf';
}

/**
 * Stream an invoice PDF to the browser.
 */
function mms_send_pdf( array $inv, bool $inline = true ): void {
	$pdf = mms_invoice_pdf( $inv );
	while ( ob_get_level() ) {
		ob_end_clean();
	}
	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: ' . ( $inline ? 'inline' : 'attachment' ) . '; filename="' . mms_pdf_filename( $inv ) . '"' );
	header( 'Content-Length: ' . strlen( $pdf ) );
	header( 'X-Content-Type-Options: nosniff' );
	echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput -- binary PDF.
	exit;
}

/**
 * Write an invoice PDF to a temporary file (for email attachments). Returns the path or ''.
 */
function mms_invoice_pdf_file( array $inv ): string {
	$dir = trailingslashit( get_temp_dir() ) . 'mms-' . wp_generate_password( 8, false );
	if ( ! wp_mkdir_p( $dir ) ) {
		return '';
	}
	$path = $dir . '/' . mms_pdf_filename( $inv );
	return false !== file_put_contents( $path, mms_invoice_pdf( $inv ) ) ? $path : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
}
