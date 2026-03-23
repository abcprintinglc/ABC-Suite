<?php

class ABC_Lifecycle {
    public static function activate(): void {
        if (class_exists('ABC_Price_Matrix')) {
            ABC_Price_Matrix::create_table();
        }

        if (class_exists('ABC_Installer')) {
            ABC_Installer::activate();
        }

        if (class_exists('ABC_User_Roles')) {
            ABC_User_Roles::ensure_roles_static();
        }

        if (function_exists('get_role') && get_role('customer')) {
            update_option('default_role', 'customer');
        }

        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules(false);
        }
    }

    public static function deactivate(): void {
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules(false);
        }
    }

    public static function uninstall(): void {
        $remove_data = get_option('abc_suite_remove_data_on_uninstall', '0');
        if ($remove_data !== '1') {
            return;
        }

        global $wpdb;
        if (!isset($wpdb) || !is_object($wpdb)) {
            return;
        }

        $prefix = $wpdb->prefix;
        $tables = [
            $prefix . 'abc_job_logs',
            $prefix . 'abc_commission_items',
            $prefix . 'abc_pricing_rules',
            $prefix . 'abc_template_items',
            $prefix . 'abc_order_links',
            $prefix . 'abc_status_history',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
        }

        delete_option('abc_suite_boot_error');
    }
}
