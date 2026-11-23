<?php
/**
 * News lists: all news, a category, a tag, an author, a search.
 *
 * @package kassmtv
 */

get_header();

if ( is_search() ) {
	/* translators: %s: search words */
	$kassmtv_title = sprintf( __( 'Search: %s', 'kassmtv' ), get_search_query() );
	$kassmtv_intro = '';
} elseif ( is_category() || is_tag() || is_tax() ) {
	$kassmtv_title = single_term_title( '', false );
	$kassmtv_intro = wp_strip_all_tags( term_description() );
} elseif ( is_author() ) {
	$kassmtv_title = get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) );
	$kassmtv_intro = '';
} elseif ( is_archive() ) {
	$kassmtv_title = wp_strip_all_tags( get_the_archive_title() );
	$kassmtv_intro = '';
} else {
	$kassmtv_title = __( 'Latest news', 'kassmtv' );
	$kassmtv_intro = __( 'Stories from KASS-mtv Diaspora News.', 'kassmtv' );
}
?>

<section class="k-container k-page-head">
	<p class="k-eyebrow"><?php esc_html_e( 'KASS-mtv News', 'kassmtv' ); ?></p>
	<h1><?php echo esc_html( $kassmtv_title ); ?></h1>
	<?php if ( $kassmtv_intro ) : ?>
		<p><?php echo esc_html( $kassmtv_intro ); ?></p>
	<?php endif; ?>
	<?php if ( is_search() ) : ?>
		<?php get_search_form(); ?>
	<?php endif; ?>
</section>

<section class="k-light">
	<div class="k-container k-section">
		<nav class="k-chips k-chips-list" aria-label="<?php esc_attr_e( 'News categories', 'kassmtv' ); ?>">
			<a class="k-chip<?php echo ( is_home() && ! is_category() ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( kassmtv_news_url() ); ?>"><?php esc_html_e( 'All', 'kassmtv' ); ?></a>
			<?php foreach ( kassmtv_news_categories( 6 ) as $kassmtv_category ) : ?>
				<a class="k-chip<?php echo is_category( $kassmtv_category->term_id ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_category_link( $kassmtv_category->term_id ) ); ?>"><?php echo esc_html( $kassmtv_category->name ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( have_posts() ) : ?>
			<div class="k-cards">
				<?php
				$kassmtv_n       = 0;
				$kassmtv_feed_ad = kassmtv_ad( 'feed' );

				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/card' );
					$kassmtv_n++;

					// An ad across the whole row after the 6th story.
					if ( 6 === $kassmtv_n && $kassmtv_feed_ad ) {
						echo '<div class="k-ad-cardrow">' . $kassmtv_feed_ad . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
					}
				endwhile;
				?>
			</div>

			<div class="k-pagination">
				<?php
				the_posts_pagination(
					array(
						'mid_size'  => 1,
						'prev_text' => __( '← Newer', 'kassmtv' ),
						'next_text' => __( 'Older →', 'kassmtv' ),
					)
				);
				?>
			</div>
		<?php else : ?>
			<p class="k-empty"><?php esc_html_e( 'Nothing here yet. Try another category or search.', 'kassmtv' ); ?></p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
