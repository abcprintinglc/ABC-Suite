<?php

class ABC_Ajax {
    public function register(): void {
        add_action('wp_ajax_abc_search_estimates', [$this, 'search_estimates']);
        add_action('wp_ajax_nopriv_abc_search_estimates', [$this, 'search_estimates']);
        add_action('wp_ajax_abc_update_status', [$this, 'update_status']);
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
