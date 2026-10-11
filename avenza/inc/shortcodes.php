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
			'category'        => '',
		),
		$atts,
		'avenza_services'
	);
	$exclude = array();
	if ( $atts['exclude_current'] && is_singular( 'avenza_service' ) ) {
		$exclude[] = get_the_ID();
	}
	$category = sanitize_title( $atts['category'] );
	if ( 'current' === $category ) {
		$term     = avenza_service_category();
		$category = $term ? $term->slug : '';
	}
	$services = avenza_get_services( (int) $atts['limit'], $exclude, $category );
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

/**
 * Category card with its plate, service count and short description.
 *
 * @param WP_Term $term    Term.
 * @param int     $index   Position, for the N° label.
 * @return string
 */
function avenza_category_card( $term, $index ) {
	$img   = avenza_category_image_id( $term );
	$media = $img ? '<div class="avz-card__media">' . wp_get_attachment_image( $img, 'avenza-card', false, array( 'loading' => 'lazy', 'sizes' => '(min-width: 1100px) 380px, (min-width: 700px) 45vw, 92vw' ) ) . '</div>' : '';
	$short = get_term_meta( $term->term_id, '_avenza_short', true );
	return sprintf(
		'<article class="avz-card avz-card--service avz-card--category">%1$s<div class="avz-card__body"><p class="avz-card__num">N&deg; %2$s <span aria-hidden="true">&middot;</span> %3$s</p><h3 class="avz-card__title"><a class="avz-card__link" href="%4$s">%5$s</a></h3><p class="avz-card__text">%6$s</p><span class="avz-card__more" aria-hidden="true">%7$s %8$s</span></div></article>',
		$media,
		esc_html( str_pad( (string) $index, 2, '0', STR_PAD_LEFT ) ),
		/* translators: %d: number of services */
		esc_html( sprintf( _n( '%d service', '%d services', (int) $term->count, 'avenza' ), (int) $term->count ) ),
		esc_url( get_term_link( $term ) ),
		esc_html( $term->name ),
		esc_html( $short ? $short : wp_trim_words( $term->description, 22 ) ),
		esc_html__( 'Explore the discipline', 'avenza' ),
		avenza_icon( 'arrow', 16 )
	);
}

/**
 * Grid of service categories.
 *
 * @return string
 */
function avenza_service_categories_shortcode() {
	$terms = avenza_service_categories();
	if ( ! $terms ) {
		return '';
	}
	$html = '<div class="avz-cards avz-cards--3">';
	foreach ( $terms as $i => $term ) {
		$html .= avenza_category_card( $term, $i + 1 );
	}
	return $html . '</div>';
}
add_shortcode( 'avenza_service_categories', 'avenza_service_categories_shortcode' );

/**
 * Full service index grouped by category, like an agency's services directory.
 *
 * @return string
 */
function avenza_service_index_shortcode() {
	$terms = avenza_service_categories();
	if ( ! $terms ) {
		return avenza_services_shortcode( array() );
	}
	$html = '<div class="avz-index">';
	foreach ( $terms as $i => $term ) {
		$services = avenza_get_services( -1, array(), $term->slug );
		if ( ! $services ) {
			continue;
		}
		$html .= '<section class="avz-index__group" aria-labelledby="avz-idx-' . esc_attr( $term->slug ) . '">';
		$html .= '<div class="avz-index__head"><p class="avz-num">N&deg; ' . esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ) . '</p>';
		$html .= '<h3 class="avz-index__title" id="avz-idx-' . esc_attr( $term->slug ) . '"><a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a></h3>';
		$short = get_term_meta( $term->term_id, '_avenza_short', true );
		if ( $short ) {
			$html .= '<p class="avz-index__desc">' . esc_html( $short ) . '</p>';
		}
		$html .= '</div><ul class="avz-index__list">';
		foreach ( $services as $service ) {
			$html .= '<li><a href="' . esc_url( get_permalink( $service ) ) . '">' . esc_html( get_the_title( $service ) ) . '</a></li>';
		}
		$html .= '</ul></section>';
	}
	return $html . '</div>';
}
add_shortcode( 'avenza_service_index', 'avenza_service_index_shortcode' );
