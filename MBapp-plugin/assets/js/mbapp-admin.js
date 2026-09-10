/**
 * MBapp Plugin – admin felület.
 *
 * - lebegő menü szerkesztő (sorok hozzáadása, törlése, sorrend)
 * - hírforrás próbalekérés
 */
(function ($) {
	'use strict';

	function i18n(key, fallback) {
		return (window.MBAppAdmin && window.MBAppAdmin.i18n && window.MBAppAdmin.i18n[key]) || fallback;
	}

	/* ------------------------------------------------------------------
	 * Menüpont szerkesztő
	 * ------------------------------------------------------------------ */
	function reindexItems() {
		$('#mbapp-menu-items .mbapp-item').each(function (index) {
			$(this).attr('data-index', index);

			$(this).find('input, select, textarea').each(function () {
				var name = $(this).attr('name');

				if (!name) {
					return;
				}

				$(this).attr('name', name.replace(/\[items\]\[[^\]]*\]/, '[items][' + index + ']'));
			});
		});
	}

	function toggleIconFields($row) {
		var type = $row.find('.mbapp-icon-type').val();
		$row.find('.mbapp-icon-builtin').toggle(type === 'builtin');
		$row.find('.mbapp-icon-custom').toggle(type !== 'builtin');
	}

	function initMenuEditor() {
		var $wrap = $('#mbapp-menu-items');

		if (!$wrap.length) {
			return;
		}

		$('#mbapp-add-item').on('click', function () {
			var template = $('#tmpl-mbapp-menu-item').html();

			if (!template) {
				return;
			}

			var index = $wrap.find('.mbapp-item').length;
			var $row = $(template.replace(/__INDEX__/g, String(index)));

			$wrap.append($row);
			toggleIconFields($row);
			reindexItems();
			$row.find('input[type="text"]').first().trigger('focus');
		});

		$wrap.on('click', '.mbapp-item__remove', function () {
			if (!window.confirm(i18n('confirm', 'Biztosan törlöd?'))) {
				return;
			}

			$(this).closest('.mbapp-item').remove();
			reindexItems();
		});

		$wrap.on('change', '.mbapp-icon-type', function () {
			toggleIconFields($(this).closest('.mbapp-item'));
		});

		$wrap.find('.mbapp-item').each(function () {
			toggleIconFields($(this));
		});

		// Sorrend húzással, ha elérhető a jQuery UI sortable.
		if ($.fn.sortable) {
			$wrap.sortable({
				handle: '.mbapp-item__handle',
				axis: 'y',
				placeholder: 'mbapp-item-placeholder',
				update: reindexItems
			});
		}
	}

	/* ------------------------------------------------------------------
	 * Forrás próbalekérés
	 * ------------------------------------------------------------------ */
	function initPreview() {
		var $button = $('#mbapp-preview');

		if (!$button.length) {
			return;
		}

		$button.on('click', function () {
			var $status = $('#mbapp-preview-status');
			var $result = $('#mbapp-preview-result');

			$button.prop('disabled', true);
			$status.text(i18n('testing', 'Lekérés folyamatban…'));
			$result.empty();

			var data = {
				action: 'mbapp_preview_source',
				nonce: window.MBAppAdmin.nonce,
				source_url: $('#mbapp_source_url').val(),
				source_type: $('#mbapp_source_type').val()
			};

			$('.mbapp-selector').each(function () {
				data[$(this).data('field')] = $(this).val();
			});

			$.post(window.MBAppAdmin.ajaxUrl, data)
				.done(function (response) {
					$button.prop('disabled', false);

					if (!response || !response.success) {
						$status.html('<span class="mbapp-error">' +
							((response && response.data && response.data.message) || i18n('error', 'Hiba történt.')) +
							'</span>');
						return;
					}

					var items = response.data.items || [];
					$status.text(i18n('found', 'Talált elem:') + ' ' + response.data.count);

					if (!items.length) {
						$result.html('<p class="mbapp-error">' + i18n('noResults', 'Nem találtunk hírt.') + '</p>');
						return;
					}

					var html = '<table class="widefat striped mbapp-preview"><thead><tr>' +
						'<th>Kép</th><th>Cím</th><th>Dátum</th><th>Link</th></tr></thead><tbody>';

					items.forEach(function (item) {
						html += '<tr>' +
							'<td>' + (item.image ? '<img src="' + item.image + '" alt="" style="width:70px;height:48px;object-fit:cover;border-radius:6px">' : '—') + '</td>' +
							'<td><strong>' + $('<div>').text(item.title).html() + '</strong>' +
							(item.excerpt ? '<br><span class="description">' + $('<div>').text(item.excerpt).html() + '</span>' : '') +
							'</td>' +
							'<td>' + (item.date || '—') + '</td>' +
							'<td>' + (item.link ? '<a href="' + item.link + '" target="_blank" rel="noopener">megnyit</a>' : '—') + '</td>' +
							'</tr>';
					});

					html += '</tbody></table>';
					$result.html(html);
				})
				.fail(function () {
					$button.prop('disabled', false);
					$status.html('<span class="mbapp-error">' + i18n('error', 'Hiba történt.') + '</span>');
				});
		});
	}

	/* ------------------------------------------------------------------
	 * Forrás felderítés
	 * ------------------------------------------------------------------ */
	function esc(text) {
		return $('<div>').text(text == null ? '' : String(text)).html();
	}

	function applyCandidate(candidate) {
		$('#mbapp_item_selector').val(candidate.selector);

		var fields = candidate.fields || {};

		Object.keys(fields).forEach(function (key) {
			if (fields[key]) {
				$('#mbapp_' + key).val(fields[key]);
			}
		});

		$('#mbapp_source_type').val('html');
		$('#mbapp-detect-status').html('<span class="mbapp-ok">' + i18n('applied', 'A szelektorok kitöltve.') + '</span>');

		$('html, body').animate({ scrollTop: $('#mbapp_item_selector').offset().top - 120 }, 300);
	}

	function renderReport(report) {
		var $result = $('#mbapp-detect-result');
		var html = '<div class="mbapp-adminbox"><h2>Mit talált a felderítés?</h2>';

		html += '<table class="widefat striped"><tbody>';
		html += '<tr><th style="width:220px">Letöltött oldal</th><td><code>' + esc(report.url) + '</code></td></tr>';
		html += '<tr><th>Tartalomtípus</th><td>' + esc(report.content_type || '—') + '</td></tr>';
		html += '<tr><th>Méret</th><td>' + Math.round((report.size || 0) / 1024) + ' KB</td></tr>';
		html += '<tr><th>Olvasható szöveg</th><td>' + (report.text_length || 0) + ' karakter</td></tr>';
		html += '<tr><th>JSON-LD hírek</th><td>' + (report.jsonld || 0) + '</td></tr>';
		html += '</tbody></table>';

		if (report.notes && report.notes.length) {
			html += '<ul class="mbapp-notes">';
			report.notes.forEach(function (note) {
				html += '<li>' + esc(note) + '</li>';
			});
			html += '</ul>';
		}

		if (report.feeds && report.feeds.length) {
			html += '<h3>Talált RSS csatornák</h3><ul class="mbapp-notes">';
			report.feeds.forEach(function (feed) {
				html += '<li><code>' + esc(feed) + '</code> ' +
					'<button type="button" class="button button-small mbapp-use-feed" data-url="' + esc(feed) + '">' +
					i18n('useFeed', 'Beállítom forrásnak') + '</button></li>';
			});
			html += '</ul>';
		}

		if (report.jsonld) {
			html += '<p><button type="button" class="button button-small" id="mbapp-use-jsonld">' +
				'JSON-LD forrásra váltok</button></p>';
		}

		if (report.candidates && report.candidates.length) {
			html += '<h3>Lehetséges hírblokkok</h3>';
			html += '<table class="widefat striped"><thead><tr>' +
				'<th>Szelektor</th><th>Darab</th><th>Kép</th><th>Minta</th><th></th></tr></thead><tbody>';

			report.candidates.forEach(function (candidate, index) {
				html += '<tr>' +
					'<td><code>' + esc(candidate.selector) + '</code></td>' +
					'<td>' + candidate.count + '</td>' +
					'<td>' + (candidate.has_image ? 'van' : '–') + '</td>' +
					'<td><span class="description">' + esc(candidate.sample) + '</span></td>' +
					'<td><button type="button" class="button button-primary button-small mbapp-use-candidate" ' +
					'data-index="' + index + '">' + i18n('apply', 'Ezt használom') + '</button></td>' +
					'</tr>';
			});

			html += '</tbody></table>';
		}

		if (report.html_head) {
			html += '<details class="mbapp-details"><summary>A letöltött HTML eleje (hibakereséshez)</summary>' +
				'<pre class="mbapp-htmlhead">' + esc(report.html_head) + '</pre></details>';
		}

		html += '</div>';

		$result.html(html);
		$result.data('candidates', report.candidates || []);
	}

	function initDetect() {
		var $button = $('#mbapp-detect');

		if (!$button.length) {
			return;
		}

		$button.on('click', function () {
			var $status = $('#mbapp-detect-status');

			$button.prop('disabled', true);
			$status.text(i18n('detecting', 'Felderítés folyamatban…'));
            $('#mbapp-detect-result').empty();

			$.post(window.MBAppAdmin.ajaxUrl, {
				action: 'mbapp_detect_source',
				nonce: window.MBAppAdmin.nonce,
				source_url: $('#mbapp_source_url').val()
			})
				.done(function (response) {
					$button.prop('disabled', false);

					if (!response || !response.success) {
						$status.html('<span class="mbapp-error">' +
							esc((response && response.data && response.data.message) || i18n('error', 'Hiba történt.')) +
							'</span>');
						return;
					}

					$status.text('');
					renderReport(response.data);
				})
				.fail(function () {
					$button.prop('disabled', false);
					$status.html('<span class="mbapp-error">' + i18n('error', 'Hiba történt.') + '</span>');
				});
		});

		$(document).on('click', '.mbapp-use-candidate', function () {
			var candidates = $('#mbapp-detect-result').data('candidates') || [];
			var candidate = candidates[parseInt($(this).data('index'), 10)];

			if (candidate) {
				applyCandidate(candidate);
			}
		});

		$(document).on('click', '.mbapp-use-feed', function () {
			$('#mbapp_source_url').val($(this).data('url'));
			$('#mbapp_source_type').val('rss');
			$('#mbapp-detect-status').html('<span class="mbapp-ok">' + i18n('applied', 'Beállítva.') + '</span>');
		});

		$(document).on('click', '#mbapp-use-jsonld', function () {
			$('#mbapp_source_type').val('jsonld');
			$('#mbapp-detect-status').html('<span class="mbapp-ok">' + i18n('applied', 'Beállítva.') + '</span>');
		});
	}

	/* ------------------------------------------------------------------
	 * AJAX mentés – az oldal nem töltődik újra, nem ugrik a tetejére
	 * ------------------------------------------------------------------ */
	function badge($form) {
		var $submit = $form.find('.submit').first();

		if (!$submit.length) {
			$submit = $form.find('[type="submit"]').last().parent();
		}

		var $badge = $submit.find('.mbapp-saved');

		if (!$badge.length) {
			$badge = $('<span class="mbapp-saved" role="status" aria-live="polite"></span>');
			$submit.append($badge);
		}

		return $badge;
	}

	function showBadge($badge, state, text) {
		var icon = '';

		if (state === 'saved') {
			icon = '<span class="mbapp-saved__check" aria-hidden="true">✓</span>';
		} else if (state === 'busy') {
			icon = '<span class="mbapp-spinner" aria-hidden="true"></span>';
		}

		$badge
			.removeClass('mbapp-saved--error mbapp-saved--busy')
			.addClass(state === 'error' ? 'mbapp-saved--error' : (state === 'busy' ? 'mbapp-saved--busy' : ''))
			.html(icon + '<span>' + $('<div>').text(text).html() + '</span>')
			.addClass('is-visible');
	}

	function initAjaxSave() {
		$('.mbapp-form[data-mbapp-group]').on('submit', function (event) {
			var $form = $(this);
			var group = $form.data('mbapp-group');

			if (!window.MBAppAdmin || !group) {
				return; // marad a hagyományos beküldés
			}

			event.preventDefault();

			var $badge = badge($form);
			var $submit = $form.find('[type="submit"]');

			$submit.prop('disabled', true);
			showBadge($badge, 'busy', i18n('saving', 'Mentés…'));

			// A hagyományos beküldés mezőit kivesszük, hogy ne fusson le kétszer.
			var data = $form.serializeArray().filter(function (field) {
				return field.name !== 'mbapp_action' && field.name !== 'mbapp_nonce';
			});

			data.push({ name: 'action', value: 'mbapp_save_settings' });
			data.push({ name: 'nonce', value: window.MBAppAdmin.nonce });
			data.push({ name: 'group', value: group });

			$.post(window.MBAppAdmin.ajaxUrl, $.param(data))
				.done(function (response) {
					$submit.prop('disabled', false);

					if (response && response.success) {
						showBadge($badge, 'saved', i18n('saved', 'Elmentve'));

						window.clearTimeout($badge.data('timer'));
						$badge.data('timer', window.setTimeout(function () {
							$badge.removeClass('is-visible');
						}, 4000));
					} else {
						showBadge($badge, 'error',
							(response && response.data && response.data.message) || i18n('saveError', 'A mentés nem sikerült.'));
					}
				})
				.fail(function () {
					$submit.prop('disabled', false);
					showBadge($badge, 'error', i18n('saveError', 'A mentés nem sikerült.'));
				});
		});
	}

	/* ------------------------------------------------------------------
	 * Animáció előnézet
	 * ------------------------------------------------------------------ */
	function initAnimPreview() {
		var $button = $('#mbapp-anim-preview');

		if (!$button.length) {
			return;
		}

		var easings = {
			'ease-out': 'cubic-bezier(.22, .61, .36, 1)',
			'ease-in-out': 'cubic-bezier(.65, .05, .36, 1)',
			spring: 'cubic-bezier(.34, 1.56, .64, 1)',
			linear: 'linear'
		};

		function play() {
			var page = document.getElementById('mbapp-anim-demo-page');

			if (!page) {
				return;
			}

			var type = $('#mbapp_anim_type').val() || 'fade';
			var duration = parseInt($('#mbapp_anim_duration').val(), 10) || 280;
			var easing = easings[$('#mbapp_anim_easing').val()] || easings['ease-out'];

			var leave = { fade: '', slide: 'translateX(-26px)', 'slide-up': 'translateY(-20px)', scale: 'scale(.97)' };
			var enter = { fade: '', slide: 'translateX(26px)', 'slide-up': 'translateY(20px)', scale: 'scale(1.03)' };

			if (type === 'none') {
				page.style.transition = '';
				page.style.opacity = '1';
				page.style.transform = '';
				return;
			}

			page.style.transition = 'opacity ' + duration + 'ms ' + easing + ', transform ' + duration + 'ms ' + easing;
			page.style.opacity = '0';
			page.style.transform = leave[type] || '';

			window.setTimeout(function () {
				page.style.transition = 'none';
				page.style.transform = enter[type] || '';
				void page.offsetWidth;
				page.style.transition = 'opacity ' + duration + 'ms ' + easing + ', transform ' + duration + 'ms ' + easing;
				page.style.opacity = '1';
				page.style.transform = '';
			}, duration + 40);
		}

		$button.on('click', play);
		$('.mbapp-anim-control').on('change', play);
	}

	/* ------------------------------------------------------------------
	 * Kezdőlap blokk szerkesztő
	 * ------------------------------------------------------------------ */
	function reindexBlocks() {
		$('#mbapp-home-blocks .mbapp-item').each(function (index) {
			$(this).attr('data-index', index);

			$(this).find('input, select, textarea').each(function () {
				var name = $(this).attr('name');

				if (!name) {
					return;
				}

				$(this).attr('name', name.replace(/\[blocks\]\[[^\]]*\]/, '[blocks][' + index + ']'));
			});
		});
	}

	function initBlockEditor() {
		var $wrap = $('#mbapp-home-blocks');

		if (!$wrap.length) {
			return;
		}

		$('#mbapp-add-block').on('click', function () {
			var type = $('#mbapp-block-type').val();
			var template = $('#tmpl-mbapp-block-' + type).html();

			if (!template) {
				return;
			}

			var index = $wrap.find('.mbapp-item').length;
			var $row = $(template.replace(/__INDEX__/g, String(index)));

			$wrap.append($row);
			reindexBlocks();

			if ($row.find('.mbapp-hero-media').length) {
				$row.find('.mbapp-hero-media').trigger('change');
			}

			if ($.fn.sortable) {
				$row.find('.mbapp-gallery__items').sortable({ items: '.mbapp-gallery__item' });
			}

			$row.find('input[type="text"], textarea').first().trigger('focus');
			$('html, body').animate({ scrollTop: $row.offset().top - 120 }, 300);
		});

		$wrap.on('click', '.mbapp-item__remove', function () {
			if (!window.confirm(i18n('confirm', 'Biztosan törlöd?'))) {
				return;
			}

			$(this).closest('.mbapp-item').remove();
			reindexBlocks();
		});

		if ($.fn.sortable) {
			$wrap.sortable({
				handle: '.mbapp-item__handle',
				axis: 'y',
				placeholder: 'mbapp-item-placeholder',
				update: reindexBlocks
			});
		}
	}

	/* ------------------------------------------------------------------
	 * Médiaválasztó (fejléc kép)
	 * ------------------------------------------------------------------ */
	function initMediaPicker() {
		var frame = null;

		$(document).on('click', '.mbapp-media__pick', function () {
			var $media = $(this).closest('.mbapp-media');

			if (!window.wp || !window.wp.media) {
				window.alert('A WordPress médiatár nem érhető el.');
				return;
			}

			frame = window.wp.media({
				title: 'Fejléc kép kiválasztása',
				library: { type: 'image' },
				button: { text: 'Kiválasztom' },
				multiple: false
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var url = attachment.url;

				if (attachment.sizes && attachment.sizes.medium) {
					url = attachment.sizes.medium.url;
				}

				$media.find('.mbapp-media__id').val(attachment.id);
				$media.find('.mbapp-media__thumb').html('<img src="' + url + '" alt="">');
				$media.find('.mbapp-media__toggle input').prop('checked', true);
			});

			frame.open();
		});

		$(document).on('click', '.mbapp-media__clear', function () {
			var $media = $(this).closest('.mbapp-media');

			$media.find('.mbapp-media__id').val('');
			$media.find('.mbapp-media__thumb').empty();
		});
	}

	/* ------------------------------------------------------------------
	 * Fejléc blokk: háttérmód és képgaléria
	 * ------------------------------------------------------------------ */
	function toggleHeroFields($block) {
		var mode = $block.find('.mbapp-hero-media').val();

		$block.find('.mbapp-hero-single').toggle(mode === 'image');
		$block.find('.mbapp-hero-gallery').toggle(mode === 'slideshow');
		$block.find('.mbapp-hero-slideopts').toggle(mode === 'slideshow');
	}

	function galleryIds($gallery) {
		return $gallery.find('.mbapp-gallery__item').map(function () {
			return String($(this).data('id'));
		}).get();
	}

	function syncGallery($gallery) {
		$gallery.find('.mbapp-gallery__ids').val(galleryIds($gallery).join(','));
	}

	function initHeroEditor() {
		var $wrap = $('#mbapp-home-blocks');

		if (!$wrap.length) {
			return;
		}

		$wrap.on('change', '.mbapp-hero-media', function () {
			toggleHeroFields($(this).closest('.mbapp-item'));
		});

		$wrap.find('.mbapp-item').each(function () {
			if ($(this).find('.mbapp-hero-media').length) {
				toggleHeroFields($(this));
			}
		});

        // Több kép kiválasztása a médiatárból
		$wrap.on('click', '.mbapp-gallery__pick', function () {
			var $gallery = $(this).closest('.mbapp-gallery');

			if (!window.wp || !window.wp.media) {
				return;
			}

			var frame = window.wp.media({
				title: 'Képek kiválasztása a diavetítéshez',
				library: { type: 'image' },
				button: { text: 'Hozzáadom' },
				multiple: 'add'
			});

			frame.on('open', function () {
				var selection = frame.state().get('selection');

				galleryIds($gallery).forEach(function (id) {
					var attachment = window.wp.media.attachment(id);
					attachment.fetch();
					selection.add(attachment ? [attachment] : []);
				});
			});

			frame.on('select', function () {
				var $items = $gallery.find('.mbapp-gallery__items');

				$items.empty();

				frame.state().get('selection').each(function (attachment) {
					var data = attachment.toJSON();
					var url = (data.sizes && data.sizes.thumbnail) ? data.sizes.thumbnail.url : data.url;

					$items.append(
						'<span class="mbapp-gallery__item" data-id="' + data.id + '">' +
						'<img src="' + url + '" alt="">' +
						'<button type="button" class="mbapp-gallery__remove" aria-label="Kép eltávolítása">&times;</button>' +
						'</span>'
					);
				});

				syncGallery($gallery);
			});

			frame.open();
		});

		$wrap.on('click', '.mbapp-gallery__remove', function () {
			var $gallery = $(this).closest('.mbapp-gallery');

			$(this).closest('.mbapp-gallery__item').remove();
			syncGallery($gallery);
		});

		$wrap.on('click', '.mbapp-gallery__clear', function () {
			var $gallery = $(this).closest('.mbapp-gallery');

			$gallery.find('.mbapp-gallery__items').empty();
			syncGallery($gallery);
		});

		// A képek sorrendje húzással állítható.
		if ($.fn.sortable) {
			$wrap.find('.mbapp-gallery__items').sortable({
				items: '.mbapp-gallery__item',
				update: function () {
					syncGallery($(this).closest('.mbapp-gallery'));
				}
			});
		}
	}

	$(function () {
		initMenuEditor();
		initBlockEditor();
		initHeroEditor();
		initMediaPicker();
		initPreview();
		initDetect();
		initAjaxSave();
		initAnimPreview();
	});
})(jQuery);
