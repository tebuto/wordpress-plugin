import { ajax, expect, login, saveSettings, settings, test } from './helpers'

test.beforeEach(async ({ request }) => {
	expect((await request.post('http://localhost:8001/__control', { data: { reset: true } })).ok()).toBe(true)
})

test.afterEach(async ({ request }) => {
	await request.post('http://localhost:8001/__control', { data: { reset: true } })
})

test('connected dashboard renders real WordPress navigation and API data', async ({ page }) => {
	await login(page)
	await page.goto('/wp-admin/admin.php?page=tebuto-main')
	await expect(page.getByRole('heading', { name: 'Dashboard', exact: true })).toBeVisible()
	await expect(page.getByRole('button', { name: 'Verbindung trennen', exact: true })).toBeVisible()
	await expect(page.locator('.tebuto-stats-grid')).toBeVisible()
	await expect(page.locator('.tebuto-dashboard-grid')).toContainText('Erstgespräch (Test)')
})

test('disconnected administrator gets a connection action instead of connected data', async ({ page }) => {
	await login(page, 'disconnected')
	await page.goto('/wp-admin/admin.php?page=tebuto-main')
	await expect(page.getByRole('link', { name: /mit tebuto verbinden/i })).toBeVisible()
	await expect(page.locator('.tebuto-stats-grid')).toHaveCount(0)
})

test('custom color survives form submission and reload; saving default does not alter its palette', async ({ page }) => {
	await login(page)
	await settings(page)
	await page.getByRole('button', { name: 'Eigene Farben festlegen', exact: true }).click()
	const primary = page.locator('.tebuto-color-row').filter({ hasText: 'Primärfarbe' })
	await primary.getByRole('button').click()
	const hex = page.getByRole('textbox', { name: /hex/i })
	await hex.fill('123456')
	await hex.press('Tab')
	await page.keyboard.press('Escape')
	await expect(page.locator('[name="primary_color"]')).toHaveValue('#123456')
	await saveSettings(page)
	await page.reload()
	await expect(page.locator('[name="primary_color"]')).toHaveValue('#123456')
	await page.getByRole('button', { name: 'Farbvorlagen', exact: true }).click()
	await page.getByRole('button', { name: 'Standard', exact: true }).click()
	await saveSettings(page)
	await page.reload()
	for (const [name, value] of Object.entries({ primary_color: '#00B4A9', background_color: '#ffffff', text_primary: '#374151', text_secondary: '#6b7280', border_color: '#E9E9E9' })) {
		await expect(page.locator(`[name="${name}"]`)).toHaveValue(value)
	}
})

test('category and seminar selectors enforce visibility and return genuine AJAX data', async ({ page }) => {
	await login(page)
	await settings(page)
	const categories = await ajax(page, 'tebuto_get_categories')
	expect(categories.ok()).toBe(true)
	const result = await categories.json()
	expect(result.success).toBe(true)
	expect(result.data.length).toBeGreaterThan(1)
	// Hidden offerings may be listed for administrators but cannot be published through the widget.
	await expect(page.getByRole('checkbox', { name: /Privater Folgetermin/ })).toBeDisabled()
	const first = page.getByRole('checkbox', { name: /Erstgespräch/ })
	const second = page.getByRole('checkbox', { name: /Beratung/ })
	await first.check()
	await second.check()
	await second.uncheck()
	await expect(first).toBeChecked()
	await expect(first).toBeDisabled()
	await expect(second).toBeEnabled()
	await second.check()
	await expect(first).toBeEnabled()
	await page.getByRole('tab', { name: 'Seminare', exact: true }).click()
	const seminars = await ajax(page, 'tebuto_get_seminars')
	const seminarResult = await seminars.json()
	expect(seminarResult.success).toBe(true)
	expect(seminarResult.data).toEqual(expect.arrayContaining([
		expect.objectContaining({ slug: 'achtsamkeit-test', publicPageEnabled: true }),
		expect.objectContaining({ slug: 'privates-testseminar', publicPageEnabled: false })
	]))
	await expect(page.locator('.tebuto-category-list')).not.toBeEmpty()
	await expect(page.getByRole('checkbox', { name: /Privates Testseminar/ })).toBeDisabled()
	const publicSeminar = page.getByRole('checkbox', { name: 'Achtsamkeit (Testseminar)', exact: true })
	await expect(publicSeminar).toBeEnabled()
	await publicSeminar.check()
	await expect(publicSeminar).toBeChecked()
	await expect(page.locator('[name="seminars"]')).toHaveValue('achtsamkeit-test')
	await saveSettings(page)
	await page.reload()
	await page.getByRole('tab', { name: 'Seminare', exact: true }).click()
	await expect(publicSeminar).toBeChecked()
	await publicSeminar.uncheck()
	await saveSettings(page)
})

test('AJAX rejects missing and forged nonces, and unauthenticated requests', async ({ page, playwright }) => {
	await login(page)
	await settings(page)
	for (const action of ['tebuto_get_categories', 'tebuto_get_seminars', 'tebuto_booking_action']) {
		for (const nonce of ['', 'forged-local-nonce']) {
			const response = await ajax(page, action, { nonce })
			expect(response.status()).toBe(403)
		}
	}
	const anonymous = await playwright.request.newContext({ baseURL: 'http://localhost:8000' })
	try {
		const response = await anonymous.post('/wp-admin/admin-ajax.php', { form: { action: 'tebuto_get_categories' } })
		expect(response.ok()).toBe(false)
	} finally {
		await anonymous.dispose()
	}
})

for (const resource of ['categories', 'seminars'] as const) {
	test(`${resource} upstream failure is shown and reload retries successfully`, async ({ page, request }) => {
		await login(page)
		await request.post('http://localhost:8001/__control', { data: { [resource]: 'error' } })
		await settings(page)
		if (resource === 'seminars') await page.getByRole('tab', { name: 'Seminare', exact: true }).click()
		await expect(page.locator('.tebuto-widget-settings-controls .components-notice.is-error')).toBeVisible()
		await request.post('http://localhost:8001/__control', { data: { [resource]: 'success' } })
		await page.reload()
		if (resource === 'seminars') await page.getByRole('tab', { name: 'Seminare', exact: true }).click()
		await expect(page.locator('.tebuto-category-list input').first()).toBeVisible()
		await expect(page.locator('.tebuto-widget-settings-controls .components-notice.is-error')).toHaveCount(0)
	})
}

test('editor with a valid WordPress nonce cannot access administrator AJAX data', async ({ page }) => {
	await login(page, 'editor')
	await page.goto('/wp-admin/post-new.php?post_type=page')
	await expect.poll(() => page.evaluate(() => Boolean((window as unknown as { tebutoData?: { nonce: string } }).tebutoData?.nonce))).toBe(true)
	expect(await page.evaluate(() => (window as unknown as { tebutoData: { connectUrl: string } }).tebutoData.connectUrl)).toBe('')
	for (const action of ['tebuto_get_categories', 'tebuto_get_seminar_occurrences', 'tebuto_booking_action']) {
		const response = await ajax(page, action)
		expect(response.status()).toBe(403)
		expect((await response.json()).success).toBe(false)
	}
	const callback = await page.request.get('/wp-admin/admin.php?page=tebuto-integration&code=local-test&state=forged-local-state')
	expect(callback.status()).toBe(403)
	expect(await callback.text()).toMatch(/Keine Berechtigung|not allowed to access this page/)
})

test('failed booking action preserves its message and restores controls for a successful retry', async ({ page, request }) => {
	await login(page)
	await request.post('http://localhost:8001/__control', { data: { bookingActions: 'error' } })
	await page.goto('/wp-admin/admin.php?page=tebuto-bookings')
	const confirm = page.locator('.tebuto-confirm-booking[data-booking-id="501"]')
	await confirm.click()
	await page.locator('#tebuto-confirm-ok').click()
	const error = page.getByText('Lokaler Testfehler: Buchung konnte nicht geändert werden.', { exact: true })
	await expect(error).toBeVisible()
	await expect(confirm).toBeEnabled()
	// The old implementation reloaded after 1s and auto-dismissed notices after 3s.
	await page.waitForTimeout(3500)
	await expect(error).toBeVisible()
	await expect(confirm).toBeFocused()
	await request.post('http://localhost:8001/__control', { data: { bookingActions: 'success' } })
	await confirm.click()
	await page.locator('#tebuto-confirm-ok').click()
	await expect(page.locator('.tebuto-cancel-booking[data-booking-id="501"]')).toBeVisible()
})
