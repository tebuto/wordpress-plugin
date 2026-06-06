<div align="center">
  <img alt="Tebuto" src="https://tebuto.de/assets/logo.svg" width="400" />
</div>

<p align="center">A <a href="https://wordpress.org" target="_blank">WordPress</a> plugin for integrating <a href="https://tebuto.de" target="_blank">Tebuto</a> appointment booking into your website.</p>

<div align="center">
  <a href="https://wordpress.org/plugins/tebuto-online-terminbuchung/"><img alt="WordPress Plugin" src="https://img.shields.io/wordpress/plugin/v/tebuto-online-terminbuchung?label=wordpress.org"></a>
  <a href="https://github.com/tebuto/wordpress-plugin/blob/main/LICENSE"><img alt="GPLv2 License" src="https://img.shields.io/github/license/tebuto/wordpress-plugin"></a>
  <a href="https://github.com/tebuto/wordpress-plugin/actions/workflows/branch.yaml"><img alt="CI Status" src="https://img.shields.io/github/actions/workflow/status/tebuto/wordpress-plugin/.github%2Fworkflows%2Fbranch.yaml?label=CI&logo=GitHub"></a>
</div>

<hr />

## Table of Contents <!-- omit in toc -->

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Shortcode](#shortcode)
  - [Attributes Reference](#attributes-reference)
- [Gutenberg Block](#gutenberg-block)
- [Admin Features](#admin-features)
- [Local Development](#local-development)
  - [First-Time Setup](#first-time-setup)
  - [Development Commands](#development-commands)
  - [Local Tebuto Stack](#local-tebuto-stack)
- [Building from Source](#building-from-source)
- [Related Projects](#related-projects)
- [Contributing](#contributing)
- [License](#license)

## Features

- **OAuth Integration** — Connect your Tebuto account with one click
- **Drop-in Widget** — Embed the Tebuto booking widget via shortcode or Gutenberg block
- **Multiple Widgets** — Use several booking widgets with different settings on the same page
- **Theming** — Customize colors, fonts, borders, and CSS to match your brand
- **Admin Dashboard** — View upcoming appointments, manage bookings, and edit categories from WordPress
- **Category Filters** — Restrict widgets to specific appointment categories
- **Multi-User Support** — Provider filter and quick filters for team accounts
- **Page Builder Compatible** — Works with Elementor, Divi, WPBakery, and other builders via shortcode

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- A [Tebuto](https://tebuto.de) account

## Installation

### From WordPress.org (Recommended)

1. Go to **Plugins → Add New** in your WordPress admin
2. Search for "Tebuto"
3. Click **Install Now**, then **Activate**

### From Source

```bash
git clone https://github.com/tebuto/wordpress-plugin.git
cd wordpress-plugin
npm install
npm run build
```

Upload the `tebuto-online-terminbuchung/` directory (or the generated `tebuto-online-terminbuchung.zip`) to `/wp-content/plugins/` and activate the plugin.

## Quick Start

1. Activate the plugin
2. Go to **Tebuto → Settings** in the WordPress admin
3. Click **Connect with Tebuto** and sign in
4. Add the shortcode or Gutenberg block to any page:

```
[tebuto_online_terminbuchung_widget]
```

Your public appointments will appear automatically on the page.

## Shortcode

The primary way to embed the booking widget:

```
[tebuto_online_terminbuchung_widget]
```

Override default settings per instance:

```
[tebuto_online_terminbuchung_widget primary_color="#3b82f6" categories="1,2,3" border="true"]
```

### Attributes Reference

| Attribute | Type | Default | Description |
| --- | --- | --- | --- |
| `primary_color` | hex color | `#00B4A9` | Primary brand color for buttons and highlights |
| `background_color` | hex color | `#ffffff` | Widget background color |
| `text_primary` | hex color | `#374151` | Primary text color |
| `text_secondary` | hex color | `#6b7280` | Secondary/muted text color |
| `border_color` | hex color | `#E9E9E9` | Border color |
| `border` | `true` / `false` | `false` | Show border around the widget |
| `inherit_font` | `true` / `false` | `false` | Use the parent page font |
| `categories` | comma-separated IDs | all | Filter to specific category IDs |
| `show_quick_filters` | `true` / `false` | `false` | Show quick filter buttons for time slots |
| `show_provider_filter` | `true` / `false` | `false` | Show provider selector (multi-user accounts) |
| `custom_css` | CSS string | — | Custom CSS scoped to this widget instance |

Defaults can be configured globally under **Tebuto → Shortcode**. The live shortcode generator on that page updates automatically as you change settings.

## Gutenberg Block

Search for **Tebuto** in the block inserter to add the booking widget. All shortcode settings are available in the block sidebar, including theme presets, category filters, and live preview.

The block uses the same underlying [Tebuto booking widget](https://github.com/tebuto/react-booking-widget) as the shortcode.

## Admin Features

Once connected, the **Tebuto** menu provides:

| Page | Description |
| --- | --- |
| **Dashboard** | Overview of upcoming appointments |
| **Bookings** | View, confirm, and cancel appointments |
| **Categories** | Manage appointment categories |
| **Shortcode** | Configure widget appearance with live preview |
| **Settings** | Connect or disconnect your Tebuto account |

## Local Development

The repository includes a Docker Compose setup that runs WordPress and MariaDB locally. Plugin changes are synced into `wordpress/wp-content/plugins/` automatically via npm scripts.

**Requirements:** [Docker](https://www.docker.com/), [Node.js](https://nodejs.org/) 20+

### First-Time Setup

```bash
npm install
npm run dev:setup
```

This will:

1. Install block dependencies
2. Start WordPress (`http://localhost:8000`) and MariaDB via Docker
3. Build the Gutenberg block and copy the plugin into the local WordPress instance
4. Create `wordpress/wp-config.local.php` with Tebuto API URL overrides for local development

Complete the WordPress installation in your browser, then activate **Tebuto - Online-Terminbuchung** under **Plugins**.

### Development Commands

| Command | Description |
| --- | --- |
| `npm run dev:setup` | First-time setup: Docker + build + sync plugin |
| `npm run dev:build` | Build block and sync plugin to local WordPress |
| `npm run dev:sync` | Sync plugin files without rebuilding the block |
| `npm run dev` | Watch block source and auto-sync on file changes |
| `npm run dev:up` | Start Docker containers |
| `npm run dev:down` | Stop Docker containers |
| `npm run dev:logs` | Tail WordPress container logs |

Typical workflow after setup:

```bash
# PHP or block changes — build and push to WordPress
npm run dev:build

# Active block development — watch + auto-sync
npm run dev

# PHP-only changes — sync without rebuilding
npm run dev:sync
```

The plugin is installed at `wordpress/wp-content/plugins/tebuto-online-terminbuchung/`. The `wordpress/` directory is gitignored and persists between container restarts.

### Local Tebuto API

When developing against a self-hosted Tebuto instance, edit `wordpress/wp-config.local.php`:

```php
define('TEBUTO_API_URL', 'https://therapists.api.tebuto.local');
define('TEBUTO_AUTH_URL', 'https://auth.tebuto.local');
define('TEBUTO_WIDGET_URL', 'https://tebuto.local/widget/booking.js');
define('TEBUTO_SSL_VERIFY', false);
```

Docker maps the required hostnames to your machine via `host-gateway` (see `docker-compose.yaml`).

For Keycloak OAuth client setup when using a self-hosted Tebuto instance, see [CONTRIBUTING.md](CONTRIBUTING.md).

See [CONTRIBUTING.md](CONTRIBUTING.md) for the full development guide.

## Building from Source

```bash
npm install          # Install block dependencies
npm run build:block  # Compile the Gutenberg block
npm run build        # Create tebuto-online-terminbuchung.zip
```

## Related Projects

| Project | Description |
| --- | --- |
| [@tebuto/react-booking-widget](https://github.com/tebuto/react-booking-widget) | React component for embedding Tebuto booking outside WordPress |
| [Tebuto](https://tebuto.de) | Online appointment booking platform for therapists and coaches |

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for development setup, coding standards, and the manual WordPress.org release process.

## License

This repository is licensed under [GPLv2 or later](LICENSE).
