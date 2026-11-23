<?php
/**
 * KASS-mtv forms: newsletter sign-up and story tips.
 *
 * Both work without any extra plugin. Sign-ups are kept in WP Admin →
 * Subscribers (with a CSV download for any email tool). Tips are kept in
 * WP Admin → Story tips, and the site email gets a short alert.
 *
 * Pages may come from a cache, so the forms use no nonce: a hidden trap
 * field, a minimum fill time and a per-visitor limit keep robots out.
 *
 * @package kassmtv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Private lists in WP Admin
 * ---------------------------------------------------------------------- */
function kassmtv_register_form_types() {
	register_post_type(
		'kass_subscriber',
		array(
			'labels'          => array(
				'name'          => __( 'Subscribers', 'kassmtv' ),
				'singular_name' => __( 'Subscriber', 'kassmtv' ),
				'menu_name'     => __( 'Subscribers', 'kassmtv' ),
				'all_items'     => __( 'All subscribers', 'kassmtv' ),
				'edit_item'     => __( 'Subscriber', 'kassmtv' ),
				'search_items'  => __( 'Search subscribers', 'kassmtv' ),
				'not_found'     => __( 'No subscribers yet.', 'kassmtv' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 26,
			'menu_icon'       => 'dashicons-email-alt',
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);

	register_post_type(
		'kass_tip',
		array(
			'labels'          => array(
				'name'          => __( 'Story tips', 'kassmtv' ),
				'singular_name' => __( 'Story tip', 'kassmtv' ),
				'menu_name'     => __( 'Story tips', 'kassmtv' ),
				'all_items'     => __( 'All tips', 'kassmtv' ),
				'edit_item'     => __( 'Story tip', 'kassmtv' ),
				'search_items'  => __( 'Search tips', 'kassmtv' ),
				'not_found'     => __( 'No tips yet.', 'kassmtv' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 27,
			'menu_icon'       => 'dashicons-megaphone',
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'kassmtv_register_form_types' );

/* -------------------------------------------------------------------------
 * Spam guard
 * ---------------------------------------------------------------------- */
function kassmtv_visitor_key( $form ) {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	// Only a one-way hash is kept, for one hour.
	return 'kassmtv_rl_' . $form . '_' . md5( $ip . wp_salt( 'nonce' ) );
}

/**
 * Returns an error message, or '' when the request looks human.
 */
function kassmtv_spam_check( $form, $limit ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- public form on cached pages; see file header.
	if ( ! empty( $_POST['kass_website'] ) ) {
		return __( 'Something went wrong. Please try again.', 'kassmtv' );
	}

	$started = isset( $_POST['kass_t'] ) ? (int) $_POST['kass_t'] : 0;
	// phpcs:enable

	if ( $started && ( time() - $started ) < 3 ) {
		return __( 'That was very fast. Please try again.', 'kassmtv' );
	}

	$key   = kassmtv_visitor_key( $form );
	$count = (int) get_transient( $key );

	if ( $count >= $limit ) {
		return __( 'Too many tries. Please wait an hour and try again.', 'kassmtv' );
	}

	set_transient( $key, $count + 1, HOUR_IN_SECONDS );

	return '';
}

/**
 * Answer the form: JSON for the script, a redirect without it.
 */
function kassmtv_form_reply( $ok, $message, $anchor ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! empty( $_POST['kass_ajax'] ) ) {
		wp_send_json(
			array(
				'ok'      => (bool) $ok,
				'message' => $message,
			),
			$ok ? 200 : 400
		);
	}

	$back = wp_get_referer();
	$back = $back ? $back : home_url( '/' );
	$back = remove_query_arg( array( 'kass_ok', 'kass_err' ), $back );
	$back = add_query_arg( $ok ? 'kass_ok' : 'kass_err', rawurlencode( $message ), $back );

	wp_safe_redirect( $back . '#' . $anchor );
	exit;
}

/* -------------------------------------------------------------------------
 * Newsletter
 * ---------------------------------------------------------------------- */
function kassmtv_handle_subscribe() {
	$error = kassmtv_spam_check( 'sub', 5 );

	if ( $error ) {
		kassmtv_form_reply( false, $error, 'newsletter' );
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$email   = isset( $_POST['kass_email'] ) ? sanitize_email( wp_unslash( $_POST['kass_email'] ) ) : '';
	$name    = isset( $_POST['kass_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kass_name'] ) ) : '';
	$source  = isset( $_POST['kass_source'] ) ? sanitize_key( wp_unslash( $_POST['kass_source'] ) ) : '';
	$consent = ! empty( $_POST['kass_consent'] );
	// phpcs:enable

	if ( ! is_email( $email ) ) {
		kassmtv_form_reply( false, __( 'Please enter a valid email address.', 'kassmtv' ), 'newsletter' );
	}

	if ( ! $consent ) {
		kassmtv_form_reply( false, __( 'Please tick the box to say we may email you.', 'kassmtv' ), 'newsletter' );
	}

	$email = strtolower( $email );

	$existing = get_posts(
		array(
			'post_type'      => 'kass_subscriber',
			'post_status'    => 'any',
			'title'          => $email,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( ! $existing ) {
		$id = wp_insert_post(
			array(
				'post_type'   => 'kass_subscriber',
				'post_status' => 'private',
				'post_title'  => $email,
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, 'kass_name', $name );
			update_post_meta( $id, 'kass_source', $source );
			update_post_meta( $id, 'kass_consent', current_time( 'mysql' ) );
		}
	}

	kassmtv_form_reply( true, __( 'You are in! Watch your inbox for KASS-mtv news.', 'kassmtv' ), 'newsletter' );
}
add_action( 'admin_post_nopriv_kassmtv_subscribe', 'kassmtv_handle_subscribe' );
add_action( 'admin_post_kassmtv_subscribe', 'kassmtv_handle_subscribe' );

/**
 * The sign-up form. $source says where it sits (home, story, page).
 */
function kassmtv_newsletter_box( $source = 'page', $compact = false ) {
	ob_start();
	?>
	<section class="k-newsletter<?php echo $compact ? ' k-newsletter-compact' : ''; ?>" id="newsletter" aria-label="<?php esc_attr_e( 'Newsletter', 'kassmtv' ); ?>">
		<div class="k-newsletter-copy">
			<p class="k-eyebrow"><?php esc_html_e( 'The KASS-mtv briefing', 'kassmtv' ); ?></p>
			<h2><?php esc_html_e( 'The stories that matter to the diaspora, in your inbox.', 'kassmtv' ); ?></h2>
			<p><?php esc_html_e( 'Free. One short email. Unsubscribe any time.', 'kassmtv' ); ?></p>
		</div>
		<form class="k-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-k-form>
			<input type="hidden" name="action" value="kassmtv_subscribe">
			<input type="hidden" name="kass_source" value="<?php echo esc_attr( $source ); ?>">
			<input type="hidden" name="kass_t" value="" data-k-time>
			<div class="k-trap" aria-hidden="true">
				<label><?php esc_html_e( 'Leave this empty', 'kassmtv' ); ?> <input type="text" name="kass_website" tabindex="-1" autocomplete="off"></label>
			</div>
			<div class="k-form-row">
				<label class="screen-reader-text" for="k-sub-email-<?php echo esc_attr( $source ); ?>"><?php esc_html_e( 'Your email', 'kassmtv' ); ?></label>
				<input type="email" id="k-sub-email-<?php echo esc_attr( $source ); ?>" name="kass_email" required autocomplete="email" inputmode="email" placeholder="<?php esc_attr_e( 'Your email address', 'kassmtv' ); ?>">
				<button type="submit"><?php esc_html_e( 'Sign me up', 'kassmtv' ); ?></button>
			</div>
			<label class="k-check">
				<input type="checkbox" name="kass_consent" value="1" required>
				<span><?php esc_html_e( 'Yes, KASS-mtv may email me news. I can unsubscribe any time.', 'kassmtv' ); ?></span>
			</label>
			<p class="k-form-msg" role="status" aria-live="polite" data-k-msg></p>
		</form>
	</section>
	<?php
	return ob_get_clean();
}

function kassmtv_newsletter_shortcode() {
	return kassmtv_newsletter_box( 'page' );
}
add_shortcode( 'kassmtv_newsletter', 'kassmtv_newsletter_shortcode' );

/* -------------------------------------------------------------------------
 * Story tips
 * ---------------------------------------------------------------------- */
function kassmtv_handle_tip() {
	$error = kassmtv_spam_check( 'tip', 5 );

	if ( $error ) {
		kassmtv_form_reply( false, $error, 'tip' );
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$tip     = isset( $_POST['kass_tip'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kass_tip'] ) ) : '';
	$name    = isset( $_POST['kass_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kass_name'] ) ) : '';
	$contact = isset( $_POST['kass_contact'] ) ? sanitize_text_field( wp_unslash( $_POST['kass_contact'] ) ) : '';
	$link    = isset( $_POST['kass_link'] ) ? esc_url_raw( wp_unslash( $_POST['kass_link'] ) ) : '';
	// phpcs:enable

	if ( mb_strlen( trim( $tip ) ) < 20 ) {
		kassmtv_form_reply( false, __( 'Please tell us a little more (at least a sentence or two).', 'kassmtv' ), 'tip' );
	}

	$tip = mb_substr( $tip, 0, 5000 );

	$id = wp_insert_post(
		array(
			'post_type'    => 'kass_tip',
			'post_status'  => 'private',
			'post_title'   => wp_trim_words( $tip, 10, '…' ),
			'post_content' => $tip,
		)
	);

	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, 'kass_name', $name );
		update_post_meta( $id, 'kass_contact', $contact );
		update_post_meta( $id, 'kass_link', $link );

		wp_mail(
			get_option( 'admin_email' ),
			__( 'New story tip on KASS-mtv', 'kassmtv' ),
			sprintf(
				/* translators: 1: tip text 2: name 3: contact 4: link 5: admin link */
				__( "A reader sent a story tip:\n\n%1\$s\n\nName: %2\$s\nContact: %3\$s\nLink: %4\$s\n\nSee all tips: %5\$s", 'kassmtv' ),
				$tip,
				$name ? $name : '—',
				$contact ? $contact : '—',
				$link ? $link : '—',
				admin_url( 'edit.php?post_type=kass_tip&post_status=private' )
			)
		);
	}

	kassmtv_form_reply( true, __( 'Thank you. Our newsroom has your tip.', 'kassmtv' ), 'tip' );
}
add_action( 'admin_post_nopriv_kassmtv_tip', 'kassmtv_handle_tip' );
add_action( 'admin_post_kassmtv_tip', 'kassmtv_handle_tip' );

function kassmtv_tip_form() {
	ob_start();
	?>
	<form class="k-form k-tip-form" id="tip" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-k-form>
		<input type="hidden" name="action" value="kassmtv_tip">
		<input type="hidden" name="kass_t" value="" data-k-time>
		<div class="k-trap" aria-hidden="true">
			<label><?php esc_html_e( 'Leave this empty', 'kassmtv' ); ?> <input type="text" name="kass_website" tabindex="-1" autocomplete="off"></label>
		</div>

		<label class="k-field">
			<span><?php esc_html_e( 'What is the story? *', 'kassmtv' ); ?></span>
			<textarea name="kass_tip" rows="6" required minlength="20" maxlength="5000" placeholder="<?php esc_attr_e( 'What happened, where, when, and who is involved?', 'kassmtv' ); ?>"></textarea>
		</label>

		<label class="k-field">
			<span><?php esc_html_e( 'A link, photo or video (optional)', 'kassmtv' ); ?></span>
			<input type="url" name="kass_link" placeholder="https://">
		</label>

		<div class="k-field-row">
			<label class="k-field">
				<span><?php esc_html_e( 'Your name (optional)', 'kassmtv' ); ?></span>
				<input type="text" name="kass_name" autocomplete="name">
			</label>
			<label class="k-field">
				<span><?php esc_html_e( 'Phone, WhatsApp or email (optional)', 'kassmtv' ); ?></span>
				<input type="text" name="kass_contact">
			</label>
		</div>

		<p class="k-form-note"><?php esc_html_e( 'You can stay anonymous. We never publish your name or contact details without your permission.', 'kassmtv' ); ?></p>

		<button type="submit" class="k-btn k-btn-dark"><?php esc_html_e( 'Send the tip', 'kassmtv' ); ?></button>
		<p class="k-form-msg" role="status" aria-live="polite" data-k-msg></p>
	</form>
	<?php
	return ob_get_clean();
}
add_shortcode( 'kassmtv_tip_form', 'kassmtv_tip_form' );

/* -------------------------------------------------------------------------
 * Show the result after a form sent without the script
 * ---------------------------------------------------------------------- */
function kassmtv_form_notice() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display only.
	$ok  = isset( $_GET['kass_ok'] ) ? sanitize_text_field( wp_unslash( $_GET['kass_ok'] ) ) : '';
	$err = isset( $_GET['kass_err'] ) ? sanitize_text_field( wp_unslash( $_GET['kass_err'] ) ) : '';
	// phpcs:enable

	if ( ! $ok && ! $err ) {
		return;
	}

	printf(
		'<div class="k-toast %1$s" role="status">%2$s</div>',
		$ok ? 'is-ok' : 'is-error',
		esc_html( $ok ? $ok : $err )
	);
}
add_action( 'wp_body_open', 'kassmtv_form_notice' );

/* -------------------------------------------------------------------------
 * Subscribers list: columns and CSV download
 * ---------------------------------------------------------------------- */
function kassmtv_subscriber_columns( $columns ) {
	return array(
		'cb'          => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'       => __( 'Email', 'kassmtv' ),
		'kass_source' => __( 'Signed up on', 'kassmtv' ),
		'date'        => __( 'Date', 'kassmtv' ),
	);
}
add_filter( 'manage_kass_subscriber_posts_columns', 'kassmtv_subscriber_columns' );

function kassmtv_subscriber_column( $column, $post_id ) {
	if ( 'kass_source' === $column ) {
		echo esc_html( get_post_meta( $post_id, 'kass_source', true ) );
	}
}
add_action( 'manage_kass_subscriber_posts_custom_column', 'kassmtv_subscriber_column', 10, 2 );

function kassmtv_tip_columns( $columns ) {
	return array(
		'cb'           => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'        => __( 'Tip', 'kassmtv' ),
		'kass_contact' => __( 'From', 'kassmtv' ),
		'date'         => __( 'Date', 'kassmtv' ),
	);
}
add_filter( 'manage_kass_tip_posts_columns', 'kassmtv_tip_columns' );

function kassmtv_tip_column( $column, $post_id ) {
	if ( 'kass_contact' === $column ) {
		$parts = array_filter(
			array(
				get_post_meta( $post_id, 'kass_name', true ),
				get_post_meta( $post_id, 'kass_contact', true ),
			)
		);

		echo esc_html( $parts ? implode( ' · ', $parts ) : __( 'Anonymous', 'kassmtv' ) );
	}
}
add_action( 'manage_kass_tip_posts_custom_column', 'kassmtv_tip_column', 10, 2 );

/**
 * Show a tip's details under its text in the editor.
 */
function kassmtv_tip_details_box() {
	add_meta_box(
		'kassmtv_tip_details',
		__( 'Who sent it', 'kassmtv' ),
		function ( $post ) {
			$rows = array(
				__( 'Name', 'kassmtv' )    => get_post_meta( $post->ID, 'kass_name', true ),
				__( 'Contact', 'kassmtv' ) => get_post_meta( $post->ID, 'kass_contact', true ),
				__( 'Link', 'kassmtv' )    => get_post_meta( $post->ID, 'kass_link', true ),
			);

			echo '<p>';
			foreach ( $rows as $label => $value ) {
				echo '<strong>' . esc_html( $label ) . ':</strong> ' . ( $value ? esc_html( $value ) : '—' ) . '<br>';
			}
			echo '</p>';
		},
		'kass_tip',
		'side'
	);
}
add_action( 'add_meta_boxes', 'kassmtv_tip_details_box' );

function kassmtv_export_button( $which ) {
	global $typenow;

	if ( 'kass_subscriber' !== $typenow || 'top' !== $which || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$url = wp_nonce_url( admin_url( 'admin-post.php?action=kassmtv_export_subscribers' ), 'kassmtv_export' );
	printf( '<a class="button button-primary" href="%1$s" style="margin-left:8px">%2$s</a>', esc_url( $url ), esc_html__( 'Download all (CSV)', 'kassmtv' ) );
}
add_action( 'manage_posts_extra_tablenav', 'kassmtv_export_button' );

function kassmtv_export_subscribers() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'kassmtv' ), 403 );
	}

	check_admin_referer( 'kassmtv_export' );

	$ids = get_posts(
		array(
			'post_type'      => 'kass_subscriber',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'ASC',
		)
	);

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=kassmtv-subscribers-' . gmdate( 'Y-m-d' ) . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'email', 'name', 'signed_up_on', 'date' ) );

	foreach ( $ids as $id ) {
		fputcsv(
			$out,
			array(
				get_the_title( $id ),
				get_post_meta( $id, 'kass_name', true ),
				get_post_meta( $id, 'kass_source', true ),
				get_post_time( 'Y-m-d H:i', false, $id ),
			)
		);
	}

	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_kassmtv_export_subscribers', 'kassmtv_export_subscribers' );
