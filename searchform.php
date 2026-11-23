<?php
/**
 * Search box.
 *
 * @package kassmtv
 */

$kassmtv_search_id = wp_unique_id( 'k-search-' );
?>
<form role="search" method="get" class="k-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $kassmtv_search_id ); ?>"><?php esc_html_e( 'Search the news', 'kassmtv' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $kassmtv_search_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search the news…', 'kassmtv' ); ?>">
	<button type="submit"><?php esc_html_e( 'Search', 'kassmtv' ); ?></button>
</form>
