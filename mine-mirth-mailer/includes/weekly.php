<?php
/**
 * The weekly email, built separately for each subscriber from what they did
 * on the site in the last 30 days:
 *   shop visitors   → miners they looked at, similar miners, a quote prompt
 *   blog readers    → articles they haven't read in the topics they read
 *   tool users      → network numbers, hosting and calculator
 *   everyone else   → what's new: miners, articles, hosting
 * Customers who already bought get hosting offered instead of the same miner.
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

function mmm_weekly_defaults(): array {
	return array(
		'enabled'  => 0,
		'day'      => 2,
		'time'     => '09:00',
		'subject'  => '',
		'intro'    => '',
		'network'  => 1,
		'products' => 4,
		'posts'    => 3,
	);
}

function mmm_weekly( string $key ) {
	$all = get_option( 'mmm_weekly', array() );
	$def = mmm_weekly_defaults();
	return is_array( $all ) && array_key_exists( $key, $all ) ? $all[ $key ] : ( $def[ $key ] ?? null );
}

/**
 * URL of the first page using one of the theme's page templates, or a matching slug.
 */
function mmm_page_url( string $kind ): string {
	$template = array( 'hosting' => 'page-templates/hosting.php', 'calculator' => 'page-templates/calculator.php' )[ $kind ] ?? '';
	if ( $template ) {
		$pages = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => $template, 'number' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $pages ) {
			return (string) get_permalink( $pages[0] );
		}
	}
	foreach ( get_pages( array( 'number' => 200 ) ) as $page ) {
		if ( str_contains( $page->post_name, $kind ) ) {
			return (string) get_permalink( $page );
		}
	}
	return 'calculator' === $kind && function_exists( 'wc_get_page_permalink' ) ? home_url( '/' ) : home_url( '/' );
}

/**
 * Miners similar to what someone looked at: same categories, in stock first.
 *
 * @return int[]
 */
function mmm_similar_products( array $cat_ids, array $exclude, int $count ): array {
	if ( ! function_exists( 'wc_get_products' ) || $count < 1 ) {
		return array();
	}
	$slugs = array();
	foreach ( $cat_ids as $tid ) {
		$t = get_term( (int) $tid, 'product_cat' );
		if ( $t && ! is_wp_error( $t ) ) {
			$slugs[] = $t->slug;
		}
	}
	$out = array();
	foreach ( array( 'instock', 'onbackorder' ) as $stock ) {
		if ( count( $out ) >= $count ) {
			break;
		}
		$args = array( 'status' => 'publish', 'visibility' => 'catalog', 'limit' => $count - count( $out ), 'return' => 'ids', 'exclude' => array_merge( $exclude, $out ), 'stock_status' => $stock, 'orderby' => 'date', 'order' => 'DESC' );
		if ( $slugs ) {
			$args['category'] = $slugs;
		}
		$out = array_merge( $out, array_map( 'intval', wc_get_products( $args ) ) );
	}
	return $out;
}

/**
 * Articles in the topics someone reads, skipping what they've read.
 *
 * @return int[]
 */
function mmm_reading_list( array $cat_ids, array $read, int $count ): array {
	$args = array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => $count, 'fields' => 'ids', 'ignore_sticky_posts' => true, 'post__not_in' => $read );
	$ids  = array();
	if ( $cat_ids ) {
		$ids = get_posts( $args + array( 'category__in' => $cat_ids ) );
	}
	if ( count( $ids ) < $count ) {
		$ids = array_merge( $ids, get_posts( array_merge( $args, array( 'posts_per_page' => $count - count( $ids ), 'post__not_in' => array_merge( $read, $ids ) ) ) ) );
	}
	return array_map( 'intval', $ids );
}

/**
 * Subject, preview text and body for one subscriber.
 *
 * @return array{subject:string,preheader:string,body:string,focus:string}
 */
function mmm_weekly_compose( int $sub ): array {
	$p      = mmm_profile( $sub );
	$focus  = $p['top'];
	$n_prod = max( 2, (int) mmm_weekly( 'products' ) );
	$n_post = max( 1, (int) mmm_weekly( 'posts' ) );
	$bought = $p['purchases'];
	$viewed = array_values( array_diff( $p['products'], $bought ) );
	$first  = '{first_name|there}';
	$sec    = array();

	$viewed_cards = $viewed ? mmm_c_products( array_slice( $viewed, 0, 2 ), __( 'Miners you looked at', 'mine-mirth-mailer' ), __( 'Still available', 'mine-mirth-mailer' ) ) : '';
	$similar      = static fn( int $n, string $title ) => mmm_c_products( mmm_similar_products( $p['product_cats'], array_merge( $viewed, $bought ), $n ), $title, __( 'Picked for you', 'mine-mirth-mailer' ) );
	$new_in_shop  = static fn( int $n, string $title ) => mmm_c_products( mmm_similar_products( array(), $bought, $n ), $title, __( 'New in the shop', 'mine-mirth-mailer' ) );
	$reads        = static fn( int $n, string $title ) => mmm_c_posts( mmm_reading_list( $p['post_cats'], $p['posts'], $n ), $title, __( 'Mining Academy', 'mine-mirth-mailer' ) );
	$network      = mmm_weekly( 'network' ) ? mmm_c_network() : '';
	$calc_cta     = mmm_c_cta( __( 'Run the numbers before you buy', 'mine-mirth-mailer' ), __( 'Profit, power cost and payback for any miner, using today’s Bitcoin price and network difficulty.', 'mine-mirth-mailer' ), __( 'Open the calculator', 'mine-mirth-mailer' ), mmm_page_url( 'calculator' ), __( 'Free tool', 'mine-mirth-mailer' ) );
	$host_cta     = mmm_c_cta( __( 'Need somewhere to run your miners?', 'mine-mirth-mailer' ), __( 'Partner facilities with published rates, setup and monitoring, so your machines earn from day one.', 'mine-mirth-mailer' ), __( 'Explore hosting', 'mine-mirth-mailer' ), mmm_page_url( 'hosting' ), __( 'Hosting', 'mine-mirth-mailer' ) );

	// Lead product name for the subject line.
	$lead = $viewed ? get_the_title( $viewed[0] ) : '';

	switch ( $focus ) {
		case 'shop':
			$subject = $lead
				/* translators: %s: product name */
				? sprintf( __( 'Still thinking about the %s?', 'mine-mirth-mailer' ), $lead )
				: __( 'This week’s miners, picked for you', 'mine-mirth-mailer' );
			$intro   = $lead
				/* translators: %s: product name */
				? sprintf( __( 'You were looking at the %s. Here’s where it stands this week, plus a few similar miners worth comparing.', 'mine-mirth-mailer' ), '<strong>' . esc_html( $lead ) . '</strong>' )
				: __( 'Here are the miners we think you’ll want to see this week, based on what you’ve been browsing.', 'mine-mirth-mailer' );
			$sec[]   = $viewed_cards;
			$sec[]   = $similar( $n_prod, $viewed ? __( 'Similar miners to compare', 'mine-mirth-mailer' ) : __( 'Miners worth a look', 'mine-mirth-mailer' ) );
			$sec[]   = $network;
			$sec[]   = $bought ? $host_cta : $calc_cta;
			$sec[]   = $bought
				? mmm_c_reply( __( 'Need a hand with your setup?', 'mine-mirth-mailer' ) )
				: mmm_c_reply( __( 'Ready to talk numbers?', 'mine-mirth-mailer' ), __( 'Tell us how many units you need and where you’ll run them, and we’ll come back with a quote, including volume pricing.', 'mine-mirth-mailer' ), __( 'Get a quote', 'mine-mirth-mailer' ), 'quote' );
			break;

		case 'blog':
			$subject = __( 'Your mining reads for this week', 'mine-mirth-mailer' );
			$intro   = __( 'Thanks for reading the Mining Academy. Here are this week’s articles picked from the topics you read most.', 'mine-mirth-mailer' );
			$sec[]   = $reads( $n_post, __( 'Picked for you', 'mine-mirth-mailer' ) );
			$sec[]   = $network;
			$sec[]   = $new_in_shop( 2, __( 'Miners our readers are looking at', 'mine-mirth-mailer' ) );
			$sec[]   = $calc_cta;
			$sec[]   = mmm_c_reply( __( 'Got a question about mining?', 'mine-mirth-mailer' ), __( 'Ask us anything, from choosing a first miner to power and cooling. A real person will answer.', 'mine-mirth-mailer' ), __( 'Ask a question', 'mine-mirth-mailer' ), 'question' );
			break;

		case 'tools':
			$subject = __( 'Your mining numbers this week', 'mine-mirth-mailer' );
			$intro   = __( 'You’ve been running the numbers. Here’s this week’s network picture and the options that fit.', 'mine-mirth-mailer' );
			$sec[]   = $network;
			$sec[]   = $p['product_cats'] ? $similar( $n_prod, __( 'Miners that fit your numbers', 'mine-mirth-mailer' ) ) : $new_in_shop( $n_prod, __( 'Efficient miners in stock', 'mine-mirth-mailer' ) );
			$sec[]   = in_array( 'hosting', $p['tools'], true ) || $bought ? $host_cta : $calc_cta;
			$sec[]   = mmm_c_reply( __( 'Want us to check your numbers?', 'mine-mirth-mailer' ), __( 'Send us your power price and target hashrate, and we’ll suggest the setup with the fastest payback.', 'mine-mirth-mailer' ), __( 'Ask our team', 'mine-mirth-mailer' ), 'hosting' );
			break;

		default:
			$subject = __( 'This week at Mine Mirth', 'mine-mirth-mailer' );
			$intro   = __( 'Here’s everything new this week: fresh miners in the shop, the latest from the blog and live network numbers.', 'mine-mirth-mailer' );
			$sec[]   = $network;
			$sec[]   = $new_in_shop( $n_prod, __( 'Just landed', 'mine-mirth-mailer' ) );
			$sec[]   = $reads( 2, __( 'Worth reading', 'mine-mirth-mailer' ) );
			$sec[]   = $bought ? $host_cta : $calc_cta;
			$sec[]   = mmm_c_reply();
	}

	$custom_subject = trim( (string) mmm_weekly( 'subject' ) );
	if ( $custom_subject ) {
		$subject = $custom_subject;
	}
	/* translators: %s: first name or "there" */
	$body  = mmm_c_heading( sprintf( __( 'Hi %s,', 'mine-mirth-mailer' ), $first ), 1, (string) mmm_opt( 'brand_name' ) );
	$body .= mmm_c_text( $intro, 'left', 17 );
	$extra = trim( (string) mmm_weekly( 'intro' ) );
	if ( $extra ) {
		$body .= mmm_c_text( nl2br( esc_html( $extra ) ) );
	}
	$body .= mmm_c_spacer( 8 ) . implode( '', array_filter( $sec ) );
	$body .= mmm_c_text( __( 'Talk soon,', 'mine-mirth-mailer' ) . '<br><strong>' . esc_html( mmm_design()['site'] ) . '</strong>' );

	$pre = array(
		'shop'  => __( 'Prices and stock for the miners you viewed, plus similar picks.', 'mine-mirth-mailer' ),
		'blog'  => __( 'New articles in the topics you read, plus this week’s network numbers.', 'mine-mirth-mailer' ),
		'tools' => __( 'This week’s hashprice, difficulty and the setups that fit.', 'mine-mirth-mailer' ),
	)[ $focus ] ?? __( 'New miners, fresh articles and this week’s network numbers.', 'mine-mirth-mailer' );

	return array( 'subject' => $subject, 'preheader' => $pre, 'body' => $body, 'focus' => $focus );
}

/* ---------------------------------------------------------------------------
 * Schedule
 * ------------------------------------------------------------------------- */

/**
 * Most recent scheduled moment (site time zone) at or before now.
 */
function mmm_weekly_slot(): int {
	$tz    = wp_timezone();
	$now   = new DateTimeImmutable( 'now', $tz );
	[ $h, $m ] = array_map( 'intval', explode( ':', (string) mmm_weekly( 'time' ) ) + array( 9, 0 ) );
	$day   = (int) mmm_weekly( 'day' );
	$slot  = $now->setTime( $h, $m );
	$diff  = ( (int) $slot->format( 'w' ) - $day + 7 ) % 7;
	$slot  = $slot->modify( '-' . $diff . ' days' );
	if ( $slot > $now ) {
		$slot = $slot->modify( '-7 days' );
	}
	return $slot->getTimestamp();
}

function mmm_weekly_next(): int {
	return mmm_weekly_slot() + WEEK_IN_SECONDS;
}

/**
 * Create this week's run and queue every active subscriber.
 */
function mmm_weekly_start(): int {
	$ids = mmm_active_subscribers();
	if ( ! $ids ) {
		return 0;
	}
	$campaign = wp_insert_post(
		array(
			'post_type'   => 'mmm_campaign',
			'post_status' => 'publish',
			/* translators: %s: date */
			'post_title'  => sprintf( __( 'Weekly email – %s', 'mine-mirth-mailer' ), wp_date( get_option( 'date_format' ) ) ),
		)
	);
	if ( ! $campaign || is_wp_error( $campaign ) ) {
		return 0;
	}
	update_post_meta( $campaign, '_mmm_kind', 'weekly' );
	update_post_meta( $campaign, '_mmm_audience', 'all' );
	mmm_queue_campaign( (int) $campaign, $ids );
	update_option( 'mmm_weekly_last', time(), false );
	return (int) $campaign;
}

add_action(
	'mmm_hourly',
	static function () {
		if ( ! mmm_weekly( 'enabled' ) ) {
			return;
		}
		$slot = mmm_weekly_slot();
		$last = (int) get_option( 'mmm_weekly_last', 0 );
		// Run once per slot, and only within a day of it (so turning it on never sends a stale week).
		if ( $last < $slot && time() - $slot < DAY_IN_SECONDS ) {
			mmm_weekly_start();
		}
	}
);
