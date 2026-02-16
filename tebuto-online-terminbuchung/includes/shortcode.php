<?php
/**
 * Tebuto booking widget shortcode.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

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
 *   [tebuto_online_terminbuchung_widget border="false" inherit_font="true" custom_css="#tebuto-booking-widget-2 { ... }"]
 *
 * @param array<string, string>|string $atts Shortcode attributes.
 * @return string Widget HTML output.
 */
function tebuto_widget_shortcode( $atts = [] ): string {
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

    // Defaults from saved user meta (or hardcoded fallbacks)
    $defaults = [
        'primary_color'      => tebuto_get_user_meta( $current_user_id, 'primary_color', '#00B4A9' ),
        'background_color'   => tebuto_get_user_meta( $current_user_id, 'background_color', '#ffffff' ),
        'text_primary'       => tebuto_get_user_meta( $current_user_id, 'text_primary', '#374151' ),
        'text_secondary'     => tebuto_get_user_meta( $current_user_id, 'text_secondary', '#6b7280' ),
        'border_color'       => tebuto_get_user_meta( $current_user_id, 'border_color', '#E9E9E9' ),
        'border'             => tebuto_get_user_meta( $current_user_id, 'border', 'false' ),
        'inherit_font'       => tebuto_get_user_meta( $current_user_id, 'inherit_font', 'false' ),
        'categories'         => tebuto_get_user_meta( $current_user_id, 'categories', '' ),
        'show_quick_filters'  => tebuto_get_user_meta( $current_user_id, 'show_quick_filters', 'false' ),
        'show_provider_filter' => tebuto_get_user_meta( $current_user_id, 'show_provider_filter', 'false' ),
        'custom_css'          => tebuto_get_user_meta( $current_user_id, 'custom_css', '' ),
    ];

    $parsed = shortcode_atts( $defaults, $atts, 'tebuto_online_terminbuchung_widget' );

    // Sanitize values
    $primary_color      = sanitize_hex_color( $parsed['primary_color'] ) ?: '#00B4A9';
    $background_color   = sanitize_hex_color( $parsed['background_color'] ) ?: '#ffffff';
    $text_primary       = sanitize_hex_color( $parsed['text_primary'] ) ?: '#374151';
    $text_secondary     = sanitize_hex_color( $parsed['text_secondary'] ) ?: '#6b7280';
    $border_color       = sanitize_hex_color( $parsed['border_color'] ) ?: '#E9E9E9';
    $border             = $parsed['border'] === 'true' ? 'true' : 'false';
    $inherit_font       = $parsed['inherit_font'] === 'true' ? 'true' : 'false';
    $show_quick_filters  = $parsed['show_quick_filters'] === 'true' ? 'true' : 'false';
    $show_provider_filter = $parsed['show_provider_filter'] === 'true' ? 'true' : 'false';
    $categories          = preg_replace( '/[^0-9,]/', '', $parsed['categories'] );
    $custom_css         = wp_strip_all_tags( $parsed['custom_css'] );

    // Generate unique instance ID
    $tebuto_widget_instance_count++;
    $instance_id = $tebuto_widget_instance_count;
    $widget_id   = 'tebuto-booking-widget' . ( $instance_id > 1 ? '-' . $instance_id : '' );

    // Build widget attributes
    $widget_attrs = [
        'data-therapist-uuid' => esc_attr( $therapist_uuid ),
        'data-border'         => $border,
        'data-container-id'   => esc_attr( $widget_id ),
    ];

    // Color attributes (only add if different from defaults to keep script tag cleaner)
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

    // Boolean attributes
    if ( $inherit_font === 'true' ) {
        $widget_attrs['data-inherit-font'] = 'true';
    }

    if ( $show_quick_filters === 'true' ) {
        $widget_attrs['data-show-quick-filters'] = 'true';
    }

    if ( $show_provider_filter === 'true' ) {
        $widget_attrs['data-include-subusers']    = 'true';
        $widget_attrs['data-show-quick-filters'] = 'true';

        $api = new Tebuto_API( $current_user_id );
        if ( $api->is_connected() ) {
            // Fetch categories and deduplicate by name for the widget UI dropdown.
            // When the user configured specific category IDs, only include
            // categories whose names match — this handles the case where
            // different providers have different IDs for the same category name.
            $all_categories = $api->get_event_categories();
            if ( ! is_wp_error( $all_categories ) && is_array( $all_categories ) ) {
                // If the user configured specific categories, resolve their
                // names first so we can filter across all providers by name.
                $configured_cat_ids = [];
                if ( ! empty( $categories ) ) {
                    $configured_cat_ids = array_map( 'intval', explode( ',', $categories ) );
                }

                $allowed_names = [];
                if ( ! empty( $configured_cat_ids ) ) {
                    foreach ( $all_categories as $cat ) {
                        $cat_id = $cat['id'] ?? 0;
                        if ( in_array( $cat_id, $configured_cat_ids, true ) ) {
                            $allowed_names[] = $cat['name'] ?? '';
                        }
                    }
                    $allowed_names = array_unique( $allowed_names );
                }

                $seen_names         = [];
                $deduped_categories = [];
                foreach ( $all_categories as $cat ) {
                    $name = $cat['name'] ?? '';

                    // Skip if we have a configured list and this name isn't in it
                    if ( ! empty( $allowed_names ) && ! in_array( $name, $allowed_names, true ) ) {
                        continue;
                    }

                    if ( in_array( $name, $seen_names, true ) ) {
                        continue;
                    }
                    $seen_names[]         = $name;
                    $deduped_categories[] = [
                        'id'    => $cat['id'] ?? 0,
                        'name'  => $name,
                        'color' => $cat['color'] ?? '#009087',
                    ];
                }
                if ( ! empty( $deduped_categories ) ) {
                    $widget_attrs['data-configured-categories'] = esc_attr( wp_json_encode( $deduped_categories ) );
                }
            }

            // Fetch all therapists (main + managed users) for the provider filter.
            // Without this, the widget only shows therapists that have events
            // in the current view, missing providers without scheduled events.
            $configured_therapists = $api->get_configured_therapists();
            if ( ! empty( $configured_therapists ) ) {
                $widget_attrs['data-configured-therapists'] = esc_attr( wp_json_encode( $configured_therapists ) );
            }
        }
    }

    // When the provider filter is active, don't restrict events by category
    // IDs. The main therapist's category IDs differ from the subuser's IDs
    // for identically named categories, so filtering by the main therapist's
    // IDs would hide the subuser's events entirely.
    if ( ! empty( $categories ) && $show_provider_filter !== 'true' ) {
        $widget_attrs['data-categories'] = esc_attr( $categories );
    }

    // Build attribute string for inline script
    $attr_string = '';
    foreach ( $widget_attrs as $key => $value ) {
        $attr_string .= ' ' . $key . '="' . $value . '"';
    }

    // Output container and script
    $output  = '<div id="' . esc_attr( $widget_id ) . '"></div>';
    $output .= '<script src="' . esc_url( TEBUTO_WIDGET_URL ) . '"' . $attr_string . ' async></script>';

    // Add custom CSS if provided
    if ( ! empty( $custom_css ) ) {
        $style_id = 'tebuto-custom-css' . ( $instance_id > 1 ? '-' . $instance_id : '' );
        $output  .= '<style id="' . esc_attr( $style_id ) . '">' . $custom_css . '</style>';
    }

    return $output;
}
add_shortcode( 'tebuto_online_terminbuchung_widget', 'tebuto_widget_shortcode' );
