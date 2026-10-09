<?php
/**
 * Hosting promo section.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_points = mm_lines( mm_opt( 'hosting_points' ) );
$mm_plans  = mm_opt( 'hosting_show_plans' ) ? mm_get_hosting_plans( 3 ) : array();
$mm_image  = (int) mm_opt( 'hosting_image' );
?>
<section id="mm-section-hosting" class="mm-section mm-section--hosting">
	<div class="mm-container">
		<div class="mm-split">
			<div class="mm-split__copy">
				<?php mm_section_heading( 'hosting' ); ?>
				<?php if ( $mm_points ) : ?>
					<ul class="mm-checklist" data-mm-reveal>
						<?php foreach ( $mm_points as $mm_point ) : ?>
							<li><?php echo mm_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $mm_point ); ?></span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<div class="mm-btn-row" data-mm-reveal>
					<?php
					echo mm_button( mm_opt( 'hosting_btn1_text' ), mm_opt( 'hosting_btn1_url' ), 'primary', array( 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo mm_button( mm_opt( 'hosting_btn2_text' ), mm_opt( 'hosting_btn2_url' ), 'ghost' ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</div>
			</div>

			<div class="mm-split__visual" data-mm-reveal>
				<?php if ( $mm_image ) : ?>
					<div class="mm-media-frame"><?php echo mm_image( $mm_image, 'large', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<?php else : ?>
					<div class="mm-rack" aria-hidden="true">
						<div class="mm-rack__head">
							<span><span class="mm-live-dot"></span><?php esc_html_e( 'Facility', 'minemirth' ); ?></span>
							<span class="mm-rack__temp"><?php echo mm_icon( 'fan' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Airflow', 'minemirth' ); ?></span>
						</div>
						<div class="mm-rack__rows">
							<?php for ( $mm_r = 0; $mm_r < 6; $mm_r++ ) : ?>
								<div class="mm-rack__row" style="--r:<?php echo esc_attr( (string) $mm_r ); ?>">
									<?php for ( $mm_u = 0; $mm_u < 4; $mm_u++ ) : ?>
										<span class="mm-rack__unit" style="--u:<?php echo esc_attr( (string) ( $mm_u + $mm_r * 4 ) ); ?>"><i></i><b></b></span>
									<?php endfor; ?>
								</div>
							<?php endfor; ?>
						</div>
						<div class="mm-rack__air"><i></i><i></i><i></i><i></i></div>
					</div>
				<?php endif; ?>

				<?php if ( $mm_plans ) : ?>
					<div class="mm-plan-mini-list">
						<?php foreach ( $mm_plans as $mm_plan ) : ?>
							<a class="mm-plan-mini" href="<?php echo esc_url( mm_url( mm_opt( 'hosting_btn1_url' ) ) ); ?>#hosting-plans">
								<span class="mm-avail mm-avail--<?php echo esc_attr( $mm_plan['availability'] ); ?>"><?php echo esc_html( $mm_plan['availability_label'] ); ?></span>
								<strong><?php echo esc_html( $mm_plan['title'] ); ?></strong>
								<?php if ( $mm_plan['rate'] ) : ?>
									<span class="mm-plan-mini__rate"><?php echo esc_html( $mm_plan['rate'] ); ?></span>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
