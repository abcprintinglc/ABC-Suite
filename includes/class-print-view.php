<?php

class ABC_Print_View {
    public function register(): void {
        add_action('init', [$this, 'maybe_render_print']);
    }

    public function maybe_render_print(): void {
        if (!isset($_GET['abc_action']) || $_GET['abc_action'] !== 'print_estimate') {
            return;
        }

        $post_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if (!$post_id) {
            wp_die('Missing estimate ID.');
        }

        if (!is_user_logged_in()) {
            wp_die('You must be logged in to view this estimate.');
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== ABC_CPT_ABC_Estimate::POST_TYPE) {
            wp_die('Estimate not found.');
        }

        $data = [
            'post' => $post,
            'meta' => [
                'invoice' => get_post_meta($post_id, 'abc_invoice_number', true),
                'order_date' => get_post_meta($post_id, 'abc_order_date', true),
                'approval_date' => get_post_meta($post_id, 'abc_approval_date', true),
                'due_date' => get_post_meta($post_id, 'abc_due_date', true),
                'rush' => get_post_meta($post_id, 'abc_rush', true),
                'status' => get_post_meta($post_id, 'abc_status', true),
                'workflow_status' => get_post_meta($post_id, 'abc_workflow_status', true),
                'line_items_json' => get_post_meta($post_id, 'abc_line_items_json', true),
            ],
        ];

        status_header(200);
        nocache_headers();
        include ABC_SUITE_PATH . 'templates/print-estimate.php';
        exit;
    }
}
