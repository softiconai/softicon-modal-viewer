// The opt-in card (includes/opt-in.php): Allow & Continue asks this site to send the opt-in to Freemius.
// If the host does not let the site reach Freemius, the browser posts the same fields to wp.freemius.com itself
// and Freemius sends it back to this page. Plain JavaScript, no build step.
(function () {
	const card = document.querySelector('.smv-optin');
	const allow = card?.querySelector('.smv-optin-allow');
	if (!allow) {
		return;
	}
	const error = card.querySelector('.smv-optin-error');
	const label = allow.textContent;

	const sendFromBrowser = ({ action, fields }) => {
		const form = document.createElement('form');
		form.method = 'post';
		form.action = action;
		Object.entries(fields).forEach(([name, value]) => {
			const input = document.createElement('input');
			input.type = 'hidden';
			input.name = name;
			input.value = 'boolean' === typeof value ? (value ? '1' : '0') : String(value ?? '');
			form.append(input);
		});
		document.body.append(form);
		form.submit();
	};

	allow.addEventListener('click', () => {
		allow.disabled = true;
		allow.setAttribute('aria-busy', 'true');
		allow.textContent = card.dataset.busy;
		error.hidden = true;
		fetch(card.dataset.ajax, {
			method: 'POST',
			credentials: 'same-origin',
			body: new URLSearchParams({ action: card.dataset.action, _ajax_nonce: card.dataset.nonce })
		})
			.then(response => response.json())
			.then(result => {
				if (result.success && result.data.browser) {
					sendFromBrowser(result.data.browser);
				} else if (result.success) {
					window.location.href = result.data.next;
				} else {
					throw new Error();
				}
			})
			.catch(() => {
				allow.disabled = false;
				allow.removeAttribute('aria-busy');
				allow.textContent = label;
				error.textContent = card.dataset.error;
				error.hidden = false;
			});
	});
}());
