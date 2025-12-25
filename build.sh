#!/bin/bash

# Define variables
PLUGIN_SLUG="tebuto-online-terminbuchung"
BUILD_DIR=".build"
ZIP_FILE="${PLUGIN_SLUG}.zip"

# Clean up previous builds
echo "Cleaning up previous builds..."
rm -rf $BUILD_DIR
rm -f $ZIP_FILE

# Create a build directory with plugin slug as subfolder
echo "Creating build directory..."
mkdir -p $BUILD_DIR/$PLUGIN_SLUG

# Copy necessary plugin files
echo "Copying plugin files..."
cp tebuto-online-terminbuchung/tebuto-plugin.php $BUILD_DIR/$PLUGIN_SLUG
cp tebuto-online-terminbuchung/uninstall.php $BUILD_DIR/$PLUGIN_SLUG 2>/dev/null || true
cp -R tebuto-online-terminbuchung/admin $BUILD_DIR/$PLUGIN_SLUG
cp -R tebuto-online-terminbuchung/assets $BUILD_DIR/$PLUGIN_SLUG
cp -R tebuto-online-terminbuchung/css $BUILD_DIR/$PLUGIN_SLUG
cp -R tebuto-online-terminbuchung/includes $BUILD_DIR/$PLUGIN_SLUG
cp -R tebuto-online-terminbuchung/js $BUILD_DIR/$PLUGIN_SLUG
cp -R tebuto-online-terminbuchung/languages $BUILD_DIR/$PLUGIN_SLUG 2>/dev/null || true
cp tebuto-online-terminbuchung/readme.txt $BUILD_DIR/$PLUGIN_SLUG

# Build Gutenberg block
echo "Building Gutenberg block..."
cd tebuto-online-terminbuchung/block
npm install
npm run build
cd ../..

# Copy only the necessary block files (exclude node_modules and source files)
mkdir -p $BUILD_DIR/$PLUGIN_SLUG/block
cp tebuto-online-terminbuchung/block/block.php $BUILD_DIR/$PLUGIN_SLUG/block/
cp -R tebuto-online-terminbuchung/block/build $BUILD_DIR/$PLUGIN_SLUG/block/


# Create the zip archive
echo "Creating the zip archive..."
cd $BUILD_DIR
zip -r ../$ZIP_FILE .
cd ..

# Clean up
echo "Cleaning up build directory..."
rm -rf $BUILD_DIR

echo "Build completed: $ZIP_FILE"
