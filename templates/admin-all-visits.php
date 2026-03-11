<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template included in function scope
?>
<div class="wrap">
    <div class="epictr-page-detail-header">
        <a href="<?php echo esc_url(admin_url('admin.php?' . http_build_query(['page' => 'epic-tracking', 'date_from' => $dateFrom, 'date_to' => $dateTo]))); ?>" class="epictr-back-link">&larr; <?php echo esc_html__('Back to Dashboard', 'epic-tracking'); ?></a>
        <h1><?php echo esc_html__('All Page Visits', 'epic-tracking'); ?></h1>
    </div>

    <?php $requestUri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? '')); ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="epictr-date-picker">
        <input type="hidden" name="page" value="epic-tracking-all-visits">
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

    <div class="epictr-section">
        <h2 class="epictr-section-title"><span class="dashicons dashicons-visibility"></span> <?php echo esc_html__('Page Visits', 'epic-tracking'); ?></h2>
        <?php if (empty($visitStats)) : ?>
            <div class="epictr-empty-state">
                <span class="dashicons dashicons-visibility"></span>
                <p><?php echo esc_html__('No visit data for this period.', 'epic-tracking'); ?></p>
            </div>
        <?php else : ?>
            <?php
            $sortColumns = [
                'page_url'        => __('Page', 'epic-tracking'),
                'total_visits'    => __('Total Visits', 'epic-tracking'),
                'unique_visitors' => __('Unique Visitors', 'epic-tracking'),
            ];
            ?>
            <table class="epictr-table">
                <thead>
                    <tr>
                        <?php foreach ($sortColumns as $col => $label) :
                            $isActive   = ($sortBy === $col);
                            $nextOrder  = $isActive && $sortOrder === 'DESC' ? 'ASC' : 'DESC';
                            $sortUrl    = add_query_arg(['vsort' => $col, 'vorder' => $nextOrder, 'paged' => 1], $requestUri);
                            $classes    = 'epictr-sortable';
                            if ($col !== 'page_url') {
                                $classes .= ' epictr-col-num';
                            }
                            if ($isActive) {
                                $classes .= $sortOrder === 'ASC' ? ' epictr-sort-asc' : ' epictr-sort-desc';
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
            <?php if ($totalPages > 1) : ?>
                <div class="epictr-pagination">
                    <?php
                    echo wp_kses_post(paginate_links([
                        'base'      => add_query_arg('paged', '%#%', $requestUri),
                        'format'    => '',
                        'current'   => $currentPage,
                        'total'     => $totalPages,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                    ]));
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php // phpcs:enable ?>
