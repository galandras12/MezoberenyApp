/**
 * MBapp téma szerkesztő – admin felület.
 *
 * - fülek
 * - médiatár választó
 * - egyedi szövegblokkok (hozzáadás, törlés, sorrend)
 * - AJAX mentés pipával, az oldal újratöltése nélkül
 */
(function ($) {
	'use strict';

	function i18n(key, fallback) {
		return (window.MBAppThemeAdmin && window.MBAppThemeAdmin.i18n && window.MBAppThemeAdmin.i18n[key]) || fallback;
	}

	/* ------------------------------------------------------------------
	 * Fülek
	 * ------------------------------------------------------------------ */
	function initTabs() {
		var $tabs = $('.mbappt-tabs .nav-tab');

		if (!$tabs.length) {
			return;
		}

		function activate(target) {
			$tabs.removeClass('nav-tab-active').filter('[href="' + target + '"]').addClass('nav-tab-active');
			$('.mbappt-panel').removeClass('is-active');
			$(target).addClass('is-active');

			try {
				window.sessionStorage.setItem('mbappt-tab', target);
			} catch (e) {
				/* privát mód – nem baj */
			}
		}

		$tabs.on('click', function (event) {
			event.preventDefault();
			activate($(this).attr('href'));
		});

		var saved = null;

		try {
			saved = window.sessionStorage.getItem('mbappt-tab');
		} catch (e) {
			saved = null;
		}

		if (saved && $(saved).length) {
			activate(saved);
		}
	}

	/* ------------------------------------------------------------------
	 * Médiaválasztó
	 * ------------------------------------------------------------------ */
	function initMedia() {
		$(document).on('click', '.mbappt-media__pick', function () {
			var $media = $(this).closest('.mbappt-media');

			if (!window.wp || !window.wp.media) {
				return;
			}

			var frame = window.wp.media({
				title: i18n('pick', 'Kép kiválasztása'),
				library: { type: 'image' },
				button: { text: i18n('use', 'Kiválasztom') },
				multiple: false
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var url = attachment.url;

				if (attachment.sizes && attachment.sizes.medium) {
					url = attachment.sizes.medium.url;
				}

				$media.find('.mbappt-media__id').val(attachment.id);
				$media.find('.mbappt-media__thumb').html('<img src="' + url + '" alt="">');
			});

			frame.open();
		});

		$(document).on('click', '.mbappt-media__clear', function () {
			var $media = $(this).closest('.mbappt-media');

			$media.find('.mbappt-media__id').val('');
			$media.find('.mbappt-media__thumb').empty();
		});
	}

	/* ------------------------------------------------------------------
	 * Egyedi szövegblokkok
	 * ------------------------------------------------------------------ */
	function reindexTexts() {
		$('#mbappt-texts-list .mbappt-item').each(function (index) {
			$(this).attr('data-index', index);

			$(this).find('input, select, textarea').each(function () {
				var name = $(this).attr('name');

				if (name) {
					$(this).attr('name', name.replace(/\[texts\]\[[^\]]*\]/, '[texts][' + index + ']'));
				}
			});
		});
	}

	function initTexts() {
		var $list = $('#mbappt-texts-list');

		if (!$list.length) {
			return;
		}

		$('#mbappt-add-text').on('click', function () {
			var template = $('#tmpl-mbappt-text').html();

			if (!template) {
				return;
			}

			var index = $list.find('.mbappt-item').length;
			var $row = $(template.replace(/__INDEX__/g, String(index)));

			$list.append($row);
			reindexTexts();
			$row.find('input[type="text"]').first().trigger('focus');
		});

		$list.on('click', '.mbappt-item__remove', function () {
			if (!window.confirm(i18n('confirm', 'Biztosan törlöd?'))) {
				return;
			}

			$(this).closest('.mbappt-item').remove();
			reindexTexts();
		});

		if ($.fn.sortable) {
			$list.sortable({
				handle: '.mbappt-item__handle',
				axis: 'y',
				placeholder: 'mbappt-item-placeholder',
				update: reindexTexts
			});
		}
	}

	/* ------------------------------------------------------------------
	 * AJAX mentés
	 * ------------------------------------------------------------------ */
	function initSave() {
		var $form = $('.mbappt-form');

		if (!$form.length || !window.MBAppThemeAdmin) {
			return;
		}

		$form.on('submit', function (event) {
			event.preventDefault();

			var $submit = $form.find('[type="submit"]');
			var $badge = $form.find('.mbappt-saved');

			if (!$badge.length) {
				$badge = $('<span class="mbappt-saved" role="status" aria-live="polite"></span>');
				$submit.parent().append($badge);
			}

			function show(state, text) {
				var icon = '';

				if (state === 'saved') {
					icon = '<span class="mbappt-saved__check" aria-hidden="true">✓</span>';
				} else if (state === 'busy') {
					icon = '<span class="mbappt-spinner" aria-hidden="true"></span>';
				}

				$badge
					.removeClass('mbappt-saved--error mbappt-saved--busy')
					.addClass(state === 'error' ? 'mbappt-saved--error' : (state === 'busy' ? 'mbappt-saved--busy' : ''))
					.html(icon + '<span>' + $('<div>').text(text).html() + '</span>')
					.addClass('is-visible');
			}

			$submit.prop('disabled', true);
			show('busy', i18n('saving', 'Mentés…'));

			// A hagyományos beküldés mezői nem kellenek az AJAX híváshoz.
			var data = $form.serializeArray().filter(function (field) {
				return field.name !== 'action' && field.name !== '_wpnonce' && field.name !== '_wp_http_referer';
			});

			data.push({ name: 'action', value: 'mbapp_theme_save' });
			data.push({ name: 'nonce', value: window.MBAppThemeAdmin.nonce });

			$.post(window.MBAppThemeAdmin.ajaxUrl, $.param(data))
				.done(function (response) {
					$submit.prop('disabled', false);

					if (response && response.success) {
						show('saved', i18n('saved', 'Elmentve'));

						window.clearTimeout($badge.data('timer'));
						$badge.data('timer', window.setTimeout(function () {
							$badge.removeClass('is-visible');
						}, 4000));
					} else {
						show('error', (response && response.data && response.data.message) || i18n('error', 'A mentés nem sikerült.'));
					}
				})
				.fail(function () {
					$submit.prop('disabled', false);
					show('error', i18n('error', 'A mentés nem sikerült.'));
				});
		});
	}

	$(function () {
		initTabs();
		initMedia();
		initTexts();
		initSave();
	});
})(jQuery);
