<?php
/**
 * WooCommerce: a 3D model per product, shown in a popup from a "View in 3D" button under the product images,
 * or in the product gallery; a 3D button on shop pages; material variants that follow the chosen variation.
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
		'url'      => $url,
		'poster'   => $poster ? $poster : '',
		'ar'       => 'no' !== $product->get_meta( '_softicon_model_viewer_ar' ),
		'alt'      => $product->get_name(),
		'hotspots' => softicon_model_viewer_wc_hotspots( $product ),
		'labels'   => 'no' !== $product->get_meta( '_softicon_model_viewer_hotspot_labels' ),
		'variants' => softicon_model_viewer_model_variants( $model_id ),
	);
}

/**
 * The material variants saved in a model (glTF's KHR_materials_variants), such as "Oak" and "Walnut".
 *
 * Read once from the file and kept with the attachment until the file changes.
 *
 * @param int $attachment_id The model's attachment ID.
 * @return string[]
 */
function softicon_model_viewer_model_variants( $attachment_id ) {
	$file = $attachment_id ? get_attached_file( $attachment_id ) : '';
	if ( ! $file || ! is_readable( $file ) ) {
		return array();
	}

	$stamp = filemtime( $file ) . ':' . filesize( $file );
	$saved = get_post_meta( $attachment_id, '_softicon_model_viewer_variants', true );
	if ( is_array( $saved ) && isset( $saved['stamp'], $saved['names'] ) && $saved['stamp'] === $stamp && is_array( $saved['names'] ) ) {
		return $saved['names'];
	}

	// kept before reading too, so a file that cannot be read is not tried again on every page
	update_post_meta(
		$attachment_id,
		'_softicon_model_viewer_variants',
		array(
			'stamp' => $stamp,
			'names' => array(),
		)
	);
	$names = softicon_model_viewer_read_variants( $file );
	update_post_meta(
		$attachment_id,
		'_softicon_model_viewer_variants',
		array(
			'stamp' => $stamp,
			'names' => $names,
		)
	);

	return $names;
}

/**
 * Reads the variant names from a GLB or glTF file: only its JSON part, never the meshes or textures.
 *
 * @param string $file Full path to the model.
 * @return string[]
 */
function softicon_model_viewer_read_variants( $file ) {
	$max = 4 * MB_IN_BYTES;
	// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local file in the uploads folder.
	$head = file_get_contents( $file, false, null, 0, 20 );
	if ( false === $head || strlen( $head ) < 20 ) {
		return array();
	}

	if ( 'glTF' === substr( $head, 0, 4 ) ) {
		// GLB: a 12-byte header, then the JSON chunk's length and type ("JSON")
		$chunk = unpack( 'Vlength/Vtype', substr( $head, 12, 8 ) );
		if ( 0x4E4F534A !== $chunk['type'] || $chunk['length'] < 2 || $chunk['length'] > $max ) {
			return array();
		}
		$json = file_get_contents( $file, false, null, 20, $chunk['length'] );
	} else {
		$json = filesize( $file ) <= $max ? file_get_contents( $file ) : '';
	}
	// phpcs:enable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	$data     = json_decode( (string) $json, true, 128 );
	$variants = isset( $data['extensions']['KHR_materials_variants']['variants'] ) ? $data['extensions']['KHR_materials_variants']['variants'] : array();
	$names    = array();
	foreach ( is_array( $variants ) ? $variants : array() as $variant ) {
		if ( is_array( $variant ) && isset( $variant['name'] ) && is_string( $variant['name'] ) && '' !== trim( $variant['name'] ) ) {
			$names[] = sanitize_text_field( $variant['name'] );
		}
	}

	return array_slice( array_values( array_unique( $names ) ), 0, 100 );
}

/**
 * Where the model shows on a product page: "popup" (a View in 3D button) or "gallery".
 *
 * @return string
 */
function softicon_model_viewer_wc_display() {
	return 'gallery' === get_option( 'softicon_model_viewer_wc_display', 'popup' ) ? 'gallery' : 'popup';
}

/**
 * Adds "3D models" under WooCommerce > Settings > Products.
 *
 * @param array $sections Sections of the Products tab.
 * @return array
 */
function softicon_model_viewer_wc_settings_section( $sections ) {
	$sections['softicon_model_viewer'] = __( '3D models', 'softicon-model-viewer' );
	return $sections;
}
add_filter( 'woocommerce_get_sections_products', 'softicon_model_viewer_wc_settings_section' );

/**
 * The settings of the "3D models" section; WooCommerce saves them.
 *
 * @param array  $settings Settings of the current section.
 * @param string $section  Current section.
 * @return array
 */
function softicon_model_viewer_wc_settings( $settings, $section ) {
	if ( 'softicon_model_viewer' !== $section ) {
		return $settings;
	}

	return array(
		array(
			'title' => __( '3D models', 'softicon-model-viewer' ),
			'type'  => 'title',
			'desc'  => __( 'How products with a 3D model show it. Add a model to a product in its 3D model tab.', 'softicon-model-viewer' ) . ' <a href="' . esc_url( SOFTICON_MODEL_VIEWER_WC_TUTORIAL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Watch the 2-minute tutorial', 'softicon-model-viewer' ) . '</a>',
			'id'    => 'softicon_model_viewer_wc',
		),
		array(
			'title'   => __( 'Product page', 'softicon-model-viewer' ),
			'id'      => 'softicon_model_viewer_wc_display',
			'type'    => 'radio',
			'default' => 'popup',
			'options' => array(
				'popup'   => __( 'A "View in 3D" button under the product images, which opens the model in a popup', 'softicon-model-viewer' ),
				'gallery' => __( 'In the product gallery, with a 3D thumbnail next to the photos', 'softicon-model-viewer' ),
			),
		),
		array(
			'title'   => __( 'Gallery', 'softicon-model-viewer' ),
			'desc'    => __( 'Show the 3D model first, before the photos', 'softicon-model-viewer' ),
			'id'      => 'softicon_model_viewer_wc_gallery_first',
			'type'    => 'checkbox',
			'default' => 'yes',
		),
		array(
			'title'    => __( 'Shop pages', 'softicon-model-viewer' ),
			'desc'     => __( 'Show a 3D button on products in the shop, categories and other product lists', 'softicon-model-viewer' ),
			'desc_tip' => __( 'It opens the model in a popup, without leaving the page. The model loads only when it is clicked.', 'softicon-model-viewer' ),
			'id'       => 'softicon_model_viewer_wc_shop_button',
			'type'     => 'checkbox',
			'default'  => 'no',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'softicon_model_viewer_wc',
		),
	);
}
add_filter( 'woocommerce_get_settings_products', 'softicon_model_viewer_wc_settings', 10, 2 );

/**
 * The "3D models" settings screen.
 *
 * @return string
 */
function softicon_model_viewer_wc_settings_url() {
	return admin_url( 'admin.php?page=wc-settings&tab=products&section=softicon_model_viewer' );
}

/**
 * A product's saved hotspots.
 *
 * @param WC_Product $product Product.
 * @return array[]
 */
function softicon_model_viewer_wc_hotspots( $product ) {
	$hotspots = $product->get_meta( '_softicon_model_viewer_hotspots' );
	return is_array( $hotspots ) ? $hotspots : array();
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
	$ar       = $product_object ? $product_object->get_meta( '_softicon_model_viewer_ar' ) : '';
	$hotspots = $product_object ? softicon_model_viewer_wc_hotspots( $product_object ) : array();
	$labels   = $product_object ? $product_object->get_meta( '_softicon_model_viewer_hotspot_labels' ) : '';
	$url      = $model && in_array( get_post_mime_type( $model ), SOFTICON_MODEL_VIEWER_WC_TYPES, true ) ? wp_get_attachment_url( $model ) : '';
	$variants = $url ? softicon_model_viewer_model_variants( $model ) : array();
	?>
	<div id="softicon_model_viewer_product_data" class="panel woocommerce_options_panel hidden">
		<div class="options_group">
			<?php
			softicon_model_viewer_wc_media_field( 'softicon_model_viewer_model', __( 'Model', 'softicon-model-viewer' ), $model, 'model', __( 'A GLB or glTF file. It then shows on the product page.', 'softicon-model-viewer' ) );
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
			<div class="form-field softicon-model-viewer-wc-hotspots" data-model="<?php echo esc_url( $url ); ?>">
				<label><?php esc_html_e( 'Hotspots', 'softicon-model-viewer' ); ?> <span class="softicon-model-viewer-wc-new"><?php esc_html_e( 'New', 'softicon-model-viewer' ); ?></span></label>
				<div class="softicon-model-viewer-wc-hotspots__body">
					<p class="softicon-model-viewer-wc-hotspots__none description"<?php echo $url ? ' hidden' : ''; ?>><?php esc_html_e( 'Choose a model first, then name the parts of your product on it.', 'softicon-model-viewer' ); ?></p>
					<div class="softicon-model-viewer-wc-hotspots__editor"<?php echo $url ? '' : ' hidden'; ?>>
						<p class="description"><?php esc_html_e( 'Click "Add hotspot", then click on the model where it goes. Shoppers see a numbered point that turns with the model; its text opens on click.', 'softicon-model-viewer' ); ?></p>
						<div class="softicon-model-viewer-wc-hotspots__stage wp-block-softicon-model-viewer">
							<?php // the model loads when the tab opens, see build/woocommerce/admin.js ?>
							<model-viewer camera-controls interaction-prompt="none" environment-image="neutral" shadow-intensity="1"></model-viewer>
							<div class="softicon-model-viewer-wc-hotspots__placing" hidden><?php esc_html_e( 'Click on the model where the hotspot goes.', 'softicon-model-viewer' ); ?> <button type="button" class="button-link"><?php esc_html_e( 'Cancel', 'softicon-model-viewer' ); ?></button></div>
						</div>
						<button type="button" class="button softicon-model-viewer-wc-hotspots__add"><?php esc_html_e( 'Add hotspot', 'softicon-model-viewer' ); ?></button>
						<ol class="softicon-model-viewer-wc-hotspots__list"></ol>
						<label class="softicon-model-viewer-wc-hotspots__labels"><input type="checkbox" name="softicon_model_viewer_hotspot_labels" value="yes"<?php checked( 'no' !== $labels ); ?> /> <?php esc_html_e( 'Always show labels', 'softicon-model-viewer' ); ?></label>
					</div>
					<input type="hidden" name="softicon_model_viewer_hotspots" value="<?php echo esc_attr( wp_json_encode( $hotspots ) ); ?>" />
				</div>
			</div>
			<div class="form-field softicon-model-viewer-wc-variants"<?php echo $variants ? '' : ' hidden'; ?>>
				<label><?php esc_html_e( 'Material variants', 'softicon-model-viewer' ); ?> <span class="softicon-model-viewer-wc-new"><?php esc_html_e( 'New', 'softicon-model-viewer' ); ?></span></label>
				<div>
					<p class="softicon-model-viewer-wc-variants__names"><?php echo esc_html( implode( ', ', $variants ) ); ?></p>
					<p class="description"><?php esc_html_e( 'On a variable product the model follows the options shoppers choose: a variant named like an option (for example "Oak") shows for it. To pick one yourself, open a variation under Variations.', 'softicon-model-viewer' ); ?></p>
				</div>
			</div>
			<p class="form-field">
				<label><?php esc_html_e( 'Where it shows', 'softicon-model-viewer' ); ?></label>
				<span>
					<?php echo esc_html( 'gallery' === softicon_model_viewer_wc_display() ? __( 'In the product gallery.', 'softicon-model-viewer' ) : __( 'A "View in 3D" button under the product images.', 'softicon-model-viewer' ) ); ?>
					<a href="<?php echo esc_url( softicon_model_viewer_wc_settings_url() ); ?>"><?php esc_html_e( 'Change for all products', 'softicon-model-viewer' ); ?></a>
				</span>
			</p>
			<p class="form-field">
				<label><?php esc_html_e( 'Help', 'softicon-model-viewer' ); ?></label>
				<a href="<?php echo esc_url( SOFTICON_MODEL_VIEWER_WC_TUTORIAL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Watch the WooCommerce tutorial (2 minutes)', 'softicon-model-viewer' ); ?></a>
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
	$labels = isset( $_POST['softicon_model_viewer_hotspot_labels'] ) ? 'yes' : 'no';
	// a JSON list from the hotspot editor; every field is checked below
	$hotspots = isset( $_POST['softicon_model_viewer_hotspots'] ) ? json_decode( wp_unslash( $_POST['softicon_model_viewer_hotspots'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Decoded JSON; each field is sanitized below.
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

	// hotspots sit on the model, so they go with it
	$clean = array();
	if ( $product->get_meta( '_softicon_model_viewer_model' ) ) {
		foreach ( softicon_model_viewer_hotspots_clean( $hotspots ) as $hotspot ) {
			$clean[] = array(
				'id'       => substr( md5( $hotspot['position'] ), 0, 12 ),
				'label'    => sanitize_text_field( $hotspot['label'] ),
				'text'     => sanitize_textarea_field( $hotspot['text'] ),
				'position' => $hotspot['position'],
				'normal'   => $hotspot['normal'],
			);
		}
	}
	if ( $clean ) {
		$product->update_meta_data( '_softicon_model_viewer_hotspots', $clean );
		$product->update_meta_data( '_softicon_model_viewer_hotspot_labels', $labels );
	} else {
		$product->delete_meta_data( '_softicon_model_viewer_hotspots' );
		$product->delete_meta_data( '_softicon_model_viewer_hotspot_labels' );
	}
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
	// the hotspot editor shows the model with the viewer and the block's hotspot style
	wp_enqueue_script( 'softicon-model-viewer-library' );
	wp_enqueue_style( 'softicon-model-viewer-style' );
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
 * The 3D model of a product with its "View in 3D" button and popup, printed once per page.
 *
 * On a product page in gallery mode the script moves the model into the gallery; when it finds no
 * gallery it can use, the button and popup stay. On shop pages it is a small 3D button on the product.
 *
 * @param int    $product_id Product ID.
 * @param string $place      "product" (after the gallery block), "classic" (classic themes; the script moves it under the images) or "shop".
 * @return string
 */
function softicon_model_viewer_wc_markup( $product_id, $place = 'product' ) {
	static $done = array();

	// a password-protected product keeps its model behind the password, in shop lists too
	$model = softicon_model_viewer_wc_model( $product_id );
	if ( ! $model || isset( $done[ $product_id ] ) || post_password_required( $product_id ) ) {
		return '';
	}
	$done[ $product_id ] = true;

	wp_enqueue_script( 'softicon-model-viewer-wc' );
	wp_enqueue_style( 'softicon-model-viewer-wc' );
	wp_enqueue_style( 'softicon-model-viewer-style' );

	$shop = 'shop' === $place;
	/* translators: %s: product name. */
	$label = sprintf( __( '3D view of %s', 'softicon-model-viewer' ), $model['alt'] );
	if ( $shop ) {
		$button = apply_filters( 'softicon_model_viewer_wc_shop_button_text', __( '3D', 'softicon-model-viewer' ), $product_id );
	} else {
		$button = apply_filters( 'softicon_model_viewer_wc_button_text', __( 'View in 3D', 'softicon-model-viewer' ), $product_id );
	}

	$data = array( 'data-softicon-model-viewer-wc' => '' );
	if ( 'classic' === $place ) {
		$data['data-under-gallery'] = '';
	}
	if ( ! $shop && 'gallery' === softicon_model_viewer_wc_display() ) {
		$data['data-display'] = 'gallery';
		if ( 'no' !== get_option( 'softicon_model_viewer_wc_gallery_first', 'yes' ) ) {
			$data['data-first'] = '';
		}
	}
	if ( ! $shop && $model['variants'] ) {
		$data['data-variants'] = wp_json_encode( $model['variants'] );
	}

	ob_start();
	?>
	<div class="softicon-model-viewer-wc<?php echo $shop ? ' softicon-model-viewer-wc--shop' : ''; ?>"<?php foreach ( $data as $name => $value ) { echo ' ' . esc_attr( $name ) . ( '' === $value ? '' : '="' . esc_attr( $value ) . '"' ); } ?>>
		<?php if ( $shop ) : ?>
			<?php /* translators: %s: product name. */ ?>
			<button type="button" class="softicon-model-viewer-wc__open softicon-model-viewer-wc__badge" aria-label="<?php echo esc_attr( sprintf( __( 'View %s in 3D', 'softicon-model-viewer' ), $model['alt'] ) ); ?>">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/></svg>
				<span aria-hidden="true"><?php echo esc_html( $button ); ?></span>
			</button>
		<?php else : ?>
			<button type="button" class="softicon-model-viewer-wc__open button wp-element-button">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/></svg>
				<?php echo esc_html( $button ); ?>
			</button>
		<?php endif; ?>
		<dialog class="softicon-model-viewer-wc__dialog" aria-label="<?php echo esc_attr( $label ); ?>">
			<button type="button" class="softicon-model-viewer-wc__close" aria-label="<?php esc_attr_e( 'Close', 'softicon-model-viewer' ); ?>">&times;</button>
			<figure class="wp-block-softicon-model-viewer">
				<model-viewer data-src="<?php echo esc_url( $model['url'] ); ?>" alt="<?php echo esc_attr( $model['alt'] ); ?>"<?php echo $model['poster'] ? ' poster="' . esc_url( $model['poster'] ) . '"' : ''; ?> camera-controls auto-rotate touch-action="pan-y" environment-image="neutral" shadow-intensity="1"<?php echo $model['ar'] ? ' ar ar-modes="webxr scene-viewer quick-look" ar-scale="fixed"' : ''; ?>>
					<?php echo softicon_model_viewer_hotspots_html( $model['hotspots'], $model['labels'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in softicon_model_viewer_hotspots_html(). ?>
					<?php if ( $model['ar'] ) : ?>
						<button type="button" slot="ar-button" class="softicon-model-viewer__ar">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/></svg>
							<?php esc_html_e( 'View in your room', 'softicon-model-viewer' ); ?>
						</button>
					<?php endif; ?>
				</model-viewer>
			</figure>
		</dialog>
		<?php if ( isset( $data['data-display'] ) ) : ?>
			<?php // the gallery's 3D thumbnail and the photos/3D switch; the script puts them in the gallery ?>
			<template class="softicon-model-viewer-wc__gallery-parts">
				<li class="softicon-model-viewer-wc__thumb-item"><button type="button" class="softicon-model-viewer-wc__thumb" aria-label="<?php echo esc_attr( $label ); ?>">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/></svg>
					<span aria-hidden="true">3D</span>
				</button></li>
				<button type="button" class="softicon-model-viewer-wc__switch" data-label-model="<?php esc_attr_e( 'View in 3D', 'softicon-model-viewer' ); ?>" data-label-photos="<?php esc_attr_e( 'Photos', 'softicon-model-viewer' ); ?>"></button>
			</template>
		<?php endif; ?>
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
	echo softicon_model_viewer_wc_markup( get_the_ID(), 'classic' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in softicon_model_viewer_wc_markup().
}
add_action( 'woocommerce_before_single_product_summary', 'softicon_model_viewer_wc_after_images', 25 );

/**
 * Whether products in the shop and other lists get a 3D button.
 *
 * @return bool
 */
function softicon_model_viewer_wc_shop_button() {
	return 'yes' === get_option( 'softicon_model_viewer_wc_shop_button', 'no' );
}

/**
 * Classic product lists: the 3D button after the product's link, before "Add to cart".
 */
function softicon_model_viewer_wc_shop_loop() {
	if ( softicon_model_viewer_wc_shop_button() ) {
		echo softicon_model_viewer_wc_markup( get_the_ID(), 'shop' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in softicon_model_viewer_wc_markup().
	}
}
add_action( 'woocommerce_after_shop_loop_item', 'softicon_model_viewer_wc_shop_loop', 7 );

/**
 * Block product lists (Product Collection, related products): the 3D button inside the product image, after its link.
 *
 * @param string   $content  Block HTML.
 * @param array    $block    Block data.
 * @param WP_Block $instance Block instance.
 * @return string
 */
function softicon_model_viewer_wc_shop_image_block( $content, $block, $instance ) {
	$product_id = isset( $instance->context['postId'] ) ? absint( $instance->context['postId'] ) : 0;
	if ( ! $product_id || ! softicon_model_viewer_wc_shop_button() || 'product' !== get_post_type( $product_id ) ) {
		return $content;
	}

	$markup = softicon_model_viewer_wc_markup( $product_id, 'shop' );
	$end    = strrpos( $content, '</div>' );
	if ( ! $markup || false === $end ) {
		return $content . $markup;
	}

	return substr( $content, 0, $end ) . $markup . substr( $content, $end );
}
add_filter( 'render_block_woocommerce/product-image', 'softicon_model_viewer_wc_shop_image_block', 10, 3 );

/**
 * The material variant field of a variation, when the product's model has variants.
 *
 * @param int     $loop           Position of the variation in the list.
 * @param array   $variation_data Variation data.
 * @param WP_Post $variation      Variation post.
 */
function softicon_model_viewer_wc_variation_field( $loop, $variation_data, $variation ) {
	$parent   = wc_get_product( $variation->post_parent );
	$model_id = $parent ? absint( $parent->get_meta( '_softicon_model_viewer_model' ) ) : 0;
	$variants = $model_id ? softicon_model_viewer_model_variants( $model_id ) : array();
	if ( ! $variants ) {
		return;
	}

	$options = array( '' => __( 'Automatic (a variant named like the option)', 'softicon-model-viewer' ) ) + array_combine( $variants, $variants );
	woocommerce_wp_select(
		array(
			'id'            => 'softicon_model_viewer_variant_' . $loop,
			'name'          => 'softicon_model_viewer_variant[' . $loop . ']',
			'label'         => __( '3D model variant', 'softicon-model-viewer' ),
			'desc_tip'      => true,
			'description'   => __( 'The material variant of the 3D model that shows when shoppers choose this variation.', 'softicon-model-viewer' ),
			'value'         => (string) get_post_meta( $variation->ID, '_softicon_model_viewer_variant', true ),
			'options'       => $options,
			'wrapper_class' => 'form-row form-row-full',
		)
	);
}
add_action( 'woocommerce_product_after_variable_attributes', 'softicon_model_viewer_wc_variation_field', 10, 3 );

/**
 * Saves a variation's material variant.
 *
 * WooCommerce runs this only after it has checked its nonce and the user's right to edit products.
 *
 * @param int $variation_id Variation ID.
 * @param int $i            Position of the variation in the submitted list.
 */
function softicon_model_viewer_wc_save_variation( $variation_id, $i ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by WooCommerce before this hook runs.
	$value = isset( $_POST['softicon_model_viewer_variant'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['softicon_model_viewer_variant'][ $i ] ) ) : '';
	if ( '' === $value ) {
		delete_post_meta( $variation_id, '_softicon_model_viewer_variant' );
	} else {
		update_post_meta( $variation_id, '_softicon_model_viewer_variant', $value );
	}
}
add_action( 'woocommerce_save_product_variation', 'softicon_model_viewer_wc_save_variation', 10, 2 );

/**
 * Gives the product page script a variation's material variant.
 *
 * @param array                $data      Variation data for the add to cart form.
 * @param WC_Product           $product   Variable product.
 * @param WC_Product_Variation $variation Variation.
 * @return array
 */
function softicon_model_viewer_wc_variation_data( $data, $product, $variation ) {
	$variant = $variation->get_meta( '_softicon_model_viewer_variant' );
	if ( $variant ) {
		$data['softicon_model_viewer_variant'] = (string) $variant;
	}
	return $data;
}
add_filter( 'woocommerce_available_variation', 'softicon_model_viewer_wc_variation_data', 10, 3 );
