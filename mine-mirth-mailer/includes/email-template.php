<?php
/**
 * Email design in the website's style: colours, fonts and logo from the
 * Mine Mirth theme, built with tables and inline styles so it holds up in
 * Gmail, Outlook, Apple Mail and phone apps.
 *
 * Placeholders filled per recipient by mmm_personalize():
 *   %%MMM_TOKEN%%  %%MMM_OPEN%%  %%MMM_UNSUB%%  %%MMM_VIEW%%  %%MMM_REPLY%%
 *   {first_name}  {first_name|there}  {email}
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Design tokens from the theme (fallbacks match the Mine Mirth defaults).
 */
function mmm_design(): array {
	static $d = null;
	if ( null !== $d ) {
		return $d;
	}
	$opt  = static fn( $k, $def ) => function_exists( 'mm_opt' ) && mm_opt( $k ) ? (string) mm_opt( $k ) : $def;
	$font = static fn( $name, $fallback ) => "'" . $name . "', " . $fallback;
	$d    = array(
		'primary'  => $opt( 'color_primary', '#35a8f6' ),
		'accent'   => $opt( 'color_accent', '#2563eb' ),
		'accent2'  => $opt( 'color_accent_2', '#5ee1ff' ),
		'on'       => $opt( 'color_on_primary', '#03111f' ),
		'dark'     => $opt( 'dark_bg', '#050b14' ),
		'dark2'    => $opt( 'dark_surface', '#0b1624' ),
		'text'     => $opt( 'light_text', '#0a1626' ),
		'muted'    => $opt( 'light_muted', '#506279' ),
		'border'   => $opt( 'light_border', '#d9e4ef' ),
		'page'     => '#eef3f9',
		'soft'     => '#f4f8fc',
		'success'  => $opt( 'color_success', '#12b981' ),
		'warning'  => $opt( 'color_warning', '#f5a524' ),
		'danger'   => $opt( 'color_danger', '#f04461' ),
		'fh'       => $font( $opt( 'font_heading', 'Sora' ), "'Segoe UI', Helvetica, Arial, sans-serif" ),
		'fb'       => $font( $opt( 'font_body', 'Inter' ), "'Segoe UI', Helvetica, Arial, sans-serif" ),
		'fm'       => "'JetBrains Mono', Menlo, Consolas, monospace",
		'site'     => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'brand'    => (string) mmm_opt( 'brand_name' ),
	);
	$d['fonts_url'] = 'https://fonts.googleapis.com/css2?family=' . rawurlencode( $opt( 'font_heading', 'Sora' ) ) . ':wght@600;700&family=' . rawurlencode( $opt( 'font_body', 'Inter' ) ) . ':wght@400;600&display=swap';
	$d['logo']      = mmm_logo_url();
	return $d;
}

/**
 * Logo for the dark email header: a chosen email logo, then the dark-mode logos, then the site logo.
 */
function mmm_logo_url(): string {
	$ids = array(
		(int) mmm_opt( 'logo_id' ),
		(int) get_theme_mod( 'mmlg_header_dark' ),
		(int) get_theme_mod( 'mm_logo_dark' ),
		(int) get_theme_mod( 'custom_logo' ),
	);
	foreach ( $ids as $id ) {
		if ( $id ) {
			$url = wp_get_attachment_image_url( $id, 'medium' );
			if ( $url ) {
				return $url;
			}
		}
	}
	return '';
}

function mmm_esc( string $s ): string {
	return esc_html( $s );
}

/* ---------------------------------------------------------------------------
 * Components
 * ------------------------------------------------------------------------- */

function mmm_c_spacer( int $h = 24 ): string {
	return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="height:' . (int) $h . 'px;line-height:' . (int) $h . 'px;font-size:0">&nbsp;</td></tr></table>';
}

function mmm_c_button( string $text, string $url, string $style = 'primary', string $align = 'left' ): string {
	$d = mmm_design();
	if ( '' === trim( $text ) || '' === trim( $url ) ) {
		return '';
	}
	if ( 'ghost' === $style ) {
		$td = 'border:1px solid ' . $d['border'] . ';border-radius:999px;background:#ffffff';
		$a  = 'color:' . $d['text'];
	} elseif ( 'light' === $style ) {
		$td = 'border-radius:999px;background:#ffffff';
		$a  = 'color:' . $d['text'];
	} else {
		$td = 'border-radius:999px;background:' . $d['primary'] . ';background-image:linear-gradient(120deg,' . $d['accent2'] . ' 0%,' . $d['primary'] . ' 45%,' . $d['accent'] . ' 100%)';
		$a  = 'color:' . $d['on'];
	}
	return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="' . esc_attr( $align ) . '" style="border-collapse:separate;margin:0' . ( 'center' === $align ? ' auto' : '' ) . '"><tr><td bgcolor="' . esc_attr( 'ghost' === $style || 'light' === $style ? '#ffffff' : $d['primary'] ) . '" style="' . esc_attr( $td ) . '"><a href="' . esc_url( $url ) . '" style="display:inline-block;padding:13px 26px;font-family:' . esc_attr( $d['fb'] ) . ';font-size:15px;font-weight:700;line-height:1;text-decoration:none;border-radius:999px;' . esc_attr( $a ) . '">' . esc_html( $text ) . '</a></td></tr></table>';
}

function mmm_c_heading( string $text, int $level = 2, string $eyebrow = '', string $align = 'left' ): string {
	$d    = mmm_design();
	$size = array( 1 => 30, 2 => 24, 3 => 19, 4 => 17 )[ max( 1, min( 4, $level ) ) ];
	$out  = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="' . esc_attr( $align ) . '" style="padding:0 0 12px">';
	if ( $eyebrow ) {
		$out .= '<div style="font-family:' . esc_attr( $d['fm'] ) . ';font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:' . esc_attr( $d['primary'] ) . ';padding-bottom:8px">' . esc_html( $eyebrow ) . '</div>';
	}
	$out .= '<div style="font-family:' . esc_attr( $d['fh'] ) . ';font-size:' . $size . 'px;line-height:1.2;font-weight:700;color:' . esc_attr( $d['text'] ) . ';letter-spacing:-0.3px">' . wp_kses( $text, mmm_inline_tags() ) . '</div>';
	return $out . '</td></tr></table>';
}

function mmm_inline_tags(): array {
	return array(
		'a'      => array( 'href' => true, 'title' => true, 'style' => true ),
		'strong' => array(),
		'b'      => array(),
		'em'     => array(),
		'i'      => array(),
		'u'      => array(),
		's'      => array(),
		'br'     => array(),
		'span'   => array( 'style' => true ),
		'code'   => array(),
		'mark'   => array( 'style' => true ),
		'sub'    => array(),
		'sup'    => array(),
	);
}

/**
 * Give links inside text the brand colour.
 */
function mmm_style_links( string $html ): string {
	$d = mmm_design();
	return (string) preg_replace( '/<a\s(?![^>]*style=)/i', '<a style="color:' . $d['primary'] . ';font-weight:600;text-decoration:underline" ', $html );
}

function mmm_c_text( string $html, string $align = 'left', int $size = 16 ): string {
	$d    = mmm_design();
	$html = mmm_style_links( wp_kses( $html, mmm_inline_tags() ) );
	return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="' . esc_attr( $align ) . '" style="padding:0 0 16px;font-family:' . esc_attr( $d['fb'] ) . ';font-size:' . (int) $size . 'px;line-height:1.65;color:#2b3a4e">' . $html . '</td></tr></table>';
}

function mmm_c_image( string $src, string $alt = '', string $link = '', string $caption = '' ): string {
	if ( ! $src ) {
		return '';
	}
	$d   = mmm_design();
	$img = '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '" width="536" style="display:block;width:100%;max-width:536px;height:auto;border:0;border-radius:14px">';
	if ( $link ) {
		$img = '<a href="' . esc_url( $link ) . '">' . $img . '</a>';
	}
	$cap = $caption ? '<div style="padding-top:8px;font-family:' . esc_attr( $d['fb'] ) . ';font-size:13px;color:' . esc_attr( $d['muted'] ) . '">' . esc_html( $caption ) . '</div>' : '';
	return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding:0 0 20px">' . $img . $cap . '</td></tr></table>';
}

function mmm_c_divider(): string {
	$d = mmm_design();
	return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding:8px 0 24px"><div style="height:1px;line-height:1px;font-size:0;background:' . esc_attr( $d['border'] ) . '">&nbsp;</div></td></tr></table>';
}

function mmm_c_hero( string $eyebrow, string $title, string $text, string $button = '', string $url = '', string $image = '' ): string {
	$out = '';
	if ( $image ) {
		$out .= mmm_c_image( $image, $title, $url );
	}
	$out .= mmm_c_heading( $title, 1, $eyebrow );
	if ( $text ) {
		$out .= mmm_c_text( $text, 'left', 17 );
	}
	if ( $button && $url ) {
		$out .= mmm_c_button( $button, $url ) . mmm_c_spacer( 8 );
	}
	return $out . mmm_c_spacer( 12 );
}

function mmm_c_list( array $items, bool $ordered = false ): string {
	$d    = mmm_design();
	$rows = '';
	$i    = 0;
	foreach ( $items as $item ) {
		++$i;
		$mark  = $ordered ? $i . '.' : '&#9679;';
		$rows .= '<tr><td valign="top" style="width:22px;padding:0 0 8px;font-family:' . esc_attr( $d['fb'] ) . ';font-size:' . ( $ordered ? '15' : '10' ) . 'px;line-height:' . ( $ordered ? '1.65' : '2.6' ) . ';color:' . esc_attr( $d['primary'] ) . ';font-weight:700">' . $mark . '</td><td style="padding:0 0 8px;font-family:' . esc_attr( $d['fb'] ) . ';font-size:16px;line-height:1.65;color:#2b3a4e">' . mmm_style_links( wp_kses( $item, mmm_inline_tags() ) ) . '</td></tr>';
	}
	return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 12px">' . $rows . '</table>';
}

function mmm_c_quote( string $html, string $cite = '' ): string {
	$d = mmm_design();
	return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding:4px 0 20px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="border-left:4px solid ' . esc_attr( $d['primary'] ) . ';background:' . esc_attr( $d['soft'] ) . ';padding:16px 20px;border-radius:0 12px 12px 0;font-family:' . esc_attr( $d['fb'] ) . ';font-size:17px;line-height:1.6;font-style:italic;color:' . esc_attr( $d['text'] ) . '">' . mmm_style_links( wp_kses( $html, mmm_inline_tags() ) ) . ( $cite ? '<div style="padding-top:8px;font-style:normal;font-size:13px;color:' . esc_attr( $d['muted'] ) . '">— ' . esc_html( $cite ) . '</div>' : '' ) . '</td></tr></table></td></tr></table>';
}

/**
 * Product data for a card.
 */
function mmm_product_card_data( int $id ): ?array {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return null;
	}
	$p = wc_get_product( $id );
	if ( ! $p || 'publish' !== $p->get_status() ) {
		return null;
	}
	$img   = wp_get_attachment_image_url( (int) $p->get_image_id(), 'woocommerce_thumbnail' );
	$img   = $img ? $img : ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_thumbnail' ) : '' );
	$price = html_entity_decode( wp_strip_all_tags( wc_price( (float) $p->get_price() ) ), ENT_QUOTES );
	$was   = $p->is_on_sale() && $p->get_regular_price() ? html_entity_decode( wp_strip_all_tags( wc_price( (float) $p->get_regular_price() ) ), ENT_QUOTES ) : '';
	$stock = $p->get_stock_status();
	$specs = array();
	if ( function_exists( 'mm_product_mining_specs' ) ) {
		$s = mm_product_mining_specs( $p );
		foreach ( (array) ( $s['raw'] ?? array() ) as $v ) {
			if ( '' !== trim( (string) $v ) ) {
				$specs[] = trim( (string) $v );
			}
		}
	}
	return array(
		'name'  => $p->get_name(),
		'url'   => get_permalink( $id ),
		'img'   => (string) $img,
		'price' => $p->get_price() !== '' ? $price : '',
		'was'   => $was,
		'stock' => 'instock' === $stock ? 'instock' : ( 'outofstock' === $stock ? 'outofstock' : 'preorder' ),
		'specs' => $specs,
	);
}

function mmm_c_products( array $ids, string $title = '', string $eyebrow = '', int $columns = 2 ): string {
	$d     = mmm_design();
	$cards = array();
	foreach ( $ids as $id ) {
		$c = mmm_product_card_data( (int) $id );
		if ( $c ) {
			$cards[] = $c;
		}
	}
	if ( ! $cards ) {
		return '';
	}
	$labels = array( 'instock' => array( __( 'In stock', 'mine-mirth-mailer' ), $d['success'] ), 'preorder' => array( __( 'Pre-order', 'mine-mirth-mailer' ), $d['warning'] ), 'outofstock' => array( __( 'Sold out', 'mine-mirth-mailer' ), $d['muted'] ) );
	$image  = static fn( array $c, int $max ) => '<a href="' . esc_url( $c['url'] ) . '"><img src="' . esc_url( $c['img'] ) . '" alt="' . esc_attr( $c['name'] ) . '" width="' . $max . '" style="display:block;width:100%;max-width:' . $max . 'px;height:auto;margin:0 auto;border:0;border-radius:10px"></a>';
	$detail = static function ( array $c, int $size ) use ( $d, $labels ): string {
		$lab  = $labels[ $c['stock'] ];
		$spec = '';
		foreach ( array_slice( $c['specs'], 0, 3 ) as $s ) {
			$spec .= '<span style="display:inline-block;margin:0 4px 4px 0;padding:3px 7px;border-radius:6px;background:' . esc_attr( $d['soft'] ) . ';font-family:' . esc_attr( $d['fm'] ) . ';font-size:11px;color:' . esc_attr( $d['muted'] ) . '">' . esc_html( $s ) . '</span>';
		}
		$out  = '<div style="font-family:' . esc_attr( $d['fb'] ) . ';font-size:12px;font-weight:700;color:' . esc_attr( $lab[1] ) . ';padding-bottom:6px">&#9679; ' . esc_html( $lab[0] ) . '</div>';
		$out .= '<a href="' . esc_url( $c['url'] ) . '" style="font-family:' . esc_attr( $d['fh'] ) . ';font-size:' . $size . 'px;line-height:1.3;font-weight:700;color:' . esc_attr( $d['text'] ) . ';text-decoration:none">' . esc_html( $c['name'] ) . '</a>';
		$out .= $spec ? '<div style="padding-top:8px">' . $spec . '</div>' : '';
		if ( $c['price'] ) {
			$out .= '<div style="padding:8px 0 12px;font-family:' . esc_attr( $d['fm'] ) . ';font-size:' . ( $size + 1 ) . 'px;font-weight:700;color:' . esc_attr( $d['text'] ) . '">' . esc_html( $c['price'] ) . ( $c['was'] ? ' <span style="font-size:13px;font-weight:400;color:' . esc_attr( $d['muted'] ) . ';text-decoration:line-through">' . esc_html( $c['was'] ) . '</span>' : '' ) . '</div>';
		} else {
			$out .= '<div style="height:12px;line-height:12px;font-size:1px">&nbsp;</div>';
		}
		return $out . mmm_c_button( 'outofstock' === $c['stock'] ? __( 'See details', 'mine-mirth-mailer' ) : ( $c['specs'] ? __( 'View miner', 'mine-mirth-mailer' ) : __( 'View product', 'mine-mirth-mailer' ) ), $c['url'], 'outofstock' === $c['stock'] ? 'ghost' : 'primary' );
	};
	$card    = 'border-collapse:separate;border:1px solid ' . esc_attr( $d['border'] ) . ';border-radius:16px;background:#ffffff';
	$out     = $title ? mmm_c_heading( $title, 2, $eyebrow ) : '';
	$columns = 1 === count( $cards ) ? 1 : max( 1, min( 3, $columns ) );

	// One miner: a wide spotlight card, image beside the details (stacks on phones).
	if ( 1 === $columns ) {
		foreach ( $cards as $c ) {
			$out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;' . $card . '"><tr>';
			$out .= '<td class="mmm-col mmm-spot" valign="middle" width="44%" style="width:44%;padding:16px">' . $image( $c, 220 ) . '</td>';
			$out .= '<td class="mmm-col mmm-spot" valign="middle" style="padding:16px 20px 18px 4px">' . $detail( $c, 19 ) . '</td>';
			$out .= '</tr></table>';
		}
		return $out;
	}

	$width = (int) floor( 100 / $columns );
	$out  .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 12px">';
	foreach ( array_chunk( $cards, $columns ) as $row ) {
		$out .= '<tr>';
		foreach ( $row as $i => $c ) {
			$pad  = 0 === $i ? '0 8px 0 0' : ( $i === $columns - 1 ? '0 0 0 8px' : '0 4px' );
			$out .= '<td class="mmm-col" valign="top" width="' . $width . '%" style="padding:' . $pad . ';padding-bottom:16px">';
			$out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="' . $card . '"><tr><td style="padding:14px 14px 0">' . $image( $c, 240 ) . '</td></tr>';
			$out .= '<tr><td style="padding:12px 16px 16px">' . $detail( $c, 16 ) . '</td></tr></table></td>';
		}
		for ( $i = count( $row ); $i < $columns; $i++ ) {
			$out .= '<td class="mmm-col" width="' . $width . '%" style="padding:0 0 0 8px">&nbsp;</td>';
		}
		$out .= '</tr>';
	}
	return $out . '</table>';
}

function mmm_c_posts( array $ids, string $title = '', string $eyebrow = '' ): string {
	$d    = mmm_design();
	$rows = '';
	foreach ( $ids as $id ) {
		$post = get_post( (int) $id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			continue;
		}
		$url   = get_permalink( $post );
		$img   = get_the_post_thumbnail_url( $post, 'medium' );
		$text  = wp_trim_words( wp_strip_all_tags( has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( $post->post_content ) ), 22, '…' );
		$cat   = get_the_category( $post->ID );
		$rows .= '<tr>';
		if ( $img ) {
			$rows .= '<td class="mmm-col" valign="top" width="150" style="width:150px;padding:0 18px 20px 0"><a href="' . esc_url( $url ) . '"><img src="' . esc_url( $img ) . '" alt="" width="150" style="display:block;width:150px;max-width:100%;height:auto;border:0;border-radius:12px"></a></td>';
		}
		$rows .= '<td class="mmm-col" valign="top" style="padding:0 0 20px">';
		if ( $cat ) {
			$rows .= '<div style="font-family:' . esc_attr( $d['fm'] ) . ';font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:' . esc_attr( $d['primary'] ) . ';padding-bottom:6px">' . esc_html( $cat[0]->name ) . '</div>';
		}
		$rows .= '<a href="' . esc_url( $url ) . '" style="font-family:' . esc_attr( $d['fh'] ) . ';font-size:17px;line-height:1.3;font-weight:700;color:' . esc_attr( $d['text'] ) . ';text-decoration:none">' . esc_html( get_the_title( $post ) ) . '</a>';
		$rows .= '<div style="padding:6px 0 8px;font-family:' . esc_attr( $d['fb'] ) . ';font-size:14px;line-height:1.6;color:' . esc_attr( $d['muted'] ) . '">' . esc_html( $text ) . '</div>';
		$rows .= '<a href="' . esc_url( $url ) . '" style="font-family:' . esc_attr( $d['fb'] ) . ';font-size:14px;font-weight:700;color:' . esc_attr( $d['primary'] ) . ';text-decoration:none">' . esc_html__( 'Read the article →', 'mine-mirth-mailer' ) . '</a></td></tr>';
	}
	if ( ! $rows ) {
		return '';
	}
	return ( $title ? mmm_c_heading( $title, 2, $eyebrow ) : '' ) . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px">' . $rows . '</table>';
}

/**
 * Dark card with live Bitcoin network numbers from the theme's cache.
 */
function mmm_c_network(): string {
	if ( ! function_exists( 'mm_market_cached' ) || ! function_exists( 'mm_market_display' ) ) {
		return '';
	}
	$data = mm_market_cached();
	if ( empty( $data['price'] ) ) {
		return '';
	}
	$v     = mm_market_display( $data );
	$d     = mmm_design();
	$stat  = static function ( string $label, string $value, string $note = '', string $note_color = '' ) use ( $d ) {
		return '<td class="mmm-col" width="50%" valign="top" style="padding:10px 12px"><div style="font-family:' . esc_attr( $d['fb'] ) . ';font-size:12px;color:#8ea3bb;padding-bottom:4px">' . esc_html( $label ) . '</div><div style="font-family:' . esc_attr( $d['fm'] ) . ';font-size:20px;font-weight:700;color:#ffffff">' . esc_html( $value ) . ( $note ? ' <span style="font-size:13px;color:' . esc_attr( $note_color ? $note_color : '#8ea3bb' ) . '">' . esc_html( $note ) . '</span>' : '' ) . '</div></td>';
	};
	$up    = 'is-up' === ( $v['change_class'] ?? '' );
	$out   = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px"><tr><td bgcolor="' . esc_attr( $d['dark2'] ) . '" style="border-radius:18px;background:' . esc_attr( $d['dark2'] ) . ';background-image:radial-gradient(120% 140% at 100% 0%,rgba(53,168,246,.28),rgba(53,168,246,0) 60%);padding:18px 12px">';
	$out  .= '<div style="padding:0 12px 6px;font-family:' . esc_attr( $d['fm'] ) . ';font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:' . esc_attr( $d['accent2'] ) . '">&#9679; ' . esc_html__( 'Bitcoin network this week', 'mine-mirth-mailer' ) . '</div>';
	$out  .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>' . $stat( __( 'Bitcoin price', 'mine-mirth-mailer' ), (string) ( $v['price'] ?? '—' ), (string) ( $v['change'] ?? '' ), $up ? '#34d399' : '#f87171' ) . $stat( __( 'Network hashrate', 'mine-mirth-mailer' ), (string) ( $v['hashrate'] ?? '—' ) ) . '</tr>';
	$out  .= '<tr>' . $stat( __( 'Difficulty', 'mine-mirth-mailer' ), (string) ( $v['difficulty'] ?? '—' ) ) . $stat( __( 'Next adjustment', 'mine-mirth-mailer' ), (string) ( $v['adjustment'] ?? '—' ) ) . '</tr></table>';
	return $out . '</td></tr></table>';
}

function mmm_c_cta( string $title, string $text, string $button, string $url, string $eyebrow = '' ): string {
	$d   = mmm_design();
	$out = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 24px"><tr><td bgcolor="#0a1b2f" style="border-radius:20px;background:#0a1b2f;background-image:radial-gradient(90% 140% at 100% 0%,rgba(53,168,246,.35),rgba(53,168,246,0) 60%),linear-gradient(160deg,#0a1b2f 0%,#040b15 100%);padding:28px">';
	if ( $eyebrow ) {
		$out .= '<div style="font-family:' . esc_attr( $d['fm'] ) . ';font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:' . esc_attr( $d['accent2'] ) . ';padding-bottom:8px">' . esc_html( $eyebrow ) . '</div>';
	}
	$out .= '<div style="font-family:' . esc_attr( $d['fh'] ) . ';font-size:22px;line-height:1.25;font-weight:700;color:#ffffff;padding-bottom:8px">' . esc_html( $title ) . '</div>';
	if ( $text ) {
		$out .= '<div style="font-family:' . esc_attr( $d['fb'] ) . ';font-size:15px;line-height:1.6;color:#a9bbd0;padding-bottom:18px">' . esc_html( $text ) . '</div>';
	}
	$out .= mmm_c_button( $button, $url );
	return $out . '</td></tr></table>';
}

/**
 * "Talk to us" box; the button opens the reply page for this subscriber.
 */
function mmm_c_reply( string $title = '', string $text = '', string $button = '', string $topic = '' ): string {
	$d      = mmm_design();
	$title  = $title ? $title : __( 'Questions? Talk to a person.', 'mine-mirth-mailer' );
	$text   = $text ? $text : __( 'Hit reply or tap below. Ask about a miner, volume pricing or hosting and our team will answer you directly.', 'mine-mirth-mailer' );
	$button = $button ? $button : __( 'Ask us anything', 'mine-mirth-mailer' );
	$url    = '%%MMM_REPLY%%' . ( $topic ? '&topic=' . rawurlencode( $topic ) : '' );
	$out    = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;margin:4px 0 8px"><tr><td style="border-radius:18px;background:#eaf5fe;border:1px solid #cfe6fb;padding:22px 24px">';
	$out   .= '<div style="font-family:' . esc_attr( $d['fh'] ) . ';font-size:18px;font-weight:700;color:' . esc_attr( $d['text'] ) . ';padding-bottom:6px">' . esc_html( $title ) . '</div>';
	$out   .= '<div style="font-family:' . esc_attr( $d['fb'] ) . ';font-size:15px;line-height:1.6;color:#2b3a4e;padding-bottom:14px">' . esc_html( $text ) . '</div>';
	$out   .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td bgcolor="' . esc_attr( $d['text'] ) . '" style="border-radius:999px;background:' . esc_attr( $d['text'] ) . '"><a href="' . $url . '" style="display:inline-block;padding:12px 22px;font-family:' . esc_attr( $d['fb'] ) . ';font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:999px">' . esc_html( $button ) . '</a></td></tr></table>';
	return $out . '</td></tr></table>';
}

/* ---------------------------------------------------------------------------
 * The full email
 * ------------------------------------------------------------------------- */

/**
 * Wrap body content in the branded layout.
 *
 * @param array $a subject, preheader, body, campaign, label (small text in the header).
 */
function mmm_document( array $a ): string {
	$d        = mmm_design();
	$subject  = (string) ( $a['subject'] ?? '' );
	$pre      = (string) ( $a['preheader'] ?? '' );
	$campaign = (int) ( $a['campaign'] ?? 0 );
	$body     = (string) ( $a['body'] ?? '' );
	$label    = (string) ( $a['label'] ?? $d['brand'] );
	$body     = mmm_track_links( $body, $campaign );

	$logo = $d['logo']
		? '<img src="' . esc_url( $d['logo'] ) . '" alt="' . esc_attr( $d['site'] ) . '" height="34" style="display:block;height:34px;width:auto;max-width:220px;border:0">'
		: '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td width="34" height="34" align="center" bgcolor="' . esc_attr( $d['primary'] ) . '" style="width:34px;height:34px;border-radius:10px;background:' . esc_attr( $d['primary'] ) . ';background-image:linear-gradient(140deg,' . esc_attr( $d['accent2'] ) . ',' . esc_attr( $d['primary'] ) . ' 50%,' . esc_attr( $d['accent'] ) . ');font-family:' . esc_attr( $d['fh'] ) . ';font-size:18px;font-weight:700;color:#ffffff">' . esc_html( mb_substr( $d['site'], 0, 1 ) ) . '</td><td style="padding-left:10px;font-family:' . esc_attr( $d['fh'] ) . ';font-size:19px;font-weight:700;color:#ffffff">' . esc_html( $d['site'] ) . '</td></tr></table>';

	// Footer details from the theme.
	$contact = array();
	if ( function_exists( 'mm_opt' ) ) {
		$mail  = sanitize_email( (string) mm_opt( 'contact_email' ) );
		$phone = trim( (string) mm_opt( 'contact_phone' ) );
		if ( $mail ) {
			$contact[] = '<a href="mailto:' . esc_attr( $mail ) . '" style="color:#cfe0f2;text-decoration:none">' . esc_html( $mail ) . '</a>';
		}
		if ( $phone ) {
			$contact[] = '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '" style="color:#cfe0f2;text-decoration:none">' . esc_html( $phone ) . '</a>';
		}
	}
	$social = array();
	if ( function_exists( 'mm_social_networks' ) && function_exists( 'mm_opt' ) ) {
		foreach ( mm_social_networks() as $net => $name ) {
			$url = (string) mm_opt( 'social_' . $net );
			if ( $url ) {
				$social[] = '<a href="' . esc_url( $url ) . '" style="color:#cfe0f2;text-decoration:none;font-weight:600">' . esc_html( $name ) . '</a>';
			}
		}
	}
	$address = trim( (string) mmm_opt( 'address' ) );
	$home    = home_url( '/' );

	ob_start();
	?>
<!doctype html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light only">
<meta name="supported-color-schemes" content="light">
<title><?php echo esc_html( $subject ); ?></title>
<link href="<?php echo esc_url( $d['fonts_url'] ); ?>" rel="stylesheet">
<style>
body{margin:0;padding:0;width:100%!important;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
table{border-collapse:collapse}
img{-ms-interpolation-mode:bicubic}
a{color:<?php echo esc_html( $d['primary'] ); ?>}
@media only screen and (max-width:620px){
	.mmm-wrap{width:100%!important}
	.mmm-pad{padding-left:20px!important;padding-right:20px!important}
	.mmm-col{display:block!important;width:100%!important;max-width:100%!important;padding-left:0!important;padding-right:0!important;box-sizing:border-box}
	.mmm-col.mmm-spot{padding:16px 18px!important}
	.mmm-hide{display:none!important}
}
</style>
</head>
<body style="margin:0;padding:0;background:<?php echo esc_attr( $d['page'] ); ?>">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all"><?php echo esc_html( $pre ); ?><?php echo str_repeat( '&#8204;&nbsp;', 40 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="<?php echo esc_attr( $d['page'] ); ?>" style="background:<?php echo esc_attr( $d['page'] ); ?>">
<tr><td align="center" style="padding:24px 12px">
	<table role="presentation" class="mmm-wrap" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px">
		<?php if ( $campaign ) : ?><tr><td align="right" class="mmm-pad" style="padding:0 4px 10px;font-family:<?php echo esc_attr( $d['fb'] ); ?>;font-size:12px;color:<?php echo esc_attr( $d['muted'] ); ?>"><a href="%%MMM_VIEW%%" style="color:<?php echo esc_attr( $d['muted'] ); ?>;text-decoration:underline"><?php esc_html_e( 'View in your browser', 'mine-mirth-mailer' ); ?></a></td></tr><?php endif; ?>
		<tr><td bgcolor="<?php echo esc_attr( $d['dark'] ); ?>" class="mmm-pad" style="background:<?php echo esc_attr( $d['dark'] ); ?>;background-image:radial-gradient(80% 160% at 100% 0%,rgba(53,168,246,.32),rgba(53,168,246,0) 60%);border-radius:20px 20px 0 0;padding:26px 32px">
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
				<td valign="middle"><a href="<?php echo esc_url( $home ); ?>" style="text-decoration:none"><?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput ?></a></td>
				<td valign="middle" align="right" class="mmm-hide" style="font-family:<?php echo esc_attr( $d['fm'] ); ?>;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:<?php echo esc_attr( $d['accent2'] ); ?>"><?php echo esc_html( $label ); ?><br><span style="color:#8ea3bb;font-weight:400;letter-spacing:.5px"><?php echo esc_html( mmm_date( 'M j, Y' ) ); ?></span></td>
			</tr></table>
		</td></tr>
		<tr><td height="4" style="height:4px;line-height:4px;font-size:0;background:<?php echo esc_attr( $d['primary'] ); ?>;background-image:linear-gradient(90deg,<?php echo esc_attr( $d['accent2'] ); ?>,<?php echo esc_attr( $d['primary'] ); ?>,<?php echo esc_attr( $d['accent'] ); ?>)">&nbsp;</td></tr>
		<tr><td bgcolor="#ffffff" class="mmm-pad" style="background:#ffffff;padding:36px 32px 20px">
			<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped components. ?>
		</td></tr>
		<tr><td bgcolor="<?php echo esc_attr( $d['dark'] ); ?>" class="mmm-pad" style="background:<?php echo esc_attr( $d['dark'] ); ?>;border-radius:0 0 20px 20px;padding:28px 32px;font-family:<?php echo esc_attr( $d['fb'] ); ?>;font-size:13px;line-height:1.7;color:#8ea3bb">
			<div style="font-family:<?php echo esc_attr( $d['fh'] ); ?>;font-size:16px;font-weight:700;color:#ffffff;padding-bottom:4px"><?php echo esc_html( $d['site'] ); ?></div>
			<?php if ( $contact ) : ?><div><?php echo implode( ' &nbsp;·&nbsp; ', $contact ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endif; ?>
			<?php if ( $social ) : ?><div style="padding-top:6px"><?php echo implode( ' &nbsp;·&nbsp; ', $social ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endif; ?>
			<div style="height:1px;line-height:1px;font-size:0;background:#172a3f;margin:16px 0">&nbsp;</div>
			<div><!--mmm-reason--><?php echo esc_html( (string) mmm_opt( 'footer_note' ) ); ?><!--/mmm-reason--></div>
			<?php if ( $address ) : ?><div><?php echo esc_html( $address ); ?></div><?php endif; ?>
			<div style="padding-top:8px"><a href="%%MMM_UNSUB%%" style="color:#cfe0f2;text-decoration:underline"><?php esc_html_e( 'Unsubscribe', 'mine-mirth-mailer' ); ?></a> &nbsp;·&nbsp; <a href="<?php echo esc_url( $home ); ?>" style="color:#cfe0f2;text-decoration:underline"><?php echo esc_html( wp_parse_url( $home, PHP_URL_HOST ) ); ?></a></div>
		</td></tr>
		<tr><td style="font-size:0;line-height:0"><img src="%%MMM_OPEN%%" width="1" height="1" alt="" style="display:block;border:0;width:1px;height:1px"></td></tr>
	</table>
</td></tr>
</table>
</body>
</html>
	<?php
	return (string) ob_get_clean();
}

/**
 * Route links through the click tracker (http/https links only).
 */
function mmm_track_links( string $html, int $campaign ): string {
	return (string) preg_replace_callback(
		'/href=(["\'])(https?:\/\/[^"\']+)\1/i',
		static function ( $m ) use ( $campaign ) {
			$url = html_entity_decode( $m[2], ENT_QUOTES );
			if ( str_contains( $url, 'mmm=' ) || str_contains( $url, 'fonts.googleapis' ) ) {
				return $m[0];
			}
			$target = mmm_opt( 'track_clicks' ) ? mmm_click_url( $url, $campaign ) : mmm_utm( $url, $campaign );
			return 'href=' . $m[1] . esc_url( $target ) . $m[1];
		},
		$html
	);
}

/**
 * Fill the per-recipient placeholders.
 */
function mmm_personalize( string $html, int $sub, int $campaign ): string {
	$token = mmm_sub_token( $sub );
	$first = mmm_sub_first_name( $sub );
	$html  = (string) preg_replace_callback(
		'/\{first_name(?:\|([^}]*))?\}/',
		static fn( $m ) => esc_html( $first ? $first : ( $m[1] ?? '' ) ),
		$html
	);
	// People you added by hand didn't "subscribe at our website": say why they get the email.
	if ( 'manual' === get_post_meta( $sub, '_mm_context', true ) && '' !== trim( (string) mmm_opt( 'footer_manual' ) ) ) {
		$html = (string) preg_replace( '#<!--mmm-reason-->.*?<!--/mmm-reason-->#s', esc_html( (string) mmm_opt( 'footer_manual' ) ), $html );
	}
	$open = mmm_opt( 'track_opens' ) ? esc_url( mmm_link( 'o', $campaign, $token ) ) : esc_url( includes_url( 'images/blank.gif' ) );
	return strtr(
		$html,
		array(
			'%%MMM_TOKEN%%' => $token,
			'%%MMM_OPEN%%'  => $open,
			'%%MMM_UNSUB%%' => esc_url( mmm_link( 'unsub', $campaign, $token ) ),
			'%%MMM_VIEW%%'  => esc_url( mmm_link( 'view', $campaign, $token ) ),
			'%%MMM_REPLY%%' => esc_url( mmm_link( 'reply', $campaign, $token ) ),
			'{email}'       => esc_html( mmm_sub_email( $sub ) ),
		)
	);
}

/**
 * Plain-text version for mail apps that show text only.
 */
function mmm_plain_text( string $html ): string {
	$html = preg_replace( '#<(style|head|title)[^>]*>.*?</\1>#is', '', $html );
	// Links keep their address; image-only links (logo, product photos) are dropped.
	$html = preg_replace_callback(
		'#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#is',
		static function ( $m ) {
			$label = trim( wp_strip_all_tags( $m[2] ) );
			return '' === $label ? '' : $label . ' (' . html_entity_decode( $m[1] ) . ')';
		},
		(string) $html
	);
	$html = preg_replace( '#<(br|div|/p|/div|/tr|/h[1-6]|/li)[^>]*>#i', "\n", (string) $html );
	$text = html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES );
	$text = preg_replace( "/[ \t\x{00a0}\x{200c}]+/u", ' ', $text );
	$text = preg_replace( "/^ +| +$/m", '', (string) $text );
	return trim( (string) preg_replace( "/\n\s*\n\s*\n+/", "\n\n", (string) $text ) );
}
