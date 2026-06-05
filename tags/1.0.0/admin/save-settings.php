<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function tebuto_online_terminbuchung_save_settings()
{
    $current_user_id = get_current_user_id();

    // Verbindung trennen
    if (isset($_POST['tebuto_online_terminbuchung_disconnect'])) {

        // Entschärfen und Verifizieren des Nonces
        $tebuto_online_terminbuchung_nonce = isset($_POST['tebuto_online_terminbuchung_nonce']) ? sanitize_text_field(wp_unslash($_POST['tebuto_online_terminbuchung_nonce'])) : '';
        if (!isset($tebuto_online_terminbuchung_nonce) || !wp_verify_nonce($tebuto_online_terminbuchung_nonce, 'tebuto_online_terminbuchung_disconnect')) {
            wp_die(esc_html__('Ungültige Anfrage. Bitte versuche es erneut.', 'tebuto-online-terminbuchung'));
        }

        // Safely delete user meta
        delete_user_meta($current_user_id, 'tebuto_online_terminbuchung_refresh_token');
        delete_user_meta($current_user_id, 'tebuto_online_terminbuchung_access_token');
        delete_user_meta($current_user_id, 'tebuto_online_terminbuchung_therapist_uuid');
        delete_user_meta($current_user_id, 'tebuto_online_terminbuchung_background_color');
        delete_user_meta($current_user_id, 'tebuto_online_terminbuchung_border');

        // Safe redirection
        wp_safe_redirect(admin_url('admin.php?page=tebuto-integration'));
        exit;
    }

    // Speichern der Einstellungen
    if (isset($_POST['tebuto_online_terminbuchung_save_settings'])) {

        // Entschärfen und Verifizieren des Nonces
        $tebuto_online_terminbuchung_nonce = isset($_POST['tebuto_online_terminbuchung_nonce']) ? sanitize_text_field(wp_unslash($_POST['tebuto_online_terminbuchung_nonce'])) : '';
        if (!isset($tebuto_online_terminbuchung_nonce) || !wp_verify_nonce($tebuto_online_terminbuchung_nonce, 'tebuto_online_terminbuchung_save_settings')) {
            wp_die(esc_html__('Ungültige Anfrage. Bitte versuche es erneut.', 'tebuto-online-terminbuchung'));
        }

        // Sanitize und validiere Eingabewerte
        $background_color = isset($_POST['background_color']) ? sanitize_hex_color(wp_unslash($_POST['background_color'])) : '#ffffff'; // wp_unslash()
        $border = isset($_POST['border']) && $_POST['border'] === 'true' ? 'true' : 'false';

        // Safely update user meta
        update_user_meta($current_user_id, 'tebuto_online_terminbuchung_background_color', $background_color);
        update_user_meta($current_user_id, 'tebuto_online_terminbuchung_border', $border);

        // Safe redirection after saving settings
        wp_safe_redirect(admin_url('admin.php?page=tebuto-shortcode'));
        exit;
    }
}
add_action('admin_init', 'tebuto_online_terminbuchung_save_settings');
