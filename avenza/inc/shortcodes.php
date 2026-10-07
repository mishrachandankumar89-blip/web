<?php
/**
 * Shortcodes for live content inside editable pages.
 *
 * [avenza_services limit="12" columns="3" exclude_current="1"]
 * [avenza_posts limit="3"]
 * [avenza_contact_details]
 * [avenza_map]
 * [avenza_contact_form] lives in contact-form.php.
 *
 * @package avenza
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service card.
 *
 * @param WP_Post $service Service.
 * @param string  $heading Heading tag.
 * @return string
 */
function avenza_service_card( $service, $heading = 'h3' ) {
	$url    = get_permalink( $service );
	$number = avenza_service_number( $service );
	$media  = '';
	if ( has_post_thumbnail( $service ) ) {
		$media = '<div class="avz-card__media">' . get_the_post_thumbnail(
			$service,
			'avenza-card',
			array(
				'loading' => 'lazy',
				'sizes'   => '(min-width: 1100px) 380px, (min-width: 700px) 45vw, 92vw',
			)
		) . '</div>';
	}
	$excerpt = has_excerpt( $service ) ? get_the_excerpt( $service ) : wp_trim_words( wp_strip_all_tags( $service->post_content ), 24 );

	return sprintf(
		'<article class="avz-card avz-card--service">%1$s<div class="avz-card__body"><p class="avz-card__num">N&deg; %2$s</p><%5$s class="avz-card__title"><a class="avz-card__link" href="%3$s">%4$s</a></%5$s><p class="avz-card__text">%6$s</p><span class="avz-card__more" aria-hidden="true">%7$s %8$s</span></div></article>',
		$media,
		esc_html( $number ),
		esc_url( $url ),
		esc_html( get_the_title( $service ) ),
		tag_escape( $heading ),
		esc_html( $excerpt ),
		esc_html__( 'Explore the service', 'avenza' ),
		avenza_icon( 'arrow', 16 )
	);
}

/**
 * Services grid.
 *
 * @param array $atts Attributes.
 * @return string
 */
function avenza_services_shortcode( $atts ) {
	$atts    = shortcode_atts(
		array(
			'limit'           => -1,
			'columns'         => 3,
			'exclude_current' => 0,
			'heading'         => 'h3',
		),
		$atts,
		'avenza_services'
	);
	$exclude = array();
	if ( $atts['exclude_current'] && is_singular( 'avenza_service' ) ) {
		$exclude[] = get_the_ID();
	}
	$services = avenza_get_services( (int) $atts['limit'], $exclude );
	if ( ! $services ) {
		return '';
	}
	$cols = max( 2, min( 4, (int) $atts['columns'] ) );
	$html = '<div class="avz-cards avz-cards--' . $cols . '">';
	foreach ( $services as $service ) {
		$html .= avenza_service_card( $service, in_array( $atts['heading'], array( 'h2', 'h3', 'h4' ), true ) ? $atts['heading'] : 'h3' );
	}
	return $html . '</div>';
}
add_shortcode( 'avenza_services', 'avenza_services_shortcode' );

/**
 * Article card.
 *
 * @param WP_Post $post    Post.
 * @param string  $heading Heading tag.
 * @return string
 */
function avenza_post_card( $post, $heading = 'h3' ) {
	$media = '';
	if ( has_post_thumbnail( $post ) ) {
		$media = '<div class="avz-card__media avz-card__media--wide">' . get_the_post_thumbnail(
			$post,
			'avenza-wide',
			array(
				'loading' => 'lazy',
				'sizes'   => '(min-width: 1100px) 380px, (min-width: 700px) 45vw, 92vw',
			)
		) . '</div>';
	}
	$cats  = get_the_category( $post->ID );
	$label = $cats ? $cats[0]->name : __( 'Insight', 'avenza' );

	return sprintf(
		'<article class="avz-card avz-card--post">%1$s<div class="avz-card__body"><p class="avz-card__num">%2$s <span aria-hidden="true">&middot;</span> %3$s</p><%7$s class="avz-card__title"><a class="avz-card__link" href="%4$s">%5$s</a></%7$s><p class="avz-card__text">%6$s</p><span class="avz-card__more" aria-hidden="true">%8$s %9$s</span></div></article>',
		$media,
		esc_html( $label ),
		/* translators: %d: minutes */
		esc_html( sprintf( _n( '%d min read', '%d min read', avenza_reading_time( $post ), 'avenza' ), avenza_reading_time( $post ) ) ),
		esc_url( get_permalink( $post ) ),
		esc_html( get_the_title( $post ) ),
		esc_html( get_the_excerpt( $post ) ),
		tag_escape( $heading ),
		esc_html__( 'Read the article', 'avenza' ),
		avenza_icon( 'arrow', 16 )
	);
}

/**
 * Latest articles grid.
 *
 * @param array $atts Attributes.
 * @return string
 */
function avenza_posts_shortcode( $atts ) {
	$atts  = shortcode_atts(
		array(
			'limit'    => 3,
			'category' => '',
		),
		$atts,
		'avenza_posts'
	);
	$args  = array(
		'post_type'           => 'post',
		'posts_per_page'      => max( 1, (int) $atts['limit'] ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $atts['category'] ) {
		$args['category_name'] = sanitize_title( $atts['category'] );
	}
	if ( is_singular( 'post' ) ) {
		$args['post__not_in'] = array( get_the_ID() );
	}
	$posts = get_posts( $args );
	if ( ! $posts ) {
		return '';
	}
	$html = '<div class="avz-cards avz-cards--3">';
	foreach ( $posts as $post ) {
		$html .= avenza_post_card( $post );
	}
	return $html . '</div>';
}
add_shortcode( 'avenza_posts', 'avenza_posts_shortcode' );

/**
 * Contact details list: phone, WhatsApp, email, hours, address. Only what is filled in.
 *
 * @return string
 */
function avenza_contact_details_shortcode() {
	$rows  = array();
	$phone = avenza_opt( 'phone' );
	if ( $phone ) {
		$rows[] = array( 'phone', __( 'Call', 'avenza' ), '<a href="tel:' . esc_attr( avenza_phone_raw() ) . '">' . esc_html( $phone ) . '</a>' );
	}
	$wa = avenza_whatsapp_url( __( 'Hello AVENZA, I would like to discuss my website.', 'avenza' ) );
	if ( $wa ) {
		$rows[] = array( 'whatsapp', __( 'WhatsApp', 'avenza' ), '<a href="' . esc_url( $wa ) . '" target="_blank" rel="noopener">' . esc_html__( 'Start a chat', 'avenza' ) . '</a>' );
	}
	$email = avenza_opt( 'email' );
	if ( is_email( $email ) ) {
		$rows[] = array( 'mail', __( 'Email', 'avenza' ), '<a href="mailto:' . esc_attr( antispambot( $email ) ) . '">' . esc_html( antispambot( $email ) ) . '</a>' );
	}
	$hours = avenza_opt( 'hours' );
	if ( $hours ) {
		$rows[] = array( 'clock', __( 'Hours', 'avenza' ), esc_html( $hours ) );
	}
	$address = avenza_opt( 'address' );
	if ( $address ) {
		$rows[] = array( 'pin', __( 'Office', 'avenza' ), nl2br( esc_html( $address ) ) );
	}

	$html = '<ul class="avz-contact-list">';
	foreach ( $rows as $row ) {
		$html .= '<li><span class="avz-contact-list__icon">' . avenza_icon( $row[0], 20 ) . '</span><span class="avz-contact-list__label">' . esc_html( $row[1] ) . '</span><span class="avz-contact-list__value">' . $row[2] . '</span></li>';
	}
	return $html . '</ul>';
}
add_shortcode( 'avenza_contact_details', 'avenza_contact_details_shortcode' );

/**
 * Map embed, only when a map URL is set.
 *
 * @return string
 */
function avenza_map_shortcode() {
	$src = avenza_opt( 'map_embed' );
	if ( ! $src || 0 !== strpos( $src, 'https://www.google.com/maps/embed' ) ) {
		return '';
	}
	return '<div class="avz-map"><iframe src="' . esc_url( $src ) . '" title="' . esc_attr__( 'AVENZA on Google Maps', 'avenza' ) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>';
}
add_shortcode( 'avenza_map', 'avenza_map_shortcode' );
