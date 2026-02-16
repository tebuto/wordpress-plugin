<?php
/**
 * Tebuto API Client.
 *
 * Handles all communication with the Tebuto API.
 *
 * @package Tebuto
 */

defined('ABSPATH') || exit;

/**
 * Tebuto API Client class.
 */
class Tebuto_API {

    /**
     * Access token.
     *
     * @var string|null
     */
    private ?string $access_token = null;

    /**
     * Refresh token.
     *
     * @var string|null
     */
    private ?string $refresh_token = null;

    /**
     * Therapist ID.
     *
     * @var int|null
     */
    private ?int $therapist_id = null;

    /**
     * User ID.
     *
     * @var int
     */
    private int $user_id;

    /**
     * Last error message.
     *
     * @var string|null
     */
    private ?string $last_error = null;

    /**
     * Whether we've already tried to refresh the token in this request.
     *
     * @var bool
     */
    private bool $token_refresh_attempted = false;

    /**
     * Constructor.
     *
     * @param int|null $user_id WordPress user ID. Defaults to current user.
     */
    public function __construct(?int $user_id = null) {
        $this->user_id = $user_id ?? get_current_user_id();
        $this->load_credentials();
    }

    /**
     * Load credentials from user meta.
     *
     * @return void
     */
    private function load_credentials(): void {
        $this->access_token = tebuto_get_user_meta($this->user_id, 'access_token');
        $therapist_id = tebuto_get_user_meta($this->user_id, 'therapist_id');
        $this->therapist_id = $therapist_id ? (int) $therapist_id : null;
        
        // Auto-fetch therapist ID if missing but we have a valid token
        if (empty($this->therapist_id) && !empty($this->access_token)) {
            $this->fetch_and_store_therapist_id();
        }
    }

    /**
     * Fetch therapist ID from API and store it.
     *
     * @param bool $retry Whether this is a retry after token refresh.
     * @return bool True if therapist ID was fetched successfully.
     */
    private function fetch_and_store_therapist_id(bool $retry = false): bool {
        if (empty($this->access_token)) {
            $this->last_error = __('Kein Access Token vorhanden.', 'tebuto-online-terminbuchung');
            return false;
        }

        $response = wp_remote_get(TEBUTO_API_URL . '/who-am-i', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            $this->last_error = $response->get_error_message();
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // If unauthorized and haven't retried yet, try refreshing token
        if ($status_code === 401 && !$retry && !$this->token_refresh_attempted) {
            if ($this->refresh_access_token()) {
                return $this->fetch_and_store_therapist_id(true);
            }
            return false;
        }
        
        if ($status_code !== 200) {
            $this->last_error = sprintf(
                __('API-Fehler (Status %d): %s', 'tebuto-online-terminbuchung'),
                $status_code,
                $body
            );
            return false;
        }

        $response_body = json_decode($body, true);

        if (!isset($response_body['therapists'][0]['therapist']['id'])) {
            $this->last_error = __('Therapeuten-Daten nicht in API-Antwort gefunden.', 'tebuto-online-terminbuchung');
            return false;
        }

        $therapist = $response_body['therapists'][0]['therapist'];
        
        // Store ID
        if (isset($therapist['id'])) {
            $this->therapist_id = absint($therapist['id']);
            tebuto_update_user_meta($this->user_id, 'therapist_id', $this->therapist_id);
        }
        
        // Store UUID if not present
        if (isset($therapist['uuid'])) {
            $uuid = tebuto_get_user_meta($this->user_id, 'therapist_uuid');
            if (empty($uuid)) {
                tebuto_update_user_meta($this->user_id, 'therapist_uuid', sanitize_text_field($therapist['uuid']));
            }
        }
        
        // Store name if not present
        if (isset($therapist['name'])) {
            $name = tebuto_get_user_meta($this->user_id, 'therapist_name');
            if (empty($name)) {
                tebuto_update_user_meta($this->user_id, 'therapist_name', sanitize_text_field($therapist['name']));
            }
        }

        return !empty($this->therapist_id);
    }

    /**
     * Check if user is connected to Tebuto.
     *
     * @return bool
     */
    public function is_connected(): bool {
        // Check refresh_token as primary indicator (same as global tebuto_is_connected())
        $refresh_token = tebuto_get_user_meta($this->user_id, 'refresh_token');
        return !empty($refresh_token);
    }

    /**
     * Get the last error message.
     *
     * @return string|null
     */
    public function get_last_error(): ?string {
        return $this->last_error;
    }

    /**
     * Get therapist ID.
     *
     * @return int|null
     */
    public function get_therapist_id(): ?int {
        return $this->therapist_id;
    }

    /**
     * Make an API request.
     *
     * @param string $method   HTTP method (GET, POST, PUT, PATCH, DELETE).
     * @param string $endpoint API endpoint (relative to base URL).
     * @param array  $data     Request data.
     * @param array  $query    Query parameters.
     * @return array|WP_Error Response data or error.
     */
    private function request(string $method, string $endpoint, array $data = [], array $query = []) {
        if (!$this->access_token) {
            $this->last_error = __('Nicht mit Tebuto verbunden.', 'tebuto-online-terminbuchung');
            return new WP_Error('not_connected', $this->last_error);
        }

        // Try to fetch therapist_id if missing
        if (!$this->therapist_id) {
            $this->fetch_and_store_therapist_id();
        }

        if (!$this->therapist_id) {
            // Return the error from fetch attempt, or a generic one
            if (empty($this->last_error)) {
                $this->last_error = __('Therapeuten-ID nicht gefunden. Bitte verbinde dich erneut mit Tebuto.', 'tebuto-online-terminbuchung');
            }
            return new WP_Error('no_therapist_id', $this->last_error);
        }

        // Refresh token if needed
        if (!$this->ensure_valid_token()) {
            $this->last_error = __('Token konnte nicht erneuert werden.', 'tebuto-online-terminbuchung');
            return new WP_Error('token_refresh_failed', $this->last_error);
        }

        $url = TEBUTO_API_URL . '/' . $endpoint;
        
        if (!empty($query)) {
            $url = add_query_arg($query, $url);
        }

        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 30,
        ];

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $this->last_error = $response->get_error_message();
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($status_code >= 400) {
            $error_message = $decoded['message'] ?? __('API-Fehler', 'tebuto-online-terminbuchung');
            $this->last_error = $error_message;
            return new WP_Error('api_error', $error_message, ['status' => $status_code, 'response' => $decoded]);
        }

        return $decoded ?? [];
    }

    /**
     * Ensure the access token is valid, refresh if needed.
     *
     * @return bool True if token is valid or was successfully refreshed.
     */
    private function ensure_valid_token(): bool {
        // If we've already tried to refresh in this request, don't try again
        if ($this->token_refresh_attempted) {
            return !empty($this->access_token);
        }

        // We don't have a good way to check if token is expired without trying
        // So we'll just return true and let the API call fail if needed
        // Token refresh will be triggered on 401 responses
        return true;
    }

    /**
     * Refresh the access token using the refresh token.
     *
     * @return bool True if token was refreshed successfully.
     */
    private function refresh_access_token(): bool {
        $this->token_refresh_attempted = true;

        $refresh_token = tebuto_get_user_meta($this->user_id, 'refresh_token');
        if (empty($refresh_token)) {
            $this->last_error = __('Kein Refresh Token vorhanden.', 'tebuto-online-terminbuchung');
            return false;
        }

        $token_url = TEBUTO_AUTH_URL . '/realms/tebuto-therapists/protocol/openid-connect/token';

        $response = wp_remote_post($token_url, [
            'body' => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refresh_token,
                'client_id'     => TEBUTO_CLIENT_ID,
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            $this->last_error = $response->get_error_message();
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200 || !isset($body['access_token'])) {
            $this->last_error = $body['error_description'] ?? __('Token-Refresh fehlgeschlagen.', 'tebuto-online-terminbuchung');
            return false;
        }

        // Update tokens
        $this->access_token = sanitize_text_field($body['access_token']);
        tebuto_update_user_meta($this->user_id, 'access_token', $this->access_token);

        if (isset($body['refresh_token'])) {
            tebuto_update_user_meta($this->user_id, 'refresh_token', sanitize_text_field($body['refresh_token']));
        }

        return true;
    }

    // =========================================================================
    // WHO AM I
    // =========================================================================

    /**
     * Get current user info from Tebuto.
     *
     * @return array|WP_Error
     */
    public function who_am_i() {
        if (!$this->access_token) {
            return new WP_Error('not_connected', __('Nicht verbunden.', 'tebuto-online-terminbuchung'));
        }

        $response = wp_remote_get(TEBUTO_API_URL . '/who-am-i', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    // =========================================================================
    // EVENT CATEGORIES
    // =========================================================================

    /**
     * Get all event categories.
     *
     * @return array|WP_Error
     */
    public function get_event_categories() {
        return $this->request('GET', 'therapists/' . $this->therapist_id . '/event-categories');
    }


    /**
     * Create an event category.
     *
     * @param array $data Category data.
     * @return array|WP_Error
     */
    public function create_event_category(array $data) {
        return $this->request('POST', 'therapists/' . $this->therapist_id . '/event-categories', $data);
    }

    /**
     * Update an event category.
     *
     * @param int   $category_id Category ID.
     * @param array $data        Category data.
     * @return array|WP_Error
     */
    public function update_event_category(int $category_id, array $data) {
        return $this->request('PUT', 'therapists/' . $this->therapist_id . '/event-categories/' . $category_id, $data);
    }

    /**
     * Delete an event category.
     *
     * @param int $category_id Category ID.
     * @return array|WP_Error
     */
    public function delete_event_category(int $category_id) {
        return $this->request('DELETE', 'therapists/' . $this->therapist_id . '/event-categories/' . $category_id);
    }

    // =========================================================================
    // EVENTS
    // =========================================================================

    /**
     * Get event overview for a date range.
     *
     * @param string $start Start date (ISO format).
     * @param string $end   End date (ISO format).
     * @return array|WP_Error
     */
    public function get_events(string $start, string $end) {
        return $this->request('GET', 'therapists/' . $this->therapist_id . '/events', [], [
            'start' => $start,
            'end'   => $end,
        ]);
    }

    /**
     * Skip an event.
     *
     * @param array $data Skip event data.
     * @return array|WP_Error
     */
    public function skip_event(array $data) {
        return $this->request('POST', 'therapists/' . $this->therapist_id . '/events/skip', $data);
    }

    /**
     * Release a skipped event.
     *
     * @param int $event_id Event ID.
     * @return array|WP_Error
     */
    public function release_event(int $event_id) {
        return $this->request('POST', 'therapists/' . $this->therapist_id . '/events/' . $event_id . '/release');
    }

    // =========================================================================
    // BOOKINGS
    // =========================================================================

    /**
     * Get bookings with optional filters.
     *
     * @param array $filters Optional filters.
     * @return array|WP_Error
     */
    public function get_bookings(array $filters = []) {
        $query = array_filter([
            'status'            => $filters['status'] ?? null,
            'outageFeeApplies'  => $filters['outage_fee_applies'] ?? null,
            'start'             => $filters['start'] ?? null,
            'end'               => $filters['end'] ?? null,
            'clientId'          => $filters['client_id'] ?? null,
            'payment-requested' => $filters['payment_requested'] ?? null,
            'payment-open'      => $filters['payment_open'] ?? null,
            'payment-due'       => $filters['payment_due'] ?? null,
            'page'              => $filters['page'] ?? 1,
            'pageSize'          => $filters['page_size'] ?? 20,
        ], fn($v) => $v !== null);

        return $this->request('GET', 'therapists/' . $this->therapist_id . '/bookings', [], $query);
    }

    /**
     * Confirm a booking.
     *
     * @param int $booking_id Booking ID.
     * @return array|WP_Error
     */
    public function confirm_booking(int $booking_id) {
        return $this->request('POST', 'therapists/' . $this->therapist_id . '/bookings/' . $booking_id . '/confirm');
    }

    /**
     * Reject a booking.
     *
     * @param int    $booking_id Booking ID.
     * @param string $message    Optional message.
     * @return array|WP_Error
     */
    public function reject_booking(int $booking_id, string $message = '') {
        return $this->request('POST', 'therapists/' . $this->therapist_id . '/bookings/' . $booking_id . '/reject', [
            'message' => $message,
        ]);
    }

    /**
     * Cancel a booking.
     *
     * @param int    $booking_id    Booking ID.
     * @param string $message       Optional message.
     * @param bool   $bookable_again Whether slot should be bookable again.
     * @return array|WP_Error
     */
    public function cancel_booking(int $booking_id, string $message = '', bool $bookable_again = true) {
        return $this->request('POST', 'therapists/' . $this->therapist_id . '/bookings/' . $booking_id . '/cancel', [
            'message'       => $message,
            'bookableAgain' => $bookable_again,
        ]);
    }

    // =========================================================================
    // EVENT RULES
    // =========================================================================

    /**
     * Get event rules for a category.
     *
     * @param int $category_id Category ID.
     * @return array|WP_Error
     */
    public function get_event_rules(int $category_id) {
        return $this->request('GET', 'therapists/' . $this->therapist_id . '/event-categories/' . $category_id . '/event-rules');
    }

    /**
     * Create an event rule.
     *
     * @param int   $category_id Category ID.
     * @param array $data        Rule data.
     * @return array|WP_Error
     */
    public function create_event_rule(int $category_id, array $data) {
        return $this->request('POST', 'therapists/' . $this->therapist_id . '/event-categories/' . $category_id . '/event-rules', $data);
    }

    /**
     * Delete an event rule.
     *
     * @param int $category_id Category ID.
     * @param int $rule_id     Rule ID.
     * @return array|WP_Error
     */
    public function delete_event_rule(int $category_id, int $rule_id) {
        return $this->request('DELETE', 'therapists/' . $this->therapist_id . '/event-categories/' . $category_id . '/event-rules/' . $rule_id);
    }

    // =========================================================================
    // CLIENTS
    // =========================================================================

    /**
     * Get all clients.
     *
     * @return array|WP_Error
     */
    public function get_clients() {
        return $this->request('GET', 'therapists/' . $this->therapist_id . '/clients');
    }

    /**
     * Get a single client.
     *
     * @param int $client_id Client ID.
     * @return array|WP_Error
     */
    public function get_client(int $client_id) {
        return $this->request('GET', 'therapists/' . $this->therapist_id . '/clients/' . $client_id);
    }

    // =========================================================================
    // THERAPIST INFO
    // =========================================================================

    /**
     * Get therapist information.
     *
     * @return array|WP_Error
     */
    public function get_therapist() {
        return $this->request('GET', 'therapists/' . $this->therapist_id);
    }
}

