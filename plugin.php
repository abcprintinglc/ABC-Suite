<?php
/**
 * Plugin Name: ABC Suite
 * Description: Modular monolith for ABC Printing internal workflow.
 * Version: 1.8.0
 * Author: ABC Printing
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ABC_SUITE_VERSION', '1.8.0');
define('ABC_SUITE_PATH', plugin_dir_path(__FILE__));
define('ABC_SUITE_URL', plugin_dir_url(__FILE__));

require_once ABC_SUITE_PATH . 'includes/class-abc-suite.php';
require_once ABC_SUITE_PATH . 'includes/class-price-matrix.php';

function abc_suite_boot(): void {
    $suite = new ABC_Suite();
    $suite->boot();
}

add_action('plugins_loaded', 'abc_suite_boot');

function abc_suite_activate(): void {
    ABC_Price_Matrix::create_table();
}

register_activation_hook(__FILE__, 'abc_suite_activate');
