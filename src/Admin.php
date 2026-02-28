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
            'Tracking',
            'Tracking',
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

    const PER_PAGE = 20;

    public static function renderDashboard(): void
    {
        wp_enqueue_style('ept-admin', EPT_PLUGIN_URL . 'assets/css/admin.css', [], EPT_VERSION);

        // Date range — default to last 7 days
        $today    = gmdate('Y-m-d');
        $dateFrom = sanitize_text_field($_GET['date_from'] ?? gmdate('Y-m-d', strtotime('-6 days')));
        $dateTo   = sanitize_text_field($_GET['date_to'] ?? $today);

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = gmdate('Y-m-d', strtotime('-6 days'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = $today;
        }

        // SQL boundaries: from start of dateFrom to start of day after dateTo
        $sqlFrom = $dateFrom . ' 00:00:00';
        $sqlTo   = gmdate('Y-m-d', strtotime($dateTo . ' +1 day')) . ' 00:00:00';

        // Pagination
        $visitPage = max(1, (int) ($_GET['vpage'] ?? 1));
        $eventPage = max(1, (int) ($_GET['epage'] ?? 1));

        // URL filter for events
        $filterUrl = isset($_GET['filter_url']) ? sanitize_text_field($_GET['filter_url']) : '';

        // Summary totals
        $visitSummary = Database::getVisitSummary($sqlFrom, $sqlTo);
        $eventSummary = Database::getEventSummary($sqlFrom, $sqlTo);

        // Paginated table data
        $visitStats      = Database::getVisitStats($sqlFrom, $sqlTo, self::PER_PAGE, $visitPage);
        $visitTotalPages = (int) ceil(Database::getVisitStatsCount($sqlFrom, $sqlTo) / self::PER_PAGE);

        $eventStats      = Database::getEventStats($sqlFrom, $sqlTo, self::PER_PAGE, $eventPage, $filterUrl);
        $eventTotalPages = (int) ceil(Database::getEventStatsCount($filterUrl) / self::PER_PAGE);

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
