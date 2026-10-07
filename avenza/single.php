<?php
/**
 * Single article with table of contents, author box and related reading.
 *
 * @package avenza
 */

get_header();

while ( have_posts() ) :
	the_post();
	$cats    = get_the_category();
	$minutes = avenza_reading_time();
	$eyebrow = ( $cats ? esc_html( $cats[0]->name ) . ' <span aria-hidden="true">&middot;</span> ' : '' ) . esc_html(
		/* translators: %d: minutes */
		sprintf( _n( '%d min read', '%d min read', $minutes, 'avenza' ), $minutes )
	);
	$meta    = sprintf(
		/* translators: 1: author, 2: date */
		esc_html__( 'By %1$s. Updated %2$s', 'avenza' ),
		'<a href="' . esc_url( avenza_page_url( 'about' ) ) . '">' . esc_html__( 'the AVENZA Strategy Team', 'avenza' ) . '</a>',
		'<time datetime="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . esc_html( get_the_modified_date() ) . '</time>'
	);
	avenza_page_hero(
		array(
			'eyebrow' => $eyebrow,
			'lead'    => has_excerpt() ? get_the_excerpt() : '',
			'meta'    => $meta,
		)
	);

	list( $body, $toc ) = avenza_content_with_toc( apply_filters( 'the_content', get_the_content() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	?>
	<article <?php post_class( 'avz-article' ); ?>>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="avz-article__cover avz-container">
				<?php
				the_post_thumbnail(
					'avenza-wide',
					array(
						'loading'       => 'eager',
						'fetchpriority' => 'high',
						'sizes'         => '(min-width: 1240px) 1180px, 94vw',
					)
				);
				?>
			</figure>
		<?php endif; ?>

		<div class="avz-container avz-article__layout<?php echo count( $toc ) > 2 ? ' has-toc' : ''; ?>">
			<?php if ( count( $toc ) > 2 ) : ?>
				<aside class="avz-toc" aria-label="<?php esc_attr_e( 'On this page', 'avenza' ); ?>">
					<p class="avz-toc__title"><?php esc_html_e( 'On this page', 'avenza' ); ?></p>
					<ol>
						<?php foreach ( $toc as $item ) : ?>
							<li><a href="#<?php echo esc_attr( $item[0] ); ?>"><?php echo esc_html( $item[1] ); ?></a></li>
						<?php endforeach; ?>
					</ol>
				</aside>
			<?php endif; ?>

			<div class="avz-article__body entry-content">
				<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered post content. ?>

				<div class="avz-author">
					<div class="avz-author__mark"><?php echo avenza_mark_svg( 'author' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<div>
						<p class="avz-author__name"><?php esc_html_e( 'Written by the AVENZA Strategy Team', 'avenza' ); ?></p>
						<p class="avz-author__bio"><?php esc_html_e( 'We plan and run search, AI visibility, paid media and website projects. Every article is checked against the primary sources linked in it before it is published, and updated when those sources change.', 'avenza' ); ?></p>
					</div>
				</div>

				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</div>
		</div>
	</article>

	<?php
	$related = do_shortcode( '[avenza_posts limit="3"]' );
	if ( $related ) :
		?>
		<section class="avz-section avz-section--alt avz-related">
			<div class="avz-container">
				<p class="avz-eyebrow"><?php esc_html_e( 'Keep reading', 'avenza' ); ?></p>
				<h2 class="avz-section__title"><?php esc_html_e( 'More from Insights', 'avenza' ); ?></h2>
				<?php echo $related; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>
		<?php
	endif;

	avenza_cta_band();
endwhile;

get_footer();
