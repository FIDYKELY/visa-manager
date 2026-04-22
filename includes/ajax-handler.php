<?php
if (
  ! current_user_can('edit_post', $_POST['post_id'] ?? 0) ||
  ! wp_verify_nonce($_POST['nonce'] ?? '', 'cerfa_nonce')
) {
  wp_send_json_error('Accès non autorisé');
}

if (! isset($_FILES['pdf'])) {
  wp_send_json_error('Fichier PDF manquant');
}

$uploaded = wp_handle_upload($_FILES['pdf'], ['test_form' => false]);
if (isset($uploaded['error'])) {
  wp_send_json_error($uploaded['error']);
}

$post_id = absint($_POST['post_id']);
$docs = get_post_meta($post_id, 'visa_documents', true);
$docs = is_array($docs) ? $docs : [];
$docs[] = $uploaded['url'];
update_post_meta($post_id, 'visa_documents', $docs);

wp_send_json_success(['url' => $uploaded['url']]);