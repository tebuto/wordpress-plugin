<?php
/**
 * Tebuto seminar occurrence detail page.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

const TEBUTO_SEMINAR_TIMEZONE = 'Europe/Berlin';

/**
 * Find an occurrence by ID within a seminar payload.
 *
 * @param array $seminar       Seminar data.
 * @param int   $occurrence_id Occurrence ID.
 * @return array|null
 */
function tebuto_find_seminar_occurrence( array $seminar, int $occurrence_id ): ?array {
	$occurrences = isset( $seminar['occurrences'] ) && is_array( $seminar['occurrences'] ) ? $seminar['occurrences'] : array();
	foreach ( $occurrences as $item ) {
		if ( absint( $item['id'] ?? 0 ) === $occurrence_id ) {
			return $item;
		}
	}

	return null;
}

/**
 * Build header action buttons HTML for the occurrence page.
 *
 * @param int    $occurrence_id Occurrence ID.
 * @param string $status        Occurrence status.
 * @param bool   $is_inherited  Whether the seminar is inherited.
 * @param string $back_url      Back link URL.
 * @return string
 */
function tebuto_build_occurrence_page_actions( int $occurrence_id, string $status, bool $is_inherited, string $back_url ): string {
	$actions = '';
	if ( ! $is_inherited ) {
		$actions .= tebuto_ui_button(
			array(
				'label'   => __( 'Bearbeiten', 'tebuto-online-terminbuchung' ),
				'type'    => 'button',
				'variant' => 'outline',
				'color'   => 'neutral',
				'attrs'   => array( 'id' => 'tebuto-edit-occurrence-btn' ),
			)
		);
	}

	if ( $status !== 'cancelled' && ! $is_inherited ) {
		if ( $status === 'published' ) {
			$actions .= tebuto_ui_button(
				array(
					'label'   => __( 'Entwurf', 'tebuto-online-terminbuchung' ),
					'type'    => 'button',
					'variant' => 'outline',
					'color'   => 'neutral',
					'class'   => 'tebuto-occurrence-status-btn',
					'attrs'   => array(
						'data-occurrence-id' => (string) $occurrence_id,
						'data-status'        => 'draft',
					),
				)
			);
		} else {
			$actions .= tebuto_ui_button(
				array(
					'label'   => __( 'Veröffentlichen', 'tebuto-online-terminbuchung' ),
					'type'    => 'button',
					'variant' => 'solid',
					'color'   => 'primary',
					'class'   => 'tebuto-occurrence-status-btn',
					'attrs'   => array(
						'data-occurrence-id' => (string) $occurrence_id,
						'data-status'        => 'published',
					),
				)
			);
		}

		$actions .= tebuto_ui_button(
			array(
				'label'   => __( 'Absagen', 'tebuto-online-terminbuchung' ),
				'type'    => 'button',
				'variant' => 'solid',
				'color'   => 'danger',
				'class'   => 'tebuto-occurrence-cancel-btn',
				'attrs'   => array(
					'data-occurrence-id' => (string) $occurrence_id,
				),
			)
		);
	}

	$actions .= tebuto_ui_button(
		array(
			'label'   => __( '← Seminare', 'tebuto-online-terminbuchung' ),
			'href'    => $back_url,
			'variant' => 'outline',
			'color'   => 'neutral',
		)
	);

	return $actions;
}

/**
 * Build title meta HTML (lifecycle badge, subtitle, seminar name).
 *
 * @param string $lifecycle Lifecycle status.
 * @param string $subtitle  Optional subtitle.
 * @param array  $seminar   Seminar data.
 * @return string
 */
function tebuto_build_occurrence_title_meta_html( string $lifecycle, string $subtitle, array $seminar ): string {
	ob_start();
	?>
	<div class="tebuto-occurrence-meta">
		<?php echo tebuto_ui_badge( tebuto_lifecycle_label( $lifecycle ), tebuto_lifecycle_tone( $lifecycle ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( $subtitle !== '' ) : ?>
			<span class="tebuto-occurrence-subtitle"><?php echo esc_html( $subtitle ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $seminar['title'] ) ) : ?>
			<span class="tebuto-occurrence-seminar-name"><?php echo esc_html( (string) $seminar['title'] ); ?></span>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render the occurrence detail page.
 *
 * @param int $seminar_id    Seminar ID.
 * @param int $occurrence_id Occurrence ID.
 * @return void
 */
function tebuto_seminar_occurrence_page( int $seminar_id, int $occurrence_id ): void {
	$api = tebuto_require_tebuto_connection();
	if ( $api === null ) {
		return;
	}

	tebuto_handle_seminar_actions( $api );

	$seminar = $api->get_seminar( $seminar_id );
	if ( is_wp_error( $seminar ) && tebuto_maybe_render_session_expired_from_error( $seminar ) ) {
		return;
	}

	if ( is_wp_error( $seminar ) || ! is_array( $seminar ) ) {
		tebuto_ui_page_open(
			array(
				'title'      => __( 'Veranstaltung', 'tebuto-online-terminbuchung' ),
				'page_class' => 'tebuto-page-seminars',
				'fullheight' => true,
			)
		);
		echo tebuto_ui_admonition( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'title' => __( 'Fehler', 'tebuto-online-terminbuchung' ),
				'body'  => $api->get_last_error() ? $api->get_last_error() : __( 'Seminar konnte nicht geladen werden.', 'tebuto-online-terminbuchung' ),
				'tone'  => 'warning',
				'icon'  => 'dashicons-warning',
			)
		);
		tebuto_ui_page_close();
		return;
	}

	$occurrence = tebuto_find_seminar_occurrence( $seminar, $occurrence_id );

	if ( $occurrence === null ) {
		tebuto_ui_page_open(
			array(
				'title'      => __( 'Veranstaltung', 'tebuto-online-terminbuchung' ),
				'page_class' => 'tebuto-page-seminars',
				'fullheight' => true,
			)
		);
		echo tebuto_ui_admonition( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'title' => __( 'Nicht gefunden', 'tebuto-online-terminbuchung' ),
				'body'  => __( 'Die Veranstaltung wurde nicht gefunden.', 'tebuto-online-terminbuchung' ),
				'tone'  => 'warning',
				'icon'  => 'dashicons-warning',
			)
		);
		tebuto_ui_page_close();
		return;
	}

	$registrations = $api->get_seminar_registrations( $occurrence_id );
	if ( is_wp_error( $registrations ) && tebuto_maybe_render_session_expired_from_error( $registrations ) ) {
		return;
	}
	if ( is_wp_error( $registrations ) ) {
		$registrations = array();
	}

	$is_inherited = ! empty( $seminar['isInherited'] );
	$status       = (string) ( $occurrence['status'] ?? 'draft' );
	$lifecycle    = (string) ( $occurrence['lifecycleStatus'] ?? $status );
	$title        = tebuto_occurrence_display_title( $occurrence );
	$subtitle     = tebuto_occurrence_display_subtitle( $occurrence );
	$back_url     = admin_url( 'admin.php?page=tebuto-seminars' );

	$actions    = tebuto_build_occurrence_page_actions( $occurrence_id, $status, $is_inherited, $back_url );
	$title_meta = tebuto_build_occurrence_title_meta_html( $lifecycle, $subtitle, $seminar );

	tebuto_ui_page_open(
		array(
			'title'           => $title,
			'title_meta_html' => $title_meta,
			'page_class'      => 'tebuto-page-seminars tebuto-page-occurrence',
			'fullheight'      => true,
			'actions_html'    => $actions,
		)
	);
	?>

	<?php if ( $is_inherited ) : ?>
		<?php
		echo tebuto_ui_admonition( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'title' => __( 'Geerbtes Seminar', 'tebuto-online-terminbuchung' ),
				'body'  => __( 'Dieses Seminar stammt von einem verwalteten Konto. Einstellungen und Ort können hier nicht geändert werden.', 'tebuto-online-terminbuchung' ),
				'tone'  => 'info',
				'icon'  => 'dashicons-info',
			)
		);
		?>
	<?php endif; ?>

	<?php tebuto_render_occurrence_settings_summary( $occurrence, $seminar ); ?>

	<div class="tebuto-occurrence-grid">
		<?php tebuto_render_occurrence_participants_card( $registrations ); ?>
		<?php tebuto_render_occurrence_sessions_card( $occurrence, $seminar_id, $is_inherited ); ?>
	</div>

	<?php
	if ( ! $is_inherited ) {
		tebuto_render_occurrence_edit_modal( $occurrence, $seminar );
	}
	tebuto_ui_page_close();
}

/**
 * Derive a display title for an occurrence.
 *
 * Matches webapp `occurrenceTitle`: label, else German session month range, else "Termin".
 *
 * @param array $occurrence Occurrence data.
 * @param array $seminar    Seminar data (unused; kept for call-site compatibility).
 * @return string
 */
function tebuto_occurrence_display_title( array $occurrence, array $seminar = array() ): string {
	unset( $seminar );
	$label = trim( (string) ( $occurrence['label'] ?? '' ) );
	if ( $label !== '' ) {
		return $label;
	}

	$sessions = isset( $occurrence['sessions'] ) && is_array( $occurrence['sessions'] ) ? $occurrence['sessions'] : array();
	$range    = tebuto_format_session_month_range( $sessions );
	if ( $range !== '' ) {
		return $range;
	}

	return __( 'Termin', 'tebuto-online-terminbuchung' );
}

/**
 * Optional subtitle when an occurrence has a custom label (month range).
 *
 * @param array $occurrence Occurrence data.
 * @return string
 */
function tebuto_occurrence_display_subtitle( array $occurrence ): string {
	$label = trim( (string) ( $occurrence['label'] ?? '' ) );
	if ( $label === '' ) {
		return '';
	}

	$sessions = isset( $occurrence['sessions'] ) && is_array( $occurrence['sessions'] ) ? $occurrence['sessions'] : array();
	return tebuto_format_session_month_range( $sessions );
}

/**
 * Format session date span as German month range (e.g. "März 2027", "August - September 2026").
 *
 * Matches webapp `formatSessionMonthRange` (Europe/Berlin, de-DE).
 *
 * @param array $sessions Session rows with start/end.
 * @return string
 */
function tebuto_format_session_month_range( array $sessions ): string {
	$starts = array();
	foreach ( $sessions as $session ) {
		if ( empty( $session['start'] ) ) {
			continue;
		}
		try {
			$dt       = new DateTimeImmutable( (string) $session['start'] );
			$dt       = $dt->setTimezone( new DateTimeZone( TEBUTO_SEMINAR_TIMEZONE ) );
			$starts[] = $dt;
		} catch ( Exception $e ) {
			continue;
		}
	}

	if ( empty( $starts ) ) {
		return '';
	}

	usort(
		$starts,
		static function ( DateTimeImmutable $a, DateTimeImmutable $b ): int {
			return $a <=> $b;
		}
	);

	$first = $starts[0];
	$last  = $starts[ count( $starts ) - 1 ];
	$start = tebuto_month_and_year( $first );
	$end   = tebuto_month_and_year( $last );

	if ( $start['month'] === $end['month'] && $start['year'] === $end['year'] ) {
		return $start['month'] . ' ' . $start['year'];
	}

	if ( $start['year'] === $end['year'] ) {
		return $start['month'] . ' - ' . $end['month'] . ' ' . $start['year'];
	}

	return $start['month'] . ' ' . $start['year'] . ' - ' . $end['month'] . ' ' . $end['year'];
}

/**
 * German month name + year for a datetime.
 *
 * @param DateTimeImmutable $dt Datetime in Europe/Berlin.
 * @return array{month: string, year: string}
 */
function tebuto_month_and_year( DateTimeImmutable $dt ): array {
	if ( class_exists( 'IntlDateFormatter' ) ) {
		$month_fmt = new IntlDateFormatter( 'de_DE', IntlDateFormatter::NONE, IntlDateFormatter::NONE, TEBUTO_SEMINAR_TIMEZONE, null, 'MMMM' );
		$year_fmt  = new IntlDateFormatter( 'de_DE', IntlDateFormatter::NONE, IntlDateFormatter::NONE, TEBUTO_SEMINAR_TIMEZONE, null, 'yyyy' );
		$month     = $month_fmt ? (string) $month_fmt->format( $dt ) : '';
		$year      = $year_fmt ? (string) $year_fmt->format( $dt ) : '';
		if ( $month !== '' && $year !== '' ) {
			return array(
				'month' => $month,
				'year'  => $year,
			);
		}
	}

	$months = array(
		1  => 'Januar',
		2  => 'Februar',
		3  => 'März',
		4  => 'April',
		5  => 'Mai',
		6  => 'Juni',
		7  => 'Juli',
		8  => 'August',
		9  => 'September',
		10 => 'Oktober',
		11 => 'November',
		12 => 'Dezember',
	);

	return array(
		'month' => $months[ (int) $dt->format( 'n' ) ] ?? $dt->format( 'F' ),
		'year'  => $dt->format( 'Y' ),
	);
}

/**
 * Human-readable lifecycle label.
 *
 * @param string $lifecycle Lifecycle status.
 * @return string
 */
function tebuto_lifecycle_label( string $lifecycle ): string {
	$labels = array(
		'draft'                => __( 'Entwurf', 'tebuto-online-terminbuchung' ),
		'published'            => __( 'Veröffentlicht', 'tebuto-online-terminbuchung' ),
		'registration_pending' => __( 'Anmeldung bald', 'tebuto-online-terminbuchung' ),
		'registration_open'    => __( 'Anmeldung offen', 'tebuto-online-terminbuchung' ),
		'registration_closed'  => __( 'Anmeldung geschlossen', 'tebuto-online-terminbuchung' ),
		'running'              => __( 'Läuft', 'tebuto-online-terminbuchung' ),
		'completed'            => __( 'Abgeschlossen', 'tebuto-online-terminbuchung' ),
		'cancelled'            => __( 'Abgesagt', 'tebuto-online-terminbuchung' ),
	);

	return $labels[ $lifecycle ] ?? $lifecycle;
}

/**
 * Badge tone for a lifecycle status.
 *
 * @param string $lifecycle Lifecycle status.
 * @return string
 */
function tebuto_lifecycle_tone( string $lifecycle ): string {
	$map = array(
		'draft'                => 'default',
		'published'            => 'info',
		'registration_pending' => 'warning',
		'registration_open'    => 'success',
		'registration_closed'  => 'warning',
		'running'              => 'primary',
		'completed'            => 'default',
		'cancelled'            => 'danger',
	);

	return $map[ $lifecycle ] ?? 'default';
}

/**
 * Convert an ISO datetime to a datetime-local input value in WP timezone.
 *
 * @param string|null $iso ISO datetime.
 * @return string
 */
function tebuto_iso_to_datetime_local( ?string $iso ): string {
	if ( empty( $iso ) ) {
		return '';
	}

	try {
		$dt = new DateTimeImmutable( $iso );
		$dt = $dt->setTimezone( wp_timezone() );
		return $dt->format( 'Y-m-d\TH:i' );
	} catch ( Exception $e ) {
		return '';
	}
}

/**
 * Convert an ISO datetime to a date input value (Y-m-d) in WP timezone.
 *
 * @param string|null $iso ISO datetime.
 * @return string
 */
function tebuto_iso_to_date( ?string $iso ): string {
	if ( empty( $iso ) ) {
		return '';
	}

	try {
		$dt = new DateTimeImmutable( $iso );
		$dt = $dt->setTimezone( wp_timezone() );
		return $dt->format( 'Y-m-d' );
	} catch ( Exception $e ) {
		return '';
	}
}

/**
 * Format an ISO datetime as a date for display (d.m.Y).
 *
 * @param string|null $iso ISO datetime.
 * @return string
 */
function tebuto_format_iso_date( ?string $iso ): string {
	if ( empty( $iso ) ) {
		return '–';
	}

	$ts = strtotime( $iso );
	if ( ! $ts ) {
		return '–';
	}

	return wp_date( 'd.m.Y', $ts );
}

/**
 * Format an ISO datetime for display.
 *
 * @param string|null $iso ISO datetime.
 * @return string
 */
function tebuto_format_iso_datetime( ?string $iso ): string {
	if ( empty( $iso ) ) {
		return '–';
	}

	$ts = strtotime( $iso );
	if ( ! $ts ) {
		return '–';
	}

	return wp_date( 'd.m.Y H:i', $ts );
}

/**
 * Render the settings summary strip (not a card).
 *
 * @param array $occurrence Occurrence data.
 * @param array $seminar    Seminar data.
 * @return void
 */
function tebuto_render_occurrence_settings_summary( array $occurrence, array $seminar ): void {
	$capacity   = absint( $occurrence['capacity'] ?? 0 );
	$booked     = absint( $occurrence['bookedSeats'] ?? 0 );
	$price      = $occurrence['priceOverride'] ?? ( $seminar['price'] ?? '0' );
	$tax        = $occurrence['taxRateOverride'] ?? ( $seminar['taxRate'] ?? null );
	$location   = (string) ( $occurrence['locationType'] ?? 'virtual' );
	$reg_opens  = $occurrence['registrationOpensAt'] ?? null;
	$reg_closes = $occurrence['registrationClosesAt'] ?? null;

	$location_labels = array(
		'onsite'    => __( 'Vor Ort', 'tebuto-online-terminbuchung' ),
		'virtual'   => __( 'Online', 'tebuto-online-terminbuchung' ),
		'not-fixed' => __( 'Flexibel', 'tebuto-online-terminbuchung' ),
	);

	$location_text = $location_labels[ $location ] ?? $location;
	if ( $location === 'onsite' ) {
		$parts = array_filter(
			array(
				$occurrence['locationName'] ?? '',
				$occurrence['streetAndNumber'] ?? '',
				$occurrence['cityZip'] ?? '',
			)
		);
		if ( ! empty( $parts ) ) {
			$location_text .= ' · ' . implode( ', ', $parts );
		}
	}

	$price_text = number_format( (float) $price, 2, ',', '.' ) . ' €';
	if ( $tax !== null && $tax !== '' ) {
		$price_text .= ' (' . number_format( (float) $tax, 2, ',', '.' ) . ' % MwSt.)';
	}

	$outage_text = __( 'Deaktiviert', 'tebuto-online-terminbuchung' );
	if ( ! empty( $occurrence['outageFeeEnabled'] ) ) {
		$outage_text = number_format( (float) ( $occurrence['outageFeePrice'] ?? 0 ), 2, ',', '.' ) . ' € / '
			. absint( $occurrence['outageFeeDays'] ?? 0 ) . ' '
			. __( 'Tage', 'tebuto-online-terminbuchung' );
	}
	?>
	<div class="tebuto-occurrence-settings-summary">
		<div class="tebuto-occurrence-summary-item">
			<span class="tebuto-occurrence-summary-label"><?php esc_html_e( 'Plätze', 'tebuto-online-terminbuchung' ); ?></span>
			<span class="tebuto-occurrence-summary-value"><?php echo esc_html( $booked . ' / ' . $capacity ); ?></span>
		</div>
		<div class="tebuto-occurrence-summary-item">
			<span class="tebuto-occurrence-summary-label"><?php esc_html_e( 'Anmeldezeitraum', 'tebuto-online-terminbuchung' ); ?></span>
			<span class="tebuto-occurrence-summary-value">
				<?php
				echo esc_html(
					tebuto_format_iso_date( is_string( $reg_opens ) ? $reg_opens : null )
					. ' – '
					. tebuto_format_iso_date( is_string( $reg_closes ) ? $reg_closes : null )
				);
				?>
			</span>
		</div>
		<div class="tebuto-occurrence-summary-item">
			<span class="tebuto-occurrence-summary-label"><?php esc_html_e( 'Preis', 'tebuto-online-terminbuchung' ); ?></span>
			<span class="tebuto-occurrence-summary-value"><?php echo esc_html( $price_text ); ?></span>
		</div>
		<div class="tebuto-occurrence-summary-item">
			<span class="tebuto-occurrence-summary-label"><?php esc_html_e( 'Ausfallgebühr', 'tebuto-online-terminbuchung' ); ?></span>
			<span class="tebuto-occurrence-summary-value"><?php echo esc_html( $outage_text ); ?></span>
		</div>
		<div class="tebuto-occurrence-summary-item">
			<span class="tebuto-occurrence-summary-label"><?php esc_html_e( 'Ort', 'tebuto-online-terminbuchung' ); ?></span>
			<span class="tebuto-occurrence-summary-value"><?php echo esc_html( $location_text ); ?></span>
		</div>
	</div>
	<?php
}

/**
 * Render a single session editor row.
 *
 * @param array $session      Session data.
 * @param int   $index        Row index.
 * @param bool  $is_inherited Whether inherited (read-only).
 * @return void
 */
function tebuto_render_occurrence_session_row( array $session, int $index, bool $is_inherited ): void {
	$row_id = 'tebuto-session-' . $index;
	?>
					<div class="tebuto-session-row">
						<input type="hidden" name="session_ids[]" value="<?php echo esc_attr( (string) absint( $session['id'] ?? 0 ) ); ?>">
						<div class="tebuto-modal-field">
							<label for="<?php echo esc_attr( $row_id . '-start' ); ?>"><?php esc_html_e( 'Beginn', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="datetime-local" id="<?php echo esc_attr( $row_id . '-start' ); ?>" name="session_starts[]"
								value="<?php echo esc_attr( tebuto_iso_to_datetime_local( isset( $session['start'] ) ? (string) $session['start'] : null ) ); ?>"
								<?php disabled( $is_inherited ); ?> required>
						</div>
						<div class="tebuto-modal-field">
							<label for="<?php echo esc_attr( $row_id . '-end' ); ?>"><?php esc_html_e( 'Ende', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="datetime-local" id="<?php echo esc_attr( $row_id . '-end' ); ?>" name="session_ends[]"
								value="<?php echo esc_attr( tebuto_iso_to_datetime_local( isset( $session['end'] ) ? (string) $session['end'] : null ) ); ?>"
								<?php disabled( $is_inherited ); ?> required>
						</div>
						<div class="tebuto-modal-field">
							<label for="<?php echo esc_attr( $row_id . '-label' ); ?>"><?php esc_html_e( 'Bezeichnung', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="text" id="<?php echo esc_attr( $row_id . '-label' ); ?>" name="session_labels[]" maxlength="120"
								value="<?php echo esc_attr( (string) ( $session['label'] ?? '' ) ); ?>"
								<?php disabled( $is_inherited ); ?>>
						</div>
						<?php if ( ! $is_inherited ) : ?>
							<button type="button" class="button tebuto-btn tebuto-btn--outline tebuto-btn--danger tebuto-btn--sm tebuto-remove-session" title="<?php esc_attr_e( 'Entfernen', 'tebuto-online-terminbuchung' ); ?>">
								<span class="dashicons dashicons-trash"></span>
							</button>
						<?php endif; ?>
					</div>
	<?php
}

/**
 * Render the sessions editor card.
 *
 * @param array $occurrence   Occurrence data.
 * @param int   $seminar_id   Seminar ID.
 * @param bool  $is_inherited Whether inherited.
 * @return void
 */
function tebuto_render_occurrence_sessions_card( array $occurrence, int $seminar_id, bool $is_inherited ): void {
	$sessions      = isset( $occurrence['sessions'] ) && is_array( $occurrence['sessions'] ) ? $occurrence['sessions'] : array();
	$occurrence_id = absint( $occurrence['id'] ?? 0 );

	tebuto_ui_card_open(
		array(
			'title' => __( 'Termine', 'tebuto-online-terminbuchung' ),
			'class' => 'tebuto-occurrence-sessions-card',
		)
	);
	?>
	<form method="post" id="tebuto-sessions-form" <?php echo $is_inherited ? 'class="tebuto-form-readonly"' : ''; ?>>
		<?php wp_nonce_field( 'tebuto_seminar_action', 'tebuto_seminar_nonce' ); ?>
		<input type="hidden" name="tebuto_action" value="update_occurrence_sessions">
		<input type="hidden" name="seminar_id" value="<?php echo esc_attr( (string) $seminar_id ); ?>">
		<input type="hidden" name="occurrence_id" value="<?php echo esc_attr( (string) $occurrence_id ); ?>">

		<div id="tebuto-sessions-list" class="tebuto-sessions-list">
			<?php if ( empty( $sessions ) ) : ?>
				<p class="tebuto-empty"><?php esc_html_e( 'Noch keine Termine hinterlegt.', 'tebuto-online-terminbuchung' ); ?></p>
			<?php else : ?>
				<?php foreach ( $sessions as $index => $session ) : ?>
					<?php tebuto_render_occurrence_session_row( $session, (int) $index, $is_inherited ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<?php if ( ! $is_inherited ) : ?>
			<div class="tebuto-sessions-actions">
				<button type="button" class="button tebuto-btn tebuto-btn--outline tebuto-btn--primary" id="tebuto-add-session">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Termin hinzufügen', 'tebuto-online-terminbuchung' ); ?>
				</button>
				<button type="submit" class="button button-primary tebuto-btn tebuto-btn--solid tebuto-btn--primary"><?php esc_html_e( 'Termine speichern', 'tebuto-online-terminbuchung' ); ?></button>
			</div>
		<?php endif; ?>
	</form>

	<template id="tebuto-session-row-template">
		<div class="tebuto-session-row">
			<input type="hidden" name="session_ids[]" value="0">
			<div class="tebuto-modal-field">
				<label for="tebuto-session-__INDEX__-start"><?php esc_html_e( 'Beginn', 'tebuto-online-terminbuchung' ); ?></label>
				<input type="datetime-local" id="tebuto-session-__INDEX__-start" name="session_starts[]" required>
			</div>
			<div class="tebuto-modal-field">
				<label for="tebuto-session-__INDEX__-end"><?php esc_html_e( 'Ende', 'tebuto-online-terminbuchung' ); ?></label>
				<input type="datetime-local" id="tebuto-session-__INDEX__-end" name="session_ends[]" required>
			</div>
			<div class="tebuto-modal-field">
				<label for="tebuto-session-__INDEX__-label"><?php esc_html_e( 'Bezeichnung', 'tebuto-online-terminbuchung' ); ?></label>
				<input type="text" id="tebuto-session-__INDEX__-label" name="session_labels[]" maxlength="120">
			</div>
			<button type="button" class="button tebuto-btn tebuto-btn--outline tebuto-btn--danger tebuto-btn--sm tebuto-remove-session" title="<?php esc_attr_e( 'Entfernen', 'tebuto-online-terminbuchung' ); ?>">
				<span class="dashicons dashicons-trash"></span>
			</button>
		</div>
	</template>
	<?php
	tebuto_ui_card_close();
}

/**
 * Render a single registration table row.
 *
 * @param array $registration  Registration data.
 * @param array $status_labels Status label map.
 * @param array $status_tones  Status tone map.
 * @param array $source_labels Source label map.
 * @return void
 */
function tebuto_render_registration_row( array $registration, array $status_labels, array $status_tones, array $source_labels ): void {
	$client         = isset( $registration['client'] ) && is_array( $registration['client'] ) ? $registration['client'] : array();
	$name           = trim( ( $client['firstName'] ?? '' ) . ' ' . ( $client['lastName'] ?? '' ) );
	$email          = (string) ( $client['email'] ?? '' );
	$status         = (string) ( $registration['status'] ?? '' );
	$source         = (string) ( $registration['source'] ?? '' );
	$payment        = isset( $registration['payment'] ) && is_array( $registration['payment'] ) ? $registration['payment'] : null;
	$payment_status = is_array( $payment ) ? (string) ( $payment['status'] ?? '' ) : '';
	?>
					<tr>
						<td><?php echo esc_html( $name !== '' ? $name : '–' ); ?></td>
						<td><?php echo esc_html( $email !== '' ? $email : '–' ); ?></td>
						<td><?php echo esc_html( (string) absint( $registration['seats'] ?? 1 ) ); ?></td>
						<td><?php echo tebuto_ui_badge( $status_labels[ $status ] ?? $status, $status_tones[ $status ] ?? 'default' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						<td><?php echo esc_html( $source_labels[ $source ] ?? $source ); ?></td>
						<td><?php echo esc_html( tebuto_format_iso_datetime( isset( $registration['registeredAt'] ) ? (string) $registration['registeredAt'] : null ) ); ?></td>
						<td><?php echo esc_html( $payment_status !== '' ? $payment_status : '–' ); ?></td>
					</tr>
	<?php
}

/**
 * Render the read-only participants card.
 *
 * @param array $registrations Registrations list.
 * @return void
 */
function tebuto_render_occurrence_participants_card( array $registrations ): void {
	tebuto_ui_card_open(
		array(
			'title' => __( 'Teilnehmer', 'tebuto-online-terminbuchung' ),
			'class' => 'tebuto-occurrence-participants-card',
		)
	);

	$status_labels = array(
		'pending'   => __( 'Ausstehend', 'tebuto-online-terminbuchung' ),
		'confirmed' => __( 'Bestätigt', 'tebuto-online-terminbuchung' ),
		'waitlist'  => __( 'Warteliste', 'tebuto-online-terminbuchung' ),
		'cancelled' => __( 'Abgesagt', 'tebuto-online-terminbuchung' ),
		'rejected'  => __( 'Abgelehnt', 'tebuto-online-terminbuchung' ),
	);

	$status_tones = array(
		'pending'   => 'warning',
		'confirmed' => 'success',
		'waitlist'  => 'info',
		'cancelled' => 'danger',
		'rejected'  => 'danger',
	);

	$source_labels = array(
		'widget'        => __( 'Widget', 'tebuto-online-terminbuchung' ),
		'public_page'   => __( 'Öffentliche Seite', 'tebuto-online-terminbuchung' ),
		'therapist'     => __( 'Manuell', 'tebuto-online-terminbuchung' ),
		'client_portal' => __( 'Klientenportal', 'tebuto-online-terminbuchung' ),
	);

	if ( empty( $registrations ) ) {
		echo tebuto_ui_empty_state( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'icon'  => 'dashicons-groups',
				'title' => __( 'Noch keine Anmeldungen', 'tebuto-online-terminbuchung' ),
				'body'  => __( 'Sobald sich Teilnehmer anmelden, erscheinen sie hier.', 'tebuto-online-terminbuchung' ),
			)
		);
		tebuto_ui_card_close();
		return;
	}
	?>
	<div class="tebuto-table-responsive">
		<table class="tebuto-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'tebuto-online-terminbuchung' ); ?></th>
					<th><?php esc_html_e( 'E-Mail', 'tebuto-online-terminbuchung' ); ?></th>
					<th><?php esc_html_e( 'Plätze', 'tebuto-online-terminbuchung' ); ?></th>
					<th><?php esc_html_e( 'Status', 'tebuto-online-terminbuchung' ); ?></th>
					<th><?php esc_html_e( 'Quelle', 'tebuto-online-terminbuchung' ); ?></th>
					<th><?php esc_html_e( 'Angemeldet', 'tebuto-online-terminbuchung' ); ?></th>
					<th><?php esc_html_e( 'Zahlung', 'tebuto-online-terminbuchung' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $registrations as $registration ) : ?>
					<?php tebuto_render_registration_row( $registration, $status_labels, $status_tones, $source_labels ); ?>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
	tebuto_ui_card_close();
}

/**
 * Render the occurrence settings edit modal.
 *
 * @param array $occurrence Occurrence data.
 * @param array $seminar    Seminar data.
 * @return void
 */
function tebuto_render_occurrence_edit_modal( array $occurrence, array $seminar ): void {
	$occurrence_id = absint( $occurrence['id'] ?? 0 );
	$seminar_id    = absint( $seminar['id'] ?? 0 );
	$location_type = (string) ( $occurrence['locationType'] ?? 'virtual' );
	?>
	<div id="tebuto-occurrence-edit-modal" class="tebuto-modal" style="display: none;">
		<div class="tebuto-modal-content tebuto-modal-lg">
			<div class="tebuto-modal-header">
				<h3><?php esc_html_e( 'Veranstaltung bearbeiten', 'tebuto-online-terminbuchung' ); ?></h3>
				<button type="button" class="tebuto-modal-close">&times;</button>
			</div>
			<form method="post" id="tebuto-occurrence-edit-form">
				<?php wp_nonce_field( 'tebuto_seminar_action', 'tebuto_seminar_nonce' ); ?>
				<input type="hidden" name="tebuto_action" value="update_occurrence">
				<input type="hidden" name="seminar_id" value="<?php echo esc_attr( (string) $seminar_id ); ?>">
				<input type="hidden" name="occurrence_id" value="<?php echo esc_attr( (string) $occurrence_id ); ?>">

				<div class="tebuto-modal-body">
					<div class="tebuto-modal-field">
						<label for="edit_occurrence_label"><?php esc_html_e( 'Bezeichnung', 'tebuto-online-terminbuchung' ); ?></label>
						<input type="text" id="edit_occurrence_label" name="occurrence_label" maxlength="120"
							value="<?php echo esc_attr( (string) ( $occurrence['label'] ?? '' ) ); ?>">
					</div>
					<div class="tebuto-modal-row">
						<div class="tebuto-modal-field">
							<label for="edit_occurrence_capacity"><?php esc_html_e( 'Kapazität', 'tebuto-online-terminbuchung' ); ?> *</label>
							<input type="number" id="edit_occurrence_capacity" name="occurrence_capacity" min="1" required
								value="<?php echo esc_attr( (string) absint( $occurrence['capacity'] ?? 1 ) ); ?>">
						</div>
						<div class="tebuto-modal-field">
							<label for="edit_occurrence_location_type"><?php esc_html_e( 'Ort', 'tebuto-online-terminbuchung' ); ?> *</label>
							<select id="edit_occurrence_location_type" name="occurrence_location_type" required>
								<option value="virtual" <?php selected( $location_type, 'virtual' ); ?>><?php esc_html_e( 'Online', 'tebuto-online-terminbuchung' ); ?></option>
								<option value="onsite" <?php selected( $location_type, 'onsite' ); ?>><?php esc_html_e( 'Vor Ort', 'tebuto-online-terminbuchung' ); ?></option>
								<option value="not-fixed" <?php selected( $location_type, 'not-fixed' ); ?>><?php esc_html_e( 'Flexibel', 'tebuto-online-terminbuchung' ); ?></option>
							</select>
						</div>
					</div>
					<div id="tebuto-edit-occurrence-address" class="tebuto-occurrence-address" <?php echo $location_type === 'onsite' ? '' : 'hidden'; ?>>
						<div class="tebuto-modal-field">
							<label for="edit_occurrence_location_name"><?php esc_html_e( 'Ort / Name', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="text" id="edit_occurrence_location_name" name="occurrence_location_name" maxlength="200"
								value="<?php echo esc_attr( (string) ( $occurrence['locationName'] ?? '' ) ); ?>">
						</div>
						<div class="tebuto-modal-row">
							<div class="tebuto-modal-field">
								<label for="edit_occurrence_street"><?php esc_html_e( 'Straße und Nr.', 'tebuto-online-terminbuchung' ); ?></label>
								<input type="text" id="edit_occurrence_street" name="occurrence_street" maxlength="200"
									value="<?php echo esc_attr( (string) ( $occurrence['streetAndNumber'] ?? '' ) ); ?>">
							</div>
							<div class="tebuto-modal-field">
								<label for="edit_occurrence_city_zip"><?php esc_html_e( 'PLZ / Ort', 'tebuto-online-terminbuchung' ); ?></label>
								<input type="text" id="edit_occurrence_city_zip" name="occurrence_city_zip"
									value="<?php echo esc_attr( (string) ( $occurrence['cityZip'] ?? '' ) ); ?>">
							</div>
						</div>
						<div class="tebuto-modal-field">
							<label for="edit_occurrence_additional"><?php esc_html_e( 'Zusätzliche Informationen', 'tebuto-online-terminbuchung' ); ?></label>
							<textarea id="edit_occurrence_additional" name="occurrence_additional" rows="2" maxlength="500"><?php echo esc_textarea( (string) ( $occurrence['additionalInformation'] ?? '' ) ); ?></textarea>
						</div>
					</div>
					<div class="tebuto-modal-row">
						<div class="tebuto-modal-field">
							<label for="edit_registration_opens"><?php esc_html_e( 'Anmeldung ab', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="date" id="edit_registration_opens" name="registration_opens_at"
								value="<?php echo esc_attr( tebuto_iso_to_date( isset( $occurrence['registrationOpensAt'] ) ? (string) $occurrence['registrationOpensAt'] : null ) ); ?>">
						</div>
						<div class="tebuto-modal-field">
							<label for="edit_registration_closes"><?php esc_html_e( 'Anmeldung bis', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="date" id="edit_registration_closes" name="registration_closes_at"
								value="<?php echo esc_attr( tebuto_iso_to_date( isset( $occurrence['registrationClosesAt'] ) ? (string) $occurrence['registrationClosesAt'] : null ) ); ?>">
						</div>
					</div>
					<div class="tebuto-modal-row">
						<div class="tebuto-modal-field">
							<label for="edit_price_override"><?php esc_html_e( 'Preis-Override (€)', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="number" id="edit_price_override" name="price_override" min="0" step="0.01"
								value="<?php echo esc_attr( (string) ( $occurrence['priceOverride'] ?? '' ) ); ?>"
								placeholder="<?php echo esc_attr( (string) ( $seminar['price'] ?? '' ) ); ?>">
						</div>
						<div class="tebuto-modal-field">
							<label for="edit_tax_override"><?php esc_html_e( 'MwSt.-Override (%)', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="number" id="edit_tax_override" name="tax_rate_override" min="0" max="100" step="0.01"
								value="<?php echo esc_attr( (string) ( $occurrence['taxRateOverride'] ?? '' ) ); ?>"
								placeholder="<?php echo esc_attr( (string) ( $seminar['taxRate'] ?? '' ) ); ?>">
						</div>
					</div>
					<div class="tebuto-switch-option">
						<div class="tebuto-switch-option-text">
							<span class="tebuto-switch-option-label"><?php esc_html_e( 'Ausfallgebühr aktivieren', 'tebuto-online-terminbuchung' ); ?></span>
						</div>
						<label class="tebuto-switch" for="edit_outage_fee_enabled">
							<input type="checkbox" name="outage_fee_enabled" id="edit_outage_fee_enabled" value="1" <?php checked( ! empty( $occurrence['outageFeeEnabled'] ) ); ?>>
							<span class="tebuto-switch-slider"></span>
							<span class="screen-reader-text"><?php esc_html_e( 'Ausfallgebühr aktivieren', 'tebuto-online-terminbuchung' ); ?></span>
						</label>
					</div>
					<div id="tebuto-edit-outage-fields" class="tebuto-modal-row" <?php echo empty( $occurrence['outageFeeEnabled'] ) ? 'hidden' : ''; ?>>
						<div class="tebuto-modal-field">
							<label for="edit_outage_fee_price"><?php esc_html_e( 'Ausfallgebühr (€)', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="number" id="edit_outage_fee_price" name="outage_fee_price" min="0" step="0.01"
								value="<?php echo esc_attr( (string) ( $occurrence['outageFeePrice'] ?? '' ) ); ?>">
						</div>
						<div class="tebuto-modal-field">
							<label for="edit_outage_fee_days"><?php esc_html_e( 'Frist (Tage)', 'tebuto-online-terminbuchung' ); ?></label>
							<input type="number" id="edit_outage_fee_days" name="outage_fee_days" min="0"
								value="<?php echo esc_attr( (string) absint( $occurrence['outageFeeDays'] ?? 0 ) ); ?>">
						</div>
					</div>
				</div>
				<div class="tebuto-modal-footer">
					<button type="button" class="button tebuto-btn tebuto-btn--outline tebuto-btn--neutral tebuto-modal-close-btn"><?php esc_html_e( 'Abbrechen', 'tebuto-online-terminbuchung' ); ?></button>
					<button type="submit" class="button button-primary tebuto-btn tebuto-btn--solid tebuto-btn--primary"><?php esc_html_e( 'Speichern', 'tebuto-online-terminbuchung' ); ?></button>
				</div>
			</form>
		</div>
	</div>
	<?php
}
