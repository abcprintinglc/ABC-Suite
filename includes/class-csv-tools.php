<?php

class ABC_CSV_Tools {
    private const NONCE_ACTION = 'abc_csv_tools';
    private const NONCE_NAME = 'abc_csv_tools_nonce';

    public function register(): void {
        add_action('admin_post_abc_import_csv', [$this, 'handle_import']);
        add_action('admin_post_abc_bulk_delete', [$this, 'handle_bulk_delete']);
        add_action('admin_notices', [$this, 'render_notices']);
    }

    public function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }
        ?>
        <div class="wrap">
            <h1>Import / Data Tools</h1>

            <div class="card" style="max-width: 900px; padding: 16px 20px; margin-top: 12px;">
                <h2 style="margin-top:0;">Accepted CSV formats</h2>
                <p><strong>A) Legacy Physical Log Book export</strong></p>
                <ul style="margin-left: 18px; list-style: disc;">
                    <li>Headers: <code>Invoice No</code>, <code>Company</code>, <code>Item</code>, <code>Quantity</code>, <code>Amount</code>, <code>Date</code></li>
                </ul>

                <p><strong>B) Simple format</strong></p>
                <ul style="margin-left: 18px; list-style: disc;">
                    <li><code>Title, Invoice, Due Date</code></li>
                </ul>
            </div>

            <h2>CSV Import</h2>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>
                <input type="hidden" name="action" value="abc_import_csv">
                <input type="file" name="abc_csv" accept=".csv" required>
                <button class="button button-primary">Import CSV</button>
            </form>

            <h2>Bulk Delete</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('WARNING: This will delete ALL entries previously imported via CSV. Are you sure?');">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>
                <input type="hidden" name="action" value="abc_bulk_delete">
                <button class="button button-link-delete">Delete All CSV Entries</button>
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
        $skipped_duplicates = 0;
        $errors = [];

        $header_map = $this->build_header_map($header);
        $legacy_invoice_idx = $this->idx($header_map, ['invoice no', 'invoice']);
        $legacy_company_idx = $this->idx($header_map, ['company', 'client']);
        $legacy_item_idx = $this->idx($header_map, ['item', 'job', 'description']);
        $legacy_qty_idx = $this->idx($header_map, ['quantity', 'qty']);
        $legacy_amount_idx = $this->idx($header_map, ['amount', 'total']);
        $legacy_date_idx = $this->idx($header_map, ['date', 'order date']);

        $is_legacy = $legacy_invoice_idx !== null && $legacy_company_idx !== null && $legacy_item_idx !== null;

        $row_index = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $row_index++;
            if (!is_array($row)) {
                continue;
            }

            if ($is_legacy) {
                $invoice = sanitize_text_field($row[$legacy_invoice_idx] ?? '');
                $company = sanitize_text_field($row[$legacy_company_idx] ?? '');
                $item = sanitize_text_field($row[$legacy_item_idx] ?? '');
                $qty = sanitize_text_field($row[$legacy_qty_idx] ?? '');
                $amount = sanitize_text_field($row[$legacy_amount_idx] ?? '');
                $date_raw = sanitize_text_field($row[$legacy_date_idx] ?? '');
                $due_date = $this->normalize_due_date_for_storage($date_raw);

                if ($invoice === '') {
                    continue;
                }

                if (!$this->validate_invoice_format($invoice)) {
                    $errors[] = "Row {$row_index} ignored (Invalid Invoice: {$invoice}). Must be tttt-yy (e.g., 1005-24).";
                    continue;
                }

                if ($this->invoice_exists($invoice)) {
                    $skipped_duplicates++;
                    $errors[] = "Row {$row_index} skipped (Duplicate Invoice: {$invoice}).";
                    continue;
                }

                $title = trim($company . ' - ' . $item);
                if ($title === '') {
                    $title = $invoice;
                }

                $line_items = [[
                    'item' => $item,
                    'qty' => $qty,
                    'total' => $amount,
                    'client' => $company,
                ]];

                $post_id = wp_insert_post([
                    'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
                    'post_title' => $title,
                    'post_status' => 'publish',
                ]);

                if ($post_id) {
                    update_post_meta($post_id, 'abc_invoice_number', $invoice);
                    update_post_meta($post_id, 'abc_due_date', $due_date);
                    update_post_meta($post_id, 'abc_order_date', $due_date);
                    update_post_meta($post_id, 'abc_status', 'estimate');
                    update_post_meta($post_id, 'abc_is_imported', '1');
                    update_post_meta($post_id, 'abc_client_name', $company);
                    update_post_meta($post_id, 'abc_job_description', $item);
                    update_post_meta($post_id, 'abc_estimate_data', wp_json_encode($line_items));
                    $imported++;
                }

                continue;
            }

            $title = sanitize_text_field($row[0] ?? '');
            $invoice = sanitize_text_field($row[1] ?? '');
            $due_date = $this->normalize_due_date_for_storage($row[2] ?? '');

            if ($row_index === 2 && preg_match('/invoice/i', $invoice)) {
                continue;
            }

            if ($invoice === '' && $title === '' && $due_date === '') {
                continue;
            }

            if (!$this->validate_invoice_format($invoice)) {
                $errors[] = "Row {$row_index} ignored (Invalid Invoice: {$invoice}). Must be tttt-yy (e.g., 1005-24).";
                continue;
            }

            if ($this->invoice_exists($invoice)) {
                $skipped_duplicates++;
                $errors[] = "Row {$row_index} skipped (Duplicate Invoice: {$invoice}).";
                continue;
            }

            $post_id = wp_insert_post([
                'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
                'post_title' => $title !== '' ? $title : $invoice,
                'post_status' => 'publish',
            ]);

            if ($post_id) {
                update_post_meta($post_id, 'abc_invoice_number', $invoice);
                update_post_meta($post_id, 'abc_due_date', $due_date);
                update_post_meta($post_id, 'abc_status', 'estimate');
                update_post_meta($post_id, 'abc_is_imported', '1');
                $imported++;
            }
        }
        fclose($handle);

        if (!empty($errors)) {
            set_transient('abc_csv_import_errors_' . get_current_user_id(), $errors, 5 * MINUTE_IN_SECONDS);
        }

        wp_safe_redirect(admin_url('edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE . '&page=abc-data-tools&imported=' . $imported . '&dupes=' . $skipped_duplicates . '&errors=' . count($errors)));
        exit;
    }

    public function handle_bulk_delete(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        $nonce = '';
        if (isset($_POST[self::NONCE_NAME])) {
            $nonce = sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME]));
        } elseif (isset($_GET[self::NONCE_NAME])) {
            $nonce = sanitize_text_field(wp_unslash($_GET[self::NONCE_NAME]));
        }

        if ($nonce === '' || !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            wp_die('Invalid nonce.');
        }

        $batch_size = 200;
        $results = new WP_Query([
            'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
            'posts_per_page' => $batch_size,
            'meta_key' => 'abc_is_imported',
            'meta_value' => '1',
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        $deleted_total = (int) get_transient('abc_csv_delete_count_' . get_current_user_id());
        $deleted_total += count($results->posts);

        foreach ($results->posts as $post_id) {
            wp_delete_post($post_id, true);
        }

        if (count($results->posts) === $batch_size) {
            set_transient('abc_csv_delete_count_' . get_current_user_id(), $deleted_total, 10 * MINUTE_IN_SECONDS);
            $continue_url = add_query_arg([
                'action' => 'abc_bulk_delete',
                self::NONCE_NAME => wp_create_nonce(self::NONCE_ACTION),
                'continue' => 1,
            ], admin_url('admin-post.php'));
            wp_safe_redirect($continue_url);
            exit;
        }

        delete_transient('abc_csv_delete_count_' . get_current_user_id());
        wp_safe_redirect(admin_url('edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE . '&page=abc-data-tools&deleted=' . $deleted_total));
        exit;
    }

    public function render_notices(): void {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'abc_estimate_page_abc-data-tools') {
            return;
        }

        if (isset($_GET['imported'])) {
            $imported = absint($_GET['imported']);
            $errors_count = isset($_GET['errors']) ? absint($_GET['errors']) : 0;
            $dupes = isset($_GET['dupes']) ? absint($_GET['dupes']) : 0;
            echo '<div class="notice notice-success"><p>' . esc_html("Imported {$imported} row(s).") . '</p></div>';
            if ($dupes > 0) {
                echo '<div class="notice notice-info"><p>' . esc_html("Skipped {$dupes} duplicate invoice row(s).") . '</p></div>';
            }
            if ($errors_count > 0) {
                echo '<div class="notice notice-warning"><p>' . esc_html("{$errors_count} row(s) were ignored or failed. See details below.") . '</p></div>';
            }
        }

        if (isset($_GET['deleted'])) {
            $deleted = absint($_GET['deleted']);
            echo '<div class="notice notice-success"><p>' . esc_html("Deleted {$deleted} imported estimate(s).") . '</p></div>';
        }

        $errors = get_transient('abc_csv_import_errors_' . get_current_user_id());
        if (!empty($errors) && is_array($errors)) {
            delete_transient('abc_csv_import_errors_' . get_current_user_id());
            echo '<div class="notice notice-warning"><p><strong>CSV Import Details</strong></p><ul style="margin-left: 18px; list-style: disc;">';
            foreach (array_slice($errors, 0, 40) as $err) {
                echo '<li>' . esc_html($err) . '</li>';
            }
            if (count($errors) > 40) {
                echo '<li>' . esc_html('…and more. (Fix the CSV and re-import.)') . '</li>';
            }
            echo '</ul></div>';
        }
    }

    private function validate_invoice_format(string $invoice): bool {
        return (bool) preg_match('/^\d{4}-\d{2}$/', $invoice);
    }

    private function invoice_exists(string $invoice): bool {
        if ($invoice === '') {
            return false;
        }

        $existing = new WP_Query([
            'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
            'posts_per_page' => 1,
            'post_status' => 'any',
            'meta_key' => 'abc_invoice_number',
            'meta_value' => $invoice,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        return $existing->have_posts();
    }

    private function normalize_due_date_for_storage($raw): string {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $matches)) {
            $year = (int) $matches[1];
            return ($year < 2026) ? '' : $raw;
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $raw, $matches)) {
            $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = (int) $matches[3];
            if ($year < 2026) {
                return '';
            }
            return sprintf('%04d-%02d-%02d', $year, (int) $month, (int) $day);
        }
        return $raw;
    }

    private function build_header_map(array $header_row): array {
        $map = [];
        foreach ($header_row as $idx => $header) {
            $key = strtolower(trim((string) $header));
            $key = preg_replace('/\s+/', ' ', $key);
            if ($key !== '') {
                $map[$key] = $idx;
            }
        }
        return $map;
    }

    private function idx(array $map, array $possible_keys): ?int {
        foreach ($possible_keys as $key) {
            $key = strtolower(trim($key));
            if (isset($map[$key])) {
                return $map[$key];
            }
        }
        return null;
    }
}
