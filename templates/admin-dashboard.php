<div class="wrap">
    <h1>Tracking</h1>

    <div class="ept-date-filter">
        <a href="<?php echo esc_url(add_query_arg(['range' => '1', 'vpage' => false, 'epage' => false])); ?>"
           class="button <?php echo $range === '1' ? 'button-primary' : ''; ?>">Today</a>
        <a href="<?php echo esc_url(add_query_arg(['range' => '7', 'vpage' => false, 'epage' => false])); ?>"
           class="button <?php echo $range === '7' ? 'button-primary' : ''; ?>">Last 7 days</a>
        <a href="<?php echo esc_url(add_query_arg(['range' => '30', 'vpage' => false, 'epage' => false])); ?>"
           class="button <?php echo $range === '30' ? 'button-primary' : ''; ?>">Last 30 days</a>
    </div>

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

    <div class="ept-dashboard-grid">
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-visibility"></span> Page Visits</h2>
            <div class="inside">
                <?php if (empty($visitStats)) : ?>
                    <div class="ept-empty-state">
                        <span class="dashicons dashicons-visibility"></span>
                        <p>No visit data for this period.</p>
                    </div>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th>Total Visits</th>
                                <th>Unique Visitors</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visitStats as $row) :
                                $eventsUrl = esc_url(add_query_arg(['filter_url' => $row['page_url'], 'epage' => false]));
                            ?>
                                <tr>
                                    <td><a href="<?php echo $eventsUrl; ?>#ept-events-section"><?php echo esc_html($row['page_url']); ?></a></td>
                                    <td><?php echo esc_html(number_format_i18n($row['total_visits'])); ?></td>
                                    <td><?php echo esc_html(number_format_i18n($row['unique_visitors'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($visitTotalPages > 1) : ?>
                        <div class="ept-pagination">
                            <?php
                            echo paginate_links([
                                'base'      => add_query_arg('vpage', '%#%'),
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
        </div>

        <div class="postbox" id="ept-events-section">
            <h2 class="hndle"><span class="dashicons dashicons-admin-links"></span> Events</h2>
            <div class="inside">
                <?php if ($filterUrl !== '') : ?>
                    <div class="ept-active-filter">
                        <span>Filtered by: <strong><?php echo esc_html($filterUrl); ?></strong></span>
                        <a href="<?php echo esc_url(add_query_arg(['filter_url' => false, 'epage' => false])); ?>" class="ept-clear-filter">Clear filter</a>
                    </div>
                <?php endif; ?>
                <?php if (empty($eventStats)) : ?>
                    <div class="ept-empty-state">
                        <span class="dashicons dashicons-admin-links"></span>
                        <p>No event data for this period.</p>
                    </div>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Event Tag</th>
                                <th>Type</th>
                                <th>Page</th>
                                <th>Triggers</th>
                                <th>Unique Visitors</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventStats as $row) : ?>
                                <tr>
                                    <td><?php echo esc_html($row['reference_name']); ?></td>
                                    <td><code><?php echo esc_html($row['event_tag']); ?></code></td>
                                    <td><?php echo esc_html($row['event_type']); ?></td>
                                    <td><?php echo esc_html($row['page_url']); ?></td>
                                    <td><?php echo esc_html(number_format_i18n($row['total_triggers'])); ?></td>
                                    <td><?php echo esc_html(number_format_i18n($row['unique_visitors'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($eventTotalPages > 1) : ?>
                        <div class="ept-pagination">
                            <?php
                            echo paginate_links([
                                'base'      => add_query_arg('epage', '%#%'),
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
</div>
