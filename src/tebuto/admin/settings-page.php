<?php

function tebuto_admin_page()
{
    $current_user_id = get_current_user_id();
    $refresh_token = get_user_meta($current_user_id, 'tebuto_refresh_token', true);

    echo '<div class="wrap">';
    echo '<h1 style="display: flex; justify-content: space-between; align-items: center;">';
    echo __('Tebuto Online-Terminbuchung', 'tebuto');
    if ($refresh_token) {
        // Verbindung trennen Button oben rechts
        echo '<form method="post" style="margin: 0;">';
        echo '<input type="hidden" name="tebuto_disconnect" value="1">';
        echo '<button class="button button-secondary" type="submit" style="background-color: #dc3545; color: white; border: none;" onclick="return confirm(\'' . __('Möchtest du Verbindung wirklich trennen?', 'tebuto') . '\');">';
        echo __('Verbindung trennen', 'tebuto');
        echo '</button>';
        echo '</form>';
    }
    echo '</h1>';

    if (!$refresh_token) {
        // Noch nicht verbunden
        $auth_url = tebuto_get_authorize_url();
        echo '<p>' . __('Du bist derzeit nicht mit Tebuto verbunden. Bitte verwende den Button, um dich mit Tebuto zu verbinden.', 'tebuto') . '</p>';
        echo '<a class="button button-primary" href="' . esc_url($auth_url) . '">' . __('Mit Tebuto verbinden', 'tebuto') . '</a>';
    } else {
        echo '<div style="align-items: center; gap: 24px; display: flex; margin-top: 40px; max-width: 800px; padding-top: 12px; padding-bottom: 12px; padding-left: 32px; padding-right: 32px; border-radius: 24px; background-color: #009087;">';
        echo '<img style="height: 60px; width: auto;" src="' . TEBUTO_PLUGIN_URL . 'assets/tebuto_icon.png"/>';
        echo '<div>';
        echo '<h2 style="color: white; margin-bottom: 0;">';
        echo 'Mit Tebuto verbunden';
        echo '</h2>';
        echo '<p style="color: white; margin-top: 8px;">';
        echo 'Du hast dich erfolgreich mit Tebuto verbunden. Du kannst das Plugin nun verwenden.';
        echo '</p>';
        echo '</div>';
        echo '</div>';
        echo '<h2 style="margin-top: 40px;">' . __('Deine nächsten Schritte:', 'tebuto') . '</h2>';
        echo '<ol style="list-style-type: decimal; margin-left: 20px;">';
        echo '<li> Erstelle deine <a href="https://app.tebuto.de/einstellungen/termine">Terminkategorien</a></li>';
        echo '<li> Erstelle ein paar <a href="https://app.tebuto.de/kalender">Terminserien oder Einzeltermine</a></li>';
        echo '<li> Verwende den <a href="?page=tebuto-shortcode">Shortcode</a> oder den Tebuto-Gutenberg-Block, um die Terminbuchung auf deiner Website anzuzeigen. Mehr dazu findest du in unserer <a href="https://tebuto.de/dokumentation/oeffentliche-termine">Dokumentation</a>.</li>';
        echo '<ol>';
    }

    echo '</div>';
}
