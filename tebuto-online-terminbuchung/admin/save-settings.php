<?php
/**
 * Handle saving Tebuto settings.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Process settings form submissions.
 *
 * @return void
 */
function tebuto_save_settings(): void {
	$current_user_id = get_current_user_id();

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified inside the handlers below.
	if ( isset( $_POST['tebuto_disconnect'] ) ) {
		tebuto_handle_disconnect_request( $current_user_id );
	}

	if ( isset( $_POST['tebuto_save_settings'] ) ) {
		tebuto_handle_save_widget_settings( $current_user_id );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}
add_action( 'admin_init', 'tebuto_save_settings' );

/**
 * Verify a settings form nonce or die.
 *
 * @param string $action Nonce action.
 * @return void
 */
function tebuto_require_settings_nonce( string $action ): void {
	$nonce = isset( $_POST['tebuto_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tebuto_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, $action ) ) {
		wp_die(
			esc_html__( 'Ungültige Anfrage. Bitte versuche es erneut.', 'tebuto-online-terminbuchung' ),
			esc_html__( 'Fehler', 'tebuto-online-terminbuchung' ),
			array( 'response' => 403 )
		);
	}
}

/**
 * Clear all Tebuto connection and widget user meta.
 *
 * @param int $user_id User ID.
 * @return void
 */
function tebuto_clear_connection_user_meta( int $user_id ): void {
	$keys = array(
		'refresh_token',
		'access_token',
		'therapist_uuid',
		'therapist_id',
		'therapist_name',
		'background_color',
		'border',
		'primary_color',
		'text_primary',
		'text_secondary',
		'border_color',
		'inherit_font',
		'categories',
		'show_quick_filters',
		'show_provider_filter',
		'show_location_quick_filter',
		'show_category_selection_first',
		'custom_css',
		'seminars',
		'show_list_first',
	);

	foreach ( $keys as $key ) {
		tebuto_delete_user_meta( $user_id, $key );
	}

	tebuto_clear_seminars_feature_cache( $user_id );
}

/**
 * Handle disconnect request.
 *
 * @param int $user_id User ID.
 * @return void
 */
function tebuto_handle_disconnect_request( int $user_id ): void {
	tebuto_require_settings_nonce( 'tebuto_disconnect' );
	tebuto_clear_connection_user_meta( $user_id );
	wp_safe_redirect( admin_url( 'admin.php?page=tebuto-main&disconnected=1' ) );
	exit;
}

/**
 * Sanitize widget theme colors from POST.
 *
 * @param array $defaults Theme defaults (snake_case).
 * @return array{background_color: string, primary_color: string, text_primary: string, text_secondary: string, border_color: string}
 */
function tebuto_sanitize_widget_theme_colors( array $defaults ): array {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by caller.
	$colors = array(
		'background_color' => isset( $_POST['background_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['background_color'] ) ) : $defaults['background_color'],
		'primary_color'    => isset( $_POST['primary_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['primary_color'] ) ) : $defaults['primary_color'],
		'text_primary'     => isset( $_POST['text_primary'] ) ? sanitize_hex_color( wp_unslash( $_POST['text_primary'] ) ) : $defaults['text_primary'],
		'text_secondary'   => isset( $_POST['text_secondary'] ) ? sanitize_hex_color( wp_unslash( $_POST['text_secondary'] ) ) : $defaults['text_secondary'],
		'border_color'     => isset( $_POST['border_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['border_color'] ) ) : $defaults['border_color'],
	);
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	foreach ( $colors as $key => $value ) {
		$colors[ $key ] = ! empty( $value ) ? $value : $defaults[ $key ];
	}

	return $colors;
}

/**
 * Sanitize widget boolean flags from POST.
 *
 * @return array<string, string>
 */
function tebuto_sanitize_widget_boolean_flags(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by caller.
	$show_provider_filter = isset( $_POST['show_provider_filter'] ) && $_POST['show_provider_filter'] === 'true' ? 'true' : 'false';

	return array(
		'border'                        => isset( $_POST['border'] ) && $_POST['border'] === 'true' ? 'true' : 'false',
		'inherit_font'                  => isset( $_POST['inherit_font'] ) && $_POST['inherit_font'] === 'true' ? 'true' : 'false',
		'show_provider_filter'          => $show_provider_filter,
		'show_location_quick_filter'    => isset( $_POST['show_location_quick_filter'] ) && $_POST['show_location_quick_filter'] === 'true' ? 'true' : 'false',
		'show_category_selection_first' => isset( $_POST['show_category_selection_first'] ) && $_POST['show_category_selection_first'] === 'true' ? 'true' : 'false',
		'show_list_first'               => isset( $_POST['show_list_first'] ) && $_POST['show_list_first'] === 'false' ? 'false' : 'true',
		'show_quick_filters'            => $show_provider_filter,
	);
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

/**
 * Sanitize widget categories from POST, defaulting to all public IDs when empty.
 *
 * @param int $user_id User ID.
 * @return string Comma-separated category IDs.
 */
function tebuto_sanitize_widget_categories( int $user_id ): string {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by caller.
	$categories = '';

	if ( isset( $_POST['categories'] ) && is_array( $_POST['categories'] ) ) {
		$category_ids = array_map( 'absint', $_POST['categories'] );
		$category_ids = array_filter( $category_ids );
		$categories   = implode( ',', $category_ids );
	} elseif ( isset( $_POST['categories'] ) && ! empty( $_POST['categories'] ) ) {
		$raw_categories = sanitize_text_field( wp_unslash( $_POST['categories'] ) );
		$categories     = preg_replace( '/[^0-9,]/', '', $raw_categories );
		$categories     = preg_replace( '/,+/', ',', trim( $categories, ',' ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( $categories !== '' ) {
		return $categories;
	}

	$api = new Tebuto_API( $user_id );
	if ( ! $api->is_connected() ) {
		return '';
	}

	$all_categories = $api->get_widget_selectable_categories();
	if ( is_wp_error( $all_categories ) || ! is_array( $all_categories ) ) {
		return '';
	}

	$public_ids = array();
	foreach ( $all_categories as $category ) {
		if ( ! empty( $category['id'] ) ) {
			$public_ids[] = absint( $category['id'] );
		}
	}
	$public_ids = array_values( array_unique( array_filter( $public_ids ) ) );

	return empty( $public_ids ) ? '' : implode( ',', $public_ids );
}

/**
 * Sanitize seminars CSV from POST.
 *
 * @return string
 */
function tebuto_sanitize_widget_seminars(): string {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by caller.
	if ( ! isset( $_POST['seminars'] ) || empty( $_POST['seminars'] ) ) {
		return '';
	}

	$raw_seminars = sanitize_text_field( wp_unslash( $_POST['seminars'] ) );
	$seminars     = preg_replace( '/[^a-zA-Z0-9_\-,]/', '', $raw_seminars );
	return preg_replace( '/,+/', ',', trim( $seminars, ',' ) );
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

/**
 * Persist widget settings to user meta.
 *
 * @param int   $user_id  User ID.
 * @param array $settings Settings map.
 * @return void
 */
function tebuto_persist_widget_settings( int $user_id, array $settings ): void {
	foreach ( $settings as $key => $value ) {
		tebuto_update_user_meta( $user_id, $key, $value );
	}
}

/**
 * Handle save widget settings request.
 *
 * @param int $user_id User ID.
 * @return void
 */
function tebuto_handle_save_widget_settings( int $user_id ): void {
	tebuto_require_settings_nonce( 'tebuto_save_settings' );

	$theme_defaults = tebuto_widget_defaults( 'booking' );
	$colors         = tebuto_sanitize_widget_theme_colors( $theme_defaults );
	$flags          = tebuto_sanitize_widget_boolean_flags();

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified above.
	$custom_css = isset( $_POST['custom_css'] ) ? wp_strip_all_tags( wp_unslash( $_POST['custom_css'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	$settings = array_merge(
		$colors,
		$flags,
		array(
			'categories' => tebuto_sanitize_widget_categories( $user_id ),
			'seminars'   => tebuto_sanitize_widget_seminars(),
			'custom_css' => $custom_css,
		)
	);

	tebuto_persist_widget_settings( $user_id, $settings );

	wp_safe_redirect( admin_url( 'admin.php?page=tebuto-shortcode&saved=1' ) );
	exit;
}
