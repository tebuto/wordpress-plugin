#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "==> Installing Node dependencies..."
npm install

echo "==> Starting Docker containers..."
docker compose up -d

echo "==> Waiting for WordPress to become ready..."
for i in $(seq 1 30); do
	if curl -sf http://localhost:8000 > /dev/null 2>&1; then
		break
	fi
	if [ "$i" -eq 30 ]; then
		echo "Warning: WordPress did not respond on port 8000 yet. It may still be starting." >&2
	fi
	sleep 2
done

mkdir -p wordpress/wp-content/plugins

echo "==> Building and syncing plugin to local WordPress..."
"$ROOT_DIR/scripts/dev-sync.sh"

WP_CONFIG="$ROOT_DIR/wordpress/wp-config.php"
WP_CONFIG_LOCAL="$ROOT_DIR/wordpress/wp-config.local.php"
WP_CONFIG_EXAMPLE="$ROOT_DIR/scripts/wp-config.local.php.example"

if [ ! -f "$WP_CONFIG_LOCAL" ] && [ -f "$WP_CONFIG_EXAMPLE" ]; then
	cp "$WP_CONFIG_EXAMPLE" "$WP_CONFIG_LOCAL"
	echo "==> Created wordpress/wp-config.local.php from example."
fi

if [ -f "$WP_CONFIG" ] && ! grep -q 'wp-config.local.php' "$WP_CONFIG"; then
	cat >> "$WP_CONFIG" <<'PHP'

// Local Tebuto development overrides (auto-added by dev:setup)
if ( file_exists( __DIR__ . '/wp-config.local.php' ) ) {
	require_once __DIR__ . '/wp-config.local.php';
}
PHP
	echo "==> Appended wp-config.local.php include to wordpress/wp-config.php"
fi

cat <<EOF

Local development environment is ready.

  WordPress:  http://localhost:8000
  Admin:      http://localhost:8000/wp-admin

Next steps:
  1. Complete the WordPress installation in your browser (if first run)
  2. Activate the "Tebuto - Online-Terminbuchung" plugin under Plugins
  3. Review wordpress/wp-config.local.php for Tebuto API URL overrides

Development commands:
  npm run dev:build   Build block and sync plugin to WordPress
  npm run dev:sync    Sync plugin files without rebuilding the block
  npm run dev         Watch block source and auto-sync on changes
  npm run dev:down    Stop Docker containers

EOF
