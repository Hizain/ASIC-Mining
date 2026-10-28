<?php
/**
 * Editor fields for each section layout. assets/js/sections-editor.js builds the sidebar from this.
 *
 * Field: k (key), t (type), l (label), h (help), o (select options), f (sub-fields for "items").
 * Types: text, textarea, html, url, select, toggle, number, icon, image, lines, items, buttons, query.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

function mm_sf( string $k, string $t, string $l, array $extra = array() ): array {
	return array_merge( array( 'k' => $k, 't' => $t, 'l' => $l ), $extra );
}

function mm_sections_schema(): array {
	$head    = array(
		mm_sf( 'eyebrow', 'text', __( 'Eyebrow', 'minemirth' ) ),
		mm_sf( 'title', 'text', __( 'Title', 'minemirth' ), array( 'h' => __( 'Wrap words in *stars* to highlight them. Live values: {btc_price}, {hashprice}, {difficulty}, {hashrate}, {halving_days}, {halving_date}, {adjustment}, {miners_count}.', 'minemirth' ) ) ),
		mm_sf( 'text', 'html', __( 'Intro text', 'minemirth' ) ),
		mm_sf( 'center', 'toggle', __( 'Center the heading', 'minemirth' ) ),
		mm_sf( 'link_label', 'text', __( 'Heading link text', 'minemirth' ) ),
		mm_sf( 'link_url', 'url', __( 'Heading link URL', 'minemirth' ) ),
	);
	$buttons = mm_sf( 'buttons', 'buttons', __( 'Buttons', 'minemirth' ), array( 'h' => __( 'Links can use {shop}, {page:contact}, {tool:profit}, {hosting}.', 'minemirth' ) ) );
	$visual  = array(
		mm_sf( 'visual', 'select', __( 'Visual', 'minemirth' ), array( 'o' => array( 'orbit' => __( 'Icon orbit', 'minemirth' ), 'market' => __( 'Live Bitcoin card', 'minemirth' ), 'product' => __( 'Featured miner', 'minemirth' ), 'checklist' => __( 'Checklist card', 'minemirth' ), 'stats' => __( 'Stats card', 'minemirth' ), 'image' => __( 'Image', 'minemirth' ), 'none' => __( 'None', 'minemirth' ) ) ) ),
		mm_sf( 'icon', 'icon', __( 'Orbit icon', 'minemirth' ) ),
		mm_sf( 'chips', 'items', __( 'Orbit chips (up to 4)', 'minemirth' ), array( 'f' => array( mm_sf( 'icon', 'icon', __( 'Icon', 'minemirth' ) ), mm_sf( 'value', 'text', __( 'Value', 'minemirth' ) ), mm_sf( 'label', 'text', __( 'Label', 'minemirth' ) ) ) ) ),
		mm_sf( 'image', 'image', __( 'Image', 'minemirth' ) ),
		mm_sf( 'query', 'query', __( 'Featured miner filter', 'minemirth' ) ),
		mm_sf( 'checklist_title', 'text', __( 'Checklist title', 'minemirth' ) ),
		mm_sf( 'checklist', 'lines', __( 'Checklist (one per line)', 'minemirth' ) ),
		mm_sf( 'stats', 'items', __( 'Stats card', 'minemirth' ), array( 'f' => array( mm_sf( 'value', 'text', __( 'Value', 'minemirth' ) ), mm_sf( 'label', 'text', __( 'Label', 'minemirth' ) ) ) ) ),
	);
	$items   = static fn( array $f, string $label = '' ) => mm_sf( 'items', 'items', $label ? $label : __( 'Items', 'minemirth' ), array( 'f' => $f ) );

	return array(
		'fxhero'     => array(
			'label'  => __( 'Animated hero', 'minemirth' ),
			'icon'   => 'superhero',
			'fields' => array(
				mm_sf( 'eyebrow', 'text', __( 'Eyebrow', 'minemirth' ) ),
				mm_sf( 'title', 'text', __( 'Headline', 'minemirth' ), array( 'h' => __( 'Use {rotate} where the rotating words go and *stars* for gradient text.', 'minemirth' ) ) ),
				mm_sf( 'rotate', 'lines', __( 'Rotating words (one per line)', 'minemirth' ) ),
				mm_sf( 'text', 'html', __( 'Text', 'minemirth' ), array( 'h' => __( 'Live values work here: {btc_price}, {hashprice}, {difficulty}.', 'minemirth' ) ) ),
				mm_sf( 'btn1_text', 'text', __( 'Main button text', 'minemirth' ) ),
				mm_sf( 'btn1_url', 'url', __( 'Main button link', 'minemirth' ) ),
				mm_sf( 'btn2_text', 'text', __( 'Second button text', 'minemirth' ) ),
				mm_sf( 'btn2_url', 'url', __( 'Second button link', 'minemirth' ) ),
				mm_sf( 'trust', 'lines', __( 'Trust points (one per line)', 'minemirth' ) ),
				mm_sf( 'visual', 'select', __( 'Visual', 'minemirth' ), array( 'o' => array( '' => __( 'As in Customize', 'minemirth' ), 'sphere' => __( '3D hash sphere', 'minemirth' ), 'rig' => __( 'Animated mining rig', 'minemirth' ), 'product' => __( 'Featured product', 'minemirth' ), 'none' => __( 'None', 'minemirth' ) ) ) ),
				mm_sf( 'background', 'select', __( 'Moving background', 'minemirth' ), array( 'o' => array( '' => __( 'As in Customize', 'minemirth' ), 'aurora' => __( 'Aurora', 'minemirth' ), 'particles' => __( 'Hash particles', 'minemirth' ), 'beams' => __( 'Light beams', 'minemirth' ), 'plain' => __( 'Plain grid', 'minemirth' ) ) ) ),
				mm_sf( 'layout', 'select', __( 'Layout', 'minemirth' ), array( 'o' => array( '' => __( 'As in Customize', 'minemirth' ), 'split' => __( 'Text left, visual right', 'minemirth' ), 'centered' => __( 'Centered', 'minemirth' ) ) ) ),
			),
			'data'   => array( 'eyebrow' => __( 'Live Bitcoin network', 'minemirth' ), 'title' => __( 'The professional way to mine *{rotate}*', 'minemirth' ), 'rotate' => array( 'Bitcoin', 'Litecoin', 'Dogecoin', 'Kaspa' ), 'visual' => 'sphere', 'background' => 'aurora', 'pad' => 'none' ),
		),
		'ticker'     => array(
			'label'  => __( 'Live ticker', 'minemirth' ),
			'icon'   => 'chart-line',
			'fields' => array(
				mm_sf( 'metrics', 'text', __( 'Live metrics', 'minemirth' ), array( 'h' => __( 'Comma separated: price, change, hashrate, difficulty, height, fees, adjustment, halving. Empty uses Customize.', 'minemirth' ) ) ),
				mm_sf( 'custom', 'lines', __( 'Your messages (one per line)', 'minemirth' ) ),
			),
			'data'   => array( 'metrics' => 'price, change, hashrate, difficulty, height, adjustment, halving', 'pad' => 'none' ),
		),
		'hero'       => array(
			'label'  => __( 'Hero', 'minemirth' ),
			'icon'   => 'cover-image',
			'fields' => array_merge(
				array( mm_sf( 'eyebrow', 'text', __( 'Eyebrow', 'minemirth' ) ), mm_sf( 'title', 'text', __( 'Title', 'minemirth' ), array( 'h' => __( '*Stars* highlight words.', 'minemirth' ) ) ), mm_sf( 'text', 'html', __( 'Text', 'minemirth' ) ), $buttons, mm_sf( 'badges', 'lines', __( 'Badges under the buttons (one per line)', 'minemirth' ) ), mm_sf( 'center', 'toggle', __( 'Centered', 'minemirth' ) ), mm_sf( 'crumbs', 'toggle', __( 'Show breadcrumbs', 'minemirth' ) ), mm_sf( 'h2', 'toggle', __( 'Use H2 instead of H1', 'minemirth' ) ) ),
				$visual
			),
			'data'   => array( 'eyebrow' => __( 'Mine Mirth', 'minemirth' ), 'title' => __( 'A bold *headline*', 'minemirth' ), 'text' => __( 'One or two sentences that say what this page offers.', 'minemirth' ), 'buttons' => array( array( 'label' => __( 'Shop miners', 'minemirth' ), 'url' => '{shop}', 'style' => 'primary' ) ), 'visual' => 'orbit', 'icon' => 'cpu', 'crumbs' => true, 'tone' => 'plain' ),
		),
		'features'   => array(
			'label'  => __( 'Feature grid', 'minemirth' ),
			'icon'   => 'grid-view',
			'fields' => array_merge(
				$head,
				array(
					mm_sf( 'columns', 'select', __( 'Columns', 'minemirth' ), array( 'o' => array( '2' => '2', '3' => '3', '4' => '4' ) ) ),
					mm_sf( 'style', 'select', __( 'Style', 'minemirth' ), array( 'o' => array( 'cards' => __( 'Cards', 'minemirth' ), 'minimal' => __( 'Minimal', 'minemirth' ), 'numbered' => __( 'Numbered', 'minemirth' ), 'bento' => __( 'Bento', 'minemirth' ) ) ) ),
					$items( array( mm_sf( 'icon', 'icon', __( 'Icon', 'minemirth' ) ), mm_sf( 'title', 'text', __( 'Title', 'minemirth' ) ), mm_sf( 'text', 'html', __( 'Text', 'minemirth' ) ), mm_sf( 'url', 'url', __( 'Link (optional)', 'minemirth' ) ), mm_sf( 'link_label', 'text', __( 'Link text', 'minemirth' ) ) ) ),
					$buttons,
				)
			),
			'data'   => array( 'title' => __( 'Why it *matters*', 'minemirth' ), 'columns' => '3', 'style' => 'cards', 'items' => array( array( 'icon' => 'shield-check', 'title' => __( 'Feature', 'minemirth' ), 'text' => __( 'Describe the benefit.', 'minemirth' ) ), array( 'icon' => 'bolt', 'title' => __( 'Feature', 'minemirth' ), 'text' => __( 'Describe the benefit.', 'minemirth' ) ), array( 'icon' => 'headset', 'title' => __( 'Feature', 'minemirth' ), 'text' => __( 'Describe the benefit.', 'minemirth' ) ) ) ),
		),
		'split'      => array(
			'label'  => __( 'Text + visual', 'minemirth' ),
			'icon'   => 'align-pull-left',
			'fields' => array_merge( array( mm_sf( 'eyebrow', 'text', __( 'Eyebrow', 'minemirth' ) ), mm_sf( 'title', 'text', __( 'Title', 'minemirth' ) ), mm_sf( 'text', 'html', __( 'Text', 'minemirth' ) ), mm_sf( 'bullets', 'lines', __( 'Bullet points (one per line)', 'minemirth' ) ), $buttons, mm_sf( 'reverse', 'toggle', __( 'Visual on the left', 'minemirth' ) ) ), $visual ),
			'data'   => array( 'title' => __( 'Explain *one idea*', 'minemirth' ), 'text' => __( 'Supporting paragraph.', 'minemirth' ), 'bullets' => array( __( 'First point', 'minemirth' ), __( 'Second point', 'minemirth' ) ), 'visual' => 'orbit', 'icon' => 'server' ),
		),
		'stats'      => array(
			'label'  => __( 'Stats', 'minemirth' ),
			'icon'   => 'chart-bar',
			'fields' => array_merge( $head, array( mm_sf( 'style', 'select', __( 'Style', 'minemirth' ), array( 'o' => array( 'band' => __( 'Gradient band', 'minemirth' ), 'cards' => __( 'Cards', 'minemirth' ) ) ) ), $items( array( mm_sf( 'icon', 'icon', __( 'Icon', 'minemirth' ) ), mm_sf( 'value', 'text', __( 'Value (tokens allowed)', 'minemirth' ) ), mm_sf( 'label', 'text', __( 'Label', 'minemirth' ) ), mm_sf( 'text', 'text', __( 'Small print', 'minemirth' ) ) ) ), mm_sf( 'live', 'toggle', __( 'Contains live values', 'minemirth' ) ), mm_sf( 'note', 'text', __( 'Note under the stats', 'minemirth' ) ) ) ),
			'data'   => array( 'style' => 'band', 'live' => true, 'items' => array( array( 'value' => '{btc_price}', 'label' => __( 'Bitcoin price', 'minemirth' ) ), array( 'value' => '{hashprice}', 'label' => __( 'Hashprice / PH / day', 'minemirth' ) ), array( 'value' => '{halving_days}', 'label' => __( 'Days to the halving', 'minemirth' ) ) ) ),
		),
		'steps'      => array(
			'label'  => __( 'Steps', 'minemirth' ),
			'icon'   => 'editor-ol',
			'fields' => array_merge( $head, array( $items( array( mm_sf( 'icon', 'icon', __( 'Icon (optional)', 'minemirth' ) ), mm_sf( 'title', 'text', __( 'Title', 'minemirth' ) ), mm_sf( 'text', 'html', __( 'Text', 'minemirth' ) ) ) ), $buttons ) ),
			'data'   => array( 'title' => __( 'How it *works*', 'minemirth' ), 'items' => array( array( 'title' => __( 'Step one', 'minemirth' ), 'text' => __( 'What happens.', 'minemirth' ) ), array( 'title' => __( 'Step two', 'minemirth' ), 'text' => __( 'What happens.', 'minemirth' ) ), array( 'title' => __( 'Step three', 'minemirth' ), 'text' => __( 'What happens.', 'minemirth' ) ) ) ),
		),
		'faq'        => array(
			'label'  => __( 'FAQ', 'minemirth' ),
			'icon'   => 'editor-help',
			'fields' => array_merge( $head, array( $items( array( mm_sf( 'q', 'text', __( 'Question', 'minemirth' ) ), mm_sf( 'a', 'html', __( 'Answer', 'minemirth' ) ) ), __( 'Questions', 'minemirth' ) ), $buttons, mm_sf( 'open_first', 'toggle', __( 'Open the first answer', 'minemirth' ) ), mm_sf( 'schema', 'toggle', __( 'Add FAQ structured data for Google', 'minemirth' ) ) ) ),
			'data'   => array( 'eyebrow' => __( 'FAQ', 'minemirth' ), 'title' => __( 'Questions, *answered*', 'minemirth' ), 'schema' => true, 'open_first' => true, 'items' => array( array( 'q' => __( 'Question?', 'minemirth' ), 'a' => __( 'Answer.', 'minemirth' ) ) ) ),
		),
		'cta'        => array(
			'label'  => __( 'Call to action', 'minemirth' ),
			'icon'   => 'megaphone',
			'fields' => array( mm_sf( 'eyebrow', 'text', __( 'Eyebrow', 'minemirth' ) ), mm_sf( 'title', 'text', __( 'Title', 'minemirth' ) ), mm_sf( 'text', 'html', __( 'Text', 'minemirth' ) ), $buttons, mm_sf( 'icon', 'icon', __( 'Icon (optional)', 'minemirth' ) ), mm_sf( 'note', 'text', __( 'Small note', 'minemirth' ) ), mm_sf( 'style', 'select', __( 'Style', 'minemirth' ), array( 'o' => array( 'dark' => __( 'Dark', 'minemirth' ), 'gradient' => __( 'Gradient', 'minemirth' ), 'light' => __( 'Light', 'minemirth' ) ) ) ) ),
			'data'   => array( 'title' => __( 'Ready to *start mining*?', 'minemirth' ), 'text' => __( 'Talk to us or pick a miner.', 'minemirth' ), 'style' => 'dark', 'buttons' => array( array( 'label' => __( 'Shop miners', 'minemirth' ), 'url' => '{shop}', 'style' => 'primary' ), array( 'label' => __( 'Talk to us', 'minemirth' ), 'url' => '{page:contact}', 'style' => 'glass' ) ) ),
		),
		'table'      => array(
			'label'  => __( 'Comparison table', 'minemirth' ),
			'icon'   => 'editor-table',
			'fields' => array_merge( $head, array( mm_sf( 'columns', 'lines', __( 'Column headings (one per line)', 'minemirth' ) ), mm_sf( 'rows', 'lines', __( 'Rows: cells separated by | (yes / no show ticks)', 'minemirth' ) ), mm_sf( 'highlight', 'number', __( 'Highlight column number (0 = first)', 'minemirth' ) ), mm_sf( 'note', 'text', __( 'Note', 'minemirth' ) ) ) ),
			'data'   => array( 'columns' => array( '', 'Option A', 'Option B' ), 'rows' => array( 'Feature | yes | no' ), 'highlight' => 1 ),
		),
		'products'   => array(
			'label'  => __( 'Miners from the shop', 'minemirth' ),
			'icon'   => 'products',
			'fields' => array_merge( $head, array( mm_sf( 'query', 'query', __( 'Which miners', 'minemirth' ) ), mm_sf( 'limit', 'number', __( 'How many', 'minemirth' ) ), mm_sf( 'show_sold', 'toggle', __( 'Include sold-out models', 'minemirth' ) ), mm_sf( 'empty', 'textarea', __( 'Message when none match', 'minemirth' ) ) ) ),
			'data'   => array( 'title' => __( 'Miners in *stock*', 'minemirth' ), 'limit' => 8, 'query' => array( 'sort' => 'popular' ) ),
		),
		'profitability' => array(
			'label'  => __( 'Miner profitability', 'minemirth' ),
			'icon'   => 'chart-bar',
			'fields' => array_merge( $head, array( mm_sf( 'limit', 'number', __( 'How many miners (0 = all, with every filter)', 'minemirth' ) ) ) ),
			'data'   => array( 'limit' => 10 ),
		),
		'links'      => array(
			'label'  => __( 'Link cards', 'minemirth' ),
			'icon'   => 'index-card',
			'fields' => array_merge( $head, array( mm_sf( 'auto', 'text', __( 'Automatic list', 'minemirth' ), array( 'h' => __( '"children" lists this page’s sub-pages. Leave empty to use the cards below.', 'minemirth' ) ) ), mm_sf( 'columns', 'select', __( 'Columns', 'minemirth' ), array( 'o' => array( '2' => '2', '3' => '3', '4' => '4' ) ) ), $items( array( mm_sf( 'icon', 'icon', __( 'Icon', 'minemirth' ) ), mm_sf( 'title', 'text', __( 'Title', 'minemirth' ) ), mm_sf( 'text', 'textarea', __( 'Text', 'minemirth' ) ), mm_sf( 'url', 'url', __( 'Link', 'minemirth' ) ), mm_sf( 'badge', 'text', __( 'Badge', 'minemirth' ) ), mm_sf( 'group', 'text', __( 'Group heading', 'minemirth' ) ) ), __( 'Cards', 'minemirth' ) ) ) ),
			'data'   => array( 'auto' => 'children', 'columns' => '3' ),
		),
		'market'     => array(
			'label'  => __( 'Live Bitcoin network', 'minemirth' ),
			'icon'   => 'chart-line',
			'fields' => array_merge( $head, array( $buttons ) ),
			'data'   => array( 'eyebrow' => __( 'Live', 'minemirth' ), 'title' => __( 'The network *right now*', 'minemirth' ) ),
		),
		'tool'       => array(
			'label'  => __( 'Mining tool', 'minemirth' ),
			'icon'   => 'calculator',
			'fields' => array_merge( $head, array( mm_sf( 'id', 'tool', __( 'Tool', 'minemirth' ) ) ) ),
			'data'   => array( 'id' => 'profit' ),
		),
		'tools'      => array(
			'label'  => __( 'Tools grid', 'minemirth' ),
			'icon'   => 'screenoptions',
			'fields' => array_merge( $head, array( mm_sf( 'limit', 'number', __( 'How many (0 = all)', 'minemirth' ) ), mm_sf( 'exclude', 'tool', __( 'Leave out', 'minemirth' ) ) ) ),
			'data'   => array( 'title' => __( 'Free *mining tools*', 'minemirth' ), 'limit' => 0 ),
		),
		'reviews'    => array(
			'label'  => __( 'Customer reviews', 'minemirth' ),
			'icon'   => 'star-filled',
			'fields' => array_merge( $head, array( mm_sf( 'limit', 'number', __( 'How many', 'minemirth' ) ), mm_sf( 'empty', 'textarea', __( 'Message when there are no reviews yet', 'minemirth' ) ), mm_sf( 'hide_empty', 'toggle', __( 'Hide this section until there are reviews', 'minemirth' ) ) ) ),
			'data'   => array( 'title' => __( 'What buyers *say*', 'minemirth' ), 'limit' => 6 ),
		),
		'pricing'    => array(
			'label'  => __( 'Hosting plans', 'minemirth' ),
			'icon'   => 'money-alt',
			'fields' => array_merge( $head, array( mm_sf( 'note', 'text', __( 'Note', 'minemirth' ) ) ) ),
			'data'   => array( 'title' => __( 'Hosting *plans*', 'minemirth' ) ),
		),
		'contact'    => array(
			'label'  => __( 'Contact', 'minemirth' ),
			'icon'   => 'email',
			'fields' => array_merge( $head, array( mm_sf( 'form', 'text', __( 'Form shortcode', 'minemirth' ), array( 'h' => __( 'Leave empty to use the Mine Mirth Contact plugin form when it is active.', 'minemirth' ) ) ), mm_sf( 'note', 'html', __( 'Note', 'minemirth' ) ) ) ),
			'data'   => array( 'title' => __( 'Talk to a *mining specialist*', 'minemirth' ) ),
		),
		'callout'    => array(
			'label'  => __( 'Callout', 'minemirth' ),
			'icon'   => 'info',
			'fields' => array( mm_sf( 'tone', 'select', __( 'Type', 'minemirth' ), array( 'o' => array( 'info' => __( 'Info', 'minemirth' ), 'primary' => __( 'Highlight', 'minemirth' ), 'success' => __( 'Good to know', 'minemirth' ), 'warning' => __( 'Warning', 'minemirth' ) ) ) ), mm_sf( 'icon', 'icon', __( 'Icon', 'minemirth' ) ), mm_sf( 'title', 'text', __( 'Title', 'minemirth' ) ), mm_sf( 'text', 'html', __( 'Text', 'minemirth' ) ), mm_sf( 'button_label', 'text', __( 'Button text', 'minemirth' ) ), mm_sf( 'button_url', 'url', __( 'Button link', 'minemirth' ) ) ),
			'data'   => array( 'tone' => 'info', 'title' => __( 'Good to know', 'minemirth' ), 'text' => __( 'A short tip.', 'minemirth' ), 'pad' => 'sm' ),
			'notone' => true,
		),
		'brands'     => array(
			'label'  => __( 'Brands', 'minemirth' ),
			'icon'   => 'awards',
			'fields' => array_merge( $head, array( $items( array( mm_sf( 'name', 'text', __( 'Brand', 'minemirth' ) ), mm_sf( 'text', 'text', __( 'Short line', 'minemirth' ) ), mm_sf( 'url', 'url', __( 'Link', 'minemirth' ) ) ), __( 'Brands', 'minemirth' ) ) ) ),
			'data'   => array( 'title' => __( 'Brands we *supply*', 'minemirth' ), 'items' => array( array( 'name' => 'Bitmain', 'text' => 'Antminer', 'url' => '{page:brand-bitmain}' ) ) ),
		),
		'posts'      => array(
			'label'  => __( 'Latest articles', 'minemirth' ),
			'icon'   => 'admin-post',
			'fields' => array_merge( $head, array( mm_sf( 'category', 'text', __( 'Category slug (optional)', 'minemirth' ) ), mm_sf( 'limit', 'number', __( 'How many', 'minemirth' ) ) ) ),
			'data'   => array( 'title' => __( 'From the *blog*', 'minemirth' ), 'limit' => 3 ),
		),
		'newsletter' => array(
			'label'  => __( 'Newsletter', 'minemirth' ),
			'icon'   => 'email-alt',
			'fields' => array( mm_sf( 'icon', 'icon', __( 'Icon', 'minemirth' ) ), mm_sf( 'eyebrow', 'text', __( 'Eyebrow', 'minemirth' ) ), mm_sf( 'title', 'text', __( 'Title', 'minemirth' ) ), mm_sf( 'text', 'html', __( 'Text', 'minemirth' ) ), mm_sf( 'note', 'text', __( 'Small print', 'minemirth' ) ), mm_sf( 'context', 'text', __( 'Sign-up source label', 'minemirth' ) ) ),
			'data'   => array( 'title' => __( 'Get the weekly *mining brief*', 'minemirth' ), 'text' => __( 'Hashprice, difficulty and new stock, once a week.', 'minemirth' ) ),
		),
		'badges'     => array(
			'label'  => __( 'Trust badges', 'minemirth' ),
			'icon'   => 'shield',
			'fields' => array( $items( array( mm_sf( 'icon', 'icon', __( 'Icon', 'minemirth' ) ), mm_sf( 'title', 'text', __( 'Title', 'minemirth' ) ), mm_sf( 'text', 'text', __( 'Text', 'minemirth' ) ) ), __( 'Badges', 'minemirth' ) ), mm_sf( 'payments', 'toggle', __( 'Show payment method logos', 'minemirth' ) ) ),
			'data'   => array( 'pad' => 'sm', 'items' => array( array( 'icon' => 'shield-check', 'title' => __( 'Warranty', 'minemirth' ), 'text' => __( 'On every miner', 'minemirth' ) ) ) ),
		),
		'prose'      => array(
			'label'  => __( 'Rich text', 'minemirth' ),
			'icon'   => 'text-page',
			'fields' => array_merge( $head, array( mm_sf( 'html', 'html', __( 'Content', 'minemirth' ) ) ) ),
			'data'   => array( 'html' => '<p>' . __( 'Write here.', 'minemirth' ) . '</p>' ),
		),
	);
}

/** Fields every layout shares (appended by the editor). */
function mm_sections_common_fields(): array {
	return array(
		mm_sf( 'tone', 'select', __( 'Background', 'minemirth' ), array( 'o' => array( 'plain' => __( 'Page background', 'minemirth' ), 'alt' => __( 'Soft tint', 'minemirth' ), 'gradient' => __( 'Gradient glow', 'minemirth' ), 'dark' => __( 'Dark', 'minemirth' ) ) ) ),
		mm_sf( 'pad', 'select', __( 'Spacing', 'minemirth' ), array( 'o' => array( 'md' => __( 'Normal', 'minemirth' ), 'sm' => __( 'Compact', 'minemirth' ), 'lg' => __( 'Roomy', 'minemirth' ), 'none' => __( 'None', 'minemirth' ) ) ) ),
		mm_sf( 'deco', 'select', __( 'Animated shapes', 'minemirth' ), array( 'o' => array( 'auto' => __( 'Automatic', 'minemirth' ), 'coins' => __( 'Floating coins', 'minemirth' ), 'chain' => __( 'Blockchain transactions', 'minemirth' ), 'none' => __( 'None', 'minemirth' ) ) ) ),
		mm_sf( 'anchor', 'text', __( 'Anchor (for #links)', 'minemirth' ) ),
	);
}
