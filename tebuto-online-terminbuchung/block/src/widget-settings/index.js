import { createRoot, render } from '@wordpress/element'
import { useCallback, useEffect, useState } from 'react'
import { buildShortcode, camelToSnake } from '../shared/attributeMap'
import { getDefaults, getTebutoData } from '../shared/theme'
import WidgetConfigurator from '../shared/WidgetConfigurator'
import '../block/editor.scss'

const BOOLEAN_FIELDS = new Set([
	'border',
	'inheritFont',
	'showProviderFilter',
	'showLocationQuickFilter',
	'showCategorySelectionFirst',
	'showListFirst',
	'showQuickFilters'
])

function readInitialAttributes() {
	const settings = window.tebutoWidgetSettings || getTebutoData().defaultSettings || {}
	const defaults = getDefaults()
	return { ...defaults, ...settings }
}

function syncHiddenInputs(attributes) {
	const form = document.getElementById('tebuto-widget-settings-form')
	if (!form) {
		return
	}

	for (const [camel, value] of Object.entries(attributes)) {
		if (camel === 'configuredCategoriesJson') {
			continue
		}

		const snake = camelToSnake(camel)

		if (camel === 'categories') {
			const categoriesInput = form.querySelector('[name="categories_json"]')
			if (categoriesInput) {
				const ids = value
					? String(value)
							.split(',')
							.map((id) => Number.parseInt(id.trim(), 10))
							.filter((id) => Number.isFinite(id))
					: []
				categoriesInput.value = JSON.stringify(ids)
			}

			let categoriesField = form.querySelector('[name="categories"]')
			if (!categoriesField) {
				categoriesField = document.createElement('input')
				categoriesField.type = 'hidden'
				categoriesField.name = 'categories'
				form.appendChild(categoriesField)
			}
			categoriesField.value = value || ''
			continue
		}

		let input = form.querySelector(`[name="${snake}"]`)
		if (!input) {
			input = document.createElement('input')
			input.type = 'hidden'
			input.name = snake
			form.appendChild(input)
		}

		if (BOOLEAN_FIELDS.has(camel)) {
			if (input.type === 'checkbox') {
				input.checked = Boolean(value)
			} else {
				input.value = value ? 'true' : 'false'
			}
			continue
		}

		input.value = value ?? ''
	}

	const shortcodeEl = document.getElementById('tebuto-shortcode')
	if (shortcodeEl) {
		shortcodeEl.textContent = buildShortcode(attributes)
	}
}

function WidgetSettingsApp() {
	const [attributes, setAttributesState] = useState(readInitialAttributes)

	const setAttributes = useCallback((next) => {
		setAttributesState((prev) => ({ ...prev, ...next }))
	}, [])

	useEffect(() => {
		syncHiddenInputs(attributes)
	}, [attributes])

	return <WidgetConfigurator variant="booking" surface="admin" attributes={attributes} setAttributes={setAttributes} />
}

const rootEl = document.getElementById('tebuto-widget-settings-app')
if (rootEl) {
	if (typeof createRoot === 'function') {
		createRoot(rootEl).render(<WidgetSettingsApp />)
	} else {
		render(<WidgetSettingsApp />, rootEl)
	}
}
