import { expect, preview, test } from './helpers'

for (const width of [1440, 390]) {
	test(`calendar slots, card spacing and keyboard focus have complete visible bounds at ${width}px`, async ({ page }) => {
		await page.setViewportSize({ width, height: 720 })
		await page.goto(preview().pages.blocks.url)
		const widget = page.locator('[id="tebuto-booking-widget"]')
		await widget.getByRole('button', { name: /Erstgespräch/ }).click()
		const slot = widget.getByTestId('booking-time-slot').first()
		await expect(slot).toBeVisible()
		await page.evaluate(() => document.fonts.ready.then(() => undefined))

		const calendarGrid = await widget.locator('.booking-calendar-days').evaluate(grid => {
			const style = getComputedStyle(grid)
			const columns = style.gridTemplateColumns.split(' ').length
			return {
				actualRows: style.gridTemplateRows.split(' ').length,
				expectedRows: Math.ceil(grid.children.length / columns)
			}
		})
		expect(calendarGrid.actualRows, 'Calendar must not reserve an empty sixth week').toBe(calendarGrid.expectedRows)

		const slotGeometry = await slot.evaluate(button => {
			const cell = button.parentElement
			const card = button.closest('.widget-container')
			if (!cell || !card) throw new Error('Expected a slot cell inside the rendered widget card')
			const bounds = button.getBoundingClientRect()
			const cellBounds = cell.getBoundingClientRect()
			const cardBounds = card.getBoundingClientRect()
			const lastSlot = [...card.querySelectorAll('[data-testid="booking-time-slot"]')].at(-1)
			if (!lastSlot) throw new Error('Expected a rendered last time slot')
			const cardStyle = getComputedStyle(card)
			const buttonStyle = getComputedStyle(button)
			return {
				width: bounds.width,
				height: bounds.height,
				cellWidthDifference: Math.abs(cellBounds.width - bounds.width),
				cellLeftDifference: Math.abs(cellBounds.left - bounds.left),
				bottomSpace: cardBounds.bottom - lastSlot.getBoundingClientRect().bottom,
				bottomPadding: Number.parseFloat(cardStyle.paddingBottom),
				cornerRadii: [cardStyle.borderTopLeftRadius, cardStyle.borderTopRightRadius, cardStyle.borderBottomLeftRadius, cardStyle.borderBottomRightRadius],
				alignItems: buttonStyle.alignItems,
				justifyContent: buttonStyle.justifyContent
			}
		})
		expect(slotGeometry.width).toBeGreaterThanOrEqual(72)
		expect(slotGeometry.height).toBeGreaterThanOrEqual(44)
		expect(slotGeometry.cellWidthDifference).toBeLessThanOrEqual(1)
		expect(slotGeometry.cellLeftDifference).toBeLessThanOrEqual(1)
		expect(slotGeometry.bottomSpace).toBeGreaterThanOrEqual(24)
		expect(slotGeometry.bottomPadding).toBeGreaterThanOrEqual(24)
		expect(slotGeometry.cornerRadii).toEqual(['16px', '16px', '16px', '16px'])
		expect(slotGeometry.alignItems).toBe('center')
		expect(slotGeometry.justifyContent).toBe('center')

		const filter = widget.getByTestId('booking-filter-trigger')
		// Tab from the preceding real control so :focus-visible reflects keyboard use.
		await widget.getByRole('button', { name: 'Ändern', exact: true }).press('Tab')
		await expect(filter).toBeFocused()
		const focusGeometry = await filter.evaluate(button => {
			const card = button.closest('.widget-container')
			if (!card) throw new Error('Expected the filter inside a rendered widget card')
			const style = getComputedStyle(button)
			const bounds = button.getBoundingClientRect()
			const cardBounds = card.getBoundingClientRect()
			const outlineWidth = Number.parseFloat(style.outlineWidth)
			const outlineOffset = Number.parseFloat(style.outlineOffset)
			const outside = Math.max(0, outlineWidth + outlineOffset)
			const frame = { left: bounds.left - outside, right: bounds.right + outside, top: bounds.top - outside, bottom: bounds.bottom + outside }
			const clippingAncestors: string[] = []
			for (let ancestor = button.parentElement; ancestor; ancestor = ancestor.parentElement) {
				const ancestorStyle = getComputedStyle(ancestor)
				const rect = ancestor.getBoundingClientRect()
				const clipsX = ['hidden', 'clip', 'auto', 'scroll'].includes(ancestorStyle.overflowX)
				const clipsY = ['hidden', 'clip', 'auto', 'scroll'].includes(ancestorStyle.overflowY)
				if ((clipsX && (frame.left < rect.left - 1 || frame.right > rect.right + 1)) || (clipsY && (frame.top < rect.top - 1 || frame.bottom > rect.bottom + 1))) {
					clippingAncestors.push(ancestor.id || ancestor.className)
				}
				if (ancestor === card) break
			}
			return {
				focusVisible: button.matches(':focus-visible'),
				height: bounds.height,
				radius: Number.parseFloat(style.borderTopLeftRadius),
				outlineWidth,
				outlineOffset,
				outlineStyle: style.outlineStyle,
				insideCard: frame.left >= cardBounds.left && frame.right <= cardBounds.right && frame.top >= cardBounds.top && frame.bottom <= cardBounds.bottom,
				clippingAncestors
			}
		})
		expect(focusGeometry.focusVisible).toBe(true)
		expect(focusGeometry.height).toBeGreaterThanOrEqual(44)
		expect(focusGeometry.radius).toBeGreaterThanOrEqual(8)
		expect(focusGeometry.outlineStyle).toBe('solid')
		expect(focusGeometry.outlineWidth).toBeGreaterThanOrEqual(2)
		expect(focusGeometry.outlineOffset).toBeLessThanOrEqual(-focusGeometry.outlineWidth)
		expect(focusGeometry.insideCard).toBe(true)
		expect(focusGeometry.clippingAncestors).toEqual([])
	})
}
