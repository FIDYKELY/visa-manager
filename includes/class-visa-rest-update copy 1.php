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
     * Déclare la route POST /visa/v1/update
     */
    public function register_routes(): void {
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

        // 2️⃣ Récupère tous les meta pour chaque post
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
}