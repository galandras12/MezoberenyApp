/**
 * MBapp Plugin – frontend.
 *
 * „További” gomb az eseménylistához (AJAX betöltés).
 */
(function () {
	'use strict';

	function t(key, fallback) {
		return (window.MBApp && window.MBApp.i18n && window.MBApp.i18n[key]) || fallback;
	}

	function initLoadMore(root) {
		var button = root.querySelector('[data-mbapp-loadmore]');
		var list = root.querySelector('[data-mbapp-items]');

		if (!button || !list || !window.MBApp) {
			return;
		}

		button.addEventListener('click', function () {
			var nextPage = parseInt(root.dataset.page || '1', 10) + 1;
			var original = button.textContent;

			button.disabled = true;
			button.classList.add('is-loading');
			button.textContent = t('loading', 'Betöltés…');

			var body = new URLSearchParams();
			body.append('action', 'mbapp_load_events');
			body.append('nonce', window.MBApp.nonce);
			body.append('page', nextPage);
			body.append('per_page', root.dataset.perPage || '25');
			body.append('layout', root.dataset.layout || 'grid');
			body.append('category', root.dataset.category || '');

			fetch(window.MBApp.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			})
				.then(function (response) {
					if (!response.ok) {
						throw new Error('HTTP ' + response.status);
					}
					return response.json();
				})
				.then(function (result) {
					if (!result || !result.success) {
						throw new Error('invalid');
					}

					var data = result.data;

					if (data.html) {
						var holder = document.createElement('div');
						holder.innerHTML = data.html;

						Array.prototype.forEach.call(holder.children, function (node) {
							node.classList.add('mbapp-card--in');
							list.appendChild(node);
						});
					}

					root.dataset.page = String(data.page);

					if (data.has_more) {
						button.disabled = false;
						button.classList.remove('is-loading');
						button.textContent = original;
					} else {
						button.remove();
					}
				})
				.catch(function () {
					button.disabled = false;
					button.classList.remove('is-loading');
					button.textContent = t('error', 'Nem sikerült betölteni. Próbáld újra!');

					window.setTimeout(function () {
						button.textContent = original;
					}, 3000);
				});
		});
	}

	function init() {
		document.querySelectorAll('.mbapp-events').forEach(initLoadMore);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
