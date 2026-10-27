<?php
/**
 * Login / register — Mine Mirth split layout with tabs.
 *
 * All fields, nonces and hooks match WooCommerce's template.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mm_register  = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$mm_btn_class = function_exists( 'wc_wp_theme_get_element_class_name' ) && wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';
$mm_points    = mm_lines( mm_opt( 'account_panel_points' ) );
$mm_image     = (int) mm_opt( 'account_panel_image' );
$mm_tab       = ( $mm_register && ! empty( $_POST['register'] ) ) ? 'register' : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

do_action( 'woocommerce_before_customer_login_form' );
?>
<div class="mm-auth">
	<aside class="mm-auth__panel mm-scheme-dark">
		<?php if ( $mm_image ) : ?>
			<?php echo mm_image( $mm_image, 'large', array( 'class' => 'mm-auth__bg', 'loading' => 'eager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php endif; ?>
		<div class="mm-auth__fx" aria-hidden="true"><span></span><span></span></div>
		<div class="mm-auth__panel-inner">
			<a class="mm-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo mm_logo( 'auth' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<h2><?php echo esc_html( (string) mm_opt( 'account_panel_title' ) ); ?></h2>
			<p><?php echo esc_html( (string) mm_opt( 'account_panel_text' ) ); ?></p>
			<?php if ( $mm_points ) : ?>
				<ul class="mm-checklist">
					<?php foreach ( $mm_points as $mm_point ) : ?>
						<li><?php echo mm_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $mm_point ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</aside>

	<div class="mm-auth__forms" data-mm-auth>
		<?php if ( $mm_register ) : ?>
			<div class="mm-auth__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Account access', 'minemirth' ); ?>">
				<button type="button" role="tab" id="mm-tab-login" aria-controls="mm-pane-login" aria-selected="<?php echo 'login' === $mm_tab ? 'true' : 'false'; ?>" data-mm-auth-tab="login"><?php esc_html_e( 'Sign in', 'minemirth' ); ?></button>
				<button type="button" role="tab" id="mm-tab-register" aria-controls="mm-pane-register" aria-selected="<?php echo 'register' === $mm_tab ? 'true' : 'false'; ?>" data-mm-auth-tab="register"><?php esc_html_e( 'Create account', 'minemirth' ); ?></button>
			</div>
		<?php endif; ?>

		<div class="u-columns col2-set" id="customer_login">

			<div class="u-column1 col-1 mm-auth__pane" id="mm-pane-login" <?php echo $mm_register ? 'role="tabpanel" aria-labelledby="mm-tab-login"' : ''; ?> <?php echo ( $mm_register && 'login' !== $mm_tab ) ? 'data-mm-hidden' : ''; ?>>

				<h2><?php esc_html_e( 'Login', 'woocommerce' ); ?></h2>
				<p class="mm-auth__lead"><?php esc_html_e( 'Welcome back. Sign in to track orders and check out faster.', 'minemirth' ); ?></p>

				<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>

					<?php do_action( 'woocommerce_login_form_start' ); ?>

					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="username"><?php esc_html_e( 'Username or email address', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /><?php // @codingStandardsIgnoreLine ?>
					</p>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
						<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
					</p>

					<?php do_action( 'woocommerce_login_form' ); ?>

					<p class="form-row mm-auth__row">
						<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
							<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
						</label>
						<a class="mm-auth__lost" href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
					</p>
					<p class="form-row">
						<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
						<button type="submit" class="woocommerce-button button woocommerce-form-login__submit mm-btn--block<?php echo esc_attr( $mm_btn_class ); ?>" name="login" value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"><?php esc_html_e( 'Log in', 'woocommerce' ); ?></button>
					</p>

					<?php do_action( 'woocommerce_login_form_end' ); ?>

				</form>

				<?php if ( $mm_register ) : ?>
					<p class="mm-auth__switch"><?php esc_html_e( 'New to Mine Mirth?', 'minemirth' ); ?> <button type="button" class="button-link" data-mm-auth-tab="register"><?php esc_html_e( 'Create an account', 'minemirth' ); ?></button></p>
				<?php endif; ?>
			</div>

			<?php if ( $mm_register ) : ?>

				<div class="u-column2 col-2 mm-auth__pane" id="mm-pane-register" role="tabpanel" aria-labelledby="mm-tab-register" <?php echo 'register' !== $mm_tab ? 'data-mm-hidden' : ''; ?>>

					<h2><?php esc_html_e( 'Register', 'woocommerce' ); ?></h2>
					<p class="mm-auth__lead"><?php esc_html_e( 'Create an account in seconds — save addresses, follow deliveries and manage hosting.', 'minemirth' ); ?></p>

					<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

						<?php do_action( 'woocommerce_register_form_start' ); ?>

						<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
								<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /><?php // @codingStandardsIgnoreLine ?>
							</p>

						<?php endif; ?>

						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
							<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required aria-required="true" /><?php // @codingStandardsIgnoreLine ?>
						</p>

						<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
								<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
							</p>

						<?php else : ?>

							<p class="mm-auth__note"><?php esc_html_e( 'A link to set a new password will be sent to your email address.', 'woocommerce' ); ?></p>

						<?php endif; ?>

						<?php do_action( 'woocommerce_register_form' ); ?>

						<p class="woocommerce-form-row form-row">
							<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
							<button type="submit" class="woocommerce-Button woocommerce-button button<?php echo esc_attr( $mm_btn_class ); ?> woocommerce-form-register__submit mm-btn--block" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
						</p>

						<?php do_action( 'woocommerce_register_form_end' ); ?>

					</form>

					<p class="mm-auth__switch"><?php esc_html_e( 'Already have an account?', 'minemirth' ); ?> <button type="button" class="button-link" data-mm-auth-tab="login"><?php esc_html_e( 'Sign in', 'minemirth' ); ?></button></p>
				</div>

			<?php endif; ?>

		</div>
	</div>
</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
