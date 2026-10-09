import fs from 'node:fs'
const path = new URL('../wordpress/wp-config.php', import.meta.url)
let config = fs.readFileSync(path, 'utf8')
// Move the legacy auto-appended override ahead of WordPress/plugin bootstrap.
config = config.replace(/\n\/\/ Local Tebuto development overrides \(auto-added by dev:setup\)\nif \( file_exists\( __DIR__ \. '\/wp-config.local.php' \) \) \{\n\s*require_once __DIR__ \. '\/wp-config.local.php';\n\}\n?/g, '\n')
const include = `// Local Tebuto development overrides (auto-added by dev:setup)\nif ( file_exists( __DIR__ . '/wp-config.local.php' ) ) {\n\trequire_once __DIR__ . '/wp-config.local.php';\n}\n\n`
const bootstrap = /require_once\s+ABSPATH\s*\.\s*['"]wp-settings.php['"]\s*;/
if (!bootstrap.test(config)) throw new Error('WordPress bootstrap not found; config was not modified.')
if (config.includes('wp-config.local.php')) throw new Error('Custom local include found; move it before wp-settings.php manually.')
fs.writeFileSync(path, config.replace(bootstrap, match => include + match))
console.log('Local overrides load before wp-settings.php.')
