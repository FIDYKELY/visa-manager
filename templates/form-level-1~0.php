<?php
// templates/form-level-1.php

defined('ABSPATH') || exit;

$remaining_slots = get_option('visa_daily_limit', 50);
$remaining_slots = get_option('visa_daily_limit', 50);
$requests_today  = Visa_Request_Handler::count_processing_requests_today();
$available       = max($remaining_slots - $requests_today, 0);
$current_datetime = current_time('Y-m-d H:i');

?>

<form method="post" class="visa-form" id="form-level-1">
    <h2>Demande de visa – Étape 1</h2>

    <p>Date et heure actuelles : <strong><?php echo esc_html($current_datetime); ?></strong></p>
    <p>Demandes restantes aujourd’hui : <strong><?php echo esc_html($available); ?></strong></p>

    <label for="email">Adresse e-mail :</label><br>
    <input type="email" name="email" required value="<?php echo esc_attr($_GET['email_attempted'] ?? ''); ?>"><br><br>

    <label for="password">Mot de passe :</label><br>
    <input type="password" name="password" required><br><br>

	<?php if ($available == 0) {
		?>
		<button class="submit-stop" disabled>Demande journalière depassée, merci de faire votre demande demain.</button>
		<?php
	} else {
		?>
		<div class="g-recaptcha" data-sitekey="6LfKYIcrAAAAAOEgVxJCcZyjWcElmx-DN8Hb6V88"></div>
		<button type="submit" name="visa_level1_submit">Valider et poursuivre</button>
		<?php
	};
	?>
    
    <?php if (isset($_GET['login_error']) && $_GET['login_error'] === 'password') : ?>
        <div class="visa-error-message" style="background:#fee;padding:15px;border:1px solid #f99;margin-bottom:20px;margin-top:20px;">
            <strong>Erreur :</strong> Le mot de passe est incorrect.<br>
            <p>Si vous avez oublié votre mot de passe, vous pouvez le réinitialiser en cliquant <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">ici</a>.</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['captcha_error'])) : ?>
        <div class="visa-error-message" style="background:#fee;padding:15px;border:1px solid #f99;margin-bottom:20px;">
            <strong>Erreur :</strong> Le captcha n’a pas été validé. Merci de réessayer.
        </div>
    <?php endif; ?>

    
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
	--vm-text:rgb(40, 48, 54);  /* dark gray-blue */
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
	box-shadow: 0 4px 12px rgba(0,0,0,0.5);
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
	.visa-form input{
		width: 100%;
		border: 1px solid var(--vm-text);
		border-radius: var(--vm-radius);
	}

	.visa-form .required {
	color: var(--vm-accent);
	}


	/* 5. Submit Button */
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

	.submit-stop {
		width: 100%;
		text-align: center;
	}
	}
</style>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
