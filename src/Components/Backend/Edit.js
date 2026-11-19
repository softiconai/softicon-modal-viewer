import { __ } from '@wordpress/i18n';
import { BlockControls, MediaPlaceholder, MediaReplaceFlow, RichText, useBlockProps } from '@wordpress/block-editor';
import { ExternalLink } from '@wordpress/components';
import { useMergeRefs, useRefEffect } from '@wordpress/compose';
import { useEffect, useState } from '@wordpress/element';
// copied from the shared softicon-tools folder by `npm run tools` (prebuild), edit it there
import useIframeAssets from '../../vendor/softicon-tools/hooks/useIframeAssets';
import Settings from './Settings/Settings';
import { blockIcon } from '../../utils/icons';
import { ACCEPT, ALLOWED_TYPES, TUTORIAL_URL } from '../../utils/options';
import { Hotspots, newHotspot, pointAt } from '../../utils/hotspots';

// The viewer library sits next to the build folder; the editor canvas is an iframe, so it may need loading there too.
const LIBRARY_URL = document.currentScript ? new URL('../vendor/model-viewer/model-viewer.min.js', document.currentScript.src).href : '';
// runs before the library, so compressed models use the plugin's own decoders (assets/js/viewer-decoders.js)
const DECODERS_URL = document.currentScript ? new URL('../assets/js/viewer-decoders.js', document.currentScript.src).href : '';

const useViewerLibrary = () => useRefEffect(node => {
	const doc = node.ownerDocument;
	const win = doc.defaultView;
	if (!LIBRARY_URL || win.customElements?.get('model-viewer') || doc.getElementById('softicon-model-viewer-library-editor')) {
		return;
	}
	// not async, so the decoders script runs first
	[['softicon-model-viewer-decoders-editor', DECODERS_URL], ['softicon-model-viewer-library-editor', LIBRARY_URL]].forEach(([scriptId, src]) => {
		const script = doc.createElement('script');
		script.id = scriptId;
		script.src = src;
		script.async = false;
		doc.head.appendChild(script);
	});
}, []);

// the model's animations once it has loaded (null while it loads), and whether one is playing
const useAnimations = viewer => {
	const [animations, setAnimations] = useState(null);
	const [playing, setPlaying] = useState(false);

	useEffect(() => {
		setAnimations(null);
		setPlaying(false);
		if (!viewer) {
			return;
		}
		const onLoad = () => setAnimations([...(viewer.availableAnimations || [])]);
		const onPlay = () => setPlaying(true);
		const onStop = () => setPlaying(false);
		viewer.addEventListener('load', onLoad);
		viewer.addEventListener('play', onPlay);
		viewer.addEventListener('pause', onStop);
		viewer.addEventListener('finished', onStop);
		if (viewer.loaded) {
			onLoad();
		}
		return () => {
			viewer.removeEventListener('load', onLoad);
			viewer.removeEventListener('play', onPlay);
			viewer.removeEventListener('pause', onStop);
			viewer.removeEventListener('finished', onStop);
		};
	}, [viewer]);

	return { animations, playing };
};

const Edit = ({ attributes, setAttributes, isSelected }) => {
	const { url, id, alt, posterUrl, caption, height, cameraControls, autoRotate, interactionPrompt, exposure, shadowIntensity, environment, cameraOrbit, hotspots, hotspotLabels, animationAutoplay, animationName, animationLoop, animationButton } = attributes;
	// "Add hotspot" was clicked: the next click on the model places it
	const [placing, setPlacing] = useState(false);
	const [viewer, setViewer] = useState(null);
	const { animations, playing } = useAnimations(viewer);
	const playAnimation = () => viewer.play(animationLoop ? undefined : { repetitions: 1 });

	// a changed animation setting plays it again from the start, as a visitor sees it; paused while placing a hotspot
	useEffect(() => {
		if (!viewer || !animations?.length) {
			return;
		}
		viewer.pause();
		viewer.currentTime = 0;
		if (animationAutoplay && !placing) {
			playAnimation();
		}
	}, [viewer, animations, animationAutoplay, animationName, animationLoop, placing]); // eslint-disable-line react-hooks/exhaustive-deps
	const libraryRef = useViewerLibrary();
	// a block installed from the inserter's search gets these only in the top window
	const assetsRef = useIframeAssets(['softicon-model-viewer-style-css', 'softicon-model-viewer-editor-style-css']);
	const blockProps = useBlockProps({ className: 'softicon-model-viewer' + (placing && isSelected ? ' is-placing' : ''), ref: useMergeRefs([libraryRef, assetsRef]) });

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

		<Settings {...{ attributes, setAttributes, placing: placing && isSelected, setPlacing, animations }} />

		<figure {...blockProps}>
			<model-viewer key={url} ref={setViewer} src={url} alt={alt} poster={posterUrl || undefined}
				animation-name={animationName || undefined}
				exposure={String(exposure)}
				shadow-intensity={String(shadowIntensity)}
				environment-image={environment}
				interaction-prompt={interactionPrompt ? 'auto' : 'none'}
				camera-orbit={cameraOrbit || undefined}
				camera-controls={cameraControls ? '' : undefined}
				// a turning model is hard to click on
				auto-rotate={autoRotate && !placing ? '' : undefined}
				style={{ height: height || '520px' }}
				onClick={placing ? event => {
					const point = pointAt(event.currentTarget, event);
					if (point) {
						setAttributes({ hotspots: [...hotspots, newHotspot(point, hotspots.length + 1)] });
						setPlacing(false);
					}
				} : undefined}>
				<Hotspots hotspots={hotspots} labels={hotspotLabels} />

				{animationButton && !!animations?.length && <button type='button' className={'softicon-model-viewer__play' + (playing ? ' is-playing' : '')}
					aria-label={playing ? __('Pause animation', 'softicon-model-viewer') : __('Play animation', 'softicon-model-viewer')}
					onClick={event => {
						event.stopPropagation();
						if (playing) {
							viewer.pause();
						} else {
							playAnimation();
						}
					}}>
					<svg className='softicon-model-viewer__play-icon' width='18' height='18' viewBox='0 0 24 24' fill='currentColor' aria-hidden='true' focusable='false'><path d='M8 5.5v13l10.5-6.5L8 5.5z' /></svg>
					<svg className='softicon-model-viewer__pause-icon' width='18' height='18' viewBox='0 0 24 24' fill='currentColor' aria-hidden='true' focusable='false'><path d='M7 5h3.5v14H7zM13.5 5H17v14h-3.5z' /></svg>
				</button>}
			</model-viewer>

			{placing && isSelected && <div className='softicon-model-viewer__placing' role='status'>
				{__('Click on the model where the hotspot goes.', 'softicon-model-viewer')}
				<button type='button' onClick={() => setPlacing(false)}>{__('Cancel', 'softicon-model-viewer')}</button>
			</div>}

			{(isSelected || caption) && <RichText tagName='figcaption' className='wp-element-caption' placeholder={__('Add caption', 'softicon-model-viewer')}
				value={caption} onChange={val => setAttributes({ caption: val })} allowedFormats={['core/bold', 'core/italic', 'core/link']} />}
		</figure>
	</>;
};

export default Edit;
