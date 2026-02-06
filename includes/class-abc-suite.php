<?php

class ABC_Suite {
    public function boot(): void {
        $this->includes();
        $this->init_modules();
    }

    private function includes(): void {
        require_once ABC_SUITE_PATH . 'includes/b2b-designer-helpers.php';
        require_once ABC_SUITE_PATH . 'includes/class-cpt-abc-estimate.php';
        require_once ABC_SUITE_PATH . 'includes/class-cpt-abc-product-template.php';
        require_once ABC_SUITE_PATH . 'includes/class-meta-box-job-jacket.php';
        require_once ABC_SUITE_PATH . 'includes/class-history.php';
        require_once ABC_SUITE_PATH . 'includes/class-search-index.php';
        require_once ABC_SUITE_PATH . 'includes/class-ajax.php';
        require_once ABC_SUITE_PATH . 'includes/class-print-view.php';
        require_once ABC_SUITE_PATH . 'includes/class-admin-logbook.php';
        require_once ABC_SUITE_PATH . 'includes/class-csv-tools.php';
        require_once ABC_SUITE_PATH . 'includes/class-shortcode-logbook.php';
        require_once ABC_SUITE_PATH . 'includes/class-duplicate-action.php';
        require_once ABC_SUITE_PATH . 'includes/class-assets.php';
        require_once ABC_SUITE_PATH . 'includes/class-price-matrix.php';
        require_once ABC_SUITE_PATH . 'includes/class-price-matrix-admin.php';
        require_once ABC_SUITE_PATH . 'includes/class-payout-report.php';
        require_once ABC_SUITE_PATH . 'includes/class-estimator-settings.php';
        require_once ABC_SUITE_PATH . 'includes/class-estimate-learning-log.php';
        require_once ABC_SUITE_PATH . 'includes/class-design-request.php';
        require_once ABC_SUITE_PATH . 'includes/class-b2b-designer-frontend.php';
    }

    private function init_modules(): void {
        (new ABC_CPT_ABC_Estimate())->register();
        (new ABC_CPT_ABC_Product_Template())->register();
        (new ABC_Meta_Box_Job_Jacket())->register();
        (new ABC_History())->register();
        (new ABC_Search_Index())->register();
        (new ABC_Ajax())->register();
        (new ABC_Print_View())->register();
        (new ABC_Admin_Logbook())->register();
        (new ABC_CSV_Tools())->register();
        (new ABC_Shortcode_Logbook())->register();
        (new ABC_Duplicate_Action())->register();
        (new ABC_Assets())->register();
        (new ABC_Design_Request())->register();
        (new ABC_B2B_Designer_Frontend())->register();
    }
}
