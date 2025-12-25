<?php
/**
 * Tebuto Gutenberg block registration.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Register the Tebuto block.
 *
 * @return void
 */
function tebuto_register_block(): void {
    register_block_type(__DIR__ . '/build/block');
}
add_action('init', 'tebuto_register_block');

/**
 * Enqueue block editor assets and pass data to the block.
 *
 * @return void
 */
function tebuto_enqueue_block_editor_assets(): void {
    $current_user_id = get_current_user_id();
    $therapist_uuid  = tebuto_get_user_meta($current_user_id, 'therapist_uuid');

    // Localize script with therapist data
    wp_localize_script('tebuto-terminbuchung-editor-script', 'tebutoData', [
        'uuid'      => $therapist_uuid,
        'widgetUrl' => TEBUTO_WIDGET_URL,
    ]);
}
add_action('enqueue_block_editor_assets', 'tebuto_enqueue_block_editor_assets');
