<?php

class ABC_Suite {
    public function boot(): void {
        $this->includes();
        $this->init_modules();
    }

    private function includes(): void {
        require_once ABC_SUITE_PATH . 'includes/class-cpt-abc-estimate.php';
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
    }

    private function init_modules(): void {
        (new ABC_CPT_ABC_Estimate())->register();
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
    }
}
