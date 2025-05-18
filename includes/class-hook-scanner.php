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
	 * Plugin slug to scan
	 *
	 * @var string
	 */
	private $plugin_slug;

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
	 * @param string $plugin_slug       Plugin slug to scan.
	 * @param string $hook_type         Type of hooks to scan for (all, action, or filter).
	 * @param bool   $include_docblocks Whether to include DocBlocks in the results.
	 */
	public function __construct( $plugin_slug, $hook_type = 'all', $include_docblocks = false ) {
		$this->plugin_slug      = $plugin_slug;
		$this->hook_type        = $hook_type;
		$this->include_docblocks = $include_docblocks;
	}

	/**
	 * Get the plugin directory path.
	 *
	 * @return string|false Path to the plugin directory or false if not found.
	 */
	public function get_plugin_directory() {
		$plugin_dir = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
		if ( ! is_dir( $plugin_dir ) ) {
			// Try with standard format (plugin-slug/plugin-slug.php).
			$plugin_dir = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
		}

		return is_dir( $plugin_dir ) ? $plugin_dir : false;
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
	 * Scan the plugin for hooks.
	 *
	 * @return array|WP_Error Array of hooks or WP_Error on failure.
	 */
	public function scan() {
		$plugin_directory = $this->get_plugin_directory();
		
		if ( ! $plugin_directory ) {
			return new \WP_Error( 'plugin_not_found', "Plugin '{$this->plugin_slug}' not found." );
		}
		
		$php_files = $this->get_php_files( $plugin_directory );
		
		if ( empty( $php_files ) ) {
			return new \WP_Error( 'no_php_files', "No PHP files found in plugin '{$this->plugin_slug}'." );
		}
		
		$hooks = array();
		$parser = ( new ParserFactory() )->create( ParserFactory::PREFER_PHP7 );
		$traverser = new NodeTraverser();
		$traverser->addVisitor( new NameResolver() );
		$hook_visitor = new HookVisitor( $this->include_docblocks );
		$traverser->addVisitor( $hook_visitor );
		
		foreach ( $php_files as $file_path ) {
			$file_content = file_get_contents( $file_path );
			
			if ( false === $file_content ) {
				continue;
			}
			
			try {
				$ast = $parser->parse( $file_content );
				$traverser->traverse( $ast );
				$file_hooks = $hook_visitor->get_hooks();
				
				foreach ( $file_hooks as $hook ) {
					// Filter by hook type if specified
					if ( 'all' !== $this->hook_type && $hook['type'] !== $this->hook_type ) {
						continue;
					}
					
					// Calculate relative path from plugin directory
					$rel_path = str_replace( $plugin_directory . '/', '', $file_path );
					
					// Determine if it's a core hook
					$is_core = $this->is_core_hook($hook['name']);
					
					$hook_data = [
						'name' => $hook['name'],
						'type' => $hook['type'], // This should be 'action' or 'filter' from HookVisitor
						'is_core' => $is_core ? 'yes' : 'no',
						'file' => $rel_path,
						'line_number' => isset($hook['line']) ? $hook['line'] : 0,
						'function_call' => isset($hook['function_call']) ? $hook['function_call'] : '',
						'docblock' => isset($hook['docblock']) ? $hook['docblock'] : '',
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
} 