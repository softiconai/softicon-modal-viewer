<?php
/**
 * Hotspots: numbered points on the model with a label and a short text, for the block and the WooCommerce popup.
 *
 * The editor saves each one as { id, label, text, position, normal }; position and normal are model-viewer
 * vectors in metres ("0.1m -0.2m 0.3m"). The same markup is drawn in the editor by src/utils/hotspots.js.
 *
 * @package SofticonModelViewer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The hotspots that can be drawn: a point is three numbers in metres, anything else is skipped.
 *
 * @param mixed $hotspots The saved list.
 * @return array[] Each with slot, position, normal, label and text.
 */
function softicon_model_viewer_hotspots_clean( $hotspots ) {
	$clean = array();
	if ( ! is_array( $hotspots ) ) {
		return $clean;
	}

	$point = '/^-?\d+(\.\d+)?m -?\d+(\.\d+)?m -?\d+(\.\d+)?m$/';
	foreach ( $hotspots as $hotspot ) {
		if ( ! is_array( $hotspot ) || empty( $hotspot['position'] ) || ! preg_match( $point, (string) $hotspot['position'] ) ) {
			continue;
		}
		$clean[] = array(
			'slot'     => 'hotspot-' . ( count( $clean ) + 1 ),
			'position' => (string) $hotspot['position'],
			'normal'   => isset( $hotspot['normal'] ) && preg_match( $point, (string) $hotspot['normal'] ) ? (string) $hotspot['normal'] : '',
			'label'    => isset( $hotspot['label'] ) ? (string) $hotspot['label'] : '',
			'text'     => isset( $hotspot['text'] ) ? (string) $hotspot['text'] : '',
		);
	}

	return $clean;
}

/**
 * The hotspot buttons that go inside <model-viewer>.
 *
 * @param mixed $hotspots The saved list.
 * @param bool  $labels   Whether the labels always show (otherwise they open with the text).
 * @return string Escaped HTML.
 */
function softicon_model_viewer_hotspots_html( $hotspots, $labels = true ) {
	$html = '';
	foreach ( softicon_model_viewer_hotspots_clean( $hotspots ) as $i => $hotspot ) {
		$html .= '<button type="button" class="softicon-model-viewer__hotspot" slot="' . esc_attr( $hotspot['slot'] ) . '" data-position="' . esc_attr( $hotspot['position'] ) . '"'
			. ( $hotspot['normal'] ? ' data-normal="' . esc_attr( $hotspot['normal'] ) . '"' : '' ) . ' data-visibility-attribute="visible">'
			. '<span class="softicon-model-viewer__hotspot-dot" aria-hidden="true">' . esc_html( $i + 1 ) . '</span>';
		if ( '' !== $hotspot['label'] ) {
			$html .= '<span class="softicon-model-viewer__hotspot-label' . ( $labels ? '' : ' is-hidden' ) . '">' . esc_html( $hotspot['label'] ) . '</span>';
		}
		if ( '' !== $hotspot['text'] ) {
			$html .= '<span class="softicon-model-viewer__hotspot-text">' . esc_html( $hotspot['text'] ) . '</span>';
		}
		$html .= '</button>';
	}

	return $html;
}
