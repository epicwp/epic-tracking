<?php

namespace EpicTracking;

class Admin
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'addMenuPages']);
        add_action('admin_init', [self::class, 'registerSettings']);
    }

    public static function addMenuPages(): void
    {
        add_menu_page(
            'Epic Tracking',
            'Epic Tracking',
            'manage_options',
            'epic-tracking',
            [self::class, 'renderDashboard'],
            'dashicons-chart-bar',
            30
        );

        add_submenu_page(
            'epic-tracking',
            'Settings',
            'Settings',
            'manage_options',
            'epic-tracking-settings',
            [self::class, 'renderSettings']
        );
    }

    public static function registerSettings(): void
    {
        register_setting('ept_settings_group', 'ept_settings', [
            'type'              => 'array',
            'sanitize_callback' => [self::class, 'sanitizeSettings'],
            'default'           => ['excluded_roles' => ['administrator']],
        ]);
    }

    public static function sanitizeSettings($input): array
    {
        $output = [];
        $output['excluded_roles'] = array_map('sanitize_text_field', $input['excluded_roles'] ?? []);
        return $output;
    }

    public static function renderDashboard(): void
    {
        wp_enqueue_style('ept-admin', EPT_PLUGIN_URL . 'assets/css/admin.css', [], EPT_VERSION);

        $range = sanitize_text_field($_GET['range'] ?? '7');
        $dateTo = current_time('mysql');
        switch ($range) {
            case '1':
                $dateFrom = gmdate('Y-m-d 00:00:00', strtotime('-1 day', current_time('timestamp')));
                break;
            case '30':
                $dateFrom = gmdate('Y-m-d 00:00:00', strtotime('-30 days', current_time('timestamp')));
                break;
            case '7':
            default:
                $dateFrom = gmdate('Y-m-d 00:00:00', strtotime('-7 days', current_time('timestamp')));
                $range = '7';
                break;
        }

        $visitStats = Database::getVisitStats($dateFrom, $dateTo);
        $eventStats = Database::getEventStats($dateFrom, $dateTo);

        include EPT_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }

    public static function renderSettings(): void
    {
        wp_enqueue_style('ept-admin', EPT_PLUGIN_URL . 'assets/css/admin.css', [], EPT_VERSION);

        $settings = get_option('ept_settings', ['excluded_roles' => ['administrator']]);
        $allRoles = wp_roles()->role_names;

        include EPT_PLUGIN_DIR . 'templates/admin-settings.php';
    }
}
