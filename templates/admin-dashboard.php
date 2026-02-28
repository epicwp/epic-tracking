<div class="wrap">
    <h1>Epic Tracking</h1>

    <div class="ept-date-filter">
        <a href="<?php echo esc_url(add_query_arg('range', '1')); ?>"
           class="button <?php echo $range === '1' ? 'button-primary' : ''; ?>">Today</a>
        <a href="<?php echo esc_url(add_query_arg('range', '7')); ?>"
           class="button <?php echo $range === '7' ? 'button-primary' : ''; ?>">Last 7 days</a>
        <a href="<?php echo esc_url(add_query_arg('range', '30')); ?>"
           class="button <?php echo $range === '30' ? 'button-primary' : ''; ?>">Last 30 days</a>
    </div>

    <div class="ept-dashboard-grid">
        <div class="postbox">
            <h2 class="hndle">Page Visits</h2>
            <div class="inside">
                <?php if (empty($visitStats)) : ?>
                    <p>No visit data for this period.</p>
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
                            <?php foreach ($visitStats as $row) : ?>
                                <tr>
                                    <td><?php echo esc_html($row['page_url']); ?></td>
                                    <td><?php echo esc_html($row['total_visits']); ?></td>
                                    <td><?php echo esc_html($row['unique_visitors']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle">Click Events</h2>
            <div class="inside">
                <?php if (empty($eventStats)) : ?>
                    <p>No event data for this period.</p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Event Tag</th>
                                <th>Page</th>
                                <th>Total Clicks</th>
                                <th>Unique Clickers</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventStats as $row) : ?>
                                <tr>
                                    <td><?php echo esc_html($row['reference_name']); ?></td>
                                    <td><code><?php echo esc_html($row['event_tag']); ?></code></td>
                                    <td><?php echo esc_html($row['page_url']); ?></td>
                                    <td><?php echo esc_html($row['total_clicks']); ?></td>
                                    <td><?php echo esc_html($row['unique_clickers']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
