<div class="wrap">
    <h1>Tracking</h1>

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

    <div class="ept-stats-row">
        <div class="ept-stat-card">
            <p class="ept-stat-card-label"><span class="dashicons dashicons-visibility"></span> Total Visits</p>
            <p class="ept-stat-card-value"><?php echo number_format_i18n($visitSummary['total_visits']); ?></p>
        </div>
        <div class="ept-stat-card">
            <p class="ept-stat-card-label"><span class="dashicons dashicons-groups"></span> Unique Visitors</p>
            <p class="ept-stat-card-value"><?php echo number_format_i18n($visitSummary['unique_visitors']); ?></p>
        </div>
        <div class="ept-stat-card">
            <p class="ept-stat-card-label"><span class="dashicons dashicons-admin-links"></span> Event Triggers</p>
            <p class="ept-stat-card-value"><?php echo number_format_i18n($eventSummary['total_triggers']); ?></p>
        </div>
        <div class="ept-stat-card">
            <p class="ept-stat-card-label"><span class="dashicons dashicons-admin-users"></span> Unique Actors</p>
            <p class="ept-stat-card-value"><?php echo number_format_i18n($eventSummary['unique_visitors']); ?></p>
        </div>
    </div>

    <div class="ept-section" style="margin-bottom: 20px;">
        <h2 class="ept-section-title"><span class="dashicons dashicons-chart-area"></span> Traffic Overview</h2>
        <?php if (empty($dailyVisits)) : ?>
            <div class="ept-empty-state">
                <span class="dashicons dashicons-chart-area"></span>
                <p>No daily data for this period.</p>
            </div>
        <?php else : ?>
            <div class="ept-chart" data-chart="<?php echo esc_attr(wp_json_encode($dailyVisits)); ?>">
                <canvas></canvas>
            </div>
        <?php endif; ?>
    </div>

    <div class="ept-dashboard-grid">
        <div class="ept-section">
            <h2 class="ept-section-title"><span class="dashicons dashicons-visibility"></span> Page Visits</h2>
            <?php if (empty($visitStats)) : ?>
                <div class="ept-empty-state">
                    <span class="dashicons dashicons-visibility"></span>
                    <p>No visit data for this period.</p>
                </div>
            <?php else : ?>
                <table class="ept-table">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th class="ept-col-num">Total Visits</th>
                            <th class="ept-col-num">Unique Visitors</th>
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
            <h2 class="ept-section-title"><span class="dashicons dashicons-admin-links"></span> Events</h2>
            <?php if ($filterUrl !== '') : ?>
                <div class="ept-active-filter">
                    <span>Filtered by: <strong><?php echo esc_html($filterUrl); ?></strong></span>
                    <a href="<?php echo esc_url(add_query_arg(['filter_url' => false, 'epage' => false], $requestUri)); ?>" class="ept-clear-filter">Clear filter</a>
                </div>
            <?php endif; ?>
            <?php if (empty($eventStats)) : ?>
                <div class="ept-empty-state">
                    <span class="dashicons dashicons-admin-links"></span>
                    <p>No event data for this period.</p>
                </div>
            <?php else : ?>
                <table class="ept-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Event Tag</th>
                            <th>Type</th>
                            <th>Page</th>
                            <th class="ept-col-num">Triggers</th>
                            <th class="ept-col-num">Unique Visitors</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventStats as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['reference_name']); ?></td>
                                <td><code><?php echo esc_html($row['event_tag']); ?></code></td>
                                <td><span class="ept-badge"><?php echo esc_html($row['event_type']); ?></span></td>
                                <td><a href="<?php echo esc_url(add_query_arg(['filter_url' => $row['page_url'], 'epage' => false], $requestUri)); ?>#ept-events-section"><?php echo esc_html($row['page_url']); ?></a></td>
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
</div>
