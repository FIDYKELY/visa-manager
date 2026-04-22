<?php
// includes/class-visa-request-handler.php

defined('ABSPATH') || exit;

class Visa_Request_Handler {
    public function __construct() {
        add_shortcode('visa_level1_form', [$this, 'render_level1_form']);
        add_action('init', [$this, 'handle_level1_submission']);
    }

    public function render_level1_form() {
        ob_start();
        include VISA_MANAGER_PATH . 'templates/form-level-1.php';
        return ob_get_clean();
    }

    public function handle_level1_submission() {
        if (isset($_POST['visa_level1_submit'])) {
            $email = sanitize_email($_POST['email'] ?? '');
            $password = sanitize_text_field($_POST['password'] ?? '');

            if (!is_email($email) || empty($password)) {
                wp_die('Email ou mot de passe invalide.');
            }

            $uuid = uniqid('visa_', true);
            $request_time = current_time('mysql');

            $post_id = wp_insert_post([
                'post_type'   => 'visa_request',
                'post_status' => 'draft',
                'post_title'  => 'Demande ' . $uuid,
                'meta_input'  => [
                    'level1_email'     => $email,
                    'level1_password'  => $password,
                    'visa_uuid'        => $uuid,
                    'visa_request_time'=> $request_time,
                ],
            ]);

            if ($post_id) {
                // Stocke l'ID en session temporaire ou redirige avec query arg
                wp_redirect(home_url("/paiement-visa/?request_id={$post_id}"));
                exit;
            } else {
                wp_die('Erreur lors de la création de la demande.');
            }
        }
    }

    /**
     * Retourne le nombre de commandes 'processing' créées depuis le début du jour.
     *
     * @return int
     */
    public static function count_processing_requests_today() {
        global $wpdb;
        // Nom de la table (préf. dynamique)
        $table = $wpdb->prefix . 'wc_orders';

        // 1) Minuit local au format WP
        $local_midnight = date_i18n('Y-m-d') . ' 00:00:00';

        // 2) Conversion en GMT pour comparer à date_created_gmt
        $gmt_midnight  = get_gmt_from_date( $local_midnight );

        // 3) Requête de comptage
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
            WHERE status = %s
            AND date_created_gmt >= %s",
            'wc-processing',
            $gmt_midnight
        );

        return (int) $wpdb->get_var($sql);
    }
}