<?php
/**
 * KASS-mtv speed: load heavy plugin files only where they are used.
 *
 * Slider Revolution and Contact Form 7 add their files to every page.
 * Here they are kept only on pages whose content actually uses them
 * (as a shortcode or an Elementor widget). Everything else gets lighter.
 *
 * @package kassmtv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Does the current page use any of these (shortcode or Elementor data)?
 */
function kassmtv_page_uses( $needles ) {
	if ( ! is_singular() ) {
		return false;
	}

	$post = get_queried_object();

	if ( ! $post || empty( $post->ID ) ) {
		return false;
	}

	$haystack = (string) $post->post_content . ' ' . (string) get_post_meta( $post->ID, '_elementor_data', true );

	foreach ( (array) $needles as $needle ) {
		if ( false !== stripos( $haystack, $needle ) ) {
			return true;
		}
	}

	return false;
}

function kassmtv_plugin_asset_rules() {
	return apply_filters(
		'kassmtv_plugin_asset_rules',
		array(
			'/plugins/revslider/'      => array( 'rev_slider', 'rev-slider', 'revslider', 'slider_revolution', 'sr7' ),
			'/plugins/contact-form-7/' => array( 'contact-form-7', 'contact_form_7', 'wpcf7' ),
		)
	);
}

function kassmtv_trim_plugin_assets() {
	if ( is_admin() || is_customize_preview() || ( defined( 'ELEMENTOR_VERSION' ) && isset( $_GET['elementor-preview'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$rules = kassmtv_plugin_asset_rules();
	$drop  = array();

	foreach ( $rules as $path => $needles ) {
		if ( ! kassmtv_page_uses( $needles ) ) {
			$drop[] = $path;
		}
	}

	if ( ! $drop ) {
		return;
	}

	foreach ( array( wp_scripts(), wp_styles() ) as $deps ) {
		foreach ( $deps->queue as $handle ) {
			$item = isset( $deps->registered[ $handle ] ) ? $deps->registered[ $handle ] : null;

			if ( ! $item || ! is_string( $item->src ) ) {
				continue;
			}

			foreach ( $drop as $path ) {
				if ( false !== strpos( $item->src, $path ) ) {
					if ( $deps instanceof WP_Scripts ) {
						wp_dequeue_script( $handle );
					} else {
						wp_dequeue_style( $handle );
					}
				}
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'kassmtv_trim_plugin_assets', 9999 );
add_action( 'wp_print_styles', 'kassmtv_trim_plugin_assets', 9999 );
add_action( 'wp_print_scripts', 'kassmtv_trim_plugin_assets', 9999 );

/**
 * No emoji script: phones and computers draw emoji themselves.
 */
function kassmtv_no_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'kassmtv_no_emoji' );

/**
 * Connect early to YouTube's picture server on the homepage.
 */
function kassmtv_speed_hints( $urls, $relation ) {
	if ( 'preconnect' === $relation && is_front_page() ) {
		$urls[] = 'https://i.ytimg.com';
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'kassmtv_speed_hints', 10, 2 );
