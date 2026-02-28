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
            <iframe id="ept-visual-iframe"></iframe>
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
