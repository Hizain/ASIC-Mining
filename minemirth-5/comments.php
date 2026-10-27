<?php
/**
 * Comments.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="mm-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="mm-comments__title">
			<?php
			$mm_count = get_comments_number();
			/* translators: %s: comment count */
			echo esc_html( sprintf( _n( '%s comment', '%s comments', $mm_count, 'minemirth' ), number_format_i18n( $mm_count ) ) );
			?>
		</h2>
		<ol class="mm-comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 48,
					'callback'    => 'mm_comment_callback',
				)
			);
			?>
		</ol>
		<?php the_comments_pagination(); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="mm-admin-note"><?php esc_html_e( 'Comments are closed.', 'minemirth' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply_before' => '<h2 id="reply-title" class="comment-reply-title">',
			'title_reply_after'  => '</h2>',
			'class_submit'       => 'submit',
		)
	);
	?>
</section>
