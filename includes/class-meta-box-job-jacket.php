<?php

class ABC_Meta_Box_Job_Jacket {
    private const NONCE_ACTION = 'abc_job_jacket_save';
    private const NONCE_NAME = 'abc_job_jacket_nonce';

    private array $fields = [
        'abc_invoice_number' => 'Invoice Number',
        'abc_order_date' => 'Order Date',
        'abc_approval_date' => 'Approval Date',
        'abc_due_date' => 'Due Date',
        'abc_rush' => 'Rush',
        'abc_status' => 'Status',
        'abc_workflow_status' => 'Workflow Status',
    ];

    public function register(): void {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta']);
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
    }

    public function render_meta_box(WP_Post $post): void {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $meta = [];
        foreach ($this->fields as $key => $label) {
            $meta[$key] = get_post_meta($post->ID, $key, true);
        }
        $meta['abc_line_items_json'] = get_post_meta($post->ID, 'abc_line_items_json', true);
        $meta['abc_history_notes'] = get_post_meta($post->ID, 'abc_history_notes', true);
        if (!is_array($meta['abc_history_notes'])) {
            $meta['abc_history_notes'] = [];
        }

        $workflow_options = [
            'estimate' => 'Estimate',
            'pending' => 'Pending',
            'production' => 'Production',
            'completed' => 'Completed',
        ];
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><label for="abc_invoice_number">Invoice Number</label></th>
                    <td><input type="text" class="regular-text" name="abc_invoice_number" id="abc_invoice_number" value="<?php echo esc_attr($meta['abc_invoice_number']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="abc_order_date">Order Date</label></th>
                    <td><input type="date" name="abc_order_date" id="abc_order_date" value="<?php echo esc_attr($meta['abc_order_date']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="abc_approval_date">Approval Date</label></th>
                    <td><input type="date" name="abc_approval_date" id="abc_approval_date" value="<?php echo esc_attr($meta['abc_approval_date']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="abc_due_date">Due Date</label></th>
                    <td><input type="date" name="abc_due_date" id="abc_due_date" value="<?php echo esc_attr($meta['abc_due_date']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="abc_rush">Rush</label></th>
                    <td><label><input type="checkbox" name="abc_rush" value="1" <?php checked($meta['abc_rush'], '1'); ?>> Rush Job</label></td>
                </tr>
                <tr>
                    <th><label for="abc_status">Status</label></th>
                    <td><input type="text" class="regular-text" name="abc_status" id="abc_status" value="<?php echo esc_attr($meta['abc_status']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="abc_workflow_status">Workflow Status</label></th>
                    <td>
                        <select name="abc_workflow_status" id="abc_workflow_status">
                            <?php foreach ($workflow_options as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($meta['abc_workflow_status'], $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="abc_line_items_json">Line Items (JSON)</label></th>
                    <td>
                        <textarea class="large-text" rows="6" name="abc_line_items_json" id="abc_line_items_json"><?php echo esc_textarea($meta['abc_line_items_json']); ?></textarea>
                        <div id="abc-react-estimate-builder-mount"></div>
                        <input type="hidden" id="abc_estimate_data" name="abc_estimate_data" value="<?php echo esc_attr($meta['abc_line_items_json']); ?>">
                    </td>
                </tr>
            </tbody>
        </table>
        <h4>History Notes</h4>
        <p>Manual notes are append-only.</p>
        <textarea class="large-text" rows="4" name="abc_history_note_new" placeholder="Add a note..."></textarea>
        <?php if (!empty($meta['abc_history_notes'])) : ?>
            <ul>
                <?php foreach (array_reverse($meta['abc_history_notes']) as $note) : ?>
                    <li><?php echo esc_html($note['timestamp'] . ' - ' . $note['user'] . ': ' . $note['note']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php
    }

    public function save_meta(int $post_id): void {
        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) !== ABC_CPT_ABC_Estimate::POST_TYPE) {
            return;
        }

        $fields = [
            'abc_invoice_number' => 'sanitize_text_field',
            'abc_order_date' => 'sanitize_text_field',
            'abc_approval_date' => 'sanitize_text_field',
            'abc_due_date' => 'sanitize_text_field',
            'abc_status' => 'sanitize_text_field',
            'abc_workflow_status' => 'sanitize_text_field',
        ];

        foreach ($fields as $key => $sanitize) {
            if (isset($_POST[$key])) {
                $value = call_user_func($sanitize, wp_unslash($_POST[$key]));
                update_post_meta($post_id, $key, $value);
            }
        }

        $rush = isset($_POST['abc_rush']) ? '1' : '0';
        update_post_meta($post_id, 'abc_rush', $rush);

        $line_items_source = '';
        if (isset($_POST['abc_estimate_data'])) {
            $line_items_source = wp_unslash($_POST['abc_estimate_data']);
        } elseif (isset($_POST['abc_line_items_json'])) {
            $line_items_source = wp_unslash($_POST['abc_line_items_json']);
        }
        if ($line_items_source !== '') {
            update_post_meta($post_id, 'abc_line_items_json', sanitize_textarea_field($line_items_source));
        }

        $new_note = isset($_POST['abc_history_note_new']) ? sanitize_textarea_field(wp_unslash($_POST['abc_history_note_new'])) : '';
        if ($new_note !== '') {
            $history = get_post_meta($post_id, 'abc_history_notes', true);
            if (!is_array($history)) {
                $history = [];
            }
            $user = wp_get_current_user();
            $history[] = [
                'timestamp' => current_time('mysql'),
                'user' => $user ? $user->display_name : 'Unknown',
                'note' => $new_note,
            ];
            update_post_meta($post_id, 'abc_history_notes', $history);
        }
    }
}
