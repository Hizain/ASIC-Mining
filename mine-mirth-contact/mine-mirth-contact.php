<?php
/**
 * Plugin Name:       Mine Mirth Contact
 * Description:       Contact page for Mine Mirth, LLC: intro text, email and phone cards, and a contact form that emails the support team. Use the shortcode [minemirth_contact].
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-contact
 *
 * @package MineMirthContact
 */

defined( 'ABSPATH' ) || exit;

const MMCT_EMAIL = 'support@minemirth.com';
const MMCT_PHONE = '+1 (249) 202-4891';

/**
 * [minemirth_contact title="" form="yes|no" to="support@minemirth.com"]
 */
function mmct_shortcode( $atts ): string {
	$a = shortcode_atts(
		array(
			'title' => __( 'Contact Mine Mirth', 'mine-mirth-contact' ),
			'form'  => 'yes',
		),
		$atts
	);

	$sent  = isset( $_GET['mmct'] ) && 'sent' === $_GET['mmct']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$error = isset( $_GET['mmct'] ) && 'error' === $_GET['mmct']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$tel   = 'tel:' . preg_replace( '/[^0-9+]/', '', MMCT_PHONE );

	ob_start();
	?>
	<section class="mmct" id="mmct">
		<div class="mmct__intro">
			<span class="mmct__eyebrow"><?php esc_html_e( 'Mine Mirth, LLC', 'mine-mirth-contact' ); ?></span>
			<h2 class="mmct__title"><?php echo esc_html( $a['title'] ); ?></h2>
			<p><?php esc_html_e( 'Mine Mirth, LLC helps miners at every stage, from a first home rig to a full deployment. We supply ASIC mining hardware, arrange hosting with partner facilities, and build practical tools that help you choose the right machine and understand its returns.', 'mine-mirth-contact' ); ?></p>
			<p><?php esc_html_e( 'Questions about a miner, an order, shipping, warranty or hosting? Our team works with this hardware every day and is glad to help. Email or call us, or send a message with the form and we will reply as soon as we can.', 'mine-mirth-contact' ); ?></p>
		</div>

		<div class="mmct__grid">
			<div class="mmct__cards">
				<a class="mmct__card" href="mailto:<?php echo esc_attr( antispambot( MMCT_EMAIL ) ); ?>">
					<span class="mmct__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>
					<small><?php esc_html_e( 'Reach us by email', 'mine-mirth-contact' ); ?></small>
					<strong><?php echo esc_html( antispambot( MMCT_EMAIL ) ); ?></strong>
				</a>
				<a class="mmct__card" href="<?php echo esc_url( $tel, array( 'tel' ) ); ?>">
					<span class="mmct__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg></span>
					<small><?php esc_html_e( 'Call us anytime', 'mine-mirth-contact' ); ?></small>
					<strong><?php echo esc_html( MMCT_PHONE ); ?></strong>
				</a>
			</div>

			<?php if ( 'no' !== $a['form'] ) : ?>
				<form class="mmct__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<h3><?php esc_html_e( 'Send us a message', 'mine-mirth-contact' ); ?></h3>
					<?php if ( $sent ) : ?>
						<p class="mmct__notice is-ok" role="status"><?php esc_html_e( 'Thank you. Your message was sent and we will get back to you soon.', 'mine-mirth-contact' ); ?></p>
					<?php elseif ( $error ) : ?>
						<p class="mmct__notice is-error" role="alert"><?php esc_html_e( 'Sorry, the message could not be sent. Please check the fields or email us directly.', 'mine-mirth-contact' ); ?></p>
					<?php endif; ?>
					<input type="hidden" name="action" value="mmct_send">
					<input type="hidden" name="back" value="<?php echo esc_url( get_permalink() ); ?>">
					<?php wp_nonce_field( 'mmct_send', 'mmct_nonce' ); ?>
					<div class="mmct__row">
						<label><span><?php esc_html_e( 'Name', 'mine-mirth-contact' ); ?></span><input type="text" name="name" required maxlength="120" autocomplete="name"></label>
						<label><span><?php esc_html_e( 'Email', 'mine-mirth-contact' ); ?></span><input type="email" name="email" required maxlength="200" autocomplete="email"></label>
					</div>
					<label><span><?php esc_html_e( 'Topic', 'mine-mirth-contact' ); ?></span>
						<select name="topic">
							<option><?php esc_html_e( 'Buying a miner', 'mine-mirth-contact' ); ?></option>
							<option><?php esc_html_e( 'An existing order', 'mine-mirth-contact' ); ?></option>
							<option><?php esc_html_e( 'Hosting', 'mine-mirth-contact' ); ?></option>
							<option><?php esc_html_e( 'Warranty or repair', 'mine-mirth-contact' ); ?></option>
							<option><?php esc_html_e( 'Something else', 'mine-mirth-contact' ); ?></option>
						</select>
					</label>
					<label><span><?php esc_html_e( 'Message', 'mine-mirth-contact' ); ?></span><textarea name="message" rows="5" required maxlength="5000"></textarea></label>
					<label class="mmct__hp" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
					<button type="submit" class="mmct__btn"><?php esc_html_e( 'Send message', 'mine-mirth-contact' ); ?></button>
				</form>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'minemirth_contact', 'mmct_shortcode' );

/**
 * Form handler: validates, rate-limits and emails the support team.
 */
function mmct_send(): void {
	$back = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['back'] ?? '' ) ), home_url( '/' ) );
	$fail = static function () use ( $back ) {
		wp_safe_redirect( add_query_arg( 'mmct', 'error', $back ) . '#mmct' );
		exit;
	};
	if ( ! isset( $_POST['mmct_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mmct_nonce'] ) ), 'mmct_send' ) || ! empty( $_POST['website'] ) ) {
		$fail();
	}
	$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$topic   = sanitize_text_field( wp_unslash( $_POST['topic'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
	if ( '' === $name || ! is_email( $email ) || '' === $message ) {
		$fail();
	}
	// At most 5 messages per hour from one address.
	$key   = 'mmct_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	$count = (int) get_transient( $key );
	if ( $count >= 5 ) {
		$fail();
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );

	/* translators: 1: topic 2: name */
	$subject = sprintf( __( '[Mine Mirth contact] %1$s — %2$s', 'mine-mirth-contact' ), $topic, $name );
	$body    = "Name: {$name}\nEmail: {$email}\nTopic: {$topic}\n\n{$message}\n";
	$ok      = wp_mail( MMCT_EMAIL, $subject, $body, array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );
	wp_safe_redirect( add_query_arg( 'mmct', $ok ? 'sent' : 'error', $back ) . '#mmct' );
	exit;
}
add_action( 'admin_post_mmct_send', 'mmct_send' );
add_action( 'admin_post_nopriv_mmct_send', 'mmct_send' );

add_action(
	'wp_head',
	static function () {
		?>
		<style id="mmct-css">
		.mmct{--c:var(--mm-primary,#35a8f6);--t:var(--mm-text,#0a1626);--m:var(--mm-muted,#506279);--s:var(--mm-surface,#fff);--b:var(--mm-border,#d9e4ef);--r:var(--mm-radius,18px);color:var(--t);scroll-margin-top:100px}
		.mmct__intro{max-width:760px;margin-bottom:32px}
		.mmct__intro p{color:var(--m);line-height:1.7}
		.mmct__eyebrow{display:inline-block;margin-bottom:8px;font-family:var(--mm-font-mono,monospace);font-size:.76rem;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:var(--c)}
		.mmct__title{margin:0 0 14px;font-size:clamp(1.8rem,1.3rem + 1.8vw,2.6rem);line-height:1.1}
		.mmct__grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.4fr);gap:24px;align-items:start}
		.mmct__cards{display:grid;gap:14px}
		.mmct__card{display:grid;gap:4px;padding:22px;border:1px solid var(--b);border-radius:var(--r);background:var(--s);color:var(--t);text-decoration:none!important;transition:border-color .2s,transform .2s}
		.mmct__card:hover{border-color:var(--c);transform:translateY(-2px)}
		.mmct__card small{color:var(--m)}
		.mmct__card strong{font-size:1.15rem;word-break:break-word}
		.mmct__icon{display:grid;place-items:center;width:44px;height:44px;margin-bottom:8px;border-radius:12px;background:color-mix(in srgb,var(--c) 14%,transparent);color:var(--c)}
		.mmct__icon svg{width:22px;height:22px}
		.mmct__form{display:grid;gap:14px;padding:clamp(20px,3vw,32px);border:1px solid var(--b);border-radius:var(--r);background:var(--s)}
		.mmct__form h3{margin:0}
		.mmct__form label{display:grid;gap:6px;font-weight:600;font-size:.88rem}
		.mmct__form input,.mmct__form select,.mmct__form textarea{width:100%;font:inherit;font-weight:400}
		.mmct__row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
		.mmct__hp{position:absolute!important;left:-9999px!important}
		.mmct__btn{min-height:50px;border:0;border-radius:var(--mm-btn-radius,999px);background:linear-gradient(120deg,var(--mm-accent-2,#5ee1ff),var(--c) 45%,var(--mm-accent,#2563eb));color:var(--mm-on-primary,#03111f);font-weight:700;cursor:pointer}
		.mmct__notice{margin:0;padding:10px 14px;border-radius:10px;font-weight:500}
		.mmct__notice.is-ok{background:color-mix(in srgb,var(--mm-success,#12b981) 15%,transparent)}
		.mmct__notice.is-error{background:color-mix(in srgb,var(--mm-danger,#f04461) 15%,transparent)}
		@media (max-width:860px){.mmct__grid,.mmct__row{grid-template-columns:minmax(0,1fr)}}
		</style>
		<?php
	}
);
