import assert from 'node:assert/strict'
import test from 'node:test'
import { getTebutoData } from './theme.js'

test('WordPress localized feature flags retain their boolean meaning', () => {
	const original = globalThis.window
	try {
		for (const value of [true, 1, '1', 'true', false, 0, '0', '', 'false', undefined]) {
			globalThis.window = {
				tebutoData: { seminarsFeatureEnabled: value, hasManagedUsers: value, isManagingUser: value, uuid: 'retained' }
			}
			const expected = [true, 1, '1', 'true'].includes(value)
			const data = getTebutoData()
			assert.equal(data.seminarsFeatureEnabled, expected)
			assert.equal(data.hasManagedUsers, expected)
			assert.equal(data.isManagingUser, expected)
			assert.equal(data.uuid, 'retained')
		}
	} finally {
		if (original === undefined) delete globalThis.window
		else globalThis.window = original
	}
})
