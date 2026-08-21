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

const CONTENT_ATTRS = new Set(['categories', 'seminars', 'customCss'])

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

function isAttrAllowedForTag(camel, isSeminars) {
	if (SKIP_SHORTCODE.has(camel)) {
		return false
	}
	if (isSeminars && BOOKING_ONLY_ATTRS.has(camel)) {
		return false
	}
	if (!isSeminars && SEMINARS_ONLY_ATTRS.has(camel)) {
		return false
	}
	return true
}

function isEmptyContentAttr(camel, value) {
	if (!CONTENT_ATTRS.has(camel)) {
		return false
	}
	return !value || toStringValue(value).trim() === ''
}

function shouldOmitAsDefault(value, defaultValue) {
	if (defaultValue !== undefined && valuesEqual(value, defaultValue)) {
		return true
	}
	if (defaultValue === undefined && (value === '' || value === false || value == null)) {
		return true
	}
	return false
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
		if (!(camel in attrs) || !isAttrAllowedForTag(camel, isSeminars)) {
			continue
		}

		const value = attrs[camel]

		if (isEmptyContentAttr(camel, value)) {
			continue
		}

		if (CONTENT_ATTRS.has(camel)) {
			params.push(`${snake}="${formatShortcodeValue(camel, value)}"`)
			continue
		}

		if (shouldOmitAsDefault(value, defaults[camel])) {
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
