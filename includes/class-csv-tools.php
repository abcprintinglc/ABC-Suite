<?php

class ABC_CSV_Tools {
    private const NONCE_ACTION = 'abc_csv_tools';
    private const NONCE_NAME = 'abc_csv_tools_nonce';

    public function register(): void {
        add_action('admin_post_abc_import_csv', [$this, 'handle_import']);
        add_action('admin_post_abc_bulk_delete', [$this, 'handle_bulk_delete']);
    }

    public function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }
        ?>
        <div class="wrap">
            <h1>Import / Data Tools</h1>
            <h2>CSV Import</h2>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>
                <input type="hidden" name="action" value="abc_import_csv">
                <input type="file" name="abc_csv" accept=".csv" required>
                <button class="button button-primary">Import CSV</button>
            </form>

            <h2>Bulk Delete</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>
                <input type="hidden" name="action" value="abc_bulk_delete">
                <label for="abc_delete_before">Delete records before date:</label>
                <input type="date" name="abc_delete_before" id="abc_delete_before">
                <button class="button">Delete</button>
            </form>
        </div>
        <?php
    }

    public function handle_import(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            wp_die('Invalid nonce.');
        }

        if (empty($_FILES['abc_csv']['tmp_name'])) {
            wp_safe_redirect(admin_url('edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE . '&page=abc-data-tools'));
            exit;
        }

        $file = $_FILES['abc_csv']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_die('Unable to read CSV.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            wp_die('CSV header missing.');
        }

        $imported = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (!$data) {
                continue;
            }
            $invoice = sanitize_text_field($data['invoice'] ?? '');
            if ($invoice === '') {
                continue;
            }
            $existing = new WP_Query([
                'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
                'meta_key' => 'abc_invoice_number',
                'meta_value' => $invoice,
                'fields' => 'ids',
            ]);
            if ($existing->found_posts) {
                continue;
            }

            $post_id = wp_insert_post([
                'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
                'post_title' => sanitize_text_field($data['title'] ?? 'Estimate ' . $invoice),
                'post_status' => 'publish',
            ]);

            if ($post_id) {
                update_post_meta($post_id, 'abc_invoice_number', $invoice);
                update_post_meta($post_id, 'abc_order_date', sanitize_text_field($data['order_date'] ?? ''));
                update_post_meta($post_id, 'abc_approval_date', sanitize_text_field($data['approval_date'] ?? ''));
                update_post_meta($post_id, 'abc_due_date', sanitize_text_field($data['due_date'] ?? ''));
                update_post_meta($post_id, 'abc_rush', !empty($data['rush']) ? '1' : '0');
                update_post_meta($post_id, 'abc_status', sanitize_text_field($data['status'] ?? ''));
                update_post_meta($post_id, 'abc_workflow_status', sanitize_text_field($data['workflow_status'] ?? 'estimate'));
                update_post_meta($post_id, 'abc_line_items_json', wp_kses_post($data['line_items_json'] ?? ''));
                $imported++;
            }
        }
        fclose($handle);

        wp_safe_redirect(admin_url('edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE . '&page=abc-data-tools&imported=' . $imported));
        exit;
    }

    public function handle_bulk_delete(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            wp_die('Invalid nonce.');
        }

        $before = isset($_POST['abc_delete_before']) ? sanitize_text_field(wp_unslash($_POST['abc_delete_before'])) : '';
        $query = [
            'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];
        if ($before !== '') {
            $query['meta_query'] = [
                [
                    'key' => 'abc_order_date',
                    'value' => $before,
                    'compare' => '<',
                    'type' => 'DATE',
                ],
            ];
        }

        $results = new WP_Query($query);
        foreach ($results->posts as $post_id) {
            wp_delete_post($post_id, true);
        }

        wp_safe_redirect(admin_url('edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE . '&page=abc-data-tools&deleted=' . count($results->posts)));
        exit;
    }
}
