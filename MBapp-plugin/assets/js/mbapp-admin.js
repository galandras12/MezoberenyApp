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

	$(function () {
		initMenuEditor();
		initPreview();
		initDetect();
	});
})(jQuery);
