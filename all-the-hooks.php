<?php
/**
 * Plugin Name: All The Hooks
 * Description: WordPress plugin to discover and document hooks in plugins and themes
 * Version: 1.0.0
 * Author: Lax Mariappan
 * License: GPL-2.0-or-later
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'ALL_THE_HOOKS_VERSION', '1.0.0' );
define( 'ALL_THE_HOOKS_FILE', __FILE__ );
define( 'ALL_THE_HOOKS_PATH', plugin_dir_path( ALL_THE_HOOKS_FILE ) );
define( 'ALL_THE_HOOKS_URL', plugin_dir_url( ALL_THE_HOOKS_FILE ) );

// Load Composer autoloader
if ( file_exists( ALL_THE_HOOKS_PATH . 'vendor/autoload.php' ) ) {
	require_once ALL_THE_HOOKS_PATH . 'vendor/autoload.php';
} else {
	// Display admin notice if Composer dependencies are missing
	add_action( 'admin_notices', 'all_the_hooks_missing_composer_dependencies' );
	return;
}

/**
 * Display admin notice for missing Composer dependencies
 */
function all_the_hooks_missing_composer_dependencies() {
	?>
	<div class="notice notice-error">
		<p>
			<?php esc_html_e( 'All The Hooks plugin requires Composer dependencies to be installed. Please run "composer install" in the plugin directory.', 'all-the-hooks' ); ?>
		</p>
	</div>
	<?php
}

// WP-CLI command is autoloaded from includes/cli/class-all-the-hooks-command.php through Composer

// Admin menu for the plugin (future GUI implementation)
add_action( 'admin_menu', 'all_the_hooks_add_admin_menu' );

/**
 * Add a menu page for the plugin.
 */
function all_the_hooks_add_admin_menu() {
    add_menu_page(
        'All The Hooks',
        'All The Hooks',
        'manage_options',
		'all-the-hooks',
		'all_the_hooks_admin_page',
        'dashicons-editor-code',
        100
    );
}

/**
 * Display the admin page content.
 */
function all_the_hooks_admin_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'All The Hooks', 'all-the-hooks' ); ?></h1>
		<p><?php esc_html_e( 'This plugin is primarily designed to be used with WP-CLI.', 'all-the-hooks' ); ?></p>
		<h2><?php esc_html_e( 'Usage', 'all-the-hooks' ); ?></h2>
		<pre>wp all-the-hooks scan --plugin=&lt;plugin-slug&gt; [--format=&lt;json|markdown|html&gt;] [--include_docblocks=&lt;true|false&gt;] [--output_path=&lt;path&gt;] [--hook_type=&lt;all|action|filter&gt;]</pre>
		<h3><?php esc_html_e( 'Example', 'all-the-hooks' ); ?></h3>
		<pre>wp all-the-hooks scan --plugin=easy-digital-downloads-pro --format=html --include_docblocks=true --output_path=./</pre>
		<p><?php esc_html_e( 'For complete documentation, please refer to the README.md file.', 'all-the-hooks' ); ?></p>
	</div>
	<?php
}

// WP-CLI commands are handled by the class in includes/cli/class-all-the-hooks-command.php