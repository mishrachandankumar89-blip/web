<?php
/**
 * Category, tag and date archives.
 *
 * @package avenza
 */

get_header();

avenza_page_hero(
	array(
		'eyebrow' => esc_html__( 'Insights', 'avenza' ),
		'title'   => esc_html( wp_strip_all_tags( get_the_archive_title() ) ),
		'lead'    => wp_strip_all_tags( get_the_archive_description() ),
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
			<p class="avz-empty"><?php esc_html_e( 'Nothing here yet.', 'avenza' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
avenza_cta_band();
get_footer();
