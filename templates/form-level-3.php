<?php
// templates/form-level-3.php

defined('ABSPATH') || exit;

$visa_group = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

if ($visa_group) {
    $existing_posts = get_posts([
        'post_type'  => 'visa_request',
        'post_status'=> 'any',
        'meta_key'   => 'visa_group',
        'meta_value' => $visa_group,
        'fields'     => 'ids',
        'numberposts'=> 1,
    ]);
    
    $existing_id = !empty($existing_posts) ? $existing_posts[0] : null;
    
    if ($existing_id) {
        $saved_type     = get_post_meta($existing_id, 'visa_type', true);
        $saved_depot    = get_post_meta($existing_id, 'visa_depot', true);
        $saved_wilaya   = get_post_meta($existing_id, 'visa_wilaya', true);
        $saved_ville    = get_post_meta($existing_id, 'visa_ville', true);
        $saved_email    = get_post_meta($existing_id, 'level1_email', true);
        $saved_info_objet_base = get_post_meta($existing_id, 'visa_info_objet_base', true);
        
        $uuid = uniqid('visa_', true);
        $request_time = current_time('mysql');
 
        $request_id = wp_insert_post([
            'post_type'   => 'visa_request',
            'post_status' => 'draft',
            'post_title'  => 'Demande ' . $uuid,
            'meta_input'  => [
                'level1_email'     => $saved_email,
                'visa_uuid'        => $uuid,
                'visa_request_time'=> $request_time,
                'visa_info_objet_base' => $saved_info_objet_base,
                'visa_type' => $saved_type,
                'visa_depot' => $saved_depot,
                'visa_wilaya' => $saved_wilaya,
                'visa_ville' => $saved_ville,
                'visa_group' => $visa_group,
            ],
        ]);
    }
} else {
    $request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
}

$visa_type = get_post_meta($request_id, 'visa_type', true);

// Si visa_type n'existe pas, mettre une valeur par défaut
if (empty($visa_type)) {
    $visa_type = 'court_sejour'; // Défaut: court séjour
    // Sauvegarder cette valeur pour les futurs accès
    update_post_meta($request_id, 'visa_type', $visa_type);
}

$confirmed = get_post_meta($request_id, 'visa_confirmed', true);
$if_block = '';

// DEBUG
error_log('=== FORM-LEVEL-3 PAGE LOAD ===');
error_log('request_id: ' . $request_id);
error_log('visa_type: ' . ($visa_type ?: 'VIDE/MANQUANT'));
error_log('confirmed: ' . ($confirmed ?: 'non'));

if ($confirmed) {
	echo '<div class="visa-already-processed" style="padding:1.2em;border:1px solid #e0e0e0;background:#fffdf6;max-width:720px;">';
	echo '<h3 style="margin-top:0;color:#333;">Demande déjà traitée</h3>';
	echo '<p>Cette demande a déjà été traitée et le paiement a été confirmé. Pour soumettre une nouvelle demande, merci de créer un nouveau formulaire avec un identifiant différent.</p>';
	echo '<p><a class="button" href="' . esc_url(home_url('/demande-de-visa/')) . '">Créer une nouvelle demande</a></p>';
	echo '</div>';
	return;
}
$ia_info_objet = get_post_meta($request_id, 'visa_info_objet', true);
$info_objet_to_display = !empty($ia_info_objet) ? $ia_info_objet : get_post_meta($request_id, 'visa_info_objet_base', true);
$mail = get_post_meta($request_id, 'level1_email', true);

// Charger les valeurs déjà enregistrées
$saved_full_name     = get_post_meta($request_id, 'visa_full_name', true);
$saved_prenom    = get_post_meta($request_id, 'visa_prenom', true);
$saved_birth_date   = get_post_meta($request_id, 'visa_birth_date', true);
$saved_lieu_naiss    = get_post_meta($request_id, 'visa_lieu_naiss', true);
$saved_num_document    = get_post_meta($request_id, 'visa_num_document', true);
$saved_date_delivrance    = get_post_meta($request_id, 'visa_date_delivrance', true);
$saved_date_expiration    = get_post_meta($request_id, 'visa_date_expiration', true);
$saved_visa_delivre_par    = get_post_meta($request_id, 'visa_delivre_par', true);
$saved_adresse = get_post_meta($request_id, 'visa_adresse', true);
$saved_sexe = get_post_meta($request_id, 'visa_sexe', true);
$saved_etat_civil = get_post_meta($request_id, 'visa_etat_civil', true);

$saved_phone = get_post_meta($request_id, 'visa_phone', true);
$saved_profession = get_post_meta($request_id, 'visa_profession', true);

// Tuteur légal n°1
$saved_nom_tuteur_legal_1 = get_post_meta($request_id, 'visa_nom_tuteur_legal_1', true);
$saved_prenom_tuteur_legal_1 = get_post_meta($request_id, 'visa_prenom_tuteur_legal_1', true);
$saved_adresse_tuteur_legal_1 = get_post_meta($request_id, 'visa_adresse_tuteur_legal_1', true);
$saved_code_postal_tuteur_legal_1 = get_post_meta($request_id, 'visa_code_postal_tuteur_legal_1', true);
$saved_ville_tuteur_legal_1 = get_post_meta($request_id, 'visa_ville_tuteur_legal_1', true);
$saved_pays_tuteur_legal_1 = get_post_meta($request_id, 'visa_pays_tuteur_legal_1', true);
$saved_telephone_tuteur_legal_1 = get_post_meta($request_id, 'visa_telephone_tuteur_legal_1', true);
$saved_email_tuteur_legal_1 = get_post_meta($request_id, 'visa_email_tuteur_legal_1', true);
$saved_nationalite_tuteur_legal_1 = get_post_meta($request_id, 'visa_nationalite_tuteur_legal_1', true);
$saved_statut_tuteur_legal_1 = get_post_meta($request_id, 'visa_statut_tuteur_legal_1', true);
$saved_statut_tuteur_legal_2 = get_post_meta($request_id, 'visa_statut_tuteur_legal_2', true);


// Tuteur légal n°2
$saved_nom_tuteur_legal_2 = get_post_meta($request_id, 'visa_nom_tuteur_legal_2', true);
$saved_prenom_tuteur_legal_2 = get_post_meta($request_id, 'visa_prenom_tuteur_legal_2', true);
$saved_adresse_tuteur_legal_2 = get_post_meta($request_id, 'visa_adresse_tuteur_legal_2', true);
$saved_code_postal_tuteur_legal_2 = get_post_meta($request_id, 'visa_code_postal_tuteur_legal_2', true);
$saved_ville_tuteur_legal_2 = get_post_meta($request_id, 'visa_ville_tuteur_legal_2', true);
$saved_pays_tuteur_legal_2 = get_post_meta($request_id, 'visa_pays_tuteur_legal_2', true);
$saved_telephone_tuteur_legal_2 = get_post_meta($request_id, 'visa_telephone_tuteur_legal_2', true);
$saved_email_tuteur_legal_2 = get_post_meta($request_id, 'visa_email_tuteur_legal_2', true);
$saved_nationalite_tuteur_legal_2 = get_post_meta($request_id, 'visa_nationalite_tuteur_legal_2', true);



// Fallbacks: certains anciens enregistrements peuvent utiliser d'autres clés
if ( empty( $saved_profession ) ) {
    $saved_profession = get_post_meta( $request_id, 'ia_profession', true ) ?: get_post_meta( $request_id, 'profession', true );
}
// Si la meta contient un libellé (ex: 'Agriculteur') convertit en code '65001'
$profession_map = [
    '65001' => 'Agriculteur',
    '65002' => 'Architecte',
    '65003' => 'Artisan',
    '65004' => 'Artiste',
    '65005' => 'Autre',
    '65006' => 'Autre technicien',
    '66001' => 'Banquier',
    '67001' => 'Cadre d\'entreprise',
    '67002' => 'Chauffeur, routier',
    '67003' => 'Chef d\'entreprise',
    '67004' => 'Chercheur, scientifique',
    '67005' => 'Chimiste',
    '67006' => 'Chômeur',
    '67007' => 'Clergé, religieux',
    '67008' => 'Commerçant',
    '68001' => 'Diplomate',
    '69001' => 'Electronicien',
    '69005' => 'Elève, Etudiant, stagiaire',
    '69002' => 'Employé',
    '69003' => 'Employé prive au service de diplomate',
    '69004' => 'Enseignant',
    '70001' => 'Fonctionnaire',
    '72001' => 'Homme politique',
    '73001' => 'Informaticien',
    '74001' => 'Journaliste',
    '77001' => 'Magistrat',
    '77002' => 'Marin',
    '77003' => 'Mode, cosmétique',
    '79001' => 'Ouvrier',
    '80001' => 'Personnel de service, administratif ou technique (postes dipl./cons.)',
    '80002' => 'Policier, militaire',
    '80003' => 'Profession juridique',
    '80004' => 'Profession libérale',
    '80005' => 'Profession médicale et paramédicale',
    '82001' => 'Retraite',
    '83001' => 'Sans profession',
    '83002' => 'Sportif',
];
if ( $saved_profession && ! preg_match('/^\d+$/', $saved_profession) ) {
    $code = array_search( $saved_profession, $profession_map, true );
    if ( $code !== false ) {
        $saved_profession = $code;
    }
}

$saved_secteur_activite = get_post_meta($request_id, 'visa_secteur_activite', true);
// fallback checks
if ( empty( $saved_secteur_activite ) ) {
    $saved_secteur_activite = get_post_meta( $request_id, 'ia_secteur_activite', true ) ?: get_post_meta( $request_id, 'secteur_activite', true );
}
$saved_situation_professionnelle = get_post_meta($request_id, 'visa_situation_professionnelle', true);
$saved_nom_employeur = get_post_meta($request_id, 'visa_nom_employeur', true);
$saved_adresse_employeur = get_post_meta($request_id, 'visa_adresse_employeur', true);
$saved_cp_employeur = get_post_meta($request_id, 'visa_cp_employeur', true);
$saved_ville_employeur = get_post_meta($request_id, 'visa_ville_employeur', true);
$saved_pays_employeur = get_post_meta($request_id, 'visa_pays_employeur', true);
$saved_num_employeur = get_post_meta($request_id, 'visa_num_employeur', true);
$saved_mail_employeur = get_post_meta($request_id, 'visa_mail_employeur', true);
$saved_employeur = get_post_meta($request_id, 'visa_employeur', true);
$saved_etat_membre = get_post_meta($request_id, 'visa_etat_membre', true);
$saved_etat_membre_1er_annee = get_post_meta($request_id, 'visa_etat_membre_1er_annee', true);
$saved_nbr_entre = get_post_meta($request_id, 'visa_nbr_entre', true);
$saved_nombre_voyages_annee = get_post_meta($request_id, 'visa_nombre_voyages_annee', true);
$saved_arrival_date = get_post_meta($request_id, 'visa_arrival_date', true);
$saved_departure_date = get_post_meta($request_id, 'visa_departure_date', true);

$saved_nom_accueil = get_post_meta($request_id, 'visa_nom_accueil', true);
$saved_prenom_accueil = get_post_meta($request_id, 'visa_prenom_accueil', true);
$saved_adresse_accueil = get_post_meta($request_id, 'visa_adresse_accueil', true);
$saved_cp_accueil = get_post_meta($request_id, 'visa_cp_accueil', true);
$saved_ville_accueil = get_post_meta($request_id, 'visa_ville_accueil', true);
$saved_pays_accueil = get_post_meta($request_id, 'visa_pays_accueil', true);
$saved_num_accueil = get_post_meta($request_id, 'visa_num_accueil', true);
$saved_mail_accueil = get_post_meta($request_id, 'visa_mail_accueil', true);
			
$saved_nom_hotel = get_post_meta($request_id, 'visa_nom_hotel', true);
$saved_adresse_hotel = get_post_meta($request_id, 'visa_adresse_hotel', true);
$saved_cp_hotel = get_post_meta($request_id, 'visa_cp_hotel', true);
$saved_ville_hotel = get_post_meta($request_id, 'visa_ville_hotel', true);
$saved_pays_hotel = get_post_meta($request_id, 'visa_pays_hotel', true);
$saved_num_hotel = get_post_meta($request_id, 'visa_num_hotel', true);
$saved_mail_hotel = get_post_meta($request_id, 'visa_mail_hotel', true);
			
$saved_nom_entreprise = get_post_meta($request_id, 'visa_nom_entreprise', true);
$saved_adresse_entreprise = get_post_meta($request_id, 'visa_adresse_entreprise', true);
$saved_cp_entreprise = get_post_meta($request_id, 'visa_cp_entreprise', true);
$saved_ville_entreprise = get_post_meta($request_id, 'visa_ville_entreprise', true);
$saved_pays_entreprise = get_post_meta($request_id, 'visa_pays_entreprise', true);
$saved_mail_entreprise = get_post_meta($request_id, 'visa_mail_entreprise', true);
			
$saved_nom_contact = get_post_meta($request_id, 'visa_nom_contact', true);
$saved_prenom_contact = get_post_meta($request_id, 'visa_prenom_contact', true);
$saved_adresse_contact = get_post_meta($request_id, 'visa_adresse_contact', true);
$saved_cp_contact = get_post_meta($request_id, 'visa_cp_contact', true);
$saved_ville_contact = get_post_meta($request_id, 'visa_ville_contact', true);
$saved_pays_contact = get_post_meta($request_id, 'visa_pays_contact', true);
$saved_num_contact = get_post_meta($request_id, 'visa_num_contact', true);
$saved_mail_contact = get_post_meta($request_id, 'visa_mail_contact', true);

$saved_adresse_inviteur    = get_post_meta($request_id, 'visa_adresse_sejour', true);
$saved_adresse_sejour    = get_post_meta($request_id, 'visa_adresse_sejour', true);
$saved_objet    = get_post_meta($request_id, 'visa_objet', true);
$saved_objet_autre    = get_post_meta($request_id, 'visa_objet_autre', true);
$saved_duree = get_post_meta($request_id, 'visa_duree', true);
$saved_moyens_existence = get_post_meta($request_id, 'visa_moyens_existence', true);

$saved_visa_nationalite  = get_post_meta($request_id, 'visa_nationalite', true );

$birth_val = $saved_birth_date;
if ($birth_val && preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $birth_val, $m)) {
    // Transforme 31/12/2000 → 2000-12-31
    $birth_val = "{$m[3]}-{$m[2]}-{$m[1]}";
}


if (!$request_id || !$visa_type) {
    echo '<p>Demande introuvable ou type de visa non spécifié.</p>';
    return;
}

$shortstay_max = get_option('visa_shortstay_max_days', 90);
$should_redirect = false;
$today = date_i18n('Y-m-d');

$birthDate = new DateTime($saved_birth_date);
$todayDate = new DateTime();
$age = $todayDate->diff($birthDate)->y;

// Fonction pour supprimer les accents
function remove_accents_php($str) {
    if (!is_string($str)) return $str;

    // Remplacement des symboles monétaires
    $str = str_replace('€', ' euro', $str);
    $str = str_replace('$', ' dollar', $str);

    // Suppression des accents via htmlentities
    $str = htmlentities($str, ENT_QUOTES, 'UTF-8');

    // Convertit é è ê à ç ô ö ü î ... → e a c o u i ...
    $str = preg_replace('/&([A-Za-z])(acute|grave|circ|tilde|uml|cedil|ring|slash);/', '$1', $str);

    // Décodage final
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');

    return $str;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visa_level3_submit'])) {
    $request_id = intval( $_POST['request_id'] ?? 0 );
    if ( ! $request_id ) {
        wp_die('ID invalide');
    }

    // Assurer que visa_type est sauvegardé si envoyé
    if ( isset($_POST['visa_type_form']) && !empty($_POST['visa_type_form']) ) {
        update_post_meta( $request_id, 'visa_type', sanitize_text_field($_POST['visa_type_form']) );
    }

    // Sauvegarde explicite du champ "Informations sur l'objet du voyage"
    // Le champ du formulaire est nommé "visa_info_objet_base" (voir plus bas dans le template),
    // mais la boucle générique ne le persistait pas (elle ne sauvegarde que les champs de $fields_map).
    if ( isset( $_POST['visa_info_objet_base'] ) ) {
        update_post_meta(
            $request_id,
            'visa_info_objet_base',
            sanitize_textarea_field( $_POST['visa_info_objet_base'] )
        );
    }

    // 1) Déclare la map champ=>sanitizer
	$fields_map = [
		// Tuteur légal n°1
		'nom_tuteur_legal_1' => 'sanitize_text_field',
		'prenom_tuteur_legal_1' => 'sanitize_text_field',
		'adresse_tuteur_legal_1' => 'sanitize_text_field',
		'code_postal_tuteur_legal_1' => 'sanitize_text_field',
		'ville_tuteur_legal_1' => 'sanitize_text_field',
		'pays_tuteur_legal_1' => 'sanitize_text_field',
		'telephone_tuteur_legal_1' => 'sanitize_text_field',
		'email_tuteur_legal_1' => 'sanitize_email',
		'nationalite_tuteur_legal_1' => 'sanitize_text_field',
		'statut_tuteur_legal_1' => 'sanitize_text_field',

		
		// Tuteur légal n°2
		'nom_tuteur_legal_2' => 'sanitize_text_field',
		'prenom_tuteur_legal_2' => 'sanitize_text_field',
		'adresse_tuteur_legal_2' => 'sanitize_text_field',
		'code_postal_tuteur_legal_2' => 'sanitize_text_field',
		'ville_tuteur_legal_2' => 'sanitize_text_field',
		'pays_tuteur_legal_2' => 'sanitize_text_field',
		'telephone_tuteur_legal_2' => 'sanitize_text_field',
		'email_tuteur_legal_2' => 'sanitize_email',
		'nationalite_tuteur_legal_2' => 'sanitize_text_field',
		'statut_tuteur_legal_2' => 'sanitize_text_field',

		
		// Résidence autre pays
		'residence_autre_pays' => 'sanitize_text_field',
		
		'full_name'                 => 'sanitize_text_field',
		'nom_famille'              => 'sanitize_text_field',
		'prenom'                   => 'sanitize_text_field',
		'birth_date'               => 'sanitize_text_field',
		'lieu_naiss'               => 'sanitize_text_field',
		'pays_naiss'               => 'sanitize_text_field',
		'nationalite'              => 'sanitize_text_field',
		'nationalite_diff'         => 'sanitize_text_field',
		'autres_nationalites'      => 'sanitize_text_field',
		'sexe'                     => 'sanitize_text_field',
		'sexe_autre'               => 'sanitize_text_field',
		'etat_civil'               => 'sanitize_text_field',
		'etat_civil_autre'         => 'sanitize_text_field',
		'autorite_parentale'       => 'sanitize_textarea_field',
		'num_national_identite'    => 'sanitize_text_field',
		'doc_voyage'               => 'sanitize_text_field',
		'doc_voyage_autre'         => 'sanitize_text_field',
		'num_document'             => 'sanitize_text_field',
		'date_delivrance'          => 'sanitize_text_field',
		'date_expiration'          => 'sanitize_text_field',
		'delivre_par'              => 'sanitize_text_field',
		'nom_famille_UE'           => 'sanitize_text_field',
		'prenom_famille'           => 'sanitize_text_field',
		'birth_famille'            => 'sanitize_text_field',
		'nationalite_famille'      => 'sanitize_text_field',
		'num_nationalite_famille'  => 'sanitize_text_field',
		'lien_parente'             => 'sanitize_text_field',
		'lien_parente_autre'       => 'sanitize_text_field',
		'adresse'                  => 'sanitize_text_field',
		'phone'                    => 'sanitize_text_field',
		'resident'                 => 'sanitize_text_field',
		'num_resident'             => 'sanitize_text_field',
		'valid_resident'           => 'sanitize_text_field',
		'info_objet'               => 'sanitize_textarea_field',
		'etat_membre'              => 'sanitize_textarea_field',
		'etat_membre_1er_annee'    => 'sanitize_text_field',
		'nbr_entre'                => 'sanitize_text_field',
		'nombre_voyages_annee'     => 'intval',
		'arrival_date'             => 'sanitize_text_field',
		'departure_date'           => 'sanitize_text_field',
		'empreinte'                => 'sanitize_text_field',
		'empreinte_date'           => 'sanitize_text_field',
		'num_visa'                 => 'sanitize_text_field',
		'autorisation_delivre_par'=> 'sanitize_text_field',
		'autorisation_validite'   => 'sanitize_text_field',
		'autorisation_delivre_au' => 'sanitize_text_field',
		'hotel'                        => 'sanitize_textarea_field',
		'adresse_inviteur'            => 'sanitize_textarea_field',
		'phone_adresse_inviteur'    => 'sanitize_textarea_field',
		'hote'                         => 'sanitize_textarea_field',
		'phone_hote'                => 'sanitize_textarea_field',
		'personne_de_contact'         => 'sanitize_textarea_field',
		'financement'                 => 'sanitize_text_field',
		'demandeur_financement_moyen'        => 'sanitize_text_field',
		'demandeur_financement_moyen_autre'  => 'sanitize_text_field',
		'financement_garant'                 => 'sanitize_text_field',
		'garant_autre_detail'               => 'sanitize_text_field',
		'garant_financement_moyen'          => 'sanitize_text_field',
		'garant_financement_moyen_autre'    => 'sanitize_text_field',
		'remplisseur'                 => 'sanitize_text_field',
		'adresse_remplisseur'        => 'sanitize_textarea_field',
		'num_remplisseur'            => 'sanitize_text_field',
		'mail'                         => 'sanitize_email',
		'num_resident'                => 'sanitize_text_field',
		'autre_date_delivrance'       => 'sanitize_text_field',
		'autre_date_expiration'       => 'sanitize_text_field',
		'profession'                   => 'sanitize_text_field',
		'secteur_activite'            => 'sanitize_text_field',
		'nom_employeur'            => 'sanitize_text_field',
		'cp_employeur'            => 'sanitize_text_field',
		'ville_employeur'            => 'sanitize_text_field',
		'pays_employeur'            => 'sanitize_text_field',
		'num_employeur'            => 'sanitize_text_field',
		'mail_employeur'            => 'sanitize_text_field',
		'adresse_employeur'            => 'sanitize_text_field',
		'situation_professionnelle'   => 'sanitize_text_field',
		'employeur'                   => 'sanitize_textarea_field',
		'objet'                        => 'sanitize_text_field',
		'objet_autre'                 => 'sanitize_text_field',
		'info_employeur'              => 'sanitize_textarea_field',
		'adresse_sejour'              => 'sanitize_text_field',
		'duree'                        => 'sanitize_text_field',
		'moyens_existence'            => 'sanitize_text_field',
		'bourse'                       => 'sanitize_text_field',
		'bourse_detail'               => 'sanitize_textarea_field',
		'prise_en_charge'             => 'sanitize_text_field',
		'info_prise_en_charge'        => 'sanitize_textarea_field',
		'famille_resident'            => 'sanitize_text_field',
		'info_famille_resident'       => 'sanitize_textarea_field',
		'duree_anterieure'            => 'sanitize_text_field',
		'info_duree_anterieure'       => 'sanitize_textarea_field',
		'adresse_duree_anterieure'    => 'sanitize_textarea_field',
		'adresse_adresse'    => 'sanitize_text_field',
		'cp_adresse'    => 'sanitize_text_field',
		'ville_adresse'    => 'sanitize_text_field',
		'pays_adresse'    => 'sanitize_text_field',
		'nom_accueil'    => 'sanitize_text_field',
		'prenom_accueil'    => 'sanitize_text_field',
		'adresse_accueil'    => 'sanitize_text_field',
		'cp_accueil'    => 'sanitize_text_field',
		'ville_accueil'    => 'sanitize_text_field',
		'pays_accueil'    => 'sanitize_text_field',
		'num_accueil'    => 'sanitize_text_field',
		'mail_accueil'    => 'sanitize_text_field',
		'nom_hotel'    => 'sanitize_text_field',
		'adresse_hotel'    => 'sanitize_text_field',
		'cp_hotel'    => 'sanitize_text_field',
		'ville_hotel'    => 'sanitize_text_field',
		'pays_hotel'    => 'sanitize_text_field',
		'num_hotel'    => 'sanitize_text_field',
		'mail_hotel'    => 'sanitize_text_field',
		'nom_entreprise'    => 'sanitize_text_field',
		'adresse_entreprise'    => 'sanitize_text_field',
		'cp_entreprise'    => 'sanitize_text_field',
		'ville_entreprise'    => 'sanitize_text_field',
		'pays_entreprise'    => 'sanitize_text_field',
		'mail_entreprise'    => 'sanitize_text_field',
		'nom_contact'    => 'sanitize_text_field',
		'prenom_contact'    => 'sanitize_text_field',
		'adresse_contact'    => 'sanitize_text_field',
		'cp_contact'    => 'sanitize_text_field',
		'ville_contact'    => 'sanitize_text_field',
		'pays_contact'    => 'sanitize_text_field',
		'num_contact'    => 'sanitize_text_field',
		'mail_contact'    => 'sanitize_text_field',
		'nom_remplisseur'    => 'sanitize_text_field',
		'prenom_remplisseur'    => 'sanitize_text_field',
		'adres_remplisseur'    => 'sanitize_text_field',
		'cp_remplisseur'    => 'sanitize_text_field',
		'ville_remplisseur'    => 'sanitize_text_field',
		'pays_remplisseur'    => 'sanitize_text_field',
		'mail_remplisseur'    => 'sanitize_text_field',
	];

    // 2) Si c’est court séjour, vérifie dates et calcule stay_duration
    $visa_type = get_post_meta( $request_id, 'visa_type', true );
    if ( $visa_type === 'court_sejour' ) {
        $a = sanitize_text_field( $_POST['arrival_date']   ?? '' );
        $b = sanitize_text_field( $_POST['departure_date'] ?? '' );
        if ( $a && $b ) {
            $days = ( strtotime($b) - strtotime($a) ) / DAY_IN_SECONDS;
            $fields_map['stay_duration'] = 'intval';
            $_POST['stay_duration'] = max( 0, (int) $days );
        }
    }

    // 3) Loop et save
    /*
    foreach ( $fields_map as $field => $sanitizer ) {
        if ( isset( $_POST[ $field ] ) ) {
            $value = call_user_func( $sanitizer, $_POST[ $field ] );
            update_post_meta( $request_id, 'visa_' . $field, $value );
        }
    }
    
    foreach ( $fields_map as $field => $sanitizer ) {
        if ( isset( $_POST[ $field ] ) ) {
            $raw = $_POST[ $field ];
    
            if ( is_array( $raw ) ) {
                $value = array_map( 'sanitize_text_field', $raw );
            } else {
                $value = call_user_func( $sanitizer, $raw );
            }
    
            update_post_meta( $request_id, 'visa_' . $field, $value );
        }
    }
    */
    foreach ( $fields_map as $field => $sanitizer ) {
        if ( isset( $_POST[ $field ] ) ) {
            $raw = $_POST[ $field ];
    
            // Détection : ne PAS enlever les accents si le field contient "pays"
            $should_remove_accents = (
                stripos($field, 'pays') === false &&
                stripos($field, 'secteur') === false &&
                stripos($field, 'delivre_par') === false &&
                stripos($field, 'situation_professionnelle') === false
            );
    
            if ( is_array( $raw ) ) {
                $value = array_map(function($v) use ($should_remove_accents) {
                    $v = sanitize_text_field($v);
                    return $should_remove_accents ? remove_accents_php($v) : $v;
                }, $raw);
            } else {
                $value = call_user_func($sanitizer, $raw);
                if ( $should_remove_accents ) {
                    $value = remove_accents_php($value);
                }
            }
    
            update_post_meta( $request_id, 'visa_' . $field, $value );
        }
    }

	// 4) Enregistrement des membres de la famille (données tabulaires)
	$lien_parent         = $_POST['lien_parent'] ?? [];
	$nom_prenom          = $_POST['nom_prenom'] ?? [];
	$date_naissance      = $_POST['date_naissance'] ?? [];
	$nationalite_famille = $_POST['nationalite_famille'] ?? [];

	$members = [];
	for ( $i = 0; $i < count($lien_parent); $i++ ) {
		// sécurisation des données
		$members[] = [
			'lien'       => sanitize_text_field( $lien_parent[$i]         ?? '' ),
			'nom'        => sanitize_text_field( $nom_prenom[$i]          ?? '' ),
			'naissance'  => sanitize_text_field( $date_naissance[$i]      ?? '' ),
			'nationalite'=> sanitize_text_field( $nationalite_famille[$i] ?? '' ),
		];
	}

	// Sauvegarde en JSON compressé dans un seul champ
	update_post_meta( $request_id, 'visa_membres_famille', wp_json_encode( $members ) );

    // Upload des documents
    if (!empty($_FILES['documents']['name'][0])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $uploads = [];

        foreach ($_FILES['documents']['name'] as $i => $name) {
            if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name'     => $_FILES['documents']['name'][$i],
                    'type'     => $_FILES['documents']['type'][$i],
                    'tmp_name' => $_FILES['documents']['tmp_name'][$i],
                    'error'    => $_FILES['documents']['error'][$i],
                    'size'     => $_FILES['documents']['size'][$i],
                ];
                $upload = wp_handle_upload($file, ['test_form' => false]);
                if (!isset($upload['error'])) {
                    $uploads[] = $upload['url'];
                }
            }
        }

        if ($uploads) {
            update_post_meta($request_id, 'visa_documents', $uploads);
        }
    }
    
    // ðÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂ Récupérer les données nécessaires
    $visa_objet = get_post_meta($request_id, 'visa_objet', true);

    $all_meta = get_post_meta($request_id);
    if (!empty($all_meta)) {
        foreach ($all_meta as $key => $values) {
            $all_meta[$key] = (count($values) === 1) ? maybe_unserialize($values[0]) : array_map('maybe_unserialize', $values);
        }
    }

    // ðÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂ Payload corrigé (UN SEUL bloc)
    $payload = [
        'request_id'          => $request_id,
        'visa_type'           => $visa_type,
        'all_meta'            => $all_meta
    ];

    // Envoi au webhook n8n
    $webhook_url = 'https://n8n.joel-stephanas.com/webhook/af6a35e4-99bd-424f-a4ba-310b7c3fe32c';

    $response = wp_remote_post($webhook_url, [
        'timeout'  => 30,
        'headers'  => ['Content-Type' => 'application/json; charset=utf-8'],
        'body'     => wp_json_encode($payload),
    ]);

    if (is_wp_error($response)) {
        error_log('Erreur envoi webhook visa: ' . $response->get_error_message());
        die('Erreur envoi webhook visa: ' . $response->get_error_message());
    }
    
    // Déclenche redirection JS
    $should_redirect = true;
}
?>

<h2>Étape 3 – Informations personnelles</h2>

<form method="post" enctype="multipart/form-data" class="visa-form" autocomplete="off" translate="no">
    <input type="hidden" name="request_id" value="<?php echo esc_attr($request_id); ?>" />
	<input type="hidden" name="max_days" id="max_days" value="<?php echo esc_attr($shortstay_max); ?>" />
	
	
	<?php if ($visa_type === 'court_sejour'): ?>
		<label>1. Nom [nom de famille] :<span class="required">*</span></label><br>
		<input type="text" name="full_name" value="<?php echo esc_attr($saved_full_name); ?>" required><br><br>

		<label>2. Nom à la naissance [nom(s) de famille antérieur(s)] :<span class="required">*</span></label><br>
		<input type="text" name="nom_famille" required><br><br>

		<label>3. Prénom(s) [nom(s) usuel(s)] :<span class="required">*</span></label><br>
		<input type="text" name="prenom" value="<?php echo esc_attr($saved_prenom); ?>" required><br><br>

		<label>4. Date de naissance (jour-mois-année) :  <?php echo esc_attr($saved_birth_date); ?><span class="required">*</span></label><br>
		<input type="date" name="birth_date" id="birth_date" value="<?php echo esc_attr($saved_birth_date); ?>" required><br><br>
		
		


		<label>5. Lieu de naissance :<span class="required">*</span></label><br>
		<input type="text" name="lieu_naiss" value="<?php echo esc_attr($saved_lieu_naiss); ?>" required><br><br>
		
    
		<label>6. Pays de naissance :<span class="required">*</span></label><br>
		<select id="country" name="pays_naiss" required>
			<option value="">-- Sélectionnez un pays --</option>
			<option value="Afghanistan">Afghanistan</option>
			<option value="Afrique du Sud">Afrique du Sud</option>
			<option value="Albanie">Albanie</option>
			<option value="Algérie">Algérie</option>
			<option value="Allemagne">Allemagne</option>
			<option value="Andorre">Andorre</option>
			<option value="Angola">Angola</option>
			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
			<option value="Arabie Saoudite">Arabie Saoudite</option>
			<option value="Argentine">Argentine</option>
			<option value="Arménie">Arménie</option>
			<option value="Australie">Australie</option>
			<option value="Autriche">Autriche</option>
			<option value="Azerbaïdjan">Azerbaïdjan</option>
			<option value="Bahamas">Bahamas</option>
			<option value="Bahreïn">Bahreïn</option>
			<option value="Bangladesh">Bangladesh</option>
			<option value="Barbade">Barbade</option>
			<option value="Belgique">Belgique</option>
			<option value="Belize">Belize</option>
			<option value="Bénin">Bénin</option>
			<option value="Bhoutan">Bhoutan</option>
			<option value="Biélorussie">Biélorussie</option>
			<option value="Birmanie">Birmanie</option>
			<option value="Bolivie">Bolivie</option>
			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
			<option value="Botswana">Botswana</option>
			<option value="Brésil">Brésil</option>
			<option value="Brunei">Brunei</option>
			<option value="Bulgarie">Bulgarie</option>
			<option value="Burkina Faso">Burkina Faso</option>
			<option value="Burundi">Burundi</option>
			<option value="Cabo Verde">Cabo Verde</option>
			<option value="Cambodge">Cambodge</option>
			<option value="Cameroun">Cameroun</option>
			<option value="Canada">Canada</option>
			<option value="République centrafricaine">République centrafricaine</option>
			<option value="Tchad">Tchad</option>
			<option value="Chili">Chili</option>
			<option value="Chine">Chine</option>
			<option value="Chypre">Chypre</option>
			<option value="Colombie">Colombie</option>
			<option value="Comores">Comores</option>
			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
			<option value="Corée du Nord">Corée du Nord</option>
			<option value="Corée du Sud">Corée du Sud</option>
			<option value="Costa Rica">Costa Rica</option>
			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
			<option value="Croatie">Croatie</option>
			<option value="Cuba">Cuba</option>
			<option value="Danemark">Danemark</option>
			<option value="Djibouti">Djibouti</option>
			<option value="Dominique">Dominique</option>
			<option value="République dominicaine">République dominicaine</option>
			<option value="Egypte">Égypte</option>
			<option value="Emirats arabes unis">Émirats arabes unis</option>
			<option value="Equateur">Équateur</option>
			<option value="Erythrée">Érythrée</option>
			<option value="Espagne">Espagne</option>
			<option value="Estonie">Estonie</option>
			<option value="Eswatini">Eswatini</option>
			<option value="Etats-Unis">États-Unis</option>
			<option value="Ethiopie">Éthiopie</option>
			<option value="Fidji">Fidji</option>
			<option value="Finlande">Finlande</option>
			<option value="France">France</option>
			<option value="Gabon">Gabon</option>
			<option value="Gambie">Gambie</option>
			<option value="Géorgie">Géorgie</option>
			<option value="Ghana">Ghana</option>
			<option value="Grèce">Grèce</option>
			<option value="Grenade">Grenade</option>
			<option value="Guatemala">Guatemala</option>
			<option value="Guinée">Guinée</option>
			<option value="Guinée-Bissau">Guinée-Bissau</option>
			<option value="Guinée équatoriale">Guinée équatoriale</option>
			<option value="Guyana">Guyana</option>
			<option value="Haïti">Haïti</option>
			<option value="Honduras">Honduras</option>
			<option value="Hongrie">Hongrie</option>
			<option value="Inde">Inde</option>
			<option value="Indonésie">Indonésie</option>
			<option value="Irak">Irak</option>
			<option value="Iran">Iran</option>
			<option value="Irlande">Irlande</option>
			<option value="Islande">Islande</option>
			<option value="Israël">Israël</option>
			<option value="Italie">Italie</option>
			<option value="Jamaïque">Jamaïque</option>
			<option value="Japon">Japon</option>
			<option value="Jordanie">Jordanie</option>
			<option value="Kazakhstan">Kazakhstan</option>
			<option value="Kenya">Kenya</option>
			<option value="Kirghizistan">Kirghizistan</option>
			<option value="Kiribati">Kiribati</option>
			<option value="Kosovo">Kosovo</option>
			<option value="Koweït">Koweït</option>
			<option value="Laos">Laos</option>
			<option value="Lettonie">Lettonie</option>
			<option value="Liban">Liban</option>
			<option value="Libéria">Libéria</option>
			<option value="Libye">Libye</option>
			<option value="Liechtenstein">Liechtenstein</option>
			<option value="Lituanie">Lituanie</option>
			<option value="Luxembourg">Luxembourg</option>
			<option value="Macédoine du Nord">Macédoine du Nord</option>
			<option value="Madagascar">Madagascar</option>
			<option value="Malaisie">Malaisie</option>
			<option value="Malawi">Malawi</option>
			<option value="Maldives">Maldives</option>
			<option value="Mali">Mali</option>
			<option value="Malte">Malte</option>
			<option value="Maroc">Maroc</option>
			<option value="Marshall">Îles Marshall</option>
			<option value="Maurice">Maurice</option>
			<option value="Mauritanie">Mauritanie</option>
			<option value="Mexique">Mexique</option>
			<option value="Micronésie">Micronésie</option>
			<option value="Moldavie">Moldavie</option>
			<option value="Monaco">Monaco</option>
			<option value="Mongolie">Mongolie</option>
			<option value="Monténégro">Monténégro</option>
			<option value="Mozambique">Mozambique</option>
			<option value="Namibie">Namibie</option>
			<option value="Nauru">Nauru</option>
			<option value="Népal">Népal</option>
			<option value="Nicaragua">Nicaragua</option>
			<option value="Niger">Niger</option>
			<option value="Nigéria">Nigéria</option>
			<option value="Norvège">Norvège</option>
			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
			<option value="Oman">Oman</option>
			<option value="Ouganda">Ouganda</option>
			<option value="Ouzbékistan">Ouzbékistan</option>
			<option value="Pakistan">Pakistan</option>
			<option value="Palaos">Palaos</option>
			<option value="Palestine">Palestine</option>
			<option value="Panama">Panama</option>
			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
			<option value="Paraguay">Paraguay</option>
			<option value="Pays-Bas">Pays-Bas</option>
			<option value="Pérou">Pérou</option>
			<option value="Philippines">Philippines</option>
			<option value="Pologne">Pologne</option>
			<option value="Portugal">Portugal</option>
			<option value="République centrafricaine">République centrafricaine</option>
			<option value="République dominicaine">République dominicaine</option>
			<option value="Roumanie">Roumanie</option>
			<option value="Royaume-Uni">Royaume-Uni</option>
			<option value="Russie">Russie</option>
			<option value="Rwanda">Rwanda</option>
			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
			<option value="Saint-Marin">Saint-Marin</option>
			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
			<option value="Sainte-Lucie">Sainte-Lucie</option>
			<option value="Salvador">Salvador</option>
			<option value="Samoa">Samoa</option>
			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
			<option value="Sénégal">Sénégal</option>
			<option value="Serbie">Serbie</option>
			<option value="Seychelles">Seychelles</option>
			<option value="Sierra Leone">Sierra Leone</option>
			<option value="Singapour">Singapour</option>
			<option value="Slovaquie">Slovaquie</option>
			<option value="Slovénie">Slovénie</option>
			<option value="Somalie">Somalie</option>
			<option value="Soudan">Soudan</option>
			<option value="Soudan du Sud">Soudan du Sud</option>
			<option value="Sri Lanka">Sri Lanka</option>
			<option value="Suède">Suède</option>
			<option value="Suisse">Suisse</option>
			<option value="Suriname">Suriname</option>
			<option value="Syrie">Syrie</option>
			<option value="Tadjikistan">Tadjikistan</option>
			<option value="Tanzanie">Tanzanie</option>
			<option value="Tchad">Tchad</option>
			<option value="Tchécoslovaquie">Tchéquie</option>
			<option value="Thaïlande">Thaïlande</option>
			<option value="Timor-Leste">Timor-Leste</option>
			<option value="Togo">Togo</option>
			<option value="Tonga">Tonga</option>
			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
			<option value="Tunisie">Tunisie</option>
			<option value="Turkménistan">Turkménistan</option>
			<option value="Turquie">Turquie</option>
			<option value="Tuvalu">Tuvalu</option>
			<option value="Ukraine">Ukraine</option>
			<option value="Uruguay">Uruguay</option>
			<option value="Vanuatu">Vanuatu</option>
			<option value="Vatican">Vatican</option>
			<option value="Venezuela">Venezuela</option>
			<option value="Vietnam">Vietnam</option>
			<option value="Yémen">Yémen</option>
			<option value="Zambie">Zambie</option>
			<option value="Zimbabwe">Zimbabwe</option>
		</select><br><br>
	
		<label>7. Nationalité actuelle :<span class="required">*</span></label><br>
		<select name="nationalite" required>
			<option value="">-- Sélectionnez une nationalité --</option>
			<option value="AFG" <?php selected($saved_visa_nationalite, 'AFG'); ?>>Afghane (Afghanistan)</option>
			<option value="ALB" <?php selected($saved_visa_nationalite, 'ALB'); ?>>Albanaise (Albanie)</option>
			<option value="DZA" <?php selected($saved_visa_nationalite, 'DZA'); ?>>Algérienne (Algérie)</option>
			<option value="DEU" <?php selected($saved_visa_nationalite, 'DEU'); ?>>Allemande (Allemagne)</option>
			<option value="USA" <?php selected($saved_visa_nationalite, 'USA'); ?>>Americaine (États-Unis)</option>
			<option value="AND" <?php selected($saved_visa_nationalite, 'AND'); ?>>Andorrane (Andorre)</option>
			<option value="AND" <?php selected($saved_visa_nationalite, 'AND'); ?>>Andorrane (Andorre)</option>
            <option value="AGO" <?php selected($saved_visa_nationalite, 'AGO'); ?>>Angolaise (Angola)</option>
            <option value="ATG" <?php selected($saved_visa_nationalite, 'ATG'); ?>>Antiguaise-et-Barbudienne (Antigua-et-Barbuda)</option>
            <option value="ARG" <?php selected($saved_visa_nationalite, 'ARG'); ?>>Argentine (Argentine)</option>
            <option value="ARM" <?php selected($saved_visa_nationalite, 'ARM'); ?>>Armenienne (Arménie)</option>
            <option value="AUS" <?php selected($saved_visa_nationalite, 'AUS'); ?>>Australienne (Australie)</option>
            <option value="AUT" <?php selected($saved_visa_nationalite, 'AUT'); ?>>Autrichienne (Autriche)</option>
            <option value="AZE" <?php selected($saved_visa_nationalite, 'AZE'); ?>>Azerbaïdjanaise (Azerbaïdjan)</option>
            <option value="BHS" <?php selected($saved_visa_nationalite, 'BHS'); ?>>Bahamienne (Bahamas)</option>
            <option value="BHR" <?php selected($saved_visa_nationalite, 'BHR'); ?>>Bahreinienne (Bahreïn)</option>
            <option value="BGD" <?php selected($saved_visa_nationalite, 'BGD'); ?>>Bangladaise (Bangladesh)</option>
            <option value="BRB" <?php selected($saved_visa_nationalite, 'BRB'); ?>>Barbadienne (Barbade)</option>
            <option value="BEL" <?php selected($saved_visa_nationalite, 'BEL'); ?>>Belge (Belgique)</option>
            <option value="BLZ" <?php selected($saved_visa_nationalite, 'BLZ'); ?>>Belizienne (Belize)</option>
            <option value="BEN" <?php selected($saved_visa_nationalite, 'BEN'); ?>>Béninoise (Bénin)</option>
            <option value="BTN" <?php selected($saved_visa_nationalite, 'BTN'); ?>>Bhoutanaise (Bhoutan)</option>
            <option value="BLR" <?php selected($saved_visa_nationalite, 'BLR'); ?>>Biélorusse (Biélorussie)</option>
            <option value="MMR" <?php selected($saved_visa_nationalite, 'MMR'); ?>>Birmane (Birmanie)</option>
            <option value="GNB" <?php selected($saved_visa_nationalite, 'GNB'); ?>>Bissau-Guinéenne (Guinée-Bissau)</option>
            <option value="BOL" <?php selected($saved_visa_nationalite, 'BOL'); ?>>Bolivienne (Bolivie)</option>
            <option value="BIH" <?php selected($saved_visa_nationalite, 'BIH'); ?>>Bosnienne (Bosnie-Herzégovine)</option>
            <option value="BWA" <?php selected($saved_visa_nationalite, 'BWA'); ?>>Botswanaise (Botswana)</option>
            <option value="BRA" <?php selected($saved_visa_nationalite, 'BRA'); ?>>Brésilienne (Brésil)</option>
            <option value="GBR" <?php selected($saved_visa_nationalite, 'GBR'); ?>>Britannique (Royaume-Uni)</option>
            <option value="BRN" <?php selected($saved_visa_nationalite, 'BRN'); ?>>Brunéienne (Brunéi)</option>
            <option value="BGR" <?php selected($saved_visa_nationalite, 'BGR'); ?>>Bulgare (Bulgarie)</option>
            <option value="BFA" <?php selected($saved_visa_nationalite, 'BFA'); ?>>Burkinabée (Burkina)</option>
            <option value="BDI" <?php selected($saved_visa_nationalite, 'BDI'); ?>>Burundaise (Burundi)</option>
            <option value="KHM" <?php selected($saved_visa_nationalite, 'KHM'); ?>>Cambodgienne (Cambodge)</option>
            <option value="CMR" <?php selected($saved_visa_nationalite, 'CMR'); ?>>Camerounaise (Cameroun)</option>
            <option value="CAN" <?php selected($saved_visa_nationalite, 'CAN'); ?>>Canadienne (Canada)</option>
            <option value="CPV" <?php selected($saved_visa_nationalite, 'CPV'); ?>>Cap-verdienne (Cap-Vert)</option>
            <option value="CAF" <?php selected($saved_visa_nationalite, 'CAF'); ?>>Centrafricaine (Centrafrique)</option>
            <option value="CHL" <?php selected($saved_visa_nationalite, 'CHL'); ?>>Chilienne (Chili)</option>
            <option value="CHN" <?php selected($saved_visa_nationalite, 'CHN'); ?>>Chinoise (Chine)</option>
            <option value="CYP" <?php selected($saved_visa_nationalite, 'CYP'); ?>>Chypriote (Chypre)</option>
            <option value="COL" <?php selected($saved_visa_nationalite, 'COL'); ?>>Colombienne (Colombie)</option>
            <option value="COM" <?php selected($saved_visa_nationalite, 'COM'); ?>>Comorienne (Comores)</option>
            <option value="COG" <?php selected($saved_visa_nationalite, 'COG'); ?>>Congolaise (Congo-Brazzaville)</option>
            <option value="COD" <?php selected($saved_visa_nationalite, 'COD'); ?>>Congolaise (Congo-Kinshasa)</option>
            <option value="COK" <?php selected($saved_visa_nationalite, 'COK'); ?>>Cookienne (Îles Cook)</option>
            <option value="CRI" <?php selected($saved_visa_nationalite, 'CRI'); ?>>Costaricaine (Costa Rica)</option>
            <option value="HRV" <?php selected($saved_visa_nationalite, 'HRV'); ?>>Croate (Croatie)</option>
            <option value="CUB" <?php selected($saved_visa_nationalite, 'CUB'); ?>>Cubaine (Cuba)</option>
            <option value="DNK" <?php selected($saved_visa_nationalite, 'DNK'); ?>>Danoise (Danemark)</option>
            <option value="DJI" <?php selected($saved_visa_nationalite, 'DJI'); ?>>Djiboutienne (Djibouti)</option>
            <option value="DOM" <?php selected($saved_visa_nationalite, 'DOM'); ?>>Dominicaine (République dominicaine)</option>
            <option value="DMA" <?php selected($saved_visa_nationalite, 'DMA'); ?>>Dominiquaise (Dominique)</option>
            <option value="EGY" <?php selected($saved_visa_nationalite, 'EGY'); ?>>Égyptienne (Égypte)</option>
            <option value="ARE" <?php selected($saved_visa_nationalite, 'ARE'); ?>>Émirienne (Émirats arabes unis)</option>
            <option value="GNQ" <?php selected($saved_visa_nationalite, 'GNQ'); ?>>Équato-guineenne (Guinée équatoriale)</option>
            <option value="ECU" <?php selected($saved_visa_nationalite, 'ECU'); ?>>Équatorienne (Équateur)</option>
            <option value="ERI" <?php selected($saved_visa_nationalite, 'ERI'); ?>>Érythréenne (Érythrée)</option>
            <option value="ESP" <?php selected($saved_visa_nationalite, 'ESP'); ?>>Espagnole (Espagne)</option>
            <option value="TLS" <?php selected($saved_visa_nationalite, 'TLS'); ?>>Est-timoraise (Timor-Leste)</option>
            <option value="EST" <?php selected($saved_visa_nationalite, 'EST'); ?>>Estonienne (Estonie)</option>
            <option value="ETH" <?php selected($saved_visa_nationalite, 'ETH'); ?>>Éthiopienne (Éthiopie)</option>
            <option value="FJI" <?php selected($saved_visa_nationalite, 'FJI'); ?>>Fidjienne (Fidji)</option>
            <option value="FIN" <?php selected($saved_visa_nationalite, 'FIN'); ?>>Finlandaise (Finlande)</option>
            <option value="FRA" <?php selected($saved_visa_nationalite, 'FRA'); ?>>Française (France)</option>
            <option value="GAB" <?php selected($saved_visa_nationalite, 'GAB'); ?>>Gabonaise (Gabon)</option>
            <option value="GMB" <?php selected($saved_visa_nationalite, 'GMB'); ?>>Gambienne (Gambie)</option>
            <option value="GEO" <?php selected($saved_visa_nationalite, 'GEO'); ?>>Georgienne (Géorgie)</option>
            <option value="GHA" <?php selected($saved_visa_nationalite, 'GHA'); ?>>Ghanéenne (Ghana)</option>
            <option value="GRD" <?php selected($saved_visa_nationalite, 'GRD'); ?>>Grenadienne (Grenade)</option>
            <option value="GTM" <?php selected($saved_visa_nationalite, 'GTM'); ?>>Guatémaltèque (Guatemala)</option>
            <option value="GIN" <?php selected($saved_visa_nationalite, 'GIN'); ?>>Guinéenne (Guinée)</option>
            <option value="GUY" <?php selected($saved_visa_nationalite, 'GUY'); ?>>Guyanienne (Guyana)</option>
            <option value="HTI" <?php selected($saved_visa_nationalite, 'HTI'); ?>>Haïtienne (Haïti)</option>
            <option value="GRC" <?php selected($saved_visa_nationalite, 'GRC'); ?>>Hellénique (Grèce)</option>
            <option value="HND" <?php selected($saved_visa_nationalite, 'HND'); ?>>Hondurienne (Honduras)</option>
            <option value="HUN" <?php selected($saved_visa_nationalite, 'HUN'); ?>>Hongroise (Hongrie)</option>
            <option value="IND" <?php selected($saved_visa_nationalite, 'IND'); ?>>Indienne (Inde)</option>
            <option value="IDN" <?php selected($saved_visa_nationalite, 'IDN'); ?>>Indonésienne (Indonésie)</option>
            <option value="IRQ" <?php selected($saved_visa_nationalite, 'IRQ'); ?>>Irakienne (Iraq)</option>
            <option value="IRN" <?php selected($saved_visa_nationalite, 'IRN'); ?>>Iranienne (Iran)</option>
            <option value="IRL" <?php selected($saved_visa_nationalite, 'IRL'); ?>>Irlandaise (Irlande)</option>
            <option value="ISL" <?php selected($saved_visa_nationalite, 'ISL'); ?>>Islandaise (Islande)</option>
            <option value="ISR" <?php selected($saved_visa_nationalite, 'ISR'); ?>>Israélienne (Israël)</option>
            <option value="ITA" <?php selected($saved_visa_nationalite, 'ITA'); ?>>Italienne (Italie)</option>
            <option value="CIV" <?php selected($saved_visa_nationalite, 'CIV'); ?>>Ivoirienne (Côte d'Ivoire)</option>
            <option value="JAM" <?php selected($saved_visa_nationalite, 'JAM'); ?>>Jamaïcaine (Jamaïque)</option>
            <option value="JPN" <?php selected($saved_visa_nationalite, 'JPN'); ?>>Japonaise (Japon)</option>
            <option value="JOR" <?php selected($saved_visa_nationalite, 'JOR'); ?>>Jordanienne (Jordanie)</option>
            <option value="KAZ" <?php selected($saved_visa_nationalite, 'KAZ'); ?>>Kazakhstanaise (Kazakhstan)</option>
            <option value="KEN" <?php selected($saved_visa_nationalite, 'KEN'); ?>>Kenyane (Kenya)</option>
            <option value="KGZ" <?php selected($saved_visa_nationalite, 'KGZ'); ?>>Kirghize (Kirghizistan)</option>
            <option value="KIR" <?php selected($saved_visa_nationalite, 'KIR'); ?>>Kiribatienne (Kiribati)</option>
            <option value="KNA" <?php selected($saved_visa_nationalite, 'KNA'); ?>>Kittitienne et Névicienne (Saint-Christophe-et-Niévès)</option>
            <option value="KWT" <?php selected($saved_visa_nationalite, 'KWT'); ?>>Koweïtienne (Koweït)</option>
            <option value="LAO" <?php selected($saved_visa_nationalite, 'LAO'); ?>>Laotienne (Laos)</option>
            <option value="LSO" <?php selected($saved_visa_nationalite, 'LSO'); ?>>Lesothane (Lesotho)</option>
            <option value="LVA" <?php selected($saved_visa_nationalite, 'LVA'); ?>>Lettone (Lettonie)</option>
            <option value="LBN" <?php selected($saved_visa_nationalite, 'LBN'); ?>>Libanaise (Liban)</option>
            <option value="LBR" <?php selected($saved_visa_nationalite, 'LBR'); ?>>Libérienne (Libéria)</option>
            <option value="LBY" <?php selected($saved_visa_nationalite, 'LBY'); ?>>Libyenne (Libye)</option>
            <option value="LIE" <?php selected($saved_visa_nationalite, 'LIE'); ?>>Liechtensteinoise (Liechtenstein)</option>
            <option value="LTU" <?php selected($saved_visa_nationalite, 'LTU'); ?>>Lituanienne (Lituanie)</option>
            <option value="LUX" <?php selected($saved_visa_nationalite, 'LUX'); ?>>Luxembourgeoise (Luxembourg)</option>
            <option value="MKD" <?php selected($saved_visa_nationalite, 'MKD'); ?>>Macédonienne (Macédoine)</option>
            <option value="MYS" <?php selected($saved_visa_nationalite, 'MYS'); ?>>Malaisienne (Malaisie)</option>
            <option value="MWI" <?php selected($saved_visa_nationalite, 'MWI'); ?>>Malawienne (Malawi)</option>
            <option value="MDV" <?php selected($saved_visa_nationalite, 'MDV'); ?>>Maldivienne (Maldives)</option>
            <option value="MDG" <?php selected($saved_visa_nationalite, 'MDG'); ?>>Malgache (Madagascar)</option>
            <option value="MLI" <?php selected($saved_visa_nationalite, 'MLI'); ?>>Maliennes (Mali)</option>
			<option value="MLT" <?php selected($saved_visa_nationalite, 'MLT'); ?>>Maltaise (Malte)</option>
            <option value="MAR" <?php selected($saved_visa_nationalite, 'MAR'); ?>>Marocaine (Maroc)</option>
            <option value="MHL" <?php selected($saved_visa_nationalite, 'MHL'); ?>>Marshallaise (Îles Marshall)</option>
            <option value="MUS" <?php selected($saved_visa_nationalite, 'MUS'); ?>>Mauricienne (Maurice)</option>
            <option value="MRT" <?php selected($saved_visa_nationalite, 'MRT'); ?>>Mauritanienne (Mauritanie)</option>
            <option value="MEX" <?php selected($saved_visa_nationalite, 'MEX'); ?>>Mexicaine (Mexique)</option>
            <option value="FSM" <?php selected($saved_visa_nationalite, 'FSM'); ?>>Micronésienne (Micronésie)</option>
            <option value="MDA" <?php selected($saved_visa_nationalite, 'MDA'); ?>>Moldave (Moldovie)</option>
            <option value="MCO" <?php selected($saved_visa_nationalite, 'MCO'); ?>>Monegasque (Monaco)</option>
            <option value="MNG" <?php selected($saved_visa_nationalite, 'MNG'); ?>>Mongole (Mongolie)</option>
            <option value="MNE" <?php selected($saved_visa_nationalite, 'MNE'); ?>>Monténégrine (Monténégro)</option>
            <option value="MOZ" <?php selected($saved_visa_nationalite, 'MOZ'); ?>>Mozambicaine (Mozambique)</option>
            <option value="NAM" <?php selected($saved_visa_nationalite, 'NAM'); ?>>Namibienne (Namibie)</option>
            <option value="NRU" <?php selected($saved_visa_nationalite, 'NRU'); ?>>Nauruane (Nauru)</option>
            <option value="NLD" <?php selected($saved_visa_nationalite, 'NLD'); ?>>Néerlandaise (Pays-Bas)</option>
            <option value="NZL" <?php selected($saved_visa_nationalite, 'NZL'); ?>>Néo-Zélandaise (Nouvelle-Zélande)</option>
            <option value="NPL" <?php selected($saved_visa_nationalite, 'NPL'); ?>>Népalaise (Népal)</option>
            <option value="NIC" <?php selected($saved_visa_nationalite, 'NIC'); ?>>Nicaraguayenne (Nicaragua)</option>
            <option value="NGA" <?php selected($saved_visa_nationalite, 'NGA'); ?>>Nigériane (Nigéria)</option>
            <option value="NER" <?php selected($saved_visa_nationalite, 'NER'); ?>>Nigérienne (Niger)</option>
            <option value="NIU" <?php selected($saved_visa_nationalite, 'NIU'); ?>>Niuéenne (Niue)</option>
            <option value="PRK" <?php selected($saved_visa_nationalite, 'PRK'); ?>>Nord-coréenne (Corée du Nord)</option>
            <option value="NOR" <?php selected($saved_visa_nationalite, 'NOR'); ?>>Norvégienne (Norvège)</option>
            <option value="OMN" <?php selected($saved_visa_nationalite, 'OMN'); ?>>Omanaise (Oman)</option>
            <option value="UGA" <?php selected($saved_visa_nationalite, 'UGA'); ?>>Ougandaise (Ouganda)</option>
            <option value="UZB" <?php selected($saved_visa_nationalite, 'UZB'); ?>>Ouzbéke (Ouzbékistan)</option>
            <option value="PAK" <?php selected($saved_visa_nationalite, 'PAK'); ?>>Pakistanaise (Pakistan)</option>
            <option value="PLW" <?php selected($saved_visa_nationalite, 'PLW'); ?>>Palaosienne (Palaos)</option>
            <option value="PSE" <?php selected($saved_visa_nationalite, 'PSE'); ?>>Palestinienne (Palestine)</option>
            <option value="PAN" <?php selected($saved_visa_nationalite, 'PAN'); ?>>Panaméenne (Panama)</option>
            <option value="PNG" <?php selected($saved_visa_nationalite, 'PNG'); ?>>Papouane-Néo-Guinéenne (Papouasie-Nouvelle-Guinée)</option>
            <option value="PRY" <?php selected($saved_visa_nationalite, 'PRY'); ?>>Paraguayenne (Paraguay)</option>
            <option value="PER" <?php selected($saved_visa_nationalite, 'PER'); ?>>Péruvienne (Pérou)</option>
            <option value="PHL" <?php selected($saved_visa_nationalite, 'PHL'); ?>>Philippine (Philippines)</option>
            <option value="POL" <?php selected($saved_visa_nationalite, 'POL'); ?>>Polonaise (Pologne)</option>
            <option value="PRT" <?php selected($saved_visa_nationalite, 'PRT'); ?>>Portugaise (Portugal)</option>
            <option value="QAT" <?php selected($saved_visa_nationalite, 'QAT'); ?>>Qatarienne (Qatar)</option>
            <option value="ROU" <?php selected($saved_visa_nationalite, 'ROU'); ?>>Roumaine (Roumanie)</option>
            <option value="RUS" <?php selected($saved_visa_nationalite, 'RUS'); ?>>Russe (Russie)</option>
            <option value="RWA" <?php selected($saved_visa_nationalite, 'RWA'); ?>>Rwandaise (Rwanda)</option>
            <option value="LCA" <?php selected($saved_visa_nationalite, 'LCA'); ?>>Saint-Lucienne (Sainte-Lucie)</option>
            <option value="SMR" <?php selected($saved_visa_nationalite, 'SMR'); ?>>Saint-Marinaise (Saint-Marin)</option>
            <option value="VCT" <?php selected($saved_visa_nationalite, 'VCT'); ?>>Saint-Vincentaise et Grenadine (Saint-Vincent-et-les Grenadines)</option>
            <option value="SLB" <?php selected($saved_visa_nationalite, 'SLB'); ?>>Salomonaise (Îles Salomon)</option>
            <option value="SLV" <?php selected($saved_visa_nationalite, 'SLV'); ?>>Salvadorienne (Salvador)</option>
            <option value="WSM" <?php selected($saved_visa_nationalite, 'WSM'); ?>>Samoane (Samoa)</option>
            <option value="STP" <?php selected($saved_visa_nationalite, 'STP'); ?>>Santoméenne (Sao Tomé-et-Principe)</option>
            <option value="SAU" <?php selected($saved_visa_nationalite, 'SAU'); ?>>Saoudienne (Arabie saoudite)</option>
            <option value="SEN" <?php selected($saved_visa_nationalite, 'SEN'); ?>>Sénégalaise (Sénégal)</option>
            <option value="SRB" <?php selected($saved_visa_nationalite, 'SRB'); ?>>Serbe (Serbie)</option>
            <option value="SYC" <?php selected($saved_visa_nationalite, 'SYC'); ?>>Seychelloise (Seychelles)</option>
            <option value="SLE" <?php selected($saved_visa_nationalite, 'SLE'); ?>>Sierra-Léonaise (Sierra Leone)</option>
            <option value="SGP" <?php selected($saved_visa_nationalite, 'SGP'); ?>>Singapourienne (Singapour)</option>
            <option value="SVK" <?php selected($saved_visa_nationalite, 'SVK'); ?>>Slovaque (Slovaquie)</option>
            <option value="SVN" <?php selected($saved_visa_nationalite, 'SVN'); ?>>Slovène (Slovénie)</option>
            <option value="SOM" <?php selected($saved_visa_nationalite, 'SOM'); ?>>Somalienne (Somalie)</option>
            <option value="SDN" <?php selected($saved_visa_nationalite, 'SDN'); ?>>Soudanaise (Soudan)</option>
            <option value="LKA" <?php selected($saved_visa_nationalite, 'LKA'); ?>>Sri-Lankaise (Sri Lanka)</option>
            <option value="ZAF" <?php selected($saved_visa_nationalite, 'ZAF'); ?>>Sud-Africaine (Afrique du Sud)</option>
            <option value="KOR" <?php selected($saved_visa_nationalite, 'KOR'); ?>>Sud-Coréenne (Corée du Sud)</option>
            <option value="SSD" <?php selected($saved_visa_nationalite, 'SSD'); ?>>Sud-Soudanaise (Soudan du Sud)</option>
            <option value="SWE" <?php selected($saved_visa_nationalite, 'SWE'); ?>>Suédoise (Suède)</option>
            <option value="CHE" <?php selected($saved_visa_nationalite, 'CHE'); ?>>Suisse (Suisse)</option>
            <option value="SUR" <?php selected($saved_visa_nationalite, 'SUR'); ?>>Surinamaise (Suriname)</option>
            <option value="SWZ" <?php selected($saved_visa_nationalite, 'SWZ'); ?>>Swazie (Swaziland)</option>
            <option value="SYR" <?php selected($saved_visa_nationalite, 'SYR'); ?>>Syrienne (Syrie)</option>
            <option value="TJK" <?php selected($saved_visa_nationalite, 'TJK'); ?>>Tadjike (Tadjikistan)</option>
            <option value="TZA" <?php selected($saved_visa_nationalite, 'TZA'); ?>>Tanzanienne (Tanzanie)</option>
            <option value="TCD" <?php selected($saved_visa_nationalite, 'TCD'); ?>>Tchadienne (Tchad)</option>
            <option value="CZE" <?php selected($saved_visa_nationalite, 'CZE'); ?>>Tchèque (Tchéquie)</option>
            <option value="THA" <?php selected($saved_visa_nationalite, 'THA'); ?>>Thaïlandaise (Thaïlande)</option>
            <option value="TGO" <?php selected($saved_visa_nationalite, 'TGO'); ?>>Togolaise (Togo)</option>
            <option value="TON" <?php selected($saved_visa_nationalite, 'TON'); ?>>Tonguienne (Tonga)</option>
            <option value="TTO" <?php selected($saved_visa_nationalite, 'TTO'); ?>>Trinidadienne (Trinité-et-Tobago)</option>
            <option value="TUN" <?php selected($saved_visa_nationalite, 'TUN'); ?>>Tunisienne (Tunisie)</option>
            <option value="TKM" <?php selected($saved_visa_nationalite, 'TKM'); ?>>Turkmène (Turkménistan)</option>
            <option value="TUR" <?php selected($saved_visa_nationalite, 'TUR'); ?>>Turque (Turquie)</option>
            <option value="TUV" <?php selected($saved_visa_nationalite, 'TUV'); ?>>Tuvaluane (Tuvalu)</option>
            <option value="UKR" <?php selected($saved_visa_nationalite, 'UKR'); ?>>Ukrainienne (Ukraine)</option>
            <option value="URY" <?php selected($saved_visa_nationalite, 'URY'); ?>>Uruguayenne (Uruguay)</option>
            <option value="VUT" <?php selected($saved_visa_nationalite, 'VUT'); ?>>Vanuatuane (Vanuatu)</option>
            <option value="VAT" <?php selected($saved_visa_nationalite, 'VAT'); ?>>Vaticane (Vatican)</option>
            <option value="VEN" <?php selected($saved_visa_nationalite, 'VEN'); ?>>Vénézuélienne (Venezuela)</option>
            <option value="VNM" <?php selected($saved_visa_nationalite, 'VNM'); ?>>Vietnamienne (Viêt Nam)</option>
            <option value="YEM" <?php selected($saved_visa_nationalite, 'YEM'); ?>>Yéménite (Yémen)</option>
            <option value="ZMB" <?php selected($saved_visa_nationalite, 'ZMB'); ?>>Zambienne (Zambie)</option>
            <option value="ZWE" <?php selected($saved_visa_nationalite, 'ZWE'); ?>>Zimbabwéenne (Zimbabwe)</option>
		</select><br><br>
	
		<label>Nationalité à la naissance, si différente :</label><br>
		<select name="nationalite_diff">
            <option value="">-- Sélectionnez une nationalité --</option>
			<option value="AFG">Afghane (Afghanistan)</option>
			<option value="ALB">Albanaise (Albanie)</option>
			<option value="DZA">Algérienne (Algérie)</option>
			<option value="DEU">Allemande (Allemagne)</option>
			<option value="USA">Americaine (États-Unis)</option>
			<option value="AND">Andorrane (Andorre)</option>
			<option value="AGO">Angolaise (Angola)</option>
			<option value="ATG">Antiguaise-et-Barbudienne (Antigua-et-Barbuda)</option>
			<option value="ARG">Argentine (Argentine)</option>
			<option value="ARM">Armenienne (Arménie)</option>
			<option value="AUS">Australienne (Australie)</option>
			<option value="AUT">Autrichienne (Autriche)</option>
			<option value="AZE">Azerbaïdjanaise (Azerbaïdjan)</option>
			<option value="BHS">Bahamienne (Bahamas)</option>
			<option value="BHR">Bahreinienne (Bahreïn)</option>
			<option value="BGD">Bangladaise (Bangladesh)</option>
			<option value="BRB">Barbadienne (Barbade)</option>
			<option value="BEL">Belge (Belgique)</option>
			<option value="BLZ">Belizienne (Belize)</option>
			<option value="BEN">Béninoise (Bénin)</option>
			<option value="BTN">Bhoutanaise (Bhoutan)</option>
			<option value="BLR">Biélorusse (Biélorussie)</option>
			<option value="MMR">Birmane (Birmanie)</option>
			<option value="GNB">Bissau-Guinéenne (Guinée-Bissau)</option>
			<option value="BOL">Bolivienne (Bolivie)</option>
			<option value="BIH">Bosnienne (Bosnie-Herzégovine)</option>
			<option value="BWA">Botswanaise (Botswana)</option>
			<option value="BRA">Brésilienne (Brésil)</option>
			<option value="GBR">Britannique (Royaume-Uni)</option>
			<option value="BRN">Brunéienne (Brunéi)</option>
			<option value="BGR">Bulgare (Bulgarie)</option>
			<option value="BFA">Burkinabée (Burkina)</option>
			<option value="BDI">Burundaise (Burundi)</option>
			<option value="KHM">Cambodgienne (Cambodge)</option>
			<option value="CMR">Camerounaise (Cameroun)</option>
			<option value="CAN">Canadienne (Canada)</option>
			<option value="CPV">Cap-verdienne (Cap-Vert)</option>
			<option value="CAF">Centrafricaine (Centrafrique)</option>
			<option value="CHL">Chilienne (Chili)</option>
			<option value="CHN">Chinoise (Chine)</option>
			<option value="CYP">Chypriote (Chypre)</option>
			<option value="COL">Colombienne (Colombie)</option>
			<option value="COM">Comorienne (Comores)</option>
			<option value="COG">Congolaise (Congo-Brazzaville)</option>
			<option value="COD">Congolaise (Congo-Kinshasa)</option>
			<option value="COK">Cookienne (Îles Cook)</option>
			<option value="CRI">Costaricaine (Costa Rica)</option>
			<option value="HRV">Croate (Croatie)</option>
			<option value="CUB">Cubaine (Cuba)</option>
			<option value="DNK">Danoise (Danemark)</option>
			<option value="DJI">Djiboutienne (Djibouti)</option>
			<option value="DOM">Dominicaine (République dominicaine)</option>
			<option value="DMA">Dominiquaise (Dominique)</option>
			<option value="EGY">Egyptienne (Égypte)</option>
			<option value="ARE">Emirienne (Émirats arabes unis)</option>
			<option value="GNQ">Equato-guineenne (Guinée équatoriale)</option>
			<option value="ECU">Equatorienne (Équateur)</option>
			<option value="ERI">Erythréenne (Érythrée)</option>
			<option value="ESP">Espagnole (Espagne)</option>
			<option value="TLS">Est-timoraise (Timor-Leste)</option>
			<option value="EST">Estonienne (Estonie)</option>
			<option value="ETH">Ethiopienne (Éthiopie)</option>
			<option value="FJI">Fidjienne (Fidji)</option>
			<option value="FIN">Finlandaise (Finlande)</option>
			<option value="FRA">Française (France)</option>
			<option value="GAB">Gabonaise (Gabon)</option>
			<option value="GMB">Gambienne (Gambie)</option>
			<option value="GEO">Georgienne (Géorgie)</option>
			<option value="GHA">Ghanéenne (Ghana)</option>
			<option value="GRD">Grenadienne (Grenade)</option>
			<option value="GTM">Guatémaltèque (Guatemala)</option>
			<option value="GIN">Guinéenne (Guinée)</option>
			<option value="GUY">Guyanienne (Guyana)</option>
			<option value="HTI">Haïtienne (Haïti)</option>
			<option value="GRC">Hellénique (Grèce)</option>
			<option value="HND">Hondurienne (Honduras)</option>
			<option value="HUN">Hongroise (Hongrie)</option>
			<option value="IND">Indienne (Inde)</option>
			<option value="IDN">Indonésienne (Indonésie)</option>
			<option value="IRQ">Irakienne (Iraq)</option>
			<option value="IRN">Iranienne (Iran)</option>
			<option value="IRL">Irlandaise (Irlande)</option>
			<option value="ISL">Islandaise (Islande)</option>
			<option value="ISR">Israélienne (Israël)</option>
			<option value="ITA">Italienne (Italie)</option>
			<option value="CIV">Ivoirienne (Côte d'Ivoire)</option>
			<option value="JAM">Jamaïcaine (Jamaïque)</option>
			<option value="JPN">Japonaise (Japon)</option>
			<option value="JOR">Jordanienne (Jordanie)</option>
			<option value="KAZ">Kazakhstanaise (Kazakhstan)</option>
			<option value="KEN">Kenyane (Kenya)</option>
			<option value="KGZ">Kirghize (Kirghizistan)</option>
			<option value="KIR">Kiribatienne (Kiribati)</option>
			<option value="KNA">Kittitienne et Névicienne (Saint-Christophe-et-Niévès)</option>
			<option value="KWT">Koweïtienne (Koweït)</option>
			<option value="LAO">Laotienne (Laos)</option>
			<option value="LSO">Lesothane (Lesotho)</option>
			<option value="LVA">Lettone (Lettonie)</option>
			<option value="LBN">Libanaise (Liban)</option>
			<option value="LBR">Libérienne (Libéria)</option>
			<option value="LBY">Libyenne (Libye)</option>
			<option value="LIE">Liechtensteinoise (Liechtenstein)</option>
			<option value="LTU">Lituanienne (Lituanie)</option>
			<option value="LUX">Luxembourgeoise (Luxembourg)</option>
			<option value="MKD">Macédonienne (Macédoine)</option>
			<option value="MYS">Malaisienne (Malaisie)</option>
			<option value="MWI">Malawienne (Malawi)</option>
			<option value="MDV">Maldivienne (Maldives)</option>
			<option value="MDG">Malgache (Madagascar)</option>
			<option value="MLI">Maliennes (Mali)</option>
			<option value="MLT">Maltaise (Malte)</option>
			<option value="MAR">Marocaine (Maroc)</option>
			<option value="MHL">Marshallaise (Îles Marshall)</option>
			<option value="MUS">Mauricienne (Maurice)</option>
			<option value="MRT">Mauritanienne (Mauritanie)</option>
			<option value="MEX">Mexicaine (Mexique)</option>
			<option value="FSM">Micronésienne (Micronésie)</option>
			<option value="MDA">Moldave (Moldovie)</option>
			<option value="MCO">Monegasque (Monaco)</option>
			<option value="MNG">Mongole (Mongolie)</option>
			<option value="MNE">Monténégrine (Monténégro)</option>
			<option value="MOZ">Mozambicaine (Mozambique)</option>
			<option value="NAM">Namibienne (Namibie)</option>
			<option value="NRU">Nauruane (Nauru)</option>
			<option value="NLD">Néerlandaise (Pays-Bas)</option>
			<option value="NZL">Néo-Zélandaise (Nouvelle-Zélande)</option>
			<option value="NPL">Népalaise (Népal)</option>
			<option value="NIC">Nicaraguayenne (Nicaragua)</option>
			<option value="NGA">Nigériane (Nigéria)</option>
			<option value="NER">Nigérienne (Niger)</option>
			<option value="NIU">Niuéenne (Niue)</option>
			<option value="PRK">Nord-coréenne (Corée du Nord)</option>
			<option value="NOR">Norvégienne (Norvège)</option>
			<option value="OMN">Omanaise (Oman)</option>
			<option value="UGA">Ougandaise (Ouganda)</option>
			<option value="UZB">Ouzbéke (Ouzbékistan)</option>
			<option value="PAK">Pakistanaise (Pakistan)</option>
			<option value="PLW">Palaosienne (Palaos)</option>
			<option value="PSE">Palestinienne (Palestine)</option>
			<option value="PAN">Panaméenne (Panama)</option>
			<option value="PNG">Papouane-Néo-Guinéenne (Papouasie-Nouvelle-Guinée)</option>
			<option value="PRY">Paraguayenne (Paraguay)</option>
			<option value="PER">Péruvienne (Pérou)</option>
			<option value="PHL">Philippine (Philippines)</option>
			<option value="POL">Polonaise (Pologne)</option>
			<option value="PRT">Portugaise (Portugal)</option>
			<option value="QAT">Qatarienne (Qatar)</option>
			<option value="ROU">Roumaine (Roumanie)</option>
			<option value="RUS">Russe (Russie)</option>
			<option value="RWA">Rwandaise (Rwanda)</option>
			<option value="LCA">Saint-Lucienne (Sainte-Lucie)</option>
			<option value="SMR">Saint-Marinaise (Saint-Marin)</option>
			<option value="VCT">Saint-Vincentaise et Grenadine (Saint-Vincent-et-les Grenadines)</option>
			<option value="SLB">Salomonaise (Îles Salomon)</option>
			<option value="SLV">Salvadorienne (Salvador)</option>
			<option value="WSM">Samoane (Samoa)</option>
			<option value="STP">Santoméenne (Sao Tomé-et-Principe)</option>
			<option value="SAU">Saoudienne (Arabie saoudite)</option>
			<option value="SEN">Sénégalaise (Sénégal)</option>
			<option value="SRB">Serbe (Serbie)</option>
			<option value="SYC">Seychelloise (Seychelles)</option>
			<option value="SLE">Sierra-Léonaise (Sierra Leone)</option>
			<option value="SGP">Singapourienne (Singapour)</option>
			<option value="SVK">Slovaque (Slovaquie)</option>
			<option value="SVN">Slovène (Slovénie)</option>
			<option value="SOM">Somalienne (Somalie)</option>
			<option value="SDN">Soudanaise (Soudan)</option>
			<option value="LKA">Sri-Lankaise (Sri Lanka)</option>
			<option value="ZAF">Sud-Africaine (Afrique du Sud)</option>
			<option value="KOR">Sud-Coréenne (Corée du Sud)</option>
			<option value="SSD">Sud-Soudanaise (Soudan du Sud)</option>
			<option value="SWE">Suédoise (Suède)</option>
			<option value="CHE">Suisse (Suisse)</option>
			<option value="SUR">Surinamaise (Suriname)</option>
			<option value="SWZ">Swazie (Swaziland)</option>
			<option value="SYR">Syrienne (Syrie)</option>
			<option value="TJK">Tadjike (Tadjikistan)</option>
			<option value="TZA">Tanzanienne (Tanzanie)</option>
			<option value="TCD">Tchadienne (Tchad)</option>
			<option value="CZE">Tchèque (Tchéquie)</option>
			<option value="THA">Thaïlandaise (Thaïlande)</option>
			<option value="TGO">Togolaise (Togo)</option>
			<option value="TON">Tonguienne (Tonga)</option>
			<option value="TTO">Trinidadienne (Trinité-et-Tobago)</option>
			<option value="TUN">Tunisienne (Tunisie)</option>
			<option value="TKM">Turkmène (Turkménistan)</option>
			<option value="TUR">Turque (Turquie)</option>
			<option value="TUV">Tuvaluane (Tuvalu)</option>
			<option value="UKR">Ukrainienne (Ukraine)</option>
			<option value="URY">Uruguayenne (Uruguay)</option>
			<option value="VUT">Vanuatuane (Vanuatu)</option>
			<option value="VAT">Vaticane (Vatican)</option>
			<option value="VEN">Vénézuélienne (Venezuela)</option>
			<option value="VNM">Vietnamienne (Viêt Nam)</option>
			<option value="YEM">Yéménite (Yémen)</option>
			<option value="ZMB">Zambienne (Zambie)</option>
			<option value="ZWE">Zimbabwéenne (Zimbabwe)</option>
		</select><br><br>

		<label>Autre(s) nationalité(s) :</label><br>
		<input type="text" name="autres_nationalites"><br><br>
	
		<label>8. Sexe :<span class="required">*</span></label><br>
		<input type="radio" name="sexe" value="homme" <?php checked($saved_sexe, 'homme'); ?>> Homme<br>
		<input type="radio" name="sexe" value="femme" <?php checked($saved_sexe, 'femme'); ?>> Femme<br>
		<input type="radio" name="sexe" value="autre" <?php checked($saved_sexe, 'autre'); ?>> Autre<br>
		<input type="text" name="sexe_autre"><br><br>
	
		<label>9. État Civil :<span class="required">*</span></label><br>
		<input type="radio" name="etat_civil" value="celibataire" <?php checked($saved_etat_civil, 'celibataire'); ?>> Célibataire<br>
		<input type="radio" name="etat_civil" value="marie" <?php checked($saved_etat_civil, 'marie'); ?>> Marié(e)<br>
		<input type="radio" name="etat_civil" value="partenariat" <?php checked($saved_etat_civil, 'partenariat'); ?>> Partenariat enregistré<br>
		<input type="radio" name="etat_civil" value="separe" <?php checked($saved_etat_civil, 'separe'); ?>> Séparé(e)<br>
		<input type="radio" name="etat_civil" value="divorce" <?php checked($saved_etat_civil, 'divorce'); ?>> Divorcé(e)<br>
		<input type="radio" name="etat_civil" value="veuf" <?php checked($saved_etat_civil, 'veuf'); ?>> Veuf(Veuve)<br>
		<input type="radio" name="etat_civil" value="autre" <?php checked($saved_etat_civil, 'autre'); ?>> Autre
		<input type="text" name="etat_civil_autre"><br><br>
	
	
		
		<label>10. Numéro national d’identité, le cas échéant :</label><br>
		<input type="text" name="num_national_identite"><br><br>
	
		<label>11. Type de document de voyage :<span class="required">*</span></label><br>
		<input type="radio" name="doc_voyage" value="passeport_ordinaire"> Passeport ordinaire<br>
		<input type="radio" name="doc_voyage" value="passeport_diplomatique"> Passeport diplomatique<br>
		<input type="radio" name="doc_voyage" value="passeport_service"> Passeport de service<br>
		<input type="radio" name="doc_voyage" value="passeport_officiel"> Passeport officiel<br>
		<input type="radio" name="doc_voyage" value="passeport_spécial"> Passeport spécial<br>
		<input type="radio" name="doc_voyage" value="autre"> Autre document de voyage (à préciser)
		<input type="text" name="doc_voyage_autre"><br><br>
	
		<label>12. Numéro du document de voyage :<span class="required">*</span></label><br>
		<input type="text" name="num_document" value="<?php echo esc_attr($saved_num_document); ?>" required><br><br>
	
		<label>13. Date de délivrance :<span class="required">*</span></label><br>
		<input type="date" name="date_delivrance" value="<?php echo esc_attr($saved_date_delivrance); ?>" required><br><br>
	
		<label>14. Date d’expiration :<span class="required">*</span></label><br>
		<input type="date" name="date_expiration" value="<?php echo esc_attr($saved_date_expiration); ?>" required><br><br>
	
		<label>15. Délivré par (pays) :<span class="required">*</span></label><br>
		<select id="country" name="delivre_par" value="<?php echo esc_attr($saved_visa_delivre_par); ?>" required>
			<option value="">-- Sélectionnez un pays --</option>
			<option value="Afghanistan">Afghanistan</option>
			<option value="Afrique du Sud">Afrique du Sud</option>
			<option value="Albanie">Albanie</option>
			<option value="Algérie">Algérie</option>
			<option value="Allemagne">Allemagne</option>
			<option value="Andorre">Andorre</option>
			<option value="Angola">Angola</option>
			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
			<option value="Arabie Saoudite">Arabie Saoudite</option>
			<option value="Argentine">Argentine</option>
			<option value="Arménie">Arménie</option>
			<option value="Australie">Australie</option>
			<option value="Autriche">Autriche</option>
			<option value="Azerbaïdjan">Azerbaïdjan</option>
			<option value="Bahamas">Bahamas</option>
			<option value="Bahreïn">Bahreïn</option>
			<option value="Bangladesh">Bangladesh</option>
			<option value="Barbade">Barbade</option>
			<option value="Belgique">Belgique</option>
			<option value="Belize">Belize</option>
			<option value="Bénin">Bénin</option>
			<option value="Bhoutan">Bhoutan</option>
			<option value="Biélorussie">Biélorussie</option>
			<option value="Birmanie">Birmanie</option>
			<option value="Bolivie">Bolivie</option>
			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
			<option value="Botswana">Botswana</option>
			<option value="Brésil">Brésil</option>
			<option value="Brunei">Brunei</option>
			<option value="Bulgarie">Bulgarie</option>
			<option value="Burkina Faso">Burkina Faso</option>
			<option value="Burundi">Burundi</option>
			<option value="Cabo Verde">Cabo Verde</option>
			<option value="Cambodge">Cambodge</option>
			<option value="Cameroun">Cameroun</option>
			<option value="Canada">Canada</option>
			<option value="République centrafricaine">République centrafricaine</option>
			<option value="Tchad">Tchad</option>
			<option value="Chili">Chili</option>
			<option value="Chine">Chine</option>
			<option value="Chypre">Chypre</option>
			<option value="Colombie">Colombie</option>
			<option value="Comores">Comores</option>
			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
			<option value="Corée du Nord">Corée du Nord</option>
			<option value="Corée du Sud">Corée du Sud</option>
			<option value="Costa Rica">Costa Rica</option>
			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
			<option value="Croatie">Croatie</option>
			<option value="Cuba">Cuba</option>
			<option value="Danemark">Danemark</option>
			<option value="Djibouti">Djibouti</option>
			<option value="Dominique">Dominique</option>
			<option value="République dominicaine">République dominicaine</option>
			<option value="Egypte">Égypte</option>
			<option value="Emirats arabes unis">Émirats arabes unis</option>
			<option value="Equateur">Équateur</option>
			<option value="Erythrée">Érythrée</option>
			<option value="Espagne">Espagne</option>
			<option value="Estonie">Estonie</option>
			<option value="Eswatini">Eswatini</option>
			<option value="Etats-Unis">États-Unis</option>
			<option value="Ethiopie">Éthiopie</option>
			<option value="Fidji">Fidji</option>
			<option value="Finlande">Finlande</option>
			<option value="France">France</option>
			<option value="Gabon">Gabon</option>
			<option value="Gambie">Gambie</option>
			<option value="Géorgie">Géorgie</option>
			<option value="Ghana">Ghana</option>
			<option value="Grèce">Grèce</option>
			<option value="Grenade">Grenade</option>
			<option value="Guatemala">Guatemala</option>
			<option value="Guinée">Guinée</option>
			<option value="Guinée-Bissau">Guinée-Bissau</option>
			<option value="Guinée équatoriale">Guinée équatoriale</option>
			<option value="Guyana">Guyana</option>
			<option value="Haïti">Haïti</option>
			<option value="Honduras">Honduras</option>
			<option value="Hongrie">Hongrie</option>
			<option value="Inde">Inde</option>
			<option value="Indonésie">Indonésie</option>
			<option value="Irak">Irak</option>
			<option value="Iran">Iran</option>
			<option value="Irlande">Irlande</option>
			<option value="Islande">Islande</option>
			<option value="Israël">Israël</option>
			<option value="Italie">Italie</option>
			<option value="Jamaïque">Jamaïque</option>
			<option value="Japon">Japon</option>
			<option value="Jordanie">Jordanie</option>
			<option value="Kazakhstan">Kazakhstan</option>
			<option value="Kenya">Kenya</option>
			<option value="Kirghizistan">Kirghizistan</option>
			<option value="Kiribati">Kiribati</option>
			<option value="Kosovo">Kosovo</option>
			<option value="Koweït">Koweït</option>
			<option value="Laos">Laos</option>
			<option value="Lettonie">Lettonie</option>
			<option value="Liban">Liban</option>
			<option value="Libéria">Libéria</option>
			<option value="Libye">Libye</option>
			<option value="Liechtenstein">Liechtenstein</option>
			<option value="Lituanie">Lituanie</option>
			<option value="Luxembourg">Luxembourg</option>
			<option value="Macédoine du Nord">Macédoine du Nord</option>
			<option value="Madagascar">Madagascar</option>
			<option value="Malaisie">Malaisie</option>
			<option value="Malawi">Malawi</option>
			<option value="Maldives">Maldives</option>
			<option value="Mali">Mali</option>
			<option value="Malte">Malte</option>
			<option value="Maroc">Maroc</option>
			<option value="Marshall">Îles Marshall</option>
			<option value="Maurice">Maurice</option>
			<option value="Mauritanie">Mauritanie</option>
			<option value="Mexique">Mexique</option>
			<option value="Micronésie">Micronésie</option>
			<option value="Moldavie">Moldavie</option>
			<option value="Monaco">Monaco</option>
			<option value="Mongolie">Mongolie</option>
			<option value="Monténégro">Monténégro</option>
			<option value="Mozambique">Mozambique</option>
			<option value="Namibie">Namibie</option>
			<option value="Nauru">Nauru</option>
			<option value="Népal">Népal</option>
			<option value="Nicaragua">Nicaragua</option>
			<option value="Niger">Niger</option>
			<option value="Nigéria">Nigéria</option>
			<option value="Norvège">Norvège</option>
			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
			<option value="Oman">Oman</option>
			<option value="Ouganda">Ouganda</option>
			<option value="Ouzbékistan">Ouzbékistan</option>
			<option value="Pakistan">Pakistan</option>
			<option value="Palaos">Palaos</option>
			<option value="Palestine">Palestine</option>
			<option value="Panama">Panama</option>
			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
			<option value="Paraguay">Paraguay</option>
			<option value="Pays-Bas">Pays-Bas</option>
			<option value="Pérou">Pérou</option>
			<option value="Philippines">Philippines</option>
			<option value="Pologne">Pologne</option>
			<option value="Portugal">Portugal</option>
			<option value="République centrafricaine">République centrafricaine</option>
			<option value="République dominicaine">République dominicaine</option>
			<option value="Roumanie">Roumanie</option>
			<option value="Royaume-Uni">Royaume-Uni</option>
			<option value="Russie">Russie</option>
			<option value="Rwanda">Rwanda</option>
			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
			<option value="Saint-Marin">Saint-Marin</option>
			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
			<option value="Sainte-Lucie">Sainte-Lucie</option>
			<option value="Salvador">Salvador</option>
			<option value="Samoa">Samoa</option>
			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
			<option value="Sénégal">Sénégal</option>
			<option value="Serbie">Serbie</option>
			<option value="Seychelles">Seychelles</option>
			<option value="Sierra Leone">Sierra Leone</option>
			<option value="Singapour">Singapour</option>
			<option value="Slovaquie">Slovaquie</option>
			<option value="Slovénie">Slovénie</option>
			<option value="Somalie">Somalie</option>
			<option value="Soudan">Soudan</option>
			<option value="Soudan du Sud">Soudan du Sud</option>
			<option value="Sri Lanka">Sri Lanka</option>
			<option value="Suède">Suède</option>
			<option value="Suisse">Suisse</option>
			<option value="Suriname">Suriname</option>
			<option value="Syrie">Syrie</option>
			<option value="Tadjikistan">Tadjikistan</option>
			<option value="Tanzanie">Tanzanie</option>
			<option value="Tchad">Tchad</option>
			<option value="Tchécoslovaquie">Tchéquie</option>
			<option value="Thaïlande">Thaïlande</option>
			<option value="Timor-Leste">Timor-Leste</option>
			<option value="Togo">Togo</option>
			<option value="Tonga">Tonga</option>
			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
			<option value="Tunisie">Tunisie</option>
			<option value="Turkménistan">Turkménistan</option>
			<option value="Turquie">Turquie</option>
			<option value="Tuvalu">Tuvalu</option>
			<option value="Ukraine">Ukraine</option>
			<option value="Uruguay">Uruguay</option>
			<option value="Vanuatu">Vanuatu</option>
			<option value="Vatican">Vatican</option>
			<option value="Venezuela">Venezuela</option>
			<option value="Vietnam">Vietnam</option>
			<option value="Yémen">Yémen</option>
			<option value="Zambie">Zambie</option>
			<option value="Zimbabwe">Zimbabwe</option>
		</select><br><br>
	
		<label>16. Données à caractère personnel du membre de la famille qui est un ressortissant de l’UE, de l’EEE ou de la Confédération suisse ou un ressortissant du Royaume-Uni bénéficiaire de l’accord sur le retrait du Royaume-Uni de l’UE, selon le cas :</label><br>
		<label>Nom (nom de famille) :</label><br>
		<input type="text" name="nom_famille_UE"><br><br>
	
		<label>Prénom(s) [nom(s) usuel(s)] :</label><br>
		<input type="text" name="prenom_famille"><br><br>
	
		<label>Date de naissance (jour-mois-année) :</label><br>
		<input type="date" name="birth_famille"><br><br>
	
		<label>Nationalité :</label><br>
		<select name="nationalite_famille">
			<option value="">-- Sélectionnez une nationalité --</option>
			<option value="AFG">Afghane (Afghanistan)</option>
			<option value="ALB">Albanaise (Albanie)</option>
			<option value="DZA">Algérienne (Algérie)</option>
			<option value="DEU">Allemande (Allemagne)</option>
			<option value="USA">Americaine (États-Unis)</option>
			<option value="AND">Andorrane (Andorre)</option>
			<option value="AGO">Angolaise (Angola)</option>
			<option value="ATG">Antiguaise-et-Barbudienne (Antigua-et-Barbuda)</option>
			<option value="ARG">Argentine (Argentine)</option>
			<option value="ARM">Armenienne (Arménie)</option>
			<option value="AUS">Australienne (Australie)</option>
			<option value="AUT">Autrichienne (Autriche)</option>
			<option value="AZE">Azerbaïdjanaise (Azerbaïdjan)</option>
			<option value="BHS">Bahamienne (Bahamas)</option>
			<option value="BHR">Bahreinienne (Bahreïn)</option>
			<option value="BGD">Bangladaise (Bangladesh)</option>
			<option value="BRB">Barbadienne (Barbade)</option>
			<option value="BEL">Belge (Belgique)</option>
			<option value="BLZ">Belizienne (Belize)</option>
			<option value="BEN">Béninoise (Bénin)</option>
			<option value="BTN">Bhoutanaise (Bhoutan)</option>
			<option value="BLR">Biélorusse (Biélorussie)</option>
			<option value="MMR">Birmane (Birmanie)</option>
			<option value="GNB">Bissau-Guinéenne (Guinée-Bissau)</option>
			<option value="BOL">Bolivienne (Bolivie)</option>
			<option value="BIH">Bosnienne (Bosnie-Herzégovine)</option>
			<option value="BWA">Botswanaise (Botswana)</option>
			<option value="BRA">Brésilienne (Brésil)</option>
			<option value="GBR">Britannique (Royaume-Uni)</option>
			<option value="BRN">Brunéienne (Brunéi)</option>
			<option value="BGR">Bulgare (Bulgarie)</option>
			<option value="BFA">Burkinabée (Burkina)</option>
			<option value="BDI">Burundaise (Burundi)</option>
			<option value="KHM">Cambodgienne (Cambodge)</option>
			<option value="CMR">Camerounaise (Cameroun)</option>
			<option value="CAN">Canadienne (Canada)</option>
			<option value="CPV">Cap-verdienne (Cap-Vert)</option>
			<option value="CAF">Centrafricaine (Centrafrique)</option>
			<option value="CHL">Chilienne (Chili)</option>
			<option value="CHN">Chinoise (Chine)</option>
			<option value="CYP">Chypriote (Chypre)</option>
			<option value="COL">Colombienne (Colombie)</option>
			<option value="COM">Comorienne (Comores)</option>
			<option value="COG">Congolaise (Congo-Brazzaville)</option>
			<option value="COD">Congolaise (Congo-Kinshasa)</option>
			<option value="COK">Cookienne (Îles Cook)</option>
			<option value="CRI">Costaricaine (Costa Rica)</option>
			<option value="HRV">Croate (Croatie)</option>
			<option value="CUB">Cubaine (Cuba)</option>
			<option value="DNK">Danoise (Danemark)</option>
			<option value="DJI">Djiboutienne (Djibouti)</option>
			<option value="DOM">Dominicaine (République dominicaine)</option>
			<option value="DMA">Dominiquaise (Dominique)</option>
			<option value="EGY">Égyptienne (Égypte)</option>
			<option value="ARE">Émirienne (Émirats arabes unis)</option>
			<option value="GNQ">Équato-guineenne (Guinée équatoriale)</option>
			<option value="ECU">Équatorienne (Équateur)</option>
			<option value="ERI">Érythréenne (Érythrée)</option>
			<option value="ESP">Espagnole (Espagne)</option>
			<option value="TLS">Est-timoraise (Timor-Leste)</option>
			<option value="EST">Estonienne (Estonie)</option>
			<option value="ETH">Éthiopienne (Éthiopie)</option>
			<option value="FJI">Fidjienne (Fidji)</option>
			<option value="FIN">Finlandaise (Finlande)</option>
			<option value="FRA">Française (France)</option>
			<option value="GAB">Gabonaise (Gabon)</option>
			<option value="GMB">Gambienne (Gambie)</option>
			<option value="GEO">Georgienne (Géorgie)</option>
			<option value="GHA">Ghanéenne (Ghana)</option>
			<option value="GRD">Grenadienne (Grenade)</option>
			<option value="GTM">Guatémaltèque (Guatemala)</option>
			<option value="GIN">Guinéenne (Guinée)</option>
			<option value="GUY">Guyanienne (Guyana)</option>
			<option value="HTI">Haïtienne (Haïti)</option>
			<option value="GRC">Hellénique (Grèce)</option>
			<option value="HND">Hondurienne (Honduras)</option>
			<option value="HUN">Hongroise (Hongrie)</option>
			<option value="IND">Indienne (Inde)</option>
			<option value="IDN">Indonésienne (Indonésie)</option>
			<option value="IRQ">Irakienne (Iraq)</option>
			<option value="IRN">Iranienne (Iran)</option>
			<option value="IRL">Irlandaise (Irlande)</option>
			<option value="ISL">Islandaise (Islande)</option>
			<option value="ISR">Israélienne (Israël)</option>
			<option value="ITA">Italienne (Italie)</option>
			<option value="CIV">Ivoirienne (Côte d'Ivoire)</option>
			<option value="JAM">Jamaïcaine (Jamaïque)</option>
			<option value="JPN">Japonaise (Japon)</option>
			<option value="JOR">Jordanienne (Jordanie)</option>
			<option value="KAZ">Kazakhstanaise (Kazakhstan)</option>
			<option value="KEN">Kenyane (Kenya)</option>
			<option value="KGZ">Kirghize (Kirghizistan)</option>
			<option value="KIR">Kiribatienne (Kiribati)</option>
			<option value="KNA">Kittitienne et Névicienne (Saint-Christophe-et-Niévès)</option>
			<option value="KWT">Koweïtienne (Koweït)</option>
			<option value="LAO">Laotienne (Laos)</option>
			<option value="LSO">Lesothane (Lesotho)</option>
			<option value="LVA">Lettone (Lettonie)</option>
			<option value="LBN">Libanaise (Liban)</option>
			<option value="LBR">Libérienne (Libéria)</option>
			<option value="LBY">Libyenne (Libye)</option>
			<option value="LIE">Liechtensteinoise (Liechtenstein)</option>
			<option value="LTU">Lituanienne (Lituanie)</option>
			<option value="LUX">Luxembourgeoise (Luxembourg)</option>
			<option value="MKD">Macédonienne (Macédoine)</option>
			<option value="MYS">Malaisienne (Malaisie)</option>
			<option value="MWI">Malawienne (Malawi)</option>
			<option value="MDV">Maldivienne (Maldives)</option>
			<option value="MDG">Malgache (Madagascar)</option>
			<option value="MLI">Maliennes (Mali)</option>
			<option value="MLT">Maltaise (Malte)</option>
			<option value="MAR">Marocaine (Maroc)</option>
			<option value="MHL">Marshallaise (Îles Marshall)</option>
			<option value="MUS">Mauricienne (Maurice)</option>
			<option value="MRT">Mauritanienne (Mauritanie)</option>
			<option value="MEX">Mexicaine (Mexique)</option>
			<option value="FSM">Micronésienne (Micronésie)</option>
			<option value="MDA">Moldave (Moldovie)</option>
			<option value="MCO">Monegasque (Monaco)</option>
			<option value="MNG">Mongole (Mongolie)</option>
			<option value="MNE">Monténégrine (Monténégro)</option>
			<option value="MOZ">Mozambicaine (Mozambique)</option>
			<option value="NAM">Namibienne (Namibie)</option>
			<option value="NRU">Nauruane (Nauru)</option>
			<option value="NLD">Néerlandaise (Pays-Bas)</option>
			<option value="NZL">Néo-Zélandaise (Nouvelle-Zélande)</option>
			<option value="NPL">Népalaise (Népal)</option>
			<option value="NIC">Nicaraguayenne (Nicaragua)</option>
			<option value="NGA">Nigériane (Nigéria)</option>
			<option value="NER">Nigérienne (Niger)</option>
			<option value="NIU">Niuéenne (Niue)</option>
			<option value="PRK">Nord-coréenne (Corée du Nord)</option>
			<option value="NOR">Norvégienne (Norvège)</option>
			<option value="OMN">Omanaise (Oman)</option>
			<option value="UGA">Ougandaise (Ouganda)</option>
			<option value="UZB">Ouzbéke (Ouzbékistan)</option>
			<option value="PAK">Pakistanaise (Pakistan)</option>
			<option value="PLW">Palaosienne (Palaos)</option>
			<option value="PSE">Palestinienne (Palestine)</option>
			<option value="PAN">Panaméenne (Panama)</option>
			<option value="PNG">Papouane-Néo-Guinéenne (Papouasie-Nouvelle-Guinée)</option>
			<option value="PRY">Paraguayenne (Paraguay)</option>
			<option value="PER">Péruvienne (Pérou)</option>
			<option value="PHL">Philippine (Philippines)</option>
			<option value="POL">Polonaise (Pologne)</option>
			<option value="PRT">Portugaise (Portugal)</option>
			<option value="QAT">Qatarienne (Qatar)</option>
			<option value="ROU">Roumaine (Roumanie)</option>
			<option value="RUS">Russe (Russie)</option>
			<option value="RWA">Rwandaise (Rwanda)</option>
			<option value="LCA">Saint-Lucienne (Sainte-Lucie)</option>
			<option value="SMR">Saint-Marinaise (Saint-Marin)</option>
			<option value="VCT">Saint-Vincentaise et Grenadine (Saint-Vincent-et-les Grenadines)</option>
			<option value="SLB">Salomonaise (Îles Salomon)</option>
			<option value="SLV">Salvadorienne (Salvador)</option>
			<option value="WSM">Samoane (Samoa)</option>
			<option value="STP">Santoméenne (Sao Tomé-et-Principe)</option>
			<option value="SAU">Saoudienne (Arabie saoudite)</option>
			<option value="SEN">Sénégalaise (Sénégal)</option>
			<option value="SRB">Serbe (Serbie)</option>
			<option value="SYC">Seychelloise (Seychelles)</option>
			<option value="SLE">Sierra-Léonaise (Sierra Leone)</option>
			<option value="SGP">Singapourienne (Singapour)</option>
			<option value="SVK">Slovaque (Slovaquie)</option>
			<option value="SVN">Slovène (Slovénie)</option>
			<option value="SOM">Somalienne (Somalie)</option>
			<option value="SDN">Soudanaise (Soudan)</option>
			<option value="LKA">Sri-Lankaise (Sri Lanka)</option>
			<option value="ZAF">Sud-Africaine (Afrique du Sud)</option>
			<option value="KOR">Sud-Coréenne (Corée du Sud)</option>
			<option value="SSD">Sud-Soudanaise (Soudan du Sud)</option>
			<option value="SWE">Suédoise (Suède)</option>
			<option value="CHE">Suisse (Suisse)</option>
			<option value="SUR">Surinamaise (Suriname)</option>
			<option value="SWZ">Swazie (Swaziland)</option>
			<option value="SYR">Syrienne (Syrie)</option>
			<option value="TJK">Tadjike (Tadjikistan)</option>
			<option value="TZA">Tanzanienne (Tanzanie)</option>
			<option value="TCD">Tchadienne (Tchad)</option>
			<option value="CZE">Tchèque (Tchéquie)</option>
			<option value="THA">Thaïlandaise (Thaïlande)</option>
			<option value="TGO">Togolaise (Togo)</option>
			<option value="TON">Tonguienne (Tonga)</option>
			<option value="TTO">Trinidadienne (Trinité-et-Tobago)</option>
			<option value="TUN">Tunisienne (Tunisie)</option>
			<option value="TKM">Turkmène (Turkménistan)</option>
			<option value="TUR">Turque (Turquie)</option>
			<option value="TUV">Tuvaluane (Tuvalu)</option>
			<option value="UKR">Ukrainienne (Ukraine)</option>
			<option value="URY">Uruguayenne (Uruguay)</option>
			<option value="VUT">Vanuatuane (Vanuatu)</option>
			<option value="VAT">Vaticane (Vatican)</option>
			<option value="VEN">Vénézuélienne (Venezuela)</option>
			<option value="VNM">Vietnamienne (Viêt Nam)</option>
			<option value="YEM">Yéménite (Yémen)</option>
			<option value="ZMB">Zambienne (Zambie)</option>
			<option value="ZWE">Zimbabwéenne (Zimbabwe)</option>
		</select><br><br>
	
		<label>Numéro du document de voyage ou de la carte d’identité :</label><br>
		<input type="text" name="num_nationalite_famille"><br><br>
	
		<label>17. Lien de parenté avec un ressortissant de l’UE, de l’EEE ou de la Confédération suisse ou un ressortissant du Royaume-Uni bénéficiaire de l’accord de retrait du Royaume-Uni de l’UE, selon le cas :</label><br>
		<input type="checkbox" name="lien_parente" value="conjoint"> Conjoint
		<input type="checkbox" name="lien_parente" value="enfant"> Enfant
		<input type="checkbox" name="lien_parente" value="petit_fils"> Petit-fils ou petite-fille
		<input type="checkbox" name="lien_parente" value="ascendant"> Ascendant dépendant
		<input type="checkbox" name="lien_parente" value="partenariat"> Partenariat enregistré
		<input type="checkbox" name="lien_parente" value="autre"> Autre
		<input type="text" name="lien_parente_autre"><br><br>
	
		<label>18. Adresse du domicile du demandeur :<span class="required">*</span></label><br>
		<div id="adresse">
    		<input type="text" name="adresse_adresse" value="<?php echo esc_attr($saved_adresse_accueil); ?>"><br><br>
    		
    		<div style="display: flex; gap: 10px;justify-content: space-between;">
    		    <div>
    		        <label>Code postal</label>
    		        <input type="text" name="cp_adresse" value="<?php echo esc_attr($saved_cp_accueil); ?>">
    		    </div>
    		    <div>
    		        <label>Ville</label>
    		        <input type="text" name="ville_adresse" value="<?php echo esc_attr($saved_ville_accueil); ?>">
    		    </div>
    		    <div>
    		        <label>Pays</label>
    		        <select id="country" name="pays_adresse" value="<?php echo esc_attr($saved_pays_accueil); ?>">
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
    		    </div>
    		</div>
	    </div>
		<input type="text" name="adresse" style="display:none"><br><br>
	
		<label>Numéro de téléphone portable :<span class="required">*</span></label><br>
		<input type="text" name="phone" value="<?php echo esc_attr($saved_phone); ?>" required pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX"><br><br>
	
		<label>19. Résidence dans un pays autre que celui de la nationalité actuelle :<span class="required">*</span></label><br>
		<input type="radio" name="resident" value="non" required> Non<br>
		<input type="radio" name="resident" value="oui" required> Oui : Titre de séjour ou équivalent
		<input type="text" name="num_resident" id="num_resident">
		<input type="date" name="valid_resident" id="valid_resident"><br><br>
		
		<label>Situation professionnelle :<span class="required">*</span></label><br>
		<select name="situation_professionnelle">
          <option value="">-- Sélectionnez une situation professionnelle --</option>
          <option value="En activité" <?php selected($saved_situation_professionnelle, 'En activité'); ?>>En activité</option>
          <option value="Sans profession" <?php selected($saved_situation_professionnelle, 'Sans profession'); ?>>Sans profession</option>
          <option value="Chômeur" <?php selected($saved_situation_professionnelle, 'Chômeur'); ?>>Chômeur</option>
          <option value="Retraité" <?php selected($saved_situation_professionnelle, 'Retraité'); ?>>Retraité</option>
          <option value="Etudiant" <?php selected($saved_situation_professionnelle, 'Etudiant'); ?>>Etudiant</option>
        </select><br><br>
	
		<div id="profession">
				<label>20. Activité professionnelle <span class="required">*</span></label><br>
				<select name="profession" required>
						<option value="">&nbsp;</option>
						<option value="65001" <?php selected($saved_profession, '65001'); ?>>Agriculteur</option>
						<option value="65002" <?php selected($saved_profession, '65002'); ?>>Architecte</option>
						<option value="65003" <?php selected($saved_profession, '65003'); ?>>Artisan</option>
						<option value="65004" <?php selected($saved_profession, '65004'); ?>>Artiste</option>
						<option value="65005" <?php selected($saved_profession, '65005'); ?>>Autre</option>
						<option value="65006" <?php selected($saved_profession, '65006'); ?>>Autre technicien</option>
						<option value="66001" <?php selected($saved_profession, '66001'); ?>>Banquier</option>
						<option value="67001" <?php selected($saved_profession, '67001'); ?>>Cadre d'entreprise</option>
						<option value="67002" <?php selected($saved_profession, '67002'); ?>>Chauffeur, routier</option>
						<option value="67003" <?php selected($saved_profession, '67003'); ?>>Chef d'entreprise</option>
						<option value="67004" <?php selected($saved_profession, '67004'); ?>>Chercheur, scientifique</option>
						<option value="67005" <?php selected($saved_profession, '67005'); ?>>Chimiste</option>
						<option value="67006" <?php selected($saved_profession, '67006'); ?>>Chômeur</option>
						<option value="67007" <?php selected($saved_profession, '67007'); ?>>Clergé, religieux</option>
						<option value="67008" <?php selected($saved_profession, '67008'); ?>>Commerçant</option>
						<option value="68001" <?php selected($saved_profession, '68001'); ?>>Diplomate</option>
						<option value="69001" <?php selected($saved_profession, '69001'); ?>>Electronicien</option>
						<option value="69005" <?php selected($saved_profession, '69005'); ?>>Elève, Etudiant, stagiaire</option>
						<option value="69002" <?php selected($saved_profession, '69002'); ?>>Employé</option>
						<option value="69003" <?php selected($saved_profession, '69003'); ?>>Employé prive au service de diplomate</option>
						<option value="69004" <?php selected($saved_profession, '69004'); ?>>Enseignant</option>
						<option value="70001" <?php selected($saved_profession, '70001'); ?>>Fonctionnaire</option>
						<option value="72001" <?php selected($saved_profession, '72001'); ?>>Homme politique</option>
						<option value="73001" <?php selected($saved_profession, '73001'); ?>>Informaticien</option>
						<option value="74001" <?php selected($saved_profession, '74001'); ?>>Journaliste</option>
						<option value="77001" <?php selected($saved_profession, '77001'); ?>>Magistrat</option>
						<option value="77002" <?php selected($saved_profession, '77002'); ?>>Marin</option>
						<option value="77003" <?php selected($saved_profession, '77003'); ?>>Mode, cosmétique</option>
						<option value="79001" <?php selected($saved_profession, '79001'); ?>>Ouvrier</option>
						<option value="80001" <?php selected($saved_profession, '80001'); ?>>Personnel de service, administratif ou technique (postes dipl./cons.)</option>
						<option value="80002" <?php selected($saved_profession, '80002'); ?>>Policier, militaire</option>
						<option value="80003" <?php selected($saved_profession, '80003'); ?>>Profession juridique</option>
						<option value="80004" <?php selected($saved_profession, '80004'); ?>>Profession libérale</option>
						<option value="80005" <?php selected($saved_profession, '80005'); ?>>Profession médicale et paramédicale</option>
						<option value="82001" <?php selected($saved_profession, '82001'); ?>>Retraite</option>
						<option value="83001" <?php selected($saved_profession, '83001'); ?>>Sans profession</option>
						<option value="83002" <?php selected($saved_profession, '83002'); ?>>Sportif</option>
				</select><br><br>

				<label>Secteur d'activité :<span class="required">*</span></label><br>
				<select name="secteur_activite">
							<option value="">-- Sélectionnez un secteur d'activité --</option>
							<option value="Activités de services administratifs et de soutien" <?php selected($saved_secteur_activite, 'Activités de services administratifs et de soutien'); ?>>Activités de services administratifs et de soutien</option>
							<option value="Activités des ménages en tant qu'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre" <?php selected($saved_secteur_activite, 'Activités des ménages en tant qu\'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre'); ?>>
								Activités des ménages en tant qu'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre
							</option>
							<option value="Activités extra-territoriales" <?php selected($saved_secteur_activite, 'Activités extra-territoriales'); ?>>Activités extra-territoriales</option>
							<option value="Activités financières et d'assurance" <?php selected($saved_secteur_activite, 'Activités financières et d\'assurance'); ?>>Activités financières et d'assurance</option>
							<option value="Activités immobilières" <?php selected($saved_secteur_activite, 'Activités immobilières'); ?>>Activités immobilières</option>
							<option value="Activités spécialisées, scientifiques et techniques" <?php selected($saved_secteur_activite, 'Activités spécialisées, scientifiques et techniques'); ?>>Activités spécialisées, scientifiques et techniques</option>
							<option value="Administration publique" <?php selected($saved_secteur_activite, 'Administration publique'); ?>>Administration publique</option>
							<option value="Agriculture, sylviculture et pêche" <?php selected($saved_secteur_activite, 'Agriculture, sylviculture et pêche'); ?>>Agriculture, sylviculture et pêche</option>
							<option value="Arts, spectacles et activités récréatives" <?php selected($saved_secteur_activite, 'Arts, spectacles et activités récréatives'); ?>>Arts, spectacles et activités récréatives</option>
							<option value="Autres activités" <?php selected($saved_secteur_activite, 'Autres activités'); ?>>Autres activités</option>
							<option value="Autres activités de services" <?php selected($saved_secteur_activite, 'Autres activités de services'); ?>>Autres activités de services</option>
							<option value="Commerce; réparation d'automobiles et de motocycles" <?php selected($saved_secteur_activite, 'Commerce; réparation d\'automobiles et de motocycles'); ?>>Commerce; réparation d'automobiles et de motocycles</option>
							<option value="Construction" <?php selected($saved_secteur_activite, 'Construction'); ?>>Construction</option>
							<option value="Enseignement" <?php selected($saved_secteur_activite, 'Enseignement'); ?>>Enseignement</option>
							<option value="Hébergement et restauration" <?php selected($saved_secteur_activite, 'Hébergement et restauration'); ?>>Hébergement et restauration</option>
							<option value="Industrie manufacturière" <?php selected($saved_secteur_activite, 'Industrie manufacturière'); ?>>Industrie manufacturière</option>
							<option value="Industries extractives" <?php selected($saved_secteur_activite, 'Industries extractives'); ?>>Industries extractives</option>
							<option value="Information et communication" <?php selected($saved_secteur_activite, 'Information et communication'); ?>>Information et communication</option>
							<option value="Production et distribution d'eau; assainissement, gestion des déchets et dépollution" <?php selected($saved_secteur_activite, 'Production et distribution d\'eau; assainissement, gestion des déchets et dépollution'); ?>>
								Production et distribution d'eau; assainissement, gestion des déchets et dépollution
							</option>
							<option value="Production et distribution d'électricité, de gaz, de vapeur et d'air conditionné" <?php selected($saved_secteur_activite, 'Production et distribution d\'électricité, de gaz, de vapeur et d\'air conditionné'); ?>>
								Production et distribution d'électricité, de gaz, de vapeur et d'air conditionné
							</option>
							<option value="Santé humaine et action sociale" <?php selected($saved_secteur_activite, 'Santé humaine et action sociale'); ?>>Santé humaine et action sociale</option>
							<option value="Transports et entreposage" <?php selected($saved_secteur_activite, 'Transports et entreposage'); ?>>Transports et entreposage</option>
						</select><br><br>
    		
    		<label>Nom de l'employeur ou de l'établissement d'enseignement <span class="required">*</span></label><br>
    		<input type="text" name="nom_employeur" value="<?php echo esc_attr($saved_nom_employeur); ?>" required><br><br>
    		
    		<label>Adresse de l'employeur ou de l'établissement d'enseignement <span class="required">*</span></label><br>
    		<input type="text" name="adresse_employeur" value="<?php echo esc_attr($saved_adresse_employeur); ?>" required><br><br>
    		
    		<div style="display: flex; gap: 10px;justify-content: space-between;">
    		    <div>
    		        <label>Code postal</label><br>
    		        <input type="text" name="cp_employeur" value="<?php echo esc_attr($saved_cp_employeur); ?>">
    		    </div>
    		    <div>
    		        <label>Ville  <span class="required">*</span></label><br>
    		        <input type="text" name="ville_employeur" value="<?php echo esc_attr($saved_ville_employeur); ?>" required>
    		    </div>
    		    <div>
    		        <label>Pays ou territoire <span class="required">*</span></label><br>
    		        <select id="country" name="pays_employeur" value="<?php echo esc_attr($saved_pays_employeur); ?>" required>
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
    		    </div>
    		</div><br>
    		
    		<label>Numéro de téléphone portable :<span class="required">*</span></label><br>
    		<input type="text" name="num_employeur" value="<?php echo esc_attr($saved_num_employeur); ?>" required><br><br>
    		
    		<label>Adresse e-mail :<span class="required">*</span></label><br>
    		<input type="text" name="mail_employeur" value="<?php echo esc_attr($saved_mail_employeur); ?>" required><br><br>
	    </div>
	
		<label>21. Nom, adresse et Numéro de téléphone portable de l’employeur. Pour les étudiants, adresse de l’établissement d’enseignement : <span class="required">*</span></label><br>
		<textarea name="employeur" readonly><?php echo esc_textarea($saved_employeur); ?></textarea>
	
		<label>22. Objet(s) du voyage :<span class="required">*</span></label><br>
        <input type="radio" name="objet" value="etablissement_familial_prive"
            <?php checked($saved_objet, 'etablissement_familial_prive'); ?>>
            Établissement familial ou privé<br>
        
        <input type="radio" name="objet" value="etudes"
            <?php checked($saved_objet, 'etudes'); ?>>
            Études<br>
        
        <input type="radio" name="objet" value="medical"
            <?php checked($saved_objet, 'medical'); ?>>
            Raisons médicales<br>
        
        <input type="radio" name="objet" value="tourisme"
            <?php checked($saved_objet, 'tourisme'); ?>>
            Tourisme<br>
        
        <input type="radio" name="objet" value="travailler"
            <?php checked($saved_objet, 'travailler'); ?>>
            Travailler<br>
        
        <input type="radio" name="objet" value="accord_retrait"
            <?php checked($saved_objet, 'accord_retrait'); ?>>
            Visa d'entrée (accord de retrait)<br>
        
        <input type="radio" name="objet" value="visite_familiale_privee"
            <?php checked($saved_objet, 'visite_familiale_privee'); ?>>
            Visite familiale ou privée<br>
        
        <input type="radio" name="objet" value="visite_officielle"
            <?php checked($saved_objet, 'visite_officielle'); ?>>
            Visite officielle<br>

        <input type="radio" name="objet" value="autre" <?php checked($saved_objet, "autre"); ?>> Autre (à préciser) :
        <input type="text" name="objet_autre" value="<?php echo esc_attr($saved_objet_autre); ?>"><br><br>
		<label>23. Informations sur l'objet du voyage - le détail des informations sur l'objet de votre voyage favorisera vos chances d'obtention du visa. Indiquer impérativement vos liens avec votre pays d'origine ainsi que tous vos moyens de subsistance.</label><br>
		<textarea name="visa_info_objet_base" required><?php echo esc_textarea($info_objet_to_display); ?></textarea>
		<input type="hidden" name="visa_info_objet_base_sent" value="1"><br><br>

		<label>24. État membre de destination principale (et autres Etats membres de destination, le cas échéant) :<span class="required">*</span></label><br>
		<select name="etat_membre" id="schengen-country" required>
			<option value="">-- Sélectionnez un pays --</option>
			<option value="allemagne" <?php selected($saved_etat_membre, 'allemagne'); ?>>Allemagne</option>
			<option value="autriche" <?php selected($saved_etat_membre, 'autriche'); ?>>Autriche</option>
			<option value="belgique" <?php selected($saved_etat_membre, 'belgique'); ?>>Belgique</option>
			<option value="croatie" <?php selected($saved_etat_membre, 'croatie'); ?>>Croatie</option>
			<option value="danemark" <?php selected($saved_etat_membre, 'danemark'); ?>>Danemark</option>
			<option value="espagne" <?php selected($saved_etat_membre, 'espagne'); ?>>Espagne</option>
			<option value="estonie" <?php selected($saved_etat_membre, 'estonie'); ?>>Estonie</option>
			<option value="finlande" <?php selected($saved_etat_membre, 'finlande'); ?>>Finlande</option>
			<option value="france" <?php selected($saved_etat_membre, 'france'); ?>>France</option>
			<option value="grèce" <?php selected($saved_etat_membre, 'grèce'); ?>>Grèce</option>
			<option value="hongrie" <?php selected($saved_etat_membre, 'hongrie'); ?>>Hongrie</option>
			<option value="islande" <?php selected($saved_etat_membre, 'islande'); ?>>Islande</option>
			<option value="italie" <?php selected($saved_etat_membre, 'italie'); ?>>Italie</option>
			<option value="lettonie" <?php selected($saved_etat_membre, 'lettonie'); ?>>Lettonie</option>
			<option value="lituanie" <?php selected($saved_etat_membre, 'lituanie'); ?>>Lituanie</option>
			<option value="luxembourg" <?php selected($saved_etat_membre, 'luxembourg'); ?>>Luxembourg</option>
			<option value="malte" <?php selected($saved_etat_membre, 'malte'); ?>>Malte</option>
			<option value="norvège" <?php selected($saved_etat_membre, 'norvège'); ?>>Norvège</option>
			<option value="pays-bas" <?php selected($saved_etat_membre, 'pays-bas'); ?>>Pays-Bas</option>
			<option value="pologne" <?php selected($saved_etat_membre, 'pologne'); ?>>Pologne</option>
			<option value="portugal" <?php selected($saved_etat_membre, 'portugal'); ?>>Portugal</option>
			<option value="république-tchèque" <?php selected($saved_etat_membre, 'république-tchèque'); ?>>République tchèque</option>
			<option value="slovaquie" <?php selected($saved_etat_membre, 'slovaquie'); ?>>Slovaquie</option>
			<option value="slovénie" <?php selected($saved_etat_membre, 'slovénie'); ?>>Slovénie</option>
			<option value="suède" <?php selected($saved_etat_membre, 'suède'); ?>>Suède</option>
			<option value="suisse" <?php selected($saved_etat_membre, 'suisse'); ?>>Suisse</option>
		</select><br><br>
	
		<label>25. État membre de première entrée :<span class="required">*</span></label><br>
		<select name="etat_membre_1er_annee" id="etat_membre_1er_annee" required>
			<option value="">-- Sélectionnez un pays --</option>
			<option value="allemagne" <?php selected($saved_etat_membre_1er_annee, 'allemagne'); ?>>Allemagne</option>
			<option value="autriche" <?php selected($saved_etat_membre_1er_annee, 'autriche'); ?>>Autriche</option>
			<option value="belgique" <?php selected($saved_etat_membre_1er_annee, 'belgique'); ?>>Belgique</option>
			<option value="croatie" <?php selected($saved_etat_membre_1er_annee, 'croatie'); ?>>Croatie</option>
			<option value="danemark" <?php selected($saved_etat_membre_1er_annee, 'danemark'); ?>>Danemark</option>
			<option value="espagne" <?php selected($saved_etat_membre_1er_annee, 'espagne'); ?>>Espagne</option>
			<option value="estonie" <?php selected($saved_etat_membre_1er_annee, 'estonie'); ?>>Estonie</option>
			<option value="finlande" <?php selected($saved_etat_membre_1er_annee, 'finlande'); ?>>Finlande</option>
			<option value="france" <?php selected($saved_etat_membre_1er_annee, 'france'); ?>>France</option>
			<option value="grèce" <?php selected($saved_etat_membre_1er_annee, 'grèce'); ?>>Grèce</option>
			<option value="hongrie" <?php selected($saved_etat_membre_1er_annee, 'hongrie'); ?>>Hongrie</option>
			<option value="islande" <?php selected($saved_etat_membre_1er_annee, 'islande'); ?>>Islande</option>
			<option value="italie" <?php selected($saved_etat_membre_1er_annee, 'italie'); ?>>Italie</option>
			<option value="lettonie" <?php selected($saved_etat_membre_1er_annee, 'lettonie'); ?>>Lettonie</option>
			<option value="lituanie" <?php selected($saved_etat_membre_1er_annee, 'lituanie'); ?>>Lituanie</option>
			<option value="luxembourg" <?php selected($saved_etat_membre_1er_annee, 'luxembourg'); ?>>Luxembourg</option>
			<option value="malte" <?php selected($saved_etat_membre_1er_annee, 'malte'); ?>>Malte</option>
			<option value="norvège" <?php selected($saved_etat_membre_1er_annee, 'norvège'); ?>>Norvège</option>
			<option value="pays-bas" <?php selected($saved_etat_membre_1er_annee, 'pays-bas'); ?>>Pays-Bas</option>
			<option value="pologne" <?php selected($saved_etat_membre_1er_annee, 'pologne'); ?>>Pologne</option>
			<option value="portugal" <?php selected($saved_etat_membre_1er_annee, 'portugal'); ?>>Portugal</option>
			<option value="république-tchèque" <?php selected($saved_etat_membre_1er_annee, 'république-tchèque'); ?>>République tchèque</option>
			<option value="slovaquie" <?php selected($saved_etat_membre_1er_annee, 'slovaquie'); ?>>Slovaquie</option>
			<option value="slovénie" <?php selected($saved_etat_membre_1er_annee, 'slovénie'); ?>>Slovénie</option>
			<option value="suède" <?php selected($saved_etat_membre_1er_annee, 'suède'); ?>>Suède</option>
			<option value="suisse" <?php selected($saved_etat_membre_1er_annee, 'suisse'); ?>>Suisse</option>
		</select><br><br>

        <label>Calculateur de jours autorisés : Séjours effectués dans les 6 mois précédents le voyage envisagé et nombre de jours autorisés</label>
		<div id="date-ranges" style="display: flex; flex-direction: column; gap: 10px;"><br>

			<!-- Bloc initial de dates -->
			<div class="range-block" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
				<div style="flex:1; min-width:200px;">
				<label>Entrée</label>
				<input type="date" class="entry-date" max="<?php echo esc_attr($today); ?>">
				</div>
				<div style="flex:1; min-width:200px;">
				<label>Sortie</label>
				<input type="date" class="exit-date" max="<?php echo esc_attr($today); ?>">
				</div>
				<button type="button" class="remove-range button" style="display:none;">–</button>
			</div>

		</div><br>

		<button type="button" id="add-range" class="button" style="margin-top:10px;">+ Ajouter une période</button>

		<div id="days-result" style="padding:10px; background:#f5f5f5; border-radius:4px; margin-top:15px;">
		<strong>Total :</strong>
		<span id="calculated-days">0</span> jours autorisés
		</div><br>
	
		<label>26. Nombre d’entrées demandées :<span class="required">*</span></label><br>
		<input type="radio" name="nbr_entre" value="une_entree" <?php checked($saved_nbr_entre, "une_entree"); ?>> Une entrée<br>
		<input type="radio" name="nbr_entre" value="deux_entrees" <?php checked($saved_nbr_entre, "deux_entrees"); ?>> Deux entrées<br>
		<input type="radio" name="nbr_entre" value="entrees_multiples" <?php checked($saved_nbr_entre, "entrees_multiples"); ?>> Entrées multiples <br><br>

<label>27b. Nombre de voyages envisagés dans l'année à venir :</label><br>
<input type="number" name="nombre_voyages_annee" value="<?php echo esc_attr($saved_nombre_voyages_annee); ?>" min="0" style="width:100px;"><br><br>
	
		<label>Date d’arrivée prévue pour le 1er séjour envisagé dans l’espace Schengen :<span class="required">*</span></label><br>
		<input type="date" name="arrival_date" value="<?php echo esc_attr($saved_arrival_date); ?>" required><br>
	
		<label>Date de départ prévue de l’espace Schengen après le 1er séjour envisagé :<span class="required">*</span></label><br>
		<input type="date" name="departure_date" value="<?php echo esc_attr($saved_departure_date); ?>" required><br><br>
	
		<hr <?php if ($age > 18) echo 'style="display:none;"'; ?>>

		<div id="autorite-parentale" <?php if ($age > 18) echo 'style="display:none;"'; ?>>
			<div class="preamble-notice">
				Parent n°1 ou Tuteur légal n°1<br>
				<label>Statut</label><br>
			<select name="statut_tuteur_legal_1">
				<option value="">-- Sélectionner un statut si vous êtes concerné --</option>
				<option value="Apatride" <?php selected($saved_statut_tuteur_legal_1, 'Apatride'); ?>>Apatride</option>
				<option value="Réfugié 1946/51" <?php selected($saved_statut_tuteur_legal_1, 'Réfugié 1946/51'); ?>>Réfugié 1946/51</option>
				<option value="Réfugié hs conv" <?php selected($saved_statut_tuteur_legal_1, 'Réfugié hs conv'); ?>>Réfugié hs conv</option>
			</select><br><br>
			</div>
		    <div style="display: flex; gap: 10px;justify-content: space-between;">
		        <div>
		            <label>Nom</label>
		            <input type="text" name="nom_tuteur_legal_1" value="<?php echo esc_attr($saved_nom_tuteur_legal_1); ?>" required>
		        </div>
		        <div>
		            <label>Prénom</label>
		            <input type="text" name="prenom_tuteur_legal_1" value="<?php echo esc_attr($saved_prenom_tuteur_legal_1); ?>">
		        </div>
		    </div>
    		
    		<label>Adresse</label><br>
    		<input type="text" name="adresse_tuteur_legal_1" value="<?php echo esc_attr($saved_adresse_tuteur_legal_1); ?>" required><br><br>
    		
    		<div style="display: flex; gap: 10px;justify-content: space-between;">
    		    <div>
    		        <label>Code postal</label>
    		        <input type="text" name="code_postal_tuteur_legal_1" value="<?php echo esc_attr($saved_code_postal_tuteur_legal_1); ?>">
    		    </div>
    		    <div>
    		        <label>Ville</label>
    		        <input type="text" name="ville_tuteur_legal_1" value="<?php echo esc_attr($saved_ville_tuteur_legal_1); ?>" required>
    		    </div>
    		    <div>
    		        <label>Pays</label>
    		            <select id="country_contact" name="pays_tuteur_legal_1" value="<?php echo esc_attr($saved_pays_tuteur_legal_1); ?>" required>
                            <option value="">-- Sélectionnez un pays --</option>
                            <option value="Afghanistan">Afghanistan</option>
                            <option value="Afrique du Sud">Afrique du Sud</option>
                            <option value="Albanie">Albanie</option>
                            <option value="Algérie">Algérie</option>
                            <option value="Allemagne">Allemagne</option>
                            <option value="Andorre">Andorre</option>
                            <option value="Angola">Angola</option>
                            <option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
                            <option value="Arabie Saoudite">Arabie Saoudite</option>
                            <option value="Argentine">Argentine</option>
                            <option value="Arménie">Arménie</option>
                            <option value="Australie">Australie</option>
                            <option value="Autriche">Autriche</option>
                            <option value="Azerbaïdjan">Azerbaïdjan</option>
                            <option value="Bahamas">Bahamas</option>
                            <option value="Bahreïn">Bahreïn</option>
                            <option value="Bangladesh">Bangladesh</option>
                            <option value="Barbade">Barbade</option>
                            <option value="Belgique">Belgique</option>
                            <option value="Belize">Belize</option>
                            <option value="Bénin">Bénin</option>
                            <option value="Bhoutan">Bhoutan</option>
                            <option value="Biélorussie">Biélorussie</option>
                            <option value="Birmanie">Birmanie</option>
                            <option value="Bolivie">Bolivie</option>
                            <option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
                            <option value="Botswana">Botswana</option>
                            <option value="Brésil">Brésil</option>
                            <option value="Brunei">Brunei</option>
                            <option value="Bulgarie">Bulgarie</option>
                            <option value="Burkina Faso">Burkina Faso</option>
                            <option value="Burundi">Burundi</option>
                            <option value="Cabo Verde">Cabo Verde</option>
                            <option value="Cambodge">Cambodge</option>
                            <option value="Cameroun">Cameroun</option>
                            <option value="Canada">Canada</option>
                            <option value="République centrafricaine">République centrafricaine</option>
                            <option value="Tchad">Tchad</option>
                            <option value="Chili">Chili</option>
                            <option value="Chine">Chine</option>
                            <option value="Chypre">Chypre</option>
                            <option value="Colombie">Colombie</option>
                            <option value="Comores">Comores</option>
                            <option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
                            <option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
                            <option value="Corée du Nord">Corée du Nord</option>
                            <option value="Corée du Sud">Corée du Sud</option>
                            <option value="Costa Rica">Costa Rica</option>
                            <option value="Côte d’Ivoire">Côte d’Ivoire</option>
                            <option value="Croatie">Croatie</option>
                            <option value="Cuba">Cuba</option>
                            <option value="Danemark">Danemark</option>
                            <option value="Djibouti">Djibouti</option>
                            <option value="Dominique">Dominique</option>
                            <option value="République dominicaine">République dominicaine</option>
                            <option value="Egypte">Égypte</option>
                            <option value="Emirats arabes unis">Émirats arabes unis</option>
                            <option value="Equateur">Équateur</option>
                            <option value="Erythrée">Érythrée</option>
                            <option value="Espagne">Espagne</option>
                            <option value="Estonie">Estonie</option>
                            <option value="Eswatini">Eswatini</option>
                            <option value="Etats-Unis">États-Unis</option>
                            <option value="Ethiopie">Éthiopie</option>
                            <option value="Fidji">Fidji</option>
                            <option value="Finlande">Finlande</option>
                            <option value="France">France</option>
                            <option value="Gabon">Gabon</option>
                            <option value="Gambie">Gambie</option>
                            <option value="Géorgie">Géorgie</option>
                            <option value="Ghana">Ghana</option>
                            <option value="Grèce">Grèce</option>
                            <option value="Grenade">Grenade</option>
                            <option value="Guatemala">Guatemala</option>
                            <option value="Guinée">Guinée</option>
                            <option value="Guinée-Bissau">Guinée-Bissau</option>
                            <option value="Guinée équatoriale">Guinée équatoriale</option>
                            <option value="Guyana">Guyana</option>
                            <option value="Haïti">Haïti</option>
                            <option value="Honduras">Honduras</option>
                            <option value="Hongrie">Hongrie</option>
                            <option value="Inde">Inde</option>
                            <option value="Indonésie">Indonésie</option>
                            <option value="Irak">Irak</option>
                            <option value="Iran">Iran</option>
                            <option value="Irlande">Irlande</option>
                            <option value="Islande">Islande</option>
                            <option value="Israël">Israël</option>
                            <option value="Italie">Italie</option>
                            <option value="Jamaïque">Jamaïque</option>
                            <option value="Japon">Japon</option>
                            <option value="Jordanie">Jordanie</option>
                            <option value="Kazakhstan">Kazakhstan</option>
                            <option value="Kenya">Kenya</option>
                            <option value="Kirghizistan">Kirghizistan</option>
                            <option value="Kiribati">Kiribati</option>
                            <option value="Kosovo">Kosovo</option>
                            <option value="Koweït">Koweït</option>
                            <option value="Laos">Laos</option>
                            <option value="Lettonie">Lettonie</option>
                            <option value="Liban">Liban</option>
                            <option value="Libéria">Libéria</option>
                            <option value="Libye">Libye</option>
                            <option value="Liechtenstein">Liechtenstein</option>
                            <option value="Lituanie">Lituanie</option>
                            <option value="Luxembourg">Luxembourg</option>
                            <option value="Macédoine du Nord">Macédoine du Nord</option>
                            <option value="Madagascar">Madagascar</option>
                            <option value="Malaisie">Malaisie</option>
                            <option value="Malawi">Malawi</option>
                            <option value="Maldives">Maldives</option>
                            <option value="Mali">Mali</option>
                            <option value="Malte">Malte</option>
                            <option value="Maroc">Maroc</option>
                            <option value="Marshall">Îles Marshall</option>
                            <option value="Maurice">Maurice</option>
                            <option value="Mauritanie">Mauritanie</option>
                            <option value="Mexique">Mexique</option>
                            <option value="Micronésie">Micronésie</option>
                            <option value="Moldavie">Moldavie</option>
                            <option value="Monaco">Monaco</option>
                            <option value="Mongolie">Mongolie</option>
                            <option value="Monténégro">Monténégro</option>
                            <option value="Mozambique">Mozambique</option>
                            <option value="Namibie">Namibie</option>
                            <option value="Nauru">Nauru</option>
                            <option value="Népal">Népal</option>
                            <option value="Nicaragua">Nicaragua</option>
                            <option value="Niger">Niger</option>
                            <option value="Nigéria">Nigéria</option>
                            <option value="Norvège">Norvège</option>
                            <option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
                            <option value="Oman">Oman</option>
                            <option value="Ouganda">Ouganda</option>
                            <option value="Ouzbékistan">Ouzbékistan</option>
                            <option value="Pakistan">Pakistan</option>
                            <option value="Palaos">Palaos</option>
                            <option value="Palestine">Palestine</option>
                            <option value="Panama">Panama</option>
                            <option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
                            <option value="Paraguay">Paraguay</option>
                            <option value="Pays-Bas">Pays-Bas</option>
                            <option value="Pérou">Pérou</option>
                            <option value="Philippines">Philippines</option>
                            <option value="Pologne">Pologne</option>
                            <option value="Portugal">Portugal</option>
                            <option value="République centrafricaine">République centrafricaine</option>
                            <option value="République dominicaine">République dominicaine</option>
                            <option value="Roumanie">Roumanie</option>
                            <option value="Royaume-Uni">Royaume-Uni</option>
                            <option value="Russie">Russie</option>
                            <option value="Rwanda">Rwanda</option>
                            <option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
                            <option value="Saint-Marin">Saint-Marin</option>
                            <option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
                            <option value="Sainte-Lucie">Sainte-Lucie</option>
                            <option value="Salvador">Salvador</option>
                            <option value="Samoa">Samoa</option>
                            <option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
                            <option value="Sénégal">Sénégal</option>
                            <option value="Serbie">Serbie</option>
                            <option value="Seychelles">Seychelles</option>
                            <option value="Sierra Leone">Sierra Leone</option>
                            <option value="Singapour">Singapour</option>
                            <option value="Slovaquie">Slovaquie</option>
                            <option value="Slovénie">Slovénie</option>
                            <option value="Somalie">Somalie</option>
                            <option value="Soudan">Soudan</option>
                            <option value="Soudan du Sud">Soudan du Sud</option>
                            <option value="Sri Lanka">Sri Lanka</option>
                            <option value="Suède">Suède</option>
                            <option value="Suisse">Suisse</option>
                            <option value="Suriname">Suriname</option>
                            <option value="Syrie">Syrie</option>
                            <option value="Tadjikistan">Tadjikistan</option>
                            <option value="Tanzanie">Tanzanie</option>
                            <option value="Tchad">Tchad</option>
                            <option value="Tchécoslovaquie">Tchéquie</option>
                            <option value="Thaïlande">Thaïlande</option>
                            <option value="Timor-Leste">Timor-Leste</option>
                            <option value="Togo">Togo</option>
                            <option value="Tonga">Tonga</option>
                            <option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
                            <option value="Tunisie">Tunisie</option>
                            <option value="Turkménistan">Turkménistan</option>
                            <option value="Turquie">Turquie</option>
                            <option value="Tuvalu">Tuvalu</option>
                            <option value="Ukraine">Ukraine</option>
                            <option value="Uruguay">Uruguay</option>
                            <option value="Vanuatu">Vanuatu</option>
                            <option value="Vatican">Vatican</option>
                            <option value="Venezuela">Venezuela</option>
                            <option value="Vietnam">Vietnam</option>
                            <option value="Yémen">Yémen</option>
                            <option value="Zambie">Zambie</option>
                            <option value="Zimbabwe">Zimbabwe</option>
                        </select>
    		    </div>
    		</div><br>
    		
    		<label>Numéro de téléphone portable :</label><br>
    		<input type="text" name="telephone_tuteur_legal_1" value="<?php echo esc_attr($saved_telephone_tuteur_legal_1); ?>" pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX" required><br><br>
    		
    		<label>Adresse e-mail :</label><br>
    		<input type="text" name="email_tuteur_legal_1" value="<?php echo esc_attr($saved_email_tuteur_legal_1); ?>" required><br><br>
			<label>Nationalité actuelle :<span class="required">*</span></label><br>
                    <select name="nationalite_tuteur_legal_1" required>
                        <option value="">-- Sélectionnez une nationalité --</option>
                        <option value="AFG" <?php selected($saved_nationalite_tuteur_legal_1, 'AFG'); ?>>Afghane (Afghanistan)</option>
                        <option value="ALB" <?php selected($saved_nationalite_tuteur_legal_1, 'ALB'); ?>>Albanaise (Albanie)</option>
                        <option value="DZA" <?php selected($saved_nationalite_tuteur_legal_1, 'DZA'); ?>>Algérienne (Algérie)</option>
                        <option value="DEU" <?php selected($saved_nationalite_tuteur_legal_1, 'DEU'); ?>>Allemande (Allemagne)</option>
                        <option value="USA" <?php selected($saved_nationalite_tuteur_legal_1, 'USA'); ?>>Americaine (États-Unis)</option>
                        <option value="AND" <?php selected($saved_nationalite_tuteur_legal_1, 'AND'); ?>>Andorrane (Andorre)</option>
                        <option value="AGO" <?php selected($saved_nationalite_tuteur_legal_1, 'AGO'); ?>>Angolaise (Angola)</option>
                        <option value="ATG" <?php selected($saved_nationalite_tuteur_legal_1, 'ATG'); ?>>Antiguaise-et-Barbudienne (Antigua-et-Barbuda)</option>
                        <option value="ARG" <?php selected($saved_nationalite_tuteur_legal_1, 'ARG'); ?>>Argentine (Argentine)</option>
                        <option value="ARM" <?php selected($saved_nationalite_tuteur_legal_1, 'ARM'); ?>>Armenienne (Arménie)</option>
                        <option value="AUS" <?php selected($saved_nationalite_tuteur_legal_1, 'AUS'); ?>>Australienne (Australie)</option>
                        <option value="AUT" <?php selected($saved_nationalite_tuteur_legal_1, 'AUT'); ?>>Autrichienne (Autriche)</option>
                        <option value="AZE" <?php selected($saved_nationalite_tuteur_legal_1, 'AZE'); ?>>Azerbaïdjanaise (Azerbaïdjan)</option>
                        <option value="BHS" <?php selected($saved_nationalite_tuteur_legal_1, 'BHS'); ?>>Bahamienne (Bahamas)</option>
                        <option value="BHR" <?php selected($saved_nationalite_tuteur_legal_1, 'BHR'); ?>>Bahreinienne (Bahreïn)</option>
                        <option value="BGD" <?php selected($saved_nationalite_tuteur_legal_1, 'BGD'); ?>>Bangladaise (Bangladesh)</option>
                        <option value="BRB" <?php selected($saved_nationalite_tuteur_legal_1, 'BRB'); ?>>Barbadienne (Barbade)</option>
                        <option value="BEL" <?php selected($saved_nationalite_tuteur_legal_1, 'BEL'); ?>>Belge (Belgique)</option>
                        <option value="BLZ" <?php selected($saved_nationalite_tuteur_legal_1, 'BLZ'); ?>>Belizienne (Belize)</option>
                        <option value="BEN" <?php selected($saved_nationalite_tuteur_legal_1, 'BEN'); ?>>Béninoise (Bénin)</option>
                        <option value="BTN" <?php selected($saved_nationalite_tuteur_legal_1, 'BTN'); ?>>Bhoutanaise (Bhoutan)</option>
                        <option value="BLR" <?php selected($saved_nationalite_tuteur_legal_1, 'BLR'); ?>>Biélorusse (Biélorussie)</option>
                        <option value="MMR" <?php selected($saved_nationalite_tuteur_legal_1, 'MMR'); ?>>Birmane (Birmanie)</option>
                        <option value="GNB" <?php selected($saved_nationalite_tuteur_legal_1, 'GNB'); ?>>Bissau-Guinéenne (Guinée-Bissau)</option>
                        <option value="BOL" <?php selected($saved_nationalite_tuteur_legal_1, 'BOL'); ?>>Bolivienne (Bolivie)</option>
                        <option value="BIH" <?php selected($saved_nationalite_tuteur_legal_1, 'BIH'); ?>>Bosnienne (Bosnie-Herzégovine)</option>
                        <option value="BWA" <?php selected($saved_nationalite_tuteur_legal_1, 'BWA'); ?>>Botswanaise (Botswana)</option>
                        <option value="BRA" <?php selected($saved_nationalite_tuteur_legal_1, 'BRA'); ?>>Brésilienne (Brésil)</option>
                        <option value="GBR" <?php selected($saved_nationalite_tuteur_legal_1, 'GBR'); ?>>Britannique (Royaume-Uni)</option>
                        <option value="BRN" <?php selected($saved_nationalite_tuteur_legal_1, 'BRN'); ?>>Brunéienne (Brunéi)</option>
                        <option value="BGR" <?php selected($saved_nationalite_tuteur_legal_1, 'BGR'); ?>>Bulgare (Bulgarie)</option>
                        <option value="BFA" <?php selected($saved_nationalite_tuteur_legal_1, 'BFA'); ?>>Burkinabée (Burkina)</option>
                        <option value="BDI" <?php selected($saved_nationalite_tuteur_legal_1, 'BDI'); ?>>Burundaise (Burundi)</option>
                        <option value="KHM" <?php selected($saved_nationalite_tuteur_legal_1, 'KHM'); ?>>Cambodgienne (Cambodge)</option>
                        <option value="CMR" <?php selected($saved_nationalite_tuteur_legal_1, 'CMR'); ?>>Camerounaise (Cameroun)</option>
                        <option value="CAN" <?php selected($saved_nationalite_tuteur_legal_1, 'CAN'); ?>>Canadienne (Canada)</option>
                        <option value="CPV" <?php selected($saved_nationalite_tuteur_legal_1, 'CPV'); ?>>Cap-verdienne (Cap-Vert)</option>
                        <option value="CAF" <?php selected($saved_nationalite_tuteur_legal_1, 'CAF'); ?>>Centrafricaine (Centrafrique)</option>
                        <option value="CHL" <?php selected($saved_nationalite_tuteur_legal_1, 'CHL'); ?>>Chilienne (Chili)</option>
                        <option value="CHN" <?php selected($saved_nationalite_tuteur_legal_1, 'CHN'); ?>>Chinoise (Chine)</option>
                        <option value="CYP" <?php selected($saved_nationalite_tuteur_legal_1, 'CYP'); ?>>Chypriote (Chypre)</option>
                        <option value="COL" <?php selected($saved_nationalite_tuteur_legal_1, 'COL'); ?>>Colombienne (Colombie)</option>
                        <option value="COM" <?php selected($saved_nationalite_tuteur_legal_1, 'COM'); ?>>Comorienne (Comores)</option>
                        <option value="COG" <?php selected($saved_nationalite_tuteur_legal_1, 'COG'); ?>>Congolaise (Congo-Brazzaville)</option>
                        <option value="COD" <?php selected($saved_nationalite_tuteur_legal_1, 'COD'); ?>>Congolaise (Congo-Kinshasa)</option>
                        <option value="COK" <?php selected($saved_nationalite_tuteur_legal_1, 'COK'); ?>>Cookienne (Îles Cook)</option>
                        <option value="CRI" <?php selected($saved_nationalite_tuteur_legal_1, 'CRI'); ?>>Costaricaine (Costa Rica)</option>
                        <option value="HRV" <?php selected($saved_nationalite_tuteur_legal_1, 'HRV'); ?>>Croate (Croatie)</option>
                        <option value="CUB" <?php selected($saved_nationalite_tuteur_legal_1, 'CUB'); ?>>Cubaine (Cuba)</option>
                        <option value="DNK" <?php selected($saved_nationalite_tuteur_legal_1, 'DNK'); ?>>Danoise (Danemark)</option>
                        <option value="DJI" <?php selected($saved_nationalite_tuteur_legal_1, 'DJI'); ?>>Djiboutienne (Djibouti)</option>
                        <option value="DOM" <?php selected($saved_nationalite_tuteur_legal_1, 'DOM'); ?>>Dominicaine (République dominicaine)</option>
                        <option value="DMA" <?php selected($saved_nationalite_tuteur_legal_1, 'DMA'); ?>>Dominiquaise (Dominique)</option>
                        <option value="EGY" <?php selected($saved_nationalite_tuteur_legal_1, 'EGY'); ?>>Égyptienne (Égypte)</option>
                        <option value="ARE" <?php selected($saved_nationalite_tuteur_legal_1, 'ARE'); ?>>Émirienne (Émirats arabes unis)</option>
                        <option value="GNQ" <?php selected($saved_nationalite_tuteur_legal_1, 'GNQ'); ?>>Équato-guineenne (Guinée équatoriale)</option>
                        <option value="ECU" <?php selected($saved_nationalite_tuteur_legal_1, 'ECU'); ?>>Équatorienne (Équateur)</option>
                        <option value="ERI" <?php selected($saved_nationalite_tuteur_legal_1, 'ERI'); ?>>Érythréenne (Érythrée)</option>
                        <option value="ESP" <?php selected($saved_nationalite_tuteur_legal_1, 'ESP'); ?>>Espagnole (Espagne)</option>
                        <option value="TLS" <?php selected($saved_nationalite_tuteur_legal_1, 'TLS'); ?>>Est-timoraise (Timor-Leste)</option>
                        <option value="EST" <?php selected($saved_nationalite_tuteur_legal_1, 'EST'); ?>>Estonienne (Estonie)</option>
                        <option value="ETH" <?php selected($saved_nationalite_tuteur_legal_1, 'ETH'); ?>>Éthiopienne (Éthiopie)</option>
                        <option value="FJI" <?php selected($saved_nationalite_tuteur_legal_1, 'FJI'); ?>>Fidjienne (Fidji)</option>
                        <option value="FIN" <?php selected($saved_nationalite_tuteur_legal_1, 'FIN'); ?>>Finlandaise (Finlande)</option>
                        <option value="FRA" <?php selected($saved_nationalite_tuteur_legal_1, 'FRA'); ?>>Française (France)</option>
                        <option value="GAB" <?php selected($saved_nationalite_tuteur_legal_1, 'GAB'); ?>>Gabonaise (Gabon)</option>
                        <option value="GMB" <?php selected($saved_nationalite_tuteur_legal_1, 'GMB'); ?>>Gambienne (Gambie)</option>
                        <option value="GEO" <?php selected($saved_nationalite_tuteur_legal_1, 'GEO'); ?>>Georgienne (Géorgie)</option>
                        <option value="GHA" <?php selected($saved_nationalite_tuteur_legal_1, 'GHA'); ?>>Ghanéenne (Ghana)</option>
                        <option value="GRD" <?php selected($saved_nationalite_tuteur_legal_1, 'GRD'); ?>>Grenadienne (Grenade)</option>
                        <option value="GTM" <?php selected($saved_nationalite_tuteur_legal_1, 'GTM'); ?>>Guatémaltèque (Guatemala)</option>
                        <option value="GIN" <?php selected($saved_nationalite_tuteur_legal_1, 'GIN'); ?>>Guinéenne (Guinée)</option>
                        <option value="GUY" <?php selected($saved_nationalite_tuteur_legal_1, 'GUY'); ?>>Guyanienne (Guyana)</option>
                        <option value="HTI" <?php selected($saved_nationalite_tuteur_legal_1, 'HTI'); ?>>Haïtienne (Haïti)</option>
                        <option value="GRC" <?php selected($saved_nationalite_tuteur_legal_1, 'GRC'); ?>>Hellénique (Grèce)</option>
                        <option value="HND" <?php selected($saved_nationalite_tuteur_legal_1, 'HND'); ?>>Hondurienne (Honduras)</option>
                        <option value="HUN" <?php selected($saved_nationalite_tuteur_legal_1, 'HUN'); ?>>Hongroise (Hongrie)</option>
                        <option value="IND" <?php selected($saved_nationalite_tuteur_legal_1, 'IND'); ?>>Indienne (Inde)</option>
                        <option value="IDN" <?php selected($saved_nationalite_tuteur_legal_1, 'IDN'); ?>>Indonésienne (Indonésie)</option>
                        <option value="IRQ" <?php selected($saved_nationalite_tuteur_legal_1, 'IRQ'); ?>>Irakienne (Iraq)</option>
                        <option value="IRN" <?php selected($saved_nationalite_tuteur_legal_1, 'IRN'); ?>>Iranienne (Iran)</option>
                        <option value="IRL" <?php selected($saved_nationalite_tuteur_legal_1, 'IRL'); ?>>Irlandaise (Irlande)</option>
                        <option value="ISL" <?php selected($saved_nationalite_tuteur_legal_1, 'ISL'); ?>>Islandaise (Islande)</option>
                        <option value="ISR" <?php selected($saved_nationalite_tuteur_legal_1, 'ISR'); ?>>Israélienne (Israël)</option>
                        <option value="ITA" <?php selected($saved_nationalite_tuteur_legal_1, 'ITA'); ?>>Italienne (Italie)</option>
                        <option value="CIV" <?php selected($saved_nationalite_tuteur_legal_1, 'CIV'); ?>>Ivoirienne (Côte d'Ivoire)</option>
                        <option value="JAM" <?php selected($saved_nationalite_tuteur_legal_1, 'JAM'); ?>>Jamaïcaine (Jamaïque)</option>
                        <option value="JPN" <?php selected($saved_nationalite_tuteur_legal_1, 'JPN'); ?>>Japonaise (Japon)</option>
                        <option value="JOR" <?php selected($saved_nationalite_tuteur_legal_1, 'JOR'); ?>>Jordanienne (Jordanie)</option>
                        <option value="KAZ" <?php selected($saved_nationalite_tuteur_legal_1, 'KAZ'); ?>>Kazakhstanaise (Kazakhstan)</option>
                        <option value="KEN" <?php selected($saved_nationalite_tuteur_legal_1, 'KEN'); ?>>Kenyane (Kenya)</option>
                        <option value="KGZ" <?php selected($saved_nationalite_tuteur_legal_1, 'KGZ'); ?>>Kirghize (Kirghizistan)</option>
                        <option value="KIR" <?php selected($saved_nationalite_tuteur_legal_1, 'KIR'); ?>>Kiribatienne (Kiribati)</option>
                        <option value="KNA" <?php selected($saved_nationalite_tuteur_legal_1, 'KNA'); ?>>Kittitienne et Névicienne (Saint-Christophe-et-Niévès)</option>
                        <option value="KWT" <?php selected($saved_nationalite_tuteur_legal_1, 'KWT'); ?>>Koweïtienne (Koweït)</option>
                        <option value="LAO" <?php selected($saved_nationalite_tuteur_legal_1, 'LAO'); ?>>Laotienne (Laos)</option>
                        <option value="LSO" <?php selected($saved_nationalite_tuteur_legal_1, 'LSO'); ?>>Lesothane (Lesotho)</option>
                        <option value="LVA" <?php selected($saved_nationalite_tuteur_legal_1, 'LVA'); ?>>Lettone (Lettonie)</option>
                        <option value="LBN" <?php selected($saved_nationalite_tuteur_legal_1, 'LBN'); ?>>Libanaise (Liban)</option>
                        <option value="LBR" <?php selected($saved_nationalite_tuteur_legal_1, 'LBR'); ?>>Libérienne (Libéria)</option>
                        <option value="LBY" <?php selected($saved_nationalite_tuteur_legal_1, 'LBY'); ?>>Libyenne (Libye)</option>
                        <option value="LIE" <?php selected($saved_nationalite_tuteur_legal_1, 'LIE'); ?>>Liechtensteinoise (Liechtenstein)</option>
                        <option value="LTU" <?php selected($saved_nationalite_tuteur_legal_1, 'LTU'); ?>>Lituanienne (Lituanie)</option>
                        <option value="LUX" <?php selected($saved_nationalite_tuteur_legal_1, 'LUX'); ?>>Luxembourgeoise (Luxembourg)</option>
                        <option value="MKD" <?php selected($saved_nationalite_tuteur_legal_1, 'MKD'); ?>>Macédonienne (Macédoine)</option>
                        <option value="MYS" <?php selected($saved_nationalite_tuteur_legal_1, 'MYS'); ?>>Malaisienne (Malaisie)</option>
                        <option value="MWI" <?php selected($saved_nationalite_tuteur_legal_1, 'MWI'); ?>>Malawienne (Malawi)</option>
                        <option value="MDV" <?php selected($saved_nationalite_tuteur_legal_1, 'MDV'); ?>>Maldivienne (Maldives)</option>
                        <option value="MDG" <?php selected($saved_nationalite_tuteur_legal_1, 'MDG'); ?>>Malgache (Madagascar)</option>
                        <option value="MLI" <?php selected($saved_nationalite_tuteur_legal_1, 'MLI'); ?>>Maliennes (Mali)</option>
                        <option value="MLT" <?php selected($saved_nationalite_tuteur_legal_1, 'MLT'); ?>>Maltaise (Malte)</option>
                        <option value="MAR" <?php selected($saved_nationalite_tuteur_legal_1, 'MAR'); ?>>Marocaine (Maroc)</option>
                        <option value="MHL" <?php selected($saved_nationalite_tuteur_legal_1, 'MHL'); ?>>Marshallaise (Îles Marshall)</option>
                        <option value="MUS" <?php selected($saved_nationalite_tuteur_legal_1, 'MUS'); ?>>Mauricienne (Maurice)</option>
                        <option value="MRT" <?php selected($saved_nationalite_tuteur_legal_1, 'MRT'); ?>>Mauritanienne (Mauritanie)</option>
                        <option value="MEX" <?php selected($saved_nationalite_tuteur_legal_1, 'MEX'); ?>>Mexicaine (Mexique)</option>
                        <option value="FSM" <?php selected($saved_nationalite_tuteur_legal_1, 'FSM'); ?>>Micronésienne (Micronésie)</option>
                        <option value="MDA" <?php selected($saved_nationalite_tuteur_legal_1, 'MDA'); ?>>Moldave (Moldovie)</option>
                        <option value="MCO" <?php selected($saved_nationalite_tuteur_legal_1, 'MCO'); ?>>Monegasque (Monaco)</option>
                        <option value="MNG" <?php selected($saved_nationalite_tuteur_legal_1, 'MNG'); ?>>Mongole (Mongolie)</option>
                        <option value="MNE" <?php selected($saved_nationalite_tuteur_legal_1, 'MNE'); ?>>Monténégrine (Monténégro)</option>
                        <option value="MOZ" <?php selected($saved_nationalite_tuteur_legal_1, 'MOZ'); ?>>Mozambicaine (Mozambique)</option>
                        <option value="NAM" <?php selected($saved_nationalite_tuteur_legal_1, 'NAM'); ?>>Namibienne (Namibie)</option>
                        <option value="NRU" <?php selected($saved_nationalite_tuteur_legal_1, 'NRU'); ?>>Nauruane (Nauru)</option>
                        <option value="NLD" <?php selected($saved_nationalite_tuteur_legal_1, 'NLD'); ?>>Néerlandaise (Pays-Bas)</option>
                        <option value="NZL" <?php selected($saved_nationalite_tuteur_legal_1, 'NZL'); ?>>Néo-Zélandaise (Nouvelle-Zélande)</option>
                        <option value="NPL" <?php selected($saved_nationalite_tuteur_legal_1, 'NPL'); ?>>Népalaise (Népal)</option>
                        <option value="NIC" <?php selected($saved_nationalite_tuteur_legal_1, 'NIC'); ?>>Nicaraguayenne (Nicaragua)</option>
                        <option value="NGA" <?php selected($saved_nationalite_tuteur_legal_1, 'NGA'); ?>>Nigériane (Nigéria)</option>
                        <option value="NER" <?php selected($saved_nationalite_tuteur_legal_1, 'NER'); ?>>Nigérienne (Niger)</option>
                        <option value="NIU" <?php selected($saved_nationalite_tuteur_legal_1, 'NIU'); ?>>Niuéenne (Niue)</option>
                        <option value="PRK" <?php selected($saved_nationalite_tuteur_legal_1, 'PRK'); ?>>Nord-coréenne (Corée du Nord)</option>
                        <option value="NOR" <?php selected($saved_nationalite_tuteur_legal_1, 'NOR'); ?>>Norvégienne (Norvège)</option>
                        <option value="OMN" <?php selected($saved_nationalite_tuteur_legal_1, 'OMN'); ?>>Omanaise (Oman)</option>
                        <option value="UGA" <?php selected($saved_nationalite_tuteur_legal_1, 'UGA'); ?>>Ougandaise (Ouganda)</option>
                        <option value="UZB" <?php selected($saved_nationalite_tuteur_legal_1, 'UZB'); ?>>Ouzbéke (Ouzbékistan)</option>
                        <option value="PAK" <?php selected($saved_nationalite_tuteur_legal_1, 'PAK'); ?>>Pakistanaise (Pakistan)</option>
                        <option value="PLW" <?php selected($saved_nationalite_tuteur_legal_1, 'PLW'); ?>>Palaosienne (Palaos)</option>
                        <option value="PSE" <?php selected($saved_nationalite_tuteur_legal_1, 'PSE'); ?>>Palestinienne (Palestine)</option>
                        <option value="PAN" <?php selected($saved_nationalite_tuteur_legal_1, 'PAN'); ?>>Panaméenne (Panama)</option>
                        <option value="PNG" <?php selected($saved_nationalite_tuteur_legal_1, 'PNG'); ?>>Papouane-Néo-Guinéenne (Papouasie-Nouvelle-Guinée)</option>
                        <option value="PRY" <?php selected($saved_nationalite_tuteur_legal_1, 'PRY'); ?>>Paraguayenne (Paraguay)</option>
                        <option value="PER" <?php selected($saved_nationalite_tuteur_legal_1, 'PER'); ?>>Péruvienne (Pérou)</option>
                        <option value="PHL" <?php selected($saved_nationalite_tuteur_legal_1, 'PHL'); ?>>Philippine (Philippines)</option>
                        <option value="POL" <?php selected($saved_nationalite_tuteur_legal_1, 'POL'); ?>>Polonaise (Pologne)</option>
                        <option value="PRT" <?php selected($saved_nationalite_tuteur_legal_1, 'PRT'); ?>>Portugaise (Portugal)</option>
                        <option value="QAT" <?php selected($saved_nationalite_tuteur_legal_1, 'QAT'); ?>>Qatarienne (Qatar)</option>
                        <option value="ROU" <?php selected($saved_nationalite_tuteur_legal_1, 'ROU'); ?>>Roumaine (Roumanie)</option>
                        <option value="RUS" <?php selected($saved_nationalite_tuteur_legal_1, 'RUS'); ?>>Russe (Russie)</option>
                        <option value="RWA" <?php selected($saved_nationalite_tuteur_legal_1, 'RWA'); ?>>Rwandaise (Rwanda)</option>
                        <option value="LCA" <?php selected($saved_nationalite_tuteur_legal_1, 'LCA'); ?>>Saint-Lucienne (Sainte-Lucie)</option>
                        <option value="SMR" <?php selected($saved_nationalite_tuteur_legal_1, 'SMR'); ?>>Saint-Marinaise (Saint-Marin)</option>
                        <option value="VCT" <?php selected($saved_nationalite_tuteur_legal_1, 'VCT'); ?>>Saint-Vincentaise et Grenadine (Saint-Vincent-et-les Grenadines)</option>
                        <option value="SLB" <?php selected($saved_nationalite_tuteur_legal_1, 'SLB'); ?>>Salomonaise (Îles Salomon)</option>
                        <option value="SLV" <?php selected($saved_nationalite_tuteur_legal_1, 'SLV'); ?>>Salvadorienne (Salvador)</option>
                        <option value="WSM" <?php selected($saved_nationalite_tuteur_legal_1, 'WSM'); ?>>Samoane (Samoa)</option>
                        <option value="STP" <?php selected($saved_nationalite_tuteur_legal_1, 'STP'); ?>>Santoméenne (Sao Tomé-et-Principe)</option>
                        <option value="SAU" <?php selected($saved_nationalite_tuteur_legal_1, 'SAU'); ?>>Saoudienne (Arabie saoudite)</option>
                        <option value="SEN" <?php selected($saved_nationalite_tuteur_legal_1, 'SEN'); ?>>Sénégalaise (Sénégal)</option>
                        <option value="SRB" <?php selected($saved_nationalite_tuteur_legal_1, 'SRB'); ?>>Serbe (Serbie)</option>
                        <option value="SYC" <?php selected($saved_nationalite_tuteur_legal_1, 'SYC'); ?>>Seychelloise (Seychelles)</option>
                        <option value="SLE" <?php selected($saved_nationalite_tuteur_legal_1, 'SLE'); ?>>Sierra-Léonaise (Sierra Leone)</option>
                        <option value="SGP" <?php selected($saved_nationalite_tuteur_legal_1, 'SGP'); ?>>Singapourienne (Singapour)</option>
                        <option value="SVK" <?php selected($saved_nationalite_tuteur_legal_1, 'SVK'); ?>>Slovaque (Slovaquie)</option>
                        <option value="SVN" <?php selected($saved_nationalite_tuteur_legal_1, 'SVN'); ?>>Slovène (Slovénie)</option>
                        <option value="SOM" <?php selected($saved_nationalite_tuteur_legal_1, 'SOM'); ?>>Somalienne (Somalie)</option>
                        <option value="SDN" <?php selected($saved_nationalite_tuteur_legal_1, 'SDN'); ?>>Soudanaise (Soudan)</option>
                        <option value="LKA" <?php selected($saved_nationalite_tuteur_legal_1, 'LKA'); ?>>Sri-Lankaise (Sri Lanka)</option>
                        <option value="ZAF" <?php selected($saved_nationalite_tuteur_legal_1, 'ZAF'); ?>>Sud-Africaine (Afrique du Sud)</option>
                        <option value="KOR" <?php selected($saved_nationalite_tuteur_legal_1, 'KOR'); ?>>Sud-Coréenne (Corée du Sud)</option>
                        <option value="SSD" <?php selected($saved_nationalite_tuteur_legal_1, 'SSD'); ?>>Sud-Soudanaise (Soudan du Sud)</option>
                        <option value="SWE" <?php selected($saved_nationalite_tuteur_legal_1, 'SWE'); ?>>Suédoise (Suède)</option>
                        <option value="CHE" <?php selected($saved_nationalite_tuteur_legal_1, 'CHE'); ?>>Suisse (Suisse)</option>
                        <option value="SUR" <?php selected($saved_nationalite_tuteur_legal_1, 'SUR'); ?>>Surinamaise (Suriname)</option>
                        <option value="SWZ" <?php selected($saved_nationalite_tuteur_legal_1, 'SWZ'); ?>>Swazie (Swaziland)</option>
                        <option value="SYR" <?php selected($saved_nationalite_tuteur_legal_1, 'SYR'); ?>>Syrienne (Syrie)</option>
                        <option value="TJK" <?php selected($saved_nationalite_tuteur_legal_1, 'TJK'); ?>>Tadjike (Tadjikistan)</option>
                        <option value="TZA" <?php selected($saved_nationalite_tuteur_legal_1, 'TZA'); ?>>Tanzanienne (Tanzanie)</option>
                        <option value="TCD" <?php selected($saved_nationalite_tuteur_legal_1, 'TCD'); ?>>Tchadienne (Tchad)</option>
                        <option value="CZE" <?php selected($saved_nationalite_tuteur_legal_1, 'CZE'); ?>>Tchèque (Tchéquie)</option>
                        <option value="THA" <?php selected($saved_nationalite_tuteur_legal_1, 'THA'); ?>>Thaïlandaise (Thaïlande)</option>
                        <option value="TGO" <?php selected($saved_nationalite_tuteur_legal_1, 'TGO'); ?>>Togolaise (Togo)</option>
                        <option value="TON" <?php selected($saved_nationalite_tuteur_legal_1, 'TON'); ?>>Tonguienne (Tonga)</option>
                        <option value="TTO" <?php selected($saved_nationalite_tuteur_legal_1, 'TTO'); ?>>Trinidadienne (Trinité-et-Tobago)</option>
                        <option value="TUN" <?php selected($saved_nationalite_tuteur_legal_1, 'TUN'); ?>>Tunisienne (Tunisie)</option>
                        <option value="TKM" <?php selected($saved_nationalite_tuteur_legal_1, 'TKM'); ?>>Turkmène (Turkménistan)</option>
                        <option value="TUR" <?php selected($saved_nationalite_tuteur_legal_1, 'TUR'); ?>>Turque (Turquie)</option>
                        <option value="TUV" <?php selected($saved_nationalite_tuteur_legal_1, 'TUV'); ?>>Tuvaluane (Tuvalu)</option>
                        <option value="UKR" <?php selected($saved_nationalite_tuteur_legal_1, 'UKR'); ?>>Ukrainienne (Ukraine)</option>
                        <option value="URY" <?php selected($saved_nationalite_tuteur_legal_1, 'URY'); ?>>Uruguayenne (Uruguay)</option>
                        <option value="VUT" <?php selected($saved_nationalite_tuteur_legal_1, 'VUT'); ?>>Vanuatuane (Vanuatu)</option>
                        <option value="VAT" <?php selected($saved_nationalite_tuteur_legal_1, 'VAT'); ?>>Vaticane (Vatican)</option>
                        <option value="VEN" <?php selected($saved_nationalite_tuteur_legal_1, 'VEN'); ?>>Vénézuélienne (Venezuela)</option>
                        <option value="VNM" <?php selected($saved_nationalite_tuteur_legal_1, 'VNM'); ?>>Vietnamienne (Viêt Nam)</option>
                        <option value="YEM" <?php selected($saved_nationalite_tuteur_legal_1, 'YEM'); ?>>Yéménite (Yémen)</option>
                        <option value="ZMB" <?php selected($saved_nationalite_tuteur_legal_1, 'ZMB'); ?>>Zambienne (Zambie)</option>
                        <option value="ZWE" <?php selected($saved_nationalite_tuteur_legal_1, 'ZWE'); ?>>Zimbabwéenne (Zimbabwe)</option>

			        </select><br><br>
			
			
					
			<hr>
			
			 <div class="preamble-notice">
				Parent n°2 ou Tuteur légal n°2
			</div>
			<label>Statut</label><br>
			<select name="statut_tuteur_legal_2">
				<option value="">-- Sélectionner un statut si vous êtes concerné --</option>
				<option value="Apatride" <?php selected($saved_statut_tuteur_legal_2, 'Apatride'); ?>>Apatride</option>
				<option value="Réfugié 1946/51" <?php selected($saved_statut_tuteur_legal_2, 'Réfugié 1946/51'); ?>>Réfugié 1946/51</option>
				<option value="Réfugié hs conv" <?php selected($saved_statut_tuteur_legal_2, 'Réfugié hs conv'); ?>>Réfugié hs conv</option>
			</select><br><br>
			<!-- Deuxième tuteur légal -->
			<div style="display: flex; gap: 10px;justify-content: space-between;">
				<div>
					<label>Nom</label>
					<input type="text" name="nom_tuteur_legal_2" value="<?php echo esc_attr($saved_nom_tuteur_legal_2); ?>" required>
				</div>
				<div>
					<label>Prénom</label>
					<input type="text" name="prenom_tuteur_legal_2" value="<?php echo esc_attr($saved_prenom_tuteur_legal_2); ?>">
				</div>
			</div>
			
			<label>Adresse</label><br>
			<input type="text" name="adresse_tuteur_legal_2" value="<?php echo esc_attr($saved_adresse_tuteur_legal_2); ?>" required><br><br>
			
			<div style="display: flex; gap: 10px;justify-content: space-between;">
				<div>
					<label>Code postal</label>
					<input type="text" name="code_postal_tuteur_legal_2" value="<?php echo esc_attr($saved_code_postal_tuteur_legal_2); ?>">
				</div>
				<div>
					<label>Ville</label>
					<input type="text" name="ville_tuteur_legal_2" value="<?php echo esc_attr($saved_ville_tuteur_legal_2); ?>" required>
				</div>
				<div>
					<label>Pays</label>
					 <select id="country_contact" name="pays_tuteur_legal_2" value="<?php echo esc_attr($saved_pays_tuteur_legal_2); ?>" required>
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
				</div>
			</div><br>
			
			<label>Numéro de téléphone portable :</label><br>
			<input type="text" name="telephone_tuteur_legal_2" value="<?php echo esc_attr($saved_telephone_tuteur_legal_2); ?>" pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX" required><br><br>
			
			<label>Adresse e-mail :</label><br>
			<input type="text" name="email_tuteur_legal_2" value="<?php echo esc_attr($saved_email_tuteur_legal_2); ?>" required><br><br>
			
			<label>Nationalité actuelle :<span class="required">*</span></label><br>
				<select name="nationalite_tuteur_legal_2" required>
                    <option value="">-- Sélectionnez une nationalité --</option>
                    <option value="AFG" <?php selected($saved_nationalite_tuteur_legal_2, 'AFG'); ?>>Afghane (Afghanistan)</option>
                    <option value="ALB" <?php selected($saved_nationalite_tuteur_legal_2, 'ALB'); ?>>Albanaise (Albanie)</option>
                    <option value="DZA" <?php selected($saved_nationalite_tuteur_legal_2, 'DZA'); ?>>Algérienne (Algérie)</option>
                    <option value="DEU" <?php selected($saved_nationalite_tuteur_legal_2, 'DEU'); ?>>Allemande (Allemagne)</option>
                    <option value="USA" <?php selected($saved_nationalite_tuteur_legal_2, 'USA'); ?>>Americaine (États-Unis)</option>
                    <option value="AND" <?php selected($saved_nationalite_tuteur_legal_2, 'AND'); ?>>Andorrane (Andorre)</option>
                    <option value="AND" <?php selected($saved_nationalite_tuteur_legal_2, 'AND'); ?>>Andorrane (Andorre)</option>
                    <option value="AGO" <?php selected($saved_nationalite_tuteur_legal_2, 'AGO'); ?>>Angolaise (Angola)</option>
                    <option value="ATG" <?php selected($saved_nationalite_tuteur_legal_2, 'ATG'); ?>>Antiguaise-et-Barbudienne (Antigua-et-Barbuda)</option>
                    <option value="ARG" <?php selected($saved_nationalite_tuteur_legal_2, 'ARG'); ?>>Argentine (Argentine)</option>
                    <option value="ARM" <?php selected($saved_nationalite_tuteur_legal_2, 'ARM'); ?>>Armenienne (Arménie)</option>
                    <option value="AUS" <?php selected($saved_nationalite_tuteur_legal_2, 'AUS'); ?>>Australienne (Australie)</option>
                    <option value="AUT" <?php selected($saved_nationalite_tuteur_legal_2, 'AUT'); ?>>Autrichienne (Autriche)</option>
                    <option value="AZE" <?php selected($saved_nationalite_tuteur_legal_2, 'AZE'); ?>>Azerbaïdjanaise (Azerbaïdjan)</option>
                    <option value="BHS" <?php selected($saved_nationalite_tuteur_legal_2, 'BHS'); ?>>Bahamienne (Bahamas)</option>
                    <option value="BHR" <?php selected($saved_nationalite_tuteur_legal_2, 'BHR'); ?>>Bahreinienne (Bahreïn)</option>
                    <option value="BGD" <?php selected($saved_nationalite_tuteur_legal_2, 'BGD'); ?>>Bangladaise (Bangladesh)</option>
                    <option value="BRB" <?php selected($saved_nationalite_tuteur_legal_2, 'BRB'); ?>>Barbadienne (Barbade)</option>
                    <option value="BEL" <?php selected($saved_nationalite_tuteur_legal_2, 'BEL'); ?>>Belge (Belgique)</option>
                    <option value="BLZ" <?php selected($saved_nationalite_tuteur_legal_2, 'BLZ'); ?>>Belizienne (Belize)</option>
                    <option value="BEN" <?php selected($saved_nationalite_tuteur_legal_2, 'BEN'); ?>>Béninoise (Bénin)</option>
                    <option value="BTN" <?php selected($saved_nationalite_tuteur_legal_2, 'BTN'); ?>>Bhoutanaise (Bhoutan)</option>
                    <option value="BLR" <?php selected($saved_nationalite_tuteur_legal_2, 'BLR'); ?>>Biélorusse (Biélorussie)</option>
                    <option value="MMR" <?php selected($saved_nationalite_tuteur_legal_2, 'MMR'); ?>>Birmane (Birmanie)</option>
                    <option value="GNB" <?php selected($saved_nationalite_tuteur_legal_2, 'GNB'); ?>>Bissau-Guinéenne (Guinée-Bissau)</option>
                    <option value="BOL" <?php selected($saved_nationalite_tuteur_legal_2, 'BOL'); ?>>Bolivienne (Bolivie)</option>
                    <option value="BIH" <?php selected($saved_nationalite_tuteur_legal_2, 'BIH'); ?>>Bosnienne (Bosnie-Herzégovine)</option>
                    <option value="BWA" <?php selected($saved_nationalite_tuteur_legal_2, 'BWA'); ?>>Botswanaise (Botswana)</option>
                    <option value="BRA" <?php selected($saved_nationalite_tuteur_legal_2, 'BRA'); ?>>Brésilienne (Brésil)</option>
                    <option value="GBR" <?php selected($saved_nationalite_tuteur_legal_2, 'GBR'); ?>>Britannique (Royaume-Uni)</option>
                    <option value="BRN" <?php selected($saved_nationalite_tuteur_legal_2, 'BRN'); ?>>Brunéienne (Brunéi)</option>
                    <option value="BGR" <?php selected($saved_nationalite_tuteur_legal_2, 'BGR'); ?>>Bulgare (Bulgarie)</option>
                    <option value="BFA" <?php selected($saved_nationalite_tuteur_legal_2, 'BFA'); ?>>Burkinabée (Burkina)</option>
                    <option value="BDI" <?php selected($saved_nationalite_tuteur_legal_2, 'BDI'); ?>>Burundaise (Burundi)</option>
                    <option value="KHM" <?php selected($saved_nationalite_tuteur_legal_2, 'KHM'); ?>>Cambodgienne (Cambodge)</option>
                    <option value="CMR" <?php selected($saved_nationalite_tuteur_legal_2, 'CMR'); ?>>Camerounaise (Cameroun)</option>
                    <option value="CAN" <?php selected($saved_nationalite_tuteur_legal_2, 'CAN'); ?>>Canadienne (Canada)</option>
                    <option value="CPV" <?php selected($saved_nationalite_tuteur_legal_2, 'CPV'); ?>>Cap-verdienne (Cap-Vert)</option>
                    <option value="CAF" <?php selected($saved_nationalite_tuteur_legal_2, 'CAF'); ?>>Centrafricaine (Centrafrique)</option>
                    <option value="CHL" <?php selected($saved_nationalite_tuteur_legal_2, 'CHL'); ?>>Chilienne (Chili)</option>
                    <option value="CHN" <?php selected($saved_nationalite_tuteur_legal_2, 'CHN'); ?>>Chinoise (Chine)</option>
                    <option value="CYP" <?php selected($saved_nationalite_tuteur_legal_2, 'CYP'); ?>>Chypriote (Chypre)</option>
                    <option value="COL" <?php selected($saved_nationalite_tuteur_legal_2, 'COL'); ?>>Colombienne (Colombie)</option>
                    <option value="COM" <?php selected($saved_nationalite_tuteur_legal_2, 'COM'); ?>>Comorienne (Comores)</option>
                    <option value="COG" <?php selected($saved_nationalite_tuteur_legal_2, 'COG'); ?>>Congolaise (Congo-Brazzaville)</option>
                    <option value="COD" <?php selected($saved_nationalite_tuteur_legal_2, 'COD'); ?>>Congolaise (Congo-Kinshasa)</option>
                    <option value="COK" <?php selected($saved_nationalite_tuteur_legal_2, 'COK'); ?>>Cookienne (Îles Cook)</option>
                    <option value="CRI" <?php selected($saved_nationalite_tuteur_legal_2, 'CRI'); ?>>Costaricaine (Costa Rica)</option>
                    <option value="HRV" <?php selected($saved_nationalite_tuteur_legal_2, 'HRV'); ?>>Croate (Croatie)</option>
                    <option value="CUB" <?php selected($saved_nationalite_tuteur_legal_2, 'CUB'); ?>>Cubaine (Cuba)</option>
                    <option value="DNK" <?php selected($saved_nationalite_tuteur_legal_2, 'DNK'); ?>>Danoise (Danemark)</option>
                    <option value="DJI" <?php selected($saved_nationalite_tuteur_legal_2, 'DJI'); ?>>Djiboutienne (Djibouti)</option>
                    <option value="DOM" <?php selected($saved_nationalite_tuteur_legal_2, 'DOM'); ?>>Dominicaine (République dominicaine)</option>
                    <option value="DMA" <?php selected($saved_nationalite_tuteur_legal_2, 'DMA'); ?>>Dominiquaise (Dominique)</option>
                    <option value="EGY" <?php selected($saved_nationalite_tuteur_legal_2, 'EGY'); ?>>Égyptienne (Égypte)</option>
                    <option value="ARE" <?php selected($saved_nationalite_tuteur_legal_2, 'ARE'); ?>>Émirienne (Émirats arabes unis)</option>
                    <option value="GNQ" <?php selected($saved_nationalite_tuteur_legal_2, 'GNQ'); ?>>Équato-guineenne (Guinée équatoriale)</option>
                    <option value="ECU" <?php selected($saved_nationalite_tuteur_legal_2, 'ECU'); ?>>Équatorienne (Équateur)</option>
                    <option value="ERI" <?php selected($saved_nationalite_tuteur_legal_2, 'ERI'); ?>>Érythréenne (Érythrée)</option>
                    <option value="ESP" <?php selected($saved_nationalite_tuteur_legal_2, 'ESP'); ?>>Espagnole (Espagne)</option>
                    <option value="TLS" <?php selected($saved_nationalite_tuteur_legal_2, 'TLS'); ?>>Est-timoraise (Timor-Leste)</option>
                    <option value="EST" <?php selected($saved_nationalite_tuteur_legal_2, 'EST'); ?>>Estonienne (Estonie)</option>
                    <option value="ETH" <?php selected($saved_nationalite_tuteur_legal_2, 'ETH'); ?>>Éthiopienne (Éthiopie)</option>
                    <option value="FJI" <?php selected($saved_nationalite_tuteur_legal_2, 'FJI'); ?>>Fidjienne (Fidji)</option>
                    <option value="FIN" <?php selected($saved_nationalite_tuteur_legal_2, 'FIN'); ?>>Finlandaise (Finlande)</option>
                    <option value="FRA" <?php selected($saved_nationalite_tuteur_legal_2, 'FRA'); ?>>Française (France)</option>
                    <option value="GAB" <?php selected($saved_nationalite_tuteur_legal_2, 'GAB'); ?>>Gabonaise (Gabon)</option>
                    <option value="GMB" <?php selected($saved_nationalite_tuteur_legal_2, 'GMB'); ?>>Gambienne (Gambie)</option>
                    <option value="GEO" <?php selected($saved_nationalite_tuteur_legal_2, 'GEO'); ?>>Georgienne (Géorgie)</option>
                    <option value="GHA" <?php selected($saved_nationalite_tuteur_legal_2, 'GHA'); ?>>Ghanéenne (Ghana)</option>
                    <option value="GRD" <?php selected($saved_nationalite_tuteur_legal_2, 'GRD'); ?>>Grenadienne (Grenade)</option>
                    <option value="GTM" <?php selected($saved_nationalite_tuteur_legal_2, 'GTM'); ?>>Guatémaltèque (Guatemala)</option>
                    <option value="GIN" <?php selected($saved_nationalite_tuteur_legal_2, 'GIN'); ?>>Guinéenne (Guinée)</option>
                    <option value="GUY" <?php selected($saved_nationalite_tuteur_legal_2, 'GUY'); ?>>Guyanienne (Guyana)</option>
                    <option value="HTI" <?php selected($saved_nationalite_tuteur_legal_2, 'HTI'); ?>>Haïtienne (Haïti)</option>
                    <option value="GRC" <?php selected($saved_nationalite_tuteur_legal_2, 'GRC'); ?>>Hellénique (Grèce)</option>
                    <option value="HND" <?php selected($saved_nationalite_tuteur_legal_2, 'HND'); ?>>Hondurienne (Honduras)</option>
                    <option value="HUN" <?php selected($saved_nationalite_tuteur_legal_2, 'HUN'); ?>>Hongroise (Hongrie)</option>
                    <option value="IND" <?php selected($saved_nationalite_tuteur_legal_2, 'IND'); ?>>Indienne (Inde)</option>
                    <option value="IDN" <?php selected($saved_nationalite_tuteur_legal_2, 'IDN'); ?>>Indonésienne (Indonésie)</option>
                    <option value="IRQ" <?php selected($saved_nationalite_tuteur_legal_2, 'IRQ'); ?>>Irakienne (Iraq)</option>
                    <option value="IRN" <?php selected($saved_nationalite_tuteur_legal_2, 'IRN'); ?>>Iranienne (Iran)</option>
                    <option value="IRL" <?php selected($saved_nationalite_tuteur_legal_2, 'IRL'); ?>>Irlandaise (Irlande)</option>
                    <option value="ISL" <?php selected($saved_nationalite_tuteur_legal_2, 'ISL'); ?>>Islandaise (Islande)</option>
                    <option value="ISR" <?php selected($saved_nationalite_tuteur_legal_2, 'ISR'); ?>>Israélienne (Israël)</option>
                    <option value="ITA" <?php selected($saved_nationalite_tuteur_legal_2, 'ITA'); ?>>Italienne (Italie)</option>
                    <option value="CIV" <?php selected($saved_nationalite_tuteur_legal_2, 'CIV'); ?>>Ivoirienne (Côte d'Ivoire)</option>
                    <option value="JAM" <?php selected($saved_nationalite_tuteur_legal_2, 'JAM'); ?>>Jamaïcaine (Jamaïque)</option>
                    <option value="JPN" <?php selected($saved_nationalite_tuteur_legal_2, 'JPN'); ?>>Japonaise (Japon)</option>
                    <option value="JOR" <?php selected($saved_nationalite_tuteur_legal_2, 'JOR'); ?>>Jordanienne (Jordanie)</option>
                    <option value="KAZ" <?php selected($saved_nationalite_tuteur_legal_2, 'KAZ'); ?>>Kazakhstanaise (Kazakhstan)</option>
                    <option value="KEN" <?php selected($saved_nationalite_tuteur_legal_2, 'KEN'); ?>>Kenyane (Kenya)</option>
                    <option value="KGZ" <?php selected($saved_nationalite_tuteur_legal_2, 'KGZ'); ?>>Kirghize (Kirghizistan)</option>
                    <option value="KIR" <?php selected($saved_nationalite_tuteur_legal_2, 'KIR'); ?>>Kiribatienne (Kiribati)</option>
                    <option value="KNA" <?php selected($saved_nationalite_tuteur_legal_2, 'KNA'); ?>>Kittitienne et Névicienne (Saint-Christophe-et-Niévès)</option>
                    <option value="KWT" <?php selected($saved_nationalite_tuteur_legal_2, 'KWT'); ?>>Koweïtienne (Koweït)</option>
                    <option value="LAO" <?php selected($saved_nationalite_tuteur_legal_2, 'LAO'); ?>>Laotienne (Laos)</option>
                    <option value="LSO" <?php selected($saved_nationalite_tuteur_legal_2, 'LSO'); ?>>Lesothane (Lesotho)</option>
                    <option value="LVA" <?php selected($saved_nationalite_tuteur_legal_2, 'LVA'); ?>>Lettone (Lettonie)</option>
                    <option value="LBN" <?php selected($saved_nationalite_tuteur_legal_2, 'LBN'); ?>>Libanaise (Liban)</option>
                    <option value="LBR" <?php selected($saved_nationalite_tuteur_legal_2, 'LBR'); ?>>Libérienne (Libéria)</option>
                    <option value="LBY" <?php selected($saved_nationalite_tuteur_legal_2, 'LBY'); ?>>Libyenne (Libye)</option>
                    <option value="LIE" <?php selected($saved_nationalite_tuteur_legal_2, 'LIE'); ?>>Liechtensteinoise (Liechtenstein)</option>
                    <option value="LTU" <?php selected($saved_nationalite_tuteur_legal_2, 'LTU'); ?>>Lituanienne (Lituanie)</option>
                    <option value="LUX" <?php selected($saved_nationalite_tuteur_legal_2, 'LUX'); ?>>Luxembourgeoise (Luxembourg)</option>
                    <option value="MKD" <?php selected($saved_nationalite_tuteur_legal_2, 'MKD'); ?>>Macédonienne (Macédoine)</option>
                    <option value="MYS" <?php selected($saved_nationalite_tuteur_legal_2, 'MYS'); ?>>Malaisienne (Malaisie)</option>
                    <option value="MWI" <?php selected($saved_nationalite_tuteur_legal_2, 'MWI'); ?>>Malawienne (Malawi)</option>
                    <option value="MDV" <?php selected($saved_nationalite_tuteur_legal_2, 'MDV'); ?>>Maldivienne (Maldives)</option>
                    <option value="MDG" <?php selected($saved_nationalite_tuteur_legal_2, 'MDG'); ?>>Malgache (Madagascar)</option>
                    <option value="MLI" <?php selected($saved_nationalite_tuteur_legal_2, 'MLI'); ?>>Maliennes (Mali)</option>
                    <option value="MLT" <?php selected($saved_nationalite_tuteur_legal_2, 'MLT'); ?>>Maltaise (Malte)</option>
                    <option value="MAR" <?php selected($saved_nationalite_tuteur_legal_2, 'MAR'); ?>>Marocaine (Maroc)</option>
                    <option value="MHL" <?php selected($saved_nationalite_tuteur_legal_2, 'MHL'); ?>>Marshallaise (Îles Marshall)</option>
                    <option value="MUS" <?php selected($saved_nationalite_tuteur_legal_2, 'MUS'); ?>>Mauricienne (Maurice)</option>
                    <option value="MRT" <?php selected($saved_nationalite_tuteur_legal_2, 'MRT'); ?>>Mauritanienne (Mauritanie)</option>
                    <option value="MEX" <?php selected($saved_nationalite_tuteur_legal_2, 'MEX'); ?>>Mexicaine (Mexique)</option>
                    <option value="FSM" <?php selected($saved_nationalite_tuteur_legal_2, 'FSM'); ?>>Micronésienne (Micronésie)</option>
                    <option value="MDA" <?php selected($saved_nationalite_tuteur_legal_2, 'MDA'); ?>>Moldave (Moldovie)</option>
                    <option value="MCO" <?php selected($saved_nationalite_tuteur_legal_2, 'MCO'); ?>>Monegasque (Monaco)</option>
                    <option value="MNG" <?php selected($saved_nationalite_tuteur_legal_2, 'MNG'); ?>>Mongole (Mongolie)</option>
                    <option value="MNE" <?php selected($saved_nationalite_tuteur_legal_2, 'MNE'); ?>>Monténégrine (Monténégro)</option>
                    <option value="MOZ" <?php selected($saved_nationalite_tuteur_legal_2, 'MOZ'); ?>>Mozambicaine (Mozambique)</option>
                    <option value="NAM" <?php selected($saved_nationalite_tuteur_legal_2, 'NAM'); ?>>Namibienne (Namibie)</option>
                    <option value="NRU" <?php selected($saved_nationalite_tuteur_legal_2, 'NRU'); ?>>Nauruane (Nauru)</option>
                    <option value="NLD" <?php selected($saved_nationalite_tuteur_legal_2, 'NLD'); ?>>Néerlandaise (Pays-Bas)</option>
                    <option value="NZL" <?php selected($saved_nationalite_tuteur_legal_2, 'NZL'); ?>>Néo-Zélandaise (Nouvelle-Zélande)</option>
                    <option value="NPL" <?php selected($saved_nationalite_tuteur_legal_2, 'NPL'); ?>>Népalaise (Népal)</option>
                    <option value="NIC" <?php selected($saved_nationalite_tuteur_legal_2, 'NIC'); ?>>Nicaraguayenne (Nicaragua)</option>
                    <option value="NGA" <?php selected($saved_nationalite_tuteur_legal_2, 'NGA'); ?>>Nigériane (Nigéria)</option>
                    <option value="NER" <?php selected($saved_nationalite_tuteur_legal_2, 'NER'); ?>>Nigérienne (Niger)</option>
                    <option value="NIU" <?php selected($saved_nationalite_tuteur_legal_2, 'NIU'); ?>>Niuéenne (Niue)</option>
                    <option value="PRK" <?php selected($saved_nationalite_tuteur_legal_2, 'PRK'); ?>>Nord-coréenne (Corée du Nord)</option>
                    <option value="NOR" <?php selected($saved_nationalite_tuteur_legal_2, 'NOR'); ?>>Norvégienne (Norvège)</option>
                    <option value="OMN" <?php selected($saved_nationalite_tuteur_legal_2, 'OMN'); ?>>Omanaise (Oman)</option>
                    <option value="UGA" <?php selected($saved_nationalite_tuteur_legal_2, 'UGA'); ?>>Ougandaise (Ouganda)</option>
                    <option value="UZB" <?php selected($saved_nationalite_tuteur_legal_2, 'UZB'); ?>>Ouzbéke (Ouzbékistan)</option>
                    <option value="PAK" <?php selected($saved_nationalite_tuteur_legal_2, 'PAK'); ?>>Pakistanaise (Pakistan)</option>
                    <option value="PLW" <?php selected($saved_nationalite_tuteur_legal_2, 'PLW'); ?>>Palaosienne (Palaos)</option>
                    <option value="PSE" <?php selected($saved_nationalite_tuteur_legal_2, 'PSE'); ?>>Palestinienne (Palestine)</option>
                    <option value="PAN" <?php selected($saved_nationalite_tuteur_legal_2, 'PAN'); ?>>Panaméenne (Panama)</option>
                    <option value="PNG" <?php selected($saved_nationalite_tuteur_legal_2, 'PNG'); ?>>Papouane-Néo-Guinéenne (Papouasie-Nouvelle-Guinée)</option>
                    <option value="PRY" <?php selected($saved_nationalite_tuteur_legal_2, 'PRY'); ?>>Paraguayenne (Paraguay)</option>
                    <option value="PER" <?php selected($saved_nationalite_tuteur_legal_2, 'PER'); ?>>Péruvienne (Pérou)</option>
                    <option value="PHL" <?php selected($saved_nationalite_tuteur_legal_2, 'PHL'); ?>>Philippine (Philippines)</option>
                    <option value="POL" <?php selected($saved_nationalite_tuteur_legal_2, 'POL'); ?>>Polonaise (Pologne)</option>
                    <option value="PRT" <?php selected($saved_nationalite_tuteur_legal_2, 'PRT'); ?>>Portugaise (Portugal)</option>
                    <option value="QAT" <?php selected($saved_nationalite_tuteur_legal_2, 'QAT'); ?>>Qatarienne (Qatar)</option>
                    <option value="ROU" <?php selected($saved_nationalite_tuteur_legal_2, 'ROU'); ?>>Roumaine (Roumanie)</option>
                    <option value="RUS" <?php selected($saved_nationalite_tuteur_legal_2, 'RUS'); ?>>Russe (Russie)</option>
                    <option value="RWA" <?php selected($saved_nationalite_tuteur_legal_2, 'RWA'); ?>>Rwandaise (Rwanda)</option>
                    <option value="LCA" <?php selected($saved_nationalite_tuteur_legal_2, 'LCA'); ?>>Saint-Lucienne (Sainte-Lucie)</option>
                    <option value="SMR" <?php selected($saved_nationalite_tuteur_legal_2, 'SMR'); ?>>Saint-Marinaise (Saint-Marin)</option>
                    <option value="VCT" <?php selected($saved_nationalite_tuteur_legal_2, 'VCT'); ?>>Saint-Vincentaise et Grenadine (Saint-Vincent-et-les Grenadines)</option>
                    <option value="SLB" <?php selected($saved_nationalite_tuteur_legal_2, 'SLB'); ?>>Salomonaise (Îles Salomon)</option>
                    <option value="SLV" <?php selected($saved_nationalite_tuteur_legal_2, 'SLV'); ?>>Salvadorienne (Salvador)</option>
                    <option value="WSM" <?php selected($saved_nationalite_tuteur_legal_2, 'WSM'); ?>>Samoane (Samoa)</option>
                    <option value="STP" <?php selected($saved_nationalite_tuteur_legal_2, 'STP'); ?>>Santoméenne (Sao Tomé-et-Principe)</option>
                    <option value="SAU" <?php selected($saved_nationalite_tuteur_legal_2, 'SAU'); ?>>Saoudienne (Arabie saoudite)</option>
                    <option value="SEN" <?php selected($saved_nationalite_tuteur_legal_2, 'SEN'); ?>>Sénégalaise (Sénégal)</option>
                    <option value="SRB" <?php selected($saved_nationalite_tuteur_legal_2, 'SRB'); ?>>Serbe (Serbie)</option>
                    <option value="SYC" <?php selected($saved_nationalite_tuteur_legal_2, 'SYC'); ?>>Seychelloise (Seychelles)</option>
                    <option value="SLE" <?php selected($saved_nationalite_tuteur_legal_2, 'SLE'); ?>>Sierra-Léonaise (Sierra Leone)</option>
                    <option value="SGP" <?php selected($saved_nationalite_tuteur_legal_2, 'SGP'); ?>>Singapourienne (Singapour)</option>
                    <option value="SVK" <?php selected($saved_nationalite_tuteur_legal_2, 'SVK'); ?>>Slovaque (Slovaquie)</option>
                    <option value="SVN" <?php selected($saved_nationalite_tuteur_legal_2, 'SVN'); ?>>Slovène (Slovénie)</option>
                    <option value="SOM" <?php selected($saved_nationalite_tuteur_legal_2, 'SOM'); ?>>Somalienne (Somalie)</option>
                    <option value="SDN" <?php selected($saved_nationalite_tuteur_legal_2, 'SDN'); ?>>Soudanaise (Soudan)</option>
                    <option value="LKA" <?php selected($saved_nationalite_tuteur_legal_2, 'LKA'); ?>>Sri-Lankaise (Sri Lanka)</option>
                    <option value="ZAF" <?php selected($saved_nationalite_tuteur_legal_2, 'ZAF'); ?>>Sud-Africaine (Afrique du Sud)</option>
                    <option value="KOR" <?php selected($saved_nationalite_tuteur_legal_2, 'KOR'); ?>>Sud-Coréenne (Corée du Sud)</option>
                    <option value="SSD" <?php selected($saved_nationalite_tuteur_legal_2, 'SSD'); ?>>Sud-Soudanaise (Soudan du Sud)</option>
                    <option value="SWE" <?php selected($saved_nationalite_tuteur_legal_2, 'SWE'); ?>>Suédoise (Suède)</option>
                    <option value="CHE" <?php selected($saved_nationalite_tuteur_legal_2, 'CHE'); ?>>Suisse (Suisse)</option>
                    <option value="SUR" <?php selected($saved_nationalite_tuteur_legal_2, 'SUR'); ?>>Surinamaise (Suriname)</option>
                    <option value="SWZ" <?php selected($saved_nationalite_tuteur_legal_2, 'SWZ'); ?>>Swazie (Swaziland)</option>
                    <option value="SYR" <?php selected($saved_nationalite_tuteur_legal_2, 'SYR'); ?>>Syrienne (Syrie)</option>
                    <option value="TJK" <?php selected($saved_nationalite_tuteur_legal_2, 'TJK'); ?>>Tadjike (Tadjikistan)</option>
                    <option value="TZA" <?php selected($saved_nationalite_tuteur_legal_2, 'TZA'); ?>>Tanzanienne (Tanzanie)</option>
                    <option value="TCD" <?php selected($saved_nationalite_tuteur_legal_2, 'TCD'); ?>>Tchadienne (Tchad)</option>
                    <option value="CZE" <?php selected($saved_nationalite_tuteur_legal_2, 'CZE'); ?>>Tchèque (Tchéquie)</option>
                    <option value="THA" <?php selected($saved_nationalite_tuteur_legal_2, 'THA'); ?>>Thaïlandaise (Thaïlande)</option>
                    <option value="TGO" <?php selected($saved_nationalite_tuteur_legal_2, 'TGO'); ?>>Togolaise (Togo)</option>
                    <option value="TON" <?php selected($saved_nationalite_tuteur_legal_2, 'TON'); ?>>Tonguienne (Tonga)</option>
                    <option value="TTO" <?php selected($saved_nationalite_tuteur_legal_2, 'TTO'); ?>>Trinidadienne (Trinité-et-Tobago)</option>
                    <option value="TUN" <?php selected($saved_nationalite_tuteur_legal_2, 'TUN'); ?>>Tunisienne (Tunisie)</option>
                    <option value="TKM" <?php selected($saved_nationalite_tuteur_legal_2, 'TKM'); ?>>Turkmène (Turkménistan)</option>
                    <option value="TUR" <?php selected($saved_nationalite_tuteur_legal_2, 'TUR'); ?>>Turque (Turquie)</option>
                    <option value="TUV" <?php selected($saved_nationalite_tuteur_legal_2, 'TUV'); ?>>Tuvaluane (Tuvalu)</option>
                    <option value="UKR" <?php selected($saved_nationalite_tuteur_legal_2, 'UKR'); ?>>Ukrainienne (Ukraine)</option>
                    <option value="URY" <?php selected($saved_nationalite_tuteur_legal_2, 'URY'); ?>>Uruguayenne (Uruguay)</option>
                    <option value="VUT" <?php selected($saved_nationalite_tuteur_legal_2, 'VUT'); ?>>Vanuatuane (Vanuatu)</option>
                    <option value="VAT" <?php selected($saved_nationalite_tuteur_legal_2, 'VAT'); ?>>Vaticane (Vatican)</option>
                    <option value="VEN" <?php selected($saved_nationalite_tuteur_legal_2, 'VEN'); ?>>Vénézuélienne (Venezuela)</option>
                    <option value="VNM" <?php selected($saved_nationalite_tuteur_legal_2, 'VNM'); ?>>Vietnamienne (Viêt Nam)</option>
                    <option value="YEM" <?php selected($saved_nationalite_tuteur_legal_2, 'YEM'); ?>>Yéménite (Yémen)</option>
                    <option value="ZMB" <?php selected($saved_nationalite_tuteur_legal_2, 'ZMB'); ?>>Zambienne (Zambie)</option>
                    <option value="ZWE" <?php selected($saved_nationalite_tuteur_legal_2, 'ZWE'); ?>>Zimbabwéenne (Zimbabwe)</option>
                </select><br><br>
			
			
			<hr>
	    </div>
		<label>27. Empreintes digitales relevées précédemment aux fins d’une demande de visa Schengen :</label><br>
		<input type="radio" name="empreinte" value="non"> Non<br>
		<input type="radio" name="empreinte" value="oui"> Oui <br>
	
		<label>Date, si elle est connue :</label><br>
		<input type="date" name="empreinte_date"><br>
	
		<label>Numéro du visa, s’il est connu :</label><br>
		<input type="text" name="num_visa"><br><br>
	
		<label>28. Autorisation d’entrée dans le pays de destination finale, le cas échéant :</label><br>	
		<label>Délivrée par</label><br>
		<input type="text" name="autorisation_delivre_par"><br>
	
		<label>valable du</label><br>
		<input type="date" name="autorisation_validite"><br>
	
		<label>au</label><br>
		<input type="date" name="autorisation_delivre_au"><br><br>
		
		<label>Accueilli (e) à titre privé par</label>
		<div style="display: flex;align-items: center;justify-content: space-around;margin: 10px 0px;">
		    <div id="go-personne">Une personne</div>
		    <div id="go-hotel">Hôtel ou lieu d’hébergement</div>
		    <div id="go-entreprise">Une Entreprise ou Organisation</div>
		</div>
	
		<div id="personne" style="display:none">
		    <label>29.a. – Accueilli (e) à titre privé par une personne</label><br>
		    <div style="display: flex; gap: 10px;justify-content: space-between;">
		        <div>
		            <label>Nom</label>
		            <input type="text" name="nom_accueil" value="<?php echo esc_attr($saved_nom_accueil); ?>">
		        </div>
		        <div>
		            <label>Prenom</label>
		            <input type="text" name="prenom_accueil" value="<?php echo esc_attr($saved_prenom_accueil); ?>">
		        </div>
		    </div>
    		
    		<label>Adresse</label><br>
    		<input type="text" name="adresse_accueil" value="<?php echo esc_attr($saved_adresse_accueil); ?>"><br><br>
    		
    		<div style="display: flex; gap: 10px;justify-content: space-between;">
    		    <div>
    		        <label>Code postal</label>
    		        <input type="text" name="cp_accueil" value="<?php echo esc_attr($saved_cp_accueil); ?>">
    		    </div>
    		    <div>
    		        <label>Ville</label>
    		        <input type="text" name="ville_accueil" value="<?php echo esc_attr($saved_ville_accueil); ?>">
    		    </div>
    		    <div>
    		        <label>Pays</label>
    		        <select id="country" name="pays_accueil" value="<?php echo esc_attr($saved_pays_accueil); ?>">
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
    		    </div>
    		</div><br>
    		
    		<label>Numéro de téléphone portable :</label><br>
    		<input type="text" name="num_accueil" value="<?php echo esc_attr($saved_num_accueil); ?>" pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX"><br><br>
    		
    		<label>Adresse e-mail :</label><br>
    		<input type="text" name="mail_accueil" value="<?php echo esc_attr($saved_mail_accueil); ?>"><br><br>
	    </div>
	    
		<div id="hotel" style="display:none">
		    <label>29.b. Accueilli (e) à titre privé à l’hôtel ou dans une lieu d’hébergement</label><br>
		    <div>
		        <div>
		            <label>Nom de l’hôtel/hébergement</label>
		            <input type="text" name="nom_hotel" value="<?php echo esc_attr($saved_nom_hotel); ?>">
		        </div>
		    </div>
    		
    		<label>Adresse</label><br>
    		<input type="text" name="adresse_hotel" value="<?php echo esc_attr($saved_adresse_hotel); ?>"><br><br>
    		
    		<div style="display: flex; gap: 10px;justify-content: space-between;">
    		    <div>
    		        <label>Code postal</label>
    		        <input type="text" name="cp_hotel" value="<?php echo esc_attr($saved_cp_hotel); ?>">
    		    </div>
    		    <div>
    		        <label>Ville</label>
    		        <input type="text" name="ville_hotel" value="<?php echo esc_attr($saved_ville_hotel); ?>">
    		    </div>
    		    <div>
    		        <label>Pays</label>
    		        <select id="country" name="pays_hotel" value="<?php echo esc_attr($saved_pays_hotel); ?>">
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
    		    </div>
    		</div><br>
    		
    		<label>Numéro de téléphone portable :</label><br>
    		<input type="text" name="num_hotel" value="<?php echo esc_attr($saved_num_hotel); ?>" pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX"><br><br>
    		
    		<label>Adresse e-mail :</label><br>
    		<input type="text" name="mail_hotel" value="<?php echo esc_attr($saved_mail_hotel); ?>">
	    </div>
		
		<label style="display:none;">30. Nom et prénom de la ou des personnes qui invitent dans le ou les États membres. A défaut, nom d’un ou des hôtels ou lieux d’hébergement temporaire dans le ou les États membres :</label><br>
		<textarea name="hotel" style="display:none;" readonly><?php echo esc_textarea($saved_hotel); ?></textarea>
	
		<label style="display:none;">Adresse et adresse électronique de la ou des personnes qui invitent /du ou des hôtels /du ou des lieux d’hébergement temporaire :</label>
		<textarea name="adresse_inviteur" style="display:none;" readonly><?php echo esc_textarea($saved_adresse_inviteur); ?></textarea>
		
		<label style="display:none;">Numéro de téléphone portable :</label>
	    <input type="text" name="phone_adresse_inviteur" style="display:none;" value="<?php echo esc_attr($saved_phone_adresse_inviteur); ?>" readonly>
		
		<div id="entreprise" style="display:none">
		    <label>31. Accueilli (e) par une Entreprise ou Organisation</label><br>
    		<input type="text" name="nom_entreprise" value="<?php echo esc_attr($saved_nom_entreprise); ?>"><br><br>
    		
    		<label>Adresse</label><br>
    		<input type="text" name="adresse_entreprise" value="<?php echo esc_attr($saved_adresse_entreprise); ?>"><br><br>
    		
    		<div style="display: flex;justify-content: space-between;">
    		    <div>
    		        <label>Code postal</label>
    		        <input type="text" name="cp_entreprise" value="<?php echo esc_attr($saved_cp_entreprise); ?>">
    		    </div>
    		    <div>
    		        <label>Ville</label>
    		        <input type="text" name="ville_entreprise" value="<?php echo esc_attr($saved_ville_entreprise); ?>">
    		    </div>
    		    <div>
    		        <label>Pays</label>
    		        <select id="country" name="pays_entreprise" value="<?php echo esc_attr($saved_pays_entreprise); ?>">
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
    		    </div>
    		</div><br>
    		
    		<label>Numéro de téléphone portable de l’entreprise /l’organisation :</label><br>
    	    <input type="text" name="phone_hote" pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX" value="<?php echo esc_attr($saved_phone_hote); ?>"><br><br>
    	    
    	    <label>Adresse e-mail de l’entreprise /l’organisation :</label><br>
        	<input type="text" name="mail_entreprise" value="<?php echo esc_attr($saved_mail_entreprise); ?>">
		</div>
	    
		<label style="display:none;">Nom et adresse de l’entreprise /l’organisation hôte :</label>
		<textarea name="hote" style="display:none;" readonly><?php echo esc_textarea($saved_hote); ?></textarea>
    	
    	<label>Coordonnées du contact :</label>
    	
    	<div id="contact">
		    <div style="display: flex;justify-content: space-between;gap: 10px;">
		        <div>
		            <label>Nom de la personne de contact</label>
		            <input type="text" name="nom_contact" value="<?php echo esc_attr($saved_nom_contact); ?>">
		        </div>
		        <div>
		            <label>Prénom de la personne de contact</label>
		            <input type="text" name="prenom_contact" value="<?php echo esc_attr($saved_prenom_contact); ?>">
		        </div>
		    </div>
    		
    		<label>Adresse</label><br>
    		<input type="text" name="adresse_contact" value="<?php echo esc_attr($saved_adresse_contact); ?>"><br><br>
    		
    		<div style="display: flex; gap: 10px;justify-content: space-between;gap: 10px;">
    		    <div>
    		        <label>Code postal</label>
    		        <input type="text" name="cp_contact" value="<?php echo esc_attr($saved_cp_contact); ?>">
    		    </div>
    		    <div>
    		        <label>Ville</label>
    		        <input type="text" name="ville_contact" value="<?php echo esc_attr($saved_ville_contact); ?>">
    		    </div>
    		    <div>
    		        <label>Pays</label>
    		        <select id="country_contact" name="pays_contact" value="<?php echo esc_attr($saved_pays_contact); ?>">
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
    		    </div>
    		</div><br>
    		
    		<label>Numéro de téléphone portable :</label><br>
    		<input type="text" name="num_contact" value="<?php echo esc_attr($saved_num_contact); ?>" pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX"><br><br>
    		
    		<label>Adresse e-mail :</label><br>
    		<input type="text" name="mail_contact" value="<?php echo esc_attr($saved_mail_contact); ?>"><br><br>
	    </div>
	
		<label style="display:none;">Nom, prénom, adresse, Numéro de téléphone portable, et adresse électronique de la personne de contact dans l’entreprise/organisation :<span class="required">*</span></label><br>
		<textarea name="personne_de_contact" style="display:none;" readonly><?php echo esc_textarea($saved_personne_de_contact); ?></textarea>

		<fieldset class="financing">
			<legend>32. Les frais de voyage et de subsistance durant le séjour du demandeur sont financés : <span class="required">*</span></legend>

			<!-- Choix du financeur -->
			<div class="financing-options">
				<label><input type="radio" name="financement" value="demandeur"> Par le demandeur</label>
				<label><input type="radio" name="financement" value="garant"> Par un garant</label>
			</div>

			<!-- Section Demandeur -->
			<div class="subsection" data-for="demandeur">
				<p>Moyens de subsistance :<span class="required">*</span></p>
				<div class="checkbox-grid">
				<label><input type="checkbox" name="demandeur_financement_moyen[]" value="liquide"> Argent liquide</label>
				<label><input type="checkbox" name="demandeur_financement_moyen[]" value="cheque"> Chèques de voyage</label>
				<label><input type="checkbox" name="demandeur_financement_moyen[]" value="credit"> Carte de crédit</label>
				<label><input type="checkbox" name="demandeur_financement_moyen[]" value="hebergement"> Hébergement prépayé</label>
				<label><input type="checkbox" name="demandeur_financement_moyen[]" value="transport"> Transport prépayé</label>
				<label>
					<input type="checkbox" name="demandeur_financement_moyen[]" value="autre"> Autre  
					<input type="text" name="demandeur_financement_moyen_autre" placeholder="Précisez">
				</label>
				</div>
			</div>

			<!-- Section Garant -->
			<div class="subsection" data-for="garant">
				<p>Précisions sur le garant :</p>
				<label><input type="radio" name="financement_garant" value="garant_vise"> Par l'entreprise ou l'organisation</label>
				<label>
				<input type="radio" name="financement_garant" value="garant_autre"> Autre  
				<input type="text" name="garant_autre_detail" placeholder="Nom" required>
				</label>

                <div id="moyen_sub" style="display:none;">
                    <p>Moyens de subsistance :</p>
    				<div class="checkbox-grid">
    				<label><input type="checkbox" name="garant_financement_moyen[]" value="liquide"> Argent liquide</label>
    				<label><input type="checkbox" name="garant_financement_moyen[]" value="finance"> Tous frais financés</label>
    				<label><input type="checkbox" name="garant_financement_moyen[]" value="hebergement"> Hébergement fourni</label>
    				<label><input type="checkbox" name="garant_financement_moyen[]" value="transport"> Transport prépayé</label>
    				<label>
    					<input type="checkbox" name="garant_financement_moyen[]" value="autre"> Autre  
    					<input type="text" name="garant_financement_moyen_autre" placeholder="Précisez">
    				</label>
    				</div>
                </div>
			</div>
		</fieldset>
		<?php /*
		<label>33. Nom et prénom de la personne qui remplit le formulaire de demande, si elle n’est pas le demandeur :</label><br>
		
		<div id="remplisseur" >
		    <div style="display: flex; gap: 10px;justify-content: space-between;">
		        <div>
		            <label>Nom</label>
		            <input type="text" name="nom_remplisseur" value="<?php echo esc_attr($saved_nom_remplisseur); ?>" readonly>
		        </div>
		        <div>
		            <label>Prénom</label>
		            <input type="text" name="prenom_remplisseur" value="<?php echo esc_attr($saved_prenom_remplisseur); ?>" readonly>
		        </div>
		    </div>
    		
    		<label>Adresse</label><br>
    		<input type="text" name="adres_remplisseur" value="<?php echo esc_attr($saved_adres_remplisseur); ?>" readonly><br><br>
    		
    		<div style="display: flex; gap: 10px;justify-content: space-between;">
    		    <div>
    		        <label>Code postal</label>
    		        <input type="text" name="cp_remplisseur" value="<?php echo esc_attr($saved_cp_remplisseur); ?>" readonly>
    		    </div>
    		    <div>
    		        <label>Ville</label>
    		        <input type="text" name="ville_remplisseur" value="<?php echo esc_attr($saved_ville_remplisseur); ?>" readonly>
    		    </div>
    		    <div>
    		        <label>Pays</label>
    		        <select id="country_contact" name="pays_remplisseur" value="<?php echo esc_attr($saved_pays_remplisseur); ?>" disabled>
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
    		    </div>
    		</div><br>
    		
    		<label>Numéro de téléphone portable :</label><br>
    		<input type="text" name="num_remplisseur" value="<?php echo esc_attr($saved_num_contact); ?>" pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX" readonly><br><br>
    		
    		<label>Adresse e-mail :</label><br>
    		<input type="text" name="mail_remplisseur" value="<?php echo esc_attr($saved_mail_contact); ?>" readonly><br><br>
    		*/ ?>
	    </div>
	    
		<input type="text" name="remplisseur" readonly><?php echo esc_attr($saved_adres_remplisseur); ?><br>
	
		<label>Adresse et adresse électronique de la personne qui remplit le formulaire de demande : </label><br>
		<textarea name="adresse_remplisseur" readonly></textarea><br>
	
		<p>Je suis informé(e) que les droits de visa ne sont pas remboursés si le visa est refusé.</p>
	
		<p>Applicable en cas de délivrance d'un visa à entrées multiples</p>
		<p>Je suis informé(e) de la nécessité de disposer d’une assurance maladie en voyage adéquate pour mon premier séjour et lors de voyages ultérieurs sur le territoire des États membres</p>
	
		<p>En connaissance de cause, j'accepte ce qui suit : aux fins de l'examen de ma demande, il y a lieu de recueillir les données requises dans ce formulaire de demande, de me photographier et, le cas échéant, de prendre mes empreintes digitales. Les données à caractère personnel me concernant qui figurent dans le présent formulaire de demande, ainsi que mes empreintes digitales et ma photo, seront communiquées aux autorités compétentes des États membres et traitées par elles, aux fins de la décision relative à ma demande de visa,</p>
		<p>Ces données ainsi que celles concernant la décision relative à ma demande, ou toute décision d'annulation, d'abrogation ou de prolongation de visa, seront saisies et conservées dans le système d'information sur les visas (VIS) pendant une période maximale de cinq ans durant laquelle elles seront accessibles aux autorités chargées des visas, aux autorités compétentes chargées de contrôler les visas aux frontières extérieures et dans les États membres, aux autorités compétentes en matière d'immigration et d'asile dans les États membres aux fins de la vérification du respect des conditions d'entrée et de séjour réguliers sur le territoire des États membres, de l'identification des personnes qui ne remplissent pas ou plus ces conditions, de l'examen d'une demande d'asile et de la détermination de l'autorité responsable de cet examen. Dans certaines conditions, ces données seront aussi accessibles aux autorités désignées des États membres et à Europol aux fins de la prévention et de la détection des infractions terroristes et des autres infractions pénales graves, ainsi qu'aux fins des enquêtes en la matière. Les autorités de l'État membre compétentes pour le traitement des données sont le Ministère de l’Intérieur (Place Beauvau - 75800 Paris CEDEX 08) et le Ministère de l’Europe et des Affaires Etrangères (27 rue de la Convention – 75732 PARIS Cedex 15).</p>
		<p>Je suis informé(e) de mon droit d'obtenir auprès de n'importe quel État membre la notification des données me concernant qui sont enregistrées dans le VIS ainsi que de l'État membre qui les a transmises, et de demander que les données me concernant soient rectifiées si elles sont erronées ou effacées si elles ont été traitées de façon illicite. À ma demande expresse, l'autorité qui a examiné ma demande m'informera de la manière dont je peux exercer mon droit de vérifier les données à caractère personnel me concernant et de les faire rectifier ou effacer, y compris des voies de recours prévues à cet égard par le droit national de l'État membre concerné. L'autorité de contrôle nationale dudit État membre [ Commission Nationale de l'Informatique et des Libertés – 3 Place de Fontenoy - TSA 80715 - 75334 PARIS CEDEX 07 ] pourra être saisie des demandes concernant la protection des données à caractère personnel.</p>
		<p>Je déclare qu'à ma connaissance, toutes les indications que j'ai fournies sont correctes et complètes. Je suis informé(e) que toute fausse déclaration entraînera le rejet de ma demande ou l'annulation du visa s'il a déjà été délivré, et peut également entraîner des poursuites pénales à mon égard en application du droit de l'État membre qui traite la demande.</p>
		<p>Je m'engage à quitter le territoire des États membres avant l'expiration du visa, si celui-ci m'est accordé. J'ai été informé(e) que la possession d'un visa n'est que l'une des conditions préalables d'entrée sur le territoire européen des États membres. Le simple fait qu'un visa m'ait été accordé ne signifie pas que j'aurai droit à une indemnisation si je ne respecte pas les dispositions pertinentes à l'article 6, paragraphe 1, du règlement UE 2016/399 ( code frontières Schengen) et que l'entrée m'est par conséquent refusée. Le respect des conditions préalables d'entrée sera contrôlé à nouveau au moment de l'entrée sur le territoire européen des États membres.</p>
    <?php else: ?>
        <label>1. Nom(s) :<span class="required">*</span></label><br>
		<input type="text" name="full_name" value="<?php echo esc_attr($saved_full_name); ?>" required><br><br>

		<label>2. Nom(s) de famille antérieur(s) :</label><br>
		<input type="text" name="nom_famille"><br><br>

		<label>3. Prénom(s) :<span class="required">*</span></label><br>
		<input type="text" name="prenom" value="<?php echo esc_attr($saved_prenom); ?>"  required><br><br>

		<label>4. Date de naissance (jour-mois-année) :<span class="required">*</span></label><br>
		<input type="date" name="birth_date" id ="birth_date" value="<?php echo esc_attr($birth_val); ?>" required><br><br>
		
		
	
		<label>5. Lieu de naissance :<span class="required">*</span></label><br>
		<input type="text" name="lieu_naiss" value="<?php echo esc_attr($saved_lieu_naiss); ?>" required><br><br>
    
		<label>6. Pays de naissance :<span class="required">*</span></label><br>
		<select id="country" name="pays_naiss">
			<option value="">-- Sélectionnez un pays --</option>
			<option value="Afghanistan">Afghanistan</option>
			<option value="Afrique du Sud">Afrique du Sud</option>
			<option value="Albanie">Albanie</option>
			<option value="Algérie">Algérie</option>
			<option value="Allemagne">Allemagne</option>
			<option value="Andorre">Andorre</option>
			<option value="Angola">Angola</option>
			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
			<option value="Arabie Saoudite">Arabie Saoudite</option>
			<option value="Argentine">Argentine</option>
			<option value="Arménie">Arménie</option>
			<option value="Australie">Australie</option>
			<option value="Autriche">Autriche</option>
			<option value="Azerbaïdjan">Azerbaïdjan</option>
			<option value="Bahamas">Bahamas</option>
			<option value="Bahreïn">Bahreïn</option>
			<option value="Bangladesh">Bangladesh</option>
			<option value="Barbade">Barbade</option>
			<option value="Belgique">Belgique</option>
			<option value="Belize">Belize</option>
			<option value="Bénin">Bénin</option>
			<option value="Bhoutan">Bhoutan</option>
			<option value="Biélorussie">Biélorussie</option>
			<option value="Birmanie">Birmanie</option>
			<option value="Bolivie">Bolivie</option>
			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
			<option value="Botswana">Botswana</option>
			<option value="Brésil">Brésil</option>
			<option value="Brunei">Brunei</option>
			<option value="Bulgarie">Bulgarie</option>
			<option value="Burkina Faso">Burkina Faso</option>
			<option value="Burundi">Burundi</option>
			<option value="Cabo Verde">Cabo Verde</option>
			<option value="Cambodge">Cambodge</option>
			<option value="Cameroun">Cameroun</option>
			<option value="Canada">Canada</option>
			<option value="République centrafricaine">République centrafricaine</option>
			<option value="Tchad">Tchad</option>
			<option value="Chili">Chili</option>
			<option value="Chine">Chine</option>
			<option value="Chypre">Chypre</option>
			<option value="Colombie">Colombie</option>
			<option value="Comores">Comores</option>
			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
			<option value="Corée du Nord">Corée du Nord</option>
			<option value="Corée du Sud">Corée du Sud</option>
			<option value="Costa Rica">Costa Rica</option>
			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
			<option value="Croatie">Croatie</option>
			<option value="Cuba">Cuba</option>
			<option value="Danemark">Danemark</option>
			<option value="Djibouti">Djibouti</option>
			<option value="Dominique">Dominique</option>
			<option value="République dominicaine">République dominicaine</option>
			<option value="Egypte">Égypte</option>
			<option value="Emirats arabes unis">Émirats arabes unis</option>
			<option value="Equateur">Équateur</option>
			<option value="Erythrée">Érythrée</option>
			<option value="Espagne">Espagne</option>
			<option value="Estonie">Estonie</option>
			<option value="Eswatini">Eswatini</option>
			<option value="Etats-Unis">États-Unis</option>
			<option value="Ethiopie">Éthiopie</option>
			<option value="Fidji">Fidji</option>
			<option value="Finlande">Finlande</option>
			<option value="France">France</option>
			<option value="Gabon">Gabon</option>
			<option value="Gambie">Gambie</option>
			<option value="Géorgie">Géorgie</option>
			<option value="Ghana">Ghana</option>
			<option value="Grèce">Grèce</option>
			<option value="Grenade">Grenade</option>
			<option value="Guatemala">Guatemala</option>
			<option value="Guinée">Guinée</option>
			<option value="Guinée-Bissau">Guinée-Bissau</option>
			<option value="Guinée équatoriale">Guinée équatoriale</option>
			<option value="Guyana">Guyana</option>
			<option value="Haïti">Haïti</option>
			<option value="Honduras">Honduras</option>
			<option value="Hongrie">Hongrie</option>
			<option value="Inde">Inde</option>
			<option value="Indonésie">Indonésie</option>
			<option value="Irak">Irak</option>
			<option value="Iran">Iran</option>
			<option value="Irlande">Irlande</option>
			<option value="Islande">Islande</option>
			<option value="Israël">Israël</option>
			<option value="Italie">Italie</option>
			<option value="Jamaïque">Jamaïque</option>
			<option value="Japon">Japon</option>
			<option value="Jordanie">Jordanie</option>
			<option value="Kazakhstan">Kazakhstan</option>
			<option value="Kenya">Kenya</option>
			<option value="Kirghizistan">Kirghizistan</option>
			<option value="Kiribati">Kiribati</option>
			<option value="Kosovo">Kosovo</option>
			<option value="Koweït">Koweït</option>
			<option value="Laos">Laos</option>
			<option value="Lettonie">Lettonie</option>
			<option value="Liban">Liban</option>
			<option value="Libéria">Libéria</option>
			<option value="Libye">Libye</option>
			<option value="Liechtenstein">Liechtenstein</option>
			<option value="Lituanie">Lituanie</option>
			<option value="Luxembourg">Luxembourg</option>
			<option value="Macédoine du Nord">Macédoine du Nord</option>
			<option value="Madagascar">Madagascar</option>
			<option value="Malaisie">Malaisie</option>
			<option value="Malawi">Malawi</option>
			<option value="Maldives">Maldives</option>
			<option value="Mali">Mali</option>
			<option value="Malte">Malte</option>
			<option value="Maroc">Maroc</option>
			<option value="Marshall">Îles Marshall</option>
			<option value="Maurice">Maurice</option>
			<option value="Mauritanie">Mauritanie</option>
			<option value="Mexique">Mexique</option>
			<option value="Micronésie">Micronésie</option>
			<option value="Moldavie">Moldavie</option>
			<option value="Monaco">Monaco</option>
			<option value="Mongolie">Mongolie</option>
			<option value="Monténégro">Monténégro</option>
			<option value="Mozambique">Mozambique</option>
			<option value="Namibie">Namibie</option>
			<option value="Nauru">Nauru</option>
			<option value="Népal">Népal</option>
			<option value="Nicaragua">Nicaragua</option>
			<option value="Niger">Niger</option>
			<option value="Nigéria">Nigéria</option>
			<option value="Norvège">Norvège</option>
			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
			<option value="Oman">Oman</option>
			<option value="Ouganda">Ouganda</option>
			<option value="Ouzbékistan">Ouzbékistan</option>
			<option value="Pakistan">Pakistan</option>
			<option value="Palaos">Palaos</option>
			<option value="Palestine">Palestine</option>
			<option value="Panama">Panama</option>
			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
			<option value="Paraguay">Paraguay</option>
			<option value="Pays-Bas">Pays-Bas</option>
			<option value="Pérou">Pérou</option>
			<option value="Philippines">Philippines</option>
			<option value="Pologne">Pologne</option>
			<option value="Portugal">Portugal</option>
			<option value="République centrafricaine">République centrafricaine</option>
			<option value="République dominicaine">République dominicaine</option>
			<option value="Roumanie">Roumanie</option>
			<option value="Royaume-Uni">Royaume-Uni</option>
			<option value="Russie">Russie</option>
			<option value="Rwanda">Rwanda</option>
			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
			<option value="Saint-Marin">Saint-Marin</option>
			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
			<option value="Sainte-Lucie">Sainte-Lucie</option>
			<option value="Salvador">Salvador</option>
			<option value="Samoa">Samoa</option>
			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
			<option value="Sénégal">Sénégal</option>
			<option value="Serbie">Serbie</option>
			<option value="Seychelles">Seychelles</option>
			<option value="Sierra Leone">Sierra Leone</option>
			<option value="Singapour">Singapour</option>
			<option value="Slovaquie">Slovaquie</option>
			<option value="Slovénie">Slovénie</option>
			<option value="Somalie">Somalie</option>
			<option value="Soudan">Soudan</option>
			<option value="Soudan du Sud">Soudan du Sud</option>
			<option value="Sri Lanka">Sri Lanka</option>
			<option value="Suède">Suède</option>
			<option value="Suisse">Suisse</option>
			<option value="Suriname">Suriname</option>
			<option value="Syrie">Syrie</option>
			<option value="Tadjikistan">Tadjikistan</option>
			<option value="Tanzanie">Tanzanie</option>
			<option value="Tchad">Tchad</option>
			<option value="Tchécoslovaquie">Tchéquie</option>
			<option value="Thaïlande">Thaïlande</option>
			<option value="Timor-Leste">Timor-Leste</option>
			<option value="Togo">Togo</option>
			<option value="Tonga">Tonga</option>
			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
			<option value="Tunisie">Tunisie</option>
			<option value="Turkménistan">Turkménistan</option>
			<option value="Turquie">Turquie</option>
			<option value="Tuvalu">Tuvalu</option>
			<option value="Ukraine">Ukraine</option>
			<option value="Uruguay">Uruguay</option>
			<option value="Vanuatu">Vanuatu</option>
			<option value="Vatican">Vatican</option>
			<option value="Venezuela">Venezuela</option>
			<option value="Vietnam">Vietnam</option>
			<option value="Yémen">Yémen</option>
			<option value="Zambie">Zambie</option>
			<option value="Zimbabwe">Zimbabwe</option>
		</select><br><br>
	
		<label>7. Nationalité actuelle :<span class="required">*</span></label><br>
		<select name="nationalite" required>
			<option value="">-- Sélectionnez une nationalité --</option>
			<option value="AFG" <?php selected($saved_visa_nationalite, 'AFG'); ?>>Afghane (Afghanistan)</option>
			<option value="ALB" <?php selected($saved_visa_nationalite, 'ALB'); ?>>Albanaise (Albanie)</option>
			<option value="DZA" <?php selected($saved_visa_nationalite, 'DZA'); ?>>Algérienne (Algérie)</option>
			<option value="DEU" <?php selected($saved_visa_nationalite, 'DEU'); ?>>Allemande (Allemagne)</option>
			<option value="USA" <?php selected($saved_visa_nationalite, 'USA'); ?>>Americaine (États-Unis)</option>
			<option value="AND" <?php selected($saved_visa_nationalite, 'AND'); ?>>Andorrane (Andorre)</option>
			<option value="AND" <?php selected($saved_visa_nationalite, 'AND'); ?>>Andorrane (Andorre)</option>
            <option value="AGO" <?php selected($saved_visa_nationalite, 'AGO'); ?>>Angolaise (Angola)</option>
            <option value="ATG" <?php selected($saved_visa_nationalite, 'ATG'); ?>>Antiguaise-et-Barbudienne (Antigua-et-Barbuda)</option>
            <option value="ARG" <?php selected($saved_visa_nationalite, 'ARG'); ?>>Argentine (Argentine)</option>
            <option value="ARM" <?php selected($saved_visa_nationalite, 'ARM'); ?>>Armenienne (Arménie)</option>
            <option value="AUS" <?php selected($saved_visa_nationalite, 'AUS'); ?>>Australienne (Australie)</option>
            <option value="AUT" <?php selected($saved_visa_nationalite, 'AUT'); ?>>Autrichienne (Autriche)</option>
            <option value="AZE" <?php selected($saved_visa_nationalite, 'AZE'); ?>>Azerbaïdjanaise (Azerbaïdjan)</option>
            <option value="BHS" <?php selected($saved_visa_nationalite, 'BHS'); ?>>Bahamienne (Bahamas)</option>
            <option value="BHR" <?php selected($saved_visa_nationalite, 'BHR'); ?>>Bahreinienne (Bahreïn)</option>
            <option value="BGD" <?php selected($saved_visa_nationalite, 'BGD'); ?>>Bangladaise (Bangladesh)</option>
            <option value="BRB" <?php selected($saved_visa_nationalite, 'BRB'); ?>>Barbadienne (Barbade)</option>
            <option value="BEL" <?php selected($saved_visa_nationalite, 'BEL'); ?>>Belge (Belgique)</option>
            <option value="BLZ" <?php selected($saved_visa_nationalite, 'BLZ'); ?>>Belizienne (Belize)</option>
            <option value="BEN" <?php selected($saved_visa_nationalite, 'BEN'); ?>>Béninoise (Bénin)</option>
            <option value="BTN" <?php selected($saved_visa_nationalite, 'BTN'); ?>>Bhoutanaise (Bhoutan)</option>
            <option value="BLR" <?php selected($saved_visa_nationalite, 'BLR'); ?>>Biélorusse (Biélorussie)</option>
            <option value="MMR" <?php selected($saved_visa_nationalite, 'MMR'); ?>>Birmane (Birmanie)</option>
            <option value="GNB" <?php selected($saved_visa_nationalite, 'GNB'); ?>>Bissau-Guinéenne (Guinée-Bissau)</option>
            <option value="BOL" <?php selected($saved_visa_nationalite, 'BOL'); ?>>Bolivienne (Bolivie)</option>
            <option value="BIH" <?php selected($saved_visa_nationalite, 'BIH'); ?>>Bosnienne (Bosnie-Herzégovine)</option>
            <option value="BWA" <?php selected($saved_visa_nationalite, 'BWA'); ?>>Botswanaise (Botswana)</option>
            <option value="BRA" <?php selected($saved_visa_nationalite, 'BRA'); ?>>Brésilienne (Brésil)</option>
            <option value="GBR" <?php selected($saved_visa_nationalite, 'GBR'); ?>>Britannique (Royaume-Uni)</option>
            <option value="BRN" <?php selected($saved_visa_nationalite, 'BRN'); ?>>Brunéienne (Brunéi)</option>
            <option value="BGR" <?php selected($saved_visa_nationalite, 'BGR'); ?>>Bulgare (Bulgarie)</option>
            <option value="BFA" <?php selected($saved_visa_nationalite, 'BFA'); ?>>Burkinabée (Burkina)</option>
            <option value="BDI" <?php selected($saved_visa_nationalite, 'BDI'); ?>>Burundaise (Burundi)</option>
            <option value="KHM" <?php selected($saved_visa_nationalite, 'KHM'); ?>>Cambodgienne (Cambodge)</option>
            <option value="CMR" <?php selected($saved_visa_nationalite, 'CMR'); ?>>Camerounaise (Cameroun)</option>
            <option value="CAN" <?php selected($saved_visa_nationalite, 'CAN'); ?>>Canadienne (Canada)</option>
            <option value="CPV" <?php selected($saved_visa_nationalite, 'CPV'); ?>>Cap-verdienne (Cap-Vert)</option>
            <option value="CAF" <?php selected($saved_visa_nationalite, 'CAF'); ?>>Centrafricaine (Centrafrique)</option>
            <option value="CHL" <?php selected($saved_visa_nationalite, 'CHL'); ?>>Chilienne (Chili)</option>
            <option value="CHN" <?php selected($saved_visa_nationalite, 'CHN'); ?>>Chinoise (Chine)</option>
            <option value="CYP" <?php selected($saved_visa_nationalite, 'CYP'); ?>>Chypriote (Chypre)</option>
            <option value="COL" <?php selected($saved_visa_nationalite, 'COL'); ?>>Colombienne (Colombie)</option>
            <option value="COM" <?php selected($saved_visa_nationalite, 'COM'); ?>>Comorienne (Comores)</option>
            <option value="COG" <?php selected($saved_visa_nationalite, 'COG'); ?>>Congolaise (Congo-Brazzaville)</option>
            <option value="COD" <?php selected($saved_visa_nationalite, 'COD'); ?>>Congolaise (Congo-Kinshasa)</option>
            <option value="COK" <?php selected($saved_visa_nationalite, 'COK'); ?>>Cookienne (Îles Cook)</option>
            <option value="CRI" <?php selected($saved_visa_nationalite, 'CRI'); ?>>Costaricaine (Costa Rica)</option>
            <option value="HRV" <?php selected($saved_visa_nationalite, 'HRV'); ?>>Croate (Croatie)</option>
            <option value="CUB" <?php selected($saved_visa_nationalite, 'CUB'); ?>>Cubaine (Cuba)</option>
            <option value="DNK" <?php selected($saved_visa_nationalite, 'DNK'); ?>>Danoise (Danemark)</option>
            <option value="DJI" <?php selected($saved_visa_nationalite, 'DJI'); ?>>Djiboutienne (Djibouti)</option>
            <option value="DOM" <?php selected($saved_visa_nationalite, 'DOM'); ?>>Dominicaine (République dominicaine)</option>
            <option value="DMA" <?php selected($saved_visa_nationalite, 'DMA'); ?>>Dominiquaise (Dominique)</option>
            <option value="EGY" <?php selected($saved_visa_nationalite, 'EGY'); ?>>Égyptienne (Égypte)</option>
            <option value="ARE" <?php selected($saved_visa_nationalite, 'ARE'); ?>>Émirienne (Émirats arabes unis)</option>
            <option value="GNQ" <?php selected($saved_visa_nationalite, 'GNQ'); ?>>Équato-guineenne (Guinée équatoriale)</option>
            <option value="ECU" <?php selected($saved_visa_nationalite, 'ECU'); ?>>Équatorienne (Équateur)</option>
            <option value="ERI" <?php selected($saved_visa_nationalite, 'ERI'); ?>>Érythréenne (Érythrée)</option>
            <option value="ESP" <?php selected($saved_visa_nationalite, 'ESP'); ?>>Espagnole (Espagne)</option>
            <option value="TLS" <?php selected($saved_visa_nationalite, 'TLS'); ?>>Est-timoraise (Timor-Leste)</option>
            <option value="EST" <?php selected($saved_visa_nationalite, 'EST'); ?>>Estonienne (Estonie)</option>
            <option value="ETH" <?php selected($saved_visa_nationalite, 'ETH'); ?>>Éthiopienne (Éthiopie)</option>
            <option value="FJI" <?php selected($saved_visa_nationalite, 'FJI'); ?>>Fidjienne (Fidji)</option>
            <option value="FIN" <?php selected($saved_visa_nationalite, 'FIN'); ?>>Finlandaise (Finlande)</option>
            <option value="FRA" <?php selected($saved_visa_nationalite, 'FRA'); ?>>Française (France)</option>
            <option value="GAB" <?php selected($saved_visa_nationalite, 'GAB'); ?>>Gabonaise (Gabon)</option>
            <option value="GMB" <?php selected($saved_visa_nationalite, 'GMB'); ?>>Gambienne (Gambie)</option>
            <option value="GEO" <?php selected($saved_visa_nationalite, 'GEO'); ?>>Georgienne (Géorgie)</option>
            <option value="GHA" <?php selected($saved_visa_nationalite, 'GHA'); ?>>Ghanéenne (Ghana)</option>
            <option value="GRD" <?php selected($saved_visa_nationalite, 'GRD'); ?>>Grenadienne (Grenade)</option>
            <option value="GTM" <?php selected($saved_visa_nationalite, 'GTM'); ?>>Guatémaltèque (Guatemala)</option>
            <option value="GIN" <?php selected($saved_visa_nationalite, 'GIN'); ?>>Guinéenne (Guinée)</option>
            <option value="GUY" <?php selected($saved_visa_nationalite, 'GUY'); ?>>Guyanienne (Guyana)</option>
            <option value="HTI" <?php selected($saved_visa_nationalite, 'HTI'); ?>>Haïtienne (Haïti)</option>
            <option value="GRC" <?php selected($saved_visa_nationalite, 'GRC'); ?>>Hellénique (Grèce)</option>
            <option value="HND" <?php selected($saved_visa_nationalite, 'HND'); ?>>Hondurienne (Honduras)</option>
            <option value="HUN" <?php selected($saved_visa_nationalite, 'HUN'); ?>>Hongroise (Hongrie)</option>
            <option value="IND" <?php selected($saved_visa_nationalite, 'IND'); ?>>Indienne (Inde)</option>
            <option value="IDN" <?php selected($saved_visa_nationalite, 'IDN'); ?>>Indonésienne (Indonésie)</option>
            <option value="IRQ" <?php selected($saved_visa_nationalite, 'IRQ'); ?>>Irakienne (Iraq)</option>
            <option value="IRN" <?php selected($saved_visa_nationalite, 'IRN'); ?>>Iranienne (Iran)</option>
            <option value="IRL" <?php selected($saved_visa_nationalite, 'IRL'); ?>>Irlandaise (Irlande)</option>
            <option value="ISL" <?php selected($saved_visa_nationalite, 'ISL'); ?>>Islandaise (Islande)</option>
            <option value="ISR" <?php selected($saved_visa_nationalite, 'ISR'); ?>>Israélienne (Israël)</option>
            <option value="ITA" <?php selected($saved_visa_nationalite, 'ITA'); ?>>Italienne (Italie)</option>
            <option value="CIV" <?php selected($saved_visa_nationalite, 'CIV'); ?>>Ivoirienne (Côte d'Ivoire)</option>
            <option value="JAM" <?php selected($saved_visa_nationalite, 'JAM'); ?>>Jamaïcaine (Jamaïque)</option>
            <option value="JPN" <?php selected($saved_visa_nationalite, 'JPN'); ?>>Japonaise (Japon)</option>
            <option value="JOR" <?php selected($saved_visa_nationalite, 'JOR'); ?>>Jordanienne (Jordanie)</option>
            <option value="KAZ" <?php selected($saved_visa_nationalite, 'KAZ'); ?>>Kazakhstanaise (Kazakhstan)</option>
            <option value="KEN" <?php selected($saved_visa_nationalite, 'KEN'); ?>>Kenyane (Kenya)</option>
            <option value="KGZ" <?php selected($saved_visa_nationalite, 'KGZ'); ?>>Kirghize (Kirghizistan)</option>
            <option value="KIR" <?php selected($saved_visa_nationalite, 'KIR'); ?>>Kiribatienne (Kiribati)</option>
            <option value="KNA" <?php selected($saved_visa_nationalite, 'KNA'); ?>>Kittitienne et Névicienne (Saint-Christophe-et-Niévès)</option>
            <option value="KWT" <?php selected($saved_visa_nationalite, 'KWT'); ?>>Koweïtienne (Koweït)</option>
            <option value="LAO" <?php selected($saved_visa_nationalite, 'LAO'); ?>>Laotienne (Laos)</option>
            <option value="LSO" <?php selected($saved_visa_nationalite, 'LSO'); ?>>Lesothane (Lesotho)</option>
            <option value="LVA" <?php selected($saved_visa_nationalite, 'LVA'); ?>>Lettone (Lettonie)</option>
            <option value="LBN" <?php selected($saved_visa_nationalite, 'LBN'); ?>>Libanaise (Liban)</option>
            <option value="LBR" <?php selected($saved_visa_nationalite, 'LBR'); ?>>Libérienne (Libéria)</option>
            <option value="LBY" <?php selected($saved_visa_nationalite, 'LBY'); ?>>Libyenne (Libye)</option>
            <option value="LIE" <?php selected($saved_visa_nationalite, 'LIE'); ?>>Liechtensteinoise (Liechtenstein)</option>
            <option value="LTU" <?php selected($saved_visa_nationalite, 'LTU'); ?>>Lituanienne (Lituanie)</option>
            <option value="LUX" <?php selected($saved_visa_nationalite, 'LUX'); ?>>Luxembourgeoise (Luxembourg)</option>
            <option value="MKD" <?php selected($saved_visa_nationalite, 'MKD'); ?>>Macédonienne (Macédoine)</option>
            <option value="MYS" <?php selected($saved_visa_nationalite, 'MYS'); ?>>Malaisienne (Malaisie)</option>
            <option value="MWI" <?php selected($saved_visa_nationalite, 'MWI'); ?>>Malawienne (Malawi)</option>
            <option value="MDV" <?php selected($saved_visa_nationalite, 'MDV'); ?>>Maldivienne (Maldives)</option>
            <option value="MDG" <?php selected($saved_visa_nationalite, 'MDG'); ?>>Malgache (Madagascar)</option>
            <option value="MLI" <?php selected($saved_visa_nationalite, 'MLI'); ?>>Maliennes (Mali)</option>
			<option value="MLT" <?php selected($saved_visa_nationalite, 'MLT'); ?>>Maltaise (Malte)</option>
            <option value="MAR" <?php selected($saved_visa_nationalite, 'MAR'); ?>>Marocaine (Maroc)</option>
            <option value="MHL" <?php selected($saved_visa_nationalite, 'MHL'); ?>>Marshallaise (Îles Marshall)</option>
            <option value="MUS" <?php selected($saved_visa_nationalite, 'MUS'); ?>>Mauricienne (Maurice)</option>
            <option value="MRT" <?php selected($saved_visa_nationalite, 'MRT'); ?>>Mauritanienne (Mauritanie)</option>
            <option value="MEX" <?php selected($saved_visa_nationalite, 'MEX'); ?>>Mexicaine (Mexique)</option>
            <option value="FSM" <?php selected($saved_visa_nationalite, 'FSM'); ?>>Micronésienne (Micronésie)</option>
            <option value="MDA" <?php selected($saved_visa_nationalite, 'MDA'); ?>>Moldave (Moldovie)</option>
            <option value="MCO" <?php selected($saved_visa_nationalite, 'MCO'); ?>>Monegasque (Monaco)</option>
            <option value="MNG" <?php selected($saved_visa_nationalite, 'MNG'); ?>>Mongole (Mongolie)</option>
            <option value="MNE" <?php selected($saved_visa_nationalite, 'MNE'); ?>>Monténégrine (Monténégro)</option>
            <option value="MOZ" <?php selected($saved_visa_nationalite, 'MOZ'); ?>>Mozambicaine (Mozambique)</option>
            <option value="NAM" <?php selected($saved_visa_nationalite, 'NAM'); ?>>Namibienne (Namibie)</option>
            <option value="NRU" <?php selected($saved_visa_nationalite, 'NRU'); ?>>Nauruane (Nauru)</option>
            <option value="NLD" <?php selected($saved_visa_nationalite, 'NLD'); ?>>Néerlandaise (Pays-Bas)</option>
            <option value="NZL" <?php selected($saved_visa_nationalite, 'NZL'); ?>>Néo-Zélandaise (Nouvelle-Zélande)</option>
            <option value="NPL" <?php selected($saved_visa_nationalite, 'NPL'); ?>>Népalaise (Népal)</option>
            <option value="NIC" <?php selected($saved_visa_nationalite, 'NIC'); ?>>Nicaraguayenne (Nicaragua)</option>
            <option value="NGA" <?php selected($saved_visa_nationalite, 'NGA'); ?>>Nigériane (Nigéria)</option>
            <option value="NER" <?php selected($saved_visa_nationalite, 'NER'); ?>>Nigérienne (Niger)</option>
            <option value="NIU" <?php selected($saved_visa_nationalite, 'NIU'); ?>>Niuéenne (Niue)</option>
            <option value="PRK" <?php selected($saved_visa_nationalite, 'PRK'); ?>>Nord-coréenne (Corée du Nord)</option>
            <option value="NOR" <?php selected($saved_visa_nationalite, 'NOR'); ?>>Norvégienne (Norvège)</option>
            <option value="OMN" <?php selected($saved_visa_nationalite, 'OMN'); ?>>Omanaise (Oman)</option>
            <option value="UGA" <?php selected($saved_visa_nationalite, 'UGA'); ?>>Ougandaise (Ouganda)</option>
            <option value="UZB" <?php selected($saved_visa_nationalite, 'UZB'); ?>>Ouzbéke (Ouzbékistan)</option>
            <option value="PAK" <?php selected($saved_visa_nationalite, 'PAK'); ?>>Pakistanaise (Pakistan)</option>
            <option value="PLW" <?php selected($saved_visa_nationalite, 'PLW'); ?>>Palaosienne (Palaos)</option>
            <option value="PSE" <?php selected($saved_visa_nationalite, 'PSE'); ?>>Palestinienne (Palestine)</option>
            <option value="PAN" <?php selected($saved_visa_nationalite, 'PAN'); ?>>Panaméenne (Panama)</option>
            <option value="PNG" <?php selected($saved_visa_nationalite, 'PNG'); ?>>Papouane-Néo-Guinéenne (Papouasie-Nouvelle-Guinée)</option>
            <option value="PRY" <?php selected($saved_visa_nationalite, 'PRY'); ?>>Paraguayenne (Paraguay)</option>
            <option value="PER" <?php selected($saved_visa_nationalite, 'PER'); ?>>Péruvienne (Pérou)</option>
            <option value="PHL" <?php selected($saved_visa_nationalite, 'PHL'); ?>>Philippine (Philippines)</option>
            <option value="POL" <?php selected($saved_visa_nationalite, 'POL'); ?>>Polonaise (Pologne)</option>
            <option value="PRT" <?php selected($saved_visa_nationalite, 'PRT'); ?>>Portugaise (Portugal)</option>
            <option value="QAT" <?php selected($saved_visa_nationalite, 'QAT'); ?>>Qatarienne (Qatar)</option>
            <option value="ROU" <?php selected($saved_visa_nationalite, 'ROU'); ?>>Roumaine (Roumanie)</option>
            <option value="RUS" <?php selected($saved_visa_nationalite, 'RUS'); ?>>Russe (Russie)</option>
            <option value="RWA" <?php selected($saved_visa_nationalite, 'RWA'); ?>>Rwandaise (Rwanda)</option>
            <option value="LCA" <?php selected($saved_visa_nationalite, 'LCA'); ?>>Saint-Lucienne (Sainte-Lucie)</option>
            <option value="SMR" <?php selected($saved_visa_nationalite, 'SMR'); ?>>Saint-Marinaise (Saint-Marin)</option>
            <option value="VCT" <?php selected($saved_visa_nationalite, 'VCT'); ?>>Saint-Vincentaise et Grenadine (Saint-Vincent-et-les Grenadines)</option>
            <option value="SLB" <?php selected($saved_visa_nationalite, 'SLB'); ?>>Salomonaise (Îles Salomon)</option>
            <option value="SLV" <?php selected($saved_visa_nationalite, 'SLV'); ?>>Salvadorienne (Salvador)</option>
            <option value="WSM" <?php selected($saved_visa_nationalite, 'WSM'); ?>>Samoane (Samoa)</option>
            <option value="STP" <?php selected($saved_visa_nationalite, 'STP'); ?>>Santoméenne (Sao Tomé-et-Principe)</option>
            <option value="SAU" <?php selected($saved_visa_nationalite, 'SAU'); ?>>Saoudienne (Arabie saoudite)</option>
            <option value="SEN" <?php selected($saved_visa_nationalite, 'SEN'); ?>>Sénégalaise (Sénégal)</option>
            <option value="SRB" <?php selected($saved_visa_nationalite, 'SRB'); ?>>Serbe (Serbie)</option>
            <option value="SYC" <?php selected($saved_visa_nationalite, 'SYC'); ?>>Seychelloise (Seychelles)</option>
            <option value="SLE" <?php selected($saved_visa_nationalite, 'SLE'); ?>>Sierra-Léonaise (Sierra Leone)</option>
            <option value="SGP" <?php selected($saved_visa_nationalite, 'SGP'); ?>>Singapourienne (Singapour)</option>
            <option value="SVK" <?php selected($saved_visa_nationalite, 'SVK'); ?>>Slovaque (Slovaquie)</option>
            <option value="SVN" <?php selected($saved_visa_nationalite, 'SVN'); ?>>Slovène (Slovénie)</option>
            <option value="SOM" <?php selected($saved_visa_nationalite, 'SOM'); ?>>Somalienne (Somalie)</option>
            <option value="SDN" <?php selected($saved_visa_nationalite, 'SDN'); ?>>Soudanaise (Soudan)</option>
            <option value="LKA" <?php selected($saved_visa_nationalite, 'LKA'); ?>>Sri-Lankaise (Sri Lanka)</option>
            <option value="ZAF" <?php selected($saved_visa_nationalite, 'ZAF'); ?>>Sud-Africaine (Afrique du Sud)</option>
            <option value="KOR" <?php selected($saved_visa_nationalite, 'KOR'); ?>>Sud-Coréenne (Corée du Sud)</option>
            <option value="SSD" <?php selected($saved_visa_nationalite, 'SSD'); ?>>Sud-Soudanaise (Soudan du Sud)</option>
            <option value="SWE" <?php selected($saved_visa_nationalite, 'SWE'); ?>>Suédoise (Suède)</option>
            <option value="CHE" <?php selected($saved_visa_nationalite, 'CHE'); ?>>Suisse (Suisse)</option>
            <option value="SUR" <?php selected($saved_visa_nationalite, 'SUR'); ?>>Surinamaise (Suriname)</option>
            <option value="SWZ" <?php selected($saved_visa_nationalite, 'SWZ'); ?>>Swazie (Swaziland)</option>
            <option value="SYR" <?php selected($saved_visa_nationalite, 'SYR'); ?>>Syrienne (Syrie)</option>
            <option value="TJK" <?php selected($saved_visa_nationalite, 'TJK'); ?>>Tadjike (Tadjikistan)</option>
            <option value="TZA" <?php selected($saved_visa_nationalite, 'TZA'); ?>>Tanzanienne (Tanzanie)</option>
            <option value="TCD" <?php selected($saved_visa_nationalite, 'TCD'); ?>>Tchadienne (Tchad)</option>
            <option value="CZE" <?php selected($saved_visa_nationalite, 'CZE'); ?>>Tchèque (Tchéquie)</option>
            <option value="THA" <?php selected($saved_visa_nationalite, 'THA'); ?>>Thaïlandaise (Thaïlande)</option>
            <option value="TGO" <?php selected($saved_visa_nationalite, 'TGO'); ?>>Togolaise (Togo)</option>
            <option value="TON" <?php selected($saved_visa_nationalite, 'TON'); ?>>Tonguienne (Tonga)</option>
            <option value="TTO" <?php selected($saved_visa_nationalite, 'TTO'); ?>>Trinidadienne (Trinité-et-Tobago)</option>
            <option value="TUN" <?php selected($saved_visa_nationalite, 'TUN'); ?>>Tunisienne (Tunisie)</option>
            <option value="TKM" <?php selected($saved_visa_nationalite, 'TKM'); ?>>Turkmène (Turkménistan)</option>
            <option value="TUR" <?php selected($saved_visa_nationalite, 'TUR'); ?>>Turque (Turquie)</option>
            <option value="TUV" <?php selected($saved_visa_nationalite, 'TUV'); ?>>Tuvaluane (Tuvalu)</option>
            <option value="UKR" <?php selected($saved_visa_nationalite, 'UKR'); ?>>Ukrainienne (Ukraine)</option>
            <option value="URY" <?php selected($saved_visa_nationalite, 'URY'); ?>>Uruguayenne (Uruguay)</option>
            <option value="VUT" <?php selected($saved_visa_nationalite, 'VUT'); ?>>Vanuatuane (Vanuatu)</option>
            <option value="VAT" <?php selected($saved_visa_nationalite, 'VAT'); ?>>Vaticane (Vatican)</option>
            <option value="VEN" <?php selected($saved_visa_nationalite, 'VEN'); ?>>Vénézuélienne (Venezuela)</option>
            <option value="VNM" <?php selected($saved_visa_nationalite, 'VNM'); ?>>Vietnamienne (Viêt Nam)</option>
            <option value="YEM" <?php selected($saved_visa_nationalite, 'YEM'); ?>>Yéménite (Yémen)</option>
            <option value="ZMB" <?php selected($saved_visa_nationalite, 'ZMB'); ?>>Zambienne (Zambie)</option>
            <option value="ZWE" <?php selected($saved_visa_nationalite, 'ZWE'); ?>>Zimbabwéenne (Zimbabwe)</option>
		</select><br><br>
	
		<label>Nationalité à la naissance, si différente :</label><br>
		<select name="nationalite_diff">
			<option value="">-- Sélectionnez une nationalité --</option>
			<option value="AFG">Afghane (Afghanistan)</option>
			<option value="ALB">Albanaise (Albanie)</option>
			<option value="DZA">Algérienne (Algérie)</option>
			<option value="DEU">Allemande (Allemagne)</option>
			<option value="USA">Americaine (États-Unis)</option>
			<option value="AND">Andorrane (Andorre)</option>
			<option value="AGO">Angolaise (Angola)</option>
			<option value="ATG">Antiguaise-et-Barbudienne (Antigua-et-Barbuda)</option>
			<option value="ARG">Argentine (Argentine)</option>
			<option value="ARM">Armenienne (Arménie)</option>
			<option value="AUS">Australienne (Australie)</option>
			<option value="AUT">Autrichienne (Autriche)</option>
			<option value="AZE">Azerbaïdjanaise (Azerbaïdjan)</option>
			<option value="BHS">Bahamienne (Bahamas)</option>
			<option value="BHR">Bahreinienne (Bahreïn)</option>
			<option value="BGD">Bangladaise (Bangladesh)</option>
			<option value="BRB">Barbadienne (Barbade)</option>
			<option value="BEL">Belge (Belgique)</option>
			<option value="BLZ">Belizienne (Belize)</option>
			<option value="BEN">Béninoise (Bénin)</option>
			<option value="BTN">Bhoutanaise (Bhoutan)</option>
			<option value="BLR">Biélorusse (Biélorussie)</option>
			<option value="MMR">Birmane (Birmanie)</option>
			<option value="GNB">Bissau-Guinéenne (Guinée-Bissau)</option>
			<option value="BOL">Bolivienne (Bolivie)</option>
			<option value="BIH">Bosnienne (Bosnie-Herzégovine)</option>
			<option value="BWA">Botswanaise (Botswana)</option>
			<option value="BRA">Brésilienne (Brésil)</option>
			<option value="GBR">Britannique (Royaume-Uni)</option>
			<option value="BRN">Brunéienne (Brunéi)</option>
			<option value="BGR">Bulgare (Bulgarie)</option>
			<option value="BFA">Burkinabée (Burkina)</option>
			<option value="BDI">Burundaise (Burundi)</option>
			<option value="KHM">Cambodgienne (Cambodge)</option>
			<option value="CMR">Camerounaise (Cameroun)</option>
			<option value="CAN">Canadienne (Canada)</option>
			<option value="CPV">Cap-verdienne (Cap-Vert)</option>
			<option value="CAF">Centrafricaine (Centrafrique)</option>
			<option value="CHL">Chilienne (Chili)</option>
			<option value="CHN">Chinoise (Chine)</option>
			<option value="CYP">Chypriote (Chypre)</option>
			<option value="COL">Colombienne (Colombie)</option>
			<option value="COM">Comorienne (Comores)</option>
			<option value="COG">Congolaise (Congo-Brazzaville)</option>
			<option value="COD">Congolaise (Congo-Kinshasa)</option>
			<option value="COK">Cookienne (Îles Cook)</option>
			<option value="CRI">Costaricaine (Costa Rica)</option>
			<option value="HRV">Croate (Croatie)</option>
			<option value="CUB">Cubaine (Cuba)</option>
			<option value="DNK">Danoise (Danemark)</option>
			<option value="DJI">Djiboutienne (Djibouti)</option>
			<option value="DOM">Dominicaine (République dominicaine)</option>
			<option value="DMA">Dominiquaise (Dominique)</option>
			<option value="EGY">Égyptienne (Égypte)</option>
			<option value="ARE">Émirienne (Émirats arabes unis)</option>
			<option value="GNQ">Équato-guineenne (Guinée équatoriale)</option>
			<option value="ECU">Équatorienne (Équateur)</option>
			<option value="ERI">Érythréenne (Érythrée)</option>
			<option value="ESP">Espagnole (Espagne)</option>
			<option value="TLS">Est-timoraise (Timor-Leste)</option>
			<option value="EST">Estonienne (Estonie)</option>
			<option value="ETH">Éthiopienne (Éthiopie)</option>
			<option value="FJI">Fidjienne (Fidji)</option>
			<option value="FIN">Finlandaise (Finlande)</option>
			<option value="FRA">Française (France)</option>
			<option value="GAB">Gabonaise (Gabon)</option>
			<option value="GMB">Gambienne (Gambie)</option>
			<option value="GEO">Georgienne (Géorgie)</option>
			<option value="GHA">Ghanéenne (Ghana)</option>
			<option value="GRD">Grenadienne (Grenade)</option>
			<option value="GTM">Guatémaltèque (Guatemala)</option>
			<option value="GIN">Guinéenne (Guinée)</option>
			<option value="GUY">Guyanienne (Guyana)</option>
			<option value="HTI">Haïtienne (Haïti)</option>
			<option value="GRC">Hellénique (Grèce)</option>
			<option value="HND">Hondurienne (Honduras)</option>
			<option value="HUN">Hongroise (Hongrie)</option>
			<option value="IND">Indienne (Inde)</option>
			<option value="IDN">Indonésienne (Indonésie)</option>
			<option value="IRQ">Irakienne (Iraq)</option>
			<option value="IRN">Iranienne (Iran)</option>
			<option value="IRL">Irlandaise (Irlande)</option>
			<option value="ISL">Islandaise (Islande)</option>
			<option value="ISR">Israélienne (Israël)</option>
			<option value="ITA">Italienne (Italie)</option>
			<option value="CIV">Ivoirienne (Côte d'Ivoire)</option>
			<option value="JAM">Jamaïcaine (Jamaïque)</option>
			<option value="JPN">Japonaise (Japon)</option>
			<option value="JOR">Jordanienne (Jordanie)</option>
			<option value="KAZ">Kazakhstanaise (Kazakhstan)</option>
			<option value="KEN">Kenyane (Kenya)</option>
			<option value="KGZ">Kirghize (Kirghizistan)</option>
			<option value="KIR">Kiribatienne (Kiribati)</option>
			<option value="KNA">Kittitienne et Névicienne (Saint-Christophe-et-Niévès)</option>
			<option value="KWT">Koweïtienne (Koweït)</option>
			<option value="LAO">Laotienne (Laos)</option>
			<option value="LSO">Lesothane (Lesotho)</option>
			<option value="LVA">Lettone (Lettonie)</option>
			<option value="LBN">Libanaise (Liban)</option>
			<option value="LBR">Libérienne (Libéria)</option>
			<option value="LBY">Libyenne (Libye)</option>
			<option value="LIE">Liechtensteinoise (Liechtenstein)</option>
			<option value="LTU">Lituanienne (Lituanie)</option>
			<option value="LUX">Luxembourgeoise (Luxembourg)</option>
			<option value="MKD">Macédonienne (Macédoine)</option>
			<option value="MYS">Malaisienne (Malaisie)</option>
			<option value="MWI">Malawienne (Malawi)</option>
			<option value="MDV">Maldivienne (Maldives)</option>
			<option value="MDG">Malgache (Madagascar)</option>
			<option value="MLI">Maliennes (Mali)</option>
			<option value="MLT">Maltaise (Malte)</option>
			<option value="MAR">Marocaine (Maroc)</option>
			<option value="MHL">Marshallaise (Îles Marshall)</option>
			<option value="MUS">Mauricienne (Maurice)</option>
			<option value="MRT">Mauritanienne (Mauritanie)</option>
			<option value="MEX">Mexicaine (Mexique)</option>
			<option value="FSM">Micronésienne (Micronésie)</option>
			<option value="MDA">Moldave (Moldovie)</option>
			<option value="MCO">Monegasque (Monaco)</option>
			<option value="MNG">Mongole (Mongolie)</option>
			<option value="MNE">Monténégrine (Monténégro)</option>
			<option value="MOZ">Mozambicaine (Mozambique)</option>
			<option value="NAM">Namibienne (Namibie)</option>
			<option value="NRU">Nauruane (Nauru)</option>
			<option value="NLD">Néerlandaise (Pays-Bas)</option>
			<option value="NZL">Néo-Zélandaise (Nouvelle-Zélande)</option>
			<option value="NPL">Népalaise (Népal)</option>
			<option value="NIC">Nicaraguayenne (Nicaragua)</option>
			<option value="NGA">Nigériane (Nigéria)</option>
			<option value="NER">Nigérienne (Niger)</option>
			<option value="NIU">Niuéenne (Niue)</option>
			<option value="PRK">Nord-coréenne (Corée du Nord)</option>
			<option value="NOR">Norvégienne (Norvège)</option>
			<option value="OMN">Omanaise (Oman)</option>
			<option value="UGA">Ougandaise (Ouganda)</option>
			<option value="UZB">Ouzbéke (Ouzbékistan)</option>
			<option value="PAK">Pakistanaise (Pakistan)</option>
			<option value="PLW">Palaosienne (Palaos)</option>
			<option value="PSE">Palestinienne (Palestine)</option>
			<option value="PAN">Panaméenne (Panama)</option>
			<option value="PNG">Papouane-Néo-Guinéenne (Papouasie-Nouvelle-Guinée)</option>
			<option value="PRY">Paraguayenne (Paraguay)</option>
			<option value="PER">Péruvienne (Pérou)</option>
			<option value="PHL">Philippine (Philippines)</option>
			<option value="POL">Polonaise (Pologne)</option>
			<option value="PRT">Portugaise (Portugal)</option>
			<option value="QAT">Qatarienne (Qatar)</option>
			<option value="ROU">Roumaine (Roumanie)</option>
			<option value="RUS">Russe (Russie)</option>
			<option value="RWA">Rwandaise (Rwanda)</option>
			<option value="LCA">Saint-Lucienne (Sainte-Lucie)</option>
			<option value="SMR">Saint-Marinaise (Saint-Marin)</option>
			<option value="VCT">Saint-Vincentaise et Grenadine (Saint-Vincent-et-les Grenadines)</option>
			<option value="SLB">Salomonaise (Îles Salomon)</option>
			<option value="SLV">Salvadorienne (Salvador)</option>
			<option value="WSM">Samoane (Samoa)</option>
			<option value="STP">Santoméenne (Sao Tomé-et-Principe)</option>
			<option value="SAU">Saoudienne (Arabie saoudite)</option>
			<option value="SEN">Sénégalaise (Sénégal)</option>
			<option value="SRB">Serbe (Serbie)</option>
			<option value="SYC">Seychelloise (Seychelles)</option>
			<option value="SLE">Sierra-Léonaise (Sierra Leone)</option>
			<option value="SGP">Singapourienne (Singapour)</option>
			<option value="SVK">Slovaque (Slovaquie)</option>
			<option value="SVN">Slovène (Slovénie)</option>
			<option value="SOM">Somalienne (Somalie)</option>
			<option value="SDN">Soudanaise (Soudan)</option>
			<option value="LKA">Sri-Lankaise (Sri Lanka)</option>
			<option value="ZAF">Sud-Africaine (Afrique du Sud)</option>
			<option value="KOR">Sud-Coréenne (Corée du Sud)</option>
			<option value="SSD">Sud-Soudanaise (Soudan du Sud)</option>
			<option value="SWE">Suédoise (Suède)</option>
			<option value="CHE">Suisse (Suisse)</option>
			<option value="SUR">Surinamaise (Suriname)</option>
			<option value="SWZ">Swazie (Swaziland)</option>
			<option value="SYR">Syrienne (Syrie)</option>
			<option value="TJK">Tadjike (Tadjikistan)</option>
			<option value="TZA">Tanzanienne (Tanzanie)</option>
			<option value="TCD">Tchadienne (Tchad)</option>
			<option value="CZE">Tchèque (Tchéquie)</option>
			<option value="THA">Thaïlandaise (Thaïlande)</option>
			<option value="TGO">Togolaise (Togo)</option>
			<option value="TON">Tonguienne (Tonga)</option>
			<option value="TTO">Trinidadienne (Trinité-et-Tobago)</option>
			<option value="TUN">Tunisienne (Tunisie)</option>
			<option value="TKM">Turkmène (Turkménistan)</option>
			<option value="TUR">Turque (Turquie)</option>
			<option value="TUV">Tuvaluane (Tuvalu)</option>
			<option value="UKR">Ukrainienne (Ukraine)</option>
			<option value="URY">Uruguayenne (Uruguay)</option>
			<option value="VUT">Vanuatuane (Vanuatu)</option>
			<option value="VAT">Vaticane (Vatican)</option>
			<option value="VEN">Vénézuélienne (Venezuela)</option>
			<option value="VNM">Vietnamienne (Viêt Nam)</option>
			<option value="YEM">Yéménite (Yémen)</option>
			<option value="ZMB">Zambienne (Zambie)</option>
			<option value="ZWE">Zimbabwéenne (Zimbabwe)</option>
		</select><br><br>
	
		<label>8. Sexe :<span class="required">*</span></label><br>
		<input type="radio" name="sexe" value="masculin" <?php checked($saved_sexe, "homme"); ?>> Masculin<br>
		<input type="radio" name="sexe" value="feminin" <?php checked($saved_sexe, "femme"); ?>> Féminin<br><br>
	
		<label>9. État Civil :<span class="required">*</span></label><br>
		<input type="radio" name="etat_civil" value="celibataire" <?php checked($saved_etat_civil, "celibataire"); ?>> Célibataire<br>
		<input type="radio" name="etat_civil" value="marie" <?php checked($saved_etat_civil, "marie"); ?>> Marié(e)<br>
		<input type="radio" name="etat_civil" value="separe" <?php checked($saved_etat_civil, "separe"); ?>> Séparé(e)<br>
		<input type="radio" name="etat_civil" value="divorce" <?php checked($saved_etat_civil, "divorce"); ?>> Divorcé(e)<br>
		<input type="radio" name="etat_civil" value="veuf" <?php checked($saved_etat_civil, "veuf"); ?>> Veuf(Veuve)<br>
		<input type="radio" name="etat_civil" value="autre" <?php checked($saved_etat_civil, "autre"); ?>> Autre (veuillez préciser)<br>
		<input type="text" name="etat_civil_autre"><br><br>



		<label>10. Numéro national d’identité, le cas échéant :</label><br>
		<input type="text" name="num_national_identite"><br><br>
	
		<label>11. Type de document de voyage :<span class="required">*</span></label><br>
		<input type="radio" name="doc_voyage" value="passeport_ordinaire"> Passeport ordinaire<br>
		<input type="radio" name="doc_voyage" value="passeport_diplomatique"> Passeport diplomatique<br>
		<input type="radio" name="doc_voyage" value="passeport_service"> Passeport de service<br>
		<input type="radio" name="doc_voyage" value="passeport_officiel"> Passeport officiel<br>
		<input type="radio" name="doc_voyage" value="passeport_spécial"> Passeport spécial<br>
		<input type="radio" name="doc_voyage" value="autre"> Autre document de voyage (à préciser)
		<input type="text" name="doc_voyage_autre"><br><br>
	
		<label>12. Numéro du document de voyage :<span class="required">*</span></label><br>
		<input type="text" name="num_document" value="<?php echo esc_attr($saved_num_document); ?>" required><br><br>
	
		<label>13. Date de délivrance :<span class="required">*</span></label><br>
		<input type="date" name="date_delivrance" value="<?php echo esc_attr($saved_date_delivrance); ?>" required><br><br>
	
		<label>14. Date d’expiration :<span class="required">*</span></label><br>
		<input type="date" name="date_expiration" value="<?php echo esc_attr($saved_date_expiration); ?>" required><br><br>
	
		<label>15. Délivré par (pays) :<span class="required">*</span></label><br>
		<select id="country" name="delivre_par">
			<option value="">-- Sélectionnez un pays --</option>
			<option value="Afghanistan">Afghanistan</option>
			<option value="Afrique du Sud">Afrique du Sud</option>
			<option value="Albanie">Albanie</option>
			<option value="Algérie">Algérie</option>
			<option value="Allemagne">Allemagne</option>
			<option value="Andorre">Andorre</option>
			<option value="Angola">Angola</option>
			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
			<option value="Arabie Saoudite">Arabie Saoudite</option>
			<option value="Argentine">Argentine</option>
			<option value="Arménie">Arménie</option>
			<option value="Australie">Australie</option>
			<option value="Autriche">Autriche</option>
			<option value="Azerbaïdjan">Azerbaïdjan</option>
			<option value="Bahamas">Bahamas</option>
			<option value="Bahreïn">Bahreïn</option>
			<option value="Bangladesh">Bangladesh</option>
			<option value="Barbade">Barbade</option>
			<option value="Belgique">Belgique</option>
			<option value="Belize">Belize</option>
			<option value="Bénin">Bénin</option>
			<option value="Bhoutan">Bhoutan</option>
			<option value="Biélorussie">Biélorussie</option>
			<option value="Birmanie">Birmanie</option>
			<option value="Bolivie">Bolivie</option>
			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
			<option value="Botswana">Botswana</option>
			<option value="Brésil">Brésil</option>
			<option value="Brunei">Brunei</option>
			<option value="Bulgarie">Bulgarie</option>
			<option value="Burkina Faso">Burkina Faso</option>
			<option value="Burundi">Burundi</option>
			<option value="Cabo Verde">Cabo Verde</option>
			<option value="Cambodge">Cambodge</option>
			<option value="Cameroun">Cameroun</option>
			<option value="Canada">Canada</option>
			<option value="République centrafricaine">République centrafricaine</option>
			<option value="Tchad">Tchad</option>
			<option value="Chili">Chili</option>
			<option value="Chine">Chine</option>
			<option value="Chypre">Chypre</option>
			<option value="Colombie">Colombie</option>
			<option value="Comores">Comores</option>
			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
			<option value="Corée du Nord">Corée du Nord</option>
			<option value="Corée du Sud">Corée du Sud</option>
			<option value="Costa Rica">Costa Rica</option>
			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
			<option value="Croatie">Croatie</option>
			<option value="Cuba">Cuba</option>
			<option value="Danemark">Danemark</option>
			<option value="Djibouti">Djibouti</option>
			<option value="Dominique">Dominique</option>
			<option value="République dominicaine">République dominicaine</option>
			<option value="Egypte">Égypte</option>
			<option value="Emirats arabes unis">Émirats arabes unis</option>
			<option value="Equateur">Équateur</option>
			<option value="Erythrée">Érythrée</option>
			<option value="Espagne">Espagne</option>
			<option value="Estonie">Estonie</option>
			<option value="Eswatini">Eswatini</option>
			<option value="Etats-Unis">États-Unis</option>
			<option value="Ethiopie">Éthiopie</option>
			<option value="Fidji">Fidji</option>
			<option value="Finlande">Finlande</option>
			<option value="France">France</option>
			<option value="Gabon">Gabon</option>
			<option value="Gambie">Gambie</option>
			<option value="Géorgie">Géorgie</option>
			<option value="Ghana">Ghana</option>
			<option value="Grèce">Grèce</option>
			<option value="Grenade">Grenade</option>
			<option value="Guatemala">Guatemala</option>
			<option value="Guinée">Guinée</option>
			<option value="Guinée-Bissau">Guinée-Bissau</option>
			<option value="Guinée équatoriale">Guinée équatoriale</option>
			<option value="Guyana">Guyana</option>
			<option value="Haïti">Haïti</option>
			<option value="Honduras">Honduras</option>
			<option value="Hongrie">Hongrie</option>
			<option value="Inde">Inde</option>
			<option value="Indonésie">Indonésie</option>
			<option value="Irak">Irak</option>
			<option value="Iran">Iran</option>
			<option value="Irlande">Irlande</option>
			<option value="Islande">Islande</option>
			<option value="Israël">Israël</option>
			<option value="Italie">Italie</option>
			<option value="Jamaïque">Jamaïque</option>
			<option value="Japon">Japon</option>
			<option value="Jordanie">Jordanie</option>
			<option value="Kazakhstan">Kazakhstan</option>
			<option value="Kenya">Kenya</option>
			<option value="Kirghizistan">Kirghizistan</option>
			<option value="Kiribati">Kiribati</option>
			<option value="Kosovo">Kosovo</option>
			<option value="Koweït">Koweït</option>
			<option value="Laos">Laos</option>
			<option value="Lettonie">Lettonie</option>
			<option value="Liban">Liban</option>
			<option value="Libéria">Libéria</option>
			<option value="Libye">Libye</option>
			<option value="Liechtenstein">Liechtenstein</option>
			<option value="Lituanie">Lituanie</option>
			<option value="Luxembourg">Luxembourg</option>
			<option value="Macédoine du Nord">Macédoine du Nord</option>
			<option value="Madagascar">Madagascar</option>
			<option value="Malaisie">Malaisie</option>
			<option value="Malawi">Malawi</option>
			<option value="Maldives">Maldives</option>
			<option value="Mali">Mali</option>
			<option value="Malte">Malte</option>
			<option value="Maroc">Maroc</option>
			<option value="Marshall">Îles Marshall</option>
			<option value="Maurice">Maurice</option>
			<option value="Mauritanie">Mauritanie</option>
			<option value="Mexique">Mexique</option>
			<option value="Micronésie">Micronésie</option>
			<option value="Moldavie">Moldavie</option>
			<option value="Monaco">Monaco</option>
			<option value="Mongolie">Mongolie</option>
			<option value="Monténégro">Monténégro</option>
			<option value="Mozambique">Mozambique</option>
			<option value="Namibie">Namibie</option>
			<option value="Nauru">Nauru</option>
			<option value="Népal">Népal</option>
			<option value="Nicaragua">Nicaragua</option>
			<option value="Niger">Niger</option>
			<option value="Nigéria">Nigéria</option>
			<option value="Norvège">Norvège</option>
			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
			<option value="Oman">Oman</option>
			<option value="Ouganda">Ouganda</option>
			<option value="Ouzbékistan">Ouzbékistan</option>
			<option value="Pakistan">Pakistan</option>
			<option value="Palaos">Palaos</option>
			<option value="Palestine">Palestine</option>
			<option value="Panama">Panama</option>
			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
			<option value="Paraguay">Paraguay</option>
			<option value="Pays-Bas">Pays-Bas</option>
			<option value="Pérou">Pérou</option>
			<option value="Philippines">Philippines</option>
			<option value="Pologne">Pologne</option>
			<option value="Portugal">Portugal</option>
			<option value="République centrafricaine">République centrafricaine</option>
			<option value="République dominicaine">République dominicaine</option>
			<option value="Roumanie">Roumanie</option>
			<option value="Royaume-Uni">Royaume-Uni</option>
			<option value="Russie">Russie</option>
			<option value="Rwanda">Rwanda</option>
			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
			<option value="Saint-Marin">Saint-Marin</option>
			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
			<option value="Sainte-Lucie">Sainte-Lucie</option>
			<option value="Salvador">Salvador</option>
			<option value="Samoa">Samoa</option>
			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
			<option value="Sénégal">Sénégal</option>
			<option value="Serbie">Serbie</option>
			<option value="Seychelles">Seychelles</option>
			<option value="Sierra Leone">Sierra Leone</option>
			<option value="Singapour">Singapour</option>
			<option value="Slovaquie">Slovaquie</option>
			<option value="Slovénie">Slovénie</option>
			<option value="Somalie">Somalie</option>
			<option value="Soudan">Soudan</option>
			<option value="Soudan du Sud">Soudan du Sud</option>
			<option value="Sri Lanka">Sri Lanka</option>
			<option value="Suède">Suède</option>
			<option value="Suisse">Suisse</option>
			<option value="Suriname">Suriname</option>
			<option value="Syrie">Syrie</option>
			<option value="Tadjikistan">Tadjikistan</option>
			<option value="Tanzanie">Tanzanie</option>
			<option value="Tchad">Tchad</option>
			<option value="Tchécoslovaquie">Tchéquie</option>
			<option value="Thaïlande">Thaïlande</option>
			<option value="Timor-Leste">Timor-Leste</option>
			<option value="Togo">Togo</option>
			<option value="Tonga">Tonga</option>
			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
			<option value="Tunisie">Tunisie</option>
			<option value="Turkménistan">Turkménistan</option>
			<option value="Turquie">Turquie</option>
			<option value="Tuvalu">Tuvalu</option>
			<option value="Ukraine">Ukraine</option>
			<option value="Uruguay">Uruguay</option>
			<option value="Vanuatu">Vanuatu</option>
			<option value="Vatican">Vatican</option>
			<option value="Venezuela">Venezuela</option>
			<option value="Vietnam">Vietnam</option>
			<option value="Yémen">Yémen</option>
			<option value="Zambie">Zambie</option>
			<option value="Zimbabwe">Zimbabwe</option>
		</select><br><br>
	
		<label>16. Adresse du domicile (n°, rue, ville, code postal, pays) <span class="required">*</span></label><br>
		<div id="adresse">
    		<input type="text" name="adresse_adresse" value="<?php echo esc_attr($saved_adresse_accueil); ?>"><br><br>
    		
    		<div style="display: flex; gap: 10px;justify-content: space-between;">
    		    <div>
    		        <label>Code postal</label>
    		        <input type="text" name="cp_adresse" value="<?php echo esc_attr($saved_cp_accueil); ?>">
    		    </div>
    		    <div>
    		        <label>Ville</label>
    		        <input type="text" name="ville_adresse" value="<?php echo esc_attr($saved_ville_accueil); ?>">
    		    </div>
    		    <div>
    		        <label>Pays</label>
    		        <select id="country" name="pays_adresse" value="<?php echo esc_attr($saved_pays_accueil); ?>">
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
    		    </div>
    		</div>
	    </div>
		<input type="text" name="adresse" style="display:none"><br><br>
	
		<label>17. Adresse électronique <span class="required">*</span></label><br>
		<input type="text" name="mail" value="<?php echo esc_attr($mail); ?>" required><br><br>
	
		<label>18. Numéro(s) de téléphone <span class="required">*</span></label><br>
		<input type="text" name="phone" value="<?php echo esc_attr($saved_phone); ?>" required pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX"><br><br>
	
		<label>19. En cas de résidence dans un pays autre que celui de la nationalité actuelle, veuillez indiquer :</label><br>
		<label>Numéro du titre de séjour</label><br>
		<input type="text" name="num_resident"><br>
	
		<label>Date de délivrance</label><br>
		<input type="date" name="autre_date_delivrance"><br>
	
		<label>Date d'expiration</label><br>
		<input type="date" name="autre_date_expiration"><br><br>
		
		<label>Situation professionnelle :<span class="required">*</span></label><br>
		<select name="situation_professionnelle">
          <option value="">-- Sélectionnez une situation professionnelle --</option>
          <option value="En activité" <?php selected($saved_situation_professionnelle, 'En activité'); ?>>En activité</option>
          <option value="Sans profession" <?php selected($saved_situation_professionnelle, 'Sans profession'); ?>>Sans profession</option>
          <option value="Chômeur" <?php selected($saved_situation_professionnelle, 'Chômeur'); ?>>Chômeur</option>
          <option value="Retraité" <?php selected($saved_situation_professionnelle, 'Retraité'); ?>>Retraité</option>
          <option value="Etudiant" <?php selected($saved_situation_professionnelle, 'Etudiant'); ?>>Etudiant</option>
        </select><br><br>
	
		<div id="profession">
			<label>20. Activité professionnelle <span class="required">*</span></label><br>
			<select name="profession" required>
				<option value="">&nbsp;</option>
				<option value="65001" <?php selected($saved_profession, '65001'); ?>>Agriculteur</option>
						<option value="65002" <?php selected($saved_profession, '65002'); ?>>Architecte</option>
						<option value="65003" <?php selected($saved_profession, '65003'); ?>>Artisan</option>
						<option value="65004" <?php selected($saved_profession, '65004'); ?>>Artiste</option>
						<option value="65005" <?php selected($saved_profession, '65005'); ?>>Autre</option>
						<option value="65006" <?php selected($saved_profession, '65006'); ?>>Autre technicien</option>
						<option value="66001" <?php selected($saved_profession, '66001'); ?>>Banquier</option>
						<option value="67001" <?php selected($saved_profession, '67001'); ?>>Cadre d'entreprise</option>
						<option value="67002" <?php selected($saved_profession, '67002'); ?>>Chauffeur, routier</option>
						<option value="67003" <?php selected($saved_profession, '67003'); ?>>Chef d'entreprise</option>
						<option value="67004" <?php selected($saved_profession, '67004'); ?>>Chercheur, scientifique</option>
						<option value="67005" <?php selected($saved_profession, '67005'); ?>>Chimiste</option>
						<option value="67006" <?php selected($saved_profession, '67006'); ?>>Chômeur</option>
						<option value="67007" <?php selected($saved_profession, '67007'); ?>>Clergé, religieux</option>
						<option value="67008" <?php selected($saved_profession, '67008'); ?>>Commerçant</option>
						<option value="68001" <?php selected($saved_profession, '68001'); ?>>Diplomate</option>
						<option value="69001" <?php selected($saved_profession, '69001'); ?>>Electronicien</option>
						<option value="69005" <?php selected($saved_profession, '69005'); ?>>Elève, Etudiant, stagiaire</option>
						<option value="69002" <?php selected($saved_profession, '69002'); ?>>Employé</option>
						<option value="69003" <?php selected($saved_profession, '69003'); ?>>Employé prive au service de diplomate</option>
						<option value="69004" <?php selected($saved_profession, '69004'); ?>>Enseignant</option>
						<option value="70001" <?php selected($saved_profession, '70001'); ?>>Fonctionnaire</option>
						<option value="72001" <?php selected($saved_profession, '72001'); ?>>Homme politique</option>
						<option value="73001" <?php selected($saved_profession, '73001'); ?>>Informaticien</option>
						<option value="74001" <?php selected($saved_profession, '74001'); ?>>Journaliste</option>
						<option value="77001" <?php selected($saved_profession, '77001'); ?>>Magistrat</option>
						<option value="77002" <?php selected($saved_profession, '77002'); ?>>Marin</option>
						<option value="77003" <?php selected($saved_profession, '77003'); ?>>Mode, cosmétique</option>
						<option value="79001" <?php selected($saved_profession, '79001'); ?>>Ouvrier</option>
						<option value="80001" <?php selected($saved_profession, '80001'); ?>>Personnel de service, administratif ou technique (postes dipl./cons.)</option>
						<option value="80002" <?php selected($saved_profession, '80002'); ?>>Policier, militaire</option>
						<option value="80003" <?php selected($saved_profession, '80003'); ?>>Profession juridique</option>
						<option value="80004" <?php selected($saved_profession, '80004'); ?>>Profession libérale</option>
						<option value="80005" <?php selected($saved_profession, '80005'); ?>>Profession médicale et paramédicale</option>
						<option value="82001" <?php selected($saved_profession, '82001'); ?>>Retraite</option>
						<option value="83001" <?php selected($saved_profession, '83001'); ?>>Sans profession</option>
						<option value="83002" <?php selected($saved_profession, '83002'); ?>>Sportif</option>
			</select><br><br>

			<label>Secteur d'activité :<span class="required">*</span></label><br>
			<select name="secteur_activite">
              <option value="" <?php selected($saved_secteur_activite, ''); ?>>-- Sélectionnez un secteur d'activité --</option>
              <option value="Activités de services administratifs et de soutien" <?php selected($saved_secteur_activite, 'Activités de services administratifs et de soutien'); ?>>Activités de services administratifs et de soutien</option>
              <option value="Activités des ménages en tant qu'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre" <?php selected($saved_secteur_activite, "Activités des ménages en tant qu'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre"); ?>>
                Activités des ménages en tant qu'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre
              </option>
              <option value="Activités extra-territoriales" <?php selected($saved_secteur_activite, 'Activités extra-territoriales'); ?>>Activités extra-territoriales</option>
              <option value="Activités financières et d'assurance" <?php selected($saved_secteur_activite, 'Activités financières et d\'assurance'); ?>>Activités financières et d'assurance</option>
              <option value="Activités immobilières" <?php selected($saved_secteur_activite, 'Activités immobilières'); ?>>Activités immobilières</option>
              <option value="Activités spécialisées, scientifiques et techniques" <?php selected($saved_secteur_activite, 'Activités spécialisées, scientifiques et techniques'); ?>>Activités spécialisées, scientifiques et techniques</option>
              <option value="Administration publique" <?php selected($saved_secteur_activite, 'Administration publique'); ?>>Administration publique</option>
              <option value="Agriculture, sylviculture et pêche" <?php selected($saved_secteur_activite, 'Agriculture, sylviculture et pêche'); ?>>Agriculture, sylviculture et pêche</option>
              <option value="Arts, spectacles et activités récréatives" <?php selected($saved_secteur_activite, 'Arts, spectacles et activités récréatives'); ?>>Arts, spectacles et activités récréatives</option>
              <option value="Autres activités" <?php selected($saved_secteur_activite, 'Autres activités'); ?>>Autres activités</option>
              <option value="Autres activités de services" <?php selected($saved_secteur_activite, 'Autres activités de services'); ?>>Autres activités de services</option>
              <option value="Commerce; réparation d'automobiles et de motocycles" <?php selected($saved_secteur_activite, 'Commerce; réparation d\'automobiles et de motocycles'); ?>>Commerce; réparation d'automobiles et de motocycles</option>
              <option value="Construction" <?php selected($saved_secteur_activite, 'Construction'); ?>>Construction</option>
              <option value="Enseignement" <?php selected($saved_secteur_activite, 'Enseignement'); ?>>Enseignement</option>
              <option value="Hébergement et restauration" <?php selected($saved_secteur_activite, 'Hébergement et restauration'); ?>>Hébergement et restauration</option>
              <option value="Industrie manufacturière" <?php selected($saved_secteur_activite, 'Industrie manufacturière'); ?>>Industrie manufacturière</option>
              <option value="Industries extractives" <?php selected($saved_secteur_activite, 'Industries extractives'); ?>>Industries extractives</option>
              <option value="Information et communication" <?php selected($saved_secteur_activite, 'Information et communication'); ?>>Information et communication</option>
              <option value="Production et distribution d'eau; assainissement, gestion des déchets et dépollution" <?php selected($saved_secteur_activite, 'Production et distribution d\'eau; assainissement, gestion des déchets et dépollution'); ?>>
                Production et distribution d'eau; assainissement, gestion des déchets et dépollution
              </option>
              <option value="Production et distribution d'électricité, de gaz, de vapeur et d'air conditionné" <?php selected($saved_secteur_activite, 'Production et distribution d\'électricité, de gaz, de vapeur et d\'air conditionné'); ?>>
                Production et distribution d'électricité, de gaz, de vapeur et d'air conditionné
              </option>
              <option value="Santé humaine et action sociale" <?php selected($saved_secteur_activite, 'Santé humaine et action sociale'); ?>>Santé humaine et action sociale</option>
              <option value="Transports et entreposage" <?php selected($saved_secteur_activite, 'Transports et entreposage'); ?>>Transports et entreposage</option>
            </select><br><br>
    		
    		<label>Nom de l'employeur ou de l'établissement d'enseignement <span class="required">*</span></label><br>
    		<input type="text" name="nom_employeur" value="<?php echo esc_attr($saved_nom_employeur); ?>" required><br><br>
    		
    		<label>Adresse de l'employeur ou de l'établissement d'enseignement <span class="required">*</span></label><br>
    		<input type="text" name="adresse_employeur" value="<?php echo esc_attr($saved_adresse_employeur); ?>" required><br><br>
    		
    		<div style="display: flex; gap: 10px;justify-content: space-between;">
    		    <div>
    		        <label>Code postal</label><br>
    		        <input type="text" name="cp_employeur" value="<?php echo esc_attr($saved_cp_employeur); ?>">
    		    </div>
    		    <div>
    		        <label>Ville  <span class="required">*</span></label><br>
    		        <input type="text" name="ville_employeur" value="<?php echo esc_attr($saved_ville_employeur); ?>" required>
    		    </div>
    		    <div>
    		        <label>Pays ou territoire <span class="required">*</span></label><br>
    		        <select id="country" name="pays_employeur" value="<?php echo esc_attr($saved_pays_employeur); ?>" required>
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
    		    </div>
    		</div><br>
    		
    		<label>Numéro de téléphone portable :<span class="required">*</span></label><br>
    		<input type="text" name="num_employeur" value="<?php echo esc_attr($saved_num_employeur); ?>" required><br><br>
    		
    		<label>Adresse e-mail :<span class="required">*</span></label><br>
    		<input type="text" name="mail_employeur" value="<?php echo esc_attr($saved_mail_employeur); ?>" required><br><br>
	    </div>
	
		<label>21. Employeur (Nom, adresse, courriel, n° téléphone) - Pour les étudiants, nom et adresse de l'établissement d'enseignement <span class="required">*</span></label><br>
		<textarea name="employeur" readonly><?php echo esc_textarea($saved_employeur); ?></textarea><br><br>
	
		<label>22. Je sollicite un visa pour le motif suivant :<span class="required">*</span></label><br>
        <input type="radio" name="objet" value="etudes" <?php checked($saved_objet, "etudes"); ?>> Études<br>
        <input type="radio" name="objet" value="installation_familiale_majeur" <?php checked($saved_objet, "installation_familiale_majeur"); ?>> Installation familiale ou privée (majeur)<br>
        <input type="radio" name="objet" value="installation_familiale_mineur" <?php checked($saved_objet, "installation_familiale_mineur"); ?>> Installation familiale ou privée (mineur)<br>
        <input type="radio" name="objet" value="fonctions_officielles" <?php checked($saved_objet, "fonctions_officielles"); ?>> Prise de fonctions officielles<br>
        <input type="radio" name="objet" value="stage_salarie" <?php checked($saved_objet, "stage_salarie"); ?>> Stage salarié<br>
        <input type="radio" name="objet" value="travailler" <?php checked($saved_objet, "travailler"); ?>> Travailler<br>
        <input type="radio" name="objet" value="visa_retour" <?php checked($saved_objet, "visa_retour"); ?>> Visa de retour<br>
        <input type="radio" name="objet" value="visiteur" <?php checked($saved_objet, "visiteur"); ?>> Visiteur<br>
        <input type="radio" name="objet" value="autre" <?php checked($saved_objet, "autre"); ?>> Autre (à préciser) :
        <input type="text" name="objet_autre" value="<?php echo esc_attr($saved_objet_autre); ?>"><br><br>
		
		<label>Accueilli (e) à titre privé par</label>
		<div style="display: flex;align-items: center;justify-content: space-around;margin: 10px 0px;">
		    <div id="go-personne">Une personne</div>
		    <div id="go-hotel">Hôtel ou lieu d’hébergement</div>
		    <div id="go-entreprise">Une Entreprise ou Organisation</div>
		</div>
		
		<div id="personne" style="display:none">
		    <label>Accueilli (e) à titre privé par une personne</label>
		    <div style="display: flex; gap: 10px;justify-content: space-between;">
		        <div>
		            <label>Nom</label>
		            <input type="text" name="nom_accueil" value="<?php echo esc_attr($saved_nom_accueil); ?>">
		        </div>
		        <div>
		            <label>Prenom</label>
		            <input type="text" name="prenom_accueil" value="<?php echo esc_attr($saved_prenom_accueil); ?>">
		        </div>
		    </div>
    		
    		<label>Adresse</label><br>
    		<input type="text" name="adresse_accueil" value="<?php echo esc_attr($saved_adresse_accueil); ?>"><br><br>
    		
    		<div style="display: flex; gap: 10px;justify-content: space-between;">
    		    <div>
    		        <label>Code postal</label>
    		        <input type="text" name="cp_accueil" value="<?php echo esc_attr($saved_cp_accueil); ?>">
    		    </div>
    		    <div>
    		        <label>Ville</label>
    		        <input type="text" name="ville_accueil" value="<?php echo esc_attr($saved_ville_accueil); ?>">
    		    </div>
    		    <div>
    		        <label>Pays</label>
    		        <select id="country" name="pays_accueil" value="<?php echo esc_attr($saved_pays_accueil); ?>">
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
    		    </div>
    		</div><br>
    		
    		<label>Numéro de téléphone portable :</label><br>
    		<input type="text" name="num_accueil" value="<?php echo esc_attr($saved_num_accueil); ?>" pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX"><br><br>
    		
    		<label>Adresse e-mail :</label><br>
    		<input type="text" name="mail_accueil" value="<?php echo esc_attr($saved_mail_accueil); ?>"><br><br>
	    </div>
	    
		<div id="hotel" style="display:none">
		    <label>23.b. Accueilli (e) à titre privé à l’hôtel ou dans une lieu d’hébergement</label>
		    <div>
		        <div>
		            <label>Nom de l’hôtel/hébergement</label>
		            <input type="text" name="nom_hotel" value="<?php echo esc_attr($saved_nom_hotel); ?>">
		        </div>
		    </div>
    		
    		<label>Adresse</label><br>
    		<input type="text" name="adresse_hotel" value="<?php echo esc_attr($saved_adresse_hotel); ?>"><br><br>
    		
    		<div style="display: flex; gap: 10px;justify-content: space-between;">
    		    <div>
    		        <label>Code postal</label>
    		        <input type="text" name="cp_hotel" value="<?php echo esc_attr($saved_cp_hotel); ?>">
    		    </div>
    		    <div>
    		        <label>Ville</label>
    		        <input type="text" name="ville_hotel" value="<?php echo esc_attr($saved_ville_hotel); ?>">
    		    </div>
    		    <div>
    		        <label>Pays</label>
    		        <select id="country" name="pays_hotel" value="<?php echo esc_attr($saved_pays_hotel); ?>">
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
    		    </div>
    		</div><br>
    		
    		<label>Numéro de téléphone portable :</label><br>
    		<input type="text" name="num_hotel" value="<?php echo esc_attr($saved_num_hotel); ?>" pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX"><br><br>
    		
    		<label>Adresse e-mail :</label><br>
    		<input type="text" name="mail_hotel" value="<?php echo esc_attr($saved_mail_hotel); ?>">
	    </div>
		
		<div id="entreprise" style="display:none">
		    <label>24. Accueilli (e) par une Entreprise ou Organisation</label><br>
    		<input type="text" name="nom_entreprise" value="<?php echo esc_attr($saved_nom_entreprise); ?>"><br><br>
    		
    		<label>Adresse</label><br>
    		<input type="text" name="adresse_entreprise" value="<?php echo esc_attr($saved_adresse_entreprise); ?>"><br><br>
    		
    		<div style="display: flex;justify-content: space-between;">
    		    <div>
    		        <label>Code postal</label>
    		        <input type="text" name="cp_entreprise" value="<?php echo esc_attr($saved_cp_entreprise); ?>">
    		    </div>
    		    <div>
    		        <label>Ville</label>
    		        <input type="text" name="ville_entreprise" value="<?php echo esc_attr($saved_ville_entreprise); ?>">
    		    </div>
    		    <div>
    		        <label>Pays</label>
    		        <select id="country" name="pays_entreprise" value="<?php echo esc_attr($saved_pays_entreprise); ?>">
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
    		    </div>
    		</div><br>
    		
    		<label>Numéro de téléphone portable de l’entreprise /l’organisation :</label><br>
    	    <input type="text" name="phone_hote" pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX" value="<?php echo esc_attr($saved_phone_hote); ?>"><br><br>
    	    
    	    <label>Adresse e-mail de l’entreprise /l’organisation :</label><br>
        	<input type="text" name="mail_entreprise" value="<?php echo esc_attr($saved_mail_entreprise); ?>">
		</div>
    	
    	<div id="contact" style="display:none">
    	    <label>Coordonnées du contact :</label>
		    <div style="display: flex;justify-content: space-between;gap: 10px;">
		        <div>
		            <label>Nom de la personne de contact</label>
		            <input type="text" name="nom_contact" value="<?php echo esc_attr($saved_nom_contact); ?>">
		        </div>
		        <div>
		            <label>Prénom de la personne de contact</label>
		            <input type="text" name="prenom_contact" value="<?php echo esc_attr($saved_prenom_contact); ?>">
		        </div>
		    </div>
    		
    		<label>Adresse</label><br>
    		<input type="text" name="adresse_contact" value="<?php echo esc_attr($saved_adresse_contact); ?>"><br><br>
    		
    		<div style="display: flex; gap: 10px;justify-content: space-between;gap: 10px;">
    		    <div>
    		        <label>Code postal</label>
    		        <input type="text" name="cp_contact" value="<?php echo esc_attr($saved_cp_contact); ?>">
    		    </div>
    		    <div>
    		        <label>Ville</label>
    		        <input type="text" name="ville_contact" value="<?php echo esc_attr($saved_ville_contact); ?>">
    		    </div>
    		    <div>
    		        <label>Pays</label>
    		        <select id="country_contact" name="pays_contact" value="<?php echo esc_attr($saved_pays_contact); ?>">
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
    		    </div>
    		</div><br>
    		
    		<label>Numéro de téléphone portable :</label><br>
    		<input type="text" name="num_contact" value="<?php echo esc_attr($saved_num_contact); ?>" pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX"><br><br>
    		
    		<label>Adresse e-mail :</label><br>
    		<input type="text" name="mail_contact" value="<?php echo esc_attr($saved_mail_contact); ?>"><br><br>
	    </div>
	
		<label>25. Nom, adresse, courriel et n° téléphone en France de l'employeur / de l'établissement d'accueil / du membre de famille invitant, ...etc</label><br>
		<textarea name="info_employeur" id="info_employeur"></textarea><br>
	
		<label>26. Quelle sera votre adresse en France pendant votre séjour ? <span class="required">*</span></label><br>
		<input type="text" name="adresse_sejour" value="<?php echo esc_attr($saved_adresse_inviteur); ?>" required><br><br>
	
		<label>27. Date d'entrée prévue sur le territoire de la France, ou dans l'espace Schengen en cas de transit (jour-mois-année) <span class="required">*</span></label><br>
		<input type="date" name="arrival_date" value="<?php echo esc_attr($saved_arrival_date); ?>" required><br><br>
		
		<label>Date de départ prévue de l’espace Schengen après le 1er séjour envisagé :<span class="required">*</span></label><br>
		<input type="date" name="departure_date" value="<?php echo esc_attr($saved_departure_date); ?>" required><br><br>
	
		<hr <?php if ($age > 18) echo 'style="display:none;"'; ?>>

		<div id="autorite-parentale" <?php if ($age > 18) echo 'style="display:none;"'; ?>>
			<div class="preamble-notice">
				Parent n°1 ou Tuteur légal n°1<br>
				<label>Statut</label><br>
			<select name="statut_tuteur_legal_1">
				<option value="">-- Sélectionner un statut si vous êtes concerné --</option>
				<option value="Apatride" <?php selected($saved_statut_tuteur_legal_1, 'Apatride'); ?>>Apatride</option>
				<option value="Réfugié 1946/51" <?php selected($saved_statut_tuteur_legal_1, 'Réfugié 1946/51'); ?>>Réfugié 1946/51</option>
				<option value="Réfugié hs conv" <?php selected($saved_statut_tuteur_legal_1, 'Réfugié hs conv'); ?>>Réfugié hs conv</option>
			</select><br><br>
			</div>
		    <div style="display: flex; gap: 10px;justify-content: space-between;">
		        <div>
		            <label>Nom</label>
		            <input type="text" name="nom_tuteur_legal_1" value="<?php echo esc_attr($saved_nom_tuteur_legal_1); ?>" required>
		        </div>
		        <div>
		            <label>Prénom</label>
		            <input type="text" name="prenom_tuteur_legal_1" value="<?php echo esc_attr($saved_prenom_tuteur_legal_1); ?>">
		        </div>
		    </div>
    		
    		<label>Adresse</label><br>
    		<input type="text" name="adresse_tuteur_legal_1" value="<?php echo esc_attr($saved_adresse_tuteur_legal_1); ?>" required><br><br>
    		
    		<div style="display: flex; gap: 10px;justify-content: space-between;">
    		    <div>
    		        <label>Code postal</label>
    		        <input type="text" name="code_postal_tuteur_legal_1" value="<?php echo esc_attr($saved_code_postal_tuteur_legal_1); ?>">
    		    </div>
    		    <div>
    		        <label>Ville</label>
    		        <input type="text" name="ville_tuteur_legal_1" value="<?php echo esc_attr($saved_ville_tuteur_legal_1); ?>" required>
    		    </div>
    		    <div>
    		        <label>Pays</label>
    		            <select id="country_contact" name="pays_tuteur_legal_1" value="<?php echo esc_attr($saved_pays_tuteur_legal_1); ?>" required>
                            <option value="">-- Sélectionnez un pays --</option>
                            <option value="Afghanistan">Afghanistan</option>
                            <option value="Afrique du Sud">Afrique du Sud</option>
                            <option value="Albanie">Albanie</option>
                            <option value="Algérie">Algérie</option>
                            <option value="Allemagne">Allemagne</option>
                            <option value="Andorre">Andorre</option>
                            <option value="Angola">Angola</option>
                            <option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
                            <option value="Arabie Saoudite">Arabie Saoudite</option>
                            <option value="Argentine">Argentine</option>
                            <option value="Arménie">Arménie</option>
                            <option value="Australie">Australie</option>
                            <option value="Autriche">Autriche</option>
                            <option value="Azerbaïdjan">Azerbaïdjan</option>
                            <option value="Bahamas">Bahamas</option>
                            <option value="Bahreïn">Bahreïn</option>
                            <option value="Bangladesh">Bangladesh</option>
                            <option value="Barbade">Barbade</option>
                            <option value="Belgique">Belgique</option>
                            <option value="Belize">Belize</option>
                            <option value="Bénin">Bénin</option>
                            <option value="Bhoutan">Bhoutan</option>
                            <option value="Biélorussie">Biélorussie</option>
                            <option value="Birmanie">Birmanie</option>
                            <option value="Bolivie">Bolivie</option>
                            <option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
                            <option value="Botswana">Botswana</option>
                            <option value="Brésil">Brésil</option>
                            <option value="Brunei">Brunei</option>
                            <option value="Bulgarie">Bulgarie</option>
                            <option value="Burkina Faso">Burkina Faso</option>
                            <option value="Burundi">Burundi</option>
                            <option value="Cabo Verde">Cabo Verde</option>
                            <option value="Cambodge">Cambodge</option>
                            <option value="Cameroun">Cameroun</option>
                            <option value="Canada">Canada</option>
                            <option value="République centrafricaine">République centrafricaine</option>
                            <option value="Tchad">Tchad</option>
                            <option value="Chili">Chili</option>
                            <option value="Chine">Chine</option>
                            <option value="Chypre">Chypre</option>
                            <option value="Colombie">Colombie</option>
                            <option value="Comores">Comores</option>
                            <option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
                            <option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
                            <option value="Corée du Nord">Corée du Nord</option>
                            <option value="Corée du Sud">Corée du Sud</option>
                            <option value="Costa Rica">Costa Rica</option>
                            <option value="Côte d’Ivoire">Côte d’Ivoire</option>
                            <option value="Croatie">Croatie</option>
                            <option value="Cuba">Cuba</option>
                            <option value="Danemark">Danemark</option>
                            <option value="Djibouti">Djibouti</option>
                            <option value="Dominique">Dominique</option>
                            <option value="République dominicaine">République dominicaine</option>
                            <option value="Egypte">Égypte</option>
                            <option value="Emirats arabes unis">Émirats arabes unis</option>
                            <option value="Equateur">Équateur</option>
                            <option value="Erythrée">Érythrée</option>
                            <option value="Espagne">Espagne</option>
                            <option value="Estonie">Estonie</option>
                            <option value="Eswatini">Eswatini</option>
                            <option value="Etats-Unis">États-Unis</option>
                            <option value="Ethiopie">Éthiopie</option>
                            <option value="Fidji">Fidji</option>
                            <option value="Finlande">Finlande</option>
                            <option value="France">France</option>
                            <option value="Gabon">Gabon</option>
                            <option value="Gambie">Gambie</option>
                            <option value="Géorgie">Géorgie</option>
                            <option value="Ghana">Ghana</option>
                            <option value="Grèce">Grèce</option>
                            <option value="Grenade">Grenade</option>
                            <option value="Guatemala">Guatemala</option>
                            <option value="Guinée">Guinée</option>
                            <option value="Guinée-Bissau">Guinée-Bissau</option>
                            <option value="Guinée équatoriale">Guinée équatoriale</option>
                            <option value="Guyana">Guyana</option>
                            <option value="Haïti">Haïti</option>
                            <option value="Honduras">Honduras</option>
                            <option value="Hongrie">Hongrie</option>
                            <option value="Inde">Inde</option>
                            <option value="Indonésie">Indonésie</option>
                            <option value="Irak">Irak</option>
                            <option value="Iran">Iran</option>
                            <option value="Irlande">Irlande</option>
                            <option value="Islande">Islande</option>
                            <option value="Israël">Israël</option>
                            <option value="Italie">Italie</option>
                            <option value="Jamaïque">Jamaïque</option>
                            <option value="Japon">Japon</option>
                            <option value="Jordanie">Jordanie</option>
                            <option value="Kazakhstan">Kazakhstan</option>
                            <option value="Kenya">Kenya</option>
                            <option value="Kirghizistan">Kirghizistan</option>
                            <option value="Kiribati">Kiribati</option>
                            <option value="Kosovo">Kosovo</option>
                            <option value="Koweït">Koweït</option>
                            <option value="Laos">Laos</option>
                            <option value="Lettonie">Lettonie</option>
                            <option value="Liban">Liban</option>
                            <option value="Libéria">Libéria</option>
                            <option value="Libye">Libye</option>
                            <option value="Liechtenstein">Liechtenstein</option>
                            <option value="Lituanie">Lituanie</option>
                            <option value="Luxembourg">Luxembourg</option>
                            <option value="Macédoine du Nord">Macédoine du Nord</option>
                            <option value="Madagascar">Madagascar</option>
                            <option value="Malaisie">Malaisie</option>
                            <option value="Malawi">Malawi</option>
                            <option value="Maldives">Maldives</option>
                            <option value="Mali">Mali</option>
                            <option value="Malte">Malte</option>
                            <option value="Maroc">Maroc</option>
                            <option value="Marshall">Îles Marshall</option>
                            <option value="Maurice">Maurice</option>
                            <option value="Mauritanie">Mauritanie</option>
                            <option value="Mexique">Mexique</option>
                            <option value="Micronésie">Micronésie</option>
                            <option value="Moldavie">Moldavie</option>
                            <option value="Monaco">Monaco</option>
                            <option value="Mongolie">Mongolie</option>
                            <option value="Monténégro">Monténégro</option>
                            <option value="Mozambique">Mozambique</option>
                            <option value="Namibie">Namibie</option>
                            <option value="Nauru">Nauru</option>
                            <option value="Népal">Népal</option>
                            <option value="Nicaragua">Nicaragua</option>
                            <option value="Niger">Niger</option>
                            <option value="Nigéria">Nigéria</option>
                            <option value="Norvège">Norvège</option>
                            <option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
                            <option value="Oman">Oman</option>
                            <option value="Ouganda">Ouganda</option>
                            <option value="Ouzbékistan">Ouzbékistan</option>
                            <option value="Pakistan">Pakistan</option>
                            <option value="Palaos">Palaos</option>
                            <option value="Palestine">Palestine</option>
                            <option value="Panama">Panama</option>
                            <option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
                            <option value="Paraguay">Paraguay</option>
                            <option value="Pays-Bas">Pays-Bas</option>
                            <option value="Pérou">Pérou</option>
                            <option value="Philippines">Philippines</option>
                            <option value="Pologne">Pologne</option>
                            <option value="Portugal">Portugal</option>
                            <option value="République centrafricaine">République centrafricaine</option>
                            <option value="République dominicaine">République dominicaine</option>
                            <option value="Roumanie">Roumanie</option>
                            <option value="Royaume-Uni">Royaume-Uni</option>
                            <option value="Russie">Russie</option>
                            <option value="Rwanda">Rwanda</option>
                            <option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
                            <option value="Saint-Marin">Saint-Marin</option>
                            <option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
                            <option value="Sainte-Lucie">Sainte-Lucie</option>
                            <option value="Salvador">Salvador</option>
                            <option value="Samoa">Samoa</option>
                            <option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
                            <option value="Sénégal">Sénégal</option>
                            <option value="Serbie">Serbie</option>
                            <option value="Seychelles">Seychelles</option>
                            <option value="Sierra Leone">Sierra Leone</option>
                            <option value="Singapour">Singapour</option>
                            <option value="Slovaquie">Slovaquie</option>
                            <option value="Slovénie">Slovénie</option>
                            <option value="Somalie">Somalie</option>
                            <option value="Soudan">Soudan</option>
                            <option value="Soudan du Sud">Soudan du Sud</option>
                            <option value="Sri Lanka">Sri Lanka</option>
                            <option value="Suède">Suède</option>
                            <option value="Suisse">Suisse</option>
                            <option value="Suriname">Suriname</option>
                            <option value="Syrie">Syrie</option>
                            <option value="Tadjikistan">Tadjikistan</option>
                            <option value="Tanzanie">Tanzanie</option>
                            <option value="Tchad">Tchad</option>
                            <option value="Tchécoslovaquie">Tchéquie</option>
                            <option value="Thaïlande">Thaïlande</option>
                            <option value="Timor-Leste">Timor-Leste</option>
                            <option value="Togo">Togo</option>
                            <option value="Tonga">Tonga</option>
                            <option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
                            <option value="Tunisie">Tunisie</option>
                            <option value="Turkménistan">Turkménistan</option>
                            <option value="Turquie">Turquie</option>
                            <option value="Tuvalu">Tuvalu</option>
                            <option value="Ukraine">Ukraine</option>
                            <option value="Uruguay">Uruguay</option>
                            <option value="Vanuatu">Vanuatu</option>
                            <option value="Vatican">Vatican</option>
                            <option value="Venezuela">Venezuela</option>
                            <option value="Vietnam">Vietnam</option>
                            <option value="Yémen">Yémen</option>
                            <option value="Zambie">Zambie</option>
                            <option value="Zimbabwe">Zimbabwe</option>
                        </select>
    		    </div>
    		</div><br>
    		
    		<label>Numéro de téléphone portable :</label><br>
    		<input type="text" name="telephone_tuteur_legal_1" value="<?php echo esc_attr($saved_telephone_tuteur_legal_1); ?>" pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX" required><br><br>
    		
    		<label>Adresse e-mail :</label><br>
    		<input type="text" name="email_tuteur_legal_1" value="<?php echo esc_attr($saved_email_tuteur_legal_1); ?>" required><br><br>
			<label>Nationalité actuelle :<span class="required">*</span></label><br>
                    <select name="nationalite_tuteur_legal_1" required>
                        <option value="">-- Sélectionnez une nationalité --</option>
                        <option value="AFG" <?php selected($saved_nationalite_tuteur_legal_1, 'AFG'); ?>>Afghane (Afghanistan)</option>
                        <option value="ALB" <?php selected($saved_nationalite_tuteur_legal_1, 'ALB'); ?>>Albanaise (Albanie)</option>
                        <option value="DZA" <?php selected($saved_nationalite_tuteur_legal_1, 'DZA'); ?>>Algérienne (Algérie)</option>
                        <option value="DEU" <?php selected($saved_nationalite_tuteur_legal_1, 'DEU'); ?>>Allemande (Allemagne)</option>
                        <option value="USA" <?php selected($saved_nationalite_tuteur_legal_1, 'USA'); ?>>Americaine (États-Unis)</option>
                        <option value="AND" <?php selected($saved_nationalite_tuteur_legal_1, 'AND'); ?>>Andorrane (Andorre)</option>
                        <option value="AGO" <?php selected($saved_nationalite_tuteur_legal_1, 'AGO'); ?>>Angolaise (Angola)</option>
                        <option value="ATG" <?php selected($saved_nationalite_tuteur_legal_1, 'ATG'); ?>>Antiguaise-et-Barbudienne (Antigua-et-Barbuda)</option>
                        <option value="ARG" <?php selected($saved_nationalite_tuteur_legal_1, 'ARG'); ?>>Argentine (Argentine)</option>
                        <option value="ARM" <?php selected($saved_nationalite_tuteur_legal_1, 'ARM'); ?>>Armenienne (Arménie)</option>
                        <option value="AUS" <?php selected($saved_nationalite_tuteur_legal_1, 'AUS'); ?>>Australienne (Australie)</option>
                        <option value="AUT" <?php selected($saved_nationalite_tuteur_legal_1, 'AUT'); ?>>Autrichienne (Autriche)</option>
                        <option value="AZE" <?php selected($saved_nationalite_tuteur_legal_1, 'AZE'); ?>>Azerbaïdjanaise (Azerbaïdjan)</option>
                        <option value="BHS" <?php selected($saved_nationalite_tuteur_legal_1, 'BHS'); ?>>Bahamienne (Bahamas)</option>
                        <option value="BHR" <?php selected($saved_nationalite_tuteur_legal_1, 'BHR'); ?>>Bahreinienne (Bahreïn)</option>
                        <option value="BGD" <?php selected($saved_nationalite_tuteur_legal_1, 'BGD'); ?>>Bangladaise (Bangladesh)</option>
                        <option value="BRB" <?php selected($saved_nationalite_tuteur_legal_1, 'BRB'); ?>>Barbadienne (Barbade)</option>
                        <option value="BEL" <?php selected($saved_nationalite_tuteur_legal_1, 'BEL'); ?>>Belge (Belgique)</option>
                        <option value="BLZ" <?php selected($saved_nationalite_tuteur_legal_1, 'BLZ'); ?>>Belizienne (Belize)</option>
                        <option value="BEN" <?php selected($saved_nationalite_tuteur_legal_1, 'BEN'); ?>>Béninoise (Bénin)</option>
                        <option value="BTN" <?php selected($saved_nationalite_tuteur_legal_1, 'BTN'); ?>>Bhoutanaise (Bhoutan)</option>
                        <option value="BLR" <?php selected($saved_nationalite_tuteur_legal_1, 'BLR'); ?>>Biélorusse (Biélorussie)</option>
                        <option value="MMR" <?php selected($saved_nationalite_tuteur_legal_1, 'MMR'); ?>>Birmane (Birmanie)</option>
                        <option value="GNB" <?php selected($saved_nationalite_tuteur_legal_1, 'GNB'); ?>>Bissau-Guinéenne (Guinée-Bissau)</option>
                        <option value="BOL" <?php selected($saved_nationalite_tuteur_legal_1, 'BOL'); ?>>Bolivienne (Bolivie)</option>
                        <option value="BIH" <?php selected($saved_nationalite_tuteur_legal_1, 'BIH'); ?>>Bosnienne (Bosnie-Herzégovine)</option>
                        <option value="BWA" <?php selected($saved_nationalite_tuteur_legal_1, 'BWA'); ?>>Botswanaise (Botswana)</option>
                        <option value="BRA" <?php selected($saved_nationalite_tuteur_legal_1, 'BRA'); ?>>Brésilienne (Brésil)</option>
                        <option value="GBR" <?php selected($saved_nationalite_tuteur_legal_1, 'GBR'); ?>>Britannique (Royaume-Uni)</option>
                        <option value="BRN" <?php selected($saved_nationalite_tuteur_legal_1, 'BRN'); ?>>Brunéienne (Brunéi)</option>
                        <option value="BGR" <?php selected($saved_nationalite_tuteur_legal_1, 'BGR'); ?>>Bulgare (Bulgarie)</option>
                        <option value="BFA" <?php selected($saved_nationalite_tuteur_legal_1, 'BFA'); ?>>Burkinabée (Burkina)</option>
                        <option value="BDI" <?php selected($saved_nationalite_tuteur_legal_1, 'BDI'); ?>>Burundaise (Burundi)</option>
                        <option value="KHM" <?php selected($saved_nationalite_tuteur_legal_1, 'KHM'); ?>>Cambodgienne (Cambodge)</option>
                        <option value="CMR" <?php selected($saved_nationalite_tuteur_legal_1, 'CMR'); ?>>Camerounaise (Cameroun)</option>
                        <option value="CAN" <?php selected($saved_nationalite_tuteur_legal_1, 'CAN'); ?>>Canadienne (Canada)</option>
                        <option value="CPV" <?php selected($saved_nationalite_tuteur_legal_1, 'CPV'); ?>>Cap-verdienne (Cap-Vert)</option>
                        <option value="CAF" <?php selected($saved_nationalite_tuteur_legal_1, 'CAF'); ?>>Centrafricaine (Centrafrique)</option>
                        <option value="CHL" <?php selected($saved_nationalite_tuteur_legal_1, 'CHL'); ?>>Chilienne (Chili)</option>
                        <option value="CHN" <?php selected($saved_nationalite_tuteur_legal_1, 'CHN'); ?>>Chinoise (Chine)</option>
                        <option value="CYP" <?php selected($saved_nationalite_tuteur_legal_1, 'CYP'); ?>>Chypriote (Chypre)</option>
                        <option value="COL" <?php selected($saved_nationalite_tuteur_legal_1, 'COL'); ?>>Colombienne (Colombie)</option>
                        <option value="COM" <?php selected($saved_nationalite_tuteur_legal_1, 'COM'); ?>>Comorienne (Comores)</option>
                        <option value="COG" <?php selected($saved_nationalite_tuteur_legal_1, 'COG'); ?>>Congolaise (Congo-Brazzaville)</option>
                        <option value="COD" <?php selected($saved_nationalite_tuteur_legal_1, 'COD'); ?>>Congolaise (Congo-Kinshasa)</option>
                        <option value="COK" <?php selected($saved_nationalite_tuteur_legal_1, 'COK'); ?>>Cookienne (Îles Cook)</option>
                        <option value="CRI" <?php selected($saved_nationalite_tuteur_legal_1, 'CRI'); ?>>Costaricaine (Costa Rica)</option>
                        <option value="HRV" <?php selected($saved_nationalite_tuteur_legal_1, 'HRV'); ?>>Croate (Croatie)</option>
                        <option value="CUB" <?php selected($saved_nationalite_tuteur_legal_1, 'CUB'); ?>>Cubaine (Cuba)</option>
                        <option value="DNK" <?php selected($saved_nationalite_tuteur_legal_1, 'DNK'); ?>>Danoise (Danemark)</option>
                        <option value="DJI" <?php selected($saved_nationalite_tuteur_legal_1, 'DJI'); ?>>Djiboutienne (Djibouti)</option>
                        <option value="DOM" <?php selected($saved_nationalite_tuteur_legal_1, 'DOM'); ?>>Dominicaine (République dominicaine)</option>
                        <option value="DMA" <?php selected($saved_nationalite_tuteur_legal_1, 'DMA'); ?>>Dominiquaise (Dominique)</option>
                        <option value="EGY" <?php selected($saved_nationalite_tuteur_legal_1, 'EGY'); ?>>Égyptienne (Égypte)</option>
                        <option value="ARE" <?php selected($saved_nationalite_tuteur_legal_1, 'ARE'); ?>>Émirienne (Émirats arabes unis)</option>
                        <option value="GNQ" <?php selected($saved_nationalite_tuteur_legal_1, 'GNQ'); ?>>Équato-guineenne (Guinée équatoriale)</option>
                        <option value="ECU" <?php selected($saved_nationalite_tuteur_legal_1, 'ECU'); ?>>Équatorienne (Équateur)</option>
                        <option value="ERI" <?php selected($saved_nationalite_tuteur_legal_1, 'ERI'); ?>>Érythréenne (Érythrée)</option>
                        <option value="ESP" <?php selected($saved_nationalite_tuteur_legal_1, 'ESP'); ?>>Espagnole (Espagne)</option>
                        <option value="TLS" <?php selected($saved_nationalite_tuteur_legal_1, 'TLS'); ?>>Est-timoraise (Timor-Leste)</option>
                        <option value="EST" <?php selected($saved_nationalite_tuteur_legal_1, 'EST'); ?>>Estonienne (Estonie)</option>
                        <option value="ETH" <?php selected($saved_nationalite_tuteur_legal_1, 'ETH'); ?>>Éthiopienne (Éthiopie)</option>
                        <option value="FJI" <?php selected($saved_nationalite_tuteur_legal_1, 'FJI'); ?>>Fidjienne (Fidji)</option>
                        <option value="FIN" <?php selected($saved_nationalite_tuteur_legal_1, 'FIN'); ?>>Finlandaise (Finlande)</option>
                        <option value="FRA" <?php selected($saved_nationalite_tuteur_legal_1, 'FRA'); ?>>Française (France)</option>
                        <option value="GAB" <?php selected($saved_nationalite_tuteur_legal_1, 'GAB'); ?>>Gabonaise (Gabon)</option>
                        <option value="GMB" <?php selected($saved_nationalite_tuteur_legal_1, 'GMB'); ?>>Gambienne (Gambie)</option>
                        <option value="GEO" <?php selected($saved_nationalite_tuteur_legal_1, 'GEO'); ?>>Georgienne (Géorgie)</option>
                        <option value="GHA" <?php selected($saved_nationalite_tuteur_legal_1, 'GHA'); ?>>Ghanéenne (Ghana)</option>
                        <option value="GRD" <?php selected($saved_nationalite_tuteur_legal_1, 'GRD'); ?>>Grenadienne (Grenade)</option>
                        <option value="GTM" <?php selected($saved_nationalite_tuteur_legal_1, 'GTM'); ?>>Guatémaltèque (Guatemala)</option>
                        <option value="GIN" <?php selected($saved_nationalite_tuteur_legal_1, 'GIN'); ?>>Guinéenne (Guinée)</option>
                        <option value="GUY" <?php selected($saved_nationalite_tuteur_legal_1, 'GUY'); ?>>Guyanienne (Guyana)</option>
                        <option value="HTI" <?php selected($saved_nationalite_tuteur_legal_1, 'HTI'); ?>>Haïtienne (Haïti)</option>
                        <option value="GRC" <?php selected($saved_nationalite_tuteur_legal_1, 'GRC'); ?>>Hellénique (Grèce)</option>
                        <option value="HND" <?php selected($saved_nationalite_tuteur_legal_1, 'HND'); ?>>Hondurienne (Honduras)</option>
                        <option value="HUN" <?php selected($saved_nationalite_tuteur_legal_1, 'HUN'); ?>>Hongroise (Hongrie)</option>
                        <option value="IND" <?php selected($saved_nationalite_tuteur_legal_1, 'IND'); ?>>Indienne (Inde)</option>
                        <option value="IDN" <?php selected($saved_nationalite_tuteur_legal_1, 'IDN'); ?>>Indonésienne (Indonésie)</option>
                        <option value="IRQ" <?php selected($saved_nationalite_tuteur_legal_1, 'IRQ'); ?>>Irakienne (Iraq)</option>
                        <option value="IRN" <?php selected($saved_nationalite_tuteur_legal_1, 'IRN'); ?>>Iranienne (Iran)</option>
                        <option value="IRL" <?php selected($saved_nationalite_tuteur_legal_1, 'IRL'); ?>>Irlandaise (Irlande)</option>
                        <option value="ISL" <?php selected($saved_nationalite_tuteur_legal_1, 'ISL'); ?>>Islandaise (Islande)</option>
                        <option value="ISR" <?php selected($saved_nationalite_tuteur_legal_1, 'ISR'); ?>>Israélienne (Israël)</option>
                        <option value="ITA" <?php selected($saved_nationalite_tuteur_legal_1, 'ITA'); ?>>Italienne (Italie)</option>
                        <option value="CIV" <?php selected($saved_nationalite_tuteur_legal_1, 'CIV'); ?>>Ivoirienne (Côte d'Ivoire)</option>
                        <option value="JAM" <?php selected($saved_nationalite_tuteur_legal_1, 'JAM'); ?>>Jamaïcaine (Jamaïque)</option>
                        <option value="JPN" <?php selected($saved_nationalite_tuteur_legal_1, 'JPN'); ?>>Japonaise (Japon)</option>
                        <option value="JOR" <?php selected($saved_nationalite_tuteur_legal_1, 'JOR'); ?>>Jordanienne (Jordanie)</option>
                        <option value="KAZ" <?php selected($saved_nationalite_tuteur_legal_1, 'KAZ'); ?>>Kazakhstanaise (Kazakhstan)</option>
                        <option value="KEN" <?php selected($saved_nationalite_tuteur_legal_1, 'KEN'); ?>>Kenyane (Kenya)</option>
                        <option value="KGZ" <?php selected($saved_nationalite_tuteur_legal_1, 'KGZ'); ?>>Kirghize (Kirghizistan)</option>
                        <option value="KIR" <?php selected($saved_nationalite_tuteur_legal_1, 'KIR'); ?>>Kiribatienne (Kiribati)</option>
                        <option value="KNA" <?php selected($saved_nationalite_tuteur_legal_1, 'KNA'); ?>>Kittitienne et Névicienne (Saint-Christophe-et-Niévès)</option>
                        <option value="KWT" <?php selected($saved_nationalite_tuteur_legal_1, 'KWT'); ?>>Koweïtienne (Koweït)</option>
                        <option value="LAO" <?php selected($saved_nationalite_tuteur_legal_1, 'LAO'); ?>>Laotienne (Laos)</option>
                        <option value="LSO" <?php selected($saved_nationalite_tuteur_legal_1, 'LSO'); ?>>Lesothane (Lesotho)</option>
                        <option value="LVA" <?php selected($saved_nationalite_tuteur_legal_1, 'LVA'); ?>>Lettone (Lettonie)</option>
                        <option value="LBN" <?php selected($saved_nationalite_tuteur_legal_1, 'LBN'); ?>>Libanaise (Liban)</option>
                        <option value="LBR" <?php selected($saved_nationalite_tuteur_legal_1, 'LBR'); ?>>Libérienne (Libéria)</option>
                        <option value="LBY" <?php selected($saved_nationalite_tuteur_legal_1, 'LBY'); ?>>Libyenne (Libye)</option>
                        <option value="LIE" <?php selected($saved_nationalite_tuteur_legal_1, 'LIE'); ?>>Liechtensteinoise (Liechtenstein)</option>
                        <option value="LTU" <?php selected($saved_nationalite_tuteur_legal_1, 'LTU'); ?>>Lituanienne (Lituanie)</option>
                        <option value="LUX" <?php selected($saved_nationalite_tuteur_legal_1, 'LUX'); ?>>Luxembourgeoise (Luxembourg)</option>
                        <option value="MKD" <?php selected($saved_nationalite_tuteur_legal_1, 'MKD'); ?>>Macédonienne (Macédoine)</option>
                        <option value="MYS" <?php selected($saved_nationalite_tuteur_legal_1, 'MYS'); ?>>Malaisienne (Malaisie)</option>
                        <option value="MWI" <?php selected($saved_nationalite_tuteur_legal_1, 'MWI'); ?>>Malawienne (Malawi)</option>
                        <option value="MDV" <?php selected($saved_nationalite_tuteur_legal_1, 'MDV'); ?>>Maldivienne (Maldives)</option>
                        <option value="MDG" <?php selected($saved_nationalite_tuteur_legal_1, 'MDG'); ?>>Malgache (Madagascar)</option>
                        <option value="MLI" <?php selected($saved_nationalite_tuteur_legal_1, 'MLI'); ?>>Maliennes (Mali)</option>
                        <option value="MLT" <?php selected($saved_nationalite_tuteur_legal_1, 'MLT'); ?>>Maltaise (Malte)</option>
                        <option value="MAR" <?php selected($saved_nationalite_tuteur_legal_1, 'MAR'); ?>>Marocaine (Maroc)</option>
                        <option value="MHL" <?php selected($saved_nationalite_tuteur_legal_1, 'MHL'); ?>>Marshallaise (Îles Marshall)</option>
                        <option value="MUS" <?php selected($saved_nationalite_tuteur_legal_1, 'MUS'); ?>>Mauricienne (Maurice)</option>
                        <option value="MRT" <?php selected($saved_nationalite_tuteur_legal_1, 'MRT'); ?>>Mauritanienne (Mauritanie)</option>
                        <option value="MEX" <?php selected($saved_nationalite_tuteur_legal_1, 'MEX'); ?>>Mexicaine (Mexique)</option>
                        <option value="FSM" <?php selected($saved_nationalite_tuteur_legal_1, 'FSM'); ?>>Micronésienne (Micronésie)</option>
                        <option value="MDA" <?php selected($saved_nationalite_tuteur_legal_1, 'MDA'); ?>>Moldave (Moldovie)</option>
                        <option value="MCO" <?php selected($saved_nationalite_tuteur_legal_1, 'MCO'); ?>>Monegasque (Monaco)</option>
                        <option value="MNG" <?php selected($saved_nationalite_tuteur_legal_1, 'MNG'); ?>>Mongole (Mongolie)</option>
                        <option value="MNE" <?php selected($saved_nationalite_tuteur_legal_1, 'MNE'); ?>>Monténégrine (Monténégro)</option>
                        <option value="MOZ" <?php selected($saved_nationalite_tuteur_legal_1, 'MOZ'); ?>>Mozambicaine (Mozambique)</option>
                        <option value="NAM" <?php selected($saved_nationalite_tuteur_legal_1, 'NAM'); ?>>Namibienne (Namibie)</option>
                        <option value="NRU" <?php selected($saved_nationalite_tuteur_legal_1, 'NRU'); ?>>Nauruane (Nauru)</option>
                        <option value="NLD" <?php selected($saved_nationalite_tuteur_legal_1, 'NLD'); ?>>Néerlandaise (Pays-Bas)</option>
                        <option value="NZL" <?php selected($saved_nationalite_tuteur_legal_1, 'NZL'); ?>>Néo-Zélandaise (Nouvelle-Zélande)</option>
                        <option value="NPL" <?php selected($saved_nationalite_tuteur_legal_1, 'NPL'); ?>>Népalaise (Népal)</option>
                        <option value="NIC" <?php selected($saved_nationalite_tuteur_legal_1, 'NIC'); ?>>Nicaraguayenne (Nicaragua)</option>
                        <option value="NGA" <?php selected($saved_nationalite_tuteur_legal_1, 'NGA'); ?>>Nigériane (Nigéria)</option>
                        <option value="NER" <?php selected($saved_nationalite_tuteur_legal_1, 'NER'); ?>>Nigérienne (Niger)</option>
                        <option value="NIU" <?php selected($saved_nationalite_tuteur_legal_1, 'NIU'); ?>>Niuéenne (Niue)</option>
                        <option value="PRK" <?php selected($saved_nationalite_tuteur_legal_1, 'PRK'); ?>>Nord-coréenne (Corée du Nord)</option>
                        <option value="NOR" <?php selected($saved_nationalite_tuteur_legal_1, 'NOR'); ?>>Norvégienne (Norvège)</option>
                        <option value="OMN" <?php selected($saved_nationalite_tuteur_legal_1, 'OMN'); ?>>Omanaise (Oman)</option>
                        <option value="UGA" <?php selected($saved_nationalite_tuteur_legal_1, 'UGA'); ?>>Ougandaise (Ouganda)</option>
                        <option value="UZB" <?php selected($saved_nationalite_tuteur_legal_1, 'UZB'); ?>>Ouzbéke (Ouzbékistan)</option>
                        <option value="PAK" <?php selected($saved_nationalite_tuteur_legal_1, 'PAK'); ?>>Pakistanaise (Pakistan)</option>
                        <option value="PLW" <?php selected($saved_nationalite_tuteur_legal_1, 'PLW'); ?>>Palaosienne (Palaos)</option>
                        <option value="PSE" <?php selected($saved_nationalite_tuteur_legal_1, 'PSE'); ?>>Palestinienne (Palestine)</option>
                        <option value="PAN" <?php selected($saved_nationalite_tuteur_legal_1, 'PAN'); ?>>Panaméenne (Panama)</option>
                        <option value="PNG" <?php selected($saved_nationalite_tuteur_legal_1, 'PNG'); ?>>Papouane-Néo-Guinéenne (Papouasie-Nouvelle-Guinée)</option>
                        <option value="PRY" <?php selected($saved_nationalite_tuteur_legal_1, 'PRY'); ?>>Paraguayenne (Paraguay)</option>
                        <option value="PER" <?php selected($saved_nationalite_tuteur_legal_1, 'PER'); ?>>Péruvienne (Pérou)</option>
                        <option value="PHL" <?php selected($saved_nationalite_tuteur_legal_1, 'PHL'); ?>>Philippine (Philippines)</option>
                        <option value="POL" <?php selected($saved_nationalite_tuteur_legal_1, 'POL'); ?>>Polonaise (Pologne)</option>
                        <option value="PRT" <?php selected($saved_nationalite_tuteur_legal_1, 'PRT'); ?>>Portugaise (Portugal)</option>
                        <option value="QAT" <?php selected($saved_nationalite_tuteur_legal_1, 'QAT'); ?>>Qatarienne (Qatar)</option>
                        <option value="ROU" <?php selected($saved_nationalite_tuteur_legal_1, 'ROU'); ?>>Roumaine (Roumanie)</option>
                        <option value="RUS" <?php selected($saved_nationalite_tuteur_legal_1, 'RUS'); ?>>Russe (Russie)</option>
                        <option value="RWA" <?php selected($saved_nationalite_tuteur_legal_1, 'RWA'); ?>>Rwandaise (Rwanda)</option>
                        <option value="LCA" <?php selected($saved_nationalite_tuteur_legal_1, 'LCA'); ?>>Saint-Lucienne (Sainte-Lucie)</option>
                        <option value="SMR" <?php selected($saved_nationalite_tuteur_legal_1, 'SMR'); ?>>Saint-Marinaise (Saint-Marin)</option>
                        <option value="VCT" <?php selected($saved_nationalite_tuteur_legal_1, 'VCT'); ?>>Saint-Vincentaise et Grenadine (Saint-Vincent-et-les Grenadines)</option>
                        <option value="SLB" <?php selected($saved_nationalite_tuteur_legal_1, 'SLB'); ?>>Salomonaise (Îles Salomon)</option>
                        <option value="SLV" <?php selected($saved_nationalite_tuteur_legal_1, 'SLV'); ?>>Salvadorienne (Salvador)</option>
                        <option value="WSM" <?php selected($saved_nationalite_tuteur_legal_1, 'WSM'); ?>>Samoane (Samoa)</option>
                        <option value="STP" <?php selected($saved_nationalite_tuteur_legal_1, 'STP'); ?>>Santoméenne (Sao Tomé-et-Principe)</option>
                        <option value="SAU" <?php selected($saved_nationalite_tuteur_legal_1, 'SAU'); ?>>Saoudienne (Arabie saoudite)</option>
                        <option value="SEN" <?php selected($saved_nationalite_tuteur_legal_1, 'SEN'); ?>>Sénégalaise (Sénégal)</option>
                        <option value="SRB" <?php selected($saved_nationalite_tuteur_legal_1, 'SRB'); ?>>Serbe (Serbie)</option>
                        <option value="SYC" <?php selected($saved_nationalite_tuteur_legal_1, 'SYC'); ?>>Seychelloise (Seychelles)</option>
                        <option value="SLE" <?php selected($saved_nationalite_tuteur_legal_1, 'SLE'); ?>>Sierra-Léonaise (Sierra Leone)</option>
                        <option value="SGP" <?php selected($saved_nationalite_tuteur_legal_1, 'SGP'); ?>>Singapourienne (Singapour)</option>
                        <option value="SVK" <?php selected($saved_nationalite_tuteur_legal_1, 'SVK'); ?>>Slovaque (Slovaquie)</option>
                        <option value="SVN" <?php selected($saved_nationalite_tuteur_legal_1, 'SVN'); ?>>Slovène (Slovénie)</option>
                        <option value="SOM" <?php selected($saved_nationalite_tuteur_legal_1, 'SOM'); ?>>Somalienne (Somalie)</option>
                        <option value="SDN" <?php selected($saved_nationalite_tuteur_legal_1, 'SDN'); ?>>Soudanaise (Soudan)</option>
                        <option value="LKA" <?php selected($saved_nationalite_tuteur_legal_1, 'LKA'); ?>>Sri-Lankaise (Sri Lanka)</option>
                        <option value="ZAF" <?php selected($saved_nationalite_tuteur_legal_1, 'ZAF'); ?>>Sud-Africaine (Afrique du Sud)</option>
                        <option value="KOR" <?php selected($saved_nationalite_tuteur_legal_1, 'KOR'); ?>>Sud-Coréenne (Corée du Sud)</option>
                        <option value="SSD" <?php selected($saved_nationalite_tuteur_legal_1, 'SSD'); ?>>Sud-Soudanaise (Soudan du Sud)</option>
                        <option value="SWE" <?php selected($saved_nationalite_tuteur_legal_1, 'SWE'); ?>>Suédoise (Suède)</option>
                        <option value="CHE" <?php selected($saved_nationalite_tuteur_legal_1, 'CHE'); ?>>Suisse (Suisse)</option>
                        <option value="SUR" <?php selected($saved_nationalite_tuteur_legal_1, 'SUR'); ?>>Surinamaise (Suriname)</option>
                        <option value="SWZ" <?php selected($saved_nationalite_tuteur_legal_1, 'SWZ'); ?>>Swazie (Swaziland)</option>
                        <option value="SYR" <?php selected($saved_nationalite_tuteur_legal_1, 'SYR'); ?>>Syrienne (Syrie)</option>
                        <option value="TJK" <?php selected($saved_nationalite_tuteur_legal_1, 'TJK'); ?>>Tadjike (Tadjikistan)</option>
                        <option value="TZA" <?php selected($saved_nationalite_tuteur_legal_1, 'TZA'); ?>>Tanzanienne (Tanzanie)</option>
                        <option value="TCD" <?php selected($saved_nationalite_tuteur_legal_1, 'TCD'); ?>>Tchadienne (Tchad)</option>
                        <option value="CZE" <?php selected($saved_nationalite_tuteur_legal_1, 'CZE'); ?>>Tchèque (Tchéquie)</option>
                        <option value="THA" <?php selected($saved_nationalite_tuteur_legal_1, 'THA'); ?>>Thaïlandaise (Thaïlande)</option>
                        <option value="TGO" <?php selected($saved_nationalite_tuteur_legal_1, 'TGO'); ?>>Togolaise (Togo)</option>
                        <option value="TON" <?php selected($saved_nationalite_tuteur_legal_1, 'TON'); ?>>Tonguienne (Tonga)</option>
                        <option value="TTO" <?php selected($saved_nationalite_tuteur_legal_1, 'TTO'); ?>>Trinidadienne (Trinité-et-Tobago)</option>
                        <option value="TUN" <?php selected($saved_nationalite_tuteur_legal_1, 'TUN'); ?>>Tunisienne (Tunisie)</option>
                        <option value="TKM" <?php selected($saved_nationalite_tuteur_legal_1, 'TKM'); ?>>Turkmène (Turkménistan)</option>
                        <option value="TUR" <?php selected($saved_nationalite_tuteur_legal_1, 'TUR'); ?>>Turque (Turquie)</option>
                        <option value="TUV" <?php selected($saved_nationalite_tuteur_legal_1, 'TUV'); ?>>Tuvaluane (Tuvalu)</option>
                        <option value="UKR" <?php selected($saved_nationalite_tuteur_legal_1, 'UKR'); ?>>Ukrainienne (Ukraine)</option>
                        <option value="URY" <?php selected($saved_nationalite_tuteur_legal_1, 'URY'); ?>>Uruguayenne (Uruguay)</option>
                        <option value="VUT" <?php selected($saved_nationalite_tuteur_legal_1, 'VUT'); ?>>Vanuatuane (Vanuatu)</option>
                        <option value="VAT" <?php selected($saved_nationalite_tuteur_legal_1, 'VAT'); ?>>Vaticane (Vatican)</option>
                        <option value="VEN" <?php selected($saved_nationalite_tuteur_legal_1, 'VEN'); ?>>Vénézuélienne (Venezuela)</option>
                        <option value="VNM" <?php selected($saved_nationalite_tuteur_legal_1, 'VNM'); ?>>Vietnamienne (Viêt Nam)</option>
                        <option value="YEM" <?php selected($saved_nationalite_tuteur_legal_1, 'YEM'); ?>>Yéménite (Yémen)</option>
                        <option value="ZMB" <?php selected($saved_nationalite_tuteur_legal_1, 'ZMB'); ?>>Zambienne (Zambie)</option>
                        <option value="ZWE" <?php selected($saved_nationalite_tuteur_legal_1, 'ZWE'); ?>>Zimbabwéenne (Zimbabwe)</option>

			        </select><br><br>
			
			
					
			<hr>
			
			 <div class="preamble-notice">
				Parent n°2 ou Tuteur légal n°2
			</div>
			<label>Statut</label><br>
			<select name="statut_tuteur_legal_2">
				<option value="">-- Sélectionner un statut si vous êtes concerné --</option>
				<option value="Apatride" <?php selected($saved_statut_tuteur_legal_2, 'Apatride'); ?>>Apatride</option>
				<option value="Réfugié 1946/51" <?php selected($saved_statut_tuteur_legal_2, 'Réfugié 1946/51'); ?>>Réfugié 1946/51</option>
				<option value="Réfugié hs conv" <?php selected($saved_statut_tuteur_legal_2, 'Réfugié hs conv'); ?>>Réfugié hs conv</option>
			</select><br><br>
			<!-- Deuxième tuteur légal -->
			<div style="display: flex; gap: 10px;justify-content: space-between;">
				<div>
					<label>Nom</label>
					<input type="text" name="nom_tuteur_legal_2" value="<?php echo esc_attr($saved_nom_tuteur_legal_2); ?>" required>
				</div>
				<div>
					<label>Prénom</label>
					<input type="text" name="prenom_tuteur_legal_2" value="<?php echo esc_attr($saved_prenom_tuteur_legal_2); ?>">
				</div>
			</div>
			
			<label>Adresse</label><br>
			<input type="text" name="adresse_tuteur_legal_2" value="<?php echo esc_attr($saved_adresse_tuteur_legal_2); ?>" required><br><br>
			
			<div style="display: flex; gap: 10px;justify-content: space-between;">
				<div>
					<label>Code postal</label>
					<input type="text" name="code_postal_tuteur_legal_2" value="<?php echo esc_attr($saved_code_postal_tuteur_legal_2); ?>">
				</div>
				<div>
					<label>Ville</label>
					<input type="text" name="ville_tuteur_legal_2" value="<?php echo esc_attr($saved_ville_tuteur_legal_2); ?>" required>
				</div>
				<div>
					<label>Pays</label>
					 <select id="country_contact" name="pays_tuteur_legal_2" value="<?php echo esc_attr($saved_pays_tuteur_legal_2); ?>" required>
            			<option value="">-- Sélectionnez un pays --</option>
            			<option value="Afghanistan">Afghanistan</option>
            			<option value="Afrique du Sud">Afrique du Sud</option>
            			<option value="Albanie">Albanie</option>
            			<option value="Algérie">Algérie</option>
            			<option value="Allemagne">Allemagne</option>
            			<option value="Andorre">Andorre</option>
            			<option value="Angola">Angola</option>
            			<option value="Antigua-et-Barbuda">Antigua-et-Barbuda</option>
            			<option value="Arabie Saoudite">Arabie Saoudite</option>
            			<option value="Argentine">Argentine</option>
            			<option value="Arménie">Arménie</option>
            			<option value="Australie">Australie</option>
            			<option value="Autriche">Autriche</option>
            			<option value="Azerbaïdjan">Azerbaïdjan</option>
            			<option value="Bahamas">Bahamas</option>
            			<option value="Bahreïn">Bahreïn</option>
            			<option value="Bangladesh">Bangladesh</option>
            			<option value="Barbade">Barbade</option>
            			<option value="Belgique">Belgique</option>
            			<option value="Belize">Belize</option>
            			<option value="Bénin">Bénin</option>
            			<option value="Bhoutan">Bhoutan</option>
            			<option value="Biélorussie">Biélorussie</option>
            			<option value="Birmanie">Birmanie</option>
            			<option value="Bolivie">Bolivie</option>
            			<option value="Bosnie-Herzégovine">Bosnie-Herzégovine</option>
            			<option value="Botswana">Botswana</option>
            			<option value="Brésil">Brésil</option>
            			<option value="Brunei">Brunei</option>
            			<option value="Bulgarie">Bulgarie</option>
            			<option value="Burkina Faso">Burkina Faso</option>
            			<option value="Burundi">Burundi</option>
            			<option value="Cabo Verde">Cabo Verde</option>
            			<option value="Cambodge">Cambodge</option>
            			<option value="Cameroun">Cameroun</option>
            			<option value="Canada">Canada</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Chili">Chili</option>
            			<option value="Chine">Chine</option>
            			<option value="Chypre">Chypre</option>
            			<option value="Colombie">Colombie</option>
            			<option value="Comores">Comores</option>
            			<option value="Congo (Brazzaville)">Congo (Brazzaville)</option>
            			<option value="Congo (Kinshasa)">Congo (Kinshasa)</option>
            			<option value="Corée du Nord">Corée du Nord</option>
            			<option value="Corée du Sud">Corée du Sud</option>
            			<option value="Costa Rica">Costa Rica</option>
            			<option value="Côte d’Ivoire">Côte d’Ivoire</option>
            			<option value="Croatie">Croatie</option>
            			<option value="Cuba">Cuba</option>
            			<option value="Danemark">Danemark</option>
            			<option value="Djibouti">Djibouti</option>
            			<option value="Dominique">Dominique</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Egypte">Égypte</option>
            			<option value="Emirats arabes unis">Émirats arabes unis</option>
            			<option value="Equateur">Équateur</option>
            			<option value="Erythrée">Érythrée</option>
            			<option value="Espagne">Espagne</option>
            			<option value="Estonie">Estonie</option>
            			<option value="Eswatini">Eswatini</option>
            			<option value="Etats-Unis">États-Unis</option>
            			<option value="Ethiopie">Éthiopie</option>
            			<option value="Fidji">Fidji</option>
            			<option value="Finlande">Finlande</option>
            			<option value="France">France</option>
            			<option value="Gabon">Gabon</option>
            			<option value="Gambie">Gambie</option>
            			<option value="Géorgie">Géorgie</option>
            			<option value="Ghana">Ghana</option>
            			<option value="Grèce">Grèce</option>
            			<option value="Grenade">Grenade</option>
            			<option value="Guatemala">Guatemala</option>
            			<option value="Guinée">Guinée</option>
            			<option value="Guinée-Bissau">Guinée-Bissau</option>
            			<option value="Guinée équatoriale">Guinée équatoriale</option>
            			<option value="Guyana">Guyana</option>
            			<option value="Haïti">Haïti</option>
            			<option value="Honduras">Honduras</option>
            			<option value="Hongrie">Hongrie</option>
            			<option value="Inde">Inde</option>
            			<option value="Indonésie">Indonésie</option>
            			<option value="Irak">Irak</option>
            			<option value="Iran">Iran</option>
            			<option value="Irlande">Irlande</option>
            			<option value="Islande">Islande</option>
            			<option value="Israël">Israël</option>
            			<option value="Italie">Italie</option>
            			<option value="Jamaïque">Jamaïque</option>
            			<option value="Japon">Japon</option>
            			<option value="Jordanie">Jordanie</option>
            			<option value="Kazakhstan">Kazakhstan</option>
            			<option value="Kenya">Kenya</option>
            			<option value="Kirghizistan">Kirghizistan</option>
            			<option value="Kiribati">Kiribati</option>
            			<option value="Kosovo">Kosovo</option>
            			<option value="Koweït">Koweït</option>
            			<option value="Laos">Laos</option>
            			<option value="Lettonie">Lettonie</option>
            			<option value="Liban">Liban</option>
            			<option value="Libéria">Libéria</option>
            			<option value="Libye">Libye</option>
            			<option value="Liechtenstein">Liechtenstein</option>
            			<option value="Lituanie">Lituanie</option>
            			<option value="Luxembourg">Luxembourg</option>
            			<option value="Macédoine du Nord">Macédoine du Nord</option>
            			<option value="Madagascar">Madagascar</option>
            			<option value="Malaisie">Malaisie</option>
            			<option value="Malawi">Malawi</option>
            			<option value="Maldives">Maldives</option>
            			<option value="Mali">Mali</option>
            			<option value="Malte">Malte</option>
            			<option value="Maroc">Maroc</option>
            			<option value="Marshall">Îles Marshall</option>
            			<option value="Maurice">Maurice</option>
            			<option value="Mauritanie">Mauritanie</option>
            			<option value="Mexique">Mexique</option>
            			<option value="Micronésie">Micronésie</option>
            			<option value="Moldavie">Moldavie</option>
            			<option value="Monaco">Monaco</option>
            			<option value="Mongolie">Mongolie</option>
            			<option value="Monténégro">Monténégro</option>
            			<option value="Mozambique">Mozambique</option>
            			<option value="Namibie">Namibie</option>
            			<option value="Nauru">Nauru</option>
            			<option value="Népal">Népal</option>
            			<option value="Nicaragua">Nicaragua</option>
            			<option value="Niger">Niger</option>
            			<option value="Nigéria">Nigéria</option>
            			<option value="Norvège">Norvège</option>
            			<option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
            			<option value="Oman">Oman</option>
            			<option value="Ouganda">Ouganda</option>
            			<option value="Ouzbékistan">Ouzbékistan</option>
            			<option value="Pakistan">Pakistan</option>
            			<option value="Palaos">Palaos</option>
            			<option value="Palestine">Palestine</option>
            			<option value="Panama">Panama</option>
            			<option value="Papouasie-Nouvelle-Guinée">Papouasie-Nouvelle-Guinée</option>
            			<option value="Paraguay">Paraguay</option>
            			<option value="Pays-Bas">Pays-Bas</option>
            			<option value="Pérou">Pérou</option>
            			<option value="Philippines">Philippines</option>
            			<option value="Pologne">Pologne</option>
            			<option value="Portugal">Portugal</option>
            			<option value="République centrafricaine">République centrafricaine</option>
            			<option value="République dominicaine">République dominicaine</option>
            			<option value="Roumanie">Roumanie</option>
            			<option value="Royaume-Uni">Royaume-Uni</option>
            			<option value="Russie">Russie</option>
            			<option value="Rwanda">Rwanda</option>
            			<option value="Saint-Kitts-et-Nevis">Saint-Kitts-et-Nevis</option>
            			<option value="Saint-Marin">Saint-Marin</option>
            			<option value="Saint-Vincent-et-les-Grenadines">Saint-Vincent-et-les-Grenadines</option>
            			<option value="Sainte-Lucie">Sainte-Lucie</option>
            			<option value="Salvador">Salvador</option>
            			<option value="Samoa">Samoa</option>
            			<option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
            			<option value="Sénégal">Sénégal</option>
            			<option value="Serbie">Serbie</option>
            			<option value="Seychelles">Seychelles</option>
            			<option value="Sierra Leone">Sierra Leone</option>
            			<option value="Singapour">Singapour</option>
            			<option value="Slovaquie">Slovaquie</option>
            			<option value="Slovénie">Slovénie</option>
            			<option value="Somalie">Somalie</option>
            			<option value="Soudan">Soudan</option>
            			<option value="Soudan du Sud">Soudan du Sud</option>
            			<option value="Sri Lanka">Sri Lanka</option>
            			<option value="Suède">Suède</option>
            			<option value="Suisse">Suisse</option>
            			<option value="Suriname">Suriname</option>
            			<option value="Syrie">Syrie</option>
            			<option value="Tadjikistan">Tadjikistan</option>
            			<option value="Tanzanie">Tanzanie</option>
            			<option value="Tchad">Tchad</option>
            			<option value="Tchécoslovaquie">Tchéquie</option>
            			<option value="Thaïlande">Thaïlande</option>
            			<option value="Timor-Leste">Timor-Leste</option>
            			<option value="Togo">Togo</option>
            			<option value="Tonga">Tonga</option>
            			<option value="Trinité-et-Tobago">Trinité-et-Tobago</option>
            			<option value="Tunisie">Tunisie</option>
            			<option value="Turkménistan">Turkménistan</option>
            			<option value="Turquie">Turquie</option>
            			<option value="Tuvalu">Tuvalu</option>
            			<option value="Ukraine">Ukraine</option>
            			<option value="Uruguay">Uruguay</option>
            			<option value="Vanuatu">Vanuatu</option>
            			<option value="Vatican">Vatican</option>
            			<option value="Venezuela">Venezuela</option>
            			<option value="Vietnam">Vietnam</option>
            			<option value="Yémen">Yémen</option>
            			<option value="Zambie">Zambie</option>
            			<option value="Zimbabwe">Zimbabwe</option>
            		</select>
				</div>
			</div><br>
			
			<label>Numéro de téléphone portable :</label><br>
			<input type="text" name="telephone_tuteur_legal_2" value="<?php echo esc_attr($saved_telephone_tuteur_legal_2); ?>" pattern="^(00213|0033)[0-9]{9}$" title="Le numéro doit commencer par 00213 ou 0033, suivi de 9 chiffres." placeholder="00 213 X XX XX XX XX ou 00 33 X XX XX XX XX" required><br><br>
			
			<label>Adresse e-mail :</label><br>
			<input type="text" name="email_tuteur_legal_2" value="<?php echo esc_attr($saved_email_tuteur_legal_2); ?>" required><br><br>
			
			<label>Nationalité actuelle :<span class="required">*</span></label><br>
				<select name="nationalite_tuteur_legal_2" required>
                    <option value="">-- Sélectionnez une nationalité --</option>
                    <option value="AFG" <?php selected($saved_nationalite_tuteur_legal_2, 'AFG'); ?>>Afghane (Afghanistan)</option>
                    <option value="ALB" <?php selected($saved_nationalite_tuteur_legal_2, 'ALB'); ?>>Albanaise (Albanie)</option>
                    <option value="DZA" <?php selected($saved_nationalite_tuteur_legal_2, 'DZA'); ?>>Algérienne (Algérie)</option>
                    <option value="DEU" <?php selected($saved_nationalite_tuteur_legal_2, 'DEU'); ?>>Allemande (Allemagne)</option>
                    <option value="USA" <?php selected($saved_nationalite_tuteur_legal_2, 'USA'); ?>>Americaine (États-Unis)</option>
                    <option value="AND" <?php selected($saved_nationalite_tuteur_legal_2, 'AND'); ?>>Andorrane (Andorre)</option>
                    <option value="AND" <?php selected($saved_nationalite_tuteur_legal_2, 'AND'); ?>>Andorrane (Andorre)</option>
                    <option value="AGO" <?php selected($saved_nationalite_tuteur_legal_2, 'AGO'); ?>>Angolaise (Angola)</option>
                    <option value="ATG" <?php selected($saved_nationalite_tuteur_legal_2, 'ATG'); ?>>Antiguaise-et-Barbudienne (Antigua-et-Barbuda)</option>
                    <option value="ARG" <?php selected($saved_nationalite_tuteur_legal_2, 'ARG'); ?>>Argentine (Argentine)</option>
                    <option value="ARM" <?php selected($saved_nationalite_tuteur_legal_2, 'ARM'); ?>>Armenienne (Arménie)</option>
                    <option value="AUS" <?php selected($saved_nationalite_tuteur_legal_2, 'AUS'); ?>>Australienne (Australie)</option>
                    <option value="AUT" <?php selected($saved_nationalite_tuteur_legal_2, 'AUT'); ?>>Autrichienne (Autriche)</option>
                    <option value="AZE" <?php selected($saved_nationalite_tuteur_legal_2, 'AZE'); ?>>Azerbaïdjanaise (Azerbaïdjan)</option>
                    <option value="BHS" <?php selected($saved_nationalite_tuteur_legal_2, 'BHS'); ?>>Bahamienne (Bahamas)</option>
                    <option value="BHR" <?php selected($saved_nationalite_tuteur_legal_2, 'BHR'); ?>>Bahreinienne (Bahreïn)</option>
                    <option value="BGD" <?php selected($saved_nationalite_tuteur_legal_2, 'BGD'); ?>>Bangladaise (Bangladesh)</option>
                    <option value="BRB" <?php selected($saved_nationalite_tuteur_legal_2, 'BRB'); ?>>Barbadienne (Barbade)</option>
                    <option value="BEL" <?php selected($saved_nationalite_tuteur_legal_2, 'BEL'); ?>>Belge (Belgique)</option>
                    <option value="BLZ" <?php selected($saved_nationalite_tuteur_legal_2, 'BLZ'); ?>>Belizienne (Belize)</option>
                    <option value="BEN" <?php selected($saved_nationalite_tuteur_legal_2, 'BEN'); ?>>Béninoise (Bénin)</option>
                    <option value="BTN" <?php selected($saved_nationalite_tuteur_legal_2, 'BTN'); ?>>Bhoutanaise (Bhoutan)</option>
                    <option value="BLR" <?php selected($saved_nationalite_tuteur_legal_2, 'BLR'); ?>>Biélorusse (Biélorussie)</option>
                    <option value="MMR" <?php selected($saved_nationalite_tuteur_legal_2, 'MMR'); ?>>Birmane (Birmanie)</option>
                    <option value="GNB" <?php selected($saved_nationalite_tuteur_legal_2, 'GNB'); ?>>Bissau-Guinéenne (Guinée-Bissau)</option>
                    <option value="BOL" <?php selected($saved_nationalite_tuteur_legal_2, 'BOL'); ?>>Bolivienne (Bolivie)</option>
                    <option value="BIH" <?php selected($saved_nationalite_tuteur_legal_2, 'BIH'); ?>>Bosnienne (Bosnie-Herzégovine)</option>
                    <option value="BWA" <?php selected($saved_nationalite_tuteur_legal_2, 'BWA'); ?>>Botswanaise (Botswana)</option>
                    <option value="BRA" <?php selected($saved_nationalite_tuteur_legal_2, 'BRA'); ?>>Brésilienne (Brésil)</option>
                    <option value="GBR" <?php selected($saved_nationalite_tuteur_legal_2, 'GBR'); ?>>Britannique (Royaume-Uni)</option>
                    <option value="BRN" <?php selected($saved_nationalite_tuteur_legal_2, 'BRN'); ?>>Brunéienne (Brunéi)</option>
                    <option value="BGR" <?php selected($saved_nationalite_tuteur_legal_2, 'BGR'); ?>>Bulgare (Bulgarie)</option>
                    <option value="BFA" <?php selected($saved_nationalite_tuteur_legal_2, 'BFA'); ?>>Burkinabée (Burkina)</option>
                    <option value="BDI" <?php selected($saved_nationalite_tuteur_legal_2, 'BDI'); ?>>Burundaise (Burundi)</option>
                    <option value="KHM" <?php selected($saved_nationalite_tuteur_legal_2, 'KHM'); ?>>Cambodgienne (Cambodge)</option>
                    <option value="CMR" <?php selected($saved_nationalite_tuteur_legal_2, 'CMR'); ?>>Camerounaise (Cameroun)</option>
                    <option value="CAN" <?php selected($saved_nationalite_tuteur_legal_2, 'CAN'); ?>>Canadienne (Canada)</option>
                    <option value="CPV" <?php selected($saved_nationalite_tuteur_legal_2, 'CPV'); ?>>Cap-verdienne (Cap-Vert)</option>
                    <option value="CAF" <?php selected($saved_nationalite_tuteur_legal_2, 'CAF'); ?>>Centrafricaine (Centrafrique)</option>
                    <option value="CHL" <?php selected($saved_nationalite_tuteur_legal_2, 'CHL'); ?>>Chilienne (Chili)</option>
                    <option value="CHN" <?php selected($saved_nationalite_tuteur_legal_2, 'CHN'); ?>>Chinoise (Chine)</option>
                    <option value="CYP" <?php selected($saved_nationalite_tuteur_legal_2, 'CYP'); ?>>Chypriote (Chypre)</option>
                    <option value="COL" <?php selected($saved_nationalite_tuteur_legal_2, 'COL'); ?>>Colombienne (Colombie)</option>
                    <option value="COM" <?php selected($saved_nationalite_tuteur_legal_2, 'COM'); ?>>Comorienne (Comores)</option>
                    <option value="COG" <?php selected($saved_nationalite_tuteur_legal_2, 'COG'); ?>>Congolaise (Congo-Brazzaville)</option>
                    <option value="COD" <?php selected($saved_nationalite_tuteur_legal_2, 'COD'); ?>>Congolaise (Congo-Kinshasa)</option>
                    <option value="COK" <?php selected($saved_nationalite_tuteur_legal_2, 'COK'); ?>>Cookienne (Îles Cook)</option>
                    <option value="CRI" <?php selected($saved_nationalite_tuteur_legal_2, 'CRI'); ?>>Costaricaine (Costa Rica)</option>
                    <option value="HRV" <?php selected($saved_nationalite_tuteur_legal_2, 'HRV'); ?>>Croate (Croatie)</option>
                    <option value="CUB" <?php selected($saved_nationalite_tuteur_legal_2, 'CUB'); ?>>Cubaine (Cuba)</option>
                    <option value="DNK" <?php selected($saved_nationalite_tuteur_legal_2, 'DNK'); ?>>Danoise (Danemark)</option>
                    <option value="DJI" <?php selected($saved_nationalite_tuteur_legal_2, 'DJI'); ?>>Djiboutienne (Djibouti)</option>
                    <option value="DOM" <?php selected($saved_nationalite_tuteur_legal_2, 'DOM'); ?>>Dominicaine (République dominicaine)</option>
                    <option value="DMA" <?php selected($saved_nationalite_tuteur_legal_2, 'DMA'); ?>>Dominiquaise (Dominique)</option>
                    <option value="EGY" <?php selected($saved_nationalite_tuteur_legal_2, 'EGY'); ?>>Égyptienne (Égypte)</option>
                    <option value="ARE" <?php selected($saved_nationalite_tuteur_legal_2, 'ARE'); ?>>Émirienne (Émirats arabes unis)</option>
                    <option value="GNQ" <?php selected($saved_nationalite_tuteur_legal_2, 'GNQ'); ?>>Équato-guineenne (Guinée équatoriale)</option>
                    <option value="ECU" <?php selected($saved_nationalite_tuteur_legal_2, 'ECU'); ?>>Équatorienne (Équateur)</option>
                    <option value="ERI" <?php selected($saved_nationalite_tuteur_legal_2, 'ERI'); ?>>Érythréenne (Érythrée)</option>
                    <option value="ESP" <?php selected($saved_nationalite_tuteur_legal_2, 'ESP'); ?>>Espagnole (Espagne)</option>
                    <option value="TLS" <?php selected($saved_nationalite_tuteur_legal_2, 'TLS'); ?>>Est-timoraise (Timor-Leste)</option>
                    <option value="EST" <?php selected($saved_nationalite_tuteur_legal_2, 'EST'); ?>>Estonienne (Estonie)</option>
                    <option value="ETH" <?php selected($saved_nationalite_tuteur_legal_2, 'ETH'); ?>>Éthiopienne (Éthiopie)</option>
                    <option value="FJI" <?php selected($saved_nationalite_tuteur_legal_2, 'FJI'); ?>>Fidjienne (Fidji)</option>
                    <option value="FIN" <?php selected($saved_nationalite_tuteur_legal_2, 'FIN'); ?>>Finlandaise (Finlande)</option>
                    <option value="FRA" <?php selected($saved_nationalite_tuteur_legal_2, 'FRA'); ?>>Française (France)</option>
                    <option value="GAB" <?php selected($saved_nationalite_tuteur_legal_2, 'GAB'); ?>>Gabonaise (Gabon)</option>
                    <option value="GMB" <?php selected($saved_nationalite_tuteur_legal_2, 'GMB'); ?>>Gambienne (Gambie)</option>
                    <option value="GEO" <?php selected($saved_nationalite_tuteur_legal_2, 'GEO'); ?>>Georgienne (Géorgie)</option>
                    <option value="GHA" <?php selected($saved_nationalite_tuteur_legal_2, 'GHA'); ?>>Ghanéenne (Ghana)</option>
                    <option value="GRD" <?php selected($saved_nationalite_tuteur_legal_2, 'GRD'); ?>>Grenadienne (Grenade)</option>
                    <option value="GTM" <?php selected($saved_nationalite_tuteur_legal_2, 'GTM'); ?>>Guatémaltèque (Guatemala)</option>
                    <option value="GIN" <?php selected($saved_nationalite_tuteur_legal_2, 'GIN'); ?>>Guinéenne (Guinée)</option>
                    <option value="GUY" <?php selected($saved_nationalite_tuteur_legal_2, 'GUY'); ?>>Guyanienne (Guyana)</option>
                    <option value="HTI" <?php selected($saved_nationalite_tuteur_legal_2, 'HTI'); ?>>Haïtienne (Haïti)</option>
                    <option value="GRC" <?php selected($saved_nationalite_tuteur_legal_2, 'GRC'); ?>>Hellénique (Grèce)</option>
                    <option value="HND" <?php selected($saved_nationalite_tuteur_legal_2, 'HND'); ?>>Hondurienne (Honduras)</option>
                    <option value="HUN" <?php selected($saved_nationalite_tuteur_legal_2, 'HUN'); ?>>Hongroise (Hongrie)</option>
                    <option value="IND" <?php selected($saved_nationalite_tuteur_legal_2, 'IND'); ?>>Indienne (Inde)</option>
                    <option value="IDN" <?php selected($saved_nationalite_tuteur_legal_2, 'IDN'); ?>>Indonésienne (Indonésie)</option>
                    <option value="IRQ" <?php selected($saved_nationalite_tuteur_legal_2, 'IRQ'); ?>>Irakienne (Iraq)</option>
                    <option value="IRN" <?php selected($saved_nationalite_tuteur_legal_2, 'IRN'); ?>>Iranienne (Iran)</option>
                    <option value="IRL" <?php selected($saved_nationalite_tuteur_legal_2, 'IRL'); ?>>Irlandaise (Irlande)</option>
                    <option value="ISL" <?php selected($saved_nationalite_tuteur_legal_2, 'ISL'); ?>>Islandaise (Islande)</option>
                    <option value="ISR" <?php selected($saved_nationalite_tuteur_legal_2, 'ISR'); ?>>Israélienne (Israël)</option>
                    <option value="ITA" <?php selected($saved_nationalite_tuteur_legal_2, 'ITA'); ?>>Italienne (Italie)</option>
                    <option value="CIV" <?php selected($saved_nationalite_tuteur_legal_2, 'CIV'); ?>>Ivoirienne (Côte d'Ivoire)</option>
                    <option value="JAM" <?php selected($saved_nationalite_tuteur_legal_2, 'JAM'); ?>>Jamaïcaine (Jamaïque)</option>
                    <option value="JPN" <?php selected($saved_nationalite_tuteur_legal_2, 'JPN'); ?>>Japonaise (Japon)</option>
                    <option value="JOR" <?php selected($saved_nationalite_tuteur_legal_2, 'JOR'); ?>>Jordanienne (Jordanie)</option>
                    <option value="KAZ" <?php selected($saved_nationalite_tuteur_legal_2, 'KAZ'); ?>>Kazakhstanaise (Kazakhstan)</option>
                    <option value="KEN" <?php selected($saved_nationalite_tuteur_legal_2, 'KEN'); ?>>Kenyane (Kenya)</option>
                    <option value="KGZ" <?php selected($saved_nationalite_tuteur_legal_2, 'KGZ'); ?>>Kirghize (Kirghizistan)</option>
                    <option value="KIR" <?php selected($saved_nationalite_tuteur_legal_2, 'KIR'); ?>>Kiribatienne (Kiribati)</option>
                    <option value="KNA" <?php selected($saved_nationalite_tuteur_legal_2, 'KNA'); ?>>Kittitienne et Névicienne (Saint-Christophe-et-Niévès)</option>
                    <option value="KWT" <?php selected($saved_nationalite_tuteur_legal_2, 'KWT'); ?>>Koweïtienne (Koweït)</option>
                    <option value="LAO" <?php selected($saved_nationalite_tuteur_legal_2, 'LAO'); ?>>Laotienne (Laos)</option>
                    <option value="LSO" <?php selected($saved_nationalite_tuteur_legal_2, 'LSO'); ?>>Lesothane (Lesotho)</option>
                    <option value="LVA" <?php selected($saved_nationalite_tuteur_legal_2, 'LVA'); ?>>Lettone (Lettonie)</option>
                    <option value="LBN" <?php selected($saved_nationalite_tuteur_legal_2, 'LBN'); ?>>Libanaise (Liban)</option>
                    <option value="LBR" <?php selected($saved_nationalite_tuteur_legal_2, 'LBR'); ?>>Libérienne (Libéria)</option>
                    <option value="LBY" <?php selected($saved_nationalite_tuteur_legal_2, 'LBY'); ?>>Libyenne (Libye)</option>
                    <option value="LIE" <?php selected($saved_nationalite_tuteur_legal_2, 'LIE'); ?>>Liechtensteinoise (Liechtenstein)</option>
                    <option value="LTU" <?php selected($saved_nationalite_tuteur_legal_2, 'LTU'); ?>>Lituanienne (Lituanie)</option>
                    <option value="LUX" <?php selected($saved_nationalite_tuteur_legal_2, 'LUX'); ?>>Luxembourgeoise (Luxembourg)</option>
                    <option value="MKD" <?php selected($saved_nationalite_tuteur_legal_2, 'MKD'); ?>>Macédonienne (Macédoine)</option>
                    <option value="MYS" <?php selected($saved_nationalite_tuteur_legal_2, 'MYS'); ?>>Malaisienne (Malaisie)</option>
                    <option value="MWI" <?php selected($saved_nationalite_tuteur_legal_2, 'MWI'); ?>>Malawienne (Malawi)</option>
                    <option value="MDV" <?php selected($saved_nationalite_tuteur_legal_2, 'MDV'); ?>>Maldivienne (Maldives)</option>
                    <option value="MDG" <?php selected($saved_nationalite_tuteur_legal_2, 'MDG'); ?>>Malgache (Madagascar)</option>
                    <option value="MLI" <?php selected($saved_nationalite_tuteur_legal_2, 'MLI'); ?>>Maliennes (Mali)</option>
                    <option value="MLT" <?php selected($saved_nationalite_tuteur_legal_2, 'MLT'); ?>>Maltaise (Malte)</option>
                    <option value="MAR" <?php selected($saved_nationalite_tuteur_legal_2, 'MAR'); ?>>Marocaine (Maroc)</option>
                    <option value="MHL" <?php selected($saved_nationalite_tuteur_legal_2, 'MHL'); ?>>Marshallaise (Îles Marshall)</option>
                    <option value="MUS" <?php selected($saved_nationalite_tuteur_legal_2, 'MUS'); ?>>Mauricienne (Maurice)</option>
                    <option value="MRT" <?php selected($saved_nationalite_tuteur_legal_2, 'MRT'); ?>>Mauritanienne (Mauritanie)</option>
                    <option value="MEX" <?php selected($saved_nationalite_tuteur_legal_2, 'MEX'); ?>>Mexicaine (Mexique)</option>
                    <option value="FSM" <?php selected($saved_nationalite_tuteur_legal_2, 'FSM'); ?>>Micronésienne (Micronésie)</option>
                    <option value="MDA" <?php selected($saved_nationalite_tuteur_legal_2, 'MDA'); ?>>Moldave (Moldovie)</option>
                    <option value="MCO" <?php selected($saved_nationalite_tuteur_legal_2, 'MCO'); ?>>Monegasque (Monaco)</option>
                    <option value="MNG" <?php selected($saved_nationalite_tuteur_legal_2, 'MNG'); ?>>Mongole (Mongolie)</option>
                    <option value="MNE" <?php selected($saved_nationalite_tuteur_legal_2, 'MNE'); ?>>Monténégrine (Monténégro)</option>
                    <option value="MOZ" <?php selected($saved_nationalite_tuteur_legal_2, 'MOZ'); ?>>Mozambicaine (Mozambique)</option>
                    <option value="NAM" <?php selected($saved_nationalite_tuteur_legal_2, 'NAM'); ?>>Namibienne (Namibie)</option>
                    <option value="NRU" <?php selected($saved_nationalite_tuteur_legal_2, 'NRU'); ?>>Nauruane (Nauru)</option>
                    <option value="NLD" <?php selected($saved_nationalite_tuteur_legal_2, 'NLD'); ?>>Néerlandaise (Pays-Bas)</option>
                    <option value="NZL" <?php selected($saved_nationalite_tuteur_legal_2, 'NZL'); ?>>Néo-Zélandaise (Nouvelle-Zélande)</option>
                    <option value="NPL" <?php selected($saved_nationalite_tuteur_legal_2, 'NPL'); ?>>Népalaise (Népal)</option>
                    <option value="NIC" <?php selected($saved_nationalite_tuteur_legal_2, 'NIC'); ?>>Nicaraguayenne (Nicaragua)</option>
                    <option value="NGA" <?php selected($saved_nationalite_tuteur_legal_2, 'NGA'); ?>>Nigériane (Nigéria)</option>
                    <option value="NER" <?php selected($saved_nationalite_tuteur_legal_2, 'NER'); ?>>Nigérienne (Niger)</option>
                    <option value="NIU" <?php selected($saved_nationalite_tuteur_legal_2, 'NIU'); ?>>Niuéenne (Niue)</option>
                    <option value="PRK" <?php selected($saved_nationalite_tuteur_legal_2, 'PRK'); ?>>Nord-coréenne (Corée du Nord)</option>
                    <option value="NOR" <?php selected($saved_nationalite_tuteur_legal_2, 'NOR'); ?>>Norvégienne (Norvège)</option>
                    <option value="OMN" <?php selected($saved_nationalite_tuteur_legal_2, 'OMN'); ?>>Omanaise (Oman)</option>
                    <option value="UGA" <?php selected($saved_nationalite_tuteur_legal_2, 'UGA'); ?>>Ougandaise (Ouganda)</option>
                    <option value="UZB" <?php selected($saved_nationalite_tuteur_legal_2, 'UZB'); ?>>Ouzbéke (Ouzbékistan)</option>
                    <option value="PAK" <?php selected($saved_nationalite_tuteur_legal_2, 'PAK'); ?>>Pakistanaise (Pakistan)</option>
                    <option value="PLW" <?php selected($saved_nationalite_tuteur_legal_2, 'PLW'); ?>>Palaosienne (Palaos)</option>
                    <option value="PSE" <?php selected($saved_nationalite_tuteur_legal_2, 'PSE'); ?>>Palestinienne (Palestine)</option>
                    <option value="PAN" <?php selected($saved_nationalite_tuteur_legal_2, 'PAN'); ?>>Panaméenne (Panama)</option>
                    <option value="PNG" <?php selected($saved_nationalite_tuteur_legal_2, 'PNG'); ?>>Papouane-Néo-Guinéenne (Papouasie-Nouvelle-Guinée)</option>
                    <option value="PRY" <?php selected($saved_nationalite_tuteur_legal_2, 'PRY'); ?>>Paraguayenne (Paraguay)</option>
                    <option value="PER" <?php selected($saved_nationalite_tuteur_legal_2, 'PER'); ?>>Péruvienne (Pérou)</option>
                    <option value="PHL" <?php selected($saved_nationalite_tuteur_legal_2, 'PHL'); ?>>Philippine (Philippines)</option>
                    <option value="POL" <?php selected($saved_nationalite_tuteur_legal_2, 'POL'); ?>>Polonaise (Pologne)</option>
                    <option value="PRT" <?php selected($saved_nationalite_tuteur_legal_2, 'PRT'); ?>>Portugaise (Portugal)</option>
                    <option value="QAT" <?php selected($saved_nationalite_tuteur_legal_2, 'QAT'); ?>>Qatarienne (Qatar)</option>
                    <option value="ROU" <?php selected($saved_nationalite_tuteur_legal_2, 'ROU'); ?>>Roumaine (Roumanie)</option>
                    <option value="RUS" <?php selected($saved_nationalite_tuteur_legal_2, 'RUS'); ?>>Russe (Russie)</option>
                    <option value="RWA" <?php selected($saved_nationalite_tuteur_legal_2, 'RWA'); ?>>Rwandaise (Rwanda)</option>
                    <option value="LCA" <?php selected($saved_nationalite_tuteur_legal_2, 'LCA'); ?>>Saint-Lucienne (Sainte-Lucie)</option>
                    <option value="SMR" <?php selected($saved_nationalite_tuteur_legal_2, 'SMR'); ?>>Saint-Marinaise (Saint-Marin)</option>
                    <option value="VCT" <?php selected($saved_nationalite_tuteur_legal_2, 'VCT'); ?>>Saint-Vincentaise et Grenadine (Saint-Vincent-et-les Grenadines)</option>
                    <option value="SLB" <?php selected($saved_nationalite_tuteur_legal_2, 'SLB'); ?>>Salomonaise (Îles Salomon)</option>
                    <option value="SLV" <?php selected($saved_nationalite_tuteur_legal_2, 'SLV'); ?>>Salvadorienne (Salvador)</option>
                    <option value="WSM" <?php selected($saved_nationalite_tuteur_legal_2, 'WSM'); ?>>Samoane (Samoa)</option>
                    <option value="STP" <?php selected($saved_nationalite_tuteur_legal_2, 'STP'); ?>>Santoméenne (Sao Tomé-et-Principe)</option>
                    <option value="SAU" <?php selected($saved_nationalite_tuteur_legal_2, 'SAU'); ?>>Saoudienne (Arabie saoudite)</option>
                    <option value="SEN" <?php selected($saved_nationalite_tuteur_legal_2, 'SEN'); ?>>Sénégalaise (Sénégal)</option>
                    <option value="SRB" <?php selected($saved_nationalite_tuteur_legal_2, 'SRB'); ?>>Serbe (Serbie)</option>
                    <option value="SYC" <?php selected($saved_nationalite_tuteur_legal_2, 'SYC'); ?>>Seychelloise (Seychelles)</option>
                    <option value="SLE" <?php selected($saved_nationalite_tuteur_legal_2, 'SLE'); ?>>Sierra-Léonaise (Sierra Leone)</option>
                    <option value="SGP" <?php selected($saved_nationalite_tuteur_legal_2, 'SGP'); ?>>Singapourienne (Singapour)</option>
                    <option value="SVK" <?php selected($saved_nationalite_tuteur_legal_2, 'SVK'); ?>>Slovaque (Slovaquie)</option>
                    <option value="SVN" <?php selected($saved_nationalite_tuteur_legal_2, 'SVN'); ?>>Slovène (Slovénie)</option>
                    <option value="SOM" <?php selected($saved_nationalite_tuteur_legal_2, 'SOM'); ?>>Somalienne (Somalie)</option>
                    <option value="SDN" <?php selected($saved_nationalite_tuteur_legal_2, 'SDN'); ?>>Soudanaise (Soudan)</option>
                    <option value="LKA" <?php selected($saved_nationalite_tuteur_legal_2, 'LKA'); ?>>Sri-Lankaise (Sri Lanka)</option>
                    <option value="ZAF" <?php selected($saved_nationalite_tuteur_legal_2, 'ZAF'); ?>>Sud-Africaine (Afrique du Sud)</option>
                    <option value="KOR" <?php selected($saved_nationalite_tuteur_legal_2, 'KOR'); ?>>Sud-Coréenne (Corée du Sud)</option>
                    <option value="SSD" <?php selected($saved_nationalite_tuteur_legal_2, 'SSD'); ?>>Sud-Soudanaise (Soudan du Sud)</option>
                    <option value="SWE" <?php selected($saved_nationalite_tuteur_legal_2, 'SWE'); ?>>Suédoise (Suède)</option>
                    <option value="CHE" <?php selected($saved_nationalite_tuteur_legal_2, 'CHE'); ?>>Suisse (Suisse)</option>
                    <option value="SUR" <?php selected($saved_nationalite_tuteur_legal_2, 'SUR'); ?>>Surinamaise (Suriname)</option>
                    <option value="SWZ" <?php selected($saved_nationalite_tuteur_legal_2, 'SWZ'); ?>>Swazie (Swaziland)</option>
                    <option value="SYR" <?php selected($saved_nationalite_tuteur_legal_2, 'SYR'); ?>>Syrienne (Syrie)</option>
                    <option value="TJK" <?php selected($saved_nationalite_tuteur_legal_2, 'TJK'); ?>>Tadjike (Tadjikistan)</option>
                    <option value="TZA" <?php selected($saved_nationalite_tuteur_legal_2, 'TZA'); ?>>Tanzanienne (Tanzanie)</option>
                    <option value="TCD" <?php selected($saved_nationalite_tuteur_legal_2, 'TCD'); ?>>Tchadienne (Tchad)</option>
                    <option value="CZE" <?php selected($saved_nationalite_tuteur_legal_2, 'CZE'); ?>>Tchèque (Tchéquie)</option>
                    <option value="THA" <?php selected($saved_nationalite_tuteur_legal_2, 'THA'); ?>>Thaïlandaise (Thaïlande)</option>
                    <option value="TGO" <?php selected($saved_nationalite_tuteur_legal_2, 'TGO'); ?>>Togolaise (Togo)</option>
                    <option value="TON" <?php selected($saved_nationalite_tuteur_legal_2, 'TON'); ?>>Tonguienne (Tonga)</option>
                    <option value="TTO" <?php selected($saved_nationalite_tuteur_legal_2, 'TTO'); ?>>Trinidadienne (Trinité-et-Tobago)</option>
                    <option value="TUN" <?php selected($saved_nationalite_tuteur_legal_2, 'TUN'); ?>>Tunisienne (Tunisie)</option>
                    <option value="TKM" <?php selected($saved_nationalite_tuteur_legal_2, 'TKM'); ?>>Turkmène (Turkménistan)</option>
                    <option value="TUR" <?php selected($saved_nationalite_tuteur_legal_2, 'TUR'); ?>>Turque (Turquie)</option>
                    <option value="TUV" <?php selected($saved_nationalite_tuteur_legal_2, 'TUV'); ?>>Tuvaluane (Tuvalu)</option>
                    <option value="UKR" <?php selected($saved_nationalite_tuteur_legal_2, 'UKR'); ?>>Ukrainienne (Ukraine)</option>
                    <option value="URY" <?php selected($saved_nationalite_tuteur_legal_2, 'URY'); ?>>Uruguayenne (Uruguay)</option>
                    <option value="VUT" <?php selected($saved_nationalite_tuteur_legal_2, 'VUT'); ?>>Vanuatuane (Vanuatu)</option>
                    <option value="VAT" <?php selected($saved_nationalite_tuteur_legal_2, 'VAT'); ?>>Vaticane (Vatican)</option>
                    <option value="VEN" <?php selected($saved_nationalite_tuteur_legal_2, 'VEN'); ?>>Vénézuélienne (Venezuela)</option>
                    <option value="VNM" <?php selected($saved_nationalite_tuteur_legal_2, 'VNM'); ?>>Vietnamienne (Viêt Nam)</option>
                    <option value="YEM" <?php selected($saved_nationalite_tuteur_legal_2, 'YEM'); ?>>Yéménite (Yémen)</option>
                    <option value="ZMB" <?php selected($saved_nationalite_tuteur_legal_2, 'ZMB'); ?>>Zambienne (Zambie)</option>
                    <option value="ZWE" <?php selected($saved_nationalite_tuteur_legal_2, 'ZWE'); ?>>Zimbabwéenne (Zimbabwe)</option>
                </select><br><br>
			
			
			<hr>
	    </div>

		<label>28. Durée prévue du séjour sur le territoire de la France <span class="required">*</span></label><br>
		<input type="radio" name="duree" value="entre_3_et_6_mois" <?php checked($saved_duree, "entre_3_et_6_mois"); ?>> Entre 3 et 6 mois<br>
		<input type="radio" name="duree" value="entre_6_mois_et_un_an" <?php checked($saved_duree, "entre_6_mois_et_un_an"); ?>> Entre 6 mois et un an<br>
		<input type="radio" name="duree" value="superieur_a_un_an" <?php checked($saved_duree, "superieur_a_un_an"); ?>> Supérieure à un an <br><br>
	

		<label>29. Si vous comptez effectuer ce séjour avec des membres de votre famille, veuillez indiquer :</label><br>
		<table id="visa-famille-table">
			<thead>
				<tr>
				<th>Lien de parenté</th>
				<th>Nom(s), prénom(s)</th>
				<th>Date de naissance (jj/mm/aa)</th>
				<th>Nationalité</th>
				</tr>
			</thead>
			<tbody>
				<tr>
				<td><input type="text" name="lien_parent[]"></td>
				<td><input type="text" name="nom_prenom[]"></td>
				<td><input type="date" name="date_naissance[]"></td>
				<td><input type="text" name="nationalite_famille[]"></td>
				</tr>
			</tbody>
		</table>
		<button type="button" class="btn-add" id="add-row" style="display: block;margin: 0 auto;">Ajouter une ligne</button><br><br>
	
		<label>30. Quels seront vos moyens d'existence en France ? <span class="required">*</span></label><br>
		<input type="text" name="moyens_existence" value="<?php echo esc_attr($saved_moyens_existence); ?>" required><br><br>
	
		<label>Serez-vous titulaire d'une bourse ?</label><br>
		<input type="radio" name="bourse" value="non" required> Non<br>
		<input type="radio" name="bourse" value="oui" required> Oui <br>
	
		<label>Si oui, indiquez le nom, l'adresse, le courriel, le téléphone de l'organisme et le montant de la bourse :</label><br>
		<textarea name="bourse_detail"></textarea><br><br>
	
		<fieldset class="financing">
      <legend>
        31. Les frais de voyage et de subsistance durant le séjour du demandeur
        sont financés : <span class="required">*</span>
      </legend>
      <!-- Choix du financeur -->
			<div class="financing-options">
				<label><input type="radio" name="financement" value="demandeur"> Par le demandeur</label>
				<label><input type="radio" name="financement" value="garant"> Par un garant</label>
			</div>

			<!-- Section Demandeur -->
			<div class="subsection" data-for="demandeur">
				<p>Moyens de subsistance :<span class="required">*</span></p>
				<div class="checkbox-grid">
				<label><input type="checkbox" name="demandeur_financement_moyen[]" value="liquide"> Argent liquide</label>
				<label><input type="checkbox" name="demandeur_financement_moyen[]" value="cheque"> Chèques de voyage</label>
				<label><input type="checkbox" name="demandeur_financement_moyen[]" value="credit"> Carte de crédit</label>
				<label><input type="checkbox" name="demandeur_financement_moyen[]" value="hebergement"> Hébergement prépayé</label>
				<label><input type="checkbox" name="demandeur_financement_moyen[]" value="transport"> Transport prépayé</label>
				<label>
					<input type="checkbox" name="demandeur_financement_moyen[]" value="autre"> Autre  
					<input type="text" name="demandeur_financement_moyen_autre" placeholder="Précisez">
				</label>
				</div>
			</div>

			<!-- Section Garant -->
			<div class="subsection" data-for="garant">
				<p>Précisions sur le garant :</p>
				<label><input type="radio" name="financement_garant" value="garant_vise"> Par l'entreprise ou l'organisation</label>
				<label>
				<input type="radio" name="financement_garant" value="garant_autre"> Autre  
				<input type="text" name="garant_autre_detail" placeholder="Nom" required>
				</label>

                <div id="moyen_sub" style="display:none;">
                    <p>Moyens de subsistance :</p>
    				<div class="checkbox-grid">
    				<label><input type="checkbox" name="garant_financement_moyen[]" value="liquide"> Argent liquide</label>
    				<label><input type="checkbox" name="garant_financement_moyen[]" value="finance"> Tous frais financés</label>
    				<label><input type="checkbox" name="garant_financement_moyen[]" value="hebergement"> Hébergement fourni</label>
    				<label><input type="checkbox" name="garant_financement_moyen[]" value="transport"> Transport prépayé</label>
    				<label>
    					<input type="checkbox" name="garant_financement_moyen[]" value="autre"> Autre  
    					<input type="text" name="garant_financement_moyen_autre" placeholder="Précisez">
    				</label>
    				</div>
                </div>
			</div>

			<div class="cm-hidden-block" style="display:none;">
			<legend>Serez-vous pris(e) en charge par une ou plusieurs personne(s) en France?</legend><br/>
			<label><input type="radio" name="prise_en_charge" value="non" required/>Non</label><br/>
			<label><input type="radio" name="prise_en_charge" value="oui" required/> Oui </label>
			<br/>
			<label for="info_prise_en_charge"> Si oui, indiquez leur nom, nationalité, qualité, adresse, courriel et téléphone :</label><br/>
			<textarea id="info_prise_en_charge" name="info_prise_en_charge"></textarea><br/><br />
			</div>
		</fieldset>
		<label>32. Des membres de votre famille résident-ils en France ?</label><br>
		<input type="radio" name="famille_resident" value="non" required> Non<br>
		<input type="radio" name="famille_resident" value="oui" required> Oui <br>
	
		<label>Si oui, indiquez leur nom, nationalité, qualité, adresse, courriel et téléphone :</label><br>
		<textarea name="info_famille_resident"></textarea><br><br>
	
		<label>33. Avez-vous déjà résidé plus de trois mois consécutifs en France ?</label><br>
		<input type="radio" name="duree_anterieure" value="non" required> Non<br>
		<input type="radio" name="duree_anterieure" value="oui" required> Oui <br>
	
		<label>Si oui, précisez à quelle(s) date(s) et pour quel(s) motif(s) :</label><br>
		<textarea name="info_duree_anterieure"></textarea><br>
	
		<label>A quelle(s) adresse(s) ?</label><br>
		<textarea name="adresse_duree_anterieure"></textarea><br><br>
	
		<p>En connaissance de cause, j'accepte ce qui suit : aux fins de l'examen de ma demande de visa, il y a lieu de recueillir les données requises dans ce formulaire, de me photographier et, le cas échéant, de prendre mes empreintes digitales. Les données à caractère personnel me concernant qui figurent dans le présent formulaire de demande de visa, ainsi que mes empreintes digitales et ma photo, seront communiquées aux autorités françaises compétentes et traitées par elles, aux fins de la décision relative à ma demande de visa.</p>
	
		<p>Ces données ainsi que celles concernant la décision relative à ma demande de visa, ou toute décision d'annulation ou d'abrogation du visa, seront saisies et conservées dans la base française des données biométriques VISABIO pendant une période maximale de cinq ans, durant laquelle elles seront accessibles aux autorités chargées des visas, aux autorités compétentes chargées de contrôler les visas aux frontières, aux autorités nationales compétentes en matière d'immigration et d'asile aux fins de la vérification du respect des conditions d'entrée et de séjour réguliers sur le territoire de la France, aux fins de l'identification des personnes qui ne remplissent pas ou plus ces conditions. Dans certaines conditions, ces données seront aussi accessibles aux autorités françaises désignées et à Europol aux fins de la prévention et de la détection des infractions terroristes et des autres infractions pénales graves, ainsi que dans la conduite des enquêtes s'y rapportant. L'autorité française est compétente pour le traitement des données [(...)]</p>
	
		<p>En application de la loi n° 78-17 du 6 janvier 1978 relative à l’informatique et aux libertés je suis informé(e) de mon droit d'obtenir auprès de l'État français communication des informations me concernant qui sont enregistrées dans la base VISABIO et de mon droit de demander que ces données soient rectifiées si elles sont erronées, ou éventuellement effacées seulement si elles ont été traitées de façon illicite. Ce droit d’accès et de rectification éventuelle s’exerce auprès du chef de poste. La Commission nationale de l'Informatique et des Libertés (CNIL) - 3 Place de Fontenoy - TSA 80715 - 75334 PARIS CEDEX 07 -peut éventuellement être saisie si j'entends contester les conditions de protection des données à caractère personnel me concernant.</p>
	
		<p>Je suis informé que tout dossier incomplet accroît le risque de refus de ma demande de visa par l'autorité consulaire et que celle-ci peut être amenée à conserver mon passeport pendant le délai de traitement de ma demande</p>
		<p>Je déclare qu'à ma connaissance, toutes les indications que j'ai fournies sont correctes et complètes. Je suis informé(e) que toute fausse déclaration entraînera le rejet de ma demande ou l'annulation du visa s'il a déjà été délivré, et sera susceptible d'entraîner des poursuites pénales à mon égard en application du droit français.</p>
		<p>« Je suis informé(e) que le silence gardé par l’administration plus de deux mois après le dépôt de ma demande attesté par la remise d’une quittance vaut décision implicite de rejet . Cette décision pourra être contestée auprès de la Commission des recours contre les décisions de refus de visa, BP 83.609, 44036 Nantes CEDEX 1, dans un délai de deux mois suivant la naissance de la décision implicite</p>
		<p>Je m'engage à quitter le territoire français avant l'expiration du visa, si celui-ci m'a été délivré, et si je n'ai pas obtenu le droit de séjourner en France au delà de cette durée.</p>
		<p>Je suis informé(e) que le livret d’informations « Venir vivre en France » est disponible à l’adresse www.immigration.interieur.gouv.fr et www.ofii.fr</p>
		
    <?php endif; ?>
		<button type="submit" name="visa_level3_submit" value="submitted">Continuer</button>
</form>

<style>
	/*------------------------------------------------------------------
	Visa Manager Form Styles
	Inspired by visalog.eu’s clean, blue-and-white theme
	-------------------------------------------------------------------*/

	/* 1. Color Palette & Typography */
	:root {
	--vm-primary:   #004494;  /* deep blue */
	--vm-primary-80:#003776;  /* hover/darker */
	--vm-accent:    #FFD617;  /* bright yellow */
	--vm-text:      #2A333B;  /* dark gray-blue */
	--vm-border:    #CCCCCC;  /* light gray */
	--vm-radius:    6px;
	--vm-spacing:   1rem;
	--vm-font:      'Montserrat', sans-serif;
	}
	#autorite-parentale {
    background-color: #f8f9fa;
    border: 2px solid #007bff;
    border-radius: 8px;
    padding: 25px;
    margin: 20px 0;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

#autorite-parentale hr {
    border-color: #007bff;
    margin: 20px 0;
}

#autorite-parentale label {
    font-weight: 500;
    color: #333;
}

#autorite-parentale input[type="text"],
#autorite-parentale select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    margin-bottom: 8px;
}

#autorite-parentale input[type="text"]:focus,
#autorite-parentale select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    outline: none;
}

#autorite-parentale .required {
    color: #dc3545;
    font-weight: bold;
}

.preamble-notice {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    font-size: 14px;
    color: #856404;
    font-weight: 500;
}

	.visa-form {
	max-width: 70%;
	margin: 2em auto;
	padding: 2em;
	background: #FFF;
	border-radius: var(--vm-radius);
	box-shadow: 0 4px 12px rgba(0,0,0,0.05);
	font-family: var(--vm-font);
	color: var(--vm-text);
	}

	/* 2. Individual Fields */
	.visa-form .form-field {
	display: flex;
	flex-direction: column;
	margin-bottom: var(--vm-spacing);
	}

	.visa-form label {
	font-weight: 500;
	margin-bottom: 0.5em;
	font-size: 0.95rem;
	}

	.visa-form .required {
	color: var(--vm-accent);
	}

	/* 3. Select & Hidden Inputs */
	.visa-form select {
	width: 100%;
	padding: 0.6em 0.8em;
	font-size: 1rem;
	border: 1px solid var(--vm-border);
	border-radius: var(--vm-radius);
	background: #FFF;
	transition: border-color 0.2s;
	color: var(--vm-text);
	}

	.visa-form select:focus {
	outline: none;
	border-color: var(--vm-primary);
	}

	/* 4. Inputs */
	.visa-form input[type="text"] {
	width: 100%;
	padding: 0.6em 0.8em;
	font-size: 1rem;
	border: 1px solid var(--vm-border);
	border-radius: var(--vm-radius);
	background: #FFF;
	transition: border-color 0.2s;
	color: var(--vm-text);
	}
	.visa-form input[type="textearea"] {
	width: 100%;
	padding: 0.6em 0.8em;
	font-size: 1rem;
	border: 1px solid var(--vm-border);
	border-radius: var(--vm-radius);
	background: #FFF;
	transition: border-color 0.2s;
	color: var(--vm-text);
	}

	/* 5. Submit Button */
	.visa-form button[type="submit"] {
	display: inline-block;
	margin-top: var(--vm-spacing);
	padding: 0.75em 1.5em;
	font-size: 1rem;
	font-weight: 600;
	color: #FFF;
	background: var(--vm-primary);
	border: none;
	border-radius: var(--vm-radius);
	cursor: pointer;
	transition: background 0.2s, transform 0.1s;
	}

	.visa-form button[type="submit"]:hover {
	background: var(--vm-primary-80);
	transform: translateY(-1px);
	}

	.visa-form button[type="submit"]:active {
	transform: translateY(0);
	}

	/* 6. Responsive */
	@media (max-width: 480px) {
	.visa-form {
		padding: 1.5em;
	}

	.visa-form button[type="submit"] {
		width: 100%;
		text-align: center;
	}
	}

	.financing {
	border: 1px solid #ddd;
	padding: 1.5rem;
	border-radius: 6px;
	background: #fff;
	margin-bottom: 2rem;
	font-family: sans-serif;
	}

	.financing legend {
	color: black;
	margin-bottom: 1rem;
	}

	.financing-options label {
	display: inline-block;
	margin-right: 1.5rem;
	cursor: pointer;
	}

	.subsection {
	margin-top: 1.5rem;
	padding: 1rem;
	background: #f9f9f9;
	border-radius: 4px;
	display: none;
	transition: all .3s;
	}

	.subsection.active {
	display: block;
	}

	.checkbox-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 0.75rem;
	margin-top: 0.5rem;
	}

	.checkbox-grid label {
	display: flex;
	align-items: center;
	background: #fff;
	padding: 0.5rem;
	border: 1px solid #ccc;
	border-radius: 4px;
	cursor: pointer;
	}

	.checkbox-grid input[type="text"] {
	flex: 1;
	margin-left: 0.5rem;
	border: 1px solid #ccc;
	padding: 0.3rem 0.5rem;
	border-radius: 4px;
	}

	span.required {
	color: #d93025;
	margin-left: 0.25em;
	vertical-align: super;
	}

	.btn-add,
	.btn-remove {
	background: var(--vm-primary, #004494);
	color: #fff;
	border: none;
	border-radius: 4px;
	padding: 0.4rem 0.7rem;
	font-size: 1rem;
	cursor: pointer;
	transition: background .2s;
	}

	.btn-add:hover,
	.btn-remove:hover {
	background: var(--vm-primary-80, #003776);
	}

	.btn-remove {
	background: #d93025;
	}

	.btn-remove:hover {
	background: #b1271b;
	}
	
	#personne, #hotel, #entreprise, #contact {
	    border: groove;
        border-radius: 10px;
        padding: 10px;
        margin: 10px 0px;
	}
	
	#go-personne, #go-hotel, #go-entreprise {
	    border: groove;
        padding: 10px;
        border-radius: 10px;
	}
</style>
<script>
	document.addEventListener('DOMContentLoaded', function() {
		// --- Variables globales PHP injectées proprement ---
		const VISA_TODAY = "<?php echo esc_js($today); ?>";
		const VISA_TYPE = "<?php echo esc_js($visa_type); ?>";
		console.log('VISA_TYPE au début:', VISA_TYPE);
		
		try {

		// === 1. Autorité parentale (mineurs) ===
		const birthInput  = document.querySelector('input[name="birth_date"]');
		// Utiliser querySelectorAll car il peut y avoir plusieurs sections (court séjour et long séjour)
		const parentFields = document.querySelectorAll('#autorite-parentale');
		const parentLabel = document.querySelector('label[for="autorite-parentale"]');

		// Fonction pour gérer les attributs required des champs dans #autorite-parentale
		function toggleRequiredFieldsInParentalSection(isVisible) {
			// Parcourir toutes les sections autorite-parentale (il peut y en avoir plusieurs)
			parentFields.forEach(parentField => {
				if (!parentField) return;
				
				// Récupérer tous les champs required dans la section
				const requiredFields = parentField.querySelectorAll('input[required], select[required], textarea[required]');
				
				requiredFields.forEach(field => {
					if (isVisible) {
						// Si la section est visible, restaurer l'attribut required
						field.setAttribute('required', 'required');
					} else {
						// Si la section est cachée, retirer l'attribut required pour éviter l'erreur "not focusable"
						field.removeAttribute('required');
					}
				});
			});
		}

		function toggleParentalRequirement() {
			if (!birthInput?.value) {
				parentLabel?.querySelector('span.required')?.remove();
				// Cacher toutes les sections et retirer les required
				parentFields.forEach(parentField => {
					if (parentField) {
						parentField.style.display = 'none';
						parentField.removeAttribute('required');
					}
				});
				toggleRequiredFieldsInParentalSection(false);
				return;
			}
			const today = new Date();
			const birthDate = new Date(birthInput.value);
			let age = today.getFullYear() - birthDate.getFullYear();
			const m = today.getMonth() - birthDate.getMonth();
			if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
				age--;
			}
			if (age < 18) {
				// Afficher toutes les sections et activer les required
				parentFields.forEach(parentField => {
					if (parentField) {
						parentField.style.display = '';
						parentField.setAttribute('required', 'required');
					}
				});
				toggleRequiredFieldsInParentalSection(true);
				if (!parentLabel?.querySelector('span.required')) {
					const star = document.createElement('span');
					star.className = 'required';
					star.textContent = ' *';
					parentLabel?.appendChild(star);
				}
			} else {
				// Cacher toutes les sections et retirer les required
				parentFields.forEach(parentField => {
					if (parentField) {
						parentField.style.display = 'none';
						parentField.removeAttribute('required');
					}
				});
				toggleRequiredFieldsInParentalSection(false);
				parentLabel?.querySelector('span.required')?.remove();
			}
		}

		// Vérifier l'état initial au chargement de la page
		function checkInitialParentalSectionState() {
			parentFields.forEach(parentField => {
				if (parentField) {
					const isHidden = parentField.style.display === 'none' || 
					                 window.getComputedStyle(parentField).display === 'none';
					if (isHidden) {
						toggleRequiredFieldsInParentalSection(false);
					}
				}
			});
		}

		if (birthInput) {
			birthInput.addEventListener('change', toggleParentalRequirement);
			toggleParentalRequirement();
		} else {
			// Si pas de champ birth_date, vérifier l'état initial de la section
			checkInitialParentalSectionState();
		}
		
		// Vérifier aussi après un court délai pour s'assurer que le DOM est complètement chargé
		setTimeout(checkInitialParentalSectionState, 100);

		// === Fonction globale pour gérer tous les champs required dans les sections cachées ===
		function manageRequiredFieldsInHiddenSections() {
			// Liste de tous les sélecteurs de sections qui peuvent être cachées
			const hiddenSections = [
				'.subsection:not(.active)',  // Sections .subsection non actives
				'#autorite-parentale[style*="display: none"]',  // Section autorité parentale cachée
				'#autorite-parentale',  // Vérifier aussi avec getComputedStyle
				'#moyen_sub[style*="display: none"]',  // Section moyen_sub cachée
				'#moyen_sub',  // Vérifier aussi avec getComputedStyle
				'#personne[style*="display: none"]',
				'#hotel[style*="display: none"]',
				'#entreprise[style*="display: none"]',
				'#contact[style*="display: none"]',
				'#remplisseur[style*="display: none"]'
			];

			// Fonction pour vérifier si un élément est vraiment caché
			function isElementHidden(element) {
				if (!element) return true;
				// Vérifier style inline
				if (element.style.display === 'none') return true;
				// Vérifier computed style
				const computed = window.getComputedStyle(element);
				if (computed.display === 'none' || computed.visibility === 'hidden') return true;
				// Pour .subsection, vérifier si elle a la classe active
				if (element.classList.contains('subsection') && !element.classList.contains('active')) return true;
				return false;
			}

			// Parcourir toutes les sections et retirer required si cachées
			hiddenSections.forEach(selector => {
				try {
					const sections = document.querySelectorAll(selector);
					sections.forEach(section => {
						if (isElementHidden(section)) {
							const requiredFields = section.querySelectorAll('input[required], select[required], textarea[required]');
							requiredFields.forEach(field => {
								field.removeAttribute('required');
							});
						}
					});
				} catch (e) {
					// Ignorer les erreurs de sélecteur invalide
				}
			});

			// Vérifier aussi toutes les sections .subsection
			const allSubsections = document.querySelectorAll('.subsection');
			allSubsections.forEach(subsection => {
				const isActive = subsection.classList.contains('active');
				const computedStyle = window.getComputedStyle(subsection);
				const isVisible = isActive && computedStyle.display !== 'none';
				
				if (!isVisible) {
					const requiredFields = subsection.querySelectorAll('input[required], select[required], textarea[required]');
					requiredFields.forEach(field => {
						// Exception pour garant_autre_detail : vérifier si la section garant est visible ET si "garant_autre" est sélectionné
						if (field.name === 'garant_autre_detail') {
							const garantAutreRadio = document.querySelector('input[name="financement_garant"][value="garant_autre"]');
							if (!isVisible || !(garantAutreRadio && garantAutreRadio.checked)) {
								field.removeAttribute('required');
							}
						} else {
							field.removeAttribute('required');
						}
					});
				} else {
					// Si la section est visible, gérer garant_autre_detail spécifiquement
					if (subsection.dataset.for === 'garant') {
						const garantAutreDetail = subsection.querySelector('input[name="garant_autre_detail"]');
						const garantAutreRadio = document.querySelector('input[name="financement_garant"][value="garant_autre"]');
						if (garantAutreDetail) {
							if (garantAutreRadio && garantAutreRadio.checked) {
								garantAutreDetail.setAttribute('required', 'required');
							} else {
								garantAutreDetail.removeAttribute('required');
							}
						}
					}
				}
			});
		}

		// Exécuter au chargement et après un délai
		manageRequiredFieldsInHiddenSections();
		setTimeout(manageRequiredFieldsInHiddenSections, 200);
		setTimeout(manageRequiredFieldsInHiddenSections, 500);

		// Nettoyer les champs required dans les sections cachées avant la soumission du formulaire
		const forms = document.querySelectorAll('form');
		forms.forEach(form => {
			form.addEventListener('submit', function(e) {
				// Nettoyer une dernière fois avant la validation HTML5
				manageRequiredFieldsInHiddenSections();
			}, false);
		});

		// === 2. Financement ===
		// Gestion de l'affichage des sections demandeur/garant pour court séjour ET long séjour
		if (VISA_TYPE === 'court_sejour' || VISA_TYPE === 'long_sejour') {
			const financingRadios = document.querySelectorAll('input[name="financement"]');
			const financingSections = document.querySelectorAll('.subsection');

			// Fonction pour gérer les attributs required dans une section
			function toggleRequiredInSection(section, isVisible) {
				if (!section) return;
				const requiredFields = section.querySelectorAll('input[required], select[required], textarea[required]');
				requiredFields.forEach(field => {
					if (isVisible) {
						// Vérifier si c'est garant_autre_detail - il ne doit être required que si "garant_autre" est sélectionné
						if (field.name === 'garant_autre_detail') {
							const garantAutreRadio = document.querySelector('input[name="financement_garant"][value="garant_autre"]');
							if (garantAutreRadio && garantAutreRadio.checked) {
								field.setAttribute('required', 'required');
							} else {
								field.removeAttribute('required');
							}
						} else {
							field.setAttribute('required', 'required');
						}
					} else {
						field.removeAttribute('required');
					}
				});
			}

			// Fonction pour gérer garant_autre_detail spécifiquement
			function manageGarantAutreDetail() {
				const garantSection = document.querySelector('.subsection[data-for="garant"]');
				const garantAutreRadio = document.querySelector('input[name="financement_garant"][value="garant_autre"]');
				const garantAutreDetail = document.querySelector('input[name="garant_autre_detail"]');
				
				if (garantSection && garantAutreDetail) {
					const isGarantSectionVisible = garantSection.classList.contains('active');
					const isGarantAutreSelected = garantAutreRadio && garantAutreRadio.checked;
					
					if (isGarantSectionVisible && isGarantAutreSelected) {
						garantAutreDetail.setAttribute('required', 'required');
					} else {
						garantAutreDetail.removeAttribute('required');
					}
				}
			}

			// Vérifier que les éléments existent avant de les manipuler
			if (financingRadios.length > 0 && financingSections.length > 0) {
				function showFinancingSection() {
					const choice = document.querySelector('input[name="financement"]:checked')?.value;
					financingSections.forEach(sec => {
						const isActive = sec.dataset.for === choice;
						sec.classList.toggle('active', isActive);
						// Gérer les attributs required selon la visibilité
						toggleRequiredInSection(sec, isActive);
					});
					// Gérer garant_autre_detail après le changement
					setTimeout(manageGarantAutreDetail, 10);
				}

				financingRadios.forEach(r => r.addEventListener('change', showFinancingSection));
				showFinancingSection();
				
				// Écouter aussi les changements sur financement_garant pour garant_autre_detail
				const garantRadios = document.querySelectorAll('input[name="financement_garant"]');
				garantRadios.forEach(radio => {
					radio.addEventListener('change', function() {
						manageGarantAutreDetail();
						// Nettoyer aussi via la fonction globale
						setTimeout(manageRequiredFieldsInHiddenSections, 10);
					});
				});
				
				// Appeler aussi la fonction globale après chaque changement
				financingRadios.forEach(r => {
					r.addEventListener('change', function() {
						setTimeout(manageRequiredFieldsInHiddenSections, 10);
					});
				});
			}
		}

		// === 3. Documents (upload) ===
		const docContainer = document.getElementById('documents-list');
		const addDocBtn = document.getElementById('add-document');

		function bindDocRemove(btn) {
			btn.addEventListener('click', function() {
				if (docContainer.children.length > 1) {
					this.closest('.document-item').remove();
				}
			});
		}

		function createDocumentItem() {
			const div = document.createElement('div');
			div.className = 'document-item';
			div.innerHTML = `
				<input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg" />
				<button type="button" class="btn-remove" aria-label="Supprimer">−</button>
			`;
			bindDocRemove(div.querySelector('.btn-remove'));
			return div;
		}

		if (docContainer && addDocBtn) {
			bindDocRemove(docContainer.querySelector('.btn-remove'));
			addDocBtn.addEventListener('click', () => {
				docContainer.appendChild(createDocumentItem());
			});
		}

		// === 4. Calculateur de jours autorisés (uniquement pour court séjour) ===
		const containerCalcul = document.getElementById('date-ranges');
		if (containerCalcul && VISA_TYPE === 'court_sejour') {
			const maxDaysInput = document.getElementById('max_days');
			if (!maxDaysInput) return;

			const maxDays = parseInt(maxDaysInput.value, 10);
			const addBtnCalcul = document.getElementById('add-range');
			const resultDisplay = document.getElementById('calculated-days');

			function recalc() {
                console.log('2');
                let totalTaken = 0;
            
                // Récupérer la date d'arrivée
                const arrivalInput = document.querySelector('input[name="arrival_date"]');
                let arrivalDate = arrivalInput?.value ? new Date(arrivalInput.value) : null;
            
                // Calculer la date minimum : 6 mois (180 jours) avant l'arrivée
                let windowStart = arrivalDate ? new Date(arrivalDate) : null;
                if (windowStart) {
                    windowStart.setDate(windowStart.getDate() - 180);
                }
            
                document.querySelectorAll('.range-block').forEach(block => {
                    const inDate = block.querySelector('.entry-date').value;
                    const outDate = block.querySelector('.exit-date').value;
            
                    if (inDate && outDate) {
                        const d1 = new Date(inDate);
                        const d2 = new Date(outDate);
            
                        // IGNORER la période si elle est complètement hors fenêtre
                        if (arrivalDate && (d2 < windowStart || d1 > arrivalDate)) {
                            return;
                        }
            
                        // Ajuster la période à la fenêtre si elle déborde
                        const start = arrivalDate ? new Date(Math.max(d1, windowStart)) : d1;
                        const end = arrivalDate ? new Date(Math.min(d2, arrivalDate)) : d2;
            
                        const diff = (end - start) / (1000 * 60 * 60 * 24);
                        if (!isNaN(diff) && diff >= 0) {
                            totalTaken += Math.floor(diff) + 1; // inclusif
                        }
                    }
                });
            
                const remaining = Math.max(0, maxDays - totalTaken);
                resultDisplay.textContent = remaining;
            }


            // Appeler à chaque recalcul
            function recalcAndLimit() {
                recalc();
                limitDepartureDate();
                console.log('4');
            }
            
			function bindEntryExit(entryInput, exitInput) {
				entryInput.addEventListener('change', () => {
					exitInput.min = entryInput.value || '';
					if (exitInput.value && exitInput.value < entryInput.value) {
						exitInput.value = entryInput.value;
					}
					recalcAndLimit();
				});
				exitInput.addEventListener('change', () => {
					entryInput.max = exitInput.value || '';
					if (entryInput.value && entryInput.value > exitInput.value) {
						entryInput.value = exitInput.value;
					}
					recalcAndLimit();
				});
			}

			if (addBtnCalcul) {
				addBtnCalcul.addEventListener('click', () => {
					const wrapper = document.createElement('div');
					wrapper.className = 'range-block';
					wrapper.style.cssText = 'display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;';
					wrapper.innerHTML = `
						<div style="flex:1; min-width:200px;">
							<label>Entrée</label>
							<input type="date" class="entry-date" max="${VISA_TODAY}">
						</div>
						<div style="flex:1; min-width:200px;">
							<label>Sortie</label>
							<input type="date" class="exit-date" max="${VISA_TODAY}">
						</div>
						<button type="button" class="remove-range button" style="margin-top:1.8em;">–</button>
					`;
					const removeBtn = wrapper.querySelector('.remove-range');
					removeBtn.addEventListener('click', () => {
						wrapper.remove();
						recalcAndLimit();
					});
					bindEntryExit(wrapper.querySelector('.entry-date'), wrapper.querySelector('.exit-date'));
					containerCalcul.appendChild(wrapper);
				});
			}

			// Initialiser le premier bloc
			const firstBlock = containerCalcul.querySelector('.range-block');
			if (firstBlock) {
				bindEntryExit(firstBlock.querySelector('.entry-date'), firstBlock.querySelector('.exit-date'));
			}
			// Sélectionner les inputs du premier séjour
            const arrivalInput = document.querySelector('input[name="arrival_date"]');
            const departureInput = document.querySelector('input[name="departure_date"]');
            
            // Fonction pour limiter la date de départ
            function limitDepartureDate() {
                console.log('1');
                if (!arrivalInput || !departureInput) return;
                
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                
                const todayStr = `${yyyy}-${mm}-${dd}`;
                arrivalInput.min = todayStr;
            
                // Récupérer le nombre de jours autorisés
                const remainingDays = parseInt(document.getElementById('calculated-days').textContent, 10);
                if (isNaN(remainingDays)) return;
            
                // Calculer la date maximale de départ
                if (arrivalInput.value) {
                    const arrivalDate = new Date(arrivalInput.value);
                    const maxDeparture = new Date(arrivalDate);
                    maxDeparture.setDate(arrivalDate.getDate() + remainingDays - 1); // inclusif
            
                    // Formater en yyyy-mm-dd pour l'input date
                    const yyyy = maxDeparture.getFullYear();
                    const mm = String(maxDeparture.getMonth() + 1).padStart(2, '0');
                    const dd = String(maxDeparture.getDate()).padStart(2, '0');
                    departureInput.max = `${yyyy}-${mm}-${dd}`;
            
                    // Ajuster la valeur si elle dépasse le max
                    if (departureInput.value && departureInput.value > departureInput.max) {
                        departureInput.value = departureInput.max;
                    }
                }
            }
            
            if (arrivalInput) {
                arrivalInput.addEventListener('change', recalcAndLimit);
            }
			recalcAndLimit();
		} else {
		    const arrivalInput = document.querySelector('input[name="arrival_date"]');
            const departureInput = document.querySelector('input[name="departure_date"]');
            const radios = document.querySelectorAll('input[name="duree"]');
        
            // Radios non modifiables
            radios.forEach(r => r.disabled = true);
        
            function setMinDeparture() {
                const arrival = new Date(arrivalInput.value);
                if (isNaN(arrival)) return;
        
                // + 90 jours (3 mois approximés)
                const minDeparture = new Date(arrival.getTime() + 90 * 24 * 60 * 60 * 1000);
        
                // Format YYYY-MM-DD
                const minDateStr = minDeparture.toISOString().split("T")[0];
        
                // Empêcher le choix = les dates deviennent grisées automatiquement
                departureInput.min = minDateStr;
        
                // Si la date actuelle n'est plus valide, on la vide
                if (departureInput.value && departureInput.value < minDateStr) {
                    departureInput.value = "";
                }
            }
		}

		// === 5. Champ "sexe_autre" ===
		const sexeRadios = document.querySelectorAll('input[name="sexe"]');
		const sexeAutreInput = document.querySelector('input[name="sexe_autre"]');

        if (sexeAutreInput) {
            function toggleSexeAutre() {
    			const checked = document.querySelector('input[name="sexe"]:checked');
    			if (checked && checked.value === 'autre') {
    				sexeAutreInput.style.display = 'inline-block';
    				sexeAutreInput.setAttribute('required', 'required');
    				sexeAutreInput.focus();
    			} else {
    				sexeAutreInput.style.display = 'none';
    				sexeAutreInput.removeAttribute('required');
    				sexeAutreInput.value = '';
    			}
    		}

    		sexeRadios.forEach(radio => radio.addEventListener('change', toggleSexeAutre));
    		toggleSexeAutre();
        }

		// === 6. Résidence étrangère ===
		const residentRadios = document.querySelectorAll('input[name="resident"]');
		const numResident = document.getElementById('num_resident');
		const validResident = document.getElementById('valid_resident');

		function toggleResidentFields() {
			const checked = document.querySelector('input[name="resident"]:checked');
			if (checked && checked.value === 'oui') {
				if (numResident) {
					numResident.style.display = 'inline-block';
					numResident.setAttribute('required', 'required');
				}
				if (validResident) {
					validResident.style.display = 'inline-block';
					validResident.setAttribute('required', 'required');
				}
			} else {
				if (numResident) {
					numResident.style.display = 'none';
					numResident.removeAttribute('required');
					numResident.value = '';
				}
				if (validResident) {
					validResident.style.display = 'none';
					validResident.removeAttribute('required');
					validResident.value = '';
				}
			}
		}

		residentRadios.forEach(radio => radio.addEventListener('change', toggleResidentFields));
		toggleResidentFields();

		// === 7. Tableaux dynamiques (famille & employeur) ===
		function initTableAddButton(tableId, btnId) {
			const btn = document.getElementById(btnId);
			if (!btn) return;
			btn.addEventListener('click', function() {
				const tbody = document.querySelector(`${tableId} tbody`);
				if (!tbody) return;
				const newRow = tbody.rows[0].cloneNode(true);
				newRow.querySelectorAll('input').forEach(input => input.value = '');
				tbody.appendChild(newRow);
				console.log("ajout");
			});
		}
        console.log("test");
		initTableAddButton('#visa-famille-table', 'add-row');
		initTableAddButton('#visa-employeur-table', 'add-row-employeur');

		// === 8. Synchronisation tableau employeur → textarea ===
		function syncInfoEmployeur() {
			const rows = document.querySelectorAll('#visa-employeur-table tbody tr');
			const lines = Array.from(rows).map(tr => {
				const lien = tr.querySelector('[name="lien_employeur[]"]')?.value.trim() || '';
				const nom = tr.querySelector('[name="nom_prenom_employeur[]"]')?.value.trim() || '';
				const date = tr.querySelector('[name="date_naissance_employeur[]"]')?.value || '';
				const nation = tr.querySelector('[name="nationalite_employeur[]"]')?.value.trim() || '';
				return [lien, nom, date, nation].join(', ');
			});
			const textarea = document.getElementById('info_employeur');
			if (textarea) {
				textarea.value = lines.join('\n');
			}
		}

		const employeurTable = document.getElementById('visa-employeur-table');
		if (employeurTable) {
			employeurTable.addEventListener('input', syncInfoEmployeur);
		}

		// === 9. Affichage dynamique des documents requis ===
		const objetRadios = document.querySelectorAll('input[name="objet"]');
		const docListContainer = document.getElementById('document-list-affichage');

		const documentsRequis = {
			tourisme: ["Passeport valable au moins 6 mois après la date de retour et comportant ≥ 2 pages vierges", "2 photos d’identité récentes conformes aux normes ICAO", "Billet d’avion aller-retour confirmé", "Preuve d’hébergement (réservation d’hôtel ou attestation d’accueil)", "Relevés bancaires des 3 derniers mois ou preuve de ressources financières", "Programme détaillé du séjour ou réservation d’activités", "Assurance voyage internationale couvrant rapatriement et frais médicaux"],
			affaires: ["Passeport valide + ≥ 2 pages vierges", "2 photos d’identité ICAO", "Billet d’avion aller-retour", "Lettre d’invitation / convention de mission de l’entreprise d’accueil", "Attestation de votre employeur précisant l’objet du déplacement", "Relevés bancaires ou justificatifs de financement de votre société", "Preuve d’hébergement (hôtel ou courrier d’accueil)", "Assurance responsabilité civile et médicale professionnelle"],
			visite: ["Passeport valide + photos ID", "Billet aller-retour", "Attestation d’accueil ou lettre d’hébergement de votre hôte", "Copie de la carte d’identité ou titre de séjour de l’hébergeant", "Justificatif de lien familial ou amical (acte de naissance, photos, échanges…)", "Relevés bancaires des 3 derniers mois ou prise en charge de l’hôte", "Assurance voyage couvrant responsabilité civile et soins médicaux"],
			culture: ["Passeport + photos ID ICAO", "Billet aller-retour", "Invitation officielle de l’organisme culturel ou programme détaillé", "Relevés bancaires ou preuve de financement", "Preuve d’hébergement", "Assurance voyage (rapatriement + frais médicaux)"],
			sports: ["Passeport + photos ID", "Billet aller-retour", "Convocation ou invitation de la fédération sportive locale", "Relevés bancaires ou prise en charge par votre fédération", "Preuve d’hébergement", "Assurance médicale sportive couvrant les risques liés à la pratique"],
			visite_officielle: ["Passeport + photos officielles", "Billet aller-retour", "Note verbale du ministère d’envoi ou lettre d’accréditation", "Ordre de mission officiel", "Preuve d’hébergement", "Assurance responsabilité civile et santé"],
			medical: ["Passeport + 2 photos d’identité ICAO", "Formulaire visa (médical) dûment rempli", "Billet aller-retour ou fonds suffisants pour l’achat", "Diagnostic ou certificat médical initial précisant la pathologie", "Lettre de l’établissement de soins confirmant la prise en charge ou rendez-vous", "Justificatifs de ressources pour couvrir les frais médicaux", "Assurance santé / rapatriement incluant le traitement médical"],
			etudes: ["Passeport + photos ID", "Lettre d’admission ou attestation de l’établissement de formation", "Preuve de paiement des frais de scolarité (le cas échéant)", "Relevés bancaires ou garanties financières (bourse, garant…) ", "Preuve d’hébergement (résidence universitaire ou contrat de location)", "Assurance santé / responsabilité civile pour étudiant", "Billet aller-retour ou fonds suffisants pour l’achat"],
			transit: ["Passeport + photos récentes", "Formulaire visa de transit rempli", "Billet confirmé pour la correspondance vers la destination finale", "Visa ou autorisation d’entrée pour le pays de destination (si requis)", "Assurance voyage (optionnelle selon la réglementation du pays)"]
		};

		function afficherDocuments(value) {
			const docs = documentsRequis[value] || [];
			if (!docs.length || !docListContainer) {
				if (docListContainer) docListContainer.innerHTML = "<p>Aucun document défini pour ce motif.</p>";
				return;
			}
			const ul = document.createElement('ul');
			docs.forEach(item => {
				const li = document.createElement('li');
				li.textContent = item;
				ul.appendChild(li);
			});
			docListContainer.innerHTML = "";
			docListContainer.appendChild(ul);
		}

		objetRadios.forEach(radio => {
			radio.addEventListener('change', e => afficherDocuments(e.target.value));
		});

		// === 10. Contraintes de dates (min/max) ===
		const datePairs = [
			{ from: 'date_delivrance', to: 'date_expiration' },
			{ from: 'autre_date_delivrance', to: 'autre_date_expiration' },
			{ from: 'arrival_date', to: 'departure_date' },
			{ from: 'autorisation_validite', to: 'autorisation_delivre_au' }
		];

		datePairs.forEach(pair => {
			const fromInput = document.querySelector(`input[name="${pair.from}"]`);
			const toInput = document.querySelector(`input[name="${pair.to}"]`);
			if (fromInput && toInput) {
				fromInput.addEventListener('change', () => {
					toInput.min = fromInput.value;
				});
			}
		});

		// === 11. Désactivation du bouton submit ===
		const form = document.querySelector('form');
		if (form) {
			form.addEventListener('submit', function() {
				const btn = this.querySelector('button[type="submit"]');
				if (btn) {
					btn.readonly = true;
					btn.textContent = 'Envoi en cours...';
				}
			});
		}
		
		console.log('âÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂâÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂâÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂ Code approache la section PROFESSION âÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂâÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂâÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂ');
		
		// Profession
		const selectSituation = document.querySelector('select[name="situation_professionnelle"]');
        const professionDiv = document.getElementById("profession");
const professionField = professionDiv?.querySelector('select[name="profession"], input[name="profession"]');
const textareaEmployeur = document.querySelector('textarea[name="employeur"]');

// Tous les champs du bloc
const champs = professionDiv.querySelectorAll("input, select");
console.log('champs found:', champs.length);

// Autorité parentale
function checkIfMinor() {
    const birthDateInput = document.getElementById('birth_date');
    const arrivalDateInput = document.querySelector('input[name="arrival_date"]');
    const autoriteDiv = document.getElementById('autorite-parentale');
    const autoriteLabel = document.getElementById('autorite_label');
    
    if (birthDateInput.value && arrivalDateInput.value) {
        const birthDate = new Date(birthDateInput.value);
        const arrivalDate = new Date(arrivalDateInput.value);
        
        // Calculer l'âge à la date d'arrivée
        let age = arrivalDate.getFullYear() - birthDate.getFullYear();
        const monthDiff = arrivalDate.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && arrivalDate.getDate() < birthDate.getDate())) {
            age--;
        }
        
        // Afficher/Masquer l'autorité parentale selon l'âge
        if (age < 18) {
            autoriteDiv.style.display = 'block';
            autoriteLabel.style.display = 'block';
        } else {
            autoriteDiv.style.display = 'none';
            autoriteLabel.style.display = 'none';
        }
        
        // Afficher/Masquer le bloc profession selon l'âge
        if (age >= 18) {
            professionDiv.style.display = 'block';
        } else {
            professionDiv.style.display = 'none';
        }
    } else {
        // Si les dates ne sont pas remplies, masquer les deux blocs
        autoriteDiv.style.display = 'none';
        autoriteLabel.style.display = 'none';
        professionDiv.style.display = 'none';
    }
}

// Vérifier au chargement de la page
document.addEventListener('DOMContentLoaded', checkIfMinor);

// Vérifier lors du changement des dates
document.getElementById('birth_date').addEventListener('change', checkIfMinor);
document.querySelector('input[name="arrival_date"]').addEventListener('change', checkIfMinor);
        
		function toggleProfession() {
    const value = selectSituation.value.trim();
    // ðÂÂÂÂÂÂ CORRECTION CLÉ : Définir les situations qui DOIVENT masquer les champs (indépendamment des valeurs existantes)
    const shouldHideProfession = ["Sans profession", "Chômeur", "Retraité"].includes(value);
    const selectSecteur = professionDiv.querySelector('select[name="secteur_activite"]');

    // âÂÂÂÂ CAS 1 : Situations qui nécessitent TOUJOURS le masquage (priorité absolue)
    if (shouldHideProfession) {
        professionDiv.style.display = "none";
        
        // Nettoyage complet ET suppression des attributs required
        if (professionField) {
            professionField.value = (professionField.tagName === 'SELECT') ? '' : '';
            professionField.removeAttribute('required');
        }
        
        if (selectSecteur) {
            selectSecteur.value = '';
            selectSecteur.removeAttribute('disabled');
            selectSecteur.removeAttribute('required');
            // Supprimer le champ caché étudiant si présent
            const hiddenStudent = document.getElementById('secteur_activite_hidden_student');
            if (hiddenStudent?.parentNode) hiddenStudent.parentNode.removeChild(hiddenStudent);
        }
        
        // Nettoyer tous les champs supplémentaires + supprimer required
        champs?.forEach(ch => {
            ch.value = '';
            ch.removeAttribute('required');
        });
        
        updateTextarea();
        return; // âÂÂ ï¸ÂÂ SORTIE IMMÉDIATE - Évite toute logique contradictoire
    }

    // âÂÂÂÂ CAS 2 : "Étudiant" - Gestion spécifique avec verrouillage
    if (value === "Etudiant") {
        professionDiv.style.display = "block";
        
        if (professionField) {
            professionField.value = (professionField.tagName === 'SELECT') ? '69005' : 'Etudiant';
            professionField.setAttribute('required', 'required');
        }
        
        if (selectSecteur) {
            // Sélectionner "Autres activités" ou fallback
            const targetOption = Array.from(selectSecteur.options).find(o => o.value === "Autres activités");
            selectSecteur.value = targetOption?.value || selectSecteur.options[0]?.value || '';
            selectSecteur.setAttribute('disabled', 'disabled');
            selectSecteur.setAttribute('required', 'required');
            
            // Créer/mettre à jour champ caché pour la soumission
            let hiddenField = document.getElementById('secteur_activite_hidden_student');
            if (!hiddenField) {
                hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.id = 'secteur_activite_hidden_student';
                hiddenField.name = 'secteur_activite';
                document.querySelector('form')?.appendChild(hiddenField);
            }
            hiddenField.value = selectSecteur.value;
        }
        
        // Champs supplémentaires requis
        champs?.forEach(ch => ch.setAttribute('required', 'required'));
        updateTextarea();
        return;
    }

    // âÂÂÂÂ CAS 3 : "En activité" ou autres situations avec données existantes
    const hasExistingValue = professionField?.value?.trim() !== '';
    const showBlock = (value === "En activité") || hasExistingValue;

    if (showBlock) {
        professionDiv.style.display = "block";
        
        if (professionField) {
            professionField.setAttribute('required', 'required');
        }
        
        if (selectSecteur) {
            selectSecteur.removeAttribute('disabled');
            selectSecteur.setAttribute('required', 'required');
            // Nettoyer le champ caché étudiant si présent (pas pertinent ici)
            const hiddenStudent = document.getElementById('secteur_activite_hidden_student');
            if (hiddenStudent?.parentNode) hiddenStudent.parentNode.removeChild(hiddenStudent);
        }
        
        champs?.forEach(ch => ch.setAttribute('required', 'required'));
    } else {
        // Masquer si aucune donnée existante ET pas "En activité"
        professionDiv.style.display = "none";
        
        if (professionField) {
            professionField.value = (professionField.tagName === 'SELECT') ? '' : '';
            professionField.removeAttribute('required');
        }
        
        if (selectSecteur) {
            selectSecteur.value = '';
            selectSecteur.removeAttribute('disabled');
            selectSecteur.removeAttribute('required');
        }
        
        champs?.forEach(ch => {
            ch.value = '';
            ch.removeAttribute('required');
        });
    }
    
    updateTextarea();
}
        
        // Met à jour le contenu du textarea avec toutes les infos du bloc
        function updateTextarea() {
            console.log('=== updateTextarea() called ===');
			const profField = professionDiv.querySelector('select[name="profession"]');

            const professionLabel = profField
              ? profField.options[profField.selectedIndex]?.text || ""
              : "";

			const data = {
				profession: professionLabel || "",
				secteur_activite: professionDiv.querySelector('select[name="secteur_activite"]')?.value || "",
				nom_employeur: professionDiv.querySelector('input[name="nom_employeur"]')?.value || "",
				cp_employeur: professionDiv.querySelector('input[name="cp_employeur"]')?.value || "",
				ville_employeur: professionDiv.querySelector('input[name="ville_employeur"]')?.value || "",
				pays_employeur: professionDiv.querySelector('select[name="pays_employeur"]')?.value || "",
				num_employeur: professionDiv.querySelector('input[name="num_employeur"]')?.value || "",
				mail_employeur: professionDiv.querySelector('input[name="mail_employeur"]')?.value || ""
			};
            
            console.log('=== PROFESSION DATA ===', data);
            textareaEmployeur.value =
              `Profession : ${data.profession}\n` +
              `Secteur d'activité : ${data.secteur_activite}\n` +
              `Nom employeur/établissement : ${data.nom_employeur}\n` +
              `Code postal : ${data.cp_employeur}\n` +
              `Ville : ${data.ville_employeur}\n` +
              `Pays : ${data.pays_employeur}\n` +
              `Téléphone : ${data.num_employeur}\n` +
              `Email : ${data.mail_employeur}`;
        }
        
        // Écouteur de changement sur la situation
        selectSituation.addEventListener("change", toggleProfession);
        console.log('âÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂ addEventListener on selectSituation added');
        
		// Écoute tous les champs du bloc pour maj le textarea
		champs.forEach(ch => ch.addEventListener("input", updateTextarea));
		// Écoute également les selects pour 'change'
		const selects = professionDiv.querySelectorAll('select');
		selects.forEach(s => s.addEventListener('change', updateTextarea));
        console.log('âÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂ addEventListener on champs added');
        
        // Initialisation au chargement
        toggleProfession();
        console.log('âÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂ toggleProfession() called');
        updateTextarea();
        console.log('âÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂ updateTextarea() called');
        console.log('=== PROFESSION SECTION END ==='); 

        // Calcul automatique de la durée et sélection des radios correspondantes
        function updateDuration() {
            const arrivalInputLocal = document.querySelector('input[name="arrival_date"]');
            const departureInputLocal = document.querySelector('input[name="departure_date"]');
            const radiosLocal = document.querySelectorAll('input[name="duree"]');

            if (!arrivalInputLocal || !departureInputLocal) return;

            const arrival = new Date(arrivalInputLocal.value);
            const departure = new Date(departureInputLocal.value);

            if (isNaN(arrival) || isNaN(departure)) return;

            const diffDays = (departure - arrival) / (1000 * 60 * 60 * 24);

            radiosLocal.forEach(r => r.checked = false);

            if (diffDays < 90) return;

            if (diffDays <= 183) {
                const el = document.querySelector('input[value="entre_3_et_6_mois"]'); if (el) el.checked = true;
            } else if (diffDays <= 365) {
                const el = document.querySelector('input[value="entre_6_mois_et_un_an"]'); if (el) el.checked = true;
            } else {
                const el = document.querySelector('input[value="superieur_a_un_an"]'); if (el) el.checked = true;
            }
        }

        // Attacher les écouteurs de façon sûre
        const arrivalInputEv = document.querySelector('input[name="arrival_date"]');
        const departureInputEv = document.querySelector('input[name="departure_date"]');

        if (arrivalInputEv) {
            arrivalInputEv.addEventListener("change", () => {
                setMinDeparture();
                updateDuration();
            });
        }

        if (departureInputEv) {
            departureInputEv.addEventListener("change", updateDuration);
        }
        
		// Personne acceuillant — safe guards when the block is absent
		const personneDiv = document.getElementById('personne');
		if (personneDiv) {
			const nomInput = personneDiv.querySelector('input[name="nom_accueil"]');
			// Tous les champs concernés sauf le code postal et le nom
			const otherFields = personneDiv.querySelectorAll('input:not([name="nom_accueil"]):not([name="cp_accueil"]), select');

			if (nomInput) {
				nomInput.addEventListener('input', function() {
					const hasName = (nomInput.value || '').trim() !== '';

					otherFields.forEach(field => {
						if (hasName) {
							field.setAttribute('required', 'required');
						} else {
							field.removeAttribute('required');
						}
					});
				});
			}
		}
        
		// Entreprise acceuillant — safe guards when the block is absent
		const entrepriseDiv = document.getElementById('entreprise');
		if (entrepriseDiv) {
			const nomEntrepriseInput = entrepriseDiv.querySelector('input[name="nom_entreprise"]');
			// Tous les champs concernés sauf le code postal et le nom
			const otherEntrepriseFields = entrepriseDiv.querySelectorAll('input:not([name="nom_entreprise"]):not([name="cp_entreprise"]), select');

			if (nomEntrepriseInput) {
				nomEntrepriseInput.addEventListener('input', function() {
					const hasName = (nomEntrepriseInput.value || '').trim() !== '';

					otherEntrepriseFields.forEach(field => {
						if (hasName) {
							field.setAttribute('required', 'required');
						} else {
							field.removeAttribute('required');
						}
					});
				});
			}
		}
        
		// Personne de contact acceuillant — safe guards when the block is absent
		const contactDiv = document.getElementById('contact');
		if (contactDiv) {
			const nomContactInput = contactDiv.querySelector('input[name="nom_contact"]');
			// Tous les champs concernés sauf le code postal et le nom
			const otherContactFields = contactDiv.querySelectorAll('input:not([name="nom_contact"]):not([name="cp_contact"]), select');

			if (nomContactInput) {
				nomContactInput.addEventListener('input', function() {
					const hasName = (nomContactInput.value || '').trim() !== '';

					otherContactFields.forEach(field => {
						if (hasName) {
							field.setAttribute('required', 'required');
						} else {
							field.removeAttribute('required');
						}
					});
				});
			}
		}
        
		// Hotel acceuillant — safe guards when the block is absent
		const hotelDiv = document.getElementById('hotel');
		if (hotelDiv) {
			const nomHotelInput = hotelDiv.querySelector('input[name="nom_hotel"]');
			// Tous les champs concernés sauf le code postal et le nom
			const otherHotelFields = hotelDiv.querySelectorAll('input:not([name="nom_hotel"]):not([name="cp_hotel"]), select');

			if (nomHotelInput) {
				nomHotelInput.addEventListener('input', function() {
					const hasName = (nomHotelInput.value || '').trim() !== '';

					otherHotelFields.forEach(field => {
						if (hasName) {
							field.setAttribute('required', 'required');
						} else {
							field.removeAttribute('required');
						}
					});
				});
			}
		}
        
        // Récupération des champs à auto-remplir
        const textareaHotel = document.querySelector('textarea[name="hotel"]');
        const textareaAdresseInviteur = document.querySelector('textarea[name="adresse_inviteur"]');
        const inputPhoneInviteur = document.querySelector('input[name="phone_adresse_inviteur"]');
    
        // Fonction pour mettre à jour les champs automatiquement
        function updateFields() {
            // Valeurs personne d'accueil
			const nomAccueil = document.querySelector('input[name="nom_accueil"]')?.value?.trim() || "";
			const prenomAccueil = document.querySelector('input[name="prenom_accueil"]')?.value?.trim() || "";
			const adresseAccueil = document.querySelector('input[name="adresse_accueil"]')?.value?.trim() || "";
			const cpAccueil = document.querySelector('input[name="cp_accueil"]')?.value?.trim() || "";
			const villeAccueil = document.querySelector('input[name="ville_accueil"]')?.value?.trim() || "";
			const paysAccueil = document.querySelector('select[name="pays_accueil"]')?.value?.trim() || "";
			const phoneAccueil = document.querySelector('input[name="num_accueil"]')?.value?.trim() || "";
    
            // Valeurs hôtel
			const nomHotel = document.querySelector('input[name="nom_hotel"]')?.value?.trim() || "";
			const adresseHotel = document.querySelector('input[name="adresse_hotel"]')?.value?.trim() || "";
			const cpHotel = document.querySelector('input[name="cp_hotel"]')?.value?.trim() || "";
			const villeHotel = document.querySelector('input[name="ville_hotel"]')?.value?.trim() || "";
			const paysHotel = document.querySelector('select[name="pays_hotel"]')?.value?.trim() || "";
			const phoneHotel = document.querySelector('input[name="num_hotel"]')?.value?.trim() || "";
    
            // === 1. Remplir le textarea "hotel" ===
            let hotelText = "";
            if (nomAccueil || prenomAccueil) {
                hotelText += `${prenomAccueil} ${nomAccueil}`;
            }
            if (nomHotel) {
                if (hotelText) hotelText += " / ";
                hotelText += nomHotel;
            }
			if (textareaHotel) textareaHotel.value = hotelText;
    
            // === 2. Remplir "adresse_inviteur" ===
            let adresseText = "";
            if (adresseAccueil) {
                adresseText += `${adresseAccueil}`;
                if (cpAccueil || villeAccueil || paysAccueil) {
                    adresseText += ` (${[cpAccueil, villeAccueil, paysAccueil].filter(Boolean).join(", ")})`;
                }
            }
            if (adresseHotel) {
                if (adresseText) adresseText += " / ";
                adresseText += `${adresseHotel}`;
                if (cpHotel || villeHotel || paysHotel) {
                    adresseText += ` (${[cpHotel, villeHotel, paysHotel].filter(Boolean).join(", ")})`;
                }
            }
			if (textareaAdresseInviteur) textareaAdresseInviteur.value = adresseText;
    
            // === 3. Remplir "phone_adresse_inviteur" ===
            let phoneText = [phoneAccueil, phoneHotel].filter(Boolean).join(" / ");
			if (inputPhoneInviteur) inputPhoneInviteur.value = phoneText;
        }
    
        // Écouteur sur tous les champs concernés - seulement si les éléments cibles existent
        if (textareaHotel || textareaAdresseInviteur || inputPhoneInviteur) {
            const inputs = document.querySelectorAll(
                'input[name^="nom_"], input[name^="prenom_"], input[name^="adresse_"], input[name^="cp_"], input[name^="ville_"], select[name^="pays_"], input[name^="num_"], input[name="nom_hotel"], input[name="adresse_hotel"], input[name="ville_hotel"], select[name="pays_hotel"], input[name="num_hotel"]'
            );
            inputs.forEach((el) => el.addEventListener("input", updateFields));
        
            // Initialisation à l'ouverture
            updateFields();
        }
        
        // Entreprise
        const textareaHote = document.querySelector('textarea[name="hote"]');

        function updateEntreprise() {
			const nomEntreprise = document.querySelector('input[name="nom_entreprise"]')?.value?.trim() || "";
			const adresseEntreprise = document.querySelector('input[name="adresse_entreprise"]')?.value?.trim() || "";
			const cpEntreprise = document.querySelector('input[name="cp_entreprise"]')?.value?.trim() || "";
			const villeEntreprise = document.querySelector('input[name="ville_entreprise"]')?.value?.trim() || "";
			const paysEntreprise = document.querySelector('select[name="pays_entreprise"]')?.value?.trim() || "";
			const phoneEntreprise = document.querySelector('input[name="phone_hote"]')?.value?.trim() || "";
			const mailEntreprise = document.querySelector('input[name="mail_entreprise"]')?.value?.trim() || "";
    
            let texteFinal = "";
    
            if (nomEntreprise) texteFinal += nomEntreprise;
    
            if (adresseEntreprise) {
                if (texteFinal) texteFinal += " - ";
                texteFinal += adresseEntreprise;
            }
    
            const localisation = [cpEntreprise, villeEntreprise, paysEntreprise].filter(Boolean).join(", ");
            if (localisation) texteFinal += ` (${localisation})`;
    
            if (phoneEntreprise) texteFinal += ` - Tél : ${phoneEntreprise}`;
            if (mailEntreprise) texteFinal += ` - Email : ${mailEntreprise}`;
    
			if (textareaHote) textareaHote.value = texteFinal;
        }
    
        // Seulement si le textarea existe
        if (textareaHote) {
            const champsHote = document.querySelectorAll(
                'input[name="nom_entreprise"], input[name="adresse_entreprise"], input[name="cp_entreprise"], input[name="ville_entreprise"], select[name="pays_entreprise"], input[name="phone_hote"], input[name="mail_entreprise"]'
            );
        
            champsHote.forEach((input) => input.addEventListener("input", updateEntreprise));
        
            // Initialisation automatique au chargement
            updateEntreprise();
        }
        
        // Personne de contact
        const textareaContact = document.querySelector('textarea[name="personne_de_contact"]');
        
        function updateContact() {
			const nomContact = document.querySelector('input[name="nom_contact"]')?.value?.trim() || "";
			const prenomContact = document.querySelector('input[name="prenom_contact"]')?.value?.trim() || "";
			const adresseContact = document.querySelector('input[name="adresse_contact"]')?.value?.trim() || "";
			const cpContact = document.querySelector('input[name="cp_contact"]')?.value?.trim() || "";
			const villeContact = document.querySelector('input[name="ville_contact"]')?.value?.trim() || "";
			const paysContact = document.querySelector('select[name="pays_contact"]')?.value?.trim() || "";
			const telContact = document.querySelector('input[name="num_contact"]')?.value?.trim() || "";
			const mailContact = document.querySelector('input[name="mail_contact"]')?.value?.trim() || "";
        
            let texteFinal = "";
        
            if (nomContact) texteFinal += nomContact;
            if (prenomContact) {
                if (texteFinal) texteFinal += " ";
                texteFinal += prenomContact;
            }
            if (adresseContact) {
                if (texteFinal) texteFinal += " - ";
                texteFinal += adresseContact;
            }
        
            const localisation = [cpContact, villeContact, paysContact].filter(Boolean).join(", ");
            if (localisation) texteFinal += ` (${localisation})`;
        
            if (telContact) texteFinal += ` - Tél : ${telContact}`;
            if (mailContact) texteFinal += ` - Email : ${mailContact}`;
        
			if (textareaContact) textareaContact.value = texteFinal;
        }
        
        // Seulement si le textarea existe
        if (textareaContact) {
            const champsContact = document.querySelectorAll(
                'input[name="nom_contact"], input[name="prenom_contact"], input[name="adresse_contact"], input[name="cp_contact"], input[name="ville_contact"], select[name="pays_contact"], input[name="num_contact"], input[name="mail_contact"]'
            );
        
            champsContact.forEach((input) => input.addEventListener("input", updateContact));
        
            // Initialisation automatique au chargement
            updateContact();
        }
        
        // Personne qui le remplit
        const inputRemplisseur = document.querySelector('input[name="remplisseur"]');
        const textareaRemplisseur = document.querySelector('textarea[name="adresse_remplisseur"]');
        
        function updateRemplisseur() {
			const nomRemplisseur = document.querySelector('input[name="nom_remplisseur"]')?.value?.trim() || "";
			const prenomRemplisseur = document.querySelector('input[name="prenom_remplisseur"]')?.value?.trim() || "";
			const adresseRemplisseur = document.querySelector('input[name="adres_remplisseur"]')?.value?.trim() || "";
			const cpRemplisseur = document.querySelector('input[name="cp_remplisseur"]')?.value?.trim() || "";
			const villeRemplisseur = document.querySelector('input[name="ville_remplisseur"]')?.value?.trim() || "";
			const paysRemplisseur = document.querySelector('select[name="pays_remplisseur"]')?.value?.trim() || "";
			const telRemplisseur = document.querySelector('input[name="num_remplisseur"]')?.value?.trim() || "";
			const mailRemplisseur = document.querySelector('input[name="mail_remplisseur"]')?.value?.trim() || "";
        
            let nomFinal = "";
            let adresseFinal = "";
        
            if (nomRemplisseur) nomFinal += nomRemplisseur;
            if (prenomRemplisseur) {
                if (nomFinal) nomFinal += " ";
                nomFinal += prenomRemplisseur;
            }
            if (adresseRemplisseur) adresseFinal += adresseRemplisseur;
        
            const localisation = [cpRemplisseur, villeRemplisseur, paysRemplisseur].filter(Boolean).join(", ");
            if (localisation) adresseFinal += ` (${localisation})`;
        
            if (telRemplisseur) adresseFinal += ` - Tél : ${telRemplisseur}`;
            if (mailRemplisseur) adresseFinal += ` - Email : ${mailRemplisseur}`;
        
			if (inputRemplisseur) inputRemplisseur.value = nomFinal;
			if (textareaRemplisseur) textareaRemplisseur.value = adresseFinal;
        }
        
        // Seulement si les éléments cibles existent
        if (inputRemplisseur || textareaRemplisseur) {
            const champsRemplisseur = document.querySelectorAll(
                'input[name="nom_remplisseur"], input[name="prenom_remplisseur"], input[name="adres_remplisseur"], input[name="cp_remplisseur"], input[name="ville_remplisseur"], select[name="pays_remplisseur"], input[name="num_remplisseur"], input[name="mail_remplisseur"]'
            );
        
            champsRemplisseur.forEach((input) => input.addEventListener("input", updateRemplisseur));
        
            // Initialisation automatique au chargement
            updateRemplisseur();
        }
        
        const adresseInput = document.querySelector('input[name="adresse_adresse"]');
        const cpInput = document.querySelector('input[name="cp_adresse"]');
        const villeInput = document.querySelector('input[name="ville_adresse"]');
        const paysSelect = document.querySelector('select[name="pays_adresse"]');
        const finalInput = document.querySelector('input[name="adresse"]');
    
        // Vérifier que tous les éléments existent avant de les utiliser
        if (adresseInput && cpInput && villeInput && paysSelect && finalInput) {
            function updateFinalAddress() {
                const adresse = adresseInput.value.trim();
                const cp = cpInput.value.trim();
                const ville = villeInput.value.trim();
                const pays = paysSelect.value.trim();
        
                // Concatène proprement
                finalInput.value = `${adresse}, ${cp} ${ville}, ${pays}`;
            }
        
            // Mise à jour à chaque changement
            adresseInput.addEventListener("input", updateFinalAddress);
            cpInput.addEventListener("input", updateFinalAddress);
            villeInput.addEventListener("input", updateFinalAddress);
            paysSelect.addEventListener("change", updateFinalAddress);
        
            // Mise à jour initiale si valeurs déjà remplies
            updateFinalAddress();
        }
      
      console.log('Code avant la section financement long séjour - VISA_TYPE:', VISA_TYPE);
      
      // Vérifie que l'on est bien sur le formulaire de long séjour *côté navigateur*
      if (VISA_TYPE === "court_sejour" || VISA_TYPE === "long_sejour") {
        console.log('Entrée dans la section financement - VISA_TYPE:', VISA_TYPE);
        // Utilise VISA_TYPE (JS) au lieu de $visa_type (PHP)
      

        // 1. Récupérer tous les éléments critiques
        // Utiliser querySelectorAll pour trouver les éléments même s'ils sont dans un div caché
        const financementRadios = document.querySelectorAll(
          'input[name="financement"]'
        );
        // Pour prise_en_charge, chercher même dans les divs cachés
        const priseEnChargeRadios = document.querySelectorAll(
          'input[name="prise_en_charge"]'
        );
        // Pour le textarea, utiliser getElementById d'abord, puis querySelector
        const infoPriseEnChargeTextarea = document.getElementById('info_prise_en_charge') || 
          document.querySelector('textarea[name="info_prise_en_charge"]');

		const buttons = [
            { btn: "go-personne", targets: ["personne"] },
            { btn: "go-hotel", targets: ["hotel"] },
            { btn: "go-entreprise", targets: ["entreprise", "contact"] }
        ];
    
        const textarea = document.getElementById("info_employeur");
    
        // Fonction qui lit tous les inputs visibles et remplit le textarea
        function updateTextarea() {
            let finalData = [];
    
            buttons.forEach(group => {
                group.targets.forEach(targetId => {
                    const targetDiv = document.getElementById(targetId);
    
                    // Seulement les div visibles
                    if (targetDiv && targetDiv.style.display === "block") {
    
                        // Inputs text
                        const inputs = targetDiv.querySelectorAll("input[type='text']");
                        inputs.forEach(input => {
                            if (input.value.trim() !== "") {
                                finalData.push(input.value.trim());
                            }
                        });
    
                        // Select (pays)
                        const selects = targetDiv.querySelectorAll("select");
                        selects.forEach(select => {
                            if (select.value.trim() !== "") {
                                finalData.push(select.value.trim());
                            }
                        });
                    }
                });
            });
    
            textarea.value = finalData.join(", ");
        }
    
        // Masquer sections au début
        buttons.forEach(item => {
            item.targets.forEach(id => {
                const div = document.getElementById(id);
                if (div) div.style.display = "none";
            });
        });
    
        // Gestion clics boutons
        buttons.forEach(item => {
            const button = document.getElementById(item.btn);
    
            button.addEventListener("click", function () {
    
                // Reset affichage + style
                buttons.forEach(hideItem => {
                    hideItem.targets.forEach(id => {
                        const div = document.getElementById(id);
                        if (div) div.style.display = "none";
                    });
                    const hideBtn = document.getElementById(hideItem.btn);
                    hideBtn.style.backgroundColor = "";
                    hideBtn.style.color = "";
                });
    
                // Afficher les bonnes divs
                item.targets.forEach(showId => {
                    const div = document.getElementById(showId);
                    if (div) div.style.display = "block";
                });
    
                // Style actif
                button.style.backgroundColor = "#1d6cff";
                button.style.color = "white";
    
                updateTextarea();
            });
        });
    
        // Mise à jour auto lors de la saisie dans les inputs/selects
        document.querySelectorAll("#personne input, #personne select, #hotel input, #hotel select, #entreprise input, #entreprise select, #contact input, #contact select")
            .forEach(field => {
                field.addEventListener("input", updateTextarea);
            });

        // Vérification de sécurité - Les éléments peuvent être dans un div caché, c'est normal
        console.log('VISA_TYPE:', VISA_TYPE);
        console.log('financementRadios trouvés:', financementRadios.length);
        console.log('priseEnChargeRadios trouvés:', priseEnChargeRadios.length);
        console.log('infoPriseEnChargeTextarea trouvé:', !!infoPriseEnChargeTextarea);
        
        if (financementRadios.length === 0) {
          console.error(
            "Éléments financement manquants pour la logique de financement - Annulation."
          );
          // Ne pas exécuter la suite si les éléments financement sont absents
        } else {
          // Pour long séjour, les éléments prise_en_charge peuvent être dans un div caché
          // On continue même s'ils ne sont pas trouvés immédiatement
          if (VISA_TYPE === "long_sejour" && (priseEnChargeRadios.length === 0 || !infoPriseEnChargeTextarea)) {
            console.warn(
              "Éléments prise_en_charge non trouvés (peut être dans un div caché) - Continuation quand même."
            );
          }

          // 2. Fonction pour générer les infos du garant à partir des champs existants
					function genererInfosGarant() {
						let infos = [];

						// --- Personne qui accueille (30a)
						const nomAccueil = document.querySelector('input[name="nom_accueil"]')?.value?.trim() || "";
						const prenomAccueil = document.querySelector('input[name="prenom_accueil"]')?.value?.trim() || "";
						const adresseAccueil = document.querySelector('input[name="adresse_accueil"]')?.value?.trim() || "";
						const cpAccueil = document.querySelector('input[name="cp_accueil"]')?.value?.trim() || "";
						const villeAccueil = document.querySelector('input[name="ville_accueil"]')?.value?.trim() || "";
						const paysAccueil = document.querySelector('select[name="pays_accueil"]')?.value?.trim() || "";
						const phoneAccueil = document.querySelector('input[name="num_accueil"]')?.value?.trim() || "";
						const emailAccueil = document.querySelector('input[name="mail_accueil"]')?.value?.trim() || "";

						if (nomAccueil || prenomAccueil) {
							let personne = `${prenomAccueil} ${nomAccueil}`.trim();
							const adressePart = [adresseAccueil, cpAccueil, villeAccueil, paysAccueil].filter(Boolean).join(', ');
							if (adressePart) personne += ` - ${adressePart}`;
							if (phoneAccueil) personne += ` - Tél: ${phoneAccueil}`;
							if (emailAccueil) personne += ` - Email: ${emailAccueil}`;
							infos.push(personne);
						}

						// --- Entreprise (31)
						const nomEntreprise = document.querySelector('input[name="nom_entreprise"]')?.value?.trim() || "";
						const adresseEntreprise = document.querySelector('input[name="adresse_entreprise"]')?.value?.trim() || "";
						const cpEntreprise = document.querySelector('input[name="cp_entreprise"]')?.value?.trim() || "";
						const villeEntreprise = document.querySelector('input[name="ville_entreprise"]')?.value?.trim() || "";
						const paysEntreprise = document.querySelector('select[name="pays_entreprise"]')?.value?.trim() || "";
						const phoneEntreprise = document.querySelector('input[name="phone_hote"]')?.value?.trim() || "";
						const emailEntreprise = document.querySelector('input[name="mail_entreprise"]')?.value?.trim() || "";

						if (nomEntreprise) {
							let entreprise = nomEntreprise;
							const adressePart = [adresseEntreprise, cpEntreprise, villeEntreprise, paysEntreprise].filter(Boolean).join(', ');
							if (adressePart) entreprise += ` - ${adressePart}`;
							if (phoneEntreprise) entreprise += ` - Tél: ${phoneEntreprise}`;
							if (emailEntreprise) entreprise += ` - Email: ${emailEntreprise}`;
							infos.push(entreprise);
						}

						// --- Contact (32)
						const nomContact = document.querySelector('input[name="nom_contact"]')?.value?.trim() || "";
						const prenomContact = document.querySelector('input[name="prenom_contact"]')?.value?.trim() || "";
						const adresseContact = document.querySelector('input[name="adresse_contact"]')?.value?.trim() || "";
						const cpContact = document.querySelector('input[name="cp_contact"]')?.value?.trim() || "";
						const villeContact = document.querySelector('input[name="ville_contact"]')?.value?.trim() || "";
						const paysContact = document.querySelector('select[name="pays_contact"]')?.value?.trim() || "";
						const phoneContact = document.querySelector('input[name="num_contact"]')?.value?.trim() || "";
						const emailContact = document.querySelector('input[name="mail_contact"]')?.value?.trim() || "";

						if (nomContact || prenomContact) {
							let contact = `${prenomContact} ${nomContact}`.trim();
							const adressePart = [adresseContact, cpContact, villeContact, paysContact].filter(Boolean).join(', ');
							if (adressePart) contact += ` - ${adressePart}`;
							if (phoneContact) contact += ` - Tél: ${phoneContact}`;
							if (emailContact) contact += ` - Email: ${emailContact}`;
							infos.push(contact);
						}

						// --- Précisions sur le garant (radio)
						const financementGarantVal = document.querySelector('input[name="financement_garant"]:checked')?.value || '';
						let precisionsGarant = '';
						if (financementGarantVal === 'garant_vise') {
							precisionsGarant = 'Visé dans la case 30 ou 31';
						} else if (financementGarantVal === 'garant_autre') {
							const autreDetail = document.querySelector('input[name="garant_autre_detail"]')?.value?.trim() || '';
							precisionsGarant = autreDetail ? `Autre: ${autreDetail}` : 'Autre';
						}

						// --- Moyens de subsistance fournis par le garant (checkboxes)
						const moyensChecked = Array.from(document.querySelectorAll('input[name="garant_financement_moyen[]"]:checked')).map(cb => cb.value);
						const moyensLabels = [];
						moyensChecked.forEach(v => {
							if (v === 'liquide') moyensLabels.push('Argent liquide');
							else if (v === 'finance') moyensLabels.push('Tous frais financés');
							else if (v === 'hebergement') moyensLabels.push('Hébergement fourni');
							else if (v === 'transport') moyensLabels.push('Transport prépayé');
							else if (v === 'autre') {
								const autreMoyen = document.querySelector('input[name="garant_financement_moyen_autre"]')?.value?.trim() || '';
								moyensLabels.push(autreMoyen ? `Autre: ${autreMoyen}` : 'Autre');
							} else {
								moyensLabels.push(v);
							}
						});

						// Construire le résultat final
						const base = infos.length > 0 ? infos.join('\n') : '';
						const parts = [];
						if (precisionsGarant) parts.push(precisionsGarant);
						if (moyensLabels.length) parts.push(moyensLabels.join(', '));

						const appended = parts.join(', '); // éléments séparés par des virgules
						const resultat = base ? (appended ? `${base}\n${appended}` : base) : (appended || 'Veuillez renseigner les informations du garant dans les sections 30, 31 ou 32.');

						return resultat;
					}

          // 3. Fonction principale pour gérer le changement sur les radios 'financement'
          function handleFinancementChange() {
            const financementChecked = document.querySelector(
              'input[name="financement"]:checked'
            );

            if (!financementChecked) {
             
              return;
            }

            const financementValue = financementChecked.value;

            // Vérifier que les éléments existent avant de les utiliser
            if (priseEnChargeRadios.length === 0 || !infoPriseEnChargeTextarea) {
              console.warn("Éléments prise_en_charge non disponibles - fonctionnalité limitée");
              return;
            }

            if (financementValue === "demandeur") {
              // Cocher "Non" pour prise_en_charge
              priseEnChargeRadios.forEach((radio) => {
                if (radio.value === "non") {
                  radio.checked = true;
                }
              });
              // Vider et désactiver le textarea info_prise_en_charge
              infoPriseEnChargeTextarea.value = "";
              infoPriseEnChargeTextarea.disabled = true;
            } else if (financementValue === "garant") {
              // Cocher "Oui" pour prise_en_charge
              priseEnChargeRadios.forEach((radio) => {
                if (radio.value === "oui") {
                  radio.checked = true;
                }
              });
              // Activer le textarea info_prise_en_charge
              infoPriseEnChargeTextarea.disabled = false;
              // Générer et remplir le textarea avec les infos du garant
              const infosGarant = genererInfosGarant();
              infoPriseEnChargeTextarea.value = infosGarant;
            }
          }

          // 4. Fonction pour mettre à jour le textarea en temps réel *si* le garant est sélectionné
          function mettreAJourTextarea() {
            // Vérifier que les éléments existent avant de les utiliser
            if (!infoPriseEnChargeTextarea) {
              return;
            }
            
            // Ne mettre à jour que si "Par un garant" est coché
            const financementGarantChecked =
              document.querySelector('input[name="financement"]:checked')
                ?.value === "garant";
            if (
              financementGarantChecked &&
              !infoPriseEnChargeTextarea.disabled
            ) {
              const infos = genererInfosGarant();
              infoPriseEnChargeTextarea.value = infos;
            } else {
            }
          }

          // 5. Attacher les événements
          financementRadios.forEach((radio) => {
            radio.addEventListener("change", handleFinancementChange);
          });
          
          // Attacher les événements pour prise_en_charge seulement s'ils existent
          if (priseEnChargeRadios.length > 0) {
            priseEnChargeRadios.forEach((radio) => {
              radio.addEventListener("change", mettreAJourTextarea);
            });
          }

          // Liste des noms de champs à surveiller pour la mise à jour en continu
          const champsAMettreAJour = [
            "nom_accueil",
            "prenom_accueil",
            "adresse_accueil",
            "cp_accueil",
            "ville_accueil",
            "pays_accueil",
            "num_accueil",
            "mail_accueil",
            "nom_entreprise",
            "adresse_entreprise",
            "cp_entreprise",
            "ville_entreprise",
            "pays_entreprise",
            "phone_hote",
            "mail_entreprise",
            "nom_contact",
            "prenom_contact",
            "adresse_contact",
            "cp_contact",
            "ville_contact",
            "pays_contact",
            "num_contact",
							"mail_contact",
							// Champs liés au garant pour mise à jour automatique
							"financement_garant",
							"garant_autre_detail",
							"garant_financement_moyen[]",
							"garant_financement_moyen_autre",
          ];

					champsAMettreAJour.forEach((nomChamp) => {
						// On cible potentiellement plusieurs éléments (checkboxes/inputs avec []), on utilise querySelectorAll
						const champsNodeList = document.querySelectorAll(
							`input[name="${nomChamp}"], select[name="${nomChamp}"]`
						);
						if (champsNodeList && champsNodeList.length) {
							champsNodeList.forEach((champ) => {
								champ.addEventListener("input", mettreAJourTextarea);
								champ.addEventListener("change", mettreAJourTextarea); // Utile pour les selects/checkboxes
							});
						} else {
							// Peut ne pas exister si la section n'est pas affichée, ce n'est pas une erreur critique ici
						
						}
					});
          // 6. Exécuter la logique une fois au chargement pour restaurer l'état si nécessaire
          handleFinancementChange(); // Cela va cocher la radio prise_en_charge correspondante et gérer le textarea
        }
      } else {
        console.log('VISA_TYPE ne correspond pas à court_sejour ou long_sejour:', VISA_TYPE);
      }
      // === Fin Logique Financement pour le Long Séjour ===
      
      console.log('Fin du script principal - VISA_TYPE:', VISA_TYPE);
		} catch (error) {
			console.error('Erreur dans le script principal:', error);
			console.error('Stack:', error.stack);
		}
	});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Bloc moyen_sub
    const moyenSub = document.getElementById('moyen_sub');
    const radios = document.querySelectorAll('input[name="financement_garant"]');

    if (!moyenSub || radios.length === 0) return;

    // Fonction pour gérer les attributs required dans moyen_sub
    function toggleRequiredInMoyenSub(isVisible) {
        if (!moyenSub) return;
        const requiredFields = moyenSub.querySelectorAll('input[required], select[required], textarea[required]');
        requiredFields.forEach(field => {
            if (isVisible) {
                field.setAttribute('required', 'required');
            } else {
                field.removeAttribute('required');
            }
        });
    }

    // Cacher par défaut
    moyenSub.style.display = 'none';
    toggleRequiredInMoyenSub(false);

    // Champ texte "Autre"
    const autreCheckbox = document.querySelector('input[name="garant_financement_moyen[]"][value="autre"]');
    const autreInput = document.querySelector('input[name="garant_financement_moyen_autre"]');

    if (autreCheckbox && autreInput) {
        // Cacher le champ texte au départ
        autreInput.style.display = 'none';
        autreInput.removeAttribute('required');

        // Toggle visibilité et obligation selon checkbox
        autreCheckbox.addEventListener('change', function () {
            if (this.checked) {
                autreInput.style.display = 'inline-block'; // ou 'block' si tu veux sur une ligne séparée
                // Ne remettre required que si moyen_sub est visible
                if (moyenSub.style.display !== 'none') {
                    autreInput.setAttribute('required', 'required');
                }
            } else {
                autreInput.style.display = 'none';
                autreInput.removeAttribute('required');
                autreInput.value = ''; // reset si décoché
            }
        });
    }

    // Fonction pour afficher moyen_sub selon radio coché
    function toggleMoyenSub() {
        const isChecked = document.querySelector('input[name="financement_garant"]:checked');
        const shouldShow = !!isChecked;
        moyenSub.style.display = shouldShow ? 'block' : 'none';
        toggleRequiredInMoyenSub(shouldShow);
    }

    // Écoute le changement des radios
    radios.forEach(radio => {
        radio.addEventListener('change', toggleMoyenSub);
    });

    // Cas où un radio est déjà coché (ex: formulaire pré-rempli)
    toggleMoyenSub();
});

// ===== RESTRICTION DATE DE DÉLIVRANCE DU PASSEPORT =====
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    if (!form) return;

    // ===== VALIDATION GARANT SEULEMENT POUR MINEURS =====
    const garantInput = document.querySelector('input[name="garant_autre_detail"]');
    const birthDateInput = document.querySelector('input[name="ia_date_naiss"]');
    
    function calculateAge(birthDate) {
        if (!birthDate) return null;
        const today = new Date();
        const birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        return age;
    }
    
    function updateGarantRequired() {
        if (!garantInput || !birthDateInput) return;
        
        const age = calculateAge(birthDateInput.value);
        const isMinor = age !== null && age < 18;
        const garantRadioChecked = document.querySelector('input[name="financement_garant"]:checked');
        const isGarantOther = garantRadioChecked && garantRadioChecked.value === 'garant_autre';
        
        // Rendre required seulement si mineur ET section garant visible ET radio "autre" coché
        if (isMinor && isGarantOther) {
            garantInput.setAttribute('required', '');
        } else {
            garantInput.removeAttribute('required');
            garantInput.value = garantInput.value; // Trigger validation visuelle
        }
    }
    
    // Écoute les changements de date de naissance et des radios
    if (birthDateInput) {
        birthDateInput.addEventListener('change', updateGarantRequired);
    }
    
    const garantRadios = document.querySelectorAll('input[name="financement_garant"]');
    garantRadios.forEach(radio => {
        radio.addEventListener('change', updateGarantRequired);
    });
    
    // Validation à la soumission du formulaire
    form.addEventListener('submit', function(e) {
        const age = calculateAge(birthDateInput?.value);
        const isMinor = age !== null && age < 18;
        const garantRadioChecked = document.querySelector('input[name="financement_garant"]:checked');
        
        if (isMinor && garantRadioChecked && garantRadioChecked.value === 'garant_autre') {
            if (!garantInput.value.trim()) {
                e.preventDefault();
                alert('Le nom du garant est obligatoire pour les demandeurs mineurs.');
                garantInput.focus();
                return false;
            }
        }
    });

    // Fonctionne sur TOUS les navigateurs
    const dateDelivranceInputs = document.querySelectorAll('input[name="date_delivrance"]');
    
    dateDelivranceInputs.forEach(function(dateInput) {
        // Définir le max en JS pour compatibilité totale
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('max', today);
        
        // Validation en temps réel (event input + change)
        function validateDate() {
            const inputDate = new Date(dateInput.value + 'T00:00:00');
            const todayDate = new Date(today + 'T00:00:00');
            const errorMsg = dateInput.parentElement.querySelector('[id^="error-date-delivrance"]') || 
                           document.getElementById('error-date-delivrance');
            
            if (dateInput.value && inputDate > todayDate) {
                dateInput.style.borderColor = '#ff0000';
                dateInput.style.backgroundColor = '#fff3cd';
                if (errorMsg) errorMsg.style.display = 'block';
            } else {
                dateInput.style.borderColor = '';
                dateInput.style.backgroundColor = '';
                if (errorMsg) errorMsg.style.display = 'none';
            }
        }
        
        dateInput.addEventListener('change', validateDate);
        dateInput.addEventListener('input', validateDate);
        dateInput.addEventListener('blur', validateDate);
    });
    
    // Validation à la soumission du formulaire
    form.addEventListener('submit', function(e) {
        const dateDelivrance = form.querySelector('input[name="date_delivrance"]');
        if (dateDelivrance && dateDelivrance.value) {
            const today = new Date().toISOString().split('T')[0];
            const inputDate = new Date(dateDelivrance.value + 'T00:00:00');
            const todayDate = new Date(today + 'T00:00:00');
            
            if (inputDate > todayDate) {
                e.preventDefault();
                alert("La date de délivrance du passeport doit être antérieure ou égale à aujourd'hui.");
                dateDelivrance.focus();
                return false;
            }
        }
    });
});
</script>


<?php if ($should_redirect): ?>
    <script>
        setTimeout(function () {
            // window.location.href = "<?php // echo esc_url(home_url("/justificatif-supplementaire/?request_id={$request_id}")); ?>";
						window.location.href = "<?php echo esc_url(home_url("/mandat-visa/?request_id={$request_id}")); ?>";
        }, 300);
    </script>
<?php endif; ?>