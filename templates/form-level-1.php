<?php
// templates/form-level-1.php

defined('ABSPATH') || exit;

$remaining_slots = get_option('visa_daily_limit', 50); // Supprimé le doublon
$requests_today  = Visa_Request_Handler::count_processing_requests_today();
$available       = max($remaining_slots - $requests_today, 0);
$current_datetime = current_time('Y-m-d H:i');

// Liste officielle des 58 wilayas (source : gouvernement algérien)
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
?>

<form method="post" class="visa-form" id="form-level-1" autocomplete="off">
    <h2>Demande de visa – Étape 1</h2>

    <p>Date et heure actuelles : <strong><?php echo esc_html($current_datetime); ?></strong></p>
    <p>Demandes restantes aujourd’hui : <strong><?php echo esc_html($available); ?></strong></p>

    <label style="font-size: large;">
        Merci de fournir, ci-dessous, <strong>des précisions détaillées sur le motif de votre séjour</strong>, ainsi que vos <strong>liens avec votre pays d’origine</strong> (emploi, situation familiale, logement, études, etc.) et vos <strong>moyens de subsistance</strong> localement et pendant le séjour (revenus, hébergement, etc.).
    </label><br><br>
    <label style="font-weight: 900; font-size: large;">
        Ces éléments avec pièces justificatives sont impératifs et obligatoires pour déterminer le visa adapté et renforcer votre demande.
    </label><br>

    <!-- Nouveau champ motif (synchronisé avec étape 3) -->
    <div class="form-field">
        <label for="visa_motif">Objet du voyage : <span class="required">*</span></label>
        <select name="visa_motif" id="visa_motif" required>
            <option value="">-- Sélectionner un motif --</option>
            <optgroup label="Visa Court Séjour (≤ 90 jours)">
                <option value="court_sejour|etablissement_familial_prive">Établissement familial ou privé</option>
                <option value="court_sejour|etudes">Études</option>
                <option value="court_sejour|medical">Raisons médicales</option>
                <option value="court_sejour|tourisme">Tourisme</option>
                <option value="court_sejour|travailler">Travailler</option>
                <option value="court_sejour|accord_retrait">Visa d'entrée (accord de retrait)</option>
                <option value="court_sejour|visite_familiale_privee">Visite familiale ou privée</option>
                <option value="court_sejour|visite_officielle">Visite officielle</option>
            </optgroup>
            
            <optgroup label="Visa Long Séjour (> 90 jours)">
                <option value="long_sejour|autre">Autre</option>
                <option value="long_sejour|etudes">Études</option>
                <option value="long_sejour|installation_familiale_majeur">Installation familiale ou privée (majeur)</option>
                <option value="long_sejour|installation_familiale_mineur">Installation familiale ou privée (mineur)</option>
                <option value="long_sejour|fonctions_officielles">Prise de fonctions officielles</option>
                <option value="long_sejour|stage_salarie">Stage salarié</option>
                <option value="long_sejour|travailler">Travailler</option>
                <option value="long_sejour|visa_retour">Visa de retour</option>
                <option value="long_sejour|visiteur">Visiteur</option>
            </optgroup>
        </select>
    </div>
    <br>

    <!-- Wilaya corrigée -->
    <label for="ia_wliaya">Wilaya de résidence :</label><br>
    <?php if (!empty($wilayas) && is_array($wilayas)): ?>
        <select name="ia_wliaya" style="width:100%" required>
            <option value="">-- Sélectionner votre Wilaya de résidence --</option>
            <?php foreach ($wilayas as $code => $nom): ?>
                <option value="<?php echo esc_attr("{$code} - {$nom}"); ?>">
                    <?php echo esc_html("{$code} - {$nom}"); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>
    <?php else: ?>
        <p style="color:#a00;">La liste des wilayas n'est pas disponible. Vos informations seront sauvegardées sans wilaya.</p>
        <input type="hidden" name="ia_wliaya" value="">
        <br><br>
    <?php endif; ?>

    <!-- Autres champs inchangés -->
    <label for="ia_date_naiss">Date de naissance :</label><br>
    <input type="date" name="ia_date_naiss"><br><br>
    <label for="ia_sexe">Sexe :</label><br>
    <select id="ia_sexe" name="ia_sexe" required>
        <option value="homme">Homme</option>
        <option value="femme">Femme</option>
        <option value="autre">Autre</option>
    </select><br><br>
    <label for="ia_etat_civil">État civil :<span class="required">*</span></label><br>
    <select name="ia_etat_civil" id="ia_etat_civil" required>
        <option value="">-- Sélectionner votre état civil --</option>
        <option value="celibataire">Célibataire</option>
        <option value="marie">Marié(e)</option>
        <option value="partenariat">Partenariat enregistré</option>
        <option value="separe">Séparé(e)</option>
        <option value="divorce">Divorcé(e)</option>
        <option value="veuf">Veuf(Veuve)</option>
        <option value="autre">Autre</option>
    </select><br><br>
    <label for="ia_nationalite">Nationalité :</label><br>
    <select name="ia_nationalite" required>
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
	<label for="ia_resident">Êtes vous résident dans l'espace Schengen ?</label><br>
    <div class="radio-group">
        <input type="radio" name="ia_resident" value="oui" id="resident">
        <label for="resident" class="radio-btn">Oui</label>
    
        <input type="radio" name="ia_resident" value="non" id="non_resident">
        <label for="non_resident" class="radio-btn">Non</label>
    </div>
    
    <div class="form-field">
        <label for="ia_profession">Profession :</label>
        <select name="ia_profession" id="ia_profession">
            <option value="">&nbsp;</option>
            <option value="65001">Agriculteur</option>
            <option value="65002">Architecte</option>
            <option value="65003">Artisan</option>
            <option value="65004">Artiste</option>
            <option value="65005">Autre</option>
            <option value="65006">Autre technicien</option>
            <option value="66001">Banquier</option>
            <option value="67001">Cadre d'entreprise</option>
            <option value="67002">Chauffeur, routier</option>
            <option value="67003">Chef d'entreprise</option>
            <option value="67004">Chercheur, scientifique</option>
            <option value="67005">Chimiste</option>
            <option value="67006">Chômeur</option>
            <option value="67007">Clergé, religieux</option>
            <option value="67008">Commerçant</option>
            <option value="68001">Diplomate</option>
            <option value="69001">Electronicien</option>
            <option value="69005">Elève, Etudiant, stagiaire</option>
            <option value="69002">Employé</option>
            <option value="69003">Employé prive au service de diplomate</option>
            <option value="69004">Enseignant</option>
            <option value="70001">Fonctionnaire</option>
            <option value="72001">Homme politique</option>
            <option value="73001">Informaticien</option>
            <option value="74001">Journaliste</option>
            <option value="77001">Magistrat</option>
            <option value="77002">Marin</option>
            <option value="77003">Mode, cosmétique</option>
            <option value="79001">Ouvrier</option>
            <option value="80001">Personnel de service, administratif ou technique (postes dipl./cons.)</option>
            <option value="80002">Policier, militaire</option>
            <option value="80003">Profession juridique</option>
            <option value="80004">Profession libérale</option>
            <option value="80005">Profession médicale et paramédicale</option>
            <option value="82001">Retraite</option>
            <option value="83001">Sans profession</option>
            <option value="83002">Sportif</option>
        </select>
    </div>
    <label>Secteur d'activité :<span class="required">*</span></label><br>
    		<select name="secteur_activite">
			<option value="">-- Sélectionnez un secteur d'activité --</option>
			<option value="Activités de services administratifs et de soutien">Activités de services administratifs et de soutien</option>
			<option value="Activités des ménages en tant qu'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre">Activités des ménages en tant qu'employeurs; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre</option>
			<option value="Activités extra-territoriales">Activités extra-territoriales</option>
			<option value="Activités financières et d'assurance">Activités financières et d'assurance</option>
			<option value="Activités immobilières">Activités immobilières</option>
			<option value="Activités spécialisées, scientifiques et techniques">Activités spécialisées, scientifiques et techniques</option>
			<option value="Administration publique">Administration publique</option>
			<option value="Agriculture, sylviculture et pêche">Agriculture, sylviculture et pêche</option>
			<option value="Arts, spectacles et activités récréatives">Arts, spectacles et activités récréatives</option>
			<option value="Autres activités">Autres activités</option>
			<option value="Autres activités de services">Autres activités de services</option>
			<option value="Commerce; réparation d'automobiles et de motocycles">Commerce; réparation d'automobiles et de motocycles</option>
			<option value="Construction">Construction</option>
			<option value="Enseignement">Enseignement</option>
			<option value="Hébergement et restauration">Hébergement et restauration</option>
			<option value="Industrie manufacturière">Industrie manufacturière</option>
			<option value="Industries extractives">Industries extractives</option>
			<option value="Information et communication">Information et communication</option>
			<option value="Production et distribution d'eau; assainissement, gestion des déchets et dépollution">Production et distribution d'eau; assainissement, gestion des déchets et dépollution</option>
			<option value="Production et distribution d'électricité, de gaz, de vapeur et d'air conditionné">Production et distribution d'électricité, de gaz, de vapeur et d'air conditionné</option>
			<option value="Santé humaine et action sociale">Santé humaine et action sociale</option>
			<option value="Transports et entreposage">Transports et entreposage</option>
            </select>
    <br><br>
    <label for="ia_revenu_local">Comment subvenez vous à vos besoins ? (Montant)</label><br>
    <input type="text" name="ia_revenu_local"><br><br>
    <label for="ia_destination">Destination principale :</label><br>
    <input type="text" name="ia_destination"><br><br>
    <label for="ia_date_arrivee">Date d’arrivée :</label><br>
    <input type="date" name="ia_date_arrivee" id="ia_date_arrivee"><br><br>
    <label for="ia_date_depart">Date de départ :</label><br>
    <input type="date" name="ia_date_depart" id="ia_date_depart"><br><br>
    <label for="ia_activite_pdt_voyage">Votre activité pendant le voyage - à détailler précisément :</label><br>
    <input type="text" name="ia_activite_pdt_voyage"><br><br>
    <label for="ia_contact_sur_place">Contact sur place :</label><br>
    <input type="text" name="ia_contact_sur_place"><br><br>
    <label for="ia_moyens_subsistance_sur_place">Moyens de subsistance sur place :</label><br>
    <input type="text" name="ia_moyens_subsistance_sur_place"><br><br>
    <label for="visa_info_objet">Informations sur l'objet du voyage – le détail favorisera vos chances d'obtention du visa. Indiquez impérativement vos liens avec votre pays d'origine ainsi que tous vos moyens de subsistance :</label><br>
    <textarea name="visa_info_objet_base"></textarea><br><br>
    <hr><br>

    <p><strong>Créer une nouvelle adresse mail et un mot de passe</strong> contenant au minimum 12 caractères dont une majuscule, une minuscule et un chiffre.</p>
    <label for="email">Impératif - Créer une nouvelle adresse mail :</label><br>
    <input type="email" name="email" required value="<?php echo esc_attr($_GET['email_attempted'] ?? ''); ?>"><br><br>

    <label for="password">Mettre exactement le mot de passe de l'adresse mail créé ci-dessus.</label><br>
    <span><strong>En cas d'erreur, votre demande ne pourra pas être traitée</strong></span><br>
    <input 
        type="text" 
        name="password" 
        required
        pattern="^(?=.*[A-Z])(?=.*\d)[A-Za-z0-9^!$*+,.?@#'(){}\[\]:;\/\-_ ]{12,}$"
        title="Votre mot de passe doit contenir au moins 12 caractères, dont 1 majuscule et 1 chiffre. Les caractères %, &, <, =, >, | et &quot; ne sont pas autorisés."
    >
    <br>
    <br>
    
    <label for="confirm_password">Confirmer votre mot de passe.</label><br>
    <input 
        type="text" 
        name="confirm_password" 
        required
        pattern="^(?=.*[A-Z])(?=.*\d)[A-Za-z0-9^!$*+,.?@#'(){}\[\]:;\/\-_ ]{12,}$"
        title="Votre mot de passe doit contenir au moins 12 caractères, dont 1 majuscule et 1 chiffre. Les caractères %, &, <, =, >, | et &quot; ne sont pas autorisés."
    >
    <br>
    <span id="confirm_password"></span>
    <br>
    <br>
    
    <label>Type de demande :</label>

    <div class="radio-group">
        <input type="radio" name="visa_group" value="0" id="visa_individual" checked>
        <label for="visa_individual" class="radio-btn">Demande individuelle</label>
    
        <input type="radio" name="visa_group" value="1" id="visa_grouped">
        <label for="visa_grouped" class="radio-btn">Demande multiple</label>
    </div>
    
    <div id="visa_group_div" style="margin-top:10px; display:none;align-items: center;">
        <label for="visa_group_id" style="width: 200px;">Numéro de groupe</label>
        <input type="number" name="visa_group_id" id="visa_group_id" style="border:none" readonly>
    </div>

    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const individual = document.getElementById('visa_individual');
        const grouped = document.getElementById('visa_grouped');
        const groupDiv = document.getElementById('visa_group_div');
    
        function toggleGroupDiv() {
            groupDiv.style.display = grouped.checked ? 'flex' : 'none';
        }
    
        individual.addEventListener('change', toggleGroupDiv);
        grouped.addEventListener('change', toggleGroupDiv);
    
        toggleGroupDiv(); // initialisation
        
        function getUrlParam(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        }
    
        const groupId = getUrlParam("group_id");
    
        if (groupId) {
            // Récupérer les éléments
            const visaIndividual = document.getElementById("visa_individual");
            const visaGrouped = document.getElementById("visa_grouped");
            const visaGroupDiv = document.getElementById("visa_group_div");
            const visaGroupInput = document.getElementById("visa_group_id");
    
            // Désactiver le bouton "Demande individuelle"
            if (visaIndividual) {
                visaIndividual.disabled = true;
                // Optionnel : changer le style pour montrer que c'est désactivé
                visaIndividual.nextElementSibling.style.opacity = "0.5";
                visaIndividual.nextElementSibling.style.pointerEvents = "none";
            }
    
            // Cocher "Demande multiple"
            if (visaGrouped) visaGrouped.checked = true;
    
            // Afficher le champ de groupe et remplir la valeur
            if (visaGroupDiv) visaGroupDiv.style.display = "block";
            if (visaGroupInput) visaGroupInput.value = groupId;
        }
        
        const dateArrivee = document.getElementById("ia_date_arrivee");
        const dateDepart  = document.getElementById("ia_date_depart");
        const visaMotif   = document.getElementById("visa_motif");
        
        const today = new Date().toISOString().split("T")[0];
        dateArrivee.min = today;
    
        function updateConstraints() {
            console.log("f");
            // Si motif ou arrivée manquant → reset propre
            if (!dateArrivee.value || !visaMotif.value) {
                dateDepart.value = "";
                dateDepart.removeAttribute("min");
                dateDepart.removeAttribute("max");
                return;
            }
    
            const arrivee = new Date(dateArrivee.value);
            const departValue = dateDepart.value ? new Date(dateDepart.value) : null;
    
            const typeSejour = visaMotif.value.split("|")[0];
    
            // --- MIN ---
            let minDate = new Date(arrivee);
            minDate.setDate(minDate.getDate() + 1);
    
            // --- MAX court séjour ---
            let maxDate = null;
    
            if (typeSejour === "court_sejour") {
                maxDate = new Date(arrivee);
                maxDate.setMonth(maxDate.getMonth() + 3);
    
                dateDepart.max = maxDate.toISOString().split("T")[0];
                dateDepart.min = minDate.toISOString().split("T")[0];
            }
    
            // --- MIN long séjour ---
            if (typeSejour === "long_sejour") {
                let minLong = new Date(arrivee);
                minLong.setMonth(minLong.getMonth() + 3);
    
                dateDepart.min = minLong.toISOString().split("T")[0];
    
                dateDepart.removeAttribute("max");
            }
    
            // SI LE CHAMP EST VIDE → ne rien vérifier
            if (!departValue) return;
    
            // --- RESET uniquement si HORS limite ---
            const current = departValue.getTime();
            const min = new Date(dateDepart.min).getTime();
            const max = dateDepart.max ? new Date(dateDepart.max).getTime() : null;
    
            if (current < min || (max && current > max)) {
                dateDepart.value = "";
            }
        }
    
        dateArrivee.addEventListener("change", updateConstraints);
        visaMotif.addEventListener("change", updateConstraints);
        dateDepart.addEventListener("change", updateConstraints);
        
        function openPickerOnFocus(input) {
            input.addEventListener('focus', () => {
              if (typeof input.showPicker === 'function') {
                input.showPicker(); // ouvre le picker natif si disponible
              } else {
                // fallback : on garde le focus (certains navigateurs ouvrent le picker automatiquement)
                input.focus();
                // optionnel : afficher un datepicker custom ici
              }
            });
        }
        
        openPickerOnFocus(document.getElementById('ia_date_arrivee'));
        openPickerOnFocus(document.getElementById('ia_date_depart'));
        
        // Confirmation Mdp
        
        const password = document.querySelector('input[name="password"]');
        const confirm  = document.querySelector('input[name="confirm_password"]');
        const msg      = document.getElementById('confirm_password');
    
        function checkPassword() {
            // Reset si champ vide
            if (!confirm.value) {
                msg.textContent = '';
                msg.classList.remove('mdp-err', 'mdp-ok');
                return;
            }
    
            if (password.value !== confirm.value) {
                msg.textContent = 'Le mot de passe doit être identique';
                msg.classList.remove('mdp-ok');
                msg.classList.add('mdp-err');
            } else {
                msg.textContent = 'Mot de passe confirmé';
                msg.classList.remove('mdp-err');
                msg.classList.add('mdp-ok');
            }
        }
    
        password.addEventListener('input', checkPassword);
        confirm.addEventListener('input', checkPassword);

    });
    </script>

    <br><br>

    <?php if ($available == 0): ?>
        <button class="submit-stop" disabled>Demande journalière dépassée, merci de faire votre demande demain.</button>
    <?php else: ?>
        <div class="g-recaptcha" data-sitekey="6LfKYIcrAAAAAOEgVxJCcZyjWcElmx-DN8Hb6V88"></div>
        <button type="submit" name="visa_level1_submit">Valider et poursuivre</button>
    <?php endif; ?>

    <?php if (isset($_GET['login_error']) && $_GET['login_error'] === 'password'): ?>
        <div class="visa-error-message" style="background:#fee;padding:15px;border:1px solid #f99;margin-bottom:20px;margin-top:20px;">
            <strong>Erreur :</strong> Le mot de passe est incorrect.<br>
            <p>Si vous avez oublié votre mot de passe, vous pouvez le réinitialiser en cliquant <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">ici</a>.</p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['captcha_error'])): ?>
        <div class="visa-error-message" style="background:#fee;padding:15px;border:1px solid #f99;margin-bottom:20px;">
            <strong>Erreur :</strong> Le captcha n’a pas été validé. Merci de réessayer.
        </div>
    <?php endif; ?>
</form>

<style>
    /* Identique à ton CSS – inchangé */
    :root {
        --vm-primary: #004494;
        --vm-primary-80: #003776;
        --vm-accent: #FFD617;
        --vm-text: rgb(40, 48, 54);
        --vm-border: #CCCCCC;
        --vm-radius: 6px;
        --vm-spacing: 1rem;
        --vm-font: 'Montserrat', sans-serif;
    }

    .visa-form {
        max-width: 600px;
        margin: 2em auto;
        padding: 2em;
        background: #FFF;
        border-radius: var(--vm-radius);
        box-shadow: 0 4px 12px rgba(0,0,0,0.5);
        font-family: var(--vm-font);
        color: var(--vm-text);
    }

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

    .visa-form input,
    .visa-form select,
    .visa-form textarea {
        width: 100%;
        padding: 0.6em 0.8em;
        font-size: 1rem;
        border: 1px solid var(--vm-border);
        border-radius: var(--vm-radius);
        background: #FFF;
        transition: border-color 0.2s;
        color: var(--vm-text);
    }

    .visa-form .required {
        color: var(--vm-accent);
    }

    .visa-form button[type="submit"] {
        width: 100%;
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

    .submit-stop {
        width: 100%;
        padding: 0.75em 1.5em;
        font-size: 1rem;
        font-weight: 600;
        color: #FFF;
        background: #64748b;
        border: none;
        border-radius: var(--vm-radius);
        cursor: not-allowed;
    }

    .visa-form button[type="submit"]:hover {
        background: var(--vm-primary-80);
        transform: translateY(-1px);
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: #2196F3;
    }

    input:checked + .slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    @media (max-width: 480px) {
        .visa-form {
            padding: 1.5em;
        }
    }
    
    /* Conteneur sur une seule ligne */
    .radio-group {
        display: flex;
        gap: 20px;
        align-items: center;
        margin: 10px 0;
    }
    
    /* Masquer le vrai bouton radio */
    .radio-group input[type="radio"] {
        display: none;
    }
    
    /* Style du label comme un bouton */
    .radio-btn {
        padding: 10px 18px;
        background: #f0f0f0;
        border: 2px solid #ccc;
        border-radius: 25px;
        cursor: pointer;
        font-size: 15px;
        transition: all .25s ease;
        user-select: none;
    }
    
    /* Quand sélectionné */
    .radio-group input[type="radio"]:checked + .radio-btn {
        background: #4a8cff;
        border-color: #4a8cff;
        color: white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    
    /* Survol */
    .radio-btn:hover {
        background: #e6e6e6;
    }

    .mdp-err {
        display: inline-block;
        margin-top: 6px;
        padding: 6px 10px;
        border-radius: 6px;
    
        color: #b42318;
        background-color: #fdecea;
        border: 1px solid #f5c2c0;
    
        font-size: 0.9rem;
        line-height: 1.4;
    }
    
    .mdp-ok {
        display: inline-block;
        margin-top: 6px;
        padding: 6px 10px;
        border-radius: 6px;
    
        color: #0f5132;
        background-color: #e6f4ea;
        border: 1px solid #badbcc;
    
        font-size: 0.9rem;
        line-height: 1.4;
    }

</style>

<!-- âÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂÂ URL corrigée (sans espaces) -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>