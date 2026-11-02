<?php
/**
 * One hosting listing on its own page: images, price, subscribe, specs, full description, related listings.
 *
 * @package MineMirthHostingListings
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$l = mmhl_listing( get_the_ID() );
	if ( ! $l ) {
		continue;
	}
	$p        = $l['plan'];
	$images   = array_values( array_unique( array_filter( array_merge( array( $l['image_id'] ), $l['gallery'] ) ) ) );
	$contact  = $l['cta_url'] ? $l['cta_url'] : mmhl_contact_url();
	$details  = array_filter(
		array(
			__( 'Location', 'mine-mirth-hosting-listings' )           => $l['region'],
			__( 'Availability', 'mine-mirth-hosting-listings' )       => $l['avail_label'],
			__( 'Hosting rate', 'mine-mirth-hosting-listings' )       => $l['rate'],
			__( 'Billing', 'mine-mirth-hosting-listings' )            => $l['billing'],
			__( 'Minimum', 'mine-mirth-hosting-listings' )            => $l['minimum'],
			__( 'Cooling / hardware', 'mine-mirth-hosting-listings' ) => $l['cooling'],
			__( 'Setup', 'mine-mirth-hosting-listings' )              => $p && $p['setup_fee'] > 0 ? mms_money( (int) $p['setup_fee'], (string) $p['currency'] ) . ' ' . __( 'one-time', 'mine-mirth-hosting-listings' ) : $l['setup'],
			__( 'Term', 'mine-mirth-hosting-listings' )               => $l['term'],
		)
	);
	$miner    = $l['miner'] && function_exists( 'wc_get_product' ) ? wc_get_product( $l['miner'] ) : null;
	?>
	<main id="mm-main" class="mm-main mmhl-main mmhl-single" tabindex="-1">
		<div class="mm-container">
			<?php echo mmhl_breadcrumbs( $l['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

			<div class="mmhl-product">
				<div class="mmhl-gallery" data-mmhl-gallery>
					<figure class="mmhl-gallery__main">
						<?php if ( $images ) : ?>
							<?php echo wp_get_attachment_image( $images[0], 'large', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'alt' => esc_attr( $l['title'] ), 'data-mmhl-main' => '1', 'sizes' => '(max-width: 900px) 100vw, 640px' ) ); ?>
						<?php else : ?>
							<span class="mmhl-card__ph" aria-hidden="true"><?php echo mmhl_icon( 'server' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<?php endif; ?>
					</figure>
					<?php if ( count( $images ) > 1 ) : ?>
						<div class="mmhl-gallery__thumbs">
							<?php foreach ( $images as $i => $img ) : ?>
								<button type="button" class="<?php echo 0 === $i ? 'is-active' : ''; ?>" data-full="<?php echo esc_url( (string) wp_get_attachment_image_url( $img, 'large' ) ); ?>" data-srcset="<?php echo esc_attr( (string) wp_get_attachment_image_srcset( $img, 'large' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: image number */ __( 'Show image %d', 'mine-mirth-hosting-listings' ), $i + 1 ) ); ?>">
									<?php echo wp_get_attachment_image( $img, 'thumbnail', false, array( 'alt' => '' ) ); ?>
								</button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="mmhl-summary">
					<div class="mmhl-summary__top">
						<span class="mm-avail mm-avail--<?php echo esc_attr( $l['availability'] ); ?>"><?php echo esc_html( $l['avail_label'] ); ?></span>
						<?php if ( $l['region'] ) : ?>
							<span class="mmhl-card__region"><?php echo mmhl_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $l['region'] ); ?></span>
						<?php endif; ?>
					</div>
					<h1 class="mmhl-title"><?php echo esc_html( $l['title'] ); ?></h1>
					<?php if ( has_excerpt() ) : ?>
						<p class="mmhl-lead"><?php echo esc_html( $l['summary'] ); ?></p>
					<?php endif; ?>

					<div class="mmhl-price">
						<?php if ( '' !== $l['price'] ) : ?>
							<strong><?php echo esc_html( $l['price'] ); ?></strong>
							<?php if ( $l['price_note'] ) : ?>
								<span><?php echo esc_html( $l['price_note'] ); ?></span>
							<?php endif; ?>
						<?php else : ?>
							<strong class="is-ask"><?php esc_html_e( 'Price on request', 'mine-mirth-hosting-listings' ); ?></strong>
						<?php endif; ?>
					</div>
					<?php if ( $l['rate'] ) : ?>
						<p class="mmhl-rate"><?php echo mmhl_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong><?php echo esc_html( $l['rate'] ); ?></strong><?php echo $l['billing'] ? '<span>' . esc_html( $l['billing'] ) . '</span>' : ''; ?></p>
					<?php endif; ?>

					<?php if ( $l['specs'] ) : ?>
						<dl class="mmhl-specs">
							<?php foreach ( array_slice( $l['specs'], 0, 6 ) as $s ) : ?>
								<div><dt><?php echo esc_html( '' !== $s[0] ? $s[0] : __( 'Detail', 'mine-mirth-hosting-listings' ) ); ?></dt><dd><?php echo esc_html( $s[1] ); ?></dd></div>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>

					<div class="mmhl-buy">
						<?php if ( $l['subscribable'] ) : ?>
							<form method="post" action="<?php echo esc_url( $l['url'] ); ?>" class="mms-subscribe mmhl-subscribe">
								<input type="hidden" name="mms_subscribe" value="1">
								<input type="hidden" name="mms_plan" value="<?php echo (int) $l['id']; ?>">
								<input type="text" name="mms_website" value="" tabindex="-1" autocomplete="off" class="mms-hp" aria-hidden="true">
								<?php if ( $p['qty'] ) : ?>
									<div class="mms-qty" data-mms-qty data-unit="<?php echo (int) $p['amount']; ?>" data-cur="<?php echo esc_attr( (string) $p['currency'] ); ?>">
										<span class="mms-qty__label"><?php echo esc_html( ucfirst( (string) $p['qty_label'] ) ); ?></span>
										<span class="mms-qty__ctl">
											<button type="button" data-step="-1" aria-label="<?php esc_attr_e( 'Fewer', 'mine-mirth-hosting-listings' ); ?>">−</button>
											<input type="number" name="mms_qty" value="1" min="1" max="<?php echo (int) $p['qty_max']; ?>" inputmode="numeric" aria-label="<?php echo esc_attr( ucfirst( (string) $p['qty_label'] ) ); ?>">
											<button type="button" data-step="1" aria-label="<?php esc_attr_e( 'More', 'mine-mirth-hosting-listings' ); ?>">+</button>
										</span>
										<span class="mms-qty__total" data-mms-total><?php echo esc_html( $l['price'] ); ?></span>
									</div>
								<?php endif; ?>
								<button type="submit" class="mm-btn mm-btn--primary mm-btn--block mm-btn--lg">
									<span><?php echo esc_html( (string) $p['button'] ); ?></span><?php echo mmhl_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</button>
								<?php if ( $p['setup_fee'] > 0 || $p['trial_days'] > 0 ) : ?>
									<p class="mmhl-buy__note">
										<?php
										$notes = array();
										if ( $p['trial_days'] > 0 ) {
											/* translators: %d: days */
											$notes[] = sprintf( _n( '%d-day free trial.', '%d-day free trial.', (int) $p['trial_days'], 'mine-mirth-hosting-listings' ), (int) $p['trial_days'] );
										}
										if ( $p['setup_fee'] > 0 ) {
											/* translators: %s: amount */
											$notes[] = sprintf( __( 'Plus a one-time %s setup fee on the first bill.', 'mine-mirth-hosting-listings' ), mms_money( (int) $p['setup_fee'], (string) $p['currency'] ) );
										}
										echo esc_html( implode( ' ', $notes ) );
										?>
									</p>
								<?php endif; ?>
								<p class="mms-secure"><?php esc_html_e( 'Secure checkout by Stripe. Cancel any time.', 'mine-mirth-hosting-listings' ); ?></p>
							</form>
						<?php elseif ( 'unavailable' !== $l['availability'] ) : ?>
							<a class="mm-btn mm-btn--primary mm-btn--block mm-btn--lg" href="<?php echo esc_url( mmhl_url( $contact ) ); ?>"><span><?php esc_html_e( 'Request availability', 'mine-mirth-hosting-listings' ); ?></span><?php echo mmhl_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
						<?php else : ?>
							<span class="mm-btn mm-btn--ghost mm-btn--block mm-btn--lg" aria-disabled="true"><?php echo esc_html( $l['avail_label'] ); ?></span>
							<a class="mmhl-link" href="<?php echo esc_url( mmhl_url( $contact ) ); ?>"><?php esc_html_e( 'Ask to join the waiting list', 'mine-mirth-hosting-listings' ); ?></a>
						<?php endif; ?>
					</div>

					<?php if ( mmhl_opt( 'help_label' ) ) : ?>
						<p class="mmhl-help"><?php echo mmhl_icon( 'message' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( (string) mmhl_opt( 'help_text' ) ); ?></span> <a href="<?php echo esc_url( mmhl_contact_url() ); ?>"><?php echo esc_html( (string) mmhl_opt( 'help_label' ) ); ?></a></p>
					<?php endif; ?>

					<?php if ( $miner && 'publish' === $miner->get_status() ) : ?>
						<a class="mm-cta-box mmhl-miner" href="<?php echo esc_url( $miner->get_permalink() ); ?>">
							<?php echo $miner->get_image_id() ? wp_get_attachment_image( $miner->get_image_id(), 'thumbnail', false, array( 'alt' => '' ) ) : mmhl_icon( 'cpu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span><strong><?php esc_html_e( 'Need the miner too?', 'mine-mirth-hosting-listings' ); ?></strong><small><?php echo esc_html( $miner->get_name() ); ?><?php echo '' !== (string) $miner->get_price() ? ' · ' . esc_html( html_entity_decode( wp_strip_all_tags( wc_price( (float) $miner->get_price() ) ), ENT_QUOTES, 'UTF-8' ) ) : ''; ?></small></span>
							<?php echo mmhl_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<div class="mmhl-details">
				<div class="mmhl-details__main">
					<?php if ( '' !== trim( $l['content'] ) ) : ?>
						<section class="mmhl-block">
							<h2><?php esc_html_e( 'Overview', 'mine-mirth-hosting-listings' ); ?></h2>
							<div class="entry-content mm-prose"><?php the_content(); ?></div>
						</section>
					<?php endif; ?>
					<?php if ( $l['features'] ) : ?>
						<section class="mmhl-block">
							<h2><?php esc_html_e( 'What’s included', 'mine-mirth-hosting-listings' ); ?></h2>
							<ul class="mmhl-features">
								<?php foreach ( $l['features'] as $f ) : ?>
									<li><?php echo mmhl_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $f ); ?></span></li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>
				</div>
				<aside class="mmhl-details__side">
					<section class="mmhl-block mmhl-facts">
						<h2><?php esc_html_e( 'Listing details', 'mine-mirth-hosting-listings' ); ?></h2>
						<dl>
							<?php foreach ( $details as $label => $value ) : ?>
								<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
							<?php endforeach; ?>
							<?php foreach ( array_slice( $l['specs'], 6 ) as $s ) : ?>
								<div><dt><?php echo esc_html( $s[0] ); ?></dt><dd><?php echo esc_html( $s[1] ); ?></dd></div>
							<?php endforeach; ?>
						</dl>
						<?php if ( $l['fine_print'] ) : ?>
							<p class="mmhl-fine"><?php echo esc_html( $l['fine_print'] ); ?></p>
						<?php endif; ?>
					</section>
				</aside>
			</div>

			<?php
			if ( mmhl_opt( 'related' ) ) :
				$mmhl_related = mmhl_grid( array( 'toolbar' => false, 'limit' => (int) mmhl_opt( 'columns' ), 'exclude' => array( $l['id'] ), 'per_page' => 0, 'empty' => false ) );
				if ( '' !== $mmhl_related ) :
					?>
					<section class="mmhl-related">
						<div class="mmhl-related__head">
							<h2><?php esc_html_e( 'Other hosting listings', 'mine-mirth-hosting-listings' ); ?></h2>
							<a class="mmhl-link" href="<?php echo esc_url( mmhl_page_url() ); ?>"><?php esc_html_e( 'See all listings', 'mine-mirth-hosting-listings' ); ?><?php echo mmhl_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
						</div>
						<?php echo $mmhl_related; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</section>
					<?php
				endif;
			endif;
			?>
		</div>
		<?php echo mmhl_schema( $l ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</main>
	<?php
endwhile;

get_footer();
