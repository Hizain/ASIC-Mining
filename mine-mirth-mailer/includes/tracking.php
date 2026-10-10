<?php
/**
 * Knowing what subscribers do on the site, and the links inside emails.
 *
 * A first-party cookie (mmm_sid) holds the subscriber's private token. It is
 * set when someone signs up, clicks a link in one of our emails, or is logged
 * in with a subscribed email address. Each page prints what it is (shop,
 * product, article…) and a tiny script reports the visit only when that cookie
 * exists, so it works on cached pages and costs nothing for other visitors.
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

const MMM_COOKIE = 'mmm_sid';

function mmm_sign( string $data ): string {
	return substr( hash_hmac( 'sha256', $data, wp_salt( 'auth' ) . 'mmm' ), 0, 20 );
}

function mmm_set_cookie( string $token ): void {
	static $done = array();
	if ( headers_sent() || ! preg_match( '/^[a-f0-9]{32}$/', $token ) || isset( $done[ $token ] ) ) {
		return;
	}
	$done[ $token ] = true;
	setcookie(
		MMM_COOKIE,
		$token,
		array(
			'expires'  => time() + YEAR_IN_SECONDS,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
			'secure'   => is_ssl(),
			'httponly' => false, // The visit script checks for it.
			'samesite' => 'Lax',
		)
	);
	$_COOKIE[ MMM_COOKIE ] = $token;
}

function mmm_cookie_sub(): int {
	$token = isset( $_COOKIE[ MMM_COOKIE ] ) ? sanitize_key( wp_unslash( $_COOKIE[ MMM_COOKIE ] ) ) : '';
	return $token ? mmm_sub_by_token( $token ) : 0;
}

/* ---------------------------------------------------------------------------
 * Recognise subscribers
 * ------------------------------------------------------------------------- */

// New sign-up through the theme's forms.
add_action(
	'mm_new_subscriber',
	static function ( $email, $id ) {
		mmm_set_cookie( mmm_sub_token( (int) $id ) );
	},
	5,
	2
);

// Someone already on the list signs up again: recognise them, and resubscribe if they had left.
add_filter(
	'rest_post_dispatch',
	static function ( $response, $server, $request ) {
		if ( $request instanceof WP_REST_Request && '/mine-mirth/v1/subscribe' === $request->get_route() && $response instanceof WP_REST_Response && 200 === $response->get_status() ) {
			$id = mmm_sub_by_email( (string) $request->get_param( 'email' ) );
			if ( $id ) {
				if ( 'unsubscribed' === get_post_meta( $id, '_mmm_status', true ) ) {
					mmm_sub_set_status( $id, 'active' );
				}
				mmm_set_cookie( mmm_sub_token( $id ) );
			}
		}
		return $response;
	},
	10,
	3
);

// Logged-in customers who are also subscribers.
add_action(
	'template_redirect',
	static function () {
		if ( ! mmm_opt( 'track_site' ) || ! is_user_logged_in() || ! empty( $_COOKIE[ MMM_COOKIE ] ) ) {
			return;
		}
		$id = mmm_sub_by_email( (string) wp_get_current_user()->user_email );
		if ( $id && mmm_sub_is_active( $id ) ) {
			mmm_set_cookie( mmm_sub_token( $id ) );
		}
	},
	1
);

/* ---------------------------------------------------------------------------
 * Visits
 * ------------------------------------------------------------------------- */

/**
 * What the current page is: [area, object id].
 *
 * @return array{0:string,1:int}
 */
function mmm_page_context(): array {
	if ( is_front_page() ) {
		return array( 'home', 0 );
	}
	if ( function_exists( 'is_product' ) && is_product() ) {
		return array( 'product', (int) get_queried_object_id() );
	}
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() ) ) {
		return array( 'shop', is_product_category() ? (int) get_queried_object_id() : 0 );
	}
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		return array( 'checkout', 0 );
	}
	if ( is_singular( 'post' ) ) {
		return array( 'post', (int) get_queried_object_id() );
	}
	if ( is_home() || is_category() || is_tag() || is_author() || is_date() ) {
		return array( 'blog', is_category() ? (int) get_queried_object_id() : 0 );
	}
	if ( is_page() ) {
		$id   = (int) get_queried_object_id();
		$hay  = strtolower( (string) get_page_template_slug( $id ) . ' ' . get_post_field( 'post_name', $id ) . ' ' . get_the_title( $id ) );
		if ( str_contains( $hay, 'hosting' ) ) {
			return array( 'hosting', $id );
		}
		if ( preg_match( '/calculator|optimizer|optimiser|scope|profitab/', $hay ) ) {
			return array( 'calculator', $id );
		}
		return array( 'page', $id );
	}
	return array( 'page', 0 );
}

add_action(
	'wp_footer',
	static function () {
		if ( ! mmm_opt( 'track_site' ) || is_admin() || is_preview() || is_customize_preview() ) {
			return;
		}
		[ $area, $obj ] = mmm_page_context();
		$endpoint       = esc_url_raw( rest_url( 'mm-mailer/v1/track' ) );
		// Runs only for recognised subscribers (cookie present); everyone else sends nothing.
		printf(
			'<script>(function(){try{if(document.cookie.indexOf(%1$s)<0||!navigator.sendBeacon){return;}var d=new FormData();d.append("a",%2$s);d.append("o",%3$s);d.append("u",location.pathname+location.search);navigator.sendBeacon(%4$s,d);}catch(e){}})();</script>' . "\n",
			wp_json_encode( MMM_COOKIE . '=' ),
			wp_json_encode( $area ),
			wp_json_encode( (string) $obj ),
			wp_json_encode( $endpoint )
		);
	},
	99
);

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'mm-mailer/v1',
			'/track',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $r ) {
					if ( ! mmm_opt( 'track_site' ) ) {
						return new WP_REST_Response( null, 204 );
					}
					$sub = mmm_cookie_sub();
					if ( ! $sub || ! mmm_sub_is_active( $sub ) ) {
						return new WP_REST_Response( null, 204 );
					}
					$area = sanitize_key( (string) $r->get_param( 'a' ) );
					if ( ! in_array( $area, array( 'home', 'product', 'shop', 'checkout', 'post', 'blog', 'hosting', 'calculator', 'page' ), true ) ) {
						return new WP_REST_Response( null, 204 );
					}
					$obj = absint( $r->get_param( 'o' ) );
					// Count the same page once per half hour.
					global $wpdb;
					$dupe = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . mmm_table( 'events' ) . " WHERE sub_id = %d AND type = 'view' AND area = %s AND object_id = %d AND created > %s LIMIT 1", $sub, $area, $obj, gmdate( 'Y-m-d H:i:s', time() - 30 * MINUTE_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB
					if ( ! $dupe ) {
						mmm_event( $sub, 'view', array( 'area' => $area, 'object_id' => $obj, 'url' => substr( esc_url_raw( home_url( (string) $r->get_param( 'u' ) ) ), 0, 255 ) ) );
						update_post_meta( $sub, '_mmm_last_seen', time() );
					}
					return new WP_REST_Response( null, 204 );
				},
			)
		);
	}
);

/* ---------------------------------------------------------------------------
 * Links inside emails
 * ------------------------------------------------------------------------- */

function mmm_link( string $action, int $campaign, string $token, array $extra = array() ): string {
	return add_query_arg( array_merge( array( 'mmm' => $action, 'c' => $campaign, 's' => $token ), $extra ), home_url( '/' ) );
}

function mmm_b64( string $s ): string {
	return rtrim( strtr( base64_encode( $s ), '+/', '-_' ), '=' );
}

function mmm_unb64( string $s ): string {
	return (string) base64_decode( strtr( $s, '-_', '+/' ) );
}

/**
 * Tracked version of a URL. The token is a placeholder filled per recipient.
 */
function mmm_click_url( string $url, int $campaign ): string {
	$u = mmm_b64( $url );
	return add_query_arg( array( 'mmm' => 'c', 'c' => $campaign, 'u' => $u, 'h' => mmm_sign( $campaign . '|' . $url ), 's' => '%%MMM_TOKEN%%' ), home_url( '/' ) );
}

/**
 * Add tracking parameters for analytics to links that stay on this site.
 */
function mmm_utm( string $url, int $campaign ): string {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( wp_parse_url( $url, PHP_URL_HOST ) !== $host ) {
		return $url;
	}
	$slug = $campaign ? (string) get_post_field( 'post_name', $campaign ) : 'email';
	return add_query_arg( array( 'utm_source' => 'newsletter', 'utm_medium' => 'email', 'utm_campaign' => $slug ? $slug : 'email' ), $url );
}

add_action(
	'init',
	static function () {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- signed or token links from emails.
		$action = isset( $_GET['mmm'] ) ? sanitize_key( wp_unslash( $_GET['mmm'] ) ) : '';
		if ( ! in_array( $action, array( 'c', 'o' ), true ) ) {
			return;
		}
		$campaign = absint( wp_unslash( $_GET['c'] ?? 0 ) );
		$token    = sanitize_key( wp_unslash( $_GET['s'] ?? '' ) );
		$sub      = $token ? mmm_sub_by_token( $token ) : 0;

		if ( 'o' === $action ) {
			if ( $sub && mmm_opt( 'track_opens' ) && ! mmm_event_exists( $sub, 'open', $campaign ) ) {
				mmm_event( $sub, 'open', array( 'campaign_id' => $campaign ) );
			}
			nocache_headers();
			header( 'Content-Type: image/gif' );
			echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' ); // phpcs:ignore WordPress.Security.EscapeOutput
			exit;
		}

		$url = mmm_unb64( sanitize_text_field( wp_unslash( $_GET['u'] ?? '' ) ) );
		$sig = sanitize_key( wp_unslash( $_GET['h'] ?? '' ) );
		// phpcs:enable
		if ( ! $url || ! hash_equals( mmm_sign( $campaign . '|' . $url ), $sig ) || ! wp_http_validate_url( $url ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}
		if ( $sub ) {
			if ( mmm_opt( 'track_clicks' ) ) {
				mmm_event( $sub, 'click', array( 'campaign_id' => $campaign, 'url' => $url ) );
				if ( mmm_opt( 'track_opens' ) && ! mmm_event_exists( $sub, 'open', $campaign ) ) {
					mmm_event( $sub, 'open', array( 'campaign_id' => $campaign ) );
				}
			}
			if ( mmm_opt( 'track_site' ) ) {
				mmm_set_cookie( $token );
			}
		}
		// The address was signed when the email was built, so this is not an open redirect.
		wp_redirect( mmm_utm( $url, $campaign ), 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	},
	1
);

/* ---------------------------------------------------------------------------
 * Unsubscribe and "view in browser"
 * ------------------------------------------------------------------------- */

/**
 * A simple page inside the site's own header and footer.
 */
function mmm_site_page( string $title, string $html ): void {
	status_header( 200 );
	nocache_headers();
	add_filter( 'pre_get_document_title', static fn() => $title . ' – ' . get_bloginfo( 'name' ) );
	add_filter( 'wp_robots', static fn( $r ) => array_merge( $r, array( 'noindex' => true, 'nofollow' => true ) ) );
	get_header();
	echo '<main id="mm-main" class="mm-main mmm-page" style="padding:clamp(48px,8vw,110px) 0"><div class="mm-container" style="max-width:720px;margin:0 auto;padding:0 20px">';
	echo '<h1 style="margin:0 0 16px;font-size:clamp(1.8rem,1.3rem + 2vw,2.6rem);line-height:1.1">' . esc_html( $title ) . '</h1>';
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts by callers.
	echo '</div></main>';
	get_footer();
	exit;
}

add_action(
	'template_redirect',
	static function () {
		// phpcs:disable WordPress.Security.NonceVerification -- token links from emails; unsubscribing must work in one click.
		$action = isset( $_GET['mmm'] ) ? sanitize_key( wp_unslash( $_GET['mmm'] ) ) : '';
		if ( ! in_array( $action, array( 'unsub', 'resub', 'view' ), true ) ) {
			return;
		}
		$token    = sanitize_key( wp_unslash( $_GET['s'] ?? '' ) );
		$campaign = absint( wp_unslash( $_GET['c'] ?? 0 ) );
		$sub      = $token ? mmm_sub_by_token( $token ) : 0;
		// phpcs:enable

		if ( 'view' === $action ) {
			$html = $sub ? mmm_render_for( $campaign, $sub ) : '';
			if ( ! $html ) {
				wp_safe_redirect( home_url( '/' ) );
				exit;
			}
			nocache_headers();
			header( 'Content-Type: text/html; charset=utf-8' );
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- full email document built by mmm_render_for().
			exit;
		}

		if ( ! $sub ) {
			mmm_site_page( __( 'Link expired', 'mine-mirth-mailer' ), '<p>' . esc_html__( 'We couldn’t find your subscription. If you keep getting emails, reply to one and we’ll remove you straight away.', 'mine-mirth-mailer' ) . '</p>' );
		}

		$email = mmm_sub_email( $sub );
		if ( 'resub' === $action ) {
			mmm_sub_set_status( $sub, 'active' );
			mmm_site_page( __( 'Welcome back', 'mine-mirth-mailer' ), '<p>' . esc_html( sprintf( /* translators: %s: email */ __( '%s is subscribed again.', 'mine-mirth-mailer' ), $email ) ) . '</p><p><a class="mm-btn mm-btn--primary" href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Back to the site', 'mine-mirth-mailer' ) . '</a></p>' );
		}

		// One-click unsubscribe from mail apps arrives as a POST; a browser visit unsubscribes too.
		if ( mmm_sub_is_active( $sub ) ) {
			mmm_sub_set_status( $sub, 'unsubscribed', $campaign );
		}
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			status_header( 200 );
			exit;
		}
		mmm_site_page(
			__( 'You’re unsubscribed', 'mine-mirth-mailer' ),
			'<p>' . esc_html( sprintf( /* translators: %s: email */ __( '%s won’t receive any more emails from us.', 'mine-mirth-mailer' ), $email ) ) . '</p><p>' . esc_html__( 'Changed your mind?', 'mine-mirth-mailer' ) . ' <a href="' . esc_url( mmm_link( 'resub', $campaign, $token ) ) . '">' . esc_html__( 'Subscribe again', 'mine-mirth-mailer' ) . '</a></p>'
		);
	},
	0
);

// Privacy policy text for Settings › Privacy.
add_action(
	'admin_init',
	static function () {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content(
				'Mine Mirth Mailer',
				'<p>' . esc_html__( 'When you subscribe to our newsletter, or click a link in one of our emails, we set a cookie (mmm_sid) so we can see which of our pages you visit, such as products and articles. We use this only to make our emails relevant to you. Our emails also tell us when they are opened and which links are clicked. You can unsubscribe at any time using the link in every email, which stops all of this.', 'mine-mirth-mailer' ) . '</p>'
			);
		}
	}
);
