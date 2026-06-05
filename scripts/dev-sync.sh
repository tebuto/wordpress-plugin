#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="tebuto-online-terminbuchung"
SOURCE_DIR="$ROOT_DIR/$PLUGIN_SLUG"
TARGET_DIR="$ROOT_DIR/wordpress/wp-content/plugins/$PLUGIN_SLUG"
SKIP_BUILD=false

for arg in "$@"; do
	case $arg in
		--skip-build)
			SKIP_BUILD=true
			;;
	esac
done

if [ ! -d "$SOURCE_DIR" ]; then
	echo "Error: Plugin source not found at $SOURCE_DIR" >&2
	exit 1
fi

if [ "$SKIP_BUILD" = false ]; then
	echo "Building Gutenberg block..."
	npm --prefix "$SOURCE_DIR/block" run build
fi

mkdir -p "$TARGET_DIR"

echo "Syncing plugin to local WordPress..."
rsync -a --delete --delete-excluded \
	--exclude='block/node_modules/' \
	--exclude='block/src/' \
	--exclude='block/package.json' \
	--exclude='block/package-lock.json' \
	--exclude='.svn/' \
	--exclude='assets-wporg/' \
	"$SOURCE_DIR/" "$TARGET_DIR/"

echo "Done — plugin available at wordpress/wp-content/plugins/$PLUGIN_SLUG"
