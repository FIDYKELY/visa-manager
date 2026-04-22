<?php
// includes/class-visa-request-handler.php

defined('ABSPATH') || exit;

class Visa_Request_Handler {
    public function __construct() {
        add_shortcode('visa_level1_form', [$this, 'render_level1_form']);
        add_action('init', [$this, 'handle_level1_submission']);
        add_action('init', [$this, 'handle_account_activation']);
    }
    /**
     * Retourne le centre de dépôt pour une Wilaya donnée
     * Paramètre: $wilaya_value = "01 - Adrar" ou "01" ou "Adrar"
     */
    public static function get_depot_for_wilaya($wilaya_value) {
        // Mapping Wilaya → Dépôt extrait directement de form-level-2.php
        $wilaya_to_depot = [
            '01' => 'Oran',
            '02' => 'Oran',
            '03' => 'Alger',
            '04' => 'Constantine',
            '05' => 'Constantine',
            '06' => 'Alger',
            '07' => 'Constantine',
            '08' => 'Oran',
            '09' => 'Alger',
            '10' => 'Alger',
            '11' => 'Alger',
            '12' => 'Annaba',
            '13' => 'Oran',
            '14' => 'Oran',
            '15' => 'Alger',
            '16' => 'Alger',
            '17' => 'Alger',
            '18' => 'Constantine',
            '19' => 'Constantine',
            '20' => 'Oran',
            '21' => 'Annaba',
            '22' => 'Oran',
            '23' => 'Annaba',
            '24' => 'Annaba',
            '25' => 'Constantine',
            '26' => 'Alger',
            '27' => 'Oran',
            '28' => 'Alger',
            '29' => 'Oran',
            '30' => 'Alger',
            '31' => 'Oran',
            '32' => 'Oran',
            '33' => 'Alger',
            '34' => 'Alger',
            '35' => 'Alger',
            '36' => 'Annaba',
            '37' => 'Oran',
            '38' => 'Oran',
            '39' => 'Alger',
            '40' => 'Constantine',
            '41' => 'Annaba',
            '42' => 'Alger',
            '43' => 'Constantine',
            '44' => 'Alger',
            '45' => 'Oran',
            '46' => 'Oran',
            '47' => 'Alger',
            '48' => 'Oran',
            '49' => 'Oran',
            '50' => 'Oran',
            '51' => 'Constantine',
            '52' => 'Oran',
            '53' => 'Alger',
            '54' => 'Alger',
            '55' => 'Alger',
            '56' => 'Alger',
            '57' => 'Alger',
            '58' => 'Alger',
            '59' => 'Alger',
            '60' => 'Oran',
            '61' => 'Oran',
            '62' => 'Constantine',
            '63' => 'Constantine',
            '64' => 'Alger',
            '65' => 'Annaba',
            '66' => 'Alger',
            '67' => 'Alger',
            '68' => 'Alger',
            '69' => 'Alger',
        ];

        // Extraire le code de Wilaya (01-69) à partir de la valeur
        $wilaya_code = '';
        if (preg_match('/^(\d{2})/', $wilaya_value, $matches)) {
            $wilaya_code = $matches[1];
        }

        return $wilaya_to_depot[$wilaya_code] ?? '';
    }

    /**
     * Retourne la valeur de l'option "depot_ville" pour une Wilaya donnée
     * Format: "Depot - Code - Nom"
     */
    public static function get_depot_ville_option($wilaya_value, $wilayas_array) {
        $depot = self::get_depot_for_wilaya($wilaya_value);
        if (!$depot) {
            return '';
        }

        // Extraire le code et le nom
        $wilaya_code = '';
        $wilaya_name = '';
        if (preg_match('/^(\d{2})\s*-\s*(.+)$/', $wilaya_value, $matches)) {
            $wilaya_code = $matches[1];
            $wilaya_name = $matches[2];
        } else if (isset($wilayas_array[$wilaya_value])) {
            $wilaya_name = $wilayas_array[$wilaya_value];
            $wilaya_code = $wilaya_value;
        }

        if (!$wilaya_code || !$wilaya_name) {
            return '';
        }

        return "{$depot} - {$wilaya_code} - {$wilaya_name}";
    }
    

    public function render_level1_form() {
        ob_start();
        include VISA_MANAGER_PATH . 'templates/form-level-1.php';
        return ob_get_clean();
    }

    public function handle_level1_submission() {
        if (isset($_POST['visa_level1_submit'])) {
            $visa_motif = sanitize_text_field($_POST['visa_motif'] ?? '');
                if (empty($visa_motif) || !preg_match('/^(court_sejour|long_sejour)\|([a-z_]+)$/', $visa_motif)) {
                    wp_die('Motif de visa invalide.');
                }
                list($visa_type, $visa_objet) = explode('|', $visa_motif, 2);
                
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
                    wp_redirect(add_query_arg('captcha_error', '1', $_SERVER['REQUEST_URI']));
                    exit;
                }
            }

            $email = sanitize_email($_POST['email'] ?? '');
            $password = sanitize_text_field($_POST['password'] ?? '');
            $visa_group_checked = (isset($_POST['visa_group']) && $_POST['visa_group'] == '1') ? 1 : 0;
            $visa_group_id = sanitize_text_field($_POST['visa_group_id'] ?? '');
            $ia_wilaya = sanitize_text_field($_POST['ia_wliaya'] ?? '');
            $ia_date_naiss = sanitize_text_field($_POST['ia_date_naiss'] ?? '');
            $ia_sexe = sanitize_text_field($_POST['ia_sexe'] ?? '');
            $ia_etat_civil = sanitize_text_field($_POST['ia_etat_civil'] ?? '');
            $ia_nationalite = sanitize_text_field($_POST['ia_nationalite'] ?? '');
            $ia_resident = sanitize_text_field($_POST['ia_resident'] ?? '');
            $ia_profession = sanitize_text_field($_POST['ia_profession'] ?? '');
            $ia_secteur_activite = sanitize_text_field($_POST['secteur_activite'] ?? '');
            $ia_revenu_local = sanitize_text_field($_POST['ia_revenu_local'] ?? '');
            $ia_objet_voyage = sanitize_text_field($_POST['visa_motif'] ?? '');
            $parts = explode('|', $ia_objet_voyage);
            $motif = $parts[1] ?? null;
            $ia_destination = sanitize_text_field($_POST['ia_destination'] ?? '');
            $ia_date_arrivee = sanitize_text_field($_POST['ia_date_arrivee'] ?? '');
            $ia_date_depart = sanitize_text_field($_POST['ia_date_depart'] ?? '');
            $ia_activite_pdt_voyage = sanitize_text_field($_POST['ia_activite_pdt_voyage'] ?? '');
            $ia_contact_sur_place = sanitize_text_field($_POST['ia_contact_sur_place'] ?? '');
            $ia_moyens_subsistance_sur_place = sanitize_text_field($_POST['ia_moyens_subsistance_sur_place'] ?? '');
            // NOTE: Récupérer visa_info_objet_niveau1 du formulaire level-1
            $objet_niveau1 = sanitize_textarea_field(
                $_POST['visa_info_objet_niveau1'] ?? ''
            );
            // NOTE: le champ du front (templates/form-level-3.php) est nommé "visa_info_objet_base".
            // Ancien nom (ou variantes) : "info_objet_base". On garde un fallback pour compatibilité.
            $objet_base = sanitize_textarea_field(
                $_POST['visa_info_objet_base'] ?? ($_POST['info_objet_base'] ?? '')
            );
           
            $ia_tout = [
                'wilaya' => $ia_wilaya,
                'date_naiss' => $ia_date_naiss,
                'sexe' => $ia_sexe,
                'etat_civil' => $ia_etat_civil,
                'nationalite' => $ia_nationalite,
                'resident' => $ia_resident,
                'profession' => $ia_profession,
                'revenu_local' => $ia_revenu_local,
                'objet_voyage' => $ia_objet_voyage,
                'destination' => $ia_destination,
                'date_arrivee' => $ia_date_arrivee,
                'date_depart' => $ia_date_depart,
                'activite_pdt_voyage' => $ia_activite_pdt_voyage,
                'contact_sur_place' => $ia_contact_sur_place,
                'moyens_subsistance_sur_place' => $ia_moyens_subsistance_sur_place,
                'autre' => $objet_base,
                ];

            if (!is_email($email) || empty($password)) {
                wp_die('Email ou mot de passe invalide.');
            }

            $uuid = uniqid('visa_', true);
            $request_time = current_time('mysql');
            
            $existing_unpaid = get_posts([
                'post_type'   => 'visa_request',
                'post_status' => 'any',
                'meta_query'  => [
                    'relation' => 'AND',
                    ['key' => 'level1_email',     'value' => $email],
                    ['key' => 'visa_objet',       'value' => $visa_objet],
                ],
                'fields'      => 'ids',
                'numberposts' => 1,
            ]);
            
            $post_id = wp_insert_post([
                'post_type'   => 'visa_request',
                'post_status' => 'draft',
                'post_title'  => 'Demande ' . $uuid,
                'meta_input'  => [
                    'level1_email'     => $email,
                    'level1_password'  => $password,
                    'visa_uuid'        => $uuid,
                    'visa_request_time'=> $request_time,
                    'visa_info_objet_base' => $objet_base,
                    'visa_info_objet_niveau1' => $objet_niveau1,
                    'visa_type'        => $visa_type,
                    'visa_objet'       => $motif,
                    'visa_nationalite' => $ia_nationalite,
                    'visa_profession'  => $ia_profession,
                    'visa_secteur_activite' => $ia_secteur_activite,
                    'visa_wilaya_level1' => $ia_wilaya,
                ],
            ]);

            // Assurance supplémentaire : force l'enregistrement explicite de ces meta
            if ( $post_id && ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, 'visa_profession', $ia_profession );
                update_post_meta( $post_id, 'visa_secteur_activite', $ia_secteur_activite );
            }
            
            if ($visa_group_checked) {
                $visa_group_id = isset($_POST['visa_group_id']) ? intval($_POST['visa_group_id']) : 0;
            
                if ($visa_group_id > 0) {
                    // Vérifie si un post avec ce visa_group_id existe déjà
                    $existing_posts = get_posts([
                        'post_type'  => 'visa_request',
                        'post_status'=> 'any',
                        'meta_key'   => 'visa_group',
                        'meta_value' => $visa_group_id,
                        'fields'     => 'ids',
                        'numberposts'=> 1,
                    ]);
            
                    if (!empty($existing_posts)) {
                        update_post_meta($post_id, 'visa_group', $visa_group_id);
                    } else {
                        $last_group_id = get_option('last_visa_group_id', 0);
                        $new_group_id  = intval($last_group_id) + 1;
            
                        update_option('last_visa_group_id', $new_group_id);
                        update_post_meta($post_id, 'visa_group', $new_group_id);
                    }
            
                } else {
                    // Aucun ID fourni → créer un nouveau groupe
                    $last_group_id = get_option('last_visa_group_id', 0);
                    $new_group_id  = intval($last_group_id) + 1;
            
                    update_option('last_visa_group_id', $new_group_id);
                    update_post_meta($post_id, 'visa_group', $new_group_id);
                }
            }

            if ( is_wp_error( $post_id ) ) {
                wp_die( 'Erreur lors de la création de la demande : ' . $post_id->get_error_message() );
            }

            // 5. Poster vers n8n
            $webhook_url = 'https://n8n.joel-stephanas.com/webhook/3143af2f-70d2-4c97-be38-16e67c8836df';
            $deadline = get_post_meta($post_id, 'visa_deadline_date', true);
            $has_new_docs = get_post_meta($post_id, 'visa_has_new_docs', true) ?: false;

            $payload = [
                'post_id' => $post_id,
                'visa_info_objet_base' => $ia_tout,
                'meta' => array_merge($ia_tout, [
                    'visa_deadline_date' => $deadline,
                    'visa_has_new_docs'  => $has_new_docs,
                ]),
            ];
            
            $args = [
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( $payload ),
                'timeout' => 15,
            ];

            $response = wp_remote_post( $webhook_url, $args );
            if ( is_wp_error( $response ) ) {
                error_log( 'Webhook n8n erreur : ' . $response->get_error_message() );
            } elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
                error_log( sprintf(
                    'Webhook n8n HTTP %d – %s',
                    wp_remote_retrieve_response_code( $response ),
                    wp_remote_retrieve_body( $response )
                ) );
            }

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
                        // wp_redirect(home_url("/type-visa/?request_id={$post_id}&confirmed=1"));
                        wp_redirect(home_url("/attente-analyse/?request_id={$post_id}&confirmed=1"));
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
                    // wp_redirect(home_url("/type-visa/?request_id={$post_id}&confirmed=1"));
                    wp_redirect(home_url("/attente-analyse/?request_id={$post_id}&confirmed=1"));
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