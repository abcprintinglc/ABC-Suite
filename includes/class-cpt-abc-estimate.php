<?php

class ABC_CPT_ABC_Estimate {
    public const POST_TYPE = 'abc_estimate';

    public function register(): void {
        add_action('init', [$this, 'register_post_type']);
    }

    public function register_post_type(): void {
        $labels = [
            'name' => 'Estimates & Jobs',
            'singular_name' => 'Estimate/Job',
            'add_new_item' => 'Add New Estimate/Job',
            'edit_item' => 'Edit Estimate/Job',
            'menu_name' => 'Estimator',
        ];

        register_post_type(self::POST_TYPE, [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-clipboard',
        ]);
    }
}
