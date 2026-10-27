<?php
/**
 * A small PDF writer for purchase orders: embedded brand fonts (Unicode, with kerning), shapes,
 * rounded boxes, gradients, transparency, JPEG and PNG images and links. No external libraries.
 *
 * Coordinates are in points from the top-left corner of the page; text is placed on its baseline.
 *
 * @package MineMirthPurchaseOrders
 */

defined( 'ABSPATH' ) || exit;

/**
 * PDF document.
 */
final class MMPO_PDF {

	/** Page width and height in points. */
	private float $w;
	private float $h;

	/** Content stream of each page. @var string[] */
	private array $pages = array();

	/** Page being drawn on. */
	private int $cur = -1;

	/** Links on each page: [x, y, w, h, url]. @var array<int,array> */
	private array $links = array();

	/** Loaded fonts: key => [def, res, used(gid => unicode)]. */
	private array $fonts = array();

	/** Images: key => [res, w, h, cs, bpc, filter, data, parms, smask, decode]. */
	private array $images = array();

	/** Gradients: [res, x0, y0, x1, y1, stops]. */
	private array $shadings = array();

	/** Opacity states: alpha => res. */
	private array $alphas = array();

	/** Document information. */
	private array $info = array();

	/** Folder with the font files. */
	private string $font_dir;

	/**
	 * @param string $size letter or a4.
	 */
	public function __construct( string $size = 'letter', string $font_dir = '' ) {
		if ( 'a4' === $size ) {
			$this->w = 595.28;
			$this->h = 841.89;
		} else {
			$this->w = 612.0;
			$this->h = 792.0;
		}
		$this->font_dir = $font_dir ? $font_dir : MMPO_DIR . 'fonts/';
	}

	public function width(): float {
		return $this->w;
	}

	public function height(): float {
		return $this->h;
	}

	public function page_count(): int {
		return count( $this->pages );
	}

	public function add_page(): void {
		$this->pages[] = '';
		$this->links[] = array();
		$this->cur     = count( $this->pages ) - 1;
	}

	/**
	 * Go back to an earlier page (0-based) to add to it, for example page numbers.
	 */
	public function set_page( int $index ): void {
		if ( isset( $this->pages[ $index ] ) ) {
			$this->cur = $index;
		}
	}

	/**
	 * Rotate what is drawn next around a point (degrees, counter-clockwise). End with restore().
	 */
	public function rotate( float $degrees, float $cx, float $cy ): void {
		$a  = deg2rad( $degrees );
		$py = $this->h - $cy;
		$c  = cos( $a );
		$s  = sin( $a );
		$this->out( 'q ' . self::n( $c ) . ' ' . self::n( $s ) . ' ' . self::n( -$s ) . ' ' . self::n( $c ) . ' ' . self::n( $cx - $cx * $c + $py * $s ) . ' ' . self::n( $py - $cx * $s - $py * $c ) . ' cm' );
	}

	/**
	 * Save the drawing state (opacity, rotation); restore() brings it back.
	 */
	public function save(): void {
		$this->out( 'q' );
	}

	public function restore(): void {
		$this->out( 'Q' );
	}

	/**
	 * Title, author, subject.
	 */
	public function set_info( array $info ): void {
		$this->info = $info;
	}

	private function out( string $s ): void {
		$this->pages[ $this->cur ] .= $s . "\n";
	}

	private static function n( float $v ): string {
		$s = number_format( $v, 3, '.', '' );
		$s = rtrim( rtrim( $s, '0' ), '.' );
		return '-0' === $s ? '0' : $s;
	}

	/**
	 * Hex colour (#rgb or #rrggbb) to 0..1 components.
	 *
	 * @return float[]
	 */
	public static function rgb( string $hex ): array {
		$hex = ltrim( trim( $hex ), '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( ! preg_match( '/^[0-9a-f]{6}$/i', $hex ) ) {
			$hex = '000000';
		}
		return array( hexdec( substr( $hex, 0, 2 ) ) / 255, hexdec( substr( $hex, 2, 2 ) ) / 255, hexdec( substr( $hex, 4, 2 ) ) / 255 );
	}

	private function color_op( string $hex, bool $stroke ): string {
		$c = self::rgb( $hex );
		return self::n( $c[0] ) . ' ' . self::n( $c[1] ) . ' ' . self::n( $c[2] ) . ( $stroke ? ' RG' : ' rg' );
	}

	/**
	 * Path of a rectangle, rounded when $r > 0 (PDF coordinates are made here).
	 */
	private function rect_path( float $x, float $y, float $w, float $h, float $r ): string {
		$r = max( 0.0, min( $r, $w / 2, $h / 2 ) );
		$x1 = $x;
		$y1 = $this->h - $y - $h;
		if ( $r <= 0 ) {
			return self::n( $x1 ) . ' ' . self::n( $y1 ) . ' ' . self::n( $w ) . ' ' . self::n( $h ) . ' re';
		}
		$k  = 0.5523 * $r;
		$x2 = $x1 + $w;
		$y2 = $y1 + $h;
		$p  = array();
		$p[] = self::n( $x1 + $r ) . ' ' . self::n( $y1 ) . ' m';
		$p[] = self::n( $x2 - $r ) . ' ' . self::n( $y1 ) . ' l';
		$p[] = self::n( $x2 - $r + $k ) . ' ' . self::n( $y1 ) . ' ' . self::n( $x2 ) . ' ' . self::n( $y1 + $r - $k ) . ' ' . self::n( $x2 ) . ' ' . self::n( $y1 + $r ) . ' c';
		$p[] = self::n( $x2 ) . ' ' . self::n( $y2 - $r ) . ' l';
		$p[] = self::n( $x2 ) . ' ' . self::n( $y2 - $r + $k ) . ' ' . self::n( $x2 - $r + $k ) . ' ' . self::n( $y2 ) . ' ' . self::n( $x2 - $r ) . ' ' . self::n( $y2 ) . ' c';
		$p[] = self::n( $x1 + $r ) . ' ' . self::n( $y2 ) . ' l';
		$p[] = self::n( $x1 + $r - $k ) . ' ' . self::n( $y2 ) . ' ' . self::n( $x1 ) . ' ' . self::n( $y2 - $r + $k ) . ' ' . self::n( $x1 ) . ' ' . self::n( $y2 - $r ) . ' c';
		$p[] = self::n( $x1 ) . ' ' . self::n( $y1 + $r ) . ' l';
		$p[] = self::n( $x1 ) . ' ' . self::n( $y1 + $r - $k ) . ' ' . self::n( $x1 + $r - $k ) . ' ' . self::n( $y1 ) . ' ' . self::n( $x1 + $r ) . ' ' . self::n( $y1 ) . ' c';
		$p[] = 'h';
		return implode( ' ', $p );
	}

	/**
	 * Filled and/or stroked rectangle.
	 *
	 * @param string $fill   Fill colour or ''.
	 * @param string $stroke Stroke colour or ''.
	 */
	public function rect( float $x, float $y, float $w, float $h, string $fill = '', string $stroke = '', float $r = 0, float $line = 0.75 ): void {
		if ( '' === $fill && '' === $stroke ) {
			return;
		}
		$ops = 'q ';
		if ( '' !== $fill ) {
			$ops .= $this->color_op( $fill, false ) . ' ';
		}
		if ( '' !== $stroke ) {
			$ops .= $this->color_op( $stroke, true ) . ' ' . self::n( $line ) . ' w ';
		}
		$ops .= $this->rect_path( $x, $y, $w, $h, $r ) . ' ' . ( '' !== $fill && '' !== $stroke ? 'B' : ( '' !== $fill ? 'f' : 'S' ) ) . ' Q';
		$this->out( $ops );
	}

	public function line( float $x1, float $y1, float $x2, float $y2, string $color, float $width = 0.75, array $dash = array() ): void {
		$d = $dash ? '[' . implode( ' ', array_map( array( self::class, 'n' ), $dash ) ) . '] 0 d ' : '';
		$this->out( 'q ' . $this->color_op( $color, true ) . ' ' . self::n( $width ) . ' w ' . $d . self::n( $x1 ) . ' ' . self::n( $this->h - $y1 ) . ' m ' . self::n( $x2 ) . ' ' . self::n( $this->h - $y2 ) . ' l S Q' );
	}

	/**
	 * Circle (filled).
	 */
	public function circle( float $cx, float $cy, float $r, string $fill ): void {
		$k  = 0.5523 * $r;
		$y  = $this->h - $cy;
		$p  = self::n( $cx + $r ) . ' ' . self::n( $y ) . ' m ';
		$p .= self::n( $cx + $r ) . ' ' . self::n( $y + $k ) . ' ' . self::n( $cx + $k ) . ' ' . self::n( $y + $r ) . ' ' . self::n( $cx ) . ' ' . self::n( $y + $r ) . ' c ';
		$p .= self::n( $cx - $k ) . ' ' . self::n( $y + $r ) . ' ' . self::n( $cx - $r ) . ' ' . self::n( $y + $k ) . ' ' . self::n( $cx - $r ) . ' ' . self::n( $y ) . ' c ';
		$p .= self::n( $cx - $r ) . ' ' . self::n( $y - $k ) . ' ' . self::n( $cx - $k ) . ' ' . self::n( $y - $r ) . ' ' . self::n( $cx ) . ' ' . self::n( $y - $r ) . ' c ';
		$p .= self::n( $cx + $k ) . ' ' . self::n( $y - $r ) . ' ' . self::n( $cx + $r ) . ' ' . self::n( $y - $k ) . ' ' . self::n( $cx + $r ) . ' ' . self::n( $y ) . ' c h';
		$this->out( 'q ' . $this->color_op( $fill, false ) . ' ' . $p . ' f Q' );
	}

	/**
	 * Rectangle filled with a linear gradient from the top-left towards the bottom-right (or across).
	 *
	 * @param string[] $stops   Colours, evenly spaced unless $bounds is given.
	 * @param float[]  $bounds  Positions (0..1) between the stops, for example [0.45].
	 * @param bool     $diagonal Diagonal (true) or left to right (false).
	 */
	public function gradient_rect( float $x, float $y, float $w, float $h, array $stops, float $r = 0, array $bounds = array(), bool $diagonal = true ): void {
		$stops = array_values( $stops );
		if ( count( $stops ) < 2 ) {
			$this->rect( $x, $y, $w, $h, $stops ? $stops[0] : '#000000', '', $r );
			return;
		}
		if ( count( $bounds ) !== count( $stops ) - 2 ) {
			$bounds = array();
			for ( $i = 1; $i < count( $stops ) - 1; $i++ ) {
				$bounds[] = $i / ( count( $stops ) - 1 );
			}
		}
		$res              = 'Sh' . ( count( $this->shadings ) + 1 );
		$x0               = $x;
		$y0               = $this->h - $y;
		$x1               = $x + $w;
		$y1               = $diagonal ? $this->h - $y - $h : $y0;
		$this->shadings[] = array( $res, $x0, $y0, $x1, $y1, $stops, $bounds );
		$this->out( 'q ' . $this->rect_path( $x, $y, $w, $h, $r ) . ' W n /' . $res . ' sh Q' );
	}

	/**
	 * Only draw inside this (rounded) rectangle until restore(). Call save() first.
	 */
	public function clip( float $x, float $y, float $w, float $h, float $r = 0 ): void {
		$this->out( $this->rect_path( $x, $y, $w, $h, $r ) . ' W n' );
	}

	/**
	 * Draw following shapes and text with this opacity (1 for normal).
	 */
	public function alpha( float $a ): void {
		$a   = max( 0.0, min( 1.0, $a ) );
		$key = self::n( $a );
		if ( ! isset( $this->alphas[ $key ] ) ) {
			$this->alphas[ $key ] = 'GS' . ( count( $this->alphas ) + 1 );
		}
		$this->out( '/' . $this->alphas[ $key ] . ' gs' );
	}

	/**
	 * Clickable area.
	 */
	public function link( float $x, float $y, float $w, float $h, string $url ): void {
		if ( '' === $url ) {
			return;
		}
		$this->links[ $this->cur ][] = array( $x, $y, $w, $h, $url );
	}

	/* ------------------------------------------------------------------ Fonts and text */

	private function font( string $key ): array {
		if ( ! isset( $this->fonts[ $key ] ) ) {
			$file = $this->font_dir . $key . '.php';
			if ( ! is_readable( $file ) ) {
				$key  = 'inter-regular';
				$file = $this->font_dir . $key . '.php';
				if ( isset( $this->fonts[ $key ] ) ) {
					return $this->fonts[ $key ];
				}
			}
			$def                = include $file;
			$this->fonts[ $key ] = array(
				'def'  => $def,
				'res'  => 'F' . ( count( $this->fonts ) + 1 ),
				'used' => array(),
				'file' => $this->font_dir . $key . '.ttf',
			);
		}
		return $this->fonts[ $key ];
	}

	/**
	 * Unicode code points of a UTF-8 string.
	 *
	 * @return int[]
	 */
	private static function codepoints( string $s ): array {
		if ( '' === $s ) {
			return array();
		}
		if ( ! preg_match( '//u', $s ) ) {
			$s = mb_convert_encoding( $s, 'UTF-8', 'ISO-8859-1' );
		}
		$chars = preg_split( '//u', $s, -1, PREG_SPLIT_NO_EMPTY );
		return array_map( 'mb_ord', $chars ? $chars : array() );
	}

	/**
	 * Glyphs and kerning for a string: [[gid, unicode, width, kern-before], ...] (widths in 1/1000 em).
	 */
	private function shape( string $key, string $s ): array {
		$f    = $this->font( $key );
		$def  = $f['def'];
		$out  = array();
		$prev = 0;
		foreach ( self::codepoints( str_replace( array( "\t", "\r" ), array( ' ', '' ), $s ) ) as $u ) {
			if ( 0xA0 === $u ) {
				$u = 0x20;
			}
			if ( ! isset( $def['cmap'][ $u ] ) ) {
				$alt = function_exists( 'iconv' ) ? @iconv( 'UTF-8', 'ASCII//TRANSLIT', mb_chr( $u ) ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
				if ( is_string( $alt ) && '' !== $alt && isset( $def['cmap'][ ord( $alt[0] ) ] ) ) {
					foreach ( str_split( $alt ) as $a ) {
						if ( isset( $def['cmap'][ ord( $a ) ] ) ) {
							$out[] = $this->glyph( $key, ord( $a ), $prev );
							$prev  = ord( $a );
						}
					}
					continue;
				}
				$u = 0x3F;
			}
			$out[] = $this->glyph( $key, $u, $prev );
			$prev  = $u;
		}
		return $out;
	}

	private function glyph( string $key, int $u, int $prev ): array {
		$def = $this->fonts[ $key ]['def'];
		$gid = (int) $def['cmap'][ $u ];
		$this->fonts[ $key ]['used'][ $gid ] = $u;
		$kern = $prev && isset( $def['kern'][ $prev ][ $u ] ) ? (int) $def['kern'][ $prev ][ $u ] : 0;
		return array( $gid, $u, (int) ( $def['w'][ $gid ] ?? $def['missing'] ), $kern );
	}

	/**
	 * Width of a string in points.
	 */
	public function text_width( string $s, string $font, float $size, float $spacing = 0 ): float {
		$this->font( $font );
		$key   = isset( $this->fonts[ $font ] ) ? $font : 'inter-regular';
		$units = 0;
		$glyphs = $this->shape( $key, $s );
		foreach ( $glyphs as $g ) {
			$units += $g[2] + $g[3];
		}
		return $units * $size / 1000 + ( $glyphs ? $spacing * ( count( $glyphs ) - 1 ) : 0 );
	}

	/**
	 * Write one line of text with its baseline at $y. Returns its width.
	 *
	 * @param array $o align (left|right|center), spacing (pt), rotate (degrees).
	 */
	public function text( float $x, float $y, string $s, string $font, float $size, string $color = '#000000', array $o = array() ): float {
		if ( '' === $s ) {
			return 0;
		}
		$this->font( $font );
		$key     = isset( $this->fonts[ $font ] ) ? $font : 'inter-regular';
		$spacing = (float) ( $o['spacing'] ?? 0 );
		$glyphs  = $this->shape( $key, $s );
		$units   = 0;
		foreach ( $glyphs as $g ) {
			$units += $g[2] + $g[3];
		}
		$width = $units * $size / 1000 + ( $glyphs ? $spacing * ( count( $glyphs ) - 1 ) : 0 );
		$align = $o['align'] ?? 'left';
		if ( 'right' === $align ) {
			$x -= $width;
		} elseif ( 'center' === $align ) {
			$x -= $width / 2;
		}
		$tj  = '';
		$run = '';
		foreach ( $glyphs as $g ) {
			if ( $g[3] && '' !== $run ) {
				$tj .= '<' . $run . '> ' . ( -$g[3] ) . ' ';
				$run = '';
			}
			$run .= sprintf( '%04X', $g[0] );
		}
		if ( '' !== $run ) {
			$tj .= '<' . $run . '>';
		}
		$py = $this->h - $y;
		if ( ! empty( $o['rotate'] ) ) {
			$a   = deg2rad( (float) $o['rotate'] );
			$m   = self::n( cos( $a ) ) . ' ' . self::n( sin( $a ) ) . ' ' . self::n( -sin( $a ) ) . ' ' . self::n( cos( $a ) ) . ' ' . self::n( $x ) . ' ' . self::n( $py ) . ' Tm';
		} else {
			$m = '1 0 0 1 ' . self::n( $x ) . ' ' . self::n( $py ) . ' Tm';
		}
		$this->out( 'BT ' . $this->color_op( $color, false ) . ' /' . $this->fonts[ $key ]['res'] . ' ' . self::n( $size ) . ' Tf ' . self::n( $spacing ) . ' Tc ' . $m . ' [' . trim( $tj ) . '] TJ ET' );
		return $width;
	}

	/**
	 * Split text into lines no wider than $max (keeps line breaks, splits words that are too long).
	 *
	 * @return string[]
	 */
	public function wrap( string $s, string $font, float $size, float $max, float $spacing = 0 ): array {
		$lines = array();
		foreach ( preg_split( "/\r\n|\r|\n/", $s ) as $para ) {
			$para = trim( preg_replace( '/[ \t]+/', ' ', $para ) );
			if ( '' === $para ) {
				$lines[] = '';
				continue;
			}
			$line = '';
			foreach ( explode( ' ', $para ) as $word ) {
				$try = '' === $line ? $word : $line . ' ' . $word;
				if ( $this->text_width( $try, $font, $size, $spacing ) <= $max ) {
					$line = $try;
					continue;
				}
				if ( '' !== $line ) {
					$lines[] = $line;
				}
				// A single word longer than the line: break it.
				while ( $this->text_width( $word, $font, $size, $spacing ) > $max && mb_strlen( $word ) > 1 ) {
					$cut = mb_strlen( $word ) - 1;
					while ( $cut > 1 && $this->text_width( mb_substr( $word, 0, $cut ), $font, $size, $spacing ) > $max ) {
						--$cut;
					}
					$lines[] = mb_substr( $word, 0, $cut );
					$word    = mb_substr( $word, $cut );
				}
				$line = $word;
			}
			$lines[] = $line;
		}
		return $lines;
	}

	/**
	 * Shorten text with an ellipsis to fit $max.
	 */
	public function fit( string $s, string $font, float $size, float $max ): string {
		if ( $this->text_width( $s, $font, $size ) <= $max ) {
			return $s;
		}
		while ( mb_strlen( $s ) > 1 && $this->text_width( $s . '…', $font, $size ) > $max ) {
			$s = mb_substr( $s, 0, -1 );
		}
		return rtrim( $s ) . '…';
	}

	/* ------------------------------------------------------------------ Images */

	/**
	 * Load an image file (JPEG, PNG, GIF, WebP). Returns [key, width, height] in pixels, or null.
	 */
	public function load_image( string $path ): ?array {
		$key = md5( $path );
		if ( isset( $this->images[ $key ] ) ) {
			return array( $key, $this->images[ $key ]['w'], $this->images[ $key ]['h'] );
		}
		if ( ! is_readable( $path ) ) {
			return null;
		}
		$data = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$img  = null;
		$info = @getimagesizefromstring( $data ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( $info && IMAGETYPE_JPEG === $info[2] && ( $info[0] * $info[1] ) <= 9000000 ) {
			$channels = (int) ( $info['channels'] ?? 3 );
			$img      = array(
				'w'      => (int) $info[0],
				'h'      => (int) $info[1],
				'cs'     => 4 === $channels ? 'DeviceCMYK' : ( 1 === $channels ? 'DeviceGray' : 'DeviceRGB' ),
				'bpc'    => (int) ( $info['bits'] ?? 8 ),
				'filter' => 'DCTDecode',
				'data'   => $data,
				'decode' => 4 === $channels ? '[1 0 1 0 1 0 1 0]' : '',
			);
		} elseif ( function_exists( 'imagecreatefromstring' ) ) {
			$img = $this->gd_image( $data );
		}
		if ( ! $img && str_starts_with( $data, "\x89PNG" ) ) {
			$img = $this->png_image( $data );
		}
		if ( ! $img ) {
			return null;
		}
		$img['res']            = 'Im' . ( count( $this->images ) + 1 );
		$this->images[ $key ] = $img;
		return array( $key, $img['w'], $img['h'] );
	}

	/**
	 * Any image GD can read, as raw RGB plus an alpha mask (scaled down to 1200 px at most).
	 */
	private function gd_image( string $data ): ?array {
		$im = @imagecreatefromstring( $data ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( ! $im ) {
			return null;
		}
		$w = imagesx( $im );
		$h = imagesy( $im );
		if ( max( $w, $h ) > 1200 ) {
			$s  = 1200 / max( $w, $h );
			$nw = max( 1, (int) round( $w * $s ) );
			$nh = max( 1, (int) round( $h * $s ) );
			$sc = imagecreatetruecolor( $nw, $nh );
			imagealphablending( $sc, false );
			imagesavealpha( $sc, true );
			imagefill( $sc, 0, 0, imagecolorallocatealpha( $sc, 0, 0, 0, 127 ) );
			imagecopyresampled( $sc, $im, 0, 0, 0, 0, $nw, $nh, $w, $h );
			imagedestroy( $im );
			$im = $sc;
			$w  = $nw;
			$h  = $nh;
		}
		if ( ! imageistruecolor( $im ) ) {
			imagepalettetotruecolor( $im );
		}
		$rgb   = '';
		$alpha = '';
		$any   = false;
		for ( $y = 0; $y < $h; $y++ ) {
			for ( $x = 0; $x < $w; $x++ ) {
				$c    = imagecolorat( $im, $x, $y );
				$a    = ( $c >> 24 ) & 0x7F;
				$rgb .= chr( ( $c >> 16 ) & 0xFF ) . chr( ( $c >> 8 ) & 0xFF ) . chr( $c & 0xFF );
				$av   = 255 - (int) round( $a * 255 / 127 );
				$alpha .= chr( $av );
				if ( $av < 255 ) {
					$any = true;
				}
			}
		}
		imagedestroy( $im );
		return array(
			'w'      => $w,
			'h'      => $h,
			'cs'     => 'DeviceRGB',
			'bpc'    => 8,
			'filter' => 'FlateDecode',
			'data'   => gzcompress( $rgb ),
			'smask'  => $any ? gzcompress( $alpha ) : '',
		);
	}

	/**
	 * PNG without GD (8-bit, not interlaced).
	 */
	private function png_image( string $data ): ?array {
		$pos = 8;
		$len = strlen( $data );
		$w   = 0;
		$h   = 0;
		$bpc = 0;
		$ct  = 0;
		$pal = '';
		$dat = '';
		while ( $pos + 8 <= $len ) {
			$n    = unpack( 'N', substr( $data, $pos, 4 ) )[1];
			$type = substr( $data, $pos + 4, 4 );
			$body = substr( $data, $pos + 8, $n );
			$pos += 12 + $n;
			if ( 'IHDR' === $type ) {
				$hd  = unpack( 'Nw/Nh/Cbpc/Cct/Ccomp/Cfilter/Cinterlace', $body );
				$w   = $hd['w'];
				$h   = $hd['h'];
				$bpc = $hd['bpc'];
				$ct  = $hd['ct'];
				if ( 8 !== $bpc || $hd['interlace'] ) {
					return null;
				}
			} elseif ( 'PLTE' === $type ) {
				$pal = $body;
			} elseif ( 'IDAT' === $type ) {
				$dat .= $body;
			} elseif ( 'IEND' === $type ) {
				break;
			}
		}
		if ( ! $w || '' === $dat ) {
			return null;
		}
		$colors = array( 0 => 1, 2 => 3, 3 => 1, 4 => 1, 6 => 3 )[ $ct ] ?? 0;
		if ( ! $colors ) {
			return null;
		}
		$img = array(
			'w'      => $w,
			'h'      => $h,
			'cs'     => 3 === $ct ? 'Indexed' : ( 1 === $colors ? 'DeviceGray' : 'DeviceRGB' ),
			'bpc'    => 8,
			'filter' => 'FlateDecode',
			'parms'  => '<< /Predictor 15 /Colors ' . $colors . ' /BitsPerComponent 8 /Columns ' . $w . ' >>',
			'data'   => $dat,
			'pal'    => $pal,
		);
		if ( $ct >= 4 ) {
			// Split colour and alpha; PNG row filters still apply per channel.
			$raw = @gzuncompress( $dat ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			if ( false === $raw ) {
				return null;
			}
			$px    = $colors + 1;
			$row   = 1 + $w * $px;
			$color = '';
			$alpha = '';
			for ( $y = 0; $y < $h; $y++ ) {
				$line   = substr( $raw, $y * $row, $row );
				$color .= $line[0];
				$alpha .= $line[0];
				$pixels = substr( $line, 1 );
				for ( $i = 0; $i < $w; $i++ ) {
					$color .= substr( $pixels, $i * $px, $colors );
					$alpha .= $pixels[ $i * $px + $colors ];
				}
			}
			$img['data']        = gzcompress( $color );
			$img['smask']       = gzcompress( $alpha );
			$img['smask_parms'] = '<< /Predictor 15 /Colors 1 /BitsPerComponent 8 /Columns ' . $w . ' >>';
		}
		return $img;
	}

	/**
	 * Draw a loaded image.
	 */
	public function image( string $key, float $x, float $y, float $w, float $h ): void {
		if ( ! isset( $this->images[ $key ] ) ) {
			return;
		}
		$this->out( 'q ' . self::n( $w ) . ' 0 0 ' . self::n( $h ) . ' ' . self::n( $x ) . ' ' . self::n( $this->h - $y - $h ) . ' cm /' . $this->images[ $key ]['res'] . ' Do Q' );
	}

	/* ------------------------------------------------------------------ Output */

	private static function pdf_text( string $s ): string {
		$u = mb_convert_encoding( $s, 'UTF-16BE', 'UTF-8' );
		return '<FEFF' . strtoupper( bin2hex( $u ) ) . '>';
	}

	private static function pdf_literal( string $s ): string {
		return '(' . strtr( $s, array( '\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => '', "\n" => '' ) ) . ')';
	}

	/**
	 * The finished PDF file.
	 */
	public function output(): string {
		$objs = array( 1 => '', 2 => '' );
		$add  = static function ( string $body ) use ( &$objs ): int {
			$objs[] = $body;
			return array_key_last( $objs );
		};
		$stream = static function ( string $dict, string $data, bool $compress = true ) use ( $add ): int {
			if ( $compress && function_exists( 'gzcompress' ) ) {
				$data = gzcompress( $data );
				$dict = '/Filter /FlateDecode ' . $dict;
			}
			return $add( '<< ' . $dict . ' /Length ' . strlen( $data ) . " >>\nstream\n" . $data . "\nendstream" );
		};

		// Fonts.
		$font_res = '';
		foreach ( $this->fonts as $f ) {
			$def  = $f['def'];
			$name = 'MMPO' . chr( 65 + ( (int) substr( $f['res'], 1 ) % 26 ) ) . 'A+' . $def['name'];
			$ttf  = (string) file_get_contents( $f['file'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$file = $stream( '/Length1 ' . strlen( $ttf ), $ttf );
			$desc = $add( '<< /Type /FontDescriptor /FontName /' . $name . ' /Flags ' . (int) $def['flags'] . ' /FontBBox [' . implode( ' ', array_map( 'intval', $def['bbox'] ) ) . '] /ItalicAngle ' . (int) $def['italic'] . ' /Ascent ' . (int) $def['asc'] . ' /Descent ' . (int) $def['desc'] . ' /CapHeight ' . (int) $def['cap'] . ' /StemV ' . (int) $def['stemv'] . ' /FontFile2 ' . $file . ' 0 R >>' );
			$used = $f['used'];
			ksort( $used );
			$w = '';
			foreach ( $used as $gid => $u ) {
				$w .= $gid . ' [' . (int) ( $def['w'][ $gid ] ?? $def['missing'] ) . '] ';
			}
			$cid  = $add( '<< /Type /Font /Subtype /CIDFontType2 /BaseFont /' . $name . ' /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> /FontDescriptor ' . $desc . ' 0 R /DW ' . (int) $def['missing'] . ' /W [' . trim( $w ) . '] /CIDToGIDMap /Identity >>' );
			$cmap = "/CIDInit /ProcSet findresource begin\n12 dict begin\nbegincmap\n/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def\n/CMapName /Adobe-Identity-UCS def\n/CMapType 2 def\n1 begincodespacerange\n<0000> <FFFF>\nendcodespacerange\n";
			foreach ( array_chunk( $used, 100, true ) as $chunk ) {
				$cmap .= count( $chunk ) . " beginbfchar\n";
				foreach ( $chunk as $gid => $u ) {
					$cmap .= sprintf( "<%04X> <%s>\n", $gid, strtoupper( bin2hex( mb_convert_encoding( mb_chr( $u ), 'UTF-16BE', 'UTF-8' ) ) ) );
				}
				$cmap .= "endbfchar\n";
			}
			$cmap     .= "endcmap\nCMapName currentdict /CMap defineresource pop\nend\nend";
			$tu        = $stream( '', $cmap );
			$font      = $add( '<< /Type /Font /Subtype /Type0 /BaseFont /' . $name . ' /Encoding /Identity-H /DescendantFonts [' . $cid . ' 0 R] /ToUnicode ' . $tu . ' 0 R >>' );
			$font_res .= '/' . $f['res'] . ' ' . $font . ' 0 R ';
		}

		// Images.
		$img_res = '';
		foreach ( $this->images as $im ) {
			$smask = '';
			if ( ! empty( $im['smask'] ) ) {
				$sid   = $add( '<< /Type /XObject /Subtype /Image /Width ' . $im['w'] . ' /Height ' . $im['h'] . ' /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode' . ( ! empty( $im['smask_parms'] ) ? ' /DecodeParms ' . $im['smask_parms'] : '' ) . ' /Length ' . strlen( $im['smask'] ) . " >>\nstream\n" . $im['smask'] . "\nendstream" );
				$smask = ' /SMask ' . $sid . ' 0 R';
			}
			$cs = '/' . $im['cs'];
			if ( 'Indexed' === $im['cs'] ) {
				$pal = $add( '<< /Length ' . strlen( $im['pal'] ) . " >>\nstream\n" . $im['pal'] . "\nendstream" );
				$cs  = '[/Indexed /DeviceRGB ' . ( strlen( $im['pal'] ) / 3 - 1 ) . ' ' . $pal . ' 0 R]';
			}
			$id       = $add( '<< /Type /XObject /Subtype /Image /Width ' . $im['w'] . ' /Height ' . $im['h'] . ' /ColorSpace ' . $cs . ' /BitsPerComponent ' . $im['bpc'] . ' /Filter /' . $im['filter'] . ( ! empty( $im['parms'] ) ? ' /DecodeParms ' . $im['parms'] : '' ) . ( ! empty( $im['decode'] ) ? ' /Decode ' . $im['decode'] : '' ) . $smask . ' /Length ' . strlen( $im['data'] ) . " >>\nstream\n" . $im['data'] . "\nendstream" );
			$img_res .= '/' . $im['res'] . ' ' . $id . ' 0 R ';
		}

		// Gradients.
		$sh_res = '';
		foreach ( $this->shadings as $sh ) {
			list( $res, $x0, $y0, $x1, $y1, $stops, $bounds ) = $sh;
			$fns = array();
			for ( $i = 0; $i < count( $stops ) - 1; $i++ ) {
				$c0    = self::rgb( $stops[ $i ] );
				$c1    = self::rgb( $stops[ $i + 1 ] );
				$fns[] = '<< /FunctionType 2 /Domain [0 1] /C0 [' . implode( ' ', array_map( array( self::class, 'n' ), $c0 ) ) . '] /C1 [' . implode( ' ', array_map( array( self::class, 'n' ), $c1 ) ) . '] /N 1 >>';
			}
			$fn = 1 === count( $fns ) ? $fns[0] : '<< /FunctionType 3 /Domain [0 1] /Functions [' . implode( ' ', $fns ) . '] /Bounds [' . implode( ' ', array_map( array( self::class, 'n' ), $bounds ) ) . '] /Encode [' . trim( str_repeat( '0 1 ', count( $fns ) ) ) . '] >>';
			$id      = $add( '<< /ShadingType 2 /ColorSpace /DeviceRGB /Coords [' . self::n( $x0 ) . ' ' . self::n( $y0 ) . ' ' . self::n( $x1 ) . ' ' . self::n( $y1 ) . '] /Function ' . $fn . ' /Extend [true true] >>' );
			$sh_res .= '/' . $res . ' ' . $id . ' 0 R ';
		}

		// Opacity.
		$gs_res = '';
		foreach ( $this->alphas as $a => $res ) {
			$gs_res .= '/' . $res . ' << /Type /ExtGState /ca ' . $a . ' /CA ' . $a . ' >> ';
		}

		$resources = '<< /ProcSet [/PDF /Text /ImageB /ImageC] /Font << ' . $font_res . '>>' . ( $img_res ? ' /XObject << ' . $img_res . '>>' : '' ) . ( $sh_res ? ' /Shading << ' . $sh_res . '>>' : '' ) . ( $gs_res ? ' /ExtGState << ' . $gs_res . '>>' : '' ) . ' >>';
		$res_id    = $add( $resources );

		$kids = array();
		foreach ( $this->pages as $i => $content ) {
			$cid   = $stream( '', $content );
			$annot = '';
			if ( ! empty( $this->links[ $i ] ) ) {
				$ids = array();
				foreach ( $this->links[ $i ] as $l ) {
					list( $x, $y, $w, $h, $url ) = $l;
					$ids[] = $add( '<< /Type /Annot /Subtype /Link /Rect [' . self::n( $x ) . ' ' . self::n( $this->h - $y - $h ) . ' ' . self::n( $x + $w ) . ' ' . self::n( $this->h - $y ) . '] /Border [0 0 0] /A << /S /URI /URI ' . self::pdf_literal( $url ) . ' >> >>' ) . ' 0 R';
				}
				$annot = ' /Annots [' . implode( ' ', $ids ) . ']';
			}
			$kids[] = $add( '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::n( $this->w ) . ' ' . self::n( $this->h ) . '] /Resources ' . $res_id . ' 0 R /Contents ' . $cid . ' 0 R' . $annot . ' >>' ) . ' 0 R';
		}
		$objs[2] = '<< /Type /Pages /Kids [' . implode( ' ', $kids ) . '] /Count ' . count( $kids ) . ' >>';
		$objs[1] = '<< /Type /Catalog /Pages 2 0 R /PageLayout /OneColumn' . ( ! empty( $this->info['Title'] ) ? ' /ViewerPreferences << /DisplayDocTitle true >>' : '' ) . ' >>';

		$info = '<< /Producer ' . self::pdf_text( 'Mine Mirth Purchase Orders' ) . ' /CreationDate (D:' . gmdate( 'YmdHis' ) . "Z)";
		foreach ( $this->info as $k => $v ) {
			if ( '' !== (string) $v ) {
				$info .= ' /' . preg_replace( '/[^A-Za-z]/', '', (string) $k ) . ' ' . self::pdf_text( (string) $v );
			}
		}
		$info_id = $add( $info . ' >>' );

		$pdf     = "%PDF-1.7\n%\xE2\xE3\xCF\xD3\n";
		$offsets = array();
		foreach ( $objs as $num => $body ) {
			$offsets[ $num ] = strlen( $pdf );
			$pdf            .= $num . " 0 obj\n" . $body . "\nendobj\n";
		}
		$xref = strlen( $pdf );
		$size = count( $objs ) + 1;
		$pdf .= "xref\n0 " . $size . "\n0000000000 65535 f \n";
		for ( $i = 1; $i < $size; $i++ ) {
			$pdf .= sprintf( "%010d 00000 n \n", $offsets[ $i ] );
		}
		$id   = md5( $pdf );
		$pdf .= 'trailer << /Size ' . $size . ' /Root 1 0 R /Info ' . $info_id . ' 0 R /ID [<' . $id . '> <' . $id . "> ] >>\nstartxref\n" . $xref . "\n%%EOF\n";
		return $pdf;
	}
}
