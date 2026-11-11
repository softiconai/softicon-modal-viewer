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

// "Getting started": its tabs (arrow keys move between them) and the Copy buttons
(function () {
	const guide = document.querySelector('.smv-start');
	if (!guide) {
		return;
	}
	const tabs = Array.from(guide.querySelectorAll('[role="tab"]'));
	const show = tab => {
		tabs.forEach(other => {
			const on = other === tab;
			other.setAttribute('aria-selected', on ? 'true' : 'false');
			other.tabIndex = on ? 0 : -1;
			document.getElementById(other.getAttribute('aria-controls')).hidden = !on;
		});
	};
	tabs.forEach((tab, i) => {
		tab.addEventListener('click', () => show(tab));
		tab.addEventListener('keydown', e => {
			const step = { ArrowRight: 1, ArrowLeft: -1 }[e.key];
			const next = step ? tabs[(i + step + tabs.length) % tabs.length] : { Home: tabs[0], End: tabs[tabs.length - 1] }[e.key];
			if (next) {
				e.preventDefault();
				show(next);
				next.focus();
			}
		});
	});

	guide.querySelectorAll('.smv-start-copy').forEach(button => {
		const label = button.textContent;
		button.addEventListener('click', () => {
			const done = () => {
				button.textContent = button.dataset.done;
				setTimeout(() => { button.textContent = label; }, 1600);
			};
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(button.dataset.copy).then(done).catch(() => {});
				return;
			}
			// http sites: select the code and copy it the old way
			const range = document.createRange();
			range.selectNodeContents(button.previousElementSibling);
			const selection = window.getSelection();
			selection.removeAllRanges();
			selection.addRange(range);
			if (document.execCommand('copy')) {
				done();
			}
		});
	});
})();
