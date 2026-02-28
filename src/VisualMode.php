<?php

namespace EpicTracking;

class VisualMode
{
    public static function init(): void
    {
        if (!is_admin() && !wp_doing_ajax()) {
            add_action('admin_bar_menu', [self::class, 'addAdminBarButton'], 100);
        }

        add_action('template_redirect', [self::class, 'maybeRenderVisualMode']);

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

        $targetUrl = remove_query_arg('ept_visual_mode');

        wp_enqueue_style('ept-visual-mode', EPT_PLUGIN_URL . 'assets/css/visual-mode.css', [], EPT_VERSION);
        wp_enqueue_script('ept-visual-mode', EPT_PLUGIN_URL . 'assets/js/visual-mode.js', [], EPT_VERSION, true);
        wp_localize_script('ept-visual-mode', 'eptVisualConfig', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('ept_visual_mode'),
            'targetUrl' => $targetUrl,
            'pageUrl'   => wp_parse_url($targetUrl, PHP_URL_PATH) ?: '/',
        ]);

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
