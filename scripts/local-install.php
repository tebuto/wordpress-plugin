<?php
// Bootstrap installation mode explicitly; never echo or place passwords in argv.
define('WP_INSTALLING', true);
require_once '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
$credentials = json_decode(file_get_contents('/local-preview/credentials.json'), true);
if (!is_blog_installed()) {
    wp_install('Tebuto – lokale Vorschau', $credentials['admin']['username'], 'local@example.test', false, '', $credentials['admin']['password']);
    update_option('home', 'http://localhost:8000');
    update_option('siteurl', 'http://localhost:8000');
    WP_CLI::success('Local WordPress installed.');
}
