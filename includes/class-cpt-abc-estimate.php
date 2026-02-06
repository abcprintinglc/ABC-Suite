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

        $text_fields = [
            'abc_client_name',
            'abc_client_email',
            'abc_job_description',
            'abc_promised_date',
            'abc_ordered_date',
            'abc_last_ticket',
            'abc_send_proof_to',
            'abc_completed_by',
            'abc_printer_tech',
            'abc_designer',
            'abc_sales_rep',
            'abc_design_request_id',
        ];

        $textarea_fields = [
            'abc_job_name',
            'abc_stock_notes',
            'abc_press_work',
            'abc_print_notes',
            'abc_numbering_notes',
            'abc_finish_notes',
            'abc_delivery_notes',
            'abc_contacted_on',
        ];

        foreach ($text_fields as $key) {
            register_post_meta(self::POST_TYPE, $key, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => static function ($value): string {
                    return sanitize_text_field((string) $value);
                },
                'auth_callback' => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]);
        }

        foreach ($textarea_fields as $key) {
            register_post_meta(self::POST_TYPE, $key, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => static function ($value): string {
                    return sanitize_textarea_field((string) $value);
                },
                'auth_callback' => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]);
        }

        $checkbox_fields = [
            'abc_is_new_job',
            'abc_is_repeat_job',
            'abc_has_changes',
            'abc_is_print_ready',
            'abc_has_copies',
            'abc_notes_see_back',
            'abc_send_proof',
            'abc_press_two_sided',
            'abc_press_color',
            'abc_press_bw',
            'abc_finish_perf',
            'abc_finish_foil',
            'abc_finish_wraparound',
            'abc_finish_fold',
            'abc_finish_score',
            'abc_finish_pad',
            'abc_finish_ncr',
            'abc_finish_spiral',
            'abc_finish_numbering_required',
            'abc_finish_numbering_black',
            'abc_delivery_deliver',
            'abc_delivery_ship',
            'abc_delivery_pickup',
            'abc_contact_email',
            'abc_contact_phone',
            'abc_contact_voicemail',
            'abc_contact_po',
        ];

        foreach ($checkbox_fields as $key) {
            register_post_meta(self::POST_TYPE, $key, [
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
        }

        $percent_fields = [
            'abc_printer_pct',
            'abc_designer_pct',
            'abc_commission_pct',
        ];

        foreach ($percent_fields as $key) {
            register_post_meta(self::POST_TYPE, $key, [
                'type' => 'number',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => static function ($value): string {
                    return is_numeric($value) ? (string) (float) $value : '0';
                },
                'auth_callback' => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]);
        }

        $number_fields = [
            'abc_commission_amount',
            'abc_estimate_total',
        ];

        foreach ($number_fields as $key) {
            register_post_meta(self::POST_TYPE, $key, [
                'type' => 'number',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => static function ($value): string {
                    return is_numeric($value) ? (string) (float) $value : '0';
                },
                'auth_callback' => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]);
        }

        $status_fields = [
            'abc_square_invoice_id',
            'abc_square_invoice_status',
        ];

        foreach ($status_fields as $key) {
            register_post_meta(self::POST_TYPE, $key, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => static function ($value): string {
                    return sanitize_text_field((string) $value);
                },
                'auth_callback' => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]);
        }

        register_post_meta(self::POST_TYPE, 'abc_estimate_paid', [
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
