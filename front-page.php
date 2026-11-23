<?php
/**
 * Homepage, news first:
 * top stories → most read → video → sections → about KASS, platforms,
 * services, mission, contact. Everything news-related fills itself from posts.
 *
 * @package kassmtv
 */

get_header();

$kassmtv_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 9,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => false,
	)
);

$kassmtv_posts = $kassmtv_query->posts;
$kassmtv_lead  = isset( $kassmtv_posts[0] ) ? $kassmtv_posts[0] : null;
$kassmtv_side  = array_slice( $kassmtv_posts, 1, 4 );
$kassmtv_row   = array_slice( $kassmtv_posts, 5, 4 );
$kassmtv_shown = wp_list_pluck( $kassmtv_posts, 'ID' );
$kassmtv_email = kassmtv_option( 'email' );
$kassmtv_phone = kassmtv_option( 'phone' );
$kassmtv_tel   = preg_replace( '/[^0-9+]/', '', (string) $kassmtv_phone );
$kassmtv_yt    = kassmtv_option( 'youtube' );
?>

<!-- ===== Top stories ===== -->
<section class="k-light k-top" id="news" aria-label="<?php esc_attr_e( 'Top stories', 'kassmtv' ); ?>">
	<?php if ( $kassmtv_lead ) : ?>
		<?php $kassmtv_cat = kassmtv_post_category( $kassmtv_lead->ID ); ?>
		<div class="k-container k-top-grid">
			<article class="k-lead-story<?php echo kassmtv_has_news_graphic( $kassmtv_lead ) ? ' is-graphic' : ''; ?>">
				<a class="k-lead-story-media" href="<?php echo esc_url( get_permalink( $kassmtv_lead ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo kassmtv_post_image( $kassmtv_lead->ID, 'kassmtv-wide', 'k-img', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</a>
				<div class="k-lead-story-body">
					<?php if ( $kassmtv_cat ) : ?>
						<a class="k-tag" href="<?php echo esc_url( $kassmtv_cat[1] ); ?>"><?php echo esc_html( $kassmtv_cat[0] ); ?></a>
					<?php endif; ?>
					<h1 class="k-lead-story-title"><a href="<?php echo esc_url( get_permalink( $kassmtv_lead ) ); ?>"><?php echo esc_html( get_the_title( $kassmtv_lead ) ); ?></a></h1>
					<p class="k-lead-story-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $kassmtv_lead ), 28, '…' ) ); ?></p>
					<p class="k-time"><?php echo esc_html( kassmtv_time_ago( $kassmtv_lead ) ); ?> · <?php echo esc_html( kassmtv_author_name( $kassmtv_lead ) ); ?></p>
				</div>
			</article>

			<div class="k-top-side">
				<h2 class="k-side-title"><?php esc_html_e( 'Top stories', 'kassmtv' ); ?></h2>
				<?php foreach ( $kassmtv_side as $kassmtv_post ) : ?>
					<?php $kassmtv_cat = kassmtv_post_category( $kassmtv_post->ID ); ?>
					<article class="k-mini">
						<div class="k-mini-body">
							<?php if ( $kassmtv_cat ) : ?>
								<a class="k-cat k-cat-sm" href="<?php echo esc_url( $kassmtv_cat[1] ); ?>"><?php echo esc_html( $kassmtv_cat[0] ); ?></a>
							<?php endif; ?>
							<h3><a href="<?php echo esc_url( get_permalink( $kassmtv_post ) ); ?>"><?php echo esc_html( get_the_title( $kassmtv_post ) ); ?></a></h3>
							<p class="k-time"><?php echo esc_html( kassmtv_time_ago( $kassmtv_post ) ); ?></p>
						</div>
						<a class="k-mini-media" href="<?php echo esc_url( get_permalink( $kassmtv_post ) ); ?>" tabindex="-1" aria-hidden="true">
							<?php echo kassmtv_post_image( $kassmtv_post->ID, 'kassmtv-card', 'k-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</div>

		<?php if ( $kassmtv_row ) : ?>
			<div class="k-container k-top-row">
				<div class="k-cards k-cards-4">
					<?php
					foreach ( $kassmtv_row as $kassmtv_post ) {
						$GLOBALS['post'] = $kassmtv_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
						setup_postdata( $kassmtv_post );
						get_template_part( 'template-parts/card' );
					}
					wp_reset_postdata();
					?>
				</div>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<div class="k-container k-section">
			<h1 class="k-h2"><?php esc_html_e( 'Latest news', 'kassmtv' ); ?></h1>
			<p class="k-empty"><?php esc_html_e( 'News stories will appear here as soon as they are published.', 'kassmtv' ); ?></p>
		</div>
	<?php endif; ?>
</section>

<?php $kassmtv_popular = kassmtv_most_read( 5 ); ?>
<?php if ( $kassmtv_popular ) : ?>
	<!-- ===== Most read ===== -->
	<section class="k-light k-popular" aria-label="<?php esc_attr_e( 'Most read', 'kassmtv' ); ?>">
		<div class="k-container">
			<div class="k-row-head">
				<h2 class="k-row-title"><?php esc_html_e( 'Most read', 'kassmtv' ); ?></h2>
			</div>
			<ol class="k-popular-list">
				<?php foreach ( $kassmtv_popular as $kassmtv_n => $kassmtv_post ) : ?>
					<li>
						<span class="k-popular-num" aria-hidden="true"><?php echo esc_html( $kassmtv_n + 1 ); ?></span>
						<a href="<?php echo esc_url( get_permalink( $kassmtv_post ) ); ?>"><?php echo esc_html( get_the_title( $kassmtv_post ) ); ?></a>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>
<?php endif; ?>

<?php $kassmtv_wa_cta = kassmtv_whatsapp_cta( 'home' ); ?>
<?php if ( $kassmtv_wa_cta ) : ?>
	<div class="k-light k-wa-row">
		<div class="k-container"><?php echo $kassmtv_wa_cta; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?></div>
	</div>
<?php endif; ?>

<?php $kassmtv_feed_ad = kassmtv_ad( 'feed' ); ?>
<?php if ( $kassmtv_feed_ad ) : ?>
	<div class="k-light k-ad-row">
		<div class="k-container"><?php echo $kassmtv_feed_ad; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?></div>
	</div>
<?php endif; ?>

<!-- ===== Watch ===== -->
<?php $kassmtv_videos = kassmtv_youtube_videos( 4 ); ?>
<section class="k-watch-band" id="watch">
	<div class="k-container k-section">
		<div class="k-row-head k-row-head-dark">
			<div>
				<p class="k-eyebrow k-eyebrow-pink"><span class="k-dot" aria-hidden="true"></span><?php esc_html_e( 'Watch', 'kassmtv' ); ?></p>
				<h2 class="k-row-title"><?php esc_html_e( 'KASS-mtv on video', 'kassmtv' ); ?></h2>
			</div>
			<?php if ( $kassmtv_yt ) : ?>
				<a class="k-btn k-btn-ghost k-btn-sm" href="<?php echo esc_url( $kassmtv_yt ); ?>" target="_blank" rel="noopener">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9l5 3-5 3z"/></svg>
					<?php esc_html_e( 'Subscribe on YouTube', 'kassmtv' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $kassmtv_videos ) : ?>
			<?php $kassmtv_first = $kassmtv_videos[0]; ?>
			<div class="k-videos" data-k-videos>
				<div class="k-player">
					<button class="k-player-button" type="button" data-k-play="<?php echo esc_attr( $kassmtv_first['id'] ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: video title */ __( 'Play: %s', 'kassmtv' ), $kassmtv_first['title'] ) ); ?>">
						<img src="<?php echo esc_url( 'https://i.ytimg.com/vi/' . $kassmtv_first['id'] . '/hqdefault.jpg' ); ?>" alt="" loading="lazy">
						<span class="k-play" aria-hidden="true"><svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
					</button>
					<p class="k-player-title" data-k-player-title><?php echo esc_html( $kassmtv_first['title'] ); ?></p>
				</div>
				<ul class="k-playlist">
					<?php foreach ( $kassmtv_videos as $kassmtv_v => $kassmtv_video ) : ?>
						<li>
							<button type="button" class="k-playlist-item<?php echo 0 === $kassmtv_v ? ' is-active' : ''; ?>" data-k-video="<?php echo esc_attr( $kassmtv_video['id'] ); ?>" data-k-title="<?php echo esc_attr( $kassmtv_video['title'] ); ?>">
								<span class="k-playlist-thumb"><img src="<?php echo esc_url( 'https://i.ytimg.com/vi/' . $kassmtv_video['id'] . '/mqdefault.jpg' ); ?>" alt="" loading="lazy"></span>
								<span class="k-playlist-text">
									<span class="k-playlist-name"><?php echo esc_html( $kassmtv_video['title'] ); ?></span>
									<?php if ( $kassmtv_video['date'] ) : ?>
										<span class="k-time"><?php echo esc_html( human_time_diff( $kassmtv_video['date'], time() ) . ' ' . __( 'ago', 'kassmtv' ) ); ?></span>
									<?php endif; ?>
								</span>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php else : ?>
			<div class="k-video">
				<?php
				$kassmtv_showreel = kassmtv_option( 'showreel' );
				$kassmtv_embed    = $kassmtv_showreel ? wp_oembed_get( $kassmtv_showreel ) : '';

				if ( $kassmtv_embed ) :
					echo $kassmtv_embed; // phpcs:ignore WordPress.Security.EscapeOutput -- oEmbed HTML from WordPress.
				else :
					?>
					<a class="k-video-ph" href="<?php echo esc_url( $kassmtv_yt ? $kassmtv_yt : '#contact' ); ?>" target="_blank" rel="noopener">
						<span class="k-play" aria-hidden="true"><svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
						<span><?php esc_html_e( 'Watch KASS-mtv on YouTube', 'kassmtv' ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<!-- ===== Sections by category ===== -->
<section class="k-light k-sections" aria-label="<?php esc_attr_e( 'News by section', 'kassmtv' ); ?>">
	<div class="k-container">
		<?php
		$kassmtv_rows = 0;

		foreach ( kassmtv_news_categories( 6 ) as $kassmtv_category ) :
			if ( $kassmtv_rows >= 4 ) {
				break;
			}

			$kassmtv_section = new WP_Query(
				array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => 4,
					'cat'                 => $kassmtv_category->term_id,
					'post__not_in'        => $kassmtv_shown,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				)
			);

			if ( $kassmtv_section->post_count < 2 ) {
				continue;
			}

			$kassmtv_rows++;
			?>
			<div class="k-section-row">
				<div class="k-row-head">
					<h2 class="k-row-title"><a href="<?php echo esc_url( get_category_link( $kassmtv_category->term_id ) ); ?>"><?php echo esc_html( $kassmtv_category->name ); ?></a></h2>
					<a class="k-row-more" href="<?php echo esc_url( get_category_link( $kassmtv_category->term_id ) ); ?>"><?php esc_html_e( 'More', 'kassmtv' ); ?> →</a>
				</div>
				<div class="k-cards k-cards-4">
					<?php
					while ( $kassmtv_section->have_posts() ) :
						$kassmtv_section->the_post();
						$kassmtv_shown[] = get_the_ID();
						get_template_part( 'template-parts/card' );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
			<?php
			// A second ad between the section rows, after the second row.
			if ( 2 === $kassmtv_rows && $kassmtv_feed_ad ) {
				echo '<div class="k-ad-inrow">' . $kassmtv_feed_ad . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
			}
			?>
		<?php endforeach; ?>

		<div class="k-all-news">
			<a class="k-btn k-btn-dark" href="<?php echo esc_url( kassmtv_news_url() ); ?>"><?php esc_html_e( 'All news →', 'kassmtv' ); ?></a>
		</div>
	</div>
</section>

<!-- ===== Newsletter ===== -->
<div class="k-light k-newsletter-band">
	<div class="k-container">
		<?php echo kassmtv_newsletter_box( 'home' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
	</div>
</div>

<!-- ===== About KASS MTV USA ===== -->
<section class="k-container k-hero" id="about-kass">
	<div class="k-hero-copy">
		<p class="k-kicker"><span class="k-kicker-line" aria-hidden="true"></span><?php esc_html_e( 'About KASS MTV USA', 'kassmtv' ); ?></p>
		<h2 class="k-hero-title"><?php echo esc_html( kassmtv_option( 'hero_title' ) ); ?></h2>
		<p class="k-hero-text"><?php echo esc_html( kassmtv_option( 'hero_text' ) ); ?></p>
		<div class="k-actions">
			<a class="k-btn k-btn-primary" href="#platforms"><?php esc_html_e( 'Explore our platforms', 'kassmtv' ); ?></a>
			<a class="k-btn k-btn-ghost" href="#contact"><?php esc_html_e( 'Work with us', 'kassmtv' ); ?></a>
		</div>
	</div>
</section>

<!-- ===== Platforms ===== -->
<section class="k-container k-section k-section-tight" id="platforms">
	<div class="k-section-head">
		<div class="k-section-title">
			<p class="k-eyebrow"><?php esc_html_e( 'Our platforms', 'kassmtv' ); ?></p>
			<h2 class="k-h2"><?php esc_html_e( 'Software we build and run for real organisations.', 'kassmtv' ); ?></h2>
		</div>
		<p class="k-section-note"><?php esc_html_e( 'Each platform is built by the KASS MTV team for the people it serves.', 'kassmtv' ); ?></p>
	</div>

	<div class="k-platforms">
		<?php foreach ( kassmtv_platforms() as $kassmtv_platform ) : ?>
			<article class="k-platform">
				<span class="k-icon" aria-hidden="true">
					<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $kassmtv_platform['icon']; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG paths. ?></svg>
				</span>
				<h3><?php echo esc_html( $kassmtv_platform['name'] ); ?></h3>
				<p class="k-platform-text"><?php echo esc_html( $kassmtv_platform['text'] ); ?></p>
				<p class="k-platform-short"><?php echo esc_html( $kassmtv_platform['short'] ); ?></p>
				<?php if ( $kassmtv_platform['link'] ) : ?>
					<a class="k-platform-link" href="<?php echo esc_url( $kassmtv_platform['link'] ); ?>"><?php echo esc_html( $kassmtv_platform['cta'] ); ?> →</a>
				<?php else : ?>
					<a class="k-platform-link" href="#contact"><?php esc_html_e( 'Ask us about it →', 'kassmtv' ); ?></a>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</div>
</section>

<!-- ===== Services ===== -->
<section class="k-light" id="services">
	<div class="k-container k-section">
		<p class="k-eyebrow k-eyebrow-red"><?php esc_html_e( 'What we do', 'kassmtv' ); ?></p>
		<h2 class="k-h2"><?php esc_html_e( 'Four ways we help you reach people.', 'kassmtv' ); ?></h2>

		<div class="k-services">
			<?php
			$kassmtv_services = array(
				array( __( 'Media & Broadcasting', 'kassmtv' ), array( __( 'News coverage', 'kassmtv' ), __( 'Video & documentaries', 'kassmtv' ), __( 'Interviews & features', 'kassmtv' ), __( 'Event coverage', 'kassmtv' ) ) ),
				array( __( 'Digital Media', 'kassmtv' ), array( __( 'Websites', 'kassmtv' ), __( 'Social media management', 'kassmtv' ), __( 'Live streaming', 'kassmtv' ), __( 'Podcast production', 'kassmtv' ) ) ),
				array( __( 'Marketing & Branding', 'kassmtv' ), array( __( 'Brand development', 'kassmtv' ), __( 'Digital marketing', 'kassmtv' ), __( 'Advertising campaigns', 'kassmtv' ), __( 'Public relations support', 'kassmtv' ) ) ),
				array( __( 'Technology Solutions', 'kassmtv' ), array( __( 'Custom software', 'kassmtv' ), __( 'Healthcare & church systems', 'kassmtv' ), __( 'Business automation', 'kassmtv' ), __( 'Digital transformation', 'kassmtv' ) ) ),
			);

			foreach ( $kassmtv_services as $kassmtv_n => $kassmtv_service ) :
				?>
				<div class="k-service">
					<p class="k-service-num"><?php echo esc_html( sprintf( '%02d', $kassmtv_n + 1 ) ); ?></p>
					<h3><?php echo esc_html( $kassmtv_service[0] ); ?></h3>
					<ul>
						<?php foreach ( $kassmtv_service[1] as $kassmtv_item ) : ?>
							<li><?php echo esc_html( $kassmtv_item ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ===== Mission ===== -->
<section class="k-mission" id="about">
	<div class="k-container k-mission-inner">
		<p class="k-eyebrow"><?php esc_html_e( 'Our mission', 'kassmtv' ); ?></p>
		<p class="k-mission-text"><?php echo esc_html( kassmtv_option( 'mission' ) ); ?></p>
		<p class="k-mission-tag"><?php esc_html_e( 'Experience · Execution · Excellence', 'kassmtv' ); ?></p>
	</div>
</section>

<!-- ===== Contact ===== -->
<section class="k-container k-section" id="contact">
	<div class="k-cta">
		<div class="k-cta-copy">
			<h2 class="k-h2"><?php esc_html_e( 'Need a consultation?', 'kassmtv' ); ?></h2>
			<p><?php esc_html_e( 'Tell us what you are building — a broadcast, a website or a platform for your organisation.', 'kassmtv' ); ?></p>
		</div>
		<div class="k-cta-actions">
			<?php if ( $kassmtv_email ) : ?>
				<a class="k-cta-btn k-cta-btn-dark" href="mailto:<?php echo esc_attr( $kassmtv_email ); ?>">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg>
					<span><?php echo esc_html( $kassmtv_email ); ?></span>
				</a>
			<?php endif; ?>
			<?php if ( $kassmtv_phone ) : ?>
				<a class="k-cta-btn" href="tel:<?php echo esc_attr( $kassmtv_tel ); ?>">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>
					<span><?php echo esc_html( $kassmtv_phone ); ?></span>
				</a>
			<?php endif; ?>
			<?php if ( kassmtv_option( 'hours' ) ) : ?>
				<p class="k-cta-hours"><?php echo esc_html( kassmtv_option( 'hours' ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php
wp_reset_postdata();
get_footer();
