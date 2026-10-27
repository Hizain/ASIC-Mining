<?php
/**
 * Subscribers you add by hand (one at a time, a pasted list or a CSV file) and the introduction
 * email they get instead of a welcome email: what Mine Mirth offers and how it can help their
 * mining operation.
 *
 * Manual adds deliberately do NOT fire the theme's "mm_new_subscriber" event, so the sign-up alert,
 * any welcome email and the visitor-tracking cookie (which would land in your own browser) are
 * skipped. "mmm_manual_subscriber" fires instead.
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Introduction email settings
 * ------------------------------------------------------------------------- */

function mmm_intro_defaults(): array {
	return array(
		'enabled'    => 1,
		'subject'    => __( '{first_name|Hello}, here’s how Mine Mirth can help your mining', 'mine-mirth-mailer' ),
		'preheader'  => __( 'ASIC miners, low-cost hosting, free profit tools and expert help, all in one place.', 'mine-mirth-mailer' ),
		'eyebrow'    => __( 'Mine Mirth for miners', 'mine-mirth-mailer' ),
		'headline'   => __( 'Everything your mining operation needs, in one place', 'mine-mirth-mailer' ),
		'intro'      => __( 'Thanks for connecting with us. We work with miners at every stage, from a first machine at home to full container deployments. Here’s how we can help you mine more profitably.', 'mine-mirth-mailer' ),
		'button'     => __( 'Explore miners', 'mine-mirth-mailer' ),
		's1_icon'    => '⛏️',
		's1_title'   => __( 'ASIC miners, ready to ship', 'mine-mirth-mailer' ),
		's1_text'    => __( 'New and tested Antminer, Whatsminer, Avalon and more, with warranty, real specs and live stock.', 'mine-mirth-mailer' ),
		's1_url'     => '',
		's1_link'    => __( 'Shop miners', 'mine-mirth-mailer' ),
		's2_icon'    => '⚡',
		's2_title'   => __( 'Hosting at low power rates', 'mine-mirth-mailer' ),
		's2_text'    => __( 'Plug your machines into partner facilities with 24/7 monitoring. We handle setup, shipping and uptime.', 'mine-mirth-mailer' ),
		's2_url'     => '',
		's2_link'    => __( 'See hosting', 'mine-mirth-mailer' ),
		's3_icon'    => '📈',
		's3_title'   => __( 'Free profitability tools', 'mine-mirth-mailer' ),
		's3_text'    => __( 'Twelve calculators on live Bitcoin network data: profit, hashprice, break-even power, ROI, halving impact and hosting vs home. Model your payback before you buy.', 'mine-mirth-mailer' ),
		's3_url'     => '',
		's3_link'    => __( 'Try the tools', 'mine-mirth-mailer' ),
		's4_icon'    => '🤝',
		's4_title'   => __( 'Expert help, start to finish', 'mine-mirth-mailer' ),
		's4_text'    => __( 'Sizing a farm, power and cooling, repairs and resale. Tell us your plan and we’ll help you get it right.', 'mine-mirth-mailer' ),
		's4_url'     => '',
		's4_link'    => __( 'Ask us anything', 'mine-mirth-mailer' ),
		'products'   => 4,
		'network'    => 1,
		'posts'      => 2,
		'cta_title'  => __( 'Tell us about your operation', 'mine-mirth-mailer' ),
		'cta_text'   => __( 'Reply with how many machines you run (or plan to), your power cost and location. We’ll come back with hardware and hosting options that fit.', 'mine-mirth-mailer' ),
		'cta_button' => __( 'Tell us about your setup', 'mine-mirth-mailer' ),
		'signoff'    => __( 'Happy mining,', 'mine-mirth-mailer' ),
		'signer'     => '',
	);
}

function mmm_intro( string $key ) {
	$saved = get_option( 'mmm_intro', array() );
	$all   = array_merge( mmm_intro_defaults(), is_array( $saved ) ? $saved : array() );
	return $all[ $key ] ?? null;
}

/**
 * The one campaign all introduction emails belong to, so their opens, clicks and replies add up.
 */
function mmm_intro_campaign(): int {
	$id = (int) get_option( 'mmm_intro_campaign' );
	if ( $id && 'mmm_campaign' === get_post_type( $id ) ) {
		return $id;
	}
	$id = (int) wp_insert_post(
		array(
			'post_type'   => 'mmm_campaign',
			'post_status' => 'publish',
			'post_title'  => __( 'Introduction email (subscribers you add)', 'mine-mirth-mailer' ),
		)
	);
	if ( $id ) {
		update_post_meta( $id, '_mmm_kind', 'intro' );
		update_post_meta( $id, '_mmm_state', 'sent' );
		update_option( 'mmm_intro_campaign', $id, false );
	}
	return $id;
}

/**
 * Queue the introduction email. Each person gets it once, ever.
 */
function mmm_intro_send( array $sub_ids ): int {
	$sub_ids = array_filter( array_map( 'intval', $sub_ids ) );
	if ( ! $sub_ids ) {
		return 0;
	}
	$campaign = mmm_intro_campaign();
	return $campaign ? mmm_queue_campaign( $campaign, $sub_ids ) : 0;
}

function mmm_intro_was_sent( int $sub ): bool {
	global $wpdb;
	$campaign = (int) get_option( 'mmm_intro_campaign' );
	return $campaign && (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM ' . mmm_table( 'queue' ) . ' WHERE campaign_id = %d AND sub_id = %d LIMIT 1', $campaign, $sub ) ); // phpcs:ignore WordPress.DB
}

/* ---------------------------------------------------------------------------
 * Introduction email content
 * ------------------------------------------------------------------------- */

/**
 * Service cards, two per row (one per row on phones).
 */
function mmm_c_services( array $items ): string {
	$d     = mmm_design();
	$cards = array();
	foreach ( $items as $it ) {
		if ( '' === trim( (string) $it['title'] ) ) {
			continue;
		}
		$link    = $it['url'] ? '<a href="' . esc_url( $it['url'] ) . '" style="font-family:' . esc_attr( $d['fb'] ) . ';font-size:14px;font-weight:700;color:' . esc_attr( $d['primary'] ) . ';text-decoration:none">' . esc_html( $it['link'] ) . ' &rarr;</a>' : '';
		$cards[] = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;border:1px solid ' . esc_attr( $d['border'] ) . ';border-radius:16px;background:#ffffff"><tr><td style="padding:20px 20px 18px">'
			. '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate"><tr><td width="44" height="44" align="center" valign="middle" style="width:44px;height:44px;border-radius:12px;background:' . esc_attr( $d['soft'] ) . ';font-size:22px;line-height:44px">' . esc_html( $it['icon'] ) . '</td></tr></table>'
			. '<div style="padding-top:12px;font-family:' . esc_attr( $d['fh'] ) . ';font-size:17px;line-height:1.3;font-weight:700;color:' . esc_attr( $d['text'] ) . '">' . esc_html( $it['title'] ) . '</div>'
			. '<div style="padding:6px 0 12px;font-family:' . esc_attr( $d['fb'] ) . ';font-size:14px;line-height:1.6;color:' . esc_attr( $d['muted'] ) . '">' . esc_html( $it['text'] ) . '</div>'
			. $link . '</td></tr></table>';
	}
	if ( ! $cards ) {
		return '';
	}
	$out = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px">';
	foreach ( array_chunk( $cards, 2 ) as $row ) {
		$out .= '<tr>';
		foreach ( $row as $i => $card ) {
			$out .= '<td class="mmm-col" valign="top" width="50%" style="padding:' . ( 0 === $i ? '0 8px 16px 0' : '0 0 16px 8px' ) . '">' . $card . '</td>';
		}
		if ( 1 === count( $row ) ) {
			$out .= '<td class="mmm-col" width="50%" style="padding:0 0 16px 8px">&nbsp;</td>';
		}
		$out .= '</tr>';
	}
	return $out . '</table>';
}

/**
 * Subject, preheader and body of the introduction email for one subscriber.
 *
 * @return array{subject:string,preheader:string,body:string,label:string,focus:string}
 */
function mmm_intro_compose( int $sub ): array {
	$shop  = function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : home_url( '/' );
	$host  = mmm_page_url( 'hosting' );
	$calc  = mmm_builder_page( 'tools' );
	$calc  = $calc ? $calc : mmm_page_url( 'calculator' );
	$auto  = array( 1 => $shop, 2 => $host ? $host : home_url( '/' ), 3 => $calc ? $calc : home_url( '/' ), 4 => '%%MMM_REPLY%%&topic=question' );
	$items = array();
	for ( $i = 1; $i <= 4; $i++ ) {
		$url     = trim( (string) mmm_intro( "s{$i}_url" ) );
		$items[] = array(
			'icon'  => (string) mmm_intro( "s{$i}_icon" ),
			'title' => (string) mmm_intro( "s{$i}_title" ),
			'text'  => (string) mmm_intro( "s{$i}_text" ),
			'link'  => (string) mmm_intro( "s{$i}_link" ),
			'url'   => '' !== $url ? $url : $auto[ $i ],
		);
	}
	$signer = trim( (string) mmm_intro( 'signer' ) );
	$signer = '' !== $signer ? $signer : sprintf( /* translators: %s: site name */ __( 'The %s team', 'mine-mirth-mailer' ), mmm_design()['site'] );

	$body  = mmm_c_text( esc_html__( 'Hi {first_name|there},', 'mine-mirth-mailer' ), 'left', 17 );
	$body .= mmm_c_hero( (string) mmm_intro( 'eyebrow' ), (string) mmm_intro( 'headline' ), (string) mmm_intro( 'intro' ), (string) mmm_intro( 'button' ), $shop );
	$body .= mmm_c_heading( __( 'How we can help', 'mine-mirth-mailer' ), 2, __( 'What we do', 'mine-mirth-mailer' ) );
	$body .= mmm_c_services( $items );
	if ( (int) mmm_intro( 'products' ) > 0 ) {
		$body .= mmm_c_products( mmm_popular_miner_ids( (int) mmm_intro( 'products' ) ), __( 'Popular with miners right now', 'mine-mirth-mailer' ), __( 'In stock', 'mine-mirth-mailer' ) );
	}
	if ( mmm_intro( 'network' ) ) {
		$body .= mmm_c_network();
	}
	if ( (int) mmm_intro( 'posts' ) > 0 ) {
		$body .= mmm_c_posts( mmm_post_ids( 'latest', (int) mmm_intro( 'posts' ) ), __( 'Guides to get you started', 'mine-mirth-mailer' ), __( 'From the blog', 'mine-mirth-mailer' ) );
	}
	$body .= mmm_c_reply( (string) mmm_intro( 'cta_title' ), (string) mmm_intro( 'cta_text' ), (string) mmm_intro( 'cta_button' ), 'quote' );
	$body .= mmm_c_text( esc_html( (string) mmm_intro( 'signoff' ) ) . '<br><strong>' . esc_html( $signer ) . '</strong>' );

	return array(
		'subject'   => (string) mmm_intro( 'subject' ),
		'preheader' => (string) mmm_intro( 'preheader' ),
		'body'      => $body,
		'label'     => __( 'How we can help', 'mine-mirth-mailer' ),
		'focus'     => 'intro',
	);
}

/* ---------------------------------------------------------------------------
 * Adding subscribers
 * ------------------------------------------------------------------------- */

/**
 * Add one subscriber to the theme's list.
 *
 * @param array $d email, first, last, phone, company, source, note.
 * @return array{0:string,1:int} status (added|exists|unsubscribed|invalid|error) and subscriber ID.
 */
function mmm_add_subscriber( array $d ): array {
	$email = strtolower( sanitize_email( (string) ( $d['email'] ?? '' ) ) );
	if ( ! is_email( $email ) ) {
		return array( 'invalid', 0 );
	}
	$fields = array(
		'_mmm_first_name' => sanitize_text_field( (string) ( $d['first'] ?? '' ) ),
		'_mmm_last_name'  => sanitize_text_field( (string) ( $d['last'] ?? '' ) ),
		'_mmm_phone'      => sanitize_text_field( (string) ( $d['phone'] ?? '' ) ),
		'_mmm_company'    => sanitize_text_field( (string) ( $d['company'] ?? '' ) ),
	);
	$id = mmm_sub_by_email( $email );
	if ( $id ) {
		// Already on the list: only fill in details that were missing. Never resubscribe someone who opted out.
		foreach ( $fields as $key => $value ) {
			if ( '' !== $value && '' === (string) get_post_meta( $id, $key, true ) ) {
				update_post_meta( $id, $key, $value );
			}
		}
		return array( mmm_sub_is_active( $id ) ? 'exists' : 'unsubscribed', $id );
	}
	$id = wp_insert_post( array( 'post_type' => 'mm_subscriber', 'post_status' => 'publish', 'post_title' => $email ), true );
	if ( is_wp_error( $id ) || ! $id ) {
		return array( 'error', 0 );
	}
	$id   = (int) $id;
	$user = wp_get_current_user();
	update_post_meta( $id, '_mm_context', 'manual' );
	/* translators: %s: admin name */
	update_post_meta( $id, '_mm_consent', mmm_date( 'Y-m-d H:i T' ) . ' · ' . sprintf( __( 'added by %s', 'mine-mirth-mailer' ), $user->exists() ? $user->display_name : 'admin' ) );
	update_post_meta( $id, '_mmm_source', sanitize_text_field( (string) ( $d['source'] ?? '' ) ) );
	update_post_meta( $id, '_mmm_note', sanitize_textarea_field( (string) ( $d['note'] ?? '' ) ) );
	update_post_meta( $id, '_mmm_added_by', (int) $user->ID );
	foreach ( $fields as $key => $value ) {
		if ( '' !== $value ) {
			update_post_meta( $id, $key, $value );
		}
	}
	mmm_sub_token( $id );
	mmm_event( $id, 'added', array( 'area' => 'manual' ) );
	do_action( 'mmm_manual_subscriber', $email, $id, $d ); // Not "mm_new_subscriber": no welcome email or sign-up alert.
	return array( 'added', $id );
}

/**
 * Turn pasted lines or a CSV file into rows. Accepts "email", "email, first, last, phone, company",
 * "First Last <email>" and CSV files with a header row (email, first name, last name, phone, company).
 *
 * @return array{rows:array,bad:array}
 */
function mmm_parse_people( string $text, string $csv_path = '' ): array {
	$rows = array();
	$bad  = array();
	$lines = array();
	if ( $csv_path && is_readable( $csv_path ) ) {
		$fh = fopen( $csv_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( $fh ) {
			$first = true;
			$map   = array();
			while ( false !== ( $cols = fgetcsv( $fh, 0, ',', '"', '\\' ) ) ) {
				$cols = array_map( static fn( $c ) => trim( (string) preg_replace( '/^\xEF\xBB\xBF/', '', (string) $c ) ), (array) $cols );
				if ( $first ) {
					$first = false;
					foreach ( $cols as $i => $h ) {
						$h = strtolower( (string) preg_replace( '/[^a-z]/i', '', $h ) );
						$k = array( 'email' => 'email', 'emailaddress' => 'email', 'mail' => 'email', 'firstname' => 'first', 'first' => 'first', 'name' => 'first', 'fullname' => 'full', 'lastname' => 'last', 'last' => 'last', 'surname' => 'last', 'phone' => 'phone', 'phonenumber' => 'phone', 'mobile' => 'phone', 'whatsapp' => 'phone', 'company' => 'company', 'business' => 'company', 'organisation' => 'company', 'organization' => 'company' )[ $h ] ?? '';
						if ( $k ) {
							$map[ $k ] = $i;
						}
					}
					if ( isset( $map['email'] ) ) {
						continue; // Header row.
					}
					$map = array();
				}
				if ( $map ) {
					$row = array();
					foreach ( $map as $k => $i ) {
						$row[ $k ] = $cols[ $i ] ?? '';
					}
					if ( ! empty( $row['full'] ) && empty( $row['first'] ) ) {
						$parts        = preg_split( '/\s+/', $row['full'], 2 );
						$row['first'] = $parts[0] ?? '';
						$row['last']  = $row['last'] ?? ( $parts[1] ?? '' );
					}
					$rows[] = $row;
				} else {
					$lines[] = implode( ',', $cols );
				}
			}
			fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
	foreach ( preg_split( '/\r\n|\r|\n/', $text ) as $line ) {
		$lines[] = $line;
	}
	foreach ( $lines as $line ) {
		$line = trim( (string) $line );
		if ( '' === $line ) {
			continue;
		}
		// "First Last <email@x.com>"
		if ( preg_match( '/^"?([^"<]*)"?\s*<([^>]+)>$/', $line, $m ) ) {
			$parts  = preg_split( '/\s+/', trim( $m[1] ), 2 );
			$rows[] = array( 'email' => $m[2], 'first' => $parts[0] ?? '', 'last' => $parts[1] ?? '' );
			continue;
		}
		$cols  = array_map( 'trim', preg_split( '/[,;\t]/', $line ) );
		$email = '';
		$rest  = array();
		foreach ( $cols as $c ) {
			if ( '' === $email && str_contains( $c, '@' ) ) {
				$email = $c;
			} elseif ( '' !== $c ) {
				$rest[] = $c;
			}
		}
		if ( '' === $email ) {
			$bad[] = $line;
			continue;
		}
		$row = array( 'email' => $email );
		foreach ( $rest as $c ) {
			if ( preg_match( '/^\+?[\d\s().-]{7,}$/', $c ) && empty( $row['phone'] ) ) {
				$row['phone'] = $c;
			} elseif ( empty( $row['first'] ) ) {
				$row['first'] = $c;
			} elseif ( empty( $row['last'] ) ) {
				$row['last'] = $c;
			} elseif ( empty( $row['company'] ) ) {
				$row['company'] = $c;
			}
		}
		$rows[] = $row;
	}
	return array( 'rows' => $rows, 'bad' => $bad );
}

add_action(
	'admin_post_mmm_add_subs',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-mailer' ) );
		}
		check_admin_referer( 'mmm_add_subs' );
		if ( empty( $_POST['consent'] ) ) {
			wp_safe_redirect( mmm_admin_url( 'mmm-add', array( 'err' => 'consent' ) ) );
			exit;
		}
		$source = sanitize_text_field( wp_unslash( $_POST['source'] ?? '' ) );
		$people = array();
		$bad    = array();
		if ( 'single' === sanitize_key( wp_unslash( $_POST['mode'] ?? '' ) ) ) {
			$people[] = array(
				'email'   => wp_unslash( $_POST['email'] ?? '' ),
				'first'   => wp_unslash( $_POST['first'] ?? '' ),
				'last'    => wp_unslash( $_POST['last'] ?? '' ),
				'phone'   => wp_unslash( $_POST['phone'] ?? '' ),
				'company' => wp_unslash( $_POST['company'] ?? '' ),
				'note'    => wp_unslash( $_POST['note'] ?? '' ),
			);
		} else {
			$file   = isset( $_FILES['csv']['tmp_name'] ) && is_uploaded_file( $_FILES['csv']['tmp_name'] ) ? (string) $_FILES['csv']['tmp_name'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$parsed = mmm_parse_people( (string) wp_unslash( $_POST['list'] ?? '' ), $file );
			// The same address twice in one list counts once (keep the line with the most details).
			$unique = array();
			foreach ( $parsed['rows'] as $row ) {
				$key = strtolower( trim( (string) ( $row['email'] ?? '' ) ) );
				if ( ! isset( $unique[ $key ] ) || count( array_filter( $row ) ) > count( array_filter( $unique[ $key ] ) ) ) {
					$unique[ $key ] = $row;
				}
			}
			$people = array_slice( array_values( $unique ), 0, 5000 );
			$bad    = $parsed['bad'];
		}
		$result = array( 'added' => array(), 'exists' => array(), 'unsubscribed' => array(), 'invalid' => $bad, 'error' => array(), 'queued' => 0 );
		$new    = array();
		foreach ( $people as $p ) {
			$p['source']     = $source;
			[ $status, $id ] = mmm_add_subscriber( $p );
			$result[ $status ][] = 'invalid' === $status ? sanitize_text_field( (string) ( $p['email'] ?? '' ) ) : mmm_sub_email( $id );
			if ( 'added' === $status ) {
				$new[] = $id;
			}
		}
		if ( $new && ! empty( $_POST['intro'] ) && mmm_intro( 'enabled' ) ) {
			$result['queued'] = mmm_intro_send( $new );
		}
		update_user_meta( get_current_user_id(), '_mmm_add_result', $result );
		wp_safe_redirect( mmm_admin_url( 'mmm-add', array( 'done' => 1 ) ) );
		exit;
	}
);

// Send the introduction email to one existing subscriber (from their profile).
add_action(
	'admin_post_mmm_intro_one',
	static function () {
		$sub = absint( wp_unslash( $_GET['sub'] ?? 0 ) );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-mailer' ) );
		}
		check_admin_referer( 'mmm_intro_one_' . $sub );
		$n = mmm_sub_is_active( $sub ) ? mmm_intro_send( array( $sub ) ) : 0;
		wp_safe_redirect( mmm_admin_url( 'mmm-subscribers', array( 'sub' => $sub, 'intro' => $n ? 'queued' : 'no' ) ) );
		exit;
	}
);

// Save name and contact details from the subscriber profile.
add_action(
	'admin_post_mmm_sub_details',
	static function () {
		$sub = absint( wp_unslash( $_POST['sub'] ?? 0 ) );
		if ( ! current_user_can( 'manage_options' ) || 'mm_subscriber' !== get_post_type( $sub ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-mailer' ) );
		}
		check_admin_referer( 'mmm_sub_details_' . $sub );
		foreach ( array( 'first' => '_mmm_first_name', 'last' => '_mmm_last_name', 'phone' => '_mmm_phone', 'company' => '_mmm_company', 'source' => '_mmm_source' ) as $field => $key ) {
			update_post_meta( $sub, $key, sanitize_text_field( wp_unslash( $_POST[ $field ] ?? '' ) ) );
		}
		update_post_meta( $sub, '_mmm_note', sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ) );
		wp_safe_redirect( mmm_admin_url( 'mmm-subscribers', array( 'sub' => $sub, 'saved' => 1 ) ) );
		exit;
	}
);

add_action(
	'admin_init',
	static function () {
		register_setting(
			'mmm_intro',
			'mmm_intro',
			array(
				'sanitize_callback' => static function ( $in ) {
					$in  = is_array( $in ) ? $in : array();
					$out = array();
					foreach ( mmm_intro_defaults() as $k => $def ) {
						if ( in_array( $k, array( 'enabled', 'network' ), true ) ) {
							$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
						} elseif ( in_array( $k, array( 'products', 'posts' ), true ) ) {
							$out[ $k ] = max( 0, min( 'products' === $k ? 6 : 4, (int) ( $in[ $k ] ?? $def ) ) );
						} elseif ( str_ends_with( $k, '_url' ) ) {
							$out[ $k ] = esc_url_raw( trim( (string) wp_unslash( $in[ $k ] ?? '' ) ) );
						} elseif ( in_array( $k, array( 'intro', 'cta_text' ), true ) || str_ends_with( $k, '_text' ) ) {
							$out[ $k ] = sanitize_textarea_field( wp_unslash( $in[ $k ] ?? $def ) );
						} else {
							$out[ $k ] = sanitize_text_field( wp_unslash( $in[ $k ] ?? $def ) );
						}
					}
					return $out;
				},
			)
		);
	}
);

/* ---------------------------------------------------------------------------
 * Admin pages
 * ------------------------------------------------------------------------- */

function mmm_add_page(): void {
	$done   = isset( $_GET['done'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$err    = sanitize_key( wp_unslash( $_GET['err'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$result = $done ? get_user_meta( get_current_user_id(), '_mmm_add_result', true ) : null;
	$intro  = (bool) mmm_intro( 'enabled' );
	?>
	<div class="wrap mmm-admin">
		<h1 class="mmm-title"><?php esc_html_e( 'Add subscribers', 'mine-mirth-mailer' ); ?> <a class="page-title-action" href="<?php echo esc_url( mmm_admin_url( 'mmm-subscribers' ) ); ?>"><?php esc_html_e( 'All subscribers', 'mine-mirth-mailer' ); ?></a></h1>
		<p class="mmm-lead"><?php esc_html_e( 'People you add here join your newsletter list like a website sign-up, but they don’t get a welcome email or trigger a sign-up alert. Instead they get the introduction email: what Mine Mirth offers and how you can help their mining operation. After that they receive your weekly email and campaigns like everyone else.', 'mine-mirth-mailer' ); ?></p>

		<?php if ( 'consent' === $err ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'Please confirm these people agreed to hear from you.', 'mine-mirth-mailer' ); ?></p></div>
		<?php endif; ?>
		<?php
		if ( is_array( $result ) ) :
			$list = static fn( array $a ) => $a ? ' <span class="description">' . esc_html( implode( ', ', array_slice( $a, 0, 12 ) ) . ( count( $a ) > 12 ? ' …' : '' ) ) . '</span>' : '';
			?>
			<div class="mmm-card mmm-result">
				<h2><?php esc_html_e( 'Done', 'mine-mirth-mailer' ); ?></h2>
				<ul class="mmm-check">
					<li class="is-done"><strong><?php echo esc_html( sprintf( /* translators: %d: count */ _n( '%d subscriber added', '%d subscribers added', count( $result['added'] ), 'mine-mirth-mailer' ), count( $result['added'] ) ) ); ?></strong><?php echo $list( $result['added'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
					<?php if ( $result['queued'] ) : ?>
						<li class="is-done"><?php echo esc_html( sprintf( /* translators: %d: count */ _n( 'Introduction email is on its way to %d person.', 'Introduction email is on its way to %d people.', $result['queued'], 'mine-mirth-mailer' ), $result['queued'] ) ); ?> <a href="<?php echo esc_url( mmm_admin_url( 'mmm-send', array( 'id' => mmm_intro_campaign() ) ) ); ?>"><?php esc_html_e( 'Track it', 'mine-mirth-mailer' ); ?></a></li>
					<?php endif; ?>
					<?php if ( $result['exists'] ) : ?>
						<li><?php echo esc_html( sprintf( /* translators: %d: count */ _n( '%d was already subscribed (no email sent, missing details filled in).', '%d were already subscribed (no email sent, missing details filled in).', count( $result['exists'] ), 'mine-mirth-mailer' ), count( $result['exists'] ) ) ); ?><?php echo $list( $result['exists'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
					<?php endif; ?>
					<?php if ( $result['unsubscribed'] ) : ?>
						<li><?php echo esc_html( sprintf( /* translators: %d: count */ _n( '%d had unsubscribed before and was left unsubscribed.', '%d had unsubscribed before and were left unsubscribed.', count( $result['unsubscribed'] ), 'mine-mirth-mailer' ), count( $result['unsubscribed'] ) ) ); ?><?php echo $list( $result['unsubscribed'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
					<?php endif; ?>
					<?php if ( $result['invalid'] ) : ?>
						<li><?php echo esc_html( sprintf( /* translators: %d: count */ _n( '%d line had no valid email address and was skipped.', '%d lines had no valid email address and were skipped.', count( $result['invalid'] ), 'mine-mirth-mailer' ), count( $result['invalid'] ) ) ); ?><?php echo $list( $result['invalid'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
					<?php endif; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div class="mmm-grid">
			<form class="mmm-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<h2><?php esc_html_e( 'One person', 'mine-mirth-mailer' ); ?></h2>
				<?php wp_nonce_field( 'mmm_add_subs' ); ?>
				<input type="hidden" name="action" value="mmm_add_subs"><input type="hidden" name="mode" value="single">
				<div class="mmm-fields">
					<label class="is-wide"><span><?php esc_html_e( 'Email', 'mine-mirth-mailer' ); ?> <span class="required">*</span></span><input type="email" name="email" required autocomplete="off"></label>
					<label><?php esc_html_e( 'First name', 'mine-mirth-mailer' ); ?><input type="text" name="first"></label>
					<label><?php esc_html_e( 'Last name', 'mine-mirth-mailer' ); ?><input type="text" name="last"></label>
					<label><?php esc_html_e( 'Phone / WhatsApp', 'mine-mirth-mailer' ); ?><input type="text" name="phone"></label>
					<label><?php esc_html_e( 'Company', 'mine-mirth-mailer' ); ?><input type="text" name="company"></label>
					<label class="is-wide"><?php esc_html_e( 'Where you met them', 'mine-mirth-mailer' ); ?><input type="text" name="source" placeholder="<?php esc_attr_e( 'e.g. WhatsApp, trade show, phone call', 'mine-mirth-mailer' ); ?>"></label>
					<label class="is-wide"><?php esc_html_e( 'Note (only you see this)', 'mine-mirth-mailer' ); ?><textarea name="note" rows="2" placeholder="<?php esc_attr_e( 'e.g. runs 40 S19s in Texas, wants hosting quote', 'mine-mirth-mailer' ); ?>"></textarea></label>
				</div>
				<?php mmm_add_form_footer( $intro ); ?>
				<p><button class="button button-primary button-large"><?php esc_html_e( 'Add subscriber', 'mine-mirth-mailer' ); ?></button></p>
			</form>

			<form class="mmm-card" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<h2><?php esc_html_e( 'Many at once', 'mine-mirth-mailer' ); ?></h2>
				<?php wp_nonce_field( 'mmm_add_subs' ); ?>
				<input type="hidden" name="action" value="mmm_add_subs"><input type="hidden" name="mode" value="bulk">
				<label class="mmm-block-label"><?php esc_html_e( 'Paste emails, one person per line', 'mine-mirth-mailer' ); ?>
					<textarea name="list" rows="8" class="large-text code" placeholder="ali@example.com&#10;sara@example.com, Sara, Khan, +92 300 1234567&#10;John Smith &lt;john@example.com&gt;"></textarea></label>
				<p class="description"><?php esc_html_e( 'Each line: email, first name, last name, phone, company (only the email is required). Duplicates and people already on your list are skipped.', 'mine-mirth-mailer' ); ?></p>
				<label class="mmm-block-label"><?php esc_html_e( 'Or upload a CSV file', 'mine-mirth-mailer' ); ?> <input type="file" name="csv" accept=".csv,text/csv"></label>
				<p class="description"><?php esc_html_e( 'With a header row such as: email, first name, last name, phone, company. Exports from Gmail, Outlook, WhatsApp Business or WooCommerce work.', 'mine-mirth-mailer' ); ?></p>
				<label class="mmm-block-label"><?php esc_html_e( 'Where these came from', 'mine-mirth-mailer' ); ?> <input type="text" name="source" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Mining expo 2026', 'mine-mirth-mailer' ); ?>"></label>
				<?php mmm_add_form_footer( $intro ); ?>
				<p><button class="button button-primary button-large"><?php esc_html_e( 'Add subscribers', 'mine-mirth-mailer' ); ?></button></p>
			</form>
		</div>
	</div>
	<?php
}

function mmm_add_form_footer( bool $intro ): void {
	?>
	<div class="mmm-add-footer">
		<label><input type="checkbox" name="consent" value="1" required> <?php esc_html_e( 'These people agreed to hear from us (for example customers, or contacts who asked for information).', 'mine-mirth-mailer' ); ?></label>
		<?php if ( $intro ) : ?>
			<label><input type="checkbox" name="intro" value="1" checked> <?php echo wp_kses( sprintf( /* translators: %s: link */ __( 'Send them the introduction email (%s)', 'mine-mirth-mailer' ), '<a href="' . esc_url( mmm_admin_url( 'mmm-intro' ) ) . '" target="_blank">' . esc_html__( 'preview', 'mine-mirth-mailer' ) . '</a>' ), array( 'a' => array( 'href' => true, 'target' => true ) ) ); ?></label>
		<?php else : ?>
			<p class="description"><?php echo wp_kses( sprintf( /* translators: %s: link */ __( 'The introduction email is turned off (%s), so nothing is sent now.', 'mine-mirth-mailer' ), '<a href="' . esc_url( mmm_admin_url( 'mmm-intro' ) ) . '">' . esc_html__( 'settings', 'mine-mirth-mailer' ) . '</a>' ), array( 'a' => array( 'href' => true ) ) ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

function mmm_intro_page(): void {
	$campaign = mmm_intro_campaign();
	$stats    = $campaign ? mmm_campaign_stats( $campaign ) : array();
	$preview  = $campaign ? wp_nonce_url( admin_url( 'admin-post.php?action=mmm_preview&id=' . $campaign ), 'mmm_preview_' . $campaign ) : '';
	$f        = static fn( $k ) => mmm_intro( $k );
	$name     = static fn( $k ) => 'mmm_intro[' . $k . ']';
	?>
	<div class="wrap mmm-admin">
		<h1 class="mmm-title"><?php esc_html_e( 'Introduction email', 'mine-mirth-mailer' ); ?> <a class="page-title-action" href="<?php echo esc_url( mmm_admin_url( 'mmm-add' ) ); ?>"><?php esc_html_e( 'Add subscribers', 'mine-mirth-mailer' ); ?></a></h1>
		<p class="mmm-lead"><?php esc_html_e( 'Sent once to each person you add by hand, instead of a welcome email. It introduces what you offer and invites them to tell you about their mining operation; replies land in Mailer › Inbox.', 'mine-mirth-mailer' ); ?></p>
		<?php
		$mmm_test = sanitize_key( wp_unslash( $_GET['test'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'test_ok' === $mmm_test ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Test email sent. Check your inbox (and the spam folder).', 'mine-mirth-mailer' ) . '</p></div>';
		} elseif ( 'test_fail' === $mmm_test ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The test email could not be sent:', 'mine-mirth-mailer' ) . ' ' . esc_html( (string) get_option( 'mmm_last_test_error' ) ) . '</p></div>';
		}
		?>
		<?php if ( isset( $_GET['settings-updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved. The preview shows the new version.', 'mine-mirth-mailer' ); ?></p></div>
		<?php endif; ?>

		<?php if ( $stats ) : ?>
			<div class="mmm-stats">
				<?php
				$pct = static fn( $n ) => $stats['sent'] ? round( 100 * $n / $stats['sent'] ) . '%' : '—';
				foreach ( array(
					array( __( 'Sent', 'mine-mirth-mailer' ), number_format_i18n( $stats['sent'] ), $stats['pending'] ? sprintf( /* translators: %d: count */ __( '%d on the way', 'mine-mirth-mailer' ), $stats['pending'] ) : '' ),
					array( __( 'Opened', 'mine-mirth-mailer' ), $pct( $stats['opens'] ), '' ),
					array( __( 'Clicked', 'mine-mirth-mailer' ), $pct( $stats['clickers'] ), '' ),
					array( __( 'Replies', 'mine-mirth-mailer' ), number_format_i18n( $stats['replies'] ), '' ),
				) as $s ) :
					?>
					<div><small><?php echo esc_html( $s[0] ); ?></small><strong><?php echo esc_html( (string) $s[1] ); ?></strong><span><?php echo esc_html( (string) $s[2] ); ?></span></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="mmm-grid mmm-grid--preview">
			<form class="mmm-card" method="post" action="options.php">
				<?php settings_fields( 'mmm_intro' ); ?>
				<p><label class="mmm-switch"><input type="checkbox" name="<?php echo esc_attr( $name( 'enabled' ) ); ?>" value="1" <?php checked( (int) $f( 'enabled' ), 1 ); ?>> <strong><?php esc_html_e( 'Send the introduction email to people I add', 'mine-mirth-mailer' ); ?></strong></label></p>
				<div class="mmm-fields">
					<label class="is-wide"><?php esc_html_e( 'Subject', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'subject' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'subject' ) ); ?>"></label>
					<label class="is-wide"><?php esc_html_e( 'Preview text (shown after the subject in the inbox)', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'preheader' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'preheader' ) ); ?>"></label>
					<label><?php esc_html_e( 'Small label', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'eyebrow' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'eyebrow' ) ); ?>"></label>
					<label><?php esc_html_e( 'Button', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'button' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'button' ) ); ?>"></label>
					<label class="is-wide"><?php esc_html_e( 'Headline', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'headline' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'headline' ) ); ?>"></label>
					<label class="is-wide"><?php esc_html_e( 'Opening text', 'mine-mirth-mailer' ); ?><textarea rows="3" name="<?php echo esc_attr( $name( 'intro' ) ); ?>"><?php echo esc_textarea( (string) $f( 'intro' ) ); ?></textarea></label>
				</div>
				<p class="description"><?php esc_html_e( 'Use {first_name} or {first_name|fallback} anywhere.', 'mine-mirth-mailer' ); ?></p>

				<h2><?php esc_html_e( 'What you offer (4 cards)', 'mine-mirth-mailer' ); ?></h2>
				<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
					<div class="mmm-fields mmm-service">
						<label class="is-icon"><?php esc_html_e( 'Icon', 'mine-mirth-mailer' ); ?><input type="text" maxlength="4" name="<?php echo esc_attr( $name( "s{$i}_icon" ) ); ?>" value="<?php echo esc_attr( (string) $f( "s{$i}_icon" ) ); ?>"></label>
						<label class="is-title"><?php esc_html_e( 'Title', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( "s{$i}_title" ) ); ?>" value="<?php echo esc_attr( (string) $f( "s{$i}_title" ) ); ?>"></label>
						<label class="is-wide"><?php esc_html_e( 'Text', 'mine-mirth-mailer' ); ?><textarea rows="2" name="<?php echo esc_attr( $name( "s{$i}_text" ) ); ?>"><?php echo esc_textarea( (string) $f( "s{$i}_text" ) ); ?></textarea></label>
						<label class="is-link"><?php esc_html_e( 'Link text', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( "s{$i}_link" ) ); ?>" value="<?php echo esc_attr( (string) $f( "s{$i}_link" ) ); ?>"></label>
						<label class="is-url"><?php esc_html_e( 'Link (empty = automatic)', 'mine-mirth-mailer' ); ?><input type="url" name="<?php echo esc_attr( $name( "s{$i}_url" ) ); ?>" value="<?php echo esc_attr( (string) $f( "s{$i}_url" ) ); ?>" placeholder="<?php echo esc_attr( array( 1 => __( 'Shop page', 'mine-mirth-mailer' ), 2 => __( 'Hosting page', 'mine-mirth-mailer' ), 3 => __( 'Free tools page', 'mine-mirth-mailer' ), 4 => __( 'Reply form', 'mine-mirth-mailer' ) )[ $i ] ); ?>"></label>
					</div>
				<?php endfor; ?>

				<h2><?php esc_html_e( 'Also include', 'mine-mirth-mailer' ); ?></h2>
				<p>
					<label><?php esc_html_e( 'Popular miners', 'mine-mirth-mailer' ); ?> <input type="number" min="0" max="6" class="small-text" name="<?php echo esc_attr( $name( 'products' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'products' ) ); ?>"></label> &nbsp;
					<label><?php esc_html_e( 'Blog guides', 'mine-mirth-mailer' ); ?> <input type="number" min="0" max="4" class="small-text" name="<?php echo esc_attr( $name( 'posts' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'posts' ) ); ?>"></label> &nbsp;
					<label><input type="checkbox" name="<?php echo esc_attr( $name( 'network' ) ); ?>" value="1" <?php checked( (int) $f( 'network' ), 1 ); ?>> <?php esc_html_e( 'Bitcoin network snapshot', 'mine-mirth-mailer' ); ?></label>
				</p>

				<h2><?php esc_html_e( 'Closing', 'mine-mirth-mailer' ); ?></h2>
				<div class="mmm-fields">
					<label class="is-wide"><?php esc_html_e( 'Reply box headline', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'cta_title' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'cta_title' ) ); ?>"></label>
					<label class="is-wide"><?php esc_html_e( 'Reply box text', 'mine-mirth-mailer' ); ?><textarea rows="2" name="<?php echo esc_attr( $name( 'cta_text' ) ); ?>"><?php echo esc_textarea( (string) $f( 'cta_text' ) ); ?></textarea></label>
					<label><?php esc_html_e( 'Reply button', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'cta_button' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'cta_button' ) ); ?>"></label>
					<label><?php esc_html_e( 'Sign-off', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'signoff' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'signoff' ) ); ?>"></label>
					<label class="is-wide"><?php esc_html_e( 'Signed by (empty = “The Mine Mirth team”)', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'signer' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'signer' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Zain, Mine Mirth', 'mine-mirth-mailer' ); ?>"></label>
				</div>
				<?php submit_button( __( 'Save', 'mine-mirth-mailer' ) ); ?>
			</form>

			<div class="mmm-card">
				<h2><?php esc_html_e( 'Preview', 'mine-mirth-mailer' ); ?></h2>
				<?php if ( $preview ) : ?>
					<iframe class="mmm-frame" src="<?php echo esc_url( $preview ); ?>" title="<?php esc_attr_e( 'Introduction email preview', 'mine-mirth-mailer' ); ?>"></iframe>
					<p><a target="_blank" href="<?php echo esc_url( $preview ); ?>"><?php esc_html_e( 'Open in a new tab', 'mine-mirth-mailer' ); ?></a> · <a href="<?php echo esc_url( mmm_admin_url( 'mmm-send', array( 'id' => $campaign ) ) ); ?>"><?php esc_html_e( 'Results and recipients', 'mine-mirth-mailer' ); ?></a></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mmm-test">
						<?php wp_nonce_field( 'mmm_send_' . $campaign ); ?>
						<input type="hidden" name="action" value="mmm_send"><input type="hidden" name="id" value="<?php echo esc_attr( (string) $campaign ); ?>"><input type="hidden" name="do" value="test"><input type="hidden" name="back" value="mmm-intro">
						<input type="email" name="test_to" class="regular-text" value="<?php echo esc_attr( (string) wp_get_current_user()->user_email ); ?>" required>
						<button class="button"><?php esc_html_e( 'Send me a test', 'mine-mirth-mailer' ); ?></button>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Where a subscriber came from, in plain words.
 */
function mmm_sub_source_label( int $sub ): string {
	$context = (string) get_post_meta( $sub, '_mm_context', true );
	if ( 'manual' === $context ) {
		$src = (string) get_post_meta( $sub, '_mmm_source', true );
		/* translators: %s: where you met them */
		return $src ? sprintf( __( 'Added by you · %s', 'mine-mirth-mailer' ), $src ) : __( 'Added by you', 'mine-mirth-mailer' );
	}
	$map = array(
		'price-alert'  => __( 'Price alert sign-up', 'mine-mirth-mailer' ),
		'footer'       => __( 'Website · footer form', 'mine-mirth-mailer' ),
		'home'         => __( 'Website · homepage', 'mine-mirth-mailer' ),
		'article'      => __( 'Website · blog article', 'mine-mirth-mailer' ),
		'promo-inline' => __( 'Website · article promo', 'mine-mirth-mailer' ),
	);
	return $map[ $context ] ?? ( $context ? sprintf( /* translators: %s: form */ __( 'Website · %s', 'mine-mirth-mailer' ), $context ) : __( 'Website', 'mine-mirth-mailer' ) );
}

/**
 * Details card on the subscriber profile: name, contact, source, note and the introduction email.
 */
function mmm_sub_details_card( int $sub ): void {
	$m     = static fn( $k ) => (string) get_post_meta( $sub, $k, true );
	$sent  = mmm_intro_was_sent( $sub );
	$state = sanitize_key( wp_unslash( $_GET['intro'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Details saved.', 'mine-mirth-mailer' ) . '</p></div>';
	}
	if ( 'queued' === $state ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'The introduction email is on its way.', 'mine-mirth-mailer' ) . '</p></div>';
	} elseif ( 'no' === $state ) {
		echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Not sent: they already got it, or they are unsubscribed.', 'mine-mirth-mailer' ) . '</p></div>';
	}
	?>
	<form class="mmm-card mmm-details" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<h2><?php esc_html_e( 'Details', 'mine-mirth-mailer' ); ?> <span class="mmm-pill"><?php echo esc_html( mmm_sub_source_label( $sub ) ); ?></span></h2>
		<?php wp_nonce_field( 'mmm_sub_details_' . $sub ); ?>
		<input type="hidden" name="action" value="mmm_sub_details"><input type="hidden" name="sub" value="<?php echo esc_attr( (string) $sub ); ?>">
		<div class="mmm-fields">
			<label><?php esc_html_e( 'First name', 'mine-mirth-mailer' ); ?><input type="text" name="first" value="<?php echo esc_attr( $m( '_mmm_first_name' ) ); ?>"></label>
			<label><?php esc_html_e( 'Last name', 'mine-mirth-mailer' ); ?><input type="text" name="last" value="<?php echo esc_attr( $m( '_mmm_last_name' ) ); ?>"></label>
			<label><?php esc_html_e( 'Phone / WhatsApp', 'mine-mirth-mailer' ); ?><input type="text" name="phone" value="<?php echo esc_attr( $m( '_mmm_phone' ) ); ?>"></label>
			<label><?php esc_html_e( 'Company', 'mine-mirth-mailer' ); ?><input type="text" name="company" value="<?php echo esc_attr( $m( '_mmm_company' ) ); ?>"></label>
			<label class="is-wide"><?php esc_html_e( 'Where you met them', 'mine-mirth-mailer' ); ?><input type="text" name="source" value="<?php echo esc_attr( $m( '_mmm_source' ) ); ?>"></label>
			<label class="is-wide"><?php esc_html_e( 'Note (only you see this)', 'mine-mirth-mailer' ); ?><textarea name="note" rows="2"><?php echo esc_textarea( $m( '_mmm_note' ) ); ?></textarea></label>
		</div>
		<p class="mmm-actions">
			<button class="button"><?php esc_html_e( 'Save details', 'mine-mirth-mailer' ); ?></button>
			<?php if ( $sent ) : ?>
				<span class="mmm-pill is-ok"><?php esc_html_e( 'Introduction email sent', 'mine-mirth-mailer' ); ?></span>
			<?php elseif ( mmm_sub_is_active( $sub ) ) : ?>
				<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mmm_intro_one&sub=' . $sub ), 'mmm_intro_one_' . $sub ) ); ?>"><?php esc_html_e( 'Send the introduction email', 'mine-mirth-mailer' ); ?></a>
			<?php endif; ?>
		</p>
	</form>
	<?php
}
