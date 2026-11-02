<?php
/**
 * WooCommerce: a 3D model per product, shown in a popup from a "View in 3D" button under the product images.
 *
 * @package SofticonModelViewer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mime types a product's model may have.
 */
const SOFTICON_MODEL_VIEWER_WC_TYPES = array( 'model/gltf-binary', 'model/gltf+json' );

/**
 * The 3D settings of a product, or null when it has no usable model.
 *
 * @param int $product_id Product ID.
 * @return array|null url, poster, ar.
 */
function softicon_model_viewer_wc_model( $product_id ) {
	$product = $product_id ? wc_get_product( $product_id ) : null;
	if ( ! $product ) {
		return null;
	}

	$model_id = absint( $product->get_meta( '_softicon_model_viewer_model' ) );
	$url      = $model_id && in_array( get_post_mime_type( $model_id ), SOFTICON_MODEL_VIEWER_WC_TYPES, true ) ? wp_get_attachment_url( $model_id ) : '';
	if ( ! $url ) {
		return null;
	}

	$poster_id = absint( $product->get_meta( '_softicon_model_viewer_poster' ) );
	$poster    = $poster_id && wp_attachment_is_image( $poster_id ) ? wp_get_attachment_image_url( $poster_id, 'large' ) : '';

	return array(
		'url'    => $url,
		'poster' => $poster ? $poster : '',
		'ar'     => 'no' !== $product->get_meta( '_softicon_model_viewer_ar' ),
		'alt'    => $product->get_name(),
	);
}

/**
 * Adds the "3D model" tab to the product data box.
 *
 * @param array $tabs Product data tabs.
 * @return array
 */
function softicon_model_viewer_wc_tab( $tabs ) {
	$tabs['softicon_model_viewer'] = array(
		'label'    => __( '3D model', 'softicon-model-viewer' ),
		'target'   => 'softicon_model_viewer_product_data',
		'class'    => array(),
		'priority' => 65,
	);

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'softicon_model_viewer_wc_tab' );

/**
 * Prints one media field (model or poster) of the "3D model" tab.
 *
 * @param string $name     Field name.
 * @param string $label    Field label.
 * @param int    $id       Selected attachment ID.
 * @param string $type     "model" or "image".
 * @param string $help     Help text under the field.
 */
function softicon_model_viewer_wc_media_field( $name, $label, $id, $type, $help ) {
	$url     = $id ? wp_get_attachment_url( $id ) : '';
	$choose  = 'image' === $type ? __( 'Choose image', 'softicon-model-viewer' ) : __( 'Choose model', 'softicon-model-viewer' );
	$replace = 'image' === $type ? __( 'Replace image', 'softicon-model-viewer' ) : __( 'Replace model', 'softicon-model-viewer' );
	?>
	<p class="form-field softicon-model-viewer-wc-media" data-type="<?php echo esc_attr( $type ); ?>">
		<label><?php echo esc_html( $label ); ?></label>
		<span class="softicon-model-viewer-wc-media__field">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo $url ? esc_attr( $id ) : ''; ?>" />
			<span class="softicon-model-viewer-wc-media__preview"><?php
			if ( $url && 'image' === $type ) {
				echo wp_get_attachment_image( $id, 'thumbnail' );
			} elseif ( $url ) {
				echo esc_html( wp_basename( $url ) );
			}
			?></span>
			<button type="button" class="button softicon-model-viewer-wc-media__choose" data-choose="<?php echo esc_attr( $choose ); ?>" data-replace="<?php echo esc_attr( $replace ); ?>"><?php echo esc_html( $url ? $replace : $choose ); ?></button>
			<button type="button" class="button-link button-link-delete softicon-model-viewer-wc-media__remove"<?php echo $url ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'softicon-model-viewer' ); ?></button>
		</span>
		<span class="description"><?php echo esc_html( $help ); ?></span>
	</p>
	<?php
}

/**
 * Prints the "3D model" tab.
 */
function softicon_model_viewer_wc_panel() {
	global $product_object;

	$model  = $product_object ? absint( $product_object->get_meta( '_softicon_model_viewer_model' ) ) : 0;
	$poster = $product_object ? absint( $product_object->get_meta( '_softicon_model_viewer_poster' ) ) : 0;
	$ar     = $product_object ? $product_object->get_meta( '_softicon_model_viewer_ar' ) : '';
	?>
	<div id="softicon_model_viewer_product_data" class="panel woocommerce_options_panel hidden">
		<div class="options_group">
			<?php
			softicon_model_viewer_wc_media_field( 'softicon_model_viewer_model', __( 'Model', 'softicon-model-viewer' ), $model, 'model', __( 'A GLB or glTF file. A "View in 3D" button then shows under the product images.', 'softicon-model-viewer' ) );
			softicon_model_viewer_wc_media_field( 'softicon_model_viewer_poster', __( 'Poster image', 'softicon-model-viewer' ), $poster, 'image', __( 'Optional. Shown while the model loads.', 'softicon-model-viewer' ) );

			woocommerce_wp_checkbox(
				array(
					'id'          => 'softicon_model_viewer_ar',
					'label'       => __( 'View in your room', 'softicon-model-viewer' ),
					'description' => __( 'Show the augmented reality button on phones and tablets that support it.', 'softicon-model-viewer' ),
					'value'       => 'no' === $ar ? 'no' : 'yes',
				)
			);
			?>
			<p class="form-field">
				<label><?php esc_html_e( 'Help', 'softicon-model-viewer' ); ?></label>
				<?php // the tutorial opens at its WooCommerce part ?>
				<a href="<?php echo esc_url( SOFTICON_MODEL_VIEWER_TUTORIAL . '&t=16s' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Watch the video tutorial', 'softicon-model-viewer' ); ?></a>
			</p>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_product_data_panels', 'softicon_model_viewer_wc_panel' );

/**
 * Saves the "3D model" tab with the product.
 *
 * WooCommerce runs this only after it has checked its nonce and the user's right to edit the product.
 *
 * @param WC_Product $product Product being saved.
 */
function softicon_model_viewer_wc_save( $product ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by WooCommerce before this hook runs.
	$model  = isset( $_POST['softicon_model_viewer_model'] ) ? absint( wp_unslash( $_POST['softicon_model_viewer_model'] ) ) : 0;
	$poster = isset( $_POST['softicon_model_viewer_poster'] ) ? absint( wp_unslash( $_POST['softicon_model_viewer_poster'] ) ) : 0;
	$ar     = isset( $_POST['softicon_model_viewer_ar'] ) ? 'yes' : 'no';
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( $model && in_array( get_post_mime_type( $model ), SOFTICON_MODEL_VIEWER_WC_TYPES, true ) ) {
		$product->update_meta_data( '_softicon_model_viewer_model', $model );
	} else {
		$product->delete_meta_data( '_softicon_model_viewer_model' );
	}

	if ( $poster && wp_attachment_is_image( $poster ) ) {
		$product->update_meta_data( '_softicon_model_viewer_poster', $poster );
	} else {
		$product->delete_meta_data( '_softicon_model_viewer_poster' );
	}

	$product->update_meta_data( '_softicon_model_viewer_ar', $ar );
}
add_action( 'woocommerce_admin_process_product_object', 'softicon_model_viewer_wc_save' );

/**
 * Loads the media picker for the "3D model" tab on the product screen.
 *
 * @param string $hook_suffix The current admin page.
 */
function softicon_model_viewer_wc_admin_assets( $hook_suffix ) {
	$screen = get_current_screen();
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) || ! $screen || 'product' !== $screen->post_type ) {
		return;
	}

	$asset = include __DIR__ . '/../build/woocommerce/admin.asset.php';

	wp_enqueue_media();
	wp_enqueue_script( 'softicon-model-viewer-wc-admin', plugins_url( 'build/woocommerce/admin.js', __DIR__ ), $asset['dependencies'], $asset['version'], true );
	wp_enqueue_style( 'softicon-model-viewer-wc-admin', plugins_url( 'build/woocommerce/admin.css', __DIR__ ), array(), $asset['version'] );
	wp_set_script_translations( 'softicon-model-viewer-wc-admin', 'softicon-model-viewer', dirname( __DIR__ ) . '/languages' );
}
add_action( 'admin_enqueue_scripts', 'softicon_model_viewer_wc_admin_assets' );

/**
 * Registers the product page script and style.
 */
function softicon_model_viewer_wc_register_assets() {
	$asset = include __DIR__ . '/../build/woocommerce/view.asset.php';

	wp_register_script(
		'softicon-model-viewer-wc',
		plugins_url( 'build/woocommerce/view.js', __DIR__ ),
		array_merge( $asset['dependencies'], array( 'softicon-model-viewer-library' ) ),
		$asset['version'],
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	wp_register_style( 'softicon-model-viewer-wc', plugins_url( 'build/woocommerce/view.css', __DIR__ ), array(), $asset['version'] );
}
// block themes render the product template before wp_head, so the handles must exist by then
add_action( 'init', 'softicon_model_viewer_wc_register_assets' );

/**
 * The "View in 3D" button and its popup for a product, printed once per page.
 *
 * @param int  $product_id    Product ID.
 * @param bool $under_gallery Whether the script should move it under the classic product images.
 * @return string
 */
function softicon_model_viewer_wc_markup( $product_id, $under_gallery = false ) {
	static $done = array();

	$model = softicon_model_viewer_wc_model( $product_id );
	if ( ! $model || isset( $done[ $product_id ] ) ) {
		return '';
	}
	$done[ $product_id ] = true;

	wp_enqueue_script( 'softicon-model-viewer-wc' );
	wp_enqueue_style( 'softicon-model-viewer-wc' );
	wp_enqueue_style( 'softicon-model-viewer-style' );

	/* translators: %s: product name. */
	$label  = sprintf( __( '3D view of %s', 'softicon-model-viewer' ), $model['alt'] );
	$button = apply_filters( 'softicon_model_viewer_wc_button_text', __( 'View in 3D', 'softicon-model-viewer' ), $product_id );

	ob_start();
	?>
	<div class="softicon-model-viewer-wc" data-softicon-model-viewer-wc<?php echo $under_gallery ? ' data-under-gallery' : ''; ?>>
		<button type="button" class="softicon-model-viewer-wc__open button wp-element-button">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/></svg>
			<?php echo esc_html( $button ); ?>
		</button>
		<dialog class="softicon-model-viewer-wc__dialog" aria-label="<?php echo esc_attr( $label ); ?>">
			<button type="button" class="softicon-model-viewer-wc__close" aria-label="<?php esc_attr_e( 'Close', 'softicon-model-viewer' ); ?>">&times;</button>
			<figure class="wp-block-softicon-model-viewer">
				<model-viewer data-src="<?php echo esc_url( $model['url'] ); ?>" alt="<?php echo esc_attr( $model['alt'] ); ?>"<?php echo $model['poster'] ? ' poster="' . esc_url( $model['poster'] ) . '"' : ''; ?> camera-controls auto-rotate touch-action="pan-y" environment-image="neutral" shadow-intensity="1"<?php echo $model['ar'] ? ' ar ar-modes="webxr scene-viewer quick-look" ar-scale="fixed"' : ''; ?>>
					<?php if ( $model['ar'] ) : ?>
						<button type="button" slot="ar-button" class="softicon-model-viewer__ar">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/></svg>
							<?php esc_html_e( 'View in your room', 'softicon-model-viewer' ); ?>
						</button>
					<?php endif; ?>
				</model-viewer>
			</figure>
		</dialog>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Block themes: adds the button under the product image gallery block.
 *
 * @param string   $content  Block HTML.
 * @param array    $block    Block data.
 * @param WP_Block $instance Block instance.
 * @return string
 */
function softicon_model_viewer_wc_after_gallery_block( $content, $block, $instance ) {
	$product_id = isset( $instance->context['postId'] ) ? absint( $instance->context['postId'] ) : get_the_ID();
	if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
		return $content;
	}

	return $content . softicon_model_viewer_wc_markup( $product_id );
}
add_filter( 'render_block_woocommerce/product-image-gallery', 'softicon_model_viewer_wc_after_gallery_block', 10, 3 );
add_filter( 'render_block_woocommerce/product-gallery', 'softicon_model_viewer_wc_after_gallery_block', 10, 3 );

/**
 * Classic themes: prints the button after the product images; the script moves it under them.
 */
function softicon_model_viewer_wc_after_images() {
	echo softicon_model_viewer_wc_markup( get_the_ID(), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in softicon_model_viewer_wc_markup().
}
add_action( 'woocommerce_before_single_product_summary', 'softicon_model_viewer_wc_after_images', 25 );
