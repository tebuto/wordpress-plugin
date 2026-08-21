const COLOR_DEFAULTS = {
	primaryColor: '#00B4A9',
	backgroundColor: '#ffffff',
	textPrimary: '#374151',
	textSecondary: '#6b7280',
	borderColor: '#E9E9E9'
}

const COLOR_DATA_KEYS = {
	primaryColor: 'data-primary-color',
	backgroundColor: 'data-background-color',
	textPrimary: 'data-text-primary',
	textSecondary: 'data-text-secondary',
	borderColor: 'data-border-color'
}

function hasSubaccountCategories(configuredCategoriesJson) {
	if (!configuredCategoriesJson) {
		return false
	}

	try {
		const configuredCategories = JSON.parse(configuredCategoriesJson)
		return Array.isArray(configuredCategories)
			? configuredCategories.some((category) => category.isFromSubaccount === true)
			: false
	} catch {
		return false
	}
}

function resolveCategoriesForEmbed(categories, configuredCategoriesJson) {
	let categoriesForEmbed = categories
	if (!categoriesForEmbed?.trim() && configuredCategoriesJson) {
		try {
			const configuredCategories = JSON.parse(configuredCategoriesJson)
			if (Array.isArray(configuredCategories)) {
				const categoryIds = configuredCategories
					.map((category) => Number(category.id))
					.filter((categoryId) => Number.isFinite(categoryId) && categoryId > 0)
				if (categoryIds.length > 0) {
					categoriesForEmbed = categoryIds.join(',')
				}
			}
		} catch {
			categoriesForEmbed = categories
		}
	}
	return categoriesForEmbed
}

export function buildBookingWidgetDataAttributes(attributes, uuid) {
	const {
		primaryColor,
		backgroundColor,
		textPrimary,
		textSecondary,
		borderColor,
		border,
		inheritFont,
		showProviderFilter,
		showLocationQuickFilter,
		showCategorySelectionFirst,
		categories,
		configuredCategoriesJson
	} = attributes

	const widgetAttributes = {
		'data-therapist-uuid': uuid,
		'data-border': border ? 'true' : 'false'
	}

	const colorValues = {
		primaryColor,
		backgroundColor,
		textPrimary,
		textSecondary,
		borderColor
	}

	for (const [key, dataKey] of Object.entries(COLOR_DATA_KEYS)) {
		const value = colorValues[key]
		if (value && value !== COLOR_DEFAULTS[key]) {
			widgetAttributes[dataKey] = value
		}
	}

	if (inheritFont) {
		widgetAttributes['data-inherit-font'] = 'true'
	}

	const includeSubusers = showProviderFilter || hasSubaccountCategories(configuredCategoriesJson)

	if (includeSubusers) {
		widgetAttributes['data-include-subusers'] = 'true'
		widgetAttributes['data-show-quick-filters'] = 'true'
	}

	if (showLocationQuickFilter) {
		widgetAttributes['data-show-location-quick-filter'] = 'true'
	}

	if (includeSubusers && configuredCategoriesJson) {
		widgetAttributes['data-configured-categories'] = configuredCategoriesJson
	}

	const categoriesForEmbed = resolveCategoriesForEmbed(categories, configuredCategoriesJson)

	if (categoriesForEmbed?.trim()) {
		widgetAttributes['data-categories'] = categoriesForEmbed
	}

	if (showCategorySelectionFirst === false) {
		widgetAttributes['data-show-category-selection-first'] = 'false'
	}

	return widgetAttributes
}

export default function save({ attributes }) {
	const { customCss } = attributes

	const uuid = window.tebutoData?.uuid || ''
	const widgetUrl = 'https://tebuto.de/widget/booking.js'
	const widgetAttributes = buildBookingWidgetDataAttributes(attributes, uuid)

	return (
		<>
			<div id="tebuto-booking-widget" />
			<script src={widgetUrl} {...widgetAttributes} />
			{customCss && <style id="tebuto-custom-css">{customCss}</style>}
		</>
	)
}
