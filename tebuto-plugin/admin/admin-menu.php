<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function tebuto_add_admin_menu()
{
    // Hauptmenü für Tebuto
    add_menu_page(
        esc_html__('Tebuto - Online-Terminbuchung', 'tebuto-online-terminbuchung'),
        esc_html__('Tebuto', 'tebuto-online-terminbuchung'),
        'manage_options',
        'tebuto-main',
        'tebuto_admin_page',
        esc_url(TEBUTO_PLUGIN_URL . 'assets/tebuto_icon.png'),
        100
    );

    // Untermenü: Einstellungen
    add_submenu_page(
        'tebuto-main',
        esc_html__('Einstellungen', 'tebuto-online-terminbuchung'),
        esc_html__('Einstellungen', 'tebuto-online-terminbuchung'),
        'manage_options',
        'tebuto-integration',
        'tebuto_admin_page'
    );

    // Untermenü: Shortcode
    add_submenu_page(
        'tebuto-main',
        esc_html__('Shortcode', 'tebuto-online-terminbuchung'),
        esc_html__('Shortcode', 'tebuto-online-terminbuchung'),
        'manage_options',
        'tebuto-shortcode',
        'tebuto_shortcode_page'
    );

    // Entfernt das Duplikat der Hauptseite im Submenü
    remove_submenu_page('tebuto-main', 'tebuto-main');
}
add_action('admin_menu', 'tebuto_add_admin_menu');
