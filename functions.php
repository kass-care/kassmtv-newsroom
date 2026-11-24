<?php
/**
 * KASS-mtv theme — setup, settings and small helpers.
 *
 * @package kassmtv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KASSMTV_VERSION', '1.4.1' );

require_once __DIR__ . '/inc/forms.php';
require_once __DIR__ . '/inc/newsroom-pages.php';
require_once __DIR__ . '/inc/speed.php';
require_once __DIR__ . '/inc/autopilot.php';
require_once __DIR__ . '/inc/ads.php';

/* -------------------------------------------------------------------------
 * Same settings in every copy of this theme.
 * The theme may run from another folder (e.g. kassmtv-live, updated from
 * GitHub). The first time it runs there, it takes over the menus, links and
 * Customizer settings saved for the original "kassmtv" folder.
 * ---------------------------------------------------------------------- */
function kassmtv_carry_settings() {
	$here = get_stylesheet();

	if ( 'kassmtv' === $here || get_option( 'kassmtv_settings_carried_' . $here ) ) {
		return;
	}

	$old = get_option( 'theme_mods_kassmtv' );

	if ( is_array( $old ) && $old ) {
		$mine = get_option( 'theme_mods_' . $here );
		$mine = is_array( $mine ) ? $mine : array();

		update_option( 'theme_mods_' . $here, array_merge( $mine, $old ) );
	}

	update_option( 'kassmtv_settings_carried_' . $here, time() );
}
add_action( 'after_setup_theme', 'kassmtv_carry_settings', 1 );

/* -------------------------------------------------------------------------
 * Theme setup
 * ---------------------------------------------------------------------- */
function kassmtv_setup() {
	load_theme_textdomain( 'kassmtv', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

	// Own menu spots, so the old theme's menus don't land here by surprise.
	register_nav_menus(
		array(
			'kass_primary' => __( 'KASS-mtv: top menu', 'kassmtv' ),
			'kass_footer'  => __( 'KASS-mtv: footer menu', 'kassmtv' ),
		)
	);

	add_image_size( 'kassmtv-card', 720, 450, true );
	add_image_size( 'kassmtv-wide', 1280, 720, true );
}
add_action( 'after_setup_theme', 'kassmtv_setup' );

function kassmtv_content_width() {
	$GLOBALS['content_width'] = 760;
}
add_action( 'after_setup_theme', 'kassmtv_content_width', 0 );

/* -------------------------------------------------------------------------
 * Styles and scripts
 * ---------------------------------------------------------------------- */
function kassmtv_assets() {
	wp_enqueue_style(
		'kassmtv-fonts',
		'https://fonts.googleapis.com/css2?family=Archivo:wght@700;800;900&family=Inter:wght@400;600;700;800&family=Nunito:wght@800&family=Source+Serif+4:ital,opsz,wght@0,8..60,400;0,8..60,700;1,8..60,400&display=swap',
		array(),
		null
	);

	wp_enqueue_style( 'kassmtv-style', get_stylesheet_uri(), array( 'kassmtv-fonts' ), KASSMTV_VERSION );

	wp_enqueue_script(
		'kassmtv-theme',
		get_template_directory_uri() . '/assets/js/theme.js',
		array(),
		KASSMTV_VERSION,
		true
	);

	wp_add_inline_script(
		'kassmtv-theme',
		'window.kassmtv = ' . wp_json_encode(
			array(
				'viewUrl' => esc_url_raw( rest_url( 'kassmtv/v1/view/' ) ),
				'postId'  => is_singular( 'post' ) ? get_the_ID() : 0,
			)
		) . ';',
		'before'
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'kassmtv_assets' );

function kassmtv_resource_hints( $urls, $relation ) {
	if ( 'preconnect' === $relation ) {
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'kassmtv_resource_hints', 10, 2 );

/* -------------------------------------------------------------------------
 * Settings (Appearance → Customize → KASS-mtv settings)
 * ---------------------------------------------------------------------- */
function kassmtv_defaults() {
	return array(
		'kicker'        => 'Diaspora News · Media · Technology',
		'hero_title'    => 'Connecting communities through media and technology.',
		'hero_text'     => 'News, video and live broadcasting — plus the software that runs care homes, classrooms, churches and businesses.',
		'mission'       => 'We inform, educate and empower communities — through journalism, storytelling and technology.',
		'email'         => 'info@kassmtvusa.com',
		'phone'         => '+1 (206) 602-8000',
		'hours'         => 'Mon–Sat · 8:00 AM–7:00 PM',
		'youtube'       => 'https://www.youtube.com/@kassmtv254',
		'facebook'      => '',
		'instagram'     => '',
		'linkedin'      => '',
		'x'             => '',
		'whatsapp'      => 'https://whatsapp.com/channel/0029VbDnjBmDeONBd3gBwJ2t',
		'showreel'      => '',
		'building_text' => '',
		'youtube_id'    => 'UCzQkI8zNGjQzC75lG9kIHjA',
		'live_now'      => false,
		'live_url'      => '',
	);
}

function kassmtv_option( $key ) {
	$defaults = kassmtv_defaults();
	$value    = get_theme_mod( 'kassmtv_' . $key, isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );

	return is_string( $value ) ? trim( $value ) : $value;
}

function kassmtv_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'kassmtv_settings',
		array(
			'title'       => __( 'KASS-mtv settings', 'kassmtv' ),
			'description' => __( 'Words, contact details and social links used across the website.', 'kassmtv' ),
			'priority'    => 30,
		)
	);

	$fields = array(
		'kicker'        => array( __( 'Small line above the big headline', 'kassmtv' ), 'text' ),
		'hero_title'    => array( __( 'Big headline', 'kassmtv' ), 'text' ),
		'hero_text'     => array( __( 'Text under the headline', 'kassmtv' ), 'textarea' ),
		'mission'       => array( __( 'Mission sentence', 'kassmtv' ), 'textarea' ),
		'email'         => array( __( 'Email', 'kassmtv' ), 'email' ),
		'phone'         => array( __( 'Phone', 'kassmtv' ), 'text' ),
		'hours'         => array( __( 'Opening hours', 'kassmtv' ), 'text' ),
		'youtube'       => array( __( 'YouTube link', 'kassmtv' ), 'url' ),
		'facebook'      => array( __( 'Facebook link', 'kassmtv' ), 'url' ),
		'instagram'     => array( __( 'Instagram link', 'kassmtv' ), 'url' ),
		'linkedin'      => array( __( 'LinkedIn link', 'kassmtv' ), 'url' ),
		'x'             => array( __( 'X (Twitter) link', 'kassmtv' ), 'url' ),
		'whatsapp'      => array( __( 'WhatsApp channel link (shows a "Follow us on WhatsApp" button next to stories)', 'kassmtv' ), 'url' ),
		'showreel'      => array( __( 'Showreel video (YouTube video link)', 'kassmtv' ), 'url' ),
		'building_text' => array( __( 'KASS BUILDING — one line (the card stays hidden while empty)', 'kassmtv' ), 'text' ),
		'youtube_id'    => array( __( 'YouTube channel ID (starts with UC…) — only if the video row does not appear', 'kassmtv' ), 'text' ),
		'live_now'      => array( __( 'We are LIVE now (shows the red LIVE badge)', 'kassmtv' ), 'checkbox' ),
		'live_url'      => array( __( 'Live stream link (YouTube live or Facebook live)', 'kassmtv' ), 'url' ),
	);

	$defaults = kassmtv_defaults();

	foreach ( $fields as $key => $field ) {
		list( $label, $type ) = $field;

		if ( 'checkbox' === $type ) {
			$sanitize = 'wp_validate_boolean';
		} elseif ( 'url' === $type ) {
			$sanitize = 'esc_url_raw';
		} elseif ( 'email' === $type ) {
			$sanitize = 'sanitize_email';
		} elseif ( 'textarea' === $type ) {
			$sanitize = 'sanitize_textarea_field';
		} else {
			$sanitize = 'sanitize_text_field';
		}

		$wp_customize->add_setting(
			'kassmtv_' . $key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => $sanitize,
			)
		);

		$wp_customize->add_control(
			'kassmtv_' . $key,
			array(
				'label'   => $label,
				'section' => 'kassmtv_settings',
				'type'    => $type,
			)
		);
	}
}
add_action( 'customize_register', 'kassmtv_customize_register' );

/* -------------------------------------------------------------------------
 * Helpers used by the templates
 * ---------------------------------------------------------------------- */

/**
 * The KASS-mtv logo, drawn as SVG so it stays sharp on dark backgrounds.
 */
function kassmtv_logo( $height = 52 ) {
	$height = (int) $height;
	$width  = (int) round( $height * 214 / 78 );

	return sprintf(
		'<svg class="k-logo-svg" width="%1$d" height="%2$d" viewBox="-2 0 214 78" role="img" aria-label="%3$s">'
		. '<path d="M185 22.3 L152 8 Q141 3.3 140.6 15 L138.3 63 Q137.8 73.8 148 68.6 L197 43.6 Q206 39 199 31.3" fill="none" stroke="#E0262B" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>'
		. '<text x="0" y="38" textLength="187" lengthAdjust="spacingAndGlyphs" fill="#FFFFFF" style="font-family:Nunito,Inter,sans-serif;font-weight:800;font-size:40px">KASS-mtv</text>'
		. '<text x="50" y="60" textLength="82" lengthAdjust="spacingAndGlyphs" fill="#C3CBE6" style="font-family:Inter,sans-serif;font-weight:700;font-size:12px">DIASPORA NEWS</text>'
		. '</svg>',
		$width,
		$height,
		esc_attr__( 'KASS-mtv Diaspora News', 'kassmtv' )
	);
}

/**
 * The name readers see as the writer of a post.
 * Newsroom autopilot stories show "KASS-mtv Newsroom".
 *
 * @param int|WP_Post|null $post Post.
 */
function kassmtv_author_name( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	$byline = kassmtv_newsroom_byline( $post );

	return $byline ? $byline : get_the_author_meta( 'display_name', $post->post_author );
}

/**
 * Where "All news" goes: the posts page if one is set, else the blog index.
 */
function kassmtv_news_url() {
	$posts_page = (int) get_option( 'page_for_posts' );

	if ( $posts_page ) {
		return get_permalink( $posts_page );
	}

	return home_url( '/blog/' );
}

/**
 * First category of a post, as array( name, url ).
 */
function kassmtv_post_category( $post_id = null ) {
	$categories = get_the_category( $post_id );

	if ( empty( $categories ) ) {
		return null;
	}

	foreach ( $categories as $category ) {
		if ( 'uncategorized' !== $category->slug ) {
			return array( $category->name, get_category_link( $category->term_id ) );
		}
	}

	return array( $categories[0]->name, get_category_link( $categories[0]->term_id ) );
}

/**
 * A post's picture, or a calm placeholder when it has none.
 */
function kassmtv_post_image( $post_id, $size = 'kassmtv-card', $class = 'k-img', $first = false ) {
	if ( has_post_thumbnail( $post_id ) ) {
		if ( function_exists( 'kassmtv_has_news_graphic' ) && kassmtv_has_news_graphic( $post_id ) ) {
			$class .= ' is-graphic';
		}

		$attr = array(
			'class'   => $class,
			'loading' => $first ? 'eager' : 'lazy',
			'alt'     => esc_attr( get_the_title( $post_id ) ),
		);

		if ( $first ) {
			$attr['fetchpriority'] = 'high';
			$attr['decoding']      = 'async';
		}

		return get_the_post_thumbnail( $post_id, $size, $attr );
	}

	return '<div class="' . esc_attr( $class ) . ' k-ph" aria-hidden="true"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 5v14l11-7z"/></svg></div>';
}

/**
 * Social links that have been filled in, in a fixed order.
 */
function kassmtv_social_links() {
	$icons = array(
		'facebook'  => array( 'Facebook', '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>' ),
		'instagram' => array( 'Instagram', '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>' ),
		'youtube'   => array( 'YouTube', '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9l5 3-5 3z"/>' ),
		'linkedin'  => array( 'LinkedIn', '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>' ),
		'x'         => array( 'X', '<path d="M4 4l16 16M20 4L4 20"/>' ),
		'whatsapp'  => array( 'WhatsApp channel', '<path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4z"/><path d="M9 9.5c.3 1.6 1.9 3.6 3.6 4.3l1.2-1.1 2 1v1.2c-3.2.8-7.3-3.3-7-6.6h1.3l.8 1.9z"/>' ),
	);

	$html = '';

	foreach ( $icons as $key => $icon ) {
		$url = kassmtv_option( $key );

		if ( ! $url ) {
			continue;
		}

		$html .= sprintf(
			'<a class="k-social" href="%1$s" target="_blank" rel="noopener" aria-label="%2$s"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%3$s</svg></a>',
			esc_url( $url ),
			esc_attr( $icon[0] ),
			$icon[1]
		);
	}

	return $html;
}

/**
 * The KASS platforms shown on the homepage.
 */
function kassmtv_platforms() {
	$platforms = array(
		array(
			'name'  => 'KassCare',
			'short' => __( 'Care homes & caregivers', 'kassmtv' ),
			'text'  => __( 'Healthcare operations for adult family homes, caregivers and providers: care notes, workflows and compliance in one place.', 'kassmtv' ),
			'link'  => 'https://app.kasscarehealth.com/register-facility',
			'cta'   => __( 'Get started', 'kassmtv' ),
			'icon'  => '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7z"/><path d="M3.2 12h4.3l1.5-3 3 6 1.5-3h7.3"/>',
		),
		array(
			'name'  => 'Kass EDTECH',
			'short' => __( 'Live classes on any phone', 'kassmtv' ),
			'text'  => __( 'Live online classes that work on any phone: teacher video, chat, reactions, attendance and a data saver for students.', 'kassmtv' ),
			'link'  => 'https://os.kassmtvusa.com/edtech',
			'cta'   => __( 'Open the classroom', 'kassmtv' ),
			'icon'  => '<path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/>',
		),
		array(
			'name'  => 'Kass Church',
			'short' => __( 'Members, giving, finances', 'kassmtv' ),
			'text'  => __( 'Church administration made simple: members, giving and finances, all in one secure system.', 'kassmtv' ),
			'link'  => 'https://church.kasscare.health/',
			'cta'   => __( 'Open Kass Church', 'kassmtv' ),
			'icon'  => '<path d="M12 2v6M9 5h6"/><path d="M5 22V12l7-4 7 4v10"/><path d="M10 22v-5h4v5"/>',
		),
		array(
			'name'  => 'KassBooking',
			'short' => __( 'Stays & payments', 'kassmtv' ),
			'text'  => __( 'Reservations and payments for guest houses and accommodation, so hosts can focus on guests.', 'kassmtv' ),
			'link'  => '',
			'cta'   => '',
			'icon'  => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M8 15l2.5 2.5L16 13"/>',
		),
		array(
			'name'  => 'KASS SHIFT',
			'short' => __( 'Post & pick up shifts', 'kassmtv' ),
			'text'  => __( 'Shift work made simple: employers post shifts, workers pick them up, and employers approve and pay in one place.', 'kassmtv' ),
			'link'  => 'https://shift.kassmtvusa.com/',
			'cta'   => __( 'Open KASS SHIFT', 'kassmtv' ),
			'icon'  => '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2.5"/><path d="M9 2h6"/>',
		),
	);

	$building = kassmtv_option( 'building_text' );

	if ( $building ) {
		$platforms[] = array(
			'name'  => 'KASS BUILDING',
			'short' => wp_trim_words( $building, 4, '…' ),
			'text'  => $building,
			'link'  => '',
			'cta'   => '',
			'icon'  => '<path d="M3 21h18"/><path d="M5 21V8l7-5 7 5v13"/><path d="M9 21v-6h6v6"/><path d="M9 10h.01M15 10h.01"/>',
		);
	}

	return apply_filters( 'kassmtv_platforms', $platforms );
}

/**
 * Menu shown until a menu is chosen in Appearance → Menus.
 */
function kassmtv_default_menu() {
	$about   = get_page_by_path( 'about-us' );
	$contact = get_page_by_path( 'contacts' );

	$items = array(
		array( __( 'News', 'kassmtv' ), kassmtv_news_url() ),
		array( __( 'Platforms', 'kassmtv' ), home_url( '/#platforms' ) ),
		array( __( 'Services', 'kassmtv' ), home_url( '/#services' ) ),
		array( __( 'About', 'kassmtv' ), $about ? get_permalink( $about ) : home_url( '/#about' ) ),
		array( __( 'Contact', 'kassmtv' ), $contact ? get_permalink( $contact ) : home_url( '/#contact' ) ),
	);

	echo '<ul class="k-menu">';

	foreach ( $items as $item ) {
		printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $item[1] ), esc_html( $item[0] ) );
	}

	echo '</ul>';
}

/**
 * Up to $limit categories that have posts, biggest first.
 */
function kassmtv_news_categories( $limit = 5 ) {
	$categories = get_categories(
		array(
			'orderby'    => 'count',
			'order'      => 'DESC',
			'hide_empty' => true,
			'number'     => $limit + 1,
		)
	);

	$categories = array_filter(
		$categories,
		function ( $category ) {
			return 'uncategorized' !== $category->slug;
		}
	);

	return array_slice( array_values( $categories ), 0, $limit );
}

/**
 * Shorter excerpts on cards.
 */
function kassmtv_excerpt_length() {
	return 24;
}
add_filter( 'excerpt_length', 'kassmtv_excerpt_length' );

function kassmtv_excerpt_more() {
	return '…';
}
add_filter( 'excerpt_more', 'kassmtv_excerpt_more' );

/**
 * Reading time, e.g. "4 min read".
 */
function kassmtv_read_time( $post_id = null ) {
	$words   = str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) );
	$minutes = max( 1, (int) ceil( $words / 220 ) );

	/* translators: %d: minutes */
	return sprintf( _n( '%d min read', '%d min read', $minutes, 'kassmtv' ), $minutes );
}

/* -------------------------------------------------------------------------
 * 1.1 — Newsroom features
 * ---------------------------------------------------------------------- */

/**
 * "3 hours ago" for recent stories, the date for older ones.
 */
function kassmtv_time_ago( $post = null, $modified = false ) {
	$time = $modified ? get_post_modified_time( 'U', true, $post ) : get_post_time( 'U', true, $post );
	$diff = time() - (int) $time;

	if ( $diff >= 0 && $diff < DAY_IN_SECONDS ) {
		/* translators: %s: time, e.g. "3 hours" */
		return sprintf( __( '%s ago', 'kassmtv' ), human_time_diff( (int) $time, time() ) );
	}

	return $modified ? get_the_modified_date( '', $post ) : get_the_date( '', $post );
}

/**
 * Breaking news: posts tagged "breaking" from the last 48 hours.
 */
function kassmtv_breaking_posts() {
	return get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'tag'              => 'breaking',
			'posts_per_page'   => 3,
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'date_query'       => array( array( 'after' => '48 hours ago' ) ),
		)
	);
}

/**
 * Most read in the last 30 days (needs at least $min stories with readers).
 */
function kassmtv_most_read( $limit = 5, $min = 3 ) {
	$posts = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'posts_per_page'   => $limit,
			'meta_key'         => 'kassmtv_views',
			'orderby'          => 'meta_value_num',
			'order'            => 'DESC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'date_query'       => array( array( 'after' => '30 days ago' ) ),
		)
	);

	return count( $posts ) >= $min ? $posts : array();
}

/**
 * Count one read. Called by the story page's script, so it also works
 * when the page itself comes from the cache.
 */
function kassmtv_register_routes() {
	register_rest_route(
		'kassmtv/v1',
		'/view/(?P<id>\d+)',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'args'                => array(
				'id' => array(
					'validate_callback' => function ( $value ) {
						return is_numeric( $value );
					},
				),
			),
			'callback'            => function ( $request ) {
				$post_id = (int) $request['id'];

				if ( 'post' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
					return new WP_REST_Response( array( 'ok' => false ), 404 );
				}

				$views = (int) get_post_meta( $post_id, 'kassmtv_views', true );
				update_post_meta( $post_id, 'kassmtv_views', $views + 1 );

				return new WP_REST_Response( array( 'ok' => true ), 200 );
			},
		)
	);
}
add_action( 'rest_api_init', 'kassmtv_register_routes' );

/**
 * The YouTube channel ID: from the settings, else looked up once from
 * the channel link and remembered for a week.
 */
function kassmtv_youtube_channel_id() {
	$manual = kassmtv_option( 'youtube_id' );

	if ( $manual && preg_match( '/^UC[\w-]{22}$/', $manual ) ) {
		return $manual;
	}

	$url = kassmtv_option( 'youtube' );

	if ( ! $url ) {
		return '';
	}

	if ( preg_match( '#/channel/(UC[\w-]{22})#', $url, $match ) ) {
		return $match[1];
	}

	$key    = 'kassmtv_yt_id_' . md5( $url );
	$cached = get_transient( $key );

	if ( false !== $cached ) {
		return $cached;
	}

	$id       = '';
	$response = wp_remote_get(
		$url,
		array(
			'timeout'    => 8,
			'user-agent' => 'Mozilla/5.0 (compatible; KASS-mtv website)',
			'headers'    => array( 'Accept-Language' => 'en-US,en;q=0.8' ),
			'cookies'    => array( 'CONSENT' => 'YES+1' ),
		)
	);

	if ( ! is_wp_error( $response ) ) {
		$body = wp_remote_retrieve_body( $response );

		if ( preg_match( '#"(?:externalId|channelId)":"(UC[\w-]{22})"#', $body, $match )
			|| preg_match( '#youtube\.com/channel/(UC[\w-]{22})#', $body, $match ) ) {
			$id = $match[1];
		}
	}

	// Remember a miss for an hour, a hit for a week.
	set_transient( $key, $id, $id ? WEEK_IN_SECONDS : HOUR_IN_SECONDS );

	return $id;
}

function kassmtv_feed_lifetime() {
	return HOUR_IN_SECONDS;
}

/**
 * Latest videos from the channel's public feed.
 * Each: array( id, title, url, date ).
 */
function kassmtv_youtube_videos( $limit = 4 ) {
	$channel = kassmtv_youtube_channel_id();

	if ( ! $channel ) {
		return array();
	}

	$key    = 'kassmtv_yt_videos_' . $channel;
	$cached = get_transient( $key );

	if ( is_array( $cached ) ) {
		return array_slice( $cached, 0, $limit );
	}

	include_once ABSPATH . WPINC . '/feed.php';

	add_filter( 'wp_feed_cache_transient_lifetime', 'kassmtv_feed_lifetime' );
	$feed = fetch_feed( 'https://www.youtube.com/feeds/videos.xml?channel_id=' . rawurlencode( $channel ) );
	remove_filter( 'wp_feed_cache_transient_lifetime', 'kassmtv_feed_lifetime' );

	$videos = array();

	if ( ! is_wp_error( $feed ) ) {
		foreach ( $feed->get_items( 0, 8 ) as $item ) {
			$link = $item->get_permalink();

			if ( ! preg_match( '#[?&]v=([\w-]{11})#', (string) $link, $match ) ) {
				continue;
			}

			$videos[] = array(
				'id'    => $match[1],
				'title' => wp_strip_all_tags( (string) $item->get_title() ),
				'url'   => $link,
				'date'  => (int) $item->get_date( 'U' ),
			);
		}
	}

	set_transient( $key, $videos, $videos ? HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS );

	return array_slice( $videos, 0, $limit );
}

/**
 * "Key points" from the post's own excerpt box: one point per line.
 */
function kassmtv_key_points( $post = null ) {
	$post = get_post( $post );

	if ( ! $post || '' === trim( (string) $post->post_excerpt ) ) {
		return array();
	}

	$lines = preg_split( '/\r\n|\r|\n/', wp_strip_all_tags( $post->post_excerpt ) );
	$lines = array_values( array_filter( array_map( 'trim', $lines ) ) );

	return array_map(
		function ( $line ) {
			return ltrim( $line, "-•* \t" );
		},
		$lines
	);
}

/**
 * Newsletter form, only when the Mailchimp plugin has a form ready.
 */
function kassmtv_newsletter_form() {
	if ( ! function_exists( 'mc4wp_get_forms' ) || ! shortcode_exists( 'mc4wp_form' ) ) {
		return '';
	}

	$forms = mc4wp_get_forms();

	if ( empty( $forms ) ) {
		return '';
	}

	return do_shortcode( '[mc4wp_form]' );
}

/* -------------------------------------------------------------------------
 * Share previews (WhatsApp, Facebook, X) and Google News article data.
 * Skipped when an SEO plugin already does this.
 * ---------------------------------------------------------------------- */
function kassmtv_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' )
		|| defined( 'RANK_MATH_VERSION' )
		|| defined( 'AIOSEO_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| defined( 'THE_SEO_FRAMEWORK_VERSION' );
}

function kassmtv_share_meta() {
	if ( kassmtv_seo_plugin_active() ) {
		return;
	}

	$site  = get_bloginfo( 'name' );
	$title = wp_get_document_title();
	$desc  = get_bloginfo( 'description' );
	$url   = home_url( add_query_arg( array() ) );
	$image = '';
	$type  = 'website';

	if ( is_singular() ) {
		$post  = get_queried_object();
		$title = wp_strip_all_tags( get_the_title( $post ) );
		$desc  = has_excerpt( $post ) ? wp_strip_all_tags( get_the_excerpt( $post ) ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 30, '…' );
		$url   = get_permalink( $post );
		$type  = is_singular( 'post' ) ? 'article' : 'website';

		if ( has_post_thumbnail( $post ) ) {
			$image = get_the_post_thumbnail_url( $post, 'large' );
		}
	}

	if ( ! $image && has_site_icon() ) {
		$image = get_site_icon_url( 512 );
	}

	$desc = wp_trim_words( preg_replace( '/\s+/', ' ', (string) $desc ), 40, '…' );

	echo "\n<!-- KASS-mtv share previews -->\n";
	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( $site ) );
	printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );

	if ( $image ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
	}

	printf( '<meta name="twitter:card" content="%s">' . "\n", $image ? 'summary_large_image' : 'summary' );

	if ( is_singular( 'post' ) ) {
		$post = get_queried_object();

		printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_post_time( 'c', true, $post ) ) );
		printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_post_modified_time( 'c', true, $post ) ) );

		$schema = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'NewsArticle',
			'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
			'datePublished'    => get_post_time( 'c', true, $post ),
			'dateModified'     => get_post_modified_time( 'c', true, $post ),
			'mainEntityOfPage' => get_permalink( $post ),
			'author'           => kassmtv_newsroom_byline( $post )
				? array(
					'@type' => 'NewsMediaOrganization',
					'name'  => kassmtv_newsroom_byline( $post ),
					'url'   => home_url( '/' ),
				)
				: array(
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', $post->post_author ),
				),
			'publisher'        => array(
				'@type' => 'NewsMediaOrganization',
				'name'  => 'KASS-mtv Diaspora News',
				'url'   => home_url( '/' ),
			),
		);

		if ( $image ) {
			$schema['image'] = array( $image );
		}

		if ( has_site_icon() ) {
			$schema['publisher']['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => get_site_icon_url( 512 ),
			);
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	}
}
add_action( 'wp_head', 'kassmtv_share_meta', 5 );

/* -------------------------------------------------------------------------
 * 1.4 — Column next to each story: ad, trending, latest, WhatsApp
 * ---------------------------------------------------------------------- */

/**
 * Latest published stories, leaving out the ones given.
 */
function kassmtv_latest_posts( $limit = 5, $exclude = array() ) {
	return get_posts(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'post__not_in'        => array_map( 'intval', (array) $exclude ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'suppress_filters'    => false,
		)
	);
}

function kassmtv_story_sidebar( $post_id ) {
	$trending = array_values(
		array_filter(
			kassmtv_most_read( 6, 3 ),
			function ( $post ) use ( $post_id ) {
				return (int) $post->ID !== (int) $post_id;
			}
		)
	);
	$trending = array_slice( $trending, 0, 5 );
	$skip     = array_merge( array( $post_id ), wp_list_pluck( $trending, 'ID' ) );
	$latest   = kassmtv_latest_posts( 4, $skip );
	$whatsapp = kassmtv_option( 'whatsapp' );

	ob_start();
	?>
	<aside class="k-story-side" aria-label="<?php esc_attr_e( 'More from KASS-mtv', 'kassmtv' ); ?>">
		<?php echo kassmtv_ad( 'side' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>

		<?php if ( $whatsapp ) : ?>
			<a class="k-side-wa" href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4z"/><path d="M9 9.5c.3 1.6 1.9 3.6 3.6 4.3l1.2-1.1 2 1v1.2c-3.2.8-7.3-3.3-7-6.6h1.3l.8 1.9z"/></svg>
				<span><strong><?php esc_html_e( 'Follow us on WhatsApp', 'kassmtv' ); ?></strong><?php esc_html_e( 'Get the top stories on your phone', 'kassmtv' ); ?></span>
			</a>
		<?php endif; ?>

		<?php if ( $trending ) : ?>
			<section class="k-side-box">
				<h2 class="k-side-head"><span class="k-dot k-pulse" aria-hidden="true"></span><?php esc_html_e( 'Trending now', 'kassmtv' ); ?></h2>
				<ol class="k-side-rank">
					<?php foreach ( $trending as $n => $post ) : ?>
						<li>
							<span class="k-side-num" aria-hidden="true"><?php echo esc_html( $n + 1 ); ?></span>
							<a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
						</li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endif; ?>

		<?php if ( $latest ) : ?>
			<section class="k-side-box">
				<h2 class="k-side-head"><?php esc_html_e( 'Latest news', 'kassmtv' ); ?></h2>
				<ul class="k-side-list">
					<?php foreach ( $latest as $post ) : ?>
						<?php $cat = kassmtv_post_category( $post->ID ); ?>
						<li>
							<a class="k-side-item" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
								<span class="k-side-thumb"><?php echo kassmtv_post_image( $post->ID, 'thumbnail', 'k-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								<span class="k-side-text">
									<?php if ( $cat ) : ?>
										<span class="k-side-cat"><?php echo esc_html( $cat[0] ); ?></span>
									<?php endif; ?>
									<span class="k-sidelist-title"><?php echo esc_html( get_the_title( $post ) ); ?></span>
									<span class="k-side-time"><?php echo esc_html( kassmtv_time_ago( $post ) ); ?></span>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	</aside>
	<?php
	return ob_get_clean();
}

/**
 * Link to the "Advertise with us" page, or ''.
 */
function kassmtv_advertise_url() {
	$page = function_exists( 'kassmtv_advertise_page' ) ? kassmtv_advertise_page() : null;

	return $page ? get_permalink( $page ) : '';
}

/**
 * Green "Follow us on WhatsApp" bar (story end, homepage). '' when no channel link.
 */
function kassmtv_whatsapp_cta( $where = 'story' ) {
	$url = kassmtv_option( 'whatsapp' );

	if ( ! $url ) {
		return '';
	}

	return sprintf(
		'<a class="k-wa-cta k-wa-cta-%1$s" href="%2$s" target="_blank" rel="noopener"><span class="k-wa-cta-icon" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4z"/><path d="M9 9.5c.3 1.6 1.9 3.6 3.6 4.3l1.2-1.1 2 1v1.2c-3.2.8-7.3-3.3-7-6.6h1.3l.8 1.9z"/></svg></span><span class="k-wa-cta-text"><strong>%3$s</strong><span>%4$s</span></span><span class="k-wa-cta-btn">%5$s</span></a>',
		esc_attr( $where ),
		esc_url( $url ),
		esc_html__( 'Get KASS-mtv news on WhatsApp', 'kassmtv' ),
		esc_html__( 'Kenya, the diaspora, world news and sports — free, every day.', 'kassmtv' ),
		esc_html__( 'Follow the channel', 'kassmtv' )
	);
}
