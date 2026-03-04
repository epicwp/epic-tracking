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
        add_action('wp_ajax_ept_track_visit', [self::class, 'handleTrackVisit']);
        add_action('wp_ajax_nopriv_ept_track_visit', [self::class, 'handleTrackVisit']);
        add_action('wp_ajax_ept_track_event', [self::class, 'handleTrackEvent']);
        add_action('wp_ajax_nopriv_ept_track_event', [self::class, 'handleTrackEvent']);
    }

    public static function enqueueTracker(): void
    {
        // Don't track in visual mode or excluded roles
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- query param check, not form processing
        if (isset($_GET['ept_visual_mode'])) {
            return;
        }
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

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- public tracking endpoint, no nonce for anonymous visitors
    public static function handleTrackVisit(): void
    {
        $visitorId = sanitize_text_field(wp_unslash($_POST['visitor_id'] ?? ''));
        $pageUrl   = sanitize_text_field(wp_unslash($_POST['page_url'] ?? ''));
        $referrer  = sanitize_text_field(wp_unslash($_POST['referrer'] ?? ''));
        // phpcs:enable WordPress.Security.NonceVerification.Missing
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

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- public tracking endpoint, no nonce for anonymous visitors
    public static function handleTrackEvent(): void
    {
        $eventId   = absint(wp_unslash($_POST['event_id'] ?? 0));
        $visitorId = sanitize_text_field(wp_unslash($_POST['visitor_id'] ?? ''));
        $pageUrl   = sanitize_text_field(wp_unslash($_POST['page_url'] ?? ''));
        // phpcs:enable WordPress.Security.NonceVerification.Missing
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

        $settings = get_option('ept_settings', []);
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
