<?php
/**
 * Tebuto booking widget shortcode.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Render the Tebuto booking widget shortcode.
 *
 * @return string Widget HTML output.
 */
function tebuto_widget_shortcode(): string {
    $current_user_id  = get_current_user_id();
    $therapist_uuid   = tebuto_get_user_meta($current_user_id, 'therapist_uuid');
    $background_color = tebuto_get_user_meta($current_user_id, 'background_color', '#ffffff');
    $border           = tebuto_get_user_meta($current_user_id, 'border', 'false');

    // Don't render in admin
    if (is_admin()) {
        return '';
    }

    // Don't render if not connected
    if (empty($therapist_uuid)) {
        return '<!-- Tebuto: Not connected -->';
    }

    // Build widget attributes
    $widget_attrs = [
        'data-therapist-uuid' => esc_attr($therapist_uuid),
        'data-border'         => $border === 'true' ? 'true' : 'false',
    ];

    if ($background_color !== '#ffffff') {
        $widget_attrs['data-background-color'] = esc_attr($background_color);
    }

    // Build attribute string for inline script
    $attr_string = '';
    foreach ($widget_attrs as $key => $value) {
        $attr_string .= ' ' . $key . '="' . $value . '"';
    }

    // Output container and script
    $output  = '<div id="tebuto-booking-widget"></div>';
    $output .= '<script src="' . esc_url(TEBUTO_WIDGET_URL) . '"' . $attr_string . ' async></script>';

    return $output;
}
add_shortcode('tebuto_online_terminbuchung_widget', 'tebuto_widget_shortcode');
