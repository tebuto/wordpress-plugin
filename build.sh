#!/bin/bash

# Define variables
PLUGIN_SLUG="tebuto-online-terminbuchung"
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
cp tebuto-online-terminbuchung/tebuto-plugin.php $BUILD_DIR
cp -R tebuto-online-terminbuchung/admin $BUILD_DIR
cp -R tebuto-online-terminbuchung/assets $BUILD_DIR
cp -R tebuto-online-terminbuchung/css $BUILD_DIR
cp -R tebuto-online-terminbuchung/includes $BUILD_DIR
cp -R tebuto-online-terminbuchung/js $BUILD_DIR
cp -R tebuto-online-terminbuchung/languages $BUILD_DIR
cp -R tebuto-online-terminbuchung/readme.txt $BUILD_DIR

# Build Gutenberg block
echo "Building Gutenberg block..."
cd tebuto-online-terminbuchung/block
npm install
npm run build
cd ../..

# Copy the build output to the build directory
rsync -av --exclude='node_modules' --exclude='*.map' --exclude='.git' tebuto-online-terminbuchung/block $BUILD_DIR


# Create the zip archive
echo "Creating the zip archive..."
cd $BUILD_DIR
zip -r ../$ZIP_FILE .
cd ..

# Clean up
echo "Cleaning up build directory..."
rm -rf $BUILD_DIR

echo "Build completed: $ZIP_FILE"
