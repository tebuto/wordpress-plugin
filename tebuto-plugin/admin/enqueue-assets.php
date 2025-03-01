<?php

function tebuto_enqueue_admin_assets()
{
    // Define paths to assets
    $style_url = esc_url(TEBUTO_PLUGIN_URL . 'css/admin-style.css');
    $script_url = esc_url(TEBUTO_PLUGIN_URL . 'js/admin-script.js');

    // Enqueue admin styles
    wp_enqueue_style(
        'tebuto-admin-style',
        $style_url,
        [],
        filemtime(plugin_dir_path(__FILE__) . 'css/admin-style.css') // Auto-update version on file change
    );

    // Enqueue WordPress color picker styles
    wp_enqueue_style('wp-color-picker');

    // Enqueue admin scripts
    wp_enqueue_script(
        'tebuto-admin-script',
        $script_url,
        ['wp-color-picker'],
        filemtime(plugin_dir_path(__FILE__) . 'js/admin-script.js'), // Auto-update version
        true
    );
}
add_action('admin_enqueue_scripts', 'tebuto_enqueue_admin_assets');
