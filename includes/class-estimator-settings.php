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
            update_option('abc_lease_monthly_cost', is_numeric($_POST['abc_lease_monthly_cost'] ?? null) ? (string) (float) $_POST['abc_lease_monthly_cost'] : '0');
            update_option('abc_service_monthly_cost', is_numeric($_POST['abc_service_monthly_cost'] ?? null) ? (string) (float) $_POST['abc_service_monthly_cost'] : '0');
            update_option('abc_expected_bw_clicks', absint($_POST['abc_expected_bw_clicks'] ?? 0));
            update_option('abc_expected_color_clicks', absint($_POST['abc_expected_color_clicks'] ?? 0));
            update_option('abc_contract_bw_click_cost', is_numeric($_POST['abc_contract_bw_click_cost'] ?? null) ? (string) (float) $_POST['abc_contract_bw_click_cost'] : '0');
            update_option('abc_contract_color_click_cost', is_numeric($_POST['abc_contract_color_click_cost'] ?? null) ? (string) (float) $_POST['abc_contract_color_click_cost'] : '0');
            update_option('abc_target_margin_pct', is_numeric($_POST['abc_target_margin_pct'] ?? null) ? (string) (float) $_POST['abc_target_margin_pct'] : '40');

            if (isset($_POST['abc_duplo_trim_presets'])) {
                $raw_duplo = wp_unslash($_POST['abc_duplo_trim_presets']);
                $decoded_duplo = json_decode((string) $raw_duplo, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_duplo)) {
                    update_option('abc_duplo_trim_presets', wp_json_encode($decoded_duplo, JSON_PRETTY_PRINT));
                }
            }

            $lease = (float) get_option('abc_lease_monthly_cost', '0');
            $service = (float) get_option('abc_service_monthly_cost', '0');
            $expected_bw = (int) get_option('abc_expected_bw_clicks', 0);
            $expected_color = (int) get_option('abc_expected_color_clicks', 0);
            $contract_bw = (float) get_option('abc_contract_bw_click_cost', '0');
            $contract_color = (float) get_option('abc_contract_color_click_cost', '0');
            $margin_pct = (float) get_option('abc_target_margin_pct', '40');

            $monthly_total_clicks = max(1, $expected_bw + $expected_color);
            $overhead_per_click = ($lease + $service) / $monthly_total_clicks;
            $margin_factor = max(0.01, 1 - ($margin_pct / 100));
            $recommended_bw = ($contract_bw + $overhead_per_click) / $margin_factor;
            $recommended_color = ($contract_color + $overhead_per_click) / $margin_factor;

            update_option('abc_click_bw_single', (string) round($recommended_bw, 4));
            update_option('abc_click_bw_double', (string) round($recommended_bw * 2, 4));
            update_option('abc_click_color_single', (string) round($recommended_color, 4));
            update_option('abc_click_color_double', (string) round($recommended_color * 2, 4));

            $message = 'Settings saved. Click sell rates were recalculated from lease/contract costs and target margin.';
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
        $lease_monthly = (string) get_option('abc_lease_monthly_cost', '0');
        $service_monthly = (string) get_option('abc_service_monthly_cost', '0');
        $expected_bw = (string) get_option('abc_expected_bw_clicks', '0');
        $expected_color = (string) get_option('abc_expected_color_clicks', '0');
        $contract_bw = (string) get_option('abc_contract_bw_click_cost', '0');
        $contract_color = (string) get_option('abc_contract_color_click_cost', '0');
        $target_margin_pct = (string) get_option('abc_target_margin_pct', '40');
        $duplo_presets = (string) get_option('abc_duplo_trim_presets', '');
        if ($duplo_presets === '') {
            $duplo_presets = wp_json_encode([
                'default' => [
                    'setup_minutes' => 8,
                    'cost' => 12.50,
                ],
            ], JSON_PRETTY_PRINT);
        }
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

                <h2>Profitability Model (Lease / Contract)</h2>
                <p class="description">Enter your contract and lease costs to auto-calculate profitable click sell rates. If your click rates PDF is updated, re-enter values here and save.</p>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">Monthly Fixed Costs</th>
                            <td>
                                <label>Lease</label> <input type="number" step="0.01" name="abc_lease_monthly_cost" value="<?php echo esc_attr($lease_monthly); ?>" class="small-text">
                                <label style="margin-left:12px;">Service / Maintenance</label> <input type="number" step="0.01" name="abc_service_monthly_cost" value="<?php echo esc_attr($service_monthly); ?>" class="small-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Expected Monthly Volume</th>
                            <td>
                                <label>B/W Clicks</label> <input type="number" name="abc_expected_bw_clicks" value="<?php echo esc_attr($expected_bw); ?>" class="small-text">
                                <label style="margin-left:12px;">Color Clicks</label> <input type="number" name="abc_expected_color_clicks" value="<?php echo esc_attr($expected_color); ?>" class="small-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Contract Cost / Click</th>
                            <td>
                                <label>B/W Cost</label> <input type="number" step="0.0001" name="abc_contract_bw_click_cost" value="<?php echo esc_attr($contract_bw); ?>" class="small-text">
                                <label style="margin-left:12px;">Color Cost</label> <input type="number" step="0.0001" name="abc_contract_color_click_cost" value="<?php echo esc_attr($contract_color); ?>" class="small-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Target Margin %</th>
                            <td><input type="number" step="0.01" name="abc_target_margin_pct" value="<?php echo esc_attr($target_margin_pct); ?>" class="small-text"></td>
                        </tr>
                    </tbody>
                </table>

                <h2>Duplo Trim Presets</h2>
                <p class="description">Paste Duplo trim settings JSON (from All 2-10-26.pdf). Keys can be trim profile names; each value should include <code>setup_minutes</code> and <code>cost</code>.</p>
                <textarea name="abc_duplo_trim_presets" rows="10" class="large-text code"><?php echo esc_textarea($duplo_presets); ?></textarea>

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
