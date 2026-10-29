<?php
/**
 * Plugin Name:       Softicon Model Viewer – Interactive 3D & AR Product Viewer
 * Description:       A block that shows an interactive 3D model (GLB or glTF). Visitors can rotate and zoom it, and place it in their own room with augmented reality on phones.
 * Version:           1.1.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            softiconai
 * Author URI:        https://profiles.wordpress.org/softiconai/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       softicon-model-viewer
 *
 * @package SofticonModelViewer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the bundled model-viewer library and the block.
 */
function softicon_model_viewer_register_block() {
	wp_register_script(
		'softicon-model-viewer-library',
		plugins_url( 'vendor/model-viewer/model-viewer.min.js', __FILE__ ),
		array(),
		'4.4.0',
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'softicon_model_viewer_register_block' );

/**
 * Turns off the Settings and Styles tabs WordPress adds above the block's own General and Style tabs.
 *
 * @param array $settings Block editor settings.
 * @return array
 */
function softicon_model_viewer_no_inspector_tabs( $settings ) {
	$tabs = isset( $settings['blockInspectorTabs'] ) && is_array( $settings['blockInspectorTabs'] ) ? $settings['blockInspectorTabs'] : array();

	$tabs['softicon/model-viewer']  = false;
	$settings['blockInspectorTabs'] = $tabs;

	return $settings;
}
add_filter( 'block_editor_settings_all', 'softicon_model_viewer_no_inspector_tabs' );

/**
 * Allows GLB and glTF files in the media library, so models can be picked in the block.
 *
 * WordPress blocks these file types by default and the REST API cannot change that,
 * so this small filter is the only way to let editors upload their models.
 *
 * @param array $mimes Allowed mime types keyed by file extension.
 * @return array
 */
function softicon_model_viewer_upload_mimes( $mimes ) {
	if ( current_user_can( 'upload_files' ) ) {
		$mimes['glb']  = 'model/gltf-binary';
		$mimes['gltf'] = 'model/gltf+json';
	}

	return $mimes;
}
add_filter( 'upload_mimes', 'softicon_model_viewer_upload_mimes' );

/**
 * Confirms the type of GLB and glTF uploads, which PHP often reports as a generic binary or text file.
 *
 * @param array        $data      File data: ext, type and proper_filename.
 * @param string       $file      Full path to the uploaded file.
 * @param string       $filename  Name of the uploaded file.
 * @param string[]|null $mimes    Allowed mime types keyed by extension.
 * @return array
 */
function softicon_model_viewer_check_filetype( $data, $file, $filename, $mimes ) {
	if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
		return $data;
	}

	$types = array(
		'glb'  => 'model/gltf-binary',
		'gltf' => 'model/gltf+json',
	);
	$ext   = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

	if ( isset( $types[ $ext ] ) && current_user_can( 'upload_files' ) ) {
		$data['ext']  = $ext;
		$data['type'] = $types[ $ext ];
	}

	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'softicon_model_viewer_check_filetype', 10, 4 );

/**
 * Adds a video tutorial and an email support link under the plugin on the Plugins screen.
 *
 * @param string[] $links Plugin row meta links.
 * @param string   $file  Plugin file path relative to the plugins folder.
 * @return string[]
 */
function softicon_model_viewer_row_meta( $links, $file ) {
	if ( plugin_basename( __FILE__ ) !== $file ) {
		return $links;
	}

	$links[] = sprintf(
		'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
		esc_url( 'https://www.youtube.com/watch?v=zlLBBaGMtHE' ),
		esc_html__( 'Video tutorial', 'softicon-model-viewer' )
	);
	$links[] = sprintf(
		'<a href="%s">%s</a>',
		esc_url( 'mailto:softiconai@gmail.com' ),
		esc_html__( 'Email support', 'softicon-model-viewer' )
	);

	return $links;
}
add_filter( 'plugin_row_meta', 'softicon_model_viewer_row_meta', 10, 2 );
