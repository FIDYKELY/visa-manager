<?php
// includes/class-visa-settings.php

defined('ABSPATH') || exit;

class Visa_Settings {
    public function __construct() {
        add_action('admin_init', [$this, 'register_plugin_settings']);
    }

    public function register_plugin_settings() {
        register_setting('visa_settings_group', 'visa_daily_limit');
        register_setting('visa_settings_group', 'visa_shortstay_max_days');
        register_setting('visa_settings_group', 'visa_payment_amount');
		register_setting( 'visa_settings_group', 'visa_insurance_price', [
		  'type'              => 'number',
		  'sanitize_callback' => 'floatval',
		  'default'           => 6000,
		]);

    }

    public static function get_setting($key, $default = '') {
        return get_option($key, $default);
    }
}