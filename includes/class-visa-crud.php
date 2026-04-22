<?php
// includes/class-visa-crud.php

defined('ABSPATH') || exit;

class Visa_CRUD {
    public function __construct() {
        add_action('init', [$this, 'register_visa_post_type']);
    }

    public function register_visa_post_type() {
        register_post_type('visa_request', [
			'label' => 'Demandes de visa',
			'public' => false,
			'show_ui' => true,
			'menu_icon' => 'dashicons-clipboard',
			'show_in_menu' => 'visa_request',
			'capability_type'   => 'post',
			'map_meta_cap' => true,
			'supports' => ['title','editor'],
		]);

        $this->add_custom_caps();
    }

    private function add_custom_caps() {
        $role = get_role('administrator');
        if ($role && !$role->has_cap('edit_visa_requests')) {
            $caps = [
                'edit_visa_requests',
                'edit_others_visa_requests',
                'publish_visa_requests',
                'read_visa_request',
                'delete_visa_request'
            ];

            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }

        // Création du rôle "visa_agent"
        if (!get_role('visa_agent')) {
            add_role('visa_agent', 'Agent Visa', [
                'read' => true,
                'edit_visa_requests' => true,
                'read_visa_request' => true
            ]);
        }
    }
}