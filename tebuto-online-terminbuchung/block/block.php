<?php
/**
 * Tebuto Gutenberg block registration.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the Tebuto blocks.
 *
 * @return void
 */
function tebuto_register_block(): void {
	register_block_type( __DIR__ . '/build/block' );
	register_block_type( __DIR__ . '/build/seminare' );
}
add_action( 'init', 'tebuto_register_block' );

/**
 * Enqueue block editor assets and pass data to the blocks.
 *
 * @return void
 */
function tebuto_enqueue_block_editor_assets(): void {
	$current_user_id = get_current_user_id();
	$therapist_uuid  = tebuto_get_user_meta( $current_user_id, 'therapist_uuid' );

	$widget_capabilities = tebuto_get_widget_account_capabilities( $current_user_id );

	// Get saved settings to use as defaults for new blocks
	$default_settings = array(
		'primaryColor'            => tebuto_get_user_meta( $current_user_id, 'primary_color', '#00B4A9' ),
		'backgroundColor'         => tebuto_get_user_meta( $current_user_id, 'background_color', '#ffffff' ),
		'textPrimary'             => tebuto_get_user_meta( $current_user_id, 'text_primary', '#374151' ),
		'textSecondary'           => tebuto_get_user_meta( $current_user_id, 'text_secondary', '#6b7280' ),
		'borderColor'             => tebuto_get_user_meta( $current_user_id, 'border_color', '#E9E9E9' ),
		'border'                  => tebuto_get_user_meta( $current_user_id, 'border', 'true' ) === 'true',
		'inheritFont'             => tebuto_get_user_meta( $current_user_id, 'inherit_font', 'false' ) === 'true',
		'showProviderFilter'      => tebuto_get_user_meta( $current_user_id, 'show_provider_filter', 'false' ) === 'true',
		'showLocationQuickFilter' => tebuto_get_user_meta( $current_user_id, 'show_location_quick_filter', 'false' ) === 'true',
		'categories'              => tebuto_get_user_meta( $current_user_id, 'categories', '' ),
		'customCss'               => tebuto_get_user_meta( $current_user_id, 'custom_css', '' ),
	);

	$tebuto_data = array(
		'uuid'              => $therapist_uuid,
		'authState'         => tebuto_get_auth_state( $current_user_id ),
		'reconnectUrl'      => tebuto_get_authorize_url(),
		'widgetUrl'         => TEBUTO_WIDGET_URL,
		'seminarsWidgetUrl' => TEBUTO_SEMINARS_WIDGET_URL,
		'settingsUrl'       => tebuto_get_authorize_url(),
		'shortcodeUrl'      => admin_url( 'admin.php?page=tebuto-shortcode' ),
		'defaultSettings'   => $default_settings,
		'nonce'             => wp_create_nonce( 'tebuto_admin' ),
		'hasManagedUsers'   => $widget_capabilities['has_managed_users'],
		'isManagingUser'    => $widget_capabilities['is_managing_user'],
	);

	wp_localize_script( 'tebuto-terminbuchung-editor-script', 'tebutoData', $tebuto_data );
	wp_localize_script( 'tebuto-seminare-editor-script', 'tebutoData', $tebuto_data );
}
add_action( 'enqueue_block_editor_assets', 'tebuto_enqueue_block_editor_assets' );
