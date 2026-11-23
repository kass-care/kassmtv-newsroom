<?php
/**
 * Site footer.
 *
 * @package kassmtv
 */

$kassmtv_email = kassmtv_option( 'email' );
$kassmtv_phone = kassmtv_option( 'phone' );
$kassmtv_tel   = preg_replace( '/[^0-9+]/', '', (string) $kassmtv_phone );
?>
</main>

<footer class="k-footer">
	<div class="k-container k-footer-grid">
		<div class="k-footer-brand">
			<a class="k-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'KASS-mtv Diaspora News home', 'kassmtv' ); ?>">
				<?php echo kassmtv_logo( 60 ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
			</a>
			<p><?php esc_html_e( 'Connecting communities through media, technology and innovation.', 'kassmtv' ); ?></p>
			<div class="k-socials"><?php echo kassmtv_social_links(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?></div>
		</div>

		<div class="k-footer-col">
			<h2><?php esc_html_e( 'Company', 'kassmtv' ); ?></h2>
			<?php
			if ( has_nav_menu( 'kass_footer' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'kass_footer',
						'container'      => false,
						'menu_class'     => 'k-footer-links',
						'depth'          => 1,
					)
				);
			} else {
				$kassmtv_about   = get_page_by_path( 'about-us' );
				$kassmtv_contact = get_page_by_path( 'contacts' );
				?>
				<ul class="k-footer-links">
					<li><a href="<?php echo esc_url( $kassmtv_about ? get_permalink( $kassmtv_about ) : home_url( '/#about' ) ); ?>"><?php esc_html_e( 'About us', 'kassmtv' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/#services' ) ); ?>"><?php esc_html_e( 'Services', 'kassmtv' ); ?></a></li>
					<li><a href="<?php echo esc_url( $kassmtv_contact ? get_permalink( $kassmtv_contact ) : home_url( '/#contact' ) ); ?>"><?php esc_html_e( 'Contact', 'kassmtv' ); ?></a></li>
				</ul>
				<?php
			}
			?>
		</div>

		<?php $kassmtv_newsroom = kassmtv_newsroom_links(); ?>
		<?php if ( $kassmtv_newsroom ) : ?>
			<div class="k-footer-col">
				<h2><?php esc_html_e( 'Newsroom', 'kassmtv' ); ?></h2>
				<ul class="k-footer-links">
					<?php foreach ( $kassmtv_newsroom as $kassmtv_label => $kassmtv_url ) : ?>
						<li><a href="<?php echo esc_url( $kassmtv_url ); ?>"><?php echo esc_html( $kassmtv_label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div class="k-footer-col">
			<h2><?php esc_html_e( 'Platforms', 'kassmtv' ); ?></h2>
			<ul class="k-footer-links">
				<?php foreach ( kassmtv_platforms() as $kassmtv_platform ) : ?>
					<li><a href="<?php echo esc_url( $kassmtv_platform['link'] ? $kassmtv_platform['link'] : home_url( '/#platforms' ) ); ?>"><?php echo esc_html( $kassmtv_platform['name'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="k-footer-col">
			<h2><?php esc_html_e( 'News', 'kassmtv' ); ?></h2>
			<ul class="k-footer-links">
				<?php foreach ( kassmtv_news_categories( 5 ) as $kassmtv_category ) : ?>
					<li><a href="<?php echo esc_url( get_category_link( $kassmtv_category->term_id ) ); ?>"><?php echo esc_html( $kassmtv_category->name ); ?></a></li>
				<?php endforeach; ?>
				<li><a href="<?php echo esc_url( kassmtv_news_url() ); ?>"><?php esc_html_e( 'All news', 'kassmtv' ); ?></a></li>
			</ul>
		</div>
	</div>

	<div class="k-container k-footer-bottom">
		<span>
			<?php
			/* translators: %s: year */
			echo esc_html( sprintf( __( '© %s KASS MTV USA LLC. All rights reserved.', 'kassmtv' ), gmdate( 'Y' ) ) );
			?>
		</span>
		<span>
			<?php if ( $kassmtv_email ) : ?>
				<a href="mailto:<?php echo esc_attr( $kassmtv_email ); ?>"><?php echo esc_html( $kassmtv_email ); ?></a>
			<?php endif; ?>
			<?php if ( $kassmtv_email && $kassmtv_phone ) : ?>
				·
			<?php endif; ?>
			<?php if ( $kassmtv_phone ) : ?>
				<a href="tel:<?php echo esc_attr( $kassmtv_tel ); ?>"><?php echo esc_html( $kassmtv_phone ); ?></a>
			<?php endif; ?>
			<?php if ( kassmtv_advertise_url() ) : ?>
				· <a href="<?php echo esc_url( kassmtv_advertise_url() ); ?>"><?php esc_html_e( 'Advertise with us', 'kassmtv' ); ?></a>
			<?php endif; ?>
		</span>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
