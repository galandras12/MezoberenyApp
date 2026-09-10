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

	/* ==================================================================
	 * AJAX oldalváltás – app szerű, animált navigáció
	 * ================================================================== */
	var nav = (window.MBApp && window.MBApp.nav) || {};
	var navigating = false;

	function reducedMotion() {
		return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	}

	function supportsAjaxNav() {
		return !!(nav.enabled &&
			window.history && window.history.pushState &&
			window.fetch && window.DOMParser);
	}

	function viewContainer(doc) {
		var selectors = (nav.container || '[data-mbapp-view], main').split(',');

		for (var i = 0; i < selectors.length; i++) {
			var node = (doc || document).querySelector(selectors[i].trim());

			if (node) {
				return node;
			}
		}

		return null;
	}

	/* --- Folyamatjelző csík --- */
	function progress(state) {
		if (!nav.progressBar) {
			return;
		}

		var bar = document.querySelector('.mbapp-progress');

		if (!bar) {
			bar = document.createElement('div');
			bar.className = 'mbapp-progress';
			bar.innerHTML = '<span class="mbapp-progress__fill"></span>';
			document.body.appendChild(bar);
		}

		bar.classList.toggle('is-active', state === 'start');

		if (state === 'done') {
			bar.classList.add('is-done');
			window.setTimeout(function () {
				bar.classList.remove('is-done');
			}, 320);
		}
	}

	/* --- Ki- és beúsztatás --- */
	function animateOut(node) {
		var type = nav.animation || 'fade';
		var duration = reducedMotion() ? 0 : (nav.duration || 280);

		return new Promise(function (resolve) {
			if (type === 'none' || !duration) {
				resolve();
				return;
			}

			node.style.transition = 'opacity ' + duration + 'ms ' + (nav.easing || 'ease') +
				', transform ' + duration + 'ms ' + (nav.easing || 'ease');
			node.classList.add('mbapp-view--leave-' + type);

			window.setTimeout(resolve, duration);
		});
	}

	function animateIn(node) {
		var type = nav.animation || 'fade';
		var duration = reducedMotion() ? 0 : (nav.duration || 280);

		node.classList.remove(
			'mbapp-view--leave-fade',
			'mbapp-view--leave-slide',
			'mbapp-view--leave-slide-up',
			'mbapp-view--leave-scale'
		);

		if (type === 'none' || !duration) {
			node.style.transition = '';
			return;
		}

		node.classList.add('mbapp-view--enter-' + type);

		// Kényszerített újrarajzolás, hogy a kezdőállapot érvényre jusson.
		void node.offsetWidth;

		node.style.transition = 'opacity ' + duration + 'ms ' + (nav.easing || 'ease') +
			', transform ' + duration + 'ms ' + (nav.easing || 'ease');
		node.classList.remove('mbapp-view--enter-' + type);

		window.setTimeout(function () {
			node.style.transition = '';
		}, duration);
	}

	/* --- Az aktív menüpont frissítése --- */
	function syncDock(url) {
		var here = url.replace(/[?#].*$/, '').replace(/\/$/, '');

		document.querySelectorAll('.mb-dock__item').forEach(function (item) {
			var href = (item.getAttribute('href') || '').replace(/[?#].*$/, '').replace(/\/$/, '');
			var active = href === here;

			item.classList.toggle('is-active', active);

			if (active) {
				item.setAttribute('aria-current', 'page');
			} else {
				item.removeAttribute('aria-current');
			}
		});
	}

	function loadPage(url, push) {
		var current = viewContainer(document);

		if (!current || navigating) {
			return;
		}

		navigating = true;
		progress('start');

		var request = fetch(url, {
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('HTTP ' + response.status);
			}
			return response.text();
		});

		Promise.all([request, animateOut(current)])
			.then(function (results) {
				var doc = new DOMParser().parseFromString(results[0], 'text/html');
				var next = viewContainer(doc);

				if (!next) {
					throw new Error('no-container');
				}

				current.innerHTML = next.innerHTML;

				var title = doc.querySelector('title');
				if (title) {
					document.title = title.textContent;
				}

				// A body osztályai oldaltípusonként változnak (pl. home, single, archive),
				// ezért átvesszük őket – de a saját jelzőosztályunkat megtartjuk.
				if (doc.body && doc.body.className) {
					document.body.className = doc.body.className;
					document.body.classList.add('mbapp-ajax-nav');
				}

				if (push) {
					window.history.pushState({ mbapp: true }, '', url);
				}

				window.scrollTo({ top: 0, behavior: reducedMotion() ? 'auto' : 'smooth' });

				animateIn(current);
				syncDock(url);

				// Képernyőolvasónak is jelezzük, hogy új tartalom érkezett.
				current.setAttribute('tabindex', '-1');
				current.focus({ preventScroll: true });
				progress('done');
				navigating = false;

				// A többi szkript (téma, események) újra bekötheti magát.
				document.dispatchEvent(new CustomEvent('mbapp:navigated', {
					detail: { url: url, container: current }
				}));

				document.querySelectorAll('.mbapp-events').forEach(initLoadMore);
				initHeroes();
			})
			.catch(function () {
				// Bármi gond van, marad a hagyományos oldalbetöltés.
				navigating = false;
				progress('done');
				window.location.href = url;
			});
	}

	function isInternal(link) {
		if (!link || link.target === '_blank' || link.hasAttribute('download')) {
			return false;
		}

		if (link.origin !== window.location.origin) {
			return false;
		}

		var href = link.getAttribute('href') || '';

		if (!href || href.charAt(0) === '#' || /^(mailto:|tel:|javascript:)/i.test(href)) {
			return false;
		}

		// Csak a nézetre mutató oldalak: fájlokat és wp-admin címeket kihagyjuk.
		if (/\/wp-admin\/|\/wp-login\.php|\.(jpe?g|png|gif|webp|svg|pdf|zip|docx?|xlsx?)$/i.test(link.pathname)) {
			return false;
		}

		// Ugyanaz az oldal, csak horgonnyal.
		if (link.pathname === window.location.pathname && link.search === window.location.search) {
			return false;
		}

		return true;
	}

	function initAjaxNav() {
		if (!supportsAjaxNav() || !viewContainer(document)) {
			return;
		}

		document.body.classList.add('mbapp-ajax-nav');

		document.addEventListener('click', function (event) {
			if (event.defaultPrevented || event.button !== 0 ||
				event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
				return;
			}

			var link = event.target.closest('a');

			if (!link || !isInternal(link)) {
				return;
			}

			if (nav.scope === 'menu' && !link.hasAttribute('data-mbapp-link')) {
				return;
			}

			event.preventDefault();
			loadPage(link.href, true);
		});

		window.addEventListener('popstate', function (event) {
			if (event.state && event.state.mbapp) {
				loadPage(window.location.href, false);
			}
		});

		// A kiindulási állapotot is elmentjük, hogy a vissza gomb működjön.
		window.history.replaceState({ mbapp: true }, '', window.location.href);
	}

	/* ==================================================================
	 * Fejléc diavetítés (hero)
	 * ================================================================== */
	function initHero(hero) {
		var slides = Array.prototype.slice.call(hero.querySelectorAll('.mbapp-hero__slide'));

		if (slides.length < 2) {
			return;
		}

		var dots = Array.prototype.slice.call(hero.querySelectorAll('.mbapp-hero__dot'));
		var index = 0;
		var timer = null;

		function show(next) {
			index = (next + slides.length) % slides.length;

			slides.forEach(function (slide, i) {
				slide.classList.toggle('is-active', i === index);
			});

			dots.forEach(function (dot, i) {
				dot.classList.toggle('is-active', i === index);
			});
		}

		function start() {
			stop();

			if (hero.dataset.autoplay !== '1' || reducedMotion()) {
				return;
			}

			timer = window.setInterval(function () {
				show(index + 1);
			}, parseInt(hero.dataset.interval, 10) || 6000);
		}

        function stop() {
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
		}

		var prev = hero.querySelector('[data-mbapp-hero-prev]');
		var next = hero.querySelector('[data-mbapp-hero-next]');

		if (prev) {
			prev.addEventListener('click', function () {
				show(index - 1);
				start();
			});
		}

		if (next) {
			next.addEventListener('click', function () {
				show(index + 1);
				start();
			});
		}

		dots.forEach(function (dot) {
			dot.addEventListener('click', function () {
				show(parseInt(dot.dataset.index, 10) || 0);
				start();
			});
		});

		hero.addEventListener('mouseenter', stop);
		hero.addEventListener('mouseleave', start);
		hero.addEventListener('focusin', stop);

		document.addEventListener('visibilitychange', function () {
			if (document.hidden) {
				stop();
			} else if (document.body.contains(hero)) {
				start();
			}
		});

		start();
	}

	function initHeroes() {
		document.querySelectorAll('[data-mbapp-hero]').forEach(initHero);
	}

	function init() {
		document.querySelectorAll('.mbapp-events').forEach(initLoadMore);
		initHeroes();
		initAjaxNav();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
