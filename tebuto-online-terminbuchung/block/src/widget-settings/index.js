import { createRoot, render } from '@wordpress/element'
import { useCallback, useEffect, useState } from 'react'
import { camelToSnake } from '../shared/attributeMap'
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
	const bookingDefaults = getDefaults('booking')
	const seminarsDefaults = getDefaults('seminars')
	return {
		...bookingDefaults,
		...seminarsDefaults,
		...settings
	}
}

function ensureHiddenInput(form, name) {
	let input = form.querySelector(`[name="${name}"]`)
	if (!input) {
		input = document.createElement('input')
		input.type = 'hidden'
		input.name = name
		form.appendChild(input)
	}
	return input
}

function syncCategoriesInputs(form, value) {
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

	const categoriesField = ensureHiddenInput(form, 'categories')
	categoriesField.value = value || ''
}

function setInputValue(input, camel, value) {
	if (BOOLEAN_FIELDS.has(camel)) {
		if (input.type === 'checkbox') {
			input.checked = Boolean(value)
		} else {
			input.value = value ? 'true' : 'false'
		}
		return
	}

	input.value = value ?? ''
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

		if (camel === 'categories') {
			syncCategoriesInputs(form, value)
			continue
		}

		const snake = camelToSnake(camel)
		const input = ensureHiddenInput(form, snake)
		setInputValue(input, camel, value)
	}
}

function WidgetSettingsApp() {
	const [variant, setVariant] = useState('booking')
	const [attributes, setAttributes] = useState(readInitialAttributes)

	const mergeAttributes = useCallback((next) => {
		setAttributes((prev) => ({ ...prev, ...next }))
	}, [])

	useEffect(() => {
		syncHiddenInputs(attributes)
	}, [attributes])

	return (
		<WidgetConfigurator
			variant={variant}
			surface="admin"
			attributes={attributes}
			setAttributes={mergeAttributes}
			onVariantChange={setVariant}
		/>
	)
}

const rootEl = document.getElementById('tebuto-widget-settings-app')
if (rootEl) {
	if (typeof createRoot === 'function') {
		createRoot(rootEl).render(<WidgetSettingsApp />)
	} else {
		render(<WidgetSettingsApp />, rootEl)
	}
}
