<?php
/**
 * Tebuto booking widget shortcode.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Instance counter for generating unique widget IDs.
 *
 * @var int
 */
$tebuto_widget_instance_count = 0;

/**
 * Parse and sanitize booking widget settings from shortcode_atts output.
 *
 * @param array<string, string> $parsed         Parsed shortcode attributes.
 * @param array<string, string> $theme_defaults Theme default values.
 * @return array<string, string> Sanitized settings.
 */
function tebuto_parse_booking_widget_settings( $parsed, $theme_defaults ): array {
	$primary_color    = sanitize_hex_color( $parsed['primary_color'] );
	$background_color = sanitize_hex_color( $parsed['background_color'] );
	$text_primary     = sanitize_hex_color( $parsed['text_primary'] );
	$text_secondary   = sanitize_hex_color( $parsed['text_secondary'] );
	$border_color     = sanitize_hex_color( $parsed['border_color'] );

	return array(
		'primary_color'                 => $primary_color ? $primary_color : $theme_defaults['primary_color'],
		'background_color'              => $background_color ? $background_color : $theme_defaults['background_color'],
		'text_primary'                  => $text_primary ? $text_primary : $theme_defaults['text_primary'],
		'text_secondary'                => $text_secondary ? $text_secondary : $theme_defaults['text_secondary'],
		'border_color'                  => $border_color ? $border_color : $theme_defaults['border_color'],
		'border'                        => $parsed['border'] === 'true' ? 'true' : 'false',
		'inherit_font'                  => $parsed['inherit_font'] === 'true' ? 'true' : 'false',
		'show_quick_filters'            => $parsed['show_quick_filters'] === 'true' ? 'true' : 'false',
		'show_provider_filter'          => $parsed['show_provider_filter'] === 'true' ? 'true' : 'false',
		'show_location_quick_filter'    => $parsed['show_location_quick_filter'] === 'true' ? 'true' : 'false',
		'show_category_selection_first' => $parsed['show_category_selection_first'] === 'false' ? 'false' : 'true',
		'categories'                    => preg_replace( '/[^0-9,]/', '', $parsed['categories'] ),
		'custom_css'                    => wp_strip_all_tags( $parsed['custom_css'] ),
	);
}

/**
 * Build base data-* attributes for the booking widget script tag.
 *
 * @param string                $therapist_uuid Therapist UUID.
 * @param string                $widget_id      Container element ID.
 * @param array<string, string> $settings       Sanitized widget settings.
 * @param array<string, string> $theme_defaults Theme default values.
 * @return array<string, string> Attribute key => escaped value.
 */
function tebuto_build_booking_widget_base_attrs( $therapist_uuid, $widget_id, $settings, $theme_defaults ): array {
	$widget_attrs = array(
		'data-therapist-uuid' => esc_attr( $therapist_uuid ),
		'data-border'         => $settings['border'],
		'data-container-id'   => esc_attr( $widget_id ),
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

	if ( $settings['show_provider_filter'] === 'true' ) {
		$widget_attrs['data-include-subusers']   = 'true';
		$widget_attrs['data-show-quick-filters'] = 'true';
	} elseif ( $settings['show_quick_filters'] === 'true' ) {
		$widget_attrs['data-show-quick-filters'] = 'true';
	}

	if ( $settings['show_location_quick_filter'] === 'true' ) {
		$widget_attrs['data-show-location-quick-filter'] = 'true';
	}

	if ( $settings['show_category_selection_first'] === 'false' ) {
		$widget_attrs['data-show-category-selection-first'] = 'false';
	}

	return $widget_attrs;
}

/**
 * Map API category rows to the widget configured-categories payload.
 *
 * @param array<int, array<string, mixed>> $cats                   Category rows from the API.
 * @param array<int, int>                  $configured_ids         Allowed category IDs (empty = all).
 * @param bool                             $include_subaccount_flag Whether to include isFromSubaccount.
 * @return array<int, array<string, mixed>> Mapped categories.
 */
function tebuto_map_categories_for_widget( array $cats, array $configured_ids, bool $include_subaccount_flag ): array {
	$configured_categories = array();

	foreach ( $cats as $cat ) {
		$cat_id = $cat['id'] ?? 0;
		if ( ! empty( $configured_ids ) && ! in_array( $cat_id, $configured_ids, true ) ) {
			continue;
		}

		$mapped = array(
			'id'            => $cat_id,
			'name'          => $cat['displayName'] ?? ( $cat['name'] ?? '' ),
			'color'         => $cat['color'] ?? TEBUTO_COLOR_FALLBACK,
			'therapistId'   => $cat['therapistId'] ?? 0,
			'therapistName' => $cat['therapistName'] ?? '',
		);

		if ( $include_subaccount_flag ) {
			$mapped['isFromSubaccount'] = ! empty( $cat['isFromSubaccount'] );
		}

		$configured_categories[] = $mapped;
	}

	return $configured_categories;
}

/**
 * Enrich widget attrs with configured categories when the provider filter is enabled.
 *
 * @param array<string, string> $attrs      Existing widget attributes.
 * @param int                   $user_id    Connected WordPress user ID.
 * @param string                $categories Comma-separated category IDs.
 * @return array<string, string> Enriched attributes.
 */
function tebuto_enrich_attrs_for_provider_filter( array $attrs, int $user_id, string $categories ): array {
	$api = new Tebuto_API( $user_id );
	if ( ! $api->is_connected() ) {
		return $attrs;
	}

	$all_categories = $api->get_widget_selectable_categories();
	if ( is_wp_error( $all_categories ) || ! is_array( $all_categories ) ) {
		return $attrs;
	}

	$configured_cat_ids = array();
	if ( ! empty( $categories ) ) {
		$configured_cat_ids = array_map( 'intval', explode( ',', $categories ) );
	}

	$configured_categories = tebuto_map_categories_for_widget( $all_categories, $configured_cat_ids, false );

	if ( ! empty( $configured_categories ) ) {
		$attrs['data-configured-categories'] = esc_attr( wp_json_encode( $configured_categories ) );
	}

	return $attrs;
}

/**
 * Enrich widget attrs when configured categories include subaccount categories.
 *
 * @param array<string, string> $attrs      Existing widget attributes.
 * @param int                   $user_id    Connected WordPress user ID.
 * @param string                $categories Comma-separated category IDs.
 * @return array<string, string> Enriched attributes.
 */
function tebuto_enrich_attrs_for_subaccount_categories( array $attrs, int $user_id, string $categories ): array {
	$configured_cat_ids = ! empty( $categories )
		? array_map( 'intval', explode( ',', $categories ) )
		: array();

	$category_api          = new Tebuto_API( $user_id );
	$selectable_categories = $category_api->is_connected()
		? $category_api->get_aggregated_event_categories()
		: array();

	$has_subaccount_categories = false;
	if ( ! empty( $configured_cat_ids ) && ! is_wp_error( $selectable_categories ) && is_array( $selectable_categories ) ) {
		foreach ( $selectable_categories as $cat ) {
			$cat_id = $cat['id'] ?? 0;
			if (
				in_array( $cat_id, $configured_cat_ids, true )
				&& ! empty( $cat['isFromSubaccount'] )
				&& ! empty( $cat['widgetSelectable'] )
			) {
				$has_subaccount_categories = true;
				break;
			}
		}
	}

	if ( ! $has_subaccount_categories ) {
		return $attrs;
	}

	$attrs['data-include-subusers']   = 'true';
	$attrs['data-show-quick-filters'] = 'true';

	if ( ! is_wp_error( $selectable_categories ) && is_array( $selectable_categories ) ) {
		$configured_categories = tebuto_map_categories_for_widget( $selectable_categories, $configured_cat_ids, true );
		if ( ! empty( $configured_categories ) ) {
			$attrs['data-configured-categories'] = esc_attr( wp_json_encode( $configured_categories ) );
		}
	}

	return $attrs;
}

/**
 * Render booking widget container, script, and optional custom CSS.
 *
 * @param string                $widget_id   Container element ID.
 * @param int                   $instance_id Widget instance number.
 * @param array<string, string> $attrs       Escaped data-* attributes.
 * @param string                $custom_css  Stripped custom CSS.
 * @return string Widget HTML.
 */
function tebuto_render_booking_widget_html( string $widget_id, int $instance_id, array $attrs, string $custom_css ): string {
	$attr_string = '';
	foreach ( $attrs as $key => $value ) {
		$attr_string .= ' ' . $key . '="' . $value . '"';
	}

	$output = '<div id="' . esc_attr( $widget_id ) . '"></div>';
	// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- External booking widget must load inline with data-* attributes.
	$output .= '<script src="' . esc_url( TEBUTO_WIDGET_URL ) . '"' . $attr_string . ' async></script>';

	if ( ! empty( $custom_css ) ) {
		$style_id = 'tebuto-custom-css' . ( $instance_id > 1 ? '-' . $instance_id : '' );
		$output  .= '<style id="' . esc_attr( $style_id ) . '">' . $custom_css . '</style>';
	}

	return $output;
}

/**
 * Render the Tebuto booking widget shortcode.
 *
 * Supports shortcode attributes to override saved defaults. This allows
 * multiple widget instances with different configurations on the same page.
 *
 * Usage:
 *   [tebuto_online_terminbuchung_widget]
 *   [tebuto_online_terminbuchung_widget primary_color="#3b82f6" categories="1,2,3"]
 *   [tebuto_online_terminbuchung_widget border="false" inherit_font="true" show_provider_filter="true" show_location_quick_filter="true" show_category_selection_first="false"]
 *
 * @param array<string, string>|string $atts Shortcode attributes.
 * @return string Widget HTML output.
 */
function tebuto_widget_shortcode( $atts = array() ): string {
	global $tebuto_widget_instance_count;

	if ( is_admin() ) {
		return '';
	}

	$current_user_id = tebuto_get_connected_user_id();
	$therapist_uuid  = tebuto_get_user_meta( $current_user_id, 'therapist_uuid' );

	if ( empty( $therapist_uuid ) ) {
		return '<!-- Tebuto: Not connected -->';
	}

	$theme_defaults = tebuto_widget_defaults( 'booking' );
	$defaults       = tebuto_widget_settings_for_user( $current_user_id, 'booking' );
	$parsed         = shortcode_atts( $defaults, $atts, 'tebuto_online_terminbuchung_widget' );
	$settings       = tebuto_parse_booking_widget_settings( $parsed, $theme_defaults );

	++$tebuto_widget_instance_count;
	$instance_id = $tebuto_widget_instance_count;
	$widget_id   = 'tebuto-booking-widget' . ( $instance_id > 1 ? '-' . $instance_id : '' );

	$widget_attrs = tebuto_build_booking_widget_base_attrs( $therapist_uuid, $widget_id, $settings, $theme_defaults );

	if ( $settings['show_provider_filter'] === 'true' ) {
		$widget_attrs = tebuto_enrich_attrs_for_provider_filter( $widget_attrs, (int) $current_user_id, $settings['categories'] );
	}

	$widget_attrs = tebuto_enrich_attrs_for_subaccount_categories( $widget_attrs, (int) $current_user_id, $settings['categories'] );

	if ( ! empty( $settings['categories'] ) ) {
		$widget_attrs['data-categories'] = esc_attr( $settings['categories'] );
	}

	return tebuto_render_booking_widget_html( $widget_id, $instance_id, $widget_attrs, $settings['custom_css'] );
}
add_shortcode( 'tebuto_online_terminbuchung_widget', 'tebuto_widget_shortcode' );
