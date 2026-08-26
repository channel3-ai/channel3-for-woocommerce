#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SVN_DIR="${WORDPRESS_ORG_SVN_DIR:-$ROOT/../channel3-for-woocommerce-svn}"
VERSION="$(grep -m1 'Stable tag:' "$ROOT/readme.txt" | awk '{print $3}')"
HEADER_VERSION="$(grep -m1 ' \* Version:' "$ROOT/channel3-for-woocommerce.php" | awk '{print $3}')"
CONST_VERSION="$(grep -m1 "define( 'CHANNEL3_VERSION'," "$ROOT/channel3-for-woocommerce.php" | sed -E "s/.*'([0-9]+\.[0-9]+\.[0-9]+)'.*/\1/")"
if [[ -z "$VERSION" ]]; then
  echo "Could not read plugin version from readme.txt" >&2
  exit 1
fi
if [[ "$VERSION" != "$HEADER_VERSION" || "$VERSION" != "$CONST_VERSION" ]]; then
  echo "Version mismatch: readme.txt=$VERSION plugin header=$HEADER_VERSION CHANNEL3_VERSION=$CONST_VERSION" >&2
  exit 1
fi
STAGING_DIR="$(mktemp -d)"

cleanup() {
  rm -rf "$STAGING_DIR"
}
trap cleanup EXIT

cd "$ROOT"

echo "Building release artifacts for v${VERSION}..."
if command -v pnpm >/dev/null 2>&1; then
  pnpm install --frozen-lockfile
  pnpm run build
  pnpm run plugin-zip
else
  npm install
  npm run build
  npm run plugin-zip
fi

unzip -q "$ROOT/channel3-for-woocommerce.zip" -d "$STAGING_DIR"
RELEASE_SRC="$STAGING_DIR/channel3-for-woocommerce"

if [[ ! -d "$RELEASE_SRC" ]]; then
  echo "Expected release folder at $RELEASE_SRC" >&2
  exit 1
fi

if [[ ! -d "$SVN_DIR/.svn" ]]; then
  echo "Checking out WordPress.org SVN to $SVN_DIR..."
  svn checkout "https://plugins.svn.wordpress.org/channel3-for-woocommerce" "$SVN_DIR"
else
  echo "Updating WordPress.org SVN checkout..."
  svn update "$SVN_DIR"
fi

echo "Syncing trunk..."
rsync -av --delete \
  --exclude '.svn' \
  --exclude 'package.json' \
  --exclude 'src/' \
  --exclude 'README.md' \
  "$RELEASE_SRC/" "$SVN_DIR/trunk/"

cd "$SVN_DIR"
svn add --force trunk/* 2>/dev/null || true

if svn info "tags/$VERSION" >/dev/null 2>&1; then
  echo "Updating existing tag tags/$VERSION..."
  rsync -av --delete \
    --exclude '.svn' \
    --exclude 'package.json' \
    --exclude 'src/' \
    --exclude 'README.md' \
    "$RELEASE_SRC/" "$SVN_DIR/tags/$VERSION/"
  svn add --force "tags/$VERSION"/* 2>/dev/null || true
else
  echo "Creating tag tags/$VERSION..."
  svn copy trunk "tags/$VERSION"
fi

echo
svn status
echo
echo "Ready to publish v${VERSION}."
if [[ "${WORDPRESS_ORG_COMMIT:-}" == "1" ]]; then
  svn commit -m "Release ${VERSION}."
  echo "Published v${VERSION} to WordPress.org."
else
  echo "Review the status above, then commit:"
  echo "  cd $SVN_DIR"
  echo "  svn commit -m \"Release ${VERSION}.\""
  echo
  echo "Or rerun with WORDPRESS_ORG_COMMIT=1 to commit automatically."
fi
