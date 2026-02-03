<?php

class ABC_Duplicate_Action {
    public function register(): void {
        add_filter('post_row_actions', [$this, 'add_row_action'], 10, 2);
        add_action('admin_action_abc_duplicate_estimate', [$this, 'handle_duplicate']);
    }

    public function add_row_action(array $actions, WP_Post $post): array {
        if ($post->post_type !== ABC_CPT_ABC_Estimate::POST_TYPE) {
            return $actions;
        }

        $url = wp_nonce_url(
            admin_url('admin.php?action=abc_duplicate_estimate&post=' . $post->ID),
            'abc_duplicate_estimate_' . $post->ID
        );
        $actions['abc_duplicate'] = '<a href="' . esc_url($url) . '">Duplicate / Save as New</a>';
        return $actions;
    }

    public function handle_duplicate(): void {
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        if (!$post_id) {
            wp_die('Missing post ID.');
        }

        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions.');
        }

        check_admin_referer('abc_duplicate_estimate_' . $post_id);

        $post = get_post($post_id);
        if (!$post || $post->post_type !== ABC_CPT_ABC_Estimate::POST_TYPE) {
            wp_die('Invalid estimate.');
        }

        $new_id = wp_insert_post([
            'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
            'post_title' => $post->post_title . ' (Copy)',
            'post_status' => 'draft',
        ]);

        if (!$new_id) {
            wp_die('Failed to duplicate.');
        }

        $meta = get_post_meta($post_id);
        foreach ($meta as $key => $values) {
            if (is_array($values)) {
                foreach ($values as $value) {
                    update_post_meta($new_id, $key, maybe_unserialize($value));
                }
            }
        }

        $history = get_post_meta($new_id, 'abc_history_notes', true);
        if (!is_array($history)) {
            $history = [];
        }
        $history[] = [
            'timestamp' => current_time('mysql'),
            'user' => wp_get_current_user()->display_name,
            'note' => 'Duplicated from #' . $post_id,
        ];
        update_post_meta($new_id, 'abc_history_notes', $history);

        wp_safe_redirect(admin_url('post.php?action=edit&post=' . $new_id));
        exit;
    }
}
