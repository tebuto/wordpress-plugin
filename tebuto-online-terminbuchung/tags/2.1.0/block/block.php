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

    // Check if user has subusers (multi-user account)
    $is_multi_user = false;
    $api = new Tebuto_API();
    if ($api->is_connected()) {
        $who_am_i = $api->who_am_i();
        if (! is_wp_error($who_am_i) && isset($who_am_i['therapists'][0]['therapist']['subusers'])) {
            $is_multi_user = count($who_am_i['therapists'][0]['therapist']['subusers']) > 0;
        }
    }

    // Get saved settings to use as defaults for new blocks
    $default_settings = [
        'primaryColor'     => tebuto_get_user_meta($current_user_id, 'primary_color', '#00B4A9'),
        'backgroundColor'  => tebuto_get_user_meta($current_user_id, 'background_color', '#ffffff'),
        'textPrimary'      => tebuto_get_user_meta($current_user_id, 'text_primary', '#374151'),
        'textSecondary'    => tebuto_get_user_meta($current_user_id, 'text_secondary', '#6b7280'),
        'borderColor'      => tebuto_get_user_meta($current_user_id, 'border_color', '#E9E9E9'),
        'border'           => tebuto_get_user_meta($current_user_id, 'border', 'false') === 'true',
        'inheritFont'      => tebuto_get_user_meta($current_user_id, 'inherit_font', 'false') === 'true',
        'showQuickFilters' => tebuto_get_user_meta($current_user_id, 'show_quick_filters', 'false') === 'true',
        'categories'       => tebuto_get_user_meta($current_user_id, 'categories', ''),
        'customCss'        => tebuto_get_user_meta($current_user_id, 'custom_css', ''),
    ];

    // Localize script with therapist data
    wp_localize_script('tebuto-terminbuchung-editor-script', 'tebutoData', [
        'uuid'            => $therapist_uuid,
        'widgetUrl'       => TEBUTO_WIDGET_URL,
        'settingsUrl'     => admin_url('admin.php?page=tebuto-integration'),
        'shortcodeUrl'    => admin_url('admin.php?page=tebuto-shortcode'),
        'defaultSettings' => $default_settings,
        'nonce'           => wp_create_nonce('tebuto_admin'),
        'isMultiUser'     => $is_multi_user,
    ]);
}
add_action('enqueue_block_editor_assets', 'tebuto_enqueue_block_editor_assets');
