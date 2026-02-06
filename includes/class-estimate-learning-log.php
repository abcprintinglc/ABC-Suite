<?php

class ABC_Estimate_Learning_Log {
    public function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        $estimates = get_posts([
            'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
            'posts_per_page' => 200,
            'post_status' => 'any',
        ]);

        $rows = [];
        foreach ($estimates as $estimate) {
            $line_items_json = get_post_meta($estimate->ID, 'abc_estimate_data', true) ?: get_post_meta($estimate->ID, 'abc_line_items_json', true);
            $line_items = json_decode((string) $line_items_json, true);
            if (!is_array($line_items)) {
                continue;
            }
            foreach ($line_items as $item) {
                $template_id = (int) ($item['template_id'] ?? 0);
                $custom_name = (string) ($item['custom_product_name'] ?? '');
                if ($template_id || $custom_name === '') {
                    continue;
                }
                $key = strtolower(trim($custom_name));
                if ($key === '') {
                    continue;
                }
                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'name' => $custom_name,
                        'count' => 0,
                        'last_estimate' => $estimate->ID,
                        'last_cost' => 0,
                        'last_sell' => 0,
                    ];
                }
                $rows[$key]['count']++;
                $rows[$key]['last_estimate'] = $estimate->ID;
                $rows[$key]['last_cost'] = (float) ($item['cost_snapshot'] ?? 0);
                $rows[$key]['last_sell'] = (float) ($item['sell_price'] ?? 0);
            }
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Estimate Learning Log</h1>
            <p class="description">Items without templates (custom product names) to promote into templates.</p>

            <table class="widefat striped" style="margin-top:12px;">
                <thead>
                    <tr>
                        <th>Custom Product</th>
                        <th>Count</th>
                        <th>Last Cost</th>
                        <th>Last Sell</th>
                        <th>Last Estimate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows) : ?>
                        <?php foreach ($rows as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['name']); ?></td>
                                <td><?php echo esc_html((string) $row['count']); ?></td>
                                <td><?php echo esc_html(number_format($row['last_cost'], 2)); ?></td>
                                <td><?php echo esc_html(number_format($row['last_sell'], 2)); ?></td>
                                <td><a href="<?php echo esc_url(get_edit_post_link($row['last_estimate'], 'raw')); ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="5">No custom products found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
