<?php
/**
 * Plugin Name: All The Hooks
 * Description: WordPress plugin to discover and document hooks in plugins and themes
 * Version: 1.1.0
 * Author: Lax Mariappan
 * License: GPL-2.0-or-later
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'ALL_THE_HOOKS_VERSION', '1.1.0' );
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

// Initialize Admin Interface (always, not just in admin, so REST API routes get registered)
$admin_interface = new AllTheHooks\Admin_Interface();

// Admin menu for the plugin
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
	$admin_interface = new AllTheHooks\Admin_Interface();
	$admin_interface->render_admin_page();
}

// WP-CLI commands are handled by the class in includes/cli/class-all-the-hooks-command.php