/**
 * MBapp Theme – felület vezérlés
 *
 * - sötét / világos téma kapcsoló (localStorage + rendszerbeállítás)
 * - kereső panel
 * - lebegő menü viselkedése görgetéskor
 * - aktív menüpont jelölése
 */
(function () {
	'use strict';

	var STORAGE_KEY = (window.MBAppTheme && window.MBAppTheme.storageKey) || 'mbapp-color-scheme';
	var root = document.documentElement;

	/* ------------------------------------------------------------------
	 * Téma kapcsoló
	 * ------------------------------------------------------------------ */
	function storedScheme() {
		try {
			return window.localStorage.getItem(STORAGE_KEY);
		} catch (e) {
			return null;
		}
	}

	function storeScheme(value) {
		try {
			if (value) {
				window.localStorage.setItem(STORAGE_KEY, value);
			} else {
				window.localStorage.removeItem(STORAGE_KEY);
			}
		} catch (e) {
			/* privát mód – nem tudjuk elmenteni, de működik tovább */
		}
	}

	function systemScheme() {
		return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
			? 'dark'
			: 'light';
	}

	function currentScheme() {
		return root.getAttribute('data-theme') || systemScheme();
	}

	function applyScheme(scheme, persist) {
		root.classList.add('mb-theme-switching');
		root.setAttribute('data-theme', scheme);

		if (persist) {
			storeScheme(scheme);
		}

		var meta = document.querySelector('meta[name="theme-color"]');
		if (meta) {
			meta.setAttribute('content', scheme === 'dark' ? '#0c1017' : '#eef1f6');
		}

		document.querySelectorAll('.mb-theme-toggle').forEach(function (btn) {
			btn.setAttribute('aria-pressed', scheme === 'dark' ? 'true' : 'false');
			var label = scheme === 'dark'
				? (window.MBAppTheme && window.MBAppTheme.i18n && window.MBAppTheme.i18n.toLight) || 'Világos téma bekapcsolása'
				: (window.MBAppTheme && window.MBAppTheme.i18n && window.MBAppTheme.i18n.toDark) || 'Sötét téma bekapcsolása';
			btn.setAttribute('aria-label', label);
			btn.setAttribute('title', label);
		});

		window.setTimeout(function () {
			root.classList.remove('mb-theme-switching');
		}, 60);

		document.dispatchEvent(new CustomEvent('mbapp:schemechange', { detail: { scheme: scheme } }));
	}

	function initThemeToggle() {
		var saved = storedScheme();
		applyScheme(saved === 'dark' || saved === 'light' ? saved : systemScheme(), false);

		document.addEventListener('click', function (event) {
			var btn = event.target.closest('.mb-theme-toggle');
			if (!btn) {
				return;
			}
			event.preventDefault();
			applyScheme(currentScheme() === 'dark' ? 'light' : 'dark', true);
		});

		// Ha a felhasználó még nem választott, kövessük a rendszert.
		if (window.matchMedia) {
			var mq = window.matchMedia('(prefers-color-scheme: dark)');
			var listener = function (e) {
				if (!storedScheme()) {
					applyScheme(e.matches ? 'dark' : 'light', false);
				}
			};
			if (mq.addEventListener) {
				mq.addEventListener('change', listener);
			} else if (mq.addListener) {
				mq.addListener(listener);
			}
		}
	}

	/* ------------------------------------------------------------------
	 * Kereső panel
	 * ------------------------------------------------------------------ */
	function initSearch() {
		var toggle = document.querySelector('.mb-search-toggle');
		var panel = document.getElementById('mb-searchpanel');

		if (!toggle || !panel) {
			return;
		}

		toggle.addEventListener('click', function () {
			var open = panel.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) {
				var input = panel.querySelector('input[type="search"]');
				if (input) {
					input.focus();
				}
			}
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && panel.classList.contains('is-open')) {
				panel.classList.remove('is-open');
				toggle.setAttribute('aria-expanded', 'false');
				toggle.focus();
			}
		});
	}

	/* ------------------------------------------------------------------
	 * Lebegő menü – görgetéskor elrejtés, aktív elem
	 * ------------------------------------------------------------------ */
	function initDock() {
		var dock = document.querySelector('.mb-dock');
		if (!dock) {
			return;
		}

		// Aktív elem jelölése az aktuális URL alapján.
		var here = window.location.href.replace(/[?#].*$/, '').replace(/\/$/, '');
		dock.querySelectorAll('.mb-dock__item').forEach(function (item) {
			if (item.classList.contains('is-active')) {
				return;
			}
			var href = (item.getAttribute('href') || '').replace(/[?#].*$/, '').replace(/\/$/, '');
			if (href && href === here) {
				item.classList.add('is-active');
				item.setAttribute('aria-current', 'page');
			}
		});

		if (dock.dataset.autohide === '0') {
			return;
		}

		var lastY = window.pageYOffset;
		var ticking = false;

		function onScroll() {
			var y = window.pageYOffset;
			if (Math.abs(y - lastY) > 12) {
				dock.classList.toggle('is-hidden', y > lastY && y > 160);
				lastY = y;
			}
			ticking = false;
		}

		window.addEventListener('scroll', function () {
			if (!ticking) {
				window.requestAnimationFrame(onScroll);
				ticking = true;
			}
		}, { passive: true });
	}

	/* ------------------------------------------------------------------
	 * Indítás
	 * ------------------------------------------------------------------ */
	function init() {
		initThemeToggle();
		initSearch();
		initDock();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
