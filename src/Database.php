<?php

namespace EpicTracking;

class Database
{
    const DB_VERSION = '1.2.0';
    const DB_VERSION_OPTION = 'ept_db_version';

    public static function init(): void
    {
        add_action('admin_init', [self::class, 'maybeUpgrade']);
    }

    public static function activate(): void
    {
        self::createTables();
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
    }

    public static function maybeUpgrade(): void
    {
        $installed = get_option(self::DB_VERSION_OPTION, '0');
        if (version_compare($installed, self::DB_VERSION, '<')) {
            self::createTables();
            update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
        }
    }

    private static function createTables(): void
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$wpdb->prefix}ept_events (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            page_url varchar(500) NOT NULL,
            selector varchar(500) NOT NULL,
            reference_name varchar(255) NOT NULL,
            event_tag varchar(100) NOT NULL,
            event_type varchar(50) NOT NULL DEFAULT 'click',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY page_url (page_url(191))
        ) $charset;

        CREATE TABLE {$wpdb->prefix}ept_visits (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            visitor_id varchar(64) NOT NULL,
            page_url varchar(500) NOT NULL,
            referrer varchar(500) DEFAULT '',
            user_agent varchar(500) DEFAULT '',
            device_type varchar(20) NOT NULL DEFAULT '',
            browser varchar(50) NOT NULL DEFAULT '',
            os varchar(50) NOT NULL DEFAULT '',
            country varchar(100) NOT NULL DEFAULT '',
            country_code varchar(2) NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY visitor_id (visitor_id),
            KEY page_url (page_url(191)),
            KEY created_at (created_at),
            KEY device_type (device_type),
            KEY browser (browser),
            KEY os (os),
            KEY country_code (country_code)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}ept_event_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            visitor_id varchar(64) NOT NULL,
            page_url varchar(500) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY visitor_id (visitor_id),
            KEY created_at (created_at)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function getEventsForPage(string $pageUrl): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ept_events WHERE page_url = %s ORDER BY id ASC",
                $pageUrl
            ),
            ARRAY_A
        );
    }

    public static function saveEvent(array $data): int
    {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}ept_events", [
            'page_url'       => $data['page_url'],
            'selector'       => $data['selector'],
            'reference_name' => $data['reference_name'],
            'event_tag'      => $data['event_tag'],
            'event_type'     => $data['event_type'] ?? 'click',
        ]);
        return (int) $wpdb->insert_id;
    }

    public static function updateEvent(int $id, array $data): bool
    {
        global $wpdb;
        $update = [];
        foreach (['reference_name', 'event_tag', 'selector'] as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }
        if (empty($update)) {
            return false;
        }
        return (bool) $wpdb->update("{$wpdb->prefix}ept_events", $update, ['id' => $id]);
    }

    public static function deleteEvent(int $id): bool
    {
        global $wpdb;
        return (bool) $wpdb->delete("{$wpdb->prefix}ept_events", ['id' => $id]);
    }

    public static function logVisit(string $visitorId, string $pageUrl, string $referrer, string $userAgent, string $deviceType = '', string $browser = '', string $os = '', string $country = '', string $countryCode = ''): void
    {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}ept_visits", [
            'visitor_id'   => $visitorId,
            'page_url'     => $pageUrl,
            'referrer'     => $referrer,
            'user_agent'   => $userAgent,
            'device_type'  => $deviceType,
            'browser'      => $browser,
            'os'           => $os,
            'country'      => $country,
            'country_code' => $countryCode,
        ]);
    }

    public static function logEvent(int $eventId, string $visitorId, string $pageUrl): void
    {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}ept_event_log", [
            'event_id'   => $eventId,
            'visitor_id' => $visitorId,
            'page_url'   => $pageUrl,
        ]);
    }

    public static function getVisitSummary(string $dateFrom, string $dateTo): array
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) as total_visits,
                        COUNT(DISTINCT visitor_id) as unique_visitors
                 FROM {$wpdb->prefix}ept_visits
                 WHERE created_at >= %s AND created_at < %s",
                $dateFrom, $dateTo
            ),
            ARRAY_A
        );
        return $row ?: ['total_visits' => 0, 'unique_visitors' => 0];
    }

    public static function getVisitStatsCount(string $dateFrom, string $dateTo): int
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT page_url)
                 FROM {$wpdb->prefix}ept_visits
                 WHERE created_at >= %s AND created_at < %s",
                $dateFrom, $dateTo
            )
        );
    }

    public static function getVisitStats(string $dateFrom, string $dateTo, int $perPage = 20, int $page = 1, string $orderBy = 'total_visits', string $order = 'DESC'): array
    {
        global $wpdb;
        $allowed = ['page_url', 'total_visits', 'unique_visitors'];
        if (!in_array($orderBy, $allowed, true)) {
            $orderBy = 'total_visits';
        }
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $offset = ($page - 1) * $perPage;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT page_url,
                        COUNT(*) as total_visits,
                        COUNT(DISTINCT visitor_id) as unique_visitors
                 FROM {$wpdb->prefix}ept_visits
                 WHERE created_at >= %s AND created_at < %s
                 GROUP BY page_url
                 ORDER BY {$orderBy} {$order}
                 LIMIT %d OFFSET %d",
                $dateFrom, $dateTo, $perPage, $offset
            ),
            ARRAY_A
        );
    }

    public static function getEventSummary(string $dateFrom, string $dateTo): array
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(l.id) as total_triggers,
                        COUNT(DISTINCT l.visitor_id) as unique_visitors
                 FROM {$wpdb->prefix}ept_event_log l
                 WHERE l.created_at >= %s AND l.created_at < %s",
                $dateFrom, $dateTo
            ),
            ARRAY_A
        );
        return $row ?: ['total_triggers' => 0, 'unique_visitors' => 0];
    }

    public static function getEventStatsCount(string $pageUrl = ''): int
    {
        global $wpdb;
        if ($pageUrl !== '') {
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}ept_events WHERE page_url = %s",
                    $pageUrl
                )
            );
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ept_events");
    }

    public static function getEventStats(string $dateFrom, string $dateTo, int $perPage = 20, int $page = 1, string $pageUrl = '', string $orderBy = 'total_triggers', string $order = 'DESC'): array
    {
        global $wpdb;
        $allowed = ['reference_name', 'event_tag', 'total_triggers', 'unique_visitors'];
        if (!in_array($orderBy, $allowed, true)) {
            $orderBy = 'total_triggers';
        }
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $offset = ($page - 1) * $perPage;
        $where = '';
        $params = [$dateFrom, $dateTo];
        if ($pageUrl !== '') {
            $where = 'WHERE e.page_url = %s';
            $params[] = $pageUrl;
        }
        $params[] = $perPage;
        $params[] = $offset;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.id, e.reference_name, e.event_tag, e.event_type, e.page_url,
                        COUNT(l.id) as total_triggers,
                        COUNT(DISTINCT l.visitor_id) as unique_visitors
                 FROM {$wpdb->prefix}ept_events e
                 LEFT JOIN {$wpdb->prefix}ept_event_log l ON e.id = l.event_id
                    AND l.created_at >= %s AND l.created_at < %s
                 $where
                 GROUP BY e.id
                 ORDER BY {$orderBy} {$order}
                 LIMIT %d OFFSET %d",
                $params
            ),
            ARRAY_A
        );
    }

    // ── Page detail & daily breakdown queries ──────────────────────────

    public static function getDailyVisits(string $dateFrom, string $dateTo): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(created_at) as visit_date,
                        COUNT(*) as total_visits,
                        COUNT(DISTINCT visitor_id) as unique_visitors
                 FROM {$wpdb->prefix}ept_visits
                 WHERE created_at >= %s AND created_at < %s
                 GROUP BY visit_date
                 ORDER BY visit_date ASC",
                $dateFrom, $dateTo
            ),
            ARRAY_A
        );
    }

    public static function getPageVisitSummary(string $pageUrl, string $dateFrom, string $dateTo): array
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) as total_visits,
                        COUNT(DISTINCT visitor_id) as unique_visitors
                 FROM {$wpdb->prefix}ept_visits
                 WHERE page_url = %s AND created_at >= %s AND created_at < %s",
                $pageUrl, $dateFrom, $dateTo
            ),
            ARRAY_A
        );
        return $row ?: ['total_visits' => 0, 'unique_visitors' => 0];
    }

    public static function getPageDailyVisits(string $pageUrl, string $dateFrom, string $dateTo): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(created_at) as visit_date,
                        COUNT(*) as total_visits,
                        COUNT(DISTINCT visitor_id) as unique_visitors
                 FROM {$wpdb->prefix}ept_visits
                 WHERE page_url = %s AND created_at >= %s AND created_at < %s
                 GROUP BY visit_date
                 ORDER BY visit_date ASC",
                $pageUrl, $dateFrom, $dateTo
            ),
            ARRAY_A
        );
    }

    public static function getPageReferrers(string $pageUrl, string $dateFrom, string $dateTo, int $limit = 10): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT referrer, COUNT(*) as visits
                 FROM {$wpdb->prefix}ept_visits
                 WHERE page_url = %s AND created_at >= %s AND created_at < %s
                       AND referrer != ''
                 GROUP BY referrer
                 ORDER BY visits DESC
                 LIMIT %d",
                $pageUrl, $dateFrom, $dateTo, $limit
            ),
            ARRAY_A
        );
    }

    public static function getPageDeviceBreakdown(string $pageUrl, string $dateFrom, string $dateTo): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT device_type, COUNT(*) as visits
                 FROM {$wpdb->prefix}ept_visits
                 WHERE page_url = %s AND created_at >= %s AND created_at < %s
                       AND device_type != ''
                 GROUP BY device_type
                 ORDER BY visits DESC",
                $pageUrl, $dateFrom, $dateTo
            ),
            ARRAY_A
        );
    }

    public static function getPageBrowserBreakdown(string $pageUrl, string $dateFrom, string $dateTo): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT browser, COUNT(*) as visits
                 FROM {$wpdb->prefix}ept_visits
                 WHERE page_url = %s AND created_at >= %s AND created_at < %s
                       AND browser != ''
                 GROUP BY browser
                 ORDER BY visits DESC",
                $pageUrl, $dateFrom, $dateTo
            ),
            ARRAY_A
        );
    }

    public static function getPageOsBreakdown(string $pageUrl, string $dateFrom, string $dateTo): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT os, COUNT(*) as visits
                 FROM {$wpdb->prefix}ept_visits
                 WHERE page_url = %s AND created_at >= %s AND created_at < %s
                       AND os != ''
                 GROUP BY os
                 ORDER BY visits DESC",
                $pageUrl, $dateFrom, $dateTo
            ),
            ARRAY_A
        );
    }

    public static function getPageEvents(string $pageUrl, string $dateFrom, string $dateTo): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.id, e.reference_name, e.event_tag, e.event_type,
                        COUNT(l.id) as total_triggers,
                        COUNT(DISTINCT l.visitor_id) as unique_visitors
                 FROM {$wpdb->prefix}ept_events e
                 LEFT JOIN {$wpdb->prefix}ept_event_log l ON e.id = l.event_id
                    AND l.created_at >= %s AND l.created_at < %s
                 WHERE e.page_url = %s
                 GROUP BY e.id
                 ORDER BY total_triggers DESC",
                $dateFrom, $dateTo, $pageUrl
            ),
            ARRAY_A
        );
    }

    public static function getPageCountryBreakdown(string $pageUrl, string $dateFrom, string $dateTo): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT country, COUNT(*) as visits
                 FROM {$wpdb->prefix}ept_visits
                 WHERE page_url = %s AND created_at >= %s AND created_at < %s
                       AND country != ''
                 GROUP BY country
                 ORDER BY visits DESC",
                $pageUrl, $dateFrom, $dateTo
            ),
            ARRAY_A
        );
    }

    public static function getTopCountries(string $dateFrom, string $dateTo, int $limit = 10): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT country, country_code, COUNT(*) as visits
                 FROM {$wpdb->prefix}ept_visits
                 WHERE created_at >= %s AND created_at < %s
                       AND country != ''
                 GROUP BY country, country_code
                 ORDER BY visits DESC
                 LIMIT %d",
                $dateFrom, $dateTo, $limit
            ),
            ARRAY_A
        );
    }
}
