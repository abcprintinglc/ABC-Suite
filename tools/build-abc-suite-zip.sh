#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
OUT_DIR="$ROOT_DIR/dist"
VERSION="$(sed -n "s/^ \* Version: \(.*\)$/\1/p" "$ROOT_DIR/plugin.php" | head -n1)"
VERSION="${VERSION:-dev}"
OUT_ZIP="$OUT_DIR/abc-suite-v${VERSION}.zip"

mkdir -p "$OUT_DIR"

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

PLUGIN_DIR="$TMP_DIR/abc-suite"
mkdir -p "$PLUGIN_DIR"

rsync -a \
  --exclude '.git' \
  --exclude 'dist' \
  --exclude '*.zip' \
  --exclude '*.pdf' \
  --exclude '*.xlsx' \
  --exclude '*.csv' \
  --exclude 'tools/build-abc-suite-zip.sh' \
  "$ROOT_DIR/" "$PLUGIN_DIR/"

(
  cd "$TMP_DIR"
  zip -rq "$OUT_ZIP" "abc-suite"
)

echo "Built: $OUT_ZIP"
