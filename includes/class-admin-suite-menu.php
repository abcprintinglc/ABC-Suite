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
        add_submenu_page(self::MENU_SLUG, 'Estimates', 'Estimates', 'edit_posts', 'edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE);
        add_submenu_page(self::MENU_SLUG, 'Designer', 'Designer', 'edit_posts', 'edit.php?post_type=abc_draft');
        add_submenu_page(self::MENU_SLUG, 'Organization Templates', 'Organization Templates', 'edit_posts', 'edit.php?post_type=abc_template');
        add_submenu_page(self::MENU_SLUG, 'Job Log', 'Job Log', 'edit_posts', 'edit.php?post_type=abc_job');
        add_submenu_page(self::MENU_SLUG, 'Job Jackets', 'Job Jackets', 'edit_posts', 'edit.php?post_type=abc_job_jacket');
        add_submenu_page(self::MENU_SLUG, 'Commissions', 'Commissions', 'manage_options', 'edit.php?post_type=abc_commission');
        add_submenu_page(self::MENU_SLUG, 'Vendors', 'Vendors', 'manage_options', 'edit.php?post_type=abc_vendor');
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
}
