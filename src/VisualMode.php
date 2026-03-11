<?php

namespace EpicTracking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VisualMode
{
    public static function init(): void
    {
        if (!is_admin() && !wp_doing_ajax()) {
            add_action('admin_bar_menu', [self::class, 'addAdminBarButton'], 100);
        }

        // Enqueue visual mode assets when ?epictr_visual_mode=1
        add_action('wp_enqueue_scripts', [self::class, 'maybeEnqueueVisualMode']);

        // AJAX endpoints for event management
        add_action('wp_ajax_epictr_save_event', [self::class, 'handleSaveEvent']);
        add_action('wp_ajax_epictr_update_event', [self::class, 'handleUpdateEvent']);
        add_action('wp_ajax_epictr_delete_event', [self::class, 'handleDeleteEvent']);
        add_action('wp_ajax_epictr_get_page_events', [self::class, 'handleGetPageEvents']);
    }

    public static function addAdminBarButton(\WP_Admin_Bar $adminBar): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $requestUri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
        $currentUrl = home_url($requestUri);
        $visualUrl = add_query_arg('epictr_visual_mode', '1', $currentUrl);

        $adminBar->add_node([
            'id'    => 'epic-tracking',
            'title' => __('Edit Tracking', 'epic-tracking'),
            'href'  => $visualUrl,
            'meta'  => [
                'title' => __('Set up event tracking on this page', 'epic-tracking'),
            ],
        ]);
    }

    public static function maybeEnqueueVisualMode(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- query param check, not form processing
        if (!isset($_GET['epictr_visual_mode']) || sanitize_text_field(wp_unslash($_GET['epictr_visual_mode'])) !== '1') {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $requestUri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
        $pageUrl = wp_parse_url(
            remove_query_arg('epictr_visual_mode', home_url($requestUri)),
            PHP_URL_PATH
        ) ?: '/';

        $exitUrl = remove_query_arg('epictr_visual_mode', $requestUri);

        wp_enqueue_style('dashicons');
        wp_enqueue_style('epictr-visual-mode', EPICTR_PLUGIN_URL . 'assets/css/visual-mode.css', ['dashicons'], EPICTR_VERSION);
        wp_enqueue_script('epictr-visual-mode', EPICTR_PLUGIN_URL . 'assets/js/visual-mode.js', [], EPICTR_VERSION, true);
        wp_localize_script('epictr-visual-mode', 'epictrVisualConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('epictr_visual_mode'),
            'pageUrl' => $pageUrl,
            'exitUrl' => $exitUrl,
        ]);
    }

    public static function handleSaveEvent(): void
    {
        check_ajax_referer('epictr_visual_mode', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'epic-tracking'), 403);
            return;
        }

        $id = Database::saveEvent([
            'page_url'       => sanitize_text_field(wp_unslash($_POST['page_url'] ?? '')),
            'selector'       => sanitize_text_field(wp_unslash($_POST['selector'] ?? '')),
            'reference_name' => sanitize_text_field(wp_unslash($_POST['reference_name'] ?? '')),
            'event_tag'      => sanitize_text_field(wp_unslash($_POST['event_tag'] ?? '')),
            'event_type'     => 'click',
        ]);

        wp_send_json_success(['id' => $id]);
    }

    public static function handleUpdateEvent(): void
    {
        check_ajax_referer('epictr_visual_mode', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'epic-tracking'), 403);
            return;
        }

        $id = absint(wp_unslash($_POST['id'] ?? 0));
        if (!$id) {
            wp_send_json_error(__('Missing event ID', 'epic-tracking'), 400);
            return;
        }

        Database::updateEvent($id, [
            'reference_name' => sanitize_text_field(wp_unslash($_POST['reference_name'] ?? '')),
            'event_tag'      => sanitize_text_field(wp_unslash($_POST['event_tag'] ?? '')),
            'selector'       => sanitize_text_field(wp_unslash($_POST['selector'] ?? '')),
        ]);

        wp_send_json_success();
    }

    public static function handleDeleteEvent(): void
    {
        check_ajax_referer('epictr_visual_mode', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'epic-tracking'), 403);
            return;
        }

        $id = absint(wp_unslash($_POST['id'] ?? 0));
        if (!$id) {
            wp_send_json_error(__('Missing event ID', 'epic-tracking'), 400);
            return;
        }

        Database::deleteEvent($id);
        wp_send_json_success();
    }

    public static function handleGetPageEvents(): void
    {
        check_ajax_referer('epictr_visual_mode', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'epic-tracking'), 403);
            return;
        }

        $pageUrl = sanitize_text_field(wp_unslash($_POST['page_url'] ?? ''));
        $events = Database::getEventsForPage($pageUrl);

        wp_send_json_success($events);
    }
}
