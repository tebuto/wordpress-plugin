<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function tebuto_online_terminbuchung_get_base_url()
{
    return 'https://auth.tebuto.de';
}

function tebuto_online_terminbuchung_get_authorize_url()
{
    $base_url = tebuto_online_terminbuchung_get_base_url();
    $auth_url = $base_url . '/realms/tebuto-therapists/protocol/openid-connect/auth';
    $redirect_uri = urlencode(admin_url('admin.php?page=tebuto-integration'));
    $client_id = 'wordpress-plugin';
    $state = wp_create_nonce('tebuto_online_terminbuchung_auth');

    list($code_verifier, $code_challenge) = tebuto_online_terminbuchung_generate_pkce_challenge();
    set_transient('tebuto_online_terminbuchung_pkce_' . get_current_user_id(), $code_verifier, 300);

    return "$auth_url?client_id=$client_id&scope=openid offline_access&response_type=code&redirect_uri=$redirect_uri&state=$state&code_challenge=$code_challenge&code_challenge_method=S256";
}

function tebuto_online_terminbuchung_generate_pkce_challenge()
{
    $code_verifier = wp_generate_password(64, false);
    $code_challenge = rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '=');
    return [$code_verifier, $code_challenge];
}
