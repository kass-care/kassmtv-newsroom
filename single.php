<?php
/**
 * One news story: headline, byline with photo, key points, story,
 * share buttons, newsletter, side column (ad, trending, latest), more news.
 *
 * @package kassmtv
 */

get_header();

while ( have_posts() ) :
	the_post();

	$kassmtv_cat     = kassmtv_post_category( get_the_ID() );
	$kassmtv_url     = rawurlencode( get_permalink() );
	$kassmtv_title   = rawurlencode( wp_strip_all_tags( get_the_title() ) );
	$kassmtv_points  = kassmtv_key_points();
	$kassmtv_updated = ( get_post_modified_time( 'U', true ) - get_post_time( 'U', true ) ) > HOUR_IN_SECONDS;
	$kassmtv_author  = (int) get_the_author_meta( 'ID' );
	$kassmtv_byline  = kassmtv_newsroom_byline();
	$kassmtv_ai      = kassmtv_is_ai_story();
	?>

	<article <?php post_class( 'k-story' ); ?>>
		<header class="k-container k-article-head">
			<?php if ( $kassmtv_cat ) : ?>
				<a class="k-tag" href="<?php echo esc_url( $kassmtv_cat[1] ); ?>"><?php echo esc_html( $kassmtv_cat[0] ); ?></a>
			<?php endif; ?>
			<h1 class="k-story-title"><?php echo esc_html( get_the_title() ); ?></h1>

			<div class="k-byline">
				<?php if ( $kassmtv_byline ) : ?>
					<span class="k-byline-photo k-byline-badge" aria-hidden="true"><svg width="26" height="26" viewBox="136 4 68 70"><path d="M185 22.3 L152 8 Q141 3.3 140.6 15 L138.3 63 Q137.8 73.8 148 68.6 L197 43.6 Q206 39 199 31.3" fill="none" stroke="#E0262B" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
				<?php else : ?>
					<span class="k-byline-photo"><?php echo get_avatar( $kassmtv_author, 48, '', '' ); ?></span>
				<?php endif; ?>
				<span class="k-byline-text">
					<span class="k-byline-name">
						<?php esc_html_e( 'By', 'kassmtv' ); ?>
						<?php if ( $kassmtv_byline ) : ?>
							<?php echo esc_html( $kassmtv_byline ); ?>
						<?php else : ?>
							<a href="<?php echo esc_url( get_author_posts_url( $kassmtv_author ) ); ?>"><?php echo esc_html( get_the_author() ); ?></a>
						<?php endif; ?>
					</span>
					<span class="k-byline-meta">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<?php if ( $kassmtv_updated ) : ?>
							· <?php esc_html_e( 'Updated', 'kassmtv' ); ?> <time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( kassmtv_time_ago( null, true ) ); ?></time>
						<?php endif; ?>
						· <?php echo esc_html( kassmtv_read_time() ); ?>
						<?php if ( $kassmtv_ai ) : ?>
							· <span class="k-ai-pill"><?php esc_html_e( 'AI-assisted', 'kassmtv' ); ?></span>
						<?php endif; ?>
					</span>
				</span>
			</div>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="k-container k-article-image">
				<?php the_post_thumbnail( 'large' ); ?>
				<?php if ( get_the_post_thumbnail_caption() ) : ?>
					<figcaption><?php echo esc_html( get_the_post_thumbnail_caption() ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>

		<div class="k-light">
			<div class="k-container k-article">
				<div class="k-story-layout">
					<aside class="k-share-rail" aria-label="<?php esc_attr_e( 'Share this story', 'kassmtv' ); ?>">
						<a class="k-share-btn k-share-wa" href="https://wa.me/?text=<?php echo esc_attr( $kassmtv_title . '%20' . $kassmtv_url ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Share on WhatsApp', 'kassmtv' ); ?>">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4z"/><path d="M9 9.5c.3 1.6 1.9 3.6 3.6 4.3l1.2-1.1 2 1v1.2c-3.2.8-7.3-3.3-7-6.6h1.3l.8 1.9z"/></svg>
							<span>WhatsApp</span>
						</a>
						<a class="k-share-btn" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $kassmtv_url ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Share on Facebook', 'kassmtv' ); ?>">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
							<span>Facebook</span>
						</a>
						<a class="k-share-btn" href="https://twitter.com/intent/tweet?url=<?php echo esc_attr( $kassmtv_url ); ?>&amp;text=<?php echo esc_attr( $kassmtv_title ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Share on X', 'kassmtv' ); ?>">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4l16 16M20 4L4 20"/></svg>
							<span>X</span>
						</a>
						<button class="k-share-btn" type="button" data-k-copy="<?php echo esc_url( get_permalink() ); ?>" aria-label="<?php esc_attr_e( 'Copy link', 'kassmtv' ); ?>">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
							<span data-k-copy-label><?php esc_html_e( 'Copy link', 'kassmtv' ); ?></span>
						</button>
					</aside>

					<div class="k-story-main">
						<?php if ( $kassmtv_points ) : ?>
							<section class="k-keypoints" aria-label="<?php esc_attr_e( 'Key points', 'kassmtv' ); ?>">
								<h2><?php echo count( $kassmtv_points ) > 1 ? esc_html__( 'Key points', 'kassmtv' ) : esc_html__( 'In brief', 'kassmtv' ); ?></h2>
								<?php if ( count( $kassmtv_points ) > 1 ) : ?>
									<ul>
										<?php foreach ( $kassmtv_points as $kassmtv_point ) : ?>
											<li><?php echo esc_html( $kassmtv_point ); ?></li>
										<?php endforeach; ?>
									</ul>
								<?php else : ?>
									<p><?php echo esc_html( $kassmtv_points[0] ); ?></p>
								<?php endif; ?>
							</section>
						<?php endif; ?>

						<div class="k-prose">
							<?php
							the_content();

							if ( $kassmtv_ai ) {
								echo kassmtv_ai_note(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
							}

							wp_link_pages(
								array(
									'before' => '<nav class="k-pagination">',
									'after'  => '</nav>',
								)
							);
							?>
						</div>

						<?php $kassmtv_tags = get_the_tags(); ?>
						<?php if ( $kassmtv_tags ) : ?>
							<div class="k-tags">
								<?php foreach ( $kassmtv_tags as $kassmtv_tag ) : ?>
									<?php
									if ( 'breaking' === $kassmtv_tag->slug ) {
										continue;
									}
									?>
									<a class="k-chip" href="<?php echo esc_url( get_tag_link( $kassmtv_tag->term_id ) ); ?>">#<?php echo esc_html( $kassmtv_tag->name ); ?></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php echo kassmtv_newsletter_box( 'story', true ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>

						<?php
						if ( comments_open() || get_comments_number() ) {
							echo '<div class="k-comments">';
							comments_template();
							echo '</div>';
						}
						?>
					</div>

					<?php echo kassmtv_story_sidebar( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
				</div>
			</div>

			<?php
			$kassmtv_related_args = array(
				'post_type'           => 'post',
				'posts_per_page'      => 4,
				'post__not_in'        => array( get_the_ID() ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			);

			$kassmtv_cats = wp_get_post_categories( get_the_ID() );

			if ( $kassmtv_cats ) {
				$kassmtv_related_args['category__in'] = $kassmtv_cats;
			}

			$kassmtv_related = new WP_Query( $kassmtv_related_args );

			if ( $kassmtv_related->have_posts() ) :
				?>
				<section class="k-container k-related" aria-label="<?php esc_attr_e( 'More news', 'kassmtv' ); ?>">
					<div class="k-row-head">
						<h2 class="k-row-title"><?php esc_html_e( 'More news', 'kassmtv' ); ?></h2>
						<a class="k-row-more" href="<?php echo esc_url( kassmtv_news_url() ); ?>"><?php esc_html_e( 'All news', 'kassmtv' ); ?> →</a>
					</div>
					<div class="k-cards k-cards-4">
						<?php
						while ( $kassmtv_related->have_posts() ) :
							$kassmtv_related->the_post();
							get_template_part( 'template-parts/card' );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</section>
			<?php endif; ?>
		</div>
	</article>

	<?php
endwhile;

get_footer();
