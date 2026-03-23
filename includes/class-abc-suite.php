<?php

class ABC_Suite {
    public function boot(): void {
        $this->includes();
        $this->init_modules();
    }

    private function includes(): void {
        $files = [
            'includes/b2b-designer-helpers.php',
            'includes/class-cpt-abc-estimate.php',
            'includes/class-cpt-abc-product-template.php',
            'includes/class-meta-box-job-jacket.php',
            'includes/class-history.php',
            'includes/class-search-index.php',
            'includes/class-ajax.php',
            'includes/class-print-view.php',
            'includes/class-admin-logbook.php',
            'includes/class-csv-tools.php',
            'includes/class-shortcode-logbook.php',
            'includes/class-duplicate-action.php',
            'includes/class-assets.php',
            'includes/class-price-matrix.php',
            'includes/class-price-matrix-admin.php',
            'includes/class-payout-report.php',
            'includes/class-estimator-settings.php',
            'includes/class-estimate-learning-log.php',
            'includes/class-design-request.php',
            'includes/class-b2b-designer-frontend.php',
            'includes/class-user-roles.php',
            'includes/class-cpt-abc-suite-records.php',
            'includes/class-admin-suite-menu.php',
        ];

        foreach ($files as $file) {
            $path = ABC_SUITE_PATH . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    private function init_modules(): void {
        $modules = [
            'ABC_CPT_ABC_Estimate',
            'ABC_CPT_ABC_Product_Template',
            'ABC_Meta_Box_Job_Jacket',
            'ABC_History',
            'ABC_Search_Index',
            'ABC_Ajax',
            'ABC_Print_View',
            'ABC_Admin_Logbook',
            'ABC_CSV_Tools',
            'ABC_Shortcode_Logbook',
            'ABC_Duplicate_Action',
            'ABC_Assets',
            'ABC_Design_Request',
            'ABC_B2B_Designer_Frontend',
            'ABC_User_Roles',
            'ABC_CPT_ABC_Suite_Records',
            'ABC_Admin_Suite_Menu',
        ];

        foreach ($modules as $module) {
            if (!class_exists($module)) {
                continue;
            }

            $instance = new $module();
            if (method_exists($instance, 'register')) {
                $instance->register();
            }
        }
    }
}
