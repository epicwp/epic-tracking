<?php
/**
 * Plugin Name: Epic Tracking
 * Description: Lightweight visit and event tracking for WordPress
 * Version: 1.0.0
 * Author: Epic WP Solutions
 * Text Domain: epic-tracking
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EPT_VERSION', '1.0.0');
define('EPT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EPT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EPT_PLUGIN_FILE', __FILE__);

require_once EPT_PLUGIN_DIR . 'vendor/autoload.php';

use EpicTracking\Database;
use EpicTracking\Tracker;
use EpicTracking\VisualMode;
use EpicTracking\Admin;

// Activation hook
register_activation_hook(__FILE__, [Database::class, 'activate']);

// Initialize plugin
add_action('plugins_loaded', function () {
    Database::init();
    Tracker::init();
    VisualMode::init();
    Admin::init();
});
