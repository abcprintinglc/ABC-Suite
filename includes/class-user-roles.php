<?php

class ABC_User_Roles {
    public function register(): void {
        add_action('init', [$this, 'ensure_roles']);
    }

    public static function ensure_roles_static(): void {
        $self = new self();
        $self->ensure_roles();
    }

    public function ensure_roles(): void {
        $roles = [
            'sales_rep' => 'Sales Rep',
            'designer' => 'Designer',
            'production_staff' => 'Production Staff',
            'vendor_partner' => 'Vendor Partner',
            // Backward compatibility aliases.
            'sales' => 'Sales',
            'printer_tech' => 'Printer Tech',
            'customer' => 'Customer',
        ];

        foreach ($roles as $slug => $label) {
            add_role($slug, $label, $this->capabilities_for($slug));
        }

        $shop_manager = get_role('shop_manager');
        if ($shop_manager) {
            foreach ($this->manager_caps() as $cap) {
                $shop_manager->add_cap($cap);
            }
        }
    }

    private function capabilities_for(string $slug): array {
        $base = ['read' => true];

        if (in_array($slug, ['sales_rep', 'sales', 'designer', 'production_staff', 'printer_tech'], true)) {
            $base['edit_posts'] = true;
            $base['upload_files'] = true;
        }

        if ($slug === 'vendor_partner') {
            $base['upload_files'] = true;
        }

        return $base;
    }

    private function manager_caps(): array {
        return [
            'read',
            'edit_posts',
            'edit_others_posts',
            'publish_posts',
            'upload_files',
            'manage_options',
        ];
    }
}
