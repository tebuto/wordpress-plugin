// These are serialization defaults, not runtime colors. Existing saved blocks omit them.
export const SERIALIZED_COLOR_DEFAULTS = {
	primaryColor: '#00B4A9',
	backgroundColor: '#ffffff',
	textPrimary: '#374151',
	textSecondary: '#6b7280',
	borderColor: '#E9E9E9'
}

export function getPreviewThemeDataset(attributes, surface = 'inspector') {
	const dataset = {}
	for (const [key, defaultValue] of Object.entries(SERIALIZED_COLOR_DEFAULTS)) {
		const value = attributes[key]
		if (!value) continue
		// PHP shortcodes compare hex colors case-insensitively; Gutenberg save() does not.
		const isDefault = surface === 'admin' ? value.toLowerCase() === defaultValue.toLowerCase() : value === defaultValue
		if (!isDefault) dataset[key] = value
	}
	return dataset
}
