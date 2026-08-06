<?php
/**
 * Tebuto plugin uninstall handler.
 *
 * Fired when the plugin is deleted from WordPress.
 *
 * @package Tebuto
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data on uninstall.
 */

// Delete all user meta with Tebuto prefix.
global $wpdb;

$tebuto_meta_keys = array(
	'tebuto_online_terminbuchung_refresh_token',
	'tebuto_online_terminbuchung_access_token',
	'tebuto_online_terminbuchung_therapist_uuid',
	'tebuto_online_terminbuchung_background_color',
	'tebuto_online_terminbuchung_border',
);

foreach ( $tebuto_meta_keys as $tebuto_meta_key ) {
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Uninstall cleanup must delete usermeta by key; caching APIs do not apply.
	$wpdb->delete(
		$wpdb->usermeta,
		array( 'meta_key' => $tebuto_meta_key ),
		array( '%s' )
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
}

// Delete transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall bulk transient cleanup via LIKE; no object-cache API for this.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'_transient_tebuto_%',
		'_transient_timeout_tebuto_%'
	)
);

// Flush rewrite rules.
flush_rewrite_rules();
