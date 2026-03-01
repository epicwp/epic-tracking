<div class="wrap">
    <div class="ept-page-detail-header">
        <a href="<?php echo esc_url(admin_url('admin.php?' . http_build_query(['page' => 'epic-tracking', 'date_from' => $dateFrom, 'date_to' => $dateTo]))); ?>" class="ept-back-link">&larr; Back to Dashboard</a>
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
                ['label' => 'Today',        'from' => gmdate('Y-m-d'),                                        'to' => gmdate('Y-m-d')],
                ['label' => 'Yesterday',     'from' => gmdate('Y-m-d', strtotime('-1 day')),                   'to' => gmdate('Y-m-d', strtotime('-1 day'))],
                ['label' => 'Last 7 days',   'from' => gmdate('Y-m-d', strtotime('-6 days')),                  'to' => gmdate('Y-m-d')],
                ['label' => 'Last 30 days',  'from' => gmdate('Y-m-d', strtotime('-29 days')),                 'to' => gmdate('Y-m-d')],
                ['label' => 'This month',    'from' => gmdate('Y-m-01'),                                      'to' => gmdate('Y-m-d')],
                ['label' => 'Last month',    'from' => gmdate('Y-m-01', strtotime('first day of last month')), 'to' => gmdate('Y-m-t', strtotime('last month'))],
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

            <button type="button" class="ept-date-picker__preset" data-preset="custom" data-label="Custom range">
                Custom range
                <span class="dashicons dashicons-yes-alt ept-date-picker__check"></span>
            </button>

            <div class="ept-date-picker__custom">
                <div class="ept-date-picker__custom-fields">
                    <input type="date" class="ept-date-picker__custom-from" value="<?php echo esc_attr($dateFrom); ?>" max="<?php echo esc_attr(gmdate('Y-m-d')); ?>">
                    <input type="date" class="ept-date-picker__custom-to" value="<?php echo esc_attr($dateTo); ?>" max="<?php echo esc_attr(gmdate('Y-m-d')); ?>">
                </div>
                <button type="button" class="button button-primary ept-date-picker__apply">Apply</button>
            </div>
        </div>
    </form>

    <div class="ept-stats-row ept-stats-row--2col">
        <div class="ept-stat-card">
            <p class="ept-stat-card-label"><span class="dashicons dashicons-visibility"></span> Total Visits</p>
            <p class="ept-stat-card-value"><?php echo number_format_i18n($summary['total_visits']); ?></p>
        </div>
        <div class="ept-stat-card">
            <p class="ept-stat-card-label"><span class="dashicons dashicons-groups"></span> Unique Visitors</p>
            <p class="ept-stat-card-value"><?php echo number_format_i18n($summary['unique_visitors']); ?></p>
        </div>
    </div>

    <div class="ept-section" style="margin-bottom: 20px;">
        <h2 class="ept-section-title"><span class="dashicons dashicons-calendar-alt"></span> Daily Visits</h2>
        <?php if (empty($dailyVisits)) : ?>
            <div class="ept-empty-state">
                <span class="dashicons dashicons-calendar-alt"></span>
                <p>No daily data for this period.</p>
            </div>
        <?php else : ?>
            <table class="ept-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="ept-col-num">Total Visits</th>
                        <th class="ept-col-num">Unique Visitors</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dailyVisits as $day) : ?>
                        <tr>
                            <td><?php echo esc_html(gmdate('M j, Y', strtotime($day['visit_date']))); ?></td>
                            <td class="ept-col-num"><?php echo esc_html(number_format_i18n($day['total_visits'])); ?></td>
                            <td class="ept-col-num"><?php echo esc_html(number_format_i18n($day['unique_visitors'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="ept-section" style="margin-bottom: 20px;">
        <h2 class="ept-section-title"><span class="dashicons dashicons-admin-links"></span> Top Referrers</h2>
        <?php if (empty($referrers)) : ?>
            <div class="ept-empty-state">
                <span class="dashicons dashicons-admin-links"></span>
                <p>No referrer data for this period.</p>
            </div>
        <?php else : ?>
            <table class="ept-table">
                <thead>
                    <tr>
                        <th>Referrer</th>
                        <th class="ept-col-num">Visits</th>
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
            <h2 class="ept-section-title"><span class="dashicons dashicons-smartphone"></span> Devices</h2>
            <?php if (empty($devices)) : ?>
                <div class="ept-empty-state">
                    <span class="dashicons dashicons-smartphone"></span>
                    <p>No device data.</p>
                </div>
            <?php else : ?>
                <table class="ept-table">
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th class="ept-col-num">Visits</th>
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
            <h2 class="ept-section-title"><span class="dashicons dashicons-admin-site-alt3"></span> Browsers</h2>
            <?php if (empty($browsers)) : ?>
                <div class="ept-empty-state">
                    <span class="dashicons dashicons-admin-site-alt3"></span>
                    <p>No browser data.</p>
                </div>
            <?php else : ?>
                <table class="ept-table">
                    <thead>
                        <tr>
                            <th>Browser</th>
                            <th class="ept-col-num">Visits</th>
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
            <h2 class="ept-section-title"><span class="dashicons dashicons-desktop"></span> Operating Systems</h2>
            <?php if (empty($osList)) : ?>
                <div class="ept-empty-state">
                    <span class="dashicons dashicons-desktop"></span>
                    <p>No OS data.</p>
                </div>
            <?php else : ?>
                <table class="ept-table">
                    <thead>
                        <tr>
                            <th>OS</th>
                            <th class="ept-col-num">Visits</th>
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
    </div>

    <div class="ept-section" style="margin-top: 20px;">
        <h2 class="ept-section-title"><span class="dashicons dashicons-admin-links"></span> Events on this Page</h2>
        <?php if (empty($events)) : ?>
            <div class="ept-empty-state">
                <span class="dashicons dashicons-admin-links"></span>
                <p>No events configured for this page.</p>
            </div>
        <?php else : ?>
            <table class="ept-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Event Tag</th>
                        <th>Type</th>
                        <th class="ept-col-num">Triggers</th>
                        <th class="ept-col-num">Unique Visitors</th>
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
</div>
