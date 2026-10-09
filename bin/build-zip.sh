#!/usr/bin/env bash
# Builds installable zips in dist/: the theme (minemirth/) and the plugin
# (mine-mirth-commerce/), each with its folder at the top level.
set -euo pipefail
root="$(cd "$(dirname "$0")/.." && pwd)"
mkdir -p "$root/dist"
build() {
	local dir="$1" header="$2"
	local version
	version="$(sed -n "s/^[ *]*Version: *//p" "$root/$dir/$header" | head -1 | tr -d '\r')"
	local out="$root/dist/$dir-$version.zip"
	rm -f "$out"
	(cd "$root" && zip -qr "$out" "$dir" -x '*.DS_Store' -x '*/node_modules/*')
	echo "$out"
}
build minemirth style.css
build mine-mirth-commerce mine-mirth-commerce.php
