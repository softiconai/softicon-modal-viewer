import './view.scss';

// "View in 3D" on a product page: the model loads only when the popup opens
document.querySelectorAll('[data-softicon-model-viewer-wc]').forEach(box => {
	const dialog = box.querySelector('dialog');
	const viewer = box.querySelector('model-viewer');

	// classic themes print the button after the images; it belongs under them, after the thumbnails
	const gallery = box.hasAttribute('data-under-gallery') && box.closest('.product')?.querySelector('.woocommerce-product-gallery');
	if (gallery) {
		const place = () => gallery.appendChild(box);
		place();
		window.addEventListener('load', place);
	}

	box.querySelector('.softicon-model-viewer-wc__open').addEventListener('click', () => {
		if (!viewer.getAttribute('src')) {
			viewer.setAttribute('src', viewer.dataset.src);
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
});
