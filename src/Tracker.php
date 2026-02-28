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
            return;
        }

        if (BotFilter::isBot($userAgent)) {
            wp_send_json_success();
            return;
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
            return;
        }

        if (BotFilter::isBot($userAgent)) {
            wp_send_json_success();
            return;
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
