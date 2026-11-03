import { __, sprintf } from '@wordpress/i18n';

// placing a hotspot, for the block editor and the WooCommerce product screen (no React here)

// a model-viewer vector as its attribute value, "0.1234m 0.5m -0.2m"
const toAttr = v => [v.x, v.y, v.z].map(n => Number(n).toFixed(4) + 'm').join(' ');

// the point of the model under a click, or null when the click missed it
export const pointAt = (viewer, event) => {
	const hit = viewer?.positionAndNormalFromPoint?.(event.clientX, event.clientY);
	return hit ? { position: toAttr(hit.position), normal: toAttr(hit.normal) } : null;
};

export const newHotspot = (point, number) => ({
	id: Date.now().toString(36) + Math.random().toString(36).slice(2, 6),
	/* translators: %d: the hotspot's number */
	label: sprintf(__('Hotspot %d', 'softicon-model-viewer'), number),
	text: '',
	...point
});
