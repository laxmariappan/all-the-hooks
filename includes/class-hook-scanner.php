<?php
/**
 * Hook Scanner class.
 *
 * @package AllTheHooks
 */

namespace AllTheHooks;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\NameResolver;

/**
 * Class HookScanner
 *
 * Scans PHP files for WordPress hooks.
 */
class HookScanner {

	/**
	 * Source slug (plugin or theme slug) to scan
	 *
	 * @var string
	 */
	private $source_slug;

	/**
	 * Source type (plugin or theme)
	 *
	 * @var string
	 */
	private $source_type;

	/**
	 * Hook type to filter by (all, action, filter)
	 *
	 * @var string
	 */
	private $hook_type;

	/**
	 * Whether to include DocBlocks
	 *
	 * @var bool
	 */
	private $include_docblocks;

	/**
	 * HookScanner constructor.
	 *
	 * @param string $source_slug       Plugin or theme slug to scan.
	 * @param string $hook_type         Type of hooks to scan for (all, action, or filter).
	 * @param bool   $include_docblocks Whether to include DocBlocks in the results.
	 * @param string $source_type       Source type: 'plugin' or 'theme'. Default 'plugin'.
	 */
	public function __construct( $source_slug, $hook_type = 'all', $include_docblocks = false, $source_type = 'plugin' ) {
		$this->source_slug       = $source_slug;
		$this->source_type       = $source_type;
		$this->hook_type         = $hook_type;
		$this->include_docblocks = $include_docblocks;
	}

	/**
	 * Get the source directory path (plugin or theme).
	 *
	 * @return string|false Path to the source directory or false if not found.
	 */
	public function get_source_directory() {
		if ( 'theme' === $this->source_type ) {
			return $this->get_theme_directory();
		}
		return $this->get_plugin_directory();
	}

	/**
	 * Get the plugin directory path.
	 *
	 * @return string|false Path to the plugin directory or false if not found.
	 */
	public function get_plugin_directory() {
		$plugin_dir = WP_PLUGIN_DIR . '/' . $this->source_slug;
		if ( ! is_dir( $plugin_dir ) ) {
			// Try with standard format (plugin-slug/plugin-slug.php).
			$plugin_dir = WP_PLUGIN_DIR . '/' . $this->source_slug;
		}

		return is_dir( $plugin_dir ) ? $plugin_dir : false;
	}

	/**
	 * Get the theme directory path.
	 *
	 * @return string|false Path to the theme directory or false if not found.
	 */
	public function get_theme_directory() {
		// Check if it's an active theme
		$theme = wp_get_theme( $this->source_slug );

		if ( $theme->exists() ) {
			return $theme->get_stylesheet_directory();
		}

		// Fallback: check themes directory directly
		$theme_dir = get_theme_root() . '/' . $this->source_slug;
		return is_dir( $theme_dir ) ? $theme_dir : false;
	}

	/**
	 * Get all PHP files in the plugin directory recursively.
	 *
	 * @param string $directory Directory to scan.
	 * @return array Array of PHP file paths.
	 */
	public function get_php_files( $directory ) {
		$php_files  = array();
		$dir_handle = opendir( $directory );
		
		if ( ! $dir_handle ) {
			return $php_files;
		}

		while ( false !== ( $file = readdir( $dir_handle ) ) ) {
			if ( '.' === $file || '..' === $file ) {
				continue;
			}
			
			$file_path = $directory . '/' . $file;
			
			if ( is_dir( $file_path ) ) {
				$php_files = array_merge( $php_files, $this->get_php_files( $file_path ) );
			} elseif ( pathinfo( $file_path, PATHINFO_EXTENSION ) === 'php' ) {
				$php_files[] = $file_path;
			}
		}
		
		closedir( $dir_handle );
		return $php_files;
	}

	/**
	 * Scan the plugin or theme for hooks.
	 *
	 * @return array|WP_Error Array of hooks or WP_Error on failure.
	 */
	public function scan() {
		$source_directory = $this->get_source_directory();

		if ( ! $source_directory ) {
			$source_type = ucfirst( $this->source_type );
			return new \WP_Error(
				strtolower( $this->source_type ) . '_not_found',
				"{$source_type} '{$this->source_slug}' not found."
			);
		}

		$php_files = $this->get_php_files( $source_directory );
		
		if ( empty( $php_files ) ) {
			$source_type = ucfirst( $this->source_type );
			return new \WP_Error(
				'no_php_files',
				"No PHP files found in {$source_type} '{$this->source_slug}'."
			);
		}
		
		$hooks = array();
		$all_listeners = array(); // Store all listeners across all files
		$parser = ( new ParserFactory() )->create( ParserFactory::PREFER_PHP7 );
		$traverser = new NodeTraverser();
		$traverser->addVisitor( new NameResolver() );
		$hook_visitor = new HookVisitor( $this->include_docblocks );
		$traverser->addVisitor( $hook_visitor );

		// First pass: collect all hooks and listeners
		foreach ( $php_files as $file_path ) {
			$file_content = file_get_contents( $file_path );
			$file_lines = file( $file_path );
			
			if ( false === $file_content ) {
				continue;
			}
			
			try {
				$ast = $parser->parse( $file_content );
				$traverser->traverse( $ast );
				$file_hooks = $hook_visitor->get_hooks();
				$file_listeners = $hook_visitor->get_listeners();

				// Process listeners and store them with file information
				foreach ( $file_listeners as $listener ) {
					$rel_path = str_replace( $source_directory . '/', '', $file_path );
					$listener['file'] = $rel_path;
					$all_listeners[] = $listener;
				}

				foreach ( $file_hooks as $hook ) {
					// Filter by hook type if specified
					if ( 'all' !== $this->hook_type && $hook['type'] !== $this->hook_type ) {
						continue;
					}
					
					// Calculate relative path from source directory
					$rel_path = str_replace( $source_directory . '/', '', $file_path );
					
					// Determine if it's a core hook
					$is_core = $this->is_core_hook($hook['name']);
					
					// Get context lines (3-5 lines surrounding the hook)
					$context_lines = array();
					$line_number = $hook['line'];
					$start_line = max(1, $line_number - 2); // 2 lines before
					$end_line = min(count($file_lines), $line_number + 2); // 2 lines after
					
					$context_lines = array_slice($file_lines, $start_line - 1, $end_line - $start_line + 1);
					
					$hook_data = [
						'name' => $hook['name'],
						'type' => $hook['type'], // This should be 'action' or 'filter' from HookVisitor
						'is_core' => $is_core ? 'yes' : 'no',
						'file' => $rel_path,
						'line_number' => isset($hook['line']) ? $hook['line'] : 0,
						'function_call' => isset($hook['function_call']) ? $hook['function_call'] : '',
						'docblock' => isset($hook['docblock']) ? $hook['docblock'] : '',
						'context' => $context_lines,
						'context_start' => $start_line,
						'related_hooks' => [], // Will store related hooks
					];
					
					$hooks[] = $hook_data;
				}
				
				// Reset the visitor for the next file
				$hook_visitor->reset();
			} catch ( Error $e ) {
				// Log parsing error but continue with other files
				continue;
			}
		}
		
		// Second pass: identify relationships between hooks
		if ( count( $hooks ) > 0 ) {
			$hooks = $this->identify_hook_relationships( $hooks );
		}

		// Third pass: attach listeners to hooks
		if ( count( $all_listeners ) > 0 ) {
			$hooks = $this->attach_listeners_to_hooks( $hooks, $all_listeners );
		}

		return $hooks;
	}

	/**
	 * Identify related hooks based on naming patterns and proximity
	 *
	 * @param array $hooks Array of collected hooks
	 * @return array Updated hooks with relationship data
	 */
	private function identify_hook_relationships($hooks) {
		// Common relationship patterns
		$patterns = [
			// Before/After patterns
			['before_', 'after_'],
			['pre_', 'post_'],
			// Start/End patterns
			['start_', 'end_'],
			['begin_', 'complete_'],
			// Init/Process/Complete patterns
			['init_', 'process_', 'complete_'],
			// Specific WP patterns
			['wp_ajax_', 'wp_ajax_nopriv_'],
		];
		
		// Common word stems that might be paired
		$common_stems = ['save', 'update', 'delete', 'create', 'render', 'display', 'load', 'process'];
		
		// Find related hooks by name patterns
		foreach ($hooks as $i => &$hook) {
			$hook_name = $hook['name'];
			$related = [];
			
			// 1. Check for common prefixes/suffixes patterns
			foreach ($patterns as $pattern_group) {
				foreach ($pattern_group as $pattern) {
					if (strpos($hook_name, $pattern) === 0) {
						// This hook starts with a pattern, look for matching hooks
						$base_name = substr($hook_name, strlen($pattern));
						
						foreach ($pattern_group as $related_pattern) {
							if ($pattern === $related_pattern) continue;
							
							$related_hook_name = $related_pattern . $base_name;
							// Find this hook in our collected hooks
							foreach ($hooks as $j => $potential_match) {
								if ($i !== $j && $potential_match['name'] === $related_hook_name) {
									$related[] = [
										'name' => $potential_match['name'],
										'type' => $potential_match['type'],
										'relationship' => 'naming pattern'
									];
								}
							}
						}
					}
				}
			}
			
			// 2. Check for common stems with prefixes/suffixes
			foreach ($common_stems as $stem) {
				if (strpos($hook_name, $stem) !== false) {
					// This hook contains a common stem, look for other hooks with the same stem
					foreach ($hooks as $j => $potential_match) {
						if ($i !== $j && $potential_match['name'] !== $hook_name && 
							strpos($potential_match['name'], $stem) !== false) {
							// Check if they share a common prefix or are clearly related
							$name_parts = explode('_', $hook_name);
							$match_parts = explode('_', $potential_match['name']);
							
							// If they share at least 2 word parts, consider them related
							$shared_parts = array_intersect($name_parts, $match_parts);
							if (count($shared_parts) >= 2) {
								$related[] = [
									'name' => $potential_match['name'],
									'type' => $potential_match['type'],
									'relationship' => 'common word stem'
								];
							}
						}
					}
				}
			}
			
			// 3. Check for hooks in the same file with sequential line numbers (proximity)
			$file = $hook['file'];
			$line = $hook['line_number'];
			$proximity_range = 15; // Consider hooks within 15 lines as potentially related
			
			foreach ($hooks as $j => $potential_match) {
				if ($i !== $j && $potential_match['file'] === $file) {
					$line_diff = abs($potential_match['line_number'] - $line);
					if ($line_diff <= $proximity_range) {
						// Check if they share naming elements to strengthen relationship
						$name_parts = explode('_', $hook_name);
						$match_parts = explode('_', $potential_match['name']);
						$shared_parts = array_intersect($name_parts, $match_parts);
						
						// If nearby and share naming elements, highly likely related
						if (count($shared_parts) >= 1) {
							$related[] = [
								'name' => $potential_match['name'],
								'type' => $potential_match['type'],
								'relationship' => 'proximity and naming'
							];
						}
						// If just nearby, possibly related
						else if ($line_diff <= 5) {
							$related[] = [
								'name' => $potential_match['name'],
								'type' => $potential_match['type'],
								'relationship' => 'proximity'
							];
						}
					}
				}
			}
			
			// Limit to most likely related hooks (avoid too many tenuous connections)
			// Sort by relationship strength
			usort($related, function($a, $b) {
				$strength = [
					'naming pattern' => 3,
					'proximity and naming' => 2,
					'common word stem' => 1,
					'proximity' => 0
				];
				return $strength[$b['relationship']] - $strength[$a['relationship']];
			});
			
			// Remove duplicates and limit to 5 most relevant
			$unique_related = [];
			$seen_hooks = [];
			foreach ($related as $rel) {
				if (!in_array($rel['name'], $seen_hooks)) {
					$unique_related[] = $rel;
					$seen_hooks[] = $rel['name'];
					if (count($unique_related) >= 5) break;
				}
			}
			
			$hook['related_hooks'] = $unique_related;
		}
		
		return $hooks;
	}

	/**
	 * Determine if a hook is a WordPress core hook or plugin-specific
	 */
	private function determine_hook_type($hook_name) {
		// List of common WordPress hook prefixes
		$wp_core_prefixes = ['wp_', 'pre_', 'post_', 'after_', 'before_', 'the_', 'admin_'];
		
		foreach ($wp_core_prefixes as $prefix) {
			if (strpos($hook_name, $prefix) === 0) {
				return 'core hook';
			}
		}
		
		// You may want to add more sophisticated detection
		return 'plugin hook';
	}

	/**
	 * Check if a hook is a core hook
	 *
	 * @param string $hook_name The hook name to check.
	 * @return bool True if the hook is a core hook, false otherwise.
	 */
	private function is_core_hook($hook_name) {
		// List of common WordPress hook prefixes
		$wp_core_prefixes = ['wp_', 'pre_', 'post_', 'after_', 'before_', 'the_', 'admin_'];

		foreach ($wp_core_prefixes as $prefix) {
			if (strpos($hook_name, $prefix) === 0) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Attach listeners to their corresponding hooks
	 *
	 * @param array $hooks     Array of collected hooks
	 * @param array $listeners Array of collected listeners
	 * @return array Updated hooks with listener data
	 */
	private function attach_listeners_to_hooks( $hooks, $listeners ) {
		// Create a map of listeners by hook name for faster lookup
		$listeners_by_hook = array();
		foreach ( $listeners as $listener ) {
			$hook_name = $listener['hook_name'];
			if ( ! isset( $listeners_by_hook[ $hook_name ] ) ) {
				$listeners_by_hook[ $hook_name ] = array();
			}
			$listeners_by_hook[ $hook_name ][] = $listener;
		}

		// Attach listeners to each hook
		foreach ( $hooks as &$hook ) {
			$hook_name = $hook['name'];

			// Initialize listeners array
			$hook['listeners'] = array();

			// Find matching listeners
			if ( isset( $listeners_by_hook[ $hook_name ] ) ) {
				$matching_listeners = $listeners_by_hook[ $hook_name ];

				// Sort by priority (lower numbers first)
				usort( $matching_listeners, function( $a, $b ) {
					return $a['priority'] - $b['priority'];
				});

				// Add listener information
				foreach ( $matching_listeners as $listener ) {
					$hook['listeners'][] = array(
						'callback'      => $listener['callback'],
						'priority'      => $listener['priority'],
						'accepted_args' => $listener['accepted_args'],
						'file'          => $listener['file'],
						'line'          => $listener['line'],
					);
				}
			}
		}

		return $hooks;
	}
} 