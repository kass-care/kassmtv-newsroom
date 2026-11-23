<?php
/**
 * Ordinary pages (About, Contacts, KassCare …).
 * Pages built with Elementor get the full width and are left as they are.
 *
 * @package kassmtv
 */

get_header();

while ( have_posts() ) :
	the_post();

	$kassmtv_builder = 'builder' === get_post_meta( get_the_ID(), '_elementor_edit_mode', true );

	if ( $kassmtv_builder ) :
		?>
		<div class="k-builder-content">
			<?php the_content(); ?>
		</div>
		<?php
	else :
		?>
		<header class="k-container k-page-head">
			<h1><?php echo esc_html( get_the_title() ); ?></h1>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="k-container k-article-image">
				<?php the_post_thumbnail( 'large' ); ?>
			</figure>
		<?php endif; ?>

		<div class="k-light">
			<div class="k-container k-article">
				<div class="k-prose">
					<?php
					the_content();

					wp_link_pages(
						array(
							'before' => '<nav class="k-pagination">',
							'after'  => '</nav>',
						)
					);
					?>
				</div>
			</div>
		</div>
		<?php
	endif;
endwhile;

get_footer();
