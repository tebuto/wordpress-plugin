<?php
/**
 * Tebuto admin menu registration.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Register admin menu pages.
 *
 * @return void
 */
function tebuto_add_admin_menu(): void {
    // Main menu page - Dashboard
    add_menu_page(
        __('Tebuto - Online-Terminbuchung', 'tebuto-online-terminbuchung'),
        __('Tebuto', 'tebuto-online-terminbuchung'),
        'manage_options',
        'tebuto-main',
        'tebuto_dashboard_page',
        TEBUTO_PLUGIN_URL . 'assets/tebuto-icon.png',
        30
    );

    // Submenu: Dashboard (duplicate to show correct name)
    add_submenu_page(
        'tebuto-main',
        __('Dashboard', 'tebuto-online-terminbuchung'),
        __('Dashboard', 'tebuto-online-terminbuchung'),
        'manage_options',
        'tebuto-main',
        'tebuto_dashboard_page'
    );

    // Submenu: Bookings
    add_submenu_page(
        'tebuto-main',
        __('Buchungen', 'tebuto-online-terminbuchung'),
        __('Buchungen', 'tebuto-online-terminbuchung'),
        'manage_options',
        'tebuto-bookings',
        'tebuto_bookings_page'
    );

    // Submenu: Categories
    add_submenu_page(
        'tebuto-main',
        __('Kategorien', 'tebuto-online-terminbuchung'),
        __('Kategorien', 'tebuto-online-terminbuchung'),
        'manage_options',
        'tebuto-categories',
        'tebuto_categories_page'
    );

    // Submenu: Shortcode
    add_submenu_page(
        'tebuto-main',
        __('Shortcode', 'tebuto-online-terminbuchung'),
        __('Shortcode', 'tebuto-online-terminbuchung'),
        'manage_options',
        'tebuto-shortcode',
        'tebuto_shortcode_page'
    );

    // Submenu: Connection
    add_submenu_page(
        'tebuto-main',
        __('Verbindung', 'tebuto-online-terminbuchung'),
        __('Verbindung', 'tebuto-online-terminbuchung'),
        'manage_options',
        'tebuto-integration',
        'tebuto_admin_page'
    );
}
