<?php
/* Template Name: Attente Analyse */
defined('ABSPATH') || exit;

$request_id = intval($_GET['request_id'] ?? 0);
$confirmed  = sanitize_text_field($_GET['confirmed'] ?? '');
$redirect_url = home_url("/type-visa/?request_id={$request_id}&confirmed={$confirmed}");
?>
<div class="attente">
    <h2>Analyse en cours...</h2>
    <p style="color:black">Merci de patienter pendant que notre système analyse attentivement vos informations.</p>
    <div id="countdown" style="font-size:3em;margin:20px 0;">00:20</div>
    <div style="width:300px;height:10px;background:rgba(255,255,255,0.3);border-radius:5px;overflow:hidden;">
        <div id="progress" style="height:100%;width:0;background:#004494;"></div>
    </div>
</div>

<style>
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

    .attente {
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        font-family:sans-serif;
        max-width: 600px;
        margin: 2em auto;
        padding: 2em;
        background: #FFF;
        border-radius: var(--vm-radius);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        font-family: var(--vm-font);
        color: var(--vm-text);
	}
</style>

<script>
let total = 20;
let elapsed = 0;
const countdown = document.getElementById('countdown');
const progress = document.getElementById('progress');

const timer = setInterval(() => {
    elapsed++;
    let remaining = total - elapsed;
    let min = String(Math.floor(remaining / 60)).padStart(2, '0');
    let sec = String(remaining % 60).padStart(2, '0');
    countdown.textContent = `${min}:${sec}`;
    progress.style.width = ((elapsed / total) * 100) + '%';

    if (remaining <= 0) {
        clearInterval(timer);
        window.location.href = "<?php echo $redirect_url; ?>";
    }
}, 1000);
</script>