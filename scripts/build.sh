#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="tebuto-online-terminbuchung"
SOURCE_DIR="$ROOT_DIR/$PLUGIN_SLUG"
BUILD_DIR="$ROOT_DIR/.build"
ZIP_FILE="$ROOT_DIR/${PLUGIN_SLUG}.zip"

cd "$ROOT_DIR"

echo "Cleaning up previous builds..."
rm -rf "$BUILD_DIR"
rm -f "$ZIP_FILE"

echo "Creating build directory..."
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"

echo "Copying plugin files..."
cp "$SOURCE_DIR/tebuto-plugin.php" "$BUILD_DIR/$PLUGIN_SLUG/"
cp "$SOURCE_DIR/uninstall.php" "$BUILD_DIR/$PLUGIN_SLUG/" 2>/dev/null || true
cp -R "$SOURCE_DIR/admin" "$BUILD_DIR/$PLUGIN_SLUG/"
cp -R "$SOURCE_DIR/assets" "$BUILD_DIR/$PLUGIN_SLUG/"
cp -R "$SOURCE_DIR/css" "$BUILD_DIR/$PLUGIN_SLUG/"
cp -R "$SOURCE_DIR/includes" "$BUILD_DIR/$PLUGIN_SLUG/"
cp -R "$SOURCE_DIR/js" "$BUILD_DIR/$PLUGIN_SLUG/"
cp -R "$SOURCE_DIR/languages" "$BUILD_DIR/$PLUGIN_SLUG/" 2>/dev/null || true
cp "$SOURCE_DIR/readme.txt" "$BUILD_DIR/$PLUGIN_SLUG/"

echo "Building Gutenberg block..."
npm --prefix "$SOURCE_DIR/block" run build

echo "Copying block assets..."
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG/block"
cp "$SOURCE_DIR/block/block.php" "$BUILD_DIR/$PLUGIN_SLUG/block/"
cp -R "$SOURCE_DIR/block/build" "$BUILD_DIR/$PLUGIN_SLUG/block/"

echo "Creating the zip archive..."
(cd "$BUILD_DIR" && zip -r "$ZIP_FILE" .)

echo "Cleaning up build directory..."
rm -rf "$BUILD_DIR"

echo "Build completed: $ZIP_FILE"
