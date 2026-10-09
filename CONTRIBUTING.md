# Contributing

Thank you for your interest in contributing to the Tebuto WordPress plugin!

## Getting Started

### Prerequisites

- [Node.js](https://nodejs.org/) 22.13 or higher
- [pnpm](https://pnpm.io/) 11 or higher
- [PHP](https://www.php.net/) 7.4+ and [Composer](https://getcomposer.org/) (for PHPCS)
- [Docker](https://www.docker.com/) (optional, for local WordPress)
- A [Tebuto](https://tebuto.de) account for testing the booking flow

### Clone and Install

```bash
git clone https://github.com/artus-engineering/tebuto-wordpress-plugin.git
cd tebuto-wordpress-plugin
pnpm install
composer install
```

This installs Node workspace dependencies (including the Gutenberg block) and PHP lint tooling.

### Local WordPress with Docker

#### First-Time Setup

```bash
pnpm dev:setup
```

This single command:

- Starts WordPress at [http://localhost:8000](http://localhost:8000) and MariaDB via Docker Compose
- Builds the Gutenberg block
- Copies the plugin into `wordpress/wp-content/plugins/tebuto-online-terminbuchung/`
- Creates `wordpress/wp-config.local.php` with local Tebuto API URL overrides

Complete the WordPress installation in your browser, then activate the plugin under **Plugins → Installed Plugins**.

#### Isolated preview and browser tests

From the Tebuto monorepo checkout, build the actual widgets once:

```bash
(cd ../webapp && node build-widget.mjs local)
./scripts/dev-preview.sh
```

The Compose images are pinned to tested multi-architecture image digests:
WordPress 6.9.1 with PHP 8.3.30, WP-CLI 2.12.0, and MariaDB 10.11. Docker chooses
the native ARM64 or AMD64 image; no emulation override is required.
The preview disables automatic core updates. Existing `wordpress/` files are
persistent and may differ from the pinned image. Check the actual core with
`./scripts/local-wp.sh core version` before comparing browser results. If an
existing installation requires a database update, back up its database before
running `./scripts/local-wp.sh core update-db`.

The preview runs actual WordPress, MariaDB, the PHP plugin and Gutenberg on
`http://localhost:8000`. A separate local service on `http://localhost:8001`
supplies synthetic therapist/category/seminar data and serves the real compiled
booking and seminars widgets. Only service origins in the bundles are replaced;
widget rendering code is unchanged. OAuth, email delivery, payments, and final
booking completion are not implemented by the adapter and are not verified here.
Unknown API endpoints fail with HTTP 501 instead of simulating success.

The setup creates a connected administrator, a disconnected administrator, an
editor, a subscriber, and sample pages. Generated credentials and page URLs are in
`.local-preview/credentials.json` (ignored, mode 0600, outside the web document
root). Read that file locally for login; never paste it into logs or screenshots.
An existing custom Tebuto configuration or WordPress installation without preview
credentials is preserved; use a separate checkout for an isolated preview.

```bash
./scripts/local-wp.sh core version
./scripts/local-wp.sh plugin status tebuto-online-terminbuchung
./scripts/local-wp.sh eval-file /scripts/local-seed.php
curl -fsS http://localhost:8001/health
curl -fsS -H 'Content-Type: application/json' -d '{"reset":true}' http://localhost:8001/__control
```

The seed operation creates missing sample pages and accounts; it does not replace
existing page content or reset widget settings. Tests reset their own settings.
For API error tests POST `{"categories":"error"}`, `{"seminars":"error"}`, or
`{"bookingActions":"error"}` to
`/__control`; POST `{"reset":true}` restores normal fixtures. These endpoints are
available only on loopback and must never be deployed. Confirm/reject/cancel actions
change synthetic fixture state only.

`docker compose down` stops the preview while preserving local WordPress files,
`.local-preview/mysql-data`, and `.local-preview/service-state`. Database and
adapter state use host directories so a full Docker Desktop VM disk cannot block
session or fixture writes. Start it again with
`docker compose --profile preview up -d wordpress tebuto-mock`.
Do not delete those directories unless you intend to discard local data. When
migrating from the earlier named database volume, keep that volume unchanged as
a rollback copy; stop WordPress and MariaDB before copying a database datadir.

Run the browser suite after setup:

```bash
pnpm test:e2e:install
pnpm test:e2e
```

The suite requires the actual bundles in `../webapp/public/widget`. The standalone
plugin repository CI does not check out that sibling project, so it cannot run this
full browser suite as currently configured. A CI job must first obtain/build those
bundles from the matching Tebuto revision; do not substitute dummy widgets and
report that as the same integration coverage.

Local configuration is included **before** `wp-settings.php`, so plugin constants
actually take effect. `dev:setup` also relocates the old auto-appended include.

#### Development Commands

| Command | Description |
| --- | --- |
| `pnpm dev:setup` | First-time setup (Docker + build + sync) |
| `pnpm dev:build` | Build block and sync plugin to local WordPress |
| `pnpm dev:sync` | Sync plugin files without rebuilding the block |
| `pnpm dev` | Watch block source and auto-sync on changes |
| `pnpm dev:up` | Start Docker containers |
| `pnpm dev:down` | Stop Docker containers |
| `pnpm dev:logs` | Tail WordPress container logs |

After the initial setup, use `pnpm dev:build` whenever you want to push changes to the local WordPress instance. For active development, `pnpm dev` watches the block and syncs automatically; use `pnpm dev:sync` for PHP-only changes.

The sync script (`scripts/dev-sync.sh`) rsyncs from `tebuto-online-terminbuchung/` to the Docker WordPress plugins directory, excluding `block/node_modules`, `block/src`, and `.svn`.

#### Local Tebuto API Overrides

Local overrides live in `wordpress/wp-config.local.php` (created from `scripts/wp-config.local.php.example`):

```php
define('TEBUTO_API_URL', 'https://therapists.api.tebuto.local');
define('TEBUTO_AUTH_URL', 'https://auth.tebuto.local');
define('TEBUTO_WIDGET_URL', 'https://tebuto.local/widget/booking.js');
define('TEBUTO_SSL_VERIFY', false);
```

The `docker-compose.yaml` maps the required hostnames to your machine via `host-gateway`.

### Keycloak Client (Local Auth)

When developing against a self-hosted Tebuto instance (for example the `*.tebuto.local` URLs in `wp-config.local.php`), create an OAuth client in the Keycloak realm `tebuto-therapists`:

1. Client id: `wordpress-plugin` (must match `TEBUTO_CLIENT_ID` in `tebuto-plugin.php`)
2. Client type: **public**; enable standard authorization code flow
3. **Valid redirect URIs** — at least `http://localhost:8000/*` (Docker WordPress default port); add other origins if WordPress runs on a different host or port
4. **Web origins** — matching WordPress admin origin (e.g. `http://localhost:8000`)
5. Client scopes — plugin requests `openid offline_access`; add optional scope `offline_access`
6. Advanced: PKCE code challenge method **S256**
7. Login theme: **tebuto** (when that theme is installed on your Keycloak instance)

Connect via the connect CTA on the **Tebuto Dashboard** (or Gutenberg block notice) — full-page OAuth redirect. The block editor iframe cannot complete login because Keycloak blocks embedded auth with `frame-ancestors`. Disconnect from the Dashboard header (**Verbindung trennen**).

## Development Workflow

### Sync to Local WordPress

```bash
# Build block and copy plugin into the Docker WordPress instance
pnpm dev:build

# Sync PHP/assets only (skip block rebuild)
pnpm dev:sync

# Watch block source and auto-sync on changes
pnpm dev
```

### Build the Gutenberg Block (without syncing)

```bash
pnpm build:block
pnpm start   # watch mode
```

### Build a Distributable ZIP

```bash
pnpm build
```

This runs `scripts/build.sh`, which compiles the block and packages the plugin into `tebuto-online-terminbuchung.zip`.

### Lint

```bash
pnpm lint
pnpm lint:js      # Biome only
pnpm lint:php     # PHPCS / WordPress Coding Standards only
pnpm lint:fix   # format JS + autofix PHP + lint entire codebase (also the pre-commit hook)
```

A **pre-commit hook** (Husky) runs `pnpm lint:fix` so the full tree stays Biome/PHPCS-clean on every commit — matching the CI **Lint** step.

## Project Structure

```
tebuto-online-terminbuchung/   # Plugin source (development trunk)
├── admin/                     # WordPress admin pages and settings
├── assets-wporg/              # WordPress.org banners, icons, screenshots (deployed to SVN assets/)
├── block/                     # Gutenberg block (React + @wordpress/scripts)
│   ├── src/                   # Block source
│   └── build/                 # Compiled block assets
├── includes/                  # Core PHP: API, OAuth, shortcode
├── tebuto-plugin.php          # Plugin bootstrap
└── readme.txt                 # WordPress.org plugin readme
scripts/                       # Build and development scripts (build, dev-sync, dev-setup)
wordpress/                     # Local WordPress instance (Docker volume, gitignored)
```

## Coding Standards

- **PHP**: [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) via PHPCS (`pnpm lint:php`). The `.editorconfig` uses tabs for PHP.
- **JavaScript**: Biome (`pnpm lint:js` / `pnpm format`). Block bundling still uses `@wordpress/scripts`.
- **YAML**: 2-space indentation per `.editorconfig`.

## Pull Requests

1. Fork the repository and create a feature branch from `main`
2. Make your changes with clear, focused commits
3. Run `pnpm lint:fix` and `pnpm dev:build` before submitting (or rely on the pre-commit hook)
4. Open a pull request with a description of what changed and why
5. Link any related GitHub issues

## Releasing to WordPress.org

Releases are automated via GitHub Actions when a GitHub Release is published. The workflow builds production-only artifacts (no `block/src/`, `pnpm-lock.yaml`, or `node_modules`) and deploys them to the WordPress.org SVN repository.

### Prerequisites

Add these repository secrets under **Settings → Secrets and variables → Actions**:

| Secret | Description |
| --- | --- |
| `SVN_USERNAME` | WordPress.org username |
| `SVN_PASSWORD` | WordPress.org password ([application password](https://make.wordpress.org/core/2020/11/05/wordpress-org-accounts-now-require-2fa/) recommended) |

### Pre-release (PR to `main`)

1. Bump the version everywhere in one step:

   ```bash
   pnpm version:bump 2.2.0
   # or: pnpm version:bump minor
   ```

   This updates `tebuto-plugin.php`, `readme.txt` (`Stable tag` + changelog/upgrade notice stubs), and `package.json`. Edit the changelog stubs in `readme.txt` before merging.

2. Verify sync and build locally:

   ```bash
   pnpm version:check
   pnpm build
   ```

3. Merge to `main`

### Release

1. Go to **GitHub → Releases → Draft a new release**
2. Choose tag `X.Y.Z` (must match the version in the plugin files; no `v` prefix)
3. Publish the release — the [Release to WordPress.org](.github/workflows/release.yaml) workflow will:
   - Build the plugin via `scripts/build.sh`
   - Deploy to SVN `trunk/` and create `tags/X.Y.Z/`
   - Update SVN `assets/` from `assets-wporg/`
   - Attach `tebuto-online-terminbuchung.zip` to the GitHub Release

Pre-releases are skipped automatically.

### Testing the release workflow

Before the first automated release, run a dry-run from the Actions tab:

1. Go to **Actions → Release to WordPress.org → Run workflow**
2. Leave **dry_run** enabled (default) and run on `main`
3. Confirm the build and version check steps pass (no SVN commit is made)

To test a live deploy without creating a GitHub Release, run the workflow manually with **dry_run** disabled. This requires the SVN secrets to be configured.

### What gets deployed

The build script (`scripts/build.sh`) packages only production files. Development artifacts are excluded — see `tebuto-online-terminbuchung/.distignore` for the full list.

Existing WordPress.org SVN tags are never modified; only new version tags are created.

> **Note:** This repository is licensed under [GPLv2 or later](LICENSE), as required by the WordPress Plugin Directory.

## Related Projects

- [@tebuto/react-booking-widget](https://github.com/artus-engineering/tebuto-react-booking-widget) — React component for embedding the Tebuto booking widget outside of WordPress

## Questions?

Open a [GitHub issue](https://github.com/artus-engineering/tebuto-wordpress-plugin/issues) or reach out at hello@tebuto.de.
