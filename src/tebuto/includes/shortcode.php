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

    return '<div id="tebuto-widget"></div>' .
           '<script src="https://tebuto.dev/widget/booking.js" ' . $attributes . '></script>';
}
add_shortcode('tebuto_widget', 'tebuto_widget_shortcode');
