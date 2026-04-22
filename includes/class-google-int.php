<?php
if (!defined('ABSPATH')) {
    exit;
}

class Visa_Manager_Google_Int {

    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $scopes = [];

    public function __construct() {

        // Charger Composer autoload
        $autoload = plugin_dir_path(__DIR__) . 'vendor/autoload.php';
        if (!file_exists($autoload)) {
            error_log('[Visa Manager] vendor/autoload.php introuvable');
            return;
        }
        require_once $autoload;
    
        if (!class_exists('Google_Client')) {
            error_log('[Visa Manager] Google_Client introuvable');
            return;
        }
    
        // Maintenant la classe Google_Service_Gmail existe
        $this->scopes = [
            Google_Service_Gmail::GMAIL_READONLY,
        ];
    
        // Config Google
        $this->client_id     = "445674036632-1i9h3qm4fdvep1dfcrgkustdr28p6p39.apps.googleusercontent.com";
        $this->client_secret = "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";
        $this->redirect_uri  = admin_url('admin-ajax.php?action=visa_gmail_callback');
    
        // Hooks AJAX
        add_action('wp_ajax_visa_gmail_oauth', [$this, 'start_user_oauth']);
        add_action('wp_ajax_nopriv_visa_gmail_oauth', [$this, 'start_user_oauth']);
    
        add_action('wp_ajax_visa_gmail_callback', [$this, 'oauth_callback']);
        add_action('wp_ajax_nopriv_visa_gmail_callback', [$this, 'oauth_callback']);
    }


    /**
     * Initialisation du client Google
     */
    private function get_client() {

        $client = new Google_Client();
        $client->setClientId($this->client_id);
        $client->setClientSecret($this->client_secret);
        $client->setRedirectUri($this->redirect_uri);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);

        foreach ($this->scopes as $scope) {
            $client->addScope($scope);
        }

        return $client;
    }

    /**
     * Bouton FRONT -> OAuth utilisateur
     */
    public function start_user_oauth() {

        if (!is_user_logged_in()) {
            wp_die('Connexion requise');
        }

        if (empty($this->client_id) || empty($this->client_secret)) {
            wp_die('Configuration Google manquante');
        }

        $user_id = get_current_user_id();
        $client  = $this->get_client();

        // ? Liaison sï¿½curisï¿½e user <-> callback
        $client->setState((string) $user_id);

        wp_redirect($client->createAuthUrl());
        exit;
    }

    /**
     * Callback Google OAuth
     */
    public function oauth_callback() {

        if (!isset($_GET['code'], $_GET['state'])) {
            wp_die('OAuth invalide');
        }

        $user_id = intval($_GET['state']);
        if (!$user_id || !get_user_by('id', $user_id)) {
            wp_die('Utilisateur invalide');
        }

        $client = $this->get_client();
        $token  = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (isset($token['error'])) {
            wp_die('Erreur OAuth Google');
        }

        // ? Stockage token par utilisateur
        update_user_meta($user_id, 'visa_gmail_token', $token);

        wp_redirect('/connexion-gmail-reussie');
        exit;
    }

    /**
     * Rï¿½cupï¿½rer le service Gmail d'un utilisateur (ADMIN)
     */
    public static function get_user_gmail_service($user_id) {

        if (!current_user_can('administrator')) {
            return false;
        }

        $token = get_user_meta($user_id, 'visa_gmail_token', true);
        if (!$token) {
            return false;
        }

        $autoload = plugin_dir_path(__DIR__) . 'vendor/autoload.php';
        require_once $autoload;

        $client = new Google_Client();
        $client->setClientId(get_option('visa_google_client_id'));
        $client->setClientSecret(get_option('visa_google_client_secret'));
        $client->setAccessType('offline');
        $client->addScope(Google_Service_Gmail::GMAIL_READONLY);

        $client->setAccessToken($token);

        // ? Refresh automatique
        if ($client->isAccessTokenExpired()) {
            if (!empty($token['refresh_token'])) {
                $client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                update_user_meta($user_id, 'visa_gmail_token', $client->getAccessToken());
            } else {
                return false;
            }
        }

        return new Google_Service_Gmail($client);
    }
}
