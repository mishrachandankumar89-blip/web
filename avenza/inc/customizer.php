<?php
/**
 * Customizer panel: business details, calls to action, tracking, AI visibility.
 *
 * @package avenza
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the AVENZA panel.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function avenza_customize_register( $wp_customize ) {
	$wp_customize->add_panel(
		'avenza',
		array(
			'title'    => __( 'AVENZA Settings', 'avenza' ),
			'priority' => 20,
		)
	);

	$sections = array(
		'contact'  => __( 'Contact Details', 'avenza' ),
		'cta'      => __( 'Calls to Action', 'avenza' ),
		'social'   => __( 'Social Profiles', 'avenza' ),
		'tracking' => __( 'Analytics and Verification', 'avenza' ),
		'ai'       => __( 'AI Visibility (llms.txt)', 'avenza' ),
	);
	$priority = 10;
	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section(
			'avenza_' . $id,
			array(
				'title'    => $title,
				'panel'    => 'avenza',
				'priority' => $priority,
			)
		);
		$priority += 10;
	}

	// key => array( section, label, type, description ).
	$fields = array(
		'phone'          => array( 'contact', __( 'Phone number', 'avenza' ), 'text', __( 'Shown in the header, footer and contact page, and used for click-to-call.', 'avenza' ) ),
		'whatsapp'       => array( 'contact', __( 'WhatsApp number', 'avenza' ), 'text', __( 'Country code and number, digits only. Example: 919680951099', 'avenza' ) ),
		'email'          => array( 'contact', __( 'Public email address', 'avenza' ), 'email', __( 'Leave empty to hide email on the site.', 'avenza' ) ),
		'form_recipient' => array( 'contact', __( 'Send form enquiries to', 'avenza' ), 'email', __( 'Defaults to the public email, then the site admin email.', 'avenza' ) ),
		'address'        => array( 'contact', __( 'Office address', 'avenza' ), 'textarea', __( 'Leave empty if you do not publish an address. Adding one also adds it to the schema.', 'avenza' ) ),
		'hours'          => array( 'contact', __( 'Working hours', 'avenza' ), 'text', __( 'Example: Monday to Saturday, 10:00 to 19:00 IST', 'avenza' ) ),
		'map_embed'      => array( 'contact', __( 'Google Maps embed URL', 'avenza' ), 'url', __( 'The src value from Google Maps, Share, Embed a map.', 'avenza' ) ),
		'cta_text'       => array( 'cta', __( 'Header button text', 'avenza' ), 'text', '' ),
		'cta_url'        => array( 'cta', __( 'Header button link', 'avenza' ), 'url', __( 'Defaults to the Contact page.', 'avenza' ) ),
		'footer_about'   => array( 'cta', __( 'Footer description', 'avenza' ), 'textarea', '' ),
		'whatsapp_float' => array( 'cta', __( 'Show floating WhatsApp button', 'avenza' ), 'checkbox', '' ),
		'call_float'     => array( 'cta', __( 'Show floating call button on phones', 'avenza' ), 'checkbox', '' ),
		'linkedin'       => array( 'social', 'LinkedIn', 'url', '' ),
		'instagram'      => array( 'social', 'Instagram', 'url', '' ),
		'facebook'       => array( 'social', 'Facebook', 'url', '' ),
		'x'              => array( 'social', 'X (Twitter)', 'url', '' ),
		'youtube'        => array( 'social', 'YouTube', 'url', '' ),
		'ga4_id'         => array( 'tracking', __( 'Google Analytics 4 measurement ID', 'avenza' ), 'text', __( 'Format G-XXXXXXXXXX. Leave empty if you use a plugin for analytics.', 'avenza' ) ),
		'gsc_verify'     => array( 'tracking', __( 'Google Search Console verification code', 'avenza' ), 'text', __( 'Only the content value of the meta tag.', 'avenza' ) ),
		'llms_txt'       => array( 'ai', __( 'Custom llms.txt', 'avenza' ), 'textarea', __( 'Leave empty to generate llms.txt automatically from your services, pages and articles.', 'avenza' ) ),
	);

	$defaults = avenza_option_defaults();
	foreach ( $fields as $key => $field ) {
		list( $section, $label, $type, $desc ) = $field;

		switch ( $type ) {
			case 'email':
				$sanitize = 'sanitize_email';
				break;
			case 'url':
				$sanitize = 'esc_url_raw';
				break;
			case 'textarea':
				$sanitize = 'sanitize_textarea_field';
				break;
			case 'checkbox':
				$sanitize = 'avenza_sanitize_checkbox';
				break;
			default:
				$sanitize = 'sanitize_text_field';
		}

		$wp_customize->add_setting(
			'avenza_' . $key,
			array(
				'default'           => isset( $defaults[ $key ] ) ? $defaults[ $key ] : '',
				'sanitize_callback' => $sanitize,
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'avenza_' . $key,
			array(
				'label'       => $label,
				'description' => $desc,
				'section'     => 'avenza_' . $section,
				'type'        => $type,
			)
		);
	}
}
add_action( 'customize_register', 'avenza_customize_register' );

/**
 * Checkbox sanitizer.
 *
 * @param mixed $value Value.
 * @return bool
 */
function avenza_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * Drop the cached llms.txt whenever settings change.
 */
function avenza_customize_saved() {
	delete_transient( 'avenza_llms_txt' );
}
add_action( 'customize_save_after', 'avenza_customize_saved' );
