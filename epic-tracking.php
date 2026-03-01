<?php
/**
 * Plugin Name:       Epic Tracking
 * Plugin URI:        https://github.com/epicwp/epic-tracking
 * Description:       Easy event tracking for WordPress. Point, click, and track — no code, no tag managers, no third-party scripts.
 * Version:           1.3.4
 * Author:            Epic WP Solutions
 * Author URI:        https://epicwpsolutions.com
 * Text Domain:       epic-tracking
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- EPT is the plugin prefix
define('EPT_VERSION', '1.3.4');
define('EPT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EPT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EPT_PLUGIN_FILE', __FILE__);
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound

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
