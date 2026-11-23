<?php
/**
 * KASS-mtv trust pages: About the newsroom, Editorial standards,
 * Corrections, Send us a story tip.
 *
 * Made once as DRAFTS the first time an administrator opens WP Admin
 * after this theme version is installed. The newsroom reviews the words,
 * fills the [brackets] and presses Publish. Nothing is ever overwritten.
 *
 * @package kassmtv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kassmtv_newsroom_page_slugs() {
	return array(
		'about-the-newsroom'  => __( 'About the newsroom', 'kassmtv' ),
		'editorial-standards' => __( 'Editorial standards', 'kassmtv' ),
		'corrections'         => __( 'Corrections', 'kassmtv' ),
		'send-a-tip'          => __( 'Send us a story tip', 'kassmtv' ),
	);
}

function kassmtv_p( $text ) {
	return "<!-- wp:paragraph -->\n<p>" . $text . "</p>\n<!-- /wp:paragraph -->\n\n";
}

function kassmtv_h( $text ) {
	return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . $text . "</h2>\n<!-- /wp:heading -->\n\n";
}

function kassmtv_ul( $items ) {
	$html = "<!-- wp:list -->\n<ul class=\"wp-block-list\">";

	foreach ( $items as $item ) {
		$html .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->";
	}

	return $html . "</ul>\n<!-- /wp:list -->\n\n";
}

function kassmtv_newsroom_page_content( $slug ) {
	$email = esc_html( kassmtv_option( 'email' ) );

	switch ( $slug ) {
		case 'about-the-newsroom':
			return kassmtv_p( 'KASS-mtv Diaspora News is the news service of KASS MTV USA LLC. We report for Kenyans and Africans living abroad, and for the communities they come from.' )
				. kassmtv_p( 'We cover politics, business, development, health, sports and everyday life in the diaspora — in writing and on video.' )
				. kassmtv_h( 'Who we are' )
				. kassmtv_ul(
					array(
						'[Name] — Editor-in-chief',
						'[Name] — Reporter, [city]',
						'[Name] — Video and production',
					)
				)
				. kassmtv_h( 'How we work' )
				. kassmtv_p( 'Every story follows our <a href="/editorial-standards/">editorial standards</a>. When we make a mistake, we fix it and say so on our <a href="/corrections/">corrections</a> page.' )
				. kassmtv_h( 'Contact the newsroom' )
				. kassmtv_p( 'Email <a href="mailto:' . $email . '">' . $email . '</a>, or <a href="/send-a-tip/">send us a story tip</a>. You can stay anonymous.' );

		case 'editorial-standards':
			return kassmtv_p( 'These are the rules every KASS-mtv story follows. [Please read them, change anything that does not match how your newsroom works, then publish.]' )
				. kassmtv_h( 'Accuracy' )
				. kassmtv_p( 'We check facts before we publish. Important claims need a source we can name, or documents, photos or video we have seen ourselves. Where we can, we confirm a story with two independent sources.' )
				. kassmtv_h( 'Fairness' )
				. kassmtv_p( 'People and organisations we report on get a fair chance to respond before we publish. If they decline, we say so.' )
				. kassmtv_h( 'Independence' )
				. kassmtv_p( 'Advertisers, sponsors and partners do not decide what we report. Paid content is clearly labelled "Sponsored". News and opinion are kept apart, and opinion is labelled.' )
				. kassmtv_h( 'Sources' )
				. kassmtv_p( 'We protect people who share information with us in confidence. We only use anonymous sources when there is no other way to report a story that matters, and we explain why.' )
				. kassmtv_h( 'Photos and video' )
				. kassmtv_p( 'We credit photos and video. We do not change news images in ways that mislead.' )
				. kassmtv_h( 'Technology' )
				. kassmtv_p( kassmtv_ai_policy_text() )
				. kassmtv_h( 'Mistakes' )
				. kassmtv_p( 'When we get something wrong, we correct it quickly and openly. See our <a href="/corrections/">corrections</a> page.' );

		case 'corrections':
			return kassmtv_p( 'We work hard to get every fact right. When we make a mistake, we fix it quickly and say what we changed.' )
				. kassmtv_h( 'How we correct' )
				. kassmtv_ul(
					array(
						'A correction note goes at the end of the story, with the date.',
						'Small spelling or typing errors are fixed without a note.',
						'If a mistake changes the meaning of a story, we say so at the top.',
					)
				)
				. kassmtv_h( 'Report a mistake' )
				. kassmtv_p( 'Email <a href="mailto:' . $email . '">' . $email . '</a> with the link to the story and what is wrong, or use our <a href="/send-a-tip/">tip form</a>.' )
				. kassmtv_h( 'Recent corrections' )
				. kassmtv_p( 'No corrections yet.' );

		case 'send-a-tip':
			return kassmtv_p( 'Seen something the diaspora should know about? Tell us. A real person in our newsroom reads every tip.' )
				. "<!-- wp:shortcode -->\n[kassmtv_tip_form]\n<!-- /wp:shortcode -->\n\n"
				. kassmtv_p( 'If the information is sensitive, use a personal phone or computer, not a work one. You do not have to give your name.' );
	}

	return '';
}

function kassmtv_create_newsroom_pages() {
	if ( wp_doing_ajax() || ! current_user_can( 'publish_pages' ) || get_option( 'kassmtv_newsroom_pages' ) ) {
		return;
	}

	$made = 0;

	foreach ( kassmtv_newsroom_page_slugs() as $slug => $title ) {
		if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
			continue;
		}

		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => kassmtv_newsroom_page_content( $slug ),
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$made++;
		}
	}

	update_option( 'kassmtv_newsroom_pages', time(), false );

	if ( $made ) {
		set_transient( 'kassmtv_newsroom_notice', $made, WEEK_IN_SECONDS );
	}
}
add_action( 'admin_init', 'kassmtv_create_newsroom_pages' );

function kassmtv_newsroom_admin_notice() {
	$made = get_transient( 'kassmtv_newsroom_notice' );

	if ( ! $made || ! current_user_can( 'publish_pages' ) ) {
		return;
	}

	$drafts = admin_url( 'edit.php?post_type=page&post_status=draft' );

	printf(
		'<div class="notice notice-info is-dismissible"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
		esc_html__( 'KASS-mtv:', 'kassmtv' ),
		esc_html(
			sprintf(
				/* translators: %d: number of pages */
				_n( 'made %d newsroom page as a draft. Read it, fill the [brackets], then press Publish.', 'made %d newsroom pages as drafts. Read them, fill the [brackets], then press Publish.', $made, 'kassmtv' ),
				$made
			)
		),
		esc_url( $drafts ),
		esc_html__( 'Open the drafts →', 'kassmtv' )
	);
}
add_action( 'admin_notices', 'kassmtv_newsroom_admin_notice' );

function kassmtv_newsroom_notice_clear( $new_status, $old_status, $post ) {
	if ( 'publish' === $new_status && 'page' === $post->post_type && array_key_exists( $post->post_name, kassmtv_newsroom_page_slugs() ) ) {
		$left = 0;

		foreach ( array_keys( kassmtv_newsroom_page_slugs() ) as $slug ) {
			$page = get_page_by_path( $slug, OBJECT, 'page' );

			if ( $page && 'publish' !== $page->post_status && $page->ID !== $post->ID ) {
				$left++;
			}
		}

		if ( $left ) {
			set_transient( 'kassmtv_newsroom_notice', $left, WEEK_IN_SECONDS );
		} else {
			delete_transient( 'kassmtv_newsroom_notice' );
		}
	}
}
add_action( 'transition_post_status', 'kassmtv_newsroom_notice_clear', 10, 3 );

/**
 * Published newsroom pages as array( title => url ), for the footer.
 */
function kassmtv_newsroom_links() {
	$links = array();

	foreach ( kassmtv_newsroom_page_slugs() as $slug => $title ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );

		if ( $page && 'publish' === $page->post_status ) {
			$links[ get_the_title( $page ) ] = get_permalink( $page );
		}
	}

	return $links;
}
