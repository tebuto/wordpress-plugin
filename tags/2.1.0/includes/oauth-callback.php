<?php
/**
 * OAuth callback handler for Tebuto plugin.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Handle OAuth callback from Tebuto auth server.
 *
 * @return void
 */
function tebuto_handle_oauth_callback(): void {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (! isset($_GET['page']) || sanitize_text_field(wp_unslash($_GET['page'])) !== 'tebuto-integration') {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (! isset($_GET['code'], $_GET['state'])) {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $state = sanitize_text_field(wp_unslash($_GET['state']));
    if (! wp_verify_nonce($state, 'tebuto_auth')) {
        wp_die(
            esc_html__('Ungültiger State-Wert. Authentifizierung fehlgeschlagen.', 'tebuto-online-terminbuchung'),
            esc_html__('Authentifizierungsfehler', 'tebuto-online-terminbuchung'),
            ['response' => 403]
        );
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $code           = sanitize_text_field(wp_unslash($_GET['code']));
    $token_url      = TEBUTO_AUTH_URL . '/realms/tebuto-therapists/protocol/openid-connect/token';
    $redirect_uri   = admin_url('admin.php?page=tebuto-integration');
    $current_user_id = get_current_user_id();
    $code_verifier   = get_transient('tebuto_pkce_' . $current_user_id);

    if (! $code_verifier) {
        wp_die(
            esc_html__('PKCE Code Verifier nicht gefunden. Authentifizierung fehlgeschlagen.', 'tebuto-online-terminbuchung'),
            esc_html__('Authentifizierungsfehler', 'tebuto-online-terminbuchung'),
            ['response' => 400]
        );
    }

    delete_transient('tebuto_pkce_' . $current_user_id);

    $response = wp_remote_post($token_url, [
        'body' => [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirect_uri,
            'client_id'     => TEBUTO_CLIENT_ID,
            'code_verifier' => $code_verifier,
        ],
        'timeout'   => 30,
        'sslverify' => TEBUTO_SSL_VERIFY,
    ]);

    if (is_wp_error($response)) {
        wp_die(
            esc_html__('Fehler bei der Token-Anfrage.', 'tebuto-online-terminbuchung') . ' ' . esc_html($response->get_error_message()),
            esc_html__('Authentifizierungsfehler', 'tebuto-online-terminbuchung'),
            ['response' => 500]
        );
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (! isset($response_body['access_token'], $response_body['refresh_token'])) {
        wp_die(
            esc_html__('Fehler beim Abrufen der Tokens.', 'tebuto-online-terminbuchung'),
            esc_html__('Authentifizierungsfehler', 'tebuto-online-terminbuchung'),
            ['response' => 500]
        );
    }

    $access_token  = sanitize_text_field($response_body['access_token']);
    $refresh_token = sanitize_text_field($response_body['refresh_token']);

    tebuto_update_user_meta($current_user_id, 'access_token', $access_token);
    tebuto_update_user_meta($current_user_id, 'refresh_token', $refresh_token);

    tebuto_store_therapist_uuid($current_user_id, $access_token);

    // Redirect to dashboard after successful connection
    wp_safe_redirect(admin_url('admin.php?page=tebuto-main&connected=1'));
    exit;
}
add_action('admin_init', 'tebuto_handle_oauth_callback');
