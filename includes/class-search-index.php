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

        $invoice = (string) get_post_meta($post_id, 'abc_invoice_number', true);
        $status = (string) get_post_meta($post_id, 'abc_status', true);
        $due = (string) get_post_meta($post_id, 'abc_due_date', true);
        $json = (string) get_post_meta($post_id, 'abc_estimate_data', true);
        if ($json === '') {
            $json = (string) get_post_meta($post_id, 'abc_line_items_json', true);
        }

        $summary = '';
        $decoded = json_decode(wp_unslash($json), true);
        if (is_array($decoded)) {
            $parts = [];
            $walker = function ($node) use (&$walker, &$parts): void {
                if (!is_array($node)) {
                    return;
                }
                foreach ($node as $key => $value) {
                    if (is_array($value)) {
                        $walker($value);
                        continue;
                    }
                    if (is_string($value) || is_numeric($value)) {
                        $key = is_string($key) ? strtolower($key) : '';
                        if (in_array($key, ['description', 'desc', 'name', 'item', 'product', 'service', 'sku', 'notes', 'note', 'client'], true)) {
                            $parts[] = (string) $value;
                        }
                    }
                }
            };
            $walker($decoded);
            if (!empty($parts)) {
                $summary = implode(' ', $parts);
            }
        }

        $summary = preg_replace('/\s+/', ' ', trim((string) $summary));
        if (strlen($summary) > 3500) {
            $summary = substr($summary, 0, 3500);
        }

        $excerpt = wp_strip_all_tags(trim(sprintf('Invoice: %s | Status: %s | Due: %s | %s', $invoice, $status, $due, $summary)));

        remove_action('save_post', [$this, 'build_excerpt'], 25);
        wp_update_post([
            'ID' => $post_id,
            'post_excerpt' => $excerpt,
        ]);
        add_action('save_post', [$this, 'build_excerpt'], 25, 2);
    }
}
