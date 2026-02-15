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
 * Discovers hooks in WordPress plugins and themes.
 *
 * ## EXAMPLES
 *
 *     # Scan WooCommerce plugin for hooks and output JSON
 *     $ wp all-the-hooks scan --plugin=woocommerce
 *
 *     # Scan a theme for hooks
 *     $ wp all-the-hooks scan --theme=twentytwentyfour
 *
 *     # Scan Akismet plugin for hooks, include docblocks, and output as Markdown
 *     $ wp all-the-hooks scan --plugin=akismet --include_docblocks=true --format=markdown
 *
 *     # Scan a plugin for actions only and save to a specific file
 *     $ wp all-the-hooks scan --plugin=jetpack --hook_type=action --output_path=/path/to/jetpack-actions.json
 *
 *     # Scan Easy Digital Downloads Pro plugin with full documentation and output as HTML to current directory
 *     $ wp all-the-hooks scan --plugin=easy-digital-downloads-pro --format=html --include_docblocks=true --output_path=./
 *
 *     # Scan active theme for hooks with HTML output
 *     $ wp all-the-hooks scan --theme=storefront --format=html --include_docblocks=true
 */
class All_The_Hooks_Command {

	/**
	 * Scans a plugin or theme for WordPress hooks.
	 *
	 * ## OPTIONS
	 *
	 * [--plugin=<plugin-slug>]
	 * : The slug of the plugin to scan.
	 *
	 * [--theme=<theme-slug>]
	 * : The slug of the theme to scan.
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
	 *     # Scan a theme for hooks
	 *     $ wp all-the-hooks scan --theme=twentytwentyfour
	 *
	 *     # Include docblocks and output as Markdown
	 *     $ wp all-the-hooks scan --plugin=akismet --include_docblocks=true --format=markdown
	 *
	 *     # Scan Easy Digital Downloads Pro plugin with full documentation and output as HTML to current directory
	 *     $ wp all-the-hooks scan --plugin=easy-digital-downloads-pro --format=html --include_docblocks=true --output_path=./
	 *
	 *     # Scan active theme for hooks
	 *     $ wp all-the-hooks scan --theme=storefront --format=html --include_docblocks=true
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command options.
	 */
	public function scan( $args, $assoc_args ) {
		// Parse arguments
		$plugin_slug = $assoc_args['plugin'] ?? '';
		$theme_slug  = $assoc_args['theme'] ?? '';
		$format = $assoc_args['format'] ?? 'json';
		$include_docblocks = filter_var( $assoc_args['include_docblocks'] ?? 'false', FILTER_VALIDATE_BOOLEAN );
		$output_path = $assoc_args['output_path'] ?? '';
		$hook_type = $assoc_args['hook_type'] ?? 'all';

		// Determine source type and slug
		if ( empty( $plugin_slug ) && empty( $theme_slug ) ) {
			WP_CLI::error( 'Either --plugin=<plugin-slug> or --theme=<theme-slug> is required.' );
			return;
		}

		if ( ! empty( $plugin_slug ) && ! empty( $theme_slug ) ) {
			WP_CLI::error( 'Please specify either --plugin or --theme, not both.' );
			return;
		}

		$source_type = ! empty( $theme_slug ) ? 'theme' : 'plugin';
		$source_slug = ! empty( $theme_slug ) ? $theme_slug : $plugin_slug;
		
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
		
		WP_CLI::log( "Scanning {$source_slug} {$source_type} for hooks..." );

		// Create scanner
		$scanner = new HookScanner( $source_slug, $hook_type, $include_docblocks, $source_type );
		$hooks = $scanner->scan();
		
		// Check for errors
		if ( is_wp_error( $hooks ) ) {
			WP_CLI::error( $hooks->get_error_message() );
			return;
		}
		
		// Check if any hooks were found
		if ( empty( $hooks ) ) {
			WP_CLI::warning( "No hooks found in {$source_slug} {$source_type}." );
			return;
		}

		// Format output
		if ( 'json' === $format ) {
			$output = OutputFormatter::to_json( $hooks, $source_slug );
			$ext = 'json';
		} elseif ( 'markdown' === $format ) {
			$output = OutputFormatter::to_markdown( $hooks, $source_slug );
			$ext = 'md';
		} else {
			$output = OutputFormatter::to_html( $hooks, $source_slug );
			$ext = 'html';
		}
		
		// Determine output destination
		if ( empty( $output_path ) ) {
			// If no output path specified, use the default .hooks directory
			$hooks_dir = WP_PLUGIN_DIR . '/all-the-hooks/.hooks';
			
			// Create the directory if it doesn't exist
			if ( ! is_dir( $hooks_dir ) ) {
				if ( ! mkdir( $hooks_dir, 0755, true ) ) {
					WP_CLI::error( "Failed to create .hooks directory." );
					return;
				}
			}
			
			$output_file = $hooks_dir . '/' . $source_slug . '-hooks.' . $ext;
			WP_CLI::log( "No output path specified. Saving to default location: {$output_file}" );
		} else {
			// If output_path is specified, use it
			$output_file = $output_path;
			
			// If output_path is just a directory, create a file with source slug
			if ( is_dir( $output_path ) ) {
				$output_file = rtrim( $output_path, '/' ) . '/' . $source_slug . '-hooks.' . $ext;
			}
			// If output_path doesn't have the right extension, add it
			elseif ( ! preg_match( '/\.' . preg_quote( $ext, '/' ) . '$/i', $output_path ) ) {
				$output_file = $output_path . '.' . $ext;
			}
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