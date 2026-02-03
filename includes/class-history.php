<?php

class ABC_History {
    private array $tracked_meta = [
        'abc_invoice_number',
        'abc_order_date',
        'abc_approval_date',
        'abc_due_date',
        'abc_rush',
        'abc_status',
        'abc_workflow_status',
        'abc_line_items_json',
    ];

    public function register(): void {
        add_action('save_post', [$this, 'track_changes'], 20, 2);
    }

    public function track_changes(int $post_id, WP_Post $post): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type !== ABC_CPT_ABC_Estimate::POST_TYPE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $previous = get_post_meta($post_id, 'abc_meta_change_snapshot', true);
        if (!is_array($previous)) {
            $previous = [];
        }

        $changes = [];
        foreach ($this->tracked_meta as $meta_key) {
            $current = get_post_meta($post_id, $meta_key, true);
            $previous_value = $previous[$meta_key] ?? '';
            if ($current !== $previous_value) {
                $changes[] = [
                    'timestamp' => current_time('mysql'),
                    'user_id' => get_current_user_id(),
                    'field' => $meta_key,
                    'old' => $previous_value,
                    'new' => $current,
                ];
                $previous[$meta_key] = $current;
            }
        }

        if (!empty($changes)) {
            $log = get_post_meta($post_id, 'abc_meta_change_log', true);
            if (!is_array($log)) {
                $log = [];
            }
            $log = array_merge($log, $changes);
            update_post_meta($post_id, 'abc_meta_change_log', $log);
        }

        update_post_meta($post_id, 'abc_meta_change_snapshot', $previous);
    }
}
