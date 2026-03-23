<?php

class ABC_Installer {
    public static function activate(): void {
        self::create_custom_tables();
    }

    public static function create_custom_tables(): void {
        global $wpdb;

        if (!isset($wpdb) || !is_object($wpdb)) {
            return;
        }

        if (!defined('ABSPATH')) {
            return;
        }

        $upgrade_path = ABSPATH . 'wp-admin/includes/upgrade.php';
        if (!file_exists($upgrade_path)) {
            return;
        }

        require_once $upgrade_path;

        if (!function_exists('dbDelta')) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;

        $tables = [
            "CREATE TABLE {$prefix}abc_job_logs (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                job_id bigint(20) unsigned NOT NULL,
                order_id bigint(20) unsigned DEFAULT 0,
                estimate_id bigint(20) unsigned DEFAULT 0,
                user_id bigint(20) unsigned DEFAULT 0,
                status varchar(100) NOT NULL DEFAULT '',
                note longtext NULL,
                machine varchar(190) NOT NULL DEFAULT '',
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY job_id (job_id),
                KEY order_id (order_id),
                KEY created_at (created_at)
            ) $charset_collate",
            "CREATE TABLE {$prefix}abc_commission_items (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                order_id bigint(20) unsigned NOT NULL,
                job_id bigint(20) unsigned DEFAULT 0,
                estimate_id bigint(20) unsigned DEFAULT 0,
                recipient_user_id bigint(20) unsigned DEFAULT 0,
                commission_type varchar(100) NOT NULL DEFAULT '',
                basis_amount decimal(18,2) NOT NULL DEFAULT 0,
                rate decimal(10,4) NOT NULL DEFAULT 0,
                amount decimal(18,2) NOT NULL DEFAULT 0,
                status varchar(50) NOT NULL DEFAULT 'pending',
                earned_at datetime NULL,
                paid_at datetime NULL,
                notes longtext NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY order_id (order_id),
                KEY job_id (job_id),
                KEY recipient_user_id (recipient_user_id),
                KEY status (status)
            ) $charset_collate",
            "CREATE TABLE {$prefix}abc_pricing_rules (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                name varchar(190) NOT NULL,
                product_type varchar(100) NOT NULL DEFAULT '',
                rule_type varchar(100) NOT NULL DEFAULT '',
                rule_config longtext NULL,
                priority int(11) NOT NULL DEFAULT 10,
                is_active tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY product_type (product_type),
                KEY is_active (is_active)
            ) $charset_collate",
            "CREATE TABLE {$prefix}abc_template_items (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                template_id bigint(20) unsigned NOT NULL,
                item_type varchar(100) NOT NULL DEFAULT '',
                item_key varchar(190) NOT NULL DEFAULT '',
                item_value longtext NULL,
                sort_order int(11) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY template_id (template_id),
                KEY item_type (item_type),
                KEY item_key (item_key)
            ) $charset_collate",
            "CREATE TABLE {$prefix}abc_order_links (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                order_id bigint(20) unsigned NOT NULL,
                estimate_id bigint(20) unsigned DEFAULT 0,
                job_id bigint(20) unsigned DEFAULT 0,
                job_jacket_id bigint(20) unsigned DEFAULT 0,
                draft_id bigint(20) unsigned DEFAULT 0,
                customer_id bigint(20) unsigned DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY order_id (order_id),
                KEY estimate_id (estimate_id),
                KEY job_id (job_id),
                KEY customer_id (customer_id)
            ) $charset_collate",
            "CREATE TABLE {$prefix}abc_status_history (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                entity_type varchar(50) NOT NULL,
                entity_id bigint(20) unsigned NOT NULL,
                old_status varchar(100) NOT NULL DEFAULT '',
                new_status varchar(100) NOT NULL DEFAULT '',
                changed_by bigint(20) unsigned DEFAULT 0,
                note longtext NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY entity_lookup (entity_type,entity_id),
                KEY created_at (created_at)
            ) $charset_collate",
        ];

        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }
}
