<?php
/**
 * Site footer.
 *
 * @package avenza
 */

$services = avenza_service_categories();
if ( ! $services ) {
	$services = avenza_get_services( 12 );
}
$socials  = avenza_socials();
?>
</main>

<footer class="avz-footer">
	<div class="avz-container">
		<div class="avz-footer__grid">
			<div class="avz-footer__brand">
				<?php avenza_logo( 'footer' ); ?>
				<p class="avz-footer__about"><?php echo esc_html( avenza_opt( 'footer_about' ) ); ?></p>
				<?php echo do_shortcode( '[avenza_contact_details]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $socials ) : ?>
					<ul class="avz-social">
						<?php foreach ( $socials as $network => $url ) : ?>
							<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener me"><?php echo avenza_icon( $network, 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span class="screen-reader-text"><?php echo esc_html( ucfirst( $network ) ); ?></span></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<?php if ( $services ) : ?>
				<div class="avz-footer__col avz-footer__col--services">
					<h2 class="avz-footer__title"><?php esc_html_e( 'Services', 'avenza' ); ?></h2>
					<ul class="avz-footer__list avz-footer__list--two">
						<?php foreach ( $services as $service ) : ?>
							<?php if ( $service instanceof WP_Term ) : ?>
								<li><a href="<?php echo esc_url( get_term_link( $service ) ); ?>"><?php echo esc_html( $service->name ); ?></a></li>
							<?php else : ?>
								<li><a href="<?php echo esc_url( get_permalink( $service ) ); ?>"><?php echo esc_html( get_the_title( $service ) ); ?></a></li>
							<?php endif; ?>
						<?php endforeach; ?>
						<li><a href="<?php echo esc_url( avenza_page_url( 'services' ) ); ?>"><?php esc_html_e( 'All services', 'avenza' ); ?></a></li>
					</ul>
				</div>
			<?php endif; ?>

			<div class="avz-footer__col">
				<h2 class="avz-footer__title"><?php esc_html_e( 'Company', 'avenza' ); ?></h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'avz-footer__list',
						'depth'          => 1,
						'fallback_cb'    => 'avenza_footer_fallback',
					)
				);
				?>
			</div>
		</div>

		<div class="avz-footer__bar">
			<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> AVENZA. <?php esc_html_e( 'All rights reserved.', 'avenza' ); ?></p>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'legal',
					'container'      => false,
					'menu_class'     => 'avz-footer__legal',
					'depth'          => 1,
					'fallback_cb'    => 'avenza_legal_fallback',
				)
			);
			?>
		</div>
	</div>
</footer>

<div class="avz-floats">
	<?php if ( avenza_opt( 'call_float' ) && avenza_opt( 'phone' ) ) : ?>
		<a class="avz-float avz-float--call" href="tel:<?php echo esc_attr( avenza_phone_raw() ); ?>">
			<?php echo avenza_icon( 'phone', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Call AVENZA', 'avenza' ); ?></span>
		</a>
	<?php endif; ?>
	<?php $wa = avenza_whatsapp_url( __( 'Hello AVENZA, I would like to discuss my website.', 'avenza' ) ); ?>
	<?php if ( avenza_opt( 'whatsapp_float' ) && $wa ) : ?>
		<a class="avz-float avz-float--wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">
			<?php echo avenza_icon( 'whatsapp', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Chat with AVENZA on WhatsApp', 'avenza' ); ?></span>
		</a>
	<?php endif; ?>
	<button class="avz-float avz-float--top" type="button" hidden>
		<?php echo avenza_icon( 'arrow-up', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Back to top', 'avenza' ); ?></span>
	</button>
</div>

<?php wp_footer(); ?>
</body>
</html>
