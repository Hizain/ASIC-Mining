<?php
/**
 * Settings: the Stripe connection (with a step-by-step guide and one-click webhook setup), checkout,
 * emails and the company details shown on invoices.
 *
 * @package MineMirthSubscriptions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Who can manage subscriptions: shop managers and administrators.
 */
function mms_cap(): string {
	return (string) apply_filters( 'mms_capability', class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options' );
}

function mms_theme_opt( string $key, $default = '' ) {
	$value = function_exists( 'mm_opt' ) ? mm_opt( $key ) : get_theme_mod( 'mm_' . $key, null );
	return null === $value || '' === $value ? $default : $value;
}

function mms_default_settings(): array {
	$po = get_option( 'mmpo_settings', array() );
	$po = is_array( $po ) ? $po : array();
	return array(
		'mode'                 => 'test',
		'test_publishable'     => '',
		'test_secret'          => '',
		'test_webhook_secret'  => '',
		'test_webhook_id'      => '',
		'live_publishable'     => '',
		'live_secret'          => '',
		'live_webhook_secret'  => '',
		'live_webhook_id'      => '',
		'currency'             => 'usd',
		'client_prefix'        => 'MMC-',
		'company'              => ! empty( $po['company'] ) ? (string) $po['company'] : 'Mine Mirth LLC',
		'address'              => ! empty( $po['address'] ) ? (string) $po['address'] : (string) mms_theme_opt( 'contact_address', '' ),
		'email'                => ! empty( $po['email'] ) ? (string) $po['email'] : (string) mms_theme_opt( 'contact_email', get_option( 'admin_email' ) ),
		'phone'                => ! empty( $po['phone'] ) ? (string) $po['phone'] : (string) mms_theme_opt( 'contact_phone', '' ),
		'plans_page'           => 0,
		'placement'            => 'replace',
		'allow_promo'          => 1,
		'collect_phone'        => 1,
		'collect_address'      => 1,
		'from_name'            => 'Mine Mirth',
		'from_email'           => '',
		'admin_copy'           => 1,
		'admin_email'          => (string) get_option( 'admin_email' ),
		'email_welcome'        => 1,
		'email_reminder'       => 1,
		'reminder_days'        => 3,
		'email_receipt'        => 1,
		'email_failed'         => 1,
		'email_refund'         => 1,
		'email_adjust'         => 1,
		'email_canceled'       => 1,
		'invoice_footer'       => __( 'Thank you for hosting with Mine Mirth.', 'mine-mirth-subscriptions' ),
	);
}

function mms_settings( bool $fresh = false ): array {
	static $cache = null;
	if ( null === $cache || $fresh ) {
		$saved = get_option( 'mms_settings', array() );
		$cache = array_merge( mms_default_settings(), is_array( $saved ) ? $saved : array() );
	}
	return $cache;
}

function mms_setting( string $key ) {
	return mms_settings()[ $key ] ?? '';
}

function mms_update_settings( array $changes ): void {
	$saved = get_option( 'mms_settings', array() );
	$saved = array_merge( is_array( $saved ) ? $saved : array(), $changes );
	update_option( 'mms_settings', $saved, false );
	mms_settings( true );
}

/**
 * The page that lists the plans (the theme's Miner hosting page by default).
 */
function mms_plans_page_id(): int {
	$id = (int) mms_setting( 'plans_page' );
	if ( $id && get_post( $id ) ) {
		return $id;
	}
	$ids = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'meta_key' => '_mm_page_key', 'meta_value' => 'hosting', 'fields' => 'ids', 'numberposts' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $ids ) {
		return (int) $ids[0];
	}
	$page = get_page_by_path( 'miner-hosting' );
	return $page ? (int) $page->ID : 0;
}

function mms_plans_page_url(): string {
	$id = mms_plans_page_id();
	return $id ? (string) get_permalink( $id ) : home_url( '/' );
}

/* ------------------------------------------------------------------ Connect */

/* ------------------------------------------------------------------ Save */

add_action(
	'admin_post_mms_settings',
	static function (): void {
		if ( ! current_user_can( mms_cap() ) || ! check_admin_referer( 'mms_settings' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-subscriptions' ), 403 );
		}
		$in   = wp_unslash( $_POST['mms'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cleaned below.
		$in   = is_array( $in ) ? $in : array();
		$tab  = sanitize_key( (string) ( $_POST['tab'] ?? 'connect' ) );
		$out  = array();
		$text = static fn( string $k ) => sanitize_text_field( (string) ( $in[ $k ] ?? '' ) );
		$flag = static fn( string $k ) => empty( $in[ $k ] ) ? 0 : 1;

		if ( 'connect' === $tab ) {
			$out['mode'] = 'live' === ( $in['mode'] ?? '' ) ? 'live' : 'test';
			foreach ( array( 'test', 'live' ) as $m ) {
				foreach ( array( 'publishable', 'secret', 'webhook_secret' ) as $f ) {
					$v = trim( $text( $m . '_' . $f ) );
					// Secret fields left empty keep the saved value.
					if ( '' !== $v || 'publishable' === $f ) {
						$out[ $m . '_' . $f ] = $v;
					}
				}
			}
		} elseif ( 'checkout' === $tab ) {
			$out['currency']        = strtolower( substr( preg_replace( '/[^a-z]/i', '', $text( 'currency' ) ), 0, 3 ) ) ?: 'usd';
			$out['client_prefix']   = preg_replace( '/[^A-Za-z0-9\-_#]/', '', $text( 'client_prefix' ) );
			$out['plans_page']      = absint( $in['plans_page'] ?? 0 );
			$out['placement']       = in_array( $in['placement'] ?? '', array( 'replace', 'top', 'off' ), true ) ? $in['placement'] : 'replace';
			$out['allow_promo']     = $flag( 'allow_promo' );
			$out['collect_phone']   = $flag( 'collect_phone' );
			$out['collect_address'] = $flag( 'collect_address' );
			$next                   = absint( $_POST['next_client'] ?? 0 );
			if ( $next ) {
				update_option( 'mms_next_client', $next, false );
			}
		} elseif ( 'emails' === $tab ) {
			$out['from_name']     = $text( 'from_name' );
			$out['from_email']    = sanitize_email( (string) ( $in['from_email'] ?? '' ) );
			$out['admin_email']   = sanitize_email( (string) ( $in['admin_email'] ?? '' ) );
			$out['reminder_days'] = max( 1, min( 30, absint( $in['reminder_days'] ?? 3 ) ) );
			foreach ( array( 'admin_copy', 'email_welcome', 'email_reminder', 'email_receipt', 'email_failed', 'email_refund', 'email_adjust', 'email_canceled' ) as $k ) {
				$out[ $k ] = $flag( $k );
			}
		} elseif ( 'company' === $tab ) {
			$out['company']        = $text( 'company' );
			$out['address']        = sanitize_textarea_field( (string) ( $in['address'] ?? '' ) );
			$out['email']          = sanitize_email( (string) ( $in['email'] ?? '' ) );
			$out['phone']          = $text( 'phone' );
			$out['invoice_footer'] = $text( 'invoice_footer' );
		}
		mms_update_settings( $out );

		$args = array( 'page' => 'mms-settings', 'tab' => $tab, 'msg' => 'saved' );
		if ( 'connect' === $tab && ! empty( $_POST['do_connect'] ) ) {
			$mode = 'live' === ( $out['mode'] ?? 'test' ) ? 'live' : 'test';
			// Read the settings just saved.
			$saved = get_option( 'mms_settings', array() );
			$res   = mms_connect_with_key( $mode, trim( (string) ( $saved[ $mode . '_secret' ] ?? '' ) ) );
			if ( is_wp_error( $res ) ) {
				$args['msg'] = 'connect_error';
				set_transient( 'mms_notice_' . get_current_user_id(), $res->get_error_message(), 120 );
			} else {
				$args['msg'] = 'connected';
			}
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
);

/**
 * Check the keys, then create (or replace) the webhook in Stripe and store its signing secret.
 *
 * @return array|WP_Error Account name and webhook ID.
 */
function mms_connect_with_key( string $mode, string $key ): array|WP_Error {
	if ( '' === $key ) {
		return new WP_Error( 'mms_key', __( 'Paste your secret key first.', 'mine-mirth-subscriptions' ) );
	}
	$account = mms_stripe( 'GET', '/v1/account', array(), '', $key );
	if ( is_wp_error( $account ) ) {
		return $account;
	}
	if ( ! str_starts_with( $key, 'sk_' . $mode . '_' ) && ! str_starts_with( $key, 'rk_' . $mode . '_' ) ) {
		/* translators: %s: test or live */
		return new WP_Error( 'mms_mode', sprintf( __( 'That secret key is not a %s key. Test keys start with sk_test_, live keys with sk_live_.', 'mine-mirth-subscriptions' ), $mode ) );
	}
	$url      = mms_webhook_url();
	$existing = mms_stripe( 'GET', '/v1/webhook_endpoints', array( 'limit' => 100 ), '', $key );
	if ( ! is_wp_error( $existing ) ) {
		foreach ( (array) ( $existing['data'] ?? array() ) as $ep ) {
			if ( ( $ep['url'] ?? '' ) === $url && ! empty( $ep['metadata']['mms'] ) ) {
				mms_stripe( 'DELETE', '/v1/webhook_endpoints/' . $ep['id'], array(), '', $key );
			}
		}
	}
	$ep = mms_stripe(
		'POST',
		'/v1/webhook_endpoints',
		array(
			'url'            => $url,
			'enabled_events' => mms_webhook_events(),
			'api_version'    => MMS_STRIPE_VERSION,
			'description'    => 'Mine Mirth Subscriptions (' . wp_parse_url( home_url(), PHP_URL_HOST ) . ')',
			'metadata'       => array( 'mms' => '1' ),
		),
		'',
		$key
	);
	if ( is_wp_error( $ep ) ) {
		return $ep;
	}
	$name = (string) ( $account['settings']['dashboard']['display_name'] ?? '' );
	$name = '' !== $name ? $name : (string) ( $account['business_profile']['name'] ?? $account['email'] ?? $account['id'] ?? '' );
	mms_update_settings(
		array(
			$mode . '_webhook_secret' => (string) ( $ep['secret'] ?? '' ),
			$mode . '_webhook_id'     => (string) ( $ep['id'] ?? '' ),
			$mode . '_account'        => $name,
		)
	);
	mms_log( 'connect', sprintf( 'Connected to Stripe account %s in %s mode; webhook %s created.', $name, $mode, (string) ( $ep['id'] ?? '' ) ) );
	return array( 'account' => $name, 'webhook' => (string) ( $ep['id'] ?? '' ) );
}

/* ------------------------------------------------------------------ Page */

function mms_settings_page(): void {
	if ( ! current_user_can( mms_cap() ) ) {
		return;
	}
	$s    = get_option( 'mms_settings', array() );
	$s    = array_merge( mms_default_settings(), is_array( $s ) ? $s : array() );
	$tab  = sanitize_key( wp_unslash( $_GET['tab'] ?? 'connect' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$msg  = sanitize_key( wp_unslash( $_GET['msg'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$tabs = array(
		'connect'  => __( 'Connect Stripe', 'mine-mirth-subscriptions' ),
		'checkout' => __( 'Plans & checkout', 'mine-mirth-subscriptions' ),
		'emails'   => __( 'Emails', 'mine-mirth-subscriptions' ),
		'company'  => __( 'Company & invoices', 'mine-mirth-subscriptions' ),
	);
	$tab  = isset( $tabs[ $tab ] ) ? $tab : 'connect';
	$v    = static fn( string $k ) => esc_attr( (string) ( $s[ $k ] ?? '' ) );
	$chk  = static fn( string $k ) => checked( ! empty( $s[ $k ] ), true, false );
	$mask = static function ( string $val ): string {
		return '' === $val ? '' : sprintf( /* translators: %s: last 4 characters */ __( 'Saved (ends in %s). Leave empty to keep it.', 'mine-mirth-subscriptions' ), substr( $val, -4 ) );
	};
	?>
	<div class="wrap mms-admin">
		<h1 class="mms-title"><?php esc_html_e( 'Subscription settings', 'mine-mirth-subscriptions' ); ?></h1>
		<?php
		if ( 'saved' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'mine-mirth-subscriptions' ) . '</p></div>';
		} elseif ( 'connected' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Connected to Stripe.', 'mine-mirth-subscriptions' ) . '</strong> ' . esc_html__( 'Your keys work and the webhook was created, so subscriptions, payments and refunds now appear here automatically.', 'mine-mirth-subscriptions' ) . '</p></div>';
		} elseif ( 'connect_error' === $msg ) {
			$err = (string) get_transient( 'mms_notice_' . get_current_user_id() );
			echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Could not connect.', 'mine-mirth-subscriptions' ) . '</strong> ' . esc_html( $err ) . '</p></div>';
		}
		?>
		<nav class="nav-tab-wrapper mms-tabs">
			<?php foreach ( $tabs as $key => $label ) : ?>
				<a class="nav-tab<?php echo $key === $tab ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=mms-settings&tab=' . $key ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mms-settings">
			<input type="hidden" name="action" value="mms_settings">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
			<?php wp_nonce_field( 'mms_settings' ); ?>

			<?php if ( 'connect' === $tab ) : ?>
				<?php $mode = 'live' === $s['mode'] ? 'live' : 'test'; ?>
				<div class="mms-grid">
					<section class="mms-card">
						<h2><?php esc_html_e( 'How to connect (about 2 minutes)', 'mine-mirth-subscriptions' ); ?></h2>
						<ol class="mms-steps">
							<li>
								<strong><?php esc_html_e( 'Open your Stripe API keys', 'mine-mirth-subscriptions' ); ?></strong>
								<span><?php esc_html_e( 'Stripe Dashboard › Developers › API keys. Start in test mode, switch to live when you are ready to take real payments.', 'mine-mirth-subscriptions' ); ?></span>
								<span class="mms-btns">
									<a class="button" href="https://dashboard.stripe.com/test/apikeys" target="_blank" rel="noopener"><?php esc_html_e( 'Test keys', 'mine-mirth-subscriptions' ); ?> ↗</a>
									<a class="button" href="https://dashboard.stripe.com/apikeys" target="_blank" rel="noopener"><?php esc_html_e( 'Live keys', 'mine-mirth-subscriptions' ); ?> ↗</a>
								</span>
							</li>
							<li>
								<strong><?php esc_html_e( 'Copy the Publishable key and the Secret key', 'mine-mirth-subscriptions' ); ?></strong>
								<span><?php esc_html_e( 'Click "Reveal" next to the secret key. Paste both below. The secret key stays on your server and is never shown to visitors.', 'mine-mirth-subscriptions' ); ?></span>
							</li>
							<li>
								<strong><?php esc_html_e( 'Click "Save and connect"', 'mine-mirth-subscriptions' ); ?></strong>
								<span><?php esc_html_e( 'The plugin checks the keys and creates the webhook in Stripe for you, so payments, renewals and refunds show up here by themselves.', 'mine-mirth-subscriptions' ); ?></span>
							</li>
							<li>
								<strong><?php esc_html_e( 'Turn on the customer portal (once)', 'mine-mirth-subscriptions' ); ?></strong>
								<span><?php esc_html_e( 'Stripe Dashboard › Settings › Billing › Customer portal › Activate. Customers use it from their emails to update their card or download receipts.', 'mine-mirth-subscriptions' ); ?></span>
								<span class="mms-btns"><a class="button" href="https://dashboard.stripe.com/<?php echo 'test' === $mode ? 'test/' : ''; ?>settings/billing/portal" target="_blank" rel="noopener"><?php esc_html_e( 'Customer portal settings', 'mine-mirth-subscriptions' ); ?> ↗</a></span>
							</li>
							<li>
								<strong><?php esc_html_e( 'Add your plans', 'mine-mirth-subscriptions' ); ?></strong>
								<span><?php esc_html_e( 'Hosting Plans › edit a plan: add an image and a monthly price in the "Stripe subscription" box and update. It is created in Stripe at once and appears on your hosting page with a Subscribe button.', 'mine-mirth-subscriptions' ); ?></span>
								<span class="mms-btns"><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=mm_hosting_plan' ) ); ?>"><?php esc_html_e( 'Open Hosting Plans', 'mine-mirth-subscriptions' ); ?></a></span>
							</li>
						</ol>
					</section>
					<section class="mms-card">
						<h2><?php esc_html_e( 'Stripe keys', 'mine-mirth-subscriptions' ); ?></h2>
						<fieldset class="mms-modes">
							<label><input type="radio" name="mms[mode]" value="test" <?php checked( $mode, 'test' ); ?>> <strong><?php esc_html_e( 'Test mode', 'mine-mirth-subscriptions' ); ?></strong> <span><?php esc_html_e( 'no real money; use card 4242 4242 4242 4242', 'mine-mirth-subscriptions' ); ?></span></label>
							<label><input type="radio" name="mms[mode]" value="live" <?php checked( $mode, 'live' ); ?>> <strong><?php esc_html_e( 'Live mode', 'mine-mirth-subscriptions' ); ?></strong> <span><?php esc_html_e( 'real payments', 'mine-mirth-subscriptions' ); ?></span></label>
						</fieldset>
						<?php foreach ( array( 'test' => __( 'Test keys', 'mine-mirth-subscriptions' ), 'live' => __( 'Live keys', 'mine-mirth-subscriptions' ) ) as $m => $label ) : ?>
							<div class="mms-keys mms-keys--<?php echo esc_attr( $m ); ?>">
								<h3><?php echo esc_html( $label ); ?>
									<?php if ( ! empty( $s[ $m . '_webhook_id' ] ) ) : ?>
										<span class="mms-pill" style="--c:#12b981"><i></i><?php echo esc_html( sprintf( /* translators: %s: account */ __( 'Connected: %s', 'mine-mirth-subscriptions' ), (string) ( $s[ $m . '_account' ] ?? '' ) ) ); ?></span>
									<?php endif; ?>
								</h3>
								<label class="mms-f"><span><?php esc_html_e( 'Publishable key', 'mine-mirth-subscriptions' ); ?></span><input class="regular-text code" name="mms[<?php echo esc_attr( $m ); ?>_publishable]" value="<?php echo $v( $m . '_publishable' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" placeholder="pk_<?php echo esc_attr( $m ); ?>_…" autocomplete="off"></label>
								<label class="mms-f"><span><?php esc_html_e( 'Secret key', 'mine-mirth-subscriptions' ); ?></span><input class="regular-text code" type="password" name="mms[<?php echo esc_attr( $m ); ?>_secret]" value="" placeholder="<?php echo esc_attr( $mask( (string) $s[ $m . '_secret' ] ) ?: 'sk_' . $m . '_…' ); ?>" autocomplete="new-password"></label>
								<details class="mms-manual">
									<summary><?php esc_html_e( 'Set up the webhook by hand instead', 'mine-mirth-subscriptions' ); ?></summary>
									<p><?php esc_html_e( 'Only needed if "Save and connect" can not create it. Stripe Dashboard › Developers › Webhooks › Add endpoint:', 'mine-mirth-subscriptions' ); ?></p>
									<p><strong><?php esc_html_e( 'Endpoint URL', 'mine-mirth-subscriptions' ); ?></strong><br><code class="mms-copy"><?php echo esc_html( mms_webhook_url() ); ?></code></p>
									<p><strong><?php esc_html_e( 'Events', 'mine-mirth-subscriptions' ); ?></strong><br><code><?php echo esc_html( implode( ', ', mms_webhook_events() ) ); ?></code></p>
									<p><strong><?php esc_html_e( 'API version', 'mine-mirth-subscriptions' ); ?></strong><br><code><?php echo esc_html( MMS_STRIPE_VERSION ); ?></code> <span class="description"><?php esc_html_e( '(choose it if Stripe asks; other versions also work, just with one extra lookup per event)', 'mine-mirth-subscriptions' ); ?></span></p>
									<label class="mms-f"><span><?php esc_html_e( 'Signing secret (whsec_…)', 'mine-mirth-subscriptions' ); ?></span><input class="regular-text code" type="password" name="mms[<?php echo esc_attr( $m ); ?>_webhook_secret]" value="" placeholder="<?php echo esc_attr( $mask( (string) $s[ $m . '_webhook_secret' ] ) ?: 'whsec_…' ); ?>" autocomplete="new-password"></label>
								</details>
							</div>
						<?php endforeach; ?>
						<p class="mms-actions">
							<button type="submit" class="button button-primary button-hero" name="do_connect" value="1"><?php esc_html_e( 'Save and connect', 'mine-mirth-subscriptions' ); ?></button>
							<button type="submit" class="button button-hero"><?php esc_html_e( 'Save only', 'mine-mirth-subscriptions' ); ?></button>
						</p>
					</section>
				</div>
			<?php elseif ( 'checkout' === $tab ) : ?>
				<section class="mms-card mms-narrow">
					<h2><?php esc_html_e( 'Where plans appear', 'mine-mirth-subscriptions' ); ?></h2>
					<label class="mms-f"><span><?php esc_html_e( 'Hosting plans page', 'mine-mirth-subscriptions' ); ?></span>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'mms[plans_page]',
								'selected'          => mms_plans_page_id(),
								'show_option_none'  => __( 'Choose a page', 'mine-mirth-subscriptions' ),
								'option_none_value' => 0,
							)
						);
						?>
					</label>
					<fieldset class="mms-f"><span><?php esc_html_e( 'On that page', 'mine-mirth-subscriptions' ); ?></span>
						<label><input type="radio" name="mms[placement]" value="replace" <?php checked( $s['placement'], 'replace' ); ?>> <?php esc_html_e( 'Show the plan cards with images and Subscribe buttons where the hosting plans are listed', 'mine-mirth-subscriptions' ); ?></label>
						<label><input type="radio" name="mms[placement]" value="top" <?php checked( $s['placement'], 'top' ); ?>> <?php esc_html_e( 'Add them at the top of the page content', 'mine-mirth-subscriptions' ); ?></label>
						<label><input type="radio" name="mms[placement]" value="off" <?php checked( $s['placement'], 'off' ); ?>> <?php esc_html_e( 'Only where I put the [mm_subscription_plans] shortcode', 'mine-mirth-subscriptions' ); ?></label>
					</fieldset>
					<h2><?php esc_html_e( 'Checkout', 'mine-mirth-subscriptions' ); ?></h2>
					<label class="mms-f mms-f--short"><span><?php esc_html_e( 'Currency', 'mine-mirth-subscriptions' ); ?></span><input name="mms[currency]" value="<?php echo $v( 'currency' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" maxlength="3" class="small-text code"></label>
					<label class="mms-check"><input type="checkbox" name="mms[allow_promo]" value="1" <?php echo $chk( 'allow_promo' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>> <?php esc_html_e( 'Customers can enter a promotion code at checkout', 'mine-mirth-subscriptions' ); ?></label>
					<label class="mms-check"><input type="checkbox" name="mms[collect_phone]" value="1" <?php echo $chk( 'collect_phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>> <?php esc_html_e( 'Ask for a phone number', 'mine-mirth-subscriptions' ); ?></label>
					<label class="mms-check"><input type="checkbox" name="mms[collect_address]" value="1" <?php echo $chk( 'collect_address' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>> <?php esc_html_e( 'Ask for the billing address', 'mine-mirth-subscriptions' ); ?></label>
					<h2><?php esc_html_e( 'Client numbers', 'mine-mirth-subscriptions' ); ?></h2>
					<div class="mms-row">
						<label class="mms-f mms-f--short"><span><?php esc_html_e( 'Prefix', 'mine-mirth-subscriptions' ); ?></span><input name="mms[client_prefix]" value="<?php echo $v( 'client_prefix' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" class="small-text code"></label>
						<label class="mms-f mms-f--short"><span><?php esc_html_e( 'Next number', 'mine-mirth-subscriptions' ); ?></span><input type="number" min="1" name="next_client" value="<?php echo (int) get_option( 'mms_next_client', 1001 ); ?>" class="small-text"></label>
					</div>
					<p class="description"><?php echo esc_html( sprintf( /* translators: %s: client number */ __( 'The next new client gets %s. The number is saved on the customer in Stripe too.', 'mine-mirth-subscriptions' ), mms_peek_client_no() ) ); ?></p>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'mine-mirth-subscriptions' ); ?></button></p>
				</section>
			<?php elseif ( 'emails' === $tab ) : ?>
				<section class="mms-card mms-narrow">
					<h2><?php esc_html_e( 'Emails to subscribers', 'mine-mirth-subscriptions' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Every email uses the Mine Mirth design: your logo, colours and the plan image.', 'mine-mirth-subscriptions' ); ?></p>
					<?php
					foreach ( array(
						'email_welcome'  => __( 'Subscription started: plan, client number, next billing date and how to manage it', 'mine-mirth-subscriptions' ),
						'email_reminder' => __( 'Billing reminder before each renewal', 'mine-mirth-subscriptions' ),
						'email_receipt'  => __( 'Payment receipt with the invoice PDF attached', 'mine-mirth-subscriptions' ),
						'email_failed'   => __( 'Payment failed, with a link to update the card', 'mine-mirth-subscriptions' ),
						'email_refund'   => __( 'Refund issued', 'mine-mirth-subscriptions' ),
						'email_adjust'   => __( 'Discount or extra charge added to their account', 'mine-mirth-subscriptions' ),
						'email_canceled' => __( 'Subscription cancelled', 'mine-mirth-subscriptions' ),
					) as $k => $label ) :
						?>
						<label class="mms-check"><input type="checkbox" name="mms[<?php echo esc_attr( $k ); ?>]" value="1" <?php echo $chk( $k ); // phpcs:ignore WordPress.Security.EscapeOutput ?>> <?php echo esc_html( $label ); ?></label>
					<?php endforeach; ?>
					<label class="mms-f mms-f--short"><span><?php esc_html_e( 'Send the billing reminder', 'mine-mirth-subscriptions' ); ?></span><span class="mms-unit"><input type="number" min="1" max="30" name="mms[reminder_days]" value="<?php echo $v( 'reminder_days' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" class="small-text"> <?php esc_html_e( 'days before billing', 'mine-mirth-subscriptions' ); ?></span></label>
					<div class="mms-row">
						<label class="mms-f"><span><?php esc_html_e( 'From name', 'mine-mirth-subscriptions' ); ?></span><input name="mms[from_name]" value="<?php echo $v( 'from_name' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
						<label class="mms-f"><span><?php esc_html_e( 'From email', 'mine-mirth-subscriptions' ); ?> <i><?php esc_html_e( 'optional', 'mine-mirth-subscriptions' ); ?></i></span><input type="email" name="mms[from_email]" value="<?php echo $v( 'from_email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" placeholder="billing@minemirth.com"></label>
					</div>
					<h2><?php esc_html_e( 'Copies to you', 'mine-mirth-subscriptions' ); ?></h2>
					<label class="mms-check"><input type="checkbox" name="mms[admin_copy]" value="1" <?php echo $chk( 'admin_copy' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>> <?php esc_html_e( 'Tell me about new subscriptions, failed payments and cancellations', 'mine-mirth-subscriptions' ); ?></label>
					<label class="mms-f"><span><?php esc_html_e( 'Send them to', 'mine-mirth-subscriptions' ); ?></span><input type="email" name="mms[admin_email]" value="<?php echo $v( 'admin_email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'mine-mirth-subscriptions' ); ?></button></p>
				</section>
			<?php else : ?>
				<section class="mms-card mms-narrow">
					<h2><?php esc_html_e( 'On invoices and emails', 'mine-mirth-subscriptions' ); ?></h2>
					<label class="mms-f"><span><?php esc_html_e( 'Company name', 'mine-mirth-subscriptions' ); ?></span><input name="mms[company]" value="<?php echo $v( 'company' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
					<label class="mms-f"><span><?php esc_html_e( 'Oregon address', 'mine-mirth-subscriptions' ); ?></span><textarea name="mms[address]" rows="3"><?php echo esc_textarea( (string) $s['address'] ); ?></textarea></label>
					<div class="mms-row">
						<label class="mms-f"><span><?php esc_html_e( 'Email', 'mine-mirth-subscriptions' ); ?></span><input type="email" name="mms[email]" value="<?php echo $v( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
						<label class="mms-f"><span><?php esc_html_e( 'Phone', 'mine-mirth-subscriptions' ); ?></span><input name="mms[phone]" value="<?php echo $v( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
					</div>
					<label class="mms-f"><span><?php esc_html_e( 'Invoice footer', 'mine-mirth-subscriptions' ); ?></span><input name="mms[invoice_footer]" value="<?php echo $v( 'invoice_footer' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"></label>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'mine-mirth-subscriptions' ); ?></button></p>
				</section>
			<?php endif; ?>
		</form>
	</div>
	<?php
}
