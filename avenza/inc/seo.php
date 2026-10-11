<?php
/**
 * Meta titles, descriptions, Open Graph, Twitter cards, GA4 and Search Console.
 * Stands down automatically when Yoast SEO, Rank Math, AIOSEO or SEOPress is active.
 *
 * @package avenza
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a dedicated SEO plugin is handling meta.
 *
 * @return bool
 */
function avenza_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * Meta title for the current view, or '' to use the WordPress default.
 *
 * @return string
 */
function avenza_meta_title() {
	if ( is_singular() ) {
		return (string) get_post_meta( get_queried_object_id(), '_avenza_meta_title', true );
	}
	if ( is_home() && get_option( 'page_for_posts' ) ) {
		return (string) get_post_meta( (int) get_option( 'page_for_posts' ), '_avenza_meta_title', true );
	}
	if ( is_tax( 'avenza_service_cat' ) ) {
		/* translators: %s: discipline name */
		return sprintf( __( '%s Services | AVENZA', 'avenza' ), single_term_title( '', false ) );
	}
	return '';
}

/**
 * Meta description for the current view.
 *
 * @return string
 */
function avenza_meta_description() {
	$desc = '';
	if ( is_singular() ) {
		$id   = get_queried_object_id();
		$desc = get_post_meta( $id, '_avenza_meta_desc', true );
		if ( ! $desc && has_excerpt( $id ) ) {
			$desc = get_the_excerpt( $id );
		}
	} elseif ( is_home() && get_option( 'page_for_posts' ) ) {
		$desc = get_post_meta( (int) get_option( 'page_for_posts' ), '_avenza_meta_desc', true );
	} elseif ( is_category() || is_tag() || is_tax( 'avenza_service_cat' ) ) {
		$desc = term_description();
	}
	if ( ! $desc && is_front_page() ) {
		$desc = get_bloginfo( 'description' );
	}
	$desc = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $desc ) ) );
	if ( mb_strlen( $desc ) > 158 ) {
		$desc = rtrim( mb_substr( $desc, 0, 155 ), " ,.;:" ) . '...';
	}
	return $desc;
}

/**
 * Use the stored meta title in the document title.
 *
 * @param string $title Title.
 * @return string
 */
function avenza_document_title( $title ) {
	if ( avenza_seo_plugin_active() ) {
		return $title;
	}
	$custom = avenza_meta_title();
	return $custom ? $custom : $title;
}
add_filter( 'pre_get_document_title', 'avenza_document_title', 20 );

/**
 * Social share image for the current view.
 *
 * @return array|null url,width,height,alt
 */
function avenza_share_image() {
	$id = 0;
	if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
		$id = get_post_thumbnail_id( get_queried_object_id() );
	}
	if ( $id ) {
		$src = wp_get_attachment_image_src( $id, 'avenza-wide' );
		if ( $src ) {
			return array(
				'url'    => $src[0],
				'width'  => $src[1],
				'height' => $src[2],
				'alt'    => get_post_meta( $id, '_wp_attachment_image_alt', true ),
			);
		}
	}
	return array(
		'url'    => avenza_asset_img( 'brand/avenza-share.jpg' ),
		'width'  => 1200,
		'height' => 630,
		'alt'    => __( 'AVENZA: be the answer, not just a result', 'avenza' ),
	);
}

/**
 * Canonical URL for the current view.
 *
 * @return string
 */
function avenza_current_url() {
	if ( is_singular() ) {
		return wp_get_canonical_url( get_queried_object_id() );
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_home() && get_option( 'page_for_posts' ) ) {
		$url   = get_permalink( (int) get_option( 'page_for_posts' ) );
		$paged = get_query_var( 'paged' );
		return $paged > 1 ? trailingslashit( $url ) . 'page/' . (int) $paged . '/' : $url;
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? home_url( '/' ) : $link;
	}
	return '';
}

/**
 * Head output: description, Open Graph, Twitter, verification, analytics.
 */
function avenza_head_meta() {
	$gsc = avenza_opt( 'gsc_verify' );
	if ( $gsc ) {
		echo '<meta name="google-site-verification" content="' . esc_attr( $gsc ) . '">' . "\n";
	}

	if ( ! avenza_seo_plugin_active() ) {
		$desc  = avenza_meta_description();
		$title = wp_get_document_title();
		$url   = avenza_current_url();
		$img   = avenza_share_image();

		if ( $desc ) {
			echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		if ( $url && ! is_singular() ) {
			// WordPress prints the canonical for singular views itself.
			echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
		}
		echo '<meta property="og:locale" content="' . esc_attr( str_replace( '-', '_', get_bloginfo( 'language' ) ) ) . '">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
		echo '<meta property="og:type" content="' . ( is_singular( 'post' ) ? 'article' : 'website' ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		if ( $desc ) {
			echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		if ( $url ) {
			echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		}
		if ( $img ) {
			echo '<meta property="og:image" content="' . esc_url( $img['url'] ) . '">' . "\n";
			echo '<meta property="og:image:width" content="' . (int) $img['width'] . '">' . "\n";
			echo '<meta property="og:image:height" content="' . (int) $img['height'] . '">' . "\n";
			if ( $img['alt'] ) {
				echo '<meta property="og:image:alt" content="' . esc_attr( $img['alt'] ) . '">' . "\n";
			}
		}
		if ( is_singular( 'post' ) ) {
			echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . '">' . "\n";
			echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . "\n";
		}
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	}

	$ga4 = avenza_opt( 'ga4_id' );
	if ( $ga4 && preg_match( '/^G-[A-Z0-9]{4,}$/', $ga4 ) && ! is_user_logged_in() ) {
		printf(
			"<script async src=\"https://www.googletagmanager.com/gtag/js?id=%1\$s\"></script>\n<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','%1\$s');</script>\n",
			esc_attr( $ga4 )
		);
	}
}
add_action( 'wp_head', 'avenza_head_meta', 3 );

/**
 * Keep thin views out of the index: search results, enquiry status URLs, attachment pages.
 *
 * @param array $robots Directives.
 * @return array
 */
function avenza_robots_directives( $robots ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( is_search() || is_attachment() || isset( $_GET['avz_status'] ) || isset( $_GET['avz_form'] ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	if ( ! isset( $robots['noindex'] ) ) {
		$robots['max-image-preview'] = 'large';
	}
	return $robots;
}
add_filter( 'wp_robots', 'avenza_robots_directives' );

/**
 * Meta box for title and description on pages, posts and services.
 */
function avenza_meta_box() {
	if ( avenza_seo_plugin_active() ) {
		return;
	}
	foreach ( array( 'page', 'post', 'avenza_service' ) as $type ) {
		add_meta_box( 'avenza_seo', __( 'Search appearance', 'avenza' ), 'avenza_meta_box_html', $type, 'normal', 'low' );
	}
}
add_action( 'add_meta_boxes', 'avenza_meta_box' );

/**
 * Meta box markup.
 *
 * @param WP_Post $post Post.
 */
function avenza_meta_box_html( $post ) {
	wp_nonce_field( 'avenza_seo_save', 'avenza_seo_nonce' );
	$title = get_post_meta( $post->ID, '_avenza_meta_title', true );
	$desc  = get_post_meta( $post->ID, '_avenza_meta_desc', true );
	?>
	<p>
		<label for="avenza-meta-title"><strong><?php esc_html_e( 'Meta title', 'avenza' ); ?></strong> <?php esc_html_e( '(up to 60 characters)', 'avenza' ); ?></label><br>
		<input type="text" id="avenza-meta-title" name="avenza_meta_title" class="widefat" maxlength="70" value="<?php echo esc_attr( $title ); ?>">
	</p>
	<p>
		<label for="avenza-meta-desc"><strong><?php esc_html_e( 'Meta description', 'avenza' ); ?></strong> <?php esc_html_e( '(up to 155 characters)', 'avenza' ); ?></label><br>
		<textarea id="avenza-meta-desc" name="avenza_meta_desc" class="widefat" rows="3" maxlength="170"><?php echo esc_textarea( $desc ); ?></textarea>
	</p>
	<?php
}

/**
 * Save the meta box.
 *
 * @param int $post_id Post ID.
 */
function avenza_meta_box_save( $post_id ) {
	if ( ! isset( $_POST['avenza_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['avenza_seo_nonce'] ) ), 'avenza_seo_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['avenza_meta_title'] ) ) {
		update_post_meta( $post_id, '_avenza_meta_title', sanitize_text_field( wp_unslash( $_POST['avenza_meta_title'] ) ) );
	}
	if ( isset( $_POST['avenza_meta_desc'] ) ) {
		update_post_meta( $post_id, '_avenza_meta_desc', sanitize_textarea_field( wp_unslash( $_POST['avenza_meta_desc'] ) ) );
	}
}
add_action( 'save_post', 'avenza_meta_box_save' );
