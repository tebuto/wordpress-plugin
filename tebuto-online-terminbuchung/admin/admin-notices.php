<?php
/**
 * Shared Tebuto admin auth notices and guards.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Render the "not connected" notice.
 *
 * @return void
 */
function tebuto_render_not_connected_notice(): void {
    ?>
    <div class="wrap tebuto-admin-wrap">
        <div class="tebuto-card tebuto-card-warning tebuto-auth-notice">
            <div class="tebuto-card-icon">
                <span class="dashicons dashicons-admin-plugins"></span>
            </div>
            <div class="tebuto-card-content">
                <h2><?php esc_html_e('Verbindung erforderlich', 'tebuto-online-terminbuchung'); ?></h2>
                <p><?php esc_html_e('Du musst dein Tebuto-Konto verbinden, um diese Funktionen nutzen zu können.', 'tebuto-online-terminbuchung'); ?></p>
                <a href="<?php echo esc_url(tebuto_get_authorize_url()); ?>" class="button button-primary button-hero">
                    <?php esc_html_e('Mit Tebuto verbinden', 'tebuto-online-terminbuchung'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render the session-expired notice with reconnect action.
 *
 * @return void
 */
function tebuto_render_session_expired_notice(): void {
    ?>
    <div class="wrap tebuto-admin-wrap">
        <div class="tebuto-card tebuto-card-warning tebuto-auth-notice tebuto-auth-notice-expired">
            <div class="tebuto-card-icon">
                <span class="dashicons dashicons-update"></span>
            </div>
            <div class="tebuto-card-content">
                <h2><?php esc_html_e('Sitzung abgelaufen', 'tebuto-online-terminbuchung'); ?></h2>
                <p><?php esc_html_e('Deine Verbindung zu Tebuto ist abgelaufen. Bitte melde dich erneut an, um Termine, Kategorien und Widget-Einstellungen zu verwalten.', 'tebuto-online-terminbuchung'); ?></p>
                <a href="<?php echo esc_url(tebuto_get_authorize_url()); ?>" class="button button-primary button-hero">
                    <?php esc_html_e('Erneut bei Tebuto anmelden', 'tebuto-online-terminbuchung'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render the appropriate auth notice and stop page rendering.
 *
 * @return void
 */
function tebuto_render_auth_required_notice(): void {
    if (tebuto_is_session_expired()) {
        tebuto_render_session_expired_notice();
        return;
    }

    tebuto_render_not_connected_notice();
}

/**
 * Require an active Tebuto connection before rendering admin content.
 *
 * @return Tebuto_API|null API client when connected, null after rendering a notice.
 */
function tebuto_require_tebuto_connection(): ?Tebuto_API {
    if (!tebuto_is_connected()) {
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
function tebuto_maybe_render_session_expired_from_error(WP_Error $error): bool {
    if ($error->get_error_code() !== 'session_expired') {
        return false;
    }

    tebuto_render_session_expired_notice();
    return true;
}
