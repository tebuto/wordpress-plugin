import { getDefaults } from './theme'

const CAMEL_TO_SNAKE = {
	primaryColor: 'primary_color',
	backgroundColor: 'background_color',
	textPrimary: 'text_primary',
	textSecondary: 'text_secondary',
	borderColor: 'border_color',
	border: 'border',
	inheritFont: 'inherit_font',
	showQuickFilters: 'show_quick_filters',
	showProviderFilter: 'show_provider_filter',
	showLocationQuickFilter: 'show_location_quick_filter',
	showCategorySelectionFirst: 'show_category_selection_first',
	categories: 'categories',
	configuredCategoriesJson: 'configured_categories_json',
	customCss: 'custom_css',
	seminars: 'seminars',
	showListFirst: 'show_list_first'
}

const CAMEL_TO_DATA = {
	primaryColor: 'primary-color',
	backgroundColor: 'background-color',
	textPrimary: 'text-primary',
	textSecondary: 'text-secondary',
	borderColor: 'border-color',
	border: 'border',
	inheritFont: 'inherit-font',
	showQuickFilters: 'show-quick-filters',
	showLocationQuickFilter: 'show-location-quick-filter',
	showCategorySelectionFirst: 'show-category-selection-first',
	categories: 'categories',
	seminars: 'seminars',
	showListFirst: 'show-list-first'
}

const BOOLEAN_ATTRS = new Set([
	'border',
	'inheritFont',
	'showQuickFilters',
	'showProviderFilter',
	'showLocationQuickFilter',
	'showCategorySelectionFirst',
	'showListFirst'
])

const SKIP_SHORTCODE = new Set(['configuredCategoriesJson'])

const BOOKING_ONLY_ATTRS = new Set([
	'categories',
	'configuredCategoriesJson',
	'showQuickFilters',
	'showProviderFilter',
	'showLocationQuickFilter',
	'showCategorySelectionFirst'
])

const SEMINARS_ONLY_ATTRS = new Set(['seminars', 'showListFirst'])

function valuesEqual(a, b) {
	if (typeof a === 'boolean' || typeof b === 'boolean') {
		return Boolean(a) === Boolean(b)
	}
	if (typeof a === 'string' && typeof b === 'string' && a.startsWith('#') && b.startsWith('#')) {
		return a.toLowerCase() === b.toLowerCase()
	}
	return String(a ?? '') === String(b ?? '')
}

function toStringValue(value) {
	if (value === null || value === undefined) {
		return ''
	}
	if (typeof value === 'object') {
		return JSON.stringify(value)
	}
	return String(value)
}

function formatShortcodeValue(key, value) {
	if (BOOLEAN_ATTRS.has(key)) {
		return value ? 'true' : 'false'
	}
	if (key === 'customCss') {
		return toStringValue(value).replaceAll('"', '&quot;')
	}
	return toStringValue(value)
}

/**
 * Map camelCase block attributes to data-* keys for the widget script.
 *
 * @param {Record<string, unknown>} attrs
 * @param {'booking'|'seminars'} _variant
 * @returns {Record<string, string>}
 */
export function toDataAttributes(attrs, _variant) {
	const data = {}

	for (const [camel, dataKey] of Object.entries(CAMEL_TO_DATA)) {
		if (!(camel in attrs) || attrs[camel] === undefined || attrs[camel] === null) {
			continue
		}

		const value = attrs[camel]

		if (BOOLEAN_ATTRS.has(camel)) {
			if (camel === 'showCategorySelectionFirst' || camel === 'showListFirst') {
				if (value === false) {
					data[`data-${dataKey}`] = 'false'
				}
				continue
			}
			if (camel === 'border') {
				data[`data-${dataKey}`] = value ? 'true' : 'false'
				continue
			}
			if (value) {
				data[`data-${dataKey}`] = 'true'
			}
			continue
		}

		if (typeof value === 'string' && value.trim() === '') {
			continue
		}

		data[`data-${dataKey}`] = toStringValue(value)
	}

	if (attrs.showProviderFilter) {
		data['data-include-subusers'] = 'true'
		data['data-show-quick-filters'] = 'true'
	}

	if (attrs.configuredCategoriesJson) {
		data['data-configured-categories'] = toStringValue(attrs.configuredCategoriesJson)
	}

	return data
}

/**
 * Build a shortcode string, emitting only attributes that differ from defaults.
 *
 * @param {Record<string, unknown>} attrs
 * @param {string} tag
 * @returns {string}
 */
export function buildShortcode(attrs, tag = 'tebuto_online_terminbuchung_widget') {
	const isSeminars = tag === 'tebuto_seminare_widget'
	const defaults = getDefaults(isSeminars ? 'seminars' : 'booking')
	const params = []

	for (const [camel, snake] of Object.entries(CAMEL_TO_SNAKE)) {
		if (SKIP_SHORTCODE.has(camel) || !(camel in attrs)) {
			continue
		}

		if (isSeminars && BOOKING_ONLY_ATTRS.has(camel)) {
			continue
		}

		if (!isSeminars && SEMINARS_ONLY_ATTRS.has(camel)) {
			continue
		}

		const value = attrs[camel]
		const defaultValue = defaults[camel]

		if (camel === 'categories' || camel === 'seminars' || camel === 'customCss') {
			if (!value || toStringValue(value).trim() === '') {
				continue
			}
			params.push(`${snake}="${formatShortcodeValue(camel, value)}"`)
			continue
		}

		if (defaultValue !== undefined && valuesEqual(value, defaultValue)) {
			continue
		}

		if (defaultValue === undefined && (value === '' || value === false || value == null)) {
			continue
		}

		params.push(`${snake}="${formatShortcodeValue(camel, value)}"`)
	}

	if (params.length === 0) {
		return `[${tag}]`
	}

	return `[${tag} ${params.join(' ')}]`
}

export function camelToSnake(key) {
	return CAMEL_TO_SNAKE[key] || key.replace(/[A-Z]/g, (m) => `_${m.toLowerCase()}`)
}

export { CAMEL_TO_SNAKE }
