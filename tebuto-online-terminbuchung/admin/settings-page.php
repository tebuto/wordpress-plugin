<?php
/**
 * Hidden OAuth landing page for Tebuto (callback slug tebuto-integration).
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Redirect accidental visits to the dashboard.
 * OAuth success is handled on admin_init before this renders.
 *
 * @return void
 */
function tebuto_oauth_landing_page(): void {
	wp_safe_redirect( admin_url( 'admin.php?page=tebuto-main' ) );
	exit;
}
