<?php

if (defined('WP_CLI') && WP_CLI) {
    /**
     * List all action and filter hooks.
     *
     * ## EXAMPLES
     *
     *     wp allthehooks list
     *
     */
    WP_CLI::add_command('allthehooks', 'AllTheHooks_CLI_Command');

    class AllTheHooks_CLI_Command {
        /**
         * List all hooks.
         *
         * @when after_wp_load
         */
        public function list() {
            $hooks = allthehooks_scan_hooks();

            WP_CLI::log('Action Hooks:');
            foreach ($hooks['actions'] as $action) {
                WP_CLI::log($action);
            }

            WP_CLI::log('Filter Hooks:');
            foreach ($hooks['filters'] as $filter) {
                WP_CLI::log($filter['hook'] . ' - ' . $filter['file'] . ' (Line ' . $filter['line'] . ')');
            }
        }

        private function scan_plugin( $plugin, $format, $include_docblocks, $output_path ) {
            $hooks = array();
            $plugin_dir = WP_PLUGIN_DIR . '/' . $plugin;
            
            if ( ! is_dir( $plugin_dir ) ) {
                WP_CLI::error( "Plugin directory not found: $plugin_dir" );
            }
            
            $files = $this->get_php_files( $plugin_dir );
            
            foreach ( $files as $file ) {
                $hooks = array_merge( $hooks, $this->get_hooks_from_file( $file, $include_docblocks, true ) ); // Added parameter for context
            }
            
            // ... existing code ...
        }

        private function get_hooks_from_file( $file, $include_docblocks = false, $include_context = false ) {
            // ... existing code ...
            
            $hooks = array();
            $file_content = file_get_contents( $file );
            $file_lines = file( $file );
            
            // ... existing code ...
            
            foreach ( $matches[0] as $index => $match ) {
                $hook = array(
                    'name'      => $matches[2][$index],
                    'type'      => $matches[1][$index],
                    'file'      => str_replace( WP_PLUGIN_DIR . '/', '', $file ),
                    'line'      => $this->get_line_number( $file_content, $match ),
                );
                
                if ( $include_docblocks ) {
                    $hook['docblock'] = $this->get_docblock_for_hook( $file_content, $match );
                }
                
                if ( $include_context ) {
                    $line_number = $hook['line'];
                    $start_line = max(1, $line_number - 2); // 2 lines before
                    $end_line = min(count($file_lines), $line_number + 2); // 2 lines after
                    
                    $context_lines = array_slice($file_lines, $start_line - 1, $end_line - $start_line + 1);
                    $hook['context'] = $context_lines;
                    $hook['context_start'] = $start_line;
                }
                
                $hooks[] = $hook;
            }
            
            return $hooks;
        }
    }
}

?> 