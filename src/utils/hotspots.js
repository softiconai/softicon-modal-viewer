// hotspots: points on the model with a label and a short text; includes/hotspots.php prints the same markup on the site
export { newHotspot, pointAt } from './hotspot-points';

// the hotspot buttons inside <model-viewer>, the same as softicon_model_viewer_hotspots_html()
export const Hotspots = ({ hotspots, labels }) => hotspots.map((hotspot, i) => <button key={hotspot.id} type='button' slot={`hotspot-${hotspot.id}`}
	className='softicon-model-viewer__hotspot' data-position={hotspot.position} data-normal={hotspot.normal} data-visibility-attribute='visible'>
	<span className='softicon-model-viewer__hotspot-dot' aria-hidden='true'>{i + 1}</span>
	{hotspot.label && <span className={'softicon-model-viewer__hotspot-label' + (labels ? '' : ' is-hidden')}>{hotspot.label}</span>}
	{hotspot.text && <span className='softicon-model-viewer__hotspot-text'>{hotspot.text}</span>}
</button>);
