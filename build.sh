#!/bin/bash

# Define variables
PLUGIN_SLUG="tebuto"
BUILD_DIR=".build"
ZIP_FILE="${PLUGIN_SLUG}.zip"

# Clean up previous builds
echo "Cleaning up previous builds..."
rm -rf $BUILD_DIR
rm -f $ZIP_FILE

# Create a build directory
echo "Creating build directory..."
mkdir $BUILD_DIR

# Copy necessary plugin files
echo "Copying plugin files..."
cp tebuto-plugin/tebuto-plugin.php $BUILD_DIR
cp -R tebuto-plugin/admin $BUILD_DIR
cp -R tebuto-plugin/assets $BUILD_DIR
cp -R tebuto-plugin/css $BUILD_DIR
cp -R tebuto-plugin/includes $BUILD_DIR
cp -R tebuto-plugin/js $BUILD_DIR
cp -R tebuto-plugin/readme.txt $BUILD_DIR

# Build Gutenberg block
echo "Building Gutenberg block..."
cd tebuto-plugin/block
npm install
npm run build
cd ../..

# Copy the build output to the build directory
rsync -av --exclude='node_modules' --exclude='*.map' --exclude='.git' tebuto-plugin/block $BUILD_DIR


# Create the zip archive
echo "Creating the zip archive..."
cd $BUILD_DIR
zip -r ../$ZIP_FILE .
cd ..

# Clean up
echo "Cleaning up build directory..."
rm -rf $BUILD_DIR

echo "Build completed: $ZIP_FILE"
