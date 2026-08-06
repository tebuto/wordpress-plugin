<?php
/**
 * Tebuto Seminars-Widget Shortcode.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Instanzzähler für eindeutige Container-IDs.
 *
 * @var int
 */
$tebuto_seminars_widget_instance_count = 0;

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
function tebuto_seminars_widget_shortcode( $atts = [] ): string {
	global $tebuto_seminars_widget_instance_count;

	if ( is_admin() ) {
		return '';
	}

	$current_user_id = tebuto_get_connected_user_id();
	$therapist_uuid  = tebuto_get_user_meta( $current_user_id, 'therapist_uuid' );

	if ( empty( $therapist_uuid ) ) {
		return '<!-- Tebuto Seminars: Not connected -->';
	}

	$defaults = [
		'primary_color'    => tebuto_get_user_meta( $current_user_id, 'primary_color', '#00B4A9' ),
		'background_color' => tebuto_get_user_meta( $current_user_id, 'background_color', '#ffffff' ),
		'text_primary'     => tebuto_get_user_meta( $current_user_id, 'text_primary', '#374151' ),
		'text_secondary'   => tebuto_get_user_meta( $current_user_id, 'text_secondary', '#6b7280' ),
		'border_color'     => tebuto_get_user_meta( $current_user_id, 'border_color', '#E9E9E9' ),
		'border'           => tebuto_get_user_meta( $current_user_id, 'border', 'true' ),
		'inherit_font'     => tebuto_get_user_meta( $current_user_id, 'inherit_font', 'false' ),
		'seminars'         => '',
		'show_list_first'  => 'true',
		'custom_css'       => tebuto_get_user_meta( $current_user_id, 'custom_css', '' ),
	];

	$parsed = shortcode_atts( $defaults, $atts, 'tebuto_seminare_widget' );

	$primary_color    = sanitize_hex_color( $parsed['primary_color'] ) ?: '#00B4A9';
	$background_color = sanitize_hex_color( $parsed['background_color'] ) ?: '#ffffff';
	$text_primary     = sanitize_hex_color( $parsed['text_primary'] ) ?: '#374151';
	$text_secondary   = sanitize_hex_color( $parsed['text_secondary'] ) ?: '#6b7280';
	$border_color     = sanitize_hex_color( $parsed['border_color'] ) ?: '#E9E9E9';
	$border           = $parsed['border'] === 'true' ? 'true' : 'false';
	$inherit_font     = $parsed['inherit_font'] === 'true' ? 'true' : 'false';
	$show_list_first  = $parsed['show_list_first'] === 'false' ? 'false' : 'true';
	$seminars         = preg_replace( '/[^a-zA-Z0-9_\-,]/', '', $parsed['seminars'] );
	$custom_css       = wp_strip_all_tags( $parsed['custom_css'] );

	$tebuto_seminars_widget_instance_count++;
	$instance_id = $tebuto_seminars_widget_instance_count;
	$widget_id   = 'tebuto-seminars-widget' . ( $instance_id > 1 ? '-' . $instance_id : '' );

	$widget_attrs = [
		'data-therapist-uuid' => esc_attr( $therapist_uuid ),
		'data-border'         => $border,
	];

	if ( $primary_color !== '#00B4A9' ) {
		$widget_attrs['data-primary-color'] = esc_attr( $primary_color );
	}

	if ( $background_color !== '#ffffff' ) {
		$widget_attrs['data-background-color'] = esc_attr( $background_color );
	}

	if ( $text_primary !== '#374151' ) {
		$widget_attrs['data-text-primary'] = esc_attr( $text_primary );
	}

	if ( $text_secondary !== '#6b7280' ) {
		$widget_attrs['data-text-secondary'] = esc_attr( $text_secondary );
	}

	if ( $border_color !== '#E9E9E9' ) {
		$widget_attrs['data-border-color'] = esc_attr( $border_color );
	}

	if ( $inherit_font === 'true' ) {
		$widget_attrs['data-inherit-font'] = 'true';
	}

	if ( ! empty( $seminars ) ) {
		$widget_attrs['data-seminars'] = esc_attr( $seminars );
	}

	if ( $show_list_first === 'false' ) {
		$widget_attrs['data-show-list-first'] = 'false';
	}

	$attr_string = '';
	foreach ( $widget_attrs as $key => $value ) {
		$attr_string .= ' ' . $key . '="' . $value . '"';
	}

	$output  = '<div id="' . esc_attr( $widget_id ) . '"></div>';
	$output .= '<script src="' . esc_url( TEBUTO_SEMINARS_WIDGET_URL ) . '"' . $attr_string . ' async></script>';

	if ( ! empty( $custom_css ) ) {
		$style_id = 'tebuto-seminars-custom-css' . ( $instance_id > 1 ? '-' . $instance_id : '' );
		$output  .= '<style id="' . esc_attr( $style_id ) . '">' . $custom_css . '</style>';
	}

	return $output;
}
add_shortcode( 'tebuto_seminare_widget', 'tebuto_seminars_widget_shortcode' );
