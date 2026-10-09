import { useCallback, useEffect, useId, useRef } from 'react'
import { getPreviewThemeDataset } from './previewTheme'

function applyCommonDataset(script, therapistUuid, attributes, surface) {
	const { border, inheritFont } = attributes

	script.dataset.therapistUuid = therapistUuid
	Object.assign(script.dataset, getPreviewThemeDataset(attributes, surface))
	script.dataset.border = border ? 'true' : 'false'
	script.dataset.inheritFont = inheritFont ? 'true' : 'false'
}

function applyBookingDataset(script, attributes, selectedCategories, shouldUseConfiguredCategories) {
	const { showLocationQuickFilter, showCategorySelectionFirst, categories } = attributes

	if (attributes.showQuickFilters) {
		script.dataset.showQuickFilters = 'true'
	}
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
}

function applySeminarsDataset(script, attributes) {
	const { seminars, showListFirst } = attributes

	if (seminars?.trim()) {
		script.dataset.seminars = seminars.trim()
	}

	if (showListFirst === false) {
		script.dataset.showListFirst = 'false'
	}
}

/**
 * Inject / reload the booking or seminars widget script into a container.
 *
 * @param {import('react').RefObject<HTMLElement|null>} containerRef
 * @param {{
 *   variant: 'booking'|'seminars',
 *   therapistUuid: string,
 *   widgetUrl: string,
 *   attributes: Record<string, unknown>,
 *   surface?: 'inspector'|'admin',
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
		surface = 'inspector',
		selectedCategories = [],
		shouldUseConfiguredCategories = false
	} = options

	const widgetScriptRef = useRef(null)
	const previewId = useId().replace(/[^a-zA-Z0-9_-]/g, '')

	const loadWidgetPreview = useCallback(() => {
		if (!containerRef.current || !therapistUuid || !widgetUrl) {
			return
		}

		const legacyId = variant === 'seminars' ? 'tebuto-seminars-widget' : 'tebuto-booking-widget'
		const containerId = `${legacyId}-preview-${previewId}`
		const mount = document.createElement('div')
		mount.id = containerId
		containerRef.current.replaceChildren(mount)
		if (attributes.customCss) {
			const style = document.createElement('style')
			const css = attributes.customCss.replaceAll(`#${legacyId}`, ':scope')
			style.textContent = `@scope (#${containerId}) { ${css} }`
			containerRef.current.appendChild(style)
		}

		if (widgetScriptRef.current) {
			widgetScriptRef.current.remove()
		}

		const script = document.createElement('script')
		script.src = widgetUrl
		script.dataset.containerId = containerId
		applyCommonDataset(script, therapistUuid, attributes, surface)

		if (variant === 'booking') {
			applyBookingDataset(script, attributes, selectedCategories, shouldUseConfiguredCategories)
		} else {
			applySeminarsDataset(script, attributes)
		}

		script.async = true
		widgetScriptRef.current = script
		containerRef.current.appendChild(script)
	}, [
		containerRef,
		previewId,
		variant,
		therapistUuid,
		widgetUrl,
		attributes,
		surface,
		selectedCategories,
		shouldUseConfiguredCategories
	])

	useEffect(() => {
		const timer = setTimeout(() => {
			loadWidgetPreview()
		}, 500)

		return () => clearTimeout(timer)
	}, [loadWidgetPreview])

	return { reload: loadWidgetPreview }
}
