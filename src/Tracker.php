<?php

namespace EpicTracking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tracker
{
    public static function init(): void
    {
        // Frontend script
        add_action('wp_enqueue_scripts', [self::class, 'enqueueTracker']);

        // AJAX endpoints (nopriv = available to non-logged-in users)
        add_action('wp_ajax_epictr_track_visit', [self::class, 'handleTrackVisit']);
        add_action('wp_ajax_nopriv_epictr_track_visit', [self::class, 'handleTrackVisit']);
        add_action('wp_ajax_epictr_track_event', [self::class, 'handleTrackEvent']);
        add_action('wp_ajax_nopriv_epictr_track_event', [self::class, 'handleTrackEvent']);
    }

    public static function enqueueTracker(): void
    {
        // Don't track in visual mode or excluded roles
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- query param check, not form processing
        if (isset($_GET['epictr_visual_mode'])) {
            return;
        }
        if (self::isExcludedUser()) {
            return;
        }

        wp_enqueue_script(
            'epictr-tracker',
            EPICTR_PLUGIN_URL . 'assets/js/tracker.js',
            [],
            EPICTR_VERSION,
            true
        );

        // Get events for the current page
        $pageUrl = self::getCurrentPageUrl();
        $events = Database::getEventsForPage($pageUrl);

        // Pass config to JS inline (no extra HTTP request)
        wp_localize_script('epictr-tracker', 'epictrConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('epictr_track'),
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
        if (!check_ajax_referer('epictr_track', 'nonce', false)) {
            wp_send_json_error(__('Invalid nonce', 'epic-tracking'), 403);
            return;
        }

        $visitorId = sanitize_text_field(wp_unslash($_POST['visitor_id'] ?? ''));
        $pageUrl   = sanitize_text_field(wp_unslash($_POST['page_url'] ?? ''));
        $referrer  = sanitize_text_field(wp_unslash($_POST['referrer'] ?? ''));
        $userAgent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? ''));

        if (empty($visitorId) || empty($pageUrl)) {
            wp_send_json_error(__('Missing required fields', 'epic-tracking'), 400);
            return;
        }

        if (BotFilter::isBot($userAgent)) {
            wp_send_json_success();
            return;
        }

        $parsed = UserAgentParser::parse($userAgent);
        $ip  = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
        $geo = GeoIP::lookup($ip);
        Database::logVisit($visitorId, $pageUrl, $referrer, $userAgent, $parsed['device_type'], $parsed['browser'], $parsed['os'], $geo['country'], $geo['country_code']);
        wp_send_json_success();
    }

    public static function handleTrackEvent(): void
    {
        if (!check_ajax_referer('epictr_track', 'nonce', false)) {
            wp_send_json_error(__('Invalid nonce', 'epic-tracking'), 403);
            return;
        }

        $eventId   = absint(wp_unslash($_POST['event_id'] ?? 0));
        $visitorId = sanitize_text_field(wp_unslash($_POST['visitor_id'] ?? ''));
        $pageUrl   = sanitize_text_field(wp_unslash($_POST['page_url'] ?? ''));
        $userAgent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? ''));

        if (empty($eventId) || empty($visitorId) || empty($pageUrl)) {
            wp_send_json_error(__('Missing required fields', 'epic-tracking'), 400);
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

        $settings = get_option('epictr_settings', []);
        $excludedRoles = $settings['excluded_roles'] ?? ['administrator'];
        $user = wp_get_current_user();

        return !empty(array_intersect($excludedRoles, $user->roles));
    }

    private static function getCurrentPageUrl(): string
    {
        $uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
        return wp_parse_url(home_url($uri), PHP_URL_PATH) ?: '/';
    }
}
