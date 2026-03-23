<?php

class ABC_OpenClaw_Bridge {
    private const OPTION_URL = 'abc_openclaw_url';
    private const OPTION_TOKEN = 'abc_openclaw_token';
    private const OPTION_ENABLED = 'abc_openclaw_enabled';
    private const OPTION_LAST_RESULT = 'abc_openclaw_last_result';
    private const OPTION_LAST_PAYLOAD = 'abc_openclaw_last_payload';
    private const MENU_SLUG = 'abc-openclaw-bridge';

    public function register(): void {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_abc_openclaw_save_settings', [$this, 'handle_save_settings']);
        add_action('admin_post_abc_openclaw_test_connection', [$this, 'handle_test_connection']);
        add_action('woocommerce_new_order', [$this, 'handle_new_order'], 10, 1);
        add_action('woocommerce_order_status_changed', [$this, 'handle_order_status_changed'], 10, 4);
    }

    public function register_menu(): void {
        add_submenu_page(
            ABC_Admin_Suite_Menu::MENU_SLUG,
            'OpenClaw Bridge',
            'OpenClaw Bridge',
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_page']
        );
    }

    public function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        $url = (string) get_option(self::OPTION_URL, '');
        $token = (string) get_option(self::OPTION_TOKEN, '');
        $enabled = (string) get_option(self::OPTION_ENABLED, '0') === '1';
        $last_result = get_option(self::OPTION_LAST_RESULT, []);
        $last_payload = (string) get_option(self::OPTION_LAST_PAYLOAD, '');
        if (!is_array($last_result)) {
            $last_result = [];
        }

        $message = isset($_GET['abc_openclaw_message']) ? sanitize_text_field(wp_unslash($_GET['abc_openclaw_message'])) : '';
        $message_type = isset($_GET['abc_openclaw_type']) ? sanitize_key(wp_unslash($_GET['abc_openclaw_type'])) : 'success';
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">OpenClaw Bridge</h1>
            <p>Connect ABC Suite to your OpenClaw gateway so orders and test payloads can be sent to your local automation server.</p>

            <?php if ($message !== '') : ?>
                <div class="notice notice-<?php echo esc_attr($message_type === 'error' ? 'error' : 'success'); ?> is-dismissible"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>

            <div style="max-width: 1000px; display: grid; grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr); gap: 24px; align-items: start; margin-top: 18px;">
                <div>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="abc_openclaw_save_settings">
                        <?php wp_nonce_field('abc_openclaw_save_settings', 'abc_openclaw_nonce'); ?>

                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="abc_openclaw_enabled">Enable Bridge</label></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="abc_openclaw_enabled" name="abc_openclaw_enabled" value="1" <?php checked($enabled); ?>>
                                            Allow ABC Suite to send payloads to OpenClaw.
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="abc_openclaw_url">OpenClaw Webhook URL</label></th>
                                    <td>
                                        <input type="url" id="abc_openclaw_url" name="abc_openclaw_url" value="<?php echo esc_attr($url); ?>" class="regular-text code" placeholder="https://your-tunnel.trycloudflare.com/webhook/order-intake">
                                        <p class="description">Paste the full URL you want WordPress to call. This can be your Cloudflare tunnel URL that points to your OpenClaw endpoint.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="abc_openclaw_token">Shared Secret Token</label></th>
                                    <td>
                                        <input type="text" id="abc_openclaw_token" name="abc_openclaw_token" value="<?php echo esc_attr($token); ?>" class="regular-text code" placeholder="paste-a-shared-token-here">
                                        <p class="description">Sent in the <code>X-ABC-OpenClaw-Token</code> header so your endpoint can verify the request came from this site.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <?php submit_button('Save OpenClaw Settings'); ?>
                    </form>

                    <hr>

                    <h2>Test Connection</h2>
                    <p>Use this after saving your URL and token. It sends a small test payload so you can confirm the bridge is working before turning on WooCommerce automation.</p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="abc_openclaw_test_connection">
                        <?php wp_nonce_field('abc_openclaw_test_connection', 'abc_openclaw_test_nonce'); ?>
                        <?php submit_button('Send Test Payload', 'secondary'); ?>
                    </form>
                </div>

                <div>
                    <div class="postbox" style="padding: 16px;">
                        <h2 style="margin-top: 0;">Last Request Status</h2>
                        <?php if (!empty($last_result)) : ?>
                            <p><strong>Time:</strong> <?php echo esc_html((string) ($last_result['time'] ?? '')); ?></p>
                            <p><strong>Outcome:</strong> <?php echo esc_html((string) ($last_result['status'] ?? 'unknown')); ?></p>
                            <p><strong>HTTP Code:</strong> <?php echo esc_html((string) ($last_result['http_code'] ?? 'n/a')); ?></p>
                            <p><strong>Message:</strong> <?php echo esc_html((string) ($last_result['message'] ?? '')); ?></p>
                        <?php else : ?>
                            <p>No requests have been sent yet.</p>
                        <?php endif; ?>
                    </div>

                    <div class="postbox" style="padding: 16px; margin-top: 16px;">
                        <h2 style="margin-top: 0;">Last Payload</h2>
                        <?php if ($last_payload !== '') : ?>
                            <textarea readonly rows="16" class="large-text code"><?php echo esc_textarea($last_payload); ?></textarea>
                        <?php else : ?>
                            <p>No payload saved yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function handle_save_settings(): void {
        $this->assert_manage_options();
        check_admin_referer('abc_openclaw_save_settings', 'abc_openclaw_nonce');

        update_option(self::OPTION_ENABLED, isset($_POST['abc_openclaw_enabled']) ? '1' : '0');
        update_option(self::OPTION_URL, esc_url_raw((string) wp_unslash($_POST['abc_openclaw_url'] ?? '')));
        update_option(self::OPTION_TOKEN, sanitize_text_field((string) wp_unslash($_POST['abc_openclaw_token'] ?? '')));

        $this->redirect_with_notice('OpenClaw settings saved.');
    }

    public function handle_test_connection(): void {
        $this->assert_manage_options();
        check_admin_referer('abc_openclaw_test_connection', 'abc_openclaw_test_nonce');

        $payload = [
            'source' => 'abc_suite',
            'event' => 'test_connection',
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url('/'),
            'timestamp_utc' => gmdate('c'),
            'message' => 'Test payload from ABC Suite OpenClaw Bridge.',
        ];

        $result = $this->send_payload($payload);
        $this->redirect_with_notice($result['message'] ?? 'Test request completed.', ($result['status'] ?? '') === 'error' ? 'error' : 'success');
    }

    public function handle_new_order($order_id): void {
        if (!$this->is_bridge_enabled() || !class_exists('WC_Order')) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $payload = $this->build_order_payload($order, 'new_order');
        $this->send_payload($payload);
    }

    public function handle_order_status_changed($order_id, $old_status, $new_status, $order): void {
        if (!$this->is_bridge_enabled()) {
            return;
        }

        if (!$order && function_exists('wc_get_order')) {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            return;
        }

        $payload = $this->build_order_payload($order, 'order_status_changed');
        $payload['old_status'] = (string) $old_status;
        $payload['new_status'] = (string) $new_status;

        $this->send_payload($payload);
    }

    private function build_order_payload($order, string $event): array {
        $items = [];
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $items[] = [
                'item_id' => $item_id,
                'name' => $item->get_name(),
                'quantity' => (int) $item->get_quantity(),
                'total' => (string) $item->get_total(),
                'sku' => $product ? (string) $product->get_sku() : '',
            ];
        }

        return [
            'source' => 'abc_suite',
            'event' => $event,
            'timestamp_utc' => gmdate('c'),
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url('/'),
            'order' => [
                'id' => (int) $order->get_id(),
                'number' => (string) $order->get_order_number(),
                'status' => (string) $order->get_status(),
                'currency' => (string) $order->get_currency(),
                'total' => (string) $order->get_total(),
                'customer_note' => (string) $order->get_customer_note(),
                'payment_method' => (string) $order->get_payment_method_title(),
                'created' => $order->get_date_created() ? $order->get_date_created()->date('c') : '',
                'billing' => [
                    'first_name' => (string) $order->get_billing_first_name(),
                    'last_name' => (string) $order->get_billing_last_name(),
                    'company' => (string) $order->get_billing_company(),
                    'email' => (string) $order->get_billing_email(),
                    'phone' => (string) $order->get_billing_phone(),
                ],
                'shipping' => [
                    'first_name' => (string) $order->get_shipping_first_name(),
                    'last_name' => (string) $order->get_shipping_last_name(),
                    'company' => (string) $order->get_shipping_company(),
                ],
                'items' => $items,
            ],
        ];
    }

    private function send_payload(array $payload): array {
        $url = trim((string) get_option(self::OPTION_URL, ''));
        $token = (string) get_option(self::OPTION_TOKEN, '');

        update_option(self::OPTION_LAST_PAYLOAD, wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), false);

        if ($url === '') {
            $result = [
                'time' => current_time('mysql'),
                'status' => 'error',
                'http_code' => '',
                'message' => 'OpenClaw URL is empty. Save the bridge settings first.',
            ];
            update_option(self::OPTION_LAST_RESULT, $result, false);
            return $result;
        }

        $response = wp_remote_post($url, [
            'timeout' => 20,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-ABC-OpenClaw-Token' => $token,
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            $result = [
                'time' => current_time('mysql'),
                'status' => 'error',
                'http_code' => '',
                'message' => $response->get_error_message(),
            ];
            update_option(self::OPTION_LAST_RESULT, $result, false);
            return $result;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = trim((string) wp_remote_retrieve_body($response));
        if (strlen($body) > 500) {
            $body = substr($body, 0, 500) . '…';
        }

        $result = [
            'time' => current_time('mysql'),
            'status' => $code >= 200 && $code < 300 ? 'success' : 'error',
            'http_code' => $code,
            'message' => $body !== '' ? $body : 'Request sent. Empty response body.',
        ];

        update_option(self::OPTION_LAST_RESULT, $result, false);
        return $result;
    }

    private function is_bridge_enabled(): bool {
        return (string) get_option(self::OPTION_ENABLED, '0') === '1' && trim((string) get_option(self::OPTION_URL, '')) !== '';
    }

    private function assert_manage_options(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }
    }

    private function redirect_with_notice(string $message, string $type = 'success'): void {
        wp_safe_redirect(add_query_arg([
            'page' => self::MENU_SLUG,
            'abc_openclaw_message' => rawurlencode($message),
            'abc_openclaw_type' => $type,
        ], admin_url('admin.php')));
        exit;
    }
}
