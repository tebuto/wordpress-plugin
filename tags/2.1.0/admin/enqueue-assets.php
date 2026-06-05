<?php
/**
 * Enqueue admin assets for Tebuto plugin.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Enqueue global admin styles for menu icon.
 * This is loaded on all admin pages to ensure the menu icon displays correctly.
 *
 * @return void
 */
function tebuto_enqueue_global_admin_assets(): void {
    wp_enqueue_style(
        'tebuto-admin-menu-icon',
        TEBUTO_PLUGIN_URL . 'css/admin-menu-icon.css',
        [],
        TEBUTO_VERSION
    );
}
add_action('admin_enqueue_scripts', 'tebuto_enqueue_global_admin_assets');

/**
 * Enqueue admin styles and scripts.
 *
 * @param string $hook_suffix The current admin page.
 * @return void
 */
function tebuto_enqueue_admin_assets(string $hook_suffix): void {
    // Only load on Tebuto admin pages
    if (strpos($hook_suffix, 'tebuto') === false) {
        return;
    }

    // Enqueue admin styles
    wp_enqueue_style(
        'tebuto-admin-style',
        TEBUTO_PLUGIN_URL . 'css/admin-style.css',
        [],
        TEBUTO_VERSION
    );

    // Enqueue WordPress color picker
    wp_enqueue_style('wp-color-picker');

    // Enqueue dashicons
    wp_enqueue_style('dashicons');

    // Enqueue admin scripts
    wp_enqueue_script(
        'tebuto-admin-script',
        TEBUTO_PLUGIN_URL . 'js/admin-script.js',
        ['jquery', 'wp-color-picker'],
        TEBUTO_VERSION,
        true
    );

    // Localize script with data
    wp_localize_script('tebuto-admin-script', 'tebutoAdmin', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('tebuto_admin'),
        'strings' => [
            'copied'          => __('Kopiert!', 'tebuto-online-terminbuchung'),
            'confirmBooking'  => __('Buchung wirklich bestätigen?', 'tebuto-online-terminbuchung'),
            'rejectBooking'   => __('Buchung wirklich ablehnen?', 'tebuto-online-terminbuchung'),
            'cancelBooking'   => __('Buchung wirklich absagen?', 'tebuto-online-terminbuchung'),
            'processing'      => __('Wird verarbeitet...', 'tebuto-online-terminbuchung'),
            'actionSuccess'   => __('Aktion erfolgreich ausgeführt.', 'tebuto-online-terminbuchung'),
            'actionError'     => __('Fehler bei der Aktion.', 'tebuto-online-terminbuchung'),
            'confirm'         => __('Bestätigen', 'tebuto-online-terminbuchung'),
            'reject'          => __('Ablehnen', 'tebuto-online-terminbuchung'),
            'cancel'          => __('Absagen', 'tebuto-online-terminbuchung'),
            'clientInfo'      => __('Klient', 'tebuto-online-terminbuchung'),
            'appointmentInfo' => __('Termin', 'tebuto-online-terminbuchung'),
            'bookingInfo'     => __('Buchung', 'tebuto-online-terminbuchung'),
            'name'            => __('Name', 'tebuto-online-terminbuchung'),
            'phone'           => __('Telefon', 'tebuto-online-terminbuchung'),
            'category'        => __('Kategorie', 'tebuto-online-terminbuchung'),
            'date'            => __('Datum', 'tebuto-online-terminbuchung'),
            'time'            => __('Zeit', 'tebuto-online-terminbuchung'),
            'location'        => __('Ort', 'tebuto-online-terminbuchung'),
            'online'          => __('Online', 'tebuto-online-terminbuchung'),
            'onsite'          => __('Vor Ort', 'tebuto-online-terminbuchung'),
            'bookedOn'        => __('Gebucht am', 'tebuto-online-terminbuchung'),
            'price'           => __('Preis', 'tebuto-online-terminbuchung'),
            'confirmed'       => __('Bestätigt', 'tebuto-online-terminbuchung'),
        ],
    ]);
}
