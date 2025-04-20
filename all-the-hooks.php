<?php
/**
 * Plugin Name: AllTheHooks
 * Description: Lists all available action and filter hooks in WordPress.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'allthehooks_add_admin_menu');

/**
 * Add a menu page for the plugin.
 */
function allthehooks_add_admin_menu() {
    add_menu_page(
        'All The Hooks',
        'All The Hooks',
        'manage_options',
        'allthehooks',
        'allthehooks_admin_page',
        'dashicons-editor-code',
        100
    );
}

/**
 * Enqueue admin scripts and styles.
 */
function allthehooks_enqueue_admin_scripts() {
    echo '<style>
        .allthehooks-search {
            margin-bottom: 20px;
        }
        .allthehooks-list {
            list-style-type: none;
            padding: 0;
        }
        .allthehooks-list li {
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }
    </style>';
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var searchInput = document.getElementById("allthehooks-search");
            searchInput.addEventListener("keyup", function() {
                var filter = searchInput.value.toLowerCase();
                var listItems = document.querySelectorAll(".allthehooks-list li");
                listItems.forEach(function(item) {
                    if (item.textContent.toLowerCase().includes(filter)) {
                        item.style.display = "";
                    } else {
                        item.style.display = "none";
                    }
                });
            });
        });
    </script>';
}
add_action('admin_head', 'allthehooks_enqueue_admin_scripts');

/**
 * Display the admin page content.
 */
function allthehooks_admin_page() {
    echo '<div class="wrap">';
    echo '<h1>All The Hooks</h1>';
    echo '<input type="text" id="allthehooks-search" class="allthehooks-search" placeholder="Search hooks...">';
    
    $hooks = allthehooks_scan_hooks();
    
    // Display action hooks
    echo '<h2>Action Hooks</h2>';
    if (!empty($hooks['actions'])) {
        echo '<ul class="allthehooks-list">';
        foreach ($hooks['actions'] as $action) {
            echo '<li>' . esc_html($action) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No action hooks found.</p>';
    }
    
    // Display filter hooks
    echo '<h2>Filter Hooks</h2>';
    if (!empty($hooks['filters'])) {
        echo '<ul class="allthehooks-list">';
        foreach ($hooks['filters'] as $filter) {
            echo '<li>' . esc_html($filter['hook']) . ' - ' . esc_html($filter['file']) . ' (Line ' . esc_html($filter['line']) . ')</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No filter hooks found.</p>';
    }
    
    echo '</div>';
}

/**
 * Scan WordPress files for action and filter hooks, including file names and line numbers for filters.
 *
 * @return array List of hooks found.
 */
function allthehooks_scan_hooks() {
    $hooks = [
        'actions' => [],
        'filters' => []
    ];
    $wp_files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ABSPATH));

    foreach ($wp_files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file($file->getRealPath());
            foreach ($content as $line_number => $line_content) {
                if (preg_match('/do_action\(\s*[\"\"](.*?)[\"\"]/s', $line_content, $action_matches)) {
                    $hooks['actions'][] = $action_matches[1];
                }
                if (preg_match('/apply_filters\(\s*[\"\"](.*?)[\"\"]/s', $line_content, $filter_matches)) {
                    $hooks['filters'][] = [
                        'hook' => $filter_matches[1],
                        'file' => $file->getRealPath(),
                        'line' => $line_number + 1
                    ];
                }
            }
        }
    }

    return $hooks;
}
?> 