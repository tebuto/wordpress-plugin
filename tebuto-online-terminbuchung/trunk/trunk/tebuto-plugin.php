<?php
/**
 * Plugin Name: Tebuto - Online-Terminbuchung
 * Plugin URI: https://tebuto.de/dokumentation/wordpress-plugin
 * Description: Integriert die Online-Terminbuchung von Tebuto in deine WordPress-Website. Verwalte Termine, Kategorien und Buchungen direkt aus WordPress.
 * Version: 2.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Tebuto GmbH
 * Author URI: https://tebuto.de?utm_source=wordpress_plugin
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tebuto-online-terminbuchung
 * Domain Path: /languages
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Plugin version.
 */
define('TEBUTO_VERSION', '2.0.0');

/**
 * Plugin directory path.
 */
define('TEBUTO_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('TEBUTO_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename.
 */
define('TEBUTO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Tebuto Therapists API URL (for authenticated therapist endpoints).
 */
define('TEBUTO_API_URL', 'https://therapists.api.tebuto.de');

/**
 * Tebuto Auth URL.
 */
define('TEBUTO_AUTH_URL', 'https://auth.tebuto.de');

/**
 * Tebuto Widget URL.
 */
define('TEBUTO_WIDGET_URL', 'https://tebuto.de/widget/booking.js');

/**
 * Tebuto OAuth Client ID.
 */
define('TEBUTO_CLIENT_ID', 'wordpress-plugin');

/**
 * Tebuto user meta prefix.
 */
define('TEBUTO_META_PREFIX', 'tebuto_online_terminbuchung_');

/**
 * Include required files.
 */
require_once TEBUTO_PLUGIN_PATH . 'includes/helpers.php';
require_once TEBUTO_PLUGIN_PATH . 'includes/class-tebuto-api.php';
require_once TEBUTO_PLUGIN_PATH . 'includes/oauth-callback.php';
require_once TEBUTO_PLUGIN_PATH . 'includes/store-uuid.php';
require_once TEBUTO_PLUGIN_PATH . 'includes/shortcode.php';
require_once TEBUTO_PLUGIN_PATH . 'includes/ajax-handlers.php';

// Admin files
require_once TEBUTO_PLUGIN_PATH . 'admin/admin-menu.php';
require_once TEBUTO_PLUGIN_PATH . 'admin/settings-page.php';
require_once TEBUTO_PLUGIN_PATH . 'admin/shortcode-page.php';
require_once TEBUTO_PLUGIN_PATH . 'admin/save-settings.php';
require_once TEBUTO_PLUGIN_PATH . 'admin/enqueue-assets.php';

// Admin pages
require_once TEBUTO_PLUGIN_PATH . 'admin/pages/dashboard-page.php';
require_once TEBUTO_PLUGIN_PATH . 'admin/pages/categories-page.php';
require_once TEBUTO_PLUGIN_PATH . 'admin/pages/bookings-page.php';

// Block registration
require_once TEBUTO_PLUGIN_PATH . 'block/block.php';

/**
 * Plugin activation hook.
 *
 * @return void
 */
function tebuto_activate(): void {
    // Create necessary database tables or options if needed
    add_option('tebuto_version', TEBUTO_VERSION);
    
    // Flush rewrite rules for any custom endpoints
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'tebuto_activate');

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function tebuto_deactivate(): void {
    // Clean up temporary data
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tebuto_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_tebuto_%'");
    
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'tebuto_deactivate');

/**
 * Add settings link on plugins page.
 *
 * @param array $links Plugin action links.
 * @return array Modified action links.
 */
function tebuto_plugin_action_links(array $links): array {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=tebuto-main'),
        __('Dashboard', 'tebuto-online-terminbuchung')
    );
    
    array_unshift($links, $settings_link);
    
    return $links;
}
add_filter('plugin_action_links_' . TEBUTO_PLUGIN_BASENAME, 'tebuto_plugin_action_links');

/**
 * Load plugin text domain for translations.
 *
 * @return void
 */
function tebuto_load_textdomain(): void {
    load_plugin_textdomain(
        'tebuto-online-terminbuchung',
        false,
        dirname(TEBUTO_PLUGIN_BASENAME) . '/languages'
    );
}
add_action('plugins_loaded', 'tebuto_load_textdomain');

/**
 * Initialize plugin after WordPress is loaded.
 *
 * @return void
 */
function tebuto_init(): void {
    // Register shortcode
    add_shortcode('tebuto_online_terminbuchung_widget', 'tebuto_widget_shortcode');
    
    // Handle OAuth callback
    if (is_admin()) {
        add_action('admin_init', 'tebuto_handle_oauth_callback');
        add_action('admin_init', 'tebuto_save_settings');
    }
}
add_action('init', 'tebuto_init');

/**
 * Register admin menu.
 *
 * @return void
 */
function tebuto_register_admin_menu(): void {
    tebuto_add_admin_menu();
}
add_action('admin_menu', 'tebuto_register_admin_menu');

/**
 * Enqueue admin assets.
 *
 * @param string $hook_suffix Current admin page hook suffix.
 * @return void
 */
function tebuto_admin_enqueue_scripts(string $hook_suffix): void {
    tebuto_enqueue_admin_assets($hook_suffix);
}
add_action('admin_enqueue_scripts', 'tebuto_admin_enqueue_scripts');

/**
 * Check plugin version and run upgrades if needed.
 *
 * @return void
 */
function tebuto_check_version(): void {
    $current_version = get_option('tebuto_version', '1.0.0');
    
    if (version_compare($current_version, TEBUTO_VERSION, '<')) {
        // Run any upgrade routines here
        update_option('tebuto_version', TEBUTO_VERSION);
    }
}
add_action('plugins_loaded', 'tebuto_check_version');
