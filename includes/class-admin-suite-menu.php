<?php

class ABC_Admin_Suite_Menu {
    public const MENU_SLUG = 'abc-suite-dashboard';

    public function register(): void {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu(): void {
        add_menu_page(
            'ABC Suite',
            'ABC Suite',
            'edit_posts',
            self::MENU_SLUG,
            [$this, 'render_dashboard'],
            'dashicons-layout',
            25
        );

        add_submenu_page(self::MENU_SLUG, 'Dashboard', 'Dashboard', 'edit_posts', self::MENU_SLUG, [$this, 'render_dashboard']);
        add_submenu_page(self::MENU_SLUG, 'Estimates', 'Estimates', 'edit_posts', 'abc-suite-estimates', [$this, 'redirect_to_estimates']);
        add_submenu_page(self::MENU_SLUG, 'Designer', 'Designer', 'edit_posts', 'abc-suite-designer', [$this, 'redirect_to_designer']);
        add_submenu_page(self::MENU_SLUG, 'Organization Templates', 'Organization Templates', 'edit_posts', 'abc-suite-templates', [$this, 'redirect_to_templates']);
        add_submenu_page(self::MENU_SLUG, 'Job Log', 'Job Log', 'edit_posts', 'abc-suite-jobs', [$this, 'redirect_to_jobs']);
        add_submenu_page(self::MENU_SLUG, 'Job Jackets', 'Job Jackets', 'edit_posts', 'abc-suite-job-jackets', [$this, 'redirect_to_job_jackets']);
        add_submenu_page(self::MENU_SLUG, 'Commissions', 'Commissions', 'manage_options', 'abc-suite-commissions', [$this, 'redirect_to_commissions']);
        add_submenu_page(self::MENU_SLUG, 'Vendors', 'Vendors', 'manage_options', 'abc-suite-vendors', [$this, 'redirect_to_vendors']);
        add_submenu_page(self::MENU_SLUG, 'WooCommerce Integration', 'WooCommerce Integration', 'manage_options', 'abc-suite-woocommerce', [$this, 'render_placeholder']);
        add_submenu_page(self::MENU_SLUG, 'Reports', 'Reports', 'manage_options', 'abc-suite-reports', [$this, 'render_placeholder']);
        add_submenu_page(self::MENU_SLUG, 'Settings', 'Settings', 'manage_options', 'abc-suite-settings', [$this, 'render_placeholder']);
    }

    public function render_dashboard(): void {
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions.');
        }

        $widgets = [
            'Today\'s jobs',
            'Awaiting customer approval',
            'Awaiting proof approval',
            'In production',
            'Ready for pickup',
            'Commission pending',
            'Recent activity log',
        ];
        ?>
        <div class="wrap">
            <h1>ABC Suite Dashboard</h1>
            <p>Operational overview for estimates, proofs, production, and commissions.</p>
            <div class="abc-matrix-grid">
                <?php foreach ($widgets as $widget) : ?>
                    <div class="abc-matrix-card">
                        <h2><?php echo esc_html($widget); ?></h2>
                        <p><em>Module data will appear here as each phase is completed.</em></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
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

    public function redirect_to_estimates(): void {
        $this->redirect_to_post_type(ABC_CPT_ABC_Estimate::POST_TYPE, 'edit_posts');
    }

    public function redirect_to_designer(): void {
        $this->redirect_to_post_type('abc_draft', 'edit_posts');
    }

    public function redirect_to_templates(): void {
        $this->redirect_to_post_type('abc_template', 'edit_posts');
    }

    public function redirect_to_jobs(): void {
        $this->redirect_to_post_type('abc_job', 'edit_posts');
    }

    public function redirect_to_job_jackets(): void {
        $this->redirect_to_post_type('abc_job_jacket', 'edit_posts');
    }

    public function redirect_to_commissions(): void {
        $this->redirect_to_post_type('abc_commission', 'manage_options');
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
