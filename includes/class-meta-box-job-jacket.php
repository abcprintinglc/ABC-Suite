<?php

class ABC_Meta_Box_Job_Jacket {
    private const NONCE_ACTION = 'abc_job_jacket_save';
    private const NONCE_NAME = 'abc_job_jacket_nonce';

    public function register(): void {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta'], 10, 2);
    }

    public function add_meta_box(): void {
        add_meta_box(
            'abc_job_jacket',
            'Job Jacket',
            [$this, 'render_meta_box'],
            ABC_CPT_ABC_Estimate::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'abc_history_log',
            'History / Change Log',
            [$this, 'render_history_box'],
            ABC_CPT_ABC_Estimate::POST_TYPE,
            'side',
            'default'
        );
    }

    public function render_meta_box(WP_Post $post): void {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $invoice = (string) get_post_meta($post->ID, 'abc_invoice_number', true);
        $order_date = (string) get_post_meta($post->ID, 'abc_order_date', true);
        $approval_date = (string) get_post_meta($post->ID, 'abc_approval_date', true);
        $due_date = (string) get_post_meta($post->ID, 'abc_due_date', true);
        $is_rush = (string) get_post_meta($post->ID, 'abc_is_rush', true);
        $status = (string) get_post_meta($post->ID, 'abc_status', true);
        if ($status === '') {
            $status = 'estimate';
        }
        $estimate_json = (string) get_post_meta($post->ID, 'abc_estimate_data', true);
        if ($estimate_json === '') {
            $estimate_json = (string) get_post_meta($post->ID, 'abc_line_items_json', true);
        }
        if ($estimate_json === '') {
            $estimate_json = '[]';
        }

        $workflow_options = [
            'estimate' => 'Estimate',
            'pending' => 'Pending',
            'production' => 'Production',
            'completed' => 'Completed',
        ];
        ?>
        <div class="abc-jacket-grid">
            <p>
                <label><strong>Invoice # (tttt-yy):</strong></label><br>
                <input type="text" name="abc_invoice_number" value="<?php echo esc_attr($invoice); ?>" placeholder="1234-24" style="width: 220px;">
            </p>
            <p>
                <label>Order Date:</label><br>
                <input type="date" name="abc_order_date" value="<?php echo esc_attr($order_date); ?>">
            </p>
            <p>
                <label>Approval Date:</label><br>
                <input type="date" name="abc_approval_date" value="<?php echo esc_attr($approval_date); ?>">
            </p>
            <p>
                <label><strong>Due Date:</strong></label><br>
                <input type="date" name="abc_due_date" value="<?php echo esc_attr($due_date); ?>">
            </p>
            <p>
                <label style="color:#b32d2e; font-weight:bold;">Rush Job?</label><br>
                <label>
                    <input type="checkbox" name="abc_is_rush" value="1" <?php checked($is_rush, '1'); ?>> Yes, Rush!
                </label>
            </p>

            <p>
                <label><strong>Current Stage:</strong></label><br>
                <select name="abc_status" style="width: 260px;">
                    <?php foreach ($workflow_options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>

            <input type="hidden" name="abc_estimate_data" id="abc_estimate_data" value="<?php echo esc_attr($estimate_json); ?>">

            <hr>
            <div id="abc-react-estimate-builder-mount">
                <p><em>(Line item grid renders here)</em></p>
            </div>

            <div class="abc-product-library-panel">
                <div class="abc-product-library-header">
                    <h3>Product Library</h3>
                    <button type="button" class="button" id="abc-toggle-library">Add from Product Library</button>
                </div>
                <div id="abc-product-library-form" class="abc-product-library-form" style="display:none;">
                    <div class="abc-product-library-row">
                        <label for="abc_template_select"><strong>Template</strong></label>
                        <select id="abc_template_select"></select>
                    </div>
                    <div id="abc-template-options"></div>
                    <div class="abc-product-library-row">
                        <label for="abc_template_qty"><strong>Qty</strong></label>
                        <input type="number" id="abc_template_qty" min="1" value="1">
                    </div>
                    <div class="abc-product-library-row">
                        <label for="abc_template_vendor"><strong>Vendor</strong></label>
                        <input type="text" id="abc_template_vendor" placeholder="Vendor">
                    </div>
                    <div class="abc-product-library-row">
                        <label for="abc_template_cost"><strong>Cost</strong></label>
                        <input type="number" id="abc_template_cost" step="0.01">
                        <span id="abc-template-cost-status" class="description"></span>
                    </div>
                    <div class="abc-product-library-row">
                        <label for="abc_template_markup_type"><strong>Markup</strong></label>
                        <select id="abc_template_markup_type">
                            <option value="percent">Percent</option>
                            <option value="multiplier">Multiplier</option>
                        </select>
                        <input type="number" id="abc_template_markup_value" step="0.01">
                    </div>
                    <div class="abc-product-library-row">
                        <label for="abc_template_sell_price"><strong>Sell Price</strong></label>
                        <input type="number" id="abc_template_sell_price" step="0.01" readonly>
                    </div>
                    <div class="abc-product-library-actions">
                        <button type="button" class="button button-primary" id="abc-add-line-item">Add to Estimate</button>
                    </div>
                </div>
                <div id="abc-template-line-items"></div>
            </div>
        </div>
        <?php
    }

    public function render_history_box(WP_Post $post): void {
        $history = get_post_meta($post->ID, 'abc_history_log', true);
        if (!empty($history) && is_array($history)) {
            echo '<ul style="max-height:200px; overflow-y:auto; padding-left:15px; margin-top:0;">';
            foreach (array_reverse($history) as $entry) {
                $date = isset($entry['date']) ? $entry['date'] : '';
                $user = isset($entry['user']) ? $entry['user'] : '';
                $note = isset($entry['note']) ? $entry['note'] : '';
                echo '<li><small>' . esc_html($date) . ' by ' . esc_html($user) . ':<br>' . esc_html($note) . '</small></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No history yet.</p>';
        }
        ?>
        <textarea name="abc_manual_note" placeholder="Add a note to the log..." rows="3" style="width:100%; margin-top:10px;"></textarea>
        <p class="description" style="margin-top:6px;">Tip: Use this for call notes, material changes, approvals, etc.</p>
        <?php
    }

    public function save_meta(int $post_id, WP_Post $post): void {
        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if ($post->post_type !== ABC_CPT_ABC_Estimate::POST_TYPE) {
            return;
        }

        $changes = [];

        $old_rush = (string) get_post_meta($post_id, 'abc_is_rush', true);
        $new_rush = isset($_POST['abc_is_rush']) ? '1' : '0';
        if ($new_rush !== $old_rush) {
            update_post_meta($post_id, 'abc_is_rush', $new_rush);
            $changes[] = 'Rush status changed to ' . ($new_rush === '1' ? 'YES' : 'NO');
        }

        $fields = [
            'abc_invoice_number',
            'abc_order_date',
            'abc_due_date',
            'abc_approval_date',
            'abc_status',
            'abc_estimate_data',
        ];

        $cpt = new ABC_CPT_ABC_Estimate();

        foreach ($fields as $field) {
            if (!isset($_POST[$field])) {
                continue;
            }

            $old = get_post_meta($post_id, $field, true);
            $new = wp_unslash($_POST[$field]);

            if ($field === 'abc_estimate_data') {
                $new = $cpt->sanitize_json($new);
            } elseif ($field === 'abc_status') {
                $new = $cpt->sanitize_status($new);
            } elseif ($field === 'abc_invoice_number') {
                $new = $cpt->sanitize_invoice($new);
            } elseif (in_array($field, ['abc_order_date', 'abc_due_date', 'abc_approval_date'], true)) {
                $new = $cpt->sanitize_date($new);
            } else {
                $new = sanitize_text_field($new);
            }

            if ((string) $new !== (string) $old) {
                update_post_meta($post_id, $field, $new);
                if ($field === 'abc_estimate_data') {
                    update_post_meta($post_id, 'abc_line_items_json', $new);
                    $changes[] = 'Line items updated.';
                } else {
                    $changes[] = str_replace('abc_', '', $field) . ' updated.';
                }
            }
        }

        $manual_note = '';
        if (isset($_POST['abc_manual_note'])) {
            $manual_note = trim(sanitize_textarea_field((string) $_POST['abc_manual_note']));
        }

        $entries_to_add = [];
        if (!empty($changes)) {
            $user = wp_get_current_user();
            $entries_to_add[] = [
                'date' => current_time('mysql'),
                'user' => $user && isset($user->display_name) ? $user->display_name : 'Unknown',
                'note' => implode(', ', $changes),
            ];
        }

        if ($manual_note !== '') {
            $user = wp_get_current_user();
            $entries_to_add[] = [
                'date' => current_time('mysql'),
                'user' => $user && isset($user->display_name) ? $user->display_name : 'Unknown',
                'note' => 'Manual Note: ' . $manual_note,
            ];
        }

        if (!empty($entries_to_add)) {
            $current_log = get_post_meta($post_id, 'abc_history_log', true);
            if (!is_array($current_log)) {
                $current_log = [];
            }
            foreach ($entries_to_add as $entry) {
                $current_log[] = $entry;
            }
            if (count($current_log) > 300) {
                $current_log = array_slice($current_log, -300);
            }
            update_post_meta($post_id, 'abc_history_log', $current_log);
        }
    }
}
