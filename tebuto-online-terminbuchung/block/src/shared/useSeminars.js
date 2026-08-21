import { __ } from '@wordpress/i18n'
import { useEffect, useState } from 'react'
import { applyAjaxNetworkError, applyAjaxResult } from './ajaxResult'
import { getTebutoData } from './theme'

export default function useSeminars() {
	const [seminars, setSeminars] = useState([])
	const [loading, setLoading] = useState(true)
	const [error, setError] = useState(null)
	const [sessionExpired, setSessionExpired] = useState(false)

	const data = getTebutoData()
	const therapistUuid = data.uuid || ''
	const ajaxUrl = data.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php'
	const seminarsFeatureEnabled = data.seminarsFeatureEnabled === true

	useEffect(() => {
		if (!therapistUuid || !seminarsFeatureEnabled) {
			setLoading(false)
			return
		}

		let cancelled = false

		const fetchSeminars = async () => {
			try {
				const formData = new FormData()
				formData.append('action', 'tebuto_get_seminars')
				formData.append('nonce', getTebutoData().nonce || '')

				const response = await fetch(ajaxUrl, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin'
				})

				const payload = await response.json()

				if (cancelled) {
					return
				}

				applyAjaxResult(payload, {
					setData: setSeminars,
					setError,
					setSessionExpired,
					fallbackError: __('Seminare konnten nicht geladen werden.', 'tebuto-online-terminbuchung')
				})
			} catch {
				if (!cancelled) {
					applyAjaxNetworkError({
						setError,
						networkError: __('Verbindungsfehler beim Laden der Seminare.', 'tebuto-online-terminbuchung')
					})
				}
			} finally {
				if (!cancelled) {
					setLoading(false)
				}
			}
		}

		fetchSeminars()

		return () => {
			cancelled = true
		}
	}, [therapistUuid, ajaxUrl, seminarsFeatureEnabled])

	return { seminars, loading, error, sessionExpired, setSessionExpired }
}
