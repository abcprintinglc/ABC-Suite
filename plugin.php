<?php
/**
 * Plugin Name: ABC Suite
 * Description: Modular monolith for ABC Printing internal workflow.
 * Version: 1.8.2
 * Author: ABC Printing
 * Update URI: https://github.com/abcprintinglc/ABC-Suite
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ABC_SUITE_VERSION', '1.8.2');
define('ABC_SUITE_PATH', plugin_dir_path(__FILE__));
define('ABC_SUITE_URL', plugin_dir_url(__FILE__));
define('ABC_SUITE_PLUGIN_FILE', __FILE__);

require_once ABC_SUITE_PATH . 'includes/class-abc-suite.php';
require_once ABC_SUITE_PATH . 'includes/class-price-matrix.php';
require_once ABC_SUITE_PATH . 'includes/class-user-roles.php';
require_once ABC_SUITE_PATH . 'includes/class-installer.php';
require_once ABC_SUITE_PATH . 'includes/class-lifecycle.php';

function abc_suite_record_boot_error($message) {
    if (!is_string($message) || $message === '') {
        return;
    }

    update_option('abc_suite_boot_error', wp_strip_all_tags($message), false);
}

function abc_suite_admin_notices() {
    if (!current_user_can('activate_plugins')) {
        return;
    }

    $message = get_option('abc_suite_boot_error', '');
    if ($message) {
        echo '<div class="notice notice-error"><p><strong>ABC Suite:</strong> ' . esc_html($message) . '</p></div>';
    }

    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-warning"><p><strong>ABC Suite:</strong> WooCommerce is not active. Storefront/order integration features will stay disabled until WooCommerce is installed and activated.</p></div>';
    }
}
add_action('admin_notices', 'abc_suite_admin_notices');

function abc_suite_boot() {
    try {
        delete_option('abc_suite_boot_error');
        $suite = new ABC_Suite();
        $suite->boot();
    } catch (Throwable $e) {
        abc_suite_record_boot_error('Plugin boot failed: ' . $e->getMessage());
        error_log('[ABC Suite] Boot failure: ' . $e->getMessage());
    }
}
add_action('plugins_loaded', 'abc_suite_boot');

function abc_suite_activate() {
    try {
        ABC_Lifecycle::activate();
    } catch (Throwable $e) {
        abc_suite_record_boot_error('Activation failed: ' . $e->getMessage());
        wp_die('ABC Suite activation failed: ' . esc_html($e->getMessage()));
    }
}

function abc_suite_deactivate() {
    try {
        ABC_Lifecycle::deactivate();
    } catch (Throwable $e) {
        error_log('[ABC Suite] Deactivation warning: ' . $e->getMessage());
    }
}

register_activation_hook(__FILE__, 'abc_suite_activate');
register_deactivation_hook(__FILE__, 'abc_suite_deactivate');
register_uninstall_hook(__FILE__, ['ABC_Lifecycle', 'uninstall']);
