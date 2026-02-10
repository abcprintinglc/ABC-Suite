<?php

class ABC_Estimator_Settings {
    public function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        $message = '';
        if (isset($_POST['abc_estimator_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['abc_estimator_settings_nonce'])), 'abc_estimator_settings_action')) {
            update_option('abc_square_access_token', sanitize_text_field(wp_unslash($_POST['abc_square_access_token'] ?? '')));
            update_option('abc_square_location_id', sanitize_text_field(wp_unslash($_POST['abc_square_location_id'] ?? '')));
            update_option('abc_square_currency', sanitize_text_field(wp_unslash($_POST['abc_square_currency'] ?? 'USD')));
            update_option('abc_b2b_design_request_page_id', absint($_POST['abc_b2b_design_request_page_id'] ?? 0));
            update_option('abc_b2b_design_approval_page_id', absint($_POST['abc_b2b_design_approval_page_id'] ?? 0));
            update_option('abc_hourly_rate', is_numeric($_POST['abc_hourly_rate'] ?? null) ? (string) (float) $_POST['abc_hourly_rate'] : '20');
            update_option('abc_click_bw_single', is_numeric($_POST['abc_click_bw_single'] ?? null) ? (string) (float) $_POST['abc_click_bw_single'] : '0');
            update_option('abc_click_bw_double', is_numeric($_POST['abc_click_bw_double'] ?? null) ? (string) (float) $_POST['abc_click_bw_double'] : '0');
            update_option('abc_click_color_single', is_numeric($_POST['abc_click_color_single'] ?? null) ? (string) (float) $_POST['abc_click_color_single'] : '0');
            update_option('abc_click_color_double', is_numeric($_POST['abc_click_color_double'] ?? null) ? (string) (float) $_POST['abc_click_color_double'] : '0');
            $message = 'Settings saved.';
        }

        $token = (string) get_option('abc_square_access_token', '');
        $location = (string) get_option('abc_square_location_id', '');
        $currency = (string) get_option('abc_square_currency', 'USD');
        $request_page_id = (int) get_option('abc_b2b_design_request_page_id', 0);
        $approval_page_id = (int) get_option('abc_b2b_design_approval_page_id', 0);
        $hourly_rate = (string) get_option('abc_hourly_rate', '20');
        $click_bw_single = (string) get_option('abc_click_bw_single', '0.02');
        $click_bw_double = (string) get_option('abc_click_bw_double', '0.04');
        $click_color_single = (string) get_option('abc_click_color_single', '0.08');
        $click_color_double = (string) get_option('abc_click_color_double', '0.16');
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Estimator Settings</h1>
            <?php if ($message !== '') : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('abc_estimator_settings_action', 'abc_estimator_settings_nonce'); ?>
                <h2>Pricing Defaults</h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="abc_hourly_rate">Hourly Rate ($ / hour)</label></th>
                            <td>
                                <input type="number" step="0.01" id="abc_hourly_rate" name="abc_hourly_rate" value="<?php echo esc_attr($hourly_rate); ?>" class="small-text">
                                <p class="description">Used as base labor rate. Minute rate is automatically calculated as hourly / 60.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">12x18 Click Rates</th>
                            <td>
                                <label>B/W Single</label> <input type="number" step="0.0001" name="abc_click_bw_single" value="<?php echo esc_attr($click_bw_single); ?>" class="small-text">
                                <label style="margin-left:12px;">B/W Double</label> <input type="number" step="0.0001" name="abc_click_bw_double" value="<?php echo esc_attr($click_bw_double); ?>" class="small-text"><br>
                                <label>Color Single</label> <input type="number" step="0.0001" name="abc_click_color_single" value="<?php echo esc_attr($click_color_single); ?>" class="small-text">
                                <label style="margin-left:12px;">Color Double</label> <input type="number" step="0.0001" name="abc_click_color_double" value="<?php echo esc_attr($click_color_double); ?>" class="small-text">
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2>Integrations</h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="abc_square_access_token">Square Access Token</label></th>
                            <td><input type="text" id="abc_square_access_token" name="abc_square_access_token" value="<?php echo esc_attr($token); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="abc_square_location_id">Square Location ID</label></th>
                            <td><input type="text" id="abc_square_location_id" name="abc_square_location_id" value="<?php echo esc_attr($location); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="abc_square_currency">Currency</label></th>
                            <td><input type="text" id="abc_square_currency" name="abc_square_currency" value="<?php echo esc_attr($currency); ?>" class="small-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="abc_b2b_design_request_page_id">Design Request Page ID</label></th>
                            <td><input type="text" id="abc_b2b_design_request_page_id" name="abc_b2b_design_request_page_id" value="<?php echo esc_attr((string) $request_page_id); ?>" class="small-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="abc_b2b_design_approval_page_id">Design Approval Page ID</label></th>
                            <td><input type="text" id="abc_b2b_design_approval_page_id" name="abc_b2b_design_approval_page_id" value="<?php echo esc_attr((string) $approval_page_id); ?>" class="small-text"></td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }
}
