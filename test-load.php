<?php
define('ABSPATH', __DIR__ . '/');
define('WPINC', 'wp-includes');

function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/'; }
function register_activation_hook($file, $function) {}
function register_deactivation_hook($file, $function) {}
function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {}
function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {}
function is_plugin_active($plugin) { return false; }
function __($text, $domain) { return $text; }
function _e($text, $domain) { echo $text; }

// Test loading Live News
try {
    require 'live-news/live-news.php';
    echo "Live News loaded successfully.\n";
} catch (Exception $e) {
    echo "Error loading Live News: " . $e->getMessage() . "\n";
}

// Test loading Live News Pro
try {
    require 'live-news-pro/live-news-pro.php';
    echo "Live News Pro loaded successfully.\n";
} catch (Exception $e) {
    echo "Error loading Live News Pro: " . $e->getMessage() . "\n";
}
