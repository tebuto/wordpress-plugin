<?php
/**
 * Tebuto Dashboard Page.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load dashboard API data.
 *
 * Returns null if a session-expired screen was already rendered.
 *
 * @param Tebuto_API $api API instance.
 * @return array{upcoming_events: array|WP_Error, bookings_result: array|WP_Error, categories: array|WP_Error, stats: array}|null
 */
function tebuto_load_dashboard_data( Tebuto_API $api ): ?array {
	$today       = wp_date( 'Y-m-d\T00:00:00' );
	$today_end   = wp_date( 'Y-m-d\T23:59:59' );
	$month_start = wp_date( 'Y-m-01\T00:00:00' );

	$upcoming_events = $api->get_events( $today, $today_end );
	if ( is_wp_error( $upcoming_events ) && tebuto_maybe_render_session_expired_from_error( $upcoming_events ) ) {
		return null;
	}

	$bookings_result = $api->get_bookings(
		array(
			'start'     => $month_start,
			'page_size' => 100,
		)
	);
	if ( is_wp_error( $bookings_result ) && tebuto_maybe_render_session_expired_from_error( $bookings_result ) ) {
		return null;
	}

	$categories = $api->get_aggregated_event_categories();
	if ( is_wp_error( $categories ) && tebuto_maybe_render_session_expired_from_error( $categories ) ) {
		return null;
	}

	return array(
		'upcoming_events' => $upcoming_events,
		'bookings_result' => $bookings_result,
		'categories'      => $categories,
		'stats'           => tebuto_calculate_dashboard_stats( $bookings_result, $upcoming_events ),
	);
}

/**
 * Render the Tebuto dashboard page.
 *
 * @return void
 */
function tebuto_dashboard_page(): void {
	$api = tebuto_require_tebuto_connection();
	if ( $api === null ) {
		return;
	}

	$data = tebuto_load_dashboard_data( $api );
	if ( $data === null ) {
		return;
	}

	$upcoming_events = $data['upcoming_events'];
	$categories      = $data['categories'];
	$stats           = $data['stats'];

	$disconnect_form  = '<form method="post" class="tebuto-disconnect-form"';
	$disconnect_form .= ' data-tebuto-confirm="' . esc_attr( __( 'Möchtest du die Verbindung wirklich trennen?', 'tebuto-online-terminbuchung' ) ) . '"';
	$disconnect_form .= ' data-tebuto-confirm-title="' . esc_attr( __( 'Verbindung trennen', 'tebuto-online-terminbuchung' ) ) . '"';
	$disconnect_form .= ' data-tebuto-confirm-label="' . esc_attr( __( 'Trennen', 'tebuto-online-terminbuchung' ) ) . '"';
	$disconnect_form .= ' data-tebuto-confirm-danger="1">';
	$disconnect_form .= wp_nonce_field( 'tebuto_disconnect', 'tebuto_nonce', true, false );
	$disconnect_form .= '<input type="hidden" name="tebuto_disconnect" value="1">';
	$disconnect_form .= tebuto_ui_button(
		array(
			'label'   => __( 'Verbindung trennen', 'tebuto-online-terminbuchung' ),
			'type'    => 'submit',
			'variant' => 'ghost',
			'color'   => 'danger',
		)
	);
	$disconnect_form .= '</form>';

	tebuto_ui_page_open(
		array(
			'title'        => __( 'Dashboard', 'tebuto-online-terminbuchung' ),
			'page_class'   => 'tebuto-page-dashboard',
			'fullheight'   => true,
			'actions_html' => $disconnect_form,
		)
	);
	?>

		<?php tebuto_render_dashboard_stats( $stats, $categories ); ?>

		<div class="tebuto-dashboard-grid">
			<?php tebuto_render_dashboard_today_events( $upcoming_events ); ?>
			<?php tebuto_render_dashboard_categories( $categories, $api ); ?>
		</div>
	<?php
	tebuto_ui_page_close();
}

/**
 * Render dashboard statistics cards.
 *
 * @param array          $stats      Dashboard stats.
 * @param array|WP_Error $categories Categories response.
 * @return void
 */
function tebuto_render_dashboard_stats( array $stats, $categories ): void {
	?>
		<div class="tebuto-stats-grid">
			<?php
			echo tebuto_ui_stat_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				array(
					'number' => $stats['upcoming_count'],
					'label'  => __( 'Anstehende Termine', 'tebuto-online-terminbuchung' ),
					'icon'   => 'dashicons-calendar-alt',
					'tone'   => 'primary',
				)
			);
			echo tebuto_ui_stat_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				array(
					'number' => $stats['confirmed_count'],
					'label'  => __( 'Bestätigte Termine (Monat)', 'tebuto-online-terminbuchung' ),
					'icon'   => 'dashicons-yes-alt',
					'tone'   => 'success',
				)
			);
			echo tebuto_ui_stat_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				array(
					'number' => is_array( $categories ) ? count( $categories ) : 0,
					'label'  => __( 'Terminkategorien', 'tebuto-online-terminbuchung' ),
					'icon'   => 'dashicons-category',
					'tone'   => 'info',
				)
			);
			?>
		</div>
	<?php
}

/**
 * Render today's events card.
 *
 * @param array|WP_Error $upcoming_events Events API response.
 * @return void
 */
function tebuto_render_dashboard_today_events( $upcoming_events ): void {
	tebuto_ui_card_open(
		array(
			'title'               => __( 'Termine heute', 'tebuto-online-terminbuchung' ),
			'header_actions_html' => tebuto_ui_button(
				array(
					'label'   => __( 'Alle Buchungen', 'tebuto-online-terminbuchung' ),
					'href'    => admin_url( 'admin.php?page=tebuto-bookings' ),
					'variant' => 'outline',
					'color'   => 'neutral',
					'size'    => 'sm',
					'class'   => 'button-small',
				)
			),
		)
	);
	$all_events = array();
	if ( ! is_wp_error( $upcoming_events ) && is_array( $upcoming_events ) ) {
		$all_events = tebuto_merge_events( $upcoming_events );
	}
	if ( empty( $all_events ) ) {
		echo tebuto_ui_empty_state( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'icon'  => 'dashicons-calendar-alt',
				'title' => __( 'Keine Termine für heute.', 'tebuto-online-terminbuchung' ),
			)
		);
	} else {
		echo '<ul class="tebuto-event-list">';
		$displayed = 0;
		foreach ( $all_events as $event ) {
			if ( $displayed >= 10 ) {
				break;
			}
			++$displayed;
			$is_booked = isset( $event['details'] ) && ! empty( $event['details']['booking'] );
			echo '<li class="tebuto-event-item ' . ( $is_booked ? 'tebuto-event-booked' : '' ) . '">';
			echo '<div class="tebuto-event-color" style="background-color: ' . esc_attr( $event['color'] ?? TEBUTO_COLOR_FALLBACK ) . '"></div>';
			echo '<div class="tebuto-event-info">';
			echo '<span class="tebuto-event-title">' . esc_html( $event['title'] ) . '</span>';
			echo '<span class="tebuto-event-time">' . esc_html( tebuto_format_event_datetime( $event['start'], $event['end'] ) ) . '</span>';
			echo '</div>';
			$badge = $is_booked
				? tebuto_ui_badge( __( 'Gebucht', 'tebuto-online-terminbuchung' ), 'success' )
				: tebuto_ui_badge( __( 'Frei', 'tebuto-online-terminbuchung' ), 'default' );
			echo $badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper escapes.
			echo '</li>';
		}
		echo '</ul>';
	}
	tebuto_ui_card_close();
}

/**
 * Render categories card on the dashboard.
 *
 * @param array|WP_Error $categories Categories response.
 * @param Tebuto_API     $api        API instance.
 * @return void
 */
function tebuto_render_dashboard_categories( $categories, Tebuto_API $api ): void {
	tebuto_ui_card_open(
		array(
			'title'               => __( 'Terminkategorien', 'tebuto-online-terminbuchung' ),
			'header_actions_html' => tebuto_ui_button(
				array(
					'label'   => __( 'Verwalten', 'tebuto-online-terminbuchung' ),
					'href'    => admin_url( 'admin.php?page=tebuto-categories' ),
					'variant' => 'outline',
					'color'   => 'neutral',
					'size'    => 'sm',
					'class'   => 'button-small',
				)
			),
		)
	);
	if ( is_wp_error( $categories ) ) {
		echo '<p class="tebuto-error">' . esc_html( $api->get_last_error() ) . '</p>';
	} elseif ( empty( $categories ) ) {
		echo tebuto_ui_empty_state( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'icon'  => 'dashicons-category',
				'title' => __( 'Noch keine Kategorien erstellt.', 'tebuto-online-terminbuchung' ),
			)
		);
	} else {
		echo '<ul class="tebuto-category-list">';
		foreach ( $categories as $category ) {
			$is_widget_selectable = ! empty( $category['widgetSelectable'] );
			echo '<li class="tebuto-category-item' . ( $is_widget_selectable ? '' : ' tebuto-category-item--unavailable' ) . '"';
			if ( ! $is_widget_selectable ) {
				echo ' title="' . esc_attr__( 'Nur öffentliche Kategorien können im WordPress-Widget verwendet werden.', 'tebuto-online-terminbuchung' ) . '"';
			}
			echo '>';
			echo '<div class="tebuto-category-color" style="background-color: ' . esc_attr( $category['color'] ) . '"></div>';
			echo '<div class="tebuto-category-info">';
			echo '<span class="tebuto-category-name">' . esc_html( $category['displayName'] ?? $category['name'] ) . '</span>';
			echo '<span class="tebuto-category-meta">' . esc_html( $category['duration'] ) . ' ' . esc_html__( 'Min.', 'tebuto-online-terminbuchung' );
			echo ' · ' . esc_html( number_format( (float) $category['price'], 2, ',', '.' ) ) . ' €</span>';
			echo '</div><div class="tebuto-category-badges">';
			$badge = $is_widget_selectable
				? tebuto_ui_badge( __( 'Öffentlich', 'tebuto-online-terminbuchung' ), 'success' )
				: tebuto_ui_badge( __( 'Nicht öffentlich', 'tebuto-online-terminbuchung' ), 'warning' );
			echo $badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper escapes.
			if ( ! empty( $category['privateBookingEnabled'] ) ) {
				echo tebuto_ui_badge( __( 'Privat', 'tebuto-online-terminbuchung' ), 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper escapes.
			}
			echo '</div></li>';
		}
		echo '</ul>';
	}
	tebuto_ui_card_close();
}

/**
 * Calculate dashboard statistics.
 *
 * @param array|WP_Error $bookings_result Bookings response.
 * @param array|WP_Error $events_result   Events response.
 * @return array Statistics.
 */
function tebuto_calculate_dashboard_stats( $bookings_result, $events_result ): array {
	$stats = array(
		'upcoming_count'  => 0,
		'confirmed_count' => 0,
	);

	// Count upcoming events
	if ( ! is_wp_error( $events_result ) ) {
		$all_events              = tebuto_merge_events( $events_result );
		$stats['upcoming_count'] = count( $all_events );
	}

	// Count bookings by status
	if ( ! is_wp_error( $bookings_result ) && ! empty( $bookings_result['bookings'] ) ) {
		foreach ( $bookings_result['bookings'] as $booking ) {
			$status = $booking['event']['status'] ?? '';
			if ( $status === 'approved' ) {
				++$stats['confirmed_count'];
			}
		}
	}

	return $stats;
}

/**
 * Merge rule events and standalone events into a single sorted array.
 *
 * @param array $events_response Events API response.
 * @return array Merged and sorted events.
 */
function tebuto_merge_events( array $events_response ): array {
	$all_events = array();

	if ( ! empty( $events_response['rules'] ) ) {
		foreach ( $events_response['rules'] as $event ) {
			if ( ! ( $event['isSkipped'] ?? false ) ) {
				$all_events[] = $event;
			}
		}
	}

	if ( ! empty( $events_response['standalone'] ) ) {
		$all_events = array_merge( $all_events, $events_response['standalone'] );
	}

	// Sort by start date
	usort(
		$all_events,
		function ( $a, $b ) {
			return strcmp( $a['start'] ?? '', $b['start'] ?? '' );
		}
	);

	return $all_events;
}

/**
 * Format event datetime for display.
 *
 * @param string $start Start datetime.
 * @param string $end   End datetime.
 * @return string Formatted datetime.
 */
function tebuto_format_event_datetime( string $start, string $end ): string {
	$start_ts = strtotime( $start );
	$end_ts   = strtotime( $end );

	$date       = wp_date( 'D, d.m.Y', $start_ts );
	$time_start = wp_date( 'H:i', $start_ts );
	$time_end   = wp_date( 'H:i', $end_ts );

	return sprintf( '%s, %s - %s', $date, $time_start, $time_end );
}
