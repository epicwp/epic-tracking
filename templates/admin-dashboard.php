<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template included in function scope
?>
<div class="wrap">
    <h1><?php echo esc_html__('Dashboard', 'epic-tracking'); ?></h1>

    <?php
    $baseUrl = admin_url('admin.php?page=epic-tracking');
    if ($filterUrl !== '') {
        $baseUrl = add_query_arg('filter_url', $filterUrl, $baseUrl);
    }
    $requestUri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? ''));
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="epictr-date-picker">
        <input type="hidden" name="page" value="epic-tracking">
        <?php if ($filterUrl !== '') : ?>
            <input type="hidden" name="filter_url" value="<?php echo esc_attr($filterUrl); ?>">
        <?php endif; ?>
        <input type="hidden" name="date_from" value="<?php echo esc_attr($dateFrom); ?>">
        <input type="hidden" name="date_to" value="<?php echo esc_attr($dateTo); ?>">

        <button type="button" class="epictr-date-picker__trigger">
            <span class="dashicons dashicons-calendar-alt"></span>
            <span class="epictr-date-picker__label"></span>
            <span class="dashicons dashicons-arrow-down-alt2"></span>
        </button>

        <div class="epictr-date-picker__dropdown">
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
                <button type="button" class="epictr-date-picker__preset"
                    data-label="<?php echo esc_attr($preset['label']); ?>"
                    data-from="<?php echo esc_attr($preset['from']); ?>"
                    data-to="<?php echo esc_attr($preset['to']); ?>">
                    <?php echo esc_html($preset['label']); ?>
                    <span class="dashicons dashicons-yes-alt epictr-date-picker__check"></span>
                </button>
            <?php endforeach; ?>

            <hr class="epictr-date-picker__separator">

            <button type="button" class="epictr-date-picker__preset" data-preset="custom" data-label="<?php echo esc_attr__('Custom range', 'epic-tracking'); ?>">
                <?php echo esc_html__('Custom range', 'epic-tracking'); ?>
                <span class="dashicons dashicons-yes-alt epictr-date-picker__check"></span>
            </button>

            <div class="epictr-date-picker__custom">
                <div class="epictr-date-picker__custom-fields">
                    <input type="date" class="epictr-date-picker__custom-from" value="<?php echo esc_attr($dateFrom); ?>" max="<?php echo esc_attr(gmdate('Y-m-d')); ?>">
                    <input type="date" class="epictr-date-picker__custom-to" value="<?php echo esc_attr($dateTo); ?>" max="<?php echo esc_attr(gmdate('Y-m-d')); ?>">
                </div>
                <button type="button" class="button button-primary epictr-date-picker__apply"><?php echo esc_html__('Apply', 'epic-tracking'); ?></button>
            </div>
        </div>
    </form>

    <div class="epictr-stats-row">
        <div class="epictr-stat-card">
            <p class="epictr-stat-card-label"><span class="dashicons dashicons-visibility"></span> <?php echo esc_html__('Total Visits', 'epic-tracking'); ?></p>
            <p class="epictr-stat-card-value"><?php echo esc_html(number_format_i18n($visitSummary['total_visits'])); ?></p>
        </div>
        <div class="epictr-stat-card">
            <p class="epictr-stat-card-label"><span class="dashicons dashicons-groups"></span> <?php echo esc_html__('Unique Visitors', 'epic-tracking'); ?></p>
            <p class="epictr-stat-card-value"><?php echo esc_html(number_format_i18n($visitSummary['unique_visitors'])); ?></p>
        </div>
        <div class="epictr-stat-card">
            <p class="epictr-stat-card-label"><span class="dashicons dashicons-admin-links"></span> <?php echo esc_html__('Event Triggers', 'epic-tracking'); ?></p>
            <p class="epictr-stat-card-value"><?php echo esc_html(number_format_i18n($eventSummary['total_triggers'])); ?></p>
        </div>
        <div class="epictr-stat-card">
            <p class="epictr-stat-card-label"><span class="dashicons dashicons-admin-users"></span> <?php echo esc_html__('Unique Actors', 'epic-tracking'); ?></p>
            <p class="epictr-stat-card-value"><?php echo esc_html(number_format_i18n($eventSummary['unique_visitors'])); ?></p>
        </div>
    </div>

    <div class="epictr-section" style="margin-bottom: 20px;">
        <h2 class="epictr-section-title"><span class="dashicons dashicons-chart-area"></span> <?php echo esc_html__('Traffic Overview', 'epic-tracking'); ?></h2>
        <?php if (empty($dailyVisits)) : ?>
            <div class="epictr-empty-state">
                <span class="dashicons dashicons-chart-area"></span>
                <p><?php echo esc_html__('No daily data for this period.', 'epic-tracking'); ?></p>
            </div>
        <?php else : ?>
            <div class="epictr-chart" data-chart="<?php echo esc_attr(wp_json_encode($dailyVisits)); ?>">
                <canvas></canvas>
            </div>
        <?php endif; ?>
    </div>

    <div class="epictr-dashboard-grid">
        <div class="epictr-section">
            <h2 class="epictr-section-title">
                <span class="dashicons dashicons-visibility"></span> <?php echo esc_html__('Page Visits', 'epic-tracking'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?' . http_build_query(['page' => 'epic-tracking-all-visits', 'date_from' => $dateFrom, 'date_to' => $dateTo]))); ?>" class="epictr-view-all"><?php echo esc_html__('View all', 'epic-tracking'); ?> &rarr;</a>
            </h2>
            <?php if (empty($visitStats)) : ?>
                <div class="epictr-empty-state">
                    <span class="dashicons dashicons-visibility"></span>
                    <p><?php echo esc_html__('No visit data for this period.', 'epic-tracking'); ?></p>
                </div>
            <?php else : ?>
                <?php
                $visitSortColumns = [
                    'page_url'        => __('Page', 'epic-tracking'),
                    'total_visits'    => __('Total Visits', 'epic-tracking'),
                    'unique_visitors' => __('Unique Visitors', 'epic-tracking'),
                ];
                ?>
                <table class="epictr-table">
                    <thead>
                        <tr>
                            <?php foreach ($visitSortColumns as $col => $label) :
                                $isActive   = ($visitSort === $col);
                                $nextOrder  = $isActive && $visitOrder === 'DESC' ? 'ASC' : 'DESC';
                                $sortUrl    = add_query_arg(['vsort' => $col, 'vorder' => $nextOrder, 'vpage' => 1], $requestUri);
                                $classes    = 'epictr-sortable';
                                if ($col !== 'page_url') {
                                    $classes .= ' epictr-col-num';
                                }
                                if ($isActive) {
                                    $classes .= $visitOrder === 'ASC' ? ' epictr-sort-asc' : ' epictr-sort-desc';
                                }
                            ?>
                                <th class="<?php echo esc_attr($classes); ?>"><a href="<?php echo esc_url($sortUrl); ?>"><?php echo esc_html($label); ?></a></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visitStats as $row) :
                            $detailUrl = admin_url('admin.php?' . http_build_query([
                                'page'      => 'epic-tracking-page-detail',
                                'page_url'  => $row['page_url'],
                                'date_from' => $dateFrom,
                                'date_to'   => $dateTo,
                            ]));
                        ?>
                            <tr>
                                <td><a href="<?php echo esc_url($detailUrl); ?>"><?php echo esc_html($row['page_url']); ?></a></td>
                                <td class="epictr-col-num"><?php echo esc_html(number_format_i18n($row['total_visits'])); ?></td>
                                <td class="epictr-col-num"><?php echo esc_html(number_format_i18n($row['unique_visitors'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($visitTotalPages > 1) : ?>
                    <div class="epictr-pagination">
                        <?php
                        echo wp_kses_post(paginate_links([
                            'base'      => add_query_arg('vpage', '%#%', $requestUri),
                            'format'    => '',
                            'current'   => $visitPage,
                            'total'     => $visitTotalPages,
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                        ]));
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="epictr-section" id="epictr-events-section">
            <h2 class="epictr-section-title">
                <span class="dashicons dashicons-admin-links"></span> <?php echo esc_html__('Events', 'epic-tracking'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?' . http_build_query(['page' => 'epic-tracking-all-events', 'date_from' => $dateFrom, 'date_to' => $dateTo]))); ?>" class="epictr-view-all"><?php echo esc_html__('View all', 'epic-tracking'); ?> &rarr;</a>
            </h2>
            <?php if ($filterUrl !== '') : ?>
                <div class="epictr-active-filter">
                    <?php
                    /* translators: %s: filtered URL */
                    printf(esc_html__('Filtered by: %s', 'epic-tracking'), '<strong>' . esc_html($filterUrl) . '</strong>');
                    ?>
                    <a href="<?php echo esc_url(add_query_arg(['filter_url' => false, 'epage' => false], $requestUri)); ?>" class="epictr-clear-filter"><?php echo esc_html__('Clear filter', 'epic-tracking'); ?></a>
                </div>
            <?php endif; ?>
            <?php if (empty($eventStats)) : ?>
                <div class="epictr-empty-state">
                    <span class="dashicons dashicons-admin-links"></span>
                    <p><?php echo esc_html__('No event data for this period.', 'epic-tracking'); ?></p>
                </div>
            <?php else : ?>
                <?php
                $eventSortColumns = [
                    'reference_name'  => __('Reference', 'epic-tracking'),
                    'event_tag'       => __('Event Tag', 'epic-tracking'),
                    'total_triggers'  => __('Triggers', 'epic-tracking'),
                    'unique_visitors' => __('Unique Visitors', 'epic-tracking'),
                ];
                ?>
                <table class="epictr-table">
                    <thead>
                        <tr>
                            <?php foreach ($eventSortColumns as $col => $label) :
                                $isActive   = ($eventSort === $col);
                                $nextOrder  = $isActive && $eventOrder === 'DESC' ? 'ASC' : 'DESC';
                                $sortUrl    = add_query_arg(['esort' => $col, 'eorder' => $nextOrder, 'epage' => 1], $requestUri);
                                $classes    = 'epictr-sortable';
                                if (in_array($col, ['total_triggers', 'unique_visitors'], true)) {
                                    $classes .= ' epictr-col-num';
                                }
                                if ($isActive) {
                                    $classes .= $eventOrder === 'ASC' ? ' epictr-sort-asc' : ' epictr-sort-desc';
                                }
                            ?>
                                <th class="<?php echo esc_attr($classes); ?>"><a href="<?php echo esc_url($sortUrl); ?>"><?php echo esc_html($label); ?></a></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventStats as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['reference_name']); ?></td>
                                <td><code><?php echo esc_html($row['event_tag']); ?></code></td>
                                <td class="epictr-col-num"><?php echo esc_html(number_format_i18n($row['total_triggers'])); ?></td>
                                <td class="epictr-col-num"><?php echo esc_html(number_format_i18n($row['unique_visitors'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($eventTotalPages > 1) : ?>
                    <div class="epictr-pagination">
                        <?php
                        echo wp_kses_post(paginate_links([
                            'base'      => add_query_arg('epage', '%#%', $requestUri),
                            'format'    => '',
                            'current'   => $eventPage,
                            'total'     => $eventTotalPages,
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                        ]));
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="epictr-section" style="margin-top: 20px;">
        <h2 class="epictr-section-title"><span class="dashicons dashicons-location"></span> <?php echo esc_html__('Top Countries', 'epic-tracking'); ?></h2>
        <?php if (empty($topCountries)) : ?>
            <div class="epictr-empty-state">
                <span class="dashicons dashicons-location"></span>
                <p><?php echo esc_html__('No country data for this period.', 'epic-tracking'); ?></p>
            </div>
        <?php else : ?>
            <table class="epictr-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Country', 'epic-tracking'); ?></th>
                        <th class="epictr-col-num"><?php echo esc_html__('Visits', 'epic-tracking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topCountries as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row['country']); ?></td>
                            <td class="epictr-col-num"><?php echo esc_html(number_format_i18n($row['visits'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php // phpcs:enable ?>
