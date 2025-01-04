<?php

function tebuto_handle_oauth_callback()
{
    if (!isset($_GET['page']) || $_GET['page'] !== 'tebuto-integration') {
        return;
    }

    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        return;
    }

    if (!wp_verify_nonce($_GET['state'], 'tebuto_auth')) {
        wp_die('Ungültiger State-Wert. Authentifizierung fehlgeschlagen.');
    }

    $code = sanitize_text_field($_GET['code']);
    $base_url = tebuto_get_base_url();
    $token_url = $base_url . '/realms/tebuto-therapists/protocol/openid-connect/token';
    $client_id = 'wordpress-plugin';
    $redirect_uri = admin_url('admin.php?page=tebuto-integration');

    $current_user_id = get_current_user_id();
    $code_verifier = get_transient('tebuto_pkce_' . $current_user_id);

    if (!$code_verifier) {
        wp_die('PKCE Code Verifier nicht gefunden. Authentifizierung fehlgeschlagen.');
    }

    $response = wp_remote_post($token_url, [
        'body' => [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirect_uri,
            'client_id' => $client_id,
            'code_verifier' => $code_verifier,
        ],
    ]);

    if (is_wp_error($response)) {
        wp_die('Fehler bei der Token-Anfrage.');
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($response_body['access_token'], $response_body['refresh_token'])) {
        wp_die('Fehler beim Abrufen der Tokens.');
    }

    // Speichere Tokens
    update_user_meta($current_user_id, 'tebuto_access_token', $response_body['access_token']);
    update_user_meta($current_user_id, 'tebuto_refresh_token', $response_body['refresh_token']);

    // Abrufen der UUID
    tebuto_store_therapist_uuid($current_user_id, $response_body['access_token']);

    wp_redirect(admin_url('admin.php?page=tebuto-integration'));
    exit;
}
add_action('admin_init', 'tebuto_handle_oauth_callback');
