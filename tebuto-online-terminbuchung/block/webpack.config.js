const defaultConfig = require('@wordpress/scripts/config/webpack.config')
const path = require('node:path')

const defaultEntry = typeof defaultConfig.entry === 'function' ? defaultConfig.entry() : defaultConfig.entry

module.exports = {
	...defaultConfig,
	entry: {
		...defaultEntry,
		'widget-settings/index': path.resolve(__dirname, 'src/widget-settings/index.js')
	}
}
