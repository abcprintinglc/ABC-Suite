<?php

class ABC_CPT_ABC_Product_Template {
    public const POST_TYPE = 'abc_product_template';

    private const NONCE_ACTION = 'abc_product_template_save';
    private const NONCE_NAME = 'abc_product_template_nonce';

    public function register(): void {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_meta']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta'], 10, 2);
    }

    public function register_post_type(): void {
        $labels = [
            'name' => 'Product Templates',
            'singular_name' => 'Product Template',
            'menu_name' => 'Product Library',
            'add_new_item' => 'Add Product Template',
            'edit_item' => 'Edit Product Template',
        ];

        register_post_type(self::POST_TYPE, [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'revisions', 'author'],
            'menu_icon' => 'dashicons-archive',
            'map_meta_cap' => true,
            'capability_type' => 'post',
            'exclude_from_search' => true,
            'show_in_rest' => false,
        ]);
    }

    public function register_meta(): void {
        $fields = [
            'abc_template_category' => 'string',
            'abc_template_vendor_default' => 'string',
            'abc_template_pricing_model' => 'string',
            'abc_template_markup_type' => 'string',
            'abc_template_markup_value' => 'number',
            'abc_template_notes' => 'string',
            'abc_template_option_schema' => 'string',
            'abc_template_schema_version' => 'string',
        ];

        foreach ($fields as $key => $type) {
            register_post_meta(self::POST_TYPE, $key, [
                'type' => $type,
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => function ($value) use ($key) {
                    return $this->sanitize_meta($value, $key);
                },
                'auth_callback' => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]);
        }
    }

    public function add_meta_boxes(): void {
        add_meta_box(
            'abc_product_template_details',
            'Template Details',
            [$this, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_meta_box(WP_Post $post): void {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $category = (string) get_post_meta($post->ID, 'abc_template_category', true);
        $vendor_default = (string) get_post_meta($post->ID, 'abc_template_vendor_default', true);
        $pricing_model = (string) get_post_meta($post->ID, 'abc_template_pricing_model', true);
        $markup_type = (string) get_post_meta($post->ID, 'abc_template_markup_type', true);
        $markup_value = (string) get_post_meta($post->ID, 'abc_template_markup_value', true);
        $notes = (string) get_post_meta($post->ID, 'abc_template_notes', true);
        $option_schema = (string) get_post_meta($post->ID, 'abc_template_option_schema', true);
        $schema_version = (string) get_post_meta($post->ID, 'abc_template_schema_version', true);

        if ($pricing_model === '') {
            $pricing_model = 'matrix';
        }
        if ($markup_type === '') {
            $markup_type = 'percent';
        }
        if ($schema_version === '') {
            $schema_version = '1';
        }
        if ($option_schema === '') {
            $option_schema = wp_json_encode([
                'schema_version' => 1,
                'groups' => [
                    [
                        'name' => 'Size',
                        'values' => ['8ft', '10ft', '12ft'],
                    ],
                    [
                        'name' => 'Sides',
                        'values' => ['Single', 'Double'],
                    ],
                    [
                        'name' => 'Base',
                        'values' => ['Ground Stake', 'Cross Base', 'Square Base'],
                    ],
                    [
                        'name' => 'Turnaround',
                        'values' => ['Standard', 'Rush'],
                    ],
                ],
            ], JSON_PRETTY_PRINT);
        }
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="abc_template_category">Category</label></th>
                    <td><input type="text" name="abc_template_category" id="abc_template_category" value="<?php echo esc_attr($category); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_template_vendor_default">Default Vendor</label></th>
                    <td><input type="text" name="abc_template_vendor_default" id="abc_template_vendor_default" value="<?php echo esc_attr($vendor_default); ?>" class="regular-text" placeholder="Signs365"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_template_pricing_model">Pricing Model</label></th>
                    <td>
                        <select name="abc_template_pricing_model" id="abc_template_pricing_model">
                            <option value="matrix" <?php selected($pricing_model, 'matrix'); ?>>Matrix (lookup)</option>
                            <option value="formula" <?php selected($pricing_model, 'formula'); ?>>Formula (future)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_template_markup_type">Default Markup Type</label></th>
                    <td>
                        <select name="abc_template_markup_type" id="abc_template_markup_type">
                            <option value="percent" <?php selected($markup_type, 'percent'); ?>>Percent</option>
                            <option value="multiplier" <?php selected($markup_type, 'multiplier'); ?>>Multiplier</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_template_markup_value">Default Markup Value</label></th>
                    <td><input type="number" step="0.01" name="abc_template_markup_value" id="abc_template_markup_value" value="<?php echo esc_attr($markup_value); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_template_schema_version">Schema Version</label></th>
                    <td><input type="text" name="abc_template_schema_version" id="abc_template_schema_version" value="<?php echo esc_attr($schema_version); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_template_option_schema">Option Schema (JSON)</label></th>
                    <td>
                        <textarea name="abc_template_option_schema" id="abc_template_option_schema" rows="10" class="large-text code"><?php echo esc_textarea($option_schema); ?></textarea>
                        <p class="description">Define option groups and values. This JSON drives dropdowns in the estimator.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="abc_template_notes">Notes</label></th>
                    <td><textarea name="abc_template_notes" id="abc_template_notes" rows="4" class="large-text"><?php echo esc_textarea($notes); ?></textarea></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public function save_meta(int $post_id, WP_Post $post): void {
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }

        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = [
            'abc_template_category',
            'abc_template_vendor_default',
            'abc_template_pricing_model',
            'abc_template_markup_type',
            'abc_template_markup_value',
            'abc_template_notes',
            'abc_template_option_schema',
            'abc_template_schema_version',
        ];

        foreach ($fields as $field) {
            if (!isset($_POST[$field])) {
                continue;
            }
            $value = wp_unslash($_POST[$field]);
            $value = $this->sanitize_meta($value, $field);
            update_post_meta($post_id, $field, $value);
        }
    }

    public function sanitize_meta($value, string $key = '') {
        if ($key === 'abc_template_markup_value') {
            return is_numeric($value) ? (string) (float) $value : '0';
        }
        if ($key === 'abc_template_option_schema') {
            $decoded = json_decode((string) $value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return wp_json_encode(['schema_version' => 1, 'groups' => []]);
            }
            return wp_json_encode($decoded, JSON_PRETTY_PRINT);
        }
        return sanitize_text_field((string) $value);
    }
}
