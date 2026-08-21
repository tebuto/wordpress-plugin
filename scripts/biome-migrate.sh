#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BIOME="$ROOT_DIR/node_modules/.bin/biome"

if [ ! -x "$BIOME" ]; then
	echo "Error: Biome is not installed. Run pnpm install." >&2
	exit 1
fi

found=0
while IFS= read -r config; do
	found=1
	echo "Migrating $config"
	"$BIOME" migrate --write --config-path="$config"
done < <(
	find "$ROOT_DIR" \
		\( -name biome.json -o -name biome.jsonc \) \
		-not -path '*/node_modules/*' \
		-not -path '*/vendor/*' \
		-not -path '*/wordpress/*' \
		-not -path '*/.svn/*' \
		| sort
)

if [ "$found" -eq 0 ]; then
	echo "Error: No biome.json or biome.jsonc files found." >&2
	exit 1
fi
