<?php

class ABC_Module_Loader {
    /**
     * @return array<int, array{file:string,class:string,required?:bool}>
     */
    public static function definitions(): array {
        return [
            ['file' => 'includes/b2b-designer-helpers.php', 'class' => 'ABC_B2B_Designer_Helpers', 'required' => false],
            ['file' => 'includes/class-cpt-abc-estimate.php', 'class' => 'ABC_CPT_ABC_Estimate'],
            ['file' => 'includes/class-cpt-abc-product-template.php', 'class' => 'ABC_CPT_ABC_Product_Template'],
            ['file' => 'includes/class-meta-box-job-jacket.php', 'class' => 'ABC_Meta_Box_Job_Jacket'],
            ['file' => 'includes/class-history.php', 'class' => 'ABC_History'],
            ['file' => 'includes/class-search-index.php', 'class' => 'ABC_Search_Index'],
            ['file' => 'includes/class-ajax.php', 'class' => 'ABC_Ajax'],
            ['file' => 'includes/class-print-view.php', 'class' => 'ABC_Print_View'],
            ['file' => 'includes/class-admin-logbook.php', 'class' => 'ABC_Admin_Logbook'],
            ['file' => 'includes/class-csv-tools.php', 'class' => 'ABC_CSV_Tools'],
            ['file' => 'includes/class-shortcode-logbook.php', 'class' => 'ABC_Shortcode_Logbook'],
            ['file' => 'includes/class-duplicate-action.php', 'class' => 'ABC_Duplicate_Action'],
            ['file' => 'includes/class-assets.php', 'class' => 'ABC_Assets'],
            ['file' => 'includes/class-price-matrix-admin.php', 'class' => 'ABC_Price_Matrix_Admin'],
            ['file' => 'includes/class-payout-report.php', 'class' => 'ABC_Payout_Report', 'required' => false],
            ['file' => 'includes/class-estimator-settings.php', 'class' => 'ABC_Estimator_Settings'],
            ['file' => 'includes/class-estimate-learning-log.php', 'class' => 'ABC_Estimate_Learning_Log'],
            ['file' => 'includes/class-design-request.php', 'class' => 'ABC_Design_Request'],
            ['file' => 'includes/class-b2b-designer-frontend.php', 'class' => 'ABC_B2B_Designer_Frontend'],
            ['file' => 'includes/class-cpt-abc-suite-records.php', 'class' => 'ABC_CPT_ABC_Suite_Records'],
            ['file' => 'includes/class-admin-suite-menu.php', 'class' => 'ABC_Admin_Suite_Menu'],
            ['file' => 'includes/class-openclaw-bridge.php', 'class' => 'ABC_OpenClaw_Bridge', 'required' => false],
        ];
    }

    /**
     * @return array{loaded:string[],registered:string[],missing_files:string[],missing_classes:string[],module_errors:array<int,string>}
     */
    public static function load_and_register(): array {
        $report = [
            'loaded' => [],
            'registered' => [],
            'missing_files' => [],
            'missing_classes' => [],
            'module_errors' => [],
        ];

        foreach (self::definitions() as $definition) {
            $file = ABC_SUITE_PATH . $definition['file'];
            $class = $definition['class'];
            $required = $definition['required'] ?? true;

            if (file_exists($file)) {
                require_once $file;
                $report['loaded'][] = $definition['file'];
            } elseif ($required) {
                $report['missing_files'][] = $definition['file'];
                continue;
            } else {
                continue;
            }

            if (!class_exists($class)) {
                if ($required) {
                    $report['missing_classes'][] = $class;
                }
                continue;
            }

            try {
                $instance = new $class();
                if (method_exists($instance, 'register')) {
                    $instance->register();
                    $report['registered'][] = $class;
                }
            } catch (Throwable $e) {
                $report['module_errors'][] = $class . ': ' . $e->getMessage();
                error_log('[ABC Suite] Module register failure in ' . $class . ': ' . $e->getMessage());
            }
        }

        return $report;
    }
}
