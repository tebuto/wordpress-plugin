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
	register_block_type( __DIR__ . '/build/block', array( 'render_callback' => 'tebuto_render_booking_block' ) );

	if ( is_admin() ) {
		tebuto_maybe_refresh_seminars_feature_cache();
	}

	if ( tebuto_seminars_feature_enabled_for_account() ) {
		register_block_type( __DIR__ . '/build/seminare', array( 'render_callback' => 'tebuto_render_seminars_block' ) );
	}
}
add_action( 'init', 'tebuto_register_block' );

/**
 * Map block attributes to the canonical shortcode renderer.
 *
 * Saved HTML stays compatible; public output uses the current site connection.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @return array<string, string> Shortcode overrides.
 */
function tebuto_block_shortcode_attributes( array $attributes ): array {
	$map  = array(
		'primaryColor'               => 'primary_color',
		'backgroundColor'            => 'background_color',
		'textPrimary'                => 'text_primary',
		'textSecondary'              => 'text_secondary',
		'borderColor'                => 'border_color',
		'border'                     => 'border',
		'inheritFont'                => 'inherit_font',
		'showQuickFilters'           => 'show_quick_filters',
		'showProviderFilter'         => 'show_provider_filter',
		'showLocationQuickFilter'    => 'show_location_quick_filter',
		'showCategorySelectionFirst' => 'show_category_selection_first',
		'categories'                 => 'categories',
		'seminars'                   => 'seminars',
		'showListFirst'              => 'show_list_first',
		'customCss'                  => 'custom_css',
	);
	$atts = array();
	foreach ( $map as $camel => $snake ) {
		if ( array_key_exists( $camel, $attributes ) && is_scalar( $attributes[ $camel ] ) ) {
			$value          = $attributes[ $camel ];
			$atts[ $snake ] = is_bool( $value ) ? ( $value ? 'true' : 'false' ) : (string) $value;
		}
	}
	// Keep saved subaccount selections usable when the management API is unavailable.
	$configured = json_decode( (string) ( $attributes['configuredCategoriesJson'] ?? '' ), true );
	if ( is_array( $configured ) ) {
		$ids = array();
		foreach ( $configured as $category ) {
			if ( ! is_array( $category ) || empty( $category['id'] ) || ! is_numeric( $category['id'] ) || (int) $category['id'] <= 0 ) {
				continue;
			}
			$ids[] = (int) $category['id'];
			if ( ( $category['isFromSubaccount'] ?? false ) === true ) {
				$atts['show_provider_filter'] = 'true';
			}
		}
		if ( empty( $atts['categories'] ) && ! empty( $ids ) ) {
			$atts['categories'] = implode( ',', $ids );
		}
	}
	return $atts;
}

/**
 * Render a booking block through the shared shortcode path.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @return string Widget markup.
 */
function tebuto_render_booking_block( array $attributes ): string {
	return tebuto_widget_shortcode( tebuto_block_shortcode_attributes( $attributes ) );
}

/**
 * Render a seminars block through the shared shortcode path.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @return string Widget markup.
 */
function tebuto_render_seminars_block( array $attributes ): string {
	return tebuto_seminars_widget_shortcode( tebuto_block_shortcode_attributes( $attributes ) );
}

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
		'uuid'                   => $therapist_uuid,
		'authState'              => tebuto_get_auth_state( $user_id ),
		'connectUrl'             => $connect_url,
		'reconnectUrl'           => $connect_url,
		'widgetUrl'              => TEBUTO_WIDGET_URL,
		'seminarsWidgetUrl'      => TEBUTO_SEMINARS_WIDGET_URL,
		'shortcodeUrl'           => admin_url( 'admin.php?page=tebuto-shortcode' ),
		'presets'                => tebuto_widget_theme_presets(),
		'defaults'               => $theme_defaults,
		'seminarsDefaults'       => $seminars_defaults,
		'defaultSettings'        => $default_settings,
		'nonce'                  => wp_create_nonce( 'tebuto_admin' ),
		'ajaxUrl'                => admin_url( 'admin-ajax.php' ),
		'hasManagedUsers'        => $widget_capabilities['has_managed_users'],
		'isManagingUser'         => $widget_capabilities['is_managing_user'],
		'seminarsFeatureEnabled' => tebuto_seminars_feature_enabled_for_account( $user_id ),
	);
}

/**
 * Enqueue block editor assets and pass data to the blocks.
 *
 * @return void
 */
function tebuto_enqueue_block_editor_assets(): void {
	tebuto_maybe_refresh_seminars_feature_cache();

	$tebuto_data = tebuto_get_localized_tebuto_data( get_current_user_id() );

	wp_localize_script( 'tebuto-terminbuchung-editor-script', 'tebutoData', $tebuto_data );

	if ( wp_script_is( 'tebuto-seminare-editor-script', 'registered' ) ) {
		wp_localize_script( 'tebuto-seminare-editor-script', 'tebutoData', $tebuto_data );
	}
}
add_action( 'enqueue_block_editor_assets', 'tebuto_enqueue_block_editor_assets' );
