<?php

class ABC_Assets {
    public function register(): void {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend']);
    }

    public function enqueue_admin(): void {
        wp_enqueue_style('abc-suite-admin', ABC_SUITE_URL . 'assets/css/admin.css', [], ABC_SUITE_VERSION);
        wp_enqueue_script('abc-suite-admin', ABC_SUITE_URL . 'assets/js/admin.js', ['jquery'], ABC_SUITE_VERSION, true);
    }

    public function enqueue_frontend(): void {
        wp_register_style('abc-suite-frontend', ABC_SUITE_URL . 'assets/css/frontend.css', [], ABC_SUITE_VERSION);
        wp_register_script('abc-suite-frontend', ABC_SUITE_URL . 'assets/js/frontend.js', ['jquery'], ABC_SUITE_VERSION, true);
        wp_localize_script('abc-suite-frontend', 'abcSuite', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('abc_suite_search'),
        ]);
    }
}
