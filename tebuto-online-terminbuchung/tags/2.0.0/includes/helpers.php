<?php
/**
 * Helper functions for Tebuto plugin.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Get Tebuto OAuth authorization URL.
 *
 * @return string Authorization URL.
 */
function tebuto_get_authorize_url(): string {
    $auth_url     = TEBUTO_AUTH_URL . '/realms/tebuto-therapists/protocol/openid-connect/auth';
    $redirect_uri = admin_url('admin.php?page=tebuto-integration');
    $state        = wp_create_nonce('tebuto_auth');

    list($code_verifier, $code_challenge) = tebuto_generate_pkce_challenge();
    set_transient('tebuto_pkce_' . get_current_user_id(), $code_verifier, 300);

    $params = [
        'client_id'             => TEBUTO_CLIENT_ID,
        'scope'                 => 'openid offline_access',
        'response_type'         => 'code',
        'redirect_uri'          => $redirect_uri,
        'state'                 => $state,
        'code_challenge'        => $code_challenge,
        'code_challenge_method' => 'S256',
    ];

    return $auth_url . '?' . http_build_query($params);
}

/**
 * Generate PKCE code verifier and challenge.
 *
 * @return array Array containing [code_verifier, code_challenge].
 */
function tebuto_generate_pkce_challenge(): array {
    $code_verifier  = wp_generate_password(64, false);
    $hash           = hash('sha256', $code_verifier, true);
    $code_challenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

    return [$code_verifier, $code_challenge];
}

/**
 * Get user meta value with Tebuto prefix.
 *
 * @param int    $user_id  User ID.
 * @param string $key      Meta key without prefix.
 * @param mixed  $default  Default value if not found.
 * @return mixed Meta value or default.
 */
function tebuto_get_user_meta(int $user_id, string $key, $default = '') {
    $value = get_user_meta($user_id, TEBUTO_META_PREFIX . $key, true);
    return $value !== '' ? $value : $default;
}

/**
 * Update user meta value with Tebuto prefix.
 *
 * @param int    $user_id User ID.
 * @param string $key     Meta key without prefix.
 * @param mixed  $value   Meta value.
 * @return bool|int Meta ID on success, false on failure.
 */
function tebuto_update_user_meta(int $user_id, string $key, $value) {
    return update_user_meta($user_id, TEBUTO_META_PREFIX . $key, $value);
}

/**
 * Delete user meta value with Tebuto prefix.
 *
 * @param int    $user_id User ID.
 * @param string $key     Meta key without prefix.
 * @return bool True on success, false on failure.
 */
function tebuto_delete_user_meta(int $user_id, string $key): bool {
    return delete_user_meta($user_id, TEBUTO_META_PREFIX . $key);
}

/**
 * Check if current user is connected to Tebuto.
 *
 * @return bool True if connected, false otherwise.
 */
function tebuto_is_connected(): bool {
    $refresh_token = tebuto_get_user_meta(get_current_user_id(), 'refresh_token');
    return ! empty($refresh_token);
}

/**
 * Get therapist UUID for current user.
 *
 * @return string Therapist UUID or empty string.
 */
function tebuto_get_therapist_uuid(): string {
    return tebuto_get_user_meta(get_current_user_id(), 'therapist_uuid');
}
