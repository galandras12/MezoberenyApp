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
	 * Vissza gomb – app szerű viselkedés
	 * ------------------------------------------------------------------ */
	function initBack() {
		var back = document.querySelector('[data-mb-back]');

		if (!back) {
			return;
		}

		back.addEventListener('click', function (event) {
			// Ha van hova visszalépni ezen az oldalon belül, azt használjuk.
			var sameOrigin = document.referrer && document.referrer.indexOf(window.location.origin) === 0;
			var navigated = document.body.dataset.mbappNavigated === '1';

			if ((sameOrigin || navigated) && window.history.length > 1) {
				event.preventDefault();
				window.history.back();
			}
			// Egyébként marad a href (archívum vagy kezdőlap).
		});
	}

	/* ------------------------------------------------------------------
	 * Diavetítés
	 * ------------------------------------------------------------------ */
	function initSlider() {
		var slider = document.querySelector('.mb-slider');

		if (!slider) {
			return;
		}

		var track = slider.querySelector('[data-mb-slider-track]');
		var slides = track ? Array.prototype.slice.call(track.children) : [];

		if (!track || slides.length < 2) {
			return;
		}

		var dots = Array.prototype.slice.call(slider.querySelectorAll('.mb-slider__dot'));
		var index = 0;
		var timer = null;

		function reducedMotion() {
			return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		}

		function goTo(next) {
			index = (next + slides.length) % slides.length;

			track.scrollTo({
				left: slides[index].offsetLeft,
				behavior: reducedMotion() ? 'auto' : 'smooth'
			});

			dots.forEach(function (dot, i) {
				dot.classList.toggle('is-active', i === index);
			});
		}

		function start() {
			if (slider.dataset.autoplay !== '1' || reducedMotion()) {
				return;
			}

			stop();
			timer = window.setInterval(function () {
				goTo(index + 1);
			}, parseInt(slider.dataset.interval, 10) || 5000);
		}

		function stop() {
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
		}

		var prev = slider.querySelector('[data-mb-slider-prev]');
		var next = slider.querySelector('[data-mb-slider-next]');

		if (prev) {
			prev.addEventListener('click', function () {
				goTo(index - 1);
				start();
			});
		}

		if (next) {
			next.addEventListener('click', function () {
				goTo(index + 1);
				start();
			});
		}

		dots.forEach(function (dot) {
			dot.addEventListener('click', function () {
				goTo(parseInt(dot.dataset.index, 10) || 0);
				start();
			});
		});

		// Kézi görgetés esetén kövessük, melyik dia látszik.
		var scrollTimer = null;

		track.addEventListener('scroll', function () {
			window.clearTimeout(scrollTimer);

			scrollTimer = window.setTimeout(function () {
				var closest = 0;
				var smallest = Infinity;

				slides.forEach(function (slide, i) {
					var distance = Math.abs(slide.offsetLeft - track.scrollLeft);

					if (distance < smallest) {
						smallest = distance;
						closest = i;
					}
				});

				index = closest;
				dots.forEach(function (dot, i) {
					dot.classList.toggle('is-active', i === index);
				});
			}, 120);
		}, { passive: true });

		// Ne váltson, amíg a látogató a diavetítés fölött van.
		slider.addEventListener('mouseenter', stop);
		slider.addEventListener('mouseleave', start);
		slider.addEventListener('focusin', stop);
		slider.addEventListener('touchstart', stop, { passive: true });

		document.addEventListener('visibilitychange', function () {
			if (document.hidden) {
				stop();
			} else {
				start();
			}
		});

		start();
	}

	/* ------------------------------------------------------------------
	 * Indítás
	 * ------------------------------------------------------------------ */
	function init() {
		initThemeToggle();
		initSearch();
		initDock();
		initBack();
		initSlider();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	// AJAX navigáció után az app bar, a lebegő menü és a lábléc a helyén marad,
	// ezért nincs mit újra bekötni – csak jelezzük, hogy már léptünk oldalt.
	document.addEventListener('mbapp:navigated', function () {
		document.body.dataset.mbappNavigated = '1';

		// A tartalommal együtt a diavetítés is kicserélődhetett.
		initSlider();
	});
})();
