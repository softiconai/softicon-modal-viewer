<?php
/**
 * The Freemius opt-in page: a hidden admin page that vendor/fs-lite opens once after activation.
 *
 * It draws the card and the notice that fs-lite describes in card() and notice(); Allow & Continue goes through
 * assets/js/opt-in.js, Skip is a plain form to admin-post.php.
 *
 * @package SofticonModelViewer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SOFTICON_MODEL_VIEWER_OPTIN_PAGE = 'softicon-model-viewer';

/**
 * Registers the page without a menu item (under options.php), so WordPress still knows its title.
 */
function softicon_model_viewer_optin_menu() {
	$hook = add_submenu_page(
		'options.php',
		__( 'Softicon Model Viewer', 'softicon-model-viewer' ),
		__( 'Softicon Model Viewer', 'softicon-model-viewer' ),
		'manage_options',
		SOFTICON_MODEL_VIEWER_OPTIN_PAGE,
		'softicon_model_viewer_optin_page'
	);
	// the hook name WordPress gives this page, so the assets load on it and nowhere else
	if ( $hook ) {
		add_action( 'load-' . $hook, 'softicon_model_viewer_optin_load' );
	}
}
add_action( 'admin_menu', 'softicon_model_viewer_optin_menu' );

/**
 * The page is opening: its style and script go in.
 */
function softicon_model_viewer_optin_load() {
	add_action( 'admin_enqueue_scripts', 'softicon_model_viewer_optin_assets' );
}

/**
 * The card's style and script, on the opt-in page only.
 */
function softicon_model_viewer_optin_assets() {
	$css = __DIR__ . '/../vendor/fs-lite/assets/fs-lite.css';
	$js  = __DIR__ . '/../assets/js/opt-in.js';
	wp_enqueue_style( 'softicon-model-viewer-fs-lite', plugins_url( 'vendor/fs-lite/assets/fs-lite.css', __DIR__ ), array(), (string) filemtime( $css ) );
	wp_enqueue_script( 'softicon-model-viewer-opt-in', plugins_url( 'assets/js/opt-in.js', __DIR__ ), array(), (string) filemtime( $js ), true );
}

/**
 * Prints the page: the message after an opt-in, the card while the choice is open, or where things stand.
 */
function softicon_model_viewer_optin_page() {
	$fs     = softicon_model_viewer_fs();
	$card   = $fs->card();
	$notice = $fs->notice();
	?>
	<div class="wrap smv-optin-wrap">
		<h1><?php esc_html_e( 'Softicon Model Viewer', 'softicon-model-viewer' ); ?></h1>

		<?php if ( $notice ) : ?>
			<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> inline"><p><?php echo esc_html( $notice['text'] ); ?></p></div>
		<?php endif; ?>

		<?php if ( $card ) : ?>
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
		<?php else : ?>
			<p><?php esc_html_e( 'Add the 3D Model Viewer block to any page or post, or open the 3D model tab of a WooCommerce product.', 'softicon-model-viewer' ); ?></p>
			<p>
				<a class="button" href="<?php echo esc_url( SOFTICON_MODEL_VIEWER_TUTORIAL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Watch the video tutorial', 'softicon-model-viewer' ); ?></a>
				<a class="button-link" href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Back to Plugins', 'softicon-model-viewer' ); ?></a>
			</p>
		<?php endif; ?>
	</div>
	<?php
}
