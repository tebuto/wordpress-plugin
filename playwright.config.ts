import { defineConfig } from '@playwright/test'

export default defineConfig({
	testDir: './tests/e2e',
	fullyParallel: false,
	// These tests share the isolated local WordPress database and service fixture.
	workers: 1,
	forbidOnly: Boolean(process.env.CI),
	retries: 0,
	timeout: 60_000,
	expect: { timeout: 15_000 },
	reporter: [['list'], ['html', { open: 'never' }]],
	projects: [
		{ name: 'seed-editor', testMatch: /editor\.setup\.ts/ },
		{ name: 'chromium', testMatch: /.*\.spec\.ts/, dependencies: ['seed-editor'] }
	],
	use: {
		actionTimeout: 15_000,
		navigationTimeout: 30_000,
		baseURL: 'http://localhost:8000',
		browserName: 'chromium',
		viewport: { width: 1440, height: 1000 },
		// Login credentials and authenticated response bodies must not enter traces.
		trace: 'off',
		screenshot: 'only-on-failure',
		serviceWorkers: 'block'
	}
})
