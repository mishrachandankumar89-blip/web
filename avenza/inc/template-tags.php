<?php
/**
 * Template parts shared by the PHP templates.
 *
 * @package avenza
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default emerald hero for views without a hero block.
 *
 * @param array $args eyebrow, title, lead, meta.
 */
function avenza_page_hero( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'eyebrow' => '',
			'title'   => get_the_title(),
			'lead'    => '',
			'meta'    => '',
		)
	);
	?>
	<section class="avz-hero avz-hero--simple">
		<div class="avz-container">
			<div class="avz-hero__text">
				<?php if ( $args['eyebrow'] ) : ?>
					<p class="avz-eyebrow"><?php echo wp_kses_post( $args['eyebrow'] ); ?></p>
				<?php endif; ?>
				<h1 class="avz-hero__title"><?php echo wp_kses_post( $args['title'] ); ?></h1>
				<?php if ( $args['lead'] ) : ?>
					<p class="avz-hero__lead"><?php echo esc_html( $args['lead'] ); ?></p>
				<?php endif; ?>
				<?php if ( $args['meta'] ) : ?>
					<p class="avz-hero__meta"><?php echo wp_kses_post( $args['meta'] ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Closing call to action band used by templates.
 *
 * @param string $heading Heading.
 * @param string $text    Text.
 */
function avenza_cta_band( $heading = '', $text = '' ) {
	$heading = $heading ? $heading : __( 'Ready to become the answer?', 'avenza' );
	$text    = $text ? $text : __( 'Tell us where you want to be found. We will show you where you stand today in Google and in AI answers, and what it takes to move.', 'avenza' );
	$wa      = avenza_whatsapp_url( __( 'Hello AVENZA, I would like to discuss my website.', 'avenza' ) );
	?>
	<section class="avz-cta-band">
		<div class="avz-container">
			<div class="avz-cta">
				<p class="avz-eyebrow"><?php esc_html_e( 'Next step', 'avenza' ); ?></p>
				<h2 class="avz-cta__title"><?php echo esc_html( $heading ); ?></h2>
				<p class="avz-cta__text"><?php echo esc_html( $text ); ?></p>
				<div class="avz-cta__actions">
					<a class="avz-btn avz-btn--light" href="<?php echo esc_url( avenza_cta_url() ); ?>"><?php echo esc_html( avenza_opt( 'cta_text' ) ); ?></a>
					<?php if ( $wa ) : ?>
						<a class="avz-btn avz-btn--ghost" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Message on WhatsApp', 'avenza' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Add ids to H2 headings and return the table of contents.
 *
 * @param string $html Rendered content.
 * @return array [ html, toc items ]
 */
function avenza_content_with_toc( $html ) {
	$toc  = array();
	$used = array();
	$html = preg_replace_callback(
		'/<h2([^>]*)>(.*?)<\/h2>/is',
		function ( $m ) use ( &$toc, &$used ) {
			$text = trim( wp_strip_all_tags( $m[2] ) );
			if ( preg_match( '/\sid="([^"]+)"/', $m[1], $existing ) ) {
				$id = $existing[1];
			} else {
				$base = sanitize_title( $text );
				$id   = $base ? $base : 'section';
				$n    = 2;
				while ( isset( $used[ $id ] ) ) {
					$id = $base . '-' . $n++;
				}
				$m[1] .= ' id="' . esc_attr( $id ) . '"';
			}
			$used[ $id ] = true;
			$toc[]       = array( $id, $text );
			return '<h2' . $m[1] . '>' . $m[2] . '</h2>';
		},
		$html
	);
	return array( $html, $toc );
}

/**
 * Pagination for listings.
 */
function avenza_pagination() {
	the_posts_pagination(
		array(
			'mid_size'           => 1,
			'prev_text'          => __( 'Newer', 'avenza' ),
			'next_text'          => __( 'Older', 'avenza' ),
			'screen_reader_text' => __( 'More articles', 'avenza' ),
			'class'              => 'avz-pagination',
		)
	);
}
