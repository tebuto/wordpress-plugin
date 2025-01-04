<?php

function tebuto_admin_page()
{
    $current_user_id = get_current_user_id();
    $refresh_token = get_user_meta($current_user_id, 'tebuto_refresh_token', true);

    echo '<div class="wrap">';
    echo '<h1 style="display: flex; justify-content: space-between; align-items: center;">';
    echo __('Einstellungen - Tebuto', 'tebuto');
    if ($refresh_token) {
        // Verbindung trennen Button oben rechts
        echo '<form method="post" style="margin: 0;">';
        echo '<input type="hidden" name="tebuto_disconnect" value="1">';
        echo '<button class="button button-secondary" type="submit" style="background-color: #dc3545; color: white; border: none;" onclick="return confirm(\'' . __('Möchten Sie die Verbindung wirklich trennen?', 'tebuto') . '\');">';
        echo __('Verbindung trennen', 'tebuto');
        echo '</button>';
        echo '</form>';
    }
    echo '</h1>';

    if (!$refresh_token) {
        // Noch nicht verbunden
        $auth_url = tebuto_get_authorize_url();
        echo '<p>' . __('Sie sind derzeit nicht mit Tebuto verbunden. Bitte verwenden Sie den Button, um sich mit Tebuto zu verbinden.', 'tebuto') . '</p>';
        echo '<a class="button button-primary" href="' . esc_url($auth_url) . '">' . __('Mit Tebuto verbinden', 'tebuto') . '</a>';
    } else {
        // Widget-Einstellungen anzeigen
        $background_color = get_user_meta($current_user_id, 'tebuto_background_color', true) ?: '#ffffff';
        $border = get_user_meta($current_user_id, 'tebuto_border', true) ?: 'false';

        echo '<form method="post">';
        echo '<h2>' . __('Widget-Einstellungen', 'tebuto') . '</h2>';
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="background_color">' . __('Hintergrundfarbe', 'tebuto') . '</label></th>';
        echo '<td><input type="text" name="background_color" id="background_color" value="' . esc_attr($background_color) . '" class="tebuto-color-picker"></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th><label for="border">' . __('Rahmen anzeigen', 'tebuto') . '</label></th>';
        echo '<td><input type="checkbox" name="border" id="border" value="true" ' . checked($border, 'true', false) . '></td>';
        echo '</tr>';
        echo '</table>';
        echo '<input type="hidden" name="tebuto_save_settings" value="1">';
        echo '<button class="button button-primary" type="submit">' . __('Speichern', 'tebuto') . '</button>';
        echo '</form>';
    }

    echo '</div>';
}
