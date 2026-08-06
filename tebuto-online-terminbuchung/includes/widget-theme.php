<?php
/**
 * Widget theme presets and defaults — single source of truth.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme presets for the booking / seminars widgets.
 *
 * @return array<int, array{name: string, description: string, primaryColor: string, backgroundColor: string, textPrimary: string, textSecondary: string, borderColor: string}>
 */
function tebuto_widget_theme_presets(): array {
	return array(
		array(
			'name'            => 'Tebuto',
			'description'     => 'Standard',
			'primaryColor'    => '#00B4A9',
			'backgroundColor' => '#ffffff',
			'textPrimary'     => '#374151',
			'textSecondary'   => '#6b7280',
			'borderColor'     => '#E9E9E9',
		),
		array(
			'name'            => 'Professional Blue',
			'description'     => 'Blau',
			'primaryColor'    => '#3b82f6',
			'backgroundColor' => '#ffffff',
			'textPrimary'     => '#1e293b',
			'textSecondary'   => '#64748b',
			'borderColor'     => '#e2e8f0',
		),
		array(
			'name'            => 'Warm Orange',
			'description'     => 'Orange',
			'primaryColor'    => '#f97316',
			'backgroundColor' => '#ffffff',
			'textPrimary'     => '#1c1917',
			'textSecondary'   => '#78716c',
			'borderColor'     => '#fed7aa',
		),
		array(
			'name'            => 'Elegant Purple',
			'description'     => 'Lila',
			'primaryColor'    => '#8b5cf6',
			'backgroundColor' => '#ffffff',
			'textPrimary'     => '#1e1b4b',
			'textSecondary'   => '#6b7280',
			'borderColor'     => '#e9d5ff',
		),
		array(
			'name'            => 'Nature Green',
			'description'     => 'Grün',
			'primaryColor'    => '#059669',
			'backgroundColor' => '#ffffff',
			'textPrimary'     => '#14532d',
			'textSecondary'   => '#166534',
			'borderColor'     => '#bbf7d0',
		),
	);
}

/**
 * Hardcoded widget defaults (snake_case).
 *
 * @param string $variant booking|seminars.
 * @return array<string, string>
 */
function tebuto_widget_defaults( string $variant = 'booking' ): array {
	$shared = array(
		'primary_color'    => '#00B4A9',
		'background_color' => '#ffffff',
		'text_primary'     => '#374151',
		'text_secondary'   => '#6b7280',
		'border_color'     => '#E9E9E9',
		'border'           => 'true',
		'inherit_font'     => 'false',
		'custom_css'       => '',
	);

	if ( $variant === 'seminars' ) {
		return array_merge(
			$shared,
			array(
				'seminars'        => '',
				'show_list_first' => 'true',
			)
		);
	}

	return array_merge(
		$shared,
		array(
			'categories'                    => '',
			'show_quick_filters'            => 'false',
			'show_provider_filter'          => 'false',
			'show_location_quick_filter'    => 'false',
			'show_category_selection_first' => 'true',
		)
	);
}

/**
 * Widget defaults in camelCase (block attributes / JS).
 *
 * @param string $variant booking|seminars.
 * @return array<string, string|bool>
 */
function tebuto_widget_defaults_camel( string $variant = 'booking' ): array {
	$snake = tebuto_widget_defaults( $variant );
	$map   = array(
		'primary_color'                 => 'primaryColor',
		'background_color'              => 'backgroundColor',
		'text_primary'                  => 'textPrimary',
		'text_secondary'                => 'textSecondary',
		'border_color'                  => 'borderColor',
		'border'                        => 'border',
		'inherit_font'                  => 'inheritFont',
		'custom_css'                    => 'customCss',
		'categories'                    => 'categories',
		'show_quick_filters'            => 'showQuickFilters',
		'show_provider_filter'          => 'showProviderFilter',
		'show_location_quick_filter'    => 'showLocationQuickFilter',
		'show_category_selection_first' => 'showCategorySelectionFirst',
		'seminars'                      => 'seminars',
		'show_list_first'               => 'showListFirst',
	);

	$camel = array();
	foreach ( $snake as $key => $value ) {
		$out_key = $map[ $key ] ?? $key;
		if ( in_array( $key, array( 'border', 'inherit_font', 'show_quick_filters', 'show_provider_filter', 'show_location_quick_filter', 'show_category_selection_first', 'show_list_first' ), true ) ) {
			$camel[ $out_key ] = $value === 'true';
		} else {
			$camel[ $out_key ] = $value;
		}
	}

	return $camel;
}

/**
 * Resolve saved user meta against widget defaults (snake_case).
 *
 * @param int    $user_id User ID.
 * @param string $variant booking|seminars.
 * @return array<string, string>
 */
function tebuto_widget_settings_for_user( int $user_id, string $variant = 'booking' ): array {
	$defaults = tebuto_widget_defaults( $variant );
	$resolved = array();

	foreach ( $defaults as $key => $fallback ) {
		$resolved[ $key ] = (string) tebuto_get_user_meta( $user_id, $key, $fallback );
	}

	return $resolved;
}

/**
 * Cache-busting version for a plugin-relative asset path.
 *
 * @param string $relative_path Path relative to TEBUTO_PLUGIN_PATH.
 * @return string
 */
function tebuto_asset_version( string $relative_path ): string {
	$path = TEBUTO_PLUGIN_PATH . ltrim( $relative_path, '/' );
	if ( file_exists( $path ) ) {
		return (string) filemtime( $path );
	}

	return TEBUTO_VERSION;
}
