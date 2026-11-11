<?php
/**
 * 3D models for the Classic Editor and page builders.
 *
 * Model Viewer → All 3D Models holds one 3D Model Viewer block per model, set up in the block editor, and
 * [softicon_model_viewer id="123"] shows it anywhere a shortcode works: the Classic Editor, Elementor, Divi, widgets.
 *
 * @package SofticonModelViewer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// the post type of a saved model (at most 20 characters)
const SOFTICON_MODEL_VIEWER_TYPE = 'softicon_3d_model';

// the shortcode's name
const SOFTICON_MODEL_VIEWER_SHORTCODE = 'softicon_model_viewer';

/**
 * Registers the post type. A model is not a page of its own on the site: it shows only where its shortcode is.
 */
function softicon_model_viewer_post_type() {
	register_post_type(
		SOFTICON_MODEL_VIEWER_TYPE,
		array(
			'labels'          => array(
				'name'               => __( '3D Models', 'softicon-model-viewer' ),
				'singular_name'      => __( '3D Model', 'softicon-model-viewer' ),
				'add_new'            => __( 'Add a 3D model', 'softicon-model-viewer' ),
				'add_new_item'       => __( 'Add a 3D model', 'softicon-model-viewer' ),
				'edit_item'          => __( 'Edit 3D model', 'softicon-model-viewer' ),
				'all_items'          => __( 'All 3D Models', 'softicon-model-viewer' ),
				'not_found'          => __( 'No 3D models yet.', 'softicon-model-viewer' ),
				'not_found_in_trash' => __( 'No 3D models in the trash.', 'softicon-model-viewer' ),
				'search_items'       => __( 'Search 3D models', 'softicon-model-viewer' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => SOFTICON_MODEL_VIEWER_DASHBOARD,
			'show_in_rest'    => true,
			'capability_type' => 'page',
			'map_meta_cap'    => true,
			'supports'        => array( 'title', 'editor' ),
			'template'        => array( array( 'softicon/model-viewer' ) ),
			'template_lock'   => 'all',
		)
	);
}
add_action( 'init', 'softicon_model_viewer_post_type' );

/**
 * A model is set up in its block, so it always opens in the block editor, even where the Classic Editor
 * plugin (priority 100) switches every other post type to the classic one.
 *
 * @param bool   $use  Whether the block editor is used.
 * @param string $type Post type.
 * @return bool
 */
function softicon_model_viewer_use_block_editor( $use, $type ) {
	return SOFTICON_MODEL_VIEWER_TYPE === $type ? true : $use;
}
add_filter( 'use_block_editor_for_post_type', 'softicon_model_viewer_use_block_editor', 200, 2 );

/**
 * The shortcode: the block of a published model.
 *
 * @param array|string $atts Shortcode attributes; only id.
 * @return string
 */
function softicon_model_viewer_shortcode( $atts ) {
	$atts  = shortcode_atts( array( 'id' => 0 ), $atts, SOFTICON_MODEL_VIEWER_SHORTCODE );
	$id    = absint( $atts['id'] );
	$model = $id ? get_post( $id ) : null;

	// only a published model, never another post or a draft
	if ( ! $model || SOFTICON_MODEL_VIEWER_TYPE !== $model->post_type || 'publish' !== $model->post_status ) {
		return '';
	}

	$html = '';
	foreach ( parse_blocks( $model->post_content ) as $block ) {
		if ( 'softicon/model-viewer' === $block['blockName'] ) {
			$html .= render_block( $block );
		}
	}
	return $html;
}
add_shortcode( SOFTICON_MODEL_VIEWER_SHORTCODE, 'softicon_model_viewer_shortcode' );

/**
 * The shortcode of a model.
 *
 * @param int $id Post ID.
 * @return string
 */
function softicon_model_viewer_shortcode_text( $id ) {
	return '[' . SOFTICON_MODEL_VIEWER_SHORTCODE . ' id="' . absint( $id ) . '"]';
}

/**
 * Adds a Shortcode column to All 3D Models, before the date.
 *
 * @param string[] $columns Column titles keyed by name.
 * @return string[]
 */
function softicon_model_viewer_shortcode_columns( $columns ) {
	$date = isset( $columns['date'] ) ? array( 'date' => $columns['date'] ) : array();
	unset( $columns['date'] );
	return $columns + array( 'smv_shortcode' => __( 'Shortcode', 'softicon-model-viewer' ) ) + $date;
}
add_filter( 'manage_' . SOFTICON_MODEL_VIEWER_TYPE . '_posts_columns', 'softicon_model_viewer_shortcode_columns' );

/**
 * Prints a model's shortcode in its row; a click copies it.
 *
 * @param string $column Column name.
 * @param int    $id     Post ID.
 */
function softicon_model_viewer_shortcode_column( $column, $id ) {
	if ( 'smv_shortcode' === $column ) {
		printf(
			'<input type="text" class="smv-shortcode" readonly value="%s" aria-label="%s" title="%s">',
			esc_attr( softicon_model_viewer_shortcode_text( $id ) ),
			esc_attr__( 'Shortcode', 'softicon-model-viewer' ),
			esc_attr__( 'Click to copy', 'softicon-model-viewer' )
		);
	}
}
add_action( 'manage_' . SOFTICON_MODEL_VIEWER_TYPE . '_posts_custom_column', 'softicon_model_viewer_shortcode_column', 10, 2 );

/**
 * The copy on click in All 3D Models.
 *
 * @param string $hook_suffix The current admin page.
 */
function softicon_model_viewer_shortcode_copy( $hook_suffix ) {
	$screen = get_current_screen();
	if ( 'edit.php' !== $hook_suffix || ! $screen || SOFTICON_MODEL_VIEWER_TYPE !== $screen->post_type ) {
		return;
	}
	wp_add_inline_style( 'common', '.column-smv_shortcode input{width:100%;max-width:280px;font-family:Menlo,Consolas,monospace;font-size:12px;cursor:copy}' );
	wp_add_inline_script( 'common', 'document.addEventListener("click",function(e){var i=e.target.closest&&e.target.closest(".smv-shortcode");if(!i){return;}i.select();var done=function(){i.title=' . wp_json_encode( __( 'Copied', 'softicon-model-viewer' ) ) . ';};if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(i.value).then(done);}else if(document.execCommand("copy")){done();}});' );
}
add_action( 'admin_enqueue_scripts', 'softicon_model_viewer_shortcode_copy' );

/**
 * Elementor's editor preview: the viewer library and the block's styles from the start, so a Shortcode
 * widget draws its model at once (model-viewer upgrades every element added later by itself).
 */
function softicon_model_viewer_builder_preview() {
	wp_enqueue_script( 'softicon-model-viewer-library' );
	wp_enqueue_style( generate_block_asset_handle( 'softicon/model-viewer', 'style' ) );
}
add_action( 'elementor/preview/enqueue_styles', 'softicon_model_viewer_builder_preview' );
