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
    $current_user_id    = get_current_user_id();
    $therapist_uuid     = tebuto_get_user_meta($current_user_id, 'therapist_uuid');
    $background_color   = tebuto_get_user_meta($current_user_id, 'background_color', '#ffffff');
    $primary_color      = tebuto_get_user_meta($current_user_id, 'primary_color', '#00B4A9');
    $text_primary       = tebuto_get_user_meta($current_user_id, 'text_primary', '#374151');
    $text_secondary     = tebuto_get_user_meta($current_user_id, 'text_secondary', '#6b7280');
    $border_color       = tebuto_get_user_meta($current_user_id, 'border_color', '#E9E9E9');
    $border             = tebuto_get_user_meta($current_user_id, 'border', 'true');
    $inherit_font       = tebuto_get_user_meta($current_user_id, 'inherit_font', 'false');
    $categories         = tebuto_get_user_meta($current_user_id, 'categories', '');
    $show_quick_filters = tebuto_get_user_meta($current_user_id, 'show_quick_filters', 'false');
    $custom_css         = tebuto_get_user_meta($current_user_id, 'custom_css', '');

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

    // Color attributes (only add if different from defaults to keep script tag cleaner)
    if ($primary_color !== '#00B4A9') {
        $widget_attrs['data-primary-color'] = esc_attr($primary_color);
    }

    if ($background_color !== '#ffffff') {
        $widget_attrs['data-background-color'] = esc_attr($background_color);
    }

    if ($text_primary !== '#374151') {
        $widget_attrs['data-text-primary'] = esc_attr($text_primary);
    }

    if ($text_secondary !== '#6b7280') {
        $widget_attrs['data-text-secondary'] = esc_attr($text_secondary);
    }

    if ($border_color !== '#E9E9E9') {
        $widget_attrs['data-border-color'] = esc_attr($border_color);
    }

    // Boolean attributes
    if ($inherit_font === 'true') {
        $widget_attrs['data-inherit-font'] = 'true';
    }

    if ($show_quick_filters === 'true') {
        $widget_attrs['data-show-quick-filters'] = 'true';
    }

    // Categories filter
    if (! empty($categories)) {
        $widget_attrs['data-categories'] = esc_attr($categories);
    }

    // Build attribute string for inline script
    $attr_string = '';
    foreach ($widget_attrs as $key => $value) {
        $attr_string .= ' ' . $key . '="' . $value . '"';
    }

    // Output container and script
    $output  = '<div id="tebuto-booking-widget"></div>';
    $output .= '<script src="' . esc_url(TEBUTO_WIDGET_URL) . '"' . $attr_string . ' async></script>';

    // Add custom CSS if provided
    if (! empty($custom_css)) {
        $output .= '<style id="tebuto-custom-css">' . wp_strip_all_tags($custom_css) . '</style>';
    }

    return $output;
}
add_shortcode('tebuto_online_terminbuchung_widget', 'tebuto_widget_shortcode');
