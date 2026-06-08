# AGENTS.md

WordPress plugin (**Tebuto - Online-Terminbuchung**) that connects a WordPress site to [Tebuto](https://tebuto.de) for appointment booking. PHP 7.4+ backend, React Gutenberg block (`@wordpress/scripts`), external Tebuto APIs (OAuth, REST, booking widget JS). GPLv2.

## Repository layout

```
tebuto-online-terminbuchung/   # Plugin source — edit here
├── tebuto-plugin.php          # Bootstrap, constants, hooks
├── admin/                     # wp-admin pages (dashboard, bookings, categories, shortcode, connection)
├── includes/                  # API client, OAuth, shortcode, AJAX, helpers
├── block/
│   ├── src/block/             # Gutenberg block source (edit.js, save.js, block.json)
│   └── build/                 # Compiled block assets — must be rebuilt after JS changes
├── assets/                    # Plugin icons/screenshots (shipped in ZIP)
├── assets-wporg/              # WordPress.org SVN assets only — not synced to local WP
├── css/, js/                  # Admin UI assets
└── readme.txt                 # WordPress.org readme (German, Stable tag + changelog)
scripts/                       # build.sh, bump-version.sh, dev-setup.sh, dev-sync.sh
wordpress/                     # Local Docker WordPress volume — gitignored, do not commit
```

Human docs: [README.md](README.md) (user-facing), [CONTRIBUTING.md](CONTRIBUTING.md) (dev + release).

## Architecture

| Layer | Role |
| --- | --- |
| **PHP plugin** | OAuth (Keycloak PKCE), token storage, admin UI, shortcode rendering, AJAX proxies to Tebuto API |
| **Gutenberg block** | Editor UI + `save()` output; embeds the same external booking widget as the shortcode |
| **Tebuto services** | `TEBUTO_AUTH_URL` (OAuth), `TEBUTO_API_URL` (therapists API), `TEBUTO_WIDGET_URL` (booking.js) |

**Data flow**

1. Admin connects via **Tebuto → Verbindung** (`tebuto-integration`) — full-page OAuth redirect (not in block editor iframe; Keycloak blocks embedded auth).
2. Tokens and settings live in **WordPress user meta** under prefix `tebuto_online_terminbuchung_` — use `tebuto_get_user_meta()` / `tebuto_update_user_meta()`.
3. Frontend widget resolves the connected admin via `tebuto_get_connected_user_id()` (visitors are not logged in).
4. Admin pages call `Tebuto_API` (token refresh, REST). Block editor and shortcode page use `wp_ajax_*` handlers in `includes/ajax-handlers.php` (nonce `tebuto_admin`).

**Widget embedding** (shortcode + block must stay aligned):

- Container: `<div id="tebuto-booking-widget">` (shortcode supports multiple instances: `tebuto-booking-widget-2`, …).
- Script: `<script src="…/booking.js" data-therapist-uuid="…" data-* …>`.
- Attribute mapping: shortcode uses `snake_case` (`primary_color`); block `block.json` uses `camelCase` (`primaryColor`). Defaults and theme presets should match across `includes/shortcode.php`, `admin/shortcode-page.php`, and `block/src/block/edit.js`.

**Configurable constants** (override in `wp-config.php` or `wordpress/wp-config.local.php` for local dev):

- `TEBUTO_API_URL`, `TEBUTO_AUTH_URL`, `TEBUTO_WIDGET_URL`, `TEBUTO_CLIENT_ID`, `TEBUTO_SSL_VERIFY`

## Commands

Prerequisites: Node.js **20+**, Docker (optional, for local WordPress).

```bash
npm install                  # Root + block deps (postinstall runs block npm ci)
npm run dev:setup            # First-time: Docker + build + sync plugin into wordpress/
npm run dev:build            # Build block + rsync plugin to local WordPress
npm run dev:sync             # Rsync PHP/assets only (no block rebuild)
npm run dev                  # Watch block + auto-sync on tebuto-online-terminbuchung/** changes
npm run build:block          # Compile Gutenberg block only
npm run build                # Production ZIP: tebuto-online-terminbuchung.zip
npm run lint                 # ESLint on block source
npm run lint:fix             # Format + lint block source (same as pre-commit hook)
npm run version:check        # Verify version fields are in sync
npm run version:bump 2.3.0   # Bump version in all tracked files (or patch/minor/major)
```

Local WordPress: http://localhost:8000 — plugin path `wordpress/wp-content/plugins/tebuto-online-terminbuchung/`.

CI (`.github/workflows/branch.yaml`): `npm ci` in `block/`, `lint:js`, `build`, `version:check`, `build.sh`.

Pre-commit (Husky + `lint-staged.config.cjs`): after root `npm install`, staged `block/src/**/*.{js,jsx,ts,tsx}` is formatted and linted before each commit.

## Coding conventions

### PHP

- WordPress Coding Standards; **tabs** for indentation (`.editorconfig`).
- Every file: `defined('ABSPATH') || exit;`
- Functions prefixed `tebuto_`; class `Tebuto_API` in `includes/class-tebuto-api.php`.
- Text domain: `tebuto-online-terminbuchung`. Admin copy is **German** — keep new user-facing strings in German and wrap with `__()` / `_e()`.
- Sanitize input (`sanitize_hex_color`, `sanitize_text_field`, …), escape output (`esc_attr`, `esc_html`, `esc_url`).
- Capabilities: admin features require `manage_options`; AJAX uses `check_ajax_referer('tebuto_admin', 'nonce')`.

### JavaScript (block)

- `@wordpress/scripts` (webpack, ESLint). Source only in `block/src/`; output in `block/build/`.
- WordPress packages: `@wordpress/i18n`, `@wordpress/block-editor`, `@wordpress/components`.
- After editing `block/src/**`, run `npm run build:block` (or `dev:build`) and commit updated `block/build/**` when preparing a release.

### YAML

- 2-space indent (`.editorconfig`).

## Do not edit

| Path | Reason |
| --- | --- |
| `wordpress/` | Gitignored local Docker volume; generated by `dev:setup` |
| `tebuto-online-terminbuchung/.svn/` | WordPress.org SVN metadata |
| `*.zip`, `.build/` | Build artifacts — regenerate with `npm run build` |
| `node_modules/` | Installed dependencies |
| `assets-wporg/` | Only for WordPress.org plugin assets deploy; excluded from dev sync |

Do not commit secrets. Do not change `TEBUTO_CLIENT_ID` without matching Keycloak client config.

## Versioning

Version must match in all four places (enforced by `scripts/bump-version.sh --check`):

1. `tebuto-online-terminbuchung/tebuto-plugin.php` — header `Version:` and `TEBUTO_VERSION`
2. `tebuto-online-terminbuchung/readme.txt` — `Stable tag:`
3. Root `package.json` — `version`

Release flow: bump → edit changelog stubs in `readme.txt` → merge `main` → GitHub Release tag `X.Y.Z` (no `v` prefix) triggers WordPress.org deploy. See [CONTRIBUTING.md](CONTRIBUTING.md).

## Common agent tasks

### PHP-only change (admin, API, shortcode)

1. Edit under `tebuto-online-terminbuchung/`.
2. `npm run dev:sync` (or `dev:build` if unsure).
3. Test in wp-admin at http://localhost:8000.

### Block / editor change

1. Edit `tebuto-online-terminbuchung/block/src/block/`.
2. `npm run build:block` or `npm run dev`.
3. Verify block inserter, inspector controls, and saved frontend markup.
4. If widget attributes changed, update `includes/shortcode.php` and `admin/shortcode-page.php` for parity.

### New widget setting

1. User meta key in `admin/save-settings.php` + defaults in `block/block.php` (`tebuto_enqueue_block_editor_assets`).
2. Shortcode attribute in `includes/shortcode.php` + generator in `admin/shortcode-page.php`.
3. Block attribute in `block/src/block/block.json`, `edit.js`, and `save.js`.
4. Map to `data-*` attributes on the booking script tag per [@tebuto/react-booking-widget](https://github.com/tebuto/react-booking-widget).

### New Tebuto API usage

1. Add method to `Tebuto_API` in `includes/class-tebuto-api.php`.
2. Expose via AJAX in `includes/ajax-handlers.php` if needed from admin/block editor.
3. Handle `is_wp_error()`, token refresh (class already retries refresh once per request).

## Pre-PR checklist

```bash
npm run lint:fix
npm run dev:build    # or npm run build for release-like verify
npm run version:check
```

- Focused diff; no unrelated refactors.
- No comments that only narrate what the code does.
- Built block assets included if `block/src/` changed and the change ships to users.

## Admin menu reference

| Slug | Page |
| --- | --- |
| `tebuto-main` | Dashboard |
| `tebuto-bookings` | Buchungen |
| `tebuto-categories` | Kategorien |
| `tebuto-shortcode` | Shortcode / widget settings |
| `tebuto-integration` | Verbindung (OAuth) |

Shortcode tag: `[tebuto_online_terminbuchung_widget]`. Block name: `tebuto/terminbuchung`.
