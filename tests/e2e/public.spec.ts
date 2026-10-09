import { assertNoOverflow, expect, login, preview, test } from './helpers'

for (const width of [1440, 390]) {
	for (const kind of ['shortcode', 'seminars', 'blocks']) {
		test(`${kind} mounts actual widgets independently at ${width}px`, async ({ page }) => {
			await page.setViewportSize({ width, height: 1000 })
			await page.goto(preview().pages[kind].url)
			const scripts = page.locator('script[data-therapist-uuid]')
			expect(await scripts.count()).toBeGreaterThan(0)
			if (kind === 'blocks') await expect(scripts).toHaveCount(4)
			const containerIDs: string[] = []
			for (const script of await scripts.all()) {
				await expect(script).toHaveAttribute('src', /^http:\/\/localhost:8001\/widget\/(booking|seminars)\.js/)
				await expect(script).toHaveAttribute('data-therapist-uuid', '11111111-1111-4111-8111-111111111111')
				const id = await script.getAttribute('data-container-id')
				if (!id) throw new Error('Every saved block and shortcode must identify its own container')
				containerIDs.push(id)
				const container = page.locator(`[id="${id}"]`)
				await expect(container).toBeVisible()
				await expect(container.locator('.widget-container')).toBeVisible()
				const type = (await script.getAttribute('src'))?.includes('seminars') ? 'seminars' : 'booking'
				// Omitted legacy colors preserve each actual widget's own default palette.
				const expectedPrimary = (await script.getAttribute('data-primary-color')) || (type === 'booking' ? '#007f78' : '#00B4A9')
				await expect.poll(() => container.locator('.widget-container').evaluate(element => getComputedStyle(element).getPropertyValue('--widget-primary').trim().toLowerCase())).toBe(expectedPrimary.toLowerCase())
				await expect(container).not.toContainText('Widget konnte nicht geladen werden')
				await expect(container).toContainText(type === 'seminars' ? 'Achtsamkeit (Testseminar)' : /Erstgespräch|Beratung/)
			}
			expect(new Set(containerIDs).size).toBe(containerIDs.length)
			if (kind === 'blocks') {
				const customCSS = await page.locator('.widget-container').evaluateAll(elements => elements.map(element => getComputedStyle(element).getPropertyValue('--e2e-custom-css').trim()))
				expect(customCSS).toEqual(['scoped', '', '', ''])
				expect(await page.locator('body').evaluate(element => getComputedStyle(element).getPropertyValue('--e2e-host-css'))).toBe('')
			}
			await assertNoOverflow(page)
		})
	}
}

test('Gutenberg loads registered Tebuto blocks without invalid-block warnings', async ({ page }) => {
	await login(page)
	await page.goto(preview().pages.blocks.editURL)
	await expect.poll(() => page.evaluate(() => {
		const wp = (window as unknown as { wp?: { data: { select: (store: string) => { getBlocks: () => Array<{ name: string }> } } } }).wp
		return wp?.data.select('core/block-editor').getBlocks().filter(block => block.name.startsWith('tebuto/')).length || 0
	})).toBeGreaterThan(0)
	await expect(page.getByText(/Dieser Block enthält unerwarteten oder ungültigen Inhalt|This block contains unexpected or invalid content/)).toHaveCount(0)
	const invalid = await page.evaluate(() => {
		const wp = (window as unknown as { wp: { data: { select: (store: string) => { getBlocks: () => Array<{ name: string; isValid: boolean }> } } } }).wp
		return wp.data.select('core/block-editor').getBlocks().filter(block => block.name.startsWith('tebuto/') && block.isValid === false)
	})
	expect(invalid).toEqual([])
})

test('booking visitor selects a category and time, reaches personal details and returns without booking', async ({ page }) => {
	await page.goto(preview().pages.blocks.url)
	const widget = page.locator('[id="tebuto-booking-widget"]')
	await widget.getByRole('button', { name: /Erstgespräch/ }).click()
	await widget.getByRole('button', { name: /^\d{2}:\d{2} Uhr/ }).first().click()
	await expect(widget.locator('[name="firstName"]')).toBeVisible()
	await expect(widget.locator('[name="lastName"]')).toBeVisible()
	await widget.getByRole('button', { name: 'Zurück', exact: true }).click()
	await expect(widget.getByRole('button', { name: /^\d{2}:\d{2} Uhr/ }).first()).toBeVisible()
})

test('seminar visitor opens real details and returns to the seminar list', async ({ page }) => {
	await page.goto(preview().pages.seminars.url)
	await page.getByTestId('seminars-widget-item-achtsamkeit-test').click()
	await expect(page.getByRole('button', { name: 'Zurück', exact: true })).toBeVisible()
	await expect(page.locator('.widget-container')).toContainText('Achtsamkeit (Testseminar)')
	await page.getByRole('button', { name: 'Zurück', exact: true }).click()
	await expect(page.getByTestId('seminars-widget-item-achtsamkeit-test')).toBeVisible()
})
