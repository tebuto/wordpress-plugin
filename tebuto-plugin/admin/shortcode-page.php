<?php

function tebuto_shortcode_page()
{
    $current_user_id = get_current_user_id();
    $therapist_uuid = get_user_meta($current_user_id, 'tebuto_therapist_uuid', true);
    $background_color = get_user_meta($current_user_id, 'tebuto_background_color', true) ?: '#ffffff';
    $border = get_user_meta($current_user_id, 'tebuto_border', true) ?: 'false';

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Shortcode', 'tebuto') . '</h1>';

    if (!$therapist_uuid) {
        echo '<p>' . esc_html__('Sie müssen sich zuerst mit Tebuto verbinden, um den Shortcode zu generieren.', 'tebuto') . '</p>';
    } else {
        // Shortcode generieren
        $shortcode = '[tebuto_widget]';

        echo '<p>' . esc_html__('Fügen Sie diesen Shortcode in eine Seite ein, um die Tebuto Terminbuchung zu verwenden. Alternativ können Sie das Widget auch in den Gutenberg Blocks finden.', 'tebuto') . '</p>';
        echo '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">' . esc_html($shortcode) . '</pre>';
        
        echo '<form method="post">';
        echo '<h2 style="margin-top: 35px">' . esc_html__('Shortcode-Widget-Einstellungen', 'tebuto') . '</h2>';
        echo '<table class="form-table">';
        
        // Background Color Input
        echo '<tr>';
        echo '<th><label for="background_color">' . esc_html__('Hintergrundfarbe', 'tebuto') . '</label></th>';
        echo '<td><input type="text" name="background_color" id="background_color" value="' . esc_attr($background_color) . '" class="tebuto-color-picker"></td>';
        echo '</tr>';
        
        // Border Checkbox
        echo '<tr>';
        echo '<th><label for="border">' . esc_html__('Rahmen anzeigen', 'tebuto') . '</label></th>';
        echo '<td><input type="checkbox" name="border" id="border" value="true" ' . checked($border, 'true', false) . '></td>';
        echo '</tr>';
        
        echo '</table>';
        
        echo '<input type="hidden" name="tebuto_save_settings" value="1">';
        echo '<button class="button button-primary" style="margin-top: 20px" type="submit">' . esc_html__('Speichern', 'tebuto') . '</button>';
        echo '</form>';
    }

    echo '</div>';
}
