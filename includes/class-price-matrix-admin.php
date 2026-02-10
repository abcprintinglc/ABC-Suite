<?php

class ABC_Price_Matrix_Admin {
    public function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        $message = '';
        if (isset($_POST['abc_price_matrix_v2_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['abc_price_matrix_v2_nonce'])), 'abc_price_matrix_v2_action')) {
            $profiles = [];
            $categories = ['commercial_printing', 'raised_printing', 'banners', 'apparel', 'booklets'];
            foreach ($categories as $cat) {
                $profiles[$cat] = [
                    'label' => ucwords(str_replace('_', ' ', $cat)),
                    'setup_minutes' => isset($_POST[$cat . '_setup_minutes']) ? absint($_POST[$cat . '_setup_minutes']) : 0,
                    'markup_percent' => isset($_POST[$cat . '_markup_percent']) && is_numeric($_POST[$cat . '_markup_percent']) ? (float) $_POST[$cat . '_markup_percent'] : 0,
                    'min_order' => isset($_POST[$cat . '_min_order']) && is_numeric($_POST[$cat . '_min_order']) ? (float) $_POST[$cat . '_min_order'] : 0,
                ];
            }
            update_option('abc_click_price_profiles', $profiles);

            $finishing = [
                'perf' => isset($_POST['finish_perf']) && is_numeric($_POST['finish_perf']) ? (float) $_POST['finish_perf'] : 0,
                'foil' => isset($_POST['finish_foil']) && is_numeric($_POST['finish_foil']) ? (float) $_POST['finish_foil'] : 0,
                'fold' => isset($_POST['finish_fold']) && is_numeric($_POST['finish_fold']) ? (float) $_POST['finish_fold'] : 0,
                'score' => isset($_POST['finish_score']) && is_numeric($_POST['finish_score']) ? (float) $_POST['finish_score'] : 0,
                'pad' => isset($_POST['finish_pad']) && is_numeric($_POST['finish_pad']) ? (float) $_POST['finish_pad'] : 0,
                'ncr' => isset($_POST['finish_ncr']) && is_numeric($_POST['finish_ncr']) ? (float) $_POST['finish_ncr'] : 0,
                'spiral' => isset($_POST['finish_spiral']) && is_numeric($_POST['finish_spiral']) ? (float) $_POST['finish_spiral'] : 0,
            ];
            update_option('abc_click_finishing_costs', $finishing);
            $message = 'Click-rate matrix saved.';
        }

        $profiles = get_option('abc_click_price_profiles', []);
        $finishing = get_option('abc_click_finishing_costs', []);

        $hourly = (float) get_option('abc_hourly_rate', '20');
        $bw_single = (float) get_option('abc_click_bw_single', '0.02');
        $bw_double = (float) get_option('abc_click_bw_double', '0.04');
        $color_single = (float) get_option('abc_click_color_single', '0.08');
        $color_double = (float) get_option('abc_click_color_double', '0.16');

        $categories = [
            'commercial_printing' => 'Commercial Printing',
            'raised_printing' => 'Raised Printing',
            'banners' => 'Banners',
            'apparel' => 'Apparel',
            'booklets' => 'Booklets',
        ];
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Price Matrix (Click-Rate Model)</h1>
            <p class="description">This matrix is now based on click rates + labor. Configure category setup/markup and finishing add-ons below. Duplo trim presets are managed in Estimator Settings and can be referenced via option key `Duplo` in template options.</p>

            <?php if ($message !== '') : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>

            <div class="abc-matrix-card" style="margin-top:12px;">
                <h2>Current Base Rates (from Estimator Settings)</h2>
                <ul>
                    <li><strong>Hourly Labor:</strong> $<?php echo esc_html(number_format($hourly, 2)); ?> / hour ($<?php echo esc_html(number_format($hourly / 60, 4)); ?> per minute)</li>
                    <li><strong>B/W Single:</strong> $<?php echo esc_html(number_format($bw_single, 4)); ?> | <strong>B/W Double:</strong> $<?php echo esc_html(number_format($bw_double, 4)); ?></li>
                    <li><strong>Color Single:</strong> $<?php echo esc_html(number_format($color_single, 4)); ?> | <strong>Color Double:</strong> $<?php echo esc_html(number_format($color_double, 4)); ?></li>
                </ul>
            </div>

            <form method="post" style="margin-top:16px;">
                <?php wp_nonce_field('abc_price_matrix_v2_action', 'abc_price_matrix_v2_nonce'); ?>

                <h2>Category Profiles</h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Setup Minutes</th>
                            <th>Markup %</th>
                            <th>Minimum Order ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $key => $label) : ?>
                            <?php $row = isset($profiles[$key]) && is_array($profiles[$key]) ? $profiles[$key] : []; ?>
                            <tr>
                                <td><strong><?php echo esc_html($label); ?></strong></td>
                                <td><input type="number" name="<?php echo esc_attr($key . '_setup_minutes'); ?>" value="<?php echo esc_attr((string) ($row['setup_minutes'] ?? 0)); ?>" class="small-text"></td>
                                <td><input type="number" step="0.01" name="<?php echo esc_attr($key . '_markup_percent'); ?>" value="<?php echo esc_attr((string) ($row['markup_percent'] ?? 0)); ?>" class="small-text"></td>
                                <td><input type="number" step="0.01" name="<?php echo esc_attr($key . '_min_order'); ?>" value="<?php echo esc_attr((string) ($row['min_order'] ?? 0)); ?>" class="small-text"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h2 style="margin-top:18px;">Finishing Add-on Costs</h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th>Perf</th><td><input type="number" step="0.01" name="finish_perf" value="<?php echo esc_attr((string) ($finishing['perf'] ?? 0)); ?>" class="small-text"></td>
                            <th>Foil</th><td><input type="number" step="0.01" name="finish_foil" value="<?php echo esc_attr((string) ($finishing['foil'] ?? 0)); ?>" class="small-text"></td>
                            <th>Fold</th><td><input type="number" step="0.01" name="finish_fold" value="<?php echo esc_attr((string) ($finishing['fold'] ?? 0)); ?>" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Score</th><td><input type="number" step="0.01" name="finish_score" value="<?php echo esc_attr((string) ($finishing['score'] ?? 0)); ?>" class="small-text"></td>
                            <th>Pad</th><td><input type="number" step="0.01" name="finish_pad" value="<?php echo esc_attr((string) ($finishing['pad'] ?? 0)); ?>" class="small-text"></td>
                            <th>NCR</th><td><input type="number" step="0.01" name="finish_ncr" value="<?php echo esc_attr((string) ($finishing['ncr'] ?? 0)); ?>" class="small-text"></td>
                        </tr>
                        <tr>
                            <th>Spiral</th><td><input type="number" step="0.01" name="finish_spiral" value="<?php echo esc_attr((string) ($finishing['spiral'] ?? 0)); ?>" class="small-text"></td>
                            <td colspan="4"></td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button('Save Click-Rate Matrix'); ?>
            </form>
        </div>
        <?php
    }
}
