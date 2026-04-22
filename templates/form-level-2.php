<?php
// templates/form-level-2.php

defined('ABSPATH') || exit;

// Vérifie que l'utilisateur vient du lien d'email
if (!isset($_GET['confirmed']) || $_GET['confirmed'] !== '1') {
    wp_die('Accès refusé. Veuillez utiliser le lien envoyé par email.');
}
$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

$besoin = get_post_meta($request_id, 'visa_besoin', true);

if ($besoin == "non") {
    echo '<div class="visa-already-processed" style="padding:1.2em;border:1px solid #e0e0e0;background:#fffdf6;max-width:720px;">';
	echo '<h3 style="margin-top:0;color:#333;">Aucun visa requis pour votre voyage dans l’espace Schengen</h3>';
	echo '<p>Vous pouvez entrer dans l’espace Schengen sans demander de visa. Assurez-vous simplement de voyager avec un passeport valide et de respecter la durée maximale autorisée de séjour selon votre nationalité.</p>';
	echo '<p><a class="button" href="' . esc_url(home_url('/')) . '">Retour</a></p>';
	echo '</div>';
	return;
}

$confirmed = get_post_meta($request_id, 'visa_confirmed', true);
if ($confirmed) {
	echo '<div class="visa-already-processed" style="padding:1.2em;border:1px solid #e0e0e0;background:#fffdf6;max-width:720px;">';
	echo '<h3 style="margin-top:0;color:#333;">Demande déjà traitée</h3>';
	echo '<p>Cette demande a déjà été traitée et le paiement a été confirmé. Pour soumettre une nouvelle demande, merci de créer un nouveau formulaire avec un identifiant différent.</p>';
	echo '<p><a class="button" href="' . esc_url(home_url('/demande-de-visa/')) . '">Créer une nouvelle demande</a></p>';
	echo '</div>';
	return;
}

if (!$request_id || get_post_type($request_id) !== 'visa_request') {
    echo '<p>Identifiant de demande non valide.</p>';
    return;
}

$should_redirect = false;

// Charger les valeurs déjà enregistrées
$saved_type     = get_post_meta($request_id, 'visa_type', true);
$saved_depot    = get_post_meta($request_id, 'visa_depot', true);
$saved_wilaya   = get_post_meta($request_id, 'visa_wilaya', true);
$saved_ville    = get_post_meta($request_id, 'visa_ville', true);

// Charger la Wilaya sauvegardée à level-1
$wilaya_level1 = get_post_meta($request_id, 'visa_wilaya_level1', true);


// Reconstruire la valeur complète du select "depot_ville"
$saved_depot_full = '';
if ($saved_depot || $saved_wilaya || $saved_ville) {
    $saved_depot_full = implode(' - ', array_filter([$saved_depot, $saved_wilaya, $saved_ville]));
} else if ($wilaya_level1) {
    // Si aucune sélection à level-2, utiliser la Wilaya de level-1
    $wilayas = [
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
    ];
    
    // Mapping inline direct pour éviter les problèmes de classe
    $wilaya_code_match = [];
    if (preg_match('/^(\d{2})/', $wilaya_level1, $wilaya_code_match)) {
        $wilaya_code = $wilaya_code_match[1];
        
        $depot_mapping = [
            '01' => 'Oran', '02' => 'Oran', '03' => 'Alger', '04' => 'Constantine', '05' => 'Constantine',
            '06' => 'Alger', '07' => 'Constantine', '08' => 'Oran', '09' => 'Alger', '10' => 'Alger',
            '11' => 'Alger', '12' => 'Annaba', '13' => 'Oran', '14' => 'Oran', '15' => 'Alger',
            '16' => 'Alger', '17' => 'Alger', '18' => 'Constantine', '19' => 'Constantine', '20' => 'Oran',
            '21' => 'Annaba', '22' => 'Oran', '23' => 'Annaba', '24' => 'Constantine', '25' => 'Constantine',
            '26' => 'Alger', '27' => 'Oran', '28' => 'Alger', '29' => 'Oran', '30' => 'Alger',
            '31' => 'Oran', '32' => 'Oran', '33' => 'Alger', '34' => 'Alger', '35' => 'Alger',
            '36' => 'Annaba', '37' => 'Oran', '38' => 'Oran', '39' => 'Alger', '40' => 'Constantine',
            '41' => 'Annaba', '42' => 'Alger', '43' => 'Constantine', '44' => 'Alger', '45' => 'Oran',
            '46' => 'Oran', '47' => 'Alger', '48' => 'Oran', '49' => 'Oran', '50' => 'Oran',
            '51' => 'Constantine', '52' => 'Oran', '53' => 'Alger', '54' => 'Alger', '55' => 'Alger',
            '56' => 'Alger', '57' => 'Alger', '58' => 'Alger', '59' => 'Alger', '60' => 'Oran',
            '61' => 'Oran', '62' => 'Constantine', '63' => 'Constantine', '64' => 'Alger', '65' => 'Annaba',
            '66' => 'Alger', '67' => 'Alger', '68' => 'Alger', '69' => 'Alger',
        ];
        
        $depot = $depot_mapping[$wilaya_code] ?? '';
        
        if ($depot) {
            // Extraire le nom de la wilaya
            $name_match = [];
            if (preg_match('/^(\d{2})\s*-\s*(.+)$/', $wilaya_level1, $name_match)) {
                $wilaya_name = $name_match[2];
                $saved_depot_full = "{$depot} - {$wilaya_code} - {$wilaya_name}";
            }
        }
    }
}

if ( isset($_POST['visa_level2_submit']) ) {
    // 1) sanitize des inputs
    $type      = sanitize_text_field( $_POST['visa_type']      ?? '' );
    $depot_raw = sanitize_text_field( $_POST['depot_ville']    ?? '' );

    // 2) on vérifie le type
    if ( $type === 'court_sejour' || $type === 'long_sejour' ) {

        // 3) on explode la valeur "XXX - YYY - ZZZ"
        $parts = array_map( 'trim', explode( ' - ', $depot_raw ) );
        $visa_depot  = $parts[0] ?? '';
        $visa_wilaya = $parts[1] ?? '';
        $visa_ville  = $parts[2] ?? '';

        // 4) on enregistre tout
        update_post_meta( $request_id, 'visa_type',    $type );
        update_post_meta( $request_id, 'visa_depot',   $visa_depot );
        update_post_meta( $request_id, 'visa_wilaya',  $visa_wilaya );
        update_post_meta( $request_id, 'visa_ville',   $visa_ville );

        $should_redirect = true;
    } else {
        echo '<p>Type de visa invalide.</p>';
    }
}
?>

<h2>Étape 2 – Type de visa</h2>



<form method="post" class="visa-form" autocomplete="off">
    <input type="hidden" name="request_id" value="<?php echo esc_attr($request_id); ?>" />
	
	<div class="form-field">
		<label>Choisissez un type de visa : <span class="required">*</span></label>
		<select name="visa_type" required>
			<option value="">-- Sélectionner --</option>
			<option value="long_sejour" <?php selected($saved_type, 'long_sejour'); ?>>Visa Long Séjour (+ de 90 jours maximum)</option>
			<option value="court_sejour" <?php selected($saved_type, 'court_sejour'); ?>>Visa Court Séjour / VTA (90 jours maximum)</option>
		</select>
	</div>
	
	<div class="form-field">
		<label>Wilaya de résidence : <span class="required">*</span></label>
		<select name="depot_ville" required>
            <option value="">-- Sélectionner votre Wilaya de résidence --</option>
            <option value="Oran - 01 - Adrar" <?php selected($saved_depot_full, "Oran - 01 - Adrar"); ?>>01 - Adrar</option>
            <option value="Oran - 02 - Chlef" <?php selected($saved_depot_full, "Oran - 02 - Chlef"); ?>>02 - Chlef</option>
            <option value="Alger - 03 - Laghouat" <?php selected($saved_depot_full, "Alger - 03 - Laghouat"); ?>>03 - Laghouat</option>
            <option value="Constantine - 04 - Oum El Bouaghi" <?php selected($saved_depot_full, "Constantine - 04 - Oum El Bouaghi"); ?>>04 - Oum El Bouaghi</option>
            <option value="Constantine - 05 - Batna" <?php selected($saved_depot_full, "Constantine - 05 - Batna"); ?>>05 - Batna</option>
            <option value="Alger - 06 - Béjaïa" <?php selected($saved_depot_full, "Alger - 06 - Béjaïa"); ?>>06 - Béjaïa</option>
            <option value="Constantine - 07 - Biskra" <?php selected($saved_depot_full, "Constantine - 07 - Biskra"); ?>>07 - Biskra</option>
            <option value="Oran - 08 - Béchar" <?php selected($saved_depot_full, "Oran - 08 - Béchar"); ?>>08 - Béchar</option>
            <option value="Alger - 09 - Blida" <?php selected($saved_depot_full, "Alger - 09 - Blida"); ?>>09 - Blida</option>
            <option value="Alger - 10 - Bouira" <?php selected($saved_depot_full, "Alger - 10 - Bouira"); ?>>10 - Bouira</option>
            <option value="Alger - 11 - Tamanrasset" <?php selected($saved_depot_full, "Alger - 11 - Tamanrasset"); ?>>11 - Tamanrasset</option>
            <option value="Annaba - 12 - Tébessa" <?php selected($saved_depot_full, "Annaba - 12 - Tébessa"); ?>>12 - Tébessa</option>
            <option value="Oran - 13 - Tlemcen" <?php selected($saved_depot_full, "Oran - 13 - Tlemcen"); ?>>13 - Tlemcen</option>
            <option value="Oran - 14 - Tiaret" <?php selected($saved_depot_full, "Oran - 14 - Tiaret"); ?>>14 - Tiaret</option>
            <option value="Alger - 15 - Tizi Ouzou" <?php selected($saved_depot_full, "Alger - 15 - Tizi Ouzou"); ?>>15 - Tizi Ouzou</option>
            <option value="Alger - 16 - Alger" <?php selected($saved_depot_full, "Alger - 16 - Alger"); ?>>16 - Alger</option>
            <option value="Alger - 17 - Djelfa" <?php selected($saved_depot_full, "Alger - 17 - Djelfa"); ?>>17 - Djelfa</option>
            <option value="Constantine - 18 - Jijel" <?php selected($saved_depot_full, "Constantine - 18 - Jijel"); ?>>18 - Jijel</option>
            <option value="Constantine - 19 - Sétif" <?php selected($saved_depot_full, "Constantine - 19 - Sétif"); ?>>19 - Sétif</option>
            <option value="Oran - 20 - Saïda" <?php selected($saved_depot_full, "Oran - 20 - Saïda"); ?>>20 - Saïda</option>
            <option value="Annaba - 21 - Skikda" <?php selected($saved_depot_full, "Annaba - 21 - Skikda"); ?>>21 - Skikda</option>
            <option value="Oran - 22 - Sidi Bel Abbès" <?php selected($saved_depot_full, "Oran - 22 - Sidi Bel Abbès"); ?>>22 - Sidi Bel Abbès</option>
            <option value="Annaba - 23 - Annaba" <?php selected($saved_depot_full, "Annaba - 23 - Annaba"); ?>>23 - Annaba</option>
            <option value="Annaba - 24 - Guelma" <?php selected($saved_depot_full, "Constantine - 24 - Guelma"); ?>>24 - Guelma</option>
            <option value="Constantine - 25 - Constantine" <?php selected($saved_depot_full, "Constantine - 25 - Constantine"); ?>>25 - Constantine</option>
            <option value="Alger - 26 - Médéa" <?php selected($saved_depot_full, "Alger - 26 - Médéa"); ?>>26 - Médéa</option>
            <option value="Oran - 27 - Mostaganem" <?php selected($saved_depot_full, "Oran - 27 - Mostaganem"); ?>>27 - Mostaganem</option>
            <option value="Alger - 28 - M’Sila" <?php selected($saved_depot_full, "Alger - 28 - M’Sila"); ?>>28 - M’Sila</option>
            <option value="Oran - 29 - Mascara" <?php selected($saved_depot_full, "Oran - 29 - Mascara"); ?>>29 - Mascara</option>
            <option value="Alger - 30 - Ouargla" <?php selected($saved_depot_full, "Alger - 30 - Ouargla"); ?>>30 - Ouargla</option>
            <option value="Oran - 31 - Oran" <?php selected($saved_depot_full, "Oran - 31 - Oran"); ?>>31 - Oran</option>
            <option value="Oran - 32 - El Bayadh" <?php selected($saved_depot_full, "Oran - 32 - El Bayadh"); ?>>32 - El Bayadh</option>
            <option value="Alger - 33 - Illizi" <?php selected($saved_depot_full, "Alger - 33 - Illizi"); ?>>33 - Illizi</option>
            <option value="Alger - 34 - Bordj Bou Arreridj" <?php selected($saved_depot_full, "Alger - 34 - Bordj Bou Arreridj"); ?>>34 - Bordj Bou Arreridj</option>
            <option value="Alger - 35 - Boumerdès" <?php selected($saved_depot_full, "Alger - 35 - Boumerdès"); ?>>35 - Boumerdès</option>
            <option value="Annaba - 36 - El Tarf" <?php selected($saved_depot_full, "Annaba - 36 - El Tarf"); ?>>36 - El Tarf</option>
            <option value="Oran - 37 - Tindouf" <?php selected($saved_depot_full, "Oran - 37 - Tindouf"); ?>>37 - Tindouf</option>
            <option value="Oran - 38 - Tissemsilt" <?php selected($saved_depot_full, "Oran - 38 - Tissemsilt"); ?>>38 - Tissemsilt</option>
            <option value="Alger - 39 - El Oued" <?php selected($saved_depot_full, "Alger - 39 - El Oued"); ?>>39 - El Oued</option>
            <option value="Constantine - 40 - Khenchela" <?php selected($saved_depot_full, "Constantine - 40 - Khenchela"); ?>>40 - Khenchela</option>
            <option value="Annaba - 41 - Souk Ahras" <?php selected($saved_depot_full, "Annaba - 41 - Souk Ahras"); ?>>41 - Souk Ahras</option>
            <option value="Alger - 42 - Tipaza" <?php selected($saved_depot_full, "Alger - 42 - Tipaza"); ?>>42 - Tipaza</option>
            <option value="Constantine - 43 - Mila" <?php selected($saved_depot_full, "Constantine - 43 - Mila"); ?>>43 - Mila</option>
            <option value="Alger - 44 - Aïn Defla" <?php selected($saved_depot_full, "Alger - 44 - Aïn Defla"); ?>>44 - Aïn Defla</option>
            <option value="Oran - 45 - Naâma" <?php selected($saved_depot_full, "Oran - 45 - Naâma"); ?>>45 - Naâma</option>
            <option value="Oran - 46 - Aïn Témouchent" <?php selected($saved_depot_full, "Oran - 46 - Aïn Témouchent"); ?>>46 - Aïn Témouchent</option>
			<option value="Alger - 47 - Ghardaïa" <?php selected($saved_depot_full, "Alger - 47 - Ghardaïa"); ?>>47 - Ghardaïa</option>
			<option value="Oran - 48 - Relizane" <?php selected($saved_depot_full, "Oran - 48 - Relizane"); ?>>48 - Relizane</option>
			<option value="Oran - 49 - Timimoun" <?php selected($saved_depot_full, "Oran - 49 - Timimoun"); ?>>49 - Timimoun</option>
			<option value="Oran - 50 - Bordj Badji Mokhtar" <?php selected($saved_depot_full, "Oran - 50 - Bordj Badji Mokhtar"); ?>>50 - Bordj Badji Mokhtar</option>
			<option value="Constantine - 51 - Ouled Djellal" <?php selected($saved_depot_full, "Constantine - 51 - Ouled Djellal"); ?>>51 - Ouled Djellal</option>
			<option value="Oran - 52 - Béni Abbès" <?php selected($saved_depot_full, "Oran - 52 - Béni Abbès"); ?>>52 - Béni Abbès</option>
			<option value="Alger - 53 - In Salah" <?php selected($saved_depot_full, "Alger - 53 - In Salah"); ?>>53 - In Salah</option>
			<option value="Alger - 54 - In Guezzam" <?php selected($saved_depot_full, "Alger - 54 - In Guezzam"); ?>>54 - In Guezzam</option>
			<option value="Alger - 55 - Touggourt" <?php selected($saved_depot_full, "Alger - 55 - Touggourt"); ?>>55 - Touggourt</option>
			<option value="Alger - 56 - Djanet" <?php selected($saved_depot_full, "Alger - 56 - Djanet"); ?>>56 - Djanet</option>
			<option value="Alger - 57 - El M'Ghair" <?php selected($saved_depot_full, "Alger - 57 - El M'Ghair"); ?>>57 - El M'Ghair</option>
			<option value="Alger - 58 - El Menia" <?php selected($saved_depot_full, "Alger - 58 - El Menia"); ?>>58 - El Menia</option>
			<option value="Alger - 59 - Aflou" <?php selected($saved_depot_full, "Alger - 59 - Aflou"); ?>>59 - Aflou</option>
			<option value="Oran - 60 - El Abiodh Sidi Cheikh" <?php selected($saved_depot_full, "Oran - 60 - El Abiodh Sidi Cheikh"); ?>>60 - El Abiodh Sidi Cheikh</option>
			<option value="Oran - 61 - El Aricha" <?php selected($saved_depot_full, "Oran - 61 - El Aricha"); ?>>61 - El Aricha</option>
			<option value="Constantine - 62 - El Kantara" <?php selected($saved_depot_full, "Constantine - 62 - El Kantara"); ?>>62 - El Kantara</option>
			<option value="Constantine - 63 - Barika" <?php selected($saved_depot_full, "Constantine - 63 - Barika"); ?>>63 - Barika</option>
			<option value="Alger - 64 - Bou Saâda" <?php selected($saved_depot_full, "Alger - 64 - Bou Saâda"); ?>>64 - Bou Saâda</option>
			<option value="Annaba - 65 - Bir El Ater" <?php selected($saved_depot_full, "Annaba - 65 - Bir El Ater"); ?>>65 - Bir El Ater</option>
			<option value="Alger - 66 - Ksar El Boukhari" <?php selected($saved_depot_full, "Alger - 66 - Ksar El Boukhari"); ?>>66 - Ksar El Boukhari</option>
			<option value="Alger - 67 - Ksar Chellala" <?php selected($saved_depot_full, "Alger - 67 - Ksar Chellala"); ?>>67 - Ksar Chellala</option>
			<option value="Alger - 68 - Aïn Oussara" <?php selected($saved_depot_full, "Alger - 68 - Aïn Oussara"); ?>>68 - Aïn Oussara</option>
			<option value="Alger - 69 - Messaad" <?php selected($saved_depot_full, "Alger - 69 - Messaad"); ?>>69 - Messaad</option>
		</select>
	</div>

	<div class="form-field" style="font-size: larger;">
		<label style="font-size: large;">Centre de dépôt du dossier : </label>
		<p id="depot"><?php 
			if ($saved_depot) {
				echo esc_html($saved_depot);
			} else if ($saved_depot_full) {
				$parts = explode(' - ', $saved_depot_full);
				echo esc_html($parts[0] ?? '');
			}
		?></p>
	</div>

    <button type="submit" name="visa_level2_submit">Poursuivre</button>
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

	.visa-form {
	max-width: 600px;
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

	/* 4. Depot Display */
	.visa-form #depot {
	min-height: 1.5em;
	padding: 0.5em;
	font-style: italic;
	color: var(--vm-primary);
	border-left: 4px solid var(--vm-accent);
	background: #F9F9F9;
	border-radius: 0 var(--vm-radius) var(--vm-radius) 0;
	margin-top: -0.5em;
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
	
	/* Bouton Gmail Connect */
    .btn-gmail {
        display: inline-block;
        padding: 12px 24px;
        background-color: #4285F4; /* bleu Google */
        color: #fff;
        font-weight: 600;
        font-size: 16px;
        text-decoration: none;
        border-radius: 6px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }
    
    .btn-gmail:hover {
        background-color: #357ae8;
        box-shadow: 0 6px 10px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    
    .btn-gmail:active {
        background-color: #2a65c7;
        box-shadow: 0 3px 5px rgba(0,0,0,0.2);
        transform: translateY(0);
    }
    
    /* Optionnel : icône Google à gauche */
    .btn-gmail::before {
        content: url('https://upload.wikimedia.org/wikipedia/commons/4/4f/Google_2015_logo.svg');
        display: inline-block;
        width: 20px;
        height: 20px;
        margin-right: 8px;
        vertical-align: middle;
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
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const selectVille = document.querySelector('select[name="depot_ville"]');
  const depotP = document.getElementById('depot');

  // Fonction pour mettre à jour l'affichage du dépôt
  function updateDepot() {
    const value = selectVille.value;
    if (!value) {
      depotP.textContent = '';
      return;
    }
    const parts = value.split(' - ');
    depotP.textContent = parts[0] || '';
  }

  // Afficher le dépôt au chargement initial
  updateDepot();

  // Afficher le dépôt lors du changement
  selectVille.addEventListener('change', updateDepot);
});
</script>

<?php if ($should_redirect): ?>
    <script>
        setTimeout(function() {
            window.location.href = "<?php echo esc_url(home_url("/formulaire-visa/?request_id={$request_id}")); ?>";
        }, 100);
    </script>
<?php endif; ?>