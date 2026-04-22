<?php
// includes/class-shortcodes.php

defined('ABSPATH') || exit;

class Visa_Shortcodes {
    public function __construct() {
        add_shortcode('attente_analyse', [$this, 'render_attente_analyse']);
        add_shortcode('visa_level2_form', [$this, 'render_level2_form']);
        add_shortcode('visa_level3_form', [$this, 'render_level3_form']);
        add_shortcode('visa_level4_form', [$this, 'render_level4_form']);
        add_shortcode('visa_mandate_form', [$this, 'render_mandate_step']);
    }

    public function render_attente_analyse() {
        ob_start();
        include VISA_MANAGER_PATH . 'templates/attente-analyse.php';
        return ob_get_clean();
    }

    public function render_level2_form() {
        ob_start();
        include VISA_MANAGER_PATH . 'templates/form-level-2.php';
        return ob_get_clean();
    }

    public function render_level3_form() {
        ob_start();
        include VISA_MANAGER_PATH . 'templates/form-level-3.php';
        return ob_get_clean();
    }

    // public function render_level4_form() {
    //     ob_start();
    //     include VISA_MANAGER_PATH . 'templates/form-level-4.php';
    //     return ob_get_clean();
    // }

    public function render_mandate_step() {
        ob_start();
        include VISA_MANAGER_PATH . 'templates/mandate-step.php';
        return ob_get_clean();
    }
}