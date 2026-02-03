<?php

class ABC_Ajax {
    public function register(): void {
        add_action('wp_ajax_abc_search_estimates', [$this, 'search_estimates']);
        add_action('wp_ajax_nopriv_abc_search_estimates', [$this, 'search_estimates']);
    }

    public function search_estimates(): void {
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'abc_suite_search')) {
            wp_send_json_error(['message' => 'Invalid nonce.'], 403);
        }

        $query = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $paged = isset($_GET['page']) ? max(1, absint($_GET['page'])) : 1;
        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';

        $args = [
            'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
            'posts_per_page' => 20,
            'paged' => $paged,
            's' => '',
        ];

        if ($status !== '') {
            $args['meta_query'] = [
                [
                    'key' => 'abc_workflow_status',
                    'value' => $status,
                ],
            ];
        }

        add_filter('posts_where', [$this, 'filter_excerpt_search'], 10, 2);
        $results = new WP_Query($args);
        remove_filter('posts_where', [$this, 'filter_excerpt_search'], 10);
        $items = [];
        foreach ($results->posts as $post) {
            $items[] = [
                'id' => $post->ID,
                'invoice' => get_post_meta($post->ID, 'abc_invoice_number', true),
                'status' => get_post_meta($post->ID, 'abc_status', true),
                'workflow_status' => get_post_meta($post->ID, 'abc_workflow_status', true),
                'due_date' => get_post_meta($post->ID, 'abc_due_date', true),
                'title' => $post->post_title,
            ];
        }

        wp_send_json_success([
            'items' => $items,
            'total' => $results->found_posts,
        ]);
    }

    public function filter_excerpt_search(string $where, WP_Query $query): string {
        global $wpdb;
        $search = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        if ($search === '') {
            return $where;
        }
        $like = '%' . $wpdb->esc_like($search) . '%';
        return $where . $wpdb->prepare(" AND {$wpdb->posts}.post_excerpt LIKE %s", $like);
    }
}
