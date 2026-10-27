<?php
/**
 * Custom Customizer controls.
 *
 * Loaded only inside customize_register.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/**
 * On/off switch.
 */
class MM_Customize_Toggle_Control extends WP_Customize_Control {
	public $type = 'mm-toggle';

	public function render_content() {
		$id = 'mm-toggle-' . $this->id;
		?>
		<div class="mm-ctrl-toggle">
			<label for="<?php echo esc_attr( $id ); ?>" class="mm-ctrl-toggle__label">
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<?php if ( $this->description ) : ?>
					<span class="description customize-control-description"><?php echo wp_kses_post( $this->description ); ?></span>
				<?php endif; ?>
			</label>
			<span class="mm-ctrl-switch">
				<input id="<?php echo esc_attr( $id ); ?>" type="checkbox" value="1" <?php $this->link(); ?> <?php checked( (bool) $this->value() ); ?>>
				<span class="mm-ctrl-switch__ui" aria-hidden="true"></span>
			</span>
		</div>
		<?php
	}
}

/**
 * Slider with numeric readout.
 */
class MM_Customize_Range_Control extends WP_Customize_Control {
	public $type = 'mm-range';

	/**
	 * Unit label.
	 *
	 * @var string
	 */
	public $unit = '';

	public function render_content() {
		$attrs = wp_parse_args(
			$this->input_attrs,
			array(
				'min'  => 0,
				'max'  => 100,
				'step' => 1,
			)
		);
		?>
		<div class="mm-ctrl-range">
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php if ( $this->description ) : ?>
				<span class="description customize-control-description"><?php echo wp_kses_post( $this->description ); ?></span>
			<?php endif; ?>
			<div class="mm-ctrl-range__row">
				<input type="range" min="<?php echo esc_attr( (string) $attrs['min'] ); ?>" max="<?php echo esc_attr( (string) $attrs['max'] ); ?>" step="<?php echo esc_attr( (string) $attrs['step'] ); ?>" value="<?php echo esc_attr( (string) $this->value() ); ?>" <?php $this->link(); ?>>
				<span class="mm-ctrl-range__value"><input type="number" min="<?php echo esc_attr( (string) $attrs['min'] ); ?>" max="<?php echo esc_attr( (string) $attrs['max'] ); ?>" step="<?php echo esc_attr( (string) $attrs['step'] ); ?>" value="<?php echo esc_attr( (string) $this->value() ); ?>" aria-label="<?php echo esc_attr( $this->label ); ?>"><?php if ( $this->unit ) : ?><em><?php echo esc_html( $this->unit ); ?></em><?php endif; ?></span>
				<button type="button" class="mm-ctrl-range__reset" data-default="<?php echo esc_attr( (string) $this->setting->default ); ?>" title="<?php esc_attr_e( 'Reset', 'minemirth' ); ?>"><span class="dashicons dashicons-image-rotate"></span></button>
			</div>
		</div>
		<?php
	}
}

/**
 * Heading / divider (no setting).
 */
class MM_Customize_Heading_Control extends WP_Customize_Control {
	public $type = 'mm-heading';

	public function render_content() {
		?>
		<div class="mm-ctrl-heading">
			<h3><?php echo esc_html( $this->label ); ?></h3>
			<?php if ( $this->description ) : ?>
				<p><?php echo wp_kses_post( $this->description ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}

/**
 * Drag-and-drop ordering with show/hide checkboxes.
 */
class MM_Customize_Sortable_Control extends WP_Customize_Control {
	public $type = 'mm-sortable';

	public function render_content() {
		$state = array();
		foreach ( explode( ',', (string) $this->value() ) as $item ) {
			$item = trim( $item );
			$id   = ltrim( $item, '-' );
			if ( isset( $this->choices[ $id ] ) ) {
				$state[ $id ] = '-' !== substr( $item, 0, 1 );
			}
		}
		foreach ( array_keys( $this->choices ) as $id ) {
			if ( ! isset( $state[ $id ] ) ) {
				$state[ $id ] = false;
			}
		}
		?>
		<div class="mm-ctrl-sortable">
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php if ( $this->description ) : ?>
				<span class="description customize-control-description"><?php echo wp_kses_post( $this->description ); ?></span>
			<?php endif; ?>
			<input type="hidden" value="<?php echo esc_attr( (string) $this->value() ); ?>" <?php $this->link(); ?>>
			<ul class="mm-ctrl-sortable__list">
				<?php foreach ( $state as $id => $enabled ) : ?>
					<li class="mm-ctrl-sortable__item<?php echo $enabled ? '' : ' is-off'; ?>" data-id="<?php echo esc_attr( $id ); ?>">
						<span class="mm-ctrl-sortable__handle dashicons dashicons-menu" aria-hidden="true"></span>
						<label class="mm-ctrl-sortable__check"><input type="checkbox" <?php checked( $enabled ); ?> aria-label="<?php echo esc_attr( sprintf( /* translators: %s: section */ __( 'Show %s', 'minemirth' ), $this->choices[ $id ] ) ); ?>"></label>
						<button type="button" class="mm-ctrl-sortable__name" data-section="mm_home_<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $this->choices[ $id ] ); ?><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></button>
						<span class="mm-ctrl-sortable__moves">
							<button type="button" class="button-link" data-move="up" aria-label="<?php esc_attr_e( 'Move up', 'minemirth' ); ?>"><span class="dashicons dashicons-arrow-up-alt2"></span></button>
							<button type="button" class="button-link" data-move="down" aria-label="<?php esc_attr_e( 'Move down', 'minemirth' ); ?>"><span class="dashicons dashicons-arrow-down-alt2"></span></button>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}

/**
 * One-click color presets.
 */
class MM_Customize_Presets_Control extends WP_Customize_Control {
	public $type = 'mm-presets';

	public function render_content() {
		?>
		<div class="mm-ctrl-presets">
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php if ( $this->description ) : ?>
				<span class="description customize-control-description"><?php echo wp_kses_post( $this->description ); ?></span>
			<?php endif; ?>
			<div class="mm-ctrl-presets__grid">
				<?php foreach ( mm_style_presets() as $key => $preset ) : ?>
					<?php $s = $preset['settings']; ?>
					<button type="button" class="mm-ctrl-preset" data-preset="<?php echo esc_attr( wp_json_encode( $s ) ); ?>">
						<span class="mm-ctrl-preset__swatch">
							<span style="background:<?php echo esc_attr( $s['light_bg'] ); ?>"></span>
							<span style="background:<?php echo esc_attr( $s['dark_bg'] ); ?>"></span>
							<span style="background:<?php echo esc_attr( $s['color_primary'] ); ?>"></span>
							<span style="background:<?php echo esc_attr( $s['color_accent'] ); ?>"></span>
							<span style="background:<?php echo esc_attr( $s['color_accent_2'] ); ?>"></span>
						</span>
						<span class="mm-ctrl-preset__name"><?php echo esc_html( $preset['label'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
