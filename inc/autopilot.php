<?php
/**
 * Newsroom autopilot.
 *
 * Every hour the site reads the KASS-mtv "newsroom box" (a feed.json file
 * in the public kass-care/kassmtv-newsroom GitHub repository). Each new
 * story in it becomes a post: headline, story, key points (excerpt),
 * category, news picture (featured image) and a sources list.
 *
 * The editor controls it under Posts → Newsroom autopilot:
 * ON (publish), ON (save as drafts) or OFF, plus an optional delay.
 *
 * Safety:
 * - Only stories from the last 3 days are imported.
 * - A story is imported once. If the editor deletes it, it never comes back.
 * - Pictures may only come from the same folder as feed.json.
 * - Story HTML is cleaned with wp_kses_post().
 *
 * @package kassmtv
 */

defined( 'ABSPATH' ) || exit;

define( 'KASSMTV_FEED_DEFAULT', 'https://raw.githubusercontent.com/kass-care/kassmtv-newsroom/main/feed.json' );
define( 'KASSMTV_NEWSROOM_BYLINE', 'KASS-mtv Newsroom' );

/**
 * Autopilot settings with defaults.
 *
 * mode: publish | draft | off
 */
function kassmtv_autopilot_settings() {
	$saved = get_option( 'kassmtv_autopilot', array() );

	$s = wp_parse_args(
		is_array( $saved ) ? $saved : array(),
		array(
			'mode'     => 'publish',
			'delay'    => 0,
			'feed_url' => KASSMTV_FEED_DEFAULT,
		)
	);

	if ( ! in_array( $s['mode'], array( 'publish', 'draft', 'off' ), true ) ) {
		$s['mode'] = 'publish';
	}

	$s['delay'] = max( 0, min( 24, (int) $s['delay'] ) );

	if ( ! wp_http_validate_url( $s['feed_url'] ) ) {
		$s['feed_url'] = KASSMTV_FEED_DEFAULT;
	}

	return $s;
}

/**
 * Hourly check, scheduled while the theme is active.
 */
function kassmtv_autopilot_schedule() {
	if ( ! wp_next_scheduled( 'kassmtv_autopilot_check' ) ) {
		wp_schedule_event( time() + 2 * MINUTE_IN_SECONDS, 'hourly', 'kassmtv_autopilot_check' );
	}
}
add_action( 'init', 'kassmtv_autopilot_schedule' );
add_action( 'kassmtv_autopilot_check', 'kassmtv_autopilot_run' );

/**
 * Stop the hourly check when another theme is switched on.
 */
function kassmtv_autopilot_unschedule() {
	wp_clear_scheduled_hook( 'kassmtv_autopilot_check' );
}
add_action( 'switch_theme', 'kassmtv_autopilot_unschedule' );

/**
 * Remember the result of the last check, for the settings page.
 *
 * @param bool   $ok      Did it work.
 * @param string $message Plain words.
 */
function kassmtv_autopilot_log( $ok, $message ) {
	update_option(
		'kassmtv_autopilot_log',
		array(
			'time'    => time(),
			'ok'      => (bool) $ok,
			'message' => (string) $message,
		),
		false
	);
}

/**
 * Read the newsroom box and import new stories.
 *
 * @param bool $force Run even when the switch is OFF (the "Check now" button).
 * @return array Counts: new, skipped, failed.
 */
function kassmtv_autopilot_run( $force = false ) {
	$counts = array(
		'new'     => 0,
		'skipped' => 0,
		'failed'  => 0,
	);

	$s = kassmtv_autopilot_settings();

	if ( 'off' === $s['mode'] && ! $force ) {
		return $counts;
	}

	if ( get_transient( 'kassmtv_autopilot_lock' ) ) {
		return $counts;
	}

	set_transient( 'kassmtv_autopilot_lock', 1, 5 * MINUTE_IN_SECONDS );

	$response = wp_remote_get(
		add_query_arg( 'v', (string) floor( time() / 300 ), $s['feed_url'] ),
		array(
			'timeout' => 20,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);

	if ( is_wp_error( $response ) ) {
		kassmtv_autopilot_log( false, 'Could not reach the newsroom box: ' . $response->get_error_message() );
		delete_transient( 'kassmtv_autopilot_lock' );
		return $counts;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	if ( 200 !== $code ) {
		kassmtv_autopilot_log( false, sprintf( 'The newsroom box answered with error %d.', $code ) );
		delete_transient( 'kassmtv_autopilot_lock' );
		return $counts;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $data ) || ! isset( $data['stories'] ) || ! is_array( $data['stories'] ) ) {
		kassmtv_autopilot_log( false, 'The newsroom box file is not in the right format.' );
		delete_transient( 'kassmtv_autopilot_lock' );
		return $counts;
	}

	$base = trailingslashit( dirname( strtok( $s['feed_url'], '?' ) ) );

	foreach ( array_slice( $data['stories'], 0, 30 ) as $story ) {
		$result = kassmtv_autopilot_import( is_array( $story ) ? $story : array(), $s, $base );

		if ( 'new' === $result ) {
			++$counts['new'];
		} elseif ( 'failed' === $result ) {
			++$counts['failed'];
		} else {
			++$counts['skipped'];
		}
	}

	$message = sprintf(
		/* translators: %d: number of stories. */
		_n( '%d new story added.', '%d new stories added.', $counts['new'], 'kassmtv' ),
		$counts['new']
	);

	if ( $counts['failed'] ) {
		$message .= ' ' . sprintf( '%d could not be added.', $counts['failed'] );
	}

	kassmtv_autopilot_log( 0 === $counts['failed'], $message );
	delete_transient( 'kassmtv_autopilot_lock' );

	return $counts;
}

/**
 * Story ids already handled (imported, too old or deleted by the editor).
 */
function kassmtv_autopilot_seen() {
	$seen = get_option( 'kassmtv_autopilot_seen', array() );
	return is_array( $seen ) ? $seen : array();
}

/**
 * Remember a story id so it is never imported twice.
 *
 * @param string $id Story id.
 */
function kassmtv_autopilot_mark_seen( $id ) {
	$seen   = kassmtv_autopilot_seen();
	$seen[] = $id;
	update_option( 'kassmtv_autopilot_seen', array_slice( array_values( array_unique( $seen ) ), -300 ), false );
}

/**
 * Turn one story from the newsroom box into a post.
 *
 * @param array  $story Story fields.
 * @param array  $s     Settings.
 * @param string $base  Folder URL of feed.json, for pictures.
 * @return string new | exists | old | bad | failed
 */
function kassmtv_autopilot_import( $story, $s, $base ) {
	$id    = isset( $story['id'] ) ? sanitize_title( (string) $story['id'] ) : '';
	$title = isset( $story['title'] ) ? sanitize_text_field( (string) $story['title'] ) : '';
	$body  = isset( $story['content'] ) ? (string) $story['content'] : '';

	if ( '' === $id || '' === $title || '' === trim( wp_strip_all_tags( $body ) ) ) {
		return 'bad';
	}

	if ( in_array( $id, kassmtv_autopilot_seen(), true ) || kassmtv_autopilot_find( $id ) ) {
		return 'exists';
	}

	$when = isset( $story['date'] ) ? strtotime( (string) $story['date'] ) : time();

	if ( $when && $when < time() - 3 * DAY_IN_SECONDS ) {
		kassmtv_autopilot_mark_seen( $id );
		return 'old';
	}

	$points = array();

	if ( ! empty( $story['key_points'] ) ) {
		foreach ( (array) $story['key_points'] as $point ) {
			$point = sanitize_text_field( (string) $point );

			if ( '' !== $point ) {
				$points[] = $point;
			}
		}
	}

	$slug = isset( $story['slug'] ) ? sanitize_title( (string) $story['slug'] ) : '';

	$postarr = array(
		'post_type'     => 'post',
		'post_status'   => 'draft',
		'post_title'    => $title,
		'post_name'     => $slug ? $slug : sanitize_title( $title ),
		'post_content'  => kassmtv_autopilot_content( $body, isset( $story['sources'] ) ? (array) $story['sources'] : array() ),
		'post_excerpt'  => implode( "\n", array_slice( $points, 0, 5 ) ),
		'post_author'   => kassmtv_autopilot_author(),
		'post_category' => array( kassmtv_autopilot_category( isset( $story['category'] ) ? (string) $story['category'] : '' ) ),
		'meta_input'    => array(
			'_kassmtv_feed_id' => $id,
			'_kassmtv_byline'  => KASSMTV_NEWSROOM_BYLINE,
			'_kassmtv_ai'      => 1,
		),
	);

	$tags = array();

	if ( ! empty( $story['tags'] ) ) {
		foreach ( array_slice( (array) $story['tags'], 0, 6 ) as $tag ) {
			$tag = sanitize_text_field( (string) $tag );

			if ( '' !== $tag && 'breaking' !== strtolower( $tag ) ) {
				$tags[] = $tag;
			}
		}
	}

	if ( ! empty( $story['breaking'] ) ) {
		$tags[] = 'breaking';
	}

	if ( $tags ) {
		$postarr['tags_input'] = $tags;
	}

	$post_id = wp_insert_post( wp_slash( $postarr ), true );

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return 'failed';
	}

	kassmtv_autopilot_mark_seen( $id );

	if ( ! empty( $story['image'] ) ) {
		kassmtv_autopilot_picture(
			$post_id,
			$base,
			(string) $story['image'],
			isset( $story['image_alt'] ) ? (string) $story['image_alt'] : $title,
			isset( $story['image_caption'] ) ? (string) $story['image_caption'] : ''
		);
	}

	if ( 'publish' === $s['mode'] ) {
		$update = array(
			'ID'          => $post_id,
			'post_status' => 'publish',
		);

		if ( $s['delay'] > 0 ) {
			$gmt                     = gmdate( 'Y-m-d H:i:s', time() + $s['delay'] * HOUR_IN_SECONDS );
			$update['post_status']   = 'future';
			$update['post_date_gmt'] = $gmt;
			$update['post_date']     = get_date_from_gmt( $gmt );
			$update['edit_date']     = true;
		}

		wp_update_post( wp_slash( $update ) );
	}

	return 'new';
}

/**
 * Find a post made from a story id.
 *
 * @param string $id Story id.
 * @return int Post id or 0.
 */
function kassmtv_autopilot_find( $id ) {
	$found = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'any',
			'meta_key'         => '_kassmtv_feed_id', // phpcs:ignore WordPress.DB.SlowDBQuery -- tiny, hourly.
			'meta_value'       => $id, // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'           => 'ids',
			'numberposts'      => 1,
			'suppress_filters' => true,
		)
	);

	return $found ? (int) $found[0] : 0;
}

/**
 * The story text plus its sources list.
 *
 * @param string $body    Story HTML.
 * @param array  $sources Each: title, url, outlet, date.
 */
function kassmtv_autopilot_content( $body, $sources ) {
	$html  = wp_kses_post( $body );
	$items = '';

	foreach ( array_slice( $sources, 0, 12 ) as $src ) {
		if ( ! is_array( $src ) ) {
			continue;
		}

		$t = isset( $src['title'] ) ? sanitize_text_field( (string) $src['title'] ) : '';
		$u = isset( $src['url'] ) ? esc_url_raw( (string) $src['url'], array( 'https', 'http' ) ) : '';
		$o = isset( $src['outlet'] ) ? sanitize_text_field( (string) $src['outlet'] ) : '';
		$d = isset( $src['date'] ) ? sanitize_text_field( (string) $src['date'] ) : '';

		if ( '' === $t || '' === $u ) {
			continue;
		}

		$items .= '<li>'
			. ( $o ? esc_html( $o ) . ': ' : '' )
			. '<a href="' . esc_url( $u ) . '" target="_blank" rel="noopener nofollow">' . esc_html( $t ) . '</a>'
			. ( $d ? ' (' . esc_html( $d ) . ')' : '' )
			. '</li>';
	}

	if ( $items ) {
		$html .= "\n\n<h2 class=\"k-sources-title\">Sources</h2>\n<ul class=\"k-sources\">" . $items . '</ul>';
	}

	return $html;
}

/**
 * Who owns newsroom posts in WordPress: the first administrator.
 * Readers see "KASS-mtv Newsroom" as the byline.
 */
function kassmtv_autopilot_author() {
	$admins = get_users(
		array(
			'role'    => 'administrator',
			'number'  => 1,
			'orderby' => 'ID',
			'order'   => 'ASC',
			'fields'  => 'ID',
		)
	);

	return $admins ? (int) $admins[0] : 1;
}

/**
 * Category id from a name, made if it does not exist yet.
 *
 * @param string $name Category name, e.g. Politics or Diaspora.
 */
function kassmtv_autopilot_category( $name ) {
	$name = sanitize_text_field( $name );

	if ( '' === $name ) {
		$name = 'News';
	}

	$term = get_term_by( 'name', $name, 'category' );

	if ( ! $term ) {
		$term = get_term_by( 'slug', sanitize_title( $name ), 'category' );
	}

	if ( $term ) {
		return (int) $term->term_id;
	}

	$made = wp_insert_term( $name, 'category' );

	return is_wp_error( $made ) ? (int) get_option( 'default_category', 1 ) : (int) $made['term_id'];
}

/**
 * Download the news picture and make it the featured image.
 * Only pictures from the newsroom box folder are allowed.
 *
 * @param int    $post_id Post.
 * @param string $base    Folder URL of feed.json.
 * @param string $path    Picture path inside that folder, e.g. images/x.jpg.
 * @param string $alt     Alt text.
 * @param string $caption Caption.
 */
function kassmtv_autopilot_picture( $post_id, $base, $path, $alt, $caption ) {
	$path = ltrim( str_replace( '\\', '/', $path ), '/' );

	if ( '' === $path || false !== strpos( $path, '..' ) || preg_match( '#^[a-z][a-z0-9+.-]*:#i', $path ) ) {
		return 0;
	}

	if ( ! preg_match( '/\.(jpe?g|png|webp)$/i', $path ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$alt = sanitize_text_field( $alt );
	$att = media_sideload_image( $base . $path, $post_id, $alt, 'id' );

	if ( is_wp_error( $att ) || ! $att ) {
		return 0;
	}

	set_post_thumbnail( $post_id, (int) $att );
	update_post_meta( (int) $att, '_wp_attachment_image_alt', $alt );
	update_post_meta( (int) $att, '_kassmtv_graphic', 1 );

	$caption = sanitize_text_field( $caption );

	if ( '' !== $caption ) {
		wp_update_post(
			array(
				'ID'           => (int) $att,
				'post_excerpt' => $caption,
			)
		);
	}

	return (int) $att;
}

/**
 * Byline name for a post: "KASS-mtv Newsroom" for autopilot stories.
 *
 * @param int|WP_Post|null $post Post.
 * @return string Empty for normal posts.
 */
function kassmtv_newsroom_byline( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	return (string) get_post_meta( $post->ID, '_kassmtv_byline', true );
}

/**
 * Is the post's featured image a KASS-mtv news graphic (a picture with the
 * headline written on it)? Then layouts must not put text on top of it.
 * If the editor swaps in a real photo, this turns false by itself.
 *
 * @param int|WP_Post|null $post Post.
 */
function kassmtv_has_news_graphic( $post = null ) {
	$post = get_post( $post );

	if ( ! $post || ! has_post_thumbnail( $post ) ) {
		return false;
	}

	$att = (int) get_post_thumbnail_id( $post );

	if ( get_post_meta( $att, '_kassmtv_graphic', true ) ) {
		return true;
	}

	return 0 === strpos( (string) get_post_meta( $att, '_wp_attachment_image_alt', true ), 'KASS-mtv news graphic' );
}

/**
 * Was this story prepared with AI assistance?
 *
 * @param int|WP_Post|null $post Post.
 */
function kassmtv_is_ai_story( $post = null ) {
	$post = get_post( $post );

	return $post && (bool) get_post_meta( $post->ID, '_kassmtv_ai', true );
}

/**
 * Note under AI-assisted stories.
 */
function kassmtv_ai_note() {
	$links = function_exists( 'kassmtv_newsroom_links' ) ? kassmtv_newsroom_links() : array();
	$fix   = '';

	foreach ( $links as $label => $url ) {
		if ( false !== stripos( $label, 'correction' ) ) {
			$fix = ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Report a mistake', 'kassmtv' ) . '</a>.';
		}
	}

	return '<p class="k-ai-note"><strong>' . esc_html__( 'Prepared with AI assistance.', 'kassmtv' ) . '</strong> '
		. esc_html__( 'This story was researched and written with AI tools, using the reports listed above. Our editors can correct or remove it at any time.', 'kassmtv' )
		. $fix . '</p>';
}

/* ---------------------------------------------------------------------
 * Settings page: Posts → Newsroom autopilot
 * ------------------------------------------------------------------- */

/**
 * Add the page under Posts.
 */
function kassmtv_autopilot_menu() {
	add_posts_page(
		__( 'Newsroom autopilot', 'kassmtv' ),
		__( 'Newsroom autopilot', 'kassmtv' ),
		'manage_options',
		'kassmtv-autopilot',
		'kassmtv_autopilot_page'
	);
}
add_action( 'admin_menu', 'kassmtv_autopilot_menu' );

/**
 * Save settings.
 */
function kassmtv_autopilot_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'kassmtv' ) );
	}

	check_admin_referer( 'kassmtv_autopilot_save' );

	$mode  = isset( $_POST['kassmtv_mode'] ) ? sanitize_key( wp_unslash( $_POST['kassmtv_mode'] ) ) : 'publish';
	$delay = isset( $_POST['kassmtv_delay'] ) ? (int) $_POST['kassmtv_delay'] : 0;
	$feed  = isset( $_POST['kassmtv_feed_url'] ) ? esc_url_raw( wp_unslash( $_POST['kassmtv_feed_url'] ), array( 'https' ) ) : '';

	update_option(
		'kassmtv_autopilot',
		array(
			'mode'     => in_array( $mode, array( 'publish', 'draft', 'off' ), true ) ? $mode : 'publish',
			'delay'    => max( 0, min( 24, $delay ) ),
			'feed_url' => $feed ? $feed : KASSMTV_FEED_DEFAULT,
		)
	);

	wp_safe_redirect( admin_url( 'edit.php?page=kassmtv-autopilot&kass_saved=1' ) );
	exit;
}
add_action( 'admin_post_kassmtv_autopilot_save', 'kassmtv_autopilot_save' );

/**
 * "Check for new stories now" button.
 */
function kassmtv_autopilot_check_now() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'kassmtv' ) );
	}

	check_admin_referer( 'kassmtv_autopilot_check' );
	delete_transient( 'kassmtv_autopilot_lock' );
	kassmtv_autopilot_run( true );

	wp_safe_redirect( admin_url( 'edit.php?page=kassmtv-autopilot&kass_checked=1' ) );
	exit;
}
add_action( 'admin_post_kassmtv_autopilot_check', 'kassmtv_autopilot_check_now' );

/**
 * The settings page.
 */
function kassmtv_autopilot_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$s    = kassmtv_autopilot_settings();
	$log  = get_option( 'kassmtv_autopilot_log', array() );
	$next = wp_next_scheduled( 'kassmtv_autopilot_check' );

	$recent = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => array( 'publish', 'future', 'draft', 'pending' ),
			'meta_key'         => '_kassmtv_feed_id', // phpcs:ignore WordPress.DB.SlowDBQuery
			'numberposts'      => 10,
			'suppress_filters' => true,
		)
	);

	$labels = array(
		'publish' => __( 'Live', 'kassmtv' ),
		'future'  => __( 'Goes live later', 'kassmtv' ),
		'draft'   => __( 'Draft', 'kassmtv' ),
		'pending' => __( 'Waiting', 'kassmtv' ),
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Newsroom autopilot', 'kassmtv' ); ?></h1>
		<p><?php esc_html_e( 'Every hour this website checks the KASS-mtv newsroom box for new stories and adds them with their picture, key points, category and sources.', 'kassmtv' ); ?></p>

		<?php if ( isset( $_GET['kass_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Saved.', 'kassmtv' ); ?></p></div>
		<?php endif; ?>

		<?php if ( isset( $_GET['kass_checked'] ) && ! empty( $log['message'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice <?php echo ! empty( $log['ok'] ) ? 'notice-success' : 'notice-error'; ?>"><p><?php echo esc_html( $log['message'] ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Status', 'kassmtv' ); ?></h2>
		<table class="widefat striped" style="max-width:720px">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Switch', 'kassmtv' ); ?></strong></td>
					<td>
						<?php
						if ( 'publish' === $s['mode'] ) {
							echo $s['delay']
								? esc_html( sprintf( 'ON: stories go live %d hour(s) after they arrive.', $s['delay'] ) )
								: esc_html__( 'ON: stories go live as soon as they arrive.', 'kassmtv' );
						} elseif ( 'draft' === $s['mode'] ) {
							esc_html_e( 'ON: stories are saved as drafts for you to check and publish.', 'kassmtv' );
						} else {
							esc_html_e( 'OFF: no new stories are added.', 'kassmtv' );
						}
						?>
					</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Last check', 'kassmtv' ); ?></strong></td>
					<td>
						<?php
						if ( ! empty( $log['time'] ) ) {
							echo esc_html( human_time_diff( (int) $log['time'] ) . ' ago: ' . $log['message'] );
						} else {
							esc_html_e( 'Not yet.', 'kassmtv' );
						}
						?>
					</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Next check', 'kassmtv' ); ?></strong></td>
					<td><?php echo $next ? esc_html( 'In about ' . human_time_diff( time(), (int) $next ) ) : esc_html__( 'Soon.', 'kassmtv' ); ?></td>
				</tr>
			</tbody>
		</table>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:16px 0 28px">
			<input type="hidden" name="action" value="kassmtv_autopilot_check">
			<?php wp_nonce_field( 'kassmtv_autopilot_check' ); ?>
			<?php submit_button( __( 'Check for new stories now', 'kassmtv' ), 'secondary', 'submit', false ); ?>
		</form>

		<h2><?php esc_html_e( 'Settings', 'kassmtv' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="kassmtv_autopilot_save">
			<?php wp_nonce_field( 'kassmtv_autopilot_save' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Switch', 'kassmtv' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="kassmtv_mode" value="publish" <?php checked( $s['mode'], 'publish' ); ?>> <?php esc_html_e( 'ON: publish stories by itself', 'kassmtv' ); ?></label><br>
							<label><input type="radio" name="kassmtv_mode" value="draft" <?php checked( $s['mode'], 'draft' ); ?>> <?php esc_html_e( 'ON: save stories as drafts, I publish them', 'kassmtv' ); ?></label><br>
							<label><input type="radio" name="kassmtv_mode" value="off" <?php checked( $s['mode'], 'off' ); ?>> <?php esc_html_e( 'OFF', 'kassmtv' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="kassmtv_delay"><?php esc_html_e( 'Wait before going live', 'kassmtv' ); ?></label></th>
					<td>
						<select id="kassmtv_delay" name="kassmtv_delay">
							<?php foreach ( array( 0, 1, 2, 3, 6, 12 ) as $h ) : ?>
								<option value="<?php echo (int) $h; ?>" <?php selected( $s['delay'], $h ); ?>><?php echo $h ? esc_html( sprintf( '%d hour(s)', $h ) ) : esc_html__( 'No wait', 'kassmtv' ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Gives you time to read a story and change or delete it before readers see it.', 'kassmtv' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="kassmtv_feed_url"><?php esc_html_e( 'Newsroom box address', 'kassmtv' ); ?></label></th>
					<td>
						<input id="kassmtv_feed_url" name="kassmtv_feed_url" type="url" class="large-text code" value="<?php echo esc_attr( $s['feed_url'] ); ?>">
						<p class="description"><?php esc_html_e( 'Leave this as it is unless you were told to change it.', 'kassmtv' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save', 'kassmtv' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'Latest newsroom stories', 'kassmtv' ); ?></h2>
		<?php if ( $recent ) : ?>
			<table class="widefat striped" style="max-width:720px">
				<tbody>
					<?php foreach ( $recent as $p ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a></td>
							<td><?php echo esc_html( isset( $labels[ $p->post_status ] ) ? $labels[ $p->post_status ] : $p->post_status ); ?></td>
							<td><?php echo esc_html( get_the_date( '', $p ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'None yet.', 'kassmtv' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Keep the Editorial standards page honest now that some stories
 * are published by the autopilot. Runs once.
 */
function kassmtv_autopilot_policy_update() {
	if ( get_option( 'kassmtv_autopilot_policy_v1' ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}

	update_option( 'kassmtv_autopilot_policy_v1', 1, false );

	$page = get_page_by_path( 'editorial-standards', OBJECT, 'page' );

	if ( ! $page ) {
		return;
	}

	$old = 'We may use digital tools, including AI, to help with research and first drafts. Every story is checked and edited by a person on our team before it is published.';

	if ( false === strpos( $page->post_content, $old ) ) {
		return;
	}

	wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => str_replace( $old, kassmtv_ai_policy_text(), $page->post_content ),
		)
	);
}
add_action( 'admin_init', 'kassmtv_autopilot_policy_update' );

/**
 * The AI sentence for the Editorial standards page.
 */
function kassmtv_ai_policy_text() {
	return 'We use digital tools, including AI, to help research and write some stories. These stories are based on reports from named news outlets and official sources, which we list at the end of each story, and they are marked "Prepared with AI assistance". Our editors can correct or remove any story at any time.';
}
