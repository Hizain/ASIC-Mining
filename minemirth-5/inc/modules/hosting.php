<?php
/**
 * Miner hosting plans.
 *
 * Uses the same post type (mm_hosting_plan), meta keys (_mm_*) and
 * shortcodes as the previous Mine Mirth theme, so existing plans carry over.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

function mm_register_hosting_post_type(): void {
	register_post_type(
		'mm_hosting_plan',
		array(
			'labels'          => array(
				'name'          => __( 'Hosting Plans', 'minemirth' ),
				'singular_name' => __( 'Hosting Plan', 'minemirth' ),
				'add_new_item'  => __( 'Add hosting plan', 'minemirth' ),
				'edit_item'     => __( 'Edit hosting plan', 'minemirth' ),
				'menu_name'     => __( 'Hosting Plans', 'minemirth' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-database',
			'menu_position'   => 57,
			'supports'        => array( 'title', 'editor', 'page-attributes' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'mm_register_hosting_post_type' );

/**
 * Field definitions for the plan meta box.
 */
function mm_hosting_fields(): array {
	return array(
		'region'       => array( 'label' => __( 'Region / facility label', 'minemirth' ), 'type' => 'text', 'help' => __( 'Only publish a location you are authorized to share.', 'minemirth' ) ),
		'availability' => array(
			'label'   => __( 'Availability', 'minemirth' ),
			'type'    => 'select',
			'options' => mm_hosting_availability_labels(),
		),
		'rate'         => array( 'label' => __( 'Published rate', 'minemirth' ), 'type' => 'text', 'help' => __( 'Example: $0.078/kWh all-in.', 'minemirth' ) ),
		'billing'      => array( 'label' => __( 'Billing basis', 'minemirth' ), 'type' => 'text', 'help' => __( 'Example: billed monthly in advance.', 'minemirth' ) ),
		'minimum'      => array( 'label' => __( 'Minimum units / commitment', 'minemirth' ), 'type' => 'text' ),
		'cooling'      => array( 'label' => __( 'Cooling / supported miners', 'minemirth' ), 'type' => 'text' ),
		'setup'        => array( 'label' => __( 'Setup / onboarding fee', 'minemirth' ), 'type' => 'text' ),
		'term'         => array( 'label' => __( 'Contract term', 'minemirth' ), 'type' => 'text' ),
		'product_id'   => array( 'label' => __( 'WooCommerce product ID (optional)', 'minemirth' ), 'type' => 'number', 'help' => __( 'Link a standard or subscription product so customers can check out directly.', 'minemirth' ) ),
		'cta_url'      => array( 'label' => __( 'Inquiry URL', 'minemirth' ), 'type' => 'url', 'help' => __( 'Used when no purchasable product is linked.', 'minemirth' ) ),
		'featured'     => array( 'label' => __( 'Highlight this plan', 'minemirth' ), 'type' => 'checkbox' ),
		'features'     => array( 'label' => __( 'Included (one per line)', 'minemirth' ), 'type' => 'textarea', 'wide' => true ),
		'fine_print'   => array( 'label' => __( 'Plan notes / exclusions', 'minemirth' ), 'type' => 'textarea', 'wide' => true, 'help' => __( 'Taxes, deposits, repair labor, curtailment rules: publish what applies.', 'minemirth' ) ),
	);
}

function mm_hosting_availability_labels(): array {
	return array(
		'available'   => __( 'Capacity available', 'minemirth' ),
		'limited'     => __( 'Limited capacity', 'minemirth' ),
		'inquiry'     => __( 'Inquiry only', 'minemirth' ),
		'unavailable' => __( 'Fully booked', 'minemirth' ),
	);
}

function mm_hosting_add_meta_box(): void {
	add_meta_box( 'mm-hosting-details', __( 'Hosting plan details', 'minemirth' ), 'mm_hosting_meta_box', 'mm_hosting_plan', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'mm_hosting_add_meta_box' );

function mm_hosting_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'mm_hosting_save', 'mm_hosting_nonce' );
	echo '<style>.mm-admin-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.mm-admin-field label{display:block;font-weight:600;margin-bottom:5px}.mm-admin-field input:not([type=checkbox]),.mm-admin-field select,.mm-admin-field textarea{width:100%}.mm-admin-help{color:#646970;font-size:12px;margin-top:4px}.mm-admin-wide{grid-column:1/-1}@media(max-width:782px){.mm-admin-grid{grid-template-columns:1fr}}</style>';
	echo '<div class="mm-admin-grid">';
	foreach ( mm_hosting_fields() as $key => $field ) {
		$value = get_post_meta( $post->ID, '_mm_' . $key, true );
		$id    = 'mm_' . $key;
		echo '<div class="mm-admin-field' . ( ! empty( $field['wide'] ) ? ' mm-admin-wide' : '' ) . '">';
		if ( 'checkbox' === $field['type'] ) {
			echo '<label><input type="checkbox" name="' . esc_attr( $id ) . '" value="1" ' . checked( (bool) $value, true, false ) . '> ' . esc_html( $field['label'] ) . '</label>';
		} else {
			echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
			if ( 'select' === $field['type'] ) {
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '">';
				foreach ( $field['options'] as $opt => $label ) {
					echo '<option value="' . esc_attr( $opt ) . '" ' . selected( $value, $opt, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
			} elseif ( 'textarea' === $field['type'] ) {
				echo '<textarea rows="4" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '">' . esc_textarea( (string) $value ) . '</textarea>';
			} else {
				echo '<input type="' . esc_attr( $field['type'] ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( (string) $value ) . '">';
			}
		}
		if ( ! empty( $field['help'] ) ) {
			echo '<div class="mm-admin-help">' . esc_html( $field['help'] ) . '</div>';
		}
		echo '</div>';
	}
	echo '</div>';
}

function mm_hosting_save_meta( int $post_id ): void {
	if ( ! isset( $_POST['mm_hosting_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mm_hosting_nonce'] ) ), 'mm_hosting_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( mm_hosting_fields() as $key => $field ) {
		$name = 'mm_' . $key;
		if ( 'checkbox' === $field['type'] ) {
			update_post_meta( $post_id, '_mm_' . $key, empty( $_POST[ $name ] ) ? '' : '1' );
			continue;
		}
		if ( ! isset( $_POST[ $name ] ) ) {
			continue;
		}
		$raw = wp_unslash( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
		switch ( $field['type'] ) {
			case 'url':
				$value = esc_url_raw( (string) $raw );
				break;
			case 'number':
				$value = absint( $raw );
				break;
			case 'textarea':
				$value = sanitize_textarea_field( (string) $raw );
				break;
			case 'select':
				$value = array_key_exists( $raw, $field['options'] ) ? $raw : 'inquiry';
				break;
			default:
				$value = sanitize_text_field( (string) $raw );
		}
		update_post_meta( $post_id, '_mm_' . $key, $value );
	}
}
add_action( 'save_post_mm_hosting_plan', 'mm_hosting_save_meta' );

function mm_hosting_columns( array $columns ): array {
	$columns['mm_region']       = __( 'Region', 'minemirth' );
	$columns['mm_rate']         = __( 'Rate', 'minemirth' );
	$columns['mm_availability'] = __( 'Availability', 'minemirth' );
	return $columns;
}
add_filter( 'manage_mm_hosting_plan_posts_columns', 'mm_hosting_columns' );

function mm_hosting_column_content( string $column, int $post_id ): void {
	if ( 'mm_availability' === $column ) {
		$labels = mm_hosting_availability_labels();
		$value  = (string) get_post_meta( $post_id, '_mm_availability', true );
		echo esc_html( $labels[ $value ] ?? $labels['inquiry'] );
		return;
	}
	if ( in_array( $column, array( 'mm_region', 'mm_rate' ), true ) ) {
		echo esc_html( (string) get_post_meta( $post_id, '_' . $column, true ) );
	}
}
add_action( 'manage_mm_hosting_plan_posts_custom_column', 'mm_hosting_column_content', 10, 2 );

/**
 * Published plans as plain arrays.
 */
function mm_get_hosting_plans( int $limit = 12 ): array {
	$query = new WP_Query(
		array(
			'post_type'      => 'mm_hosting_plan',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'no_found_rows'  => true,
		)
	);
	$labels = mm_hosting_availability_labels();
	$plans  = array();
	foreach ( $query->posts as $post ) {
		$id    = $post->ID;
		$avail = (string) get_post_meta( $id, '_mm_availability', true );
		$avail = isset( $labels[ $avail ] ) ? $avail : 'inquiry';
		$plans[] = array(
			'id'                 => $id,
			'title'              => get_the_title( $post ),
			'content'            => $post->post_content,
			'region'             => (string) get_post_meta( $id, '_mm_region', true ),
			'availability'       => $avail,
			'availability_label' => $labels[ $avail ],
			'rate'               => (string) get_post_meta( $id, '_mm_rate', true ),
			'billing'            => (string) get_post_meta( $id, '_mm_billing', true ),
			'minimum'            => (string) get_post_meta( $id, '_mm_minimum', true ),
			'cooling'            => (string) get_post_meta( $id, '_mm_cooling', true ),
			'setup'              => (string) get_post_meta( $id, '_mm_setup', true ),
			'term'               => (string) get_post_meta( $id, '_mm_term', true ),
			'product_id'         => absint( get_post_meta( $id, '_mm_product_id', true ) ),
			'cta_url'            => (string) get_post_meta( $id, '_mm_cta_url', true ),
			'featured'           => (bool) get_post_meta( $id, '_mm_featured', true ),
			'features'           => mm_lines( get_post_meta( $id, '_mm_features', true ) ),
			'fine_print'         => (string) get_post_meta( $id, '_mm_fine_print', true ),
		);
	}
	return $plans;
}

/**
 * Plan card markup.
 */
function mm_render_hosting_plan( array $plan ): string {
	$product = $plan['product_id'] && function_exists( 'wc_get_product' ) ? wc_get_product( $plan['product_id'] ) : null;
	$facts   = array(
		__( 'Minimum', 'minemirth' )            => $plan['minimum'],
		__( 'Cooling / hardware', 'minemirth' ) => $plan['cooling'],
		__( 'Setup', 'minemirth' )              => $plan['setup'],
		__( 'Term', 'minemirth' )               => $plan['term'],
	);
	ob_start();
	?>
	<article class="mm-plan mm-card<?php echo $plan['featured'] ? ' is-featured' : ''; ?>" data-mm-reveal>
		<div class="mm-plan__top">
			<span class="mm-avail mm-avail--<?php echo esc_attr( $plan['availability'] ); ?>"><?php echo esc_html( $plan['availability_label'] ); ?></span>
			<?php if ( $plan['region'] ) : ?>
				<span class="mm-plan__region"><?php echo mm_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $plan['region'] ); ?></span>
			<?php endif; ?>
		</div>
		<h3 class="mm-plan__name"><?php echo esc_html( $plan['title'] ); ?></h3>
		<?php if ( $plan['rate'] ) : ?>
			<div class="mm-plan__rate">
				<strong><?php echo esc_html( $plan['rate'] ); ?></strong>
				<?php if ( $plan['billing'] ) : ?>
					<span><?php echo esc_html( $plan['billing'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( trim( $plan['content'] ) ) : ?>
			<div class="mm-plan__desc"><?php echo wp_kses_post( wpautop( $plan['content'] ) ); ?></div>
		<?php endif; ?>
		<?php if ( $plan['features'] ) : ?>
			<ul class="mm-plan__features">
				<?php foreach ( $plan['features'] as $feature ) : ?>
					<li><?php echo mm_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $feature ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( array_filter( $facts ) ) : ?>
			<dl class="mm-plan__facts">
				<?php foreach ( $facts as $label => $value ) : ?>
					<?php if ( $value ) : ?>
						<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
					<?php endif; ?>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
		<?php if ( $plan['fine_print'] ) : ?>
			<p class="mm-plan__fine"><?php echo esc_html( $plan['fine_print'] ); ?></p>
		<?php endif; ?>
		<div class="mm-plan__cta">
			<?php if ( $product instanceof WC_Product && $product->is_purchasable() && 'unavailable' !== $plan['availability'] ) : ?>
				<span class="mm-plan__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
				<?php echo mm_button( __( 'Review & continue', 'minemirth' ), $product->get_permalink(), $plan['featured'] ? 'primary' : 'ghost', array( 'class' => 'mm-btn--block', 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php elseif ( 'unavailable' !== $plan['availability'] ) : ?>
				<?php echo mm_button( __( 'Request availability', 'minemirth' ), $plan['cta_url'] ? $plan['cta_url'] : mm_opt( 'contact_url' ), $plan['featured'] ? 'primary' : 'ghost', array( 'class' => 'mm-btn--block', 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php else : ?>
				<span class="mm-btn mm-btn--ghost mm-btn--block" aria-disabled="true"><?php echo esc_html( $plan['availability_label'] ); ?></span>
			<?php endif; ?>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * [mm_hosting_plans] / [mine_mirth_hosting_plans]
 */
function mm_hosting_plans_shortcode( $atts = array() ): string {
	$atts  = shortcode_atts( array( 'limit' => 12 ), $atts );
	$plans = mm_get_hosting_plans( (int) $atts['limit'] );
	if ( ! $plans ) {
		$msg = current_user_can( 'edit_posts' )
			? __( 'No hosting plans are published yet. Add confirmed facility options under Hosting Plans in the admin. The theme never invents rates or capacity.', 'minemirth' )
			: __( 'Hosting capacity is being confirmed. Contact us for current availability.', 'minemirth' );
		return '<div class="mm-plans-empty">' . esc_html( $msg ) . '</div>';
	}
	$out = '<div class="mm-plans">';
	foreach ( $plans as $plan ) {
		$out .= mm_render_hosting_plan( $plan );
	}
	return $out . '</div>';
}
add_shortcode( 'mm_hosting_plans', 'mm_hosting_plans_shortcode' );
add_shortcode( 'mine_mirth_hosting_plans', 'mm_hosting_plans_shortcode' );

/**
 * Old companion plugin active? Ask to deactivate it to avoid duplicates.
 */
function mm_hosting_legacy_notice(): void {
	if ( defined( 'MM_CORE_FILE' ) && current_user_can( 'activate_plugins' ) ) {
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Mine Mirth: the old “Mine Mirth Core” plugin is still active. Everything it did is now built into the theme, so you can deactivate it. Your hosting plans are kept.', 'minemirth' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'mm_hosting_legacy_notice' );
