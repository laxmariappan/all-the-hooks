/**
 * All The Hooks - Admin JavaScript
 */

(function($) {
	'use strict';

	let currentResults = null;
	let currentFormat = 'json';

	$(document).ready(function() {
		initializeAdmin();
	});

	function initializeAdmin() {
		// Toggle between plugin and theme selection
		$('input[name="source_type"]').on('change', function() {
			const sourceType = $(this).val();
			if (sourceType === 'plugin') {
				$('#ath-plugin-row').show();
				$('#ath-theme-row').hide();
			} else {
				$('#ath-plugin-row').hide();
				$('#ath-theme-row').show();
			}
		});

		// Handle form submission
		$('#ath-scan-form').on('submit', function(e) {
			e.preventDefault();
			performScan();
		});

		// Handle download button
		$(document).on('click', '#ath-download-results', function() {
			downloadResults();
		});

		// Handle view details button
		$(document).on('click', '#ath-view-details', function() {
			$('#ath-results-table-container').slideToggle();
			const buttonText = $('#ath-results-table-container').is(':visible')
				? 'Hide Details'
				: 'View Details';
			$(this).find('span:last').text(buttonText);
		});

		// Handle search and filters
		$('#ath-search-hooks').on('keyup', function() {
			filterResults();
		});

		$('#ath-filter-type, #ath-filter-source').on('change', function() {
			filterResults();
		});
	}

	function performScan() {
		const sourceType = $('input[name="source_type"]:checked').val();
		const sourceSlug = sourceType === 'plugin'
			? $('#ath-plugin-select').val()
			: $('#ath-theme-select').val();

		if (!sourceSlug) {
			showNotice('error', 'Please select a ' + sourceType + ' to scan.');
			return;
		}

		const formData = {
			action: 'ath_scan_source',
			nonce: athAdmin.nonce,
			source_type: sourceType,
			source_slug: sourceSlug,
			hook_type: $('#ath-hook-type').val(),
			include_docblocks: $('#ath-include-docblocks').is(':checked'),
			format: $('#ath-output-format').val()
		};

		// Show progress
		$('#ath-progress').show();
		$('#ath-results').hide();
		$('#ath-scan-submit').prop('disabled', true);
		updateProgress('Scanning ' + sourceType + '...', 0);

		// Simulate progress
		let progress = 0;
		const progressInterval = setInterval(function() {
			progress += 10;
			if (progress < 90) {
				updateProgress('Analyzing files...', progress);
			}
		}, 300);

		// Perform AJAX request
		$.ajax({
			url: athAdmin.ajaxUrl,
			type: 'POST',
			data: formData,
			success: function(response) {
				clearInterval(progressInterval);
				updateProgress('Scan complete!', 100);

				setTimeout(function() {
					$('#ath-progress').hide();
					$('#ath-scan-submit').prop('disabled', false);

					if (response.success) {
						currentResults = response.data.hooks;
						currentFormat = formData.format;
						displayResults(response.data);
					} else {
						showNotice('error', response.data.message || 'An error occurred during scanning.');
					}
				}, 500);
			},
			error: function(xhr, status, error) {
				clearInterval(progressInterval);
				$('#ath-progress').hide();
				$('#ath-scan-submit').prop('disabled', false);
				showNotice('error', 'AJAX error: ' + error);
			}
		});
	}

	function updateProgress(text, percent) {
		$('.ath-progress-text').text(text);
		$('.ath-progress-indicator').css('width', percent + '%');
	}

	function displayResults(data) {
		// Show results section
		$('#ath-results').show();

		// Display summary
		const summaryHtml = `
			<div class="ath-summary-grid">
				<div class="ath-summary-item">
					<span class="ath-summary-number">${data.total}</span>
					<span class="ath-summary-label">Total Hooks</span>
				</div>
				<div class="ath-summary-item">
					<span class="ath-summary-number">${data.actions}</span>
					<span class="ath-summary-label">Actions</span>
				</div>
				<div class="ath-summary-item">
					<span class="ath-summary-number">${data.filters}</span>
					<span class="ath-summary-label">Filters</span>
				</div>
				<div class="ath-summary-item">
					<span class="ath-summary-number">${data.hooks_with_listeners}</span>
					<span class="ath-summary-label">With Listeners</span>
				</div>
			</div>
		`;
		$('#ath-results-summary').html(summaryHtml);

		// Populate results table
		populateTable(data.hooks);

		// Show success notice
		showNotice('success', data.message);

		// Scroll to results
		$('html, body').animate({
			scrollTop: $('#ath-results').offset().top - 50
		}, 500);
	}

	function populateTable(hooks) {
		const tbody = $('#ath-hooks-tbody');
		tbody.empty();

		hooks.forEach(function(hook) {
			const listenersCount = hook.listeners ? hook.listeners.length : 0;
			const listenersHtml = listenersCount > 0
				? `<span class="ath-badge ath-badge-success">${listenersCount}</span>`
				: '<span class="ath-badge">0</span>';

			const typeClass = hook.type === 'action' ? 'ath-badge-action' : 'ath-badge-filter';
			const sourceClass = hook.is_core === 'yes' ? 'ath-badge-core' : 'ath-badge-custom';

			const row = `
				<tr data-hook-name="${escapeHtml(hook.name)}" data-hook-type="${hook.type}" data-hook-source="${hook.is_core}">
					<td class="column-name column-primary" data-colname="Hook Name">
						<strong>${escapeHtml(hook.name)}</strong>
						${listenersCount > 0 ? '<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>' : ''}
					</td>
					<td class="column-type" data-colname="Type">
						<span class="ath-badge ${typeClass}">${hook.type}</span>
					</td>
					<td class="column-source" data-colname="Source">
						<span class="ath-badge ${sourceClass}">${hook.is_core === 'yes' ? 'Core' : 'Custom'}</span>
					</td>
					<td class="column-file" data-colname="File">
						<code>${escapeHtml(hook.file)}:${hook.line_number}</code>
					</td>
					<td class="column-listeners" data-colname="Listeners">
						${listenersHtml}
					</td>
				</tr>
				${listenersCount > 0 ? renderListenersRow(hook) : ''}
			`;
			tbody.append(row);
		});
	}

	function renderListenersRow(hook) {
		let listenersHtml = '<tr class="ath-listeners-row" style="display:none;"><td colspan="5"><div class="ath-listeners-container"><h4>Listeners:</h4><table class="widefat"><thead><tr><th>Callback</th><th>Priority</th><th>Args</th><th>File</th></tr></thead><tbody>';

		hook.listeners.forEach(function(listener) {
			listenersHtml += `
				<tr>
					<td><code>${escapeHtml(listener.callback)}</code></td>
					<td>${listener.priority}</td>
					<td>${listener.accepted_args}</td>
					<td><code>${escapeHtml(listener.file)}:${listener.line}</code></td>
				</tr>
			`;
		});

		listenersHtml += '</tbody></table></div></td></tr>';
		return listenersHtml;
	}

	function filterResults() {
		const searchTerm = $('#ath-search-hooks').val().toLowerCase();
		const filterType = $('#ath-filter-type').val();
		const filterSource = $('#ath-filter-source').val();

		$('#ath-hooks-tbody tr:not(.ath-listeners-row)').each(function() {
			const row = $(this);
			const hookName = row.data('hook-name').toLowerCase();
			const hookType = row.data('hook-type');
			const hookSource = row.data('hook-source');

			let show = true;

			// Filter by search term
			if (searchTerm && hookName.indexOf(searchTerm) === -1) {
				show = false;
			}

			// Filter by type
			if (filterType && hookType !== filterType) {
				show = false;
			}

			// Filter by source
			if (filterSource && hookSource !== filterSource) {
				show = false;
			}

			if (show) {
				row.show();
			} else {
				row.hide();
				row.next('.ath-listeners-row').hide();
			}
		});
	}

	function downloadResults() {
		if (!currentResults) {
			showNotice('error', 'No results to download.');
			return;
		}

		let content = '';
		let filename = 'hooks-export';
		let mimeType = 'application/json';

		if (currentFormat === 'json') {
			content = JSON.stringify(currentResults, null, 2);
			filename += '.json';
			mimeType = 'application/json';
		} else if (currentFormat === 'markdown') {
			// Generate markdown from results
			content = generateMarkdown(currentResults);
			filename += '.md';
			mimeType = 'text/markdown';
		} else if (currentFormat === 'html') {
			// Generate HTML from results
			content = generateHTML(currentResults);
			filename += '.html';
			mimeType = 'text/html';
		}

		// Create and trigger download
		const blob = new Blob([content], { type: mimeType });
		const url = window.URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = filename;
		document.body.appendChild(a);
		a.click();
		window.URL.revokeObjectURL(url);
		document.body.removeChild(a);

		showNotice('success', 'Results downloaded successfully!');
	}

	function generateMarkdown(hooks) {
		let md = '# Hooks Export\n\n';
		md += `Total Hooks: ${hooks.length}\n\n`;

		hooks.forEach(function(hook) {
			md += `## ${hook.name}\n`;
			md += `- **Type:** ${hook.type}\n`;
			md += `- **File:** ${hook.file}:${hook.line_number}\n`;
			md += `- **Source:** ${hook.is_core === 'yes' ? 'Core' : 'Custom'}\n`;

			if (hook.listeners && hook.listeners.length > 0) {
				md += `- **Listeners:** ${hook.listeners.length}\n`;
				hook.listeners.forEach(function(listener) {
					md += `  - ${listener.callback} (Priority: ${listener.priority}, Args: ${listener.accepted_args})\n`;
				});
			}
			md += '\n';
		});

		return md;
	}

	function generateHTML(hooks) {
		let html = '<!DOCTYPE html><html><head><title>Hooks Export</title><style>body{font-family:sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background:#f1f1f1;}</style></head><body>';
		html += '<h1>Hooks Export</h1>';
		html += `<p>Total Hooks: ${hooks.length}</p>`;
		html += '<table><thead><tr><th>Hook Name</th><th>Type</th><th>File</th><th>Listeners</th></tr></thead><tbody>';

		hooks.forEach(function(hook) {
			const listenersCount = hook.listeners ? hook.listeners.length : 0;
			html += `<tr><td>${escapeHtml(hook.name)}</td><td>${hook.type}</td><td>${escapeHtml(hook.file)}:${hook.line_number}</td><td>${listenersCount}</td></tr>`;
		});

		html += '</tbody></table></body></html>';
		return html;
	}

	function showNotice(type, message) {
		const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
		const notice = `
			<div class="notice ${noticeClass} is-dismissible">
				<p>${message}</p>
			</div>
		`;

		$('.wrap h1').after(notice);

		// Auto-dismiss after 5 seconds
		setTimeout(function() {
			$('.notice.is-dismissible').fadeOut(function() {
				$(this).remove();
			});
		}, 5000);
	}

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	// Toggle listeners row on click
	$(document).on('click', '.toggle-row', function(e) {
		e.preventDefault();
		$(this).closest('tr').next('.ath-listeners-row').slideToggle();
	});

})(jQuery);
