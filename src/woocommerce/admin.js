import { __ } from '@wordpress/i18n';
import { newHotspot, pointAt } from '../utils/hotspot-points';
import './admin.scss';

// the "3D model" tab on the product screen: pick the model and the poster from the media library
document.querySelectorAll('.softicon-model-viewer-wc-media').forEach(field => {
	const input = field.querySelector('input[type="hidden"]');
	const preview = field.querySelector('.softicon-model-viewer-wc-media__preview');
	const choose = field.querySelector('.softicon-model-viewer-wc-media__choose');
	const remove = field.querySelector('.softicon-model-viewer-wc-media__remove');
	const isImage = 'image' === field.dataset.type;
	let frame;

	const show = media => {
		input.value = media ? media.id : '';
		preview.replaceChildren();
		if (media && isImage) {
			const img = document.createElement('img');
			img.src = media.sizes?.thumbnail?.url || media.url;
			img.alt = '';
			preview.appendChild(img);
		} else if (media) {
			preview.textContent = media.filename || media.url;
		}
		choose.textContent = media ? choose.dataset.replace : choose.dataset.choose;
		remove.hidden = !media;
		// the hotspot editor shows the chosen model
		if (!isImage) {
			document.dispatchEvent(new CustomEvent('softicon-model-viewer:model', { detail: media ? media.url : '' }));
		}
	};

	choose.addEventListener('click', e => {
		e.preventDefault();
		if (!frame) {
			frame = window.wp.media({
				title: isImage ? __('Choose a poster image', 'softicon-model-viewer') : __('Choose a 3D model', 'softicon-model-viewer'),
				library: { type: isImage ? 'image' : ['model/gltf-binary', 'model/gltf+json'] },
				button: { text: __('Use this file', 'softicon-model-viewer') },
				multiple: false
			});
			frame.on('select', () => show(frame.state().get('selection').first().toJSON()));
		}
		frame.open();
	});

	remove.addEventListener('click', e => {
		e.preventDefault();
		show(null);
	});
});

// the hotspots of the product's model: "Add hotspot", then a click on the model places one; the list names them.
// Everything goes into one hidden field as JSON, checked again by softicon_model_viewer_wc_save().
const box = document.querySelector('.softicon-model-viewer-wc-hotspots');
if (box) {
	const input = box.querySelector('input[name="softicon_model_viewer_hotspots"]');
	const editor = box.querySelector('.softicon-model-viewer-wc-hotspots__editor');
	const none = box.querySelector('.softicon-model-viewer-wc-hotspots__none');
	const stage = box.querySelector('.softicon-model-viewer-wc-hotspots__stage');
	const viewer = stage.querySelector('model-viewer');
	const note = stage.querySelector('.softicon-model-viewer-wc-hotspots__placing');
	const add = box.querySelector('.softicon-model-viewer-wc-hotspots__add');
	const list = box.querySelector('.softicon-model-viewer-wc-hotspots__list');
	const labels = box.querySelector('.softicon-model-viewer-wc-hotspots__labels input');
	let hotspots = [];
	try {
		hotspots = JSON.parse(input.value) || [];
	} catch (e) {
		hotspots = [];
	}
	let placing = false;

	const el = (tag, props = {}, ...children) => {
		const node = Object.assign(document.createElement(tag), props);
		node.append(...children.filter(Boolean));
		return node;
	};
	const save = () => {
		input.value = JSON.stringify(hotspots);
	};

	// the points on the model, the same markup as softicon_model_viewer_hotspots_html()
	const drawPoints = () => {
		viewer.querySelectorAll('.softicon-model-viewer__hotspot').forEach(node => node.remove());
		hotspots.forEach((hotspot, i) => {
			const button = el('button', { type: 'button', className: 'softicon-model-viewer__hotspot', slot: 'hotspot-' + hotspot.id },
				el('span', { className: 'softicon-model-viewer__hotspot-dot', textContent: String(i + 1) }),
				hotspot.label && el('span', { className: 'softicon-model-viewer__hotspot-label' + (labels.checked ? '' : ' is-hidden'), textContent: hotspot.label }),
				hotspot.text && el('span', { className: 'softicon-model-viewer__hotspot-text', textContent: hotspot.text }));
			button.dataset.position = hotspot.position;
			button.dataset.normal = hotspot.normal || '';
			button.dataset.visibilityAttribute = 'visible';
			viewer.append(button);
		});
	};

	// Enter in a field would submit the whole product form
	const noSubmit = event => 'Enter' === event.key && 'INPUT' === event.target.tagName && event.preventDefault();

	const drawList = () => {
		list.replaceChildren(...hotspots.map((hotspot, i) => {
			const label = el('input', { type: 'text', value: hotspot.label, placeholder: __('Label', 'softicon-model-viewer'), onkeydown: noSubmit });
			label.setAttribute('aria-label', __('Label', 'softicon-model-viewer'));
			label.addEventListener('input', () => {
				hotspot.label = label.value;
				save();
				drawPoints();
			});
			const text = el('textarea', { rows: 2, value: hotspot.text, placeholder: __('Text, shown when the point is clicked (optional)', 'softicon-model-viewer') });
			text.setAttribute('aria-label', __('Text', 'softicon-model-viewer'));
			text.addEventListener('input', () => {
				hotspot.text = text.value;
				save();
				drawPoints();
			});
			const remove = el('button', { type: 'button', className: 'button-link button-link-delete', textContent: __('Remove', 'softicon-model-viewer') });
			remove.addEventListener('click', () => {
				hotspots = hotspots.filter(h => h !== hotspot);
				save();
				drawPoints();
				drawList();
			});
			return el('li', {}, el('span', { className: 'softicon-model-viewer-wc-hotspots__number', textContent: String(i + 1) }), el('div', {}, label, text), remove);
		}));
	};

	const setPlacing = on => {
		placing = on;
		note.hidden = !on;
		add.disabled = on;
		stage.classList.toggle('is-placing', on);
	};

	add.addEventListener('click', () => setPlacing(true));
	note.querySelector('button').addEventListener('click', () => setPlacing(false));
	viewer.addEventListener('click', event => {
		if (!placing) {
			return;
		}
		const point = pointAt(viewer, event);
		if (!point) {
			return;
		}
		hotspots.push(newHotspot(point, hotspots.length + 1));
		save();
		drawPoints();
		drawList();
		setPlacing(false);
		list.querySelector('li:last-child input')?.select();
	});
	labels.addEventListener('change', drawPoints);

	// the model loads once the tab shows: loaded while hidden, model-viewer frames it at the wrong size
	let model = '';
	const load = () => {
		if (model && box.offsetParent && viewer.getAttribute('src') !== model) {
			viewer.setAttribute('src', model);
		}
	};
	const setModel = url => {
		model = url || '';
		editor.hidden = !model;
		none.hidden = !!model;
		setPlacing(false);
		load();
	};
	document.querySelector('li.softicon_model_viewer_tab a')?.addEventListener('click', () => setTimeout(load));
	document.addEventListener('softicon-model-viewer:model', event => setModel(event.detail));
	setModel(box.dataset.model);
	drawPoints();
	drawList();
}
