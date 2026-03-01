<div class="wrap">
    <h1><?php echo esc_html__('Dashboard', 'epic-tracking'); ?></h1>

    <?php
    $baseUrl = admin_url('admin.php?page=epic-tracking');
    if ($filterUrl !== '') {
        $baseUrl = add_query_arg('filter_url', $filterUrl, $baseUrl);
    }
    $requestUri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? ''));
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="ept-date-picker">
        <input type="hidden" name="page" value="epic-tracking">
        <?php if ($filterUrl !== '') : ?>
            <input type="hidden" name="filter_url" value="<?php echo esc_attr($filterUrl); ?>">
        <?php endif; ?>
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

    <div class="ept-stats-row">
        <div class="ept-stat-card">
            <p class="ept-stat-card-label"><span class="dashicons dashicons-visibility"></span> <?php echo esc_html__('Total Visits', 'epic-tracking'); ?></p>
            <p class="ept-stat-card-value"><?php echo number_format_i18n($visitSummary['total_visits']); ?></p>
        </div>
        <div class="ept-stat-card">
            <p class="ept-stat-card-label"><span class="dashicons dashicons-groups"></span> <?php echo esc_html__('Unique Visitors', 'epic-tracking'); ?></p>
            <p class="ept-stat-card-value"><?php echo number_format_i18n($visitSummary['unique_visitors']); ?></p>
        </div>
        <div class="ept-stat-card">
            <p class="ept-stat-card-label"><span class="dashicons dashicons-admin-links"></span> <?php echo esc_html__('Event Triggers', 'epic-tracking'); ?></p>
            <p class="ept-stat-card-value"><?php echo number_format_i18n($eventSummary['total_triggers']); ?></p>
        </div>
        <div class="ept-stat-card">
            <p class="ept-stat-card-label"><span class="dashicons dashicons-admin-users"></span> <?php echo esc_html__('Unique Actors', 'epic-tracking'); ?></p>
            <p class="ept-stat-card-value"><?php echo number_format_i18n($eventSummary['unique_visitors']); ?></p>
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

    <div class="ept-dashboard-grid">
        <div class="ept-section">
            <h2 class="ept-section-title"><span class="dashicons dashicons-visibility"></span> <?php echo esc_html__('Page Visits', 'epic-tracking'); ?></h2>
            <?php if (empty($visitStats)) : ?>
                <div class="ept-empty-state">
                    <span class="dashicons dashicons-visibility"></span>
                    <p><?php echo esc_html__('No visit data for this period.', 'epic-tracking'); ?></p>
                </div>
            <?php else : ?>
                <table class="ept-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Page', 'epic-tracking'); ?></th>
                            <th class="ept-col-num"><?php echo esc_html__('Total Visits', 'epic-tracking'); ?></th>
                            <th class="ept-col-num"><?php echo esc_html__('Unique Visitors', 'epic-tracking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visitStats as $row) :
                            $detailUrl = esc_url(admin_url('admin.php?' . http_build_query([
                                'page'      => 'epic-tracking-page-detail',
                                'page_url'  => $row['page_url'],
                                'date_from' => $dateFrom,
                                'date_to'   => $dateTo,
                            ])));
                        ?>
                            <tr>
                                <td><a href="<?php echo $detailUrl; ?>"><?php echo esc_html($row['page_url']); ?></a></td>
                                <td class="ept-col-num"><?php echo esc_html(number_format_i18n($row['total_visits'])); ?></td>
                                <td class="ept-col-num"><?php echo esc_html(number_format_i18n($row['unique_visitors'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($visitTotalPages > 1) : ?>
                    <div class="ept-pagination">
                        <?php
                        echo paginate_links([
                            'base'      => add_query_arg('vpage', '%#%', $requestUri),
                            'format'    => '',
                            'current'   => $visitPage,
                            'total'     => $visitTotalPages,
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                        ]);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="ept-section" id="ept-events-section">
            <h2 class="ept-section-title"><span class="dashicons dashicons-admin-links"></span> <?php echo esc_html__('Events', 'epic-tracking'); ?></h2>
            <?php if ($filterUrl !== '') : ?>
                <div class="ept-active-filter">
                    <?php
                    /* translators: %s: filtered URL */
                    printf(esc_html__('Filtered by: %s', 'epic-tracking'), '<strong>' . esc_html($filterUrl) . '</strong>');
                    ?>
                    <a href="<?php echo esc_url(add_query_arg(['filter_url' => false, 'epage' => false], $requestUri)); ?>" class="ept-clear-filter"><?php echo esc_html__('Clear filter', 'epic-tracking'); ?></a>
                </div>
            <?php endif; ?>
            <?php if (empty($eventStats)) : ?>
                <div class="ept-empty-state">
                    <span class="dashicons dashicons-admin-links"></span>
                    <p><?php echo esc_html__('No event data for this period.', 'epic-tracking'); ?></p>
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
                        <?php foreach ($eventStats as $row) : ?>
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
                <?php if ($eventTotalPages > 1) : ?>
                    <div class="ept-pagination">
                        <?php
                        echo paginate_links([
                            'base'      => add_query_arg('epage', '%#%', $requestUri),
                            'format'    => '',
                            'current'   => $eventPage,
                            'total'     => $eventTotalPages,
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                        ]);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="ept-section" style="margin-top: 20px;">
        <h2 class="ept-section-title"><span class="dashicons dashicons-location"></span> <?php echo esc_html__('Top Countries', 'epic-tracking'); ?></h2>
        <?php if (empty($topCountries)) : ?>
            <div class="ept-empty-state">
                <span class="dashicons dashicons-location"></span>
                <p><?php echo esc_html__('No country data for this period.', 'epic-tracking'); ?></p>
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
                    <?php foreach ($topCountries as $row) : ?>
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
