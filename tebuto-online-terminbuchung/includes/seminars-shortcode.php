<?php
/**
 * Tebuto Seminars-Widget Shortcode.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Instanzzähler für eindeutige Container-IDs.
 *
 * @var int
 */
$tebuto_seminars_widget_instance_count = 0;

/**
 * Parse and sanitize seminars widget settings from shortcode_atts output.
 *
 * @param array<string, string> $parsed         Parsed shortcode attributes.
 * @param array<string, string> $theme_defaults Theme default values.
 * @return array<string, string> Sanitized settings.
 */
function tebuto_parse_seminars_widget_settings( $parsed, $theme_defaults ): array {
	$primary_color    = sanitize_hex_color( $parsed['primary_color'] );
	$background_color = sanitize_hex_color( $parsed['background_color'] );
	$text_primary     = sanitize_hex_color( $parsed['text_primary'] );
	$text_secondary   = sanitize_hex_color( $parsed['text_secondary'] );
	$border_color     = sanitize_hex_color( $parsed['border_color'] );

	return array(
		'primary_color'    => $primary_color ? $primary_color : $theme_defaults['primary_color'],
		'background_color' => $background_color ? $background_color : $theme_defaults['background_color'],
		'text_primary'     => $text_primary ? $text_primary : $theme_defaults['text_primary'],
		'text_secondary'   => $text_secondary ? $text_secondary : $theme_defaults['text_secondary'],
		'border_color'     => $border_color ? $border_color : $theme_defaults['border_color'],
		'border'           => $parsed['border'] === 'true' ? 'true' : 'false',
		'inherit_font'     => $parsed['inherit_font'] === 'true' ? 'true' : 'false',
		'show_list_first'  => $parsed['show_list_first'] === 'false' ? 'false' : 'true',
		'seminars'         => preg_replace( '/[^a-zA-Z0-9_\-,]/', '', $parsed['seminars'] ),
		'custom_css'       => wp_strip_all_tags( $parsed['custom_css'] ),
	);
}

/**
 * Build data-* attributes for the seminars widget script tag.
 *
 * @param string                $therapist_uuid Therapist UUID.
 * @param array<string, string> $settings       Sanitized widget settings.
 * @param array<string, string> $theme_defaults Theme default values.
 * @return array<string, string> Attribute key => escaped value.
 */
function tebuto_build_seminars_widget_attrs( $therapist_uuid, $settings, $theme_defaults ): array {
	$widget_attrs = array(
		'data-therapist-uuid' => esc_attr( $therapist_uuid ),
		'data-border'         => $settings['border'],
	);

	if ( strcasecmp( $settings['primary_color'], $theme_defaults['primary_color'] ) !== 0 ) {
		$widget_attrs['data-primary-color'] = esc_attr( $settings['primary_color'] );
	}

	if ( strcasecmp( $settings['background_color'], $theme_defaults['background_color'] ) !== 0 ) {
		$widget_attrs['data-background-color'] = esc_attr( $settings['background_color'] );
	}

	if ( strcasecmp( $settings['text_primary'], $theme_defaults['text_primary'] ) !== 0 ) {
		$widget_attrs['data-text-primary'] = esc_attr( $settings['text_primary'] );
	}

	if ( strcasecmp( $settings['text_secondary'], $theme_defaults['text_secondary'] ) !== 0 ) {
		$widget_attrs['data-text-secondary'] = esc_attr( $settings['text_secondary'] );
	}

	if ( strcasecmp( $settings['border_color'], $theme_defaults['border_color'] ) !== 0 ) {
		$widget_attrs['data-border-color'] = esc_attr( $settings['border_color'] );
	}

	if ( $settings['inherit_font'] === 'true' ) {
		$widget_attrs['data-inherit-font'] = 'true';
	}

	if ( ! empty( $settings['seminars'] ) ) {
		$widget_attrs['data-seminars'] = esc_attr( $settings['seminars'] );
	}

	if ( $settings['show_list_first'] === 'false' ) {
		$widget_attrs['data-show-list-first'] = 'false';
	}

	return $widget_attrs;
}

/**
 * Render seminars widget container, script, and optional custom CSS.
 *
 * @param string                $widget_id   Container element ID.
 * @param int                   $instance_id Widget instance number.
 * @param array<string, string> $attrs       Escaped data-* attributes.
 * @param string                $custom_css  Stripped custom CSS.
 * @return string Widget HTML.
 */
function tebuto_render_seminars_widget_html( string $widget_id, int $instance_id, array $attrs, string $custom_css ): string {
	$attr_string = '';
	foreach ( $attrs as $key => $value ) {
		$attr_string .= ' ' . $key . '="' . $value . '"';
	}

	$output = '<div id="' . esc_attr( $widget_id ) . '"></div>';
	// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- External seminars widget must load inline with data-* attributes.
	$output .= '<script src="' . esc_url( TEBUTO_SEMINARS_WIDGET_URL ) . '"' . $attr_string . ' async></script>';

	if ( ! empty( $custom_css ) ) {
		$style_id = 'tebuto-seminars-custom-css' . ( $instance_id > 1 ? '-' . $instance_id : '' );
		$output  .= '<style id="' . esc_attr( $style_id ) . '">' . $custom_css . '</style>';
	}

	return $output;
}

/**
 * Rendert den Tebuto-Seminare-Widget-Shortcode.
 *
 * Unterstützt Shortcode-Attribute zur Überschreibung von Standardwerten.
 *
 * Verwendung:
 *   [tebuto_seminare_widget]
 *   [tebuto_seminare_widget seminars="slug-1,slug-2"]
 *   [tebuto_seminare_widget seminars="einfuehrung" show_list_first="false"]
 *   [tebuto_seminare_widget primary_color="#3b82f6" border="false" inherit_font="true"]
 *
 * @param array<string, string>|string $atts Shortcode-Attribute.
 * @return string Widget-HTML.
 */
function tebuto_seminars_widget_shortcode( $atts = array() ): string {
	global $tebuto_seminars_widget_instance_count;

	if ( is_admin() ) {
		return '';
	}

	if ( ! tebuto_seminars_feature_enabled_for_account() ) {
		return '<!-- Tebuto Seminars: Feature disabled -->';
	}

	$current_user_id = tebuto_get_connected_user_id();
	$therapist_uuid  = tebuto_get_user_meta( $current_user_id, 'therapist_uuid' );

	if ( empty( $therapist_uuid ) ) {
		return '<!-- Tebuto Seminars: Not connected -->';
	}

	$theme_defaults = tebuto_widget_defaults( 'seminars' );
	$defaults       = tebuto_widget_settings_for_user( $current_user_id, 'seminars' );
	$parsed         = shortcode_atts( $defaults, $atts, 'tebuto_seminare_widget' );
	$settings       = tebuto_parse_seminars_widget_settings( $parsed, $theme_defaults );

	++$tebuto_seminars_widget_instance_count;
	$instance_id = $tebuto_seminars_widget_instance_count;
	$widget_id   = 'tebuto-seminars-widget' . ( $instance_id > 1 ? '-' . $instance_id : '' );

	$widget_attrs = tebuto_build_seminars_widget_attrs( $therapist_uuid, $settings, $theme_defaults );

	return tebuto_render_seminars_widget_html( $widget_id, $instance_id, $widget_attrs, $settings['custom_css'] );
}
