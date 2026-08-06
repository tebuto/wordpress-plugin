<?php
/**
 * Shared Tebuto admin auth notices and guards.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the "not connected" notice.
 *
 * @param bool $just_disconnected Whether the user just disconnected.
 * @return void
 */
function tebuto_render_not_connected_notice( bool $just_disconnected = false ): void {
	$title = $just_disconnected
		? __( 'Verbindung getrennt', 'tebuto-online-terminbuchung' )
		: __( 'Verbindung erforderlich', 'tebuto-online-terminbuchung' );
	$body  = $just_disconnected
		? __( 'Die Verbindung zu Tebuto wurde getrennt. Verbinde dein Konto erneut, um das Plugin weiter zu nutzen.', 'tebuto-online-terminbuchung' )
		: __( 'Du musst dein Tebuto-Konto verbinden, um diese Funktionen nutzen zu können.', 'tebuto-online-terminbuchung' );

	$actions = tebuto_ui_button(
		array(
			'label'   => __( 'Mit Tebuto verbinden', 'tebuto-online-terminbuchung' ),
			'href'    => tebuto_get_authorize_url(),
			'variant' => 'solid',
			'color'   => 'primary',
			'size'    => 'lg',
			'class'   => 'button-hero',
		)
	);

	tebuto_ui_page_open(
		array(
			'title'      => __( 'Tebuto', 'tebuto-online-terminbuchung' ),
			'page_class' => 'tebuto-page-auth',
			'fullheight' => true,
		)
	);
	echo tebuto_ui_admonition( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		array(
			'title'        => $title,
			'body'         => $body,
			'tone'         => 'warning',
			'icon'         => 'dashicons-admin-plugins',
			'actions_html' => $actions,
		)
	);
	tebuto_ui_page_close();
}

/**
 * Render the session-expired notice with reconnect action.
 *
 * @return void
 */
function tebuto_render_session_expired_notice(): void {
	$actions = tebuto_ui_button(
		array(
			'label'   => __( 'Erneut bei Tebuto anmelden', 'tebuto-online-terminbuchung' ),
			'href'    => tebuto_get_authorize_url(),
			'variant' => 'solid',
			'color'   => 'primary',
			'size'    => 'lg',
			'class'   => 'button-hero',
		)
	);

	tebuto_ui_page_open(
		array(
			'title'      => __( 'Tebuto', 'tebuto-online-terminbuchung' ),
			'page_class' => 'tebuto-page-auth',
			'fullheight' => true,
		)
	);
	echo tebuto_ui_admonition( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		array(
			'title'        => __( 'Sitzung abgelaufen', 'tebuto-online-terminbuchung' ),
			'body'         => __( 'Deine Verbindung zu Tebuto ist abgelaufen. Bitte melde dich erneut an, um Termine, Kategorien und Widget-Einstellungen zu verwalten.', 'tebuto-online-terminbuchung' ),
			'tone'         => 'warning',
			'icon'         => 'dashicons-update',
			'class'        => 'tebuto-auth-notice-expired',
			'actions_html' => $actions,
		)
	);
	tebuto_ui_page_close();
}

/**
 * Render the appropriate auth notice and stop page rendering.
 *
 * @return void
 */
function tebuto_render_auth_required_notice(): void {
	if ( tebuto_is_session_expired() ) {
		tebuto_render_session_expired_notice();
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only flash flag from post-disconnect redirect.
	$just_disconnected = isset( $_GET['disconnected'] ) && (string) $_GET['disconnected'] === '1';
	tebuto_render_not_connected_notice( $just_disconnected );
}

/**
 * Require an active Tebuto connection before rendering admin content.
 *
 * @return Tebuto_API|null API client when connected, null after rendering a notice.
 */
function tebuto_require_tebuto_connection(): ?Tebuto_API {
	if ( ! tebuto_is_connected() ) {
		tebuto_render_auth_required_notice();
		return null;
	}

	return new Tebuto_API();
}

/**
 * Stop page rendering when an API response indicates an expired session.
 *
 * @param WP_Error $error API error.
 * @return bool True when rendering stopped.
 */
function tebuto_maybe_render_session_expired_from_error( WP_Error $error ): bool {
	if ( $error->get_error_code() !== 'session_expired' ) {
		return false;
	}

	tebuto_render_session_expired_notice();
	return true;
}
