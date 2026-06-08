<?php
/**
 * AJAX handlers for Tebuto plugin.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Send a JSON error for failed Tebuto API calls.
 *
 * @param Tebuto_API $api API client.
 * @param WP_Error   $error API error.
 * @return void
 */
function tebuto_send_ajax_api_error(Tebuto_API $api, WP_Error $error): void {
    if ($error->get_error_code() === 'session_expired') {
        wp_send_json_error(
            [
                'code'    => 'session_expired',
                'message' => $error->get_error_message(),
            ],
            401
        );
    }

    wp_send_json_error($api->get_last_error() ?: $error->get_error_message(), 500);
}

/**
 * Register AJAX handlers.
 *
 * @return void
 */
function tebuto_register_ajax_handlers(): void {
    add_action('wp_ajax_tebuto_get_events', 'tebuto_ajax_get_events');
    add_action('wp_ajax_tebuto_booking_action', 'tebuto_ajax_booking_action');
    add_action('wp_ajax_tebuto_get_categories', 'tebuto_ajax_get_categories');
}
add_action('init', 'tebuto_register_ajax_handlers');

/**
 * AJAX handler: Get categories for multiselect.
 *
 * @return void
 */
function tebuto_ajax_get_categories(): void {
    check_ajax_referer('tebuto_admin', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Keine Berechtigung.', 'tebuto-online-terminbuchung'), 403);
    }

    $api = new Tebuto_API();

    if (!$api->is_connected()) {
        if (tebuto_is_session_expired()) {
            wp_send_json_error(
                [
                    'code'    => 'session_expired',
                    'message' => __('Deine Tebuto-Sitzung ist abgelaufen. Bitte melde dich erneut an.', 'tebuto-online-terminbuchung'),
                ],
                401
            );
        }

        wp_send_json_error(__('Nicht mit Tebuto verbunden.', 'tebuto-online-terminbuchung'), 401);
    }

    $categories = $api->get_aggregated_event_categories();

    if (is_wp_error($categories)) {
        tebuto_send_ajax_api_error($api, $categories);
    }

    $result = [];
    if (is_array($categories)) {
        foreach ($categories as $category) {
            $result[] = [
                'id'                   => $category['id'] ?? 0,
                'name'                 => $category['displayName'] ?? ($category['name'] ?? __('Unbenannt', 'tebuto-online-terminbuchung')),
                'color'                => $category['color'] ?? '#009087',
                'therapistId'          => $category['therapistId'] ?? 0,
                'therapistName'        => $category['therapistName'] ?? '',
                'isFromSubaccount'     => ! empty($category['isFromSubaccount']),
                'publicBookingEnabled' => ! empty($category['publicBookingEnabled']),
                'widgetSelectable'     => ! empty($category['widgetSelectable']),
            ];
        }
    }

    wp_send_json_success($result);
}

/**
 * AJAX handler: Get events for calendar.
 *
 * @return void
 */
function tebuto_ajax_get_events(): void {
    check_ajax_referer('tebuto_calendar', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Keine Berechtigung.', 'tebuto-online-terminbuchung'), 403);
    }

    $api = new Tebuto_API();

    if (!$api->is_connected()) {
        if (tebuto_is_session_expired()) {
            wp_send_json_error(
                [
                    'code'    => 'session_expired',
                    'message' => __('Deine Tebuto-Sitzung ist abgelaufen. Bitte melde dich erneut an.', 'tebuto-online-terminbuchung'),
                ],
                401
            );
        }

        wp_send_json_error(__('Nicht mit Tebuto verbunden.', 'tebuto-online-terminbuchung'), 401);
    }

    $start = isset($_POST['start']) ? sanitize_text_field(wp_unslash($_POST['start'])) : '';
    $end = isset($_POST['end']) ? sanitize_text_field(wp_unslash($_POST['end'])) : '';
    $category_filter = isset($_POST['category']) ? absint($_POST['category']) : 0;
    $status_filter = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';

    if (empty($start) || empty($end)) {
        wp_send_json_error(__('Start- und Enddatum erforderlich.', 'tebuto-online-terminbuchung'), 400);
    }

    $events_result = $api->get_events($start, $end);

    if (is_wp_error($events_result)) {
        tebuto_send_ajax_api_error($api, $events_result);
    }

    $calendar_events = tebuto_transform_events_for_calendar($events_result, $category_filter, $status_filter);

    wp_send_json_success($calendar_events);
}

/**
 * Transform API events to FullCalendar format.
 *
 * @param array $events_result   API events response.
 * @param int   $category_filter Category ID filter (0 = all).
 * @param string $status_filter  Status filter.
 * @return array FullCalendar events.
 */
function tebuto_transform_events_for_calendar(array $events_result, int $category_filter, string $status_filter): array {
    $calendar_events = [];

    // Process rule-based events
    if (!empty($events_result['rules'])) {
        foreach ($events_result['rules'] as $event) {
            // Skip if filtered by category
            if ($category_filter > 0 && ($event['categoryId'] ?? 0) !== $category_filter) {
                continue;
            }

            // Skip skipped events unless showing all
            if ($event['isSkipped'] ?? false) {
                continue;
            }

            // Determine status
            $status = 'available';
            $booking_id = null;
            $client_name = null;

            if (!empty($event['details'])) {
                $details = $event['details'];
                $status = $details['status'] ?? 'available';
                $booking_id = $details['booking']['id'] ?? null;

                if (!empty($details['booking']['client'])) {
                    $client = $details['booking']['client'];
                    $client_name = trim(($client['firstName'] ?? '') . ' ' . ($client['lastName'] ?? ''));
                }
            }

            // Apply status filter
            if (!empty($status_filter)) {
                if ($status_filter === 'available' && $status !== 'open') {
                    continue;
                }
                if ($status_filter !== 'available' && $status !== $status_filter) {
                    continue;
                }
            }

            $calendar_events[] = [
                'id'               => 'rule-' . ($event['ruleId'] ?? 0) . '-' . strtotime($event['start']),
                'title'            => $event['title'] ?? __('Termin', 'tebuto-online-terminbuchung'),
                'start'            => $event['start'],
                'end'              => $event['end'],
                'backgroundColor'  => tebuto_get_event_color($status, $event['color'] ?? '#009087'),
                'borderColor'      => tebuto_get_event_color($status, $event['color'] ?? '#009087'),
                'textColor'        => '#ffffff',
                'extendedProps'    => [
                    'status'       => $status === 'open' ? 'available' : $status,
                    'ruleId'       => $event['ruleId'] ?? null,
                    'categoryId'   => $event['categoryId'] ?? null,
                    'categoryName' => $event['title'] ?? null,
                    'bookingId'    => $booking_id,
                    'clientName'   => $client_name,
                    'location'     => $event['details']['booking']['locationSelection'] ?? null,
                    'duration'     => tebuto_calculate_duration_minutes($event['start'], $event['end']),
                ],
            ];
        }
    }

    // Process standalone events
    if (!empty($events_result['standalone'])) {
        foreach ($events_result['standalone'] as $event) {
            // Skip if filtered by category
            if ($category_filter > 0 && ($event['categoryId'] ?? 0) !== $category_filter) {
                continue;
            }

            $status = $event['details']['status'] ?? 'booked';
            $booking_id = $event['details']['id'] ?? null;
            $client_name = null;

            if (!empty($event['details']['booking']['client'])) {
                $client = $event['details']['booking']['client'];
                $client_name = trim(($client['firstName'] ?? '') . ' ' . ($client['lastName'] ?? ''));
            }

            // Apply status filter
            if (!empty($status_filter) && $status_filter !== $status) {
                continue;
            }

            $calendar_events[] = [
                'id'               => 'standalone-' . ($event['id'] ?? 0),
                'title'            => $event['title'] ?? __('Termin', 'tebuto-online-terminbuchung'),
                'start'            => $event['start'],
                'end'              => $event['end'],
                'backgroundColor'  => tebuto_get_event_color($status, $event['color'] ?? '#009087'),
                'borderColor'      => tebuto_get_event_color($status, $event['color'] ?? '#009087'),
                'textColor'        => '#ffffff',
                'extendedProps'    => [
                    'status'       => $status,
                    'eventId'      => $event['id'] ?? null,
                    'categoryId'   => $event['categoryId'] ?? null,
                    'categoryName' => $event['title'] ?? null,
                    'bookingId'    => $booking_id,
                    'clientName'   => $client_name,
                    'location'     => $event['details']['booking']['locationSelection'] ?? null,
                    'duration'     => tebuto_calculate_duration_minutes($event['start'], $event['end']),
                ],
            ];
        }
    }

    return $calendar_events;
}

/**
 * Get event color based on status.
 *
 * @param string $status        Event status.
 * @param string $default_color Default category color.
 * @return string Color hex code.
 */
function tebuto_get_event_color(string $status, string $default_color): string {
    switch ($status) {
        case 'open':
        case 'available':
            return $default_color; // Use category color for available
        case 'booked':
            return '#f0ad4e'; // Warning yellow for pending
        case 'approved':
            return '#28a745'; // Success green for confirmed
        case 'cancelled':
        case 'rejected':
        case 'outage':
            return '#dc3545'; // Danger red for cancelled
        case 'skipped':
            return '#6c757d'; // Gray for skipped
        default:
            return $default_color;
    }
}

/**
 * Calculate duration in minutes between two datetime strings.
 *
 * @param string $start Start datetime.
 * @param string $end   End datetime.
 * @return int Duration in minutes.
 */
function tebuto_calculate_duration_minutes(string $start, string $end): int {
    $start_ts = strtotime($start);
    $end_ts = strtotime($end);
    return (int) round(($end_ts - $start_ts) / 60);
}

/**
 * AJAX handler: Perform booking action.
 *
 * @return void
 */
function tebuto_ajax_booking_action(): void {
    // Check both nonces (calendar and admin)
    $valid_nonce = false;
    if (isset($_POST['nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
        if (wp_verify_nonce($nonce, 'tebuto_calendar') || wp_verify_nonce($nonce, 'tebuto_admin')) {
            $valid_nonce = true;
        }
    }

    if (!$valid_nonce) {
        wp_send_json_error(__('Ungültige Sicherheitsüberprüfung.', 'tebuto-online-terminbuchung'), 403);
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Keine Berechtigung.', 'tebuto-online-terminbuchung'), 403);
    }

    $api = new Tebuto_API();

    if (!$api->is_connected()) {
        if (tebuto_is_session_expired()) {
            wp_send_json_error(
                [
                    'code'    => 'session_expired',
                    'message' => __('Deine Tebuto-Sitzung ist abgelaufen. Bitte melde dich erneut an.', 'tebuto-online-terminbuchung'),
                ],
                401
            );
        }

        wp_send_json_error(__('Nicht mit Tebuto verbunden.', 'tebuto-online-terminbuchung'), 401);
    }

    $action = isset($_POST['booking_action']) ? sanitize_text_field(wp_unslash($_POST['booking_action'])) : '';
    $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;

    if (empty($action) || empty($booking_id)) {
        wp_send_json_error(__('Fehlende Parameter.', 'tebuto-online-terminbuchung'), 400);
    }

    $result = null;

    switch ($action) {
        case 'confirm':
            $result = $api->confirm_booking($booking_id);
            break;
        case 'reject':
            $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
            $result = $api->reject_booking($booking_id, $message);
            break;
        case 'cancel':
            $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
            $bookable_again = isset($_POST['bookable_again']) ? (bool) $_POST['bookable_again'] : true;
            $result = $api->cancel_booking($booking_id, $message, $bookable_again);
            break;
        default:
            wp_send_json_error(__('Unbekannte Aktion.', 'tebuto-online-terminbuchung'), 400);
    }

    if (is_wp_error($result)) {
        tebuto_send_ajax_api_error($api, $result);
    }

    wp_send_json_success($result);
}

