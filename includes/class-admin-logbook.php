<?php

class ABC_Admin_Logbook {
    public function register(): void {
        // Menu registration is centralized in ABC_Admin_Suite_Menu.
    }

    public function register_menu(): void {
        // Legacy no-op kept for backward compatibility.
    }

    public function render_hub_page(): void {
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions.');
        }

        $links = [
            ['label' => 'Job Log', 'url' => admin_url('admin.php?page=' . ABC_Admin_Suite_Menu::MENU_SLUG)],
            ['label' => 'Organization Templates', 'url' => admin_url('admin.php?page=abc-suite-templates')],
            ['label' => 'Data Tools', 'url' => admin_url('admin.php?page=abc-data-tools')],
            ['label' => 'Designer', 'url' => admin_url('admin.php?page=abc-suite-designer')],
            ['label' => 'Design Requests', 'url' => admin_url('admin.php?page=abc-suite-design-requests')],
            ['label' => 'Product Library', 'url' => admin_url('admin.php?page=abc-suite-product-library')],
            ['label' => 'Price Matrix', 'url' => admin_url('admin.php?page=abc-price-matrix')],
            ['label' => 'Estimator Settings', 'url' => admin_url('admin.php?page=abc-estimator-settings')],
            ['label' => 'Estimate Learning Log', 'url' => admin_url('admin.php?page=abc-estimate-learning-log')],
        ];
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">ABC Suite Dashboard</h1>
            <p class="description">Quick access to the active ABC Suite tools.</p>
            <div class="abc-matrix-grid">
                <?php foreach ($links as $link) : ?>
                    <div class="abc-matrix-card">
                        <h2><?php echo esc_html($link['label']); ?></h2>
                        <p><a class="button button-primary" href="<?php echo esc_url($link['url']); ?>">Open</a></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function render_page(): void {
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions.');
        }
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Estimator Log Book</h1>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE)); ?>" class="page-title-action">New Job Jacket</a>

            <p class="description">Main job log view for ticket #, date, customer, quantity, amount, estimate access, job jacket creation, WooCommerce linkage, Square invoice status, and user-account tie-in.</p>

            <div class="abc-logbook-panel">
                <div class="abc-logbook-toolbar">
                    <input type="text" id="abc-log-search" placeholder="Search ticket #, client, job name, quantity, or keywords..." class="abc-logbook-input">
                    <span class="spinner" id="abc-admin-spinner" style="float:none; margin:0;"></span>
                </div>

                <table class="widefat striped abc-logbook-table">
                    <thead>
                        <tr>
                            <th style="width:120px;">Ticket #</th>
                            <th style="width:110px;">Date</th>
                            <th>Customer / Job</th>
                            <th style="width:90px;">Qty</th>
                            <th style="width:110px;">Amount</th>
                            <th style="width:140px;">Stage</th>
                            <th style="width:130px;">Woo / Square</th>
                            <th style="width:320px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="abc-log-results"></tbody>
                </table>

                <p id="abc-no-results" style="display:none; color:#666; margin-top:14px;">No jobs found.</p>
            </div>
        </div>
        <?php
    }
}
