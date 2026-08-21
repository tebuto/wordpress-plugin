import { __ } from '@wordpress/i18n'
import { useEffect, useState } from 'react'
import { applyAjaxNetworkError, applyAjaxResult } from './ajaxResult'
import { getTebutoData } from './theme'

export default function useCategories() {
	const [categories, setCategories] = useState([])
	const [loading, setLoading] = useState(true)
	const [error, setError] = useState(null)
	const [sessionExpired, setSessionExpired] = useState(false)

	const data = getTebutoData()
	const therapistUuid = data.uuid || ''
	const ajaxUrl = data.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php'

	useEffect(() => {
		if (!therapistUuid) {
			setLoading(false)
			return
		}

		let cancelled = false

		const fetchCategories = async () => {
			try {
				const formData = new FormData()
				formData.append('action', 'tebuto_get_categories')
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
					setData: setCategories,
					setError,
					setSessionExpired,
					fallbackError: __('Kategorien konnten nicht geladen werden.', 'tebuto-online-terminbuchung')
				})
			} catch {
				if (!cancelled) {
					applyAjaxNetworkError({
						setError,
						networkError: __('Verbindungsfehler beim Laden der Kategorien.', 'tebuto-online-terminbuchung')
					})
				}
			} finally {
				if (!cancelled) {
					setLoading(false)
				}
			}
		}

		fetchCategories()

		return () => {
			cancelled = true
		}
	}, [therapistUuid, ajaxUrl])

	return { categories, loading, error, sessionExpired, setSessionExpired }
}
