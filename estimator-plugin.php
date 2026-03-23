<?php
/**
 * Legacy compatibility shim for the merged ABC Suite plugin.
 *
 * This file intentionally does not declare a WordPress plugin header.
 * The former "ABC Estimator Pro" standalone plugin has been merged into
 * the unified ABC Suite bootstrap in plugin.php.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('ABC_SUITE_PLUGIN_FILE')) {
    require_once __DIR__ . '/plugin.php';
}
