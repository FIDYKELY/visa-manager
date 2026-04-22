<?php
// templates/form-level-2.php

defined('ABSPATH') || exit;

// Vérifie que l'utilisateur vient du lien d'email
if (!isset($_GET['confirmed']) || $_GET['confirmed'] !== '1') {
    wp_die('Accès refusé. Veuillez utiliser le lien envoyé par email.');
}
$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if (!$request_id || get_post_type($request_id) !== 'visa_request') {
    echo '<p>Identifiant de demande non valide.</p>';
    return;
}

$should_redirect = false;

if ( isset($_POST['visa_level2_submit']) ) {
    // 1) sanitize des inputs
    $type      = sanitize_text_field( $_POST['visa_type']      ?? '' );
    $depot_raw = sanitize_text_field( $_POST['depot_ville']    ?? '' );
    $objet_base = sanitize_text_field( $_POST['info_objet_base']    ?? '' );

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
        update_post_meta( $request_id, 'visa_info_objet_base',   $objet_base );

        $should_redirect = true;
    } else {
        echo '<p>Type de visa invalide.</p>';
    }
}
?>

<h2>Étape 2 – Type de visa</h2>

<form method="post" class="visa-form">
    <input type="hidden" name="request_id" value="<?php echo esc_attr($request_id); ?>" />
    
    <label style="font-size: large;">Merci de fournir, ci-dessous, <strong>des précisions détaillées sur le motif de votre séjour</strong> : tourisme, affaires, visite familiale, études, etc. ainsi que durée, dates, type et lieu, d'hébergement, activités prévues, contacts sur place, etc.</label><br>
	<label style="font-size: large;">Indiquez également vos <strong>liens avec votre pays d’origine</strong> (emploi, situation familiale, logement, études, etc.) ainsi que vos <strong>moyens de subsistance</strong> localement et pendant le séjour (revenus, hébergement, etc.).</label><br><br>
	<label style="font-weight: 900;font-size: large;">Ces éléments avec pièces justificatives sont impératifs et obligatoires pour déterminer le visa adapté et renforcer votre demande.</label><br>
	<textarea name="info_objet_base" required></textarea><br><br>
	
	<div class="form-field">
		<label>Choisissez un type de visa : <span class="required">*</span></label>
		<select name="visa_type" required>
			<option value="">-- Sélectionner --</option>
			<option value="long_sejour">Visa Long Séjour (+ de 90 jours maximum)</option>
			<option value="court_sejour">Visa Court Séjour / VTA (90 jours maximum)</option>
		</select>
	</div>
	
	<div class="form-field">
		<label>Wilaya de résidence : <span class="required">*</span></label>
		<select name="depot_ville" required>
			<option value="">-- Sélectionner votre Wilaya de residence--</option>
				<option value="Alger - 01 - Adrar">01 - Adrar</option>
				<option value="Oran - 02 - Chlef">02 - Chlef</option>
				<option value="Oran - 03 - Laghouat">03 - Laghouat</option>
				<option value="Constantine - 04 - Oum El Bouaghi">04 - Oum El Bouaghi</option>
				<option value="Alger - 05 - Batna">05 - Batna</option>
				<option value="Alger - 06 - Béjaïa">06 - Béjaïa</option>
				<option value="Alger - 07 - Biskra">07 - Biskra</option>
				<option value="Alger - 08 - Béchar">08 - Béchar</option>
				<option value="Alger - 09 - Blida">09 - Blida</option>
				<option value="Alger - 10 - Bouira">10 - Bouira</option>
				<option value="Alger - 11 - Tamanrasset">11 - Tamanrasset</option>
				<option value="Annaba - 12 - Tébessa">12 - Tébessa</option>
				<option value="Oran - 13 - Tlemcen">13 - Tlemcen</option>
				<option value="Oran - 14 - Tiaret">14 - Tiaret</option>
				<option value="Alger - 15 - Tizi Ouzou">15 - Tizi Ouzou</option>
				<option value="Alger - 16 - Alger">16 - Alger</option>
				<option value="Alger - 17 - Djelfa">17 - Djelfa</option>
				<option value="Constantine - 18 - Jijel">18 - Jijel</option>
				<option value="Constantine - 19 - Sétif">19 - Sétif</option>
				<option value="Oran - 20 - Saïda">20 - Saïda</option>
				<option value="Annaba - 21 - Skikda">21 - Skikda</option>
				<option value="Oran - 22 - Sidi Bel Abbès">22 - Sidi Bel Abbès</option>
				<option value="Annaba - 23 - Annaba">23 - Annaba</option>
				<option value="Annaba - 24 - Guelma">24 - Guelma</option>
				<option value="Constantine - 25 - Constantine">25 - Constantine</option>
				<option value="Alger - 26 - Médéa">26 - Médéa</option>
				<option value="Oran - 27 - Mostaganem">27 - Mostaganem</option>
				<option value="Alger - 28 - M’Sila">28 - M’Sila</option>
				<option value="Oran - 29 - Mascara">29 - Mascara</option>
				<option value="Alger - 30 - Ouargla">30 - Ouargla</option>
				<option value="Oran - 31 - Oran">31 - Oran</option>
				<option value="Alger - 32 - El Bayadh">32 - El Bayadh</option>
				<option value="Alger - 33 - Illizi">33 - Illizi</option>
				<option value="Oran - 34 - Relizane">34 - Relizane</option>
				<option value="Alger - 35 - Boumerdès">35 - Boumerdès</option>
				<option value="Annaba - 36 - El Tarf">36 - El Tarf</option>
				<option value="Oran - 37 - Tindouf">36 - El Tarf</option>
				<option value="Oran - 38 - Tissemsilt">36 - El Tarf</option>
				<option value="Oran - 39 - Tindouf">39 - Tindouf</option>
				<option value="Constantine - 40 - Khenchela">40 - Khenchela</option>
				<option value="Annaba - 41 - Souk Ahras">41 - Souk Ahras</option>
				<option value="Alger - 42 - Tipaza">42 - Tipaza</option>
				<option value="Constantine - 43 - Mila">43 - Mila</option>
				<option value="Oran - 44 - Naâma">44 - Naâma</option>
				<option value="Oran - 45 - Aïn Defla">45 - Aïn Defla</option>
				<option value="Oran - 46 - Aïn Témouchent">46 - Aïn Témouchent</option>
				<option value="Alger - 47 - Ghardaïa">47 - Ghardaïa</option>
				<option value="Alger - 49 - El Oued">49 - El Oued</option>
				<option value="Alger - 50 - El M’Ghaier">50 - El M’Ghaier</option>
				<option value="Alger - 51 - El Meniaa">51 - El Meniaa</option>
				<option value="Alger - 52 - Ouled Djellal">52 - Ouled Djellal</option>
				<option value="Alger - 53 - Béni Abbès">53 - Béni Abbès</option>
				<option value="Alger - 54 - Timimoun">54 - Timimoun</option>
				<option value="Alger - 55 - Bordj Badji Mokhtar">55 - Bordj Badji Mokhtar</option>
				<option value="Alger - 56 - Djanet">56 - Djanet</option>
				<option value="Alger - 57 - In Salah">57 - In Salah</option>
				<option value="Alger - 58 - In Guezzam">58 - In Guezzam</option>
		</select>
	</div>

	<div class="form-field" style="font-size: larger;">
		<label style="font-size: large;">Centre de dépôt du dossier : </label>
		<p id="depot"></p>
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

  selectVille.addEventListener('change', function() {
    const parts = this.value.split(' - ');
    depotP.textContent = parts[0] || '';
  });
});
</script>

<?php if ($should_redirect): ?>
    <script>
        setTimeout(function() {
            window.location.href = "<?php echo esc_url(home_url("/formulaire-visa/?request_id={$request_id}")); ?>";
        }, 100);
    </script>
<?php endif; ?>