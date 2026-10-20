<?php
/**
 * Front-end markup for the 3D Model Viewer block.
 *
 * @package SofticonModelViewer
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $attributes['url'] ) ) {
	return;
}

$softicon_model_viewer_height = isset( $attributes['height'] ) && preg_match( '/^\d+(\.\d+)?(px|vh|rem|em|%)$/', $attributes['height'] ) ? $attributes['height'] : '520px';
$softicon_model_viewer_label  = ! empty( $attributes['arLabel'] ) ? $attributes['arLabel'] : __( 'View in your room', 'softicon-model-viewer' );
$softicon_model_viewer_orbit  = isset( $attributes['cameraOrbit'] ) ? preg_replace( '/[^0-9a-z.%\s-]/i', '', $attributes['cameraOrbit'] ) : '';

$softicon_model_viewer_attrs = array(
	'src'                => esc_url( $attributes['url'] ),
	'alt'                => esc_attr( isset( $attributes['alt'] ) ? $attributes['alt'] : '' ),
	'exposure'           => esc_attr( (string) (float) ( isset( $attributes['exposure'] ) ? $attributes['exposure'] : 1 ) ),
	'shadow-intensity'   => esc_attr( (string) (float) ( isset( $attributes['shadowIntensity'] ) ? $attributes['shadowIntensity'] : 1 ) ),
	'environment-image'  => ( isset( $attributes['environment'] ) && 'legacy' === $attributes['environment'] ) ? 'legacy' : 'neutral',
	'loading'            => 'lazy',
	'interaction-prompt' => empty( $attributes['interactionPrompt'] ) ? 'none' : 'auto',
	'touch-action'       => 'pan-y',
	'style'              => esc_attr( 'height:' . $softicon_model_viewer_height ),
);

if ( ! empty( $attributes['posterUrl'] ) ) {
	$softicon_model_viewer_attrs['poster'] = esc_url( $attributes['posterUrl'] );
}
if ( '' !== trim( $softicon_model_viewer_orbit ) ) {
	$softicon_model_viewer_attrs['camera-orbit'] = esc_attr( trim( $softicon_model_viewer_orbit ) );
}

$softicon_model_viewer_flags = array();
if ( ! empty( $attributes['cameraControls'] ) ) {
	$softicon_model_viewer_flags[] = 'camera-controls';
}
if ( ! empty( $attributes['autoRotate'] ) ) {
	$softicon_model_viewer_flags[] = 'auto-rotate';
}
if ( ! empty( $attributes['ar'] ) ) {
	$softicon_model_viewer_flags[]           = 'ar';
	$softicon_model_viewer_attrs['ar-modes'] = 'webxr scene-viewer quick-look';
	$softicon_model_viewer_attrs['ar-scale'] = 'fixed';
}

$softicon_model_viewer_html = '';
foreach ( $softicon_model_viewer_attrs as $softicon_model_viewer_name => $softicon_model_viewer_value ) {
	$softicon_model_viewer_html .= ' ' . $softicon_model_viewer_name . '="' . $softicon_model_viewer_value . '"';
}
$softicon_model_viewer_html .= $softicon_model_viewer_flags ? ' ' . implode( ' ', $softicon_model_viewer_flags ) : '';

$softicon_model_viewer_wrapper = get_block_wrapper_attributes( array( 'class' => 'softicon-model-viewer' ) );
?>
<figure <?php echo $softicon_model_viewer_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes(). ?>>
	<model-viewer<?php echo $softicon_model_viewer_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each value is escaped above. ?>>
		<?php if ( ! empty( $attributes['ar'] ) ) : ?>
			<button type="button" slot="ar-button" class="softicon-model-viewer__ar">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/></svg>
				<?php echo esc_html( $softicon_model_viewer_label ); ?>
			</button>
		<?php endif; ?>
	</model-viewer>
	<?php if ( ! empty( $attributes['caption'] ) ) : ?>
		<figcaption class="wp-element-caption"><?php echo wp_kses_post( $attributes['caption'] ); ?></figcaption>
	<?php endif; ?>
</figure>
