<?php
/**
 * Handle saving Tebuto settings.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Process settings form submissions.
 *
 * @return void
 */
function tebuto_save_settings(): void {
	$current_user_id = get_current_user_id();

	// Handle disconnect request
	if ( isset( $_POST['tebuto_disconnect'] ) ) {
		$nonce = isset( $_POST['tebuto_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tebuto_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'tebuto_disconnect' ) ) {
			wp_die(
				esc_html__( 'Ungültige Anfrage. Bitte versuche es erneut.', 'tebuto-online-terminbuchung' ),
				esc_html__( 'Fehler', 'tebuto-online-terminbuchung' ),
				array( 'response' => 403 )
			);
		}

		// Delete all Tebuto user meta
		tebuto_delete_user_meta( $current_user_id, 'refresh_token' );
		tebuto_delete_user_meta( $current_user_id, 'access_token' );
		tebuto_delete_user_meta( $current_user_id, 'therapist_uuid' );
		tebuto_delete_user_meta( $current_user_id, 'therapist_id' );
		tebuto_delete_user_meta( $current_user_id, 'therapist_name' );
		tebuto_delete_user_meta( $current_user_id, 'background_color' );
		tebuto_delete_user_meta( $current_user_id, 'border' );
		tebuto_delete_user_meta( $current_user_id, 'primary_color' );
		tebuto_delete_user_meta( $current_user_id, 'text_primary' );
		tebuto_delete_user_meta( $current_user_id, 'text_secondary' );
		tebuto_delete_user_meta( $current_user_id, 'border_color' );
		tebuto_delete_user_meta( $current_user_id, 'inherit_font' );
		tebuto_delete_user_meta( $current_user_id, 'categories' );
		tebuto_delete_user_meta( $current_user_id, 'show_quick_filters' );
		tebuto_delete_user_meta( $current_user_id, 'show_provider_filter' );
		tebuto_delete_user_meta( $current_user_id, 'show_location_quick_filter' );
		tebuto_delete_user_meta( $current_user_id, 'show_category_selection_first' );
		tebuto_delete_user_meta( $current_user_id, 'custom_css' );
		tebuto_clear_seminars_feature_cache( $current_user_id );

		wp_safe_redirect( admin_url( 'admin.php?page=tebuto-main&disconnected=1' ) );
		exit;
	}

	// Handle settings save
	if ( isset( $_POST['tebuto_save_settings'] ) ) {
		$nonce = isset( $_POST['tebuto_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tebuto_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'tebuto_save_settings' ) ) {
			wp_die(
				esc_html__( 'Ungültige Anfrage. Bitte versuche es erneut.', 'tebuto-online-terminbuchung' ),
				esc_html__( 'Fehler', 'tebuto-online-terminbuchung' ),
				array( 'response' => 403 )
			);
		}

		$theme_defaults = tebuto_widget_defaults( 'booking' );

		$background_color = isset( $_POST['background_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['background_color'] ) ) : $theme_defaults['background_color'];
		$primary_color    = isset( $_POST['primary_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['primary_color'] ) ) : $theme_defaults['primary_color'];
		$text_primary     = isset( $_POST['text_primary'] ) ? sanitize_hex_color( wp_unslash( $_POST['text_primary'] ) ) : $theme_defaults['text_primary'];
		$text_secondary   = isset( $_POST['text_secondary'] ) ? sanitize_hex_color( wp_unslash( $_POST['text_secondary'] ) ) : $theme_defaults['text_secondary'];
		$border_color     = isset( $_POST['border_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['border_color'] ) ) : $theme_defaults['border_color'];

		$background_color = ! empty( $background_color ) ? $background_color : $theme_defaults['background_color'];
		$primary_color    = ! empty( $primary_color ) ? $primary_color : $theme_defaults['primary_color'];
		$text_primary     = ! empty( $text_primary ) ? $text_primary : $theme_defaults['text_primary'];
		$text_secondary   = ! empty( $text_secondary ) ? $text_secondary : $theme_defaults['text_secondary'];
		$border_color     = ! empty( $border_color ) ? $border_color : $theme_defaults['border_color'];

		// Boolean settings
		$border                        = isset( $_POST['border'] ) && $_POST['border'] === 'true' ? 'true' : 'false';
		$inherit_font                  = isset( $_POST['inherit_font'] ) && $_POST['inherit_font'] === 'true' ? 'true' : 'false';
		$show_provider_filter          = isset( $_POST['show_provider_filter'] ) && $_POST['show_provider_filter'] === 'true' ? 'true' : 'false';
		$show_location_quick_filter    = isset( $_POST['show_location_quick_filter'] ) && $_POST['show_location_quick_filter'] === 'true' ? 'true' : 'false';
		$show_category_selection_first = isset( $_POST['show_category_selection_first'] ) && $_POST['show_category_selection_first'] === 'true' ? 'true' : 'false';
		// Quick filters are enabled together with the provider filter (HTML widget configurator behavior).
		$show_quick_filters = $show_provider_filter;

		// Categories (comma-separated list of IDs from multiselect)
		$categories = '';
		if ( isset( $_POST['categories'] ) && is_array( $_POST['categories'] ) ) {
			$category_ids = array_map( 'absint', $_POST['categories'] );
			$category_ids = array_filter( $category_ids ); // Remove zeros
			$categories   = implode( ',', $category_ids );
		} elseif ( isset( $_POST['categories'] ) && ! empty( $_POST['categories'] ) ) {
			// Fallback for text input
			$raw_categories = sanitize_text_field( wp_unslash( $_POST['categories'] ) );
			$categories     = preg_replace( '/[^0-9,]/', '', $raw_categories );
			$categories     = preg_replace( '/,+/', ',', trim( $categories, ',' ) );
		}

		// Default to all public categories when none selected (HTML widget wizard behavior).
		if ( $categories === '' ) {
			$api = new Tebuto_API( $current_user_id );
			if ( $api->is_connected() ) {
				$all_categories = $api->get_widget_selectable_categories();
				if ( ! is_wp_error( $all_categories ) && is_array( $all_categories ) ) {
					$public_ids = array();
					foreach ( $all_categories as $category ) {
						if ( ! empty( $category['id'] ) ) {
							$public_ids[] = absint( $category['id'] );
						}
					}
					$public_ids = array_values( array_unique( array_filter( $public_ids ) ) );
					if ( ! empty( $public_ids ) ) {
						$categories = implode( ',', $public_ids );
					}
				}
			}
		}

		// Custom CSS
		$custom_css = '';
		if ( isset( $_POST['custom_css'] ) ) {
			// Strip any script tags and sanitize
			$custom_css = wp_strip_all_tags( wp_unslash( $_POST['custom_css'] ) );
		}

		// Save all settings
		tebuto_update_user_meta( $current_user_id, 'background_color', $background_color );
		tebuto_update_user_meta( $current_user_id, 'primary_color', $primary_color );
		tebuto_update_user_meta( $current_user_id, 'text_primary', $text_primary );
		tebuto_update_user_meta( $current_user_id, 'text_secondary', $text_secondary );
		tebuto_update_user_meta( $current_user_id, 'border_color', $border_color );
		tebuto_update_user_meta( $current_user_id, 'border', $border );
		tebuto_update_user_meta( $current_user_id, 'inherit_font', $inherit_font );
		tebuto_update_user_meta( $current_user_id, 'categories', $categories );
		tebuto_update_user_meta( $current_user_id, 'show_quick_filters', $show_quick_filters );
		tebuto_update_user_meta( $current_user_id, 'show_provider_filter', $show_provider_filter );
		tebuto_update_user_meta( $current_user_id, 'show_location_quick_filter', $show_location_quick_filter );
		tebuto_update_user_meta( $current_user_id, 'show_category_selection_first', $show_category_selection_first );
		tebuto_update_user_meta( $current_user_id, 'custom_css', $custom_css );

		wp_safe_redirect( admin_url( 'admin.php?page=tebuto-shortcode&saved=1' ) );
		exit;
	}
}
add_action( 'admin_init', 'tebuto_save_settings' );
