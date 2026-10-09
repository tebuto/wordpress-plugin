import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { getPreviewThemeDataset, SERIALIZED_COLOR_DEFAULTS } from './previewTheme.js'

for (const variant of ['block', 'seminare']) {
	test(`${variant}: legacy block defaults let the runtime choose its theme`, () => {
		const metadata = JSON.parse(readFileSync(new URL(`../${variant}/block.json`, import.meta.url), 'utf8'))
		const attributes = Object.fromEntries(
			Object.entries(metadata.attributes).map(([key, value]) => [key, value.default])
		)
		for (const [key, value] of Object.entries(SERIALIZED_COLOR_DEFAULTS)) {
			assert.equal(attributes[key], value, `${key} must retain existing block serialization semantics`)
		}
		assert.deepEqual(getPreviewThemeDataset(attributes), {})
		assert.deepEqual(getPreviewThemeDataset(attributes, 'admin'), {})
	})
}

test('custom colors survive unchanged on both surfaces without mutating saved attributes', () => {
	const colors = {
		primaryColor: '#Cc3355',
		backgroundColor: '#202020',
		textPrimary: '#FaFaFa',
		textSecondary: '#bBbBbB',
		borderColor: '#787878'
	}
	const attributes = Object.freeze({ ...colors, categories: '12,34', border: true, customCss: '.example{}' })
	assert.deepEqual(getPreviewThemeDataset(attributes), colors)
	assert.deepEqual(getPreviewThemeDataset(attributes, 'admin'), colors)
	assert.deepEqual(attributes, { ...colors, categories: '12,34', border: true, customCss: '.example{}' })
})

test('case variants match the different existing Gutenberg and PHP omission rules', () => {
	const attributes = { ...SERIALIZED_COLOR_DEFAULTS, primaryColor: '#00b4a9', borderColor: '#e9e9e9' }
	assert.deepEqual(getPreviewThemeDataset(attributes), { primaryColor: '#00b4a9', borderColor: '#e9e9e9' })
	assert.deepEqual(getPreviewThemeDataset(attributes, 'admin'), {})
})

test('partial themes retain only supplied overrides', () => {
	assert.deepEqual(getPreviewThemeDataset({ backgroundColor: '', textPrimary: undefined, primaryColor: '#123456' }), {
		primaryColor: '#123456'
	})
})
