<?php

class ABC_Payout_Report {
    public function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        $printer_filter = isset($_GET['printer']) ? absint($_GET['printer']) : 0;
        $designer_filter = isset($_GET['designer']) ? absint($_GET['designer']) : 0;
        $date_start = isset($_GET['date_start']) ? sanitize_text_field(wp_unslash($_GET['date_start'])) : '';
        $date_end = isset($_GET['date_end']) ? sanitize_text_field(wp_unslash($_GET['date_end'])) : '';

        $meta_query = [];
        if ($printer_filter) {
            $meta_query[] = [
                'key' => 'abc_printer_tech',
                'value' => (string) $printer_filter,
                'compare' => '=',
            ];
        }
        if ($designer_filter) {
            $meta_query[] = [
                'key' => 'abc_designer',
                'value' => (string) $designer_filter,
                'compare' => '=',
            ];
        }

        $date_query = [];
        if ($date_start !== '') {
            $date_query[] = ['after' => $date_start];
        }
        if ($date_end !== '') {
            $date_query[] = ['before' => $date_end];
        }

        $args = [
            'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
            'posts_per_page' => 200,
            'post_status' => 'any',
        ];
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }
        if (!empty($date_query)) {
            $args['date_query'] = $date_query;
        }

        $posts = get_posts($args);
        $rows = [];
        $total_profit = 0.0;
        $total_printer = 0.0;
        $total_designer = 0.0;

        foreach ($posts as $post) {
            $line_items_json = get_post_meta($post->ID, 'abc_estimate_data', true) ?: get_post_meta($post->ID, 'abc_line_items_json', true);
            $line_items = json_decode((string) $line_items_json, true);
            if (!is_array($line_items)) {
                $line_items = [];
            }

            $profit = 0.0;
            foreach ($line_items as $item) {
                $sell = isset($item['sell_price']) ? (float) $item['sell_price'] : 0.0;
                $cost = isset($item['cost_snapshot']) ? (float) $item['cost_snapshot'] : 0.0;
                $profit += ($sell - $cost);
            }

            $printer_pct = (float) (get_post_meta($post->ID, 'abc_printer_pct', true) ?: 0);
            $designer_pct = (float) (get_post_meta($post->ID, 'abc_designer_pct', true) ?: 0);
            $printer_payout = $profit * ($printer_pct / 100);
            $designer_payout = $profit * ($designer_pct / 100);

            $rows[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'profit' => $profit,
                'printer' => (int) get_post_meta($post->ID, 'abc_printer_tech', true),
                'designer' => (int) get_post_meta($post->ID, 'abc_designer', true),
                'printer_pct' => $printer_pct,
                'designer_pct' => $designer_pct,
                'printer_payout' => $printer_payout,
                'designer_payout' => $designer_payout,
            ];

            $total_profit += $profit;
            $total_printer += $printer_payout;
            $total_designer += $designer_payout;
        }

        $users = get_users(['fields' => ['ID', 'display_name']]);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Payout Report</h1>
            <form method="get" class="abc-matrix-filter" style="margin-top:12px;">
                <input type="hidden" name="post_type" value="<?php echo esc_attr(ABC_CPT_ABC_Estimate::POST_TYPE); ?>">
                <input type="hidden" name="page" value="abc-payout-report">
                <label>Printer Tech
                    <select name="printer">
                        <option value="">All</option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($printer_filter, $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Designer
                    <select name="designer">
                        <option value="">All</option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($designer_filter, $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Start
                    <input type="date" name="date_start" value="<?php echo esc_attr($date_start); ?>">
                </label>
                <label>End
                    <input type="date" name="date_end" value="<?php echo esc_attr($date_end); ?>">
                </label>
                <?php submit_button('Filter', 'secondary', '', false); ?>
            </form>

            <table class="widefat striped" style="margin-top:12px;">
                <thead>
                    <tr>
                        <th>Estimate</th>
                        <th>Profit</th>
                        <th>Printer Tech</th>
                        <th>Printer %</th>
                        <th>Printer Payout</th>
                        <th>Designer</th>
                        <th>Designer %</th>
                        <th>Designer Payout</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows) : ?>
                        <?php foreach ($rows as $row) : ?>
                            <tr>
                                <td><a href="<?php echo esc_url(get_edit_post_link($row['id'], 'raw')); ?>"><?php echo esc_html($row['title']); ?></a></td>
                                <td><?php echo esc_html(number_format($row['profit'], 2)); ?></td>
                                <td><?php echo esc_html($row['printer'] ? get_the_author_meta('display_name', $row['printer']) : ''); ?></td>
                                <td><?php echo esc_html(number_format($row['printer_pct'], 2)); ?>%</td>
                                <td><?php echo esc_html(number_format($row['printer_payout'], 2)); ?></td>
                                <td><?php echo esc_html($row['designer'] ? get_the_author_meta('display_name', $row['designer']) : ''); ?></td>
                                <td><?php echo esc_html(number_format($row['designer_pct'], 2)); ?>%</td>
                                <td><?php echo esc_html(number_format($row['designer_payout'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="8">No estimates found.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th><?php echo esc_html(number_format($total_profit, 2)); ?></th>
                        <th colspan="2"></th>
                        <th><?php echo esc_html(number_format($total_printer, 2)); ?></th>
                        <th colspan="2"></th>
                        <th><?php echo esc_html(number_format($total_designer, 2)); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php
    }
}
