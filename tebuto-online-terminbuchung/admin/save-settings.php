<?php
/**
 * Handle saving Tebuto settings.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Process settings form submissions.
 *
 * @return void
 */
function tebuto_save_settings(): void {
    $current_user_id = get_current_user_id();

    // Handle disconnect request
    if (isset($_POST['tebuto_disconnect'])) {
        $nonce = isset($_POST['tebuto_nonce']) ? sanitize_text_field(wp_unslash($_POST['tebuto_nonce'])) : '';

        if (! wp_verify_nonce($nonce, 'tebuto_disconnect')) {
            wp_die(
                esc_html__('Ungültige Anfrage. Bitte versuche es erneut.', 'tebuto-online-terminbuchung'),
                esc_html__('Fehler', 'tebuto-online-terminbuchung'),
                ['response' => 403]
            );
        }

        // Delete all Tebuto user meta
        tebuto_delete_user_meta($current_user_id, 'refresh_token');
        tebuto_delete_user_meta($current_user_id, 'access_token');
        tebuto_delete_user_meta($current_user_id, 'therapist_uuid');
        tebuto_delete_user_meta($current_user_id, 'background_color');
        tebuto_delete_user_meta($current_user_id, 'border');

        wp_safe_redirect(admin_url('admin.php?page=tebuto-integration&disconnected=1'));
        exit;
    }

    // Handle settings save
    if (isset($_POST['tebuto_save_settings'])) {
        $nonce = isset($_POST['tebuto_nonce']) ? sanitize_text_field(wp_unslash($_POST['tebuto_nonce'])) : '';

        if (! wp_verify_nonce($nonce, 'tebuto_save_settings')) {
            wp_die(
                esc_html__('Ungültige Anfrage. Bitte versuche es erneut.', 'tebuto-online-terminbuchung'),
                esc_html__('Fehler', 'tebuto-online-terminbuchung'),
                ['response' => 403]
            );
        }

        // Sanitize and save settings
        $background_color = isset($_POST['background_color']) ? sanitize_hex_color(wp_unslash($_POST['background_color'])) : '#ffffff';
        $border           = isset($_POST['border']) && $_POST['border'] === 'true' ? 'true' : 'false';

        // Use default if sanitization returns empty
        if (empty($background_color)) {
            $background_color = '#ffffff';
        }

        tebuto_update_user_meta($current_user_id, 'background_color', $background_color);
        tebuto_update_user_meta($current_user_id, 'border', $border);

        wp_safe_redirect(admin_url('admin.php?page=tebuto-shortcode&saved=1'));
        exit;
    }
}
add_action('admin_init', 'tebuto_save_settings');
