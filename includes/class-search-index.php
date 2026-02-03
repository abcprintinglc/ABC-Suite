<?php

class ABC_Search_Index {
    public function register(): void {
        add_action('save_post', [$this, 'build_excerpt'], 25, 2);
    }

    public function build_excerpt(int $post_id, WP_Post $post): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type !== ABC_CPT_ABC_Estimate::POST_TYPE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $meta_keys = [
            'abc_invoice_number',
            'abc_order_date',
            'abc_approval_date',
            'abc_due_date',
            'abc_rush',
            'abc_status',
            'abc_workflow_status',
            'abc_line_items_json',
        ];
        $parts = [$post->post_title];

        foreach ($meta_keys as $key) {
            $value = get_post_meta($post_id, $key, true);
            if (is_array($value)) {
                $value = wp_json_encode($value);
            }
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        $excerpt = wp_strip_all_tags(implode(' | ', $parts));

        remove_action('save_post', [$this, 'build_excerpt'], 25);
        wp_update_post([
            'ID' => $post_id,
            'post_excerpt' => $excerpt,
        ]);
        add_action('save_post', [$this, 'build_excerpt'], 25, 2);
    }
}
