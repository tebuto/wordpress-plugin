<?php
/*
Plugin Name: Tebuto - Online-Terminbuchung
Description: Dieses Plugin integriert die Online-Terminbuchung von Tebuto in Ihre WordPress-Website.
Version: 1.0
Author: Tebuto GmbH
Author URI: https://tebuto.de?utm_source=wordpress_plugin
License: GPLv2 or later
*/

if (!defined('ABSPATH')) {
    exit; // Sicherheitsmaßnahme: Verhindert direkten Zugriff auf die Datei.
}

// Konstanten definieren
define('TEBUTO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TEBUTO_PLUGIN_URL', plugin_dir_url(__FILE__));

// Admin-spezifische Funktionen
require_once TEBUTO_PLUGIN_PATH . 'admin/admin-menu.php';
require_once TEBUTO_PLUGIN_PATH . 'admin/settings-page.php';
require_once TEBUTO_PLUGIN_PATH . 'admin/shortcode-page.php';
require_once TEBUTO_PLUGIN_PATH . 'admin/save-settings.php';
require_once TEBUTO_PLUGIN_PATH . 'admin/enqueue-assets.php';

// Blocks
require_once TEBUTO_PLUGIN_PATH . 'block/block.php';

// Allgemeine Funktionen
require_once TEBUTO_PLUGIN_PATH . 'includes/helpers.php';
require_once TEBUTO_PLUGIN_PATH . 'includes/oauth-callback.php';
require_once TEBUTO_PLUGIN_PATH . 'includes/store-uuid.php';
require_once TEBUTO_PLUGIN_PATH . 'includes/shortcode.php';
