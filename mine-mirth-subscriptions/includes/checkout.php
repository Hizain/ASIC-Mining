<?php
/**
 * Front end: plan cards with images and Subscribe buttons on the hosting page, Stripe Checkout,
 * the thank-you message with the client number, and signed links from emails (billing portal,
 * invoice PDF).
 *
 * @package MineMirthSubscriptions
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ Signed links */

function mms_sign( string $data ): string {
	return substr( hash_hmac( 'sha256', $data, wp_salt( 'auth' ) . 'mms' ), 0, 32 );
}

/**
 * Link that opens the Stripe customer portal (update card, see invoices, cancel), valid 30 days.
 */
function mms_portal_link( string $customer ): string {
	$exp = time() + 30 * DAY_IN_SECONDS;
	return add_query_arg( array( 'c' => $customer, 'e' => $exp, 's' => mms_sign( 'portal|' . $customer . '|' . $exp ) ), rest_url( 'mine-mirth-subscriptions/v1/portal' ) );
}

/**
 * Link to download an invoice PDF, valid 90 days.
 */
function mms_invoice_link( string $invoice ): string {
	$exp = time() + 90 * DAY_IN_SECONDS;
	return add_query_arg( array( 'i' => $invoice, 'e' => $exp, 's' => mms_sign( 'invoice|' . $invoice . '|' . $exp ) ), rest_url( 'mine-mirth-subscriptions/v1/invoice' ) );
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'mine-mirth-subscriptions/v1',
			'/portal',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $r ) {
					$c   = sanitize_text_field( (string) $r->get_param( 'c' ) );
					$exp = absint( $r->get_param( 'e' ) );
					if ( '' === $c || $exp < time() || ! hash_equals( mms_sign( 'portal|' . $c . '|' . $exp ), (string) $r->get_param( 's' ) ) ) {
						return new WP_REST_Response( array( 'error' => __( 'This link has expired. Ask us for a new one.', 'mine-mirth-subscriptions' ) ), 403 );
					}
					$session = mms_stripe( 'POST', '/v1/billing_portal/sessions', array( 'customer' => $c, 'return_url' => mms_plans_page_url() ) );
					if ( is_wp_error( $session ) ) {
						mms_log( 'portal', $session->get_error_message(), $c, 'error' );
						wp_die( esc_html__( 'The billing page is not available right now. Please contact us and we will help you directly.', 'mine-mirth-subscriptions' ), '', array( 'response' => 503 ) );
					}
					wp_redirect( (string) $session['url'] ); // phpcs:ignore WordPress.Security.SafeRedirect -- Stripe's own URL.
					exit;
				},
			)
		);
		register_rest_route(
			'mine-mirth-subscriptions/v1',
			'/invoice',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $r ) {
					$i   = sanitize_text_field( (string) $r->get_param( 'i' ) );
					$exp = absint( $r->get_param( 'e' ) );
					$row = $i ? mms_get( 'invoices', 'stripe_id', $i ) : null;
					if ( ! $row || $exp < time() || ! hash_equals( mms_sign( 'invoice|' . $i . '|' . $exp ), (string) $r->get_param( 's' ) ) ) {
						return new WP_REST_Response( array( 'error' => __( 'This link has expired. Ask us for a new one.', 'mine-mirth-subscriptions' ) ), 403 );
					}
					mms_send_pdf( $row, false );
				},
			)
		);
	}
);

/* ------------------------------------------------------------------ Checkout */

/**
 * Start Stripe Checkout for a plan. Returns the Checkout URL or an error.
 */
function mms_checkout_url( int $plan_id, int $qty = 1, string $email = '', string $customer = '' ): string|WP_Error {
	$p = mms_plan( $plan_id );
	if ( ! $p || ! mms_plan_ready( $p ) ) {
		return new WP_Error( 'mms_plan', __( 'This plan can not be ordered online right now. Please contact us.', 'mine-mirth-subscriptions' ) );
	}
	$qty  = $p['qty'] ? max( 1, min( $p['qty_max'], $qty ) ) : 1;
	$page = mms_plans_page_url();
	$line = array(
		'price'    => $p['price'],
		'quantity' => $qty,
	);
	if ( $p['qty'] ) {
		$line['adjustable_quantity'] = array( 'enabled' => true, 'minimum' => 1, 'maximum' => $p['qty_max'] );
	}
	$params = array(
		'mode'                       => 'subscription',
		'line_items'                 => array( $line ),
		'success_url'                => add_query_arg( 'mms_checkout', 'success', $page ) . '&session_id={CHECKOUT_SESSION_ID}',
		'cancel_url'                 => add_query_arg( 'mms_checkout', 'cancelled', $page ) . '#hosting-plans',
		'metadata'                   => array( 'wp_plan_id' => (string) $plan_id ),
		'subscription_data'          => array(
			'metadata'    => array( 'wp_plan_id' => (string) $plan_id, 'site' => home_url( '/' ) ),
			'description' => $p['title'],
		),
		'billing_address_collection' => mms_setting( 'collect_address' ) ? 'required' : 'auto',
		'allow_promotion_codes'      => (bool) mms_setting( 'allow_promo' ),
	);
	if ( mms_setting( 'collect_phone' ) ) {
		$params['phone_number_collection'] = array( 'enabled' => true );
	}
	if ( $p['trial_days'] > 0 ) {
		$params['subscription_data']['trial_period_days'] = $p['trial_days'];
	}
	if ( $p['setup_fee'] > 0 ) {
		// A one-time line on the first invoice.
		$params['line_items'][] = array(
			'price_data' => array(
				'currency'     => $p['currency'],
				'unit_amount'  => $p['setup_fee'],
				'product_data' => array( 'name' => sprintf( /* translators: %s: plan */ __( 'Setup fee: %s', 'mine-mirth-subscriptions' ), $p['title'] ) ),
			),
			'quantity'   => 1,
		);
	}
	if ( '' !== $customer ) {
		$params['customer']        = $customer;
		$params['customer_update'] = array( 'address' => 'auto', 'name' => 'auto' );
	} elseif ( is_email( $email ) ) {
		$params['customer_email'] = $email;
	}
	$session = mms_stripe( 'POST', '/v1/checkout/sessions', $params, 'checkout-' . md5( wp_json_encode( $params ) . '|' . floor( time() / 30 ) ) );
	if ( is_wp_error( $session ) ) {
		mms_log( 'checkout', sprintf( 'Checkout for "%s" could not start: %s', $p['title'], $session->get_error_message() ), '', 'error' );
		return $session;
	}
	return (string) $session['url'];
}

/**
 * The Subscribe button posts here (works without JavaScript and with page caching).
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( empty( $_POST['mms_subscribe'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- public checkout start; Stripe handles payment.
			return;
		}
		// A hidden field bots fill in.
		if ( ! empty( $_POST['mms_website'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( mms_plans_page_url() );
			exit;
		}
		// At most 20 checkout starts per visitor per 10 minutes.
		$ip  = md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$hit = (int) get_transient( 'mms_rl_' . $ip );
		if ( $hit > 20 ) {
			wp_die( esc_html__( 'Too many attempts. Please wait a few minutes and try again.', 'mine-mirth-subscriptions' ), '', array( 'response' => 429 ) );
		}
		set_transient( 'mms_rl_' . $ip, $hit + 1, 10 * MINUTE_IN_SECONDS );

		$plan     = absint( $_POST['mms_plan'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$qty      = absint( $_POST['mms_qty'] ?? 1 ); // phpcs:ignore WordPress.Security.NonceVerification
		$user     = wp_get_current_user();
		$email    = $user && $user->exists() ? (string) $user->user_email : '';
		$customer = '';
		if ( $email ) {
			global $wpdb;
			$customer = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT stripe_id FROM ' . mms_table( 'customers' ) . ' WHERE email = %s AND mode = %s ORDER BY id DESC LIMIT 1', $email, mms_mode() ) ); // phpcs:ignore WordPress.DB
		}
		$url = mms_checkout_url( $plan, $qty, $email, $customer );
		if ( is_wp_error( $url ) ) {
			wp_safe_redirect( add_query_arg( 'mms_checkout', 'error', mms_plans_page_url() ) . '#hosting-plans' );
			exit;
		}
		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect -- Stripe Checkout URL.
		exit;
	}
);

/**
 * Store what a completed Checkout created (also called by the webhook). Returns the local subscription ID.
 */
function mms_process_checkout( string $session_id ): int {
	$s = mms_stripe( 'GET', '/v1/checkout/sessions/' . rawurlencode( $session_id ), array( 'expand' => array( 'subscription', 'customer' ) ) );
	if ( is_wp_error( $s ) || 'subscription' !== ( $s['mode'] ?? '' ) || empty( $s['subscription'] ) ) {
		return 0;
	}
	$details = (array) ( $s['customer_details'] ?? array() );
	$cust    = is_array( $s['customer'] ?? null ) ? $s['customer'] : null;
	if ( $cust ) {
		// Fill in what the customer typed at checkout when Stripe did not copy it to the customer.
		$update = array();
		if ( empty( $cust['name'] ) && ! empty( $details['name'] ) ) {
			$update['name'] = (string) $details['name'];
		}
		if ( empty( $cust['phone'] ) && ! empty( $details['phone'] ) ) {
			$update['phone'] = (string) $details['phone'];
		}
		if ( $update ) {
			$res  = mms_stripe( 'POST', '/v1/customers/' . rawurlencode( (string) $cust['id'] ), $update );
			$cust = is_wp_error( $res ) ? array_merge( $cust, $update ) : $res;
		}
		mms_sync_customer( $cust );
	}
	$sub = is_array( $s['subscription'] ) ? $s['subscription'] : mms_stripe( 'GET', '/v1/subscriptions/' . rawurlencode( (string) $s['subscription'] ) );
	if ( is_wp_error( $sub ) ) {
		return 0;
	}
	if ( empty( $sub['metadata']['wp_plan_id'] ) && ! empty( $s['metadata']['wp_plan_id'] ) ) {
		$sub['metadata']['wp_plan_id'] = $s['metadata']['wp_plan_id'];
	}
	$id = mms_sync_subscription( $sub );
	// The first invoice, so it shows at once (the webhook updates it as well).
	$inv = mms_id( $sub['latest_invoice'] ?? '' );
	if ( '' !== $inv && ! mms_get( 'invoices', 'stripe_id', $inv ) ) {
		$i = mms_stripe( 'GET', '/v1/invoices/' . rawurlencode( $inv ) );
		if ( ! is_wp_error( $i ) ) {
			mms_sync_invoice( $i );
		}
	}
	return $id;
}

/* ------------------------------------------------------------------ Plan cards */

/**
 * Plan cards: image, price, what is included and a Subscribe button.
 */
function mms_render_plans( array $atts = array() ): string {
	$plans = mms_plans();
	$only  = ! empty( $atts['only_subscribable'] );
	if ( $only ) {
		$plans = array_values( array_filter( $plans, 'mms_plan_ready' ) );
	}
	if ( ! $plans ) {
		$msg = current_user_can( 'edit_posts' )
			? __( 'No hosting plans are published yet. Add them under Hosting Plans: give each an image and a price in the "Stripe subscription" box.', 'mine-mirth-subscriptions' )
			: __( 'Hosting capacity is being confirmed. Contact us for current availability.', 'mine-mirth-subscriptions' );
		return '<div class="mm-plans-empty mms-empty">' . esc_html( $msg ) . '</div>';
	}
	wp_enqueue_style( 'mms-front' );
	wp_enqueue_script( 'mms-front' );
	$avail_labels = function_exists( 'mm_hosting_availability_labels' ) ? mm_hosting_availability_labels() : array();
	$contact      = function_exists( 'mm_opt' ) ? (string) mm_opt( 'contact_url' ) : home_url( '/contact/' );
	$out          = '<div class="mm-plans mms-plans">';
	foreach ( $plans as $p ) {
		$ready = mms_plan_ready( $p );
		$facts = array_filter(
			array(
				__( 'Minimum', 'mine-mirth-subscriptions' )            => $p['minimum'],
				__( 'Cooling / hardware', 'mine-mirth-subscriptions' ) => $p['cooling'],
				__( 'Setup', 'mine-mirth-subscriptions' )              => $p['setup_fee'] > 0 ? mms_money( $p['setup_fee'], $p['currency'] ) . ' ' . __( 'one-time', 'mine-mirth-subscriptions' ) : $p['setup'],
				__( 'Term', 'mine-mirth-subscriptions' )               => $p['term'],
			)
		);
		ob_start();
		?>
		<article class="mm-plan mm-card mms-plan<?php echo $p['featured'] ? ' is-featured' : ''; ?>" data-mm-reveal id="plan-<?php echo (int) $p['id']; ?>">
			<?php if ( $p['image'] ) : ?>
				<div class="mms-plan__media"><img src="<?php echo esc_url( $p['image'] ); ?>" alt="<?php echo esc_attr( $p['title'] ); ?>" loading="lazy" decoding="async"></div>
			<?php endif; ?>
			<div class="mm-plan__top">
				<?php if ( isset( $avail_labels[ $p['availability'] ] ) ) : ?>
					<span class="mm-avail mm-avail--<?php echo esc_attr( $p['availability'] ); ?>"><?php echo esc_html( $avail_labels[ $p['availability'] ] ); ?></span>
				<?php endif; ?>
				<?php if ( $p['region'] ) : ?>
					<span class="mm-plan__region"><?php echo function_exists( 'mm_icon' ) ? mm_icon( 'map-pin' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $p['region'] ); ?></span>
				<?php endif; ?>
			</div>
			<h3 class="mm-plan__name"><?php echo esc_html( $p['title'] ); ?></h3>
			<?php if ( $ready ) : ?>
				<div class="mms-plan__price">
					<strong><?php echo esc_html( mms_money( $p['amount'], $p['currency'] ) ); ?></strong>
					<span><?php echo esc_html( ( $p['qty'] ? sprintf( /* translators: %s: unit */ __( 'per %s', 'mine-mirth-subscriptions' ), rtrim( $p['qty_label'], 's' ) ) . ' ' : '' ) . mms_interval_label( $p['interval'], $p['interval_count'] ) ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $p['rate'] ) : ?>
				<div class="mm-plan__rate"><strong><?php echo esc_html( $p['rate'] ); ?></strong><?php echo $p['billing'] ? '<span>' . esc_html( $p['billing'] ) . '</span>' : ''; ?></div>
			<?php endif; ?>
			<?php if ( '' !== trim( $p['content'] ) ) : ?>
				<div class="mm-plan__desc"><?php echo wp_kses_post( wpautop( $p['content'] ) ); ?></div>
			<?php endif; ?>
			<?php if ( $p['features'] ) : ?>
				<ul class="mm-plan__features">
					<?php foreach ( $p['features'] as $f ) : ?>
						<li><?php echo function_exists( 'mm_icon' ) ? mm_icon( 'check' ) : '✓ '; // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $f ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $facts ) : ?>
				<dl class="mm-plan__facts">
					<?php foreach ( $facts as $label => $value ) : ?>
						<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<?php if ( $p['fine_print'] ) : ?>
				<p class="mm-plan__fine"><?php echo esc_html( $p['fine_print'] ); ?></p>
			<?php endif; ?>
			<div class="mm-plan__cta">
				<?php if ( $ready ) : ?>
					<form method="post" action="<?php echo esc_url( mms_plans_page_url() ); ?>" class="mms-subscribe">
						<input type="hidden" name="mms_subscribe" value="1">
						<input type="hidden" name="mms_plan" value="<?php echo (int) $p['id']; ?>">
						<input type="text" name="mms_website" value="" tabindex="-1" autocomplete="off" class="mms-hp" aria-hidden="true">
						<?php if ( $p['qty'] ) : ?>
							<div class="mms-qty" data-mms-qty data-unit="<?php echo (int) $p['amount']; ?>" data-cur="<?php echo esc_attr( $p['currency'] ); ?>">
								<span class="mms-qty__label"><?php echo esc_html( ucfirst( $p['qty_label'] ) ); ?></span>
								<span class="mms-qty__ctl">
									<button type="button" data-step="-1" aria-label="<?php esc_attr_e( 'Fewer', 'mine-mirth-subscriptions' ); ?>">−</button>
									<input type="number" name="mms_qty" value="1" min="1" max="<?php echo (int) $p['qty_max']; ?>" inputmode="numeric" aria-label="<?php echo esc_attr( ucfirst( $p['qty_label'] ) ); ?>">
									<button type="button" data-step="1" aria-label="<?php esc_attr_e( 'More', 'mine-mirth-subscriptions' ); ?>">+</button>
								</span>
								<span class="mms-qty__total" data-mms-total><?php echo esc_html( mms_money( $p['amount'], $p['currency'] ) ); ?></span>
							</div>
						<?php endif; ?>
						<button type="submit" class="mm-btn <?php echo $p['featured'] ? 'mm-btn--primary' : 'mm-btn--ghost'; ?> mm-btn--block mms-btn">
							<span><?php echo esc_html( $p['button'] ); ?></span>
							<?php echo function_exists( 'mm_icon' ) ? mm_icon( 'arrow-right' ) : '→'; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</button>
						<p class="mms-secure"><?php esc_html_e( 'Secure checkout by Stripe. Cancel any time.', 'mine-mirth-subscriptions' ); ?></p>
					</form>
				<?php elseif ( 'unavailable' !== $p['availability'] ) : ?>
					<a class="mm-btn mm-btn--ghost mm-btn--block" href="<?php echo esc_url( $p['cta_url'] ? $p['cta_url'] : $contact ); ?>"><span><?php esc_html_e( 'Request availability', 'mine-mirth-subscriptions' ); ?></span></a>
				<?php else : ?>
					<span class="mm-btn mm-btn--ghost mm-btn--block" aria-disabled="true"><?php echo esc_html( $avail_labels['unavailable'] ?? __( 'Unavailable', 'mine-mirth-subscriptions' ) ); ?></span>
				<?php endif; ?>
			</div>
		</article>
		<?php
		$out .= (string) ob_get_clean();
	}
	return $out . '</div>';
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_register_style( 'mms-front', MMS_URI . 'assets/front.css', array(), MMS_VERSION );
		wp_register_script( 'mms-front', MMS_URI . 'assets/front.js', array(), MMS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		if ( is_page( mms_plans_page_id() ) ) {
			wp_enqueue_style( 'mms-front' );
		}
	}
);

add_shortcode( 'mm_subscription_plans', static fn( $atts ) => mms_render_plans( (array) $atts ) );

/**
 * Where the theme lists hosting plans (its shortcode and "Hosting plans" section), show these cards.
 */
add_action(
	'init',
	static function (): void {
		if ( 'off' === mms_setting( 'placement' ) ) {
			return;
		}
		foreach ( array( 'mm_hosting_plans', 'mine_mirth_hosting_plans' ) as $tag ) {
			remove_shortcode( $tag );
			add_shortcode( $tag, static fn( $atts ) => mms_render_plans( (array) $atts ) );
		}
	},
	30
);

add_filter(
	'render_block',
	static function ( $html, $block ) {
		if ( 'replace' !== mms_setting( 'placement' ) || 'mm/section' !== ( $block['blockName'] ?? '' ) || 'pricing' !== ( $block['attrs']['variant'] ?? '' ) ) {
			return $html;
		}
		// The theme's own plan list inside the section: replace it with the cards.
		$ours = mms_render_plans();
		$new  = preg_replace( '#<div class="mm-plans">.*</article>\s*</div>|<div class="mm-plans-empty">.*?</div>#s', '%%MMS_PLANS%%', (string) $html, 1, $count );
		return $count ? str_replace( '%%MMS_PLANS%%', $ours, (string) $new ) : $html;
	},
	10,
	2
);

add_filter(
	'the_content',
	static function ( $content ) {
		if ( 'top' !== mms_setting( 'placement' ) || ! is_page( mms_plans_page_id() ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		return '<div class="mms-top" id="hosting-plans">' . mms_render_plans() . '</div>' . $content;
	},
	20
);

/* ------------------------------------------------------------------ After checkout */

add_action(
	'wp_footer',
	static function (): void {
		$state = sanitize_key( wp_unslash( $_GET['mms_checkout'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' === $state ) {
			return;
		}
		wp_enqueue_style( 'mms-front' );
		$title = '';
		$text  = '';
		$ok    = false;
		if ( 'success' === $state ) {
			$session = sanitize_text_field( wp_unslash( $_GET['session_id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$id      = $session ? mms_process_checkout( $session ) : 0;
			$sub     = $id ? mms_get( 'subscriptions', 'id', $id ) : null;
			$cust    = $sub ? mms_get( 'customers', 'id', (int) $sub['customer_id'] ) : null;
			$ok      = true;
			$title   = __( 'You are subscribed. Welcome to Mine Mirth!', 'mine-mirth-subscriptions' );
			$text    = $sub && $cust
				? sprintf( /* translators: 1: plan, 2: client number, 3: email */ __( 'Your %1$s subscription is active. Your client number is %2$s: please quote it when you contact us. A confirmation is on its way to %3$s.', 'mine-mirth-subscriptions' ), $sub['plan_name'], $cust['client_no'], $cust['email'] )
				: __( 'Thank you. Your payment went through and your confirmation email is on its way.', 'mine-mirth-subscriptions' );
		} elseif ( 'cancelled' === $state ) {
			$title = __( 'Checkout cancelled', 'mine-mirth-subscriptions' );
			$text  = __( 'Nothing was charged. Choose a plan again whenever you are ready, or ask us a question first.', 'mine-mirth-subscriptions' );
		} elseif ( 'error' === $state ) {
			$title = __( 'Checkout could not start', 'mine-mirth-subscriptions' );
			$text  = __( 'Something went wrong on our side. Nothing was charged. Please try again in a minute or contact us.', 'mine-mirth-subscriptions' );
		}
		if ( '' === $title ) {
			return;
		}
		?>
		<div class="mms-toast<?php echo $ok ? ' is-ok' : ''; ?>" role="status" data-mms-toast>
			<div class="mms-toast__icon" aria-hidden="true"><?php echo $ok ? '✓' : 'i'; ?></div>
			<div class="mms-toast__body"><strong><?php echo esc_html( $title ); ?></strong><p><?php echo esc_html( $text ); ?></p></div>
			<button type="button" class="mms-toast__close" aria-label="<?php esc_attr_e( 'Close', 'mine-mirth-subscriptions' ); ?>" onclick="this.parentNode.remove()">×</button>
		</div>
		<?php
	}
);
