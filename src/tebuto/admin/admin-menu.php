<?php

function tebuto_add_admin_menu()
{
    // Hauptmenü für Tebuto
    add_menu_page(
        __('Tebuto - Online-Terminbuchung', 'tebuto'),
        __('Tebuto', 'tebuto'),
        'manage_options',
        'tebuto-main',
        'tebuto_admin_page', // Die Funktion für die Standardseite (Einstellungen)
        TEBUTO_PLUGIN_URL . 'assets/tebuto_icon.png',
        100
    );

    // Untermenü: Einstellungen
    add_submenu_page(
        'tebuto-main',
        __('Einstellungen', 'tebuto'),
        __('Einstellungen', 'tebuto'),
        'manage_options',
        'tebuto-integration',
        'tebuto_admin_page'
    );

    // Untermenü: Shortcode
    add_submenu_page(
        'tebuto-main',
        __('Shortcode', 'tebuto'),
        __('Shortcode', 'tebuto'),
        'manage_options',
        'tebuto-shortcode',
        'tebuto_shortcode_page'
    );

    // Entferne das Duplikat der Hauptseite im Submenü
    remove_submenu_page('tebuto-main', 'tebuto-main');
}
add_action('admin_menu', 'tebuto_add_admin_menu');
