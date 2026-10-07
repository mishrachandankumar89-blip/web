<?php
/**
 * Single service page.
 *
 * @package avenza
 */

get_header();

while ( have_posts() ) :
	the_post();
	if ( ! avenza_has_hero() ) {
		avenza_page_hero(
			array(
				/* translators: %s: service number */
				'eyebrow' => esc_html( sprintf( __( 'N° %s Service', 'avenza' ), avenza_service_number() ) ),
				'lead'    => has_excerpt() ? get_the_excerpt() : '',
			)
		);
	}
	?>
	<div class="avz-content entry-content<?php echo avenza_has_hero() ? '' : ' avz-content--plain'; ?>">
		<?php the_content(); ?>
	</div>
	<?php
	if ( ! avenza_has_hero() ) {
		avenza_cta_band();
	}
endwhile;

get_footer();
