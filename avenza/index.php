<?php
/**
 * Insights listing (posts page) and the fallback template.
 *
 * @package avenza
 */

get_header();

$posts_page = (int) get_option( 'page_for_posts' );
$title      = $posts_page ? get_the_title( $posts_page ) : __( 'Insights', 'avenza' );
$lead       = $posts_page && has_excerpt( $posts_page ) ? get_the_excerpt( $posts_page ) : __( 'Practical, sourced guides on search, AI answers and growth.', 'avenza' );

avenza_page_hero(
	array(
		'eyebrow' => esc_html__( 'Insights', 'avenza' ),
		'title'   => esc_html( $title ),
		'lead'    => $lead,
	)
);
?>
<section class="avz-section avz-listing">
	<div class="avz-container">
		<?php if ( have_posts() ) : ?>
			<div class="avz-cards avz-cards--3">
				<?php
				while ( have_posts() ) :
					the_post();
					echo avenza_post_card( get_post(), 'h2' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				endwhile;
				?>
			</div>
			<?php avenza_pagination(); ?>
		<?php else : ?>
			<p class="avz-empty"><?php esc_html_e( 'New articles are on the way.', 'avenza' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
avenza_cta_band();
get_footer();
