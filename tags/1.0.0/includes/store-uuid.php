<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function tebuto_online_terminbuchung_store_therapist_uuid($user_id, $access_token)
{
    $whoami_url = 'https://therapists.api.tebuto.de/who-am-i';
    $response = wp_remote_get($whoami_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
        ],
    ]);

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($response_body['therapists'][0]['therapist']['uuid'])) {
            update_user_meta($user_id, 'tebuto_online_terminbuchung_therapist_uuid', sanitize_text_field($response_body['therapists'][0]['therapist']['uuid']));
        }
    }
}
