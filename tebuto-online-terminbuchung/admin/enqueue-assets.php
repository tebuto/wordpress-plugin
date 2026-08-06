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
				'cancelDialog'    => __( 'Abbrechen', 'tebuto-online-terminbuchung' ),
				'confirmTitle'    => __( 'Bitte bestätigen', 'tebuto-online-terminbuchung' ),
				'deleteLabel'     => __( 'Löschen', 'tebuto-online-terminbuchung' ),
				'close'           => __( 'Schließen', 'tebuto-online-terminbuchung' ),
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

	if ( strpos( $hook_suffix, 'tebuto-seminars' ) !== false ) {
		tebuto_enqueue_seminars_admin_assets();
	}
}
add_action( 'admin_enqueue_scripts', 'tebuto_enqueue_admin_assets' );

/**
 * Keep the seminars menu visibility in sync with the therapist feature flag.
 *
 * Runs before admin_menu so the submenu can be omitted on the same request.
 *
 * @return void
 */
function tebuto_admin_maybe_refresh_seminars_feature(): void {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! tebuto_is_connected() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page slug.
	$page      = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	$on_tebuto = $page !== '' && strpos( $page, 'tebuto' ) === 0;
	$cached    = tebuto_get_user_meta( get_current_user_id(), 'feature_seminars_access' );

	if ( ! $on_tebuto && $cached !== '' ) {
		return;
	}

	// Positive hits keep a 15-minute TTL. Empty/negative cache always rechecks on
	// Tebuto admin pages so enabling the feature in the app shows up immediately.
	$max_age = ( $on_tebuto && $cached === '1' ) ? 900 : 0;
	tebuto_maybe_refresh_seminars_feature_cache( $max_age );
}
add_action( 'admin_init', 'tebuto_admin_maybe_refresh_seminars_feature', 5 );

/**
 * Enqueue seminars admin page assets.
 *
 * @return void
 */
function tebuto_enqueue_seminars_admin_assets(): void {
	wp_enqueue_editor();

	wp_enqueue_script(
		'tebuto-seminars-admin',
		TEBUTO_PLUGIN_URL . 'js/seminars-admin.js',
		array( 'jquery', 'tebuto-admin-script' ),
		tebuto_asset_version( 'js/seminars-admin.js' ),
		true
	);

	wp_localize_script(
		'tebuto-admin-script',
		'tebutoAdmin',
		array(
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'tebuto_admin' ),
			'seminarsPageUrl' => admin_url( 'admin.php?page=tebuto-seminars' ),
			'strings'         => array(
				'copied'                  => __( 'Kopiert!', 'tebuto-online-terminbuchung' ),
				'confirmBooking'          => __( 'Buchung wirklich bestätigen?', 'tebuto-online-terminbuchung' ),
				'rejectBooking'           => __( 'Buchung wirklich ablehnen?', 'tebuto-online-terminbuchung' ),
				'cancelBooking'           => __( 'Buchung wirklich absagen?', 'tebuto-online-terminbuchung' ),
				'processing'              => __( 'Wird verarbeitet...', 'tebuto-online-terminbuchung' ),
				'actionSuccess'           => __( 'Aktion erfolgreich ausgeführt.', 'tebuto-online-terminbuchung' ),
				'actionError'             => __( 'Fehler bei der Aktion.', 'tebuto-online-terminbuchung' ),
				'confirm'                 => __( 'Bestätigen', 'tebuto-online-terminbuchung' ),
				'reject'                  => __( 'Ablehnen', 'tebuto-online-terminbuchung' ),
				'cancel'                  => __( 'Absagen', 'tebuto-online-terminbuchung' ),
				'cancelDialog'            => __( 'Abbrechen', 'tebuto-online-terminbuchung' ),
				'confirmTitle'            => __( 'Bitte bestätigen', 'tebuto-online-terminbuchung' ),
				'deleteLabel'             => __( 'Löschen', 'tebuto-online-terminbuchung' ),
				'close'                   => __( 'Schließen', 'tebuto-online-terminbuchung' ),
				'publishLabel'            => __( 'Veröffentlichen', 'tebuto-online-terminbuchung' ),
				'draftLabel'              => __( 'Als Entwurf speichern', 'tebuto-online-terminbuchung' ),
				'newSeminar'              => __( 'Neues Seminar', 'tebuto-online-terminbuchung' ),
				'editSeminar'             => __( 'Seminar bearbeiten', 'tebuto-online-terminbuchung' ),
				'createSeminar'           => __( 'Seminar erstellen', 'tebuto-online-terminbuchung' ),
				'saveSeminar'             => __( 'Seminar speichern', 'tebuto-online-terminbuchung' ),
				'addOccurrence'           => __( 'Neue Veranstaltung', 'tebuto-online-terminbuchung' ),
				'noOccurrences'           => __( 'Noch keine Veranstaltungen.', 'tebuto-online-terminbuchung' ),
				'noSessions'              => __( 'Noch keine Termine hinterlegt.', 'tebuto-online-terminbuchung' ),
				'occurrence'              => __( 'Veranstaltung', 'tebuto-online-terminbuchung' ),
				'occurrenceFallback'      => __( 'Termin', 'tebuto-online-terminbuchung' ),
				'pastOccurrences'         => __( 'Vergangene Veranstaltungen', 'tebuto-online-terminbuchung' ),
				'showMore'                => __( 'Mehr anzeigen', 'tebuto-online-terminbuchung' ),
				'seats'                   => __( 'Plätze', 'tebuto-online-terminbuchung' ),
				'status'                  => __( 'Status', 'tebuto-online-terminbuchung' ),
				'actions'                 => __( 'Aktionen', 'tebuto-online-terminbuchung' ),
				'details'                 => __( 'Details', 'tebuto-online-terminbuchung' ),
				'moveUp'                  => __( 'Nach oben', 'tebuto-online-terminbuchung' ),
				'moveDown'                => __( 'Nach unten', 'tebuto-online-terminbuchung' ),
				'loadError'               => __( 'Veranstaltungen konnten nicht geladen werden.', 'tebuto-online-terminbuchung' ),
				'sessionRangeError'       => __( 'Das Ende muss nach dem Beginn liegen.', 'tebuto-online-terminbuchung' ),
				'confirmPublish'          => __( 'Veranstaltung wirklich veröffentlichen?', 'tebuto-online-terminbuchung' ),
				'confirmUnpublish'        => __( 'Veranstaltung wirklich als Entwurf speichern?', 'tebuto-online-terminbuchung' ),
				'confirmCancelOccurrence' => __( 'Veranstaltung wirklich absagen?', 'tebuto-online-terminbuchung' ),
				'cancelReasonPrompt'      => __( 'Optionaler Absagegrund', 'tebuto-online-terminbuchung' ),
				'cancelReasonPlaceholder' => __( 'Grund der Absage (optional)', 'tebuto-online-terminbuchung' ),
				'lifecycleLabels'         => array(
					'draft'                => __( 'Entwurf', 'tebuto-online-terminbuchung' ),
					'published'            => __( 'Veröffentlicht', 'tebuto-online-terminbuchung' ),
					'registration_pending' => __( 'Anmeldung bald', 'tebuto-online-terminbuchung' ),
					'registration_open'    => __( 'Anmeldung offen', 'tebuto-online-terminbuchung' ),
					'registration_closed'  => __( 'Anmeldung geschlossen', 'tebuto-online-terminbuchung' ),
					'running'              => __( 'Läuft', 'tebuto-online-terminbuchung' ),
					'completed'            => __( 'Abgeschlossen', 'tebuto-online-terminbuchung' ),
					'cancelled'            => __( 'Abgesagt', 'tebuto-online-terminbuchung' ),
				),
			),
		)
	);
}

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

	$user_id        = get_current_user_id();
	$saved_booking  = tebuto_widget_settings_for_user( $user_id, 'booking' );
	$saved_seminars = tebuto_widget_settings_for_user( $user_id, 'seminars' );
	wp_localize_script(
		'tebuto-widget-settings',
		'tebutoWidgetSettings',
		array(
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
		)
	);
}
