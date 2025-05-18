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
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>' . esc_html( $plugin_slug ) . ' Hooks Reference</title>
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; line-height: 1.5; max-width: 1200px; margin: 0 auto; padding: 20px; color: #333; }
		h1 { margin-bottom: 10px; }
		.filters { margin: 20px 0; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
		input[type="text"] { padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px; }
		select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
		.stats { margin-bottom: 20px; font-size: 0.9em; color: #666; }
		table { width: 100%; border-collapse: collapse; margin-top: 20px; }
		th { background: #f5f5f5; padding: 10px; text-align: left; border-bottom: 2px solid #ddd; }
		td { padding: 10px; border-bottom: 1px solid #eee; vertical-align: top; }
		tr:hover { background-color: #f9f9f9; }
		.hook-name { font-weight: bold; font-family: monospace; }
		.badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; margin-right: 5px; }
		.action { background-color: #e7f5ff; color: #0066cc; }
		.filter { background-color: #f3f0ff; color: #5f3dc4; }
		.core { background-color: #e6fcf5; color: #099268; }
		.plugin { background-color: #fff9db; color: #e67700; }
		.hook-meta { color: #666; font-size: 0.85em; margin-top: 5px; }
		.docblock { background: #f5f5f5; padding: 10px; margin-top: 5px; white-space: pre-wrap; font-size: 0.9em; border-radius: 4px; }
		.no-results { padding: 20px; text-align: center; color: #888; }
	</style>
</head>
<body>
	<h1>' . esc_html( $plugin_slug ) . ' Hooks Reference</h1>
	
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