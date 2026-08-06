<?php
/**
 * Enqueue admin assets for Tebuto plugin.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue global admin styles for menu icon.
 *
 * @return void
 */
function tebuto_enqueue_global_admin_assets(): void {
	wp_enqueue_style(
		'tebuto-admin-menu-icon',
		TEBUTO_PLUGIN_URL . 'css/admin-menu-icon.css',
		array(),
		tebuto_asset_version( 'css/admin-menu-icon.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'tebuto_enqueue_global_admin_assets' );

/**
 * Enqueue admin styles and scripts.
 *
 * @param string $hook_suffix The current admin page.
 * @return void
 */
function tebuto_enqueue_admin_assets( string $hook_suffix ): void {
	if ( strpos( $hook_suffix, 'tebuto' ) === false ) {
		return;
	}

	wp_enqueue_style(
		'tebuto-tokens',
		TEBUTO_PLUGIN_URL . 'css/tebuto-tokens.css',
		array(),
		tebuto_asset_version( 'css/tebuto-tokens.css' )
	);

	wp_enqueue_style(
		'tebuto-components',
		TEBUTO_PLUGIN_URL . 'css/tebuto-components.css',
		array( 'tebuto-tokens' ),
		tebuto_asset_version( 'css/tebuto-components.css' )
	);

	wp_enqueue_style(
		'tebuto-pages',
		TEBUTO_PLUGIN_URL . 'css/tebuto-pages.css',
		array( 'tebuto-components' ),
		tebuto_asset_version( 'css/tebuto-pages.css' )
	);

	wp_enqueue_style( 'dashicons' );

	wp_enqueue_script(
		'tebuto-admin-script',
		TEBUTO_PLUGIN_URL . 'js/admin-script.js',
		array( 'jquery' ),
		tebuto_asset_version( 'js/admin-script.js' ),
		true
	);

	wp_localize_script(
		'tebuto-admin-script',
		'tebutoAdmin',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'tebuto_admin' ),
			'strings' => array(
				'copied'          => __( 'Kopiert!', 'tebuto-online-terminbuchung' ),
				'confirmBooking'  => __( 'Buchung wirklich bestätigen?', 'tebuto-online-terminbuchung' ),
				'rejectBooking'   => __( 'Buchung wirklich ablehnen?', 'tebuto-online-terminbuchung' ),
				'cancelBooking'   => __( 'Buchung wirklich absagen?', 'tebuto-online-terminbuchung' ),
				'processing'      => __( 'Wird verarbeitet...', 'tebuto-online-terminbuchung' ),
				'actionSuccess'   => __( 'Aktion erfolgreich ausgeführt.', 'tebuto-online-terminbuchung' ),
				'actionError'     => __( 'Fehler bei der Aktion.', 'tebuto-online-terminbuchung' ),
				'confirm'         => __( 'Bestätigen', 'tebuto-online-terminbuchung' ),
				'reject'          => __( 'Ablehnen', 'tebuto-online-terminbuchung' ),
				'cancel'          => __( 'Absagen', 'tebuto-online-terminbuchung' ),
				'clientInfo'      => __( 'Klient', 'tebuto-online-terminbuchung' ),
				'appointmentInfo' => __( 'Termin', 'tebuto-online-terminbuchung' ),
				'bookingInfo'     => __( 'Buchung', 'tebuto-online-terminbuchung' ),
				'name'            => __( 'Name', 'tebuto-online-terminbuchung' ),
				'phone'           => __( 'Telefon', 'tebuto-online-terminbuchung' ),
				'category'        => __( 'Kategorie', 'tebuto-online-terminbuchung' ),
				'date'            => __( 'Datum', 'tebuto-online-terminbuchung' ),
				'time'            => __( 'Zeit', 'tebuto-online-terminbuchung' ),
				'location'        => __( 'Ort', 'tebuto-online-terminbuchung' ),
				'online'          => __( 'Online', 'tebuto-online-terminbuchung' ),
				'onsite'          => __( 'Vor Ort', 'tebuto-online-terminbuchung' ),
				'bookedOn'        => __( 'Gebucht am', 'tebuto-online-terminbuchung' ),
				'price'           => __( 'Preis', 'tebuto-online-terminbuchung' ),
				'confirmed'       => __( 'Bestätigt', 'tebuto-online-terminbuchung' ),
			),
		)
	);

	if ( strpos( $hook_suffix, 'tebuto-shortcode' ) !== false ) {
		tebuto_enqueue_widget_settings_assets();
	}
}
add_action( 'admin_enqueue_scripts', 'tebuto_enqueue_admin_assets' );

/**
 * Enqueue the shared React widget settings app on the shortcode page.
 *
 * @return void
 */
function tebuto_enqueue_widget_settings_assets(): void {
	$asset_file = TEBUTO_PLUGIN_PATH . 'block/build/widget-settings/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = include $asset_file;
	$deps  = isset( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) ? $asset['dependencies'] : array();
	$ver   = isset( $asset['version'] ) ? (string) $asset['version'] : tebuto_asset_version( 'block/build/widget-settings/index.js' );

	wp_enqueue_style( 'wp-components' );

	wp_enqueue_script(
		'tebuto-widget-settings',
		TEBUTO_PLUGIN_URL . 'block/build/widget-settings/index.js',
		$deps,
		$ver,
		true
	);

	$style_path = TEBUTO_PLUGIN_PATH . 'block/build/widget-settings/index.css';
	if ( file_exists( $style_path ) ) {
		wp_enqueue_style(
			'tebuto-widget-settings-style',
			TEBUTO_PLUGIN_URL . 'block/build/widget-settings/index.css',
			array( 'wp-components' ),
			$ver
		);
	}

	$editor_css = TEBUTO_PLUGIN_PATH . 'block/build/block/index.css';
	if ( file_exists( $editor_css ) ) {
		wp_enqueue_style(
			'tebuto-block-editor-style',
			TEBUTO_PLUGIN_URL . 'block/build/block/index.css',
			array( 'wp-components' ),
			tebuto_asset_version( 'block/build/block/index.css' )
		);
	}

	wp_localize_script(
		'tebuto-widget-settings',
		'tebutoData',
		tebuto_get_localized_tebuto_data( get_current_user_id() )
	);

	$saved = tebuto_widget_settings_for_user( get_current_user_id(), 'booking' );
	wp_localize_script(
		'tebuto-widget-settings',
		'tebutoWidgetSettings',
		array(
			'primaryColor'               => $saved['primary_color'],
			'backgroundColor'            => $saved['background_color'],
			'textPrimary'                => $saved['text_primary'],
			'textSecondary'              => $saved['text_secondary'],
			'borderColor'                => $saved['border_color'],
			'border'                     => $saved['border'] === 'true',
			'inheritFont'                => $saved['inherit_font'] === 'true',
			'showProviderFilter'         => $saved['show_provider_filter'] === 'true',
			'showLocationQuickFilter'    => $saved['show_location_quick_filter'] === 'true',
			'showCategorySelectionFirst' => $saved['show_category_selection_first'] !== 'false',
			'categories'                 => $saved['categories'],
			'customCss'                  => $saved['custom_css'],
		)
	);
}
