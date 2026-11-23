<?php
/**
 * Page not found.
 *
 * @package kassmtv
 */

get_header();
?>

<section class="k-container k-page-head k-404">
	<p class="k-eyebrow"><?php esc_html_e( 'Page not found', 'kassmtv' ); ?></p>
	<h1><?php esc_html_e( 'This page has moved or no longer exists.', 'kassmtv' ); ?></h1>
	<p><?php esc_html_e( 'Search the news, or go back to the homepage.', 'kassmtv' ); ?></p>
	<?php get_search_form(); ?>
	<div class="k-actions">
		<a class="k-btn k-btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go to the homepage', 'kassmtv' ); ?></a>
		<a class="k-btn k-btn-ghost" href="<?php echo esc_url( kassmtv_news_url() ); ?>"><?php esc_html_e( 'Latest news', 'kassmtv' ); ?></a>
	</div>
</section>

<?php
get_footer();
