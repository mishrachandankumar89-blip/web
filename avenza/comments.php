<?php
/**
 * Comments. Closed by default on this business site; shown only if enabled.
 *
 * @package avenza
 */

if ( post_password_required() ) {
	return;
}
?>
<section class="avz-comments" id="comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="avz-comments__title"><?php esc_html_e( 'Discussion', 'avenza' ); ?></h2>
		<ol class="avz-comments__list">
			<?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true ) ); ?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
