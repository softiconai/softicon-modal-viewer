import { __ } from '@wordpress/i18n';
import { BlockControls, MediaPlaceholder, MediaReplaceFlow, RichText, useBlockProps } from '@wordpress/block-editor';
import { ExternalLink } from '@wordpress/components';
import { useMergeRefs, useRefEffect } from '@wordpress/compose';
// copied from the shared softicon-tools folder by `npm run tools` (prebuild), edit it there
import useIframeAssets from '../../vendor/softicon-tools/hooks/useIframeAssets';
import Settings from './Settings/Settings';
import { blockIcon } from '../../utils/icons';
import { ACCEPT, ALLOWED_TYPES, TUTORIAL_URL } from '../../utils/options';

// The viewer library sits next to the build folder; the editor canvas is an iframe, so it may need loading there too.
const LIBRARY_URL = document.currentScript ? new URL('../vendor/model-viewer/model-viewer.min.js', document.currentScript.src).href : '';

const useViewerLibrary = () => useRefEffect(node => {
	const doc = node.ownerDocument;
	const win = doc.defaultView;
	if (!LIBRARY_URL || win.customElements?.get('model-viewer') || doc.getElementById('softicon-model-viewer-library-editor')) {
		return;
	}
	const script = doc.createElement('script');
	script.id = 'softicon-model-viewer-library-editor';
	script.src = LIBRARY_URL;
	doc.head.appendChild(script);
}, []);

const Edit = ({ attributes, setAttributes, isSelected }) => {
	const { url, id, alt, posterUrl, caption, height, cameraControls, autoRotate, interactionPrompt, exposure, shadowIntensity, environment, cameraOrbit } = attributes;
	const libraryRef = useViewerLibrary();
	// a block installed from the inserter's search gets these only in the top window
	const assetsRef = useIframeAssets(['softicon-model-viewer-style-css', 'softicon-model-viewer-editor-style-css']);
	const blockProps = useBlockProps({ className: 'softicon-model-viewer', ref: useMergeRefs([libraryRef, assetsRef]) });

	const onSelectModel = media => {
		if (!media?.url) {
			return;
		}
		setAttributes({ url: media.url, id: media.id, alt: alt || media.alt || '' });
	};
	const onSelectURL = val => setAttributes({ url: val, id: undefined });

	// the settings show before a model is chosen too, so the sidebar opens on the block's own tabs
	if (!url) {
		return <>
			<Settings {...{ attributes, setAttributes }} />

			<figure {...blockProps}>
				<MediaPlaceholder icon={blockIcon} onSelect={onSelectModel} onSelectURL={onSelectURL} accept={ACCEPT} allowedTypes={ALLOWED_TYPES}
					labels={{
						title: __('3D Model Viewer', 'softicon-model-viewer'),
						instructions: __('Upload a GLB or glTF file, pick one from the media library, or add one from a URL.', 'softicon-model-viewer')
					}}>
					<p className='softicon-model-viewer__help'>
						<ExternalLink href={TUTORIAL_URL}>{__('Watch the video tutorial', 'softicon-model-viewer')}</ExternalLink>
					</p>
				</MediaPlaceholder>
			</figure>
		</>;
	}

	return <>
		<BlockControls group='other'>
			<MediaReplaceFlow mediaId={id} mediaURL={url} allowedTypes={ALLOWED_TYPES} accept={ACCEPT} onSelect={onSelectModel} onSelectURL={onSelectURL}
				name={__('Replace model', 'softicon-model-viewer')} />
		</BlockControls>

		<Settings {...{ attributes, setAttributes }} />

		<figure {...blockProps}>
			<model-viewer key={url} src={url} alt={alt} poster={posterUrl || undefined}
				exposure={String(exposure)}
				shadow-intensity={String(shadowIntensity)}
				environment-image={environment}
				interaction-prompt={interactionPrompt ? 'auto' : 'none'}
				camera-orbit={cameraOrbit || undefined}
				camera-controls={cameraControls ? '' : undefined}
				auto-rotate={autoRotate ? '' : undefined}
				style={{ height: height || '520px' }} />

			{(isSelected || caption) && <RichText tagName='figcaption' className='wp-element-caption' placeholder={__('Add caption', 'softicon-model-viewer')}
				value={caption} onChange={val => setAttributes({ caption: val })} allowedFormats={['core/bold', 'core/italic', 'core/link']} />}
		</figure>
	</>;
};

export default Edit;
