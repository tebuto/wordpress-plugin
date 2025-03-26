<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function tebuto_online_terminbuchung_admin_page()
{
    $current_user_id = get_current_user_id();
    $refresh_token = get_user_meta($current_user_id, 'tebuto_online_terminbuchung_refresh_token', true);

    echo '<div class="wrap">';
    echo '<h1 style="display: flex; justify-content: space-between; align-items: center;">';
    echo esc_html__('Tebuto Online-Terminbuchung', 'tebuto-online-terminbuchung');

    if ($refresh_token) {
        // Verbindung trennen Button oben rechts
        echo '<form method="post" style="margin: 0;">';
        echo '<input type="hidden" name="tebuto_online_terminbuchung_disconnect" value="1">';
        echo '<button class="button button-secondary" type="submit" style="background-color: #dc3545; color: white; border: none;" onclick="return confirm(\'' . esc_js(__('Möchtest du Verbindung wirklich trennen?', 'tebuto-online-terminbuchung')) . '\');">';
        echo esc_html__('Verbindung trennen', 'tebuto-online-terminbuchung');
        echo '</button>';
        echo '</form>';
    }
    echo '</h1>';

    if (!$refresh_token) {
        // Noch nicht verbunden
        $auth_url = tebuto_online_terminbuchung_get_authorize_url();
        echo '<p>' . esc_html__('Du bist derzeit nicht mit Tebuto verbunden. Bitte verwende den Button, um dich mit Tebuto zu verbinden.', 'tebuto-online-terminbuchung') . '</p>';
        echo '<a class="button button-primary" href="' . esc_url($auth_url) . '">' . esc_html__('Mit Tebuto verbinden', 'tebuto-online-terminbuchung') . '</a>';
    } else {
        echo '<div style="align-items: center; gap: 24px; display: flex; margin-top: 40px; max-width: 800px; padding-top: 12px; padding-bottom: 12px; padding-left: 32px; padding-right: 32px; border-radius: 24px; background-color: #009087;">';

        // Bild aus dem Plugin-Ordner, sicher eingebaut mit esc_url()
        echo wp_get_attachment_image(attachment_url_to_postid(TEBUTO_PLUGIN_URL . 'assets/tebuto-icon.png'), 'full', false, [
            'class' => 'tebuto-icon',
            'style' => 'height: 60px; width: auto;',
            'alt'   => esc_attr__('Tebuto Icon', 'tebuto-online-terminbuchung'),
        ]);        

        echo '<div>';
        echo '<h2 style="color: white; margin-bottom: 0;">';
        echo esc_html__('Mit Tebuto verbunden', 'tebuto-online-terminbuchung');
        echo '</h2>';
        echo '<p style="color: white; margin-top: 8px;">';
        echo esc_html__('Du hast dich erfolgreich mit Tebuto verbunden. Du kannst das Plugin jetzt verwenden.', 'tebuto-online-terminbuchung');
        echo '</p>';
        echo '</div>';
        echo '</div>';

        echo '<h2 style="margin-top: 40px;">' . esc_html__('Deine nächsten Schritte:', 'tebuto-online-terminbuchung') . '</h2>';
        echo '<ol style="list-style-type: decimal; margin-left: 20px;">';
        echo '<li>' . esc_html__('Erstelle deine', 'tebuto-online-terminbuchung') . ' <a href="' . esc_url('https://app.tebuto.de/einstellungen/termine') . '">' . esc_html__('Terminkategorien', 'tebuto-online-terminbuchung') . '</a></li>';
        echo '<li>' . esc_html__('Erstelle ein paar', 'tebuto-online-terminbuchung') . ' <a href="' . esc_url('https://app.tebuto.de/termin') . '">' . esc_html__('Terminserien oder Einzeltermine', 'tebuto-online-terminbuchung') . '</a></li>';
        echo '<li>' . esc_html__('Verwende den', 'tebuto-online-terminbuchung') . ' <a href="' . esc_url('?page=tebuto-shortcode') . '">' . esc_html__('Shortcode', 'tebuto-online-terminbuchung') . '</a> ' . esc_html__('oder den Tebuto-Gutenberg-Block, um die Terminbuchung auf deiner Website anzuzeigen. Mehr dazu findest du in unserer', 'tebuto-online-terminbuchung') . ' <a href="' . esc_url('https://tebuto.de/dokumentation/oeffentliche-termine') . '">' . esc_html__('Dokumentation', 'tebuto-online-terminbuchung') . '</a>.</li>';
        echo '</ol>';
    }

    echo '</div>';
}
