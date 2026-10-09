#!/usr/bin/env bash
# Builds dist/minemirth-<version>.zip with a top-level minemirth/ folder,
# ready for Appearance › Themes › Add New › Upload Theme.
set -euo pipefail
root="$(cd "$(dirname "$0")/.." && pwd)"
version="$(sed -n 's/^Version: *//p' "$root/minemirth/style.css" | tr -d '\r')"
mkdir -p "$root/dist"
out="$root/dist/minemirth-$version.zip"
rm -f "$out"
(cd "$root" && zip -qr "$out" minemirth -x '*.DS_Store' -x '*/node_modules/*')
echo "$out"
