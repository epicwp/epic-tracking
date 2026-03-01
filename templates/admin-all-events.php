<div class="wrap">
    <div class="ept-page-detail-header">
        <a href="<?php echo esc_url(admin_url('admin.php?' . http_build_query(['page' => 'epic-tracking', 'date_from' => $dateFrom, 'date_to' => $dateTo]))); ?>" class="ept-back-link">&larr; <?php echo esc_html__('Back to Dashboard', 'epic-tracking'); ?></a>
        <h1><?php echo esc_html__('All Events', 'epic-tracking'); ?></h1>
    </div>

    <?php $requestUri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? '')); ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="ept-date-picker">
        <input type="hidden" name="page" value="epic-tracking-all-events">
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

    <div class="ept-section">
        <h2 class="ept-section-title"><span class="dashicons dashicons-admin-links"></span> <?php echo esc_html__('Events', 'epic-tracking'); ?></h2>
        <?php if (empty($eventStats)) : ?>
            <div class="ept-empty-state">
                <span class="dashicons dashicons-admin-links"></span>
                <p><?php echo esc_html__('No event data for this period.', 'epic-tracking'); ?></p>
            </div>
        <?php else : ?>
            <?php
            $sortColumns = [
                'reference_name'  => __('Reference', 'epic-tracking'),
                'event_tag'       => __('Event Tag', 'epic-tracking'),
                'total_triggers'  => __('Triggers', 'epic-tracking'),
                'unique_visitors' => __('Unique Visitors', 'epic-tracking'),
            ];
            ?>
            <table class="ept-table">
                <thead>
                    <tr>
                        <?php foreach ($sortColumns as $col => $label) :
                            $isActive   = ($sortBy === $col);
                            $nextOrder  = $isActive && $sortOrder === 'DESC' ? 'ASC' : 'DESC';
                            $sortUrl    = add_query_arg(['esort' => $col, 'eorder' => $nextOrder, 'paged' => 1], $requestUri);
                            $classes    = 'ept-sortable';
                            if (in_array($col, ['total_triggers', 'unique_visitors'], true)) {
                                $classes .= ' ept-col-num';
                            }
                            if ($isActive) {
                                $classes .= $sortOrder === 'ASC' ? ' ept-sort-asc' : ' ept-sort-desc';
                            }
                        ?>
                            <th class="<?php echo esc_attr($classes); ?>"><a href="<?php echo esc_url($sortUrl); ?>"><?php echo esc_html($label); ?></a></th>
                        <?php endforeach; ?>
                        <th><?php echo esc_html__('Page', 'epic-tracking'); ?></th>
                        <th><?php echo esc_html__('Type', 'epic-tracking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventStats as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row['reference_name']); ?></td>
                            <td><code><?php echo esc_html($row['event_tag']); ?></code></td>
                            <td class="ept-col-num"><?php echo esc_html(number_format_i18n($row['total_triggers'])); ?></td>
                            <td class="ept-col-num"><?php echo esc_html(number_format_i18n($row['unique_visitors'])); ?></td>
                            <td><?php echo esc_html($row['page_url']); ?></td>
                            <td><span class="ept-badge"><?php echo esc_html($row['event_type']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($totalPages > 1) : ?>
                <div class="ept-pagination">
                    <?php
                    echo paginate_links([
                        'base'      => add_query_arg('paged', '%#%', $requestUri),
                        'format'    => '',
                        'current'   => $currentPage,
                        'total'     => $totalPages,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
