<?php

class ABC_Admin_Logbook {
    public function register(): void {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu(): void {
        add_submenu_page(
            'edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE,
            'Log Book',
            'Log Book',
            'edit_posts',
            'abc-log-book',
            [$this, 'render_page']
        );

        add_submenu_page(
            'edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE,
            'Product Library',
            'Product Library',
            'edit_posts',
            'edit.php?post_type=' . ABC_CPT_ABC_Product_Template::POST_TYPE
        );

        add_submenu_page(
            'edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE,
            'Price Matrix',
            'Price Matrix',
            'manage_options',
            'abc-price-matrix',
            [new ABC_Price_Matrix_Admin(), 'render_page']
        );

        add_submenu_page(
            'edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE,
            'Payout Report',
            'Payout Report',
            'manage_options',
            'abc-payout-report',
            [new ABC_Payout_Report(), 'render_page']
        );

        add_submenu_page(
            'edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE,
            'Estimator Settings',
            'Estimator Settings',
            'manage_options',
            'abc-estimator-settings',
            [new ABC_Estimator_Settings(), 'render_page']
        );

        add_submenu_page(
            'edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE,
            'Estimate Learning Log',
            'Estimate Learning Log',
            'manage_options',
            'abc-estimate-learning-log',
            [new ABC_Estimate_Learning_Log(), 'render_page']
        );

        add_submenu_page(
            'edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE,
            'Design Requests',
            'Design Requests',
            'edit_posts',
            'edit.php?post_type=' . ABC_Design_Request::POST_TYPE
        );

        add_submenu_page(
            'edit.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE,
            'Import / Data Tools',
            'Import / Data Tools',
            'manage_options',
            'abc-data-tools',
            [new ABC_CSV_Tools(), 'render_page']
        );
    }

    public function render_page(): void {
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions.');
        }
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Estimator Log Book</h1>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE)); ?>" class="page-title-action">New Job Jacket</a>

            <div class="abc-logbook-panel">
                <div class="abc-logbook-toolbar">
                    <input type="text" id="abc-log-search" placeholder="Search invoice #, client, job name, keywords..." class="abc-logbook-input">
                    <span class="spinner" id="abc-admin-spinner" style="float:none; margin:0;"></span>
                </div>

                <table class="widefat striped abc-logbook-table">
                    <thead>
                        <tr>
                            <th style="width:160px;">Invoice #</th>
                            <th>Job / Client</th>
                            <th style="width:140px;">Stage</th>
                            <th style="width:140px;">Due Date</th>
                            <th style="width:220px;">Actions</th>
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
