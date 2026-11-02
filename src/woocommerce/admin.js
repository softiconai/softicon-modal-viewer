import { __ } from '@wordpress/i18n';
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
