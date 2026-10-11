<?php
/**
 * JSON-LD graph: Organization, WebSite, WebPage, BreadcrumbList, Service,
 * FAQPage (read from the FAQ blocks on the page) and BlogPosting.
 * Every URL is absolute and every entity points at the same Organization @id,
 * so search engines and AI systems resolve one consistent brand entity.
 *
 * @package avenza
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Organization node.
 *
 * @return array
 */
function avenza_schema_organization() {
	$home = home_url( '/' );
	$org  = array(
		'@type'       => array( 'Organization', 'ProfessionalService' ),
		'@id'         => $home . '#organization',
		'name'        => 'AVENZA',
		'url'         => $home,
		'logo'        => array(
			'@type'  => 'ImageObject',
			'@id'    => $home . '#logo',
			'url'    => avenza_asset_img( 'brand/avenza-logo.png' ),
			'width'  => 600,
			'height' => 600,
		),
		'image'       => array( '@id' => $home . '#logo' ),
		'slogan'      => __( 'Be the answer, not just a result.', 'avenza' ),
		'description' => wp_strip_all_tags( avenza_opt( 'footer_about' ) ),
		'areaServed'  => array(
			array(
				'@type' => 'Country',
				'name'  => 'India',
			),
			'Worldwide',
		),
		'knowsAbout'  => array( 'Generative Engine Optimization', 'Answer Engine Optimization', 'Search Engine Optimization', 'Technical SEO', 'Local SEO', 'Content Marketing', 'Digital PR', 'Google Ads', 'Social Media Marketing', 'Web Design', 'Conversion Rate Optimization' ),
	);

	$phone = avenza_phone_raw();
	if ( $phone ) {
		$org['telephone']    = $phone;
		$org['contactPoint'] = array(
			'@type'             => 'ContactPoint',
			'telephone'         => $phone,
			'contactType'       => 'sales',
			'areaServed'        => 'IN',
			'availableLanguage' => array( 'en', 'hi' ),
		);
	}
	$email = avenza_opt( 'email' );
	if ( is_email( $email ) ) {
		$org['email'] = $email;
		if ( isset( $org['contactPoint'] ) ) {
			$org['contactPoint']['email'] = $email;
		}
	}
	$address = avenza_opt( 'address' );
	if ( $address ) {
		$org['address'] = array(
			'@type'          => 'PostalAddress',
			'streetAddress'  => preg_replace( '/\s*\n\s*/', ', ', trim( $address ) ),
			'addressCountry' => 'IN',
		);
	}
	$same = array_values( avenza_socials() );
	if ( $same ) {
		$org['sameAs'] = $same;
	}
	return $org;
}

/**
 * Plain text of the questions and answers inside FAQ details blocks.
 *
 * @param string $content Post content.
 * @return array[] question, answer
 */
function avenza_extract_faqs( $content ) {
	$faqs = array();
	if ( false === strpos( $content, '<details' ) ) {
		return $faqs;
	}
	if ( preg_match_all( '/<details[^>]*>\s*<summary[^>]*>(.*?)<\/summary>(.*?)<\/details>/is', $content, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			$question = trim( wp_strip_all_tags( $match[1] ) );
			$answer   = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( preg_replace( '/<!--.*?-->/s', '', $match[2] ) ) ) );
			if ( $question && $answer ) {
				$faqs[] = array( $question, $answer );
			}
		}
	}
	return $faqs;
}

/**
 * Build and print the graph.
 */
function avenza_schema_output() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	// Rank Math and Yoast print their own graph; keep only the FAQ and Service nodes then.
	$plugin = avenza_seo_plugin_active();
	$home   = home_url( '/' );
	$graph  = array();

	if ( ! $plugin ) {
		$graph[] = avenza_schema_organization();
		$graph[] = array(
			'@type'           => 'WebSite',
			'@id'             => $home . '#website',
			'url'             => $home,
			'name'            => get_bloginfo( 'name' ),
			'description'     => get_bloginfo( 'description' ),
			'publisher'       => array( '@id' => $home . '#organization' ),
			'inLanguage'      => get_bloginfo( 'language' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => $home . '?s={search_term_string}',
				),
				'query-input' => 'required name=search_term_string',
			),
		);
	}

	$url = avenza_current_url();

	if ( ! $plugin && $url ) {
		$crumbs = avenza_breadcrumb_items();
		$page   = array(
			'@type'      => is_page( 'about' ) ? 'AboutPage' : ( is_page( 'contact' ) ? 'ContactPage' : ( ( is_home() || is_tax( 'avenza_service_cat' ) ) ? 'CollectionPage' : 'WebPage' ) ),
			'@id'        => $url . '#webpage',
			'url'        => $url,
			'name'       => wp_get_document_title(),
			'isPartOf'   => array( '@id' => $home . '#website' ),
			'about'      => array( '@id' => $home . '#organization' ),
			'inLanguage' => get_bloginfo( 'language' ),
		);
		$desc = avenza_meta_description();
		if ( $desc ) {
			$page['description'] = $desc;
		}
		if ( is_singular() && has_post_thumbnail() ) {
			$page['primaryImageOfPage'] = array(
				'@type' => 'ImageObject',
				'url'   => get_the_post_thumbnail_url( null, 'avenza-wide' ),
			);
		}
		if ( count( $crumbs ) > 1 ) {
			$items = array();
			foreach ( $crumbs as $i => $crumb ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $i + 1,
					'name'     => $crumb['name'],
					'item'     => $crumb['url'],
				);
			}
			$graph[]            = array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $url . '#breadcrumb',
				'itemListElement' => $items,
			);
			$page['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
		}
		$graph[] = $page;
	}

	if ( is_singular( 'avenza_service' ) ) {
		$graph[] = array(
			'@type'       => 'Service',
			'@id'         => get_permalink() . '#service',
			'name'        => get_the_title(),
			'serviceType' => get_the_title(),
			'description' => avenza_meta_description(),
			'url'         => get_permalink(),
			'provider'    => $plugin ? array(
				'@type' => 'Organization',
				'name'  => 'AVENZA',
				'url'   => $home,
			) : array( '@id' => $home . '#organization' ),
			'areaServed'  => array(
				array(
					'@type' => 'Country',
					'name'  => 'India',
				),
				'Worldwide',
			),
			'image'       => has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'avenza-wide' ) : avenza_share_image()['url'],
		);
	}

	if ( ( is_page( 'services' ) || is_tax( 'avenza_service_cat' ) ) && ! $plugin ) {
		$list = array();
		foreach ( avenza_get_services( -1, array(), is_tax( 'avenza_service_cat' ) ? get_queried_object()->slug : '' ) as $i => $service ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'url'      => get_permalink( $service ),
				'name'     => get_the_title( $service ),
			);
		}
		if ( $list ) {
			$graph[] = array(
				'@type'           => 'ItemList',
				'@id'             => $url . '#services',
				'name'            => __( 'AVENZA services', 'avenza' ),
				'itemListElement' => $list,
			);
		}
	}

	if ( is_singular( 'post' ) && ! $plugin ) {
		$graph[] = array(
			'@type'            => 'BlogPosting',
			'@id'              => get_permalink() . '#article',
			'headline'         => get_the_title(),
			'description'      => avenza_meta_description(),
			'datePublished'    => get_the_date( 'c' ),
			'dateModified'     => get_the_modified_date( 'c' ),
			'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
			'author'           => array(
				'@type' => 'Organization',
				'name'  => __( 'AVENZA Strategy Team', 'avenza' ),
				'url'   => avenza_page_url( 'about' ),
			),
			'publisher'        => array( '@id' => $home . '#organization' ),
			'image'            => has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'avenza-wide' ) : avenza_share_image()['url'],
			'wordCount'        => str_word_count( wp_strip_all_tags( get_post_field( 'post_content' ) ) ),
			'inLanguage'       => get_bloginfo( 'language' ),
		);
	}

	if ( is_singular() ) {
		$faqs = avenza_extract_faqs( get_post_field( 'post_content', get_queried_object_id() ) );
		if ( count( $faqs ) >= 2 ) {
			$entities = array();
			foreach ( $faqs as $faq ) {
				$entities[] = array(
					'@type'          => 'Question',
					'name'           => $faq[0],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $faq[1],
					),
				);
			}
			$graph[] = array(
				'@type'      => 'FAQPage',
				'@id'        => ( $url ? $url : get_permalink() ) . '#faq',
				'mainEntity' => $entities,
			);
		}
	}

	if ( ! $graph ) {
		return;
	}

	echo '<script type="application/ld+json">' . wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	) . '</script>' . "\n";
}
add_action( 'wp_head', 'avenza_schema_output', 20 );
