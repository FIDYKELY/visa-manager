<?php
// templates/form-level-4.php

defined('ABSPATH') || exit;

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
$visa_type = get_post_meta($request_id, 'visa_type', true);

$confirmed = get_post_meta($request_id, 'visa_confirmed', true);
if ($confirmed) {
	echo '<div class="visa-already-processed" style="padding:1.2em;border:1px solid #e0e0e0;background:#fffdf6;max-width:720px;">';
	echo '<h3 style="margin-top:0;color:#333;">Demande déjà traitée</h3>';
	echo '<p>Cette demande a déjà été traitée et le paiement a été confirmé. Pour soumettre une nouvelle demande, merci de créer un nouveau formulaire avec un identifiant différent.</p>';
	echo '<p><a class="button" href="' . esc_url(home_url('/demande-de-visa/')) . '">Créer une nouvelle demande</a></p>';
	echo '</div>';
	return;
}

$saved_doc_requis = get_post_meta($request_id, 'visa_doc_requis', true);
$should_redirect = false; 
if (!$request_id || !$visa_type) {
    echo '<p>Demande introuvable ou type de visa non spécifié.</p>';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visa_level4_submit'])) {
    $request_id = intval($_POST['request_id'] ?? 0);
    if (!$request_id) {
        wp_die('ID invalide');
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';

    // Liste des champs à gérer
    $fields = [
        'contrat_travail',
        'attestation_conge',
        'fiche_de_paie',
        'releve_bancaire',
        'preuve_de_propriété',
        'contrat_location',
        'attestation_scolarite_enfant',
        'justificatif_inscription_universitaire',
        'lettre_mission_professionnelle',
        'engagement_retour',
        'preuve_responsabilités_familiales',
        'attestation_mariage_restees_au_pays',
        'preuve_activite_eco',
        'attestation_bancaire',
        'engagement_honneur_de_retour',
        'reservation_billet_retour',
        'preuve_participation',
        'lettre_organisme_accueil',
        'preuve_prec_conformite',
        'casier_vierge'
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($request_id, $field, sanitize_text_field($_POST[$field]));
        }

        $file_key = $field . '_doc';
        if (!empty($_FILES[$file_key]['name']) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $file = [
                'name'     => $_FILES[$file_key]['name'],
                'type'     => $_FILES[$file_key]['type'],
                'tmp_name' => $_FILES[$file_key]['tmp_name'],
                'error'    => $_FILES[$file_key]['error'],
                'size'     => $_FILES[$file_key]['size'],
            ];
            $upload = wp_handle_upload($file, ['test_form' => false]);
            if (!isset($upload['error'])) {
                update_post_meta($request_id, $file_key, $upload['url']);
            }
        }
    }

    // ðÂÂÂ Récupérer les données nécessaires
    $visa_objet = get_post_meta($request_id, 'visa_objet', true);
    $documents_officiels = visa_get_required_documents_list($visa_objet);

    $documents_risque = [];
    foreach ($fields as $field) {
        $choice = get_post_meta($request_id, $field, true);
        $file_url = get_post_meta($request_id, $field . '_doc', true);
        $documents_risque[$field] = [
            'name'     => $field,
            'choice'   => $choice,
            'file_url' => $file_url
        ];
    }

    $all_meta = get_post_meta($request_id);
    if (!empty($all_meta)) {
        foreach ($all_meta as $key => $values) {
            $all_meta[$key] = (count($values) === 1) ? maybe_unserialize($values[0]) : array_map('maybe_unserialize', $values);
        }
    }

    // ðÂÂÂ Payload corrigé (UN SEUL bloc)
    $payload = [
        'request_id'          => $request_id,
        'visa_type'           => $visa_type,
        'documents_officiels' => $documents_officiels,
        'documents_risque'    => $documents_risque,
        'all_meta'            => $all_meta
    ];

    // Envoi au webhook n8n
    $webhook_url = 'https://n8n.joel-stephanas.com/webhook/6bbfa4ef-119c-42a9-974c-a82b2aee5a49';

    $response = wp_remote_post($webhook_url, [
        'timeout'  => 30,
        'headers'  => ['Content-Type' => 'application/json; charset=utf-8'],
        'body'     => wp_json_encode($payload),
    ]);

    if (is_wp_error($response)) {
        error_log('Erreur envoi webhook visa: ' . $response->get_error_message());
        die('Erreur envoi webhook visa: ' . $response->get_error_message());
    }

    $should_redirect = true;
}
?>

<h2>Étape 4 – Justificatifs "éventuels et non obligatoire" à fournir pour réduire les motifs de rejet</h2>

<form method="post" enctype="multipart/form-data" class="visa-form" autocomplete="off">
    <input type="hidden" name="request_id" value="<?php echo esc_attr($request_id); ?>" />
	<input type="hidden" name="max_days" id ="max_days" value="<?php echo esc_attr($shortstay_max); ?>" />

	<div>
		<label>Contrat de travail en cours</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="contrat_travail" value="non" required> Non</div>
			<div><input type="radio" name="contrat_travail" value="oui" required> Oui</div>
		</div>
		<input type="file" name="contrat_travail_doc" accept=".pdf,.jpg,.jpeg,.doc,.docx" />
	</div>

	<div>
		<label>Attestation de congé temporaire signé par l’employeur</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="attestation_conge" value="non" required> Non</div>
			<div><input type="radio" name="attestation_conge" value="oui" required> Oui</div>
		</div>
		<input type="file" name="attestation_conge_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Fiches de paie récentes (3 à 6 mois)</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="fiche_de_paie" value="non" required> Non</div>
			<div><input type="radio" name="fiche_de_paie" value="oui" required> Oui</div>
		</div>
		<input type="file" name="fiche_de_paie_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Relevés bancaires indiquant des versements réguliers</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="releve_bancaire" value="non" required> Non</div>
			<div><input type="radio" name="releve_bancaire" value="oui" required> Oui</div>
		</div>
		<input type="file" name="releve_bancaire_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Preuve de propriété</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="preuve_de_propriété" value="non" required> Non</div>
			<div><input type="radio" name="preuve_de_propriété" value="oui" required> Oui</div>
		</div>
		<input type="file" name="preuve_de_propriété_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Contrat de location longue durée à votre nom</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="contrat_location" value="non" required> Non</div>
			<div><input type="radio" name="contrat_location" value="oui" required> Oui</div>
		</div>
		<input type="file" name="contrat_location_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Attestation de scolarité pour vous ou vos enfants (si applicable)</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="attestation_scolarite_enfant" value="non" required> Non</div>
			<div><input type="radio" name="attestation_scolarite_enfant" value="oui" required> Oui</div>
		</div>
		<input type="file" name="attestation_scolarite_enfant_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Justificatif d’inscription universitaire (si étudiant dans le pays d’origine)</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="justificatif_inscription_universitaire" value="non" required> Non</div>
			<div><input type="radio" name="justificatif_inscription_universitaire" value="oui" required> Oui</div>
		</div>
		<input type="file" name="justificatif_inscription_universitaire_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Lettre de mission professionnelle (déplacement temporaire uniquement)</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="lettre_mission_professionnelle" value="non" required> Non</div>
			<div><input type="radio" name="lettre_mission_professionnelle" value="oui" required> Oui</div>
		</div>
		<input type="file" name="lettre_mission_professionnelle_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Preuve de responsabilités familiales (enfants, parents âgés à charge)</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="preuve_responsabilités_familiales" value="non" required> Non</div>
			<div><input type="radio" name="preuve_responsabilités_familiales" value="oui" required> Oui</div>
		</div>
		<input type="file" name="preuve_responsabilités_familiales_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Attestation de mariage ou de lien familial avec des personnes restées au pays</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="attestation_mariage_restees_au_pays" value="non" required> Non</div>
			<div><input type="radio" name="attestation_mariage_restees_au_pays" value="oui" required> Oui</div>
		</div>
		<input type="file" name="attestation_mariage_restees_au_pays_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Preuve d’activités économiques (registre du commerce, patente, déclaration fiscale)</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="preuve_activite_eco" value="non" required> Non</div>
			<div><input type="radio" name="preuve_activite_eco" value="oui" required> Oui</div>
		</div>
		<input type="file" name="preuve_activite_eco_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Attestation bancaire de placements à long terme</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="attestation_bancaire" value="non" required> Non</div>
			<div><input type="radio" name="attestation_bancaire" value="oui" required> Oui</div>
		</div>
		<input type="file" name="attestation_bancaire_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Engagement sur l’honneur de retour signé</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="engagement_honneur_de_retour" value="non" required> Non</div>
			<div><input type="radio" name="engagement_honneur_de_retour" value="oui" required> Oui</div>
		</div>
		<input type="file" name="engagement_honneur_de_retour_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Réservation de billet retour (avec justification du programme de séjour)</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="reservation_billet_retour" value="non" required> Non</div>
			<div><input type="radio" name="reservation_billet_retour" value="oui" required> Oui</div>
		</div>
		<input type="file" name="reservation_billet_retour_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Preuve de participation à un programme officiel temporaire (stage, formation, etc.)</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="preuve_participation" value="non" required> Non</div>
			<div><input type="radio" name="preuve_participation" value="oui" required> Oui</div>
		</div>
		<input type="file" name="preuve_participation_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Lettre de l’organisme d’accueil précisant la durée strictement limitée du séjour</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="lettre_organisme_accueil" value="non" required> Non</div>
			<div><input type="radio" name="lettre_organisme_accueil" value="oui" required> Oui</div>
		</div>
		<input type="file" name="lettre_organisme_accueil_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Preuve de précédente conformité aux visas accordés (sortie dans les délais) </label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="preuve_prec_conformite" value="non" required> Non</div>
			<div><input type="radio" name="preuve_prec_conformite" value="oui" required> Oui</div>
		</div>
		<input type="file" name="preuve_prec_conformite_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

	<div>
		<label>Casier judiciaire vierge (pour rassurer sur la régularité du comportement)</label>
		<div style="display:flex;gap: 20px;">
			<div><input type="radio" name="casier_vierge" value="non" required> Non</div>
			<div><input type="radio" name="casier_vierge" value="oui" required> Oui</div>
		</div>
		<input type="file" name="casier_vierge_doc" accept=".pdf,.jpg,.jpeg,.doc.docx" />
	</div>

    <button type="submit" name="visa_level4_submit">Continuer</button>
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
	max-width: 70%;
	margin: 2em auto;
	padding: 2em;
	background: #FFF;
	border-radius: var(--vm-radius);
	box-shadow: 0 4px 12px rgba(0,0,0,0.05);
	font-family: var(--vm-font);
	color: var(--vm-text);
	}

	.visa-form div {
	margin-bottom: 1.5rem;
	}

	.visa-form label {
	display: block;
	font-weight: bold;
	margin-bottom: 0.5rem;
	color: #333;
	}

	.visa-form input[type="radio"] {
	margin-right: 0.5rem;
	}

	.visa-form input[type="file"] {
	margin-top: 0.5rem;
	display: block;
	width: 100%;
	padding: 0.4rem;
	border: 1px solid #ccc;
	border-radius: 4px;
	background-color: #fff;
	}

	.visa-form .radio-group {
	display: flex;
	gap: 1.5rem;
	margin-bottom: 0.5rem;
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

	/* Documents */
	.documents-section {
	border: 1px solid #ddd;
	padding: 1.5rem;
	border-radius: 6px;
	background: #fff;
	max-width: 600px;
	margin: 2rem auto;
	font-family: sans-serif;
	}

	.documents-section legend {
	font-weight: 600;
	margin-bottom: 1rem;
	font-size: 1.05rem;
	}

	#documents-list {
	display: flex;
	flex-direction: column;
	gap: 0.75rem;
	margin-bottom: 1rem;
	}

	.document-item {
	display: flex;
	align-items: center;
	gap: 0.5rem;
	}

	.document-item input[type="file"] {
	flex: 1;
	padding: 0.4rem;
	border: 1px solid #ccc;
	border-radius: 4px;
	background: #fafafa;
	cursor: pointer;
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
	
	.documents-section {
	border: 1px solid #ddd;
	padding: 1.5rem;
	border-radius: 6px;
	background: #fff;
	max-width: 600px;
	margin: 2rem auto;
	font-family: sans-serif;
	}

	.documents-section legend {
	font-weight: 600;
	margin-bottom: 1rem;
	font-size: 1.05rem;
	}

	#documents-list {
	display: flex;
	flex-direction: column;
	gap: 0.75rem;
	margin-bottom: 1rem;
	}

	.document-item {
	display: flex;
	align-items: center;
	gap: 0.5rem;
	}

	.document-item input[type="file"] {
	flex: 1;
	padding: 0.4rem;
	border: 1px solid #ccc;
	border-radius: 4px;
	background: #fafafa;
	cursor: pointer;
	}
</style>

<script>
	document.addEventListener("DOMContentLoaded", function () {
		const form = document.querySelector(".visa-form");

		if (!form) return;

		// Pour chaque groupe de radio
		form.querySelectorAll("div").forEach(group => {
			const radios = group.querySelectorAll('input[type="radio"]');
			const fileInput = group.querySelector('input[type="file"]');

			if (radios.length === 2 && fileInput) {
			// Masquer le champ fichier par défaut
			fileInput.style.display = "none";
			fileInput.required = false;

			radios.forEach(radio => {
				radio.addEventListener("change", function () {
				if (radio.value === "oui") {
					fileInput.style.display = "block";
					fileInput.required = true;
				} else {
					fileInput.style.display = "none";
					fileInput.required = false;
					fileInput.value = ""; // reset si "Non" est décoché
				}
				});
			});
			}
		});
	});
</script>

<?php if ($should_redirect): ?>
    <script>
        setTimeout(function () {
            window.location.href = "<?php echo esc_url(home_url("/mandat-visa/?request_id={$request_id}")); ?>";
        }, 300);
    </script>
<?php endif; ?>