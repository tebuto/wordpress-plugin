<?php
/**
 * Tebuto admin menu registration.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register admin menu pages.
 *
 * @return void
 */
function tebuto_add_admin_menu(): void {
	// Main menu page - Dashboard
	add_menu_page(
		__( 'Tebuto - Online-Terminbuchung', 'tebuto-online-terminbuchung' ),
		__( 'Tebuto', 'tebuto-online-terminbuchung' ),
		'manage_options',
		'tebuto-main',
		'tebuto_dashboard_page',
		TEBUTO_PLUGIN_URL . 'assets/tebuto-icon.png',
		30
	);

	// Submenu: Dashboard (duplicate to show correct name)
	add_submenu_page(
		'tebuto-main',
		__( 'Dashboard', 'tebuto-online-terminbuchung' ),
		__( 'Dashboard', 'tebuto-online-terminbuchung' ),
		'manage_options',
		'tebuto-main',
		'tebuto_dashboard_page'
	);

	// Submenu: Bookings
	add_submenu_page(
		'tebuto-main',
		__( 'Buchungen', 'tebuto-online-terminbuchung' ),
		__( 'Buchungen', 'tebuto-online-terminbuchung' ),
		'manage_options',
		'tebuto-bookings',
		'tebuto_bookings_page'
	);

	// Submenu: Categories
	add_submenu_page(
		'tebuto-main',
		__( 'Kategorien', 'tebuto-online-terminbuchung' ),
		__( 'Kategorien', 'tebuto-online-terminbuchung' ),
		'manage_options',
		'tebuto-categories',
		'tebuto_categories_page'
	);

	// Submenu: Seminars (visible only when the feature is enabled for the connected account)
	add_submenu_page(
		'tebuto-main',
		__( 'Seminare', 'tebuto-online-terminbuchung' ),
		__( 'Seminare', 'tebuto-online-terminbuchung' ),
		'manage_options',
		'tebuto-seminars',
		'tebuto_seminars_page'
	);

	if ( ! tebuto_user_can_access_seminars_admin() ) {
		remove_submenu_page( 'tebuto-main', 'tebuto-seminars' );
	}

	// Submenu: Shortcode
	add_submenu_page(
		'tebuto-main',
		__( 'Shortcode', 'tebuto-online-terminbuchung' ),
		__( 'Shortcode', 'tebuto-online-terminbuchung' ),
		'manage_options',
		'tebuto-shortcode',
		'tebuto_shortcode_page'
	);

	// Hidden OAuth callback landing (not shown in menu; Keycloak redirect URI).
	add_submenu_page(
		null,
		__( 'Tebuto Verbindung', 'tebuto-online-terminbuchung' ),
		'',
		'manage_options',
		'tebuto-integration',
		'tebuto_oauth_landing_page'
	);
}
