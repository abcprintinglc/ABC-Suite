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
            $message = 'Settings saved.';
        }

        $token = (string) get_option('abc_square_access_token', '');
        $location = (string) get_option('abc_square_location_id', '');
        $currency = (string) get_option('abc_square_currency', 'USD');
        $request_page_id = (int) get_option('abc_b2b_design_request_page_id', 0);
        $approval_page_id = (int) get_option('abc_b2b_design_approval_page_id', 0);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Estimator Settings</h1>
            <?php if ($message !== '') : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('abc_estimator_settings_action', 'abc_estimator_settings_nonce'); ?>
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
