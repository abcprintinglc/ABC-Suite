<?php

class ABC_CPT_ABC_Suite_Records {
    public function register(): void {
        add_action('init', [$this, 'register_post_types']);
    }

    public function register_post_types(): void {
        $this->register_cpt('abc_draft', 'Drafts', 'Draft');
        $this->register_cpt('abc_job', 'Jobs', 'Job');
        $this->register_cpt('abc_job_jacket', 'Job Jackets', 'Job Jacket');
        $this->register_cpt('abc_template', 'Templates', 'Template');
        $this->register_cpt('abc_vendor', 'Vendors', 'Vendor');
        $this->register_cpt('abc_commission', 'Commissions', 'Commission');
    }

    private function register_cpt(string $slug, string $plural, string $single): void {
        register_post_type($slug, [
            'labels' => [
                'name' => $plural,
                'singular_name' => $single,
                'menu_name' => $plural,
                'add_new_item' => 'Add New ' . $single,
                'edit_item' => 'Edit ' . $single,
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_rest' => false,
            'supports' => ['title', 'editor', 'author', 'revisions'],
            'map_meta_cap' => true,
            'capability_type' => 'post',
            'exclude_from_search' => true,
        ]);
    }
}
