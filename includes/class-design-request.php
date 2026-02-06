<?php

class ABC_Design_Request {
    public const POST_TYPE = 'abc_design_request';

    private const NONCE_ACTION = 'abc_design_request_save';
    private const NONCE_NAME = 'abc_design_request_nonce';

    public function register(): void {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_meta']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta'], 10, 2);
        add_action('pre_get_posts', [$this, 'filter_admin_list']);
    }

    public function register_post_type(): void {
        $labels = [
            'name' => 'Design Requests',
            'singular_name' => 'Design Request',
            'menu_name' => 'Design Requests',
            'add_new_item' => 'Add Design Request',
            'edit_item' => 'Edit Design Request',
        ];

        register_post_type(self::POST_TYPE, [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'revisions', 'author'],
            'menu_icon' => 'dashicons-art',
            'map_meta_cap' => true,
            'capability_type' => 'post',
            'exclude_from_search' => true,
            'show_in_rest' => false,
        ]);
    }

    public function register_meta(): void {
        $text_fields = [
            'abc_design_org_id',
            'abc_design_employee_id',
            'abc_design_admin_id',
            'abc_design_status',
            'abc_design_notes',
            'abc_design_quantity',
            'abc_design_ink',
            'abc_design_stock',
            'abc_design_amount',
            'abc_design_pdf_url',
            'abc_design_template_id',
            'abc_design_estimate_id',
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

        $checkbox_fields = [
            'abc_design_employee_approved',
            'abc_design_admin_approved',
            'abc_design_web_downloadable',
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
    }

    public function add_meta_boxes(): void {
        add_meta_box(
            'abc_design_request_details',
            'Design Request Details',
            [$this, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_meta_box(WP_Post $post): void {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $org_id = (string) get_post_meta($post->ID, 'abc_design_org_id', true);
        $employee_id = (string) get_post_meta($post->ID, 'abc_design_employee_id', true);
        $admin_id = (string) get_post_meta($post->ID, 'abc_design_admin_id', true);
        $status = (string) get_post_meta($post->ID, 'abc_design_status', true);
        $notes = (string) get_post_meta($post->ID, 'abc_design_notes', true);
        $quantity = (string) get_post_meta($post->ID, 'abc_design_quantity', true);
        $ink = (string) get_post_meta($post->ID, 'abc_design_ink', true);
        $stock = (string) get_post_meta($post->ID, 'abc_design_stock', true);
        $amount = (string) get_post_meta($post->ID, 'abc_design_amount', true);
        $pdf_url = (string) get_post_meta($post->ID, 'abc_design_pdf_url', true);
        $employee_approved = (string) get_post_meta($post->ID, 'abc_design_employee_approved', true);
        $admin_approved = (string) get_post_meta($post->ID, 'abc_design_admin_approved', true);
        $web_downloadable = (string) get_post_meta($post->ID, 'abc_design_web_downloadable', true);

        $current_org_id = abc_b2b_designer_current_user_org_id();
        if ($org_id === '' && $current_org_id) {
            $org_id = (string) $current_org_id;
        }

        $org_users = [];
        if ($org_id !== '') {
            $org_users = get_users([
                'meta_key' => 'abc_b2b_org_id',
                'meta_value' => (int) $org_id,
                'number' => 200,
                'fields' => ['ID', 'display_name'],
            ]);
        }
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="abc_design_org_id">Organization ID</label></th>
                    <td><input type="text" name="abc_design_org_id" id="abc_design_org_id" value="<?php echo esc_attr($org_id); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_employee_id">Employee</label></th>
                    <td>
                        <select name="abc_design_employee_id" id="abc_design_employee_id">
                            <option value="">Select employee</option>
                            <?php foreach ($org_users as $user) : ?>
                                <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($employee_id, (string) $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_admin_id">Org Admin</label></th>
                    <td>
                        <select name="abc_design_admin_id" id="abc_design_admin_id">
                            <option value="">Select admin</option>
                            <?php foreach ($org_users as $user) : ?>
                                <?php $role = get_user_meta($user->ID, 'abc_b2b_org_role', true); ?>
                                <?php if ($role !== 'admin') { continue; } ?>
                                <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($admin_id, (string) $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_status">Status</label></th>
                    <td><input type="text" name="abc_design_status" id="abc_design_status" value="<?php echo esc_attr($status); ?>" class="regular-text" placeholder="Draft / In Review / Approved"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_quantity">Quantity</label></th>
                    <td><input type="text" name="abc_design_quantity" id="abc_design_quantity" value="<?php echo esc_attr($quantity); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_ink">Ink</label></th>
                    <td><input type="text" name="abc_design_ink" id="abc_design_ink" value="<?php echo esc_attr($ink); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_stock">Stock</label></th>
                    <td><input type="text" name="abc_design_stock" id="abc_design_stock" value="<?php echo esc_attr($stock); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_amount">Amount</label></th>
                    <td><input type="text" name="abc_design_amount" id="abc_design_amount" value="<?php echo esc_attr($amount); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_pdf_url">Design PDF URL</label></th>
                    <td><input type="text" name="abc_design_pdf_url" id="abc_design_pdf_url" value="<?php echo esc_attr($pdf_url); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row">Web Downloadable</th>
                    <td><label><input type="checkbox" name="abc_design_web_downloadable" value="1" <?php checked($web_downloadable, '1'); ?>> Allow customer PDF download</label></td>
                </tr>
                <tr>
                    <th scope="row">Employee Approval</th>
                    <td><label><input type="checkbox" name="abc_design_employee_approved" value="1" <?php checked($employee_approved, '1'); ?>> Employee approved information</label></td>
                </tr>
                <tr>
                    <th scope="row">Org Admin Approval</th>
                    <td><label><input type="checkbox" name="abc_design_admin_approved" value="1" <?php checked($admin_approved, '1'); ?>> Admin approved design, qty, ink, stock, amount</label></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_notes">Notes</label></th>
                    <td><textarea name="abc_design_notes" id="abc_design_notes" rows="4" class="large-text"><?php echo esc_textarea($notes); ?></textarea></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public function save_meta(int $post_id, WP_Post $post): void {
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }

        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = [
            'abc_design_org_id',
            'abc_design_employee_id',
            'abc_design_admin_id',
            'abc_design_status',
            'abc_design_notes',
            'abc_design_quantity',
            'abc_design_ink',
            'abc_design_stock',
            'abc_design_amount',
            'abc_design_pdf_url',
            'abc_design_template_id',
            'abc_design_estimate_id',
            'abc_design_employee_approved',
            'abc_design_admin_approved',
            'abc_design_web_downloadable',
        ];

        $is_org_admin = abc_b2b_designer_current_user_is_org_admin();
        $is_approved = abc_b2b_designer_current_user_is_approved();
        $current_org_id = abc_b2b_designer_current_user_org_id();
        $assigned_org_id = (int) get_post_meta($post_id, 'abc_design_org_id', true);
        $same_org = $current_org_id && $assigned_org_id === $current_org_id;

        foreach ($fields as $field) {
            if (!isset($_POST[$field])) {
                continue;
            }
            $value = wp_unslash($_POST[$field]);

            if (in_array($field, ['abc_design_admin_approved'], true) && (!$is_org_admin || !$same_org)) {
                continue;
            }

            if (in_array($field, ['abc_design_employee_approved'], true) && (!$is_approved || !$same_org)) {
                continue;
            }

            if (in_array($field, ['abc_design_employee_approved', 'abc_design_admin_approved', 'abc_design_web_downloadable'], true)) {
                $value = $value === '1' ? '1' : '0';
            } else {
                $value = sanitize_text_field((string) $value);
            }

            update_post_meta($post_id, $field, $value);
        }
    }

    public function filter_admin_list(WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        if ($query->get('post_type') !== self::POST_TYPE) {
            return;
        }

        if (current_user_can('manage_options')) {
            return;
        }

        $org_id = abc_b2b_designer_current_user_org_id();
        if ($org_id) {
            $query->set('meta_query', [
                [
                    'key' => 'abc_design_org_id',
                    'value' => (string) $org_id,
                    'compare' => '=',
                ],
            ]);
        }
    }
}
