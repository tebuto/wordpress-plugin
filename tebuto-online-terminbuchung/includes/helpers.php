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

/**
 * Find the WordPress user ID that has a Tebuto connection configured.
 *
 * On the frontend, `get_current_user_id()` returns 0 for unauthenticated
 * visitors. The widget settings (UUID, colors, categories, etc.) are stored
 * as user meta on the admin who configured the plugin. This helper resolves
 * that admin user ID so the shortcode can render for all visitors.
 *
 * @return int Connected user ID, or 0 if no user is connected.
 */
function tebuto_get_connected_user_id(): int {
    $current_user_id = get_current_user_id();

    // If the current user has a therapist UUID, use them directly.
    if ( $current_user_id > 0 ) {
        $uuid = tebuto_get_user_meta( $current_user_id, 'therapist_uuid' );
        if ( ! empty( $uuid ) ) {
            return $current_user_id;
        }
    }

    // Find any WordPress user who has connected to Tebuto.
    $users = get_users( [
        'meta_key'   => TEBUTO_META_PREFIX . 'therapist_uuid',
        'meta_compare' => '!=',
        'meta_value' => '',
        'number'     => 1,
        'fields'     => 'ID',
    ] );

    return ! empty( $users ) ? (int) $users[0] : 0;
}

/**
 * Resolve widget account capabilities (matches Tebuto webapp WidgetThemeConfigurator).
 *
 * @param int|null $user_id WordPress user ID. Defaults to current user.
 * @return array{has_managed_users: bool, is_managing_user: bool}
 */
function tebuto_get_widget_account_capabilities( ?int $user_id = null ): array {
    $defaults = [
        'has_managed_users' => false,
        'is_managing_user'  => false,
    ];

    $user_id = $user_id ?? get_current_user_id();
    if ( $user_id <= 0 ) {
        return $defaults;
    }

    $api = new Tebuto_API( $user_id );
    if ( ! $api->is_connected() ) {
        return $defaults;
    }

    $who_am_i = $api->who_am_i();
    if ( is_wp_error( $who_am_i ) ) {
        return $defaults;
    }

    $is_managing_user  = ! empty( $who_am_i['isMultiUserManager'] );
    $has_managed_users = false;

    if ( $is_managing_user ) {
        $managed = $api->get_managed_users();
        if ( ! is_wp_error( $managed ) && ! empty( $managed['users'] ) && is_array( $managed['users'] ) ) {
            $has_managed_users = count( $managed['users'] ) > 0;
        }
    }

    return [
        'has_managed_users' => $has_managed_users,
        'is_managing_user'  => $is_managing_user,
    ];
}
