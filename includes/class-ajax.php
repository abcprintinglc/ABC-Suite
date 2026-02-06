<?php

class ABC_Ajax {
    public function register(): void {
        add_action('wp_ajax_abc_search_estimates', [$this, 'search_estimates']);
        add_action('wp_ajax_nopriv_abc_search_estimates', [$this, 'search_estimates']);
        add_action('wp_ajax_abc_update_status', [$this, 'update_status']);
        add_action('wp_ajax_abc_get_templates', [$this, 'get_templates']);
        add_action('wp_ajax_abc_get_template', [$this, 'get_template']);
        add_action('wp_ajax_abc_price_lookup', [$this, 'price_lookup']);
        add_action('wp_ajax_abc_save_template', [$this, 'save_template']);
        add_action('wp_ajax_abc_update_template', [$this, 'update_template']);
        add_action('wp_ajax_abc_matrix_upsert', [$this, 'matrix_upsert']);
    }

    public function search_estimates(): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'abc_log_book_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce.'], 403);
        }

        $term = isset($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term'])) : '';
        $args = [
            'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
            'posts_per_page' => 50,
            'post_status' => 'any',
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if ($term !== '') {
            $args['s'] = $term;
            $args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key' => 'abc_invoice_number',
                    'value' => $term,
                    'compare' => 'LIKE',
                ],
                [
                    'key' => 'abc_client_name',
                    'value' => $term,
                    'compare' => 'LIKE',
                ],
                [
                    'key' => 'abc_client_phone',
                    'value' => $term,
                    'compare' => 'LIKE',
                ],
                [
                    'key' => 'abc_job_description',
                    'value' => $term,
                    'compare' => 'LIKE',
                ],
            ];
        }

        $results = new WP_Query($args);
        $items = [];
        foreach ($results->posts as $post) {
            $due_raw = get_post_meta($post->ID, 'abc_due_date', true);
            $due_date = $this->due_date_for_display((string) $due_raw);
            $rush = get_post_meta($post->ID, 'abc_is_rush', true);
            $status = get_post_meta($post->ID, 'abc_status', true) ?: 'estimate';
            $client = get_post_meta($post->ID, 'abc_client_name', true);

            $urgency = self::get_urgency_status($due_date);
            if ($rush === '1') {
                $urgency = 'urgent';
            }

            $items[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'client' => $client,
                'invoice' => get_post_meta($post->ID, 'abc_invoice_number', true),
                'due_date' => $due_date,
                'stage' => $status,
                'is_rush' => $rush === '1',
                'urgency' => $urgency,
                'urgency_class' => 'urgency-' . $urgency,
                'edit_url' => get_edit_post_link($post->ID, 'raw'),
                'print_url' => home_url('/?abc_action=print_estimate&id=' . $post->ID),
            ];
        }

        wp_send_json_success($items);
    }

    public function update_status(): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'abc_log_book_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce.'], 403);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized.'], 403);
        }

        $post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        $allowed = ['estimate', 'pending', 'production', 'completed'];

        if (!$post_id || !in_array($status, $allowed, true)) {
            wp_send_json_error(['message' => 'Invalid data.'], 400);
        }

        $old_status = get_post_meta($post_id, 'abc_status', true);
        if ($old_status !== $status) {
            update_post_meta($post_id, 'abc_status', $status);

            $log = get_post_meta($post_id, 'abc_history_log', true);
            if (!is_array($log)) {
                $log = [];
            }
            $user = wp_get_current_user();
            $log[] = [
                'date' => current_time('mysql'),
                'user' => $user ? $user->display_name : 'Unknown',
                'note' => 'Quick Status change: ' . ($old_status ?: '(empty)') . ' -> ' . $status,
            ];
            update_post_meta($post_id, 'abc_history_log', array_slice($log, -300));

            $invoice = get_post_meta($post_id, 'abc_invoice_number', true);
            $client = get_post_meta($post_id, 'abc_client_name', true);
            $desc = get_post_meta($post_id, 'abc_job_description', true);
            $index = trim("Inv:$invoice | $client | $status | $desc");
            $index = preg_replace('/\s+/', ' ', $index);

            global $wpdb;
            $wpdb->update($wpdb->posts, ['post_excerpt' => $index], ['ID' => $post_id]);
        }

        wp_send_json_success();
    }

    public function get_templates(): void {
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'abc_log_book_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce.'], 403);
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized.'], 403);
        }

        $templates = get_posts([
            'post_type' => ABC_CPT_ABC_Product_Template::POST_TYPE,
            'posts_per_page' => 200,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $items = [];
        foreach ($templates as $template) {
            $items[] = [
                'id' => $template->ID,
                'title' => $template->post_title,
                'vendor_default' => get_post_meta($template->ID, 'abc_template_vendor_default', true),
                'option_schema' => get_post_meta($template->ID, 'abc_template_option_schema', true),
                'markup_type' => get_post_meta($template->ID, 'abc_template_markup_type', true),
                'markup_value' => get_post_meta($template->ID, 'abc_template_markup_value', true),
            ];
        }

        wp_send_json_success($items);
    }

    public function get_template(): void {
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'abc_log_book_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce.'], 403);
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized.'], 403);
        }

        $template_id = isset($_GET['template_id']) ? absint($_GET['template_id']) : 0;
        if (!$template_id) {
            wp_send_json_error(['message' => 'Missing template id.'], 400);
        }

        $template = get_post($template_id);
        if (!$template || $template->post_type !== ABC_CPT_ABC_Product_Template::POST_TYPE) {
            wp_send_json_error(['message' => 'Template not found.'], 404);
        }

        wp_send_json_success([
            'id' => $template->ID,
            'title' => $template->post_title,
            'category' => get_post_meta($template->ID, 'abc_template_category', true),
            'vendor_default' => get_post_meta($template->ID, 'abc_template_vendor_default', true),
            'option_schema' => get_post_meta($template->ID, 'abc_template_option_schema', true),
            'pricing_model' => get_post_meta($template->ID, 'abc_template_pricing_model', true),
            'markup_type' => get_post_meta($template->ID, 'abc_template_markup_type', true),
            'markup_value' => get_post_meta($template->ID, 'abc_template_markup_value', true),
            'notes' => get_post_meta($template->ID, 'abc_template_notes', true),
            'schema_version' => get_post_meta($template->ID, 'abc_template_schema_version', true),
        ]);
    }

    public function price_lookup(): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'abc_log_book_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce.'], 403);
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized.'], 403);
        }

        $template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
        $vendor = isset($_POST['vendor']) ? sanitize_text_field(wp_unslash($_POST['vendor'])) : '';
        $qty = isset($_POST['qty']) ? absint($_POST['qty']) : 0;
        $turnaround = isset($_POST['turnaround']) ? sanitize_text_field(wp_unslash($_POST['turnaround'])) : '';
        $options_json = isset($_POST['options_json']) ? wp_unslash($_POST['options_json']) : '';

        if (!$template_id || $vendor === '' || $qty <= 0) {
            wp_send_json_error(['message' => 'Missing required data.'], 400);
        }

        $options = ABC_Price_Matrix::parse_options_json((string) $options_json);
        $row = ABC_Price_Matrix::lookup($template_id, $vendor, $qty, $options, $turnaround);
        if (!$row) {
            wp_send_json_error(['message' => 'No matrix match.'], 404);
        }

        wp_send_json_success([
            'id' => $row['id'],
            'cost' => $row['cost'],
            'last_verified' => $row['last_verified'],
            'options_json' => $row['options_json'],
        ]);
    }

    public function save_template(): void {
        $this->save_or_update_template(0);
    }

    public function update_template(): void {
        $template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
        $this->save_or_update_template($template_id);
    }

    private function save_or_update_template(int $template_id): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'abc_log_book_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce.'], 403);
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized.'], 403);
        }

        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        if ($title === '') {
            wp_send_json_error(['message' => 'Missing title.'], 400);
        }

        $data = [
            'post_type' => ABC_CPT_ABC_Product_Template::POST_TYPE,
            'post_title' => $title,
            'post_status' => 'publish',
        ];

        if ($template_id) {
            $data['ID'] = $template_id;
            $template_id = wp_update_post($data, true);
        } else {
            $template_id = wp_insert_post($data, true);
        }

        if (is_wp_error($template_id)) {
            wp_send_json_error(['message' => 'Error saving template.'], 500);
        }

        $meta_fields = [
            'abc_template_category' => sanitize_text_field(wp_unslash($_POST['category'] ?? '')),
            'abc_template_vendor_default' => sanitize_text_field(wp_unslash($_POST['vendor_default'] ?? '')),
            'abc_template_pricing_model' => sanitize_text_field(wp_unslash($_POST['pricing_model'] ?? 'matrix')),
            'abc_template_markup_type' => sanitize_text_field(wp_unslash($_POST['markup_type'] ?? 'percent')),
            'abc_template_markup_value' => sanitize_text_field(wp_unslash($_POST['markup_value'] ?? '0')),
            'abc_template_notes' => sanitize_textarea_field(wp_unslash($_POST['notes'] ?? '')),
            'abc_template_option_schema' => wp_unslash($_POST['option_schema'] ?? '{}'),
            'abc_template_schema_version' => sanitize_text_field(wp_unslash($_POST['schema_version'] ?? '1')),
        ];

        $cpt = new ABC_CPT_ABC_Product_Template();
        foreach ($meta_fields as $key => $value) {
            update_post_meta($template_id, $key, $cpt->sanitize_meta($value, $key));
        }

        wp_send_json_success(['id' => $template_id]);
    }

    public function matrix_upsert(): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'abc_log_book_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce.'], 403);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized.'], 403);
        }

        $payload = [
            'id' => isset($_POST['id']) ? absint($_POST['id']) : 0,
            'template_id' => isset($_POST['template_id']) ? absint($_POST['template_id']) : 0,
            'vendor' => isset($_POST['vendor']) ? sanitize_text_field(wp_unslash($_POST['vendor'])) : '',
            'qty_min' => isset($_POST['qty_min']) ? absint($_POST['qty_min']) : 0,
            'qty_max' => isset($_POST['qty_max']) ? sanitize_text_field(wp_unslash($_POST['qty_max'])) : '',
            'options' => ABC_Price_Matrix::parse_options_json(isset($_POST['options_json']) ? wp_unslash($_POST['options_json']) : ''),
            'turnaround' => isset($_POST['turnaround']) ? sanitize_text_field(wp_unslash($_POST['turnaround'])) : '',
            'cost' => isset($_POST['cost']) ? sanitize_text_field(wp_unslash($_POST['cost'])) : '0',
            'last_verified' => isset($_POST['last_verified']) ? sanitize_text_field(wp_unslash($_POST['last_verified'])) : '',
            'source_note' => isset($_POST['source_note']) ? sanitize_textarea_field(wp_unslash($_POST['source_note'])) : '',
        ];

        if (!$payload['template_id'] || $payload['vendor'] === '' || $payload['qty_min'] <= 0) {
            wp_send_json_error(['message' => 'Missing required data.'], 400);
        }

        $id = ABC_Price_Matrix::upsert($payload);
        wp_send_json_success(['id' => $id]);
    }

    public static function get_urgency_status(string $due_date_str): string {
        $due_date_str = trim($due_date_str);
        if ($due_date_str === '') {
            return 'normal';
        }
        $diff = (strtotime($due_date_str . ' 23:59:59') - current_time('timestamp')) / DAY_IN_SECONDS;
        if ($diff < 0) {
            return 'urgent';
        }
        if ($diff <= 1) {
            return 'warning';
        }
        return 'normal';
    }

    private function due_date_for_display(string $due_date_str): string {
        if ($due_date_str === '') {
            return '';
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $due_date_str, $matches)) {
            $year = (int) $matches[1];
            if ($year < 2026) {
                return '';
            }
        }
        return $due_date_str;
    }
}
