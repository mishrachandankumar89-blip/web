<?php
/**
 * Page not found.
 *
 * @package avenza
 */

get_header();

avenza_page_hero(
	array(
		'eyebrow' => esc_html__( 'Error 404', 'avenza' ),
		'title'   => wp_kses_post( __( 'This page is not here, <em>but the answer is</em>', 'avenza' ) ),
		'lead'    => __( 'The link may be old or mistyped. Search below, or start from one of our services.', 'avenza' ),
	)
);
?>
<section class="avz-section avz-listing">
	<div class="avz-container">
		<div class="avz-search-again"><?php get_search_form(); ?></div>
		<h2 class="avz-section__title"><?php esc_html_e( 'Popular services', 'avenza' ); ?></h2>
		<?php echo do_shortcode( '[avenza_services limit="6"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</section>
<?php
avenza_cta_band();
get_footer();
