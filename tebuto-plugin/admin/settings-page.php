<?php

function tebuto_admin_page()
{
    $current_user_id = get_current_user_id();
    $refresh_token = get_user_meta($current_user_id, 'tebuto_refresh_token', true);

    echo '<div class="wrap">';
    echo '<h1 style="display: flex; justify-content: space-between; align-items: center;">';
    echo esc_html__('Tebuto Online-Terminbuchung', 'tebuto');

    if ($refresh_token) {
        // Verbindung trennen Button oben rechts
        echo '<form method="post" style="margin: 0;">';
        echo '<input type="hidden" name="tebuto_disconnect" value="1">';
        echo '<button class="button button-secondary" type="submit" style="background-color: #dc3545; color: white; border: none;" onclick="return confirm(\'' . esc_js(__('Möchtest du Verbindung wirklich trennen?', 'tebuto')) . '\');">';
        echo esc_html__('Verbindung trennen', 'tebuto');
        echo '</button>';
        echo '</form>';
    }
    echo '</h1>';

    if (!$refresh_token) {
        // Noch nicht verbunden
        $auth_url = tebuto_get_authorize_url();
        echo '<p>' . esc_html__('Du bist derzeit nicht mit Tebuto verbunden. Bitte verwende den Button, um dich mit Tebuto zu verbinden.', 'tebuto') . '</p>';
        echo '<a class="button button-primary" href="' . esc_url($auth_url) . '">' . esc_html__('Mit Tebuto verbinden', 'tebuto') . '</a>';
    } else {
        echo '<div style="align-items: center; gap: 24px; display: flex; margin-top: 40px; max-width: 800px; padding-top: 12px; padding-bottom: 12px; padding-left: 32px; padding-right: 32px; border-radius: 24px; background-color: #009087;">';

        // Bild aus dem Plugin-Ordner, sicher eingebaut mit esc_url()
        echo '<img class="tebuto-icon" style="height: 60px; width: auto;" src="' . esc_url(TEBUTO_PLUGIN_URL . 'assets/tebuto_icon.png') . '" alt="' . esc_attr__('Tebuto Icon', 'tebuto') . '" />';

        echo '<div>';
        echo '<h2 style="color: white; margin-bottom: 0;">';
        echo esc_html__('Mit Tebuto verbunden', 'tebuto');
        echo '</h2>';
        echo '<p style="color: white; margin-top: 8px;">';
        echo esc_html__('Du hast dich erfolgreich mit Tebuto verbunden. Du kannst das Plugin jetzt verwenden.', 'tebuto');
        echo '</p>';
        echo '</div>';
        echo '</div>';

        echo '<h2 style="margin-top: 40px;">' . esc_html__('Deine nächsten Schritte:', 'tebuto') . '</h2>';
        echo '<ol style="list-style-type: decimal; margin-left: 20px;">';
        echo '<li>' . esc_html__('Erstelle deine', 'tebuto') . ' <a href="' . esc_url('https://app.tebuto.de/einstellungen/termine') . '">' . esc_html__('Terminkategorien', 'tebuto') . '</a></li>';
        echo '<li>' . esc_html__('Erstelle ein paar', 'tebuto') . ' <a href="' . esc_url('https://app.tebuto.de/kalender') . '">' . esc_html__('Terminserien oder Einzeltermine', 'tebuto') . '</a></li>';
        echo '<li>' . esc_html__('Verwende den', 'tebuto') . ' <a href="' . esc_url('?page=tebuto-shortcode') . '">' . esc_html__('Shortcode', 'tebuto') . '</a> ' . esc_html__('oder den Tebuto-Gutenberg-Block, um die Terminbuchung auf deiner Website anzuzeigen. Mehr dazu findest du in unserer', 'tebuto') . ' <a href="' . esc_url('https://tebuto.de/dokumentation/oeffentliche-termine') . '">' . esc_html__('Dokumentation', 'tebuto') . '</a>.</li>';
        echo '</ol>';
    }

    echo '</div>';
}
