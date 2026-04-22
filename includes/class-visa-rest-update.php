<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Visa_Rest_Update {

    /**
     * Clé secrète fixe pour valider l’appel n8n
     */
    private const WEBHOOK_SECRET = 'sk_live_51N2xZ3DfOq9lm2rX7bWj2fQ9g8aC3xY4zM5n6o7p8q9r0s1t2u3v4w5x6y7z8a9bC0D1E2F3G4H5I6J7K8L9M0N';

    public function __construct() {
        // Enregistre la route REST au lancement de l'API
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
   }

     /**
     * Crée la table visa_documents_refuses
     */
    public function create_documents_refuses_table(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'visa_documents_refuses';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            nom_document varchar(255) NOT NULL,
            fichier_url text DEFAULT NULL,
            motif_refus text DEFAULT NULL,
            date_refus date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Déclare la route POST /visa/v1/update
     */
    public function register_routes(): void {
        // Nouvelle route : ajouter un document refusé
        register_rest_route(
            'visa/v1',
            '/documents-refuses',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'handle_document_refuse' ],
                'permission_callback' => [ $this, 'validate_secret' ],
            ]
        );

        // Nouvelle route : récupérer les documents refusés d'un post
        register_rest_route(
            'visa/v1',
            '/documents-refuses/(?P<post_id>\d+)',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_documents_refuses' ],
                'permission_callback' => [ $this, 'validate_secret' ],
            ]
        );
        register_rest_route(
            'visa/v1',
            '/update',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'handle_n8n_update' ],
                'permission_callback' => [ $this, 'validate_secret' ],
            ]
        );

        register_rest_route(
            'visa/v1',
            '/validated-today',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_validated_today' ],
                'permission_callback' => [ $this, 'validate_secret' ],
            ]
        );
    
        register_rest_route(
            'visa/v1',
            '/passport-check',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'handle_passport_check' ],
                'permission_callback' => [ $this, 'validate_secret' ],
            ]
        );
    
        register_rest_route(
            'visa/v1',
            '/synthese',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'handle_synthese' ],
                'permission_callback' => [ $this, 'validate_secret' ],
            ]
        );
    
        register_rest_route(
            'visa/v1',
            '/expertise',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'handle_expertise' ],
                'permission_callback' => [ $this, 'validate_secret' ],
            ]
        );
        
        register_rest_route('visa/v1', '/upload-documents', [
            'methods' => 'POST',
            'callback' => [ $this, 'visa_receive_documents' ],
            'permission_callback' => [ $this, 'validate_secret' ],
        ]);

        register_rest_route(
            'visa/v1',
            '/to-send',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_and_update_validated' ],
                'permission_callback' => [ $this, 'validate_secret' ],
            ]
        );
        
        register_rest_route(
            'visa/v1',
            '/metas/(?P<post_id>\d+)',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_post_metas' ],
                'permission_callback' => [ $this, 'validate_secret' ],
            ]
        );

    }
    /**
     * Ajoute un document refusé à la base de données
     */
    public function handle_document_refuse( \WP_REST_Request $request ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'visa_documents_refuses';
        $data = $request->get_json_params();
        
        $post_id = isset( $data['post_id'] ) ? absint( $data['post_id'] ) : 0;
        $nom = isset( $data['nom'] ) ? sanitize_text_field( $data['nom'] ) : '';
        $date = isset( $data['date'] ) ? sanitize_text_field( $data['date'] ) : current_time( 'Y-m-d' );
        $fichier_url = isset( $data['fichier_url'] ) ? esc_url_raw( $data['fichier_url'] ) : '';
        $motif_refus = isset( $data['motif_refus'] ) ? sanitize_textarea_field( $data['motif_refus'] ) : '';
        
        // Validation de la date : si invalide ou vide, utiliser la date actuelle
        if ( empty( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            $date = current_time( 'Y-m-d' );
        }
        
        if ( ! $post_id || empty( $nom ) ) {
            return new \WP_Error(
                'invalid_data',
                'Données invalides',
                [ 'status' => 400 ]
            );
        }
        
        $result = $wpdb->insert(
            $table_name,
            [
                'post_id'       => $post_id,
                'nom_document'  => $nom,
                'motif_refus' => $motif_refus,
                'fichier_url'   => $fichier_url,
                'date_refus'    => $date,
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
            ]
        );
        
        if ( $result === false ) {
            return new \WP_Error(
                'db_error',
                'Erreur lors de l\'insertion',
                [ 'status' => 500 ]
            );
        }
        
        return rest_ensure_response( [
            'success'     => true,
            'id'          => $wpdb->insert_id,
            'post_id'     => $post_id,
            'nom'         => $nom,
            'fichier_url' => $fichier_url,
            'motif_refus' => $motif_refus,
            'date'        => $date,
        ] );
    }

    /**
     * Récupère tous les documents refusés d'un post
     */
    public function get_documents_refuses( \WP_REST_Request $request ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'visa_documents_refuses';
        $post_id = absint( $request->get_param( 'post_id' ) );
        
        $documents = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, nom_document AS nom, fichier_url, motif_refus AS motif, date_refus AS date, created_at 
                 FROM $table_name 
                 WHERE post_id = %d 
                 ORDER BY date_refus DESC",
                $post_id
            ),
            ARRAY_A
        );
        
        return rest_ensure_response( [
            'post_id'   => $post_id,
            'documents' => $documents,
            'count'     => count( $documents ),
        ] );
    }

    /**
     * Vérifie que le header x-visa-webhook-secret correspond à WEBHOOK_SECRET
     */
    public function validate_secret( \WP_REST_Request $request ): bool {
        $provided = (string) $request->get_header( 'x-visa-webhook-secret' );
        return hash_equals( self::WEBHOOK_SECRET, $provided );
    }

    /**
     * Traite la requête n8n et met à jour le post et ses méta
     */
    public function handle_n8n_update( \WP_REST_Request $request ) {
        $data = $request->get_json_params();

        // 1. Validation du post_id
        $post_id = isset( $data['post_id'] ) ? absint( $data['post_id'] ) : 0;
        if ( ! $post_id || ! get_post( $post_id ) ) {
            return new \WP_Error(
                'invalid_post_id',
                'Post ID invalide',
                [ 'status' => 400 ]
            );
        }

        // 2. Mise à jour des meta (visa_type, visa_wilaya, etc.)
        if ( ! empty( $data['meta'] ) && is_array( $data['meta'] ) ) {
            foreach ( $data['meta'] as $meta_key => $meta_value ) {
                $meta_key = sanitize_key( $meta_key );

                // Liste des clés qui peuvent contenir du HTML
                $html_fields = [ 'visa_doc_requis', 'html_content', 'instructions' ];

                if ( in_array( $meta_key, $html_fields, true ) ) {
                    $meta_value = wp_kses_post( $meta_value );
                } else {
                    $meta_value = sanitize_text_field( $meta_value );
                }

                update_post_meta( $post_id, $meta_key, $meta_value );
                
                // Efface les caches 
                wp_cache_delete( $post_id, 'post_meta' );
                clean_post_cache( $post_id );
            }
        }

        // 3. Mise à jour des champs WP (statut, titre…)
        $update_args = [ 'ID' => $post_id ];
        if ( ! empty( $data['post_status'] ) ) {
            $update_args['post_status'] = sanitize_key( $data['post_status'] );
        }
        if ( ! empty( $data['post_title'] ) ) {
            $update_args['post_title'] = sanitize_text_field( $data['post_title'] );
        }

        if ( count( $update_args ) > 1 ) {
            $result = wp_update_post( $update_args, true );
            if ( is_wp_error( $result ) ) {
                return $result;
            }
        }

        // 4. Réponse JSON
        return rest_ensure_response( [
            'success' => true,
            'post_id' => $post_id,
        ] );
    }

    /**
     * Callback pour /visa/v1/validated-today
     */
    public function get_validated_today( \WP_REST_Request $request ) {
        global $wpdb;

        $today = current_time( 'Y-m-d' ); // format YYYY-MM-DD
        $meta_key_validated = 'visa_validated';
        $meta_key_date      = 'visa_validated_date';

        $results = $wpdb->get_results( $wpdb->prepare("
            SELECT p.ID, pm1.meta_value AS validated, pm2.meta_value AS validated_date
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = %s
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
            WHERE p.post_type = 'visa_request'
              AND pm1.meta_value = '1'
              AND DATE(pm2.meta_value) = %s
        ", $meta_key_validated, $meta_key_date, $today ) );

        // Récupère tous les meta pour chaque post
        $posts_with_meta = [];
        foreach ( $results as $row ) {
            $meta = get_post_meta( $row->ID ); // retourne tous les meta sous forme de tableau
            // Simplifie : chaque meta contient un tableau de valeurs, on ne garde que la première valeur
            $meta_simple = [];
            foreach ( $meta as $key => $values ) {
                $meta_simple[$key] = maybe_unserialize($values[0] ?? null);
            }

            $posts_with_meta[] = [
                'ID'   => $row->ID,
                'meta' => $meta_simple,
            ];
        }

        return rest_ensure_response( [
            'success' => true,
            'today'   => $today,
            'count'   => count( $posts_with_meta ),
            'posts'   => $posts_with_meta,
        ] );
    }
    
    /**
     * Sauvegarde le résultat de la vérification du passeport
     */
    public function handle_passport_check( \WP_REST_Request $request ) {
        $data = $request->get_json_params();
        $post_id = isset($data['post_id']) ? absint($data['post_id']) : 0;
    
        if (!$post_id || !get_post($post_id)) {
            return new \WP_Error('invalid_post_id', 'Post ID invalide', ['status' => 400]);
        }
    
        // On attend maintenant un tableau complet venant de l'IA
        $result = $data['result'] ?? null;
    
        if (!is_array($result) || empty($result[0])) {
            return new \WP_Error('invalid_data', 'Données de vérification invalides', ['status' => 400]);
        }
    
        $comparaison = $result[0]['comparaison'] ?? [];
        $resume      = $result[0]['resume'] ?? '';
    
        // On peut stocker "resume" comme cohérence globale
        update_post_meta($post_id, 'visa_passport_coherence', sanitize_text_field($resume));
    
        // Et la comparaison détaillée en JSON pour pouvoir la récupérer
        update_post_meta($post_id, 'visa_passport_coherence_detail', $comparaison);
    
        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'coherence' => $resume,
            'detail' => $comparaison,
        ]);
    }

    /**
     * Sauvegarde l'URL du document de synthèse envoyé par n8n
     */
    public function handle_synthese( \WP_REST_Request $request ) {
        $post_id  = absint( $request->get_param('post_id') );
        $resume = sanitize_text_field( $request->get_param('resume') );
        $file_url = esc_url_raw( $request->get_param('url') );
    
        if ( ! $post_id || ! get_post( $post_id ) ) {
            return new \WP_Error( 'invalid_post_id', 'Post ID invalide', [ 'status' => 400 ] );
        }
    
        if ( empty( $file_url ) ) {
            return new \WP_Error( 'missing_url', 'URL non fournie', [ 'status' => 400 ] );
        }
    
        update_post_meta( $post_id, 'visa_synthese_doc', $file_url );
        update_post_meta( $post_id, 'visa_synthese_resume', $resume );
    
        return rest_ensure_response([
            'success'  => true,
            'post_id'  => $post_id,
            'file_url' => $file_url,
            'resume'   => $resume,
        ]);
    }
    
    /**
     * Sauvegarde l'URL du document d'expertise envoyé par n8n
     */
    public function handle_expertise( \WP_REST_Request $request ) {
        $post_id  = absint( $request->get_param('post_id') );
        $file_url = esc_url_raw( $request->get_param('url') );
    
        if ( ! $post_id || ! get_post( $post_id ) ) {
            return new \WP_Error( 'invalid_post_id', 'Post ID invalide', [ 'status' => 400 ] );
        }
    
        if ( empty( $file_url ) ) {
            return new \WP_Error( 'missing_url', 'URL non fournie', [ 'status' => 400 ] );
        }
    
        update_post_meta( $post_id, 'visa_expertise_doc', $file_url );
    
        return rest_ensure_response([
            'success'  => true,
            'post_id'  => $post_id,
            'file_url' => $file_url,
        ]);
    }
    
    /**
     * Sauvegarde des documents venant du client sur le mail
     */
    public function visa_receive_documents(WP_REST_Request $request) {
        $post_id   = absint($request->get_param('request_id'));
        $documents = $request->get_param('documents');
    
        if (!$post_id || !get_post($post_id) || empty($documents) || !is_array($documents)) {
            return new WP_Error('invalid_data', 'Données invalides', ['status' => 400]);
        }
    
        $uploaded_urls = [];
    
        foreach ($documents as $doc) {
            $filename = sanitize_file_name($doc['filename']);
            $content  = base64_decode($doc['content']);
            if (!$filename || !$content) continue;
    
            $upload = wp_upload_bits($filename, null, $content);
            if ($upload['error']) continue;
    
            $uploaded_urls[] = esc_url_raw($upload['url']);
        }
    
        if ($uploaded_urls) {
            $existing = get_post_meta($post_id, 'visa_documents', true);
            $merged   = is_array($existing) ? array_merge($existing, $uploaded_urls) : $uploaded_urls;
            update_post_meta($post_id, 'visa_documents', array_values(array_unique($merged)));
            update_post_meta($post_id, 'visa_validated', 'nouveau_document');
        }
    
        return ['success' => true, 'uploaded' => count($uploaded_urls)];
    }
    
    /**
     * Retourne toutes les demandes de visa avec visa_validated = "valide"
     * puis met à jour ce meta en "envoye"
     */
    public function get_and_update_validated( \WP_REST_Request $request ) {
        global $wpdb;
    
        // Désactive l'ajout au cache objet pendant cette requête
        wp_suspend_cache_addition( true );
    
        $meta_key   = 'visa_validated';
        $meta_value = 'valide';
    
        // SQL : ne sélectionne que la DERNIÈRE valeur de visa_validated
        $results = $wpdb->get_results( $wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm 
                ON p.ID = pm.post_id
            INNER JOIN (
                SELECT post_id, MAX(meta_id) AS last_meta_id
                FROM {$wpdb->postmeta}
                WHERE meta_key = %s
                GROUP BY post_id
            ) AS latest 
                ON latest.post_id = p.ID 
               AND latest.last_meta_id = pm.meta_id
            WHERE p.post_type = 'visa_request'
              AND pm.meta_key = %s
              AND pm.meta_value = %s
        ", $meta_key, $meta_key, $meta_value ) );
    
        // Aucun résultat
        if ( empty( $results ) ) {
            wp_suspend_cache_addition( false );
            return rest_ensure_response([
                'success' => true,
                'count'   => 0,
                'posts'   => [],
                'message' => 'Aucune demande à envoyer.',
            ]);
        }
    
        $posts_with_meta = [];
    
        foreach ( $results as $row ) {
    
            // Nettoyage du cache WordPress
            clean_post_cache( $row->ID );
            wp_cache_delete( $row->ID, 'post_meta' );
    
            // Lecture directe des metas (sans cache)
            $raw_meta = $wpdb->get_results( $wpdb->prepare("
                SELECT meta_key, meta_value
                FROM {$wpdb->postmeta}
                WHERE post_id = %d
            ", $row->ID ), ARRAY_A );
    
            // Normalisation
            $meta_simple = [];
            foreach ( $raw_meta as $m ) {
                $meta_simple[ $m['meta_key'] ] = maybe_unserialize( $m['meta_value'] );
            }
    
            $posts_with_meta[] = [
                'ID'   => $row->ID,
                'meta' => $meta_simple,
            ];
        }
    
        // Réactive le cache objet
        wp_suspend_cache_addition( false );
    
        return rest_ensure_response([
            'success' => true,
            'count'   => count( $posts_with_meta ),
            'posts'   => $posts_with_meta,
        ]);
    }
    
    public function get_post_metas( \WP_REST_Request $request ) {
        global $wpdb;
    
        $post_id = intval( $request->get_param('post_id') );
    
        if ( ! $post_id || get_post_status( $post_id ) === false ) {
            return new \WP_REST_Response( [ 'error' => 'Post ID invalide.' ], 400 );
        }
    
        // Récupérer toutes les métadonnées du post
        $metas = get_post_meta( $post_id );
    
        // Nettoyer le tableau pour ne pas avoir des sous-tableaux inutiles
        $cleaned = [];
        foreach ( $metas as $key => $values ) {
            $cleaned[ $key ] = count( $values ) === 1 ? maybe_unserialize( $values[0] ) : array_map( 'maybe_unserialize', $values );
        }
    
        return new \WP_REST_Response( [
            'post_id' => $post_id,
            'metas'   => $cleaned,
        ], 200 );
    }
}