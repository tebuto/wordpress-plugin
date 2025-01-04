<?php

function tebuto_enqueue_admin_assets()
{
    wp_enqueue_style(
        'tebuto-admin-style',
        TEBUTO_PLUGIN_URL . 'css/admin-style.css',
        [],
        '1.0'
    );

    wp_enqueue_style('wp-color-picker'); // WordPress Color Picker

    wp_enqueue_script(
        'tebuto-admin-script',
        TEBUTO_PLUGIN_URL . 'js/admin-script.js',
        ['wp-color-picker'],
        false,
        true
    );
}
add_action('admin_enqueue_scripts', 'tebuto_enqueue_admin_assets');

