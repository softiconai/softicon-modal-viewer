// the block's animation on the page: plays it once when it should, and runs the play/pause button
// listens on the document, so viewers added later (Elementor's preview, a popup) work too
const VIEWER = '.wp-block-softicon-model-viewer model-viewer';

const buttonOf = viewer => viewer.querySelector(':scope > .softicon-model-viewer__play');

const play = viewer => viewer.play(viewer.hasAttribute('data-animation-once') ? { repetitions: 1 } : undefined);

const show = (viewer, playing) => {
	const button = buttonOf(viewer);
	if (button) {
		button.classList.toggle('is-playing', playing);
		button.setAttribute('aria-label', button.dataset[playing ? 'labelPause' : 'labelPlay']);
	}
};

const ready = viewer => {
	// a model without animations keeps its button hidden
	if (!viewer.availableAnimations?.length) {
		return;
	}
	const button = buttonOf(viewer);
	if (button) {
		button.hidden = false;
	}
	if (viewer.hasAttribute('data-animation-autoplay')) {
		play(viewer);
	}
	show(viewer, !viewer.paused);
};

// model-viewer's events do not bubble, so they are caught on the way down
const on = (type, callback) => document.addEventListener(type, event => {
	if (event.target instanceof Element && event.target.matches(VIEWER)) {
		callback(event.target);
	}
}, true);

on('load', ready);
on('play', viewer => show(viewer, true));
on('pause', viewer => show(viewer, false));
on('finished', viewer => show(viewer, false));

document.addEventListener('click', event => {
	const button = event.target instanceof Element ? event.target.closest('.softicon-model-viewer__play') : null;
	const viewer = button?.closest(VIEWER);
	if (!viewer) {
		return;
	}
	if (button.classList.contains('is-playing')) {
		viewer.pause();
	} else {
		play(viewer);
	}
});

// a model that finished loading before this script ran
document.querySelectorAll(VIEWER).forEach(viewer => viewer.loaded && ready(viewer));
