<?php

function tebuto_save_settings()
{
    $current_user_id = get_current_user_id();

    // Verbindung trennen
    if (isset($_POST['tebuto_disconnect'])) {
        delete_user_meta($current_user_id, 'tebuto_refresh_token');
        delete_user_meta($current_user_id, 'tebuto_access_token');
        delete_user_meta($current_user_id, 'tebuto_therapist_uuid');
        delete_user_meta($current_user_id, 'tebuto_background_color');
        delete_user_meta($current_user_id, 'tebuto_border');
        wp_redirect(admin_url('admin.php?page=tebuto-integration'));
        exit;
    }

    // Speichern der Einstellungen
    if (isset($_POST['tebuto_save_settings'])) {
        $background_color = sanitize_hex_color($_POST['background_color']) ?: '#ffffff';
        $border = isset($_POST['border']) && $_POST['border'] === 'true' ? 'true' : 'false';

        update_user_meta($current_user_id, 'tebuto_background_color', $background_color);
        update_user_meta($current_user_id, 'tebuto_border', $border);
    }
}
add_action('admin_init', 'tebuto_save_settings');
