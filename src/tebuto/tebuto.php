<?php
/*
Plugin Name: Tebuto - Online-Terminbuchung für Psychologen und Therapeuten
Description: Dieses Plugin integriert die Online-Terminbuchung von Tebuto in Ihre WordPress-Website.
Version: 1.0
Author: Tebuto GmbH
Author URI: https://tebuto.de?utm_source=wordpress_plugin
*/

if (!defined('ABSPATH')) {
    exit; // Sicherheitsmaßnahme: Verhindert direkten Zugriff auf die Datei.
}

// Umgebungs-URLs definieren
function tebuto_get_base_url()
{
    // TODO: Stelle sicher, dass die Umgebung korrekt ist, bevor du diesen Wert anpasst.
    // Ändere die URL zwischen dev und prod.
    return 'https://auth.tebuto.de';
}

// "Einstellungen"-Button zur Plugin-Seite hinzufügen
function tebuto_add_settings_link($links)
{
    $settings_link = '<a href="' . admin_url('admin.php?page=tebuto-integration') . '">' . __('Einstellungen', 'tebuto') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'tebuto_add_settings_link');

// CSS einbinden
function tebuto_enqueue_admin_styles()
{
    wp_enqueue_style(
        'tebuto-admin-style',
        plugin_dir_url(__FILE__) . 'admin-style.css'
    );
}
add_action('admin_enqueue_scripts', 'tebuto_enqueue_admin_styles');

// Menüeintrag erstellen
function tebuto_add_admin_menu()
{
    add_menu_page(
        'Tebuto - Online-Terminbuchung für Psychologen und Therapeuten',
        'Tebuto',
        'manage_options',
        'tebuto-integration',
        'tebuto_admin_page',
        plugin_dir_url(__FILE__) . 'tebuto_icon.png',
        100
    );
}
add_action('admin_menu', 'tebuto_add_admin_menu');

// PKCE: Code Challenge generieren
function generate_pkce_challenge()
{
    $code_verifier = wp_generate_password(64, false);
    $code_challenge = rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '=');
    return [$code_verifier, $code_challenge];
}

// URL für die Autorisierung erstellen
function tebuto_get_authorize_url()
{
    $base_url = tebuto_get_base_url();
    $auth_url = $base_url . '/realms/tebuto-therapists/protocol/openid-connect/auth';
    $redirect_uri = urlencode(admin_url('admin.php?page=tebuto-integration'));
    $client_id = 'wordpress-plugin';
    $state = wp_create_nonce('tebuto_auth');

    // PKCE Challenge generieren
    list($code_verifier, $code_challenge) = generate_pkce_challenge();
    set_transient('tebuto_pkce_' . get_current_user_id(), $code_verifier, 300); // Speichere Code Verifier für später

    return "$auth_url?client_id=$client_id&scope=openid offline_access&response_type=code&redirect_uri=$redirect_uri&state=$state&code_challenge=$code_challenge&code_challenge_method=S256";
}

// Admin-Seite für Tebuto
function tebuto_admin_page()
{
    $current_user_id = get_current_user_id();
    $refresh_token = get_user_meta($current_user_id, 'tebuto_refresh_token', true);

    echo '<div class="wrap">';
    echo '<h1>' . __('Tebuto - Online-Terminbuchung', 'tebuto') . '</h1>';

    if (!$refresh_token) {
        // Noch keine Verbindung hergestellt
        $auth_url = tebuto_get_authorize_url();
        echo '<p>' . __('Um Tebuto mit Ihrer Website zu verbinden, klicken Sie auf den Button unten:', 'tebuto') . '</p>';
        echo '<a class="button button-primary" href="' . esc_url($auth_url) . '">' . __('Mit Tebuto verbinden', 'tebuto') . '</a>';
    } else {
        // Überprüfen, ob die Authentifizierung noch gültig ist
        $is_authenticated = tebuto_test_authentication_for_user($current_user_id);
        echo '<p>' . ($is_authenticated ? __('Sie sind erfolgreich verbunden.', 'tebuto') : __('Ihre Verbindung ist abgelaufen. Bitte erneut verbinden.', 'tebuto')) . '</p>';

        if ($is_authenticated) {
            // Verbindung testen Button
            echo '<form method="post">';
            echo '<input type="hidden" name="tebuto_test_connection" value="1">';
            echo '<button class="button button-secondary" type="submit">' . __('Verbindung Testen', 'tebuto') . '</button>';
            echo '</form>';
        } else {
            // Erneut Verbinden Button
            $auth_url = tebuto_get_authorize_url();
            echo '<p>' . __('Ihre Verbindung ist abgelaufen. Bitte verbinden Sie sich erneut mit Tebuto.', 'tebuto') . '</p>';
            echo '<a class="button button-primary" href="' . esc_url($auth_url) . '">' . __('Erneut Verbinden', 'tebuto') . '</a>';
        }
    }

    // Testverbindungsergebnis anzeigen
    if (isset($_POST['tebuto_test_connection'])) {
        $test_result = tebuto_test_connection($current_user_id);
        echo '<h2>' . __('Testergebnis:', 'tebuto') . '</h2>';
        if ($test_result['success']) {
            // JSON prettified anzeigen
            $pretty_json = json_encode(json_decode($test_result['response'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            echo '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ddd; border-radius: 5px; white-space: pre-wrap;">' . esc_html($pretty_json) . '</pre>';
        } else {
            echo '<p style="color: red;">' . esc_html($test_result['error']) . '</p>';
        }
    }

    echo '</div>';
}

// Verbindung testen
function tebuto_test_connection($user_id)
{
    $access_token = get_user_meta($user_id, 'tebuto_access_token', true);
    if (!$access_token) {
        return [
            'success' => false,
            'error' => __('Kein Access Token vorhanden. Verbindung ist abgelaufen.', 'tebuto'),
        ];
    }

    $whoami_url = 'https://therapists.api.tebuto.de/who-am-i';
    $response = wp_remote_get($whoami_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
        ],
    ]);

    if (is_wp_error($response)) {
        return [
            'success' => false,
            'error' => $response->get_error_message(),
        ];
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($response_code === 200) {
        return [
            'success' => true,
            'response' => $response_body,
        ];
    } else {
        return [
            'success' => false,
            'error' => sprintf(__('HTTP Fehler: %d - %s', 'tebuto'), $response_code, $response_body),
        ];
    }
}


function tebuto_handle_oauth_callback()
{
    // Überprüfen, ob wir auf der richtigen Seite sind
    if (!isset($_GET['page']) || $_GET['page'] !== 'tebuto-integration') {
        return; // Führe den Code nicht aus, wenn es sich nicht um unsere Seite handelt
    }

    // Überprüfen, ob Code und State vorhanden sind
    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        error_log('Fehler: Code oder State fehlen in der Callback-URL.');
        return;
    }

    if (!wp_verify_nonce($_GET['state'], 'tebuto_auth')) {
        error_log('Fehler: Ungültiger State-Wert.');
        wp_die('Ungültiger State-Wert. Authentifizierung fehlgeschlagen.');
    }

    $code = sanitize_text_field($_GET['code']);
    $base_url = tebuto_get_base_url();
    $token_url = $base_url . '/realms/tebuto-therapists/protocol/openid-connect/token';
    $client_id = 'wordpress-plugin';
    $redirect_uri = admin_url('admin.php?page=tebuto-integration');

    // Hole Code Verifier aus der Sitzung
    $current_user_id = get_current_user_id();
    $code_verifier = get_transient('tebuto_pkce_' . $current_user_id);

    if (!$code_verifier) {
        error_log('Fehler: PKCE Code Verifier nicht gefunden.');
        wp_die('PKCE Code Verifier nicht gefunden. Authentifizierung fehlgeschlagen.');
    }

    // Token abrufen
    $response = wp_remote_post($token_url, [

        'body' => [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirect_uri,
            'client_id' => $client_id,
            'code_verifier' => $code_verifier,
        ],
    ]);

    // Detaillierte Fehlerbehandlung
    if (is_wp_error($response)) {
        error_log('Fehler bei der Token-Anfrage: ' . $response->get_error_message());

        // Zusätzliche Debugging-Informationen
        $debug_data = $response->get_error_data();
        if ($debug_data) {
            error_log('Fehlerdaten: ' . print_r($debug_data, true));
        }

        // cURL-Debugging aktivieren (falls cURL genutzt wird)
        if (function_exists('curl_version')) {
            $curl_version = curl_version();
            error_log('cURL-Version: ' . $curl_version['version']);
            error_log('cURL-SSL-Version: ' . $curl_version['ssl_version']);
        }

        wp_die('Fehler bei der Token-Anfrage: ' . $response->get_error_message());
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($response_code !== 200) {
        error_log("HTTP-Statuscode: $response_code");
        error_log('Antwortinhalt: ' . $response_body);

        // Zusätzliche Debugging-Informationen für HTTP-Fehler
        $request_args = [
            'Token URL' => $token_url,
            'Request Body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirect_uri,
                'client_id' => $client_id,
                'code_verifier' => $code_verifier,
            ],
        ];
        error_log('Anfrageinformationen: ' . print_r($request_args, true));

        wp_die('Fehler bei der Token-Anfrage: ' . $response_body);
    }

    $response_data = json_decode($response_body, true);
    if (!isset($response_data['refresh_token'])) {
        error_log('Fehler: Refresh Token konnte nicht abgerufen werden.');
        wp_die('Refresh Token konnte nicht abgerufen werden.');
    }

    // Tokens speichern
    update_user_meta($current_user_id, 'tebuto_refresh_token', $response_data['refresh_token']);
    update_user_meta($current_user_id, 'tebuto_access_token', $response_data['access_token']);

    wp_redirect(admin_url('admin.php?page=tebuto-integration'));
    exit;
}
add_action('admin_init', 'tebuto_handle_oauth_callback');

// Authentifizierung testen
function tebuto_test_authentication_for_user($user_id)
{
    $access_token = get_user_meta($user_id, 'tebuto_access_token', true);
    if (!$access_token) {
        error_log('Fehler: Kein Access Token vorhanden.');
        return false;
    }

    $base_url = tebuto_get_base_url();
    $whoami_url = $base_url . '/realms/tebuto-therapists/protocol/openid-connect/userinfo';

    $response = wp_remote_get($whoami_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
        ],
    ]);

    if (is_wp_error($response)) {
        error_log('Fehler beim Abrufen der Benutzerinformationen: ' . $response->get_error_message());
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        error_log("HTTP-Statuscode beim Abrufen der Benutzerinformationen: $response_code");
        error_log('Antwortinhalt: ' . wp_remote_retrieve_body($response));
        return false;
    }

    error_log('Benutzerinformationen erfolgreich abgerufen: ' . wp_remote_retrieve_body($response));
    return true;
}
