<?php
/**
 * Plugin Name:       Mine Mirth Tools Showcase
 * Description:       Shows your mining calculators and tools as a filterable card grid with the shortcode [minemirth_tools]. Add, edit, reorder and remove tools in Settings › Tools Showcase.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-tools
 *
 * @package MineMirthTools
 */

defined( 'ABSPATH' ) || exit;

define( 'MMT_VERSION', '1.0.0' );
define( 'MMT_URI', plugin_dir_url( __FILE__ ) );

/**
 * Colour styles for icons, badges and tags.
 */
function mmt_styles(): array {
	return array(
		'blue'   => __( 'Blue', 'mine-mirth-tools' ),
		'sky'    => __( 'Sky blue', 'mine-mirth-tools' ),
		'green'  => __( 'Green', 'mine-mirth-tools' ),
		'orange' => __( 'Orange', 'mine-mirth-tools' ),
	);
}

function mmt_default_settings(): array {
	$t = static function ( $title, $desc, $icon, $style, $tags, $url, $status, $badge = '', $featured = 0 ) {
		return array(
			'title'    => $title,
			'desc'     => $desc,
			'icon'     => $icon,
			'style'    => $style,
			'tags'     => $tags,
			'badge'    => $badge,
			'url'      => $url,
			'status'   => $status,
			'featured' => $featured,
		);
	};
	return array(
		'heading'   => 'Free Mining Calculators',
		'intro'     => 'Advanced tools to maximize your mining profitability with data-driven insights',
		'subtitle'  => 'Go beyond basic calculations with our specialized tool suite',
		'show_tabs' => 1,
		'button'    => 'Use Tool',
		'soon'      => 'Coming Soon',
		'tools'     => array(
			$t( 'ASIC Mining Profitability Calculator', 'Calculate potential profits from your ASIC mining operations based on current market conditions, electricity costs, and hardware efficiency.', 'fa-calculator', 'blue', 'Profit Analysis, Real-time Data', 'https://minemirth.com/free-calculators/bitcoin-mining-calculator/', 'available' ),
			$t( 'ASIC Market Scope – Long-Term View', 'Analyze long-term trends in the ASIC mining market to make strategic decisions about hardware investments and mining strategies.', 'fa-chart-line', 'sky', 'Market Analysis, Trend Forecasting', 'https://minemirth.com/free-calculators/asic-market-scope-long-term-view/', 'available' ),
			$t( 'Mining Rig Optimizer', 'AI-driven analysis to optimize your mining rig configuration for maximum efficiency and hash rate based on your specific hardware combination.', 'fa-satellite-dish', 'green', 'AI Optimization, Performance, Hardware', 'https://minemirth.com/free-calculators/mining-rig-optimizer-ai-driven-analysis-mine-mirth/', 'available', 'AI-Powered', 1 ),
			$t( 'Hydro-Cooling Efficiency Calculator', 'Calculate the efficiency gains and cost savings of implementing hydro-cooling systems for your mining operation in various climates.', 'fa-water', 'green', 'Cooling, Efficiency', '', 'soon', 'Innovative' ),
			$t( 'Automated Profit Switching Advisor', 'Intelligent system that recommends when to switch between different cryptocurrencies based on real-time profitability metrics.', 'fa-robot', 'orange', 'Automation, Multi-Algo', '', 'soon', 'Smart' ),
			$t( 'Global Mining Location Analyzer', 'Compare electricity costs, regulatory environments, and climate conditions across global locations to find your ideal mining destination.', 'fa-map-marked-alt', 'green', 'Geographic, Regulatory', '', 'soon', 'Strategic' ),
			$t( 'Staking vs Mining Comparator', 'Compare profitability between traditional mining and proof-of-stake alternatives to optimize your crypto earnings strategy.', 'fa-broadcast-tower', 'orange', 'Comparison, ROI Analysis', '', 'soon', 'Hybrid' ),
			$t( 'Renewable Energy Integration Planner', 'Plan and calculate ROI for integrating solar, wind, or other renewable energy sources into your mining operation.', 'fa-wind', 'green', 'Sustainability, Solar/Wind', '', 'soon', 'Eco-Friendly' ),
			$t( 'Mining Security Audit Tool', 'Comprehensive security assessment for your mining operation including network vulnerabilities, physical security, and wallet protection.', 'fa-shield-alt', 'orange', 'Risk Assessment, Security', '', 'soon', 'Security' ),
			$t( 'Pool Hopping Strategy Simulator', 'Simulate different pool hopping strategies to maximize earnings while minimizing the risks associated with frequent pool switching.', 'fa-network-wired', 'green', 'Strategy, Simulation', '', 'soon', 'Advanced' ),
			$t( 'Heat Reutilization Calculator', 'Calculate potential savings from repurposing mining rig heat for residential or commercial heating applications.', 'fa-temperature-high', 'orange', 'Heat Management, Energy Savings', '', 'soon', 'Efficiency' ),
			$t( 'Tax Optimization Calculator', 'Navigate cryptocurrency mining taxation with tools to optimize your tax strategy across different jurisdictions and mining structures.', 'fa-file-invoice-dollar', 'green', 'Tax Planning, Compliance', '', 'soon', 'Financial' ),
		),
	);
}

function mmt_settings(): array {
	$saved = get_option( 'mmt_settings' );
	return is_array( $saved ) ? array_merge( mmt_default_settings(), $saved ) : mmt_default_settings();
}

/* ---------------------------------------------------------------------------
 * Shortcode
 * ------------------------------------------------------------------------- */

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_register_style( 'mmt-fa', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );
		wp_register_style( 'mmt-tools', MMT_URI . 'assets/tools.css', array( 'mmt-fa' ), MMT_VERSION );
		wp_register_script( 'mmt-tools', MMT_URI . 'assets/tools.js', array(), MMT_VERSION, true );
	}
);

/**
 * [minemirth_tools] — optional attributes: show="all|available|soon", tabs="yes|no", heading="no".
 */
function mmt_shortcode( $atts ): string {
	$s    = mmt_settings();
	$atts = shortcode_atts( array( 'show' => 'all', 'tabs' => '', 'heading' => 'yes' ), $atts, 'minemirth_tools' );
	wp_enqueue_style( 'mmt-tools' );
	wp_enqueue_script( 'mmt-tools' );

	$tools = array_values(
		array_filter(
			(array) $s['tools'],
			static fn( $t ) => ! empty( $t['title'] ) && ( 'all' === $atts['show'] || ( $t['status'] ?? 'available' ) === $atts['show'] )
		)
	);
	$tabs = '' !== $atts['tabs'] ? 'yes' === $atts['tabs'] : (bool) $s['show_tabs'];
	$has  = array( 'available' => 0, 'soon' => 0 );
	foreach ( $tools as $t ) {
		++$has[ 'soon' === ( $t['status'] ?? '' ) ? 'soon' : 'available' ];
	}

	ob_start();
	?>
	<section class="mmt">
		<?php if ( 'no' !== $atts['heading'] && ( $s['heading'] || $s['intro'] || $s['subtitle'] ) ) : ?>
			<header class="mmt__head">
				<?php if ( $s['heading'] ) : ?><h2 class="mmt__title"><?php echo esc_html( $s['heading'] ); ?></h2><?php endif; ?>
				<?php if ( $s['intro'] ) : ?><p class="mmt__intro"><?php echo esc_html( $s['intro'] ); ?></p><?php endif; ?>
				<?php if ( $s['subtitle'] ) : ?><p class="mmt__sub"><?php echo esc_html( $s['subtitle'] ); ?></p><?php endif; ?>
			</header>
		<?php endif; ?>

		<?php if ( $tabs && $has['available'] && $has['soon'] ) : ?>
			<div class="mmt__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Filter tools', 'mine-mirth-tools' ); ?>">
				<button type="button" class="mmt__tab is-active" role="tab" aria-selected="true" data-filter="all"><?php esc_html_e( 'All Tools', 'mine-mirth-tools' ); ?> <span><?php echo (int) count( $tools ); ?></span></button>
				<button type="button" class="mmt__tab" role="tab" aria-selected="false" data-filter="available"><?php esc_html_e( 'Available Now', 'mine-mirth-tools' ); ?> <span><?php echo (int) $has['available']; ?></span></button>
				<button type="button" class="mmt__tab" role="tab" aria-selected="false" data-filter="soon"><?php echo esc_html( $s['soon'] ); ?> <span><?php echo (int) $has['soon']; ?></span></button>
			</div>
		<?php endif; ?>

		<div class="mmt__grid">
			<?php
			foreach ( $tools as $t ) :
				$soon  = 'soon' === ( $t['status'] ?? '' ) || '' === trim( (string) ( $t['url'] ?? '' ) );
				$style = isset( mmt_styles()[ $t['style'] ?? '' ] ) ? $t['style'] : 'blue';
				$icon  = preg_replace( '/[^a-z0-9 -]/', '', strtolower( (string) ( $t['icon'] ?? 'fa-calculator' ) ) );
				$icon  = false !== strpos( $icon, 'fa-' ) ? $icon : 'fa-' . $icon;
				$icon  = preg_match( '/\bfa-(solid|regular|brands)\b|\bfa[srb]\b/', $icon ) ? $icon : 'fa-solid ' . $icon;
				$tags  = array_filter( array_map( 'trim', explode( ',', (string) ( $t['tags'] ?? '' ) ) ) );
				?>
				<article class="mmt-card mmt-card--<?php echo esc_attr( $style ); ?><?php echo ! empty( $t['featured'] ) ? ' is-featured' : ''; ?>" data-status="<?php echo $soon ? 'soon' : 'available'; ?>">
					<?php if ( ! empty( $t['featured'] ) ) : ?><span class="mmt-card__featured"><?php esc_html_e( 'Featured', 'mine-mirth-tools' ); ?></span><?php endif; ?>
					<div class="mmt-card__icon" aria-hidden="true"><i class="<?php echo esc_attr( $icon ); ?>"></i></div>
					<h3 class="mmt-card__title"><?php echo esc_html( $t['title'] ); ?><?php echo ! empty( $t['badge'] ) ? ' <span class="mmt-card__badge">' . esc_html( $t['badge'] ) . '</span>' : ''; ?></h3>
					<?php if ( $tags ) : ?>
						<ul class="mmt-card__tags"><?php foreach ( $tags as $tag ) : ?><li><?php echo esc_html( $tag ); ?></li><?php endforeach; ?></ul>
					<?php endif; ?>
					<p class="mmt-card__desc"><?php echo esc_html( (string) ( $t['desc'] ?? '' ) ); ?></p>
					<?php if ( $soon ) : ?>
						<span class="mmt-card__btn is-soon" aria-disabled="true"><?php echo esc_html( $s['soon'] ); ?> <i class="fa-solid fa-clock" aria-hidden="true"></i></span>
					<?php else : ?>
						<a class="mmt-card__btn" href="<?php echo esc_url( (string) $t['url'] ); ?>"><?php echo esc_html( $s['button'] ); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'minemirth_tools', 'mmt_shortcode' );

/* ---------------------------------------------------------------------------
 * Settings › Tools Showcase
 * ------------------------------------------------------------------------- */

add_action(
	'admin_menu',
	static function () {
		add_options_page( __( 'Tools Showcase', 'mine-mirth-tools' ), __( 'Tools Showcase', 'mine-mirth-tools' ), 'manage_options', 'mmt-tools', 'mmt_admin_page' );
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=mmt-tools' ) ) . '">' . esc_html__( 'Settings', 'mine-mirth-tools' ) . '</a>' );
		return $links;
	}
);

add_action(
	'admin_init',
	static function () {
		register_setting( 'mmt_settings', 'mmt_settings', array( 'sanitize_callback' => 'mmt_sanitize' ) );
	}
);

function mmt_sanitize( $in ): array {
	$in  = is_array( $in ) ? $in : array();
	$out = array(
		'heading'   => sanitize_text_field( $in['heading'] ?? '' ),
		'intro'     => sanitize_text_field( $in['intro'] ?? '' ),
		'subtitle'  => sanitize_text_field( $in['subtitle'] ?? '' ),
		'show_tabs' => empty( $in['show_tabs'] ) ? 0 : 1,
		'button'    => sanitize_text_field( $in['button'] ?? '' ) ?: 'Use Tool',
		'soon'      => sanitize_text_field( $in['soon'] ?? '' ) ?: 'Coming Soon',
		'tools'     => array(),
	);
	foreach ( (array) ( $in['tools'] ?? array() ) as $t ) {
		if ( ! is_array( $t ) || '' === trim( (string) ( $t['title'] ?? '' ) ) ) {
			continue;
		}
		$out['tools'][] = array(
			'title'    => sanitize_text_field( $t['title'] ),
			'desc'     => sanitize_textarea_field( $t['desc'] ?? '' ),
			'icon'     => sanitize_text_field( $t['icon'] ?? '' ),
			'style'    => isset( mmt_styles()[ $t['style'] ?? '' ] ) ? $t['style'] : 'blue',
			'tags'     => sanitize_text_field( $t['tags'] ?? '' ),
			'badge'    => sanitize_text_field( $t['badge'] ?? '' ),
			'url'      => esc_url_raw( trim( (string) ( $t['url'] ?? '' ) ) ),
			'status'   => 'soon' === ( $t['status'] ?? '' ) ? 'soon' : 'available',
			'featured' => empty( $t['featured'] ) ? 0 : 1,
		);
	}
	return $out;
}

function mmt_tool_row( $i, array $t ): void {
	$n = 'mmt_settings[tools][' . $i . ']';
	?>
	<div class="mmt-row">
		<div class="mmt-row__bar">
			<span class="dashicons dashicons-menu mmt-row__handle" aria-hidden="true"></span>
			<strong class="mmt-row__name"><?php echo esc_html( $t['title'] ?? '' ); ?></strong>
			<span class="mmt-row__state"><?php echo 'soon' === ( $t['status'] ?? '' ) ? esc_html__( 'Coming soon', 'mine-mirth-tools' ) : esc_html__( 'Available', 'mine-mirth-tools' ); ?></span>
			<button type="button" class="button-link mmt-up" aria-label="<?php esc_attr_e( 'Move up', 'mine-mirth-tools' ); ?>">↑</button>
			<button type="button" class="button-link mmt-down" aria-label="<?php esc_attr_e( 'Move down', 'mine-mirth-tools' ); ?>">↓</button>
			<button type="button" class="button-link mmt-toggle"><?php esc_html_e( 'Edit', 'mine-mirth-tools' ); ?></button>
			<button type="button" class="button-link button-link-delete mmt-remove"><?php esc_html_e( 'Remove', 'mine-mirth-tools' ); ?></button>
		</div>
		<div class="mmt-row__body">
			<p class="mmt-wide"><label><?php esc_html_e( 'Tool name', 'mine-mirth-tools' ); ?><input type="text" class="widefat mmt-title" name="<?php echo esc_attr( $n ); ?>[title]" value="<?php echo esc_attr( $t['title'] ?? '' ); ?>"></label></p>
			<p class="mmt-wide"><label><?php esc_html_e( 'Page link', 'mine-mirth-tools' ); ?><input type="url" class="widefat" name="<?php echo esc_attr( $n ); ?>[url]" value="<?php echo esc_attr( $t['url'] ?? '' ); ?>" placeholder="https://minemirth.com/free-calculators/…"></label></p>
			<p><label><?php esc_html_e( 'Status', 'mine-mirth-tools' ); ?>
				<select name="<?php echo esc_attr( $n ); ?>[status]" class="mmt-status">
					<option value="available" <?php selected( $t['status'] ?? '', 'available' ); ?>><?php esc_html_e( 'Available now', 'mine-mirth-tools' ); ?></option>
					<option value="soon" <?php selected( $t['status'] ?? '', 'soon' ); ?>><?php esc_html_e( 'Coming soon', 'mine-mirth-tools' ); ?></option>
				</select></label></p>
			<p><label><?php esc_html_e( 'Colour', 'mine-mirth-tools' ); ?>
				<select name="<?php echo esc_attr( $n ); ?>[style]">
					<?php foreach ( mmt_styles() as $k => $label ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $t['style'] ?? 'blue', $k ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select></label></p>
			<p><label><?php esc_html_e( 'Icon', 'mine-mirth-tools' ); ?><input type="text" name="<?php echo esc_attr( $n ); ?>[icon]" value="<?php echo esc_attr( $t['icon'] ?? '' ); ?>" placeholder="fa-calculator"></label>
				<span class="description"><?php echo wp_kses( __( 'Any free name from <a href="https://fontawesome.com/search?o=r&m=free&s=solid" target="_blank" rel="noopener">fontawesome.com</a>, e.g. fa-bolt', 'mine-mirth-tools' ), array( 'a' => array( 'href' => true, 'target' => true, 'rel' => true ) ) ); ?></span></p>
			<p><label><?php esc_html_e( 'Badge next to the name', 'mine-mirth-tools' ); ?><input type="text" name="<?php echo esc_attr( $n ); ?>[badge]" value="<?php echo esc_attr( $t['badge'] ?? '' ); ?>" placeholder="AI-Powered"></label></p>
			<p class="mmt-wide"><label><?php esc_html_e( 'Tags (comma separated)', 'mine-mirth-tools' ); ?><input type="text" class="widefat" name="<?php echo esc_attr( $n ); ?>[tags]" value="<?php echo esc_attr( $t['tags'] ?? '' ); ?>"></label></p>
			<p class="mmt-wide"><label><?php esc_html_e( 'Description', 'mine-mirth-tools' ); ?><textarea class="widefat" rows="3" name="<?php echo esc_attr( $n ); ?>[desc]"><?php echo esc_textarea( $t['desc'] ?? '' ); ?></textarea></label></p>
			<p><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[featured]" value="1" <?php checked( ! empty( $t['featured'] ) ); ?>> <?php esc_html_e( 'Show "Featured" ribbon', 'mine-mirth-tools' ); ?></label></p>
		</div>
	</div>
	<?php
}

function mmt_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s = mmt_settings();
	?>
	<div class="wrap mmt-admin">
		<h1><?php esc_html_e( 'Tools Showcase', 'mine-mirth-tools' ); ?></h1>
		<p><?php echo wp_kses( __( 'Put this shortcode on any page: <code>[minemirth_tools]</code>. Options: <code>[minemirth_tools show="available"]</code> shows only live tools, <code>show="soon"</code> only upcoming ones, <code>tabs="no"</code> hides the filter, <code>heading="no"</code> hides the title.', 'mine-mirth-tools' ), array( 'code' => array() ) ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'mmt_settings' ); ?>
			<h2><?php esc_html_e( 'Section heading', 'mine-mirth-tools' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="mmt-h"><?php esc_html_e( 'Title', 'mine-mirth-tools' ); ?></label></th><td><input id="mmt-h" class="regular-text" name="mmt_settings[heading]" value="<?php echo esc_attr( $s['heading'] ); ?>"></td></tr>
				<tr><th><label for="mmt-i"><?php esc_html_e( 'Intro', 'mine-mirth-tools' ); ?></label></th><td><input id="mmt-i" class="large-text" name="mmt_settings[intro]" value="<?php echo esc_attr( $s['intro'] ); ?>"></td></tr>
				<tr><th><label for="mmt-s"><?php esc_html_e( 'Second line', 'mine-mirth-tools' ); ?></label></th><td><input id="mmt-s" class="large-text" name="mmt_settings[subtitle]" value="<?php echo esc_attr( $s['subtitle'] ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Buttons', 'mine-mirth-tools' ); ?></th><td>
					<label><?php esc_html_e( 'Live tool', 'mine-mirth-tools' ); ?> <input name="mmt_settings[button]" value="<?php echo esc_attr( $s['button'] ); ?>"></label>
					&nbsp; <label><?php esc_html_e( 'Upcoming tool', 'mine-mirth-tools' ); ?> <input name="mmt_settings[soon]" value="<?php echo esc_attr( $s['soon'] ); ?>"></label></td></tr>
				<tr><th><?php esc_html_e( 'Filter tabs', 'mine-mirth-tools' ); ?></th><td><label><input type="checkbox" name="mmt_settings[show_tabs]" value="1" <?php checked( (int) $s['show_tabs'], 1 ); ?>> <?php esc_html_e( 'Show "All / Available Now / Coming Soon" tabs', 'mine-mirth-tools' ); ?></label></td></tr>
			</table>

			<h2><?php esc_html_e( 'Tools', 'mine-mirth-tools' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Shown in this order. When a tool goes live, set its status to "Available now" and paste its page link. A tool without a link always shows as coming soon.', 'mine-mirth-tools' ); ?></p>
			<div id="mmt-rows">
				<?php
				foreach ( array_values( (array) $s['tools'] ) as $i => $t ) {
					mmt_tool_row( $i, $t );
				}
				?>
			</div>
			<p><button type="button" class="button" id="mmt-add"><?php esc_html_e( '+ Add a tool', 'mine-mirth-tools' ); ?></button></p>
			<script type="text/template" id="mmt-tpl"><?php mmt_tool_row( '__i__', array( 'status' => 'available', 'style' => 'blue', 'icon' => 'fa-calculator' ) ); ?></script>
			<?php submit_button(); ?>
		</form>
	</div>
	<style>
		.mmt-row{background:#fff;border:1px solid #dcdcde;border-radius:8px;margin:0 0 8px;max-width:1000px}
		.mmt-row__bar{display:flex;align-items:center;gap:10px;padding:10px 14px}
		.mmt-row__name{flex:1}
		.mmt-row__state{font-size:12px;color:#646970;background:#f0f0f1;border-radius:10px;padding:1px 8px}
		.mmt-row__handle{color:#a7aaad}
		.mmt-row__body{display:none;grid-template-columns:repeat(3,minmax(0,1fr));gap:4px 16px;padding:4px 14px 14px;border-top:1px solid #f0f0f1}
		.mmt-row.is-open .mmt-row__body{display:grid}
		.mmt-row__body .mmt-wide{grid-column:1/-1}
		.mmt-row__body label{display:block;font-weight:600}
		.mmt-row__body label input[type=text],.mmt-row__body label input[type=url],.mmt-row__body label select,.mmt-row__body textarea{display:block;width:100%;margin-top:4px;font-weight:400}
		.mmt-row__body label input[type=checkbox]{margin-top:0}
		@media (max-width:782px){.mmt-row__body{grid-template-columns:1fr}}
	</style>
	<script>
	(function () {
		var rows = document.getElementById('mmt-rows'), tpl = document.getElementById('mmt-tpl').innerHTML, next = rows.children.length + 1000;
		function renumber() {
			Array.prototype.forEach.call(rows.children, function (row, i) {
				row.querySelectorAll('[name^="mmt_settings[tools]"]').forEach(function (f) { f.name = f.name.replace(/mmt_settings\[tools\]\[[^\]]+\]/, 'mmt_settings[tools][' + i + ']'); });
			});
		}
		rows.addEventListener('click', function (e) {
			var row = e.target.closest('.mmt-row');
			if (!row) { return; }
			if (e.target.closest('.mmt-toggle') || e.target.closest('.mmt-row__name')) { row.classList.toggle('is-open'); }
			if (e.target.closest('.mmt-remove') && window.confirm('Remove this tool?')) { row.remove(); renumber(); }
			if (e.target.closest('.mmt-up') && row.previousElementSibling) { rows.insertBefore(row, row.previousElementSibling); renumber(); }
			if (e.target.closest('.mmt-down') && row.nextElementSibling) { rows.insertBefore(row.nextElementSibling, row); renumber(); }
		});
		rows.addEventListener('input', function (e) {
			var row = e.target.closest('.mmt-row');
			if (e.target.classList.contains('mmt-title')) { row.querySelector('.mmt-row__name').textContent = e.target.value; }
		});
		rows.addEventListener('change', function (e) {
			if (e.target.classList.contains('mmt-status')) { e.target.closest('.mmt-row').querySelector('.mmt-row__state').textContent = e.target.options[e.target.selectedIndex].text; }
		});
		document.getElementById('mmt-add').addEventListener('click', function () {
			var wrap = document.createElement('div');
			wrap.innerHTML = tpl.replace(/__i__/g, String(next++)).trim();
			var row = wrap.firstChild;
			row.classList.add('is-open');
			rows.appendChild(row);
			renumber();
			row.querySelector('.mmt-title').focus();
		});
	})();
	</script>
	<?php
}
