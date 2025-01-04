<?php

function tebuto_shortcode_page()
{
    $current_user_id = get_current_user_id();
    $therapist_uuid = get_user_meta($current_user_id, 'tebuto_therapist_uuid', true);
    $background_color = get_user_meta($current_user_id, 'tebuto_background_color', true) ?: '#ffffff';
    $border = get_user_meta($current_user_id, 'tebuto_border', true) ?: 'false';

    echo '<div class="wrap">';
    echo '<h1>' . __('Shortcode für das Widget', 'tebuto') . '</h1>';

    if (!$therapist_uuid) {
        echo '<p>' . __('Sie müssen sich zuerst mit Tebuto verbinden, um den Shortcode zu generieren.', 'tebuto') . '</p>';
    } else {
        // Shortcode generieren
        $shortcode = '[tebuto_widget]';

        // HTML-Code generieren
        $attributes = 'data-therapist-uuid="' . esc_attr($therapist_uuid) . '"';
        if ($border === 'true') {
            $attributes .= ' data-border="true"';
        }
        if ($background_color !== '#ffffff') {
            $attributes .= ' data-background-color="' . esc_attr($background_color) . '"';
        }

        $html_code = '<div id="tebuto-widget"></div>' .
                     '<script src="https://tebuto.dev/widget/booking.js" ' . $attributes . '></script>';

        echo '<h2>' . __('Shortcode', 'tebuto') . '</h2>';
        echo '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">' . esc_html($shortcode) . '</pre>';

        echo '<h2>' . __('Manueller Code', 'tebuto') . '</h2>';
        echo '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ddd; border-radius: 5px; white-space: pre-wrap;">' . esc_html($html_code) . '</pre>';
    }

    echo '</div>';
}
