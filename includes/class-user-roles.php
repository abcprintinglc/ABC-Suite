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
        add_role('printer_tech', 'Printer Tech', [
            'read' => true,
            'edit_posts' => true,
        ]);
        add_role('designer', 'Designer', [
            'read' => true,
            'edit_posts' => true,
        ]);
        add_role('sales', 'Sales', [
            'read' => true,
            'edit_posts' => true,
        ]);
        add_role('customer', 'Customer', [
            'read' => true,
        ]);
    }
}
