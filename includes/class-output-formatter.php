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
			--highlight-bg: #e3f2fd;
			--highlight-text: #0d47a1;
			--row-highlight-bg: rgba(227, 242, 253, 0.6);
			--section-bg: #f9f9f9;
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
			--highlight-bg: #2d4f3a;
			--highlight-text: #a6e3a1;
			--row-highlight-bg: rgba(45, 79, 58, 0.4);
			--section-bg: #20203b;
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
		.top-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
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
		table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; }
		th { background: var(--header-bg); padding: 10px; text-align: left; border-bottom: 2px solid var(--border-color); }
		th:first-child { width: 40%; }
		th:last-child { width: 60%; }
		td { padding: 10px; border-bottom: 1px solid var(--border-color); vertical-align: top; word-wrap: break-word; }
		tr:hover { background-color: var(--hover-color); }
		.hook-name { font-weight: bold; font-family: monospace; }
		.badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; margin-right: 5px; }
		.action { background-color: var(--badge-action-bg); color: var(--badge-action-color); }
		.filter { background-color: var(--badge-filter-bg); color: var(--badge-filter-color); }
		.core { background-color: var(--badge-core-bg); color: var(--badge-core-color); }
		.plugin { background-color: var(--badge-plugin-bg); color: var(--badge-plugin-color); }
		.hook-meta { 
			color: var(--text-color); 
			font-size: 0.9em; 
			margin: 10px 0; 
			padding: 10px;
			background-color: var(--bg-color);
			border: 1px solid var(--border-color);
			border-radius: 4px;
		}
		.hook-meta strong {
			display: inline-block;
			width: 80px;
		}
		.docblock { 
			background: var(--docblock-bg); 
			padding: 15px; 
			margin-top: 15px; 
			font-size: 0.9em; 
			border-radius: 4px;
			line-height: 1.6;
		}
		.docblock-param {
			margin: 8px 0;
			padding-left: 15px;
		}
		.docblock-param-name {
			font-family: monospace;
			font-weight: bold;
		}
		.no-results { padding: 20px; text-align: center; color: var(--text-color); opacity: 0.7; }
		.context-code {
			background-color: var(--docblock-bg);
			padding: 10px;
			border-radius: 4px;
			font-family: monospace;
			white-space: pre;
			margin-top: 10px;
			display: none;
			overflow-x: auto;
			max-width: 100%;
			line-height: 1.5;
			grid-column: 1 / -1;
			width: 100%;
		}
		.context-line {
			display: block;
			padding: 1px 0;
		}
		.context-line-highlight {
			background-color: var(--highlight-bg);
			color: var(--highlight-text);
			display: block;
		}
		.view-context-btn {
			background-color: #4CAF50;
			color: white;
			border: none;
			padding: 5px 10px;
			text-align: center;
			text-decoration: none;
			display: inline-block;
			font-size: 12px;
			margin: 5px 0;
			cursor: pointer;
			border-radius: 3px;
		}
		.related-hooks {
			margin-top: 15px;
			padding: 15px;
			border-radius: 4px;
			background-color: var(--section-bg);
			border: 1px solid var(--border-color);
		}
		.related-hooks h4 {
			margin: 0 0 12px 0;
			font-size: 1em;
			color: var(--text-color);
			border-bottom: 1px solid var(--border-color);
			padding-bottom: 5px;
		}
		.related-hooks-list {
			margin: 0;
			padding: 0;
			list-style: none;
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
		}
		.related-hooks-list li {
			margin-bottom: 8px;
			font-size: 0.9em;
			background: var(--bg-color);
			padding: 5px 8px;
			border-radius: 4px;
			border: 1px solid var(--border-color);
			flex: 1 0 calc(50% - 10px);
			min-width: 200px;
			box-sizing: border-box;
		}
		.related-hook-link {
			text-decoration: none;
			color: var(--text-color);
			display: flex;
			align-items: center;
			gap: 5px;
		}
		.related-hook-link:hover .related-hook-name {
			text-decoration: underline;
		}
		.badge.small {
			font-size: 0.7em;
			padding: 1px 4px;
			margin-right: 0;
		}
		.relationship-type {
			margin-top: 4px;
			display: block;
			opacity: 0.7;
			font-size: 0.85em;
			font-style: italic;
		}
		.highlight-row {
			animation: highlight-animation 2s ease-out;
		}
		@keyframes highlight-animation {
			0% { background-color: var(--row-highlight-bg); }
			100% { background-color: transparent; }
		}
		/* Responsive adjustments */
		@media (max-width: 1024px) {
			body {
				padding: 15px;
			}
			.filters {
				flex-direction: column;
				align-items: stretch;
			}
			input[type="text"] {
				width: 100%;
				box-sizing: border-box;
			}
			.hook-meta strong {
				width: 70px;
			}
			table {
				display: block;
			}
			th:first-child, th:last-child {
				width: auto;
			}
		}
		@media (max-width: 768px) {
			h1 {
				font-size: 1.5em;
			}
			.top-bar {
				flex-direction: column;
				align-items: flex-start;
				gap: 10px;
			}
			.theme-toggle {
				align-self: flex-end;
			}
			table, thead, tbody, th, td, tr {
				display: block;
			}
			thead tr {
				position: absolute;
				top: -9999px;
				left: -9999px;
			}
			tr {
				margin-bottom: 15px;
				border: 1px solid var(--border-color);
				border-radius: 4px;
			}
			td {
				border: none;
				border-bottom: 1px solid var(--border-color);
				position: relative;
				padding-left: 10px;
			}
			td:last-child {
				border-bottom: 0;
			}
			td:before {
				content: attr(data-column);
				font-weight: bold;
				margin-right: 10px;
			}
			.related-hooks-list li {
				flex: 1 0 100%;
			}
		}
		.view-details-btn {
			background-color: #007bff;
			color: white;
			border: none;
			padding: 5px 10px;
			text-align: center;
			text-decoration: none;
			display: inline-block;
			font-size: 12px;
			margin: 5px 0;
			cursor: pointer;
			border-radius: 3px;
		}
		.view-details-btn:hover {
			background-color: #0056b3;
		}
		/* Add styles for when context is shown - the row will use CSS grid */
		tr.showing-context {
			display: grid !important;
			grid-template-columns: 1fr;
			width: 100%;
		}
		
		tr.showing-context > td:first-child {
			grid-column: 1;
			width: 100%;
			box-sizing: border-box;
		}
		
		/* Hide all other columns when context is shown */
		tr.showing-context > td:not(:first-child) {
			display: none;
		}
		
		/* Main table layout */
		table.hooks-table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; }
		table.hooks-table th { background: var(--header-bg); padding: 10px; text-align: left; border-bottom: 2px solid var(--border-color); }
		table.hooks-table th:first-child { width: 40%; }
		table.hooks-table th:last-child { width: 60%; }
		table.hooks-table td { padding: 10px; border-bottom: 1px solid var(--border-color); vertical-align: top; word-wrap: break-word; }
		
		/* Sticky search bar */
		.sticky-filters {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			z-index: 100;
			background-color: var(--bg-color);
			padding: 15px;
			box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
			display: flex;
			gap: 15px;
			flex-wrap: wrap;
			align-items: center;
			max-width: 1200px;
			margin: 0 auto;
			box-sizing: border-box;
			border-bottom: 1px solid var(--border-color);
			transform: translateY(-100%);
			transition: transform 0.3s ease-in-out;
		}
		
		.sticky-filters.visible {
			transform: translateY(0);
		}
		
		.sticky-filters input[type="text"] {
			flex: 1;
			min-width: 200px;
		}
		
		.filters-placeholder {
			height: 0;
			visibility: hidden;
			transition: height 0.3s ease-in-out;
		}
		
		.filters-placeholder.active {
			height: 60px;
			visibility: visible;
		}
		
		@media (max-width: 768px) {
			.sticky-filters {
				flex-direction: column;
				align-items: stretch;
				padding: 10px;
			}
			
			.filters-placeholder.active {
				height: 150px;
			}
		}
		
		/* Parameter table should have auto layout, not affected by main table fixed widths */
		.param-table {
			width: 100%;
			border-collapse: collapse;
			margin: 15px 0;
			font-size: 0.9em;
			border-radius: 4px;
			overflow: hidden;
			table-layout: auto;
		}
		
		.param-table th {
			background-color: var(--header-bg);
			padding: 8px 12px;
			text-align: left;
			font-weight: bold;
			color: var(--text-color);
			border-bottom: 1px solid var(--border-color);
			width: auto !important; /* Override any width constraints */
		}
		
		.param-table th:first-child {
			width: auto !important;
		}
		
		.param-table th:last-child {
			width: auto !important;
		}
		
		.param-table td {
			padding: 8px 12px;
			border-bottom: 1px solid var(--border-color);
			vertical-align: top;
		}
		
		.param-table tr:last-child td {
			border-bottom: none;
		}
		
		.param-name {
			font-family: monospace;
			font-weight: 600;
			white-space: nowrap;
		}
		
		.param-type {
			color: var(--badge-filter-color);
			font-size: 0.9em;
			white-space: nowrap;
		}
		
		.param-description {
			line-height: 1.5;
		}
		
		.param-optional {
			opacity: 0.6;
			font-style: italic;
			font-size: 0.85em;
			margin-left: 5px;
		}
		
		.docblock-section {
			margin: 15px 0;
			padding-bottom: 10px;
			border-bottom: 1px solid var(--border-color);
		}
		
		.docblock-section:last-child {
			border-bottom: none;
			padding-bottom: 0;
		}
		
		.docblock-section-title {
			font-weight: bold;
			margin-bottom: 8px;
			color: var(--text-color);
		}
		
		.return-value {
			margin-top: 8px;
			padding: 8px 12px;
			background-color: var(--section-bg);
			border-radius: 4px;
			border-left: 3px solid var(--badge-filter-color);
		}
		
		.return-type {
			font-family: monospace;
			font-weight: 600;
			color: var(--badge-filter-color);
		}
		
		/* Scroll to top button */
		.scroll-to-top {
			position: fixed;
			bottom: 20px;
			right: 20px;
			background-color: var(--badge-action-bg);
			color: var(--badge-action-color);
			width: 40px;
			height: 40px;
			border-radius: 50%;
			display: flex;
			justify-content: center;
			align-items: center;
			cursor: pointer;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
			border: none;
			font-size: 20px;
			z-index: 100;
			opacity: 0;
			transform: translateY(20px);
			transition: opacity 0.3s, transform 0.3s;
		}
		
		.scroll-to-top.visible {
			opacity: 1;
			transform: translateY(0);
		}
		
		.scroll-to-top:hover {
			background-color: var(--badge-action-color);
			color: var(--bg-color);
		}
		
		@media (max-width: 768px) {
			.scroll-to-top {
				bottom: 15px;
				right: 15px;
				width: 35px;
				height: 35px;
				font-size: 18px;
			}
		}
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
	
	<!-- Placeholder to prevent content jump when filters become fixed -->
	<div class="filters-placeholder" id="filters-placeholder"></div>
	
	<!-- Sticky version of filters that appears when scrolling -->
	<div class="sticky-filters" id="sticky-filters">
		<input type="text" id="sticky-search" placeholder="Search hooks..." autocomplete="off">
		<select id="sticky-type-filter">
			<option value="all">All Types</option>
			<option value="action">Actions</option>
			<option value="filter">Filters</option>
		</select>
		<select id="sticky-core-filter">
			<option value="all">All Sources</option>
			<option value="yes">WordPress Core</option>
			<option value="no">Plugin Specific</option>
		</select>
	</div>
	
	<div class="stats">
		<span id="shown-count">0</span> of <span id="total-count">' . count( $hooks ) . '</span> hooks shown
	</div>
	
	<table id="hooks-table" class="hooks-table">
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
		
		$html .= '<tr data-name="' . esc_attr( $hook['name'] ) . '" data-type="' . esc_attr( $hook['type'] ) . '" data-core="' . esc_attr( $hook['is_core'] ) . '" class="hook-row">
			<td data-column="Hook">
				<div class="hook-name">' . esc_html( $hook['name'] ) . '</div>
				<div>
					<span class="badge ' . $hook_type_class . '">' . esc_html( $hook['type'] ) . '</span>
					<span class="badge ' . $hook_source_class . '">' . ($hook['is_core'] === 'yes' ? 'Core' : 'Plugin') . '</span>
				</div>';
				
		if ( isset( $hook['context'] ) && ! empty( $hook['context'] ) ) {
			$html .= '<button class="view-context-btn" onclick="toggleContext(this)">View Source Context</button>';
			$html .= '<div class="context-code">';
			
			foreach ( $hook['context'] as $index => $line ) {
				$line_number = $hook['context_start'] + $index;
				$is_hook_line = ( $line_number == $hook['line_number'] );
				$html .= '<div class="' . ($is_hook_line ? 'context-line-highlight' : 'context-line') . '">';
				$html .= $line_number . ': ' . htmlspecialchars($line) . '</div>';
			}
			
			$html .= '</div>';
		}
				
		$html .= '</td>
			<td data-column="Details">
				<div class="hook-meta">
					<div><strong>File:</strong> ' . esc_html( $hook['file'] ) . '</div>
					<div><strong>Line:</strong> ' . intval( $hook['line_number'] ) . '</div>
					<div><strong>Function:</strong> ' . esc_html( $hook['function_call'] ) . '</div>
				</div>';
				
		if ( !empty( $hook['docblock'] ) ) {
			// Format the docblock for better readability
			$docblock = $hook['docblock'];
			
			// Initialize arrays to store different parts of the docblock
			$description_lines = [];
			$param_lines = [];
			$return_line = '';
			$since_line = '';
			$see_lines = [];
			$other_lines = [];
			
			// Parse the docblock
			$lines = explode("\n", $docblock);
			$current_section = 'description';
			
			foreach ($lines as $line) {
				$line = trim($line);
				// Skip empty lines and comment markers
				if (empty($line) || $line === '/**' || $line === '*/') {
					continue;
				}
				
				// Remove leading asterisk if present
				if (substr($line, 0, 1) === '*') {
					$line = trim(substr($line, 1));
				}
				
				// Categorize the line based on its tag
				if (strpos($line, '@param') === 0) {
					$param_lines[] = $line;
					$current_section = 'param';
				} 
				else if (strpos($line, '@return') === 0) {
					$return_line = $line;
					$current_section = 'return';
				}
				else if (strpos($line, '@since') === 0) {
					$since_line = $line;
					$current_section = 'since';
				}
				else if (strpos($line, '@see') === 0) {
					$see_lines[] = $line;
					$current_section = 'see';
				}
				else if (strpos($line, '@') === 0) {
					$other_lines[] = $line;
					$current_section = 'other';
				}
				else {
					// Add to the current section
					if ($current_section === 'description') {
						$description_lines[] = $line;
					} else if ($current_section === 'param' && !empty($param_lines)) {
						// Append to the last param line if it's a continuation
						$last_index = count($param_lines) - 1;
						$param_lines[$last_index] .= ' ' . $line;
					} else if ($current_section === 'return' && !empty($return_line)) {
						$return_line .= ' ' . $line;
					} else if ($current_section === 'since' && !empty($since_line)) {
						$since_line .= ' ' . $line;
					} else if ($current_section === 'see' && !empty($see_lines)) {
						$last_index = count($see_lines) - 1;
						$see_lines[$last_index] .= ' ' . $line;
					} else if ($current_section === 'other' && !empty($other_lines)) {
						$last_index = count($other_lines) - 1;
						$other_lines[$last_index] .= ' ' . $line;
					}
				}
			}
			
			// Build the HTML output
			$docblock_html = '';
			
			// Description section
			if (!empty($description_lines)) {
				$docblock_html .= '<div class="docblock-section">';
				$docblock_html .= '<div class="docblock-section-content">' . esc_html(implode(' ', $description_lines)) . '</div>';
				$docblock_html .= '</div>';
			}
			
			// Parameters section
			if (!empty($param_lines)) {
				$docblock_html .= '<div class="docblock-section">';
				$docblock_html .= '<div class="docblock-section-title">Parameters</div>';
				$docblock_html .= '<table class="param-table">';
				$docblock_html .= '<thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>';
				$docblock_html .= '<tbody>';
				
				foreach ($param_lines as $param_line) {
					// Remove @param tag
					$param_line = trim(substr($param_line, 6));
					
					// Extract parts using regex to handle complex types and descriptions
					if (preg_match('/^\s*(\S+)\s+(\$\S+)(?:\s+(.*))?$/', $param_line, $matches)) {
						$type = $matches[1];
						$name = $matches[2];
						$description = isset($matches[3]) ? $matches[3] : '';
						
						// Check if parameter is optional
						$optional = '';
						if (strpos($type, '|null') !== false || strpos($description, 'optional') !== false) {
							$optional = '<span class="param-optional">(Optional)</span>';
						}
						
						$docblock_html .= '<tr>';
						$docblock_html .= '<td class="param-name">' . esc_html($name) . $optional . '</td>';
						$docblock_html .= '<td class="param-type">' . esc_html($type) . '</td>';
						$docblock_html .= '<td class="param-description">' . esc_html($description) . '</td>';
						$docblock_html .= '</tr>';
					}
				}
				
				$docblock_html .= '</tbody></table>';
				$docblock_html .= '</div>';
			}
			
			// Return section
			if (!empty($return_line)) {
				$docblock_html .= '<div class="docblock-section">';
				$docblock_html .= '<div class="docblock-section-title">Return Value</div>';
				
				// Extract return type and description
				$return_line = trim(substr($return_line, 7)); // Remove @return tag
				if (preg_match('/^\s*(\S+)(?:\s+(.*))?$/', $return_line, $matches)) {
					$return_type = $matches[1];
					$return_description = isset($matches[2]) ? $matches[2] : '';
					
					$docblock_html .= '<div class="return-value">';
					$docblock_html .= '<span class="return-type">' . esc_html($return_type) . '</span>';
					if (!empty($return_description)) {
						$docblock_html .= ' - ' . esc_html($return_description);
					}
					$docblock_html .= '</div>';
				} else {
					$docblock_html .= '<div class="return-value">' . esc_html($return_line) . '</div>';
				}
				
				$docblock_html .= '</div>';
			}
			
			// Since section
			if (!empty($since_line)) {
				$docblock_html .= '<div class="docblock-section">';
				$docblock_html .= '<div class="docblock-section-title">Since</div>';
				$docblock_html .= '<div>' . esc_html(trim(substr($since_line, 6))) . '</div>'; // Remove @since tag
				$docblock_html .= '</div>';
			}
			
			// See also section
			if (!empty($see_lines)) {
				$docblock_html .= '<div class="docblock-section">';
				$docblock_html .= '<div class="docblock-section-title">See Also</div>';
				$docblock_html .= '<ul>';
				
				foreach ($see_lines as $see_line) {
					$docblock_html .= '<li>' . esc_html(trim(substr($see_line, 4))) . '</li>'; // Remove @see tag
				}
				
				$docblock_html .= '</ul>';
				$docblock_html .= '</div>';
			}
			
			// Other tags
			if (!empty($other_lines)) {
				$docblock_html .= '<div class="docblock-section">';
				$docblock_html .= '<div class="docblock-section-title">Additional Information</div>';
				
				foreach ($other_lines as $line) {
					$docblock_html .= '<div>' . esc_html($line) . '</div>';
				}
				
				$docblock_html .= '</div>';
			}
			
			$html .= '<div class="docblock">' . $docblock_html . '</div>';
		}
		
		// Add related hooks section if available
		if ( !empty( $hook['related_hooks'] ) ) {
			$html .= '<div class="related-hooks">
				<h4>Related Hooks</h4>
				<ul class="related-hooks-list">';
			
			foreach ( $hook['related_hooks'] as $related ) {
				$related_type_class = $related['type'] === 'action' ? 'action' : 'filter';
				$relationship_label = ucfirst(str_replace('_', ' ', $related['relationship']));
				
				$html .= '<li>
					<a href="#" class="related-hook-link" data-hook-name="' . esc_attr( $related['name'] ) . '">
						<span class="badge small ' . $related_type_class . '">' . esc_html( $related['type'] ) . '</span>
						<span class="related-hook-name">' . esc_html( $related['name'] ) . '</span>
					</a>
					<span class="relationship-type" title="Relationship type">(' . esc_html( $relationship_label ) . ')</span>
				</li>';
			}
			
			$html .= '</ul>
			</div>';
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
	// Define toggleContext globally to avoid "not defined" error
	function toggleContext(button) {
		var contextCode = button.nextElementSibling;
		var row = button.closest("tr");
		
		if (contextCode.style.display === "block") {
			// Hide context, restore normal table layout
			contextCode.style.display = "none";
			button.textContent = "View Source Context";
			row.classList.remove("showing-context");
			
			// Make sure to restore any hidden details cell
			var detailsCell = row.querySelector("td[data-column=\"Details\"]");
			if (detailsCell) {
				detailsCell.style.display = "";
			}
		} else {
			// Show context, use full-width layout
			contextCode.style.display = "block";
			button.textContent = "Hide Source Context";
			row.classList.add("showing-context");
			
			// Hide details cell
			var detailsCell = row.querySelector("td[data-column=\"Details\"]");
			if (detailsCell) {
				detailsCell.style.display = "none";
			}
		}
	}

	// Scroll to top function
	function scrollToTop() {
		window.scrollTo({
			top: 0,
			behavior: "smooth"
		});
	}

	document.addEventListener("DOMContentLoaded", function() {
		const searchInput = document.getElementById("search");
		const typeFilter = document.getElementById("type-filter");
		const coreFilter = document.getElementById("core-filter");
		const stickySearch = document.getElementById("sticky-search");
		const stickyTypeFilter = document.getElementById("sticky-type-filter");
		const stickyCoreFilter = document.getElementById("sticky-core-filter");
		const stickyFilters = document.getElementById("sticky-filters");
		const filtersPlaceholder = document.getElementById("filters-placeholder");
		const filtersOriginal = document.querySelector(".filters");
		const scrollToTopBtn = document.getElementById("scroll-to-top");
		const table = document.getElementById("hooks-table");
		const rows = table.querySelectorAll("tbody tr");
		const noResults = document.getElementById("no-results");
		const shownCount = document.getElementById("shown-count");
		const totalCount = document.getElementById("total-count");
		const themeToggle = document.getElementById("theme-toggle");
		const themeIcon = document.getElementById("theme-icon");
		const themeText = document.getElementById("theme-text");
		const html = document.documentElement;
		
		// Sticky filters handling
		function handleScroll() {
			const filtersPosition = filtersOriginal.getBoundingClientRect().top;
			const scrollThreshold = 0;
			
			if (filtersPosition < scrollThreshold) {
				stickyFilters.classList.add("visible");
				filtersPlaceholder.classList.add("active");
			} else {
				stickyFilters.classList.remove("visible");
				filtersPlaceholder.classList.remove("active");
			}
			
			// Check scroll position for scroll-to-top button
			if (window.scrollY > 300) {
				scrollToTopBtn.classList.add("visible");
			} else {
				scrollToTopBtn.classList.remove("visible");
			}
		}
		
		// Add scroll event listener
		window.addEventListener("scroll", handleScroll);
		
		// Sync between regular and sticky inputs
		function syncInputs(source, target) {
			target.value = source.value;
		}
		
		// Sync inputs when changed
		searchInput.addEventListener("input", function() {
			syncInputs(searchInput, stickySearch);
			filterTable();
		});
		
		stickySearch.addEventListener("input", function() {
			syncInputs(stickySearch, searchInput);
			filterTable();
		});
		
		typeFilter.addEventListener("change", function() {
			syncInputs(typeFilter, stickyTypeFilter);
			filterTable();
		});
		
		stickyTypeFilter.addEventListener("change", function() {
			syncInputs(stickyTypeFilter, typeFilter);
			filterTable();
		});
		
		coreFilter.addEventListener("change", function() {
			syncInputs(coreFilter, stickyCoreFilter);
			filterTable();
		});
		
		stickyCoreFilter.addEventListener("change", function() {
			syncInputs(stickyCoreFilter, coreFilter);
			filterTable();
		});
		
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
				// Get attributes safely with null checks to prevent TypeError
				const nameAttr = row.getAttribute("data-name");
				const typeAttr = row.getAttribute("data-type");
				const coreAttr = row.getAttribute("data-core");
				
				// Only proceed with filtering if all required attributes exist
				if (nameAttr && typeAttr && coreAttr) {
					const name = nameAttr.toLowerCase();
					const type = typeAttr;
					const isCore = coreAttr;
					
					const matchesSearch = searchTerm === "" || name.includes(searchTerm);
					const matchesType = typeValue === "all" || type === typeValue;
					const matchesCore = coreValue === "all" || isCore === coreValue;
					
					if (matchesSearch && matchesType && matchesCore) {
						row.style.display = "";
						visibleCount++;
					} else {
						row.style.display = "none";
					}
				} else {
					// If missing attributes, show the row to avoid hiding content
					row.style.display = "";
					visibleCount++;
				}
			});
			
			shownCount.textContent = visibleCount;
			noResults.style.display = visibleCount === 0 ? "block" : "none";
		}
		
		// Initial count and scroll check
		filterTable();
		handleScroll();

		// Handle clicking on related hook links
		document.querySelectorAll(".related-hook-link").forEach(link => {
			link.addEventListener("click", function(e) {
				e.preventDefault();
				const hookName = this.getAttribute("data-hook-name");
				
				// Clear any current search/filters
				searchInput.value = "";
				typeFilter.value = "all";
				coreFilter.value = "all";
				
				// Sync with sticky filters
				syncInputs(searchInput, stickySearch);
				syncInputs(typeFilter, stickyTypeFilter);
				syncInputs(coreFilter, stickyCoreFilter);
				
				// Filter to just this hook
				searchInput.value = hookName;
				syncInputs(searchInput, stickySearch);
				filterTable();
				
				// Scroll to the hook
				const hookRow = document.querySelector(\'tr[data-name="\' + hookName + \'"]\');
				if (hookRow) {
					hookRow.scrollIntoView({ behavior: "smooth", block: "center" });
					hookRow.classList.add("highlight-row");
					setTimeout(() => {
						hookRow.classList.remove("highlight-row");
					}, 2000);
				}
			});
		});
	});
	</script>
	
	<button id="scroll-to-top" class="scroll-to-top" onclick="scrollToTop()">↑</button>
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