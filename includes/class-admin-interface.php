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
 * Handles the WordPress admin interface for the plugin.
 */
class Admin_Interface {

	/**
	 * Initialize the admin interface
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_ath_scan_source', array( $this, 'ajax_scan_source' ) );
		add_action( 'wp_ajax_ath_get_plugins', array( $this, 'ajax_get_plugins' ) );
		add_action( 'wp_ajax_ath_get_themes', array( $this, 'ajax_get_themes' ) );
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

		wp_enqueue_style(
			'ath-admin',
			ALL_THE_HOOKS_URL . 'assets/css/admin.css',
			array(),
			ALL_THE_HOOKS_VERSION
		);

		wp_enqueue_script(
			'ath-admin',
			ALL_THE_HOOKS_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-api-fetch' ),
			ALL_THE_HOOKS_VERSION,
			true
		);

		wp_localize_script(
			'ath-admin',
			'athAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ath_scan_nonce' ),
			)
		);
	}

	/**
	 * Render the admin page
	 */
	public function render_admin_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="ath-admin-container">
				<!-- Scan Form -->
				<div class="ath-scan-form card">
					<h2><?php esc_html_e( 'Scan for Hooks', 'all-the-hooks' ); ?></h2>

					<form id="ath-scan-form" class="ath-form">
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="ath-source-type">
											<?php esc_html_e( 'Source Type', 'all-the-hooks' ); ?>
										</label>
									</th>
									<td>
										<fieldset>
											<label>
												<input type="radio" name="source_type" value="plugin" checked>
												<?php esc_html_e( 'Plugin', 'all-the-hooks' ); ?>
											</label>
											<br>
											<label>
												<input type="radio" name="source_type" value="theme">
												<?php esc_html_e( 'Theme', 'all-the-hooks' ); ?>
											</label>
										</fieldset>
									</td>
								</tr>

								<tr id="ath-plugin-row">
									<th scope="row">
										<label for="ath-plugin-select">
											<?php esc_html_e( 'Select Plugin', 'all-the-hooks' ); ?>
										</label>
									</th>
									<td>
										<select id="ath-plugin-select" name="plugin" class="regular-text">
											<option value=""><?php esc_html_e( '-- Select a Plugin --', 'all-the-hooks' ); ?></option>
											<?php
											$plugins = get_plugins();
											foreach ( $plugins as $plugin_file => $plugin_data ) {
												$plugin_slug = dirname( $plugin_file );
												if ( '.' === $plugin_slug ) {
													$plugin_slug = basename( $plugin_file, '.php' );
												}
												printf(
													'<option value="%s">%s</option>',
													esc_attr( $plugin_slug ),
													esc_html( $plugin_data['Name'] )
												);
											}
											?>
										</select>
										<p class="description">
											<?php esc_html_e( 'Select the plugin you want to scan for hooks.', 'all-the-hooks' ); ?>
										</p>
									</td>
								</tr>

								<tr id="ath-theme-row" style="display: none;">
									<th scope="row">
										<label for="ath-theme-select">
											<?php esc_html_e( 'Select Theme', 'all-the-hooks' ); ?>
										</label>
									</th>
									<td>
										<select id="ath-theme-select" name="theme" class="regular-text">
											<option value=""><?php esc_html_e( '-- Select a Theme --', 'all-the-hooks' ); ?></option>
											<?php
											$themes = wp_get_themes();
											foreach ( $themes as $theme_slug => $theme_data ) {
												printf(
													'<option value="%s">%s</option>',
													esc_attr( $theme_slug ),
													esc_html( $theme_data->get( 'Name' ) )
												);
											}
											?>
										</select>
										<p class="description">
											<?php esc_html_e( 'Select the theme you want to scan for hooks.', 'all-the-hooks' ); ?>
										</p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="ath-hook-type">
											<?php esc_html_e( 'Hook Type', 'all-the-hooks' ); ?>
										</label>
									</th>
									<td>
										<select id="ath-hook-type" name="hook_type" class="regular-text">
											<option value="all"><?php esc_html_e( 'All (Actions & Filters)', 'all-the-hooks' ); ?></option>
											<option value="action"><?php esc_html_e( 'Actions Only', 'all-the-hooks' ); ?></option>
											<option value="filter"><?php esc_html_e( 'Filters Only', 'all-the-hooks' ); ?></option>
										</select>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<?php esc_html_e( 'Options', 'all-the-hooks' ); ?>
									</th>
									<td>
										<fieldset>
											<label for="ath-include-docblocks">
												<input type="checkbox" id="ath-include-docblocks" name="include_docblocks" value="1">
												<?php esc_html_e( 'Include DocBlocks', 'all-the-hooks' ); ?>
											</label>
											<p class="description">
												<?php esc_html_e( 'Extract and include PHPDoc comments for each hook.', 'all-the-hooks' ); ?>
											</p>
										</fieldset>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="ath-output-format">
											<?php esc_html_e( 'Output Format', 'all-the-hooks' ); ?>
										</label>
									</th>
									<td>
										<select id="ath-output-format" name="format" class="regular-text">
											<option value="json"><?php esc_html_e( 'JSON', 'all-the-hooks' ); ?></option>
											<option value="markdown"><?php esc_html_e( 'Markdown', 'all-the-hooks' ); ?></option>
											<option value="html"><?php esc_html_e( 'HTML', 'all-the-hooks' ); ?></option>
										</select>
									</td>
								</tr>
							</tbody>
						</table>

						<?php submit_button( __( 'Scan for Hooks', 'all-the-hooks' ), 'primary large', 'ath-scan-submit', false ); ?>
					</form>

					<!-- Progress Indicator -->
					<div id="ath-progress" class="ath-progress" style="display: none;">
						<div class="ath-progress-bar">
							<div class="ath-progress-indicator"></div>
						</div>
						<p class="ath-progress-text"></p>
					</div>
				</div>

				<!-- Results Section -->
				<div id="ath-results" class="ath-results" style="display: none;">
					<div class="card">
						<h2><?php esc_html_e( 'Scan Results', 'all-the-hooks' ); ?></h2>

						<div id="ath-results-summary" class="ath-results-summary"></div>

						<div id="ath-results-actions" class="ath-results-actions">
							<button type="button" class="button" id="ath-download-results">
								<span class="dashicons dashicons-download"></span>
								<?php esc_html_e( 'Download Results', 'all-the-hooks' ); ?>
							</button>
							<button type="button" class="button" id="ath-view-details">
								<span class="dashicons dashicons-visibility"></span>
								<?php esc_html_e( 'View Details', 'all-the-hooks' ); ?>
							</button>
						</div>

						<!-- Results Table -->
						<div id="ath-results-table-container" style="display: none;">
							<div class="ath-results-filters">
								<input type="search" id="ath-search-hooks" class="regular-text" placeholder="<?php esc_attr_e( 'Search hooks...', 'all-the-hooks' ); ?>">
								<select id="ath-filter-type" class="regular-text">
									<option value=""><?php esc_html_e( 'All Types', 'all-the-hooks' ); ?></option>
									<option value="action"><?php esc_html_e( 'Actions', 'all-the-hooks' ); ?></option>
									<option value="filter"><?php esc_html_e( 'Filters', 'all-the-hooks' ); ?></option>
								</select>
								<select id="ath-filter-source" class="regular-text">
									<option value=""><?php esc_html_e( 'All Sources', 'all-the-hooks' ); ?></option>
									<option value="yes"><?php esc_html_e( 'Core Hooks', 'all-the-hooks' ); ?></option>
									<option value="no"><?php esc_html_e( 'Plugin/Theme Hooks', 'all-the-hooks' ); ?></option>
								</select>
							</div>

							<table class="wp-list-table widefat fixed striped" id="ath-hooks-table">
								<thead>
									<tr>
										<th class="column-name column-primary"><?php esc_html_e( 'Hook Name', 'all-the-hooks' ); ?></th>
										<th class="column-type"><?php esc_html_e( 'Type', 'all-the-hooks' ); ?></th>
										<th class="column-source"><?php esc_html_e( 'Source', 'all-the-hooks' ); ?></th>
										<th class="column-file"><?php esc_html_e( 'File', 'all-the-hooks' ); ?></th>
										<th class="column-listeners"><?php esc_html_e( 'Listeners', 'all-the-hooks' ); ?></th>
									</tr>
								</thead>
								<tbody id="ath-hooks-tbody">
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
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

	/**
	 * AJAX handler for getting available plugins
	 */
	public function ajax_get_plugins() {
		check_ajax_referer( 'ath_scan_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'all-the-hooks' ) ) );
		}

		$plugins      = get_plugins();
		$plugin_list = array();

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			$plugin_slug = dirname( $plugin_file );
			if ( '.' === $plugin_slug ) {
				$plugin_slug = basename( $plugin_file, '.php' );
			}

			$plugin_list[] = array(
				'slug' => $plugin_slug,
				'name' => $plugin_data['Name'],
			);
		}

		wp_send_json_success( array( 'plugins' => $plugin_list ) );
	}

	/**
	 * AJAX handler for getting available themes
	 */
	public function ajax_get_themes() {
		check_ajax_referer( 'ath_scan_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'all-the-hooks' ) ) );
		}

		$themes     = wp_get_themes();
		$theme_list = array();

		foreach ( $themes as $theme_slug => $theme_data ) {
			$theme_list[] = array(
				'slug' => $theme_slug,
				'name' => $theme_data->get( 'Name' ),
			);
		}

		wp_send_json_success( array( 'themes' => $theme_list ) );
	}
}
