<?php
/**
 * Plugin Name: Visa Manager
 * Description: Plugin de gestion complète des demandes de visa multi-étapes avec interface backend personnalisée.
 * Version: 1.1.0
 * Author: Joël & Copilot
 */

defined('ABSPATH') || exit;

define('VISA_MANAGER_PATH', plugin_dir_path(__FILE__));
define('VISA_MANAGER_URL', plugin_dir_url(__FILE__));
define('VISA_MANAGER_VERSION', '1.0.0');

// Auto-chargement des classes
require_once VISA_MANAGER_PATH . 'includes/class-visa-request-handler.php';
require_once VISA_MANAGER_PATH . 'includes/class-visa-admin.php';
require_once VISA_MANAGER_PATH . 'includes/class-visa-crud.php';
require_once VISA_MANAGER_PATH . 'includes/class-visa-settings.php';
require_once VISA_MANAGER_PATH . 'includes/class-woo-integration.php';
require_once VISA_MANAGER_PATH . 'includes/class-shortcodes.php';

// Initialisation
add_action('plugins_loaded', function () {
    new Visa_Request_Handler();
    new Visa_Admin();
    new Visa_CRUD();
    new Visa_Settings();
    new Visa_Woo_Integration();
    new Visa_Shortcodes();
});

add_filter('woocommerce_price_format', 'custom_price_format');

function custom_price_format($format) {
    return '%1$s %2$s'; // Montant + Devise (ex: 22 000,00 DZD)
}

add_filter('woocommerce_currency_symbol', 'custom_dzd_symbol', 10, 2);

function custom_dzd_symbol($currency, $currency_code) {
    if ($currency_code === 'DZD') {
        return 'DZD'; // Utilise le code au lieu du symbole arabe
    }
    return $currency;
}

add_filter( 'woocommerce_order_button_text', 'custom_order_button_text' );
function custom_order_button_text( $button_text ) {
    return 'Payer';
}

wp_enqueue_script(
  'html2pdf-js',
  plugin_dir_url(__FILE__) . 'assets/js/html2pdf.bundle.min.js',
  [],
  null,
  true
);

wp_enqueue_script(
  'cerfa-export',
  plugin_dir_url(__FILE__) . 'assets/js/back.js',
  ['html2pdf-js'],
  '1.0',
  true
);

add_action('admin_enqueue_scripts', function() {
  wp_localize_script('cerfa-export', 'cerfaAjax', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce'   => wp_create_nonce('cerfa_nonce'),
    'templateUrl' => plugin_dir_url(__FILE__) . 'templates/',
    'photoCerfaUrl'    => plugins_url('assets/img/cerfa.png', __FILE__),
    'photoUeUrl'    => plugins_url('assets/img/ue.png', __FILE__),
    'photoLefaUrl'    => plugins_url('assets/img/LEF.png', __FILE__),
  ]);
});

add_action('wp_ajax_cerfa_upload_pdf', function() {
  require_once plugin_dir_path(__FILE__) . 'includes/ajax-handler.php';
});

// Woocommerce

add_filter('woocommerce_checkout_fields', 'customize_billing_phone_field');

function customize_billing_phone_field($fields) {
    $fields['billing']['billing_phone']['type'] = 'tel';
    $fields['billing']['billing_phone']['label'] = 'Téléphone';
    $fields['billing']['billing_phone']['placeholder'] = '00 213 X XX XX XX XX ou 00 33 X XX XX XX XX';
    $fields['billing']['billing_phone']['custom_attributes'] = array(
        'pattern' => '^00\s?(213|33)\s?\d\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2}$',
        'title'   => 'Format attendu : 00 213 X XX XX XX XX ou 00 33 X XX XX XX XX',
        'autocomplete' => 'tel',
    );
    return $fields;
}

add_action('woocommerce_checkout_process', 'validate_billing_phone_custom');
function validate_billing_phone_custom() {
    if (!isset($_POST['billing_phone'])) return;

    $phone = trim($_POST['billing_phone']);

    if (!preg_match('/^00\s?(213|33)\s?\d\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2}$/', $phone)) {
        wc_add_notice(__('Le numéro de téléphone doit être au format : 00 213 X XX XX XX XX ou 00 33 X XX XX XX XX'), 'error');
    }
}