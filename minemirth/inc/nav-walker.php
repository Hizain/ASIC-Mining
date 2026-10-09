<?php
/**
 * Navigation walker.
 *
 * Adds accessible submenu toggles, item descriptions, optional icons and
 * mega menus. In Appearance > Menus (enable "CSS Classes" and "Description"
 * in Screen Options):
 *   - add class "mm-mega" to a top-level item to turn its submenu into a mega panel;
 *   - add class "icon-bolt" (any icon name) to show an icon;
 *   - fill "Description" to show a subtitle in dropdowns.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

class MM_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Unique id counter for submenus.
	 *
	 * @var int
	 */
	private static $sub_id = 0;

	/**
	 * Current submenu id.
	 *
	 * @var string
	 */
	private $current_sub = '';

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$classes = array( 'sub-menu', 'mm-submenu', 'mm-submenu--depth-' . ( $depth + 1 ) );
		$output .= '<div class="mm-submenu-wrap" id="' . esc_attr( $this->current_sub ) . '"><ul class="' . esc_attr( implode( ' ', $classes ) ) . '">';
	}

	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul></div>';
	}

	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item    = $data_object;
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$icon    = '';
		foreach ( $classes as $i => $class ) {
			if ( is_string( $class ) && 0 === strpos( $class, 'icon-' ) ) {
				$icon = mm_icon( substr( $class, 5 ) );
				unset( $classes[ $i ] );
			}
		}
		$has_children = in_array( 'menu-item-has-children', $classes, true );
		$classes[]    = 'mm-menu-item';
		$classes[]    = 'mm-menu-item--depth-' . $depth;

		$class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$output     .= '<li class="' . esc_attr( $class_names ) . '">';

		$atts = array(
			'title'  => ! empty( $item->attr_title ) ? $item->attr_title : '',
			'target' => ! empty( $item->target ) ? $item->target : '',
			'rel'    => ! empty( $item->xfn ) ? $item->xfn : ( '_blank' === $item->target ? 'noopener' : '' ),
			'href'   => ! empty( $item->url ) ? $item->url : '',
			'class'  => 'mm-menu-link',
		);
		if ( $item->current ) {
			$atts['aria-current'] = 'page';
		}
		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( is_scalar( $value ) && '' !== $value && false !== $value ) {
				$value       = 'href' === $attr ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

		$desc = '';
		if ( $depth > 0 && ! empty( $item->description ) ) {
			$desc = '<span class="mm-menu-desc">' . esc_html( $item->description ) . '</span>';
		}

		$item_output  = $args->before ?? '';
		$item_output .= '<a' . $attributes . '>';
		$item_output .= $icon ? '<span class="mm-menu-icon">' . $icon . '</span>' : '';
		$item_output .= '<span class="mm-menu-label"><span class="mm-menu-title">' . ( $args->link_before ?? '' ) . $title . ( $args->link_after ?? '' ) . '</span>' . $desc . '</span>';
		if ( $has_children && 0 === $depth ) {
			$item_output .= mm_icon( 'chevron-down', 'mm-menu-caret' );
		}
		$item_output .= '</a>';

		if ( $has_children ) {
			++self::$sub_id;
			$this->current_sub = 'mm-sub-' . self::$sub_id;
			$item_output      .= sprintf(
				'<button class="mm-submenu-toggle" type="button" aria-expanded="false" aria-controls="%1$s"><span class="screen-reader-text">%2$s</span>%3$s</button>',
				esc_attr( $this->current_sub ),
				/* translators: %s: menu item title */
				esc_html( sprintf( __( 'Show submenu for %s', 'minemirth' ), wp_strip_all_tags( $title ) ) ),
				mm_icon( 'chevron-down' )
			);
		}
		$item_output .= $args->after ?? '';

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
}

/**
 * Fallback primary menu when none is assigned.
 */
function mm_menu_fallback( $args = array() ): void {
	$links = array(
		mm_shop_url()                                      => __( 'Shop', 'minemirth' ),
		home_url( '/miner-hosting/' )                      => __( 'Hosting', 'minemirth' ),
		home_url( '/free-calculators/' )                   => __( 'Calculators', 'minemirth' ),
		mm_blog_url()                                      => __( 'Learn', 'minemirth' ),
		mm_url( (string) mm_opt( 'contact_url' ) )         => __( 'Contact', 'minemirth' ),
	);
	$class = is_array( $args ) && ! empty( $args['menu_class'] ) ? $args['menu_class'] : 'mm-menu';
	echo '<ul class="' . esc_attr( $class ) . '">';
	foreach ( $links as $url => $label ) {
		echo '<li class="mm-menu-item mm-menu-item--depth-0"><a class="mm-menu-link" href="' . esc_url( $url ) . '"><span class="mm-menu-label"><span class="mm-menu-title">' . esc_html( $label ) . '</span></span></a></li>';
	}
	echo '</ul>';
}

/**
 * Footer column fallback links.
 */
function mm_footer_fallback_links( int $column ): array {
	$account = mm_account_url();
	$cols    = array(
		1 => array(
			mm_shop_url()                                    => __( 'All ASIC miners', 'minemirth' ),
			add_query_arg( 'cryptocurrency', 'btc', mm_shop_url() )      => __( 'Bitcoin miners', 'minemirth' ),
			add_query_arg( 'cryptocurrency', 'ltc-doge', mm_shop_url() ) => __( 'LTC / DOGE miners', 'minemirth' ),
			home_url( '/miner-hosting/' )                    => __( 'Miner hosting', 'minemirth' ),
		),
		2 => array(
			home_url( '/free-calculators/' )                            => __( 'Free calculators', 'minemirth' ),
			home_url( '/free-calculators/bitcoin-mining-calculator/' ) => __( 'Bitcoin calculator', 'minemirth' ),
			mm_blog_url()                                              => __( 'Mining guides', 'minemirth' ),
			home_url( '/llms.txt' )                                    => __( 'Site summary for AI', 'minemirth' ),
		),
		3 => array(
			mm_url( (string) mm_opt( 'contact_url' ) ) => __( 'Contact us', 'minemirth' ),
			$account                                    => __( 'My account', 'minemirth' ),
			get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/privacy-policy/' ) => __( 'Privacy policy', 'minemirth' ),
		),
	);
	return $cols[ $column ] ?? array();
}
