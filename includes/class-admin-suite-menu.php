<?php

class ABC_Admin_Suite_Menu {
    public const MENU_SLUG = 'abc-suite-job-log';

    public function register(): void {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu(): void {
        add_menu_page(
            'ABC Suite',
            'ABC Suite',
            'edit_posts',
            self::MENU_SLUG,
            [$this, 'render_job_log'],
            'dashicons-layout',
            25
        );

        // Ordered to match the numbered request from the screenshot.
        add_submenu_page(self::MENU_SLUG, 'Job Log', 'Job Log', 'edit_posts', self::MENU_SLUG, [$this, 'render_job_log']);
        add_submenu_page(self::MENU_SLUG, 'Organization Templates', 'Organization Templates', 'edit_posts', 'abc-suite-templates', [$this, 'redirect_to_templates']);
        add_submenu_page(self::MENU_SLUG, 'Data Tools', 'Data Tools', 'manage_options', 'abc-data-tools', [$this, 'render_data_tools']);
        add_submenu_page(self::MENU_SLUG, 'Designer', 'Designer', 'edit_posts', 'abc-suite-designer', [$this, 'redirect_to_designer']);
        add_submenu_page(self::MENU_SLUG, 'Design Requests', 'Design Requests', 'edit_posts', 'abc-suite-design-requests', [$this, 'redirect_to_design_requests']);
        add_submenu_page(self::MENU_SLUG, 'Product Library', 'Product Library', 'edit_posts', 'abc-suite-product-library', [$this, 'redirect_to_product_library']);
        add_submenu_page(self::MENU_SLUG, 'Price Matrix', 'Price Matrix', 'manage_options', 'abc-price-matrix', [$this, 'render_price_matrix']);
        add_submenu_page(self::MENU_SLUG, 'Estimator Settings', 'Estimator Settings', 'manage_options', 'abc-estimator-settings', [$this, 'render_estimator_settings']);
        add_submenu_page(self::MENU_SLUG, 'Estimate Learning Log', 'Estimate Learning Log', 'manage_options', 'abc-estimate-learning-log', [$this, 'render_estimate_learning_log']);

        add_submenu_page(self::MENU_SLUG, 'Vendors', 'Vendors', 'manage_options', 'abc-suite-vendors', [$this, 'redirect_to_vendors']);
        add_submenu_page(self::MENU_SLUG, 'WooCommerce Integration', 'WooCommerce Integration', 'manage_options', 'abc-suite-woocommerce', [$this, 'render_placeholder']);
        add_submenu_page(self::MENU_SLUG, 'Reports', 'Reports', 'manage_options', 'abc-suite-reports', [$this, 'render_placeholder']);
        add_submenu_page(self::MENU_SLUG, 'Settings', 'Settings', 'manage_options', 'abc-suite-settings', [$this, 'render_placeholder']);
    }

    public function render_job_log(): void {
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions.');
        }

        if (class_exists('ABC_Admin_Logbook')) {
            (new ABC_Admin_Logbook())->render_page();
            return;
        }

        $this->render_placeholder();
    }

    public function render_price_matrix(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        (new ABC_Price_Matrix_Admin())->render_page();
    }

    public function render_estimator_settings(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        (new ABC_Estimator_Settings())->render_page();
    }

    public function render_estimate_learning_log(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        (new ABC_Estimate_Learning_Log())->render_page();
    }

    public function render_data_tools(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        (new ABC_CSV_Tools())->render_page();
    }

    public function render_placeholder(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        ?>
        <div class="wrap">
            <h1>ABC Suite Module</h1>
            <p>This screen is scaffolded and ready for module-specific implementation in a follow-up phase.</p>
        </div>
        <?php
    }

    public function redirect_to_designer(): void {
        $this->redirect_to_post_type('abc_draft', 'edit_posts');
    }

    public function redirect_to_design_requests(): void {
        $this->redirect_to_post_type(ABC_Design_Request::POST_TYPE, 'edit_posts');
    }

    public function redirect_to_product_library(): void {
        $this->redirect_to_post_type(ABC_CPT_ABC_Product_Template::POST_TYPE, 'edit_posts');
    }

    public function redirect_to_templates(): void {
        $this->redirect_to_post_type('abc_template', 'edit_posts');
    }

    public function redirect_to_vendors(): void {
        $this->redirect_to_post_type('abc_vendor', 'manage_options');
    }

    private function redirect_to_post_type(string $post_type, string $capability): void {
        if (!current_user_can($capability)) {
            wp_die('Insufficient permissions.');
        }

        wp_safe_redirect(admin_url('edit.php?post_type=' . $post_type));
        exit;
    }
}
