// The review request on the dashboard (includes/dashboard.php): saves the answer and hides it. Plain JavaScript.
(function () {
	const box = document.querySelector('.smv-dash-review');
	if (!box) {
		return;
	}
	box.querySelectorAll('[data-answer]').forEach(button => {
		button.addEventListener('click', () => {
			const body = new URLSearchParams({ action: 'softicon_model_viewer_review', _ajax_nonce: box.dataset.nonce, answer: button.dataset.answer });
			fetch(box.dataset.ajax, { method: 'POST', credentials: 'same-origin', body }).catch(() => {});
			// "Leave a review" still opens WordPress.org in a new tab
			box.hidden = true;
		});
	});
})();
