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
        tebuto_delete_user_meta($current_user_id, 'primary_color');
        tebuto_delete_user_meta($current_user_id, 'text_primary');
        tebuto_delete_user_meta($current_user_id, 'text_secondary');
        tebuto_delete_user_meta($current_user_id, 'border_color');
        tebuto_delete_user_meta($current_user_id, 'inherit_font');
        tebuto_delete_user_meta($current_user_id, 'categories');
        tebuto_delete_user_meta($current_user_id, 'show_quick_filters');
        tebuto_delete_user_meta($current_user_id, 'show_provider_filter');
        tebuto_delete_user_meta($current_user_id, 'custom_css');

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

        // Sanitize and save color settings
        $background_color = isset($_POST['background_color']) ? sanitize_hex_color(wp_unslash($_POST['background_color'])) : '#ffffff';
        $primary_color    = isset($_POST['primary_color']) ? sanitize_hex_color(wp_unslash($_POST['primary_color'])) : '#00B4A9';
        $text_primary     = isset($_POST['text_primary']) ? sanitize_hex_color(wp_unslash($_POST['text_primary'])) : '#374151';
        $text_secondary   = isset($_POST['text_secondary']) ? sanitize_hex_color(wp_unslash($_POST['text_secondary'])) : '#6b7280';
        $border_color     = isset($_POST['border_color']) ? sanitize_hex_color(wp_unslash($_POST['border_color'])) : '#E9E9E9';

        // Use defaults if sanitization returns empty
        $background_color = ! empty($background_color) ? $background_color : '#ffffff';
        $primary_color    = ! empty($primary_color) ? $primary_color : '#00B4A9';
        $text_primary     = ! empty($text_primary) ? $text_primary : '#374151';
        $text_secondary   = ! empty($text_secondary) ? $text_secondary : '#6b7280';
        $border_color     = ! empty($border_color) ? $border_color : '#E9E9E9';

        // Boolean settings
        $border             = isset($_POST['border']) && $_POST['border'] === 'true' ? 'true' : 'false';
        $inherit_font       = isset($_POST['inherit_font']) && $_POST['inherit_font'] === 'true' ? 'true' : 'false';
        $show_quick_filters  = isset($_POST['show_quick_filters']) && $_POST['show_quick_filters'] === 'true' ? 'true' : 'false';
        $show_provider_filter = isset($_POST['show_provider_filter']) && $_POST['show_provider_filter'] === 'true' ? 'true' : 'false';

        // Categories (comma-separated list of IDs from multiselect)
        $categories = '';
        if (isset($_POST['categories']) && is_array($_POST['categories'])) {
            $category_ids = array_map('absint', $_POST['categories']);
            $category_ids = array_filter($category_ids); // Remove zeros
            $categories = implode(',', $category_ids);
        } elseif (isset($_POST['categories']) && ! empty($_POST['categories'])) {
            // Fallback for text input
            $raw_categories = sanitize_text_field(wp_unslash($_POST['categories']));
            $categories = preg_replace('/[^0-9,]/', '', $raw_categories);
            $categories = preg_replace('/,+/', ',', trim($categories, ','));
        }

        // Custom CSS
        $custom_css = '';
        if (isset($_POST['custom_css'])) {
            // Strip any script tags and sanitize
            $custom_css = wp_strip_all_tags(wp_unslash($_POST['custom_css']));
        }

        // Save all settings
        tebuto_update_user_meta($current_user_id, 'background_color', $background_color);
        tebuto_update_user_meta($current_user_id, 'primary_color', $primary_color);
        tebuto_update_user_meta($current_user_id, 'text_primary', $text_primary);
        tebuto_update_user_meta($current_user_id, 'text_secondary', $text_secondary);
        tebuto_update_user_meta($current_user_id, 'border_color', $border_color);
        tebuto_update_user_meta($current_user_id, 'border', $border);
        tebuto_update_user_meta($current_user_id, 'inherit_font', $inherit_font);
        tebuto_update_user_meta($current_user_id, 'categories', $categories);
        tebuto_update_user_meta($current_user_id, 'show_quick_filters', $show_quick_filters);
        tebuto_update_user_meta($current_user_id, 'show_provider_filter', $show_provider_filter);
        tebuto_update_user_meta($current_user_id, 'custom_css', $custom_css);

        wp_safe_redirect(admin_url('admin.php?page=tebuto-shortcode&saved=1'));
        exit;
    }
}
add_action('admin_init', 'tebuto_save_settings');
