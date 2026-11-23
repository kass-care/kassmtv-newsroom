<?php
/**
 * Site header: date bar, logo, menu, search, Watch, Work with us, breaking news.
 *
 * @package kassmtv
 */

$kassmtv_live     = (bool) kassmtv_option( 'live_now' );
$kassmtv_live_url = kassmtv_option( 'live_url' ) ? kassmtv_option( 'live_url' ) : kassmtv_option( 'youtube' );
$kassmtv_watch    = is_front_page() ? '#watch' : home_url( '/#watch' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="theme-color" content="#070B1F">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="k-skip" href="#k-main"><?php esc_html_e( 'Skip to content', 'kassmtv' ); ?></a>

<?php if ( is_singular( 'post' ) ) : ?>
	<div class="k-progress" aria-hidden="true"><span></span></div>
<?php endif; ?>

<div class="k-topbar">
	<div class="k-container k-topbar-inner">
		<span class="k-date" data-k-date><?php echo esc_html( date_i18n( 'l, j F Y' ) ); ?></span>
		<span class="k-topbar-right">
			<?php if ( $kassmtv_live && $kassmtv_live_url ) : ?>
				<a class="k-live-pill" href="<?php echo esc_url( $kassmtv_live_url ); ?>" target="_blank" rel="noopener"><span class="k-dot k-dot-white k-pulse" aria-hidden="true"></span><?php esc_html_e( 'Live now', 'kassmtv' ); ?></a>
			<?php endif; ?>
			<?php
			$kassmtv_tip_page = get_page_by_path( 'send-a-tip', OBJECT, 'page' );

			if ( $kassmtv_tip_page && 'publish' === $kassmtv_tip_page->post_status ) :
				?>
				<a class="k-topbar-link" href="<?php echo esc_url( get_permalink( $kassmtv_tip_page ) ); ?>"><?php esc_html_e( 'Send a tip', 'kassmtv' ); ?></a>
			<?php endif; ?>
			<a class="k-topbar-link" href="<?php echo esc_url( is_front_page() ? '#newsletter' : home_url( '/#newsletter' ) ); ?>"><?php esc_html_e( 'Newsletter', 'kassmtv' ); ?></a>
			<?php if ( kassmtv_advertise_url() ) : ?>
				<a class="k-topbar-link k-topbar-ad" href="<?php echo esc_url( kassmtv_advertise_url() ); ?>"><?php esc_html_e( 'Advertise', 'kassmtv' ); ?></a>
			<?php endif; ?>
			<span class="k-topbar-tag"><?php esc_html_e( 'Diaspora News · Media · Technology', 'kassmtv' ); ?></span>
		</span>
	</div>
</div>

<header class="k-header">
	<div class="k-container k-header-inner">
		<a class="k-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'KASS-mtv Diaspora News home', 'kassmtv' ); ?>">
			<?php echo kassmtv_logo( 52 ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
		</a>

		<nav class="k-nav" id="k-nav" aria-label="<?php esc_attr_e( 'Main', 'kassmtv' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'kass_primary',
					'container'      => false,
					'menu_class'     => 'k-menu',
					'depth'          => 2,
					'fallback_cb'    => 'kassmtv_default_menu',
				)
			);
			?>
		</nav>

		<div class="k-header-actions">
			<button class="k-icon-btn k-search-toggle" type="button" aria-controls="k-search-panel" aria-expanded="false" aria-label="<?php esc_attr_e( 'Search', 'kassmtv' ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
			</button>
			<a class="k-btn k-btn-ghost k-btn-sm k-watch" href="<?php echo esc_url( $kassmtv_live && $kassmtv_live_url ? $kassmtv_live_url : $kassmtv_watch ); ?>">
				<span class="k-dot<?php echo $kassmtv_live ? ' k-pulse' : ''; ?>" aria-hidden="true"></span>
				<?php echo $kassmtv_live ? esc_html__( 'Live', 'kassmtv' ) : esc_html__( 'Watch', 'kassmtv' ); ?>
			</a>
			<a class="k-btn k-btn-primary k-btn-sm k-hide-phone" href="<?php echo esc_url( is_front_page() ? '#contact' : home_url( '/#contact' ) ); ?>">
				<?php esc_html_e( 'Work with us', 'kassmtv' ); ?>
			</a>
			<button class="k-icon-btn k-menu-toggle" type="button" aria-controls="k-nav" aria-expanded="false" aria-label="<?php esc_attr_e( 'Open menu', 'kassmtv' ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
			</button>
		</div>
	</div>

	<div class="k-search-panel" id="k-search-panel" hidden>
		<div class="k-container">
			<?php get_search_form(); ?>
		</div>
	</div>
</header>

<?php
$kassmtv_breaking = kassmtv_breaking_posts();

if ( $kassmtv_breaking ) :
	?>
	<div class="k-breaking" role="region" aria-label="<?php esc_attr_e( 'Breaking news', 'kassmtv' ); ?>">
		<div class="k-container k-breaking-inner">
			<span class="k-breaking-label"><span class="k-dot k-dot-white k-pulse" aria-hidden="true"></span><?php esc_html_e( 'Breaking', 'kassmtv' ); ?></span>
			<?php foreach ( $kassmtv_breaking as $kassmtv_i => $kassmtv_b ) : ?>
				<?php if ( $kassmtv_i > 0 ) : ?>
					<span class="k-breaking-sep" aria-hidden="true">•</span>
				<?php endif; ?>
				<a href="<?php echo esc_url( get_permalink( $kassmtv_b ) ); ?>"><?php echo esc_html( get_the_title( $kassmtv_b ) ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>

<?php $kassmtv_top_ad = is_page( 'advertise' ) ? '' : kassmtv_ad( 'top' ); ?>
<?php if ( $kassmtv_top_ad ) : ?>
	<div class="k-ad-band">
		<div class="k-container">
			<?php echo $kassmtv_top_ad; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
		</div>
	</div>
<?php endif; ?>

<main id="k-main">
