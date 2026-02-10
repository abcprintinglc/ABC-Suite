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
            'abc_design_draft_id',
            'abc_design_customer_message',
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

        foreach (['abc_design_employee_approved','abc_design_admin_approved','abc_design_web_downloadable'] as $key) {
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
            'Design Request (B2B Approval Flow)',
            [$this, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_meta_box(WP_Post $post): void {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $meta = [
            'org_id' => (string) get_post_meta($post->ID, 'abc_design_org_id', true),
            'employee_id' => (string) get_post_meta($post->ID, 'abc_design_employee_id', true),
            'admin_id' => (string) get_post_meta($post->ID, 'abc_design_admin_id', true),
            'status' => (string) get_post_meta($post->ID, 'abc_design_status', true),
            'notes' => (string) get_post_meta($post->ID, 'abc_design_notes', true),
            'quantity' => (string) get_post_meta($post->ID, 'abc_design_quantity', true),
            'ink' => (string) get_post_meta($post->ID, 'abc_design_ink', true),
            'stock' => (string) get_post_meta($post->ID, 'abc_design_stock', true),
            'amount' => (string) get_post_meta($post->ID, 'abc_design_amount', true),
            'pdf_url' => (string) get_post_meta($post->ID, 'abc_design_pdf_url', true),
            'estimate_id' => (string) get_post_meta($post->ID, 'abc_design_estimate_id', true),
            'draft_id' => (string) get_post_meta($post->ID, 'abc_design_draft_id', true),
            'message' => (string) get_post_meta($post->ID, 'abc_design_customer_message', true),
            'employee_approved' => (string) get_post_meta($post->ID, 'abc_design_employee_approved', true),
            'admin_approved' => (string) get_post_meta($post->ID, 'abc_design_admin_approved', true),
            'web_downloadable' => (string) get_post_meta($post->ID, 'abc_design_web_downloadable', true),
        ];

        $customers = get_users(['role__in' => ['customer'], 'number' => 300, 'orderby' => 'display_name', 'order' => 'ASC']);
        $admins = get_users(['meta_key' => 'abc_b2b_org_role', 'meta_value' => 'admin', 'number' => 300, 'orderby' => 'display_name', 'order' => 'ASC']);
        $estimates = get_posts(['post_type' => ABC_CPT_ABC_Estimate::POST_TYPE, 'posts_per_page' => 250, 'post_status' => 'any', 'orderby' => 'date', 'order' => 'DESC']);
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="abc_design_estimate_id">Estimate Entry</label></th>
                    <td>
                        <select name="abc_design_estimate_id" id="abc_design_estimate_id">
                            <option value="">Select estimate</option>
                            <?php foreach ($estimates as $estimate) : ?>
                                <option value="<?php echo esc_attr((string) $estimate->ID); ?>" <?php selected($meta['estimate_id'], (string) $estimate->ID); ?>>#<?php echo esc_html((string) get_post_meta($estimate->ID, 'abc_invoice_number', true)); ?> — <?php echo esc_html($estimate->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_employee_id">Client (Customer User)</label></th>
                    <td>
                        <select name="abc_design_employee_id" id="abc_design_employee_id">
                            <option value="">Select customer</option>
                            <?php foreach ($customers as $customer) : ?>
                                <?php $org = (string) get_user_meta($customer->ID, 'abc_b2b_org_id', true); ?>
                                <option value="<?php echo esc_attr((string) $customer->ID); ?>" data-org="<?php echo esc_attr($org); ?>" <?php selected($meta['employee_id'], (string) $customer->ID); ?>><?php echo esc_html($customer->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Uses the same org/user linkage model as ABC B2B Designer.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_org_id">Organization ID</label></th>
                    <td><input type="text" name="abc_design_org_id" id="abc_design_org_id" value="<?php echo esc_attr($meta['org_id']); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_admin_id">Store Manager (Org Admin)</label></th>
                    <td>
                        <select name="abc_design_admin_id" id="abc_design_admin_id">
                            <option value="">Select store manager</option>
                            <?php foreach ($admins as $admin) : ?>
                                <option value="<?php echo esc_attr((string) $admin->ID); ?>" <?php selected($meta['admin_id'], (string) $admin->ID); ?>><?php echo esc_html($admin->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_draft_id">B2B Draft ID</label></th>
                    <td><input type="text" name="abc_design_draft_id" id="abc_design_draft_id" value="<?php echo esc_attr($meta['draft_id']); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_status">Status</label></th>
                    <td>
                        <select name="abc_design_status" id="abc_design_status">
                            <?php foreach (['draft','awaiting_client','awaiting_manager','approved','in_production','completed'] as $st) : ?>
                                <option value="<?php echo esc_attr($st); ?>" <?php selected($meta['status'], $st); ?>><?php echo esc_html(ucwords(str_replace('_', ' ', $st))); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_quantity">Quantity</label></th>
                    <td><input type="text" name="abc_design_quantity" id="abc_design_quantity" value="<?php echo esc_attr($meta['quantity']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_ink">Ink</label></th>
                    <td><input type="text" name="abc_design_ink" id="abc_design_ink" value="<?php echo esc_attr($meta['ink']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_stock">Stock</label></th>
                    <td><input type="text" name="abc_design_stock" id="abc_design_stock" value="<?php echo esc_attr($meta['stock']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_amount">Amount</label></th>
                    <td><input type="text" name="abc_design_amount" id="abc_design_amount" value="<?php echo esc_attr($meta['amount']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_pdf_url">Proof URL</label></th>
                    <td><input type="text" name="abc_design_pdf_url" id="abc_design_pdf_url" value="<?php echo esc_attr($meta['pdf_url']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_customer_message">Customer Message</label></th>
                    <td><textarea name="abc_design_customer_message" id="abc_design_customer_message" rows="3" class="large-text"><?php echo esc_textarea($meta['message']); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row">Approvals</th>
                    <td>
                        <label><input type="checkbox" name="abc_design_employee_approved" value="1" <?php checked($meta['employee_approved'], '1'); ?>> Client approved</label><br>
                        <label><input type="checkbox" name="abc_design_admin_approved" value="1" <?php checked($meta['admin_approved'], '1'); ?>> Store manager approved</label><br>
                        <label><input type="checkbox" name="abc_design_web_downloadable" value="1" <?php checked($meta['web_downloadable'], '1'); ?>> Allow client proof download</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_design_notes">Internal Notes</label></th>
                    <td><textarea name="abc_design_notes" id="abc_design_notes" rows="4" class="large-text"><?php echo esc_textarea($meta['notes']); ?></textarea></td>
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

        if (isset($_POST['abc_design_employee_id'])) {
            $employee_id = absint(wp_unslash($_POST['abc_design_employee_id']));
            if ($employee_id > 0) {
                $linked_org_id = (int) get_user_meta($employee_id, 'abc_b2b_org_id', true);
                if ($linked_org_id > 0) {
                    $_POST['abc_design_org_id'] = (string) $linked_org_id;
                }
                $org_admins = get_users([
                    'meta_key' => 'abc_b2b_org_id',
                    'meta_value' => $linked_org_id,
                    'number' => 1,
                    'fields' => ['ID'],
                ]);
                foreach ($org_admins as $admin) {
                    if (get_user_meta((int) $admin->ID, 'abc_b2b_org_role', true) === 'admin') {
                        $_POST['abc_design_admin_id'] = (string) (int) $admin->ID;
                        break;
                    }
                }
            }
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
            'abc_design_draft_id',
            'abc_design_customer_message',
            'abc_design_employee_approved',
            'abc_design_admin_approved',
            'abc_design_web_downloadable',
        ];

        foreach ($fields as $field) {
            if (!isset($_POST[$field])) {
                if (in_array($field, ['abc_design_employee_approved', 'abc_design_admin_approved', 'abc_design_web_downloadable'], true)) {
                    update_post_meta($post_id, $field, '0');
                }
                continue;
            }
            $value = wp_unslash($_POST[$field]);
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
