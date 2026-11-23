<?php
/**
 * KASS-mtv ads: your own paid ads first, Google ads when a space is empty,
 * and an "Advertise with us" box when there is neither.
 *
 * WP Admin → Ads:
 *   - Add new ad: advertiser name, picture, link, where it shows, start and end date.
 *     The ad goes away by itself after the end date. Views and clicks are counted.
 *   - Ad requests: messages from businesses that filled in the form on the
 *     "Advertise with us" page.
 *   - Settings & Google: your Google AdSense number, prices, and on/off switches.
 *
 * Ad spaces (each labelled "Advertisement"):
 *   top     — banner under the menu, on every page
 *   side    — column next to every story (below the story on phones)
 *   article — inside the story, after the third paragraph
 *   feed    — between stories on the homepage and news lists
 *
 * @package kassmtv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * The ad spaces
 * ---------------------------------------------------------------------- */
function kassmtv_ad_places() {
	return array(
		'top'     => array(
			'name' => __( 'Top banner (every page, under the menu)', 'kassmtv' ),
			'size' => '1200 × 150',
			'ratio' => '8 / 1',
		),
		'side'    => array(
			'name' => __( 'Side column (next to every story)', 'kassmtv' ),
			'size' => '300 × 600',
			'ratio' => '1 / 2',
		),
		'article' => array(
			'name' => __( 'Inside stories (after the 3rd paragraph)', 'kassmtv' ),
			'size' => '1200 × 400',
			'ratio' => '3 / 1',
		),
		'feed'    => array(
			'name' => __( 'Between stories (homepage and news lists)', 'kassmtv' ),
			'size' => '1200 × 400',
			'ratio' => '3 / 1',
		),
	);
}

/* -------------------------------------------------------------------------
 * Settings (WP Admin → Ads → Settings & Google)
 * ---------------------------------------------------------------------- */
function kassmtv_ads_defaults() {
	return array(
		'google_client' => '',
		'google_on'     => 1,
		'slot_top'      => '',
		'slot_side'     => '',
		'slot_article'  => '',
		'slot_feed'     => '',
		'house_on'      => 1,
		'prices'        => '',
		'lead_email'    => '',
	);
}

function kassmtv_ads_option( $key ) {
	$saved    = get_option( 'kassmtv_ads', array() );
	$defaults = kassmtv_ads_defaults();
	$saved    = is_array( $saved ) ? $saved : array();

	return isset( $saved[ $key ] ) ? $saved[ $key ] : ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );
}

/**
 * The Google publisher ID in the form "ca-pub-1234567890123456", or ''.
 */
function kassmtv_google_client() {
	$client = trim( (string) kassmtv_ads_option( 'google_client' ) );

	if ( preg_match( '/(\d{10,20})/', $client, $match ) ) {
		return 'ca-pub-' . $match[1];
	}

	return '';
}

function kassmtv_google_on() {
	return kassmtv_google_client() && kassmtv_ads_option( 'google_on' );
}

function kassmtv_ads_sanitize( $input ) {
	$input = is_array( $input ) ? $input : array();
	$clean = kassmtv_ads_defaults();

	$clean['google_client'] = isset( $input['google_client'] ) ? sanitize_text_field( $input['google_client'] ) : '';
	$clean['google_on']     = empty( $input['google_on'] ) ? 0 : 1;
	$clean['house_on']      = empty( $input['house_on'] ) ? 0 : 1;
	$clean['prices']        = isset( $input['prices'] ) ? sanitize_textarea_field( $input['prices'] ) : '';
	$clean['lead_email']    = isset( $input['lead_email'] ) ? sanitize_email( $input['lead_email'] ) : '';

	foreach ( array_keys( kassmtv_ad_places() ) as $place ) {
		$value                   = isset( $input[ 'slot_' . $place ] ) ? $input[ 'slot_' . $place ] : '';
		$clean[ 'slot_' . $place ] = preg_replace( '/\D/', '', (string) $value );
	}

	return $clean;
}

function kassmtv_ads_register_setting() {
	register_setting(
		'kassmtv_ads',
		'kassmtv_ads',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kassmtv_ads_sanitize',
			'default'           => kassmtv_ads_defaults(),
		)
	);
}
add_action( 'admin_init', 'kassmtv_ads_register_setting' );

/* -------------------------------------------------------------------------
 * Ads and ad requests in WP Admin
 * ---------------------------------------------------------------------- */
function kassmtv_register_ad_types() {
	register_post_type(
		'kass_ad',
		array(
			'labels'          => array(
				'name'               => __( 'Ads', 'kassmtv' ),
				'singular_name'      => __( 'Ad', 'kassmtv' ),
				'menu_name'          => __( 'Ads', 'kassmtv' ),
				'all_items'          => __( 'All ads', 'kassmtv' ),
				'add_new'            => __( 'Add new ad', 'kassmtv' ),
				'add_new_item'       => __( 'Add new ad', 'kassmtv' ),
				'edit_item'          => __( 'Edit ad', 'kassmtv' ),
				'new_item'           => __( 'New ad', 'kassmtv' ),
				'search_items'       => __( 'Search ads', 'kassmtv' ),
				'not_found'          => __( 'No ads yet. Press "Add new ad" to put your first one up.', 'kassmtv' ),
				'featured_image'     => __( 'Ad picture', 'kassmtv' ),
				'set_featured_image' => __( 'Choose the ad picture', 'kassmtv' ),
				'remove_featured_image' => __( 'Remove the ad picture', 'kassmtv' ),
				'use_featured_image' => __( 'Use as the ad picture', 'kassmtv' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 25,
			'menu_icon'       => 'dashicons-money-alt',
			'supports'        => array( 'title', 'thumbnail' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		)
	);

	register_post_type(
		'kass_ad_lead',
		array(
			'labels'          => array(
				'name'          => __( 'Ad requests', 'kassmtv' ),
				'singular_name' => __( 'Ad request', 'kassmtv' ),
				'menu_name'     => __( 'Ad requests', 'kassmtv' ),
				'all_items'     => __( 'Ad requests', 'kassmtv' ),
				'edit_item'     => __( 'Ad request', 'kassmtv' ),
				'search_items'  => __( 'Search ad requests', 'kassmtv' ),
				'not_found'     => __( 'No ad requests yet. They arrive from the "Advertise with us" page.', 'kassmtv' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'edit.php?post_type=kass_ad',
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'kassmtv_register_ad_types' );

function kassmtv_ads_title_placeholder( $text, $post ) {
	return 'kass_ad' === $post->post_type ? __( 'Advertiser name (e.g. Mama Ngina Restaurant)', 'kassmtv' ) : $text;
}
add_filter( 'enter_title_here', 'kassmtv_ads_title_placeholder', 10, 2 );

/* ----- The ad details box ----- */
function kassmtv_ad_meta_box() {
	add_meta_box( 'kassmtv_ad_details', __( 'Ad details', 'kassmtv' ), 'kassmtv_ad_meta_box_html', 'kass_ad', 'normal', 'high' );
	add_meta_box( 'kassmtv_ad_stats', __( 'How the ad is doing', 'kassmtv' ), 'kassmtv_ad_stats_html', 'kass_ad', 'side', 'default' );
	add_meta_box( 'kassmtv_lead_details', __( 'Contact details', 'kassmtv' ), 'kassmtv_lead_details_html', 'kass_ad_lead', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'kassmtv_ad_meta_box' );

function kassmtv_ad_meta_box_html( $post ) {
	wp_nonce_field( 'kassmtv_ad_save', 'kassmtv_ad_nonce' );

	$link   = get_post_meta( $post->ID, 'kass_ad_link', true );
	$places = (array) get_post_meta( $post->ID, 'kass_ad_places', true );
	$starts = get_post_meta( $post->ID, 'kass_ad_starts', true );
	$ends   = get_post_meta( $post->ID, 'kass_ad_ends', true );
	$notes  = get_post_meta( $post->ID, 'kass_ad_notes', true );
	?>
	<p style="font-size:14px;margin-top:0">
		<?php esc_html_e( 'Step 1: write the advertiser name at the top. Step 2: choose the ad picture on the right (box "Ad picture"). Step 3: fill in below. Step 4: press Publish.', 'kassmtv' ); ?>
	</p>
	<p>
		<label for="kass_ad_link"><strong><?php esc_html_e( 'Where the ad goes when clicked (website, WhatsApp or Facebook link)', 'kassmtv' ); ?></strong></label><br>
		<input type="url" id="kass_ad_link" name="kass_ad_link" value="<?php echo esc_attr( $link ); ?>" class="widefat" placeholder="https://">
		<span class="description"><?php esc_html_e( 'For WhatsApp use https://wa.me/ and the number, e.g. https://wa.me/12065550123', 'kassmtv' ); ?></span>
	</p>
	<p><strong><?php esc_html_e( 'Where should the ad show? (tick one or more)', 'kassmtv' ); ?></strong></p>
	<?php foreach ( kassmtv_ad_places() as $key => $place ) : ?>
		<label style="display:block;margin:6px 0">
			<input type="checkbox" name="kass_ad_places[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $places, true ) ); ?>>
			<?php echo esc_html( $place['name'] ); ?>
			<span class="description">— <?php echo esc_html( sprintf( /* translators: %s: picture size */ __( 'best picture size %s pixels', 'kassmtv' ), $place['size'] ) ); ?></span>
		</label>
	<?php endforeach; ?>
	<p style="display:flex;gap:24px;flex-wrap:wrap">
		<label><strong><?php esc_html_e( 'Start date', 'kassmtv' ); ?></strong><br>
			<input type="date" name="kass_ad_starts" value="<?php echo esc_attr( $starts ); ?>">
		</label>
		<label><strong><?php esc_html_e( 'End date (the ad stops by itself after this day)', 'kassmtv' ); ?></strong><br>
			<input type="date" name="kass_ad_ends" value="<?php echo esc_attr( $ends ); ?>">
		</label>
	</p>
	<p class="description"><?php esc_html_e( 'Leave the start date empty to start now. Leave the end date empty to keep the ad up until you remove it.', 'kassmtv' ); ?></p>
	<p>
		<label for="kass_ad_notes"><strong><?php esc_html_e( 'Private notes (price paid, contact person …) — never shown on the website', 'kassmtv' ); ?></strong></label><br>
		<textarea id="kass_ad_notes" name="kass_ad_notes" rows="3" class="widefat"><?php echo esc_textarea( $notes ); ?></textarea>
	</p>
	<?php
}

function kassmtv_ad_stats_html( $post ) {
	$views  = (int) get_post_meta( $post->ID, 'kass_ad_views', true );
	$clicks = (int) get_post_meta( $post->ID, 'kass_ad_clicks', true );
	$rate   = $views ? round( $clicks / $views * 100, 2 ) : 0;
	?>
	<p style="font-size:15px;margin:0 0 6px"><strong><?php echo esc_html( number_format_i18n( $views ) ); ?></strong> <?php esc_html_e( 'times seen', 'kassmtv' ); ?></p>
	<p style="font-size:15px;margin:0 0 6px"><strong><?php echo esc_html( number_format_i18n( $clicks ) ); ?></strong> <?php esc_html_e( 'clicks', 'kassmtv' ); ?></p>
	<p style="margin:0" class="description"><?php echo esc_html( sprintf( /* translators: %s: percent */ __( '%s%% of people who saw it clicked. Send these numbers to the advertiser.', 'kassmtv' ), $rate ) ); ?></p>
	<?php
}

function kassmtv_ad_save( $post_id ) {
	if ( ! isset( $_POST['kassmtv_ad_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kassmtv_ad_nonce'] ) ), 'kassmtv_ad_save' ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$link   = isset( $_POST['kass_ad_link'] ) ? esc_url_raw( wp_unslash( $_POST['kass_ad_link'] ) ) : '';
	$places = isset( $_POST['kass_ad_places'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['kass_ad_places'] ) ) : array();
	$places = array_values( array_intersect( $places, array_keys( kassmtv_ad_places() ) ) );
	$starts = isset( $_POST['kass_ad_starts'] ) ? sanitize_text_field( wp_unslash( $_POST['kass_ad_starts'] ) ) : '';
	$ends   = isset( $_POST['kass_ad_ends'] ) ? sanitize_text_field( wp_unslash( $_POST['kass_ad_ends'] ) ) : '';
	$notes  = isset( $_POST['kass_ad_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kass_ad_notes'] ) ) : '';

	$starts = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $starts ) ? $starts : '';
	$ends   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ends ) ? $ends : '';

	update_post_meta( $post_id, 'kass_ad_link', $link );
	update_post_meta( $post_id, 'kass_ad_places', $places );
	update_post_meta( $post_id, 'kass_ad_starts', $starts );
	update_post_meta( $post_id, 'kass_ad_ends', $ends );
	update_post_meta( $post_id, 'kass_ad_notes', $notes );

	delete_transient( 'kassmtv_live_ads' );
}
add_action( 'save_post_kass_ad', 'kassmtv_ad_save' );

function kassmtv_ads_flush( $post_id ) {
	if ( 'kass_ad' === get_post_type( $post_id ) ) {
		delete_transient( 'kassmtv_live_ads' );
	}
}
add_action( 'trashed_post', 'kassmtv_ads_flush' );
add_action( 'deleted_post', 'kassmtv_ads_flush' );
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( 'kass_ad' === $post->post_type && $new !== $old ) {
		delete_transient( 'kassmtv_live_ads' );
	}
}, 10, 3 );

/* ----- Columns in the ads list ----- */
function kassmtv_ad_columns( $columns ) {
	return array(
		'cb'          => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'kass_pic'    => __( 'Picture', 'kassmtv' ),
		'title'       => __( 'Advertiser', 'kassmtv' ),
		'kass_places' => __( 'Shows in', 'kassmtv' ),
		'kass_status' => __( 'Status', 'kassmtv' ),
		'kass_views'  => __( 'Seen', 'kassmtv' ),
		'kass_clicks' => __( 'Clicks', 'kassmtv' ),
	);
}
add_filter( 'manage_kass_ad_posts_columns', 'kassmtv_ad_columns' );

function kassmtv_ad_column( $column, $post_id ) {
	switch ( $column ) {
		case 'kass_pic':
			echo get_the_post_thumbnail( $post_id, 'medium', array( 'style' => 'max-width:120px;max-height:80px;width:auto;height:auto;border-radius:6px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'kass_places':
			$names = array();
			$all   = kassmtv_ad_places();

			foreach ( (array) get_post_meta( $post_id, 'kass_ad_places', true ) as $key ) {
				if ( isset( $all[ $key ] ) ) {
					$names[] = strtok( $all[ $key ]['name'], '(' );
				}
			}

			echo esc_html( $names ? implode( ', ', array_map( 'trim', $names ) ) : '—' );
			break;
		case 'kass_status':
			echo esc_html( kassmtv_ad_status_text( $post_id ) );
			break;
		case 'kass_views':
			echo esc_html( number_format_i18n( (int) get_post_meta( $post_id, 'kass_ad_views', true ) ) );
			break;
		case 'kass_clicks':
			echo esc_html( number_format_i18n( (int) get_post_meta( $post_id, 'kass_ad_clicks', true ) ) );
			break;
	}
}
add_action( 'manage_kass_ad_posts_custom_column', 'kassmtv_ad_column', 10, 2 );

function kassmtv_ad_status_text( $post_id ) {
	if ( 'publish' !== get_post_status( $post_id ) ) {
		return __( 'Not published', 'kassmtv' );
	}

	$today  = current_time( 'Y-m-d' );
	$starts = get_post_meta( $post_id, 'kass_ad_starts', true );
	$ends   = get_post_meta( $post_id, 'kass_ad_ends', true );

	if ( ! has_post_thumbnail( $post_id ) ) {
		return __( 'Needs a picture', 'kassmtv' );
	}

	if ( ! get_post_meta( $post_id, 'kass_ad_places', true ) ) {
		return __( 'Choose where it shows', 'kassmtv' );
	}

	if ( $starts && $starts > $today ) {
		/* translators: %s: date */
		return sprintf( __( 'Starts %s', 'kassmtv' ), $starts );
	}

	if ( $ends && $ends < $today ) {
		/* translators: %s: date */
		return sprintf( __( 'Ended %s', 'kassmtv' ), $ends );
	}

	/* translators: %s: date */
	return $ends ? sprintf( __( 'Live until %s', 'kassmtv' ), $ends ) : __( 'Live', 'kassmtv' );
}

/* ----- Ad requests: contact box and list columns ----- */
function kassmtv_lead_details_html( $post ) {
	$fields = array(
		'kass_business' => __( 'Business', 'kassmtv' ),
		'kass_name'     => __( 'Contact person', 'kassmtv' ),
		'kass_phone'    => __( 'Phone / WhatsApp', 'kassmtv' ),
		'kass_email'    => __( 'Email', 'kassmtv' ),
		'kass_places'   => __( 'Interested in', 'kassmtv' ),
		'kass_budget'   => __( 'Budget', 'kassmtv' ),
	);

	foreach ( $fields as $key => $label ) {
		$value = get_post_meta( $post->ID, $key, true );
		printf( '<p style="margin:0 0 8px"><strong>%1$s:</strong><br>%2$s</p>', esc_html( $label ), esc_html( $value ? $value : '—' ) );
	}
}

function kassmtv_lead_columns( $columns ) {
	return array(
		'cb'            => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'         => __( 'Business', 'kassmtv' ),
		'kass_contact'  => __( 'Contact', 'kassmtv' ),
		'kass_places'   => __( 'Interested in', 'kassmtv' ),
		'date'          => __( 'Date', 'kassmtv' ),
	);
}
add_filter( 'manage_kass_ad_lead_posts_columns', 'kassmtv_lead_columns' );

function kassmtv_lead_column( $column, $post_id ) {
	if ( 'kass_contact' === $column ) {
		echo esc_html( trim( get_post_meta( $post_id, 'kass_name', true ) . ' · ' . get_post_meta( $post_id, 'kass_phone', true ) . ' · ' . get_post_meta( $post_id, 'kass_email', true ), ' ·' ) );
	} elseif ( 'kass_places' === $column ) {
		echo esc_html( get_post_meta( $post_id, 'kass_places', true ) );
	}
}
add_action( 'manage_kass_ad_lead_posts_custom_column', 'kassmtv_lead_column', 10, 2 );

/* ----- Settings screen ----- */
function kassmtv_ads_menu() {
	add_submenu_page(
		'edit.php?post_type=kass_ad',
		__( 'Ads: settings & Google', 'kassmtv' ),
		__( 'Settings & Google', 'kassmtv' ),
		'manage_options',
		'kassmtv-ads',
		'kassmtv_ads_settings_html'
	);
}
add_action( 'admin_menu', 'kassmtv_ads_menu' );

function kassmtv_ads_settings_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$o       = get_option( 'kassmtv_ads', array() );
	$o       = wp_parse_args( is_array( $o ) ? $o : array(), kassmtv_ads_defaults() );
	$page    = kassmtv_advertise_page();
	$client  = kassmtv_google_client();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Ads: settings & Google', 'kassmtv' ); ?></h1>
		<p style="font-size:14px;max-width:760px">
			<?php esc_html_e( 'How the ad spaces are filled: 1) your own paid ad (WP Admin → Ads → Add new ad). 2) If there is none, a Google ad (once Google has approved the website). 3) If there is neither, a small "Advertise with KASS-mtv" box that sends businesses to your advertise page.', 'kassmtv' ); ?>
		</p>
		<?php if ( $page ) : ?>
			<p><a class="button" href="<?php echo esc_url( get_permalink( $page ) ); ?>" target="_blank"><?php esc_html_e( 'Open the "Advertise with us" page', 'kassmtv' ); ?></a></p>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kassmtv_ads' ); ?>

			<h2><?php esc_html_e( 'Google AdSense', 'kassmtv' ); ?></h2>
			<ol style="max-width:760px">
				<li><?php echo wp_kses_post( __( 'Go to <a href="https://adsense.google.com" target="_blank" rel="noopener">adsense.google.com</a> and sign up with your Google account. Add the website <strong>kassmtvusa.com</strong>.', 'kassmtv' ) ); ?></li>
				<li><?php esc_html_e( 'Google shows a number that starts with "pub-" (your publisher ID). Paste it below and press Save. The website then adds the Google code and the ads.txt file by itself.', 'kassmtv' ); ?></li>
				<li><?php esc_html_e( 'In AdSense, press "Verify" / "Request review". Approval usually takes from a few days to a few weeks.', 'kassmtv' ); ?></li>
				<li><?php esc_html_e( 'After approval: in AdSense go to Ads → By ad unit → Display ads, make one unit for each space below, and paste each unit\'s "data-ad-slot" number here. (Or simply switch on "Auto ads" in AdSense.)', 'kassmtv' ); ?></li>
			</ol>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="kass_google_client"><?php esc_html_e( 'Publisher ID', 'kassmtv' ); ?></label></th>
					<td>
						<input type="text" id="kass_google_client" name="kassmtv_ads[google_client]" value="<?php echo esc_attr( $o['google_client'] ); ?>" class="regular-text" placeholder="pub-1234567890123456">
						<?php if ( $client ) : ?>
							<p class="description">✓ <?php echo esc_html( sprintf( /* translators: %s: ads.txt url */ __( 'Google code is on the website. Your ads.txt: %s', 'kassmtv' ), home_url( '/ads.txt' ) ) ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Google ads on/off', 'kassmtv' ); ?></th>
					<td><label><input type="checkbox" name="kassmtv_ads[google_on]" value="1" <?php checked( $o['google_on'] ); ?>> <?php esc_html_e( 'Show Google ads in spaces that have no paid ad', 'kassmtv' ); ?></label></td>
				</tr>
				<?php foreach ( kassmtv_ad_places() as $key => $place ) : ?>
					<tr>
						<th scope="row"><label for="kass_slot_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( sprintf( /* translators: %s: ad space */ __( 'Ad unit number: %s', 'kassmtv' ), strtok( $place['name'], '(' ) ) ); ?></label></th>
						<td><input type="text" inputmode="numeric" id="kass_slot_<?php echo esc_attr( $key ); ?>" name="kassmtv_ads[slot_<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $o[ 'slot_' . $key ] ); ?>" class="regular-text" placeholder="1234567890"></td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2><?php esc_html_e( 'Your own ads', 'kassmtv' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( '"Advertise with us" box', 'kassmtv' ); ?></th>
					<td><label><input type="checkbox" name="kassmtv_ads[house_on]" value="1" <?php checked( $o['house_on'] ); ?>> <?php esc_html_e( 'When a space has no ad at all, show a small box inviting businesses to advertise', 'kassmtv' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="kass_prices"><?php esc_html_e( 'Price list (shown on the advertise page)', 'kassmtv' ); ?></label></th>
					<td>
						<textarea id="kass_prices" name="kassmtv_ads[prices]" rows="6" class="large-text" placeholder="<?php esc_attr_e( "Top banner | $150 / month | Every page, first thing readers see\nSide column | $100 / month | Next to every story", 'kassmtv' ); ?>"><?php echo esc_textarea( $o['prices'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One package per line: Name | Price | Short note. Leave empty to show "Ask us for the price list".', 'kassmtv' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="kass_lead_email"><?php esc_html_e( 'Send ad requests to this email', 'kassmtv' ); ?></label></th>
					<td><input type="email" id="kass_lead_email" name="kassmtv_ads[lead_email]" value="<?php echo esc_attr( $o['lead_email'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"></td>
				</tr>
			</table>

			<?php submit_button( __( 'Save', 'kassmtv' ) ); ?>
		</form>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Which paid ads are live right now
 * ---------------------------------------------------------------------- */
function kassmtv_live_ads() {
	static $live = null;

	if ( null !== $live ) {
		return $live;
	}

	$live = get_transient( 'kassmtv_live_ads' );

	if ( ! is_array( $live ) ) {
		$live = array();
		$ids  = get_posts(
			array(
				'post_type'        => 'kass_ad',
				'post_status'      => 'publish',
				'posts_per_page'   => 100,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);

		foreach ( $ids as $id ) {
			$image = get_post_thumbnail_id( $id );
			$link  = get_post_meta( $id, 'kass_ad_link', true );

			if ( ! $image ) {
				continue;
			}

			$live[] = array(
				'id'     => (int) $id,
				'name'   => get_the_title( $id ),
				'image'  => (int) $image,
				'link'   => $link,
				'places' => (array) get_post_meta( $id, 'kass_ad_places', true ),
				'starts' => (string) get_post_meta( $id, 'kass_ad_starts', true ),
				'ends'   => (string) get_post_meta( $id, 'kass_ad_ends', true ),
			);
		}

		set_transient( 'kassmtv_live_ads', $live, HOUR_IN_SECONDS );
	}

	$today = current_time( 'Y-m-d' );

	$live = array_values(
		array_filter(
			$live,
			function ( $ad ) use ( $today ) {
				return ( ! $ad['starts'] || $ad['starts'] <= $today ) && ( ! $ad['ends'] || $ad['ends'] >= $today );
			}
		)
	);

	return $live;
}

function kassmtv_ads_for( $place ) {
	return array_values(
		array_filter(
			kassmtv_live_ads(),
			function ( $ad ) use ( $place ) {
				return in_array( $place, $ad['places'], true );
			}
		)
	);
}

/* -------------------------------------------------------------------------
 * Showing an ad space
 * ---------------------------------------------------------------------- */

/**
 * The HTML for one ad space, or '' when there is nothing to show.
 *
 * @param string $place top, side, article or feed.
 */
function kassmtv_ad( $place ) {
	if ( is_admin() || is_feed() || is_preview() ) {
		return '';
	}

	$places = kassmtv_ad_places();

	if ( ! isset( $places[ $place ] ) ) {
		return '';
	}

	$label = '<span class="k-ad-label">' . esc_html__( 'Advertisement', 'kassmtv' ) . '</span>';
	$ads   = kassmtv_ads_for( $place );

	// 1. Paid ads. All are in the page; the script shows one at random (pages may be cached).
	if ( $ads ) {
		$html = '<aside class="k-ad k-ad-' . esc_attr( $place ) . '" aria-label="' . esc_attr__( 'Advertisement', 'kassmtv' ) . '" data-k-ads>' . $label;

		foreach ( $ads as $n => $ad ) {
			$img = wp_get_attachment_image(
				$ad['image'],
				'side' === $place ? 'medium_large' : 'large',
				false,
				array(
					'class'    => 'k-ad-img',
					'loading'  => 'top' === $place ? 'eager' : 'lazy',
					'decoding' => 'async',
					'alt'      => sprintf( /* translators: %s: advertiser */ __( 'Advertisement: %s', 'kassmtv' ), $ad['name'] ),
				)
			);

			if ( ! $img ) {
				continue;
			}

			$attrs = sprintf(
				' class="k-ad-item" data-k-ad="%1$d" data-k-ad-end="%2$s"%3$s',
				$ad['id'],
				esc_attr( $ad['ends'] ),
				$n > 0 ? ' hidden' : ''
			);

			if ( $ad['link'] ) {
				$html .= sprintf(
					'<a%1$s href="%2$s" target="_blank" rel="sponsored noopener">%3$s</a>',
					$attrs,
					esc_url( rest_url( 'kassmtv/v1/ad-click/' . $ad['id'] ) ),
					$img
				);
			} else {
				$html .= '<div' . $attrs . '>' . $img . '</div>';
			}
		}

		return $html . '</aside>';
	}

	// 2. Google ads.
	$slot = preg_replace( '/\D/', '', (string) kassmtv_ads_option( 'slot_' . $place ) );

	if ( kassmtv_google_on() && $slot ) {
		return sprintf(
			'<aside class="k-ad k-ad-%1$s k-ad-google" aria-label="%2$s">%3$s<ins class="adsbygoogle" style="display:block" data-ad-client="%4$s" data-ad-slot="%5$s" data-ad-format="%6$s" data-full-width-responsive="true"></ins><script>(adsbygoogle=window.adsbygoogle||[]).push({});</script></aside>',
			esc_attr( $place ),
			esc_attr__( 'Advertisement', 'kassmtv' ),
			$label,
			esc_attr( kassmtv_google_client() ),
			esc_attr( $slot ),
			'side' === $place ? 'vertical' : 'auto'
		);
	}

	// 3. "Advertise with us" box, only in the roomier spaces.
	if ( kassmtv_ads_option( 'house_on' ) && in_array( $place, array( 'side', 'feed' ), true ) ) {
		$page = kassmtv_advertise_page();

		if ( ! $page ) {
			return '';
		}

		return sprintf(
			'<aside class="k-ad k-ad-%1$s k-ad-house" aria-label="%2$s"><a class="k-ad-house-inner" href="%3$s"><span class="k-ad-house-kicker">%4$s</span><span class="k-ad-house-title">%5$s</span><span class="k-ad-house-text">%6$s</span><span class="k-ad-house-btn">%7$s →</span></a></aside>',
			esc_attr( $place ),
			esc_attr__( 'Advertise with KASS-mtv', 'kassmtv' ),
			esc_url( get_permalink( $page ) ),
			esc_html__( 'Your business here', 'kassmtv' ),
			esc_html__( 'Reach Kenyans in America and across the diaspora.', 'kassmtv' ),
			esc_html__( 'Put your shop, church, service or event in front of our readers every day.', 'kassmtv' ),
			esc_html__( 'Advertise with us', 'kassmtv' )
		);
	}

	return '';
}

/* ----- Google code in the page head, and ads.txt ----- */
function kassmtv_google_head() {
	if ( ! kassmtv_google_on() || is_admin() ) {
		return;
	}

	printf(
		'<script async src="%s" crossorigin="anonymous"></script>' . "\n",
		esc_url( 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . kassmtv_google_client() )
	);
}
add_action( 'wp_head', 'kassmtv_google_head', 2 );

function kassmtv_ads_txt() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? strtok( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '?' ) : '';

	if ( '/ads.txt' !== $path ) {
		return;
	}

	$client = kassmtv_google_client();

	if ( ! $client ) {
		return;
	}

	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Cache-Control: public, max-age=3600' );
	echo 'google.com, ' . esc_html( str_replace( 'ca-', '', $client ) ) . ", DIRECT, f08c47fec0942fa0\n";
	exit;
}
add_action( 'init', 'kassmtv_ads_txt', 1 );

/* ----- Count views and clicks ----- */
function kassmtv_ads_routes() {
	register_rest_route(
		'kassmtv/v1',
		'/ad-click/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => function ( $request ) {
				$id   = (int) $request['id'];
				$link = 'kass_ad' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ? get_post_meta( $id, 'kass_ad_link', true ) : '';

				if ( ! $link ) {
					wp_safe_redirect( home_url( '/' ) );
					exit;
				}

				update_post_meta( $id, 'kass_ad_clicks', (int) get_post_meta( $id, 'kass_ad_clicks', true ) + 1 );

				nocache_headers();
				wp_redirect( esc_url_raw( $link ), 302 ); // phpcs:ignore WordPress.Security.SafeRedirect -- the advertiser's own link, set by an editor.
				exit;
			},
		)
	);

	register_rest_route(
		'kassmtv/v1',
		'/ad-view/(?P<id>\d+)',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => function ( $request ) {
				$id = (int) $request['id'];

				if ( 'kass_ad' !== get_post_type( $id ) ) {
					return new WP_REST_Response( array( 'ok' => false ), 404 );
				}

				update_post_meta( $id, 'kass_ad_views', (int) get_post_meta( $id, 'kass_ad_views', true ) + 1 );

				return new WP_REST_Response( array( 'ok' => true ), 200 );
			},
		)
	);
}
add_action( 'rest_api_init', 'kassmtv_ads_routes' );

function kassmtv_ads_script_data() {
	wp_add_inline_script(
		'kassmtv-theme',
		'window.kassmtvAds = ' . wp_json_encode( array( 'viewUrl' => esc_url_raw( rest_url( 'kassmtv/v1/ad-view/' ) ) ) ) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'kassmtv_ads_script_data', 20 );

/* -------------------------------------------------------------------------
 * Inside stories: an ad after the 3rd paragraph, "Also read" after the 6th
 * ---------------------------------------------------------------------- */
function kassmtv_story_inserts( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$parts = preg_split( '#(</p>)#i', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	$count = substr_count( strtolower( $content ), '</p>' );

	if ( $count < 4 ) {
		return $content;
	}

	$ad   = kassmtv_ad( 'article' );
	$also = $count >= 7 ? kassmtv_also_read() : '';
	$out  = '';
	$seen = 0;

	foreach ( $parts as $part ) {
		$out .= $part;

		if ( '</p>' === strtolower( $part ) ) {
			$seen++;

			if ( 3 === $seen && $ad ) {
				$out .= $ad;
			}

			if ( 6 === $seen && $also ) {
				$out .= $also;
			}
		}
	}

	return $out;
}
add_filter( 'the_content', 'kassmtv_story_inserts', 20 );

/**
 * One more story to read, from the same section when possible.
 */
function kassmtv_also_read() {
	$post_id = get_the_ID();
	$args    = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 1,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	$cats = wp_get_post_categories( $post_id );

	if ( $cats ) {
		$args['category__in'] = $cats;
	}

	$posts = get_posts( $args );

	if ( ! $posts ) {
		unset( $args['category__in'] );
		$posts = get_posts( $args );
	}

	if ( ! $posts ) {
		return '';
	}

	$other = $posts[0];

	return sprintf(
		'<aside class="k-also"><span class="k-also-label">%1$s</span><a class="k-also-link" href="%2$s"><span class="k-also-media">%3$s</span><span class="k-also-title">%4$s</span></a></aside>',
		esc_html__( 'Also read', 'kassmtv' ),
		esc_url( get_permalink( $other ) ),
		kassmtv_post_image( $other->ID, 'thumbnail', 'k-img' ),
		esc_html( get_the_title( $other ) )
	);
}

/* -------------------------------------------------------------------------
 * "Advertise with us" page: made once, filled by [kassmtv_advertise]
 * ---------------------------------------------------------------------- */
function kassmtv_advertise_page() {
	static $page = false;

	if ( false !== $page ) {
		return $page;
	}

	$found = get_page_by_path( 'advertise', OBJECT, 'page' );
	$page  = ( $found && 'publish' === $found->post_status ) ? $found : null;

	return $page;
}

function kassmtv_make_advertise_page() {
	if ( get_option( 'kassmtv_advertise_page' ) || wp_installing() ) {
		return;
	}

	update_option( 'kassmtv_advertise_page', time(), false );

	if ( get_page_by_path( 'advertise', OBJECT, 'page' ) ) {
		return;
	}

	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => __( 'Advertise with us', 'kassmtv' ),
			'post_name'    => 'advertise',
			'post_content' => "<!-- wp:shortcode -->\n[kassmtv_advertise]\n<!-- /wp:shortcode -->",
			'post_author'  => 1,
		)
	);
}
add_action( 'init', 'kassmtv_make_advertise_page', 30 );

/**
 * Real numbers only: how many stories, and reads in the last 30 days.
 */
function kassmtv_audience_numbers() {
	$cached = get_transient( 'kassmtv_audience' );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	global $wpdb;

	$stories = (int) wp_count_posts( 'post' )->publish;
	$since   = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
	$reads   = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT SUM(CAST(pm.meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = 'kassmtv_views' AND p.post_status = 'publish' AND p.post_type = 'post' AND p.post_date_gmt >= %s",
			$since
		)
	);

	$numbers = array(
		'stories' => $stories,
		'reads'   => $reads,
	);

	set_transient( 'kassmtv_audience', $numbers, 6 * HOUR_IN_SECONDS );

	return $numbers;
}

function kassmtv_advertise_shortcode() {
	$numbers = kassmtv_audience_numbers();
	$prices  = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) kassmtv_ads_option( 'prices' ) ) ) );
	$email   = kassmtv_option( 'email' );
	$phone   = kassmtv_option( 'phone' );

	ob_start();
	?>
	<div class="k-adv">
		<p class="k-adv-lead"><?php esc_html_e( 'KASS-mtv Diaspora News is read by Kenyans and Africans living in America and around the world — and by their families back home. Put your business in front of them every day.', 'kassmtv' ); ?></p>

		<div class="k-adv-stats">
			<div><strong><?php esc_html_e( 'Every day', 'kassmtv' ); ?></strong><span><?php esc_html_e( 'new stories: Kenya politics, the diaspora, world news and sports', 'kassmtv' ); ?></span></div>
			<?php if ( $numbers['stories'] > 0 ) : ?>
				<div><strong><?php echo esc_html( number_format_i18n( $numbers['stories'] ) ); ?></strong><span><?php esc_html_e( 'stories published so far', 'kassmtv' ); ?></span></div>
			<?php endif; ?>
			<?php if ( $numbers['reads'] >= 500 ) : ?>
				<div><strong><?php echo esc_html( number_format_i18n( $numbers['reads'] ) ); ?></strong><span><?php esc_html_e( 'story reads in the last 30 days', 'kassmtv' ); ?></span></div>
			<?php endif; ?>
			<div><strong><?php esc_html_e( 'Phone first', 'kassmtv' ); ?></strong><span><?php esc_html_e( 'ads look sharp on phones and computers', 'kassmtv' ); ?></span></div>
		</div>

		<h2><?php esc_html_e( 'Who should advertise', 'kassmtv' ); ?></h2>
		<ul class="k-adv-who">
			<li><?php esc_html_e( 'Money transfer and banking services', 'kassmtv' ); ?></li>
			<li><?php esc_html_e( 'Land, houses and building in Kenya', 'kassmtv' ); ?></li>
			<li><?php esc_html_e( 'Immigration, tax and legal services', 'kassmtv' ); ?></li>
			<li><?php esc_html_e( 'Churches, events and concerts', 'kassmtv' ); ?></li>
			<li><?php esc_html_e( 'Restaurants, shops and travel', 'kassmtv' ); ?></li>
			<li><?php esc_html_e( 'Schools, training and jobs', 'kassmtv' ); ?></li>
		</ul>

		<h2><?php esc_html_e( 'Our ad spaces', 'kassmtv' ); ?></h2>
		<div class="k-adv-places">
			<?php foreach ( kassmtv_ad_places() as $key => $place ) : ?>
				<div class="k-adv-place">
					<span class="k-adv-shape k-adv-shape-<?php echo esc_attr( $key ); ?>" aria-hidden="true"><span><?php echo esc_html( $place['size'] ); ?></span></span>
					<strong><?php echo esc_html( strtok( $place['name'], '(' ) ); ?></strong>
					<span><?php echo esc_html( trim( (string) strstr( $place['name'], '(' ), '()' ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="k-adv-note"><?php esc_html_e( 'Send us your picture in the size shown (JPG or PNG) and the link people should go to — your website, WhatsApp or Facebook page. No picture yet? Our design team can make one for you.', 'kassmtv' ); ?></p>

		<h2><?php esc_html_e( 'Prices', 'kassmtv' ); ?></h2>
		<?php if ( $prices ) : ?>
			<div class="k-adv-prices">
				<?php foreach ( $prices as $line ) : ?>
					<?php $cells = array_map( 'trim', explode( '|', $line ) ); ?>
					<div class="k-adv-price">
						<strong><?php echo esc_html( $cells[0] ); ?></strong>
						<?php if ( ! empty( $cells[1] ) ) : ?>
							<span class="k-adv-amount"><?php echo esc_html( $cells[1] ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $cells[2] ) ) : ?>
							<span><?php echo esc_html( $cells[2] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'Ask us for the price list — fill in the form below and we will reply within one working day. Weekly, monthly and event packages are available.', 'kassmtv' ); ?></p>
		<?php endif; ?>

		<h2 id="advertise-form"><?php esc_html_e( 'Book your ad', 'kassmtv' ); ?></h2>
		<form class="k-form k-adv-form" id="advertise" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-k-form>
			<input type="hidden" name="action" value="kassmtv_ad_request">
			<input type="hidden" name="kass_t" value="" data-k-time>
			<div class="k-trap" aria-hidden="true">
				<label><?php esc_html_e( 'Leave this empty', 'kassmtv' ); ?> <input type="text" name="kass_website" tabindex="-1" autocomplete="off"></label>
			</div>

			<div class="k-field-row">
				<label class="k-field">
					<span><?php esc_html_e( 'Business name *', 'kassmtv' ); ?></span>
					<input type="text" name="kass_business" required maxlength="120" autocomplete="organization">
				</label>
				<label class="k-field">
					<span><?php esc_html_e( 'Your name *', 'kassmtv' ); ?></span>
					<input type="text" name="kass_name" required maxlength="120" autocomplete="name">
				</label>
			</div>
			<div class="k-field-row">
				<label class="k-field">
					<span><?php esc_html_e( 'Phone or WhatsApp', 'kassmtv' ); ?></span>
					<input type="tel" name="kass_phone" maxlength="40" autocomplete="tel">
				</label>
				<label class="k-field">
					<span><?php esc_html_e( 'Email *', 'kassmtv' ); ?></span>
					<input type="email" name="kass_email" required maxlength="120" autocomplete="email">
				</label>
			</div>

			<fieldset class="k-adv-choose">
				<legend><?php esc_html_e( 'Which ad spaces interest you?', 'kassmtv' ); ?></legend>
				<?php foreach ( kassmtv_ad_places() as $key => $place ) : ?>
					<label class="k-check"><input type="checkbox" name="kass_places[]" value="<?php echo esc_attr( $key ); ?>"> <span><?php echo esc_html( strtok( $place['name'], '(' ) ); ?></span></label>
				<?php endforeach; ?>
			</fieldset>

			<label class="k-field">
				<span><?php esc_html_e( 'Budget per month (optional)', 'kassmtv' ); ?></span>
				<input type="text" name="kass_budget" maxlength="60" placeholder="<?php esc_attr_e( 'e.g. $100', 'kassmtv' ); ?>">
			</label>
			<label class="k-field">
				<span><?php esc_html_e( 'Tell us about your business and what you want to promote', 'kassmtv' ); ?></span>
				<textarea name="kass_message" rows="4" maxlength="3000"></textarea>
			</label>

			<button type="submit" class="k-btn k-btn-dark"><?php esc_html_e( 'Send my request', 'kassmtv' ); ?></button>
			<p class="k-form-msg" role="status" aria-live="polite" data-k-msg></p>
		</form>

		<?php if ( $email || $phone ) : ?>
			<p class="k-adv-note">
				<?php esc_html_e( 'Prefer to talk?', 'kassmtv' ); ?>
				<?php if ( $email ) : ?>
					<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
				<?php endif; ?>
				<?php if ( $email && $phone ) : ?>
					·
				<?php endif; ?>
				<?php if ( $phone ) : ?>
					<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', (string) $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<p class="k-adv-small"><?php esc_html_e( 'All ads are clearly labelled "Advertisement". Advertisers do not decide what we report. We do not accept ads that are misleading, hateful or illegal.', 'kassmtv' ); ?></p>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'kassmtv_advertise', 'kassmtv_advertise_shortcode' );

/* ----- The form on the advertise page ----- */
function kassmtv_handle_ad_request() {
	$error = kassmtv_spam_check( 'adreq', 4 );

	if ( $error ) {
		kassmtv_form_reply( false, $error, 'advertise' );
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- public form on cached pages; see inc/forms.php.
	$business = isset( $_POST['kass_business'] ) ? sanitize_text_field( wp_unslash( $_POST['kass_business'] ) ) : '';
	$name     = isset( $_POST['kass_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kass_name'] ) ) : '';
	$phone    = isset( $_POST['kass_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kass_phone'] ) ) : '';
	$email    = isset( $_POST['kass_email'] ) ? sanitize_email( wp_unslash( $_POST['kass_email'] ) ) : '';
	$budget   = isset( $_POST['kass_budget'] ) ? sanitize_text_field( wp_unslash( $_POST['kass_budget'] ) ) : '';
	$message  = isset( $_POST['kass_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kass_message'] ) ) : '';
	$chosen   = isset( $_POST['kass_places'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['kass_places'] ) ) : array();
	// phpcs:enable

	if ( '' === $business || '' === $name ) {
		kassmtv_form_reply( false, __( 'Please give your business name and your name.', 'kassmtv' ), 'advertise' );
	}

	if ( ! is_email( $email ) ) {
		kassmtv_form_reply( false, __( 'Please enter a valid email address.', 'kassmtv' ), 'advertise' );
	}

	$all   = kassmtv_ad_places();
	$names = array();

	foreach ( $chosen as $key ) {
		if ( isset( $all[ $key ] ) ) {
			$names[] = trim( strtok( $all[ $key ]['name'], '(' ) );
		}
	}

	$places = implode( ', ', $names );

	$id = wp_insert_post(
		array(
			'post_type'    => 'kass_ad_lead',
			'post_status'  => 'private',
			'post_title'   => mb_substr( $business, 0, 120 ),
			'post_content' => mb_substr( $message, 0, 3000 ),
		)
	);

	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, 'kass_business', $business );
		update_post_meta( $id, 'kass_name', $name );
		update_post_meta( $id, 'kass_phone', $phone );
		update_post_meta( $id, 'kass_email', $email );
		update_post_meta( $id, 'kass_places', $places );
		update_post_meta( $id, 'kass_budget', $budget );

		$to = kassmtv_ads_option( 'lead_email' );
		$to = is_email( $to ) ? $to : get_option( 'admin_email' );

		wp_mail(
			$to,
			/* translators: %s: business */
			sprintf( __( 'New ad request on KASS-mtv: %s', 'kassmtv' ), $business ),
			sprintf(
				/* translators: 1: business 2: name 3: phone 4: email 5: places 6: budget 7: message 8: admin link */
				__( "A business wants to advertise.\n\nBusiness: %1\$s\nName: %2\$s\nPhone/WhatsApp: %3\$s\nEmail: %4\$s\nInterested in: %5\$s\nBudget: %6\$s\n\nMessage:\n%7\$s\n\nAll ad requests: %8\$s", 'kassmtv' ),
				$business,
				$name,
				$phone ? $phone : '—',
				$email,
				$places ? $places : '—',
				$budget ? $budget : '—',
				$message ? $message : '—',
				admin_url( 'edit.php?post_type=kass_ad_lead&post_status=private' )
			),
			array( 'Reply-To: ' . $name . ' <' . $email . '>' )
		);
	}

	kassmtv_form_reply( true, __( 'Thank you! We have your request and will reply within one working day.', 'kassmtv' ), 'advertise' );
}
add_action( 'admin_post_nopriv_kassmtv_ad_request', 'kassmtv_handle_ad_request' );
add_action( 'admin_post_kassmtv_ad_request', 'kassmtv_handle_ad_request' );
