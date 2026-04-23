<?php
// templates/mandate-step.php
defined('ABSPATH') || exit;

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if (!$request_id || get_post_type($request_id) !== 'visa_request') {
    echo '<p>Demande introuvable ou invalide.</p>';
    return;
}
    
// Bloquer l'accès si la demande a déjà été confirmée / payée
$confirmed = get_post_meta($request_id, 'visa_confirmed', true);
if ($confirmed) {
	echo '<div class="visa-already-processed" style="padding:1.2em;border:1px solid #e0e0e0;background:#fffdf6;max-width:720px;">';
	echo '<h3 style="margin-top:0;color:#333;">Demande déjà traitée</h3>';
	echo '<p>Cette demande a déjà été traitée et le paiement a été confirmé. Pour soumettre une nouvelle demande, merci de créer un nouveau formulaire avec un identifiant différent.</p>';
	echo '<p><a class="button" href="' . esc_url(home_url('/demande-de-visa/')) . '">Créer une nouvelle demande</a></p>';
	echo '</div>';
	return;
}
    
// Vérification du groupe
$visa_group = get_post_meta($request_id, 'visa_group', true);

// Exemple de données simulées (à remplacer par des métadonnées récupérées dynamiquement)
$first_name = get_post_meta($request_id, 'visa_prenom', true) ?: '';
$last_name = get_post_meta($request_id, 'visa_full_name', true) ?: '';
$applicant_name = trim($first_name . ' ' . $last_name);
$birth_date = get_post_meta($request_id, 'visa_birth_date', true) ?: '01/01/1990';
$birth_place = get_post_meta($request_id, 'visa_lieu_naiss', true) ?: 'Alger';
$passport_number = get_post_meta($request_id, 'visa_num_document', true) ?: '123456789';
$address = get_post_meta($request_id, 'visa_adresse', true) ?: '123 Rue Exemple, Alger';
$company_name = 'Visa Logistics';
$current_city = get_post_meta($request_id, 'visa_ville', true) ?: 'Alger';
$current_date = date('d/m/Y');
$nationalities = [
			'AFG' => 'Afghane (Afghanistan)',
			'ALB' => 'Albanaise (Albanie)',
			'DZA' => 'Algérienne (Algérie)',
			'DEU' => 'Allemande (Allemagne)',
			'USA' => 'Américaine (États-Unis)',
			'AND' => 'Andorrane (Andorre)',
			'AGO' => 'Angolaise (Angola)',
			'ATG' => 'Antiguaise-et-Barbudienne (Antigua-et-Barbuda)',
			'ARG' => 'Argentine (Argentine)',
			'ARM' => 'Arménienne (Arménie)',
			'AUS' => 'Australienne (Australie)',
			'AUT' => 'Autrichienne (Autriche)',
			'AZE' => 'Azerbaïdjanaise (Azerbaïdjan)',
			'BHS' => 'Bahamienne (Bahamas)',
			'BHR' => 'Bahreïnienne (Bahreïn)',
			'BGD' => 'Bangladaise (Bangladesh)',
			'BRB' => 'Barbadienne (Barbade)',
			'BEL' => 'Belge (Belgique)',
			'BLZ' => 'Belizienne (Belize)',
			'BEN' => 'Béninoise (Bénin)',
			'BTN' => 'Bhoutanaise (Bhoutan)',
			'BLR' => 'Biélorusse (Biélorussie)',
			'MMR' => 'Birmane (Birmanie)',
			'GNB' => 'Bissau-Guinéenne (Guinée-Bissau)',
			'BOL' => 'Bolivienne (Bolivie)',
			'BIH' => 'Bosnienne (Bosnie-Herzégovine)',
			'BWA' => 'Botswanaise (Botswana)',
			'BRA' => 'Brésilienne (Brésil)',
			'GBR' => 'Britannique (Royaume-Uni)',
			'BRN' => 'Brunéienne (Brunéi)',
			'BGR' => 'Bulgare (Bulgarie)',
			'BFA' => 'Burkinabée (Burkina)',
			'BDI' => 'Burundaise (Burundi)',
			'KHM' => 'Cambodgienne (Cambodge)',
			'CMR' => 'Camerounaise (Cameroun)',
			'CAN' => 'Canadienne (Canada)',
			'CPV' => 'Cap-verdienne (Cap-Vert)',
			'CAF' => 'Centrafricaine (Centrafrique)',
			'CHL' => 'Chilienne (Chili)',
			'CHN' => 'Chinoise (Chine)',
			'CYP' => 'Chypriote (Chypre)',
			'COL' => 'Colombienne (Colombie)',
			'COM' => 'Comorienne (Comores)',
			'COG' => 'Congolaise (Congo-Brazzaville)',
			'COD' => 'Congolaise (Congo-Kinshasa)',
			'COK' => 'Cookienne (Îles Cook)',
			'CRI' => 'Costaricaine (Costa Rica)',
			'HRV' => 'Croate (Croatie)',
			'CUB' => 'Cubaine (Cuba)',
			'DNK' => 'Danoise (Danemark)',
			'DJI' => 'Djiboutienne (Djibouti)',
			'DOM' => 'Dominicaine (République dominicaine)',
			'DMA' => 'Dominiquaise (Dominique)',
			'EGY' => 'Égyptienne (Égypte)',
			'ARE' => 'Émirienne (Émirats arabes unis)',
			'GNQ' => 'Équato-guineenne (Guinée équatoriale)',
			'ECU' => 'Équatorienne (Équateur)',
			'ERI' => 'Érythréenne (Érythrée)',
			'ESP' => 'Espagnole (Espagne)',
			'TLS' => 'Est-timoraise (Timor-Leste)',
			'EST' => 'Estonienne (Estonie)',
			'ETH' => 'Éthiopienne (Éthiopie)',
			'FJI' => 'Fidjienne (Fidji)',
			'FIN' => 'Finlandaise (Finlande)',
			'FRA' => 'Française (France)',
			'GAB' => 'Gabonaise (Gabon)',
			'GMB' => 'Gambienne (Gambie)',
			'GEO' => 'Géorgienne (Géorgie)',
			'GHA' => 'Ghanéenne (Ghana)',
			'GRD' => 'Grenadienne (Grenade)',
			'GTM' => 'Guatémaltèque (Guatemala)',
			'GIN' => 'Guinéenne (Guinée)',
			'GUY' => 'Guyanienne (Guyana)',
			'HTI' => 'Haïtienne (Haïti)',
			'GRC' => 'Hellénique (Grèce)',
			'HND' => 'Hondurienne (Honduras)',
			'HUN' => 'Hongroise (Hongrie)',
			'IND' => 'Indienne (Inde)',
			'IDN' => 'Indonésienne (Indonésie)',
			'IRQ' => 'Irakienne (Irak)',
			'IRN' => 'Iranienne (Iran)',
			'IRL' => 'Irlandaise (Irlande)',
			'ISL' => 'Islandaise (Islande)',
			'ISR' => 'Israélienne (Israël)',
			'ITA' => 'Italienne (Italie)',
			'CIV' => 'Ivoirienne (Côte d’Ivoire)',
			'JAM' => 'Jamaïcaine (Jamaïque)',
			'JPN' => 'Japonaise (Japon)',
			'JOR' => 'Jordanienne (Jordanie)',
			'KAZ' => 'Kazakhstanaise (Kazakhstan)',
			'KEN' => 'Kenyane (Kenya)',
			'KGZ' => 'Kirghize (Kirghizistan)',
			'KIR' => 'Kiribatienne (Kiribati)',
			'KNA' => 'Kittitienne et Névicienne (Saint-Christophe-et-Niévès)',
			'KWT' => 'Koweïtienne (Koweït)',
			'LAO' => 'Laotienne (Laos)',
			'LSO' => 'Lesothane (Lesotho)',
			'LVA' => 'Lettone (Lettonie)',
			'LBN' => 'Libanaise (Liban)',
			'LBR' => 'Libérienne (Libéria)',
			'LBY' => 'Libyenne (Libye)',
			'LIE' => 'Liechtensteinoise (Liechtenstein)',
			'LTU' => 'Lituanienne (Lituanie)',
			'LUX' => 'Luxembourgeoise (Luxembourg)',
			'MKD' => 'Macédonienne (Macédoine du Nord)',
			'MYS' => 'Malaisienne (Malaisie)',
			'MWI' => 'Malawienne (Malawi)',
			'MDV' => 'Maldivienne (Maldives)',
			'MDG' => 'Malgache (Madagascar)',
			'MLI' => 'Malienne (Mali)',
			'MLT' => 'Maltaise (Malte)',
			'MAR' => 'Marocaine (Maroc)',
			'MHL' => 'Marshallaise (Îles Marshall)',
			'MUS' => 'Mauricienne (Maurice)',
			'MRT' => 'Mauritanienne (Mauritanie)',
			'MEX' => 'Mexicaine (Mexique)',
			'FSM' => 'Micronésienne (Micronésie)',
			'MDA' => 'Moldave (Moldavie)',
			'MCO' => 'Monégasque (Monaco)',
			'MNG' => 'Mongole (Mongolie)',
			'MNE' => 'Monténégrine (Monténégro)',
			'MOZ' => 'Mozambicaine (Mozambique)',
			'NAM' => 'Namibienne (Namibie)',
			'NRU' => 'Nauruane (Nauru)',
			'NLD' => 'Néerlandaise (Pays-Bas)',
			'NZL' => 'Néo-Zélandaise (Nouvelle-Zélande)',
			'NPL' => 'Népalaise (Népal)',
			'NIC' => 'Nicaraguayenne (Nicaragua)',
			'NGA' => 'Nigériane (Nigéria)',
			'NER' => 'Nigérienne (Niger)',
			'NIU' => 'Niuéenne (Niue)',
			'PRK' => 'Nord-coréenne (Corée du Nord)',
			'NOR' => 'Norvégienne (Norvège)',
			'OMN' => 'Omanaise (Oman)',
			'UGA' => 'Ougandaise (Ouganda)',
			'UZB' => 'Ouzbéke (Ouzbékistan)',
			'PAK' => 'Pakistanaise (Pakistan)',
			'PLW' => 'Palaosienne (Palaos)',
			'PSE' => 'Palestinienne (Palestine)',
			'PAN' => 'Panaméenne (Panama)',
			'PNG' => 'Papouane-Néo-Guinéenne (Papouasie-Nouvelle-Guinée)',
			'PRY' => 'Paraguayenne (Paraguay)',
			'PER' => 'Péruvienne (Pérou)',
			'PHL' => 'Philippine (Philippines)',
			'POL' => 'Polonaise (Pologne)',
			'PRT' => 'Portugaise (Portugal)',
			'QAT' => 'Qatarienne (Qatar)',
			'ROU' => 'Roumaine (Roumanie)',
			'RUS' => 'Russe (Russie)',
			'RWA' => 'Rwandaise (Rwanda)',
			'LCA' => 'Saint-Lucienne (Sainte-Lucie)',
			'SMR' => 'Saint-Marinaise (Saint-Marin)',
			'VCT' => 'Saint-Vincentaise et Grenadine (Saint-Vincent-et-les-Grenadines)',
			'SLB' => 'Salomonaise (Îles Salomon)',
			'SLV' => 'Salvadorienne (Salvador)',
			'WSM' => 'Samoane (Samoa)',
			'STP' => 'Santoméenne (Sao Tomé-et-Principe)',
			'SAU' => 'Saoudienne (Arabie saoudite)',
			'SEN' => 'Sénégalaise (Sénégal)',
			'SRB' => 'Serbe (Serbie)',
			'SYC' => 'Seychelloise (Seychelles)',
			'SLE' => 'Sierra-Léonaise (Sierra Leone)',
			'SGP' => 'Singapourienne (Singapour)',
			'SVK' => 'Slovaque (Slovaquie)',
			'SVN' => 'Slovène (Slovénie)',
			'SOM' => 'Somalienne (Somalie)',
			'SDN' => 'Soudanaise (Soudan)',
			'LKA' => 'Sri-Lankaise (Sri Lanka)',
			'ZAF' => 'Sud-Africaine (Afrique du Sud)',
			'KOR' => 'Sud-Coréenne (Corée du Sud)',
			'SSD' => 'Sud-Soudanaise (Soudan du Sud)',
];
$nationalite_code = get_post_meta($request_id, 'visa_nationalite', true) ?: 'DZA';
$nationalite = $nationalities[$nationalite_code] ?? 'Nationalité inconnue';

// Traitement de la soumission du formulaire
if (isset($_POST['accept_mandate'])) {

    $mandate_time = current_time('mysql');
    $confirm_add_to_group = isset($_POST['confirm_add_to_group']) ? true : false;
    
    // Générer le contenu du mandat en HTML
    ob_start();
    ?>
    <div style="border:1px solid #ccc; padding:20px; margin-bottom:20px;">
        <h1>Mandat de procuration</h1>
        <p>Je soussigné(e), <strong><?php echo esc_html($applicant_name); ?></strong>, né(e) le <strong><?php echo esc_html($birth_date); ?></strong> à <strong><?php echo esc_html($birth_place); ?></strong>, de nationalité <strong><?php echo esc_html($nationalite); ?></strong>, titulaire du passeport n° <strong><?php echo esc_html($passport_number); ?></strong>, résidant à <strong><?php echo esc_html($address); ?></strong>,</p>
        <p>donne pouvoir à l’entreprise <strong><?php echo esc_html($company_name); ?></strong>,</p>
        <p>pour effectuer en mon nom les démarches préalables à la demande d’un <strong>visa pour l'espace Schengen</strong>, à savoir :
        <ul>
            <li>Remplir le formulaire de demande de visa sur le site <a href="https://france-visas.gouv.fr" target="_blank">France-Visas</a></li>
            <li>Prendre un rendez-vous auprès du prestataire <strong>Capago International</strong></li>
            <li>Préparer les documents administratifs nécessaires à la constitution du dossier</li>
        </ul></p>
        <p>Je m’engage à me présenter personnellement au centre de dépôt pour la prise de mes données biométriques et le dépôt final du dossier.</p>
        <p>Ce mandat est valable uniquement pour la demande en cours.</p>
        <h3>1. Nature de l’Utilisation de l’IA</h3>
        <p>Le Mandataire est autorisé à utiliser des technologies d’intelligence artificielle, y compris, sans s’y limiter, des modèles de traitement automatique du langage naturel, de génération de contenu, d’analyse de données ou de recommandation automatisée, dans le but de :</p>
        <ul>
            <li>Améliorer la qualité ou l’efficacité des services fournis</li>
            <li>Générer des rapports, documents, contenus ou recommandations</li>
            <li>Analyser ou traiter des données fournies par le Client</li>
        </ul>
    
        <h3>2. Limites de l’Utilisation</h3>
        <p>L’IA ne sera pas utilisée pour :</p>
        <ul>
            <li>Prendre des décisions juridiques ou médicales sans validation humaine</li>
            <li>Réutiliser, vendre ou partager les données du Client sans son consentement</li>
            <li>Traiter des données sensibles sans encadrement explicite</li>
        </ul>
    
        <h3>3. Confidentialité et Protection des Données</h3>
        <p>Le Mandataire s’engage à :</p>
        <ul>
            <li>Protéger la confidentialité des données du Client</li>
            <li>Ne transmettre aucune donnée personnelle identifiable à des systèmes d’IA tiers sans anonymisation</li>
            <li>Respecter la législation en vigueur, notamment le RGPD (Règlement Général sur la Protection des Données)</li>
        </ul>
    
        <h3>4. Responsabilités</h3>
        <p>Le Mandataire demeure responsable de l’usage des outils d’IA et de la validation finale des résultats fournis par ceux-ci. Le Client comprend que les systèmes d’IA peuvent générer des erreurs ou des contenus inexacts, et accepte que les résultats doivent être interprétés avec prudence.</p>
    
        <h3>5. Durée du Mandat</h3>
        <p>Ce mandat est valide pour la présente demande et restera en vigueur jusqu’à l’obtention ou le refus du visa ou jusqu’à révocation écrite par l’une des parties.</p>
        
        <h3>6. Révocation</h3>
        <p>Le Client peut, à tout moment, révoquer le présent mandat par notification écrite au Mandataire.</p>
        
        <p>Fait à <strong><?php echo esc_html($current_city); ?></strong>, le <strong><?php echo esc_html($current_date); ?></strong></p>
        <p><strong>Signature du mandant :</strong> <br><?php echo esc_html($applicant_name); ?></p>
        
    </div>
    <?php
    $html = ob_get_clean();

    // Générer le PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4');
    $dompdf->render();
    $pdf_content = $dompdf->output();

    // Sauvegarder le fichier PDF
    $pdf_file_name = 'mandat_' . $request_id . '_' . time() . '.pdf';
    $upload = wp_upload_bits($pdf_file_name, null, $pdf_content);

    if (!$upload['error']) {
        // Enregistrer dans les documents
        $uploads = get_post_meta($request_id, 'visa_documents', true) ?: [];
        $uploads[] = esc_url_raw($upload['url']);
        update_post_meta($request_id, 'visa_documents', $uploads);
    }

    update_post_meta($request_id, 'mandate_accepted', 'yes');
    update_post_meta($request_id, 'mandate_signed_at', $mandate_time);

    $creation_redirect = false;
    $should_redirect  = false;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_demand'])) {
        if ($_POST['new_demand'] === "yes") {
            $creation_redirect = true;
        } else {
            $should_redirect = true;
        }
    }
	
	/*
	// Enregistrement terminé → redirige vers le paiement
	$payment_url = home_url('/paiement-visa/?request_id=' . $request_id);
	wp_redirect($payment_url);
	exit;
	*/
    // echo '<p>Mandat validé. <a href="' . esc_url($upload['url']) . '" target="_blank">Télécharger le PDF</a></p>';
    // return;
}
?>

<h2 translate="no">Étape finale – Mandat</h2>

<div style="border:1px solid #ccc; padding:20px; margin-bottom:20px;" translate="no">
    <h3>Aperçu du Mandat</h3>
    <p>Je soussigné(e), <strong><?php echo esc_html($applicant_name); ?></strong>, né(e) le <strong><?php echo esc_html($birth_date); ?></strong> à <strong><?php echo esc_html($birth_place); ?></strong>, de nationalité <strong><?php echo esc_html($nationalite); ?></strong>, titulaire du passeport n° <strong><?php echo esc_html($passport_number); ?></strong>, résidant à <strong><?php echo esc_html($address); ?></strong>,</p>
    <p>donne pouvoir à l’entreprise <strong><?php echo esc_html($company_name); ?></strong>,</p>
    <p>pour effectuer en mon nom les démarches préalables à la demande d’un <strong>visa pour l'espace Schengen</strong>, à savoir :
    <ul>
        <li>Remplir le formulaire de demande de visa sur le site <a href="https://france-visas.gouv.fr" target="_blank">France-Visas</a></li>
        <li>Prendre un rendez-vous auprès du prestataire <strong>Capago International</strong></li>
        <li>Préparer les documents administratifs nécessaires à la constitution du dossier</li>
    </ul></p>
    <p>Je m’engage à me présenter personnellement au centre de dépôt pour la prise de mes données biométriques et le dépôt final du dossier.</p>
    <p>Ce mandat est valable uniquement pour la demande en cours.</p>
    <h3>1. Nature de l’Utilisation de l’IA</h3>
    <p>Le Mandataire est autorisé à utiliser des technologies d’intelligence artificielle, y compris, sans s’y limiter, des modèles de traitement automatique du langage naturel, de génération de contenu, d’analyse de données ou de recommandation automatisée, dans le but de :</p>
    <ul>
        <li>Améliorer la qualité ou l’efficacité des services fournis</li>
        <li>Générer des rapports, documents, contenus ou recommandations</li>
        <li>Analyser ou traiter des données fournies par le Client</li>
    </ul>

    <h3>2. Limites de l’Utilisation</h3>
    <p>L’IA ne sera pas utilisée pour :</p>
    <ul>
        <li>Prendre des décisions juridiques ou médicales sans validation humaine</li>
        <li>Réutiliser, vendre ou partager les données du Client sans son consentement</li>
        <li>Traiter des données sensibles sans encadrement explicite</li>
    </ul>

    <h3>3. Confidentialité et Protection des Données</h3>
    <p>Le Mandataire s’engage à :</p>
    <ul>
        <li>Protéger la confidentialité des données du Client</li>
        <li>Ne transmettre aucune donnée personnelle identifiable à des systèmes d’IA tiers sans anonymisation</li>
        <li>Respecter la législation en vigueur, notamment la loi n° 18-07 du 10 juin 2018 relative à la protection des personnes physiques dans le traitement des données à caractère personnel et le RGPD (Règlement Général sur la Protection des Données)</li>
    </ul>

    <h3>4. Responsabilités</h3>
    <p>Le Mandataire demeure responsable de l’usage des outils d’IA et de la validation finale des résultats fournis par ceux-ci. Le Client comprend que les systèmes d’IA peuvent générer des erreurs ou des contenus inexacts, et accepte que les résultats doivent être interprétés avec prudence.</p>

    <h3>5. Durée du Mandat</h3>
    <p>Ce mandat est valide pour la présente demande et restera en vigueur jusqu’à l’obtention ou le refus du visa ou jusqu’à révocation écrite par l’une des parties.</p>
    
    <h3>6. Révocation</h3>
    <p>Le Client peut, à tout moment, révoquer le présent mandat par notification écrite au Mandataire.</p>
    
    <p>Fait à <strong><?php echo esc_html($current_city); ?></strong>, le <strong><?php echo esc_html($current_date); ?></strong></p>
    <p><strong>Signature du mandant :</strong> <br><?php echo esc_html($applicant_name); ?></p>
    
</div>

<!-- Dans mandate-step.php -->
<form method="post" id="visa-mandate-form" translate="no">
    <?php wp_nonce_field('visa_mandate_submit', 'visa_mandate_nonce'); ?>
    <input type="hidden" name="request_id" value="<?php echo esc_attr($request_id); ?>" />
    <label>
        <input type="checkbox" name="accept_mandate" value="1" required>
		<strong>J'ACCEPTE LE MANDAT ET AUTORISE LE TRAITEMENT DE MA DEMANDE.</strong>
    </label><br><br>

	<?php if ($visa_group): ?>
        
		<h3>Voulez-vous soumettre une nouvelle demande pour ce groupe&nbsp;? (Groupe <?php echo esc_html($visa_group); ?>)</h3>
        <div style="display:flex;gap:15px;margin:15px 0;">
            <button type="submit" name="new_demand" value="yes">Oui</button>
            <button type="submit" name="new_demand" value="no">Non</button>
        </div>
    <?php else: ?>
        <button type="submit" name="new_demand" value="no">Soumettre définitivement</button>
    <?php endif; ?>
</form>

<?php if ($creation_redirect): ?>
    <script>
        setTimeout(function () {
            window.location.href = "<?php echo esc_url(home_url("/demande-de-visa/?group_id={$visa_group}")); ?>";
        }, 300);
    </script>
<?php endif; ?>

<?php if ($should_redirect): ?>
    <script>
        setTimeout(function () {
            window.location.href = "<?php echo esc_url(home_url("/paiement-visa/?request_id={$request_id}")); ?>";
        }, 300);
    </script>
<?php endif; ?>
