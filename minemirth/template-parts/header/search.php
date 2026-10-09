<?php
/**
 * Search overlay with live results (⌘K / Ctrl+K).
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

if ( ! mm_opt( 'header_search' ) ) {
	return;
}
$mm_popular = mm_list( mm_opt( 'search_popular' ) );
?>
<div class="mm-search" data-mm-search inert>
	<div class="mm-search__backdrop" data-mm-search-close></div>
	<div class="mm-search__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'minemirth' ); ?>">
		<form class="mm-search__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php echo mm_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<label class="screen-reader-text" for="mm-search-input"><?php esc_html_e( 'Search the site', 'minemirth' ); ?></label>
			<input id="mm-search-input" type="search" name="s" autocomplete="off" spellcheck="false" placeholder="<?php echo esc_attr( (string) mm_opt( 'search_placeholder' ) ); ?>" data-mm-search-input>
			<button type="button" class="mm-kbd mm-search__esc" data-mm-search-close><?php esc_html_e( 'Esc', 'minemirth' ); ?></button>
		</form>
		<div class="mm-search__body">
			<?php if ( $mm_popular ) : ?>
				<div class="mm-search__popular" data-mm-search-popular>
					<span class="mm-search__label"><?php esc_html_e( 'Popular', 'minemirth' ); ?></span>
					<div class="mm-search__chips">
						<?php foreach ( $mm_popular as $mm_term ) : ?>
							<a class="mm-pill" href="<?php echo esc_url( add_query_arg( 's', rawurlencode( $mm_term ), home_url( '/' ) ) ); ?>" data-mm-search-term="<?php echo esc_attr( $mm_term ); ?>"><?php echo esc_html( $mm_term ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
			<div class="mm-search__results" data-mm-search-results aria-live="polite"></div>
		</div>
	</div>
</div>
