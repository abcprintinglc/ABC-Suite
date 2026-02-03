<?php

class ABC_CPT_ABC_Estimate {
    public const POST_TYPE = 'abc_estimate';

    public function register(): void {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_meta']);
    }

    public function register_post_type(): void {
        $labels = [
            'name' => 'Estimates & Jobs',
            'singular_name' => 'Job',
            'menu_name' => 'Estimator / Log',
            'add_new_item' => 'New Estimate',
            'edit_item' => 'Edit Job Jacket',
        ];

        register_post_type(self::POST_TYPE, [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'revisions', 'author'],
            'menu_icon' => 'dashicons-calculator',
            'map_meta_cap' => true,
            'capability_type' => 'post',
            'exclude_from_search' => true,
            'show_in_rest' => false,
        ]);
    }

    public function register_meta(): void {
        register_post_meta(self::POST_TYPE, 'abc_invoice_number', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
            'sanitize_callback' => [$this, 'sanitize_invoice'],
            'auth_callback' => static function (): bool {
                return current_user_can('edit_posts');
            },
        ]);

        foreach (['abc_order_date', 'abc_due_date', 'abc_approval_date'] as $key) {
            register_post_meta(self::POST_TYPE, $key, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => [$this, 'sanitize_date'],
                'auth_callback' => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]);
        }

        register_post_meta(self::POST_TYPE, 'abc_is_rush', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
            'sanitize_callback' => static function ($value): string {
                return $value === '1' ? '1' : '0';
            },
            'auth_callback' => static function (): bool {
                return current_user_can('edit_posts');
            },
        ]);

        register_post_meta(self::POST_TYPE, 'abc_status', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
            'sanitize_callback' => [$this, 'sanitize_status'],
            'auth_callback' => static function (): bool {
                return current_user_can('edit_posts');
            },
        ]);

        register_post_meta(self::POST_TYPE, 'abc_estimate_data', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
            'sanitize_callback' => [$this, 'sanitize_json'],
            'auth_callback' => static function (): bool {
                return current_user_can('edit_posts');
            },
        ]);

        register_post_meta(self::POST_TYPE, 'abc_history_log', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => false,
            'sanitize_callback' => [$this, 'sanitize_history'],
            'auth_callback' => static function (): bool {
                return current_user_can('edit_posts');
            },
        ]);
    }

    public function sanitize_invoice($value): string {
        $value = strtoupper(trim((string) $value));
        if ($value === '') {
            return '';
        }
        if (!preg_match('/^\d{4}-\d{2}$/', $value)) {
            return sanitize_text_field($value);
        }
        return $value;
    }

    public function sanitize_date($value): string {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return sanitize_text_field($value);
        }
        return $value;
    }

    public function sanitize_status($value): string {
        $value = strtolower(trim((string) $value));
        $allowed = ['estimate', 'pending', 'production', 'completed'];
        if (!in_array($value, $allowed, true)) {
            return 'estimate';
        }
        return $value;
    }

    public function sanitize_json($value): string {
        if ($value === null) {
            return '[]';
        }
        $value = (string) $value;
        $decoded = json_decode(wp_unslash($value), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '[]';
        }
        return wp_json_encode($decoded);
    }

    public function sanitize_history($value): array {
        if (!is_array($value)) {
            return [];
        }
        $sanitized = [];
        foreach ($value as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $sanitized[] = [
                'date' => sanitize_text_field($entry['date'] ?? ''),
                'user' => sanitize_text_field($entry['user'] ?? ''),
                'note' => sanitize_text_field($entry['note'] ?? ''),
            ];
        }
        return $sanitized;
    }
}
