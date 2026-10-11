<?php
/**
 * One-click setup and version updates.
 *
 * Reads content/manifest.json and creates the images, services, pages,
 * articles, menus and reading settings. Runs in small AJAX steps so it never
 * hits a host's execution time limit. Pages a client has edited since the
 * last import are left alone on updates.
 *
 * @package avenza
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The bundled content manifest.
 *
 * @return array
 */
function avenza_manifest() {
	static $manifest = null;
	if ( null === $manifest ) {
		$file     = AVENZA_DIR . '/content/manifest.json';
		$manifest = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $manifest ) ) {
			$manifest = array();
		}
	}
	return $manifest;
}

/**
 * Whether setup has ever run, and whether this version's content is applied.
 *
 * @return string none|outdated|current
 */
function avenza_import_state() {
	if ( ! get_option( 'avenza_imported' ) ) {
		return 'none';
	}
	return version_compare( (string) get_option( 'avenza_content_version', '0' ), AVENZA_VERSION, '<' ) ? 'outdated' : 'current';
}

/**
 * Admin notice pointing to the setup screen.
 */
function avenza_import_notice() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && 'appearance_page_avenza-setup' === $screen->id ) {
		return;
	}
	$state = avenza_import_state();
	if ( 'current' === $state ) {
		return;
	}
	$url = admin_url( 'themes.php?page=avenza-setup' );
	echo '<div class="notice notice-info"><p><strong>AVENZA</strong>: ';
	if ( 'none' === $state ) {
		echo esc_html__( 'Your theme is active. One click builds every page, service, article, menu and image.', 'avenza' );
		echo ' <a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'Set up the AVENZA website', 'avenza' ) . '</a>';
	} else {
		/* translators: %s: version */
		echo esc_html( sprintf( __( 'Version %s brings new artwork and updated pages.', 'avenza' ), AVENZA_VERSION ) );
		/* translators: %s: version */
		echo ' <a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html( sprintf( __( 'Apply the AVENZA %s update', 'avenza' ), AVENZA_VERSION ) ) . '</a>';
	}
	echo '</p></div>';
}
add_action( 'admin_notices', 'avenza_import_notice' );

/**
 * Setup screen under Appearance.
 */
function avenza_setup_menu() {
	add_theme_page( __( 'AVENZA Setup', 'avenza' ), __( 'AVENZA Setup', 'avenza' ), 'edit_theme_options', 'avenza-setup', 'avenza_setup_screen' );
}
add_action( 'admin_menu', 'avenza_setup_menu' );

/**
 * Setup screen markup and the small step runner.
 */
function avenza_setup_screen() {
	$state    = avenza_import_state();
	$manifest = avenza_manifest();
	$counts   = array(
		'images'   => isset( $manifest['images'] ) ? count( $manifest['images'] ) : 0,
		'services' => isset( $manifest['services'] ) ? count( $manifest['services'] ) : 0,
		'pages'    => isset( $manifest['pages'] ) ? count( $manifest['pages'] ) : 0,
		'posts'    => isset( $manifest['posts'] ) ? count( $manifest['posts'] ) : 0,
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'AVENZA Setup', 'avenza' ); ?></h1>
		<p style="max-width:720px">
			<?php
			printf(
				/* translators: 1: pages, 2: services, 3: articles, 4: images */
				esc_html__( 'This builds %1$d pages, %2$d service pages, %3$d articles and %4$d images, then sets up the menus, the front page and the Insights page. All content is normal block editor content you can change later.', 'avenza' ),
				(int) $counts['pages'],
				(int) $counts['services'],
				(int) $counts['posts'],
				(int) $counts['images']
			);
			?>
		</p>
		<?php if ( 'none' !== $state ) : ?>
			<p style="max-width:720px"><?php esc_html_e( 'Running it again updates the theme pages to this version. Any page you edited yourself after the last setup is kept exactly as it is and listed below.', 'avenza' ); ?></p>
		<?php endif; ?>
		<p>
			<button type="button" class="button button-primary button-hero" id="avenza-run">
				<?php
				if ( 'none' === $state ) {
					esc_html_e( 'Set up the website', 'avenza' );
				} else {
					/* translators: %s: version */
					echo esc_html( sprintf( __( 'Apply the AVENZA %s update', 'avenza' ), AVENZA_VERSION ) );
				}
				?>
			</button>
		</p>
		<div id="avenza-progress" style="max-width:720px;display:none">
			<progress id="avenza-bar" max="100" value="0" style="width:100%;height:18px"></progress>
			<ol id="avenza-log" style="margin-top:12px"></ol>
		</div>
	</div>
	<script>
	(function () {
		var btn = document.getElementById('avenza-run');
		var bar = document.getElementById('avenza-bar');
		var log = document.getElementById('avenza-log');
		var nonce = <?php echo wp_json_encode( wp_create_nonce( 'avenza_import' ) ); ?>;
		var ajax = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		function line(text) { var li = document.createElement('li'); li.textContent = text; log.appendChild(li); }
		function step(name, offset) {
			var body = new FormData();
			body.append('action', 'avenza_import_step');
			body.append('nonce', nonce);
			body.append('step', name);
			body.append('offset', offset || 0);
			return fetch(ajax, { method: 'POST', body: body, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res || !res.success) { throw new Error(res && res.data ? res.data : 'Unknown error'); }
					var d = res.data;
					if (d.message) { line(d.message); }
					if (typeof d.progress === 'number') { bar.value = d.progress; }
					if (d.next) { return step(d.next, d.offset); }
					return d;
				});
		}
		btn.addEventListener('click', function () {
			btn.disabled = true;
			document.getElementById('avenza-progress').style.display = 'block';
			step('media', 0).then(function (d) {
				bar.value = 100;
				line(<?php echo wp_json_encode( __( 'Done. Your website is ready.', 'avenza' ) ); ?>);
				var a = document.createElement('a');
				a.href = <?php echo wp_json_encode( home_url( '/' ) ); ?>;
				a.className = 'button button-primary';
				a.textContent = <?php echo wp_json_encode( __( 'View the website', 'avenza' ) ); ?>;
				log.parentNode.appendChild(a);
			}).catch(function (e) {
				line('Error: ' + e.message);
				btn.disabled = false;
			});
		});
	})();
	</script>
	<?php
}

/**
 * AJAX step runner.
 */
function avenza_import_ajax() {
	check_ajax_referer( 'avenza_import', 'nonce' );
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( __( 'You do not have permission to run the setup.', 'avenza' ) );
	}
	$step   = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';
	$offset = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;

	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	switch ( $step ) {
		case 'media':
			wp_send_json_success( avenza_import_media( $offset, 3 ) );
			break;
		case 'content':
			wp_send_json_success( avenza_import_content( $offset, 20 ) );
			break;
		case 'finish':
			wp_send_json_success( avenza_import_finish() );
			break;
	}
	wp_send_json_error( 'Unknown step' );
}
add_action( 'wp_ajax_avenza_import_step', 'avenza_import_ajax' );

/**
 * Run every step in one go. Used by WP-CLI and automated tests.
 *
 * @return array Summary.
 */
function avenza_import_all() {
	$offset = 0;
	do {
		$result = avenza_import_media( $offset, 50 );
		$offset = isset( $result['offset'] ) ? $result['offset'] : 0;
	} while ( 'media' === $result['next'] );
	$offset = 0;
	do {
		$content = avenza_import_content( $offset, 200 );
		$offset  = isset( $content['offset'] ) ? $content['offset'] : 0;
	} while ( 'content' === $content['next'] );
	$finish = avenza_import_finish();
	return array( $content['message'], $finish['message'] );
}

/**
 * Attachment ID previously imported for an image key and file.
 *
 * @param string $key  Image key.
 * @param string $file Bundled file path.
 * @return int
 */
function avenza_find_attachment( $key, $file ) {
	$found = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_avenza_source',
					'value' => $file,
				),
			),
		)
	);
	return $found ? (int) $found[0] : 0;
}

/**
 * Import a batch of images into the media library.
 *
 * @param int $offset Start index.
 * @param int $batch  Batch size.
 * @return array
 */
function avenza_import_media( $offset, $batch ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';

	$manifest = avenza_manifest();
	$images   = isset( $manifest['images'] ) ? $manifest['images'] : array();
	$total    = count( $images );
	$map      = get_option( 'avenza_media_map', array() );
	if ( ! is_array( $map ) || 0 === $offset ) {
		$map = 0 === $offset ? array() : (array) $map;
	}

	$slice = array_slice( $images, $offset, $batch );
	foreach ( $slice as $image ) {
		$key  = $image['key'];
		$file = $image['file'];
		$id   = avenza_find_attachment( $key, $file );

		if ( ! $id ) {
			$path = AVENZA_DIR . '/assets/img/' . $file;
			if ( ! file_exists( $path ) ) {
				continue;
			}
			$upload = wp_upload_bits( basename( $file ), null, (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( ! empty( $upload['error'] ) ) {
				continue;
			}
			$type = wp_check_filetype( $upload['file'] );
			$id   = wp_insert_attachment(
				array(
					'post_mime_type' => $type['type'],
					'post_name'      => 'avenza-art-' . $key,
					'post_title'     => $image['title'],
					'post_content'   => '',
					'post_excerpt'   => isset( $image['caption'] ) ? $image['caption'] : '',
					'post_status'    => 'inherit',
				),
				$upload['file']
			);
			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
			update_post_meta( $id, '_avenza_source', $file );
			update_post_meta( $id, '_avenza_key', $key );
		}
		// Attachment slugs must never match a page or post slug, or the
		// article URL can resolve to the image instead.
		if ( 'avenza-art-' . $key !== get_post_field( 'post_name', $id ) ) {
			wp_update_post(
				array(
					'ID'        => $id,
					'post_name' => 'avenza-art-' . $key,
				)
			);
		}
		update_post_meta( $id, '_wp_attachment_image_alt', $image['alt'] );
		$map[ $key ] = (int) $id;
	}
	update_option( 'avenza_media_map', $map, false );

	$next_offset = $offset + count( $slice );
	$done        = $next_offset >= $total;

	return array(
		'message'  => sprintf(
			/* translators: 1: done, 2: total */
			__( 'Images: %1$d of %2$d ready.', 'avenza' ),
			min( $next_offset, $total ),
			$total
		),
		'progress' => $total ? (int) round( 70 * min( $next_offset, $total ) / $total ) : 70,
		'next'     => $done ? 'content' : 'media',
		'offset'   => $done ? 0 : $next_offset,
	);
}

/**
 * Create or find a post by slug and type.
 *
 * @param string $type   Post type.
 * @param string $slug   Slug.
 * @param string $title  Title.
 * @param int    $parent Parent ID.
 * @return int
 */
function avenza_upsert_shell( $type, $slug, $title, $parent = 0 ) {
	$existing = get_posts(
		array(
			'post_type'      => $type,
			'name'           => $slug,
			'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'post_parent'    => 'page' === $type ? $parent : '',
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}
	if ( 'page' === $type ) {
		$by_path = get_page_by_path( $slug );
		if ( $by_path ) {
			return (int) $by_path->ID;
		}
	}
	$id = wp_insert_post(
		array(
			'post_type'   => $type,
			'post_name'   => $slug,
			'post_title'  => $title,
			'post_status' => 'publish',
			'post_parent' => $parent,
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	update_post_meta( $id, '_avenza_new', 1 );
	return (int) $id;
}

/**
 * Replace content tokens with real URLs and IDs.
 *
 * Tokens: {{site}}, {{img_id:key}}, {{img_src:key}}, {{link:type:slug}}.
 *
 * @param string $html  Content.
 * @param array  $media key => attachment ID.
 * @param array  $ids   "type:slug" => post ID.
 * @return string
 */
function avenza_resolve_tokens( $html, $media, $ids ) {
	$html = preg_replace_callback(
		'/\{\{img_(id|src):([a-z0-9-]+)\}\}/',
		function ( $m ) use ( $media ) {
			$id = isset( $media[ $m[2] ] ) ? (int) $media[ $m[2] ] : 0;
			if ( 'id' === $m[1] ) {
				return (string) $id;
			}
			$url = $id ? wp_get_attachment_image_url( $id, 'large' ) : '';
			return $url ? esc_url( $url ) : '';
		},
		$html
	);
	$html = preg_replace_callback(
		'/\{\{link:(page|service|post|term):([a-z0-9\/-]+)\}\}/',
		function ( $m ) use ( $ids ) {
			$key = $m[1] . ':' . $m[2];
			if ( 'term' === $m[1] && isset( $ids[ $key ] ) ) {
				$link = get_term_link( (int) $ids[ $key ], 'avenza_service_cat' );
				return is_wp_error( $link ) ? esc_url( home_url( '/expertise/' . $m[2] . '/' ) ) : esc_url( $link );
			}
			if ( isset( $ids[ $key ] ) ) {
				return esc_url( get_permalink( $ids[ $key ] ) );
			}
			return esc_url( home_url( '/' . $m[2] . '/' ) );
		},
		$html
	);
	return str_replace( '{{site}}', untrailingslashit( home_url() ), $html );
}

/**
 * Every content item in import order, as [ group, item ] pairs.
 *
 * @return array[]
 */
function avenza_import_items() {
	$manifest = avenza_manifest();
	$all      = array();
	foreach ( array( 'service' => 'services', 'page' => 'pages', 'post' => 'posts' ) as $group => $key ) {
		foreach ( isset( $manifest[ $key ] ) ? $manifest[ $key ] : array() as $item ) {
			$all[] = array( $group, $item );
		}
	}
	return $all;
}

/**
 * First content step: permalinks, service categories and an empty shell for
 * every item, so links between items resolve whatever order they are written in.
 *
 * @return array "type:slug" => ID.
 */
function avenza_import_prepare() {
	$manifest = avenza_manifest();
	$ids      = array();

	// On a first setup, swap WordPress's default date-based permalinks for
	// short /%postname%/ URLs before any link inside the content is resolved.
	// A structure the owner chose themselves is never changed.
	if ( 'none' === avenza_import_state() && in_array( (string) get_option( 'permalink_structure' ), array( '', '/%year%/%monthnum%/%day%/%postname%/' ), true ) ) {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
	}

	foreach ( isset( $manifest['service_categories'] ) ? $manifest['service_categories'] : array() as $order => $cat ) {
		$term = term_exists( $cat['slug'], 'avenza_service_cat' );
		if ( ! $term ) {
			$term = wp_insert_term(
				$cat['name'],
				'avenza_service_cat',
				array(
					'slug'        => $cat['slug'],
					'description' => $cat['description'],
				)
			);
		} else {
			wp_update_term(
				(int) $term['term_id'],
				'avenza_service_cat',
				array(
					'name'        => $cat['name'],
					'description' => $cat['description'],
				)
			);
		}
		if ( is_wp_error( $term ) ) {
			continue;
		}
		$term_id = (int) $term['term_id'];
		update_term_meta( $term_id, '_avenza_order', $order + 1 );
		update_term_meta( $term_id, '_avenza_image', $cat['image'] );
		update_term_meta( $term_id, '_avenza_short', $cat['short'] );
		$ids[ 'term:' . $cat['slug'] ] = $term_id;
	}

	foreach ( avenza_import_items() as $pair ) {
		list( $group, $item ) = $pair;
		$type                 = 'service' === $group ? 'avenza_service' : $group;
		$ids[ $group . ':' . $item['slug'] ] = avenza_upsert_shell( $type, $item['slug'], $item['title'] );
	}

	update_option( 'avenza_import_ids', $ids, false );
	update_option( 'avenza_import_kept', array(), false );
	update_option( 'avenza_import_written', 0, false );
	return $ids;
}

/**
 * Create or update services, pages and articles, a batch at a time.
 *
 * @param int $offset Start index in the item list.
 * @param int $batch  Items per request.
 * @return array
 */
function avenza_import_content( $offset = 0, $batch = 20 ) {
	$media = (array) get_option( 'avenza_media_map', array() );
	$ids   = 0 === $offset ? avenza_import_prepare() : (array) get_option( 'avenza_import_ids', array() );
	$kept  = (array) get_option( 'avenza_import_kept', array() );
	$count = (int) get_option( 'avenza_import_written', 0 );
	$all   = avenza_import_items();
	$slice = array_slice( $all, $offset, $batch );

	// The bundled content is trusted theme markup; keep it exactly as shipped.
	$filtered = ! current_user_can( 'unfiltered_html' );
	if ( $filtered ) {
		kses_remove_filters();
	}

	foreach ( $slice as $pair ) {
		list( $group, $item ) = $pair;
		$id = isset( $ids[ $group . ':' . $item['slug'] ] ) ? $ids[ $group . ':' . $item['slug'] ] : 0;
		if ( ! $id ) {
			continue;
		}
		$current = get_post( $id );
		$hash    = get_post_meta( $id, '_avenza_hash', true );
		$is_new  = (bool) get_post_meta( $id, '_avenza_new', true );
		$edited  = $hash && md5( $current->post_content ) !== $hash;
		if ( $edited && ! $is_new ) {
			$kept[] = $item['title'];
			continue;
		}

		$file = AVENZA_DIR . '/content/' . $item['content_file'];
		$html = file_exists( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$html = avenza_resolve_tokens( $html, $media, $ids );

		$data = array(
			'ID'             => $id,
			'post_title'     => $item['title'],
			'post_content'   => $html,
			'post_excerpt'   => isset( $item['excerpt'] ) ? $item['excerpt'] : '',
			'post_status'    => 'publish',
			'menu_order'     => isset( $item['menu_order'] ) ? (int) $item['menu_order'] : 0,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);
		if ( 'post' === $group && ! empty( $item['date'] ) && $is_new ) {
			$data['post_date']     = $item['date'] . ' 10:00:00';
			$data['post_date_gmt'] = get_gmt_from_date( $data['post_date'] );
		}
		$saved = wp_update_post( wp_slash( $data ), true );
		if ( is_wp_error( $saved ) ) {
			continue;
		}
		$stored = get_post_field( 'post_content', $id, 'raw' );
		update_post_meta( $id, '_avenza_hash', md5( $stored ) );
		delete_post_meta( $id, '_avenza_new' );

		update_post_meta( $id, '_avenza_meta_title', $item['meta_title'] );
		update_post_meta( $id, '_avenza_meta_desc', $item['meta_description'] );
		if ( 'service' === $group && ! empty( $item['number'] ) ) {
			update_post_meta( $id, '_avenza_number', $item['number'] );
		}
		if ( 'service' === $group && ! empty( $item['category'] ) && ! empty( $ids[ 'term:' . $item['category'] ] ) ) {
			wp_set_object_terms( $id, array( (int) $ids[ 'term:' . $item['category'] ] ), 'avenza_service_cat' );
		}
		if ( ! empty( $item['image'] ) && ! empty( $media[ $item['image'] ] ) ) {
			set_post_thumbnail( $id, (int) $media[ $item['image'] ] );
		}
		if ( 'post' === $group && ! empty( $item['category'] ) ) {
			$term = term_exists( $item['category'], 'category' );
			if ( ! $term ) {
				$term = wp_insert_term( $item['category'], 'category' );
			}
			if ( ! is_wp_error( $term ) ) {
				wp_set_post_categories( $id, array( (int) $term['term_id'] ) );
			}
		}
		++$count;
	}

	if ( $filtered ) {
		kses_init_filters();
	}

	update_option( 'avenza_import_kept', $kept, false );
	update_option( 'avenza_import_written', $count, false );

	$next_offset = $offset + count( $slice );
	$total       = count( $all );
	if ( $next_offset < $total ) {
		return array(
			/* translators: 1: done, 2: total */
			'message'  => sprintf( __( 'Pages, services and articles: %1$d of %2$d written.', 'avenza' ), $next_offset, $total ),
			'progress' => 70 + (int) round( 20 * $next_offset / max( 1, $total ) ),
			'next'     => 'content',
			'offset'   => $next_offset,
		);
	}

	$message = sprintf(
		/* translators: %d: count */
		__( 'Pages, services and articles written: %d.', 'avenza' ),
		$count
	);
	if ( $kept ) {
		/* translators: %s: list of titles */
		$message .= ' ' . sprintf( __( 'Kept your edited versions of: %s.', 'avenza' ), implode( ', ', $kept ) );
	}

	return array(
		'message'  => $message,
		'progress' => 90,
		'next'     => 'finish',
	);
}

/**
 * Build or rebuild one theme menu.
 *
 * @param string $location Menu location.
 * @param string $name     Menu name.
 * @param array  $items    Items from the manifest.
 * @param array  $ids      "type:slug" => post ID.
 */
function avenza_build_menu( $location, $name, $items, $ids ) {
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu ) {
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $old ) {
			wp_delete_post( $old->ID, true );
		}
		$menu_id = $menu->term_id;
	} else {
		$menu_id = wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			return;
		}
	}

	$add = function ( $item, $parent = 0 ) use ( $menu_id, $ids ) {
		$key = $item['type'] . ':' . $item['slug'];
		if ( empty( $ids[ $key ] ) ) {
			return 0;
		}
		$is_term = 'term' === $item['type'];
		$object  = $is_term ? 'avenza_service_cat' : ( 'service' === $item['type'] ? 'avenza_service' : ( 'post' === $item['type'] ? 'post' : 'page' ) );
		return wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $item['title'],
				'menu-item-object'    => $object,
				'menu-item-object-id' => $ids[ $key ],
				'menu-item-type'      => $is_term ? 'taxonomy' : 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent,
			)
		);
	};

	foreach ( $items as $item ) {
		$parent = $add( $item );
		if ( $parent && ! empty( $item['children'] ) ) {
			foreach ( $item['children'] as $child ) {
				$add( $child, $parent );
			}
		}
	}

	$locations              = get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Menus, reading settings and housekeeping.
 *
 * @return array
 */
function avenza_import_finish() {
	$manifest = avenza_manifest();
	$ids      = (array) get_option( 'avenza_import_ids', array() );
	$first    = 'none' === avenza_import_state();

	if ( ! empty( $manifest['menus'] ) ) {
		$names = array(
			'primary' => 'AVENZA Primary',
			'footer'  => 'AVENZA Company',
			'legal'   => 'AVENZA Legal',
		);
		foreach ( $manifest['menus'] as $location => $items ) {
			if ( isset( $names[ $location ] ) ) {
				avenza_build_menu( $location, $names[ $location ], $items, $ids );
			}
		}
	}

	if ( ! empty( $manifest['front_page'] ) && ! empty( $ids[ 'page:' . $manifest['front_page'] ] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $ids[ 'page:' . $manifest['front_page'] ] );
	}
	if ( ! empty( $manifest['posts_page'] ) && ! empty( $ids[ 'page:' . $manifest['posts_page'] ] ) ) {
		update_option( 'page_for_posts', (int) $ids[ 'page:' . $manifest['posts_page'] ] );
	}
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	if ( $first ) {
		if ( ! empty( $manifest['site']['title'] ) ) {
			update_option( 'blogname', $manifest['site']['title'] );
		}
		if ( ! empty( $manifest['site']['tagline'] ) ) {
			update_option( 'blogdescription', $manifest['site']['tagline'] );
		}
		update_option( 'default_comment_status', 'closed' );
		update_option( 'posts_per_page', 9 );
	}

	// The default "Hello world!" post and "Sample Page" add nothing to a business site.
	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hello && 1 === (int) $hello->ID ) {
		wp_trash_post( $hello->ID );
	}
	$sample = get_page_by_path( 'sample-page' );
	if ( $sample ) {
		wp_trash_post( $sample->ID );
	}

	update_option( 'avenza_imported', 1 );
	update_option( 'avenza_content_version', AVENZA_VERSION );
	delete_transient( 'avenza_llms_txt' );
	flush_rewrite_rules();

	return array(
		'message'  => __( 'Menus, front page, Insights page and permalinks are set.', 'avenza' ),
		'progress' => 100,
		'next'     => '',
	);
}

/**
 * Re-register rewrite rules when the theme is switched on.
 */
function avenza_after_switch() {
	avenza_register_types();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'avenza_after_switch' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'avenza setup',
		function () {
			foreach ( avenza_import_all() as $line ) {
				WP_CLI::log( $line );
			}
			WP_CLI::success( 'AVENZA content is in place.' );
		}
	);
}
