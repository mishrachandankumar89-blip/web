<?php
/**
 * Content types: Services (public pages) and Leads (private form submissions).
 *
 * @package avenza
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register post types.
 */
function avenza_register_types() {
	register_post_type(
		'avenza_service',
		array(
			'labels'        => array(
				'name'          => __( 'Services', 'avenza' ),
				'singular_name' => __( 'Service', 'avenza' ),
				'add_new_item'  => __( 'Add New Service', 'avenza' ),
				'edit_item'     => __( 'Edit Service', 'avenza' ),
				'all_items'     => __( 'All Services', 'avenza' ),
			),
			'public'        => true,
			'has_archive'   => false,
			'menu_icon'     => 'dashicons-awards',
			'menu_position' => 21,
			'rewrite'       => array(
				'slug'       => 'services',
				'with_front' => false,
			),
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields', 'revisions' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'avenza_lead',
		array(
			'labels'          => array(
				'name'          => __( 'Enquiries', 'avenza' ),
				'singular_name' => __( 'Enquiry', 'avenza' ),
				'edit_item'     => __( 'View Enquiry', 'avenza' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 25,
			'supports'        => array( 'title', 'editor', 'custom-fields' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);

	register_taxonomy(
		'avenza_service_cat',
		'avenza_service',
		array(
			'labels'            => array(
				'name'          => __( 'Service Categories', 'avenza' ),
				'singular_name' => __( 'Service Category', 'avenza' ),
				'menu_name'     => __( 'Categories', 'avenza' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'expertise',
				'with_front' => false,
			),
		)
	);

	register_post_meta(
		'avenza_service',
		'_avenza_number',
		array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'avenza_register_types' );

/**
 * Plate number for a service, for example "01".
 *
 * @param int|WP_Post|null $post Post.
 * @return string
 */
function avenza_service_number( $post = null ) {
	$post   = get_post( $post );
	$number = $post ? get_post_meta( $post->ID, '_avenza_number', true ) : '';
	if ( ! $number && $post ) {
		$number = str_pad( (string) max( 1, (int) $post->menu_order ), 2, '0', STR_PAD_LEFT );
	}
	return $number;
}

/**
 * Services in menu order.
 *
 * @param int   $limit   Max items, -1 for all.
 * @param array $exclude IDs to skip.
 * @return WP_Post[]
 */
function avenza_get_services( $limit = -1, $exclude = array(), $category = '' ) {
	$args = array(
		'post_type'      => 'avenza_service',
		'posts_per_page' => $limit,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'post__not_in'   => $exclude,
		'no_found_rows'  => true,
	);
	if ( $category ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'avenza_service_cat',
				'field'    => 'slug',
				'terms'    => $category,
			),
		);
	}
	return get_posts( $args );
}

/**
 * Lead list columns.
 *
 * @param array $columns Columns.
 * @return array
 */
function avenza_lead_columns( $columns ) {
	$columns['avenza_email']   = __( 'Email', 'avenza' );
	$columns['avenza_phone']   = __( 'Phone', 'avenza' );
	$columns['avenza_service'] = __( 'Interested in', 'avenza' );
	return $columns;
}
add_filter( 'manage_avenza_lead_posts_columns', 'avenza_lead_columns' );

/**
 * Lead list column values.
 *
 * @param string $column  Column.
 * @param int    $post_id Post ID.
 */
function avenza_lead_column_values( $column, $post_id ) {
	$map = array(
		'avenza_email'   => '_avenza_email',
		'avenza_phone'   => '_avenza_phone',
		'avenza_service' => '_avenza_service',
	);
	if ( isset( $map[ $column ] ) ) {
		echo esc_html( get_post_meta( $post_id, $map[ $column ], true ) );
	}
}
add_action( 'manage_avenza_lead_posts_custom_column', 'avenza_lead_column_values', 10, 2 );

/**
 * Service categories in display order.
 *
 * @return WP_Term[]
 */
function avenza_service_categories() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'avenza_service_cat',
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	usort(
		$terms,
		function ( $a, $b ) {
			return (int) get_term_meta( $a->term_id, '_avenza_order', true ) <=> (int) get_term_meta( $b->term_id, '_avenza_order', true );
		}
	);
	return $terms;
}

/**
 * The category a service belongs to.
 *
 * @param int|WP_Post|null $post Post.
 * @return WP_Term|null
 */
function avenza_service_category( $post = null ) {
	$post  = get_post( $post );
	$terms = $post ? get_the_terms( $post, 'avenza_service_cat' ) : false;
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/**
 * Image attachment ID chosen for a service category.
 *
 * @param WP_Term $term Term.
 * @return int
 */
function avenza_category_image_id( $term ) {
	$key = get_term_meta( $term->term_id, '_avenza_image', true );
	$map = (array) get_option( 'avenza_media_map', array() );
	return ( $key && ! empty( $map[ $key ] ) ) ? (int) $map[ $key ] : 0;
}
