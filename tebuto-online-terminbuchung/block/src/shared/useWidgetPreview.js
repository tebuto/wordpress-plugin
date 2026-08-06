import { useCallback, useEffect, useRef } from 'react'

/**
 * Inject / reload the booking or seminars widget script into a container.
 *
 * @param {import('react').RefObject<HTMLElement|null>} containerRef
 * @param {{
 *   variant: 'booking'|'seminars',
 *   therapistUuid: string,
 *   widgetUrl: string,
 *   attributes: Record<string, unknown>,
 *   selectedCategories?: Array<Record<string, unknown>>,
 *   shouldUseConfiguredCategories?: boolean,
 * }} options
 */
export default function useWidgetPreview(containerRef, options) {
	const {
		variant,
		therapistUuid,
		widgetUrl,
		attributes,
		selectedCategories = [],
		shouldUseConfiguredCategories = false
	} = options

	const widgetScriptRef = useRef(null)

	const loadWidgetPreview = useCallback(() => {
		if (!containerRef.current || !therapistUuid || !widgetUrl) {
			return
		}

		const containerId = variant === 'seminars' ? 'tebuto-seminars-widget' : 'tebuto-booking-widget'
		containerRef.current.innerHTML = `<div id="${containerId}"></div>`

		if (widgetScriptRef.current) {
			widgetScriptRef.current.remove()
		}

		const {
			primaryColor,
			backgroundColor,
			textPrimary,
			textSecondary,
			borderColor,
			border,
			inheritFont,
			showLocationQuickFilter,
			showCategorySelectionFirst,
			categories,
			seminars,
			showListFirst
		} = attributes

		const script = document.createElement('script')
		script.src = widgetUrl
		script.dataset.therapistUuid = therapistUuid
		script.dataset.primaryColor = primaryColor
		script.dataset.backgroundColor = backgroundColor
		script.dataset.textPrimary = textPrimary
		script.dataset.textSecondary = textSecondary
		script.dataset.borderColor = borderColor
		script.dataset.border = border ? 'true' : 'false'
		script.dataset.inheritFont = inheritFont ? 'true' : 'false'

		if (variant === 'booking') {
			if (shouldUseConfiguredCategories) {
				script.dataset.includeSubusers = 'true'
				script.dataset.showQuickFilters = 'true'
			}
			if (showLocationQuickFilter) {
				script.dataset.showLocationQuickFilter = 'true'
			}

			if (shouldUseConfiguredCategories && selectedCategories.length > 0) {
				script.dataset.configuredCategories = JSON.stringify(
					selectedCategories.map((category) => ({
						id: category.id,
						name: category.name,
						color: category.color,
						isFromSubaccount: Boolean(category.isFromSubaccount),
						therapistId: category.therapistId ?? 0,
						therapistName: category.therapistName ?? ''
					}))
				)
			}

			if (categories) {
				script.dataset.categories = categories
			}

			if (showCategorySelectionFirst === false) {
				script.dataset.showCategorySelectionFirst = 'false'
			}
		} else {
			if (seminars?.trim()) {
				script.dataset.seminars = seminars.trim()
			}

			if (showListFirst === false) {
				script.dataset.showListFirst = 'false'
			}
		}

		script.async = true
		widgetScriptRef.current = script
		containerRef.current.appendChild(script)
	}, [containerRef, variant, therapistUuid, widgetUrl, attributes, selectedCategories, shouldUseConfiguredCategories])

	useEffect(() => {
		const timer = setTimeout(() => {
			loadWidgetPreview()
		}, 500)

		return () => clearTimeout(timer)
	}, [loadWidgetPreview])

	return { reload: loadWidgetPreview }
}
