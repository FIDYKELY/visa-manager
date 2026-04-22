<?php
// includes/class-visa-request-handler.php

defined('ABSPATH') || exit;

class Visa_Request_Handler {
    public function __construct() {
        add_shortcode('visa_level1_form', [$this, 'render_level1_form']);
        add_action('init', [$this, 'handle_level1_submission']);
        add_action('init', [$this, 'handle_account_activation']);
    }

    public function render_level1_form() {
        ob_start();
        include VISA_MANAGER_PATH . 'templates/form-level-1.php';
        return ob_get_clean();
    }

    public function handle_level1_submission() {
        if (isset($_POST['visa_level1_submit'])) {
            if (isset($_POST['g-recaptcha-response'])) {
                $captcha = $_POST['g-recaptcha-response'];
                $secretKey = '6LfKYIcrAAAAAEgE0_zQB8dpIr695yL1JcM6BQTW';
                $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
                    'body' => [
                        'secret'   => $secretKey,
                        'response' => $captcha,
                        'remoteip' => $_SERVER['REMOTE_ADDR']
                    ]
                ]);
            
                $response_body = json_decode(wp_remote_retrieve_body($response), true);
            
                if (!$response_body['success']) {
                    // CAPTCHA invalide : rediriger ou afficher une erreur
                    wp_redirect(add_query_arg('captcha_error', '1', $_SERVER['REQUEST_URI']));
                    exit;
                }
            }

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

            $user = get_user_by('email', $email);

            if ($post_id) {
                $visa_uuid = 'visa_' . $post_id;

                update_post_meta($post_id, 'visa_uuid', $visa_uuid);
        
                wp_update_post([
                    'ID' => $post_id,
                    'post_title' => 'Demande ' . $visa_uuid,
                ]);
        
                $user = get_user_by('email', $email);
                if ($user) {
                    if (wp_check_password($password, $user->user_pass, $user->ID)) {
                        // Connexion OK
                        wp_set_current_user($user->ID);
                        wp_set_auth_cookie($user->ID);

                        // Redirige vers l’étape 2
                        wp_redirect(home_url("/type-visa/?request_id={$post_id}"));
                        exit;
                    } else {
                        // Mot de passe incorrect
                        wp_redirect(add_query_arg([
                            'login_error' => 'password',
                            'email_attempted' => urlencode($email),
                        ], wp_get_referer() ?: home_url('/demande-de-visa')));
                        exit;
                    }
                } else {
                    $user_id = wp_create_user($email, $password, $email);
                    if (is_wp_error($user_id)) {
                        wp_die('Erreur lors de la création du compte utilisateur.');
                    }
            
                    $activation_code = wp_generate_password(20, false);
                    update_user_meta($user_id, 'visa_activation_code', $activation_code);
                    update_user_meta($user_id, 'visa_account_activated', false);
            /*
                    $activation_url = add_query_arg([
                        'activate_user' => $user_id,
                        'code' => $activation_code
                    ], home_url('/activation-compte'));
            */        
                    $activation_url = add_query_arg([
                        'activate_user' => $user_id,
                        'code' => $activation_code
                    ], home_url('/formulaire-visa'));
            
                    $subject = 'Activation de votre compte Visa';

                    $message = '
                    <html>
                    <head>
                      <style>
                        .email-container {
                          font-family: Arial, sans-serif;
                          color: #333;
                          padding: 20px;
                          background: #f9f9f9;
                        }
                        .button {
                          display: inline-block;
                          padding: 12px 24px;
                          margin: 20px 0;
                          background-color: #0073aa;
                          color: #fff !important;
                          text-decoration: none;
                          border-radius: 5px;
                          font-weight: bold;
                        }
                        .button:hover {
                          background-color: #005177;
                        }
                      </style>
                    </head>
                    <body>
                      <div class="email-container">
                        <h2>Bienvenue chez Visa Logistics</h2>
                        <p>Merci de vous être inscrit. Pour activer votre compte, veuillez cliquer sur le bouton ci-dessous :</p>
                        <p><a href="' . esc_url($activation_url) . '" class="button">Activer mon compte</a></p>
                        <p>Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :</p>
                        <p><a href="' . esc_url($activation_url) . '">' . esc_html($activation_url) . '</a></p>
                        <p>Merci,<br>L\'équipe Visa Logistics</p>
                      </div>
                    </body>
                    </html>
                    ';
                    
                    $headers = ['Content-Type: text/html; charset=UTF-8'];
                    
                    wp_mail($email, $subject, $message, $headers);

            
                    wp_redirect(add_query_arg('email', urlencode($email), home_url('/merci-inscription')));
                    exit;
                }
            }
        }
    }

    public function handle_account_activation() {
        if (isset($_GET['activate_user']) && isset($_GET['code'])) {
            $user_id = intval($_GET['activate_user']);
            $code = sanitize_text_field($_GET['code']);

            $saved_code = get_user_meta($user_id, 'visa_activation_code', true);
            $is_activated = get_user_meta($user_id, 'visa_account_activated', true);
            
            
            $user = get_userdata($user_id);
            if (!$user) {
                wp_die("Utilisateur introuvable.");
            }
    
            $email = $user->user_email;
            $saved_code = get_user_meta($user_id, 'visa_activation_code', true);
            $is_activated = get_user_meta($user_id, 'visa_account_activated', true);
            
            if (!$is_activated && $saved_code === $code) {
                update_user_meta($user_id, 'visa_account_activated', true);
                delete_user_meta($user_id, 'visa_activation_code');

                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);

                // Rediriger vers la page de paiement (récupère l'ID de la dernière demande liée à l'email)
                $email = get_userdata($user_id)->user_email;
                $latest_request = get_posts([
                    'post_type'  => 'visa_request',
                    'post_status' => 'any',
                    'meta_query' => [
                        [
                            'key'     => 'level1_email',
                            'value'   => $email,
                            'compare' => '=',
                        ],
                    ],
                    'orderby'    => 'date',
                    'order'      => 'DESC',
                    'numberposts'=> 1,
                ]);
/*
                if (!empty($latest_request)) {
                    $post_id = $latest_request[0]->ID;
                    wp_redirect(home_url("/paiement-visa/?request_id={$post_id}"));
                } else {
                    wp_die("Aucune demande liée trouvée pour l'email {$email}");
                    wp_redirect(home_url('/'));
                }
*/
                if (!empty($latest_request)) {
                    $post_id = $latest_request[0]->ID;
                    // Redirige vers l'étape 2 du formulaire
                    wp_redirect(home_url("/type-visa/?request_id={$post_id}&confirmed=1"));
                } else {
                    wp_die("Aucune demande liée trouvée pour l'email {$email}");
                }
                exit;
            } else {
                wp_die("Lien d'activation invalide ou compte déjà activé.");
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