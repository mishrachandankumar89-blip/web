<?php
/**
 * GEO and AEO layer: llms.txt at the site root and robots.txt rules that
 * welcome search and AI crawlers.
 *
 * @package avenza
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build llms.txt from live content, or return the Customizer override.
 *
 * @return string
 */
function avenza_llms_txt() {
	$custom = trim( (string) avenza_opt( 'llms_txt' ) );
	if ( $custom ) {
		return $custom . "\n";
	}

	$cached = get_transient( 'avenza_llms_txt' );
	if ( $cached ) {
		return $cached;
	}

	$name  = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'AVENZA';
	$lines = array();

	$lines[] = '# ' . $name;
	$lines[] = '';
	$lines[] = '> ' . wp_strip_all_tags( avenza_opt( 'footer_about' ) );
	$lines[] = '';
	$lines[] = $name . ' is a digital marketing agency. It plans and runs Generative Engine Optimization (GEO), Answer Engine Optimization (AEO), SEO, content, digital PR, Google Ads, social media, website design and conversion work for businesses in India and other countries.';
	$lines[] = '';

	$services = avenza_get_services();
	if ( $services ) {
		$lines[] = '## Services';
		$lines[] = '';
		foreach ( $services as $service ) {
			$excerpt = has_excerpt( $service ) ? get_the_excerpt( $service ) : '';
			$lines[] = '- [' . get_the_title( $service ) . '](' . get_permalink( $service ) . ')' . ( $excerpt ? ': ' . wp_strip_all_tags( $excerpt ) : '' );
		}
		$lines[] = '';
	}

	$key_pages = array( 'about', 'approach', 'services', 'faq', 'contact' );
	$page_rows = array();
	foreach ( $key_pages as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			$desc        = get_post_meta( $page->ID, '_avenza_meta_desc', true );
			$page_rows[] = '- [' . get_the_title( $page ) . '](' . get_permalink( $page ) . ')' . ( $desc ? ': ' . $desc : '' );
		}
	}
	if ( $page_rows ) {
		$lines[] = '## Key pages';
		$lines[] = '';
		$lines   = array_merge( $lines, $page_rows );
		$lines[] = '';
	}

	$posts = get_posts(
		array(
			'posts_per_page' => 20,
			'no_found_rows'  => true,
		)
	);
	if ( $posts ) {
		$lines[] = '## Insights';
		$lines[] = '';
		foreach ( $posts as $post ) {
			$lines[] = '- [' . get_the_title( $post ) . '](' . get_permalink( $post ) . '): ' . wp_strip_all_tags( get_the_excerpt( $post ) );
		}
		$lines[] = '';
	}

	$lines[] = '## Contact';
	$lines[] = '';
	$phone   = avenza_opt( 'phone' );
	if ( $phone ) {
		$lines[] = '- Phone and WhatsApp: ' . $phone;
	}
	$email = avenza_opt( 'email' );
	if ( is_email( $email ) ) {
		$lines[] = '- Email: ' . $email;
	}
	$lines[] = '- Enquiry form: ' . avenza_page_url( 'contact' );
	$lines[] = '- Website: ' . home_url( '/' );
	$lines[] = '';

	$text = implode( "\n", $lines );
	set_transient( 'avenza_llms_txt', $text, DAY_IN_SECONDS );
	return $text;
}

/**
 * Serve /llms.txt.
 */
function avenza_serve_llms_txt() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	$home = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$home = $home ? rtrim( $home, '/' ) : '';
	if ( $path !== $home . '/llms.txt' ) {
		return;
	}
	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo avenza_llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain text.
	exit;
}
add_action( 'init', 'avenza_serve_llms_txt', 1 );

/**
 * Clear the cached llms.txt when content changes.
 *
 * @param int $post_id Post ID.
 */
function avenza_flush_llms_cache( $post_id ) {
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	delete_transient( 'avenza_llms_txt' );
}
add_action( 'save_post', 'avenza_flush_llms_cache' );

/**
 * robots.txt: allow search and AI crawlers explicitly and point to the sitemap.
 *
 * @param string $output Existing output.
 * @param bool   $public Whether the site is public.
 * @return string
 */
function avenza_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}
	$bots = array( 'Googlebot', 'Bingbot', 'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot', 'Applebot-Extended', 'CCBot', 'DuckAssistBot', 'meta-externalagent' );

	$lines   = array();
	$lines[] = '# Search engines and AI assistants are welcome to read and cite this site.';
	foreach ( $bots as $bot ) {
		$lines[] = 'User-agent: ' . $bot;
	}
	$lines[] = 'Allow: /';
	$lines[] = 'Disallow: /wp-admin/';
	$lines[] = 'Allow: /wp-admin/admin-ajax.php';
	$lines[] = '';

	$output = implode( "\n", $lines ) . "\n" . $output;

	if ( false === stripos( $output, 'Sitemap:' ) ) {
		$output .= "\nSitemap: " . home_url( '/wp-sitemap.xml' ) . "\n";
	}
	$output .= '# AI summary of this site: ' . home_url( '/llms.txt' ) . "\n";
	return $output;
}
add_filter( 'robots_txt', 'avenza_robots_txt', 20, 2 );

/**
 * Keep enquiries out of the core sitemap and drop the users sitemap.
 *
 * @param array $post_types Post types.
 * @return array
 */
function avenza_sitemap_post_types( $post_types ) {
	unset( $post_types['avenza_lead'] );
	return $post_types;
}
add_filter( 'wp_sitemaps_post_types', 'avenza_sitemap_post_types' );

/**
 * Remove the users provider from the core sitemap.
 *
 * @param WP_Sitemaps_Provider $provider Provider.
 * @param string               $name     Name.
 * @return WP_Sitemaps_Provider|false
 */
function avenza_sitemap_providers( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'avenza_sitemap_providers', 10, 2 );
