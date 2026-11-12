#!/usr/bin/env bash
# Renders the WordPress.org icon and banner PNGs from the sources in this folder.
# Needs Google Chrome (or Chromium) plus the Inter and JetBrains Mono fonts installed.
# Usage: docs/brand/render.sh   (writes into .wordpress-org/)
set -euo pipefail

here="$(cd "$(dirname "$0")" && pwd)"
out="$here/../../.wordpress-org"
chrome="${CHROME:-$(command -v google-chrome || command -v chromium || command -v chromium-browser)}"

shot() { # shot <source> <css-width> <css-height> <scale> <output>
	"$chrome" --headless=new --disable-gpu --hide-scrollbars --no-sandbox \
		--default-background-color=00000000 \
		--window-size="$2,$3" --force-device-scale-factor="$4" \
		--screenshot="$5" "file://$1" 2> /dev/null
	echo "wrote $(basename "$5")"
}

shot "$here/icon.svg"     256 256 1   "$out/icon-256x256.png"
shot "$here/icon.svg"     256 256 0.5 "$out/icon-128x128.png"
shot "$here/banner.html"  772 250 1   "$out/banner-772x250.png"
shot "$here/banner.html"  772 250 2   "$out/banner-1544x500.png"
cp "$here/icon.svg" "$out/icon.svg"
