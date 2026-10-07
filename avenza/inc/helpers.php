<?php
/**
 * Small helpers shared by templates and modules.
 *
 * @package avenza
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default values for every Customizer setting.
 *
 * @return array
 */
function avenza_option_defaults() {
	return array(
		'phone'          => '+91 96809 51099',
		'whatsapp'       => '919680951099',
		'email'          => '',
		'address'        => '',
		'hours'          => '',
		'map_embed'      => '',
		'cta_text'       => __( 'Book a Strategy Call', 'avenza' ),
		'cta_url'        => '',
		'footer_about'   => __( 'AVENZA helps businesses get found, trusted and chosen in Google and in AI answers from ChatGPT, Gemini, Perplexity and Copilot.', 'avenza' ),
		'ga4_id'         => '',
		'gsc_verify'     => '',
		'llms_txt'       => '',
		'form_recipient' => '',
		'linkedin'       => '',
		'instagram'      => '',
		'facebook'       => '',
		'x'              => '',
		'youtube'        => '',
		'whatsapp_float' => true,
		'call_float'     => true,
	);
}

/**
 * Read a theme option with its default.
 *
 * @param string $key Option key without prefix.
 * @return mixed
 */
function avenza_opt( $key ) {
	$defaults = avenza_option_defaults();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	return get_theme_mod( 'avenza_' . $key, $default );
}

/**
 * Phone number formatted for tel: links.
 *
 * @return string
 */
function avenza_phone_raw() {
	return preg_replace( '/[^0-9+]/', '', (string) avenza_opt( 'phone' ) );
}

/**
 * WhatsApp chat link.
 *
 * @param string $text Optional prefilled message.
 * @return string
 */
function avenza_whatsapp_url( $text = '' ) {
	$number = preg_replace( '/[^0-9]/', '', (string) avenza_opt( 'whatsapp' ) );
	if ( ! $number ) {
		return '';
	}
	$url = 'https://wa.me/' . $number;
	if ( $text ) {
		$url .= '?text=' . rawurlencode( $text );
	}
	return $url;
}

/**
 * Email that should receive form leads.
 *
 * @return string
 */
function avenza_form_recipient() {
	$to = avenza_opt( 'form_recipient' );
	if ( ! is_email( $to ) ) {
		$to = avenza_opt( 'email' );
	}
	if ( ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}
	return $to;
}

/**
 * Permalink of a page by slug path, falling back to a home-relative URL.
 *
 * @param string $path Page path, for example "contact".
 * @return string
 */
function avenza_page_url( $path ) {
	$page = get_page_by_path( $path );
	if ( $page ) {
		return get_permalink( $page );
	}
	return home_url( '/' . trim( $path, '/' ) . '/' );
}

/**
 * Main call to action URL.
 *
 * @return string
 */
function avenza_cta_url() {
	$url = avenza_opt( 'cta_url' );
	return $url ? $url : avenza_page_url( 'contact' );
}

/**
 * Social profile URLs that have been filled in.
 *
 * @return array network => url
 */
function avenza_socials() {
	$out = array();
	foreach ( array( 'linkedin', 'instagram', 'facebook', 'x', 'youtube' ) as $network ) {
		$url = avenza_opt( $network );
		if ( $url ) {
			$out[ $network ] = esc_url_raw( $url );
		}
	}
	return $out;
}

/**
 * Integer to Roman numeral, used for process steps and plates.
 *
 * @param int $number Number.
 * @return string
 */
function avenza_roman( $number ) {
	$map = array(
		'M'  => 1000,
		'CM' => 900,
		'D'  => 500,
		'CD' => 400,
		'C'  => 100,
		'XC' => 90,
		'L'  => 50,
		'XL' => 40,
		'X'  => 10,
		'IX' => 9,
		'V'  => 5,
		'IV' => 4,
		'I'  => 1,
	);
	$out = '';
	foreach ( $map as $roman => $value ) {
		while ( $number >= $value ) {
			$out    .= $roman;
			$number -= $value;
		}
	}
	return $out;
}

/**
 * Approximate reading time for a post.
 *
 * @param int|WP_Post|null $post Post.
 * @return int Minutes.
 */
function avenza_reading_time( $post = null ) {
	$post  = get_post( $post );
	$words = $post ? str_word_count( wp_strip_all_tags( $post->post_content ) ) : 0;
	return max( 1, (int) ceil( $words / 220 ) );
}

/**
 * Inline SVG icons. Stroke icons drawn on a 24 unit grid.
 *
 * @param string $name  Icon name.
 * @param int    $size  Pixel size.
 * @return string
 */
function avenza_icon( $name, $size = 20 ) {
	$paths = array(
		'phone'    => '<path d="M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="1"/><path d="m3 7 9 6 9-6"/>',
		'pin'      => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'arrow-up' => '<path d="M12 19V5M6 11l6-6 6 6"/>',
		'chevron'  => '<path d="m6 9 6 6 6-6"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'check'    => '<path d="m5 12 5 5L20 7"/>',
		'linkedin' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 10v7"/>',
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5v.01"/>',
		'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/>',
		'x'        => '<path d="M4 4l16 16M20 4 4 20"/>',
		'youtube'  => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3z"/>',
	);
	if ( 'whatsapp' === $name ) {
		return '<svg class="avz-icon" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .1-1.2c0-.1-.2-.2-.4-.3z"/></svg>';
	}
	$path = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['check'];
	return '<svg class="avz-icon" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

/**
 * The AVENZA "A" mark as inline SVG, matching the brand logo.
 *
 * @param string $uid Unique suffix for gradient ids.
 * @return string
 */
function avenza_mark_svg( $uid = 'h' ) {
	$uid = sanitize_key( $uid );
	return '<svg class="avz-mark" viewBox="0 0 200 170" width="44" height="37" aria-hidden="true" focusable="false">'
		. '<defs>'
		. '<linearGradient id="avzA' . $uid . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFA412"/><stop offset="1" stop-color="#F26A2E"/></linearGradient>'
		. '<linearGradient id="avzL' . $uid . '" x1="100" y1="22" x2="30" y2="150" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#FFA412"/><stop offset=".45" stop-color="#F0703A"/><stop offset=".72" stop-color="#A8297F"/><stop offset="1" stop-color="#F0602F"/></linearGradient>'
		. '<linearGradient id="avzP' . $uid . '" x1="74" y1="140" x2="128" y2="112" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#6A1FC9"/><stop offset="1" stop-color="#DE4B5A"/></linearGradient>'
		. '</defs>'
		. '<path d="M100 24 L168 140" stroke="url(#avzA' . $uid . ')" stroke-width="30" stroke-linecap="round" fill="none"/>'
		. '<path d="M100 24 L32 140" stroke="url(#avzL' . $uid . ')" stroke-width="30" stroke-linecap="round" fill="none"/>'
		. '<path d="M80 139 L122 115" stroke="url(#avzP' . $uid . ')" stroke-width="27" stroke-linecap="round" fill="none"/>'
		. '</svg>';
}

/**
 * Site logo: the Customizer logo if one is set, otherwise the built-in mark and wordmark.
 *
 * @param string $context header|footer|drawer.
 */
function avenza_logo( $context = 'header' ) {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	$label = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'AVENZA';
	printf(
		'<a class="avz-brand" href="%1$s" rel="home" aria-label="%2$s">%3$s<span class="avz-brand__word" aria-hidden="true">AVENZA</span></a>',
		esc_url( home_url( '/' ) ),
		/* translators: %s: site name */
		esc_attr( sprintf( __( '%s home', 'avenza' ), $label ) ),
		avenza_mark_svg( $context ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * Whether the current singular post carries its own hero block.
 *
 * @param int|null $post_id Post ID.
 * @return bool
 */
function avenza_has_hero( $post_id = null ) {
	$post = get_post( $post_id );
	return $post && false !== strpos( $post->post_content, 'avz-hero' );
}

/**
 * URL of a bundled theme image.
 *
 * @param string $file File name inside assets/img.
 * @return string
 */
function avenza_asset_img( $file ) {
	return AVENZA_URI . '/assets/img/' . ltrim( $file, '/' );
}
