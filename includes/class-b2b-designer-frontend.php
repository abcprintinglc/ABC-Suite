<?php

class ABC_B2B_Designer_Frontend {
    public function register(): void {
        add_shortcode('abc_design_request_form', [$this, 'render_request_form']);
        add_shortcode('abc_design_request_approval', [$this, 'render_approval_form']);

        add_action('admin_post_abc_design_request_submit', [$this, 'handle_request_submit']);
        add_action('admin_post_abc_design_request_employee_approve', [$this, 'handle_employee_approve']);
        add_action('admin_post_abc_design_request_admin_approve', [$this, 'handle_admin_approve']);
    }

    public function render_request_form(): string {
        if (!is_user_logged_in()) {
            return '<p>Please log in to request a design proof.</p>';
        }

        $org_id = abc_b2b_designer_current_user_org_id();
        if (!$org_id) {
            return '<p>Your account is not linked to an organization.</p>';
        }

        $templates = get_posts([
            'post_type' => ABC_CPT_ABC_Product_Template::POST_TYPE,
            'posts_per_page' => 200,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'abc_template_org_id',
                    'value' => (string) $org_id,
                    'compare' => '=',
                ],
            ],
        ]);

        $general_templates = get_posts([
            'post_type' => ABC_CPT_ABC_Product_Template::POST_TYPE,
            'posts_per_page' => 200,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'abc_template_org_id',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);

        $org_users = get_users([
            'meta_key' => 'abc_b2b_org_id',
            'meta_value' => (int) $org_id,
            'number' => 200,
            'fields' => ['ID', 'display_name', 'user_email'],
        ]);

        ob_start();
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="abc-design-request-form">
            <?php wp_nonce_field('abc_design_request_submit', 'abc_design_request_nonce'); ?>
            <input type="hidden" name="action" value="abc_design_request_submit">
            <h3>Design Proof Request</h3>
            <p>
                <label>Template</label><br>
                <select name="template_id" required>
                    <option value="">Select template</option>
                    <?php foreach ($templates as $template) : ?>
                        <option value="<?php echo esc_attr($template->ID); ?>"><?php echo esc_html($template->post_title); ?></option>
                    <?php endforeach; ?>
                    <?php foreach ($general_templates as $template) : ?>
                        <option value="<?php echo esc_attr($template->ID); ?>"><?php echo esc_html($template->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label>Employee</label><br>
                <select name="employee_id" required>
                    <option value="">Select employee</option>
                    <?php foreach ($org_users as $user) : ?>
                        <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label>Quantity</label><br>
                <input type="text" name="quantity" required>
            </p>
            <p>
                <label>Ink</label><br>
                <input type="text" name="ink" required>
            </p>
            <p>
                <label>Stock</label><br>
                <input type="text" name="stock" required>
            </p>
            <p>
                <label>Amount</label><br>
                <input type="text" name="amount" required>
            </p>
            <p>
                <label>Notes</label><br>
                <textarea name="notes" rows="4"></textarea>
            </p>
            <button type="submit" class="button button-primary">Send Proof to Employee</button>
        </form>
        <?php
        return ob_get_clean();
    }

    public function render_approval_form(): string {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view this proof.</p>';
        }

        $request_id = isset($_GET['request_id']) ? absint($_GET['request_id']) : 0;
        if (!$request_id) {
            return '<p>Missing design request.</p>';
        }

        $request = get_post($request_id);
        if (!$request || $request->post_type !== ABC_Design_Request::POST_TYPE) {
            return '<p>Design request not found.</p>';
        }

        $employee_id = (int) get_post_meta($request_id, 'abc_design_employee_id', true);
        $org_id = (int) get_post_meta($request_id, 'abc_design_org_id', true);
        if ($org_id !== abc_b2b_designer_current_user_org_id()) {
            wp_die('Unauthorized.');
        }
        $admin_id = (int) get_post_meta($request_id, 'abc_design_admin_id', true);
        $employee_approved = (string) get_post_meta($request_id, 'abc_design_employee_approved', true);
        $admin_approved = (string) get_post_meta($request_id, 'abc_design_admin_approved', true);
        $quantity = (string) get_post_meta($request_id, 'abc_design_quantity', true);
        $ink = (string) get_post_meta($request_id, 'abc_design_ink', true);
        $stock = (string) get_post_meta($request_id, 'abc_design_stock', true);
        $amount = (string) get_post_meta($request_id, 'abc_design_amount', true);
        $pdf_url = (string) get_post_meta($request_id, 'abc_design_pdf_url', true);
        $estimate_id = (int) get_post_meta($request_id, 'abc_design_estimate_id', true);

        $current_user_id = get_current_user_id();
        $is_employee = $current_user_id === $employee_id;
        $is_admin = $current_user_id === $admin_id || abc_b2b_designer_current_user_is_org_admin();

        ob_start();
        ?>
        <div class="abc-design-approval">
            <h3>Design Proof Approval</h3>
            <p><strong>Request:</strong> <?php echo esc_html($request->post_title); ?></p>
            <ul>
                <li><strong>Quantity:</strong> <?php echo esc_html($quantity); ?></li>
                <li><strong>Ink:</strong> <?php echo esc_html($ink); ?></li>
                <li><strong>Stock:</strong> <?php echo esc_html($stock); ?></li>
                <li><strong>Amount:</strong> <?php echo esc_html($amount); ?></li>
            </ul>
            <?php if ($pdf_url) : ?>
                <p><a href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener">View PDF Proof</a></p>
            <?php endif; ?>

            <?php if ($is_employee && $employee_approved !== '1') : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('abc_design_request_employee_approve', 'abc_design_request_nonce'); ?>
                    <input type="hidden" name="action" value="abc_design_request_employee_approve">
                    <input type="hidden" name="request_id" value="<?php echo esc_attr((string) $request_id); ?>">
                    <button type="submit" class="button button-primary">Employee Approve</button>
                </form>
            <?php elseif ($employee_approved === '1') : ?>
                <p><strong>Employee approval:</strong> Approved.</p>
            <?php endif; ?>

            <?php if ($is_admin && $admin_approved !== '1') : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('abc_design_request_admin_approve', 'abc_design_request_nonce'); ?>
                    <input type="hidden" name="action" value="abc_design_request_admin_approve">
                    <input type="hidden" name="request_id" value="<?php echo esc_attr((string) $request_id); ?>">
                    <button type="submit" class="button button-primary">Org Admin Approve &amp; Create Estimate</button>
                </form>
            <?php elseif ($admin_approved === '1') : ?>
                <p><strong>Org admin approval:</strong> Approved.</p>
            <?php endif; ?>

            <?php if ($estimate_id) : ?>
                <p><a href="<?php echo esc_url(get_edit_post_link($estimate_id, 'raw')); ?>">View Job Jacket</a></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_request_submit(): void {
        if (!is_user_logged_in()) {
            wp_safe_redirect(wp_login_url());
            exit;
        }

        if (!isset($_POST['abc_design_request_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['abc_design_request_nonce'])), 'abc_design_request_submit')) {
            wp_die('Invalid submission.');
        }

        $org_id = abc_b2b_designer_current_user_org_id();
        $template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
        $employee_id = isset($_POST['employee_id']) ? absint($_POST['employee_id']) : 0;
        $quantity = sanitize_text_field(wp_unslash($_POST['quantity'] ?? ''));
        $ink = sanitize_text_field(wp_unslash($_POST['ink'] ?? ''));
        $stock = sanitize_text_field(wp_unslash($_POST['stock'] ?? ''));
        $amount = sanitize_text_field(wp_unslash($_POST['amount'] ?? ''));
        $notes = sanitize_textarea_field(wp_unslash($_POST['notes'] ?? ''));

        if (!$org_id || !$template_id || !$employee_id) {
            wp_die('Missing required fields.');
        }

        $template = get_post($template_id);
        $title = $template ? $template->post_title : 'Design Request';

        $request_id = wp_insert_post([
            'post_type' => ABC_Design_Request::POST_TYPE,
            'post_title' => $title . ' Proof',
            'post_status' => 'publish',
        ]);

        if (is_wp_error($request_id)) {
            wp_die('Unable to create request.');
        }

        update_post_meta($request_id, 'abc_design_org_id', (string) $org_id);
        update_post_meta($request_id, 'abc_design_template_id', (string) $template_id);
        update_post_meta($request_id, 'abc_design_employee_id', (string) $employee_id);
        update_post_meta($request_id, 'abc_design_admin_id', (string) $this->find_org_admin($org_id));
        update_post_meta($request_id, 'abc_design_status', 'Pending Employee Approval');
        update_post_meta($request_id, 'abc_design_quantity', $quantity);
        update_post_meta($request_id, 'abc_design_ink', $ink);
        update_post_meta($request_id, 'abc_design_stock', $stock);
        update_post_meta($request_id, 'abc_design_amount', $amount);
        update_post_meta($request_id, 'abc_design_notes', $notes);

        $approval_url = add_query_arg(['request_id' => (int) $request_id], $this->approval_page_url());
        $employee = get_user_by('id', $employee_id);
        if ($employee) {
            wp_mail($employee->user_email, 'Proof approval requested', "Please review and approve: {$approval_url}");
        }

        wp_safe_redirect(add_query_arg(['request_sent' => 1], $this->approval_page_url()));
        exit;
    }

    public function handle_employee_approve(): void {
        $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
        if (!$request_id) {
            wp_die('Missing request.');
        }

        if (!isset($_POST['abc_design_request_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['abc_design_request_nonce'])), 'abc_design_request_employee_approve')) {
            wp_die('Invalid submission.');
        }

        $employee_id = (int) get_post_meta($request_id, 'abc_design_employee_id', true);
        if (get_current_user_id() !== $employee_id) {
            wp_die('Unauthorized.');
        }

        update_post_meta($request_id, 'abc_design_employee_approved', '1');
        update_post_meta($request_id, 'abc_design_status', 'Pending Admin Approval');

        $admin_id = (int) get_post_meta($request_id, 'abc_design_admin_id', true);
        $admin = $admin_id ? get_user_by('id', $admin_id) : null;
        if ($admin) {
            $approval_url = add_query_arg(['request_id' => (int) $request_id], $this->approval_page_url());
            wp_mail($admin->user_email, 'Design ready for admin approval', "Please approve: {$approval_url}");
        }

        wp_safe_redirect(add_query_arg(['request_id' => $request_id], $this->approval_page_url()));
        exit;
    }

    public function handle_admin_approve(): void {
        $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
        if (!$request_id) {
            wp_die('Missing request.');
        }

        if (!isset($_POST['abc_design_request_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['abc_design_request_nonce'])), 'abc_design_request_admin_approve')) {
            wp_die('Invalid submission.');
        }

        if (!abc_b2b_designer_current_user_is_org_admin()) {
            wp_die('Unauthorized.');
        }
        $org_id = (int) get_post_meta($request_id, 'abc_design_org_id', true);
        if ($org_id !== abc_b2b_designer_current_user_org_id()) {
            wp_die('Unauthorized.');
        }

        $employee_approved = (string) get_post_meta($request_id, 'abc_design_employee_approved', true);
        if ($employee_approved !== '1') {
            wp_die('Employee approval required.');
        }

        update_post_meta($request_id, 'abc_design_admin_approved', '1');
        update_post_meta($request_id, 'abc_design_status', 'Approved');

        $estimate_id = $this->ensure_estimate_for_request($request_id);
        if ($estimate_id) {
            update_post_meta($request_id, 'abc_design_estimate_id', (string) $estimate_id);
            $this->maybe_create_square_invoice($estimate_id);
        }

        wp_safe_redirect(add_query_arg(['request_id' => $request_id], $this->approval_page_url()));
        exit;
    }

    private function ensure_estimate_for_request(int $request_id): int {
        $existing = (int) get_post_meta($request_id, 'abc_design_estimate_id', true);
        if ($existing) {
            return $existing;
        }

        $title = get_the_title($request_id);
        $estimate_id = wp_insert_post([
            'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
            'post_title' => $title ? $title : 'Estimate',
            'post_status' => 'publish',
        ]);

        if (is_wp_error($estimate_id)) {
            return 0;
        }

        update_post_meta($estimate_id, 'abc_design_request_id', (string) $request_id);
        update_post_meta($estimate_id, 'abc_status', 'estimate');

        $quantity = (float) get_post_meta($request_id, 'abc_design_quantity', true);
        $amount = (float) get_post_meta($request_id, 'abc_design_amount', true);
        $line_items = [
            [
                'template_id' => 0,
                'product_label' => get_the_title($request_id),
                'qty' => $quantity ?: 1,
                'options_json' => [
                    'Ink' => get_post_meta($request_id, 'abc_design_ink', true),
                    'Stock' => get_post_meta($request_id, 'abc_design_stock', true),
                ],
                'vendor' => 'Org Approved',
                'cost_snapshot' => '0.00',
                'markup_type' => 'percent',
                'markup_value' => 0,
                'sell_price' => number_format($amount, 2, '.', ''),
                'custom_product_name' => get_the_title($request_id),
            ],
        ];
        update_post_meta($estimate_id, 'abc_estimate_data', wp_json_encode($line_items));
        update_post_meta($estimate_id, 'abc_line_items_json', wp_json_encode($line_items));

        return (int) $estimate_id;
    }

    private function maybe_create_square_invoice(int $estimate_id): void {
        $token = (string) get_option('abc_square_access_token', '');
        $location_id = (string) get_option('abc_square_location_id', '');
        $currency = (string) get_option('abc_square_currency', 'USD');

        if ($token === '' || $location_id === '') {
            return;
        }

        $line_items_json = get_post_meta($estimate_id, 'abc_estimate_data', true) ?: get_post_meta($estimate_id, 'abc_line_items_json', true);
        $line_items = json_decode((string) $line_items_json, true);
        if (!is_array($line_items) || empty($line_items)) {
            return;
        }

        $order_items = [];
        foreach ($line_items as $item) {
            $qty = (float) ($item['qty'] ?? 1);
            $qty = $qty > 0 ? $qty : 1;
            $sell = (float) ($item['sell_price'] ?? 0);
            $name = (string) ($item['custom_product_name'] ?? $item['product_label'] ?? 'Estimate Item');
            $order_items[] = [
                'name' => $name,
                'quantity' => (string) $qty,
                'base_price_money' => [
                    'amount' => (int) round($sell * 100),
                    'currency' => $currency,
                ],
            ];
        }

        $order_response = $this->square_request('POST', '/v2/orders', $token, [
            'idempotency_key' => wp_generate_uuid4(),
            'order' => [
                'location_id' => $location_id,
                'line_items' => $order_items,
            ],
        ]);

        if (is_wp_error($order_response) || empty($order_response['order']['id'])) {
            return;
        }

        $order_id = $order_response['order']['id'];
        $invoice_payload = [
            'idempotency_key' => wp_generate_uuid4(),
            'invoice' => [
                'location_id' => $location_id,
                'order_id' => $order_id,
                'payment_requests' => [
                    [
                        'request_type' => 'BALANCE',
                        'due_date' => gmdate('Y-m-d'),
                    ],
                ],
            ],
        ];

        $invoice_response = $this->square_request('POST', '/v2/invoices', $token, $invoice_payload);
        if (is_wp_error($invoice_response) || empty($invoice_response['invoice']['id'])) {
            return;
        }

        $invoice_id = $invoice_response['invoice']['id'];
        $status = $invoice_response['invoice']['status'] ?? '';
        update_post_meta($estimate_id, 'abc_square_invoice_id', $invoice_id);
        update_post_meta($estimate_id, 'abc_square_invoice_status', $status);
    }

    private function square_request(string $method, string $path, string $token, array $body) {
        $response = wp_remote_request('https://connect.squareup.com' . $path, [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Square-Version' => '2024-04-17',
            ],
            'body' => wp_json_encode($body),
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code >= 400) {
            return new WP_Error('square_error', 'Square API error', $data);
        }

        return $data;
    }

    private function find_org_admin(int $org_id): int {
        $admins = get_users([
            'meta_key' => 'abc_b2b_org_id',
            'meta_value' => (int) $org_id,
            'number' => 200,
            'fields' => ['ID'],
        ]);
        foreach ($admins as $user) {
            if (get_user_meta($user->ID, 'abc_b2b_org_role', true) === 'admin') {
                return (int) $user->ID;
            }
        }
        return 0;
    }

    private function approval_page_url(): string {
        $page_id = (int) get_option('abc_b2b_design_approval_page_id', 0);
        if ($page_id && get_post($page_id)) {
            return get_permalink($page_id);
        }
        return home_url('/');
    }
}
