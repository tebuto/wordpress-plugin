# Contributing

Thank you for your interest in contributing to the Tebuto WordPress plugin!

## Getting Started

### Prerequisites

- [Node.js](https://nodejs.org/) 20 or higher
- [Docker](https://www.docker.com/) (optional, for local WordPress)
- A [Tebuto](https://tebuto.de) account for testing the booking flow

### Clone and Install

```bash
git clone https://github.com/tebuto/wordpress-plugin.git
cd wordpress-plugin
npm install
```

This installs dependencies for the Gutenberg block and development tooling.

### Local WordPress with Docker

#### First-Time Setup

```bash
npm run dev:setup
```

This single command:

- Starts WordPress at [http://localhost:8000](http://localhost:8000) and MariaDB via Docker Compose
- Builds the Gutenberg block
- Copies the plugin into `wordpress/wp-content/plugins/tebuto-online-terminbuchung/`
- Creates `wordpress/wp-config.local.php` with local Tebuto API URL overrides

Complete the WordPress installation in your browser, then activate the plugin under **Plugins → Installed Plugins**.

#### Development Commands

| Command | Description |
| --- | --- |
| `npm run dev:setup` | First-time setup (Docker + build + sync) |
| `npm run dev:build` | Build block and sync plugin to local WordPress |
| `npm run dev:sync` | Sync plugin files without rebuilding the block |
| `npm run dev` | Watch block source and auto-sync on changes |
| `npm run dev:up` | Start Docker containers |
| `npm run dev:down` | Stop Docker containers |
| `npm run dev:logs` | Tail WordPress container logs |

After the initial setup, use `npm run dev:build` whenever you want to push changes to the local WordPress instance. For active development, `npm run dev` watches the block and syncs automatically; use `npm run dev:sync` for PHP-only changes.

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

Connect via **Tebuto → Verbindung** in wp-admin (full-page OAuth redirect). The block editor iframe cannot complete login because Keycloak blocks embedded auth with `frame-ancestors`.

## Development Workflow

### Sync to Local WordPress

```bash
# Build block and copy plugin into the Docker WordPress instance
npm run dev:build

# Sync PHP/assets only (skip block rebuild)
npm run dev:sync

# Watch block source and auto-sync on changes
npm run dev
```

### Build the Gutenberg Block (without syncing)

```bash
npm run build:block
npm run start   # watch mode
```

### Build a Distributable ZIP

```bash
npm run build
```

This runs `scripts/build.sh`, which compiles the block and packages the plugin into `tebuto-online-terminbuchung.zip`.

### Lint JavaScript

```bash
npm run lint
npm run lint:fix   # format + lint (same checks as the pre-commit hook)
```

A **pre-commit hook** (Husky + lint-staged) runs automatically after `npm install` and formats/lints staged Gutenberg block sources under `tebuto-online-terminbuchung/block/src/` before each commit — matching the CI **Lint JavaScript** step.

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

- **PHP**: Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/). The `.editorconfig` uses tabs for PHP.
- **JavaScript**: ESLint via `@wordpress/scripts` (`npm run lint`).
- **YAML**: 2-space indentation per `.editorconfig`.

## Pull Requests

1. Fork the repository and create a feature branch from `main`
2. Make your changes with clear, focused commits
3. Run `npm run lint:fix` and `npm run dev:build` before submitting (or rely on the pre-commit hook for block JS)
4. Open a pull request with a description of what changed and why
5. Link any related GitHub issues

## Releasing to WordPress.org

Releases are automated via GitHub Actions when a GitHub Release is published. The workflow builds production-only artifacts (no `block/src/`, `package-lock.json`, or `node_modules`) and deploys them to the WordPress.org SVN repository.

### Prerequisites

Add these repository secrets under **Settings → Secrets and variables → Actions**:

| Secret | Description |
| --- | --- |
| `SVN_USERNAME` | WordPress.org username |
| `SVN_PASSWORD` | WordPress.org password ([application password](https://make.wordpress.org/core/2020/11/05/wordpress-org-accounts-now-require-2fa/) recommended) |

### Pre-release (PR to `main`)

1. Bump the version everywhere in one step:

   ```bash
   npm run version:bump 2.2.0
   # or: npm run version:bump minor
   ```

   This updates `tebuto-plugin.php`, `readme.txt` (`Stable tag` + changelog/upgrade notice stubs), and `package.json`. Edit the changelog stubs in `readme.txt` before merging.

2. Verify sync and build locally:

   ```bash
   npm run version:check
   npm run build
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

- [@tebuto/react-booking-widget](https://github.com/tebuto/react-booking-widget) — React component for embedding the Tebuto booking widget outside of WordPress

## Questions?

Open a [GitHub issue](https://github.com/tebuto/wordpress-plugin/issues) or reach out at hello@tebuto.de.
