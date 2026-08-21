<?php
/**
 * Tebuto API Client.
 *
 * Handles all communication with the Tebuto API.
 *
 * @package Tebuto
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tebuto API Client class.
 */
class Tebuto_API {

	private const HEADER_AUTH_PREFIX    = 'Bearer ';
	private const PATH_THERAPISTS       = 'therapists/';
	private const PATH_EVENT_CATEGORIES = '/event-categories/';
	private const PATH_BOOKINGS         = '/bookings/';
	private const PATH_SEMINARS         = '/seminars/';
	private const PATH_OCCURRENCES      = '/seminars/occurrences/';

	/**
	 * Access token.
	 *
	 * @var string|null
	 */
	private ?string $access_token = null;

	/**
	 * Therapist ID.
	 *
	 * @var int|null
	 */
	private ?int $therapist_id = null;

	/**
	 * User ID (from who-am-i, needed for managed users endpoint).
	 *
	 * @var int|null
	 */
	private ?int $tebuto_user_id = null;

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
	public function __construct( ?int $user_id = null ) {
		$this->user_id = $user_id ?? get_current_user_id();
		$this->load_credentials();
	}

	/**
	 * Load credentials from user meta.
	 *
	 * @return void
	 */
	private function load_credentials(): void {
		$this->access_token   = tebuto_get_user_meta( $this->user_id, 'access_token' );
		$therapist_id         = tebuto_get_user_meta( $this->user_id, 'therapist_id' );
		$this->therapist_id   = $therapist_id ? (int) $therapist_id : null;
		$tebuto_user_id       = tebuto_get_user_meta( $this->user_id, 'tebuto_user_id' );
		$this->tebuto_user_id = $tebuto_user_id ? (int) $tebuto_user_id : null;

		// Auto-fetch therapist ID if missing but we have a valid token
		if ( empty( $this->therapist_id ) && ! empty( $this->access_token ) ) {
			$this->fetch_and_store_therapist_id();
		}
	}

	/**
	 * Fetch therapist ID from API and store it.
	 *
	 * @param bool $retry Whether this is a retry after token refresh.
	 * @return bool True if therapist ID was fetched successfully.
	 */
	private function fetch_and_store_therapist_id( bool $retry = false ): bool {
		if ( empty( $this->access_token ) ) {
			$this->last_error = __( 'Kein Access Token vorhanden.', 'tebuto-online-terminbuchung' );
			return false;
		}

		$response = wp_remote_get(
			TEBUTO_API_URL . '/who-am-i',
			array(
				'headers'   => array(
					'Authorization' => self::HEADER_AUTH_PREFIX . $this->access_token,
				),
				'timeout'   => 30,
				'sslverify' => TEBUTO_SSL_VERIFY,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->last_error = $response->get_error_message();
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		// If unauthorized and haven't retried yet, try refreshing token
		if ( $status_code === 401 && ! $retry && ! $this->token_refresh_attempted ) {
			if ( $this->refresh_access_token() ) {
				return $this->fetch_and_store_therapist_id( true );
			}

			$this->session_expired_error();
			return false;
		}

		if ( $status_code !== 200 ) {
			$this->last_error = sprintf(
				/* translators: 1: HTTP status code, 2: response body */
				__( 'API-Fehler (Status %1$d): %2$s', 'tebuto-online-terminbuchung' ),
				$status_code,
				$body
			);
			return false;
		}

		$response_body = json_decode( $body, true );

		if ( ! is_array( $response_body ) ) {
			$this->last_error = __( 'Therapeuten-Daten nicht in API-Antwort gefunden.', 'tebuto-online-terminbuchung' );
			return false;
		}

		return $this->persist_therapist_identity_from_whoami( $response_body );
	}

	/**
	 * Persist therapist / user identity from a who-am-i response body.
	 *
	 * @param array $response_body Decoded who-am-i payload.
	 * @return bool True if therapist ID was stored.
	 */
	private function persist_therapist_identity_from_whoami( array $response_body ): bool {
		if ( ! isset( $response_body['therapists'][0]['therapist']['id'] ) ) {
			$this->last_error = __( 'Therapeuten-Daten nicht in API-Antwort gefunden.', 'tebuto-online-terminbuchung' );
			return false;
		}

		if ( isset( $response_body['id'] ) ) {
			$this->tebuto_user_id = absint( $response_body['id'] );
			tebuto_update_user_meta( $this->user_id, 'tebuto_user_id', $this->tebuto_user_id );
		}

		$therapist = $response_body['therapists'][0]['therapist'];

		if ( isset( $therapist['id'] ) ) {
			$this->therapist_id = absint( $therapist['id'] );
			tebuto_update_user_meta( $this->user_id, 'therapist_id', $this->therapist_id );
		}

		if ( isset( $therapist['uuid'] ) ) {
			$uuid = tebuto_get_user_meta( $this->user_id, 'therapist_uuid' );
			if ( empty( $uuid ) ) {
				tebuto_update_user_meta( $this->user_id, 'therapist_uuid', sanitize_text_field( $therapist['uuid'] ) );
			}
		}

		if ( isset( $therapist['name'] ) ) {
			$name = tebuto_get_user_meta( $this->user_id, 'therapist_name' );
			if ( empty( $name ) ) {
				tebuto_update_user_meta( $this->user_id, 'therapist_name', sanitize_text_field( $therapist['name'] ) );
			}
		}

		return ! empty( $this->therapist_id );
	}

	/**
	 * Check if user is connected to Tebuto.
	 *
	 * @return bool
	 */
	public function is_connected(): bool {
		// Check refresh_token as primary indicator (same as global tebuto_is_connected())
		$refresh_token = tebuto_get_user_meta( $this->user_id, 'refresh_token' );
		return ! empty( $refresh_token );
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
	 * Ensure credentials and token are ready for an authenticated API call.
	 *
	 * @return true|WP_Error
	 */
	private function ensure_ready_for_authenticated_request() {
		if ( ! $this->access_token ) {
			$this->last_error = __( 'Nicht mit Tebuto verbunden.', 'tebuto-online-terminbuchung' );
			return new WP_Error( 'not_connected', $this->last_error );
		}

		if ( ! $this->therapist_id ) {
			$this->fetch_and_store_therapist_id();
		}

		if ( ! $this->therapist_id ) {
			if ( empty( $this->last_error ) ) {
				$this->last_error = __( 'Therapeuten-ID nicht gefunden. Bitte verbinde dich erneut mit Tebuto.', 'tebuto-online-terminbuchung' );
			}
			return new WP_Error( 'no_therapist_id', $this->last_error );
		}

		if ( ! $this->ensure_valid_token() ) {
			$this->last_error = __( 'Token konnte nicht erneuert werden.', 'tebuto-online-terminbuchung' );
			return new WP_Error( 'token_refresh_failed', $this->last_error );
		}

		return true;
	}

	/**
	 * Decode an HTTP response, refresh once on 401, and map API errors.
	 *
	 * @param array|WP_Error $response       HTTP response from wp_remote_*.
	 * @param bool           $is_retry       Whether this is already a retry.
	 * @param callable       $retry_callback Zero-arg callback to re-issue the request.
	 * @return array|WP_Error
	 */
	private function decode_authenticated_http_response( $response, bool $is_retry, callable $retry_callback ) {
		if ( is_wp_error( $response ) ) {
			$this->last_error = $response->get_error_message();
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$decoded     = json_decode( $body, true );

		if ( $status_code === 401 && ! $is_retry ) {
			if ( $this->refresh_access_token() ) {
				return $retry_callback();
			}

			return $this->session_expired_error();
		}

		if ( $status_code >= 400 ) {
			$error_message    = is_array( $decoded ) && isset( $decoded['message'] )
				? $decoded['message']
				: __( 'API-Fehler', 'tebuto-online-terminbuchung' );
			$this->last_error = $error_message;
			return new WP_Error(
				'api_error',
				$error_message,
				array(
					'status'   => $status_code,
					'response' => $decoded,
				)
			);
		}

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Make an API request.
	 *
	 * @param string $method   HTTP method (GET, POST, PUT, PATCH, DELETE).
	 * @param string $endpoint API endpoint (relative to base URL).
	 * @param array  $data     Request data.
	 * @param array  $query    Query parameters.
	 * @param bool   $is_retry Whether this is a retry after token refresh.
	 * @return array|WP_Error Response data or error.
	 */
	private function request( string $method, string $endpoint, array $data = array(), array $query = array(), bool $is_retry = false ) {
		$ready = $this->ensure_ready_for_authenticated_request();
		if ( is_wp_error( $ready ) ) {
			return $ready;
		}

		$url = TEBUTO_API_URL . '/' . $endpoint;

		if ( ! empty( $query ) ) {
			$url = add_query_arg( $query, $url );
		}

		$args = array(
			'method'    => $method,
			'headers'   => array(
				'Authorization' => self::HEADER_AUTH_PREFIX . $this->access_token,
				'Content-Type'  => 'application/json',
			),
			'timeout'   => 30,
			'sslverify' => TEBUTO_SSL_VERIFY,
		);

		if ( ! empty( $data ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $data );
		}

		$response = wp_remote_request( $url, $args );

		return $this->decode_authenticated_http_response(
			$response,
			$is_retry,
			function () use ( $method, $endpoint, $data, $query ) {
				return $this->request( $method, $endpoint, $data, $query, true );
			}
		);
	}

	/**
	 * Build a session-expired error and clear stored OAuth tokens.
	 *
	 * @return WP_Error
	 */
	private function session_expired_error(): WP_Error {
		tebuto_clear_auth_tokens( $this->user_id );
		$this->access_token = null;
		$this->last_error   = __( 'Deine Tebuto-Sitzung ist abgelaufen. Bitte melde dich erneut an.', 'tebuto-online-terminbuchung' );

		return new WP_Error( 'session_expired', $this->last_error );
	}

	/**
	 * Ensure the access token is valid, refresh if needed.
	 *
	 * @return bool True if token is valid or was successfully refreshed.
	 */
	private function ensure_valid_token(): bool {
		// If we've already tried to refresh in this request, don't try again
		if ( $this->token_refresh_attempted ) {
			return ! empty( $this->access_token );
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

		$refresh_token = tebuto_get_user_meta( $this->user_id, 'refresh_token' );
		if ( empty( $refresh_token ) ) {
			$this->last_error = __( 'Kein Refresh Token vorhanden.', 'tebuto-online-terminbuchung' );
			return false;
		}

		$token_url = TEBUTO_AUTH_URL . '/realms/tebuto-therapists/protocol/openid-connect/token';

		$response = wp_remote_post(
			$token_url,
			array(
				'body'      => array(
					'grant_type'    => 'refresh_token',
					'refresh_token' => $refresh_token,
					'client_id'     => TEBUTO_CLIENT_ID,
				),
				'timeout'   => 30,
				'sslverify' => TEBUTO_SSL_VERIFY,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->last_error = $response->get_error_message();
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code !== 200 || ! isset( $body['access_token'] ) ) {
			$error_code = $body['error'] ?? '';
			if ( in_array( $error_code, array( 'invalid_grant', 'invalid_token' ), true ) ) {
				tebuto_clear_auth_tokens( $this->user_id );
				$this->access_token = null;
			}

			$this->last_error = $body['error_description'] ?? __( 'Token-Refresh fehlgeschlagen.', 'tebuto-online-terminbuchung' );
			return false;
		}

		// Update tokens
		$this->access_token = sanitize_text_field( $body['access_token'] );
		tebuto_update_user_meta( $this->user_id, 'access_token', $this->access_token );

		if ( isset( $body['refresh_token'] ) ) {
			tebuto_update_user_meta( $this->user_id, 'refresh_token', sanitize_text_field( $body['refresh_token'] ) );
		}

		return true;
	}

	// =========================================================================
	// WHO AM I
	// =========================================================================

	/**
	 * Get current user info from Tebuto.
	 *
	 * @param bool $is_retry Whether this is a retry after token refresh.
	 * @return array|WP_Error
	 */
	public function who_am_i( bool $is_retry = false ) {
		if ( ! $this->access_token ) {
			return new WP_Error( 'not_connected', __( 'Nicht verbunden.', 'tebuto-online-terminbuchung' ) );
		}

		$response = wp_remote_get(
			TEBUTO_API_URL . '/who-am-i',
			array(
				'headers'   => array(
					'Authorization' => self::HEADER_AUTH_PREFIX . $this->access_token,
				),
				'timeout'   => 30,
				'sslverify' => TEBUTO_SSL_VERIFY,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( $status_code === 401 && ! $is_retry ) {
			if ( $this->refresh_access_token() ) {
				return $this->who_am_i( true );
			}

			return $this->session_expired_error();
		}

		if ( $status_code !== 200 ) {
			$body             = wp_remote_retrieve_body( $response );
			$this->last_error = sprintf(
				/* translators: 1: HTTP status code, 2: response body */
				__( 'API-Fehler (Status %1$d): %2$s', 'tebuto-online-terminbuchung' ),
				$status_code,
				$body
			);
			return new WP_Error( 'api_error', $this->last_error, array( 'status' => $status_code ) );
		}

		$body = wp_remote_retrieve_body( $response );
		return json_decode( $body, true );
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
		return $this->get_event_categories_for_therapist( $this->therapist_id );
	}

	/**
	 * Get event categories for a specific therapist (manager may query subaccounts).
	 *
	 * @param int|null $therapist_id Therapist ID. Defaults to the connected therapist.
	 * @return array|WP_Error
	 */
	public function get_event_categories_for_therapist( ?int $therapist_id = null ) {
		$resolved_therapist_id = $therapist_id ?? $this->therapist_id;
		if ( empty( $resolved_therapist_id ) ) {
			return new WP_Error( 'no_therapist_id', __( 'Therapeuten-ID nicht gefunden.', 'tebuto-online-terminbuchung' ) );
		}

		return $this->request( 'GET', self::PATH_THERAPISTS . $resolved_therapist_id . '/event-categories' );
	}

	/**
	 * Normalize a category row for plugin lists (dashboard, widget configurator).
	 *
	 * @param array  $category           Raw API category.
	 * @param int    $therapist_id       Owning therapist ID.
	 * @param string $therapist_name     Owning therapist name.
	 * @param bool   $is_from_subaccount Whether the row belongs to a managed account.
	 * @return array
	 */
	private function map_aggregated_category_row(
		array $category,
		int $therapist_id,
		string $therapist_name,
		bool $is_from_subaccount
	): array {
		$name                   = $category['name'] ?? __( 'Unbenannt', 'tebuto-online-terminbuchung' );
		$display_name           = $is_from_subaccount && $therapist_name !== ''
			? sprintf( '%s (%s)', $name, $therapist_name )
			: $name;
		$public_booking_enabled = ! empty( $category['publicBookingEnabled'] );

		return array_merge(
			$category,
			array(
				'id'                   => $category['id'] ?? 0,
				'name'                 => $name,
				'displayName'          => $display_name,
				'color'                => $category['color'] ?? TEBUTO_COLOR_FALLBACK,
				'therapistId'          => $therapist_id,
				'therapistName'        => $therapist_name,
				'isFromSubaccount'     => $is_from_subaccount,
				'isInheritedCategory'  => ! empty( $category['isInheritedCategory'] ),
				'publicBookingEnabled' => $public_booking_enabled,
				'widgetSelectable'     => $public_booking_enabled,
			)
		);
	}

	/**
	 * Sort aggregated categories for plugin UIs: public first, then by display name.
	 *
	 * @param array $categories Aggregated category rows.
	 * @return array
	 */
	private function sort_aggregated_event_categories( array $categories ): array {
		usort(
			$categories,
			static function ( array $a, array $b ): int {
				$public_compare = ( empty( $a['widgetSelectable'] ) ? 1 : 0 ) <=> ( empty( $b['widgetSelectable'] ) ? 1 : 0 );
				if ( $public_compare !== 0 ) {
					return $public_compare;
				}

				return strcasecmp(
					(string) ( $a['displayName'] ?? $a['name'] ?? '' ),
					(string) ( $b['displayName'] ?? $b['name'] ?? '' )
				);
			}
		);

		return $categories;
	}

	/**
	 * Aggregate event categories owned by the main therapist.
	 *
	 * @param string $main_name Main therapist display name.
	 * @return array|WP_Error
	 */
	private function aggregate_main_therapist_categories( string $main_name ) {
		$main_categories = $this->get_event_categories_for_therapist( $this->therapist_id );
		if ( is_wp_error( $main_categories ) ) {
			return $main_categories;
		}

		$result = array();
		if ( is_array( $main_categories ) ) {
			foreach ( $main_categories as $category ) {
				$result[] = $this->map_aggregated_category_row(
					$category,
					$this->therapist_id,
					$main_name,
					false
				);
			}
		}

		return $result;
	}

	/**
	 * Append non-inherited event categories from managed subaccounts.
	 *
	 * @param array $result Aggregated categories so far.
	 * @return array
	 */
	private function append_subaccount_event_categories( array $result ): array {
		$capabilities = tebuto_get_widget_account_capabilities( $this->user_id );
		if ( empty( $capabilities['has_managed_users'] ) ) {
			return $result;
		}

		$configured_therapists = $this->get_configured_therapists();
		foreach ( $configured_therapists as $therapist ) {
			$therapist_id = $therapist['id'] ?? 0;
			if ( ! $therapist_id || $therapist_id === $this->therapist_id ) {
				continue;
			}

			$sub_categories = $this->get_event_categories_for_therapist( $therapist_id );
			if ( is_wp_error( $sub_categories ) || ! is_array( $sub_categories ) ) {
				continue;
			}

			$therapist_name = $therapist['name'] ?? '';
			foreach ( $sub_categories as $category ) {
				if ( ! empty( $category['isInheritedCategory'] ) ) {
					continue;
				}

				$result[] = $this->map_aggregated_category_row(
					$category,
					$therapist_id,
					$therapist_name,
					true
				);
			}
		}

		return $result;
	}

	/**
	 * Aggregate all event categories for the manager account and subaccounts.
	 *
	 * Includes every category for display in the plugin. Non-public rows stay visible
	 * but are marked with widgetSelectable=false. Subaccount inherited mirrors are omitted.
	 *
	 * @return array|WP_Error
	 */
	public function get_aggregated_event_categories() {
		if ( empty( $this->therapist_id ) ) {
			return new WP_Error( 'no_therapist_id', __( 'Therapeuten-ID nicht gefunden.', 'tebuto-online-terminbuchung' ) );
		}

		$main_name = $this->get_therapist_name() ?? '';
		$result    = $this->aggregate_main_therapist_categories( $main_name );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result = $this->append_subaccount_event_categories( $result );

		return $this->sort_aggregated_event_categories( $result );
	}

	/**
	 * Aggregate widget-selectable categories for the manager account and subaccounts.
	 *
	 * Public categories from the main therapist plus non-inherited public categories
	 * owned by subaccounts. Subaccount rows use "Name (Therapist)" labels.
	 *
	 * @return array|WP_Error
	 */
	public function get_widget_selectable_categories() {
		$aggregated = $this->get_aggregated_event_categories();
		if ( is_wp_error( $aggregated ) ) {
			return $aggregated;
		}

		return array_values(
			array_filter(
				$aggregated,
				static fn( array $category ): bool => ! empty( $category['widgetSelectable'] )
			)
		);
	}


	/**
	 * Create an event category.
	 *
	 * @param array $data Category data.
	 * @return array|WP_Error
	 */
	public function create_event_category( array $data ) {
		return $this->request( 'POST', self::PATH_THERAPISTS . $this->therapist_id . '/event-categories', $data );
	}

	/**
	 * Update an event category.
	 *
	 * @param int   $category_id Category ID.
	 * @param array $data        Category data.
	 * @return array|WP_Error
	 */
	public function update_event_category( int $category_id, array $data ) {
		return $this->request( 'PUT', self::PATH_THERAPISTS . $this->therapist_id . self::PATH_EVENT_CATEGORIES . $category_id, $data );
	}

	/**
	 * Delete an event category.
	 *
	 * @param int $category_id Category ID.
	 * @return array|WP_Error
	 */
	public function delete_event_category( int $category_id ) {
		return $this->request( 'DELETE', self::PATH_THERAPISTS . $this->therapist_id . self::PATH_EVENT_CATEGORIES . $category_id );
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
	public function get_events( string $start, string $end ) {
		return $this->request(
			'GET',
			self::PATH_THERAPISTS . $this->therapist_id . '/events',
			array(),
			array(
				'start' => $start,
				'end'   => $end,
			)
		);
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
	public function get_bookings( array $filters = array() ) {
		$query = array_filter(
			array(
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
			),
			fn( $v ) => $v !== null
		);

		return $this->request( 'GET', self::PATH_THERAPISTS . $this->therapist_id . '/bookings', array(), $query );
	}

	/**
	 * Confirm a booking.
	 *
	 * @param int $booking_id Booking ID.
	 * @return array|WP_Error
	 */
	public function confirm_booking( int $booking_id ) {
		return $this->request( 'POST', self::PATH_THERAPISTS . $this->therapist_id . self::PATH_BOOKINGS . $booking_id . '/confirm' );
	}

	/**
	 * Reject a booking.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $message    Optional message.
	 * @return array|WP_Error
	 */
	public function reject_booking( int $booking_id, string $message = '' ) {
		return $this->request(
			'POST',
			self::PATH_THERAPISTS . $this->therapist_id . self::PATH_BOOKINGS . $booking_id . '/reject',
			array(
				'message' => $message,
			)
		);
	}

	/**
	 * Cancel a booking.
	 *
	 * @param int    $booking_id    Booking ID.
	 * @param string $message       Optional message.
	 * @param bool   $bookable_again Whether slot should be bookable again.
	 * @return array|WP_Error
	 */
	public function cancel_booking( int $booking_id, string $message = '', bool $bookable_again = true ) {
		return $this->request(
			'POST',
			self::PATH_THERAPISTS . $this->therapist_id . self::PATH_BOOKINGS . $booking_id . '/cancel',
			array(
				'message'       => $message,
				'bookableAgain' => $bookable_again,
			)
		);
	}

	// =========================================================================
	// EVENT RULES
	// =========================================================================

	// =========================================================================
	// SEMINARS
	// =========================================================================

	/**
	 * List all seminars for the connected therapist.
	 *
	 * @return array|WP_Error
	 */
	public function get_seminars() {
		return $this->request( 'GET', self::PATH_THERAPISTS . $this->therapist_id . '/seminars' );
	}

	/**
	 * Get a single seminar including occurrences.
	 *
	 * @param int $seminar_id Seminar ID.
	 * @return array|WP_Error
	 */
	public function get_seminar( int $seminar_id ) {
		return $this->request( 'GET', self::PATH_THERAPISTS . $this->therapist_id . self::PATH_SEMINARS . $seminar_id );
	}

	/**
	 * Create a seminar (requires a nested first occurrence).
	 *
	 * @param array $data CreateSeminarPayload.
	 * @return array|WP_Error
	 */
	public function create_seminar( array $data ) {
		return $this->request( 'POST', self::PATH_THERAPISTS . $this->therapist_id . '/seminars', $data );
	}

	/**
	 * Update a seminar.
	 *
	 * @param int   $seminar_id Seminar ID.
	 * @param array $data       UpdateSeminarPayload.
	 * @return array|WP_Error
	 */
	public function update_seminar( int $seminar_id, array $data ) {
		return $this->request( 'PATCH', self::PATH_THERAPISTS . $this->therapist_id . self::PATH_SEMINARS . $seminar_id, $data );
	}

	/**
	 * Delete a seminar.
	 *
	 * @param int $seminar_id Seminar ID.
	 * @return array|WP_Error
	 */
	public function delete_seminar( int $seminar_id ) {
		return $this->request( 'DELETE', self::PATH_THERAPISTS . $this->therapist_id . self::PATH_SEMINARS . $seminar_id );
	}

	/**
	 * Upload a seminar banner image (multipart field name: file).
	 *
	 * @param int   $seminar_id Seminar ID.
	 * @param array $file       $_FILES entry with name, type, tmp_name, size, error.
	 * @return array|WP_Error
	 */
	public function upload_seminar_banner( int $seminar_id, array $file ) {
		$allowed_types = array( 'image/png', 'image/jpeg', 'image/webp' );
		$max_size      = 5 * 1024 * 1024;

		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'invalid_file', __( 'Keine gültige Datei hochgeladen.', 'tebuto-online-terminbuchung' ) );
		}

		$file_type = isset( $file['type'] ) ? (string) $file['type'] : '';
		if ( ! in_array( $file_type, $allowed_types, true ) ) {
			return new WP_Error( 'invalid_file_type', __( 'Nur PNG, JPEG oder WebP erlaubt.', 'tebuto-online-terminbuchung' ) );
		}

		if ( isset( $file['size'] ) && (int) $file['size'] > $max_size ) {
			return new WP_Error( 'file_too_large', __( 'Die Datei darf maximal 5 MB groß sein.', 'tebuto-online-terminbuchung' ) );
		}

		return $this->request_multipart(
			'POST',
			self::PATH_THERAPISTS . $this->therapist_id . self::PATH_SEMINARS . $seminar_id . '/banner',
			$file
		);
	}

	/**
	 * List occurrences for a seminar.
	 *
	 * @param int $seminar_id Seminar ID.
	 * @return array|WP_Error
	 */
	public function get_seminar_occurrences( int $seminar_id ) {
		return $this->request( 'GET', self::PATH_THERAPISTS . $this->therapist_id . self::PATH_SEMINARS . $seminar_id . '/occurrences' );
	}

	/**
	 * Create an occurrence for a seminar.
	 *
	 * @param int   $seminar_id Seminar ID.
	 * @param array $data       CreateSeminarOccurrencePayload.
	 * @return array|WP_Error
	 */
	public function create_seminar_occurrence( int $seminar_id, array $data ) {
		return $this->request( 'POST', self::PATH_THERAPISTS . $this->therapist_id . self::PATH_SEMINARS . $seminar_id . '/occurrences', $data );
	}

	/**
	 * Update occurrence settings (not sessions or status).
	 *
	 * @param int   $occurrence_id Occurrence ID.
	 * @param array $data          UpdateSeminarOccurrencePayload.
	 * @return array|WP_Error
	 */
	public function update_seminar_occurrence( int $occurrence_id, array $data ) {
		return $this->request( 'PATCH', self::PATH_THERAPISTS . $this->therapist_id . self::PATH_OCCURRENCES . $occurrence_id, $data );
	}

	/**
	 * Replace the sessions list of an occurrence.
	 *
	 * @param int   $occurrence_id Occurrence ID.
	 * @param array $sessions      SeminarSessionPayload[].
	 * @return array|WP_Error
	 */
	public function update_seminar_occurrence_sessions( int $occurrence_id, array $sessions ) {
		return $this->request(
			'PUT',
			self::PATH_THERAPISTS . $this->therapist_id . self::PATH_OCCURRENCES . $occurrence_id . '/sessions',
			array(
				'sessions' => $sessions,
			)
		);
	}

	/**
	 * Set occurrence publication status (draft|published|cancelled).
	 *
	 * @param int    $occurrence_id Occurrence ID.
	 * @param string $status        New status.
	 * @return array|WP_Error
	 */
	public function set_seminar_occurrence_status( int $occurrence_id, string $status ) {
		return $this->request(
			'POST',
			self::PATH_THERAPISTS . $this->therapist_id . self::PATH_OCCURRENCES . $occurrence_id . '/status',
			array(
				'status' => $status,
			)
		);
	}

	/**
	 * Cancel an occurrence.
	 *
	 * @param int         $occurrence_id Occurrence ID.
	 * @param string|null $reason        Optional cancellation reason.
	 * @return array|WP_Error
	 */
	public function cancel_seminar_occurrence( int $occurrence_id, ?string $reason = null ) {
		$data = array();
		if ( $reason !== null && $reason !== '' ) {
			$data['reason'] = $reason;
		}

		return $this->request(
			'POST',
			self::PATH_THERAPISTS . $this->therapist_id . self::PATH_OCCURRENCES . $occurrence_id . '/cancel',
			$data
		);
	}

	/**
	 * Reorder occurrences within a seminar.
	 *
	 * @param int   $seminar_id     Seminar ID.
	 * @param array $occurrence_ids Ordered occurrence IDs.
	 * @return array|WP_Error
	 */
	public function reorder_seminar_occurrences( int $seminar_id, array $occurrence_ids ) {
		return $this->request(
			'PUT',
			self::PATH_THERAPISTS . $this->therapist_id . self::PATH_SEMINARS . $seminar_id . '/occurrences/order',
			array(
				'occurrenceIds' => array_map( 'absint', $occurrence_ids ),
			)
		);
	}

	/**
	 * List registrations for an occurrence.
	 *
	 * @param int $occurrence_id Occurrence ID.
	 * @return array|WP_Error
	 */
	public function get_seminar_registrations( int $occurrence_id ) {
		return $this->request( 'GET', self::PATH_THERAPISTS . $this->therapist_id . self::PATH_OCCURRENCES . $occurrence_id . '/registrations' );
	}

	/**
	 * Whether the seminars feature is available and enabled for this therapist.
	 *
	 * @return array{enabled: bool, available: bool}|WP_Error
	 */
	public function is_seminars_feature_enabled() {
		$therapist = $this->get_therapist();
		if ( is_wp_error( $therapist ) ) {
			return $therapist;
		}

		$features = isset( $therapist['features'] ) && is_array( $therapist['features'] ) ? $therapist['features'] : array();

		$result = array(
			'enabled'   => ! empty( $features['featureSeminarsEnabled'] ),
			'available' => ! empty( $features['featureSeminarsAvailable'] ),
		);

		tebuto_store_seminars_feature_cache( $result, $this->user_id );

		return $result;
	}

	/**
	 * Build a multipart/form-data body for a single file field named "file".
	 *
	 * @param array $file $_FILES entry.
	 * @return array{body: string, boundary: string}|WP_Error
	 */
	private function build_multipart_file_body( array $file ) {
		$boundary  = wp_generate_password( 24, false );
		$filename  = isset( $file['name'] ) ? (string) $file['name'] : 'banner';
		$file_type = isset( $file['type'] ) ? (string) $file['type'] : 'application/octet-stream';
		$contents  = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading uploaded temp file.

		if ( $contents === false ) {
			$this->last_error = __( 'Datei konnte nicht gelesen werden.', 'tebuto-online-terminbuchung' );
			return new WP_Error( 'file_read_error', $this->last_error );
		}

		$body  = '--' . $boundary . "\r\n";
		$body .= 'Content-Disposition: form-data; name="file"; filename="' . $filename . "\"\r\n";
		$body .= 'Content-Type: ' . $file_type . "\r\n\r\n";
		$body .= $contents . "\r\n";
		$body .= '--' . $boundary . "--\r\n";

		return array(
			'body'     => $body,
			'boundary' => $boundary,
		);
	}

	/**
	 * Make a multipart/form-data API request (for file uploads).
	 *
	 * @param string $method   HTTP method.
	 * @param string $endpoint Relative endpoint.
	 * @param array  $file     $_FILES entry.
	 * @param bool   $is_retry Whether this is a retry after token refresh.
	 * @return array|WP_Error
	 */
	private function request_multipart( string $method, string $endpoint, array $file, bool $is_retry = false ) {
		$ready = $this->ensure_ready_for_authenticated_request();
		if ( is_wp_error( $ready ) ) {
			return $ready;
		}

		$multipart = $this->build_multipart_file_body( $file );
		if ( is_wp_error( $multipart ) ) {
			return $multipart;
		}

		$url  = TEBUTO_API_URL . '/' . $endpoint;
		$args = array(
			'method'    => $method,
			'headers'   => array(
				'Authorization' => self::HEADER_AUTH_PREFIX . $this->access_token,
				'Content-Type'  => 'multipart/form-data; boundary=' . $multipart['boundary'],
			),
			'body'      => $multipart['body'],
			'timeout'   => 60,
			'sslverify' => TEBUTO_SSL_VERIFY,
		);

		$response = wp_remote_request( $url, $args );

		return $this->decode_authenticated_http_response(
			$response,
			$is_retry,
			function () use ( $method, $endpoint, $file ) {
				return $this->request_multipart( $method, $endpoint, $file, true );
			}
		);
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
		return $this->request( 'GET', self::PATH_THERAPISTS . $this->therapist_id );
	}

	/**
	 * Get therapist name from stored meta.
	 *
	 * @return string|null
	 */
	public function get_therapist_name(): ?string {
		$therapist_name = tebuto_get_user_meta( $this->user_id, 'therapist_name' );
		return $therapist_name ? $therapist_name : null;
	}

	// =========================================================================
	// MANAGED USERS
	// =========================================================================

	/**
	 * Get managed users (subusers).
	 *
	 * Requires the Tebuto user ID (from who-am-i), not the therapist ID.
	 *
	 * @return array|WP_Error
	 */
	public function get_managed_users() {
		if ( empty( $this->tebuto_user_id ) ) {
			// Try to fetch it from who-am-i
			$who_am_i = $this->who_am_i();
			if ( is_wp_error( $who_am_i ) ) {
				return $who_am_i;
			}
			if ( isset( $who_am_i['id'] ) ) {
				$this->tebuto_user_id = absint( $who_am_i['id'] );
				tebuto_update_user_meta( $this->user_id, 'tebuto_user_id', $this->tebuto_user_id );
			}
		}

		if ( empty( $this->tebuto_user_id ) ) {
			return new WP_Error( 'no_user_id', __( 'Tebuto User-ID nicht gefunden.', 'tebuto-online-terminbuchung' ) );
		}

		return $this->request( 'GET', 'users/' . $this->tebuto_user_id . '/managed-users' );
	}

	/**
	 * Build the main therapist entry for configured-therapist lists.
	 *
	 * @return array{id: int, name: string}|null
	 */
	private function build_main_therapist_entry(): ?array {
		if ( ! $this->therapist_id ) {
			return null;
		}

		$main_name = $this->get_therapist_name();
		if ( $main_name ) {
			return array(
				'id'   => $this->therapist_id,
				'name' => $main_name,
			);
		}

		$info = $this->get_therapist();
		if ( ! is_wp_error( $info ) && isset( $info['name'] ) ) {
			tebuto_update_user_meta( $this->user_id, 'therapist_name', sanitize_text_field( $info['name'] ) );
			return array(
				'id'   => $this->therapist_id,
				'name' => $info['name'],
			);
		}

		return null;
	}

	/**
	 * Append managed users' therapists that are not already in the list.
	 *
	 * @param array $therapists Existing therapist entries.
	 * @return array
	 */
	private function append_managed_therapists( array $therapists ): array {
		$managed = $this->get_managed_users();
		if ( is_wp_error( $managed ) || ! isset( $managed['users'] ) || ! is_array( $managed['users'] ) ) {
			return $therapists;
		}

		$seen_ids = array_column( $therapists, 'id' );
		foreach ( $managed['users'] as $user ) {
			if ( ! isset( $user['therapists'] ) || ! is_array( $user['therapists'] ) ) {
				continue;
			}
			foreach ( $user['therapists'] as $t ) {
				$tid = $t['id'] ?? 0;
				if ( $tid && ! in_array( $tid, $seen_ids, true ) ) {
					$therapists[] = array(
						'id'   => $tid,
						'name' => $t['name'] ?? '',
					);
					$seen_ids[]   = $tid;
				}
			}
		}

		return $therapists;
	}

	/**
	 * Get all configured therapists (main therapist + managed users' therapists).
	 *
	 * Used internally when aggregating subaccount categories for the widget
	 * category picker — not emitted as a booking widget data attribute.
	 *
	 * @return array List of therapist entries with 'id' and 'name'.
	 */
	public function get_configured_therapists(): array {
		$therapists = array();

		$main = $this->build_main_therapist_entry();
		if ( null !== $main ) {
			$therapists[] = $main;
		}

		return $this->append_managed_therapists( $therapists );
	}
}
