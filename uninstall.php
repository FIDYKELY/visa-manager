<?php
// uninstall.php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Suppression des options
delete_option('visa_daily_limit');
delete_option('visa_shortstay_max_days');
delete_option('visa_payment_amount');
delete_option('visa_product_id');

// Suppression des rôles personnalisés
remove_role('visa_agent');

// Suppression des demandes de visa
$requests = get_posts([
    'post_type' => 'visa_request',
    'numberposts' => -1,
    'fields' => 'ids',
]);

foreach ($requests as $post_id) {
    wp_delete_post($post_id, true);
}