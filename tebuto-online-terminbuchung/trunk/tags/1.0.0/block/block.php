<?php

/**
 * Plugin Name:       Tebuto Terminbuchung
 * Description:       Dieses Plugin integriert die Online-Terminbuchung von Tebuto in Ihre WordPress-Website.
 * Version:           1.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            Tebuto GmbH
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tebuto-online-terminbuchung
 *
 * @package CreateBlock
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function tebuto_online_terminbuchung_create_block_init()
{
	register_block_type(__DIR__ . '/build/block');
}
add_action('init', 'tebuto_online_terminbuchung_create_block_init');


function tebuto_online_terminbuchung_enqueue_block_editor_assets()
{
	// UUID aus den Benutzereinstellungen holen
	$current_user_id = get_current_user_id();
	$therapist_uuid = get_user_meta($current_user_id, 'tebuto_online_terminbuchung_therapist_uuid', true);

	// Skripte und Stile für den Editor laden
	wp_enqueue_script(
		'tebuto-editor-script',
		plugins_url('build/index.js', __FILE__),
		['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'],
		filemtime(plugin_dir_path(__FILE__) . 'build/index.js'),
		true
	);

	// UUID an den Block übergeben
	wp_localize_script('tebuto-editor-script', 'tebutoData', [
		'uuid' => $therapist_uuid,
	]);
}
add_action('enqueue_block_editor_assets', 'tebuto_online_terminbuchung_enqueue_block_editor_assets');
