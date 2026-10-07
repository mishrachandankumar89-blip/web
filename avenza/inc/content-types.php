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
function avenza_get_services( $limit = -1, $exclude = array() ) {
	return get_posts(
		array(
			'post_type'      => 'avenza_service',
			'posts_per_page' => $limit,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'post__not_in'   => $exclude,
			'no_found_rows'  => true,
		)
	);
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
