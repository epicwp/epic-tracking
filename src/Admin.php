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
            __('Tracking', 'epic-tracking'),
            __('Tracking', 'epic-tracking'),
            'manage_options',
            'epic-tracking',
            [self::class, 'renderDashboard'],
            'dashicons-chart-bar',
            30
        );

        add_submenu_page(
            'epic-tracking',
            __('Settings', 'epic-tracking'),
            __('Settings', 'epic-tracking'),
            'manage_options',
            'epic-tracking-settings',
            [self::class, 'renderSettings']
        );

        // Page detail — registered under parent for proper WP globals, hidden via CSS
        add_submenu_page(
            'epic-tracking',
            __('Page Detail', 'epic-tracking'),
            '',
            'manage_options',
            'epic-tracking-page-detail',
            [self::class, 'renderPageDetail']
        );

        // All visits — full-page list view, hidden from menu
        add_submenu_page(
            'epic-tracking',
            __('All Page Visits', 'epic-tracking'),
            '',
            'manage_options',
            'epic-tracking-all-visits',
            [self::class, 'renderAllVisits']
        );

        // All events — full-page list view, hidden from menu
        add_submenu_page(
            'epic-tracking',
            __('All Events', 'epic-tracking'),
            '',
            'manage_options',
            'epic-tracking-all-events',
            [self::class, 'renderAllEvents']
        );

        // Remove hidden pages from the sidebar menu
        remove_submenu_page('epic-tracking', 'epic-tracking-page-detail');
        remove_submenu_page('epic-tracking', 'epic-tracking-all-visits');
        remove_submenu_page('epic-tracking', 'epic-tracking-all-events');
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

    const PER_PAGE = 10;
    const PER_PAGE_FULL = 25;

    public static function renderDashboard(): void
    {
        wp_enqueue_style('ept-admin', EPT_PLUGIN_URL . 'assets/css/admin.css', [], EPT_VERSION);
        wp_enqueue_script('ept-admin', EPT_PLUGIN_URL . 'assets/js/admin.js', [], EPT_VERSION, true);

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

        // Sorting — visits
        $visitSort  = sanitize_text_field($_GET['vsort'] ?? 'total_visits');
        $visitOrder = sanitize_text_field($_GET['vorder'] ?? 'DESC');

        // Sorting — events
        $eventSort  = sanitize_text_field($_GET['esort'] ?? 'total_triggers');
        $eventOrder = sanitize_text_field($_GET['eorder'] ?? 'DESC');

        // URL filter for events
        $filterUrl = isset($_GET['filter_url']) ? sanitize_text_field($_GET['filter_url']) : '';

        // Summary totals
        $visitSummary = Database::getVisitSummary($sqlFrom, $sqlTo);
        $eventSummary = Database::getEventSummary($sqlFrom, $sqlTo);

        // Daily breakdown
        $dailyVisits = Database::getDailyVisits($sqlFrom, $sqlTo);

        // Top countries
        $topCountries = Database::getTopCountries($sqlFrom, $sqlTo);

        // Paginated table data
        $visitStats      = Database::getVisitStats($sqlFrom, $sqlTo, self::PER_PAGE, $visitPage, $visitSort, $visitOrder);
        $visitTotalPages = (int) ceil(Database::getVisitStatsCount($sqlFrom, $sqlTo) / self::PER_PAGE);

        $eventStats      = Database::getEventStats($sqlFrom, $sqlTo, self::PER_PAGE, $eventPage, $filterUrl, $eventSort, $eventOrder);
        $eventTotalPages = (int) ceil(Database::getEventStatsCount($filterUrl) / self::PER_PAGE);

        include EPT_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }

    public static function renderPageDetail(): void
    {
        wp_enqueue_style('ept-admin', EPT_PLUGIN_URL . 'assets/css/admin.css', [], EPT_VERSION);
        wp_enqueue_script('ept-admin', EPT_PLUGIN_URL . 'assets/js/admin.js', [], EPT_VERSION, true);

        $pageUrl = sanitize_text_field($_GET['page_url'] ?? '');
        if ($pageUrl === '') {
            wp_die(__('Missing page URL.', 'epic-tracking'));
        }

        // Date range — same logic as dashboard
        $today    = gmdate('Y-m-d');
        $dateFrom = sanitize_text_field($_GET['date_from'] ?? gmdate('Y-m-d', strtotime('-6 days')));
        $dateTo   = sanitize_text_field($_GET['date_to'] ?? $today);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = gmdate('Y-m-d', strtotime('-6 days'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = $today;
        }

        $sqlFrom = $dateFrom . ' 00:00:00';
        $sqlTo   = gmdate('Y-m-d', strtotime($dateTo . ' +1 day')) . ' 00:00:00';

        // All page-specific queries
        $summary    = Database::getPageVisitSummary($pageUrl, $sqlFrom, $sqlTo);
        $dailyVisits = Database::getPageDailyVisits($pageUrl, $sqlFrom, $sqlTo);
        $referrers  = Database::getPageReferrers($pageUrl, $sqlFrom, $sqlTo);
        $devices    = Database::getPageDeviceBreakdown($pageUrl, $sqlFrom, $sqlTo);
        $browsers   = Database::getPageBrowserBreakdown($pageUrl, $sqlFrom, $sqlTo);
        $osList     = Database::getPageOsBreakdown($pageUrl, $sqlFrom, $sqlTo);
        $countries  = Database::getPageCountryBreakdown($pageUrl, $sqlFrom, $sqlTo);
        $events     = Database::getPageEvents($pageUrl, $sqlFrom, $sqlTo);

        include EPT_PLUGIN_DIR . 'templates/admin-page-detail.php';
    }

    public static function renderAllVisits(): void
    {
        wp_enqueue_style('ept-admin', EPT_PLUGIN_URL . 'assets/css/admin.css', [], EPT_VERSION);
        wp_enqueue_script('ept-admin', EPT_PLUGIN_URL . 'assets/js/admin.js', [], EPT_VERSION, true);

        $today    = gmdate('Y-m-d');
        $dateFrom = sanitize_text_field($_GET['date_from'] ?? gmdate('Y-m-d', strtotime('-6 days')));
        $dateTo   = sanitize_text_field($_GET['date_to'] ?? $today);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = gmdate('Y-m-d', strtotime('-6 days'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = $today;
        }

        $sqlFrom = $dateFrom . ' 00:00:00';
        $sqlTo   = gmdate('Y-m-d', strtotime($dateTo . ' +1 day')) . ' 00:00:00';

        $currentPage = max(1, (int) ($_GET['paged'] ?? 1));
        $sortBy      = sanitize_text_field($_GET['vsort'] ?? 'total_visits');
        $sortOrder   = sanitize_text_field($_GET['vorder'] ?? 'DESC');

        $visitStats      = Database::getVisitStats($sqlFrom, $sqlTo, self::PER_PAGE_FULL, $currentPage, $sortBy, $sortOrder);
        $totalPages      = (int) ceil(Database::getVisitStatsCount($sqlFrom, $sqlTo) / self::PER_PAGE_FULL);

        include EPT_PLUGIN_DIR . 'templates/admin-all-visits.php';
    }

    public static function renderAllEvents(): void
    {
        wp_enqueue_style('ept-admin', EPT_PLUGIN_URL . 'assets/css/admin.css', [], EPT_VERSION);
        wp_enqueue_script('ept-admin', EPT_PLUGIN_URL . 'assets/js/admin.js', [], EPT_VERSION, true);

        $today    = gmdate('Y-m-d');
        $dateFrom = sanitize_text_field($_GET['date_from'] ?? gmdate('Y-m-d', strtotime('-6 days')));
        $dateTo   = sanitize_text_field($_GET['date_to'] ?? $today);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = gmdate('Y-m-d', strtotime('-6 days'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = $today;
        }

        $sqlFrom = $dateFrom . ' 00:00:00';
        $sqlTo   = gmdate('Y-m-d', strtotime($dateTo . ' +1 day')) . ' 00:00:00';

        $currentPage = max(1, (int) ($_GET['paged'] ?? 1));
        $sortBy      = sanitize_text_field($_GET['esort'] ?? 'total_triggers');
        $sortOrder   = sanitize_text_field($_GET['eorder'] ?? 'DESC');

        $eventStats      = Database::getEventStats($sqlFrom, $sqlTo, self::PER_PAGE_FULL, $currentPage, '', $sortBy, $sortOrder);
        $totalPages      = (int) ceil(Database::getEventStatsCount() / self::PER_PAGE_FULL);

        include EPT_PLUGIN_DIR . 'templates/admin-all-events.php';
    }

    public static function renderSettings(): void
    {
        wp_enqueue_style('ept-admin', EPT_PLUGIN_URL . 'assets/css/admin.css', [], EPT_VERSION);

        $settings = get_option('ept_settings', ['excluded_roles' => ['administrator']]);
        $allRoles = wp_roles()->role_names;

        include EPT_PLUGIN_DIR . 'templates/admin-settings.php';
    }
}
