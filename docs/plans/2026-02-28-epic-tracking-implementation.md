# Epic Tracking Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a lightweight WordPress plugin for tracking page visits and click events with a visual configuration mode.

**Architecture:** PSR-4 autoloaded PHP classes under `EpicTracking` namespace. Vanilla JS frontend. WordPress AJAX API for all endpoints. Custom DB tables via `dbDelta()`. WordPress-native admin UI.

**Tech Stack:** PHP 7.4+, Composer PSR-4, vanilla JavaScript, WordPress AJAX API, WordPress admin CSS

---

### Task 1: Project Scaffold

**Files:**
- Create: `composer.json`
- Create: `epic-tracking.php`

**Step 1: Create composer.json**

```json
{
    "name": "epicwpsolutions/epic-tracking",
    "description": "Lightweight visit and event tracking for WordPress",
    "type": "wordpress-plugin",
    "autoload": {
        "psr-4": {
            "EpicTracking\\": "src/"
        }
    },
    "require": {
        "php": ">=7.4"
    }
}
```

**Step 2: Run composer install**

Run: `cd /Users/robbertvermeulen/dev/epicwpsolutions/wp-content/plugins/epic-tracking && composer install`
Expected: `vendor/` directory created with autoload files.

**Step 3: Create main plugin file**

Create `epic-tracking.php` — the WordPress plugin bootstrap:

```php
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
```

**Step 4: Create directory structure**

```bash
mkdir -p src assets/js assets/css templates
```

**Step 5: Commit**

```bash
git init
git add composer.json composer.lock epic-tracking.php vendor/autoload.php
git commit -m "feat: project scaffold with Composer PSR-4 autoloading"
```

Note: Add a `.gitignore` with `vendor/` contents excluded except `autoload.php` — or commit the full vendor since there are no dependencies beyond autoload.

---

### Task 2: Database Layer

**Files:**
- Create: `src/Database.php`

**Step 1: Create Database class**

```php
<?php

namespace EpicTracking;

class Database
{
    const DB_VERSION = '1.0.0';
    const DB_VERSION_OPTION = 'ept_db_version';

    public static function init(): void
    {
        // Check for DB upgrades on admin_init
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

    // --- Query methods ---

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
```

**Step 2: Verify activation creates tables**

Activate the plugin in WordPress admin and verify tables exist:
- `wp_ept_events`
- `wp_ept_visits`
- `wp_ept_event_log`

**Step 3: Commit**

```bash
git add src/Database.php
git commit -m "feat: database layer with table creation and query methods"
```

---

### Task 3: Bot Filter

**Files:**
- Create: `src/BotFilter.php`

**Step 1: Create BotFilter class**

```php
<?php

namespace EpicTracking;

class BotFilter
{
    private static array $botPatterns = [
        'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
        'yandexbot', 'sogou', 'facebookexternalhit', 'twitterbot',
        'rogerbot', 'linkedinbot', 'embedly', 'showyoubot', 'outbrain',
        'pinterest', 'applebot', 'semrushbot', 'ahrefsbot', 'mj12bot',
        'dotbot', 'petalbot', 'bytespider',
        'bot', 'crawl', 'spider', 'scrape', 'fetch',
    ];

    public static function isBot(string $userAgent): bool
    {
        $ua = strtolower($userAgent);
        foreach (self::$botPatterns as $pattern) {
            if (str_contains($ua, $pattern)) {
                return true;
            }
        }
        return false;
    }
}
```

**Step 2: Commit**

```bash
git add src/BotFilter.php
git commit -m "feat: bot filter with User-Agent pattern matching"
```

---

### Task 4: Tracker Backend (AJAX Endpoints)

**Files:**
- Create: `src/Tracker.php`

**Step 1: Create Tracker class**

```php
<?php

namespace EpicTracking;

class Tracker
{
    public static function init(): void
    {
        // Frontend script
        add_action('wp_enqueue_scripts', [self::class, 'enqueueTracker']);

        // AJAX endpoints (nopriv = available to non-logged-in users)
        add_action('wp_ajax_ept_track_visit', [self::class, 'handleTrackVisit']);
        add_action('wp_ajax_nopriv_ept_track_visit', [self::class, 'handleTrackVisit']);
        add_action('wp_ajax_ept_track_event', [self::class, 'handleTrackEvent']);
        add_action('wp_ajax_nopriv_ept_track_event', [self::class, 'handleTrackEvent']);
    }

    public static function enqueueTracker(): void
    {
        // Don't track excluded roles
        if (self::isExcludedUser()) {
            return;
        }

        wp_enqueue_script(
            'ept-tracker',
            EPT_PLUGIN_URL . 'assets/js/tracker.js',
            [],
            EPT_VERSION,
            true
        );

        // Get events for the current page
        $pageUrl = self::getCurrentPageUrl();
        $events = Database::getEventsForPage($pageUrl);

        // Pass config to JS inline (no extra HTTP request)
        wp_localize_script('ept-tracker', 'eptConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'pageUrl' => $pageUrl,
            'events'  => array_map(function ($event) {
                return [
                    'id'       => (int) $event['id'],
                    'selector' => $event['selector'],
                    'tag'      => $event['event_tag'],
                ];
            }, $events),
        ]);
    }

    public static function handleTrackVisit(): void
    {
        $visitorId = sanitize_text_field($_POST['visitor_id'] ?? '');
        $pageUrl   = sanitize_text_field($_POST['page_url'] ?? '');
        $referrer  = sanitize_text_field($_POST['referrer'] ?? '');
        $userAgent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');

        if (empty($visitorId) || empty($pageUrl)) {
            wp_send_json_error('Missing required fields', 400);
        }

        if (BotFilter::isBot($userAgent)) {
            wp_send_json_success(); // Silently ignore bots
        }

        Database::logVisit($visitorId, $pageUrl, $referrer, $userAgent);
        wp_send_json_success();
    }

    public static function handleTrackEvent(): void
    {
        $eventId   = (int) ($_POST['event_id'] ?? 0);
        $visitorId = sanitize_text_field($_POST['visitor_id'] ?? '');
        $pageUrl   = sanitize_text_field($_POST['page_url'] ?? '');
        $userAgent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');

        if (empty($eventId) || empty($visitorId) || empty($pageUrl)) {
            wp_send_json_error('Missing required fields', 400);
        }

        if (BotFilter::isBot($userAgent)) {
            wp_send_json_success();
        }

        Database::logEvent($eventId, $visitorId, $pageUrl);
        wp_send_json_success();
    }

    private static function isExcludedUser(): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $settings = get_option('ept_settings', []);
        $excludedRoles = $settings['excluded_roles'] ?? ['administrator'];
        $user = wp_get_current_user();

        return !empty(array_intersect($excludedRoles, $user->roles));
    }

    private static function getCurrentPageUrl(): string
    {
        return wp_parse_url(
            home_url(add_query_arg(null, null)),
            PHP_URL_PATH
        ) ?: '/';
    }
}
```

**Step 2: Commit**

```bash
git add src/Tracker.php
git commit -m "feat: tracker backend with visit and event AJAX endpoints"
```

---

### Task 5: Frontend Tracker JavaScript

**Files:**
- Create: `assets/js/tracker.js`

**Step 1: Create tracker.js**

```javascript
(function () {
    'use strict';

    if (typeof eptConfig === 'undefined') return;

    var COOKIE_NAME = 'ept_visitor_id';
    var COOKIE_DAYS = 365;

    function getVisitorId() {
        var match = document.cookie.match(new RegExp('(^| )' + COOKIE_NAME + '=([^;]+)'));
        if (match) return match[2];

        var id = generateUUID();
        var expires = new Date(Date.now() + COOKIE_DAYS * 864e5).toUTCString();
        document.cookie = COOKIE_NAME + '=' + id + '; expires=' + expires + '; path=/; SameSite=Lax';
        return id;
    }

    function generateUUID() {
        // Simple UUID v4 generator
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = (Math.random() * 16) | 0;
            var v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    function sendBeacon(action, data) {
        var formData = new FormData();
        formData.append('action', action);
        for (var key in data) {
            formData.append(key, data[key]);
        }

        if (navigator.sendBeacon) {
            navigator.sendBeacon(eptConfig.ajaxUrl, formData);
        } else {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', eptConfig.ajaxUrl, true);
            xhr.send(formData);
        }
    }

    // --- Init ---
    var visitorId = getVisitorId();

    // Track page visit
    sendBeacon('ept_track_visit', {
        visitor_id: visitorId,
        page_url: eptConfig.pageUrl,
        referrer: document.referrer || '',
    });

    // Bind configured events
    if (eptConfig.events && eptConfig.events.length > 0) {
        // Set data-ept-id on matched elements
        eptConfig.events.forEach(function (evt) {
            var el = document.querySelector(evt.selector);
            if (el) {
                el.setAttribute('data-ept-id', evt.id);
            }
        });

        // Single delegated click listener
        document.addEventListener('click', function (e) {
            var target = e.target.closest('[data-ept-id]');
            if (!target) return;

            sendBeacon('ept_track_event', {
                event_id: target.getAttribute('data-ept-id'),
                visitor_id: visitorId,
                page_url: eptConfig.pageUrl,
            });
        });
    }
})();
```

**Step 2: Verify file size**

Run: `wc -c assets/js/tracker.js`
Target: under 2KB before gzip.

**Step 3: Commit**

```bash
git add assets/js/tracker.js
git commit -m "feat: frontend tracker with cookie-based visits and delegated click events"
```

---

### Task 6: Visual Mode Backend

**Files:**
- Create: `src/VisualMode.php`

**Step 1: Create VisualMode class**

```php
<?php

namespace EpicTracking;

class VisualMode
{
    public static function init(): void
    {
        if (!is_admin() && !wp_doing_ajax()) {
            // Admin bar node on frontend
            add_action('admin_bar_menu', [self::class, 'addAdminBarButton'], 100);
        }

        // Visual mode page (loaded in wrapper)
        add_action('template_redirect', [self::class, 'maybeRenderVisualMode']);

        // AJAX endpoints for event management
        add_action('wp_ajax_ept_save_event', [self::class, 'handleSaveEvent']);
        add_action('wp_ajax_ept_update_event', [self::class, 'handleUpdateEvent']);
        add_action('wp_ajax_ept_delete_event', [self::class, 'handleDeleteEvent']);
        add_action('wp_ajax_ept_get_page_events', [self::class, 'handleGetPageEvents']);
    }

    public static function addAdminBarButton(\WP_Admin_Bar $adminBar): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $currentUrl = home_url(add_query_arg(null, null));
        $visualUrl = add_query_arg('ept_visual_mode', '1', $currentUrl);

        $adminBar->add_node([
            'id'    => 'epic-tracking',
            'title' => 'Epic Tracking',
            'href'  => $visualUrl,
            'meta'  => [
                'title' => 'Open Epic Tracking Visual Mode',
            ],
        ]);
    }

    public static function maybeRenderVisualMode(): void
    {
        if (!isset($_GET['ept_visual_mode']) || $_GET['ept_visual_mode'] !== '1') {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        // Get the target URL (current page without the visual mode param)
        $targetUrl = remove_query_arg('ept_visual_mode');

        // Enqueue visual mode assets
        wp_enqueue_style('ept-visual-mode', EPT_PLUGIN_URL . 'assets/css/visual-mode.css', [], EPT_VERSION);
        wp_enqueue_script('ept-visual-mode', EPT_PLUGIN_URL . 'assets/js/visual-mode.js', [], EPT_VERSION, true);
        wp_localize_script('ept-visual-mode', 'eptVisualConfig', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('ept_visual_mode'),
            'targetUrl' => $targetUrl,
            'pageUrl'   => wp_parse_url($targetUrl, PHP_URL_PATH) ?: '/',
        ]);

        // Render the visual mode wrapper (bypasses theme template)
        include EPT_PLUGIN_DIR . 'templates/visual-mode.php';
        exit;
    }

    public static function handleSaveEvent(): void
    {
        check_ajax_referer('ept_visual_mode', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $id = Database::saveEvent([
            'page_url'       => sanitize_text_field($_POST['page_url'] ?? ''),
            'selector'       => sanitize_text_field($_POST['selector'] ?? ''),
            'reference_name' => sanitize_text_field($_POST['reference_name'] ?? ''),
            'event_tag'      => sanitize_text_field($_POST['event_tag'] ?? ''),
            'event_type'     => 'click',
        ]);

        wp_send_json_success(['id' => $id]);
    }

    public static function handleUpdateEvent(): void
    {
        check_ajax_referer('ept_visual_mode', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            wp_send_json_error('Missing event ID', 400);
        }

        Database::updateEvent($id, [
            'reference_name' => sanitize_text_field($_POST['reference_name'] ?? ''),
            'event_tag'      => sanitize_text_field($_POST['event_tag'] ?? ''),
            'selector'       => sanitize_text_field($_POST['selector'] ?? ''),
        ]);

        wp_send_json_success();
    }

    public static function handleDeleteEvent(): void
    {
        check_ajax_referer('ept_visual_mode', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            wp_send_json_error('Missing event ID', 400);
        }

        Database::deleteEvent($id);
        wp_send_json_success();
    }

    public static function handleGetPageEvents(): void
    {
        check_ajax_referer('ept_visual_mode', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $pageUrl = sanitize_text_field($_POST['page_url'] ?? '');
        $events = Database::getEventsForPage($pageUrl);

        wp_send_json_success($events);
    }
}
```

**Step 2: Create visual mode template**

Create `templates/visual-mode.php`:

```php
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Epic Tracking — Visual Mode</title>
    <?php wp_head(); ?>
</head>
<body class="ept-visual-mode-body">
    <div id="ept-visual-wrapper">
        <div id="ept-visual-iframe-container">
            <iframe id="ept-visual-iframe" src="<?php echo esc_url(eptVisualConfig['targetUrl'] ?? ''); ?>"></iframe>
        </div>
        <div id="ept-visual-sidebar">
            <div class="ept-sidebar-header">
                <h2>Epic Tracking</h2>
                <a href="<?php echo esc_url(remove_query_arg('ept_visual_mode')); ?>" class="ept-close-btn">&times;</a>
            </div>
            <div class="ept-sidebar-content">
                <div id="ept-events-list">
                    <h3>Configured Events</h3>
                    <div id="ept-events-container">
                        <p class="ept-loading">Loading events...</p>
                    </div>
                </div>
                <hr>
                <button id="ept-select-element" class="button button-primary">Select Element</button>
                <div id="ept-event-form" style="display:none;">
                    <h3>Configure Event</h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="ept-selector">Selector</label></th>
                            <td><input type="text" id="ept-selector" class="regular-text" readonly></td>
                        </tr>
                        <tr>
                            <th><label for="ept-reference-name">Reference Name</label></th>
                            <td><input type="text" id="ept-reference-name" class="regular-text" placeholder="e.g. CTA Button Hero"></td>
                        </tr>
                        <tr>
                            <th><label for="ept-event-tag">Event Tag</label></th>
                            <td><input type="text" id="ept-event-tag" class="regular-text" placeholder="e.g. cta_hero_click"></td>
                        </tr>
                    </table>
                    <p>
                        <button id="ept-save-event" class="button button-primary">Save Event</button>
                        <button id="ept-cancel-event" class="button">Cancel</button>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
```

Note: The iframe `src` should be set via JS from `eptVisualConfig.targetUrl` rather than inline PHP to avoid escaping issues. The template should output the config as a JS variable via `wp_localize_script` (already done in VisualMode::maybeRenderVisualMode).

**Step 3: Commit**

```bash
git add src/VisualMode.php templates/visual-mode.php
git commit -m "feat: visual mode backend with iframe wrapper and event CRUD endpoints"
```

---

### Task 7: Visual Mode Frontend (JS + CSS)

**Files:**
- Create: `assets/js/visual-mode.js`
- Create: `assets/css/visual-mode.css`

**Step 1: Create visual-mode.css**

WordPress-native styling for the visual mode wrapper:

```css
/* Visual mode wrapper layout */
.ept-visual-mode-body {
    margin: 0;
    padding: 0;
    overflow: hidden;
    height: 100vh;
}

#ept-visual-wrapper {
    display: flex;
    height: 100vh;
    width: 100vw;
}

#ept-visual-iframe-container {
    flex: 1;
    min-width: 0;
}

#ept-visual-iframe {
    width: 100%;
    height: 100%;
    border: none;
}

/* Sidebar — WP native look */
#ept-visual-sidebar {
    width: 360px;
    min-width: 360px;
    background: #f0f0f1;
    border-left: 1px solid #c3c4c7;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    font-size: 13px;
    color: #1d2327;
}

.ept-sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: #fff;
    border-bottom: 1px solid #c3c4c7;
}

.ept-sidebar-header h2 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.ept-close-btn {
    font-size: 20px;
    text-decoration: none;
    color: #787c82;
    line-height: 1;
    padding: 4px;
}

.ept-close-btn:hover {
    color: #d63638;
}

.ept-sidebar-content {
    padding: 16px;
}

.ept-sidebar-content h3 {
    font-size: 13px;
    font-weight: 600;
    margin: 0 0 8px;
    color: #1d2327;
}

.ept-sidebar-content hr {
    border: none;
    border-top: 1px solid #c3c4c7;
    margin: 16px 0;
}

/* Event list items */
.ept-event-item {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 2px;
    padding: 10px 12px;
    margin-bottom: 8px;
}

.ept-event-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ept-event-item-name {
    font-weight: 600;
    font-size: 13px;
}

.ept-event-item-tag {
    color: #787c82;
    font-size: 12px;
    font-family: monospace;
}

.ept-event-item-selector {
    color: #787c82;
    font-size: 11px;
    font-family: monospace;
    margin-top: 4px;
    word-break: break-all;
}

.ept-event-item-actions {
    margin-top: 8px;
}

.ept-event-item-actions .button {
    font-size: 11px;
    min-height: 24px;
    line-height: 22px;
    padding: 0 8px;
}

/* Form styling */
#ept-event-form .form-table {
    margin: 0;
}

#ept-event-form .form-table th {
    font-size: 12px;
    font-weight: 600;
    padding: 8px 0 4px;
    width: auto;
}

#ept-event-form .form-table td {
    padding: 0 0 8px;
}

#ept-event-form .regular-text {
    width: 100%;
    font-size: 13px;
}

#ept-event-form p {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

/* Selection mode indicator */
.ept-selecting-active #ept-select-element {
    background: #d63638;
    border-color: #d63638;
    color: #fff;
}

/* No events message */
.ept-no-events {
    color: #787c82;
    font-style: italic;
}

.ept-loading {
    color: #787c82;
}
```

**Step 2: Create visual-mode.js**

```javascript
(function () {
    'use strict';

    if (typeof eptVisualConfig === 'undefined') return;

    var config = eptVisualConfig;
    var iframe = document.getElementById('ept-visual-iframe');
    var sidebar = document.getElementById('ept-visual-sidebar');
    var eventsContainer = document.getElementById('ept-events-container');
    var selectBtn = document.getElementById('ept-select-element');
    var eventForm = document.getElementById('ept-event-form');
    var selectorInput = document.getElementById('ept-selector');
    var referenceInput = document.getElementById('ept-reference-name');
    var eventTagInput = document.getElementById('ept-event-tag');
    var saveBtn = document.getElementById('ept-save-event');
    var cancelBtn = document.getElementById('ept-cancel-event');

    var isSelecting = false;
    var highlightOverlay = null;

    // --- Set iframe src ---
    iframe.src = config.targetUrl;

    // --- Load events ---
    function loadEvents() {
        var formData = new FormData();
        formData.append('action', 'ept_get_page_events');
        formData.append('nonce', config.nonce);
        formData.append('page_url', config.pageUrl);

        fetch(config.ajaxUrl, { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    renderEvents(res.data);
                }
            });
    }

    function renderEvents(events) {
        if (!events || events.length === 0) {
            eventsContainer.innerHTML = '<p class="ept-no-events">No events configured for this page.</p>';
            return;
        }

        var html = '';
        events.forEach(function (evt) {
            html += '<div class="ept-event-item" data-event-id="' + evt.id + '">'
                + '<div class="ept-event-item-header">'
                + '<span class="ept-event-item-name">' + escHtml(evt.reference_name) + '</span>'
                + '<span class="ept-event-item-tag">' + escHtml(evt.event_tag) + '</span>'
                + '</div>'
                + '<div class="ept-event-item-selector">' + escHtml(evt.selector) + '</div>'
                + '<div class="ept-event-item-actions">'
                + '<button class="button ept-highlight-event" data-selector="' + escAttr(evt.selector) + '">Highlight</button> '
                + '<button class="button ept-delete-event" data-id="' + evt.id + '">Delete</button>'
                + '</div>'
                + '</div>';
        });
        eventsContainer.innerHTML = html;

        // Bind delete buttons
        eventsContainer.querySelectorAll('.ept-delete-event').forEach(function (btn) {
            btn.addEventListener('click', function () {
                deleteEvent(parseInt(this.getAttribute('data-id')));
            });
        });

        // Bind highlight buttons
        eventsContainer.querySelectorAll('.ept-highlight-event').forEach(function (btn) {
            btn.addEventListener('click', function () {
                highlightInIframe(this.getAttribute('data-selector'));
            });
        });
    }

    // --- Element selection ---
    selectBtn.addEventListener('click', function () {
        if (isSelecting) {
            stopSelecting();
        } else {
            startSelecting();
        }
    });

    function startSelecting() {
        isSelecting = true;
        selectBtn.textContent = 'Cancel Selection';
        document.body.classList.add('ept-selecting-active');

        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

        // Create overlay element in iframe
        highlightOverlay = iframeDoc.createElement('div');
        highlightOverlay.id = 'ept-highlight-overlay';
        highlightOverlay.style.cssText = 'position:absolute;background:rgba(0,124,186,0.15);border:2px solid #007cba;pointer-events:none;z-index:999999;transition:all 0.1s ease;display:none;';
        iframeDoc.body.appendChild(highlightOverlay);

        iframeDoc.addEventListener('mouseover', onIframeMouseOver);
        iframeDoc.addEventListener('click', onIframeClick);
    }

    function stopSelecting() {
        isSelecting = false;
        selectBtn.textContent = 'Select Element';
        document.body.classList.remove('ept-selecting-active');

        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        iframeDoc.removeEventListener('mouseover', onIframeMouseOver);
        iframeDoc.removeEventListener('click', onIframeClick);

        if (highlightOverlay && highlightOverlay.parentNode) {
            highlightOverlay.parentNode.removeChild(highlightOverlay);
        }
        highlightOverlay = null;
    }

    function onIframeMouseOver(e) {
        if (!highlightOverlay) return;
        var rect = e.target.getBoundingClientRect();
        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        var scrollX = iframeDoc.documentElement.scrollLeft || iframeDoc.body.scrollLeft;
        var scrollY = iframeDoc.documentElement.scrollTop || iframeDoc.body.scrollTop;

        highlightOverlay.style.display = 'block';
        highlightOverlay.style.top = (rect.top + scrollY) + 'px';
        highlightOverlay.style.left = (rect.left + scrollX) + 'px';
        highlightOverlay.style.width = rect.width + 'px';
        highlightOverlay.style.height = rect.height + 'px';
    }

    function onIframeClick(e) {
        e.preventDefault();
        e.stopPropagation();

        var selector = generateSelector(e.target);
        selectorInput.value = selector;
        referenceInput.value = '';
        eventTagInput.value = '';
        eventForm.style.display = 'block';
        referenceInput.focus();

        stopSelecting();
    }

    // --- Selector generation ---
    function generateSelector(el) {
        // Priority 1: Elementor data-id
        if (el.getAttribute('data-id')) {
            return '[data-id="' + el.getAttribute('data-id') + '"]';
        }

        // Priority 2: HTML id
        if (el.id && !el.id.match(/^\d/)) {
            return '#' + CSS.escape(el.id);
        }

        // Priority 3: Build unique selector
        return buildUniqueSelector(el);
    }

    function buildUniqueSelector(el) {
        var parts = [];
        var current = el;

        while (current && current !== current.ownerDocument.body) {
            var tag = current.tagName.toLowerCase();

            // Check for data-id (Elementor)
            if (current.getAttribute('data-id')) {
                parts.unshift('[data-id="' + current.getAttribute('data-id') + '"]');
                break;
            }

            // Check for id
            if (current.id && !current.id.match(/^\d/)) {
                parts.unshift('#' + CSS.escape(current.id));
                break;
            }

            // Use nth-child for disambiguation
            var parent = current.parentElement;
            if (parent) {
                var siblings = Array.from(parent.children).filter(function (s) {
                    return s.tagName === current.tagName;
                });
                if (siblings.length > 1) {
                    var index = siblings.indexOf(current) + 1;
                    tag += ':nth-of-type(' + index + ')';
                }
            }

            parts.unshift(tag);
            current = current.parentElement;
        }

        return parts.join(' > ');
    }

    // --- Save event ---
    saveBtn.addEventListener('click', function () {
        var referenceName = referenceInput.value.trim();
        var eventTag = eventTagInput.value.trim();
        var selector = selectorInput.value.trim();

        if (!referenceName || !eventTag || !selector) {
            alert('Please fill in all fields.');
            return;
        }

        var formData = new FormData();
        formData.append('action', 'ept_save_event');
        formData.append('nonce', config.nonce);
        formData.append('page_url', config.pageUrl);
        formData.append('selector', selector);
        formData.append('reference_name', referenceName);
        formData.append('event_tag', eventTag);

        fetch(config.ajaxUrl, { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    eventForm.style.display = 'none';
                    loadEvents();
                }
            });
    });

    cancelBtn.addEventListener('click', function () {
        eventForm.style.display = 'none';
    });

    // --- Delete event ---
    function deleteEvent(id) {
        if (!confirm('Delete this event?')) return;

        var formData = new FormData();
        formData.append('action', 'ept_delete_event');
        formData.append('nonce', config.nonce);
        formData.append('id', id);

        fetch(config.ajaxUrl, { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    loadEvents();
                }
            });
    }

    // --- Highlight element in iframe ---
    function highlightInIframe(selector) {
        try {
            var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            var el = iframeDoc.querySelector(selector);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.style.outline = '3px solid #007cba';
                el.style.outlineOffset = '2px';
                setTimeout(function () {
                    el.style.outline = '';
                    el.style.outlineOffset = '';
                }, 2000);
            }
        } catch (e) {
            // Cross-origin or selector error
        }
    }

    // --- Helpers ---
    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function escAttr(str) {
        return str.replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // --- Init ---
    iframe.addEventListener('load', function () {
        loadEvents();
    });
})();
```

**Step 3: Commit**

```bash
git add assets/js/visual-mode.js assets/css/visual-mode.css
git commit -m "feat: visual mode frontend with element selection and sidebar UI"
```

---

### Task 8: Admin Dashboard & Settings

**Files:**
- Create: `src/Admin.php`
- Create: `assets/css/admin.css`
- Create: `templates/admin-dashboard.php`

**Step 1: Create Admin class**

```php
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

        // Determine date range
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
```

**Step 2: Create admin-dashboard.php template**

```php
<div class="wrap">
    <h1>Epic Tracking</h1>

    <div class="ept-date-filter">
        <a href="<?php echo esc_url(add_query_arg('range', '1')); ?>"
           class="button <?php echo $range === '1' ? 'button-primary' : ''; ?>">Today</a>
        <a href="<?php echo esc_url(add_query_arg('range', '7')); ?>"
           class="button <?php echo $range === '7' ? 'button-primary' : ''; ?>">Last 7 days</a>
        <a href="<?php echo esc_url(add_query_arg('range', '30')); ?>"
           class="button <?php echo $range === '30' ? 'button-primary' : ''; ?>">Last 30 days</a>
    </div>

    <div class="ept-dashboard-grid">
        <div class="postbox">
            <h2 class="hndle">Page Visits</h2>
            <div class="inside">
                <?php if (empty($visitStats)) : ?>
                    <p>No visit data for this period.</p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th>Total Visits</th>
                                <th>Unique Visitors</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visitStats as $row) : ?>
                                <tr>
                                    <td><?php echo esc_html($row['page_url']); ?></td>
                                    <td><?php echo esc_html($row['total_visits']); ?></td>
                                    <td><?php echo esc_html($row['unique_visitors']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle">Click Events</h2>
            <div class="inside">
                <?php if (empty($eventStats)) : ?>
                    <p>No event data for this period.</p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Event Tag</th>
                                <th>Page</th>
                                <th>Total Clicks</th>
                                <th>Unique Clickers</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventStats as $row) : ?>
                                <tr>
                                    <td><?php echo esc_html($row['reference_name']); ?></td>
                                    <td><code><?php echo esc_html($row['event_tag']); ?></code></td>
                                    <td><?php echo esc_html($row['page_url']); ?></td>
                                    <td><?php echo esc_html($row['total_clicks']); ?></td>
                                    <td><?php echo esc_html($row['unique_clickers']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
```

**Step 3: Create admin-settings.php template**

```php
<div class="wrap">
    <h1>Epic Tracking Settings</h1>

    <form method="post" action="options.php">
        <?php settings_fields('ept_settings_group'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">Exclude Roles from Tracking</th>
                <td>
                    <fieldset>
                        <?php foreach ($allRoles as $slug => $name) : ?>
                            <label>
                                <input type="checkbox"
                                       name="ept_settings[excluded_roles][]"
                                       value="<?php echo esc_attr($slug); ?>"
                                       <?php checked(in_array($slug, $settings['excluded_roles'])); ?>>
                                <?php echo esc_html($name); ?>
                            </label><br>
                        <?php endforeach; ?>
                        <p class="description">Visits and events from these roles will not be tracked.</p>
                    </fieldset>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
```

**Step 4: Create admin.css**

```css
.ept-date-filter {
    margin: 16px 0;
    display: flex;
    gap: 4px;
}

.ept-dashboard-grid {
    display: grid;
    gap: 20px;
}

.ept-dashboard-grid .postbox {
    margin: 0;
}

.ept-dashboard-grid .hndle {
    cursor: default;
}

.ept-dashboard-grid .inside {
    margin: 0;
    padding: 0 12px 12px;
}
```

**Step 5: Commit**

```bash
git add src/Admin.php assets/css/admin.css templates/admin-dashboard.php templates/admin-settings.php
git commit -m "feat: admin dashboard with visit/event stats and role exclusion settings"
```

---

### Task 9: Final Wiring & Testing

**Step 1: Create .gitignore**

```
/vendor/*
!/vendor/autoload.php
!/vendor/composer/
```

**Step 2: Verify full plugin activation**

1. Activate plugin in WP admin
2. Check that DB tables are created (via phpMyAdmin or WP-CLI)
3. Visit a frontend page — verify `tracker.js` is loaded and visit is logged
4. Open Visual Mode from admin bar — verify iframe loads and sidebar works
5. Select an element, configure an event, save it
6. Visit the page as a non-admin — click the tracked element
7. Check admin dashboard — verify visit and event data appear

**Step 3: Commit**

```bash
git add .gitignore
git commit -m "chore: add gitignore and finalize plugin"
```
