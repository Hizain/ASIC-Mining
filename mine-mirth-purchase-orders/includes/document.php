<?php
/**
 * The purchase order PDF in Mine Mirth branding.
 *
 * @package MineMirthPurchaseOrders
 */

defined( 'ABSPATH' ) || exit;

/**
 * Why PDFs can not be made on this server, or '' when they can.
 */
function mmpo_pdf_problem(): string {
	if ( ! function_exists( 'mb_ord' ) || ! function_exists( 'mb_convert_encoding' ) ) {
		return __( 'Making PDFs needs the PHP "mbstring" extension, which is turned off on this server. Ask your host to turn it on.', 'mine-mirth-purchase-orders' );
	}
	if ( ! function_exists( 'gzcompress' ) ) {
		return __( 'Making PDFs needs the PHP "zlib" extension, which is turned off on this server. Ask your host to turn it on.', 'mine-mirth-purchase-orders' );
	}
	return '';
}

/**
 * File name for a purchase order PDF.
 */
function mmpo_pdf_filename( array $d ): string {
	$doc = mmpo_doc_types()[ $d['doc'] ] ?? __( 'Purchase order', 'mine-mirth-purchase-orders' );
	$raw = 'Mine Mirth ' . $doc . ' ' . $d['number'];
	return sanitize_file_name( str_replace( ' ', '-', $raw ) ) . '.pdf';
}

/**
 * Logo image file for the PDF header, or '' to draw the Mine Mirth mark.
 */
function mmpo_logo_file( bool $dark ): string {
	$mode = (string) mmpo_setting( 'logo' );
	$id   = 0;
	if ( 'custom' === $mode ) {
		$id = (int) mmpo_setting( 'logo_id' );
	} elseif ( 'site' === $mode ) {
		$dark_logo = (int) mmpo_theme_opt( 'logo_dark', 0 );
		$id        = $dark && $dark_logo ? $dark_logo : (int) get_theme_mod( 'custom_logo' );
	}
	if ( ! $id ) {
		return '';
	}
	$file = get_attached_file( $id );
	return $file && is_readable( $file ) && preg_match( '/\.(png|jpe?g|gif|webp)$/i', $file ) ? (string) $file : '';
}

/**
 * Lighter or darker version of a colour (amount -1..1).
 */
function mmpo_shade( string $hex, float $amount ): string {
	$c = MMPO_PDF::rgb( $hex );
	foreach ( $c as $i => $v ) {
		$c[ $i ] = $amount >= 0 ? $v + ( 1 - $v ) * $amount : $v * ( 1 + $amount );
	}
	return sprintf( '#%02x%02x%02x', (int) round( $c[0] * 255 ), (int) round( $c[1] * 255 ), (int) round( $c[2] * 255 ) );
}

/**
 * Quantity without needless decimals (2, 1.5).
 */
function mmpo_qty( float $q ): string {
	return rtrim( rtrim( number_format( $q, 2, '.', ',' ), '0' ), '.' );
}

/**
 * Build the PDF for a purchase order.
 */
function mmpo_pdf( array $d ): string {
	$s       = mmpo_settings();
	$pdf     = new MMPO_PDF( 'a4' === $s['paper'] ? 'a4' : 'letter' );
	$W       = $pdf->width();
	$H       = $pdf->height();
	$M       = 40.0;
	$R       = $W - $M;
	$t       = mmpo_totals( $d );
	$cur     = (string) $d['currency'];
	$money   = static fn( float $v ) => mmpo_money( $v, $cur );
	$status  = mmpo_status( (string) $d['status'] );
	$doc     = mmpo_doc_types()[ $d['doc'] ] ?? __( 'Purchase order', 'mine-mirth-purchase-orders' );
	$dark    = 'light' !== $s['header'];
	$primary = sanitize_hex_color( (string) $s['primary'] ) ? (string) $s['primary'] : '#35a8f6';
	$accent  = sanitize_hex_color( (string) $s['accent'] ) ? (string) $s['accent'] : '#2563eb';
	$hi      = sanitize_hex_color( (string) $s['highlight'] ) ? (string) $s['highlight'] : '#5ee1ff';
	$ink     = '#0a1626';
	$muted   = '#506279';
	$faint   = '#8a9bb0';
	$line    = '#e2e9f1';
	$soft    = '#f4f8fc';
	$navy    = '#071221';
	$link    = mmpo_shade( $accent, -0.1 );
	$brand   = trim( (string) get_bloginfo( 'name' ) );
	$brand   = '' !== $brand ? $brand : 'Mine Mirth';
	$company = trim( (string) $s['company'] );

	$pdf->set_info(
		array(
			'Title'   => $doc . ' ' . $d['number'],
			'Author'  => $company,
			'Subject' => $doc . ' ' . $d['number'] . ( '' !== $d['ship']['name'] ? ' · ' . $d['ship']['name'] : '' ),
			'Creator' => $company,
		)
	);

	$logo      = mmpo_logo_file( $dark );
	$logo_img  = $logo ? $pdf->load_image( $logo ) : null;
	$addr      = array_values( array_filter( array_map( 'trim', preg_split( "/\r\n|\r|\n/", (string) $s['address'] ) ) ) );
	$contact   = array_filter( array( trim( (string) $s['email'] ), trim( (string) $s['phone'] ), trim( (string) $s['website'] ) ) );
	$head_text = $dark ? '#ffffff' : $ink;
	$head_sub  = $dark ? '#a9bacd' : $muted;

	/**
	 * Mine Mirth mark or logo at (x, y) with the given height.
	 */
	$draw_logo = static function ( float $x, float $y, float $h ) use ( $pdf, $logo_img, $primary, $accent, $hi, $brand, $head_text ) {
		if ( $logo_img ) {
			$w = $h * $logo_img[1] / max( 1, $logo_img[2] );
			if ( $w > 200 ) {
				$w = 200;
				$h = $w * $logo_img[2] / max( 1, $logo_img[1] );
			}
			$pdf->image( $logo_img[0], $x, $y, $w, $h );
			return;
		}
		$pdf->gradient_rect( $x, $y, $h, $h, array( $hi, $primary, $accent ), $h * 0.3, array( 0.45 ) );
		$mark = (string) mmpo_theme_opt( 'logo_text_mark', 'M' );
		$mark = '' !== $mark ? mb_substr( $mark, 0, 2 ) : 'M';
		$pdf->text( $x + $h / 2, $y + $h / 2 + 0.73 * $h * 0.55 / 2, $mark, 'sora-bold', $h * 0.55, '#ffffff', array( 'align' => 'center' ) );
		$pdf->text( $x + $h + 11, $y + $h / 2 + 0.73 * $h * 0.48 / 2, $brand, 'sora-bold', $h * 0.48, $head_text, array( 'spacing' => -0.2 ) );
	};

	/**
	 * Status pill, right-aligned at $right. Returns its width.
	 */
	$pill = static function ( float $right, float $y ) use ( $pdf, $status ): float {
		$label = mb_strtoupper( $status['label'] );
		$tw    = $pdf->text_width( $label, 'inter-semibold', 7.5, 0.8 );
		$w     = $tw + 28;
		$x     = $right - $w;
		$pdf->rect( $x, $y, $w, 19, $status['color'], '', 9.5 );
		$pdf->circle( $x + 11, $y + 9.5, 2.6, '#ffffff' );
		$pdf->text( $x + 18, $y + 12.4, $label, 'inter-semibold', 7.5, '#ffffff', array( 'spacing' => 0.8 ) );
		return $w;
	};

	/* ---------------------------------------------------------- First page header */

	$pdf->add_page();
	$head_h = 108 + 12.5 * ( count( $addr ) + ( $contact ? 1 : 0 ) );
	$head_h = max( 150, $head_h );
	if ( $dark ) {
		$pdf->gradient_rect( 0, 0, $W, $head_h, array( '#040a13', $navy, '#0c2442' ), 0, array( 0.5 ) );
		$pdf->save();
		$pdf->clip( 0, 0, $W, $head_h );
		$pdf->alpha( 0.16 );
		$pdf->circle( $W - 40, 8, 150, $primary );
		$pdf->alpha( 0.10 );
		$pdf->circle( $W - 150, $head_h + 30, 110, $hi );
		$pdf->alpha( 0.08 );
		$pdf->circle( 120, -60, 120, $accent );
		$pdf->restore();
	} else {
		$pdf->rect( 0, 0, $W, $head_h, '#ffffff' );
	}
	$pdf->gradient_rect( 0, $head_h - 3, $W, 3, array( $hi, $primary, $accent ), 0, array( 0.45 ), false );

	$draw_logo( $M, 34, 38 );
	$y = 98;
	if ( '' !== $company ) {
		$pdf->text( $M, $y, $company, 'inter-semibold', 9.5, $head_text );
		$y += 13;
	}
	foreach ( $addr as $a ) {
		$pdf->text( $M, $y, $a, 'inter-regular', 9, $head_sub );
		$y += 12.5;
	}
	if ( $contact ) {
		$x = $M;
		foreach ( array_values( $contact ) as $i => $c ) {
			if ( $i ) {
				$x += $pdf->text( $x + 6, $y, '·', 'inter-semibold', 9, $hi ) + 12;
			}
			$w = $pdf->text( $x, $y, $c, 'inter-regular', 9, $dark ? '#dbe7f3' : $ink );
			if ( is_email( $c ) ) {
				$pdf->link( $x, $y - 9, $w, 12, 'mailto:' . $c );
			} elseif ( preg_match( '/^\+?[0-9 ().\-]{6,}$/', $c ) ) {
				$pdf->link( $x, $y - 9, $w, 12, 'tel:' . preg_replace( '/[^0-9+]/', '', $c ) );
			} elseif ( preg_match( '/^(https?:\/\/)?[a-z0-9.\-]+\.[a-z]{2,}/i', $c ) ) {
				$pdf->link( $x, $y - 9, $w, 12, ( str_starts_with( $c, 'http' ) ? '' : 'https://' ) . $c );
			}
			$x += $w;
		}
	}

	$pdf->text( $R, 62, mb_strtoupper( $doc ), 'sora-bold', 21, $head_text, array( 'align' => 'right', 'spacing' => 1.4 ) );
	$pdf->text( $R, 82, $d['number'], 'mono-medium', 11, $dark ? $hi : $link, array( 'align' => 'right', 'spacing' => 0.4 ) );
	if ( ! empty( $d['show_status'] ) ) {
		$pill( $R, 96 );
	}

	/* ---------------------------------------------------------- Ship to and details */

	$top  = $head_h + 26;
	$y    = $top;
	$colw = 268;
	$pdf->text( $M, $y, mb_strtoupper( __( 'Ship to', 'mine-mirth-purchase-orders' ) ), 'inter-semibold', 7.5, $link, array( 'spacing' => 1.4 ) );
	$y   += 19;
	$ship = $d['ship'];
	if ( '' !== $ship['name'] ) {
		foreach ( $pdf->wrap( $ship['name'], 'sora-semibold', 13, $colw ) as $l ) {
			$pdf->text( $M, $y, $l, 'sora-semibold', 13, $ink );
			$y += 16;
		}
	}
	if ( '' !== $ship['company'] ) {
		$pdf->text( $M, $y, $pdf->fit( $ship['company'], 'inter-semibold', 9.8, $colw ), 'inter-semibold', 9.8, $ink );
		$y += 14;
	}
	$y += 2;
	foreach ( mmpo_address_lines( $ship ) as $l ) {
		foreach ( $pdf->wrap( $l, 'inter-regular', 9.5, $colw ) as $ll ) {
			$pdf->text( $M, $y, $ll, 'inter-regular', 9.5, $muted );
			$y += 13.5;
		}
	}
	if ( '' !== $ship['phone'] || '' !== $ship['email'] ) {
		$y += 4;
		foreach ( array( $ship['phone'], $ship['email'] ) as $c ) {
			if ( '' !== $c ) {
				$pdf->text( $M, $y, $pdf->fit( $c, 'inter-regular', 9.5, $colw ), 'inter-regular', 9.5, $muted );
				$y += 13.5;
			}
		}
	}
	if ( '' === $ship['name'] && ! mmpo_address_lines( $ship ) ) {
		$pdf->text( $M, $y, __( 'No ship-to address yet', 'mine-mirth-purchase-orders' ), 'inter-regular', 9.5, $faint );
		$y += 14;
	}
	$left_end = $y;

	// Balance card and order details on the right.
	$cx = $W - $M - 228;
	$cw = 228;
	$y  = $top - 12;
	$pdf->gradient_rect( $cx, $y, $cw, 66, array( '#06101d', '#0d2744' ), 12 );
	$pdf->save();
	$pdf->clip( $cx, $y, $cw, 66, 12 );
	$pdf->alpha( 0.22 );
	$pdf->circle( $cx + $cw - 10, $y + 6, 46, $primary );
	$pdf->restore();
	$fully_paid = $t['paid'] > 0 && $t['balance'] <= 0;
	$big_label  = $fully_paid ? __( 'Paid in full', 'mine-mirth-purchase-orders' ) : ( $t['paid'] > 0 ? __( 'Balance due', 'mine-mirth-purchase-orders' ) : __( 'Total due', 'mine-mirth-purchase-orders' ) );
	$big_value  = $fully_paid ? $t['paid'] : ( $t['paid'] > 0 ? $t['balance'] : $t['total'] );
	$pdf->text( $cx + 16, $y + 21, mb_strtoupper( $big_label ), 'inter-semibold', 7.5, $hi, array( 'spacing' => 1.3 ) );
	$amount_size = 21;
	while ( $amount_size > 12 && $pdf->text_width( $money( $big_value ), 'sora-bold', $amount_size ) > $cw - 32 ) {
		--$amount_size;
	}
	$pdf->text( $cx + 16, $y + 48, $money( $big_value ), 'sora-bold', $amount_size, '#ffffff' );
	if ( ! $fully_paid && '' !== $d['due'] ) {
		$pdf->text( $cx + $cw - 16, $y + 21, sprintf( /* translators: %s: date */ __( 'by %s', 'mine-mirth-purchase-orders' ), mmpo_date( $d['due'] ) ), 'inter-regular', 8, '#a9bacd', array( 'align' => 'right' ) );
	}

	$rows = array( array( __( 'Date issued', 'mine-mirth-purchase-orders' ), mmpo_date( $d['date'] ) ) );
	if ( '' !== $d['due'] ) {
		$rows[] = array( 'quote' === $d['doc'] ? __( 'Valid until', 'mine-mirth-purchase-orders' ) : __( 'Payment due', 'mine-mirth-purchase-orders' ), mmpo_date( $d['due'] ) );
	}
	if ( '' !== $d['payment_terms'] ) {
		$rows[] = array( __( 'Payment terms', 'mine-mirth-purchase-orders' ), $d['payment_terms'] );
	}
	if ( '' !== $d['reference'] ) {
		$rows[] = array( __( 'Your reference', 'mine-mirth-purchase-orders' ), $d['reference'] );
	}
	if ( ! empty( $d['show_status'] ) ) {
		$rows[] = array( __( 'Status', 'mine-mirth-purchase-orders' ), $status['label'] . ( ! empty( $d['status_time'] ) ? ' · ' . wp_date( 'M j, Y', (int) $d['status_time'] ) : '' ), $status['color'] );
	}
	if ( '' !== $d['tracking']['number'] || '' !== $d['tracking']['carrier'] ) {
		$rows[] = array( __( 'Tracking', 'mine-mirth-purchase-orders' ), trim( $d['tracking']['carrier'] . ' ' . $d['tracking']['number'] ) );
	}
	$y += 66 + 6;
	foreach ( $rows as $i => $row ) {
		$y += 18;
		$pdf->text( $cx + 2, $y, $row[0], 'inter-regular', 8.8, $muted );
		$vx = $cx + $cw - 2;
		if ( isset( $row[2] ) ) {
			$vw = $pdf->text_width( $row[1], 'inter-semibold', 8.8 );
			$pdf->circle( $vx - $vw - 7, $y - 3, 2.7, $row[2] );
		}
		$pdf->text( $vx, $y, $pdf->fit( $row[1], 'inter-semibold', 8.8, $cw - 92 ), 'inter-semibold', 8.8, $ink, array( 'align' => 'right' ) );
		$pdf->line( $cx, $y + 7, $cx + $cw, $y + 7, $line, 0.6 );
	}
	$right_end = $y + 7;

	/* ---------------------------------------------------------- Items */

	$bottom = $H - 56;
	$c_num  = $M + 12;
	$c_name = $M + 38;
	$c_qty  = $W - $M - 186;
	$c_unit = $W - $M - 96;
	$c_amt  = $R - 12;
	$name_w = $c_qty - 44 - $c_name;

	$table_head = static function ( float $y ) use ( $pdf, $M, $W, $c_num, $c_name, $c_qty, $c_unit, $c_amt, $muted ): float {
		$pdf->rect( $M, $y, $W - 2 * $M, 26, '#edf3f9', '', 8 );
		$lab = static fn( float $x, string $t, string $align = 'left' ) => $pdf->text( $x, $y + 16.5, mb_strtoupper( $t ), 'inter-semibold', 7.2, $muted, array( 'spacing' => 1, 'align' => $align ) );
		$lab( $c_num, '#' );
		$lab( $c_name, __( 'Product', 'mine-mirth-purchase-orders' ) );
		$lab( $c_qty, __( 'Qty', 'mine-mirth-purchase-orders' ), 'right' );
		$lab( $c_unit, __( 'Unit price', 'mine-mirth-purchase-orders' ), 'right' );
		$lab( $c_amt, __( 'Amount', 'mine-mirth-purchase-orders' ), 'right' );
		return $y + 26;
	};

	$continued = static function () use ( $pdf, $W, $M, $R, $dark, $navy, $primary, $accent, $hi, $draw_logo, $doc, $d, $head_text ): float {
		$pdf->add_page();
		if ( $dark ) {
			$pdf->gradient_rect( 0, 0, $W, 70, array( '#040a13', $navy, '#0c2442' ), 0, array( 0.5 ) );
		}
		$pdf->gradient_rect( 0, 67, $W, 3, array( $hi, $primary, $accent ), 0, array( 0.45 ), false );
		$draw_logo( $M, 18, 30 );
		$pdf->text( $R, 32, mb_strtoupper( $doc ) . '  ' . $d['number'], 'sora-bold', 11, $head_text, array( 'align' => 'right', 'spacing' => 0.8 ) );
		$pdf->text( $R, 47, __( 'continued', 'mine-mirth-purchase-orders' ), 'inter-regular', 8.5, $dark ? '#a9bacd' : '#506279', array( 'align' => 'right' ) );
		return 96;
	};

	$y = max( $left_end, $right_end ) + 22;
	$y = $table_head( $y );
	$rows_top   = $y;
	$rows_end   = $y;
	$first_page = true;
	if ( ! $d['items'] ) {
		$pdf->text( $c_name, $y + 24, __( 'No products added yet.', 'mine-mirth-purchase-orders' ), 'inter-regular', 9.5, $faint );
		$y += 40;
	}
	foreach ( $d['items'] as $i => $item ) {
		$name_lines = $pdf->wrap( $item['name'], 'inter-semibold', 10, $name_w );
		$desc_lines = '' !== trim( $item['desc'] ) ? $pdf->wrap( $item['desc'], 'inter-regular', 8.6, $name_w ) : array();
		$h          = 14 + 13 * count( $name_lines ) + ( '' !== $item['sku'] ? 12 : 0 ) + 11.5 * count( $desc_lines ) + 8;
		if ( $y + $h > $bottom ) {
			$first_page = false;
			$y          = $table_head( $continued() );
		}
		$ry = $y + 22;
		$pdf->text( $c_num, $ry, str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ), 'mono-medium', 8, $faint );
		foreach ( $name_lines as $k => $l ) {
			$pdf->text( $c_name, $ry + 13 * $k, $l, 'inter-semibold', 10, $ink );
		}
		$pdf->text( $c_qty, $ry, mmpo_qty( (float) $item['qty'] ), 'inter-regular', 9.6, $ink, array( 'align' => 'right' ) );
		$pdf->text( $c_unit, $ry, $money( (float) $item['price'] ), 'inter-regular', 9.6, $ink, array( 'align' => 'right' ) );
		$pdf->text( $c_amt, $ry, $money( (float) ( $t['lines'][ $i ] ?? 0 ) ), 'inter-semibold', 9.6, $ink, array( 'align' => 'right' ) );
		$ly = $ry + 13 * ( count( $name_lines ) - 1 );
		if ( '' !== $item['sku'] ) {
			$ly += 12.5;
			$pdf->text( $c_name, $ly, 'SKU ' . $item['sku'], 'mono-medium', 7.4, $faint, array( 'spacing' => 0.2 ) );
		}
		foreach ( $desc_lines as $l ) {
			$ly += 11.5;
			$pdf->text( $c_name, $ly, $l, 'inter-regular', 8.6, $muted );
		}
		$y = $ly + 12;
		$pdf->line( $M + 8, $y, $R - 8, $y, $line, 0.6 );
		if ( $first_page ) {
			$rows_end = $y;
		}
	}

	/* ---------------------------------------------------------- Stamp (over the product rows on page 1, never the totals) */

	if ( ! empty( $d['show_status'] ) && '' !== trim( $status['stamp'] ) ) {
		$here  = $pdf->page_count() - 1;
		$stamp = mb_strtoupper( trim( $status['stamp'] ) );
		$size  = 38;
		$sw    = $pdf->text_width( $stamp, 'sora-bold', $size, 4 ) + 44;
		$sx    = $c_name + ( $c_qty - $c_name ) * 0.55;
		$sy    = $rows_top + max( 34, ( $rows_end - $rows_top ) / 2 );
		$pdf->set_page( 0 );
		$pdf->save();
		$pdf->rotate( 12, $sx, $sy );
		$pdf->alpha( 0.15 );
		$pdf->rect( $sx - $sw / 2, $sy - 32, $sw, 64, '', $status['color'], 14, 3.5 );
		$pdf->rect( $sx - $sw / 2 + 6, $sy - 26, $sw - 12, 52, '', $status['color'], 10, 1 );
		$pdf->text( $sx, $sy + 13.5, $stamp, 'sora-bold', $size, $status['color'], array( 'align' => 'center', 'spacing' => 4 ) );
		$pdf->restore();
		$pdf->set_page( $here );
	}

	/* ---------------------------------------------------------- Totals and notes */

	$tw   = 236;
	$tx   = $R - $tw;
	$rows = array( array( __( 'Subtotal', 'mine-mirth-purchase-orders' ), $t['subtotal'], '' ) );
	if ( $t['shipping'] || '' !== trim( (string) $d['shipping']['label'] ) && $d['items'] ) {
		$rows[] = array( '' !== trim( (string) $d['shipping']['label'] ) ? $d['shipping']['label'] : __( 'Shipping', 'mine-mirth-purchase-orders' ), $t['shipping'], '' );
	}
	foreach ( $t['fees'] as $f ) {
		$rows[] = array( $f['label'], $f['amount'], null !== $f['rate'] ? mmpo_qty( $f['rate'] ) . '%' : '' );
	}
	foreach ( $t['taxes'] as $f ) {
		$rows[] = array( $f['label'], $f['amount'], null !== $f['rate'] ? mmpo_qty( $f['rate'] ) . '%' : '' );
	}
	$totals_h = 6 + 19 * count( $rows ) + 10 + 38 + ( $t['paid'] > 0 ? 19 * 2 + 8 : 0 );

	$notes = array();
	if ( '' !== trim( $d['notes'] ) ) {
		$notes[] = array( __( 'Notes', 'mine-mirth-purchase-orders' ), $d['notes'] );
	}
	if ( '' !== trim( $d['payment'] ) && ! $fully_paid ) {
		$notes[] = array( __( 'How to pay', 'mine-mirth-purchase-orders' ), $d['payment'] );
	}

	// The totals stay together: start a new page when they do not fit.
	$y += 14;
	if ( $y + $totals_h > $bottom ) {
		$y = $continued();
	}
	$block = $y;
	$ty    = $y;
	foreach ( $rows as $row ) {
		$ty   += 19;
		$label = $row[0] . ( '' !== $row[2] ? ' (' . $row[2] . ')' : '' );
		$value = $money( (float) $row[1] );
		$room  = $tw - 8 - $pdf->text_width( $value, 'inter-semibold', 9.4 ) - 14;
		$pdf->text( $tx + 4, $ty, $pdf->fit( $label, 'inter-regular', 9.4, $room ), 'inter-regular', 9.4, $muted );
		$pdf->text( $R - 4, $ty, $value, 'inter-semibold', 9.4, $ink, array( 'align' => 'right' ) );
	}
	$ty += 10;
	$pdf->gradient_rect( $tx, $ty, $tw, 38, array( '#06101d', '#0d2744' ), 10 );
	$pdf->gradient_rect( $tx + 14, $ty + 10, 3, 18, array( $hi, $accent ), 1.5 );
	$pdf->text( $tx + 26, $ty + 23.5, __( 'Total', 'mine-mirth-purchase-orders' ), 'inter-semibold', 10, '#c6d4e3' );
	$size = 16;
	while ( $size > 10 && $pdf->text_width( $money( $t['total'] ), 'sora-bold', $size ) > $tw - 90 ) {
		--$size;
	}
	$pdf->text( $R - 14, $ty + 25, $money( $t['total'] ), 'sora-bold', $size, '#ffffff', array( 'align' => 'right' ) );
	$ty += 38;
	if ( $t['paid'] > 0 ) {
		$ty        += 19;
		$paid_label = __( 'Amount paid', 'mine-mirth-purchase-orders' ) . ( '' !== $d['paid_note'] ? ' · ' . $d['paid_note'] : '' );
		$pdf->text( $tx + 4, $ty, $pdf->fit( $paid_label, 'inter-regular', 9.4, $tw - 100 ), 'inter-regular', 9.4, '#0f9f6e' );
		$pdf->text( $R - 4, $ty, '-' . $money( $t['paid'] ), 'inter-semibold', 9.4, '#0f9f6e', array( 'align' => 'right' ) );
		$ty += 19;
		$pdf->text( $tx + 4, $ty, __( 'Balance due', 'mine-mirth-purchase-orders' ), 'inter-semibold', 10, $ink );
		$pdf->text( $R - 4, $ty, $money( max( 0, $t['balance'] ) ), 'sora-bold', 11, $ink, array( 'align' => 'right' ) );
		$ty += 8;
	}

	// Notes sit beside the totals while there is room, then continue below them at full width.
	$ny     = $block + 8;
	$beside = true;
	foreach ( $notes as $n ) {
		$bw    = $beside ? $tx - $M - 28 : $W - 2 * $M;
		$lines = $pdf->wrap( $n[1], 'inter-regular', 8.8, $bw - 28 );
		$bh    = 34 + 12.6 * count( $lines );
		if ( $beside && $ny + $bh > $bottom ) {
			$beside = false;
			$ny     = max( $ny, $ty ) + 16;
			$bw     = $W - 2 * $M;
			$lines  = $pdf->wrap( $n[1], 'inter-regular', 8.8, $bw - 28 );
			$bh     = 34 + 12.6 * count( $lines );
		}
		if ( $ny + $bh > $bottom ) {
			$ny = $continued();
		}
		$pdf->rect( $M, $ny, $bw, $bh, $soft, $line, 10, 0.6 );
		$pdf->gradient_rect( $M, $ny + 12, 3, 16, array( $hi, $accent ), 1.5 );
		$pdf->text( $M + 14, $ny + 23, mb_strtoupper( $n[0] ), 'inter-semibold', 7.4, $link, array( 'spacing' => 1.2 ) );
		$ly = $ny + 23;
		foreach ( $lines as $l ) {
			$ly += 12.6;
			$pdf->text( $M + 14, $ly, $l, 'inter-regular', 8.8, $muted );
		}
		$ny += $bh + 10;
	}

	/* ---------------------------------------------------------- Terms */

	// Beside the totals when they fit there, otherwise below at full width.
	$terms_done = '' === trim( $d['terms'] );
	if ( ! $terms_done && $beside ) {
		$lw = $tx - $M - 28;
		$tl = $pdf->wrap( $d['terms'], 'inter-regular', 7.8, $lw );
		$t0 = $ny + ( $notes ? 6 : 10 );
		if ( $t0 + 12 + 11 * count( $tl ) <= $bottom ) {
			$pdf->text( $M + 2, $t0 + 8, mb_strtoupper( __( 'Terms', 'mine-mirth-purchase-orders' ) ), 'inter-semibold', 7.2, $faint, array( 'spacing' => 1.2 ) );
			$ly = $t0 + 8;
			foreach ( $tl as $l ) {
				$ly += 11;
				$pdf->text( $M + 2, $ly, $l, 'inter-regular', 7.8, $faint );
			}
			$ny         = $ly;
			$terms_done = true;
		}
	}
	$y = max( $ty, $ny ) + 18;

	if ( ! $terms_done ) {
		$lines = $pdf->wrap( $d['terms'], 'inter-regular', 7.8, $W - 2 * $M );
		if ( $y + 26 > $bottom ) {
			$y = $continued();
		}
		$pdf->line( $M, $y, $R, $y, $line, 0.6 );
		$y += 18;
		$pdf->text( $M, $y, mb_strtoupper( __( 'Terms', 'mine-mirth-purchase-orders' ) ), 'inter-semibold', 7.2, $faint, array( 'spacing' => 1.2 ) );
		foreach ( $lines as $l ) {
			$y += 11;
			if ( $y > $bottom ) {
				$y = $continued() + 4;
			}
			$pdf->text( $M, $y, $l, 'inter-regular', 7.8, $faint );
		}
	}

	/* ---------------------------------------------------------- Footer on every page */

	$count = $pdf->page_count();
	$foot  = trim( (string) $s['footer'] );
	for ( $p = 0; $p < $count; $p++ ) {
		$pdf->set_page( $p );
		$fy = $H - 34;
		$pdf->line( $M, $fy - 14, $R, $fy - 14, $line, 0.6 );
		$fx = $M;
		if ( '' !== $foot ) {
			$fx += $pdf->text( $M, $fy, $pdf->fit( $foot, 'inter-semibold', 8, 260 ), 'inter-semibold', 8, $ink ) + 10;
		}
		$right = $d['number'] . '   ·   ' . sprintf( /* translators: 1: page, 2: pages */ __( 'Page %1$d of %2$d', 'mine-mirth-purchase-orders' ), $p + 1, $count );
		$pdf->text( $R, $fy, $right, 'inter-regular', 8, $faint, array( 'align' => 'right' ) );
		$mid = trim( $company . ( $contact ? '  ·  ' . implode( '  ·  ', array_slice( array_values( $contact ), 0, 2 ) ) : '' ) );
		$avail = $R - $pdf->text_width( $right, 'inter-regular', 8 ) - 16 - $fx;
		if ( $avail > 80 ) {
			$pdf->text( $fx, $fy, $pdf->fit( $mid, 'inter-regular', 8, $avail ), 'inter-regular', 8, $faint );
		}
	}

	return $pdf->output();
}
