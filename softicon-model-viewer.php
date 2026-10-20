<?php
/**
 * Plugin Name:       Softicon Model Viewer
 * Description:       A block that shows an interactive 3D model (GLB or glTF). Visitors can rotate and zoom it, and place it in their own room with augmented reality on phones.
 * Version:           1.0.1
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

	register_block_type( __DIR__ . '/block' );
}
add_action( 'init', 'softicon_model_viewer_register_block' );

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
