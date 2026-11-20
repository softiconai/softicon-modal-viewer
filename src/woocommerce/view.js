import './view.scss';

// a product's 3D model: a popup from "View in 3D" (product and shop pages) or a place in the product gallery;
// the model loads only when it shows
// each box's viewer, which gallery mode moves out of the box
const viewers = new WeakMap();

const load = viewer => {
	if (!viewer.getAttribute('src')) {
		viewer.setAttribute('src', viewer.dataset.src);
	}
};

const setupPopup = box => {
	const dialog = box.querySelector('dialog');

	box.querySelector('.softicon-model-viewer-wc__open').addEventListener('click', () => {
		const viewer = dialog.querySelector('model-viewer');
		if (viewer) {
			load(viewer);
		}
		dialog.showModal();
	});

	box.querySelector('.softicon-model-viewer-wc__close').addEventListener('click', () => dialog.close());

	// a click on the dark backdrop closes it too
	dialog.addEventListener('click', e => {
		if (e.target === dialog) {
			dialog.close();
		}
	});
};

// the product gallery the model can sit in: the Product Image Gallery block just before the box, or the classic one
const galleryFor = box => {
	const before = box.previousElementSibling;
	return before?.querySelector('.woocommerce-product-gallery') || box.closest('.product')?.querySelector('.woocommerce-product-gallery') || null;
};

// gallery mode: the model covers the main image, a 3D thumbnail and a photos/3D switch choose between them
const setupGallery = (box, gallery) => {
	const figure = box.querySelector('dialog figure');
	const viewer = figure.querySelector('model-viewer');
	const parts = box.querySelector('.softicon-model-viewer-wc__gallery-parts').content;
	const thumb = parts.querySelector('.softicon-model-viewer-wc__thumb-item').cloneNode(true);
	const toggle = parts.querySelector('.softicon-model-viewer-wc__switch').cloneNode(true);
	const stage = document.createElement('div');
	stage.className = 'softicon-model-viewer-wc__stage';
	stage.append(figure);
	box.classList.add('is-in-gallery');

	const show = on => {
		stage.hidden = !on;
		if (on) {
			load(viewer);
		}
		gallery.classList.toggle('softicon-model-viewer-wc-3d', on);
		thumb.querySelector('button').setAttribute('aria-pressed', on ? 'true' : 'false');
		toggle.textContent = on ? toggle.dataset.labelPhotos : toggle.dataset.labelModel;
	};

	// over the main image (the slider's viewport once it is built, so the stage does not slide), and in the thumbnails row
	const place = () => {
		const main = gallery.querySelector(':scope > .flex-viewport') || gallery.querySelector(':scope > .woocommerce-product-gallery__wrapper');
		if (main && stage.parentNode !== main) {
			if ('static' === getComputedStyle(main).position) {
				main.style.position = 'relative';
			}
			main.appendChild(stage);
		}
		const thumbs = gallery.querySelector('.flex-control-thumbs');
		if (thumbs && thumb.parentNode !== thumbs) {
			thumbs.appendChild(thumb);
		}
		if (toggle.parentNode !== gallery) {
			gallery.appendChild(toggle);
		}
	};

	thumb.querySelector('button').addEventListener('click', () => show(true));
	toggle.addEventListener('click', () => show(stage.hidden));
	// a photo's thumbnail brings the photos back
	gallery.addEventListener('click', event => {
		if (event.target instanceof Element && event.target.closest('.flex-control-thumbs li') && !event.target.closest('.softicon-model-viewer-wc__thumb-item')) {
			show(false);
		}
	});

	place();
	show(box.hasAttribute('data-first'));
	window.jQuery?.(gallery).on('wc-product-gallery-after-init', place);
	window.addEventListener('load', place);
};

document.querySelectorAll('[data-softicon-model-viewer-wc]').forEach(box => {
	viewers.set(box, box.querySelector('model-viewer'));
	setupPopup(box);

	const gallery = 'gallery' === box.dataset.display ? galleryFor(box) : null;
	if (gallery) {
		setupGallery(box, gallery);
		return;
	}

	// classic themes print the button after the images; it belongs under them, after the thumbnails
	const under = box.hasAttribute('data-under-gallery') && box.closest('.product')?.querySelector('.woocommerce-product-gallery');
	if (under) {
		const place = () => under.appendChild(box);
		place();
		window.addEventListener('load', place);
	}
});

// material variants: the model follows the variation shoppers choose
const norm = text => String(text || '').toLowerCase().replace(/[\s_\-.,/()]+/g, '');

const variantFor = (variants, variation, form) => {
	const chosen = variation?.softicon_model_viewer_variant;
	if (chosen && variants.includes(chosen)) {
		return chosen;
	}
	// a variant named like one of the chosen options: its slug or the text shoppers see
	const wanted = new Set(Object.values(variation?.attributes || {}).map(norm));
	form?.querySelectorAll('select[name^="attribute_"]').forEach(select => {
		const option = select.options[select.selectedIndex];
		if (option?.value) {
			wanted.add(norm(option.textContent));
		}
	});
	wanted.delete('');
	return variants.find(name => wanted.has(norm(name))) || null;
};

const applyVariant = (form, variation) => {
	const scope = form.closest('.product')?.querySelector('[data-softicon-model-viewer-wc][data-variants]') ? form.closest('.product') : document;
	scope.querySelectorAll('[data-softicon-model-viewer-wc][data-variants]').forEach(box => {
		let variants = [];
		try {
			variants = JSON.parse(box.dataset.variants) || [];
		} catch (e) {
			variants = [];
		}
		const viewer = viewers.get(box);
		const name = variation ? variantFor(variants, variation, form) : null;
		if (!viewer) {
			return;
		}
		if (name) {
			viewer.setAttribute('variant-name', name);
		} else {
			viewer.removeAttribute('variant-name');
		}
	});
};

if (window.jQuery && document.querySelector('[data-softicon-model-viewer-wc][data-variants]')) {
	window.jQuery(document)
		.on('found_variation', 'form.variations_form', (event, variation) => applyVariant(event.currentTarget, variation))
		.on('reset_data', 'form.variations_form', event => applyVariant(event.currentTarget, null));
}
