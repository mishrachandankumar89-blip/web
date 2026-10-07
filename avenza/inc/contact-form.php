<?php
/**
 * Contact form: nonce, honeypot, time trap, rate limit, server-side validation.
 * Every enquiry is saved as a private Enquiry post and emailed, so nothing is
 * lost if email delivery fails.
 *
 * @package avenza
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Signed timestamp for the time trap.
 *
 * @param int $ts Timestamp.
 * @return string
 */
function avenza_form_sign( $ts ) {
	return hash_hmac( 'sha256', (string) $ts, wp_salt( 'nonce' ) );
}

/**
 * Field values to repopulate after an error.
 *
 * @return array
 */
function avenza_form_old_values() {
	$token = isset( $_GET['avz_form'] ) ? sanitize_key( wp_unslash( $_GET['avz_form'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $token ) {
		return array();
	}
	$data = get_transient( 'avz_form_' . $token );
	return is_array( $data ) ? $data : array();
}

/**
 * Render the form.
 *
 * @return string
 */
function avenza_contact_form_shortcode() {
	$status = isset( $_GET['avz_status'] ) ? sanitize_key( wp_unslash( $_GET['avz_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$old    = avenza_form_old_values();
	$values = isset( $old['values'] ) ? $old['values'] : array();
	$errors = isset( $old['errors'] ) ? $old['errors'] : array();
	$v      = function ( $key ) use ( $values ) {
		return isset( $values[ $key ] ) ? $values[ $key ] : '';
	};

	$ts       = time();
	$services = avenza_get_services();

	ob_start();
	?>
	<div class="avz-form-wrap" id="avz-form">
		<?php if ( 'sent' === $status ) : ?>
			<div class="avz-alert avz-alert--ok" role="status" tabindex="-1">
				<p><strong><?php esc_html_e( 'Thank you. Your message has reached us.', 'avenza' ); ?></strong> <?php esc_html_e( 'A strategist will reply within one working day. For anything urgent, call or message us on WhatsApp.', 'avenza' ); ?></p>
			</div>
		<?php elseif ( $errors ) : ?>
			<div class="avz-alert avz-alert--error" role="alert" tabindex="-1">
				<p><strong><?php esc_html_e( 'Please check the highlighted fields.', 'avenza' ); ?></strong></p>
				<ul>
					<?php foreach ( $errors as $message ) : ?>
						<li><?php echo esc_html( $message ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form class="avz-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
			<input type="hidden" name="action" value="avenza_contact">
			<input type="hidden" name="avz_ts" value="<?php echo esc_attr( $ts ); ?>">
			<input type="hidden" name="avz_sig" value="<?php echo esc_attr( avenza_form_sign( $ts ) ); ?>">
			<input type="hidden" name="avz_return" value="<?php echo esc_url( get_permalink() ? get_permalink() : home_url( '/' ) ); ?>">
			<?php wp_nonce_field( 'avenza_contact', 'avz_nonce' ); ?>

			<div class="avz-form__hp" aria-hidden="true">
				<label for="avz-company-site"><?php esc_html_e( 'Leave this field empty', 'avenza' ); ?></label>
				<input type="text" id="avz-company-site" name="avz_company_site" tabindex="-1" autocomplete="off">
			</div>

			<div class="avz-form__grid">
				<p class="avz-field<?php echo isset( $errors['name'] ) ? ' has-error' : ''; ?>">
					<label for="avz-name"><?php esc_html_e( 'Your name', 'avenza' ); ?> <span aria-hidden="true">*</span></label>
					<input type="text" id="avz-name" name="avz_name" required autocomplete="name" maxlength="80" value="<?php echo esc_attr( $v( 'name' ) ); ?>"<?php echo isset( $errors['name'] ) ? ' aria-invalid="true"' : ''; ?>>
				</p>
				<p class="avz-field<?php echo isset( $errors['email'] ) ? ' has-error' : ''; ?>">
					<label for="avz-email"><?php esc_html_e( 'Work email', 'avenza' ); ?> <span aria-hidden="true">*</span></label>
					<input type="email" id="avz-email" name="avz_email" required autocomplete="email" maxlength="120" value="<?php echo esc_attr( $v( 'email' ) ); ?>"<?php echo isset( $errors['email'] ) ? ' aria-invalid="true"' : ''; ?>>
				</p>
				<p class="avz-field<?php echo isset( $errors['phone'] ) ? ' has-error' : ''; ?>">
					<label for="avz-phone"><?php esc_html_e( 'Phone or WhatsApp', 'avenza' ); ?></label>
					<input type="tel" id="avz-phone" name="avz_phone" autocomplete="tel" maxlength="20" value="<?php echo esc_attr( $v( 'phone' ) ); ?>"<?php echo isset( $errors['phone'] ) ? ' aria-invalid="true"' : ''; ?>>
				</p>
				<p class="avz-field<?php echo isset( $errors['website'] ) ? ' has-error' : ''; ?>">
					<label for="avz-website"><?php esc_html_e( 'Website', 'avenza' ); ?></label>
					<input type="url" id="avz-website" name="avz_website" autocomplete="url" maxlength="200" placeholder="https://" value="<?php echo esc_attr( $v( 'website' ) ); ?>"<?php echo isset( $errors['website'] ) ? ' aria-invalid="true"' : ''; ?>>
				</p>
				<p class="avz-field avz-field--full">
					<label for="avz-service"><?php esc_html_e( 'What do you need help with?', 'avenza' ); ?></label>
					<select id="avz-service" name="avz_service">
						<option value=""><?php esc_html_e( 'Not sure yet, I would like advice', 'avenza' ); ?></option>
						<?php foreach ( $services as $service ) : ?>
							<option value="<?php echo esc_attr( get_the_title( $service ) ); ?>"<?php selected( $v( 'service' ), get_the_title( $service ) ); ?>><?php echo esc_html( get_the_title( $service ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<p class="avz-field avz-field--full<?php echo isset( $errors['message'] ) ? ' has-error' : ''; ?>">
					<label for="avz-message"><?php esc_html_e( 'Tell us about your business and goals', 'avenza' ); ?> <span aria-hidden="true">*</span></label>
					<textarea id="avz-message" name="avz_message" rows="6" required maxlength="3000"<?php echo isset( $errors['message'] ) ? ' aria-invalid="true"' : ''; ?>><?php echo esc_textarea( $v( 'message' ) ); ?></textarea>
				</p>
				<p class="avz-field avz-field--full avz-field--check<?php echo isset( $errors['consent'] ) ? ' has-error' : ''; ?>">
					<input type="checkbox" id="avz-consent" name="avz_consent" value="1" required<?php checked( '1', $v( 'consent' ) ); ?>>
					<label for="avz-consent">
						<?php
						printf(
							/* translators: %s: privacy policy link */
							esc_html__( 'I agree that AVENZA may use these details to reply to my enquiry, as explained in the %s.', 'avenza' ),
							'<a href="' . esc_url( avenza_page_url( 'privacy-policy' ) ) . '">' . esc_html__( 'Privacy Policy', 'avenza' ) . '</a>'
						);
						?>
					</label>
				</p>
			</div>
			<p class="avz-form__submit">
				<button type="submit" class="avz-btn avz-btn--primary"><?php esc_html_e( 'Send enquiry', 'avenza' ); ?></button>
				<span class="avz-form__note"><?php esc_html_e( 'We reply within one working day. No spam, ever.', 'avenza' ); ?></span>
			</p>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'avenza_contact_form', 'avenza_contact_form_shortcode' );

/**
 * Handle a submission.
 */
function avenza_handle_contact() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified below, after the bot checks.
	$return = isset( $_POST['avz_return'] ) ? esc_url_raw( wp_unslash( $_POST['avz_return'] ) ) : home_url( '/' );
	$return = wp_validate_redirect( $return, home_url( '/' ) );

	$redirect = function ( $args ) use ( $return ) {
		wp_safe_redirect( add_query_arg( $args, $return ) . '#avz-form' );
		exit;
	};

	// Bots: honeypot filled or the form came back faster than a person can type.
	$honeypot = isset( $_POST['avz_company_site'] ) ? trim( (string) wp_unslash( $_POST['avz_company_site'] ) ) : '';
	$ts       = isset( $_POST['avz_ts'] ) ? (int) $_POST['avz_ts'] : 0;
	$sig      = isset( $_POST['avz_sig'] ) ? sanitize_text_field( wp_unslash( $_POST['avz_sig'] ) ) : '';
	$elapsed  = time() - $ts;
	if ( '' !== $honeypot || ! hash_equals( avenza_form_sign( $ts ), $sig ) || $elapsed < 3 ) {
		$redirect( array( 'avz_status' => 'sent' ) ); // Pretend success so bots learn nothing.
	}

	$values = array(
		'name'    => isset( $_POST['avz_name'] ) ? sanitize_text_field( wp_unslash( $_POST['avz_name'] ) ) : '',
		'email'   => isset( $_POST['avz_email'] ) ? sanitize_email( wp_unslash( $_POST['avz_email'] ) ) : '',
		'phone'   => isset( $_POST['avz_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['avz_phone'] ) ) : '',
		'website' => isset( $_POST['avz_website'] ) ? esc_url_raw( wp_unslash( $_POST['avz_website'] ) ) : '',
		'service' => isset( $_POST['avz_service'] ) ? sanitize_text_field( wp_unslash( $_POST['avz_service'] ) ) : '',
		'message' => isset( $_POST['avz_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['avz_message'] ) ) : '',
		'consent' => ! empty( $_POST['avz_consent'] ) ? '1' : '',
	);
	$nonce  = isset( $_POST['avz_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['avz_nonce'] ) ) : '';
	// phpcs:enable

	$errors = array();
	if ( ! wp_verify_nonce( $nonce, 'avenza_contact' ) || $elapsed > DAY_IN_SECONDS ) {
		$errors['session'] = __( 'This page was open for a long time, so the form expired. Please press Send again.', 'avenza' );
	}
	if ( mb_strlen( $values['name'] ) < 2 ) {
		$errors['name'] = __( 'Please enter your name.', 'avenza' );
	}
	if ( ! is_email( $values['email'] ) ) {
		$errors['email'] = __( 'Please enter a valid email address.', 'avenza' );
	}
	if ( $values['phone'] && ! preg_match( '/^[0-9+()\s-]{7,20}$/', $values['phone'] ) ) {
		$errors['phone'] = __( 'Please enter a valid phone number, digits only.', 'avenza' );
	}
	if ( ! empty( $_POST['avz_website'] ) && ! $values['website'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$errors['website'] = __( 'Please enter the website address starting with https://', 'avenza' );
	}
	if ( mb_strlen( $values['message'] ) < 20 ) {
		$errors['message'] = __( 'Please tell us a little more, at least a sentence or two.', 'avenza' );
	}
	if ( ! $values['consent'] ) {
		$errors['consent'] = __( 'Please tick the consent box so we can reply to you.', 'avenza' );
	}

	// Rate limit: five enquiries an hour from one connection.
	$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rl_key = 'avz_rl_' . md5( $ip . wp_salt( 'auth' ) );
	$count  = (int) get_transient( $rl_key );
	if ( $count >= 5 ) {
		$errors['rate'] = __( 'We have received several messages from you already. Please call or WhatsApp us instead.', 'avenza' );
	}

	if ( $errors ) {
		$token = strtolower( wp_generate_password( 12, false ) );
		set_transient(
			'avz_form_' . $token,
			array(
				'values' => $values,
				'errors' => $errors,
			),
			15 * MINUTE_IN_SECONDS
		);
		$redirect( array( 'avz_form' => $token ) );
	}

	set_transient( $rl_key, $count + 1, HOUR_IN_SECONDS );

	$service = $values['service'] ? $values['service'] : __( 'General enquiry', 'avenza' );
	$lead_id = wp_insert_post(
		array(
			'post_type'    => 'avenza_lead',
			'post_status'  => 'private',
			'post_title'   => $values['name'] . ' : ' . $service,
			'post_content' => $values['message'],
		)
	);
	if ( $lead_id && ! is_wp_error( $lead_id ) ) {
		update_post_meta( $lead_id, '_avenza_email', $values['email'] );
		update_post_meta( $lead_id, '_avenza_phone', $values['phone'] );
		update_post_meta( $lead_id, '_avenza_website', $values['website'] );
		update_post_meta( $lead_id, '_avenza_service', $service );
		update_post_meta( $lead_id, '_avenza_page', $return );
		update_post_meta( $lead_id, '_avenza_consent', current_time( 'mysql' ) );
	}

	$lines = array(
		__( 'Name', 'avenza' ) . ': ' . $values['name'],
		__( 'Email', 'avenza' ) . ': ' . $values['email'],
		__( 'Phone', 'avenza' ) . ': ' . ( $values['phone'] ? $values['phone'] : '-' ),
		__( 'Website', 'avenza' ) . ': ' . ( $values['website'] ? $values['website'] : '-' ),
		__( 'Interested in', 'avenza' ) . ': ' . $service,
		__( 'Sent from', 'avenza' ) . ': ' . $return,
		'',
		$values['message'],
	);
	wp_mail(
		avenza_form_recipient(),
		/* translators: 1: name, 2: service */
		sprintf( __( 'New enquiry from %1$s: %2$s', 'avenza' ), $values['name'], $service ),
		implode( "\n", $lines ),
		array( 'Reply-To: ' . $values['name'] . ' <' . $values['email'] . '>' )
	);

	$redirect( array( 'avz_status' => 'sent' ) );
}
add_action( 'admin_post_nopriv_avenza_contact', 'avenza_handle_contact' );
add_action( 'admin_post_avenza_contact', 'avenza_handle_contact' );
