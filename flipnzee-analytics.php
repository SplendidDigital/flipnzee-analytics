<?php
/*
Plugin Name: Flipnzee Analytics
Description: GA Verified Traffic + Insights
Version: 2.0
Author: Flipnzee
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load required files
require_once plugin_dir_path(__FILE__) . 'includes/ga-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';

// Optional: define constants (can be used later for API credentials)
define('FLIPNZEE_CLIENT_ID', '');
define('FLIPNZEE_CLIENT_SECRET', '');
define('FLIPNZEE_REDIRECT_URI', '');
