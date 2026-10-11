<?php
/**
 * Service category hub, for example /expertise/seo/.
 *
 * @package avenza
 */

get_header();

$term     = get_queried_object();
$services = avenza_get_services( -1, array(), $term->slug );
$img      = avenza_category_image_id( $term );
$short    = get_term_meta( $term->term_id, '_avenza_short', true );
?>
<section class="avz-hero avz-hero--hub">
	<div class="avz-container avz-hub__grid">
		<div class="avz-hero__text">
			<p class="avz-eyebrow">
				<?php
				/* translators: %d: number of services */
				echo esc_html( sprintf( _n( 'Expertise · %d service', 'Expertise · %d services', count( $services ), 'avenza' ), count( $services ) ) );
				?>
			</p>
			<h1 class="avz-hero__title"><?php echo esc_html( $term->name ); ?></h1>
			<?php if ( $short ) : ?>
				<p class="avz-hero__lead"><?php echo esc_html( $short ); ?></p>
			<?php endif; ?>
			<div class="wp-block-buttons avz-hero__actions">
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( avenza_cta_url() ); ?>"><?php echo esc_html( avenza_opt( 'cta_text' ) ); ?></a></div>
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( avenza_page_url( 'services' ) ); ?>"><?php esc_html_e( 'All services', 'avenza' ); ?></a></div>
			</div>
		</div>
		<?php if ( $img ) : ?>
			<figure class="avz-plate avz-hub__art">
				<?php echo wp_get_attachment_image( $img, 'large', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(min-width: 900px) 480px, 92vw' ) ); ?>
			</figure>
		<?php endif; ?>
	</div>
</section>

<?php if ( $term->description ) : ?>
	<div class="avz-answer-wrap">
		<div class="avz-answer">
			<p class="avz-eyebrow"><?php esc_html_e( 'The short answer', 'avenza' ); ?></p>
			<h2>
				<?php
				/* translators: %s: discipline name */
				echo esc_html( sprintf( __( 'What does AVENZA do in %s?', 'avenza' ), $term->name ) );
				?>
			</h2>
			<p><?php echo esc_html( $term->description ); ?></p>
		</div>
	</div>
<?php endif; ?>

<section class="avz-section">
	<div class="avz-section__head">
		<p class="avz-eyebrow"><?php esc_html_e( 'Services in this discipline', 'avenza' ); ?></p>
		<h2 class="avz-section__title">
			<?php
			/* translators: %s: discipline name */
			echo esc_html( sprintf( __( 'Every %s service we offer', 'avenza' ), $term->name ) );
			?>
		</h2>
	</div>
	<div class="avz-cards avz-cards--3">
		<?php
		foreach ( $services as $service ) {
			echo avenza_service_card( $service ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</div>
</section>

<section class="avz-section avz-section--alt">
	<div class="avz-section__head">
		<p class="avz-eyebrow"><?php esc_html_e( 'Other disciplines', 'avenza' ); ?></p>
		<h2 class="avz-section__title"><?php esc_html_e( 'Explore the rest of our expertise', 'avenza' ); ?></h2>
	</div>
	<ul class="avz-chips">
		<?php foreach ( avenza_service_categories() as $other ) : ?>
			<?php if ( $other->term_id !== $term->term_id ) : ?>
				<li><a href="<?php echo esc_url( get_term_link( $other ) ); ?>"><?php echo esc_html( $other->name ); ?></a></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
</section>
<?php
avenza_cta_band();
get_footer();
