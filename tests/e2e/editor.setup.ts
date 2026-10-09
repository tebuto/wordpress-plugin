import { expect, login, preview, test } from './helpers'

test('create and save real Gutenberg booking and seminar blocks for the public regression suite', async ({ page }) => {
	await login(page)
	await page.goto(preview().pages.blocks.editURL)
	await expect.poll(() => page.evaluate(() => Boolean(window.wp?.blocks.getBlockType('tebuto/seminare')))).toBe(true)
	// Exercise registered block serialization and WordPress persistence, not a handwritten HTML stand-in.
	await page.evaluate(async () => {
		const wp = window.wp
		wp.data.dispatch('core/block-editor').resetBlocks([
			wp.blocks.createBlock('tebuto/terminbuchung', { categories: '101,103', customCss: 'body { --e2e-host-css: leaked; } #tebuto-booking-widget .widget-container { --e2e-custom-css: scoped; }' }),
			wp.blocks.createBlock('tebuto/seminare'),
			wp.blocks.createBlock('core/shortcode', { text: '[tebuto_online_terminbuchung_widget primary_color="#123456"]' }),
			wp.blocks.createBlock('core/shortcode', { text: '[tebuto_seminare_widget]' })
		])
	})
	await expect.poll(() => page.evaluate(() => window.wp.data.select('core/block-editor').getBlocks().length)).toBe(4)
	await page.evaluate(async () => { await window.wp.data.dispatch('core/editor').savePost() })
	await page.reload()
	await expect.poll(() => page.evaluate(() => window.wp?.data.select('core/block-editor').getBlocks().length || 0)).toBe(4)
	const saved = await page.evaluate(() => window.wp.data.select('core/block-editor').getBlocks())
	expect(saved[0].attributes.customCss).toContain('--e2e-custom-css: scoped')
	expect(saved[0].attributes.categories).toBe('101,103')
	expect(saved.every(block => block.isValid !== false)).toBe(true)
})
