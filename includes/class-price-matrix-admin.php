<?php

class ABC_Price_Matrix_Admin {
    public function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        $message = '';
        if (isset($_POST['abc_price_matrix_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['abc_price_matrix_nonce'])), 'abc_price_matrix_action')) {
            $payload = [
                'id' => isset($_POST['matrix_id']) ? absint($_POST['matrix_id']) : 0,
                'template_id' => isset($_POST['template_id']) ? absint($_POST['template_id']) : 0,
                'vendor' => isset($_POST['vendor']) ? sanitize_text_field(wp_unslash($_POST['vendor'])) : '',
                'qty_min' => isset($_POST['qty_min']) ? absint($_POST['qty_min']) : 0,
                'qty_max' => isset($_POST['qty_max']) ? sanitize_text_field(wp_unslash($_POST['qty_max'])) : '',
                'options' => ABC_Price_Matrix::parse_options_json(isset($_POST['options_json']) ? wp_unslash($_POST['options_json']) : ''),
                'turnaround' => isset($_POST['turnaround']) ? sanitize_text_field(wp_unslash($_POST['turnaround'])) : '',
                'cost' => isset($_POST['cost']) ? sanitize_text_field(wp_unslash($_POST['cost'])) : '0',
                'last_verified' => isset($_POST['last_verified']) ? sanitize_text_field(wp_unslash($_POST['last_verified'])) : '',
                'source_note' => isset($_POST['source_note']) ? sanitize_textarea_field(wp_unslash($_POST['source_note'])) : '',
            ];

            if ($payload['template_id'] && $payload['vendor'] !== '' && $payload['qty_min'] > 0) {
                ABC_Price_Matrix::upsert($payload);
                $message = 'Matrix row saved.';
            } else {
                $message = 'Please complete required fields (template, vendor, qty min).';
            }
        }

        $templates = get_posts([
            'post_type' => ABC_CPT_ABC_Product_Template::POST_TYPE,
            'posts_per_page' => 200,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $filter_template = isset($_GET['template_id']) ? absint($_GET['template_id']) : 0;
        $filter_vendor = isset($_GET['vendor']) ? sanitize_text_field(wp_unslash($_GET['vendor'])) : '';
        $filter_search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

        global $wpdb;
        $table = ABC_Price_Matrix::table_name();
        $where = 'WHERE 1=1';
        $args = [];

        if ($filter_template) {
            $where .= ' AND template_id = %d';
            $args[] = $filter_template;
        }
        if ($filter_vendor !== '') {
            $where .= ' AND vendor = %s';
            $args[] = $filter_vendor;
        }
        if ($filter_search !== '') {
            $like = '%' . $wpdb->esc_like($filter_search) . '%';
            $where .= ' AND (options_json LIKE %s OR source_note LIKE %s)';
            $args[] = $like;
            $args[] = $like;
        }

        $query = "SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT 200";
        $rows = $args ? $wpdb->get_results($wpdb->prepare($query, $args), ARRAY_A) : $wpdb->get_results($query, ARRAY_A);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Price Matrix</h1>
            <?php if ($message !== '') : ?>
                <div class="notice notice-info is-dismissible"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>

            <div class="abc-matrix-grid">
                <div class="abc-matrix-card">
                    <h2>Add / Update Matrix Row</h2>
                    <form method="post" id="abc-price-matrix-form">
                        <?php wp_nonce_field('abc_price_matrix_action', 'abc_price_matrix_nonce'); ?>
                        <input type="hidden" name="matrix_id" id="abc_matrix_id" value="">
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">Template</th>
                                    <td>
                                        <select name="template_id" id="abc_matrix_template" required>
                                            <option value="">Select template</option>
                                            <?php foreach ($templates as $template) : ?>
                                                <option value="<?php echo esc_attr($template->ID); ?>"><?php echo esc_html($template->post_title); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Vendor</th>
                                    <td><input type="text" name="vendor" id="abc_matrix_vendor" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th scope="row">Qty Min</th>
                                    <td><input type="number" name="qty_min" id="abc_matrix_qty_min" class="small-text" required></td>
                                </tr>
                                <tr>
                                    <th scope="row">Qty Max</th>
                                    <td><input type="number" name="qty_max" id="abc_matrix_qty_max" class="small-text" placeholder="Leave blank for open-ended"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Options JSON</th>
                                    <td><textarea name="options_json" id="abc_matrix_options" rows="5" class="large-text code">{}</textarea></td>
                                </tr>
                                <tr>
                                    <th scope="row">Turnaround</th>
                                    <td><input type="text" name="turnaround" id="abc_matrix_turnaround" class="regular-text" placeholder="Standard"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Cost</th>
                                    <td><input type="number" step="0.01" name="cost" id="abc_matrix_cost" class="small-text" required></td>
                                </tr>
                                <tr>
                                    <th scope="row">Last Verified</th>
                                    <td><input type="date" name="last_verified" id="abc_matrix_last_verified"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Source Note</th>
                                    <td><textarea name="source_note" id="abc_matrix_source" rows="3" class="large-text"></textarea></td>
                                </tr>
                            </tbody>
                        </table>
                        <?php submit_button('Save Matrix Row'); ?>
                    </form>
                </div>

                <div class="abc-matrix-card">
                    <h2>Matrix Search</h2>
                    <form method="get" class="abc-matrix-filter">
                        <input type="hidden" name="post_type" value="<?php echo esc_attr(ABC_CPT_ABC_Estimate::POST_TYPE); ?>">
                        <input type="hidden" name="page" value="abc-price-matrix">
                        <select name="template_id">
                            <option value="">All Templates</option>
                            <?php foreach ($templates as $template) : ?>
                                <option value="<?php echo esc_attr($template->ID); ?>" <?php selected($filter_template, $template->ID); ?>><?php echo esc_html($template->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="vendor" placeholder="Vendor" value="<?php echo esc_attr($filter_vendor); ?>">
                        <input type="text" name="s" placeholder="Options / notes search" value="<?php echo esc_attr($filter_search); ?>">
                        <?php submit_button('Filter', 'secondary', '', false); ?>
                    </form>

                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Template</th>
                                <th>Vendor</th>
                                <th>Qty</th>
                                <th>Options</th>
                                <th>Turnaround</th>
                                <th>Cost</th>
                                <th>Last Verified</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($rows) : ?>
                                <?php foreach ($rows as $row) : ?>
                                    <tr>
                                        <td><?php echo esc_html($row['id']); ?></td>
                                        <td><?php echo esc_html(get_the_title((int) $row['template_id'])); ?></td>
                                        <td><?php echo esc_html($row['vendor']); ?></td>
                                        <td><?php echo esc_html($row['qty_min'] . ($row['qty_max'] ? ' - ' . $row['qty_max'] : '+')); ?></td>
                                        <td><code><?php echo esc_html($row['options_json']); ?></code></td>
                                        <td><?php echo esc_html($row['turnaround']); ?></td>
                                        <td><?php echo esc_html($row['cost']); ?></td>
                                        <td><?php echo esc_html($row['last_verified']); ?></td>
                                        <td>
                                            <button class="button abc-matrix-edit"
                                                data-id="<?php echo esc_attr($row['id']); ?>"
                                                data-template="<?php echo esc_attr($row['template_id']); ?>"
                                                data-vendor="<?php echo esc_attr($row['vendor']); ?>"
                                                data-qty-min="<?php echo esc_attr($row['qty_min']); ?>"
                                                data-qty-max="<?php echo esc_attr($row['qty_max']); ?>"
                                                data-options="<?php echo esc_attr($row['options_json']); ?>"
                                                data-turnaround="<?php echo esc_attr($row['turnaround']); ?>"
                                                data-cost="<?php echo esc_attr($row['cost']); ?>"
                                                data-last-verified="<?php echo esc_attr($row['last_verified']); ?>"
                                                data-source-note="<?php echo esc_attr($row['source_note']); ?>"
                                            >Edit</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr><td colspan="9">No rows found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
