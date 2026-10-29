import { registerBlockType } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
import metadata from './block.json';
import './style.scss';
import './editor.scss';
import Edit from './Components/Backend/Edit';
import { blockIcon } from './utils/icons';

registerBlockType(metadata, {
	icon: blockIcon,
	edit: Edit,
	// Dynamic block, the markup comes from render.php.
	save: () => null
});

// no WordPress Background, Dimensions and Border panels for this block; values saved before still apply on the page
const hiddenSettings = ['color.background', 'spacing.padding', 'spacing.margin', 'border.color', 'border.radius', 'border.style', 'border.width'];
addFilter('blockEditor.useSetting.before', 'softicon-model-viewer/no-style-panels', (value, path, clientId, blockName) => (
	metadata.name === blockName && hiddenSettings.includes(path) ? false : value
));
