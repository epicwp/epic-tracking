#!/usr/bin/env bash
#
# Build a distributable ZIP for Epic Tracking.
# Usage: bash .github/scripts/build-zip.sh <version>
#

set -euo pipefail

VERSION="${1:?Usage: build-zip.sh <version>}"
PLUGIN_SLUG="epic-tracking"
BUILD_DIR="build/${PLUGIN_SLUG}"
ZIP_FILE="build/${PLUGIN_SLUG}-${VERSION}.zip"

rm -rf build
mkdir -p "${BUILD_DIR}"

rsync -a \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='node_modules' \
  --exclude='package.json' \
  --exclude='package-lock.json' \
  --exclude='.releaserc.json' \
  --exclude='CHANGELOG.md' \
  --exclude='docs' \
  --exclude='build' \
  --exclude='*.zip' \
  --exclude='dev' \
  --exclude='.gitignore' \
  --exclude='languages/.gitkeep' \
  --exclude='README.md' \
  ./ "${BUILD_DIR}/"

cd build
zip -rq "../${ZIP_FILE}" "${PLUGIN_SLUG}"
cd ..

echo "Created ${ZIP_FILE}"
