import fs from 'node:fs'
import crypto from 'node:crypto'
fs.mkdirSync(new URL('../.local-preview/', import.meta.url), {recursive: true, mode: 0o700})
const file = new URL('../.local-preview/credentials.json', import.meta.url)
if (!fs.existsSync(file)) {
 const credentials = {baseURL: 'http://localhost:8000'}
 for (const [role, username] of Object.entries({admin:'tebuto-local',disconnected:'tebuto-disconnected',subscriber:'tebuto-subscriber',editor:'tebuto-editor'})) {
   credentials[role] = {username, password: crypto.randomBytes(24).toString('base64url')}
 }
 fs.writeFileSync(file, JSON.stringify(credentials, null, 2), {mode: 0o600})
}
