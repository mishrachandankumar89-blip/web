<?php
/**
 * AVENZA theme bootstrap.
 *
 * @package avenza
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AVENZA_VERSION', '1.5.0' );
define( 'AVENZA_DIR', get_template_directory() );
define( 'AVENZA_URI', get_template_directory_uri() );

require AVENZA_DIR . '/inc/helpers.php';
require AVENZA_DIR . '/inc/setup.php';
require AVENZA_DIR . '/inc/customizer.php';
require AVENZA_DIR . '/inc/content-types.php';
require AVENZA_DIR . '/inc/navigation.php';
require AVENZA_DIR . '/inc/template-tags.php';
require AVENZA_DIR . '/inc/shortcodes.php';
require AVENZA_DIR . '/inc/contact-form.php';
require AVENZA_DIR . '/inc/seo.php';
require AVENZA_DIR . '/inc/schema.php';
require AVENZA_DIR . '/inc/geo.php';
require AVENZA_DIR . '/inc/importer.php';
