<?php
/**
 * WP-CLI command for All The Hooks plugin.
 *
 * @package AllTheHooks
 */

namespace AllTheHooks\CLI;

use AllTheHooks\HookScanner;
use AllTheHooks\OutputFormatter;
use WP_CLI;
use WP_CLI\Utils;

// Register the command
if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'all-the-hooks', 'AllTheHooks\CLI\All_The_Hooks_Command' );
}

/**
 * Discovers hooks in WordPress plugins.
 *
 * ## EXAMPLES
 *
 *     # Scan WooCommerce plugin for hooks and output JSON
 *     $ wp all-the-hooks scan --plugin=woocommerce
 *
 *     # Scan Akismet plugin for hooks, include docblocks, and output as Markdown
 *     $ wp all-the-hooks scan --plugin=akismet --include_docblocks=true --format=markdown
 *
 *     # Scan a plugin for actions only and save to a specific file
 *     $ wp all-the-hooks scan --plugin=jetpack --hook_type=action --output_path=/path/to/jetpack-actions.json
 */
class All_The_Hooks_Command {

	/**
	 * Scans a plugin for WordPress hooks.
	 *
	 * ## OPTIONS
	 *
	 * --plugin=<plugin-slug>
	 * : The slug of the plugin to scan.
	 *
	 * [--format=<format>]
	 * : Output format (json, markdown, or html).
	 * ---
	 * default: json
	 * options:
	 *   - json
	 *   - markdown
	 *   - html
	 * ---
	 *
	 * [--include_docblocks=<include>]
	 * : Whether to include docblocks in the output.
	 * ---
	 * default: false
	 * options:
	 *   - true
	 *   - false
	 * ---
	 *
	 * [--output_path=<path>]
	 * : Path to save the output. If not provided, prints to STDOUT.
	 *
	 * [--hook_type=<type>]
	 * : Type of hooks to scan for.
	 * ---
	 * default: all
	 * options:
	 *   - all
	 *   - action
	 *   - filter
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Scan WooCommerce plugin for hooks
	 *     $ wp all-the-hooks scan --plugin=woocommerce
	 *
	 *     # Include docblocks and output as Markdown
	 *     $ wp all-the-hooks scan --plugin=akismet --include_docblocks=true --format=markdown
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command options.
	 */
	public function scan( $args, $assoc_args ) {
		// Parse arguments
		$plugin_slug = $assoc_args['plugin'] ?? '';
		$format = $assoc_args['format'] ?? 'json';
		$include_docblocks = filter_var( $assoc_args['include_docblocks'] ?? 'false', FILTER_VALIDATE_BOOLEAN );
		$output_path = $assoc_args['output_path'] ?? '';
		$hook_type = $assoc_args['hook_type'] ?? 'all';
		
		if ( empty( $plugin_slug ) ) {
			WP_CLI::error( 'Plugin slug is required. Use --plugin=<plugin-slug>.' );
			return;
		}
		
		// Validate format
		if ( ! in_array( $format, array( 'json', 'markdown', 'html' ), true ) ) {
			WP_CLI::error( 'Invalid format. Use --format=json, --format=markdown, or --format=html.' );
			return;
		}
		
		// Validate hook type
		if ( ! in_array( $hook_type, array( 'all', 'action', 'filter' ), true ) ) {
			WP_CLI::error( 'Invalid hook type. Use --hook_type=all, --hook_type=action, or --hook_type=filter.' );
			return;
		}
		
		WP_CLI::log( "Scanning {$plugin_slug} plugin for hooks..." );
		
		// Create scanner
		$scanner = new HookScanner( $plugin_slug, $hook_type, $include_docblocks );
		$hooks = $scanner->scan();
		
		// Check for errors
		if ( is_wp_error( $hooks ) ) {
			WP_CLI::error( $hooks->get_error_message() );
			return;
		}
		
		// Check if any hooks were found
		if ( empty( $hooks ) ) {
			WP_CLI::warning( "No hooks found in {$plugin_slug} plugin." );
			return;
		}
		
		// Format output
		if ( 'json' === $format ) {
			$output = OutputFormatter::to_json( $hooks, $plugin_slug );
			$ext = 'json';
		} elseif ( 'markdown' === $format ) {
			$output = OutputFormatter::to_markdown( $hooks, $plugin_slug );
			$ext = 'md';
		} else {
			$output = OutputFormatter::to_html( $hooks, $plugin_slug );
			$ext = 'html';
		}
		
		// Determine output destination
		if ( empty( $output_path ) ) {
			// Output to STDOUT
			WP_CLI::log( $output );
			
			// Display summary
			$action_count = count( array_filter( $hooks, function( $hook ) {
				return 'action' === $hook['type'];
			} ) );
			
			$filter_count = count( array_filter( $hooks, function( $hook ) {
				return 'filter' === $hook['type'];
			} ) );
			
			WP_CLI::success( "Found " . count( $hooks ) . " hooks ({$action_count} actions, {$filter_count} filters)." );
		} else {
			// Save to file
			$output_file = $output_path;
			
			// If output_path doesn't have the right extension, add it
			if ( ! preg_match( '/\.' . preg_quote( $ext, '/' ) . '$/i', $output_path ) ) {
				$output_file = $output_path . '.' . $ext;
			}
			
			// If output_path is just a directory, create a file with plugin slug
			if ( is_dir( $output_path ) ) {
				$output_file = rtrim( $output_path, '/' ) . '/' . $plugin_slug . '-hooks.' . $ext;
			}
			
			$result = OutputFormatter::save_to_file( $output, $output_file );
			
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result->get_error_message() );
				return;
			}
			
			// Display summary
			$action_count = count( array_filter( $hooks, function( $hook ) {
				return 'action' === $hook['type'];
			} ) );
			
			$filter_count = count( array_filter( $hooks, function( $hook ) {
				return 'filter' === $hook['type'];
			} ) );
			
			WP_CLI::success( "Found " . count( $hooks ) . " hooks ({$action_count} actions, {$filter_count} filters)." );
			WP_CLI::log( "Output saved to: {$output_file}" );
		}
	}
} 