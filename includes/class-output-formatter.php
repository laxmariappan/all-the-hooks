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
	 * Save formatted output to a file.
	 *
	 * @param string $content    Formatted content to save.
	 * @param string $output_path Path where to save the file.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function save_to_file( $content, $output_path ) {
		// Ensure directory exists
		$dir = dirname( $output_path );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		
		// Write the file
		$result = file_put_contents( $output_path, $content );
		
		if ( false === $result ) {
			return new \WP_Error( 'save_failed', "Failed to save output to: {$output_path}" );
		}
		
		return true;
	}
} 