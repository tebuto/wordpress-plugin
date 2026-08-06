<?php
/**
 * Tebuto shortcode settings page.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the shortcode settings page.
 *
 * @return void
 */
function tebuto_shortcode_page(): void {
	if ( ! tebuto_is_connected() ) {
		tebuto_render_auth_required_notice();
		return;
	}

	$therapist_uuid = tebuto_get_user_meta( get_current_user_id(), 'therapist_uuid' );

	tebuto_ui_page_open(
		array(
			'title'        => __( 'Shortcode & Widget', 'tebuto-online-terminbuchung' ),
			'page_class'   => 'tebuto-page-shortcode',
			'fullheight'   => true,
			'actions_html' => tebuto_ui_button(
				array(
					'label'   => __( '← Dashboard', 'tebuto-online-terminbuchung' ),
					'href'    => admin_url( 'admin.php?page=tebuto-main' ),
					'variant' => 'outline',
					'color'   => 'neutral',
				)
			),
		)
	);

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only flash flag from post-save redirect.
	if ( isset( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Einstellungen wurden gespeichert.', 'tebuto-online-terminbuchung' ) . '</p></div>';
	}

	if ( empty( $therapist_uuid ) ) {
		echo tebuto_ui_admonition( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'title'        => __( 'Verbindung erforderlich', 'tebuto-online-terminbuchung' ),
				'body'         => __( 'Du musst dein Tebuto-Konto verbinden, um diese Funktionen nutzen zu können.', 'tebuto-online-terminbuchung' ),
				'tone'         => 'warning',
				'icon'         => 'dashicons-admin-plugins',
				'actions_html' => tebuto_ui_button(
					array(
						'label'   => __( 'Mit Tebuto verbinden', 'tebuto-online-terminbuchung' ),
						'href'    => tebuto_get_authorize_url(),
						'variant' => 'solid',
						'color'   => 'primary',
						'size'    => 'lg',
						'class'   => 'button-hero',
					)
				),
			)
		);
		tebuto_ui_page_close();
		return;
	}
	?>
	<form method="post" class="tebuto-settings-form" id="tebuto-widget-settings-form">
		<?php wp_nonce_field( 'tebuto_save_settings', 'tebuto_nonce' ); ?>
		<input type="hidden" name="tebuto_save_settings" value="1">
		<input type="hidden" name="categories_json" id="categories_json" value="[]">
		<div id="tebuto-widget-settings-app" class="tebuto-widget-settings-app"></div>
		<div class="tebuto-form-actions">
			<?php
			echo tebuto_ui_button( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				array(
					'label'   => __( 'Als Standard speichern', 'tebuto-online-terminbuchung' ),
					'type'    => 'submit',
					'variant' => 'solid',
					'color'   => 'primary',
					'size'    => 'lg',
					'icon'    => 'dashicons-saved',
					'class'   => 'button-hero',
				)
			);
			?>
		</div>
	</form>
	<?php
	tebuto_ui_page_close();
}
