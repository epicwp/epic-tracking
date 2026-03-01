<?php
/**
 * Epic Tracking uninstall handler.
 *
 * Fired when the plugin is deleted via the WordPress admin.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop tables in dependency order (foreign-key-safe).
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ept_event_log");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ept_events");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ept_visits");

// Remove plugin options.
delete_option('ept_settings');
delete_option('ept_db_version');
