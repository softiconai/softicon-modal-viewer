import { __ } from '@wordpress/i18n';
import { RangeControl, TextControl, __experimentalUnitControl as UnitControl } from '@wordpress/components';
import { FieldHead, Panel, Reset, Tiles } from '../../../../vendor/softicon-tools/components/Panels';
import { environments } from '../../../../utils/options';

const units = [{ value: 'px', label: 'px', default: 520 }, { value: 'vh', label: 'vh', default: 70 }];

const Style = ({ attributes, setAttributes }) => {
	const { height, exposure, shadowIntensity, environment, cameraOrbit } = attributes;
	const set = key => val => setAttributes({ [key]: val });

	return <>
		<Panel id='size' title={__('Size', 'softicon-model-viewer')}>
			<div>
				<FieldHead label={__('Height', 'softicon-model-viewer')} action={<Reset onClick={() => setAttributes({ height: '520px' })} disabled={'520px' === height} />} />
				<UnitControl label={__('Height', 'softicon-model-viewer')} hideLabelFromVision value={height} units={units} onChange={val => setAttributes({ height: val || '520px' })} __next40pxDefaultSize />
			</div>
		</Panel>

		<Panel id='lighting' title={__('Lighting', 'softicon-model-viewer')}>
			<Tiles label={__('Light style', 'softicon-model-viewer')} value={environment} options={environments} onChange={set('environment')} />

			<div>
				<FieldHead label={__('Brightness', 'softicon-model-viewer')} action={<Reset onClick={() => setAttributes({ exposure: 1 })} disabled={1 === exposure} />} />
				<RangeControl label={__('Brightness', 'softicon-model-viewer')} hideLabelFromVision value={exposure} onChange={val => setAttributes({ exposure: undefined === val ? 1 : val })} min={0.2} max={2} step={0.05} __nextHasNoMarginBottom />
			</div>

			<div>
				<FieldHead label={__('Shadow', 'softicon-model-viewer')} action={<Reset onClick={() => setAttributes({ shadowIntensity: 1 })} disabled={1 === shadowIntensity} />} />
				<RangeControl label={__('Shadow', 'softicon-model-viewer')} hideLabelFromVision value={shadowIntensity} onChange={val => setAttributes({ shadowIntensity: undefined === val ? 1 : val })} min={0} max={2} step={0.05} __nextHasNoMarginBottom />
			</div>
		</Panel>

		<Panel id='camera' title={__('Camera', 'softicon-model-viewer')}>
			<div>
				<FieldHead label={__('Starting angle', 'softicon-model-viewer')} tip={__('For example: 30deg 75deg 105%. Leave empty for an automatic angle.', 'softicon-model-viewer')} />
				<TextControl label={__('Starting angle', 'softicon-model-viewer')} hideLabelFromVision placeholder='30deg 75deg 105%' value={cameraOrbit} onChange={set('cameraOrbit')} __next40pxDefaultSize __nextHasNoMarginBottom />
			</div>
		</Panel>
	</>;
};

export default Style;
