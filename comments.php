<?php
/**
 * Comments under a story.
 *
 * @package kassmtv
 */

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" aria-label="<?php esc_attr_e( 'Comments', 'kassmtv' ); ?>">
	<?php if ( have_comments() ) : ?>
		<h2>
			<?php
			/* translators: %d: number of comments */
			echo esc_html( sprintf( _n( '%d comment', '%d comments', get_comments_number(), 'kassmtv' ), get_comments_number() ) );
			?>
		</h2>
		<ol class="k-comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 40,
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php comment_form(); ?>
</section>
