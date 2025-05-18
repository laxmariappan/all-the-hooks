<?php
/**
 * Output Formatter class.
 *
 * @package AllTheHooks
 */

namespace AllTheHooks;

/**
 * Class OutputFormatter
 *
 * Formats hook data to various output formats (JSON, Markdown).
 */
class OutputFormatter {

	/**
	 * Format hook data to JSON.
	 *
	 * @param array  $hooks       Array of hook data.
	 * @param string $plugin_slug Plugin slug that was scanned.
	 * @return string JSON formatted output.
	 */
	public static function to_json( $hooks, $plugin_slug ) {
		return wp_json_encode( $hooks, JSON_PRETTY_PRINT );
	}

	/**
	 * Format hook data to Markdown.
	 *
	 * @param array  $hooks       Array of hook data.
	 * @param string $plugin_slug Plugin slug that was scanned.
	 * @return string Markdown formatted output.
	 */
	public static function to_markdown( $hooks, $plugin_slug ) {
		// Group hooks by type (action/filter)
		$actions = array_filter( $hooks, function( $hook ) {
			return 'action' === $hook['type'];
		} );
		
		$filters = array_filter( $hooks, function( $hook ) {
			return 'filter' === $hook['type'];
		} );
		
		// Generate markdown
		$output = "# Hooks for Plugin: {$plugin_slug}\n\n";
		$output .= "This document lists all hooks (actions and filters) found in the {$plugin_slug} plugin.\n\n";
		$output .= "## Summary\n\n";
		$output .= "- Total Hooks: " . count( $hooks ) . "\n";
		$output .= "- Actions: " . count( $actions ) . "\n";
		$output .= "- Filters: " . count( $filters ) . "\n\n";
		
		// Sort hooks alphabetically by name
		usort( $actions, function( $a, $b ) {
			return strcmp( $a['name'], $b['name'] );
		} );
		
		usort( $filters, function( $a, $b ) {
			return strcmp( $a['name'], $b['name'] );
		} );
		
		// Actions
		if ( ! empty( $actions ) ) {
			$output .= "## Actions\n\n";
			foreach ( $actions as $hook ) {
				$output .= self::format_hook_markdown( $hook );
			}
		}
		
		// Filters
		if ( ! empty( $filters ) ) {
			$output .= "## Filters\n\n";
			foreach ( $filters as $hook ) {
				$output .= self::format_hook_markdown( $hook );
			}
		}
		
		return $output;
	}
	
	/**
	 * Format a single hook to Markdown.
	 *
	 * @param array $hook Hook data.
	 * @return string Markdown for this hook.
	 */
	private static function format_hook_markdown( $hook ) {
		$output = "### `{$hook['name']}`\n";
		$output .= "- **File:** `{$hook['file']}`\n";
		$output .= "- **Line:** {$hook['line_number']}\n";
		$output .= "- **Function:** `{$hook['function_call']}`\n";
		
		if ( isset( $hook['docblock_raw'] ) && ! empty( $hook['docblock_raw'] ) ) {
			$output .= "- **DocBlock:**\n";
			$output .= "```php\n";
			$output .= $hook['docblock_raw'] . "\n";
			$output .= "```\n";
			
			// Add parsed docblock info if available
			if ( isset( $hook['docblock_parsed'] ) && ! empty( $hook['docblock_parsed'] ) ) {
				$docblock = $hook['docblock_parsed'];
				
				if ( ! empty( $docblock['summary'] ) ) {
					$output .= "- **Summary:** " . $docblock['summary'] . "\n";
				}
				
				if ( ! empty( $docblock['description'] ) ) {
					$output .= "- **Description:** " . $docblock['description'] . "\n";
				}
				
				if ( ! empty( $docblock['params'] ) ) {
					$output .= "- **Parameters:**\n";
					foreach ( $docblock['params'] as $param ) {
						$output .= "  - `{$param['name']}` (" . ( ! empty( $param['type'] ) ? $param['type'] : 'mixed' ) . "): " . $param['description'] . "\n";
					}
				}
				
				if ( ! empty( $docblock['return'] ) ) {
					$output .= "- **Returns:** " . ( ! empty( $docblock['return']['type'] ) ? $docblock['return']['type'] : 'mixed' ) . " - " . $docblock['return']['description'] . "\n";
				}
			}
		}
		
		$output .= "\n";
		
		return $output;
	}

	/**
	 * Generate HTML output for hooks data
	 *
	 * @param array  $hooks       Array of hooks data.
	 * @param string $plugin_slug Plugin slug.
	 * @return string HTML content.
	 */
	public static function to_html( $hooks, $plugin_slug ) {
		$html = '<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>' . esc_html( $plugin_slug ) . ' Hooks Reference</title>
	<style>
		/* Light/Dark mode variables */
		:root[data-theme="light"] {
			--bg-color: #ffffff;
			--text-color: #333333;
			--header-bg: #f5f5f5;
			--border-color: #ddd;
			--hover-color: #f9f9f9;
			--docblock-bg: #f5f5f5;
			--badge-filter-bg: #f3f0ff;
			--badge-filter-color: #5f3dc4;
			--badge-action-bg: #e7f5ff;
			--badge-action-color: #0066cc;
			--badge-core-bg: #e6fcf5;
			--badge-core-color: #099268;
			--badge-plugin-bg: #fff9db;
			--badge-plugin-color: #e67700;
		}
		
		:root[data-theme="dark"] {
			--bg-color: #1e1e2e;
			--text-color: #cdd6f4;
			--header-bg: #181825;
			--border-color: #313244;
			--hover-color: #242436;
			--docblock-bg: #181825;
			--badge-filter-bg: #45438f;
			--badge-filter-color: #cba6f7;
			--badge-action-bg: #1e3a8a;
			--badge-action-color: #89b4fa;
			--badge-core-bg: #234e52;
			--badge-core-color: #94e2d5;
			--badge-plugin-bg: #4e411b;
			--badge-plugin-color: #f9e2af;
		}
		
		body { 
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; 
			line-height: 1.5; 
			max-width: 1200px; 
			margin: 0 auto; 
			padding: 20px; 
			color: var(--text-color);
			background-color: var(--bg-color);
			transition: background-color 0.3s ease, color 0.3s ease;
		}
		h1 { margin-bottom: 10px; }
		.filters { margin: 20px 0; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
		input[type="text"] { 
			padding: 8px; 
			width: 300px; 
			border: 1px solid var(--border-color); 
			border-radius: 4px; 
			background-color: var(--bg-color);
			color: var(--text-color);
		}
		select { 
			padding: 8px; 
			border: 1px solid var(--border-color); 
			border-radius: 4px; 
			background-color: var(--bg-color);
			color: var(--text-color);
		}
		.stats { margin-bottom: 20px; font-size: 0.9em; color: var(--text-color); opacity: 0.7; }
		.top-bar { display: flex; justify-content: space-between; align-items: center; }
		.theme-toggle {
			background: none;
			border: 1px solid var(--border-color);
			border-radius: 20px;
			padding: 5px 10px;
			cursor: pointer;
			display: flex;
			align-items: center;
			gap: 5px;
			color: var(--text-color);
			transition: all 0.3s ease;
		}
		.theme-toggle:hover {
			background-color: var(--hover-color);
		}
		table { width: 100%; border-collapse: collapse; margin-top: 20px; }
		th { background: var(--header-bg); padding: 10px; text-align: left; border-bottom: 2px solid var(--border-color); }
		td { padding: 10px; border-bottom: 1px solid var(--border-color); vertical-align: top; }
		tr:hover { background-color: var(--hover-color); }
		.hook-name { font-weight: bold; font-family: monospace; }
		.badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; margin-right: 5px; }
		.action { background-color: var(--badge-action-bg); color: var(--badge-action-color); }
		.filter { background-color: var(--badge-filter-bg); color: var(--badge-filter-color); }
		.core { background-color: var(--badge-core-bg); color: var(--badge-core-color); }
		.plugin { background-color: var(--badge-plugin-bg); color: var(--badge-plugin-color); }
		.hook-meta { color: var(--text-color); opacity: 0.8; font-size: 0.85em; margin-top: 5px; }
		.docblock { background: var(--docblock-bg); padding: 10px; margin-top: 5px; white-space: pre-wrap; font-size: 0.9em; border-radius: 4px; }
		.no-results { padding: 20px; text-align: center; color: var(--text-color); opacity: 0.7; }
	</style>
</head>
<body>
	<div class="top-bar">
		<h1>' . esc_html( $plugin_slug ) . ' Hooks Reference</h1>
		<button id="theme-toggle" class="theme-toggle">
			<span id="theme-icon">🌙</span> <span id="theme-text">Dark Mode</span>
		</button>
	</div>
	
	<div class="filters">
		<input type="text" id="search" placeholder="Search hooks..." autocomplete="off">
		<select id="type-filter">
			<option value="all">All Types</option>
			<option value="action">Actions</option>
			<option value="filter">Filters</option>
		</select>
		<select id="core-filter">
			<option value="all">All Sources</option>
			<option value="yes">WordPress Core</option>
			<option value="no">Plugin Specific</option>
		</select>
	</div>
	
	<div class="stats">
		<span id="shown-count">0</span> of <span id="total-count">' . count( $hooks ) . '</span> hooks shown
	</div>
	
	<table id="hooks-table">
		<thead>
			<tr>
				<th>Hook</th>
				<th>Details</th>
			</tr>
		</thead>
		<tbody>';

	foreach ( $hooks as $hook ) {
		$hook_type_class = $hook['type'] === 'action' ? 'action' : 'filter';
		$hook_source_class = $hook['is_core'] === 'yes' ? 'core' : 'plugin';
		
		$html .= '<tr data-name="' . esc_attr( $hook['name'] ) . '" data-type="' . esc_attr( $hook['type'] ) . '" data-core="' . esc_attr( $hook['is_core'] ) . '">
			<td>
				<div class="hook-name">' . esc_html( $hook['name'] ) . '</div>
				<div>
					<span class="badge ' . $hook_type_class . '">' . esc_html( $hook['type'] ) . '</span>
					<span class="badge ' . $hook_source_class . '">' . ($hook['is_core'] === 'yes' ? 'Core' : 'Plugin') . '</span>
				</div>
			</td>
			<td>
				<div class="hook-meta">
					<strong>File:</strong> ' . esc_html( $hook['file'] ) . ' (line ' . intval( $hook['line_number'] ) . ')<br>
					<strong>Function:</strong> ' . esc_html( $hook['function_call'] ) . '
				</div>';
				
		if ( !empty( $hook['docblock'] ) ) {
			$html .= '<div class="docblock">' . esc_html( $hook['docblock'] ) . '</div>';
		}
				
		$html .= '</td>
		</tr>';
	}

	$html .= '</tbody>
	</table>
	
	<div id="no-results" class="no-results" style="display: none;">
		No hooks match your search criteria
	</div>

	<script>
	document.addEventListener("DOMContentLoaded", function() {
		const searchInput = document.getElementById("search");
		const typeFilter = document.getElementById("type-filter");
		const coreFilter = document.getElementById("core-filter");
		const table = document.getElementById("hooks-table");
		const rows = table.querySelectorAll("tbody tr");
		const noResults = document.getElementById("no-results");
		const shownCount = document.getElementById("shown-count");
		const totalCount = document.getElementById("total-count");
		const themeToggle = document.getElementById("theme-toggle");
		const themeIcon = document.getElementById("theme-icon");
		const themeText = document.getElementById("theme-text");
		const html = document.documentElement;
		
		// Theme toggle functionality
		function setTheme(theme) {
			html.setAttribute("data-theme", theme);
			localStorage.setItem("hooks-theme", theme);
			
			if (theme === "dark") {
				themeIcon.textContent = "☀️";
				themeText.textContent = "Light Mode";
			} else {
				themeIcon.textContent = "🌙";
				themeText.textContent = "Dark Mode";
			}
		}
		
		// Check for saved theme preference or respect OS preference
		const savedTheme = localStorage.getItem("hooks-theme");
		if (savedTheme) {
			setTheme(savedTheme);
		} else if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
			setTheme("dark");
		}
		
		// Theme toggle button
		themeToggle.addEventListener("click", function() {
			const currentTheme = html.getAttribute("data-theme");
			setTheme(currentTheme === "dark" ? "light" : "dark");
		});
		
		function filterTable() {
			const searchTerm = searchInput.value.toLowerCase().trim();
			const typeValue = typeFilter.value;
			const coreValue = coreFilter.value;
			
			let visibleCount = 0;
			
			rows.forEach(row => {
				const name = row.getAttribute("data-name").toLowerCase();
				const type = row.getAttribute("data-type");
				const isCore = row.getAttribute("data-core");
				
				const matchesSearch = searchTerm === "" || name.includes(searchTerm);
				const matchesType = typeValue === "all" || type === typeValue;
				const matchesCore = coreValue === "all" || isCore === coreValue;
				
				if (matchesSearch && matchesType && matchesCore) {
					row.style.display = "";
					visibleCount++;
				} else {
					row.style.display = "none";
				}
			});
			
			shownCount.textContent = visibleCount;
			noResults.style.display = visibleCount === 0 ? "block" : "none";
		}
		
		searchInput.addEventListener("input", filterTable);
		typeFilter.addEventListener("change", filterTable);
		coreFilter.addEventListener("change", filterTable);
		
		// Initial count
		filterTable();
	});
	</script>
</body>
</html>';

		return $html;
	}

	/**
	 * Save content to a file.
	 *
	 * @param string $content     Content to save.
	 * @param string $output_file Path to the output file.
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public static function save_to_file( $content, $output_file ) {
		$result = file_put_contents( $output_file, $content );
		
		if ( false === $result ) {
			return new \WP_Error( 'file_save_error', "Could not save output to {$output_file}." );
		}
		
		return true;
	}
} 