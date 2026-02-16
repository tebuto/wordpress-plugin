<?php
/**
 * Therapist data storage for Tebuto plugin.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Fetch and store therapist data from Tebuto API.
 *
 * @param int    $user_id      WordPress user ID.
 * @param string $access_token OAuth access token.
 * @return bool True on success, false on failure.
 */
function tebuto_store_therapist_uuid(int $user_id, string $access_token): bool {
    $whoami_url = TEBUTO_API_URL . '/who-am-i';

    $response = wp_remote_get($whoami_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
        ],
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        return false;
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (! isset($response_body['therapists'][0]['therapist'])) {
        return false;
    }

    $therapist = $response_body['therapists'][0]['therapist'];

    // Store UUID for widget
    if (isset($therapist['uuid'])) {
        $uuid = sanitize_text_field($therapist['uuid']);
        tebuto_update_user_meta($user_id, 'therapist_uuid', $uuid);
    }

    // Store ID for API calls
    if (isset($therapist['id'])) {
        $id = absint($therapist['id']);
        tebuto_update_user_meta($user_id, 'therapist_id', $id);
    }

    // Store therapist name
    if (isset($therapist['name'])) {
        $name = sanitize_text_field($therapist['name']);
        tebuto_update_user_meta($user_id, 'therapist_name', $name);
    }

    return true;
}
