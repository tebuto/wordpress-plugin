<?php

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

function tebuto_handle_oauth_callback()
{
    if (!isset($_GET['page']) || sanitize_text_field(wp_unslash($_GET['page'])) !== 'tebuto-integration') {
        return;
    }

    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        return;
    }

    $state = sanitize_text_field(wp_unslash($_GET['state']));
    if (!wp_verify_nonce($state, 'tebuto_auth')) {
        wp_die(esc_html__('Ungültiger State-Wert. Authentifizierung fehlgeschlagen.', 'tebuto-online-terminbuchung'));
    }

    $code = sanitize_text_field(wp_unslash($_GET['code']));
    $base_url = tebuto_get_base_url();
    $token_url = esc_url_raw($base_url . '/realms/tebuto-therapists/protocol/openid-connect/token');
    $client_id = 'wordpress-plugin';
    $redirect_uri = esc_url_raw(admin_url('admin.php?page=tebuto-integration'));

    $current_user_id = get_current_user_id();
    $code_verifier = sanitize_text_field(get_transient('tebuto_pkce_' . $current_user_id));

    if (!$code_verifier) {
        wp_die(esc_html__('PKCE Code Verifier nicht gefunden. Authentifizierung fehlgeschlagen.', 'tebuto-online-terminbuchung'));
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
        wp_die(esc_html__('Fehler bei der Token-Anfrage.', 'tebuto-online-terminbuchung'));
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($response_body['access_token'], $response_body['refresh_token'])) {
        wp_die(esc_html__('Fehler beim Abrufen der Tokens.', 'tebuto-online-terminbuchung'));
    }

    // Sanitize tokens before saving
    $access_token = sanitize_text_field($response_body['access_token']);
    $refresh_token = sanitize_text_field($response_body['refresh_token']);

    // Store sanitized tokens
    update_user_meta($current_user_id, 'tebuto_access_token', $access_token);
    update_user_meta($current_user_id, 'tebuto_refresh_token', $refresh_token);

    // Retrieve and store the therapist UUID securely
    tebuto_store_therapist_uuid($current_user_id, $access_token);

    wp_safe_redirect(admin_url('admin.php?page=tebuto-integration'));
    exit;
}
add_action('admin_init', 'tebuto_handle_oauth_callback');
