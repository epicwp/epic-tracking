<?php

namespace EpicTracking;

class Database
{
    const DB_VERSION = '1.0.0';
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
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY visitor_id (visitor_id),
            KEY page_url (page_url(191)),
            KEY created_at (created_at)
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

    public static function logVisit(string $visitorId, string $pageUrl, string $referrer, string $userAgent): void
    {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}ept_visits", [
            'visitor_id' => $visitorId,
            'page_url'   => $pageUrl,
            'referrer'   => $referrer,
            'user_agent' => $userAgent,
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

    public static function getVisitStats(string $dateFrom, string $dateTo): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT page_url,
                        COUNT(*) as total_visits,
                        COUNT(DISTINCT visitor_id) as unique_visitors
                 FROM {$wpdb->prefix}ept_visits
                 WHERE created_at BETWEEN %s AND %s
                 GROUP BY page_url
                 ORDER BY total_visits DESC",
                $dateFrom,
                $dateTo
            ),
            ARRAY_A
        );
    }

    public static function getEventStats(string $dateFrom, string $dateTo): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.id, e.reference_name, e.event_tag, e.page_url,
                        COUNT(l.id) as total_clicks,
                        COUNT(DISTINCT l.visitor_id) as unique_clickers
                 FROM {$wpdb->prefix}ept_events e
                 LEFT JOIN {$wpdb->prefix}ept_event_log l ON e.id = l.event_id
                    AND l.created_at BETWEEN %s AND %s
                 GROUP BY e.id
                 ORDER BY total_clicks DESC",
                $dateFrom,
                $dateTo
            ),
            ARRAY_A
        );
    }
}
