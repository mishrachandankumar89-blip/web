<?php
/**
 * Search form.
 *
 * @package avenza
 */

$avenza_id = wp_unique_id( 'avz-search-' );
?>
<form role="search" method="get" class="avz-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $avenza_id ); ?>"><?php esc_html_e( 'Search for', 'avenza' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $avenza_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search services and articles', 'avenza' ); ?>">
	<button type="submit" class="avz-btn avz-btn--primary"><?php echo avenza_icon( 'search', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Search', 'avenza' ); ?></span></button>
</form>
