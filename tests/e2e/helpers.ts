import { readFileSync } from 'node:fs'
import { expect, test as base, type Locator, type Page } from '@playwright/test'

type Account = { username: string; password: string }
type WordPressBlock = { name: string; attributes: Record<string, unknown>; isValid: boolean }
type WordPressStore = {
	getBlocks(): WordPressBlock[]
	isEditedPostDirty(): boolean
	getEditedPostContent(): string
	getCurrentPost(): { content: string }
	getCurrentPostId(): number
}
declare global {
	interface Window {
		wp: {
			blocks: {
				getBlockType(name: string): object | undefined
				createBlock(name: string, attributes?: Record<string, unknown>): WordPressBlock
			}
			data: {
				select(store: string): WordPressStore
				dispatch(store: string): { resetBlocks(blocks: WordPressBlock[]): void; savePost(): Promise<void> }
			}
		}
	}
}
type LocalPreview = {
	baseURL?: string
	admin: Account
	disconnected: Account
	editor: Account
	pages: Record<string, { id: number; url: string; editURL: string }>
}

export function preview(): LocalPreview {
	try {
		return JSON.parse(readFileSync('.local-preview/credentials.json', 'utf8'))
	} catch {
		throw new Error('Local WordPress is not seeded. Run pnpm dev:preview first.')
	}
}

export const test = base.extend<{ localNetwork: void }>({
	localNetwork: [async ({ context }, use) => {
		const unexpected = new Set<string>()
		const pageErrors: string[] = []
		context.on('page', page => page.on('pageerror', error => pageErrors.push(error.message)))
		await context.route('**/*', async (route) => {
			const url = new URL(route.request().url())
			if (['localhost', '127.0.0.1', '[::1]'].includes(url.hostname)) {
				await route.continue()
			} else {
				unexpected.add(`${url.origin}${url.pathname}`)
				await route.abort('blockedbyclient')
			}
		})
		await use()
		expect([...unexpected], 'Preview must never request production assets or APIs').toEqual([])
		expect(pageErrors, 'Real WordPress and widget scripts must not throw uncaught browser errors').toEqual([])
	}, { auto: true }]
})

export async function login(page: Page, role: 'admin' | 'disconnected' | 'editor' = 'admin') {
	const account = preview()[role]
	await page.goto('/wp-login.php')
	// WordPress focuses and selects this field in a delayed callback after load.
	await expect(page.locator('#user_login')).toBeFocused()
	await page.locator('#user_login').fill(account.username)
	await page.locator('#user_pass').fill(account.password)
	await Promise.all([
		page.waitForURL(/\/wp-admin\//, { waitUntil: 'domcontentloaded' }),
		page.locator('#wp-submit').click()
	])
}

export async function settings(page: Page) {
	await page.goto('/wp-admin/admin.php?page=tebuto-shortcode')
	await expect(page.getByRole('tab', { name: 'Termine', exact: true })).toBeVisible()
}

export async function ajax(page: Page, action: string, fields: Record<string, string> = {}) {
	const nonce = await page.evaluate(() => (window as unknown as { tebutoData?: { nonce: string } }).tebutoData?.nonce || '')
	return page.request.post('/wp-admin/admin-ajax.php', { form: { action, nonce, ...fields } })
}

export async function saveSettings(page: Page) {
	await page.getByRole('button', { name: /Als Standard speichern/ }).click()
	await expect(page.getByText('Einstellungen wurden gespeichert.', { exact: true })).toBeVisible()
}

export async function assertNoOverflow(page: Page) {
	expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true)
}

export async function assertNaturalVerticalFlow(scope: Locator) {
	await expect.poll(() => scope.evaluate(root => {
		return [root, ...root.querySelectorAll('*')].flatMap(element => {
			if (!(element instanceof HTMLElement) || element.matches('input, textarea, select, [contenteditable="true"]')) return []
			const style = getComputedStyle(element)
			const rect = element.getBoundingClientRect()
			if (rect.width <= 1 || rect.height <= 1 || style.visibility === 'hidden' || style.display === 'none') return []
			const constrained = ['auto', 'scroll', 'hidden', 'clip'].includes(style.overflowY)
			if (!constrained || element.scrollHeight <= element.clientHeight + 2) return []
			return [{
				element: element.id ? `#${element.id}` : `${element.tagName.toLowerCase()}.${[...element.classList].join('.')}`,
				overflowY: style.overflowY,
				visibleHeight: element.clientHeight,
				contentHeight: element.scrollHeight
			}]
		})
	}), { message: 'Content must expand the WordPress page, without nested scrolling or vertically clipped panels' }).toEqual([])
}

export { expect }
