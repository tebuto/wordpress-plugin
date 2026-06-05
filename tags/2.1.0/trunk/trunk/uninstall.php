<?php
/**
 * Tebuto plugin uninstall handler.
 *
 * Fired when the plugin is deleted from WordPress.
 *
 * @package Tebuto
 */

// Exit if not called by WordPress
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data on uninstall.
 */

// Delete all user meta with Tebuto prefix
global $wpdb;

$meta_keys = [
    'tebuto_online_terminbuchung_refresh_token',
    'tebuto_online_terminbuchung_access_token',
    'tebuto_online_terminbuchung_therapist_uuid',
    'tebuto_online_terminbuchung_background_color',
    'tebuto_online_terminbuchung_border',
];

foreach ($meta_keys as $meta_key) {
    $wpdb->delete(
        $wpdb->usermeta,
        ['meta_key' => $meta_key],
        ['%s']
    );
}

// Delete transients
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        '_transient_tebuto_%',
        '_transient_timeout_tebuto_%'
    )
);

// Flush rewrite rules
flush_rewrite_rules();

