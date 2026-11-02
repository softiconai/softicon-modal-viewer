import { __ } from '@wordpress/i18n';
import { icons } from './icons';
import { icons as shared } from '../vendor/softicon-tools/components/icons';

export const tabs = [
	{ name: 'general', title: __('General', 'softicon-model-viewer'), className: 'general' },
	{ name: 'style', title: __('Style', 'softicon-model-viewer'), className: 'style' }
];

export const ALLOWED_TYPES = ['model/gltf-binary', 'model/gltf+json'];
export const ACCEPT = '.glb,.gltf,model/gltf-binary,model/gltf+json';

// the tutorial on the SoftIcon YouTube channel (the Plugins screen links it too, from softicon-model-viewer.php)
export const TUTORIAL_URL = 'https://www.youtube.com/watch?v=9WtqRuBFUv8';

export const environments = [
	{ label: __('Soft studio', 'softicon-model-viewer'), value: 'neutral', icon: icons.softLight },
	{ label: __('Classic', 'softicon-model-viewer'), value: 'legacy', icon: icons.classicLight }
];

// the cards in the Help panel
export const helpCards = [
	{ href: TUTORIAL_URL, label: __('Video tutorial', 'softicon-model-viewer'), icon: shared.video },
	{ href: 'https://wordpress.org/support/plugin/softicon-model-viewer/', label: __('Get support', 'softicon-model-viewer'), icon: shared.support },
	{ href: 'https://wordpress.org/support/plugin/softicon-model-viewer/reviews/#new-post', label: __('Rate Model Viewer', 'softicon-model-viewer'), icon: shared.star },
	{ href: 'https://wordpress.org/plugins/author/softiconai/', label: __('More plugins', 'softicon-model-viewer'), icon: shared.plugins }
];
