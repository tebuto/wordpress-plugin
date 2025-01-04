<?php

function tebuto_register_block_assets()
{
    // Editor- und Frontend-Styles
    wp_register_style(
        'tebuto-block-editor-style',
        TEBUTO_PLUGIN_URL . 'blocks/editor.css',
        [],
        '1.0'
    );

    // JavaScript für den Block
    wp_register_script(
        'tebuto-block-editor-script',
        TEBUTO_PLUGIN_URL . 'blocks/index.js',
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-i18n'],
        '1.0',
        true
    );

    // Block-Registrierung
    register_block_type('tebuto/widget', [
        'editor_script' => 'tebuto-block-editor-script',
        'editor_style' => 'tebuto-block-editor-style',
        'render_callback' => 'tebuto_render_widget_block',
    ]);
}
add_action('init', 'tebuto_register_block_assets');
