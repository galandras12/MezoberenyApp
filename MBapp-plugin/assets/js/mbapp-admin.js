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

	$(function () {
		initMenuEditor();
		initPreview();
	});
})(jQuery);
