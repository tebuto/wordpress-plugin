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
 * Build localized data shared by block editor and admin widget settings.
 *
 * @param int $user_id Current user ID.
 * @return array<string, mixed>
 */
function tebuto_get_localized_tebuto_data( int $user_id ): array {
	$therapist_uuid      = tebuto_get_user_meta( $user_id, 'therapist_uuid' );
	$widget_capabilities = tebuto_get_widget_account_capabilities( $user_id );
	$saved_booking       = tebuto_widget_settings_for_user( $user_id, 'booking' );
	$saved_seminars      = tebuto_widget_settings_for_user( $user_id, 'seminars' );
	$theme_defaults      = tebuto_widget_defaults_camel( 'booking' );
	$seminars_defaults   = tebuto_widget_defaults_camel( 'seminars' );

	$default_settings = array(
		'primaryColor'               => $saved_booking['primary_color'],
		'backgroundColor'            => $saved_booking['background_color'],
		'textPrimary'                => $saved_booking['text_primary'],
		'textSecondary'              => $saved_booking['text_secondary'],
		'borderColor'                => $saved_booking['border_color'],
		'border'                     => $saved_booking['border'] === 'true',
		'inheritFont'                => $saved_booking['inherit_font'] === 'true',
		'showProviderFilter'         => $saved_booking['show_provider_filter'] === 'true',
		'showLocationQuickFilter'    => $saved_booking['show_location_quick_filter'] === 'true',
		'showCategorySelectionFirst' => $saved_booking['show_category_selection_first'] !== 'false',
		'categories'                 => $saved_booking['categories'],
		'seminars'                   => $saved_seminars['seminars'],
		'showListFirst'              => $saved_seminars['show_list_first'] !== 'false',
		'customCss'                  => $saved_booking['custom_css'],
	);

	$connect_url = tebuto_get_authorize_url();

	return array(
		'uuid'              => $therapist_uuid,
		'authState'         => tebuto_get_auth_state( $user_id ),
		'connectUrl'        => $connect_url,
		'reconnectUrl'      => $connect_url,
		'widgetUrl'         => TEBUTO_WIDGET_URL,
		'seminarsWidgetUrl' => TEBUTO_SEMINARS_WIDGET_URL,
		'shortcodeUrl'      => admin_url( 'admin.php?page=tebuto-shortcode' ),
		'presets'           => tebuto_widget_theme_presets(),
		'defaults'          => $theme_defaults,
		'seminarsDefaults'  => $seminars_defaults,
		'defaultSettings'   => $default_settings,
		'nonce'             => wp_create_nonce( 'tebuto_admin' ),
		'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
		'hasManagedUsers'   => $widget_capabilities['has_managed_users'],
		'isManagingUser'    => $widget_capabilities['is_managing_user'],
	);
}

/**
 * Enqueue block editor assets and pass data to the blocks.
 *
 * @return void
 */
function tebuto_enqueue_block_editor_assets(): void {
	$tebuto_data = tebuto_get_localized_tebuto_data( get_current_user_id() );

	wp_localize_script( 'tebuto-terminbuchung-editor-script', 'tebutoData', $tebuto_data );
	wp_localize_script( 'tebuto-seminare-editor-script', 'tebutoData', $tebuto_data );
}
add_action( 'enqueue_block_editor_assets', 'tebuto_enqueue_block_editor_assets' );
