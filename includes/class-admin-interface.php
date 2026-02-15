<?php
/**
 * Admin Interface class.
 *
 * @package AllTheHooks
 */

namespace AllTheHooks;

/**
 * Class Admin_Interface
 *
 * Handles the WordPress admin interface for the plugin using React.
 */
class Admin_Interface {

	/**
	 * Initialize the admin interface
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_ath_scan_source', array( $this, 'ajax_scan_source' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		register_rest_route(
			'all-the-hooks/v1',
			'/plugins',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_plugins' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'all-the-hooks/v1',
			'/themes',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_themes' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get available plugins
	 *
	 * @return \WP_REST_Response
	 */
	public function get_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins      = get_plugins();
		$plugin_list = array();

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			$plugin_slug = dirname( $plugin_file );
			if ( '.' === $plugin_slug ) {
				$plugin_slug = basename( $plugin_file, '.php' );
			}

			$plugin_list[] = array(
				'name'   => $plugin_data['Name'],
				'plugin' => $plugin_file,
				'slug'   => $plugin_slug,
			);
		}

		return rest_ensure_response( $plugin_list );
	}

	/**
	 * Get available themes
	 *
	 * @return \WP_REST_Response
	 */
	public function get_themes() {
		$themes     = wp_get_themes();
		$theme_list = array();

		foreach ( $themes as $theme_slug => $theme_data ) {
			$theme_list[] = array(
				'slug' => $theme_slug,
				'name' => $theme_data->get( 'Name' ),
			);
		}

		return rest_ensure_response( array( 'themes' => $theme_list ) );
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our plugin page
		if ( 'toplevel_page_all-the-hooks' !== $hook ) {
			return;
		}

		$asset_file = ALL_THE_HOOKS_PATH . 'build/admin/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_enqueue_script(
			'ath-admin',
			ALL_THE_HOOKS_URL . 'build/admin/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// Enqueue the main style
		if ( file_exists( ALL_THE_HOOKS_PATH . 'build/style-admin.css' ) ) {
			wp_enqueue_style(
				'ath-admin',
				ALL_THE_HOOKS_URL . 'build/style-admin.css',
				array( 'wp-components' ),
				$asset['version']
			);
		}

		wp_localize_script(
			'ath-admin',
			'athAdminData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ath_scan_nonce' ),
				'restUrl' => rest_url( 'all-the-hooks/v1' ),
			)
		);
	}

	/**
	 * Render the admin page
	 */
	public function render_admin_page() {
		?>
		<div class="wrap" style="max-width: 100%; width: 100%; margin: 20px 0;">
			<div id="ath-admin-root"></div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for scanning a source (plugin or theme)
	 */
	public function ajax_scan_source() {
		check_ajax_referer( 'ath_scan_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'all-the-hooks' ) ) );
		}

		$source_type       = sanitize_text_field( $_POST['source_type'] ?? 'plugin' );
		$source_slug       = sanitize_text_field( $_POST['source_slug'] ?? '' );
		$hook_type         = sanitize_text_field( $_POST['hook_type'] ?? 'all' );
		$include_docblocks = filter_var( $_POST['include_docblocks'] ?? false, FILTER_VALIDATE_BOOLEAN );
		$format            = sanitize_text_field( $_POST['format'] ?? 'json' );

		if ( empty( $source_slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Source slug is required.', 'all-the-hooks' ) ) );
		}

		// Create scanner
		$scanner = new HookScanner( $source_slug, $hook_type, $include_docblocks, $source_type );
		$hooks   = $scanner->scan();

		// Check for errors
		if ( is_wp_error( $hooks ) ) {
			wp_send_json_error( array( 'message' => $hooks->get_error_message() ) );
		}

		// Check if any hooks were found
		if ( empty( $hooks ) ) {
			wp_send_json_error( array( 'message' => __( 'No hooks found.', 'all-the-hooks' ) ) );
		}

		// Format output
		$output = '';
		$ext    = '';

		if ( 'json' === $format ) {
			$output = OutputFormatter::to_json( $hooks, $source_slug );
			$ext    = 'json';
		} elseif ( 'markdown' === $format ) {
			$output = OutputFormatter::to_markdown( $hooks, $source_slug );
			$ext    = 'md';
		} else {
			$output = OutputFormatter::to_html( $hooks, $source_slug );
			$ext    = 'html';
		}

		// Save to default location
		$hooks_dir = WP_PLUGIN_DIR . '/all-the-hooks/.hooks';

		if ( ! is_dir( $hooks_dir ) ) {
			wp_mkdir_p( $hooks_dir );
		}

		$output_file = $hooks_dir . '/' . $source_slug . '-hooks.' . $ext;
		$result      = OutputFormatter::save_to_file( $output, $output_file );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Calculate summary
		$action_count = count(
			array_filter(
				$hooks,
				function ( $hook ) {
					return 'action' === $hook['type'];
				}
			)
		);

		$filter_count = count(
			array_filter(
				$hooks,
				function ( $hook ) {
					return 'filter' === $hook['type'];
				}
			)
		);

		// Count hooks with listeners
		$hooks_with_listeners = count(
			array_filter(
				$hooks,
				function ( $hook ) {
					return ! empty( $hook['listeners'] );
				}
			)
		);

		wp_send_json_success(
			array(
				'message'              => sprintf(
					/* translators: %d: number of hooks */
					__( 'Found %d hooks successfully!', 'all-the-hooks' ),
					count( $hooks )
				),
				'total'                => count( $hooks ),
				'actions'              => $action_count,
				'filters'              => $filter_count,
				'hooks_with_listeners' => $hooks_with_listeners,
				'output_file'          => $output_file,
				'download_url'         => content_url( 'plugins/all-the-hooks/.hooks/' . $source_slug . '-hooks.' . $ext ),
				'hooks'                => $hooks,
				'format'               => $format,
			)
		);
	}
}
