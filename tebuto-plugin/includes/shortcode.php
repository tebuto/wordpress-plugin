<?php

function tebuto_widget_shortcode()
{
    // Get user data
    $current_user_id = get_current_user_id();
    $therapist_uuid = get_user_meta($current_user_id, 'tebuto_therapist_uuid', true);
    $background_color = get_user_meta($current_user_id, 'tebuto_background_color', true) ?: '#ffffff';
    $border = get_user_meta($current_user_id, 'tebuto_border', true) ?: 'false';

    // Prepare attributes for the script tag
    $attributes = array(
        'data-therapist-uuid' => esc_attr($therapist_uuid),
        'data-border'         => ($border === 'true') ? 'true' : 'false',
        'data-background-color' => ($background_color !== '#ffffff') ? esc_attr($background_color) : '#ffffff'
    );

    // Enqueue the script if it's not already enqueued
    if (!is_admin()) {
        $script_version = '1.0.0'; // You can change this to the actual version number or a timestamp
        wp_enqueue_script('tebuto-booking-widget', 'https://tebuto.de/widget/booking.js', array(), $script_version, true);

        // Add custom attributes to the enqueued script using wp_script_add_data
        foreach ($attributes as $key => $value) {
            wp_script_add_data('tebuto-booking-widget', $key, $value);
        }
    }

    return '<div id="tebuto-booking-widget"></div>';
}
add_shortcode('tebuto_widget', 'tebuto_widget_shortcode');
