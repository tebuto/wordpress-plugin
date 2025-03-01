<?php

function tebuto_widget_shortcode()
{
    $current_user_id = get_current_user_id();
    $therapist_uuid = get_user_meta($current_user_id, 'tebuto_therapist_uuid', true);
    $background_color = get_user_meta($current_user_id, 'tebuto_background_color', true) ?: '#ffffff';
    $border = get_user_meta($current_user_id, 'tebuto_border', true) ?: 'false';

    $attributes = 'data-therapist-uuid="' . esc_attr($therapist_uuid) . '"';
    if ($border === 'true') {
        $attributes .= ' data-border="true"';
    }
    if ($background_color !== '#ffffff') {
        $attributes .= ' data-background-color="' . esc_attr($background_color) . '"';
    }

    // Enqueue the script if it's not already enqueued
    if (!is_admin()) {
        // Using file modification time or version number as a cache buster
        $script_version = '1.0.0'; // You can change this to the actual version number or a timestamp
        wp_enqueue_script('tebuto-booking-widget', 'https://tebuto.de/widget/booking.js', array(), $script_version, true);
    }

    return '<div id="tebuto-booking-widget" ' . $attributes . '></div>';
}
add_shortcode('tebuto_widget', 'tebuto_widget_shortcode');
