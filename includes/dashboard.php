<?php
/**
 * The plugin's dashboard: a menu page that sets things up from what the site already has.
 *
 * Welcome shows the Freemius opt-in card while the choice is open (from vendor/fs-lite), a setup checklist that
 * ticks itself, the 3D content on the site and a review request once there is some. Help has the videos, answers
 * and support. Nothing on the page is loaded from another server.
 *
 * @package SofticonModelViewer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// fs-lite opens this page once after activation, see the 'page' in softicon-model-viewer.php
const SOFTICON_MODEL_VIEWER_DASHBOARD = 'softicon-model-viewer';

// the menu icon: the block's cube (WordPress recolours an SVG given this way)
const SOFTICON_MODEL_VIEWER_MENU_ICON = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZmlsbD0iYmxhY2siIGQ9Ik0xMiAyLjVsOC41IDQuN3Y5LjZMMTIgMjEuNWwtOC41LTQuN1Y3LjJMMTIgMi41em0wIDEuN0w1LjYgNy43IDEyIDExLjJsNi40LTMuNUwxMiA0LjJ6TTUgOXY2LjlsNi4yNSAzLjQ1di02LjlMNSA5em04Ljc1IDEwLjM1TDIwIDE1LjlWOWwtNi4yNSAzLjQ1djYuOXoiLz48L3N2Zz4=';

/**
 * Adds the dashboard to the admin menu.
 */
function softicon_model_viewer_dashboard_menu() {
	$hook = add_menu_page(
		__( 'Softicon Model Viewer', 'softicon-model-viewer' ),
		__( 'Model Viewer', 'softicon-model-viewer' ),
		'manage_options',
		SOFTICON_MODEL_VIEWER_DASHBOARD,
		'softicon_model_viewer_dashboard_page',
		SOFTICON_MODEL_VIEWER_MENU_ICON,
		58
	);
	// with All 3D Models under it (includes/shortcode.php) the menu needs its own first item
	add_submenu_page( SOFTICON_MODEL_VIEWER_DASHBOARD, __( 'Softicon Model Viewer', 'softicon-model-viewer' ), __( 'Dashboard', 'softicon-model-viewer' ), 'manage_options', SOFTICON_MODEL_VIEWER_DASHBOARD, 'softicon_model_viewer_dashboard_page' );
	if ( $hook ) {
		add_action( 'load-' . $hook, 'softicon_model_viewer_dashboard_load' );
	}
}
// before WordPress adds All 3D Models (priority 10), so Dashboard stays the first item and the menu's own link
add_action( 'admin_menu', 'softicon_model_viewer_dashboard_menu', 9 );

/**
 * The dashboard is opening: its style and scripts go in.
 */
function softicon_model_viewer_dashboard_load() {
	add_action( 'admin_enqueue_scripts', 'softicon_model_viewer_dashboard_assets' );
}

/**
 * The dashboard's style, the opt-in card's style and script, and the review buttons.
 */
function softicon_model_viewer_dashboard_assets() {
	$version = static function ( $file ) {
		return (string) filemtime( dirname( __DIR__ ) . '/' . $file );
	};
	wp_enqueue_style( 'softicon-model-viewer-fs-lite', plugins_url( 'vendor/fs-lite/assets/fs-lite.css', __DIR__ ), array(), $version( 'vendor/fs-lite/assets/fs-lite.css' ) );
	wp_enqueue_style( 'softicon-model-viewer-dashboard', plugins_url( 'assets/css/dashboard.css', __DIR__ ), array(), $version( 'assets/css/dashboard.css' ) );
	wp_enqueue_script( 'softicon-model-viewer-opt-in', plugins_url( 'assets/js/opt-in.js', __DIR__ ), array(), $version( 'assets/js/opt-in.js' ), true );
	wp_enqueue_script( 'softicon-model-viewer-dashboard', plugins_url( 'assets/js/dashboard.js', __DIR__ ), array(), $version( 'assets/js/dashboard.js' ), true );
}

/**
 * The URL of a dashboard tab.
 *
 * @param string $tab 'welcome' or 'help'.
 * @return string
 */
function softicon_model_viewer_dashboard_url( $tab = 'welcome' ) {
	$args = array( 'page' => SOFTICON_MODEL_VIEWER_DASHBOARD );
	if ( 'welcome' !== $tab ) {
		$args['tab'] = $tab;
	}
	return add_query_arg( $args, admin_url( 'admin.php' ) );
}

/**
 * A new page with an empty 3D Model Viewer block in it, ready to pick the model.
 *
 * @return string
 */
function softicon_model_viewer_new_page_url() {
	return add_query_arg(
		array(
			'post_type'             => 'page',
			'softicon_model_viewer' => 'block',
		),
		admin_url( 'post-new.php' )
	);
}

/**
 * Puts the block into a page opened from "New page with a 3D model".
 *
 * @param string  $content The new post's content.
 * @param WP_Post $post    The new post.
 * @return string
 */
function softicon_model_viewer_default_content( $content, $post ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only fills an empty new page, nothing is saved
	if ( '' === $content && isset( $_GET['softicon_model_viewer'] ) && 'block' === $_GET['softicon_model_viewer'] && 'page' === $post->post_type ) {
		return '<!-- wp:softicon/model-viewer /-->';
	}
	return $content;
}
add_filter( 'default_content', 'softicon_model_viewer_default_content', 10, 2 );

/**
 * What the site already has: models, pages and products with 3D, and their hotspots.
 *
 * @return array
 */
function softicon_model_viewer_dashboard_data() {
	global $wpdb;

	$counts = wp_count_attachments();
	$models = 0;
	foreach ( array( 'model/gltf-binary', 'model/gltf+json' ) as $mime ) {
		$models += isset( $counts->$mime ) ? (int) $counts->$mime : 0;
	}

	// posts and pages with the block; the newest first
	$like  = '%' . $wpdb->esc_like( '<!-- wp:softicon/model-viewer' ) . '%';
	$pages = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- a content search WP_Query cannot do; only on this page
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_type NOT IN ('revision', 'attachment', 'customize_changeset') AND post_status IN ('publish', 'draft', 'future', 'pending', 'private') ORDER BY post_modified DESC LIMIT 200",
			$like
		)
	);
	$hotspots = 0;
	foreach ( $pages as $id ) {
		foreach ( parse_blocks( (string) get_post_field( 'post_content', $id ) ) as $block ) {
			if ( 'softicon/model-viewer' === $block['blockName'] && ! empty( $block['attrs']['hotspots'] ) && is_array( $block['attrs']['hotspots'] ) ) {
				$hotspots += count( $block['attrs']['hotspots'] );
			}
		}
	}
	// a model saved for the shortcode counts its hotspots, but is not a page of its own
	$pages = array_values(
		array_filter(
			$pages,
			static function ( $id ) {
				return SOFTICON_MODEL_VIEWER_TYPE !== get_post_type( $id );
			}
		)
	);

	$woo      = class_exists( 'WooCommerce' );
	$products = array();
	if ( $woo ) {
		$products = get_posts(
			array(
				'post_type'   => 'product',
				'post_status' => array( 'publish', 'draft', 'future', 'pending', 'private' ),
				'meta_key'    => '_softicon_model_viewer_model', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- only on this page
				'orderby'     => 'modified',
				'numberposts' => 200,
				'fields'      => 'ids',
			)
		);
		foreach ( $products as $id ) {
			$saved     = get_post_meta( $id, '_softicon_model_viewer_hotspots', true );
			$hotspots += is_array( $saved ) ? count( $saved ) : 0;
		}
	}

	return array(
		'models'   => $models,
		'pages'    => array_map( 'absint', $pages ),
		'woo'      => $woo,
		'products' => array_map( 'absint', $products ),
		'hotspots' => $hotspots,
	);
}

/**
 * The setup checklist, ticked from what the site has.
 *
 * @param array $data From softicon_model_viewer_dashboard_data().
 * @return array[] Each with done, title, text and an action.
 */
function softicon_model_viewer_dashboard_steps( $data ) {
	$steps = array(
		array(
			'done'   => $data['models'] > 0,
			'title'  => __( 'Upload a 3D model', 'softicon-model-viewer' ),
			'text'   => __( 'A GLB or glTF file. The plugin lets the Media Library take them.', 'softicon-model-viewer' ),
			'action' => array( admin_url( 'media-new.php' ), __( 'Upload a model', 'softicon-model-viewer' ) ),
		),
		array(
			'done'   => count( $data['pages'] ) > 0,
			'title'  => __( 'Show it on a page', 'softicon-model-viewer' ),
			'text'   => __( 'Add the 3D Model Viewer block to any page or post.', 'softicon-model-viewer' ),
			'action' => array( softicon_model_viewer_new_page_url(), __( 'New page with a 3D model', 'softicon-model-viewer' ) ),
		),
	);
	if ( $data['woo'] ) {
		$steps[] = array(
			'done'   => count( $data['products'] ) > 0,
			'title'  => __( 'Add 3D to a product', 'softicon-model-viewer' ),
			'text'   => __( 'Open a product, then its 3D model tab in the Product data box.', 'softicon-model-viewer' ),
			'action' => array( admin_url( 'edit.php?post_type=product' ), __( 'Open products', 'softicon-model-viewer' ) ),
		);
	}
	$steps[] = array(
		'done'   => $data['hotspots'] > 0,
		'title'  => __( 'Point out its parts', 'softicon-model-viewer' ),
		'text'   => __( 'Add hotspots: numbered points with a label and a short text, right on the model.', 'softicon-model-viewer' ),
		'action' => array( softicon_model_viewer_dashboard_url( 'help' ) . '#smv-faq-hotspots', __( 'How hotspots work', 'softicon-model-viewer' ) ),
	);
	return $steps;
}

/**
 * Whether to ask for a review: only once the site shows something in 3D, and not after "I already did".
 *
 * @param array $data From softicon_model_viewer_dashboard_data().
 * @return bool
 */
function softicon_model_viewer_dashboard_ask_review( $data ) {
	if ( ! count( $data['pages'] ) && ! count( $data['products'] ) ) {
		return false;
	}
	$saved = get_user_meta( get_current_user_id(), 'softicon_model_viewer_review', true );
	return '' === $saved || ( is_numeric( $saved ) && time() >= (int) $saved );
}

/**
 * "Maybe later" asks again in two weeks; "Leave a review" and "I already did" stop asking.
 */
function softicon_model_viewer_review_ajax() {
	check_ajax_referer( 'softicon_model_viewer_review' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( null, 403 );
	}
	$answer = isset( $_POST['answer'] ) ? sanitize_key( wp_unslash( $_POST['answer'] ) ) : '';
	update_user_meta( get_current_user_id(), 'softicon_model_viewer_review', 'later' === $answer ? time() + 14 * DAY_IN_SECONDS : 'done' );
	wp_send_json_success();
}
add_action( 'wp_ajax_softicon_model_viewer_review', 'softicon_model_viewer_review_ajax' );

/**
 * Prints the opt-in card, as fs-lite describes it in card().
 *
 * @param array $card From card().
 */
function softicon_model_viewer_dashboard_optin( $card ) {
	?>
	<section class="smv-optin" data-ajax="<?php echo esc_url( $card['ajax'] ); ?>" data-action="<?php echo esc_attr( $card['allow']['action'] ); ?>" data-nonce="<?php echo esc_attr( $card['allow']['nonce'] ); ?>" data-busy="<?php echo esc_attr( $card['allow']['busy'] ); ?>" data-error="<?php echo esc_attr( $card['allow']['error'] ); ?>">
		<?php if ( $card['icon'] ) : ?>
			<img src="<?php echo esc_url( $card['icon'] ); ?>" alt="" width="56" height="56" />
		<?php endif; ?>
		<div class="smv-optin-body">
			<h2><?php echo esc_html( $card['title'] ); ?></h2>
			<p><?php echo esc_html( $card['text'] ); ?></p>
			<details>
				<summary><?php echo esc_html( $card['shared'] ); ?></summary>
				<ul>
					<?php foreach ( $card['permissions'] as $item ) : ?>
						<li><span class="dashicons dashicons-<?php echo esc_attr( $item['icon'] ); ?>" aria-hidden="true"></span><strong><?php echo esc_html( $item['title'] ); ?></strong> <?php echo esc_html( $item['text'] ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p><?php echo esc_html( $card['note'] ); ?> <a href="<?php echo esc_url( $card['privacy']['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $card['privacy']['label'] ); ?></a></p>
			</details>
			<p class="smv-optin-error" role="alert" hidden></p>
			<div class="smv-optin-actions">
				<button type="button" class="button button-primary button-hero smv-optin-allow"><?php echo esc_html( $card['allow']['label'] ); ?></button>
				<?php if ( $card['skip'] ) : ?>
					<form method="post" action="<?php echo esc_url( $card['form'] ); ?>">
						<input type="hidden" name="action" value="<?php echo esc_attr( $card['skip']['action'] ); ?>" />
						<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $card['skip']['nonce'] ); ?>" />
						<button type="submit" class="button-link"><?php echo esc_html( $card['skip']['label'] ); ?></button>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * A row of the "Your 3D content" list.
 *
 * @param int    $id   Post ID.
 * @param string $type What it is, shown as a tag.
 */
function softicon_model_viewer_dashboard_item( $id, $type ) {
	$title  = get_the_title( $id );
	$status = get_post_status( $id );
	?>
	<li>
		<span class="smv-dash-tag"><?php echo esc_html( $type ); ?></span>
		<strong><?php echo esc_html( '' !== $title ? $title : __( '(no title)', 'softicon-model-viewer' ) ); ?></strong>
		<?php if ( 'publish' !== $status ) : ?>
			<span class="smv-dash-status"><?php echo esc_html( get_post_status_object( $status ) ? get_post_status_object( $status )->label : $status ); ?></span>
		<?php endif; ?>
		<span class="smv-dash-links">
			<a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><?php esc_html_e( 'Edit', 'softicon-model-viewer' ); ?></a>
			<a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( 'publish' === $status ? __( 'View', 'softicon-model-viewer' ) : __( 'Preview', 'softicon-model-viewer' ) ); ?></a>
		</span>
	</li>
	<?php
}

/**
 * Prints the dashboard.
 */
function softicon_model_viewer_dashboard_page() {
	$tab    = isset( $_GET['tab'] ) && 'help' === sanitize_key( wp_unslash( $_GET['tab'] ) ) ? 'help' : 'welcome'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only picks a tab
	$fs     = softicon_model_viewer_fs();
	$notice = $fs->notice();
	$card   = 'welcome' === $tab ? $fs->card() : null;
	$data   = softicon_model_viewer_dashboard_data();
	$plugin = get_file_data( dirname( __DIR__ ) . '/softicon-model-viewer.php', array( 'version' => 'Version' ) );
	$reviews = 'https://wordpress.org/support/plugin/softicon-model-viewer/reviews/#new-post';
	?>
	<div class="smv-dash">
		<header class="smv-dash-head">
			<div class="smv-dash-brand">
				<img src="<?php echo esc_url( plugins_url( 'assets/images/icon-128x128.png', __DIR__ ) ); ?>" alt="" width="40" height="40" />
				<h1><?php esc_html_e( 'Model Viewer', 'softicon-model-viewer' ); ?></h1>
				<span class="smv-dash-version"><?php echo esc_html( 'v' . $plugin['version'] ); ?></span>
			</div>
			<nav class="smv-dash-tabs" aria-label="<?php esc_attr_e( 'Model Viewer', 'softicon-model-viewer' ); ?>">
				<a href="<?php echo esc_url( softicon_model_viewer_dashboard_url() ); ?>"<?php echo 'welcome' === $tab ? ' class="is-active" aria-current="page"' : ''; ?>><?php esc_html_e( 'Welcome', 'softicon-model-viewer' ); ?></a>
				<a href="<?php echo esc_url( softicon_model_viewer_dashboard_url( 'help' ) ); ?>"<?php echo 'help' === $tab ? ' class="is-active" aria-current="page"' : ''; ?>><?php esc_html_e( 'Help', 'softicon-model-viewer' ); ?></a>
			</nav>
			<a class="smv-dash-more" href="https://wordpress.org/plugins/author/softiconai/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Our plugins', 'softicon-model-viewer' ); ?></a>
		</header>

		<div class="smv-dash-body">
			<?php // for the admin notices WordPress moves to the top of the page ?>
			<h2 class="screen-reader-text"><?php esc_html_e( 'Softicon Model Viewer', 'softicon-model-viewer' ); ?></h2>

			<?php if ( $notice ) : ?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> inline"><p><?php echo esc_html( $notice['text'] ); ?></p></div>
			<?php endif; ?>

			<?php
			if ( 'help' === $tab ) {
				softicon_model_viewer_dashboard_help();
			} else {
				softicon_model_viewer_dashboard_welcome( $data, $card, $reviews );
			}
			?>
		</div>
	</div>
	<?php
}

/**
 * The newest published model, for a real shortcode in the guide.
 *
 * @return int 0 when there is none yet.
 */
function softicon_model_viewer_dashboard_latest_model() {
	$ids = get_posts(
		array(
			'post_type'   => SOFTICON_MODEL_VIEWER_TYPE,
			'post_status' => 'publish',
			'numberposts' => 1,
			'fields'      => 'ids',
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * The "Getting started" guide: one tab per way to show a model, each a few numbered steps.
 *
 * A step has a title and a text (strong, code and kbd allowed), and may have an action link or a code box to copy.
 *
 * @param array $data From softicon_model_viewer_dashboard_data().
 * @return array[] Tabs keyed by name, each with label, icon and steps.
 */
function softicon_model_viewer_dashboard_start_tabs( $data ) {
	$model     = softicon_model_viewer_dashboard_latest_model();
	$shortcode = softicon_model_viewer_shortcode_text( $model ? $model : 123 );
	$new_model = admin_url( 'post-new.php?post_type=' . SOFTICON_MODEL_VIEWER_TYPE );
	$models    = admin_url( 'edit.php?post_type=' . SOFTICON_MODEL_VIEWER_TYPE );
	$code_note = $model ? __( 'The shortcode of your newest model:', 'softicon-model-viewer' ) : __( 'An example; your own number shows here once you publish a model:', 'softicon-model-viewer' );

	// saving a model is the first step of every way that uses its shortcode
	$save = array(
		'title'  => __( 'Save a 3D model', 'softicon-model-viewer' ),
		'text'   => __( 'Go to <strong>Model Viewer → All 3D Models</strong>, click <strong>Add a 3D model</strong>, choose the model in the block, set it up and click <strong>Publish</strong>.', 'softicon-model-viewer' ),
		'action' => array( $new_model, __( 'Add a 3D model', 'softicon-model-viewer' ) ),
	);
	$copy = array(
		'title'  => __( 'Copy its shortcode', 'softicon-model-viewer' ),
		'text'   => __( 'In <strong>All 3D Models</strong>, click the shortcode in the <strong>Shortcode</strong> column to copy it.', 'softicon-model-viewer' ),
		'action' => array( $models, __( 'All 3D Models', 'softicon-model-viewer' ) ),
		'note'   => $code_note,
		'code'   => $shortcode,
	);

	$tabs = array(
		'block'     => array(
			'label' => __( 'Block editor', 'softicon-model-viewer' ),
			'icon'  => 'screenoptions',
			'steps' => array(
				array(
					'title'  => __( 'Add the 3D Model Viewer block', 'softicon-model-viewer' ),
					'text'   => __( 'Open any page or post in the block editor. Click <strong>+</strong> in the top left, or type <kbd>/3d</kbd>, and pick <strong>3D Model Viewer</strong>.', 'softicon-model-viewer' ),
					'action' => array( softicon_model_viewer_new_page_url(), __( 'New page with a 3D model', 'softicon-model-viewer' ) ),
				),
				array(
					'title'  => __( 'Choose your model', 'softicon-model-viewer' ),
					'text'   => __( 'Click <strong>Upload</strong>, <strong>Media Library</strong> or <strong>Insert from URL</strong> and pick a GLB or glTF file.', 'softicon-model-viewer' ),
					'action' => array( admin_url( 'media-new.php' ), __( 'Upload a model', 'softicon-model-viewer' ) ),
				),
				array(
					'title' => __( 'Set it up', 'softicon-model-viewer' ),
					'text'  => __( 'The <strong>General</strong> tab has the model, interaction, the AR button and hotspots; the <strong>Style</strong> tab has the height, lighting and the starting angle.', 'softicon-model-viewer' ),
				),
				array(
					'title' => __( 'Publish', 'softicon-model-viewer' ),
					'text'  => __( 'Visitors can now rotate and zoom the model, and place it in their room on a phone.', 'softicon-model-viewer' ),
				),
			),
		),
		'shortcode' => array(
			'label' => __( 'Shortcode', 'softicon-model-viewer' ),
			'icon'  => 'shortcode',
			'steps' => array(
				$save,
				$copy,
				array(
					'title' => __( 'Paste it', 'softicon-model-viewer' ),
					'text'  => __( 'Paste it in a post in the <strong>Classic Editor</strong>, in a <strong>Shortcode</strong> widget, or in the block editor\'s <strong>Shortcode</strong> block.', 'softicon-model-viewer' ),
				),
				array(
					'title' => __( 'Change it in one place', 'softicon-model-viewer' ),
					'text'  => __( 'Edit the model in All 3D Models later and every page with its shortcode shows the change. Only published models show.', 'softicon-model-viewer' ),
				),
			),
		),
		'elementor' => array(
			'label' => __( 'Elementor', 'softicon-model-viewer' ),
			'icon'  => 'layout',
			'steps' => array(
				$save,
				$copy,
				array(
					'title' => __( 'Add a Shortcode widget', 'softicon-model-viewer' ),
					'text'  => __( 'In Elementor, search the widgets for <strong>Shortcode</strong>, drag it onto the page and paste the shortcode. The model shows in the editor at once.', 'softicon-model-viewer' ),
				),
				array(
					'title' => __( 'Other page builders', 'softicon-model-viewer' ),
					'text'  => __( 'Divi: a <strong>Code</strong> module. Beaver Builder, WPBakery and others: their shortcode or text module. Then save the page.', 'softicon-model-viewer' ),
				),
			),
		),
	);

	if ( $data['woo'] ) {
		$tabs['woocommerce'] = array(
			'label' => __( 'WooCommerce', 'softicon-model-viewer' ),
			'icon'  => 'cart',
			'steps' => array(
				array(
					'title'  => __( 'Edit a product', 'softicon-model-viewer' ),
					'text'   => __( 'Go to <strong>Products</strong> and open the product that should turn in 3D.', 'softicon-model-viewer' ),
					'action' => array( admin_url( 'edit.php?post_type=product' ), __( 'Open products', 'softicon-model-viewer' ) ),
				),
				array(
					'title' => __( 'Open the 3D model tab', 'softicon-model-viewer' ),
					'text'  => __( 'In the <strong>Product data</strong> box, open <strong>3D model</strong> and click <strong>Choose model</strong>.', 'softicon-model-viewer' ),
				),
				array(
					'title' => __( 'Add a poster and hotspots', 'softicon-model-viewer' ),
					'text'  => __( 'Optional: a poster image while it loads, hotspots on the preview, and the <strong>View in your room</strong> button on or off.', 'softicon-model-viewer' ),
				),
				array(
					'title' => __( 'Update the product', 'softicon-model-viewer' ),
					'text'  => __( 'A <strong>View in 3D</strong> button now shows under the product images. No shortcode needed.', 'softicon-model-viewer' ),
				),
				array(
					'title'  => __( 'Choose where it shows', 'softicon-model-viewer' ),
					'text'   => __( 'Optional: put the model in the product gallery, or add a 3D button to products on shop pages, under <strong>WooCommerce › Settings › Products › 3D models</strong>.', 'softicon-model-viewer' ),
					'action' => array( softicon_model_viewer_wc_settings_url(), __( 'Open the settings', 'softicon-model-viewer' ) ),
				),
			),
		);
	}

	$tabs['php'] = array(
		'label' => __( 'Theme / PHP', 'softicon-model-viewer' ),
		'icon'  => 'editor-code',
		'steps' => array(
			$save,
			array(
				'title' => __( 'Put it in a template', 'softicon-model-viewer' ),
				'text'  => __( 'In a classic theme\'s template file (a child theme is best), print the shortcode with <code>do_shortcode()</code>:', 'softicon-model-viewer' ),
				'code'  => "<?php echo do_shortcode( '" . $shortcode . "' ); ?>",
			),
			array(
				'title'  => __( 'Block themes', 'softicon-model-viewer' ),
				'text'   => __( 'In <strong>Appearance → Editor</strong>, open a template and add the <strong>3D Model Viewer</strong> block, or a <strong>Shortcode</strong> block with the shortcode.', 'softicon-model-viewer' ),
				'action' => wp_is_block_theme() ? array( admin_url( 'site-editor.php' ), __( 'Open the Site Editor', 'softicon-model-viewer' ) ) : null,
			),
		),
	);

	return $tabs;
}

/**
 * Prints the "Getting started" guide. The first tab shows without JavaScript; dashboard.js switches tabs.
 *
 * @param array $data From softicon_model_viewer_dashboard_data().
 */
function softicon_model_viewer_dashboard_start( $data ) {
	$tabs    = softicon_model_viewer_dashboard_start_tabs( $data );
	$allowed = array(
		'strong' => array(),
		'code'   => array(),
		'kbd'    => array(),
	);
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only picks the tab that opens first
	$current = isset( $_GET['start'] ) ? sanitize_key( wp_unslash( $_GET['start'] ) ) : '';
	if ( ! isset( $tabs[ $current ] ) ) {
		$current = 'block';
	}
	?>
	<section class="smv-dash-card smv-start" aria-labelledby="smv-start-title">
		<div class="smv-start-head">
			<div>
				<h2 id="smv-start-title"><?php esc_html_e( 'Getting started', 'softicon-model-viewer' ); ?></h2>
				<p><?php esc_html_e( 'Pick where you build your pages; the steps follow.', 'softicon-model-viewer' ); ?></p>
			</div>
			<div class="smv-start-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Where you build your pages', 'softicon-model-viewer' ); ?>">
				<?php foreach ( $tabs as $key => $tab ) : ?>
					<button type="button" role="tab" id="smv-start-tab-<?php echo esc_attr( $key ); ?>" aria-controls="smv-start-<?php echo esc_attr( $key ); ?>" aria-selected="<?php echo $current === $key ? 'true' : 'false'; ?>"<?php echo $current === $key ? '' : ' tabindex="-1"'; ?>>
						<span class="dashicons dashicons-<?php echo esc_attr( $tab['icon'] ); ?>" aria-hidden="true"></span>
						<?php echo esc_html( $tab['label'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div>

		<?php foreach ( $tabs as $key => $tab ) : ?>
			<div class="smv-start-panel" role="tabpanel" id="smv-start-<?php echo esc_attr( $key ); ?>" aria-labelledby="smv-start-tab-<?php echo esc_attr( $key ); ?>"<?php echo $current === $key ? '' : ' hidden'; ?>>
				<ol class="smv-start-steps">
					<?php foreach ( $tab['steps'] as $i => $step ) : ?>
						<li>
							<span class="smv-start-num" aria-hidden="true"><?php echo esc_html( number_format_i18n( $i + 1 ) ); ?></span>
							<strong><?php echo esc_html( $step['title'] ); ?></strong>
							<p><?php echo wp_kses( $step['text'], $allowed ); ?></p>
							<?php if ( ! empty( $step['code'] ) ) : ?>
								<?php if ( ! empty( $step['note'] ) ) : ?>
									<p class="smv-start-note"><?php echo esc_html( $step['note'] ); ?></p>
								<?php endif; ?>
								<div class="smv-start-code">
									<code><?php echo esc_html( $step['code'] ); ?></code>
									<button type="button" class="smv-start-copy" data-copy="<?php echo esc_attr( $step['code'] ); ?>" data-done="<?php esc_attr_e( 'Copied', 'softicon-model-viewer' ); ?>"><?php esc_html_e( 'Copy', 'softicon-model-viewer' ); ?></button>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $step['action'] ) ) : ?>
								<a class="smv-start-link" href="<?php echo esc_url( $step['action'][0] ); ?>"><?php echo esc_html( $step['action'][1] ); ?> <span aria-hidden="true">→</span></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endforeach; ?>

		<div class="smv-start-foot">
			<p><strong><?php esc_html_e( 'Rather watch it?', 'softicon-model-viewer' ); ?></strong> <?php esc_html_e( 'The tutorial shows every step in 80 seconds; the WooCommerce one adds the gallery, the shop button and colors.', 'softicon-model-viewer' ); ?></p>
			<div>
				<a class="button button-primary" href="<?php echo esc_url( SOFTICON_MODEL_VIEWER_TUTORIAL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Watch the tutorial', 'softicon-model-viewer' ); ?></a>
				<?php if ( $data['woo'] ) : ?>
					<a class="button" href="<?php echo esc_url( SOFTICON_MODEL_VIEWER_WC_TUTORIAL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WooCommerce tutorial', 'softicon-model-viewer' ); ?></a>
				<?php endif; ?>
				<a class="button" href="<?php echo esc_url( softicon_model_viewer_dashboard_url( 'help' ) ); ?>"><?php esc_html_e( 'Open Help', 'softicon-model-viewer' ); ?></a>
			</div>
		</div>
	</section>
	<?php
}

/**
 * The Welcome tab.
 *
 * @param array      $data    From softicon_model_viewer_dashboard_data().
 * @param array|null $card    The opt-in card while the choice is open.
 * @param string     $reviews Where to leave a review.
 */
function softicon_model_viewer_dashboard_welcome( $data, $card, $reviews ) {
	$steps = softicon_model_viewer_dashboard_steps( $data );
	$done  = count( array_filter( wp_list_pluck( $steps, 'done' ) ) );
	$total = count( $steps );
	?>
	<?php if ( $card ) : ?>
		<?php softicon_model_viewer_dashboard_optin( $card ); ?>
	<?php elseif ( softicon_model_viewer_dashboard_ask_review( $data ) ) : ?>
		<div class="smv-dash-review" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'softicon_model_viewer_review' ) ); ?>">
			<span class="smv-dash-stars" aria-hidden="true">★★★★★</span>
			<p><strong><?php esc_html_e( 'Your site shows products in 3D now.', 'softicon-model-viewer' ); ?></strong> <?php esc_html_e( 'A quick review on WordPress.org helps other people find the plugin, and means a lot to our small team.', 'softicon-model-viewer' ); ?></p>
			<div>
				<a class="button button-primary" href="<?php echo esc_url( $reviews ); ?>" target="_blank" rel="noopener noreferrer" data-answer="done"><?php esc_html_e( 'Leave a review', 'softicon-model-viewer' ); ?></a>
				<button type="button" class="button" data-answer="later"><?php esc_html_e( 'Maybe later', 'softicon-model-viewer' ); ?></button>
				<button type="button" class="button-link" data-answer="done"><?php esc_html_e( 'I already did', 'softicon-model-viewer' ); ?></button>
			</div>
		</div>
	<?php endif; ?>

	<section class="smv-dash-hero">
		<div>
			<h2><?php esc_html_e( 'Let people see your products from every side', 'softicon-model-viewer' ); ?></h2>
			<p><?php esc_html_e( 'Interactive 3D models with hotspots, and "View in your room" with augmented reality on phones. On any page, and on WooCommerce products.', 'softicon-model-viewer' ); ?></p>
			<div class="smv-dash-actions">
				<a class="smv-dash-button is-primary" href="<?php echo esc_url( softicon_model_viewer_new_page_url() ); ?>"><?php esc_html_e( 'New page with a 3D model', 'softicon-model-viewer' ); ?></a>
				<?php if ( $data['woo'] ) : ?>
					<a class="smv-dash-button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>"><?php esc_html_e( 'Add 3D to a product', 'softicon-model-viewer' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<nav class="smv-dash-hero-videos" aria-label="<?php esc_attr_e( 'Videos', 'softicon-model-viewer' ); ?>">
			<a class="smv-dash-video" href="<?php echo esc_url( SOFTICON_MODEL_VIEWER_TUTORIAL ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="smv-dash-play" aria-hidden="true"></span>
				<span><strong><?php esc_html_e( 'Watch the tutorial', 'softicon-model-viewer' ); ?></strong> <?php esc_html_e( 'Every step in 80 seconds', 'softicon-model-viewer' ); ?></span>
			</a>
			<a class="smv-dash-video" href="<?php echo esc_url( SOFTICON_MODEL_VIEWER_WHATS_NEW ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="smv-dash-play" aria-hidden="true"></span>
				<span><strong><?php esc_html_e( 'New in 1.6', 'softicon-model-viewer' ); ?> <em class="smv-dash-new"><?php esc_html_e( 'New', 'softicon-model-viewer' ); ?></em></strong> <?php esc_html_e( 'Animations, the product gallery, colors in 3D', 'softicon-model-viewer' ); ?></span>
			</a>
		</nav>
	</section>

	<?php softicon_model_viewer_dashboard_start( $data ); ?>

	<div class="smv-dash-grid">
		<section class="smv-dash-card smv-dash-steps">
			<div class="smv-dash-card-head">
				<h2><?php esc_html_e( 'Get set up', 'softicon-model-viewer' ); ?></h2>
				<span class="smv-dash-progress<?php echo $done === $total ? ' is-done' : ''; ?>">
					<?php
					if ( $done === $total ) {
						esc_html_e( 'All set', 'softicon-model-viewer' );
					} else {
						/* translators: 1: steps done, 2: all steps */
						echo esc_html( sprintf( __( '%1$d of %2$d done', 'softicon-model-viewer' ), $done, $total ) );
					}
					?>
				</span>
			</div>
			<div class="smv-dash-bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $total ); ?>" aria-valuenow="<?php echo esc_attr( $done ); ?>"><i style="width:<?php echo esc_attr( round( $done / max( 1, $total ) * 100 ) ); ?>%"></i></div>
			<ol>
				<?php foreach ( $steps as $i => $step ) : ?>
					<li class="<?php echo $step['done'] ? 'is-done' : ''; ?>">
						<span class="smv-dash-check" aria-hidden="true"><?php echo $step['done'] ? '' : esc_html( $i + 1 ); ?></span>
						<div>
							<strong><?php echo esc_html( $step['title'] ); ?></strong>
							<?php if ( $step['done'] ) : ?>
								<span class="screen-reader-text"><?php esc_html_e( '(done)', 'softicon-model-viewer' ); ?></span>
							<?php endif; ?>
							<p><?php echo esc_html( $step['text'] ); ?></p>
						</div>
						<?php if ( ! $step['done'] ) : ?>
							<a class="button" href="<?php echo esc_url( $step['action'][0] ); ?>"><?php echo esc_html( $step['action'][1] ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>

		<section class="smv-dash-card smv-dash-stats">
			<h2><?php esc_html_e( 'Your 3D content', 'softicon-model-viewer' ); ?></h2>
			<div class="smv-dash-numbers">
				<div><b><?php echo esc_html( number_format_i18n( $data['models'] ) ); ?></b> <?php esc_html_e( 'Models', 'softicon-model-viewer' ); ?></div>
				<div><b><?php echo esc_html( number_format_i18n( count( $data['pages'] ) ) ); ?></b> <?php esc_html_e( 'Pages and posts', 'softicon-model-viewer' ); ?></div>
				<?php if ( $data['woo'] ) : ?>
					<div><b><?php echo esc_html( number_format_i18n( count( $data['products'] ) ) ); ?></b> <?php esc_html_e( 'Products', 'softicon-model-viewer' ); ?></div>
				<?php endif; ?>
				<div><b><?php echo esc_html( number_format_i18n( $data['hotspots'] ) ); ?></b> <?php esc_html_e( 'Hotspots', 'softicon-model-viewer' ); ?></div>
			</div>
			<?php if ( $data['pages'] || $data['products'] ) : ?>
				<ul class="smv-dash-list">
					<?php
					foreach ( array_slice( $data['pages'], 0, 4 ) as $id ) {
						softicon_model_viewer_dashboard_item( $id, get_post_type_object( get_post_type( $id ) ) ? get_post_type_object( get_post_type( $id ) )->labels->singular_name : '' );
					}
					foreach ( array_slice( $data['products'], 0, 4 ) as $id ) {
						softicon_model_viewer_dashboard_item( $id, __( 'Product', 'softicon-model-viewer' ) );
					}
					?>
				</ul>
			<?php else : ?>
				<p class="smv-dash-empty"><?php esc_html_e( 'Nothing in 3D yet. Your pages and products with a model show up here.', 'softicon-model-viewer' ); ?></p>
			<?php endif; ?>
			<?php if ( ! $data['woo'] ) : ?>
				<p class="smv-dash-tip"><?php esc_html_e( 'Running a shop? With WooCommerce active, every product gets a 3D model tab and a View in 3D button.', 'softicon-model-viewer' ); ?></p>
			<?php endif; ?>
		</section>
	</div>
	<?php
}

/**
 * The Help tab: videos, answers and support.
 */
function softicon_model_viewer_dashboard_help() {
	$faq = array(
		'models'    => array( __( 'Which files can I use?', 'softicon-model-viewer' ), __( 'GLB (one file, recommended) and glTF. Keep them small for fast pages, ideally under 5 MB. For iPhone and iPad, the viewer turns the GLB into the format AR needs by itself.', 'softicon-model-viewer' ) ),
		'hotspots'  => array( __( 'How do hotspots work?', 'softicon-model-viewer' ), __( 'In the block, open Hotspots on the General tab; on a product, use the preview in its 3D model tab. Click "Add hotspot", then click on the model where it goes, and give it a label and an optional text. The point turns with the model and fades when it is behind it.', 'softicon-model-viewer' ) ),
		'ar'        => array( __( 'Why is there no "View in your room" button on my computer?', 'softicon-model-viewer' ), __( 'Augmented reality needs a phone or tablet with a camera and AR support. On other devices the button stays hidden, and visitors can still turn and zoom the model.', 'softicon-model-viewer' ) ),
		'shortcode' => array( __( 'Can I use it with the Classic Editor, Elementor or Divi?', 'softicon-model-viewer' ), __( 'Yes. Go to Model Viewer → All 3D Models, click "Add a 3D model", set it up in the block and publish it. The list shows its shortcode, such as [softicon_model_viewer id="123"]; paste it where the model should be. Change the model there later and every page with its shortcode shows the change.', 'softicon-model-viewer' ) ),
		'woo'       => array( __( 'Where is the View in 3D button on my product?', 'softicon-model-viewer' ), __( 'It shows under the product images once the product has a model in its 3D model tab. Block themes get it below the product image gallery block, classic themes like Storefront below the gallery.', 'softicon-model-viewer' ) ),
		'data'      => array( __( 'Does the plugin collect any data?', 'softicon-model-viewer' ), __( 'Nothing from your visitors. From you, only if you click "Allow & Continue" on the opt-in: your name and email, your site address, title and language, and the WordPress, PHP and plugin versions, through Freemius. You can opt out from the Plugins page at any time.', 'softicon-model-viewer' ) ),
	);
	$videos = array(
		array( SOFTICON_MODEL_VIEWER_WHATS_NEW, __( "What's new in 1.6", 'softicon-model-viewer' ), __( 'Animations, the product gallery, the shop button and colors in 3D, in 1 minute', 'softicon-model-viewer' ) ),
		array( SOFTICON_MODEL_VIEWER_WC_TUTORIAL, __( 'WooCommerce tutorial', 'softicon-model-viewer' ), __( 'A model on a product, the gallery, the shop button and colors, in 2 minutes', 'softicon-model-viewer' ) ),
		array( SOFTICON_MODEL_VIEWER_TUTORIAL, __( 'Setup tutorial', 'softicon-model-viewer' ), __( 'Install, a product, a page: every step in 80 seconds', 'softicon-model-viewer' ) ),
		array( 'https://www.youtube.com/watch?v=y3ep9HGLhso', __( '3D and AR for WooCommerce', 'softicon-model-viewer' ), __( 'What your shoppers see, in 30 seconds', 'softicon-model-viewer' ) ),
	);
	?>
	<div class="smv-dash-grid">
		<section class="smv-dash-card">
			<h2><?php esc_html_e( 'Video tutorials', 'softicon-model-viewer' ); ?></h2>
			<ul class="smv-dash-videos">
				<?php foreach ( $videos as $video ) : ?>
					<li><a href="<?php echo esc_url( $video[0] ); ?>" target="_blank" rel="noopener noreferrer"><span class="smv-dash-play" aria-hidden="true"></span><span><strong><?php echo esc_html( $video[1] ); ?></strong> <?php echo esc_html( $video[2] ); ?></span></a></li>
				<?php endforeach; ?>
			</ul>

			<h2><?php esc_html_e( 'Questions and answers', 'softicon-model-viewer' ); ?></h2>
			<div class="smv-dash-faq">
				<?php foreach ( $faq as $key => $item ) : ?>
					<details id="smv-faq-<?php echo esc_attr( $key ); ?>">
						<summary><?php echo esc_html( $item[0] ); ?></summary>
						<p><?php echo esc_html( $item[1] ); ?></p>
					</details>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="smv-dash-card smv-dash-support">
			<h2><?php esc_html_e( 'Need help?', 'softicon-model-viewer' ); ?></h2>
			<p><?php esc_html_e( 'Ask in the support forum and we reply there, so the answer helps others too. Guides and more help are on our website, and for anything you would rather not post in public, email us.', 'softicon-model-viewer' ); ?></p>
			<p class="smv-dash-support-actions">
				<a class="button button-primary" href="https://wordpress.org/support/plugin/softicon-model-viewer/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open the support forum', 'softicon-model-viewer' ); ?></a>
				<a class="button" href="<?php echo esc_url( SOFTICON_MODEL_VIEWER_WEBSITE ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Visit our website', 'softicon-model-viewer' ); ?></a>
			</p>
			<p><?php esc_html_e( 'Website:', 'softicon-model-viewer' ); ?> <a href="<?php echo esc_url( SOFTICON_MODEL_VIEWER_WEBSITE ); ?>" target="_blank" rel="noopener noreferrer">softiconai.com</a></p>
			<p><?php esc_html_e( 'Email:', 'softicon-model-viewer' ); ?> <a href="mailto:softiconai@gmail.com">softiconai@gmail.com</a></p>
			<hr />
			<h2><?php esc_html_e( 'Like the plugin?', 'softicon-model-viewer' ); ?></h2>
			<p><?php esc_html_e( 'A review on WordPress.org helps other people find it.', 'softicon-model-viewer' ); ?></p>
			<p><a class="button" href="https://wordpress.org/support/plugin/softicon-model-viewer/reviews/#new-post" target="_blank" rel="noopener noreferrer"><span class="smv-dash-stars" aria-hidden="true">★★★★★</span> <?php esc_html_e( 'Rate Model Viewer', 'softicon-model-viewer' ); ?></a></p>
		</section>
	</div>
	<?php
}
