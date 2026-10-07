<?php
/**
 * Navigation: walker with accessible sub-menu toggles, and fallbacks.
 *
 * @package avenza
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a toggle button after every parent item so sub-menus work by
 * keyboard and touch, not only on hover.
 */
class Avenza_Walker_Nav extends Walker_Nav_Menu {

	/**
	 * Start a sub-menu.
	 *
	 * @param string   $output Output.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Args.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '<ul class="sub-menu">';
	}

	/**
	 * Start an item.
	 *
	 * @param string   $output Output.
	 * @param WP_Post  $item   Item.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Args.
	 * @param int      $id     ID.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$has_kids  = in_array( 'menu-item-has-children', $classes, true );
		$is_active = in_array( 'current-menu-item', $classes, true ) || in_array( 'current-menu-ancestor', $classes, true ) || in_array( 'current-menu-parent', $classes, true );

		$li_classes = array( 'menu-item' );
		if ( $has_kids ) {
			$li_classes[] = 'has-sub';
		}
		if ( $is_active ) {
			$li_classes[] = 'is-active';
		}

		$output .= '<li class="' . esc_attr( implode( ' ', $li_classes ) ) . '">';

		$atts = array(
			'href' => ! empty( $item->url ) ? $item->url : '',
		);
		if ( in_array( 'current-menu-item', $classes, true ) ) {
			$atts['aria-current'] = 'page';
		}
		if ( ! empty( $item->target ) ) {
			$atts['target'] = $item->target;
		}
		if ( ! empty( $item->xfn ) ) {
			$atts['rel'] = $item->xfn;
		}

		$attr_html = '';
		foreach ( $atts as $name => $value ) {
			$value      = 'href' === $name ? esc_url( $value ) : esc_attr( $value );
			$attr_html .= ' ' . $name . '="' . $value . '"';
		}

		$title   = apply_filters( 'the_title', $item->title, $item->ID );
		$output .= '<a' . $attr_html . '>' . esc_html( $title ) . '</a>';

		if ( $has_kids ) {
			$output .= sprintf(
				'<button class="avz-sub-toggle" type="button" aria-expanded="false"><span class="screen-reader-text">%s</span>%s</button>',
				/* translators: %s: menu item title */
				esc_html( sprintf( __( 'Show %s sub-menu', 'avenza' ), $title ) ),
				avenza_icon( 'chevron', 16 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}
	}
}

/**
 * Fallback when no menu is assigned: pages and services built from content.
 *
 * @param array $args Args (expects 'menu_class').
 */
function avenza_menu_fallback( $args = array() ) {
	$class = isset( $args['menu_class'] ) ? $args['menu_class'] : 'avz-menu';
	$items = array(
		array( 'home', __( 'Home', 'avenza' ), home_url( '/' ) ),
		array( 'about', __( 'About', 'avenza' ), avenza_page_url( 'about' ) ),
		array( 'services', __( 'Services', 'avenza' ), avenza_page_url( 'services' ) ),
		array( 'approach', __( 'Approach', 'avenza' ), avenza_page_url( 'approach' ) ),
		array( 'insights', __( 'Insights', 'avenza' ), avenza_page_url( 'insights' ) ),
		array( 'contact', __( 'Contact', 'avenza' ), avenza_page_url( 'contact' ) ),
	);

	echo '<ul class="' . esc_attr( $class ) . '">';
	foreach ( $items as $item ) {
		list( $key, $label, $url ) = $item;
		$services = 'services' === $key ? avenza_get_services() : array();
		echo '<li class="menu-item' . ( $services ? ' has-sub' : '' ) . '"><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		if ( $services ) {
			printf(
				'<button class="avz-sub-toggle" type="button" aria-expanded="false"><span class="screen-reader-text">%s</span>%s</button>',
				esc_html__( 'Show Services sub-menu', 'avenza' ),
				avenza_icon( 'chevron', 16 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
			echo '<ul class="sub-menu">';
			foreach ( $services as $service ) {
				echo '<li class="menu-item"><a href="' . esc_url( get_permalink( $service ) ) . '">' . esc_html( get_the_title( $service ) ) . '</a></li>';
			}
			echo '</ul>';
		}
		echo '</li>';
	}
	echo '</ul>';
}

/**
 * Print the primary menu.
 *
 * @param string $class Menu class.
 */
function avenza_primary_menu( $class = 'avz-menu' ) {
	wp_nav_menu(
		array(
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => $class,
			'walker'         => new Avenza_Walker_Nav(),
			'fallback_cb'    => 'avenza_menu_fallback',
			'depth'          => 2,
		)
	);
}

/**
 * Breadcrumb trail for inner pages.
 */
function avenza_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}
	$trail = avenza_breadcrumb_items();
	if ( count( $trail ) < 2 ) {
		return;
	}
	echo '<nav class="avz-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'avenza' ) . '"><div class="avz-container"><ol>';
	$last = count( $trail ) - 1;
	foreach ( $trail as $i => $crumb ) {
		if ( $i === $last ) {
			echo '<li><span aria-current="page">' . esc_html( $crumb['name'] ) . '</span></li>';
		} else {
			echo '<li><a href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['name'] ) . '</a></li>';
		}
	}
	echo '</ol></div></nav>';
}

/**
 * Breadcrumb items, shared with the BreadcrumbList schema.
 *
 * @return array[] Each with name and url.
 */
function avenza_breadcrumb_items() {
	$items = array(
		array(
			'name' => __( 'Home', 'avenza' ),
			'url'  => home_url( '/' ),
		),
	);

	if ( is_singular( 'avenza_service' ) ) {
		$items[] = array(
			'name' => __( 'Services', 'avenza' ),
			'url'  => avenza_page_url( 'services' ),
		);
		$items[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);
	} elseif ( is_singular( 'post' ) ) {
		$posts_page = (int) get_option( 'page_for_posts' );
		if ( $posts_page ) {
			$items[] = array(
				'name' => get_the_title( $posts_page ),
				'url'  => get_permalink( $posts_page ),
			);
		}
		$items[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);
	} elseif ( is_page() ) {
		$ancestors = array_reverse( get_post_ancestors( get_the_ID() ) );
		foreach ( $ancestors as $ancestor ) {
			$items[] = array(
				'name' => get_the_title( $ancestor ),
				'url'  => get_permalink( $ancestor ),
			);
		}
		$items[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);
	} elseif ( is_home() ) {
		$posts_page = (int) get_option( 'page_for_posts' );
		$items[]    = array(
			'name' => $posts_page ? get_the_title( $posts_page ) : __( 'Insights', 'avenza' ),
			'url'  => $posts_page ? get_permalink( $posts_page ) : home_url( '/' ),
		);
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$link    = get_term_link( get_queried_object() );
		$items[] = array(
			'name' => single_term_title( '', false ),
			'url'  => is_wp_error( $link ) ? home_url( '/' ) : $link,
		);
	} elseif ( is_search() ) {
		$items[] = array(
			'name' => __( 'Search results', 'avenza' ),
			'url'  => get_search_link(),
		);
	} elseif ( is_404() ) {
		$items[] = array(
			'name' => __( 'Page not found', 'avenza' ),
			'url'  => home_url( add_query_arg( array() ) ),
		);
	}

	return $items;
}

/**
 * Footer company links when no menu is assigned.
 */
function avenza_footer_fallback() {
	$links = array(
		'about'    => __( 'About', 'avenza' ),
		'approach' => __( 'Our Approach', 'avenza' ),
		'services' => __( 'Services', 'avenza' ),
		'insights' => __( 'Insights', 'avenza' ),
		'faq'      => __( 'FAQ', 'avenza' ),
		'contact'  => __( 'Contact', 'avenza' ),
	);
	echo '<ul class="avz-footer__list">';
	foreach ( $links as $slug => $label ) {
		echo '<li><a href="' . esc_url( avenza_page_url( $slug ) ) . '">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
}

/**
 * Legal links when no menu is assigned.
 */
function avenza_legal_fallback() {
	echo '<ul class="avz-footer__legal">';
	echo '<li><a href="' . esc_url( avenza_page_url( 'privacy-policy' ) ) . '">' . esc_html__( 'Privacy Policy', 'avenza' ) . '</a></li>';
	echo '<li><a href="' . esc_url( avenza_page_url( 'terms' ) ) . '">' . esc_html__( 'Terms of Service', 'avenza' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/llms.txt' ) ) . '">llms.txt</a></li>';
	echo '</ul>';
}
