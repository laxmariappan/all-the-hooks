<?php
/**
 * Plugin Name: Test Plugin for All The Hooks
 * Description: A simple plugin with hooks for testing All The Hooks scanner
 * Version: 1.0.0
 * Author: Test Author
 * License: GPL-2.0-or-later
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action hook that runs when the plugin is activated.
 *
 * @param bool $network_wide Whether the plugin is being activated network-wide.
 */
function test_plugin_activated( $network_wide ) {
	do_action( 'test_plugin_activated', $network_wide );
}
register_activation_hook( __FILE__, 'test_plugin_activated' );

/**
 * Action hook that runs when the plugin is deactivated.
 *
 * @param bool $network_wide Whether the plugin is being deactivated network-wide.
 */
function test_plugin_deactivated( $network_wide ) {
	do_action( 'test_plugin_deactivated', $network_wide );
}
register_deactivation_hook( __FILE__, 'test_plugin_deactivated' );

/**
 * Add custom content to the head section.
 */
function test_plugin_wp_head() {
	echo '<!-- Added by Test Plugin -->';
}
add_action( 'wp_head', 'test_plugin_wp_head' );

/**
 * Filter the content to add something at the beginning.
 *
 * @param string $content The post content.
 * @return string Modified content.
 */
function test_plugin_filter_content( $content ) {
	// Only modify single post content
	if ( is_single() && in_the_loop() && is_main_query() ) {
		$prefix = '<p><em>This content was filtered by Test Plugin.</em></p>';
		return $prefix . $content;
	}
	
	return $content;
}
add_filter( 'the_content', 'test_plugin_filter_content' );

/**
 * Register a custom post type.
 */
function test_plugin_register_post_type() {
	$args = array(
		'public'      => true,
		'label'       => 'Test Items',
		'supports'    => array( 'title', 'editor', 'thumbnail' ),
		'has_archive' => true,
	);
	
	/**
	 * Filter the arguments for registering the test post type.
	 *
	 * @param array $args Arguments for register_post_type function.
	 * @return array Modified arguments.
	 */
	$args = apply_filters( 'test_plugin_post_type_args', $args );
	
	register_post_type( 'test_item', $args );
}
add_action( 'init', 'test_plugin_register_post_type' );

/**
 * Add a custom admin menu page.
 */
function test_plugin_admin_menu() {
	/**
	 * Action that fires before the admin menu is added.
	 */
	do_action( 'test_plugin_before_admin_menu' );
	
	add_menu_page(
		'Test Plugin Settings',
		'Test Plugin',
		'manage_options',
		'test-plugin-settings',
		'test_plugin_settings_page',
		'dashicons-admin-generic',
		100
	);
	
	/**
	 * Action that fires after the admin menu is added.
	 */
	do_action( 'test_plugin_after_admin_menu' );
}
add_action( 'admin_menu', 'test_plugin_admin_menu' );

/**
 * Render settings page content.
 */
function test_plugin_settings_page() {
	echo '<div class="wrap">';
	echo '<h1>Test Plugin Settings</h1>';
	echo '<p>This is a test plugin for the All The Hooks plugin.</p>';
	echo '</div>';
}

/**
 * Enqueue scripts and styles.
 */
function test_plugin_enqueue_scripts() {
	// This is just a stub - we're not actually loading any scripts
	
	/**
	 * Action that fires after scripts are enqueued.
	 */
	do_action( 'test_plugin_after_enqueue_scripts' );
}
add_action( 'wp_enqueue_scripts', 'test_plugin_enqueue_scripts' ); 