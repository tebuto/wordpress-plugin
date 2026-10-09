#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
if [ -f wordpress/wp-config.local.php ] && ! grep -q 'TEBUTO_LOCAL_PREVIEW' wordpress/wp-config.local.php; then
  echo 'Existing local Tebuto configuration found; not replacing it. Use a separate checkout for the isolated preview.' >&2
  exit 1
fi
if [ ! -s ../webapp/public/widget/booking.js ] || [ ! -s ../webapp/public/widget/seminars.js ]; then
  echo 'Build actual widget bundles first: (cd ../webapp && node build-widget.mjs local)' >&2
  exit 1
fi
mkdir -p .local-preview/assets .local-preview/service-state
if [ ! -s .local-preview/assets/altcha.min.js ]; then
  curl -fLsS https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js -o .local-preview/assets/altcha.min.js
fi
docker compose --profile preview up -d wordpress tebuto-mock
for i in $(seq 1 60); do
  if [ -f wordpress/wp-config.php ] && curl -fsS http://localhost:8000/wp-admin/install.php > /dev/null; then break; fi
  if [ "$i" -eq 60 ]; then echo 'WordPress startup timed out.' >&2; exit 1; fi
  sleep 2
done
cat > wordpress/wp-config.local.php <<'PHP'
<?php
// TEBUTO_LOCAL_PREVIEW: synthetic services, actual WordPress/plugin/widget code.
define('WP_AUTO_UPDATE_CORE', false);
define('AUTOMATIC_UPDATER_DISABLED', true);
define('TEBUTO_API_URL', 'http://tebuto-mock:8001');
define('TEBUTO_AUTH_URL', 'http://tebuto-mock:8001');
define('TEBUTO_WIDGET_URL', 'http://localhost:8001/widget/booking.js');
define('TEBUTO_SSL_VERIFY', true);
PHP
node scripts/dev-config.mjs
./scripts/dev-sync.sh
if ! ./scripts/local-wp.sh core is-installed >/dev/null 2>&1; then
  node scripts/local-credentials.mjs
  ./scripts/local-wp.sh eval-file /scripts/local-install.php --skip-wordpress
elif [ ! -f .local-preview/credentials.json ]; then
  echo 'Existing WordPress installation found; preserving users. Preview seeding requires a fresh checkout.' >&2
  exit 1
fi
./scripts/local-wp.sh plugin activate tebuto-online-terminbuchung
./scripts/local-wp.sh eval-file /scripts/local-seed.php
./scripts/local-wp.sh eval 'if (TEBUTO_API_URL !== "http://tebuto-mock:8001") { WP_CLI::error("Local constants did not load before the plugin"); } WP_CLI::success("Local API constants verified");'
printf '\nLocal preview: http://localhost:8000\nAdmin: http://localhost:8000/wp-admin/admin.php?page=tebuto-main\nCredentials: .local-preview/credentials.json (private; do not commit)\n'
