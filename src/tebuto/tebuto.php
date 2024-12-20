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

// CSS
function tebuto_enqueue_admin_styles() {
    wp_enqueue_style(
        'tebuto-admin-style', 
        plugin_dir_url(__FILE__) . 'admin-style.css'
    );
}
add_action('admin_enqueue_scripts', 'tebuto_enqueue_admin_styles');


// Menüeintrag erstellen
function tebuto_add_admin_menu() {
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

// Admin-Seite für Tebuto
function tebuto_admin_page() {
    // ID abrufen
    $tebuto_id = get_option('tebuto_id');

    if (!$tebuto_id) {
        // Button anzeigen, wenn keine ID vorhanden ist
        $auth_url = 'https://auth.tebuto.de/realms/tebuto-therapists/protocol/openid-connect/auth';
        $redirect_uri = urlencode(admin_url('admin.php?page=tebuto-integration'));
        $state = wp_create_nonce('tebuto_auth');
        $code_challenge = 'L_xkStfJE7ql1dxZEk0rNBXAbYO5icbnI6Wlr9oeRYk';

        $full_auth_url = "$auth_url?client_id=therapists&scope=openid&response_type=code&redirect_uri=$redirect_uri&state=$state&code_challenge=$code_challenge&code_challenge_method=S256";

        echo '<div class="wrap">';
        echo '<h1>Tebuto verbinden</h1>';
        echo '<p>Um Tebuto mit Ihrer Website zu verbinden, klicken Sie auf den Button unten:</p>';
        echo '<a class="button button-primary" href="' . esc_url($full_auth_url) . '">Mit Tebuto verbinden</a>';
        echo '</div>';
    } else {
        // Erfolgreiche Verbindung anzeigen
        echo '<div class="wrap">';
        echo '<h1>Tebuto verbunden</h1>';
        echo '<p>Ihre Website ist erfolgreich mit Tebuto verbunden. Ihre ID lautet: <strong>' . esc_html($tebuto_id) . '</strong></p>';
        echo '</div>';
    }
}

// Callback-Handling nach der OAuth-Anmeldung
function tebuto_handle_oauth_callback() {
    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        return;
    }

    // Sicherheitsprüfung: Validieren Sie den State-Wert
    if (!wp_verify_nonce($_GET['state'], 'tebuto_auth')) {
        wp_die('Ungültiger State-Wert. Authentifizierung fehlgeschlagen.');
    }

    // Den Code verwenden, um Informationen vom Server abzurufen
    $code = sanitize_text_field($_GET['code']);
    $token_url = 'https://therapists.api.tebuto.de/auth/callback';

    $response = wp_remote_post($token_url, [
        'body' => [
            'code' => $code,
            'redirect_uri' => admin_url('admin.php?page=tebuto-integration'),
            'client_id' => 'therapists',
            'grant_type' => 'authorization_code',
        ],
    ]);

    if (is_wp_error($response)) {
        wp_die('Fehler bei der Authentifizierung: ' . $response->get_error_message());
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($response_body['access_token'])) {
        wp_die('Zugriffstoken konnte nicht abgerufen werden.');
    }

    // Zugriffstoken verwenden, um die ID vom Server abzurufen
    $access_token = $response_body['access_token'];
    $whoami_response = wp_remote_get('https://therapists.api.tebuto.de/who-am-i', [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
        ],
    ]);

    if (is_wp_error($whoami_response)) {
        wp_die('Fehler beim Abrufen der Benutzerinformationen: ' . $whoami_response->get_error_message());
    }

    $whoami_body = json_decode(wp_remote_retrieve_body($whoami_response), true);
    if (!isset($whoami_body['id'])) {
        wp_die('Benutzer-ID konnte nicht abgerufen werden.');
    }

    // ID speichern
    update_option('tebuto_id', sanitize_text_field($whoami_body['id']));

    // Redirect zurück zur Admin-Seite
    wp_redirect(admin_url('admin.php?page=tebuto-integration'));
    exit;
}
add_action('admin_init', 'tebuto_handle_oauth_callback');
