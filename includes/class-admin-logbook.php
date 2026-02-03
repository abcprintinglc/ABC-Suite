<?php

class ABC_Admin_Logbook {
    public function register(): void {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu(): void {
        add_submenu_page(
            'edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE,
            'Log Book',
            'Log Book',
            'edit_posts',
            'abc-log-book',
            [$this, 'render_page']
        );

        add_submenu_page(
            'edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE,
            'Import / Data Tools',
            'Import / Data Tools',
            'manage_options',
            'abc-data-tools',
            [new ABC_CSV_Tools(), 'render_page']
        );
    }

    public function render_page(): void {
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions.');
        }

        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $query_args = [
            'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
            'posts_per_page' => 50,
            's' => $search,
        ];
        $results = new WP_Query($query_args);
        ?>
        <div class="wrap">
            <h1>Estimator Log Book</h1>
            <form method="get" class="abc-logbook-search">
                <input type="hidden" name="post_type" value="<?php echo esc_attr(ABC_CPT_ABC_Estimate::POST_TYPE); ?>">
                <input type="hidden" name="page" value="abc-log-book">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search estimates...">
                <button class="button">Search</button>
            </form>
            <table class="widefat striped abc-logbook-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Client</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($results->have_posts()) : ?>
                        <?php foreach ($results->posts as $post) : ?>
                            <?php
                            $invoice = get_post_meta($post->ID, 'abc_invoice_number', true);
                            $status = get_post_meta($post->ID, 'abc_status', true);
                            $date = get_post_meta($post->ID, 'abc_order_date', true);
                            $line_items = get_post_meta($post->ID, 'abc_line_items_json', true);
                            $item = '';
                            $qty = '';
                            $total = '';
                            $client = '';
                            if ($line_items) {
                                $decoded = json_decode($line_items, true);
                                if (is_array($decoded) && isset($decoded[0])) {
                                    $item = $decoded[0]['item'] ?? '';
                                    $qty = $decoded[0]['qty'] ?? '';
                                    $total = $decoded[0]['total'] ?? '';
                                    $client = $decoded[0]['client'] ?? '';
                                }
                            }
                            ?>
                            <tr>
                                <td><?php echo $this->highlight($invoice, $search); ?></td>
                                <td><?php echo $this->highlight($client, $search); ?></td>
                                <td><?php echo $this->highlight($item, $search); ?></td>
                                <td><?php echo esc_html($qty); ?></td>
                                <td><?php echo esc_html($total); ?></td>
                                <td><?php echo $this->highlight($date, $search); ?></td>
                                <td><?php echo $this->highlight($status, $search); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="7">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function highlight(string $value, string $needle): string {
        $escaped = esc_html($value);
        if ($needle === '') {
            return $escaped;
        }
        return preg_replace('/(' . preg_quote($needle, '/') . ')/i', '<mark>$1</mark>', $escaped);
    }
}
