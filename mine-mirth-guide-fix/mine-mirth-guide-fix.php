<?php
/**
 * Plugin Name:       Mine Mirth Guide Page Fix
 * Description:       Fixes guide pages (Learn › any guide) on the Mine Mirth 5 theme, where the article, related guides and the "Run the numbers" card squeezed into narrow columns and covered each other. No settings: activate it and clear your page cache.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-guide-fix
 *
 * Why the pages broke: the theme's Guide template puts the class "mm-guide" on the page's <main>
 * element, and the floating live-guide bubble uses the same class. The bubble's styles (fixed to the
 * bottom-left corner, 380px wide, ignoring clicks) were applied to the whole page content. This
 * plugin renames the Guide template's classes in the page output to "mm-gpage" and gives them the
 * styles they were meant to have. The live-guide bubble is not touched and keeps working.
 *
 * @package MineMirthGuideFix
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Guide template's own class names and their safe replacements (exact markup from the theme).
 */
function mmgf_replacements(): array {
	return array(
		'class="mm-main mm-guide"'                     => 'class="mm-main mm-gpage"',
		'class="mm-article-head mm-guide__head"'        => 'class="mm-article-head mm-gpage__head"',
		'class="mm-pill mm-guide__pill"'                => 'class="mm-pill mm-gpage__pill"',
		'class="mm-meta mm-guide__meta"'                => 'class="mm-meta mm-gpage__meta"',
		'class="entry-content mm-prose mm-guide__body"' => 'class="entry-content mm-prose mm-gpage__body"',
	);
}

/**
 * Is this a page using the theme's Guide template?
 */
function mmgf_is_guide(): bool {
	return ! is_admin() && is_page() && is_page_template( 'page-templates/guide.php' );
}

/**
 * Rewrite the class names in the finished page.
 */
add_action(
	'template_redirect',
	static function () {
		if ( ! mmgf_is_guide() ) {
			return;
		}
		ob_start(
			static function ( $html ) {
				if ( ! is_string( $html ) || false === strpos( $html, 'mm-guide' ) ) {
					return $html;
				}
				return strtr( $html, mmgf_replacements() );
			}
		);
	},
	0
);

/**
 * The styles the Guide template's elements were meant to have, under the new names, plus a safety
 * net in case a page cache still serves the old markup.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! mmgf_is_guide() ) {
			return;
		}
		$css = '
.mm-gpage__pill { margin-bottom: 18px; }
.mm-gpage__pill .mm-icon { width: 14px; height: 14px; }
.mm-gpage__meta { display: flex; flex-wrap: wrap; gap: 8px 18px; color: var(--mm-muted); font-size: .88rem; }
.mm-gpage__meta span { display: inline-flex; align-items: center; gap: 6px; }
.mm-gpage__meta .mm-icon { width: 16px; height: 16px; }
.mm-gpage .mm-article-layout { padding-bottom: clamp(32px, 5vw, 64px); }
.mm-gpage__body > h2:first-child { margin-top: 0; }
main.mm-guide { position: static; left: auto; bottom: auto; z-index: auto; display: block; max-width: none; pointer-events: auto; transition: none; }
body.mm-lock main.mm-guide { visibility: visible; }
@media print { main.mm-guide { display: block !important; } }
@media (max-width: 640px) { main.mm-guide { left: auto; bottom: auto; max-width: none; } }
';
		wp_register_style( 'mmgf', false, array(), '1.0.0' );
		wp_enqueue_style( 'mmgf' );
		wp_add_inline_style( 'mmgf', $css );
	},
	20
);
