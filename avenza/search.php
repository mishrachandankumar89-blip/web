<?php
/**
 * Search results.
 *
 * @package avenza
 */

get_header();

avenza_page_hero(
	array(
		'eyebrow' => esc_html__( 'Search', 'avenza' ),
		/* translators: %s: search query */
		'title'   => esc_html( sprintf( __( 'Results for "%s"', 'avenza' ), get_search_query() ) ),
	)
);
?>
<section class="avz-section avz-listing">
	<div class="avz-container">
		<div class="avz-search-again"><?php get_search_form(); ?></div>
		<?php if ( have_posts() ) : ?>
			<div class="avz-cards avz-cards--3">
				<?php
				while ( have_posts() ) :
					the_post();
					$item = get_post();
					echo 'avenza_service' === $item->post_type ? avenza_service_card( $item, 'h2' ) : avenza_post_card( $item, 'h2' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				endwhile;
				?>
			</div>
			<?php avenza_pagination(); ?>
		<?php else : ?>
			<p class="avz-empty"><?php esc_html_e( 'No matches. Try a shorter phrase, or browse the services below.', 'avenza' ); ?></p>
			<?php echo do_shortcode( '[avenza_services limit="6"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
