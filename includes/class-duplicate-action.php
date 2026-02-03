<?php

class ABC_Duplicate_Action {
    public function register(): void {
        add_filter('post_row_actions', [$this, 'add_row_action'], 10, 2);
        add_action('admin_post_abc_duplicate_estimate', [$this, 'handle_duplicate']);
    }

    public function add_row_action(array $actions, WP_Post $post): array {
        if ($post->post_type !== ABC_CPT_ABC_Estimate::POST_TYPE) {
            return $actions;
        }

        if (!current_user_can('edit_post', $post->ID)) {
            return $actions;
        }

        $url = wp_nonce_url(
            admin_url('admin-post.php?action=abc_duplicate_estimate&id=' . $post->ID),
            'abc_dup_nonce'
        );
        $actions['abc_duplicate'] = '<a href="' . esc_url($url) . '">Duplicate</a>';
        return $actions;
    }

    public function handle_duplicate(): void {
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions.');
        }

        check_admin_referer('abc_dup_nonce');

        $post_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if (!$post_id) {
            wp_die('Missing post ID.');
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== ABC_CPT_ABC_Estimate::POST_TYPE) {
            wp_die('Invalid estimate.');
        }

        $new_id = wp_insert_post([
            'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
            'post_title' => $post->post_title . ' - COPY',
            'post_status' => 'draft',
            'post_author' => get_current_user_id(),
        ]);

        if (!$new_id) {
            wp_die('Failed to duplicate.');
        }

        $meta = get_post_meta($post_id);
        foreach ($meta as $key => $values) {
            if (in_array($key, ['_edit_lock', '_edit_last'], true)) {
                continue;
            }
            if (in_array($key, ['abc_invoice_number', 'abc_history_log', 'abc_is_imported'], true)) {
                continue;
            }
            foreach ($values as $value) {
                add_post_meta($new_id, $key, maybe_unserialize($value));
            }
        }

        update_post_meta($new_id, 'abc_invoice_number', '');
        update_post_meta($new_id, 'abc_is_imported', '0');

        $user = wp_get_current_user();
        update_post_meta($new_id, 'abc_history_log', [[
            'date' => current_time('mysql'),
            'user' => $user && isset($user->display_name) ? $user->display_name : 'Unknown',
            'note' => 'Duplicated from #' . $post_id,
        ]]);

        wp_safe_redirect(get_edit_post_link($new_id, 'raw'));
        exit;
    }
}
