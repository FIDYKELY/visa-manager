<?php
/**
 * Plugin Name: Visa Manager
 * Description: Plugin de gestion complète des demandes de visa multi-étapes avec interface backend personnalisée.
 * Version: 1.3.0
 * Author: Joël Stépphanas
 */

defined('ABSPATH') || exit;

define('VISA_MANAGER_PATH', plugin_dir_path(__FILE__));
define('VISA_MANAGER_URL', plugin_dir_url(__FILE__));
define('VISA_MANAGER_VERSION', '1.0.0');

// === AJOUTER CETTE FONCTION ICI ===
function vm_create_documents_refuses_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'visa_documents_refuses';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        nom_document varchar(255) NOT NULL,
        date_refus date NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id)
    ) {$charset_collate};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
// Auto-chargement des classes
require_once VISA_MANAGER_PATH . 'includes/class-visa-request-handler.php';
require_once VISA_MANAGER_PATH . 'includes/class-visa-admin.php';
require_once VISA_MANAGER_PATH . 'includes/class-visa-crud.php';
require_once VISA_MANAGER_PATH . 'includes/class-visa-settings.php';
require_once VISA_MANAGER_PATH . 'includes/class-woo-integration.php';
require_once VISA_MANAGER_PATH . 'includes/class-shortcodes.php';
require_once VISA_MANAGER_PATH . 'includes/class-google-int.php';
new Visa_Manager_Google_Int();

// N8N
require_once VISA_MANAGER_PATH . 'includes/class-visa-rest-update.php';

add_action('init', function() {
    if (isset($_GET['test_email']) && current_user_can('manage_options')) {
        $order = wc_get_orders(['limit' => 1, 'status' => 'processing'])[0] ?? null;
        if ($order) {
            error_log('TEST EMAIL: Hook déclenché manuellement');
            // Simule l'appel du hook
            do_action('woocommerce_order_status_processing', $order->get_id());
        } else {
            error_log('TEST EMAIL: Aucune commande "processing" trouvée');
        }
    }
});

// Initialisation
add_action('plugins_loaded', function () {
    new Visa_Request_Handler();
    new Visa_Admin();
    new Visa_CRUD();
    new Visa_Settings();
    new Visa_Woo_Integration();
    new Visa_Shortcodes();
    new Visa_Rest_Update();
});

function custom_checkout_notranslate() {
    return '<div class="notranslate" translate="no">' . do_shortcode('[woocommerce_checkout]') . '</div>';
}
add_shortcode('custom_checkout', 'custom_checkout_notranslate');



// Rôle
register_activation_hook(__FILE__, 'vm_activate');
function vm_activate() {
    // Création de la table documents refusés
    vm_create_documents_refuses_table();
    $cap = 'manage_visa_manager';

    // Créer le rôle visa_manager s'il n'existe pas (optionnel)
    if ( null === get_role('visa_manager') ) {
        add_role('visa_manager', 'Agent Visa', array('read' => true));
    }

    // Donner la capability au rôle visa_manager
    $role_agent = get_role('visa_manager');
    if ($role_agent && ! $role_agent->has_cap($cap)) {
        $role_agent->add_cap($cap);
    }

    // Donner la capability explicitement à administrator (sécurise si admin n'a pas toutes les caps)
    $role_admin = get_role('administrator');
    if ($role_admin && ! $role_admin->has_cap($cap)) {
        $role_admin->add_cap($cap);
    }
}

register_deactivation_hook(__FILE__, 'vm_deactivate');
function vm_deactivate() {
    $cap = 'manage_visa_manager';

    // Retirer la capability du rôle visa_manager (optionnel)
    $role_agent = get_role('visa_manager');
    if ($role_agent && $role_agent->has_cap($cap)) {
        $role_agent->remove_cap($cap);
    }

    // Ne retirez pas automatiquement la capability des admins sauf si vous êtes sûr
    // $role_admin = get_role('administrator');
    // if ($role_admin && $role_admin->has_cap($cap)) {
    //     $role_admin->remove_cap($cap);
    // }
}

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

// Redirection après paiement
add_action('woocommerce_thankyou', function($order_id) {
    if (!$order_id) return;
    // Avant la redirection, marquer les request_id liés comme confirmés
    if (function_exists('visa_mark_request_confirmed')) {
        visa_mark_request_confirmed($order_id);
    }

    $redirect_url = home_url('/merci-paiement/');
    wp_safe_redirect($redirect_url);
    exit; // Obligatoire pour interrompre le flux
});

/**
 * Marque les visa_request attachés à une commande comme confirmés/paid.
 * Utiliser depuis plusieurs hooks WooCommerce afin de couvrir différents
 * scénarios de paiement (payment_complete, processing, thankyou...).
 */
function visa_mark_request_confirmed($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    foreach ($order->get_items() as $item) {
        $request_id = $item->get_meta('request_id');
        if (!$request_id) continue;

        update_post_meta($request_id, 'visa_confirmed', '1');
        update_post_meta($request_id, 'visa_paid_order_id', $order_id);
    }
}

// Appel lors du paiement effectif
add_action('woocommerce_payment_complete', function($order_id) {
    visa_mark_request_confirmed($order_id);
});

// Appel si la commande passe en 'processing' (ex: paiement CB accepté)
add_action('woocommerce_order_status_completed', function($order_id) {
    
    // Lancement du premier analyse
    $n8n_url = 'https://n8n.joel-stephanas.com/webhook/6bbfa4ef-119c-42a9-974c-a82b2aee5a49';
    $order = wc_get_order($order_id);
    if (!$order) return;

    foreach ($order->get_items() as $item) {
        $request_id = $item->get_meta('request_id');
        $response = wp_remote_post($n8n_url, [
            'body'    => wp_json_encode([
                'post_id' => $request_id
            ]),
            'headers' => ['Content-Type' => 'application/json'],
        ]);
    }
});

add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
    if ($order->has_status('pending')) {
        // Vérifier que c'est une commande de visa
        $is_visa_order = false;
        foreach ($order->get_items() as $item) {
            if ($item->get_meta('request_id')) {
                $is_visa_order = true;
                
                break;
            }
        }

        if ($is_visa_order) {
            visa_send_custom_pending_email($order);
        }
    }
}, 10, 3);

/*
// Envoi du mail quand la commande passe en "terminé"
add_action('woocommerce_order_status_completed', function($order_id) {
    error_log("Hook 'woocommerce_order_status_completed' déclenché pour la commande {$order_id}");
    $order = wc_get_order($order_id);
    if (!$order) {
        error_log("Pas de commande trouvée pour ID {$order_id}");
        return;
    }
    if (!$order) return;

    // Vérifie si déjà envoyé
    //if (get_post_meta($order_id, '_visa_email_sent', true)) return;

    foreach ($order->get_items() as $item) {
        $request_id = $item->get_meta('request_id');
        if (!$request_id) continue;

        $visa_objet = get_post_meta($request_id, 'visa_objet', true);
        $group = get_post_meta($request_id, 'visa_group', true);
        $docs_list = get_post_meta($request_id, 'visa_doc_requis', true);

        $message = "
            <table width='100%' cellpadding='0' cellspacing='0'>
                <tr><td>
                <p>Bonjour,</p>
                <p>VISA LOGISTICS a bien reçu votre paiement et vous en remercie.</p>
                <p><strong>Votre demande de visa a fait l’objet d’une analyse et d'une expertise approfondies.</strong></p>
                <p style='text-align: justify;'>Afin de pouvoir lui donner suite, nous vous prions de bien vouloir nous transmettre, par retour de courriel, une copie des documents listés ci-dessous. Nous vous remercions de les enregistrer avant envoi sous une extension correspondant clairement à leur nature (par exemple : « passeport », « photographie d’identité », « assurance », etc.).</p>
                <p>Nous attirons votre attention sur le fait que l’absence de l’un ou de plusieurs documents obligatoires est susceptible de compromettre les chances de succès de votre dossier.</p>
                <p><strong>En tout état de cause, la décision finale relève exclusivement de l’autorité consulaire compétente.</strong></p>
                
                <p style='text-decoration: underline;'><strong>Documents obligatoires - uniquement une copie</strong> :</p>
                <div>{$docs_list}</div>

                <p style='text-decoration: underline;'><strong>Documents facultatifs </strong>(non obligatoires) :</p>
                <p style='text-align: justify;'>Afin de renforcer votre demande, vous avez la possibilité, si vous le souhaitez, de fournir,<span style='text-decoration: underline;'> selon votre situation</span>, tout ou partie des justificatifs ci-après.</p>
                <p style='text-align: justify;'>Ces documents, bien que facultatifs, visent exclusivement à réduire les risques potentiels de rejet liés à l’appréciation du risque migratoire.</p>

                <p style='text-decoration: underline;'><strong>Documents facultatifs non obligatoires – uniquement une copie</strong> :</p>
                <ul>
                <li>Contrat de travail en cours</li>
                <li>Attestation de congé temporaire signée par l’employeur</li>
                <li>Fiches de paie récentes (3 à 6 mois)</li>
                <li>Relevés bancaires indiquant des versements réguliers</li>
                <li>Preuve de propriété</li>
                <li>Contrat de location longue durée à votre nom</li>
                <li>Attestation de scolarité pour vous ou vos enfants (si applicable)</li>
                <li>Justificatif d’inscription universitaire (si étudiant dans le pays d’origine)</li>
                <li>Lettre de mission professionnelle (déplacement temporaire uniquement)</li>
                <li>Preuve de responsabilités familiales (enfants, parents âgés à charge)</li>
                <li>Attestation de mariage ou de lien familial avec des personnes restées au pays</li>
                <li>Preuve d’activités économiques (registre du commerce, patente, déclaration fiscale)</li>
                <li>Attestation bancaire de placements à long terme</li>
                <li>Engagement sur l’honneur de retour signé</li>
                <li>Réservation de billet retour (avec justification du programme de séjour)</li>
                <li>Preuve de participation à un programme officiel temporaire (stage, formation, etc.)</li>
                <li>Lettre de l’organisme d’accueil précisant la durée strictement limitée du séjour</li>
                <li>Preuve de précédente conformité aux visas accordés (sortie dans les délais)</li>
                <li>Casier judiciaire vierge (pour rassurer sur la régularité du comportement)</li>
                </ul>

                <p><em>Ces documents sont <strong>facultatifs</strong> et destinés uniquement à réduire les motifs potentiels de rejet liés au risque migratoire.</em></p>

                <p><strong>
                Les éléments devront être transmis dans un délai de <span style='text-decoration: underline;'>5 jours ouvrés</span>, 
                sous peine d’annulation de la demande.</strong>
                </p>
                <p>Pour faciliter le traitement, merci de ne pas modifier l’objet figurant sur ce courriel lors de l’envoi de vos documents. Il est conseillé de répondre directement à ce message :<br>
                <strong>Documents pour la demande de visa #{$request_id}</strong></p>";
                
                if ($group) {
                    $message .= "<p>Pour une demande groupée numéro <strong>{$group}</strong>.</p>";
                }
            
                $message .= "<p>Bien cordialement,<br>L’équipe VISA LOGISTICS</p>
                </td></tr>
            </table>";

        $sent = wp_mail(
            $order->get_billing_email(),
            "Votre demande de visa #{$request_id} - Paiement confirmé",
            $message,
            ['Content-Type: text/html; charset=UTF-8']
        );

        if ($sent) {
            update_post_meta($order_id, '_visa_email_sent', 'yes');
            error_log("Email visa envoyé à " . $order->get_billing_email());
        } else {
            error_log("Échec d'envoi pour " . $order->get_billing_email());
        }

        // Marquer la demande comme confirmée / payée afin d'empêcher
        // l'accès ultérieur au formulaire via le même request_id.
        try {
            update_post_meta($request_id, 'visa_confirmed', '1');
            update_post_meta($request_id, 'visa_paid_order_id', $order_id);
        } catch (\Exception $e) {
            error_log('Impossible de marquer la demande comme confirmée: ' . $e->getMessage());
        }
    }
});
*/

add_action('woocommerce_order_status_completed', function($order_id) {

    error_log("Hook woocommerce_order_status_completed déclenché pour la commande #{$order_id}");

    $order = wc_get_order($order_id);
    if (!$order) {
        error_log("Commande introuvable pour ID {$order_id}");
        return;
    }

    // Parcourt chaque produit de la commande
    foreach ($order->get_items() as $item) {

        // Récupère l'ID de la demande de visa liée à ce produit
        $request_id = $item->get_meta('request_id');
        if (!$request_id) {
            error_log("Aucun request_id trouvé pour un item de la commande #{$order_id}");
            continue;
        }

        // Vérifie si l'email pour cette demande est déjà envoyé
        if (get_post_meta($request_id, '_visa_email_sent', true)) {
            error_log("Email déjà envoyé pour la demande #{$request_id}, on ignore.");
            continue;
        }

        // Récupération des infos de la demande
        $visa_mail  = get_post_meta($request_id, 'level1_email', true);
        $docs_list  = get_post_meta($request_id, 'visa_doc_requis', true);
        $group      = get_post_meta($request_id, 'visa_group', true);
        $prenom = get_post_meta($request_id, 'visa_prenom', true);
        $nom    = get_post_meta($request_id, 'visa_full_name', true);

        // Construction du nom complet proprement
        $full_name = trim($prenom . ' ' . $nom);

        if (!$visa_mail) {
            error_log("Aucune adresse mail level1_email pour la demande #{$request_id}");
            continue;
        }

        // Construction du message email HTML
        $message = "
            <table width='100%' cellpadding='0' cellspacing='0'>
                <tr><td>
                <p>Bonjour {$full_name},</p>
                <p>VISA LOGISTICS a bien reçu votre paiement et vous en remercie.</p>
                <p><strong>Votre demande de visa a fait l’objet d’une analyse et d'une expertise approfondies.</strong></p>
                <p style='text-align: justify;'>Afin de pouvoir lui donner suite, nous vous prions de bien vouloir nous transmettre, par retour de courriel, une copie des documents listés, en français ou traduits, ci-dessous. Nous vous remercions de les enregistrer avant envoi sous une extension correspondant clairement à leur nature (par exemple : « passeport », « photographie d’identité », « assurance », etc.).</p>
                <p>Nous attirons votre attention sur le fait que l’absence de l’un ou de plusieurs documents obligatoires est susceptible de compromettre les chances de succès de votre dossier.</p>
                <p><strong>En tout état de cause, la décision finale relève exclusivement de l’autorité consulaire compétente.</strong></p>
                
                <p style='text-decoration: underline;'><strong>Documents obligatoires - uniquement une copie</strong> :</p>
                <div>{$docs_list}</div>

                <p style='text-decoration: underline;'><strong>Documents complémentaires</strong> :</p>
                <p style='text-align: justify;'>Afin de renforcer votre demande, vous avez la possibilité, de fournir,<span style='text-decoration: underline;'> <strong><u>selon votre situation</u></strong></span>, tout ou partie des justificatifs ci-après :</p>
                <p style='text-align: justify;'>Ces documents, visent à préciser votre profil et à réduire les risques potentiels de rejet liés à l’appréciation du risque migratoire.</p>

                <p style='text-decoration: underline;'><strong>Documents complémentaires : uniquement une copie</strong> :</p>
                <ul>
                <li>Contrat de travail en cours</li>
                <li>Attestation de congé temporaire signée par l’employeur</li>
                <li>Fiches de paie récentes (3 à 6 mois)</li>
                <li>Relevés bancaires indiquant des versements réguliers</li>
                <li>Preuve de propriété</li>
                <li>Contrat de location longue durée à votre nom</li>
                <li>Attestation de scolarité pour vous ou vos enfants (si applicable)</li>
                <li>Justificatif d’inscription universitaire (si étudiant dans le pays d’origine)</li>
                <li>Lettre de mission professionnelle (déplacement temporaire uniquement)</li>
                <li>Preuve de responsabilités familiales (enfants, parents âgés à charge)</li>
                <li>Attestation de mariage ou de lien familial avec des personnes restées au pays</li>
                <li>Preuve d’activités économiques (registre du commerce, patente, déclaration fiscale)</li>
                <li>Attestation bancaire de placements à long terme</li>
                <li>Engagement sur l’honneur de retour signé</li>
                <li>Réservation de billet retour (avec justification du programme de séjour)</li>
                <li>Preuve de participation à un programme officiel temporaire (stage, formation, etc.)</li>
                <li>Lettre de l’organisme d’accueil précisant la durée strictement limitée du séjour</li>
                <li>Preuve de précédente conformité aux visas accordés (sortie dans les délais)</li>
                <li>Casier judiciaire vierge (pour rassurer sur la régularité du comportement)</li>
                </ul>

                <p><strong>
                Les éléments devront être transmis dans un délai de <span style='text-decoration: underline;'>5 jours ouvrés</span>, 
                sous peine d’annulation de la demande.</strong>
                </p>
                <p><strong>CLIQUER SUR RÉPONDRE ET JOIGNEZ VOS DOCUMENTS.</strong></p>
                <p>Pour faciliter le traitement, merci de ne pas modifier l’objet figurant sur ce courriel lors de l’envoi de vos documents :<br>
                <strong>Documents pour la demande de visa #{$request_id}</strong></p>";
                
                if ($group) {
                    $message .= "<p>Pour une demande groupée numéro <strong>#{$group}</strong>.</p>";
                }
            
                $message .= "<p>Bien cordialement,<br>L’équipe VISA LOGISTICS</p>
                </td></tr>
            </table>
        ";

        // Envoi de l'email au destinataire spécifique
        $sent = wp_mail(
            $visa_mail,
            "Votre demande de visa #{$request_id} - Paiement confirmé",
            $message,
            ['Content-Type: text/html; charset=UTF-8']
        );

        // Traitement du résultat
        if ($sent) {
            update_post_meta($request_id, '_visa_email_sent', 'yes');
            error_log("Email envoyé pour la demande #{$request_id} à {$visa_mail}");
        } else {
            error_log("ÉCHEC d'envoi pour la demande #{$request_id} ({$visa_mail})");
        }

        // Marquage de la demande comme confirmée et payée
        try {
            update_post_meta($request_id, 'visa_confirmed', '1');
            update_post_meta($request_id, 'visa_paid_order_id', $order_id);
        } catch (\Exception $e) {
            error_log("Erreur lors du marquage de la demande #{$request_id} : " . $e->getMessage());
        }
    }
});
/*
function visa_send_custom_pending_email($order) {
    $request_id = null;
    foreach ($order->get_items() as $item) {
        $request_id = $item->get_meta('request_id');
        if ($request_id) break;
    }

    if (!$request_id) return;

    // Empêcher l'envoi en double
    if (get_post_meta($order->get_id(), '_visa_pending_email_sent', true)) {
        return;
    }
    update_post_meta($order->get_id(), '_visa_pending_email_sent', 'yes');
    
    // Récupérer les comptes bancaires depuis l'option WooCommerce (stockée séparément)
    $bacs_instructions = '';
    $accounts = get_option('woocommerce_bacs_accounts', []);

    if (!empty($accounts) && is_array($accounts)) {
        $first_account = reset($accounts);

        $bacs_instructions = '
            <ul style="list-style: none; padding-left: 0; margin: 10px 0;">
                <li>• <strong>Nom du compte :</strong> ' . esc_html($first_account['account_name'] ?? '') . '</li>
                <li>• <strong>Numéro du compte :</strong> ' . esc_html($first_account['account_number'] ?? '') . '</li>
                <li>• <strong>Nom de la banque :</strong> ' . esc_html($first_account['bank_name'] ?? '') . '</li>
                <li>• <strong>Code guichet :</strong> ' . esc_html($first_account['sort_code'] ?? '') . '</li>
                <li>• <strong>IBAN :</strong> ' . esc_html($first_account['iban'] ?? '') . '</li>
                <li>• <strong>BIC / SWIFT :</strong> ' . esc_html($first_account['bic'] ?? '') . '</li>
            </ul>
        ';
    }

    if (empty($bacs_instructions)) {
        $bacs_instructions = '<em>Aucun compte bancaire configuré. Veuillez vérifier les paramètres de paiement "Virement bancaire" dans WooCommerce.</em>';
    }

    $customer_name = $order->get_billing_first_name();
    $order_date = wc_format_datetime($order->get_date_created(), 'd F Y');
    $order_number = $order->get_order_number();
    $total = wc_price($order->get_total(), ['currency' => $order->get_currency()]);

    $subject = "Merci pour votre demande de visa – Visa Logistics";

    $message = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 700px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f9f9f9;
                }
                .container {
                    background: #ffffff;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                    border: 1px solid #eee;
                }
                .header {
                    font-size: 22px;
                    font-weight: bold;
                    color: #004680;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #004680;
                }
                .highlight {
                    color: #d32f2f;
                    font-weight: bold;
                }
                .section {
                    margin: 25px 0;
                }
                .section h3 {
                    margin: 15px 0 10px;
                    color: #004680;
                    font-size: 16px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                    font-size: 14px;
                }
                th, td {
                    padding: 10px;
                    text-align: left;
                    border-bottom: 1px solid #eee;
                }
                th {
                    background-color: #f5f9ff;
                    color: #004680;
                }
                .footer {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #eee;
                    color: #666;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>Visa Logistics</div>

                <p>Merci pour votre demande de visa</p>

                <p>Bonjour <strong>" . esc_html($customer_name) . "</strong>,<br>
                Nous avons bien reçu votre demande de visa, Elle est actuellement <span class='highlight'>en attente jusqu’à confirmation de votre paiement.</span>.<br>
                Vous disposez de <strong>02 (DEUX) jours ouvrés</strong> pour procéder au paiement, <strong>sous peine d’annulation définitive</strong> de votre demande.</p>
                <p><strong>Nous vous rappelons que le paiement s’effectue exclusivement sous forme de « TRANSFERT INSTANTANE » depuis un compte bancaire.</strong></p>

                <div class='section'>
                    <h3>Nos Coordonnées bancaires</h3>
                    <h4>Compte pour paiement:</h4>
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        " . $bacs_instructions . "
                    </div>
                </div>

                <div class='section'>
                    <h3>Résumé de la demande de visa</h3>
                    <p><strong>Demande de visa n°" . esc_html($order_number) . "</strong> (" . esc_html($order_date) . ")</p>
                    <table>
                        <tr>
                            <th>Produit</th>
                            <th>request_id</th>
                            <th>Type</th>
                            <th>Montant</th>
                        </tr>
                        <tr>
                            <td>Demande de Visa</td>
                            <td>" . esc_html($request_id) . "</td>
                            <td>visa</td>
                            <td>" . wp_kses_post($total) . "</td>
                        </tr>
                    </table>
                    <p><strong>Sous-total :</strong> " . wp_kses_post($total) . "<br>
                    <strong>Total :</strong> " . wp_kses_post($total) . "<br>
                    <strong>Moyen de paiement :</strong> Virement bancaire</p>
                </div>

                <div class='section'>
                    <h3>Adresse de facturation</h3>
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        " . $order->get_formatted_billing_address() . "
                    </div>
                </div>

                <div class='footer'>
                    <p>Bien cordialement,<br>
                    L’équipe Visa Logistics</p>
                </div>
            </div>
        </body>
        </html>";

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    wp_mail($order->get_billing_email(), $subject, $message, $headers);
}
*/
function visa_send_custom_pending_email($order) {

    foreach ($order->get_items() as $item) {

        // Récupération request_id
        $request_id = $item->get_meta('request_id');
        if (!$request_id) continue;

        // Empêche doublons : 1 email par demande
        if (get_post_meta($request_id, '_visa_pending_email_sent', true)) {
            continue;
        }

        update_post_meta($request_id, '_visa_pending_email_sent', 'yes');

        // Email de la demande
        $visa_mail = get_post_meta($request_id, 'level1_email', true);
        if (!$visa_mail) {
            error_log("Aucun email level1_email pour la demande #{$request_id}");
            continue;
        }

        // Récupérer le premier compte bancaire WooCommerce BACS
        $bacs_instructions = '';
        $accounts = get_option('woocommerce_bacs_accounts', []);

        if (!empty($accounts) && is_array($accounts)) {
            $acc = reset($accounts);

            $bacs_instructions = '
                <ul style="list-style:none;padding-left:0;margin:10px 0;">
                    <li>• <strong>Nom du compte :</strong> ' . esc_html($acc['account_name'] ?? '') . '</li>
                    <li>• <strong>Numéro du compte :</strong> ' . esc_html($acc['account_number'] ?? '') . '</li>
                    <li>• <strong>Nom de la banque :</strong> ' . esc_html($acc['bank_name'] ?? '') . '</li>
                    <li>• <strong>Code guichet :</strong> ' . esc_html($acc['sort_code'] ?? '') . '</li>
                    <li>• <strong>IBAN :</strong> ' . esc_html($acc['iban'] ?? '') . '</li>
                    <li>• <strong>BIC / SWIFT :</strong> ' . esc_html($acc['bic'] ?? '') . '</li>
                </ul>
            ';
        }

        if (!$bacs_instructions) {
            $bacs_instructions = '<em>Aucun compte bancaire configuré dans WooCommerce.</em>';
        }

        // Données commande
        $customer_name = get_post_meta($request_id, 'level1_nom', true)
            ?: $order->get_billing_first_name();

        $order_date   = wc_format_datetime($order->get_date_created(), 'd F Y');
        $order_number = $order->get_order_number();
        $total = wc_price($order->get_total(), ['currency' => $order->get_currency()]);

        // Sujet
        $subject = "Merci pour votre commande – Visa Logistics";

        // Email HTML
        ob_start(); ?>
        <html>
        <head><meta charset="UTF-8"></head>
        <body style="font-family:Arial,sans-serif;">
            <div style="max-width:700px;margin:auto;background:white;padding:20px;border:1px solid #eee;border-radius:8px;">
                <h2 style="color:#004680;">Visa Logistics</h2>

                <p>Bonjour <strong><?= esc_html($customer_name) ?></strong>,</p>

                <p>Nous avons bien reçu votre commande, Elle est actuellement <span class='highlight'>en attente jusqu’à confirmation de votre paiement.</span>.<br>
                Vous disposez de <strong>02 (DEUX) jours ouvrés</strong> pour procéder au paiement, <strong>sous peine d’annulation définitive</strong> de votre demande.</p>
                <p><strong>Nous vous rappelons que le paiement s’effectue exclusivement sous forme de « TRANSFERT INSTANTANE » depuis un compte bancaire.</strong></p>

                <h3 style="color:#004680;">Coordonnées bancaires</h3>
                <?= $bacs_instructions ?>

                <h3 style="color:#004680;">Résumé de votre demande</h3>
                <table width="100%" style="border-collapse:collapse;margin-top:10px;">
                    <tr>
                        <th style="background:#f5f9ff;padding:10px;">Produit</th>
                        <th style="background:#f5f9ff;padding:10px;">request_id</th>
                        <th style="background:#f5f9ff;padding:10px;">Montant</th>
                    </tr>
                    <tr>
                        <td style="padding:10px;">Demande de Visa</td>
                        <td style="padding:10px;"><?= esc_html($request_id) ?></td>
                        <td style="padding:10px;"><?= wp_kses_post($total) ?></td>
                    </tr>
                </table>

                <p style="margin-top:20px;">Merci de votre confiance.</p>

                <p>Cordialement,<br>L'équipe Visa Logistics</p>
            </div>
        </body>
        </html>
        <?php
        $message = ob_get_clean();

        // Envoi du mail
        wp_mail(
            $visa_mail,
            $subject,
            $message,
            ['Content-Type: text/html; charset=UTF-8']
        );

        error_log("Email pending envoyé pour la demande #{$request_id} à {$visa_mail}");
    }
}

add_filter('woocommerce_checkout_fields', 'custom_checkout_fields');
function custom_checkout_fields($fields) {
    // Facturation : vider toutes les valeurs par défaut
    foreach ($fields['billing'] as $key => $field) {
        $fields['billing'][$key]['default'] = '';
    }
    return $fields;
}

add_filter('woocommerce_checkout_get_value', '__return_empty_string');

add_action('init', function() {
    if (isset($_GET['debug_product']) && current_user_can('manage_options')) {
        $pid = get_option('visa_prod_demande-de-visa_id');
        if ($pid) {
            $prod = wc_get_product($pid);
            if ($prod) {
                echo '<pre>';
                print_r([
                    'ID' => $prod->get_id(),
                    'virtual' => $prod->get_virtual(),
                    'downloadable' => $prod->get_downloadable(),
                    'price' => $prod->get_price()
                ]);
                echo '</pre>';
            } else {
                echo 'Produit non trouvé';
            }
        }
        exit;
    }
});

add_filter( 'woocommerce_states', 'custom_algeria_states' );

function custom_algeria_states( $states ) {
    $states['DZ'] = array(
        '01' => 'Adrar',
        '02' => 'Chlef',
        '03' => 'Laghouat',
        '04' => 'Oum El Bouaghi',
        '05' => 'Batna',
        '06' => 'Béjaïa',
        '07' => 'Biskra',
        '08' => 'Béchar',
        '09' => 'Blida',
        '10' => 'Bouira',
        '11' => 'Tamanrasset',
        '12' => 'Tébessa',
        '13' => 'Tlemcen',
        '14' => 'Tiaret',
        '15' => 'Tizi Ouzou',
        '16' => 'Alger',
        '17' => 'Djelfa',
        '18' => 'Jijel',
        '19' => 'Sétif',
        '20' => 'Saïda',
        '21' => 'Skikda',
        '22' => 'Sidi Bel Abbès',
        '23' => 'Annaba',
        '24' => 'Guelma',
        '25' => 'Constantine',
        '26' => 'Médéa',
        '27' => 'Mostaganem',
        '28' => 'M’Sila',
        '29' => 'Mascara',
        '30' => 'Ouargla',
        '31' => 'Oran',
        '32' => 'El Bayadh',
        '33' => 'Illizi',
        '34' => 'Bordj Bou Arreridj',
        '35' => 'Boumerdès',
        '36' => 'El Tarf',
        '37' => 'Tindouf',
        '38' => 'Tissemsilt',
        '39' => 'El Oued',
        '40' => 'Khenchela',
        '41' => 'Souk Ahras',
        '42' => 'Tipaza',
        '43' => 'Mila',
        '44' => 'Aïn Defla',
        '45' => 'Naâma',
        '46' => 'Aïn Témouchent',
        '47' => 'Ghardaïa',
        '48' => 'Relizane',
        '49' => 'Timimoun',
        '50' => 'Bordj Badji Mokhtar',
        '51' => 'Ouled Djellal',
        '52' => 'Béni Abbès',
        '53' => 'In Salah',
        '54' => 'In Guezzam',
        '55' => 'Touggourt',
        '56' => 'Djanet',
        '57' => 'El M’Ghair',
        '58' => 'El Menia',
        '59' => 'Aflou',
        '60' => 'El Abiodh Sidi Cheikh',
        '61' => 'El Aricha',
        '62' => 'El Kantara',
        '63' => 'Barika',
        '64' => 'Bou Saâda',
        '65' => 'Bir El Ater',
        '66' => 'Ksar El Boukhari',
        '67' => 'Ksar Chellala',
        '68' => 'Aïn Oussara',
        '69' => 'Messaad',
    );
    return $states;
}