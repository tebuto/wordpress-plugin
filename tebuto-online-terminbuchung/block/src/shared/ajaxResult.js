/**
 * Apply a WordPress AJAX JSON payload to React state setters.
 *
 * @param {{ success: boolean, data?: unknown }} payload
 * @param {{
 *   setData: (data: unknown) => void,
 *   setError: (error: unknown) => void,
 *   setSessionExpired: (expired: boolean) => void,
 *   fallbackError: string,
 * }} handlers
 */
export function applyAjaxResult(payload, { setData, setError, setSessionExpired, fallbackError }) {
	if (payload.success) {
		setData(payload.data)
		setError(null)
		return
	}

	const errorPayload = payload.data
	const errorCode = typeof errorPayload === 'object' && errorPayload !== null ? errorPayload.code : null

	if (errorCode === 'session_expired') {
		setSessionExpired(true)
		setError(null)
		return
	}

	const errorMessage =
		typeof errorPayload === 'object' && errorPayload !== null && errorPayload.message
			? errorPayload.message
			: errorPayload
	setError(errorMessage || fallbackError)
}

/**
 * Apply a network / parse failure to React state setters.
 *
 * @param {{ setError: (error: unknown) => void, networkError: string }} handlers
 */
export function applyAjaxNetworkError({ setError, networkError }) {
	setError(networkError)
}
