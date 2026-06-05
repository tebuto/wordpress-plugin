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

When running against a local Tebuto stack, configure a Keycloak client in the `tebuto-therapists` realm:

1. Create a client named `wordpress-plugin`
2. Set **Valid redirect URIs** to `*`
3. Add the `offline_access` scope
4. Enable PKCE: **Advanced Settings → Proof Key for Code Exchange Code Challenge Method → S256**
5. Set the login theme to `tebuto`

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
```

## Project Structure

```
tebuto-online-terminbuchung/   # Plugin source (development trunk)
├── admin/                     # WordPress admin pages and settings
├── block/                     # Gutenberg block (React + @wordpress/scripts)
│   ├── src/                   # Block source
│   └── build/                 # Compiled block assets
├── includes/                  # Core PHP: API, OAuth, shortcode
├── tebuto-plugin.php          # Plugin bootstrap
└── readme.txt                 # WordPress.org plugin readme
scripts/                       # Build and development scripts (build, dev-sync, dev-setup)
wordpress/                     # Local WordPress instance (Docker volume, gitignored)
tags/                          # Historical release snapshots (mirrors SVN tags)
```

## Coding Standards

- **PHP**: Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/). The `.editorconfig` uses tabs for PHP.
- **JavaScript**: ESLint via `@wordpress/scripts` (`npm run lint`).
- **YAML**: 2-space indentation per `.editorconfig`.

## Pull Requests

1. Fork the repository and create a feature branch from `main`
2. Make your changes with clear, focused commits
3. Run `npm run lint` and `npm run dev:build` before submitting
4. Open a pull request with a description of what changed and why
5. Link any related GitHub issues

## Releasing to WordPress.org

Releases are published manually via the WordPress.org SVN repository — there is no automated release pipeline.

### SVN Layout

- **Trunk**: `tebuto-online-terminbuchung/` (plugin source)
- **Tags**: `tags/{version}/` (immutable release snapshots)

### Release Steps

1. Update the version in `tebuto-plugin.php`, `readme.txt` (Stable tag + changelog), and root `package.json`
2. Build and verify: `npm run build`
3. Copy the plugin to SVN trunk and create a new tag:

   ```sh
   svn copy tebuto-online-terminbuchung tags/X.Y.Z
   svn --username=tebuto commit -m "Release version X.Y.Z"
   ```

4. Update the `tags/` directory in this repository to mirror the SVN tag (optional, for history)

> **Note:** The WordPress.org distribution is licensed under [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html), as required by the WordPress Plugin Directory. The source code in this GitHub repository is licensed under the [MIT License](LICENSE).

## Related Projects

- [@tebuto/react-booking-widget](https://github.com/tebuto/react-booking-widget) — React component for embedding the Tebuto booking widget outside of WordPress

## Questions?

Open a [GitHub issue](https://github.com/tebuto/wordpress-plugin/issues) or reach out at hello@tebuto.de.
