<?php
/**
 * Tebuto Categories Management Page.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the categories management page.
 *
 * @return void
 */
function tebuto_categories_page(): void {
	$api = tebuto_require_tebuto_connection();
	if ( $api === null ) {
		return;
	}

	// Handle form submissions
	tebuto_handle_category_actions( $api );

	$categories = $api->get_event_categories();
	if ( is_wp_error( $categories ) && tebuto_maybe_render_session_expired_from_error( $categories ) ) {
		return;
	}

	?>
	<?php
	tebuto_ui_page_open(
		array(
			'title'        => __( 'Terminkategorien', 'tebuto-online-terminbuchung' ),
			'page_class'   => 'tebuto-page-categories',
			'fullheight'   => true,
			'actions_html' => tebuto_ui_button(
				array(
					'label'   => __( 'Neue Kategorie', 'tebuto-online-terminbuchung' ),
					'type'    => 'button',
					'variant' => 'solid',
					'color'   => 'primary',
					'icon'    => 'dashicons-plus-alt2',
					'attrs'   => array( 'id' => 'tebuto-add-category-btn' ),
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

		<!-- Categories Table -->
		<div class="tebuto-card">
			<?php if ( is_wp_error( $categories ) ) : ?>
				<div class="tebuto-card-body">
					<p class="tebuto-error"><?php echo esc_html( $api->get_last_error() ); ?></p>
				</div>
			<?php elseif ( empty( $categories ) ) : ?>
				<div class="tebuto-card-body">
					<div class="tebuto-empty-state">
						<span class="dashicons dashicons-category"></span>
						<p><?php esc_html_e( 'Noch keine Kategorien vorhanden.', 'tebuto-online-terminbuchung' ); ?></p>
						<button type="button" class="button button-primary tebuto-btn tebuto-btn--solid tebuto-btn--primary" id="tebuto-add-category-btn-empty">
							<?php esc_html_e( 'Erste Kategorie erstellen', 'tebuto-online-terminbuchung' ); ?>
						</button>
					</div>
				</div>
			<?php else : ?>
				<div class="tebuto-table-responsive">
					<table class="tebuto-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Name', 'tebuto-online-terminbuchung' ); ?></th>
								<th><?php esc_html_e( 'Dauer', 'tebuto-online-terminbuchung' ); ?></th>
								<th><?php esc_html_e( 'Preis', 'tebuto-online-terminbuchung' ); ?></th>
								<th><?php esc_html_e( 'Ort', 'tebuto-online-terminbuchung' ); ?></th>
								<th><?php esc_html_e( 'Buchung', 'tebuto-online-terminbuchung' ); ?></th>
								<th class="tebuto-actions-col"><?php esc_html_e( 'Aktionen', 'tebuto-online-terminbuchung' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $categories as $category ) : ?>
								<tr>
									<td>
										<div class="tebuto-category-name-cell">
											<span class="tebuto-category-color-dot" style="background-color: <?php echo esc_attr( $category['color'] ); ?>"></span>
											<strong><?php echo esc_html( $category['name'] ); ?></strong>
										</div>
									</td>
									<td><?php echo esc_html( $category['duration'] ); ?> Min.</td>
									<td><?php echo esc_html( number_format( (float) $category['price'], 2, ',', '.' ) ); ?> €</td>
									<td>
										<?php
										$location_labels = array(
											'onsite'    => __( 'Vor Ort', 'tebuto-online-terminbuchung' ),
											'virtual'   => __( 'Online', 'tebuto-online-terminbuchung' ),
											'not-fixed' => __( 'Flexibel', 'tebuto-online-terminbuchung' ),
										);
										echo esc_html( $location_labels[ $category['location'] ] ?? $category['location'] );
										?>
									</td>
									<td>
										<?php if ( $category['publicBookingEnabled'] ) : ?>
											<span class="tebuto-badge tebuto-badge-success"><?php esc_html_e( 'Öffentlich', 'tebuto-online-terminbuchung' ); ?></span>
										<?php endif; ?>
										<?php if ( $category['privateBookingEnabled'] ) : ?>
											<span class="tebuto-badge tebuto-badge-info"><?php esc_html_e( 'Privat', 'tebuto-online-terminbuchung' ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<div class="tebuto-action-buttons">
											<button type="button" class="button button-small tebuto-btn tebuto-btn--outline tebuto-btn--neutral tebuto-btn--sm tebuto-edit-category"
												data-category="<?php echo esc_attr( wp_json_encode( $category ) ); ?>">
												<?php esc_html_e( 'Bearbeiten', 'tebuto-online-terminbuchung' ); ?>
											</button>
											<form method="post" style="display: inline;"
												data-tebuto-confirm="<?php echo esc_attr( __( 'Kategorie wirklich löschen?', 'tebuto-online-terminbuchung' ) ); ?>"
												data-tebuto-confirm-title="<?php echo esc_attr( __( 'Kategorie löschen', 'tebuto-online-terminbuchung' ) ); ?>"
												data-tebuto-confirm-label="<?php echo esc_attr( __( 'Löschen', 'tebuto-online-terminbuchung' ) ); ?>"
												data-tebuto-confirm-danger="1">
												<?php wp_nonce_field( 'tebuto_category_action', 'tebuto_category_nonce' ); ?>
												<input type="hidden" name="tebuto_action" value="delete_category">
												<input type="hidden" name="category_id" value="<?php echo esc_attr( $category['id'] ); ?>">
												<button type="submit" class="button button-small tebuto-btn tebuto-btn--solid tebuto-btn--danger tebuto-btn--sm">
													<?php esc_html_e( 'Löschen', 'tebuto-online-terminbuchung' ); ?>
												</button>
											</form>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>

		<!-- Category Modal -->
		<div id="tebuto-category-modal" class="tebuto-modal" style="display: none;">
			<div class="tebuto-modal-content tebuto-modal-lg">
				<div class="tebuto-modal-header">
					<h3 id="tebuto-category-modal-title"><?php esc_html_e( 'Neue Kategorie', 'tebuto-online-terminbuchung' ); ?></h3>
					<button type="button" class="tebuto-modal-close">&times;</button>
				</div>
				<form method="post" id="tebuto-category-form">
					<?php wp_nonce_field( 'tebuto_category_action', 'tebuto_category_nonce' ); ?>
					<input type="hidden" name="tebuto_action" id="tebuto-category-action" value="create_category">
					<input type="hidden" name="category_id" id="tebuto-category-id" value="">

					<div class="tebuto-modal-body">
						<div class="tebuto-modal-section">
							<h4><?php esc_html_e( 'Allgemein', 'tebuto-online-terminbuchung' ); ?></h4>

							<div class="tebuto-modal-row">
								<div class="tebuto-modal-field tebuto-modal-field-grow">
									<label for="category_name"><?php esc_html_e( 'Bezeichnung', 'tebuto-online-terminbuchung' ); ?> *</label>
									<input type="text" id="category_name" name="category_name" required
										placeholder="<?php esc_attr_e( 'z.B. Erstgespräch', 'tebuto-online-terminbuchung' ); ?>">
								</div>
								<div class="tebuto-modal-field tebuto-modal-field-auto">
									<label for="category_color"><?php esc_html_e( 'Farbe', 'tebuto-online-terminbuchung' ); ?></label>
									<div class="tebuto-color-input">
										<input type="color" id="category_color" name="category_color" value="<?php echo esc_attr( TEBUTO_COLOR_FALLBACK ); ?>">
										<label class="screen-reader-text" for="category_color_hex"><?php esc_html_e( 'Farbe als Hex-Wert', 'tebuto-online-terminbuchung' ); ?></label>
										<input type="text" id="category_color_hex" class="tebuto-color-hex" value="<?php echo esc_attr( TEBUTO_COLOR_FALLBACK ); ?>" maxlength="7">
									</div>
								</div>
							</div>
						</div>

						<div class="tebuto-modal-section">
							<h4><?php esc_html_e( 'Termin-Details', 'tebuto-online-terminbuchung' ); ?></h4>

							<div class="tebuto-modal-row tebuto-modal-row-3">
								<div class="tebuto-modal-field">
									<label for="category_duration"><?php esc_html_e( 'Dauer (Min.)', 'tebuto-online-terminbuchung' ); ?> *</label>
									<input type="number" id="category_duration" name="category_duration" required
										min="5" max="720" step="5" value="50">
									<span class="tebuto-field-hint" id="duration-hint"></span>
								</div>
								<div class="tebuto-modal-field">
									<label for="category_price"><?php esc_html_e( 'Preis (€)', 'tebuto-online-terminbuchung' ); ?> *</label>
									<input type="number" id="category_price" name="category_price" required
										min="0" step="0.01" value="0">
								</div>
								<div class="tebuto-modal-field">
									<label for="category_location"><?php esc_html_e( 'Ort', 'tebuto-online-terminbuchung' ); ?> *</label>
									<select id="category_location" name="category_location" required>
										<option value="not-fixed"><?php esc_html_e( 'Flexibel', 'tebuto-online-terminbuchung' ); ?></option>
										<option value="onsite"><?php esc_html_e( 'Vor Ort', 'tebuto-online-terminbuchung' ); ?></option>
										<option value="virtual"><?php esc_html_e( 'Online', 'tebuto-online-terminbuchung' ); ?></option>
									</select>
								</div>
							</div>
						</div>

						<div class="tebuto-modal-section">
							<h4><?php esc_html_e( 'Buchungsoptionen', 'tebuto-online-terminbuchung' ); ?></h4>

							<div class="tebuto-switch-option">
								<div class="tebuto-switch-option-text">
									<span class="tebuto-switch-option-label"><?php esc_html_e( 'Öffentliche Buchung', 'tebuto-online-terminbuchung' ); ?></span>
									<span class="tebuto-switch-option-desc"><?php esc_html_e( 'Klienten können über das Widget auf deiner Website buchen', 'tebuto-online-terminbuchung' ); ?></span>
								</div>
								<label class="tebuto-switch" for="category_public_booking">
									<input type="checkbox" name="category_public_booking" id="category_public_booking" value="1">
									<span class="tebuto-switch-slider"></span>
									<span class="screen-reader-text"><?php esc_html_e( 'Öffentliche Buchung', 'tebuto-online-terminbuchung' ); ?></span>
								</label>
							</div>

							<div class="tebuto-switch-option">
								<div class="tebuto-switch-option-text">
									<span class="tebuto-switch-option-label"><?php esc_html_e( 'Portal Buchung', 'tebuto-online-terminbuchung' ); ?></span>
									<span class="tebuto-switch-option-desc"><?php esc_html_e( 'Klienten können über termin.tebuto.de buchen', 'tebuto-online-terminbuchung' ); ?></span>
								</div>
								<label class="tebuto-switch" for="category_private_booking">
									<input type="checkbox" name="category_private_booking" id="category_private_booking" value="1" checked>
									<span class="tebuto-switch-slider"></span>
									<span class="screen-reader-text"><?php esc_html_e( 'Portal Buchung', 'tebuto-online-terminbuchung' ); ?></span>
								</label>
							</div>
						</div>

						<div class="tebuto-modal-section">
							<h4><?php esc_html_e( 'Ausfallhonorar', 'tebuto-online-terminbuchung' ); ?></h4>

							<div class="tebuto-switch-option">
								<div class="tebuto-switch-option-text">
									<span class="tebuto-switch-option-label"><?php esc_html_e( 'Ausfallhonorar aktivieren', 'tebuto-online-terminbuchung' ); ?></span>
									<span class="tebuto-switch-option-desc"><?php esc_html_e( 'Bei kurzfristiger Absage oder Nichterscheinen kann ein Ausfallhonorar berechnet werden', 'tebuto-online-terminbuchung' ); ?></span>
								</div>
								<label class="tebuto-switch" for="category_outage_fee">
									<input type="checkbox" name="category_outage_fee" id="category_outage_fee" value="1">
									<span class="tebuto-switch-slider"></span>
									<span class="screen-reader-text"><?php esc_html_e( 'Ausfallhonorar aktivieren', 'tebuto-online-terminbuchung' ); ?></span>
								</label>
							</div>

							<div id="tebuto-outage-fee-options" class="tebuto-outage-fee-options" style="display: none;">
								<div class="tebuto-modal-row">
									<div class="tebuto-modal-field">
										<label for="category_outage_fee_amount"><?php esc_html_e( 'Betrag', 'tebuto-online-terminbuchung' ); ?></label>
										<div class="tebuto-input-suffix">
											<input type="number" id="category_outage_fee_amount" name="category_outage_fee_amount"
												min="0" step="0.01" value="0">
											<span class="tebuto-suffix">€</span>
										</div>
									</div>
									<div class="tebuto-modal-field">
										<label for="category_outage_fee_hours"><?php esc_html_e( 'Frist', 'tebuto-online-terminbuchung' ); ?></label>
										<div class="tebuto-input-suffix">
											<input type="number" id="category_outage_fee_hours" name="category_outage_fee_hours"
												min="1" step="1" value="48">
											<span class="tebuto-suffix"><?php esc_html_e( 'Stunden', 'tebuto-online-terminbuchung' ); ?></span>
										</div>
									</div>
								</div>

								<div class="tebuto-outage-fee-warning">
									<span class="dashicons dashicons-warning"></span>
									<p><?php esc_html_e( 'Ausfallhonorare dürfen nicht den Charakter einer Strafzahlung annehmen. Wir empfehlen, das Ausfallhonorar auf maximal 80% des regulären Honorars festzusetzen. Andernfalls besteht das Risiko, dass die Forderung im Streitfall nicht anerkannt wird.', 'tebuto-online-terminbuchung' ); ?></p>
								</div>
							</div>
						</div>
					</div>

					<div class="tebuto-modal-footer">
						<button type="button" class="button tebuto-btn tebuto-btn--outline tebuto-btn--neutral tebuto-modal-close-btn"><?php esc_html_e( 'Abbrechen', 'tebuto-online-terminbuchung' ); ?></button>
						<button type="submit" class="button button-primary tebuto-btn tebuto-btn--solid tebuto-btn--primary" id="tebuto-category-submit">
							<?php esc_html_e( 'Kategorie erstellen', 'tebuto-online-terminbuchung' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
	<?php
	tebuto_ui_page_close();
	?>

	<script>
	jQuery(document).ready(function($) {
		const modal = $('#tebuto-category-modal');
		const form = $('#tebuto-category-form');
		const modalTitle = $('#tebuto-category-modal-title');
		const submitBtn = $('#tebuto-category-submit');
		const actionInput = $('#tebuto-category-action');
		const categoryIdInput = $('#tebuto-category-id');
		const durationInput = $('#category_duration');
		const durationHint = $('#duration-hint');
		const outageFeeCheckbox = $('#category_outage_fee');
		const outageFeeOptions = $('#tebuto-outage-fee-options');
		const colorPicker = $('#category_color');
		const colorHex = $('#category_color_hex');

		// Sync color picker and hex input
		colorPicker.on('input', function() {
			colorHex.val($(this).val().toUpperCase());
		});

		colorHex.on('input', function() {
			let val = $(this).val();
			if (!val.startsWith('#')) val = '#' + val;
			if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
				colorPicker.val(val);
			}
		});

		// Toggle outage fee options visibility
		outageFeeCheckbox.on('change', function() {
			if ($(this).is(':checked')) {
				outageFeeOptions.slideDown(200);
			} else {
				outageFeeOptions.slideUp(200);
			}
		});

		// Open modal for new category
		$('#tebuto-add-category-btn, #tebuto-add-category-btn-empty').on('click', function() {
			resetForm();
			modalTitle.text('<?php echo esc_js( __( 'Neue Kategorie', 'tebuto-online-terminbuchung' ) ); ?>');
			submitBtn.text('<?php echo esc_js( __( 'Kategorie erstellen', 'tebuto-online-terminbuchung' ) ); ?>');
			actionInput.val('create_category');
			categoryIdInput.val('');
			durationInput.prop('readonly', false);
			durationHint.text('');
			outageFeeOptions.hide();
			modal.fadeIn(200);
		});

		// Open modal for editing
		$('.tebuto-edit-category').on('click', function() {
			const category = $(this).data('category');

			modalTitle.text('<?php echo esc_js( __( 'Kategorie bearbeiten', 'tebuto-online-terminbuchung' ) ); ?>');
			submitBtn.text('<?php echo esc_js( __( 'Änderungen speichern', 'tebuto-online-terminbuchung' ) ); ?>');
			actionInput.val('update_category');
			categoryIdInput.val(category.id);

			// Fill form
			$('#category_name').val(category.name);
			const color = category.color || '#009087';
			$('#category_color').val(color);
			$('#category_color_hex').val(color.toUpperCase());
			$('#category_duration').val(category.duration).prop('readonly', true);
			$('#category_price').val(category.price);
			$('#category_location').val(category.location);
			$('#category_public_booking').prop('checked', category.publicBookingEnabled);
			$('#category_private_booking').prop('checked', category.privateBookingEnabled);
			$('#category_outage_fee').prop('checked', category.outageFeeEnabled);

			// Outage fee options
			$('#category_outage_fee_amount').val(category.outageFee || 0);
			$('#category_outage_fee_hours').val(category.outageFeeHours || 48);

			// Show/hide outage fee options
			if (category.outageFeeEnabled) {
				outageFeeOptions.show();
			} else {
				outageFeeOptions.hide();
			}

			durationHint.text('<?php echo esc_js( __( 'Kann nicht geändert werden', 'tebuto-online-terminbuchung' ) ); ?>');
			modal.fadeIn(200);
		});

		// Close modal
		$('.tebuto-modal-close, .tebuto-modal-close-btn').on('click', function() {
			modal.fadeOut(200);
		});

		modal.on('click', function(e) {
			if (e.target === this) {
				modal.fadeOut(200);
			}
		});

		// Reset form
		function resetForm() {
			form[0].reset();
			$('#category_color').val('#009087');
			$('#category_color_hex').val('#009087');
			$('#category_private_booking').prop('checked', true);
			$('#category_outage_fee_amount').val(0);
			$('#category_outage_fee_hours').val(48);
		}

		// Close on escape
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape') {
				modal.fadeOut(200);
			}
		});
	});
	</script>
	<?php
}

/**
 * Handle category form actions.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_category_actions( Tebuto_API $api ): void {
	if ( ! isset( $_POST['tebuto_action'], $_POST['tebuto_category_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['tebuto_category_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'tebuto_category_action' ) ) {
		tebuto_admin_notice( __( 'Ungültige Anfrage.', 'tebuto-online-terminbuchung' ), 'error' );
		return;
	}

	$action = sanitize_text_field( wp_unslash( $_POST['tebuto_action'] ) );

	switch ( $action ) {
		case 'create_category':
			tebuto_handle_create_category( $api );
			break;
		case 'update_category':
			tebuto_handle_update_category( $api );
			break;
		case 'delete_category':
			tebuto_handle_delete_category( $api );
			break;
		default:
			tebuto_admin_notice( __( 'Unbekannte Aktion.', 'tebuto-online-terminbuchung' ), 'error' );
			break;
	}
}

/**
 * Handle create category action.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_create_category( Tebuto_API $api ): void {
	$data = tebuto_get_category_form_data();

	if ( is_wp_error( $data ) ) {
		tebuto_admin_notice( $data->get_error_message(), 'error' );
		return;
	}

	$result = $api->create_event_category( $data );

	if ( is_wp_error( $result ) ) {
		tebuto_admin_notice(
			sprintf(
				/* translators: %s: error message from the API */
				__( 'Fehler beim Erstellen: %s', 'tebuto-online-terminbuchung' ),
				$api->get_last_error()
			),
			'error'
		);
		return;
	}

	tebuto_admin_notice( __( 'Kategorie erfolgreich erstellt.', 'tebuto-online-terminbuchung' ), 'success' );
}

/**
 * Handle update category action.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_update_category( Tebuto_API $api ): void {
	// Nonce verified in tebuto_handle_category_actions().
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( ! isset( $_POST['category_id'] ) ) {
		return;
	}

	$category_id = absint( $_POST['category_id'] );
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	$data = tebuto_get_category_form_data( true );

	if ( is_wp_error( $data ) ) {
		tebuto_admin_notice( $data->get_error_message(), 'error' );
		return;
	}

	$result = $api->update_event_category( $category_id, $data );

	if ( is_wp_error( $result ) ) {
		tebuto_admin_notice(
			sprintf(
				/* translators: %s: error message from the API */
				__( 'Fehler beim Aktualisieren: %s', 'tebuto-online-terminbuchung' ),
				$api->get_last_error()
			),
			'error'
		);
		return;
	}

	tebuto_admin_notice( __( 'Kategorie erfolgreich aktualisiert.', 'tebuto-online-terminbuchung' ), 'success' );
}

/**
 * Handle delete category action.
 *
 * @param Tebuto_API $api API instance.
 * @return void
 */
function tebuto_handle_delete_category( Tebuto_API $api ): void {
	// Nonce verified in tebuto_handle_category_actions().
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( ! isset( $_POST['category_id'] ) ) {
		return;
	}

	$category_id = absint( $_POST['category_id'] );
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	$result = $api->delete_event_category( $category_id );

	if ( is_wp_error( $result ) ) {
		tebuto_admin_notice(
			sprintf(
				/* translators: %s: error message from the API */
				__( 'Fehler beim Löschen: %s', 'tebuto-online-terminbuchung' ),
				$api->get_last_error()
			),
			'error'
		);
		return;
	}

	tebuto_admin_notice( __( 'Kategorie erfolgreich gelöscht.', 'tebuto-online-terminbuchung' ), 'success' );
}

/**
 * Get and validate category form data.
 *
 * @param bool $is_update Whether this is an update operation.
 * @return array|WP_Error Form data or error.
 */
function tebuto_get_category_form_data( bool $is_update = false ) {
	// Nonce verified in tebuto_handle_category_actions() before this is called.
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$name               = isset( $_POST['category_name'] ) ? sanitize_text_field( wp_unslash( $_POST['category_name'] ) ) : '';
	$color              = isset( $_POST['category_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['category_color'] ) ) : TEBUTO_COLOR_FALLBACK;
	$duration           = isset( $_POST['category_duration'] ) ? absint( $_POST['category_duration'] ) : 50;
	$price              = isset( $_POST['category_price'] ) ? floatval( $_POST['category_price'] ) : 0;
	$location           = isset( $_POST['category_location'] ) ? sanitize_text_field( wp_unslash( $_POST['category_location'] ) ) : 'not-fixed';
	$public_booking     = isset( $_POST['category_public_booking'] );
	$private_booking    = isset( $_POST['category_private_booking'] );
	$outage_fee_enabled = isset( $_POST['category_outage_fee'] );
	$outage_fee_amount  = isset( $_POST['category_outage_fee_amount'] ) ? floatval( $_POST['category_outage_fee_amount'] ) : 0;
	$outage_fee_hours   = isset( $_POST['category_outage_fee_hours'] ) ? absint( $_POST['category_outage_fee_hours'] ) : 48;
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( empty( $name ) ) {
		return new WP_Error( 'missing_name', __( 'Bitte gib einen Namen ein.', 'tebuto-online-terminbuchung' ) );
	}

	if ( ! $public_booking && ! $private_booking ) {
		return new WP_Error( 'no_booking_type', __( 'Mindestens eine Buchungsart muss aktiviert sein.', 'tebuto-online-terminbuchung' ) );
	}

	$data = array(
		'name'                  => $name,
		'color'                 => $color ? $color : TEBUTO_COLOR_FALLBACK,
		'price'                 => (string) $price,
		'location'              => $location,
		'publicBookingEnabled'  => $public_booking,
		'privateBookingEnabled' => $private_booking,
		'currency'              => 'EUR',
		'taxRate'               => '0',
		'paymentEnabled'        => false,
		'outageFeeEnabled'      => $outage_fee_enabled,
		'outageFee'             => (string) $outage_fee_amount,
		'outageFeeHours'        => $outage_fee_hours,
	);

	// Duration can only be set on create
	if ( ! $is_update ) {
		$data['duration'] = $duration;
	}

	return $data;
}
