<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
    <div class="ept-page-detail-header">
        <a href="<?php echo esc_url(admin_url('admin.php?' . http_build_query(['page' => 'epic-tracking', 'date_from' => $dateFrom, 'date_to' => $dateTo]))); ?>" class="ept-back-link">&larr; <?php echo esc_html__('Back to Dashboard', 'epic-tracking'); ?></a>
        <h1><?php echo esc_html($pageUrl); ?></h1>
    </div>

    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="ept-date-picker">
        <input type="hidden" name="page" value="epic-tracking-page-detail">
        <input type="hidden" name="page_url" value="<?php echo esc_attr($pageUrl); ?>">
        <input type="hidden" name="date_from" value="<?php echo esc_attr($dateFrom); ?>">
        <input type="hidden" name="date_to" value="<?php echo esc_attr($dateTo); ?>">

        <button type="button" class="ept-date-picker__trigger">
            <span class="dashicons dashicons-calendar-alt"></span>
            <span class="ept-date-picker__label"></span>
            <span class="dashicons dashicons-arrow-down-alt2"></span>
        </button>

        <div class="ept-date-picker__dropdown">
            <?php
            $presets = [
                ['label' => __('Today', 'epic-tracking'),        'from' => gmdate('Y-m-d'),                                        'to' => gmdate('Y-m-d')],
                ['label' => __('Yesterday', 'epic-tracking'),     'from' => gmdate('Y-m-d', strtotime('-1 day')),                   'to' => gmdate('Y-m-d', strtotime('-1 day'))],
                ['label' => __('Last 7 days', 'epic-tracking'),   'from' => gmdate('Y-m-d', strtotime('-6 days')),                  'to' => gmdate('Y-m-d')],
                ['label' => __('Last 30 days', 'epic-tracking'),  'from' => gmdate('Y-m-d', strtotime('-29 days')),                 'to' => gmdate('Y-m-d')],
                ['label' => __('This month', 'epic-tracking'),    'from' => gmdate('Y-m-01'),                                      'to' => gmdate('Y-m-d')],
                ['label' => __('Last month', 'epic-tracking'),    'from' => gmdate('Y-m-01', strtotime('first day of last month')), 'to' => gmdate('Y-m-t', strtotime('last month'))],
            ];
            foreach ($presets as $preset) : ?>
                <button type="button" class="ept-date-picker__preset"
                    data-label="<?php echo esc_attr($preset['label']); ?>"
                    data-from="<?php echo esc_attr($preset['from']); ?>"
                    data-to="<?php echo esc_attr($preset['to']); ?>">
                    <?php echo esc_html($preset['label']); ?>
                    <span class="dashicons dashicons-yes-alt ept-date-picker__check"></span>
                </button>
            <?php endforeach; ?>

            <hr class="ept-date-picker__separator">

            <button type="button" class="ept-date-picker__preset" data-preset="custom" data-label="<?php echo esc_attr__('Custom range', 'epic-tracking'); ?>">
                <?php echo esc_html__('Custom range', 'epic-tracking'); ?>
                <span class="dashicons dashicons-yes-alt ept-date-picker__check"></span>
            </button>

            <div class="ept-date-picker__custom">
                <div class="ept-date-picker__custom-fields">
                    <input type="date" class="ept-date-picker__custom-from" value="<?php echo esc_attr($dateFrom); ?>" max="<?php echo esc_attr(gmdate('Y-m-d')); ?>">
                    <input type="date" class="ept-date-picker__custom-to" value="<?php echo esc_attr($dateTo); ?>" max="<?php echo esc_attr(gmdate('Y-m-d')); ?>">
                </div>
                <button type="button" class="button button-primary ept-date-picker__apply"><?php echo esc_html__('Apply', 'epic-tracking'); ?></button>
            </div>
        </div>
    </form>

    <div class="ept-stats-row ept-stats-row--2col">
        <div class="ept-stat-card">
            <p class="ept-stat-card-label"><span class="dashicons dashicons-visibility"></span> <?php echo esc_html__('Total Visits', 'epic-tracking'); ?></p>
            <p class="ept-stat-card-value"><?php echo esc_html(number_format_i18n($summary['total_visits'])); ?></p>
        </div>
        <div class="ept-stat-card">
            <p class="ept-stat-card-label"><span class="dashicons dashicons-groups"></span> <?php echo esc_html__('Unique Visitors', 'epic-tracking'); ?></p>
            <p class="ept-stat-card-value"><?php echo esc_html(number_format_i18n($summary['unique_visitors'])); ?></p>
        </div>
    </div>

    <div class="ept-section" style="margin-bottom: 20px;">
        <h2 class="ept-section-title"><span class="dashicons dashicons-chart-area"></span> <?php echo esc_html__('Traffic Overview', 'epic-tracking'); ?></h2>
        <?php if (empty($dailyVisits)) : ?>
            <div class="ept-empty-state">
                <span class="dashicons dashicons-chart-area"></span>
                <p><?php echo esc_html__('No daily data for this period.', 'epic-tracking'); ?></p>
            </div>
        <?php else : ?>
            <div class="ept-chart" data-chart="<?php echo esc_attr(wp_json_encode($dailyVisits)); ?>">
                <canvas></canvas>
            </div>
        <?php endif; ?>
    </div>

    <div class="ept-section" style="margin-bottom: 20px;">
        <h2 class="ept-section-title"><span class="dashicons dashicons-admin-links"></span> <?php echo esc_html__('Events on this Page', 'epic-tracking'); ?></h2>
        <?php if (empty($events)) : ?>
            <div class="ept-empty-state">
                <span class="dashicons dashicons-admin-links"></span>
                <p><?php echo esc_html__('No events configured for this page.', 'epic-tracking'); ?></p>
            </div>
        <?php else : ?>
            <table class="ept-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Reference', 'epic-tracking'); ?></th>
                        <th><?php echo esc_html__('Event Tag', 'epic-tracking'); ?></th>
                        <th><?php echo esc_html__('Type', 'epic-tracking'); ?></th>
                        <th class="ept-col-num"><?php echo esc_html__('Triggers', 'epic-tracking'); ?></th>
                        <th class="ept-col-num"><?php echo esc_html__('Unique Visitors', 'epic-tracking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row['reference_name']); ?></td>
                            <td><code><?php echo esc_html($row['event_tag']); ?></code></td>
                            <td><span class="ept-badge"><?php echo esc_html($row['event_type']); ?></span></td>
                            <td class="ept-col-num"><?php echo esc_html(number_format_i18n($row['total_triggers'])); ?></td>
                            <td class="ept-col-num"><?php echo esc_html(number_format_i18n($row['unique_visitors'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="ept-section" style="margin-bottom: 20px;">
        <h2 class="ept-section-title"><span class="dashicons dashicons-admin-links"></span> <?php echo esc_html__('Top Referrers', 'epic-tracking'); ?></h2>
        <?php if (empty($referrers)) : ?>
            <div class="ept-empty-state">
                <span class="dashicons dashicons-admin-links"></span>
                <p><?php echo esc_html__('No referrer data for this period.', 'epic-tracking'); ?></p>
            </div>
        <?php else : ?>
            <table class="ept-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Referrer', 'epic-tracking'); ?></th>
                        <th class="ept-col-num"><?php echo esc_html__('Visits', 'epic-tracking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referrers as $ref) : ?>
                        <tr>
                            <td><?php echo esc_html($ref['referrer']); ?></td>
                            <td class="ept-col-num"><?php echo esc_html(number_format_i18n($ref['visits'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="ept-breakdown-grid">
        <div class="ept-section">
            <h2 class="ept-section-title"><span class="dashicons dashicons-smartphone"></span> <?php echo esc_html__('Devices', 'epic-tracking'); ?></h2>
            <?php if (empty($devices)) : ?>
                <div class="ept-empty-state">
                    <span class="dashicons dashicons-smartphone"></span>
                    <p><?php echo esc_html__('No device data.', 'epic-tracking'); ?></p>
                </div>
            <?php else : ?>
                <table class="ept-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Device', 'epic-tracking'); ?></th>
                            <th class="ept-col-num"><?php echo esc_html__('Visits', 'epic-tracking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['device_type']); ?></td>
                                <td class="ept-col-num"><?php echo esc_html(number_format_i18n($row['visits'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="ept-section">
            <h2 class="ept-section-title"><span class="dashicons dashicons-admin-site-alt3"></span> <?php echo esc_html__('Browsers', 'epic-tracking'); ?></h2>
            <?php if (empty($browsers)) : ?>
                <div class="ept-empty-state">
                    <span class="dashicons dashicons-admin-site-alt3"></span>
                    <p><?php echo esc_html__('No browser data.', 'epic-tracking'); ?></p>
                </div>
            <?php else : ?>
                <table class="ept-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Browser', 'epic-tracking'); ?></th>
                            <th class="ept-col-num"><?php echo esc_html__('Visits', 'epic-tracking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($browsers as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['browser']); ?></td>
                                <td class="ept-col-num"><?php echo esc_html(number_format_i18n($row['visits'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="ept-section">
            <h2 class="ept-section-title"><span class="dashicons dashicons-desktop"></span> <?php echo esc_html__('Operating Systems', 'epic-tracking'); ?></h2>
            <?php if (empty($osList)) : ?>
                <div class="ept-empty-state">
                    <span class="dashicons dashicons-desktop"></span>
                    <p><?php echo esc_html__('No OS data.', 'epic-tracking'); ?></p>
                </div>
            <?php else : ?>
                <table class="ept-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('OS', 'epic-tracking'); ?></th>
                            <th class="ept-col-num"><?php echo esc_html__('Visits', 'epic-tracking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($osList as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['os']); ?></td>
                                <td class="ept-col-num"><?php echo esc_html(number_format_i18n($row['visits'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="ept-section">
            <h2 class="ept-section-title"><span class="dashicons dashicons-location"></span> <?php echo esc_html__('Countries', 'epic-tracking'); ?></h2>
            <?php if (empty($countries)) : ?>
                <div class="ept-empty-state">
                    <span class="dashicons dashicons-location"></span>
                    <p><?php echo esc_html__('No country data.', 'epic-tracking'); ?></p>
                </div>
            <?php else : ?>
                <table class="ept-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Country', 'epic-tracking'); ?></th>
                            <th class="ept-col-num"><?php echo esc_html__('Visits', 'epic-tracking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($countries as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['country']); ?></td>
                                <td class="ept-col-num"><?php echo esc_html(number_format_i18n($row['visits'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</div>
