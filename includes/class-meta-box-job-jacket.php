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
        $client_name = (string) get_post_meta($post->ID, 'abc_client_name', true);
        $client_email = (string) get_post_meta($post->ID, 'abc_client_email', true);
        $job_description = (string) get_post_meta($post->ID, 'abc_job_description', true);
        $promised_date = (string) get_post_meta($post->ID, 'abc_promised_date', true);
        $ordered_date = (string) get_post_meta($post->ID, 'abc_ordered_date', true);
        $last_ticket = (string) get_post_meta($post->ID, 'abc_last_ticket', true);
        $send_proof_to = (string) get_post_meta($post->ID, 'abc_send_proof_to', true);
        $job_name = (string) get_post_meta($post->ID, 'abc_job_name', true);
        $stock_notes = (string) get_post_meta($post->ID, 'abc_stock_notes', true);
        $press_work = (string) get_post_meta($post->ID, 'abc_press_work', true);
        $print_notes = (string) get_post_meta($post->ID, 'abc_print_notes', true);
        $numbering_notes = (string) get_post_meta($post->ID, 'abc_numbering_notes', true);
        $finish_notes = (string) get_post_meta($post->ID, 'abc_finish_notes', true);
        $delivery_notes = (string) get_post_meta($post->ID, 'abc_delivery_notes', true);
        $contacted_on = (string) get_post_meta($post->ID, 'abc_contacted_on', true);
        $is_new_job = (string) get_post_meta($post->ID, 'abc_is_new_job', true);
        $is_repeat_job = (string) get_post_meta($post->ID, 'abc_is_repeat_job', true);
        $has_changes = (string) get_post_meta($post->ID, 'abc_has_changes', true);
        $is_print_ready = (string) get_post_meta($post->ID, 'abc_is_print_ready', true);
        $has_copies = (string) get_post_meta($post->ID, 'abc_has_copies', true);
        $notes_see_back = (string) get_post_meta($post->ID, 'abc_notes_see_back', true);
        $send_proof = (string) get_post_meta($post->ID, 'abc_send_proof', true);
        $press_two_sided = (string) get_post_meta($post->ID, 'abc_press_two_sided', true);
        $press_color = (string) get_post_meta($post->ID, 'abc_press_color', true);
        $press_bw = (string) get_post_meta($post->ID, 'abc_press_bw', true);
        $finish_perf = (string) get_post_meta($post->ID, 'abc_finish_perf', true);
        $finish_foil = (string) get_post_meta($post->ID, 'abc_finish_foil', true);
        $finish_wraparound = (string) get_post_meta($post->ID, 'abc_finish_wraparound', true);
        $finish_fold = (string) get_post_meta($post->ID, 'abc_finish_fold', true);
        $finish_score = (string) get_post_meta($post->ID, 'abc_finish_score', true);
        $finish_pad = (string) get_post_meta($post->ID, 'abc_finish_pad', true);
        $finish_ncr = (string) get_post_meta($post->ID, 'abc_finish_ncr', true);
        $finish_spiral = (string) get_post_meta($post->ID, 'abc_finish_spiral', true);
        $finish_numbering_required = (string) get_post_meta($post->ID, 'abc_finish_numbering_required', true);
        $finish_numbering_black = (string) get_post_meta($post->ID, 'abc_finish_numbering_black', true);
        $delivery_deliver = (string) get_post_meta($post->ID, 'abc_delivery_deliver', true);
        $delivery_ship = (string) get_post_meta($post->ID, 'abc_delivery_ship', true);
        $delivery_pickup = (string) get_post_meta($post->ID, 'abc_delivery_pickup', true);
        $contact_email = (string) get_post_meta($post->ID, 'abc_contact_email', true);
        $contact_phone = (string) get_post_meta($post->ID, 'abc_contact_phone', true);
        $contact_voicemail = (string) get_post_meta($post->ID, 'abc_contact_voicemail', true);
        $contact_po = (string) get_post_meta($post->ID, 'abc_contact_po', true);
        $completed_by = (string) get_post_meta($post->ID, 'abc_completed_by', true);
        $printer_tech = (string) get_post_meta($post->ID, 'abc_printer_tech', true);
        $designer = (string) get_post_meta($post->ID, 'abc_designer', true);
        $sales_rep = (string) get_post_meta($post->ID, 'abc_sales_rep', true);
        $design_request_id = (string) get_post_meta($post->ID, 'abc_design_request_id', true);
        $printer_pct = (string) get_post_meta($post->ID, 'abc_printer_pct', true);
        $designer_pct = (string) get_post_meta($post->ID, 'abc_designer_pct', true);
        $commission_pct = (string) get_post_meta($post->ID, 'abc_commission_pct', true);
        $commission_amount = (string) get_post_meta($post->ID, 'abc_commission_amount', true);
        $estimate_total = (string) get_post_meta($post->ID, 'abc_estimate_total', true);
        $square_invoice_id = (string) get_post_meta($post->ID, 'abc_square_invoice_id', true);
        $square_invoice_status = (string) get_post_meta($post->ID, 'abc_square_invoice_status', true);
        $estimate_paid = (string) get_post_meta($post->ID, 'abc_estimate_paid', true);
        if ($printer_pct === '') {
            $printer_pct = '5';
        }
        if ($designer_pct === '') {
            $designer_pct = '5';
        }
        if ($commission_pct === '') {
            $commission_pct = '0';
        }
        $design_requests = get_posts([
            'post_type' => ABC_Design_Request::POST_TYPE,
            'posts_per_page' => 200,
            'post_status' => 'any',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
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
        <div class="abc-jacket-grid abc-jacket-sheet">
            <div class="abc-jacket-header">
                <div class="abc-jacket-invoice">
                    <label><strong>Invoice #</strong></label>
                    <input type="text" name="abc_invoice_number" value="<?php echo esc_attr($invoice); ?>" placeholder="0401-26">
                </div>
                <div class="abc-jacket-year">
                    <label>Year</label>
                    <div class="abc-jacket-year-display"><?php echo esc_html(substr($invoice, -2)); ?></div>
                </div>
                <div class="abc-jacket-hot">
                    <label>HOT</label>
                    <input type="text" name="abc_client_name" value="<?php echo esc_attr($client_name); ?>" placeholder="Client / Name">
                </div>
                <div class="abc-jacket-date">
                    <label>PROMISED</label>
                    <input type="date" name="abc_promised_date" value="<?php echo esc_attr($promised_date); ?>">
                </div>
                <div class="abc-jacket-date">
                    <label>ORDERED</label>
                    <input type="date" name="abc_ordered_date" value="<?php echo esc_attr($ordered_date); ?>">
                </div>
            </div>

            <div class="abc-jacket-row">
                <label>NAME</label>
                <div class="abc-jacket-stack">
                    <input type="text" name="abc_job_description" value="<?php echo esc_attr($job_description); ?>" class="regular-text">
                    <input type="email" name="abc_client_email" value="<?php echo esc_attr($client_email); ?>" placeholder="Client Email">
                </div>
            </div>

            <div class="abc-jacket-checks">
                <label><input type="checkbox" name="abc_is_new_job" value="1" <?php checked($is_new_job, '1'); ?>> NEW</label>
                <label><input type="checkbox" name="abc_is_repeat_job" value="1" <?php checked($is_repeat_job, '1'); ?>> REPEAT</label>
                <label><input type="checkbox" name="abc_has_changes" value="1" <?php checked($has_changes, '1'); ?>> CHANGES</label>
                <label><input type="checkbox" name="abc_send_proof" value="1" <?php checked($send_proof, '1'); ?>> SEND PROOF TO:</label>
                <input type="text" name="abc_send_proof_to" value="<?php echo esc_attr($send_proof_to); ?>" class="regular-text">
                <label><input type="checkbox" name="abc_is_print_ready" value="1" <?php checked($is_print_ready, '1'); ?>> PRINT-READY</label>
                <label><input type="checkbox" name="abc_has_copies" value="1" <?php checked($has_copies, '1'); ?>> COPIES</label>
                <label><input type="checkbox" name="abc_notes_see_back" value="1" <?php checked($notes_see_back, '1'); ?>> NOTES: SEE BACK</label>
                <label>LAST TKT #</label>
                <input type="text" name="abc_last_ticket" value="<?php echo esc_attr($last_ticket); ?>">
            </div>

            <div class="abc-jacket-row">
                <label>QTY / JOB NAME</label>
                <textarea name="abc_job_name" rows="2"><?php echo esc_textarea($job_name); ?></textarea>
            </div>

            <div class="abc-jacket-row">
                <label>STOCK</label>
                <textarea name="abc_stock_notes" rows="3"><?php echo esc_textarea($stock_notes); ?></textarea>
            </div>

            <div class="abc-jacket-row">
                <label>PRESS WORK / SET-UP</label>
                <div class="abc-jacket-stack">
                    <div class="abc-jacket-checks">
                        <label><input type="checkbox" name="abc_press_two_sided" value="1" <?php checked($press_two_sided, '1'); ?>> 2 SIDED</label>
                        <label><input type="checkbox" name="abc_press_color" value="1" <?php checked($press_color, '1'); ?>> COLOR</label>
                        <label><input type="checkbox" name="abc_press_bw" value="1" <?php checked($press_bw, '1'); ?>> B/W</label>
                    </div>
                    <textarea name="abc_press_work" rows="3"><?php echo esc_textarea($press_work); ?></textarea>
                </div>
            </div>

            <div class="abc-jacket-row">
                <label>PRINT NOTES</label>
                <textarea name="abc_print_notes" rows="2"><?php echo esc_textarea($print_notes); ?></textarea>
            </div>

            <div class="abc-jacket-row">
                <label>NUMBERING / COLOR</label>
                <div class="abc-jacket-stack">
                    <div class="abc-jacket-checks">
                        <label><input type="checkbox" name="abc_finish_numbering_required" value="1" <?php checked($finish_numbering_required, '1'); ?>> NUMBERING REQUIRED</label>
                        <label><input type="checkbox" name="abc_finish_numbering_black" value="1" <?php checked($finish_numbering_black, '1'); ?>> BLACK</label>
                    </div>
                    <textarea name="abc_numbering_notes" rows="2"><?php echo esc_textarea($numbering_notes); ?></textarea>
                </div>
            </div>

            <div class="abc-jacket-row">
                <label>FINISHING</label>
                <div class="abc-jacket-stack">
                    <div class="abc-jacket-checks">
                        <label><input type="checkbox" name="abc_finish_perf" value="1" <?php checked($finish_perf, '1'); ?>> PERF</label>
                        <label><input type="checkbox" name="abc_finish_foil" value="1" <?php checked($finish_foil, '1'); ?>> FOIL</label>
                        <label><input type="checkbox" name="abc_finish_wraparound" value="1" <?php checked($finish_wraparound, '1'); ?>> WRAPAROUND</label>
                        <label><input type="checkbox" name="abc_finish_fold" value="1" <?php checked($finish_fold, '1'); ?>> FOLD</label>
                        <label><input type="checkbox" name="abc_finish_score" value="1" <?php checked($finish_score, '1'); ?>> SCORE</label>
                        <label><input type="checkbox" name="abc_finish_pad" value="1" <?php checked($finish_pad, '1'); ?>> PAD</label>
                        <label><input type="checkbox" name="abc_finish_ncr" value="1" <?php checked($finish_ncr, '1'); ?>> NCR</label>
                        <label><input type="checkbox" name="abc_finish_spiral" value="1" <?php checked($finish_spiral, '1'); ?>> SPIRAL</label>
                    </div>
                    <textarea name="abc_finish_notes" rows="3"><?php echo esc_textarea($finish_notes); ?></textarea>
                </div>
            </div>

            <div class="abc-jacket-row">
                <label>DELIVERY / SHIP TO</label>
                <div class="abc-jacket-stack">
                    <div class="abc-jacket-checks">
                        <label><input type="checkbox" name="abc_delivery_deliver" value="1" <?php checked($delivery_deliver, '1'); ?>> DELIVER</label>
                        <label><input type="checkbox" name="abc_delivery_ship" value="1" <?php checked($delivery_ship, '1'); ?>> SHIP TO</label>
                        <label><input type="checkbox" name="abc_delivery_pickup" value="1" <?php checked($delivery_pickup, '1'); ?>> PICK UP</label>
                    </div>
                    <textarea name="abc_delivery_notes" rows="2"><?php echo esc_textarea($delivery_notes); ?></textarea>
                </div>
            </div>

            <div class="abc-jacket-row">
                <label>CONTACTED ON</label>
                <div class="abc-jacket-stack">
                    <div class="abc-jacket-checks">
                        <label><input type="checkbox" name="abc_contact_email" value="1" <?php checked($contact_email, '1'); ?>> EMAIL</label>
                        <label><input type="checkbox" name="abc_contact_phone" value="1" <?php checked($contact_phone, '1'); ?>> PHONE</label>
                        <label><input type="checkbox" name="abc_contact_voicemail" value="1" <?php checked($contact_voicemail, '1'); ?>> VOICEMAIL</label>
                        <label><input type="checkbox" name="abc_contact_po" value="1" <?php checked($contact_po, '1'); ?>> PO</label>
                    </div>
                    <textarea name="abc_contacted_on" rows="2"><?php echo esc_textarea($contacted_on); ?></textarea>
                </div>
            </div>

            <div class="abc-jacket-row abc-jacket-meta">
                <div>
                    <label>Order Date</label>
                    <input type="date" name="abc_order_date" value="<?php echo esc_attr($order_date); ?>">
                </div>
                <div>
                    <label>Approval Date</label>
                    <input type="date" name="abc_approval_date" value="<?php echo esc_attr($approval_date); ?>">
                </div>
                <div>
                    <label>Due Date</label>
                    <input type="date" name="abc_due_date" value="<?php echo esc_attr($due_date); ?>">
                </div>
                <div>
                    <label>Rush?</label>
                    <input type="checkbox" name="abc_is_rush" value="1" <?php checked($is_rush, '1'); ?>>
                </div>
                <div>
                    <label>Current Stage</label>
                    <select name="abc_status">
                        <?php foreach ($workflow_options as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="abc-jacket-row abc-jacket-assignments">
                <?php $users = get_users(['fields' => ['ID', 'display_name']]); ?>
                <div>
                    <label>Completed By</label>
                    <select name="abc_completed_by">
                        <option value="">Select user</option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($completed_by, (string) $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Printer Tech</label>
                    <select name="abc_printer_tech">
                        <option value="">Select user</option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($printer_tech, (string) $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Printer %</label>
                    <input type="number" step="0.01" name="abc_printer_pct" value="<?php echo esc_attr($printer_pct); ?>">
                </div>
                <div>
                    <label>Designer</label>
                    <select name="abc_designer">
                        <option value="">Select user</option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($designer, (string) $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Designer %</label>
                    <input type="number" step="0.01" name="abc_designer_pct" value="<?php echo esc_attr($designer_pct); ?>">
                </div>
                <div>
                    <label>Sales Rep</label>
                    <select name="abc_sales_rep">
                        <option value="">Select user</option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($sales_rep, (string) $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Commission %</label>
                    <input type="number" step="0.01" name="abc_commission_pct" id="abc_commission_pct" value="<?php echo esc_attr($commission_pct); ?>">
                </div>
                <div>
                    <label>Commission $</label>
                    <input type="number" step="0.01" name="abc_commission_amount" id="abc_commission_amount" value="<?php echo esc_attr($commission_amount); ?>" readonly>
                </div>
                <div>
                    <label>Design Request</label>
                    <select name="abc_design_request_id">
                        <option value="">Select design</option>
                        <?php foreach ($design_requests as $design) : ?>
                            <option value="<?php echo esc_attr($design->ID); ?>" <?php selected($design_request_id, (string) $design->ID); ?>><?php echo esc_html($design->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($design_request_id) : ?>
                        <p class="description"><a href="<?php echo esc_url(get_edit_post_link((int) $design_request_id, 'raw')); ?>" target="_blank" rel="noopener">View design request</a></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="abc-jacket-row abc-jacket-assignments">
                <div>
                    <label>Estimate Total</label>
                    <input type="number" step="0.01" name="abc_estimate_total" id="abc_estimate_total" value="<?php echo esc_attr($estimate_total); ?>" readonly>
                </div>
                <div>
                    <label>Paid?</label>
                    <input type="checkbox" name="abc_estimate_paid" value="1" <?php checked($estimate_paid, '1'); ?>>
                </div>
                <div>
                    <label>Square Invoice ID</label>
                    <input type="text" name="abc_square_invoice_id" id="abc_square_invoice_id" value="<?php echo esc_attr($square_invoice_id); ?>" readonly>
                </div>
                <div>
                    <label>Square Status</label>
                    <input type="text" name="abc_square_invoice_status" id="abc_square_invoice_status" value="<?php echo esc_attr($square_invoice_status); ?>" readonly>
                </div>
                <div>
                    <label>&nbsp;</label>
                    <button type="button" class="button" id="abc-create-square-invoice" data-estimate-id="<?php echo esc_attr((string) $post->ID); ?>">Create Square Invoice</button>
                </div>
            </div>

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
                        <label for="abc_template_wc_product"><strong>Woo Product</strong></label>
                        <input type="text" id="abc_template_wc_product" placeholder="WooCommerce Product ID" readonly>
                    </div>
                    <div class="abc-product-library-row">
                        <label for="abc_template_custom_product"><strong>Custom Product</strong></label>
                        <input type="text" id="abc_template_custom_product" placeholder="Custom product name">
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
            'abc_client_name',
            'abc_client_email',
            'abc_job_description',
            'abc_promised_date',
            'abc_ordered_date',
            'abc_last_ticket',
            'abc_send_proof_to',
            'abc_job_name',
            'abc_stock_notes',
            'abc_press_work',
            'abc_print_notes',
            'abc_numbering_notes',
            'abc_finish_notes',
            'abc_delivery_notes',
            'abc_contacted_on',
            'abc_completed_by',
            'abc_printer_tech',
            'abc_designer',
            'abc_sales_rep',
            'abc_design_request_id',
            'abc_printer_pct',
            'abc_designer_pct',
            'abc_commission_pct',
            'abc_commission_amount',
            'abc_estimate_total',
            'abc_is_new_job',
            'abc_is_repeat_job',
            'abc_has_changes',
            'abc_is_print_ready',
            'abc_has_copies',
            'abc_notes_see_back',
            'abc_send_proof',
            'abc_press_two_sided',
            'abc_press_color',
            'abc_press_bw',
            'abc_finish_perf',
            'abc_finish_foil',
            'abc_finish_wraparound',
            'abc_finish_fold',
            'abc_finish_score',
            'abc_finish_pad',
            'abc_finish_ncr',
            'abc_finish_spiral',
            'abc_finish_numbering_required',
            'abc_finish_numbering_black',
            'abc_delivery_deliver',
            'abc_delivery_ship',
            'abc_delivery_pickup',
            'abc_contact_email',
            'abc_contact_phone',
            'abc_contact_voicemail',
            'abc_contact_po',
            'abc_estimate_paid',
            'abc_square_invoice_id',
            'abc_square_invoice_status',
        ];

        $cpt = new ABC_CPT_ABC_Estimate();
        $textarea_fields = [
            'abc_job_name',
            'abc_stock_notes',
            'abc_press_work',
            'abc_print_notes',
            'abc_numbering_notes',
            'abc_finish_notes',
            'abc_delivery_notes',
            'abc_contacted_on',
        ];

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
            } elseif (in_array($field, ['abc_promised_date', 'abc_ordered_date'], true)) {
                $new = $cpt->sanitize_date($new);
            } elseif (in_array($field, [
                'abc_is_new_job',
                'abc_is_repeat_job',
                'abc_has_changes',
                'abc_is_print_ready',
                'abc_has_copies',
                'abc_notes_see_back',
                'abc_send_proof',
                'abc_press_two_sided',
                'abc_press_color',
                'abc_press_bw',
                'abc_finish_perf',
                'abc_finish_foil',
                'abc_finish_wraparound',
                'abc_finish_fold',
                'abc_finish_score',
                'abc_finish_pad',
                'abc_finish_ncr',
                'abc_finish_spiral',
                'abc_finish_numbering_required',
                'abc_finish_numbering_black',
                'abc_delivery_deliver',
                'abc_delivery_ship',
                'abc_delivery_pickup',
                'abc_contact_email',
                'abc_contact_phone',
                'abc_contact_voicemail',
                'abc_contact_po',
                'abc_estimate_paid',
            ], true)) {
                $new = $new === '1' ? '1' : '0';
            } elseif (in_array($field, ['abc_printer_pct', 'abc_designer_pct', 'abc_commission_pct', 'abc_commission_amount', 'abc_estimate_total'], true)) {
                $new = is_numeric($new) ? (string) (float) $new : '0';
            } elseif (in_array($field, $textarea_fields, true)) {
                $new = sanitize_textarea_field($new);
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
