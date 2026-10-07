<?php
/**
 * Pages. Theme pages carry their own hero block; any other page gets the default hero.
 *
 * @package avenza
 */

get_header();

while ( have_posts() ) :
	the_post();
	if ( ! avenza_has_hero() ) {
		avenza_page_hero(
			array(
				'lead' => has_excerpt() ? get_the_excerpt() : '',
			)
		);
	}
	?>
	<div class="avz-content entry-content<?php echo avenza_has_hero() ? '' : ' avz-content--plain'; ?>">
		<?php the_content(); ?>
	</div>
	<?php
endwhile;

get_footer();
