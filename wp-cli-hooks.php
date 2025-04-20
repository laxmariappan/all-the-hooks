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
    }
}

?> 