<?php

namespace EpicTracking;

class VisualMode
{
    public static function init(): void
    {
        if (!is_admin() && !wp_doing_ajax()) {
            add_action('admin_bar_menu', [self::class, 'addAdminBarButton'], 100);
        }

        // Enqueue visual mode assets when ?ept_visual_mode=1
        add_action('wp_enqueue_scripts', [self::class, 'maybeEnqueueVisualMode']);

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

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $currentUrl = home_url($requestUri);
        $visualUrl = add_query_arg('ept_visual_mode', '1', $currentUrl);

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
        if (!isset($_GET['ept_visual_mode']) || $_GET['ept_visual_mode'] !== '1') {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $pageUrl = wp_parse_url(
            remove_query_arg('ept_visual_mode', home_url($requestUri)),
            PHP_URL_PATH
        ) ?: '/';

        $exitUrl = remove_query_arg('ept_visual_mode', $requestUri);

        wp_enqueue_style('dashicons');
        wp_enqueue_style('ept-visual-mode', EPT_PLUGIN_URL . 'assets/css/visual-mode.css', ['dashicons'], EPT_VERSION);
        wp_enqueue_script('ept-visual-mode', EPT_PLUGIN_URL . 'assets/js/visual-mode.js', [], EPT_VERSION, true);
        wp_localize_script('ept-visual-mode', 'eptVisualConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ept_visual_mode'),
            'pageUrl' => $pageUrl,
            'exitUrl' => $exitUrl,
        ]);
    }

    public static function handleSaveEvent(): void
    {
        check_ajax_referer('ept_visual_mode', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'epic-tracking'), 403);
            return;
        }

        $id = Database::saveEvent([
            'page_url'       => sanitize_text_field($_POST['page_url'] ?? ''),
            'selector'       => wp_unslash($_POST['selector'] ?? ''),
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
            wp_send_json_error(__('Unauthorized', 'epic-tracking'), 403);
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            wp_send_json_error(__('Missing event ID', 'epic-tracking'), 400);
            return;
        }

        Database::updateEvent($id, [
            'reference_name' => sanitize_text_field($_POST['reference_name'] ?? ''),
            'event_tag'      => sanitize_text_field($_POST['event_tag'] ?? ''),
            'selector'       => wp_unslash($_POST['selector'] ?? ''),
        ]);

        wp_send_json_success();
    }

    public static function handleDeleteEvent(): void
    {
        check_ajax_referer('ept_visual_mode', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'epic-tracking'), 403);
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            wp_send_json_error(__('Missing event ID', 'epic-tracking'), 400);
            return;
        }

        Database::deleteEvent($id);
        wp_send_json_success();
    }

    public static function handleGetPageEvents(): void
    {
        check_ajax_referer('ept_visual_mode', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'epic-tracking'), 403);
            return;
        }

        $pageUrl = sanitize_text_field($_POST['page_url'] ?? '');
        $events = Database::getEventsForPage($pageUrl);

        wp_send_json_success($events);
    }
}
