import { __ } from '@wordpress/i18n';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, TextareaControl, TextControl } from '@wordpress/components';
import { FieldHead, HelpCards, Panel, ToggleRow } from '../../../../vendor/softicon-tools/components/Panels';
import { helpCards } from '../../../../utils/options';

const General = ({ attributes, setAttributes }) => {
	const { alt, posterUrl, posterId, cameraControls, autoRotate, interactionPrompt, ar, arLabel } = attributes;
	const set = key => val => setAttributes({ [key]: val });

	return <>
		<Panel id='help' title={__('Help', 'softicon-model-viewer')}>
			<HelpCards cards={helpCards} />
			<p className='softicon-model-viewer__email'>{__('Email:', 'softicon-model-viewer')} <a href='mailto:softiconai@gmail.com'>softiconai@gmail.com</a></p>
		</Panel>

		<Panel id='model' title={__('Model', 'softicon-model-viewer')}>
			<div>
				<FieldHead label={__('Alternative text', 'softicon-model-viewer')} tip={__('Describe the model for people who cannot see it.', 'softicon-model-viewer')} />
				<TextareaControl label={__('Alternative text', 'softicon-model-viewer')} hideLabelFromVision value={alt} onChange={set('alt')} __nextHasNoMarginBottom />
			</div>

			<div className='softicon-model-viewer__stack'>
				<FieldHead label={__('Poster image', 'softicon-model-viewer')} tip={__('Shown while the model loads.', 'softicon-model-viewer')} />
				{posterUrl && <img className='softicon-model-viewer__poster' src={posterUrl} alt='' />}
				<div className='softicon-model-viewer__buttons'>
					<MediaUploadCheck>
						<MediaUpload allowedTypes={['image']} value={posterId} onSelect={media => setAttributes({ posterUrl: media.url, posterId: media.id })}
							render={({ open }) => <Button variant='secondary' onClick={open}>{posterUrl ? __('Replace poster', 'softicon-model-viewer') : __('Set poster', 'softicon-model-viewer')}</Button>} />
					</MediaUploadCheck>
					{posterUrl && <Button variant='tertiary' isDestructive onClick={() => setAttributes({ posterUrl: '', posterId: undefined })}>{__('Remove', 'softicon-model-viewer')}</Button>}
				</div>
			</div>
		</Panel>

		<Panel id='interaction' title={__('Interaction', 'softicon-model-viewer')}>
			<ToggleRow label={__('Let visitors rotate and zoom', 'softicon-model-viewer')} checked={cameraControls} onChange={set('cameraControls')} />

			<ToggleRow label={__('Rotate automatically', 'softicon-model-viewer')} checked={autoRotate} onChange={set('autoRotate')} />

			<ToggleRow label={__('Show a hint to drag', 'softicon-model-viewer')} checked={interactionPrompt} onChange={set('interactionPrompt')} />

			<ToggleRow label={__('View in your room (AR)', 'softicon-model-viewer')} tip={__('The button appears on phones and tablets that support augmented reality.', 'softicon-model-viewer')} checked={ar} onChange={set('ar')} />

			{ar && <div>
				<FieldHead label={__('AR button text', 'softicon-model-viewer')} />
				<TextControl label={__('AR button text', 'softicon-model-viewer')} hideLabelFromVision placeholder={__('View in your room', 'softicon-model-viewer')} value={arLabel} onChange={set('arLabel')} __next40pxDefaultSize __nextHasNoMarginBottom />
			</div>}
		</Panel>
	</>;
};

export default General;
