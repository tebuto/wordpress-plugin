<?php
/**
 * Tebuto Seminars management page (list + create/edit).
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the seminars admin page (list or occurrence detail).
 *
 * @return void
 */
function tebuto_seminars_page(): void {
	tebuto_refresh_seminars_feature_cache();

	if ( ! tebuto_user_can_access_seminars_admin() ) {
		wp_safe_redirect( admin_url( 'admin.php?page=tebuto-main' ) );
		exit;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing.
	$seminar_id = isset( $_GET['seminar_id'] ) ? absint( $_GET['seminar_id'] ) : 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing.
	$occurrence_id = isset( $_GET['occurrence_id'] ) ? absint( $_GET['occurrence_id'] ) : 0;

	if ( $seminar_id > 0 && $occurrence_id > 0 ) {
		tebuto_seminar_occurrence_page( $seminar_id, $occurrence_id );
		return;
	}

	tebuto_seminars_list_page();
}

/**
 * Render the seminars accordion list page.
 *
 * @return void
 */
function tebuto_seminars_list_page(): void {
	$api = tebuto_require_tebuto_connection();
	if ( $api === null ) {
		return;
	}

	tebuto_handle_seminar_actions( $api );

	$seminars = $api->get_seminars();
	if ( is_wp_error( $seminars ) && tebuto_maybe_render_session_expired_from_error( $seminars ) ) {
		return;
	}

	tebuto_ui_page_open(
		array(
			'title'        => __( 'Seminare', 'tebuto-online-terminbuchung' ),
			'page_class'   => 'tebuto-page-seminars',
			'fullheight'   => true,
			'actions_html' => tebuto_ui_button(
				array(
					'label'   => __( 'Neues Seminar', 'tebuto-online-terminbuchung' ),
					'type'    => 'button',
					'variant' => 'solid',
					'color'   => 'primary',
					'icon'    => 'dashicons-plus-alt2',
					'attrs'   => array( 'id' => 'tebuto-add-seminar-btn' ),
				)
			) . tebuto_ui_button(
				array(
					'label'   => __( '← Dashboard', 'tebuto-online-terminbuchung' ),
					'href'    => admin_url( 'admin.php?page=tebuto-main' ),
					'variant' => 'outline',
					'color'   => 'neutral',
				)
			),
		)
	);
	?>

	<div class="tebuto-card tebuto-seminars-card">
		<?php if ( is_wp_error( $seminars ) ) : ?>
			<div class="tebuto-card-body">
				<p class="tebuto-error"><?php echo esc_html( $api->get_last_error() ); ?></p>
			</div>
		<?php elseif ( empty( $seminars ) || ! is_array( $seminars ) ) : ?>
			<div class="tebuto-card-body">
				<?php
				echo tebuto_ui_empty_state( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					array(
						'icon'         => 'dashicons-welcome-learn-more',
						'title'        => __( 'Noch keine Seminare', 'tebuto-online-terminbuchung' ),
						'body'         => __( 'Erstelle dein erstes Seminar, um Veranstaltungen und Anmeldungen zu verwalten.', 'tebuto-online-terminbuchung' ),
						'actions_html' => tebuto_ui_button(
							array(
								'label'   => __( 'Erstes Seminar erstellen', 'tebuto-online-terminbuchung' ),
								'type'    => 'button',
								'variant' => 'solid',
								'color'   => 'primary',
								'attrs'   => array( 'id' => 'tebuto-add-seminar-btn-empty' ),
							)
						),
					)
				);
				?>
			</div>
		<?php else : ?>
			<div class="tebuto-accordion" id="tebuto-seminars-accordion">
				<?php foreach ( $seminars as $seminar ) : ?>
					<?php
					$sid          = absint( $seminar['id'] ?? 0 );
					$title        = (string) ( $seminar['title'] ?? '' );
					$topic        = (string) ( $seminar['topic'] ?? '' );
					$price        = (string) ( $seminar['price'] ?? '0' );
					$is_inherited = ! empty( $seminar['isInherited'] );
					?>
					<div class="tebuto-accordion-item" data-seminar-id="<?php echo esc_attr( (string) $sid ); ?>" data-inherited="<?php echo $is_inherited ? '1' : '0'; ?>">
						<div class="tebuto-accordion-header">
							<button type="button" class="tebuto-accordion-toggle" aria-expanded="false">
								<span class="dashicons dashicons-arrow-right-alt2 tebuto-accordion-chevron"></span>
								<span class="tebuto-accordion-title">
									<strong><?php echo esc_html( $title ); ?></strong>
									<?php if ( $topic !== '' ) : ?>
										<span class="tebuto-badge tebuto-badge-default"><?php echo esc_html( $topic ); ?></span>
									<?php endif; ?>
									<?php if ( $is_inherited ) : ?>
										<span class="tebuto-badge tebuto-badge-info"><?php esc_html_e( 'Geerbt', 'tebuto-online-terminbuchung' ); ?></span>
									<?php endif; ?>
								</span>
								<span class="tebuto-accordion-meta">
									<?php echo esc_html( number_format( (float) $price, 2, ',', '.' ) ); ?> €
								</span>
							</button>
							<div class="tebuto-accordion-actions">
								<?php if ( ! $is_inherited ) : ?>
									<button type="button" class="button button-small tebuto-btn tebuto-btn--outline tebuto-btn--neutral tebuto-btn--sm tebuto-edit-seminar"
										data-seminar="<?php echo esc_attr( wp_json_encode( $seminar ) ); ?>">
										<?php esc_html_e( 'Bearbeiten', 'tebuto-online-terminbuchung' ); ?>
									</button>
									<button type="button" class="button button-small tebuto-btn tebuto-btn--outline tebuto-btn--neutral tebuto-btn--sm tebuto-add-occurrence-btn"
										data-seminar-id="<?php echo esc_attr( (string) $sid ); ?>">
										<?php esc_html_e( 'Neue Veranstaltung', 'tebuto-online-terminbuchung' ); ?>
									</button>
									<form method="post" class="tebuto-inline-form"
										data-tebuto-confirm="<?php echo esc_attr( __( 'Seminar wirklich löschen?', 'tebuto-online-terminbuchung' ) ); ?>"
										data-tebuto-confirm-title="<?php echo esc_attr( __( 'Seminar löschen', 'tebuto-online-terminbuchung' ) ); ?>"
										data-tebuto-confirm-label="<?php echo esc_attr( __( 'Löschen', 'tebuto-online-terminbuchung' ) ); ?>"
										data-tebuto-confirm-danger="1">
										<?php wp_nonce_field( 'tebuto_seminar_action', 'tebuto_seminar_nonce' ); ?>
										<input type="hidden" name="tebuto_action" value="delete_seminar">
										<input type="hidden" name="seminar_id" value="<?php echo esc_attr( (string) $sid ); ?>">
										<button type="submit" class="button button-small tebuto-btn tebuto-btn--solid tebuto-btn--danger tebuto-btn--sm">
											<?php esc_html_e( 'Löschen', 'tebuto-online-terminbuchung' ); ?>
										</button>
									</form>
								<?php endif; ?>
							</div>
						</div>
						<div class="tebuto-accordion-body" hidden>
							<div class="tebuto-accordion-loading">
								<span class="spinner is-active"></span>
								<span><?php esc_html_e( 'Veranstaltungen werden geladen…', 'tebuto-online-terminbuchung' ); ?></span>
							</div>
							<div class="tebuto-accordion-content"></div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php tebuto_render_seminar_modal(); ?>
	<?php tebuto_ui_page_close(); ?>
	<?php
}

/**
 * Render the seminar create/edit modal.
 *
 * @return void
 */
function tebuto_render_seminar_modal(): void {
	?>
	<div id="tebuto-seminar-modal" class="tebuto-modal" style="display: none;">
		<div class="tebuto-modal-content tebuto-modal-xl">
			<div class="tebuto-modal-header">
				<h3 id="tebuto-seminar-modal-title"><?php esc_html_e( 'Neues Seminar', 'tebuto-online-terminbuchung' ); ?></h3>
				<button type="button" class="tebuto-modal-close">&times;</button>
			</div>
			<form method="post" id="tebuto-seminar-form" enctype="multipart/form-data">
				<?php wp_nonce_field( 'tebuto_seminar_action', 'tebuto_seminar_nonce' ); ?>
				<input type="hidden" name="tebuto_action" id="tebuto-seminar-action" value="create_seminar">
				<input type="hidden" name="seminar_id" id="tebuto-seminar-id" value="">

				<div class="tebuto-modal-body">
					<div class="tebuto-modal-section">
						<h4><?php esc_html_e( 'Allgemein', 'tebuto-online-terminbuchung' ); ?></h4>
						<div class="tebuto-modal-field">
							<label for="seminar_title"><?php esc_html_e( 'Titel', 'tebuto-online-terminbuchung' ); ?> *</label>
							<input type="text" id="seminar_title" name="seminar_title" required maxlength="200"
								placeholder="<?php esc_attr_e( 'z.B. Einführungskurs', 'tebuto-online-terminbuchung' ); ?>">
						</div>
						<div class="tebuto-modal-row">
							<div class="tebuto-modal-field">
								<label for="seminar_subtitle"><?php esc_html_e( 'Untertitel', 'tebuto-online-terminbuchung' ); ?></label>
								<input type="text" id="seminar_subtitle" name="seminar_subtitle" maxlength="300">
							</div>
							<div class="tebuto-modal-field">
								<label for="seminar_topic"><?php esc_html_e( 'Thema', 'tebuto-online-terminbuchung' ); ?></label>
								<input type="text" id="seminar_topic" name="seminar_topic" maxlength="120">
							</div>
						</div>
						<div class="tebuto-modal-field">
							<label for="seminar_description"><?php esc_html_e( 'Beschreibung', 'tebuto-online-terminbuchung' ); ?> *</label>
							<?php
							wp_editor(
								'',
								'seminar_description',
								array(
									'textarea_name' => 'seminar_description',
									'textarea_rows' => 8,
									'media_buttons' => false,
									'teeny'         => true,
									'quicktags'     => true,
								)
							);
							?>
						</div>
						<div class="tebuto-modal-field">
							<label for="seminar_banner"><?php esc_html_e( 'Banner-Bild', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="file" id="seminar_banner" name="seminar_banner" accept="image/png,image/jpeg,image/webp">
							<p class="tebuto-field-hint"><?php esc_html_e( 'PNG, JPEG oder WebP, max. 5 MB.', 'tebuto-online-terminbuchung' ); ?></p>
							<div id="tebuto-seminar-banner-preview" class="tebuto-seminar-banner-preview" hidden>
								<img src="" alt="" />
							</div>
						</div>
					</div>

					<div class="tebuto-modal-section">
						<h4><?php esc_html_e( 'Preis & Buchung', 'tebuto-online-terminbuchung' ); ?></h4>
						<div class="tebuto-modal-row">
							<div class="tebuto-modal-field">
								<label for="seminar_price"><?php esc_html_e( 'Preis (€)', 'tebuto-online-terminbuchung' ); ?> *</label>
								<input type="number" id="seminar_price" name="seminar_price" required min="0" step="0.01" value="0">
							</div>
							<div class="tebuto-modal-field">
								<label for="seminar_tax_rate"><?php esc_html_e( 'MwSt. (%)', 'tebuto-online-terminbuchung' ); ?></label>
								<input type="number" id="seminar_tax_rate" name="seminar_tax_rate" min="0" max="100" step="0.01" value="19">
							</div>
						</div>
					</div>

					<div class="tebuto-modal-section" id="tebuto-seminar-occurrence-fieldset">
						<h4><?php esc_html_e( 'Erste Veranstaltung', 'tebuto-online-terminbuchung' ); ?></h4>
						<p class="tebuto-field-hint"><?php esc_html_e( 'Beim Anlegen eines Seminars muss eine erste Veranstaltung angegeben werden. Termine kannst du danach ergänzen.', 'tebuto-online-terminbuchung' ); ?></p>
						<div class="tebuto-modal-field">
							<label for="occurrence_label"><?php esc_html_e( 'Bezeichnung (optional)', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="text" id="occurrence_label" name="occurrence_label" maxlength="120">
						</div>
						<div class="tebuto-modal-row">
							<div class="tebuto-modal-field">
								<label for="occurrence_location_type"><?php esc_html_e( 'Ort', 'tebuto-online-terminbuchung' ); ?> *</label>
								<select id="occurrence_location_type" name="occurrence_location_type" required>
									<option value="virtual"><?php esc_html_e( 'Online', 'tebuto-online-terminbuchung' ); ?></option>
									<option value="onsite"><?php esc_html_e( 'Vor Ort', 'tebuto-online-terminbuchung' ); ?></option>
									<option value="not-fixed"><?php esc_html_e( 'Flexibel', 'tebuto-online-terminbuchung' ); ?></option>
								</select>
							</div>
							<div class="tebuto-modal-field">
								<label for="occurrence_capacity"><?php esc_html_e( 'Kapazität', 'tebuto-online-terminbuchung' ); ?> *</label>
								<input type="number" id="occurrence_capacity" name="occurrence_capacity" min="1" value="10" required>
							</div>
						</div>
						<div id="tebuto-occurrence-address-fields" class="tebuto-occurrence-address" hidden>
							<div class="tebuto-modal-field">
								<label for="occurrence_location_name"><?php esc_html_e( 'Ort / Name', 'tebuto-online-terminbuchung' ); ?></label>
								<input type="text" id="occurrence_location_name" name="occurrence_location_name" maxlength="200">
							</div>
							<div class="tebuto-modal-row">
								<div class="tebuto-modal-field">
									<label for="occurrence_street"><?php esc_html_e( 'Straße und Nr.', 'tebuto-online-terminbuchung' ); ?></label>
									<input type="text" id="occurrence_street" name="occurrence_street" maxlength="200">
								</div>
								<div class="tebuto-modal-field">
									<label for="occurrence_city_zip"><?php esc_html_e( 'PLZ / Ort', 'tebuto-online-terminbuchung' ); ?></label>
									<input type="text" id="occurrence_city_zip" name="occurrence_city_zip">
								</div>
							</div>
						</div>
						<div class="tebuto-modal-row">
							<div class="tebuto-modal-field">
								<label for="registration_opens_at"><?php esc_html_e( 'Anmeldung ab', 'tebuto-online-terminbuchung' ); ?></label>
								<input type="date" id="registration_opens_at" name="registration_opens_at">
							</div>
							<div class="tebuto-modal-field">
								<label for="registration_closes_at"><?php esc_html_e( 'Anmeldung bis', 'tebuto-online-terminbuchung' ); ?></label>
								<input type="date" id="registration_closes_at" name="registration_closes_at">
							</div>
						</div>
					</div>
				</div>

				<div class="tebuto-modal-footer">
					<button type="button" class="button tebuto-btn tebuto-btn--outline tebuto-btn--neutral tebuto-modal-close-btn"><?php esc_html_e( 'Abbrechen', 'tebuto-online-terminbuchung' ); ?></button>
					<button type="submit" class="button button-primary tebuto-btn tebuto-btn--solid tebuto-btn--primary" id="tebuto-seminar-submit">
						<?php esc_html_e( 'Seminar erstellen', 'tebuto-online-terminbuchung' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>

	<div id="tebuto-create-occurrence-modal" class="tebuto-modal" style="display: none;">
		<div class="tebuto-modal-content tebuto-modal-lg">
			<div class="tebuto-modal-header">
				<h3><?php esc_html_e( 'Neue Veranstaltung', 'tebuto-online-terminbuchung' ); ?></h3>
				<button type="button" class="tebuto-modal-close">&times;</button>
			</div>
			<form method="post" id="tebuto-create-occurrence-form">
				<?php wp_nonce_field( 'tebuto_seminar_action', 'tebuto_seminar_nonce' ); ?>
				<input type="hidden" name="tebuto_action" value="create_occurrence">
				<input type="hidden" name="seminar_id" id="tebuto-create-occurrence-seminar-id" value="">
				<div class="tebuto-modal-body">
					<div class="tebuto-modal-field">
						<label for="new_occurrence_label"><?php esc_html_e( 'Bezeichnung (optional)', 'tebuto-online-terminbuchung' ); ?></label>
						<input type="text" id="new_occurrence_label" name="occurrence_label" maxlength="120">
					</div>
					<div class="tebuto-modal-row">
						<div class="tebuto-modal-field">
							<label for="new_occurrence_location_type"><?php esc_html_e( 'Ort', 'tebuto-online-terminbuchung' ); ?> *</label>
							<select id="new_occurrence_location_type" name="occurrence_location_type" required>
								<option value="virtual"><?php esc_html_e( 'Online', 'tebuto-online-terminbuchung' ); ?></option>
								<option value="onsite"><?php esc_html_e( 'Vor Ort', 'tebuto-online-terminbuchung' ); ?></option>
								<option value="not-fixed"><?php esc_html_e( 'Flexibel', 'tebuto-online-terminbuchung' ); ?></option>
							</select>
						</div>
						<div class="tebuto-modal-field">
							<label for="new_occurrence_capacity"><?php esc_html_e( 'Kapazität', 'tebuto-online-terminbuchung' ); ?> *</label>
							<input type="number" id="new_occurrence_capacity" name="occurrence_capacity" min="1" value="10" required>
						</div>
					</div>
					<div id="tebuto-new-occurrence-address" class="tebuto-occurrence-address" hidden>
						<div class="tebuto-modal-field">
							<label for="new_occurrence_location_name"><?php esc_html_e( 'Ort / Name', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="text" id="new_occurrence_location_name" name="occurrence_location_name" maxlength="200">
						</div>
						<div class="tebuto-modal-row">
							<div class="tebuto-modal-field">
								<label for="new_occurrence_street"><?php esc_html_e( 'Straße und Nr.', 'tebuto-online-terminbuchung' ); ?></label>
								<input type="text" id="new_occurrence_street" name="occurrence_street" maxlength="200">
							</div>
							<div class="tebuto-modal-field">
								<label for="new_occurrence_city_zip"><?php esc_html_e( 'PLZ / Ort', 'tebuto-online-terminbuchung' ); ?></label>
								<input type="text" id="new_occurrence_city_zip" name="occurrence_city_zip">
							</div>
						</div>
					</div>
					<div class="tebuto-modal-row">
						<div class="tebuto-modal-field">
							<label for="new_registration_opens"><?php esc_html_e( 'Anmeldung ab', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="date" id="new_registration_opens" name="registration_opens_at">
						</div>
						<div class="tebuto-modal-field">
							<label for="new_registration_closes"><?php esc_html_e( 'Anmeldung bis', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="date" id="new_registration_closes" name="registration_closes_at">
						</div>
					</div>
					<p class="tebuto-field-hint"><?php esc_html_e( 'Termine kannst du nach dem Erstellen auf der Veranstaltungsseite hinzufügen.', 'tebuto-online-terminbuchung' ); ?></p>
				</div>
				<div class="tebuto-modal-footer">
					<button type="button" class="button tebuto-btn tebuto-btn--outline tebuto-btn--neutral tebuto-modal-close-btn"><?php esc_html_e( 'Abbrechen', 'tebuto-online-terminbuchung' ); ?></button>
					<button type="submit" class="button button-primary tebuto-btn tebuto-btn--solid tebuto-btn--primary"><?php esc_html_e( 'Veranstaltung erstellen', 'tebuto-online-terminbuchung' ); ?></button>
				</div>
			</form>
		</div>
	</div>
	<?php
}

/**
 * Handle seminar form POST actions.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_seminar_actions( Tebuto_API $api ): void {
	if ( ! isset( $_POST['tebuto_action'], $_POST['tebuto_seminar_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['tebuto_seminar_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'tebuto_seminar_action' ) ) {
		tebuto_admin_notice( __( 'Ungültige Anfrage.', 'tebuto-online-terminbuchung' ), 'error' );
		return;
	}

	$action = sanitize_text_field( wp_unslash( $_POST['tebuto_action'] ) );

	switch ( $action ) {
		case 'create_seminar':
			tebuto_handle_create_seminar( $api );
			break;
		case 'update_seminar':
			tebuto_handle_update_seminar( $api );
			break;
		case 'delete_seminar':
			tebuto_handle_delete_seminar( $api );
			break;
		case 'create_occurrence':
			tebuto_handle_create_occurrence( $api );
			break;
		case 'update_occurrence':
			tebuto_handle_update_occurrence( $api );
			break;
		case 'update_occurrence_sessions':
			tebuto_handle_update_occurrence_sessions( $api );
			break;
	}
}

/**
 * Normalize a decimal form value to a 2-digit string.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function tebuto_normalize_decimal( $value ): string {
	$number = is_numeric( $value ) ? (float) $value : 0.0;
	return number_format( $number, 2, '.', '' );
}

/**
 * Convert a datetime-local value to an ISO-8601 string in the WP timezone.
 *
 * @param string $value datetime-local value (Y-m-d\TH:i).
 * @return string|null
 */
function tebuto_datetime_local_to_iso( string $value ): ?string {
	$value = trim( $value );
	if ( $value === '' ) {
		return null;
	}

	try {
		$tz = wp_timezone();
		$dt = date_create_immutable_from_format( 'Y-m-d\TH:i', $value, $tz );
		if ( ! $dt ) {
			$dt = date_create_immutable( $value, $tz );
		}
		if ( ! $dt ) {
			return null;
		}
		return $dt->format( DATE_ATOM );
	} catch ( Exception $e ) {
		return null;
	}
}

/**
 * Convert a date (Y-m-d) to ISO start-of-day in the WP timezone.
 *
 * @param string $value Date value.
 * @return string|null
 */
function tebuto_date_to_iso_start_of_day( string $value ): ?string {
	$value = trim( $value );
	if ( $value === '' ) {
		return null;
	}

	try {
		$dt = date_create_immutable_from_format( 'Y-m-d', $value, wp_timezone() );
		if ( ! $dt ) {
			return null;
		}
		return $dt->setTime( 0, 0, 0 )->format( DATE_ATOM );
	} catch ( Exception $e ) {
		return null;
	}
}

/**
 * Convert a date (Y-m-d) to ISO end-of-day in the WP timezone.
 *
 * @param string $value Date value.
 * @return string|null
 */
function tebuto_date_to_iso_end_of_day( string $value ): ?string {
	$value = trim( $value );
	if ( $value === '' ) {
		return null;
	}

	try {
		$dt = date_create_immutable_from_format( 'Y-m-d', $value, wp_timezone() );
		if ( ! $dt ) {
			return null;
		}
		return $dt->setTime( 23, 59, 59 )->format( DATE_ATOM );
	} catch ( Exception $e ) {
		return null;
	}
}

/**
 * Collect seminar form fields shared by create/update.
 *
 * @return array|WP_Error
 */
function tebuto_get_seminar_form_data() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by caller.
	$title       = isset( $_POST['seminar_title'] ) ? sanitize_text_field( wp_unslash( $_POST['seminar_title'] ) ) : '';
	$subtitle    = isset( $_POST['seminar_subtitle'] ) ? sanitize_text_field( wp_unslash( $_POST['seminar_subtitle'] ) ) : '';
	$topic       = isset( $_POST['seminar_topic'] ) ? sanitize_text_field( wp_unslash( $_POST['seminar_topic'] ) ) : '';
	$description = isset( $_POST['seminar_description'] ) ? wp_kses_post( wp_unslash( $_POST['seminar_description'] ) ) : '';
	$price       = isset( $_POST['seminar_price'] ) ? tebuto_normalize_decimal( sanitize_text_field( wp_unslash( $_POST['seminar_price'] ) ) ) : '0.00';
	$tax_raw     = isset( $_POST['seminar_tax_rate'] ) ? sanitize_text_field( wp_unslash( $_POST['seminar_tax_rate'] ) ) : '';
	$tax_rate    = $tax_raw !== '' ? tebuto_normalize_decimal( $tax_raw ) : null;
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( $title === '' ) {
		return new WP_Error( 'missing_title', __( 'Bitte gib einen Titel ein.', 'tebuto-online-terminbuchung' ) );
	}

	if ( trim( wp_strip_all_tags( $description ) ) === '' ) {
		return new WP_Error( 'missing_description', __( 'Bitte gib eine Beschreibung ein.', 'tebuto-online-terminbuchung' ) );
	}

	$data = array(
		'title'       => $title,
		'description' => $description,
		'price'       => $price,
	);

	$data['subtitle'] = $subtitle !== '' ? $subtitle : null;
	$data['topic']    = $topic !== '' ? $topic : null;
	$data['taxRate']  = $tax_rate;

	return $data;
}

/**
 * Collect occurrence fields from POST for create flows.
 *
 * Sessions are omitted here — they are edited on the occurrence detail page.
 *
 * @param bool $require_sessions Unused; kept for call-site compatibility.
 * @return array|WP_Error
 */
function tebuto_get_occurrence_form_data( bool $require_sessions = false ) {
	unset( $require_sessions );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by caller.
	$label         = isset( $_POST['occurrence_label'] ) ? sanitize_text_field( wp_unslash( $_POST['occurrence_label'] ) ) : '';
	$location_type = isset( $_POST['occurrence_location_type'] ) ? sanitize_text_field( wp_unslash( $_POST['occurrence_location_type'] ) ) : 'virtual';
	$capacity      = isset( $_POST['occurrence_capacity'] ) ? absint( $_POST['occurrence_capacity'] ) : 0;
	$location_name = isset( $_POST['occurrence_location_name'] ) ? sanitize_text_field( wp_unslash( $_POST['occurrence_location_name'] ) ) : '';
	$street        = isset( $_POST['occurrence_street'] ) ? sanitize_text_field( wp_unslash( $_POST['occurrence_street'] ) ) : '';
	$city_zip      = isset( $_POST['occurrence_city_zip'] ) ? sanitize_text_field( wp_unslash( $_POST['occurrence_city_zip'] ) ) : '';
	$reg_opens     = isset( $_POST['registration_opens_at'] ) ? sanitize_text_field( wp_unslash( $_POST['registration_opens_at'] ) ) : '';
	$reg_closes    = isset( $_POST['registration_closes_at'] ) ? sanitize_text_field( wp_unslash( $_POST['registration_closes_at'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( ! in_array( $location_type, array( 'virtual', 'onsite', 'not-fixed' ), true ) ) {
		$location_type = 'virtual';
	}

	if ( $capacity < 1 ) {
		return new WP_Error( 'invalid_capacity', __( 'Die Kapazität muss mindestens 1 betragen.', 'tebuto-online-terminbuchung' ) );
	}

	$data = array(
		'locationType'    => $location_type,
		'capacity'        => $capacity,
		'waitlistEnabled' => false,
		'status'          => 'draft',
		'sessions'        => array(),
	);

	if ( $label !== '' ) {
		$data['label'] = $label;
	}

	if ( $location_type === 'onsite' ) {
		$data['locationName']          = $location_name !== '' ? $location_name : null;
		$data['streetAndNumber']       = $street !== '' ? $street : null;
		$data['cityZip']               = $city_zip !== '' ? $city_zip : null;
		$data['additionalInformation'] = null;
	}

	$opens_iso  = tebuto_date_to_iso_start_of_day( $reg_opens );
	$closes_iso = tebuto_date_to_iso_end_of_day( $reg_closes );
	if ( $opens_iso ) {
		$data['registrationOpensAt'] = $opens_iso;
	}
	if ( $closes_iso ) {
		$data['registrationClosesAt'] = $closes_iso;
	}

	return $data;
}

/**
 * Handle create seminar.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_create_seminar( Tebuto_API $api ): void {
	$seminar_data = tebuto_get_seminar_form_data();
	if ( is_wp_error( $seminar_data ) ) {
		tebuto_admin_notice( $seminar_data->get_error_message(), 'error' );
		return;
	}

	$occurrence = tebuto_get_occurrence_form_data();
	if ( is_wp_error( $occurrence ) ) {
		tebuto_admin_notice( $occurrence->get_error_message(), 'error' );
		return;
	}

	$seminar_data['occurrence'] = $occurrence;

	$result = $api->create_seminar( $seminar_data );
	if ( is_wp_error( $result ) ) {
		tebuto_admin_notice(
			sprintf(
				/* translators: %s: error message */
				__( 'Fehler beim Erstellen: %s', 'tebuto-online-terminbuchung' ),
				$api->get_last_error()
			),
			'error'
		);
		return;
	}

	$seminar_id = absint( $result['id'] ?? ( $result['seminar']['id'] ?? 0 ) );
	// Nonce verified in tebuto_handle_seminar_actions().
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File validated in API method.
	if ( $seminar_id > 0 && ! empty( $_FILES['seminar_banner']['tmp_name'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
		$upload = $api->upload_seminar_banner( $seminar_id, $_FILES['seminar_banner'] );
		if ( is_wp_error( $upload ) ) {
			tebuto_admin_notice(
				sprintf(
					/* translators: %s: error message */
					__( 'Seminar erstellt, aber Banner-Upload fehlgeschlagen: %s', 'tebuto-online-terminbuchung' ),
					$upload->get_error_message()
				),
				'warning'
			);
			return;
		}
	}

	tebuto_admin_notice( __( 'Seminar erfolgreich erstellt.', 'tebuto-online-terminbuchung' ), 'success' );
}

/**
 * Handle update seminar.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_update_seminar( Tebuto_API $api ): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by caller.
	$seminar_id = isset( $_POST['seminar_id'] ) ? absint( $_POST['seminar_id'] ) : 0;
	if ( $seminar_id < 1 ) {
		tebuto_admin_notice( __( 'Ungültige Seminar-ID.', 'tebuto-online-terminbuchung' ), 'error' );
		return;
	}

	$data = tebuto_get_seminar_form_data();
	if ( is_wp_error( $data ) ) {
		tebuto_admin_notice( $data->get_error_message(), 'error' );
		return;
	}

	$result = $api->update_seminar( $seminar_id, $data );
	if ( is_wp_error( $result ) ) {
		tebuto_admin_notice(
			sprintf(
				/* translators: %s: error message */
				__( 'Fehler beim Aktualisieren: %s', 'tebuto-online-terminbuchung' ),
				$api->get_last_error()
			),
			'error'
		);
		return;
	}

	// Nonce verified in tebuto_handle_seminar_actions().
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File validated in API method.
	if ( ! empty( $_FILES['seminar_banner']['tmp_name'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
		$upload = $api->upload_seminar_banner( $seminar_id, $_FILES['seminar_banner'] );
		if ( is_wp_error( $upload ) ) {
			tebuto_admin_notice(
				sprintf(
					/* translators: %s: error message */
					__( 'Seminar gespeichert, aber Banner-Upload fehlgeschlagen: %s', 'tebuto-online-terminbuchung' ),
					$upload->get_error_message()
				),
				'warning'
			);
			return;
		}
	}

	tebuto_admin_notice( __( 'Seminar erfolgreich aktualisiert.', 'tebuto-online-terminbuchung' ), 'success' );
}

/**
 * Handle delete seminar.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_delete_seminar( Tebuto_API $api ): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by caller.
	$seminar_id = isset( $_POST['seminar_id'] ) ? absint( $_POST['seminar_id'] ) : 0;
	if ( $seminar_id < 1 ) {
		return;
	}

	$result = $api->delete_seminar( $seminar_id );
	if ( is_wp_error( $result ) ) {
		tebuto_admin_notice(
			sprintf(
				/* translators: %s: error message */
				__( 'Fehler beim Löschen: %s', 'tebuto-online-terminbuchung' ),
				$api->get_last_error()
			),
			'error'
		);
		return;
	}

	tebuto_admin_notice( __( 'Seminar erfolgreich gelöscht.', 'tebuto-online-terminbuchung' ), 'success' );
}

/**
 * Handle create occurrence.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_create_occurrence( Tebuto_API $api ): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by caller.
	$seminar_id = isset( $_POST['seminar_id'] ) ? absint( $_POST['seminar_id'] ) : 0;
	if ( $seminar_id < 1 ) {
		tebuto_admin_notice( __( 'Ungültige Seminar-ID.', 'tebuto-online-terminbuchung' ), 'error' );
		return;
	}

	$data = tebuto_get_occurrence_form_data();
	if ( is_wp_error( $data ) ) {
		tebuto_admin_notice( $data->get_error_message(), 'error' );
		return;
	}

	$result = $api->create_seminar_occurrence( $seminar_id, $data );
	if ( is_wp_error( $result ) ) {
		tebuto_admin_notice(
			sprintf(
				/* translators: %s: error message */
				__( 'Fehler beim Erstellen der Veranstaltung: %s', 'tebuto-online-terminbuchung' ),
				$api->get_last_error()
			),
			'error'
		);
		return;
	}

	tebuto_admin_notice( __( 'Veranstaltung erfolgreich erstellt.', 'tebuto-online-terminbuchung' ), 'success' );
}

/**
 * Handle update occurrence settings.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_update_occurrence( Tebuto_API $api ): void {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by caller.
	$occurrence_id  = isset( $_POST['occurrence_id'] ) ? absint( $_POST['occurrence_id'] ) : 0;
	$label          = isset( $_POST['occurrence_label'] ) ? sanitize_text_field( wp_unslash( $_POST['occurrence_label'] ) ) : '';
	$location_type  = isset( $_POST['occurrence_location_type'] ) ? sanitize_text_field( wp_unslash( $_POST['occurrence_location_type'] ) ) : 'virtual';
	$capacity       = isset( $_POST['occurrence_capacity'] ) ? absint( $_POST['occurrence_capacity'] ) : 0;
	$location_name  = isset( $_POST['occurrence_location_name'] ) ? sanitize_text_field( wp_unslash( $_POST['occurrence_location_name'] ) ) : '';
	$street         = isset( $_POST['occurrence_street'] ) ? sanitize_text_field( wp_unslash( $_POST['occurrence_street'] ) ) : '';
	$city_zip       = isset( $_POST['occurrence_city_zip'] ) ? sanitize_text_field( wp_unslash( $_POST['occurrence_city_zip'] ) ) : '';
	$additional     = isset( $_POST['occurrence_additional'] ) ? sanitize_textarea_field( wp_unslash( $_POST['occurrence_additional'] ) ) : '';
	$reg_opens      = isset( $_POST['registration_opens_at'] ) ? sanitize_text_field( wp_unslash( $_POST['registration_opens_at'] ) ) : '';
	$reg_closes     = isset( $_POST['registration_closes_at'] ) ? sanitize_text_field( wp_unslash( $_POST['registration_closes_at'] ) ) : '';
	$price_override = isset( $_POST['price_override'] ) ? sanitize_text_field( wp_unslash( $_POST['price_override'] ) ) : '';
	$tax_override   = isset( $_POST['tax_rate_override'] ) ? sanitize_text_field( wp_unslash( $_POST['tax_rate_override'] ) ) : '';
	$outage_enabled = isset( $_POST['outage_fee_enabled'] );
	$outage_days    = isset( $_POST['outage_fee_days'] ) ? absint( $_POST['outage_fee_days'] ) : 0;
	$outage_price   = isset( $_POST['outage_fee_price'] ) ? sanitize_text_field( wp_unslash( $_POST['outage_fee_price'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( $occurrence_id < 1 ) {
		tebuto_admin_notice( __( 'Ungültige Veranstaltungs-ID.', 'tebuto-online-terminbuchung' ), 'error' );
		return;
	}

	if ( $capacity < 1 ) {
		tebuto_admin_notice( __( 'Die Kapazität muss mindestens 1 betragen.', 'tebuto-online-terminbuchung' ), 'error' );
		return;
	}

	if ( ! in_array( $location_type, array( 'virtual', 'onsite', 'not-fixed' ), true ) ) {
		$location_type = 'virtual';
	}

	$data = array(
		'label'            => $label !== '' ? $label : null,
		'locationType'     => $location_type,
		'capacity'         => $capacity,
		'waitlistEnabled'  => false,
		'outageFeeEnabled' => $outage_enabled,
	);

	if ( $location_type === 'onsite' ) {
		$data['locationName']          = $location_name !== '' ? $location_name : null;
		$data['streetAndNumber']       = $street !== '' ? $street : null;
		$data['cityZip']               = $city_zip !== '' ? $city_zip : null;
		$data['additionalInformation'] = $additional !== '' ? $additional : null;
	} else {
		$data['locationName']          = null;
		$data['streetAndNumber']       = null;
		$data['cityZip']               = null;
		$data['additionalInformation'] = null;
	}

	$data['registrationOpensAt']  = tebuto_date_to_iso_start_of_day( $reg_opens );
	$data['registrationClosesAt'] = tebuto_date_to_iso_end_of_day( $reg_closes );
	$data['priceOverride']        = $price_override !== '' ? tebuto_normalize_decimal( $price_override ) : null;
	$data['taxRateOverride']      = $tax_override !== '' ? tebuto_normalize_decimal( $tax_override ) : null;
	$data['outageFeeDays']        = $outage_enabled && $outage_days > 0 ? $outage_days : null;
	$data['outageFeePrice']       = $outage_enabled && $outage_price !== '' ? tebuto_normalize_decimal( $outage_price ) : null;

	$result = $api->update_seminar_occurrence( $occurrence_id, $data );
	if ( is_wp_error( $result ) ) {
		tebuto_admin_notice(
			sprintf(
				/* translators: %s: error message */
				__( 'Fehler beim Speichern: %s', 'tebuto-online-terminbuchung' ),
				$api->get_last_error()
			),
			'error'
		);
		return;
	}

	tebuto_admin_notice( __( 'Veranstaltung erfolgreich aktualisiert.', 'tebuto-online-terminbuchung' ), 'success' );
}

/**
 * Handle update occurrence sessions.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_update_occurrence_sessions( Tebuto_API $api ): void {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by caller.
	$occurrence_id = isset( $_POST['occurrence_id'] ) ? absint( $_POST['occurrence_id'] ) : 0;
	$starts        = isset( $_POST['session_starts'] ) && is_array( $_POST['session_starts'] ) ? wp_unslash( $_POST['session_starts'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$ends          = isset( $_POST['session_ends'] ) && is_array( $_POST['session_ends'] ) ? wp_unslash( $_POST['session_ends'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$labels        = isset( $_POST['session_labels'] ) && is_array( $_POST['session_labels'] ) ? wp_unslash( $_POST['session_labels'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$ids           = isset( $_POST['session_ids'] ) && is_array( $_POST['session_ids'] ) ? wp_unslash( $_POST['session_ids'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( $occurrence_id < 1 ) {
		tebuto_admin_notice( __( 'Ungültige Veranstaltungs-ID.', 'tebuto-online-terminbuchung' ), 'error' );
		return;
	}

	$sessions = array();
	$count    = max( count( $starts ), count( $ends ) );

	for ( $i = 0; $i < $count; $i++ ) {
		$start_raw = isset( $starts[ $i ] ) ? sanitize_text_field( $starts[ $i ] ) : '';
		$end_raw   = isset( $ends[ $i ] ) ? sanitize_text_field( $ends[ $i ] ) : '';
		$label_raw = isset( $labels[ $i ] ) ? sanitize_text_field( $labels[ $i ] ) : '';
		$id_raw    = isset( $ids[ $i ] ) ? absint( $ids[ $i ] ) : 0;

		if ( $start_raw === '' && $end_raw === '' ) {
			continue;
		}

		$start_iso = tebuto_datetime_local_to_iso( $start_raw );
		$end_iso   = tebuto_datetime_local_to_iso( $end_raw );

		if ( ! $start_iso || ! $end_iso ) {
			tebuto_admin_notice( __( 'Bitte gib für jede Sitzung Beginn und Ende an.', 'tebuto-online-terminbuchung' ), 'error' );
			return;
		}

		if ( strtotime( $end_iso ) <= strtotime( $start_iso ) ) {
			tebuto_admin_notice( __( 'Das Ende jeder Sitzung muss nach dem Beginn liegen.', 'tebuto-online-terminbuchung' ), 'error' );
			return;
		}

		$session = array(
			'start' => $start_iso,
			'end'   => $end_iso,
		);

		if ( $label_raw !== '' ) {
			$session['label'] = $label_raw;
		}

		if ( $id_raw > 0 ) {
			$session['id'] = $id_raw;
		}

		$sessions[] = $session;
	}

	$result = $api->update_seminar_occurrence_sessions( $occurrence_id, $sessions );
	if ( is_wp_error( $result ) ) {
		tebuto_admin_notice(
			sprintf(
				/* translators: %s: error message */
				__( 'Fehler beim Speichern der Termine: %s', 'tebuto-online-terminbuchung' ),
				$api->get_last_error()
			),
			'error'
		);
		return;
	}

	tebuto_admin_notice( __( 'Termine erfolgreich gespeichert.', 'tebuto-online-terminbuchung' ), 'success' );
}
