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

	// Don't render in admin
	if ( is_admin() ) {
		return '';
	}

	// Resolve the admin user who configured the plugin. On the frontend,
	// visitors are not logged into WordPress, so get_current_user_id() is 0.
	$current_user_id = tebuto_get_connected_user_id();
	$therapist_uuid  = tebuto_get_user_meta( $current_user_id, 'therapist_uuid' );

	// Don't render if no user has connected the plugin yet.
	if ( empty( $therapist_uuid ) ) {
		return '<!-- Tebuto: Not connected -->';
	}

	$theme_defaults = tebuto_widget_defaults( 'booking' );
	$defaults       = tebuto_widget_settings_for_user( $current_user_id, 'booking' );

	$parsed = shortcode_atts( $defaults, $atts, 'tebuto_online_terminbuchung_widget' );

	$primary_color                 = sanitize_hex_color( $parsed['primary_color'] );
	$primary_color                 = $primary_color ? $primary_color : $theme_defaults['primary_color'];
	$background_color              = sanitize_hex_color( $parsed['background_color'] );
	$background_color              = $background_color ? $background_color : $theme_defaults['background_color'];
	$text_primary                  = sanitize_hex_color( $parsed['text_primary'] );
	$text_primary                  = $text_primary ? $text_primary : $theme_defaults['text_primary'];
	$text_secondary                = sanitize_hex_color( $parsed['text_secondary'] );
	$text_secondary                = $text_secondary ? $text_secondary : $theme_defaults['text_secondary'];
	$border_color                  = sanitize_hex_color( $parsed['border_color'] );
	$border_color                  = $border_color ? $border_color : $theme_defaults['border_color'];
	$border                        = $parsed['border'] === 'true' ? 'true' : 'false';
	$inherit_font                  = $parsed['inherit_font'] === 'true' ? 'true' : 'false';
	$show_quick_filters            = $parsed['show_quick_filters'] === 'true' ? 'true' : 'false';
	$show_provider_filter          = $parsed['show_provider_filter'] === 'true' ? 'true' : 'false';
	$show_location_quick_filter    = $parsed['show_location_quick_filter'] === 'true' ? 'true' : 'false';
	$show_category_selection_first = $parsed['show_category_selection_first'] === 'false' ? 'false' : 'true';
	$categories                    = preg_replace( '/[^0-9,]/', '', $parsed['categories'] );
	$custom_css                    = wp_strip_all_tags( $parsed['custom_css'] );

	// Generate unique instance ID
	++$tebuto_widget_instance_count;
	$instance_id = $tebuto_widget_instance_count;
	$widget_id   = 'tebuto-booking-widget' . ( $instance_id > 1 ? '-' . $instance_id : '' );

	// Build widget attributes
	$widget_attrs = array(
		'data-therapist-uuid' => esc_attr( $therapist_uuid ),
		'data-border'         => $border,
		'data-container-id'   => esc_attr( $widget_id ),
	);

	if ( strcasecmp( $primary_color, $theme_defaults['primary_color'] ) !== 0 ) {
		$widget_attrs['data-primary-color'] = esc_attr( $primary_color );
	}

	if ( strcasecmp( $background_color, $theme_defaults['background_color'] ) !== 0 ) {
		$widget_attrs['data-background-color'] = esc_attr( $background_color );
	}

	if ( strcasecmp( $text_primary, $theme_defaults['text_primary'] ) !== 0 ) {
		$widget_attrs['data-text-primary'] = esc_attr( $text_primary );
	}

	if ( strcasecmp( $text_secondary, $theme_defaults['text_secondary'] ) !== 0 ) {
		$widget_attrs['data-text-secondary'] = esc_attr( $text_secondary );
	}

	if ( strcasecmp( $border_color, $theme_defaults['border_color'] ) !== 0 ) {
		$widget_attrs['data-border-color'] = esc_attr( $border_color );
	}

	// Boolean attributes
	if ( $inherit_font === 'true' ) {
		$widget_attrs['data-inherit-font'] = 'true';
	}

	if ( $show_provider_filter === 'true' ) {
		$widget_attrs['data-include-subusers']   = 'true';
		$widget_attrs['data-show-quick-filters'] = 'true';
	} elseif ( $show_quick_filters === 'true' ) {
		$widget_attrs['data-show-quick-filters'] = 'true';
	}

	if ( $show_location_quick_filter === 'true' ) {
		$widget_attrs['data-show-location-quick-filter'] = 'true';
	}

	if ( $show_category_selection_first === 'false' ) {
		$widget_attrs['data-show-category-selection-first'] = 'false';
	}

	if ( $show_provider_filter === 'true' ) {

		$api = new Tebuto_API( $current_user_id );
		if ( $api->is_connected() ) {
			$all_categories = $api->get_widget_selectable_categories();
			if ( ! is_wp_error( $all_categories ) && is_array( $all_categories ) ) {
				$configured_cat_ids = array();
				if ( ! empty( $categories ) ) {
					$configured_cat_ids = array_map( 'intval', explode( ',', $categories ) );
				}

				$configured_categories = array();
				foreach ( $all_categories as $cat ) {
					$cat_id = $cat['id'] ?? 0;
					if ( ! empty( $configured_cat_ids ) && ! in_array( $cat_id, $configured_cat_ids, true ) ) {
						continue;
					}

					$configured_categories[] = array(
						'id'            => $cat_id,
						'name'          => $cat['displayName'] ?? ( $cat['name'] ?? '' ),
						'color'         => $cat['color'] ?? TEBUTO_COLOR_FALLBACK,
						'therapistId'   => $cat['therapistId'] ?? 0,
						'therapistName' => $cat['therapistName'] ?? '',
					);
				}

				if ( ! empty( $configured_categories ) ) {
					$widget_attrs['data-configured-categories'] = esc_attr( wp_json_encode( $configured_categories ) );
				}
			}
		}
	}

	$configured_cat_ids    = ! empty( $categories )
		? array_map( 'intval', explode( ',', $categories ) )
		: array();
	$category_api          = new Tebuto_API( $current_user_id );
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

	if ( $has_subaccount_categories ) {
		$widget_attrs['data-include-subusers']   = 'true';
		$widget_attrs['data-show-quick-filters'] = 'true';

		if ( ! is_wp_error( $selectable_categories ) && is_array( $selectable_categories ) ) {
			$configured_categories = array();
			foreach ( $selectable_categories as $cat ) {
				$cat_id = $cat['id'] ?? 0;
				if ( ! in_array( $cat_id, $configured_cat_ids, true ) ) {
					continue;
				}
				$configured_categories[] = array(
					'id'               => $cat_id,
					'name'             => $cat['displayName'] ?? ( $cat['name'] ?? '' ),
					'color'            => $cat['color'] ?? TEBUTO_COLOR_FALLBACK,
					'therapistId'      => $cat['therapistId'] ?? 0,
					'therapistName'    => $cat['therapistName'] ?? '',
					'isFromSubaccount' => ! empty( $cat['isFromSubaccount'] ),
				);
			}
			if ( ! empty( $configured_categories ) ) {
				$widget_attrs['data-configured-categories'] = esc_attr( wp_json_encode( $configured_categories ) );
			}
		}
	}

	if ( ! empty( $categories ) ) {
		$widget_attrs['data-categories'] = esc_attr( $categories );
	}

	// Build attribute string for inline script
	$attr_string = '';
	foreach ( $widget_attrs as $key => $value ) {
		$attr_string .= ' ' . $key . '="' . $value . '"';
	}

	// Output container and script
	$output = '<div id="' . esc_attr( $widget_id ) . '"></div>';
	// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- External booking widget must load inline with data-* attributes.
	$output .= '<script src="' . esc_url( TEBUTO_WIDGET_URL ) . '"' . $attr_string . ' async></script>';

	// Add custom CSS if provided
	if ( ! empty( $custom_css ) ) {
		$style_id = 'tebuto-custom-css' . ( $instance_id > 1 ? '-' . $instance_id : '' );
		$output  .= '<style id="' . esc_attr( $style_id ) . '">' . $custom_css . '</style>';
	}

	return $output;
}
add_shortcode( 'tebuto_online_terminbuchung_widget', 'tebuto_widget_shortcode' );
