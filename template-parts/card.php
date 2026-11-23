<?php
/**
 * One story card (used on news lists and "More news").
 *
 * @package kassmtv
 */

$kassmtv_card_cat = kassmtv_post_category( get_the_ID() );
?>
<article <?php post_class( 'k-card' ); ?>>
	<a class="k-card-media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php echo kassmtv_post_image( get_the_ID(), 'kassmtv-card', 'k-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</a>
	<div class="k-card-body">
		<?php if ( $kassmtv_card_cat ) : ?>
			<a class="k-cat k-cat-sm" href="<?php echo esc_url( $kassmtv_card_cat[1] ); ?>"><?php echo esc_html( $kassmtv_card_cat[0] ); ?></a>
		<?php endif; ?>
		<h3><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>
		<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?></p>
		<p class="k-meta"><?php echo esc_html( kassmtv_time_ago() ); ?></p>
	</div>
</article>
