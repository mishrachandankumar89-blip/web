#!/usr/bin/env bash
# Build content, lint PHP and JS, and zip the theme into dist/avenza.zip.
# Usage: bash tools/package.sh [--art]   (--art also redraws the artwork)
set -euo pipefail
cd "$(dirname "$0")/.."

if [[ "${1:-}" == "--art" ]]; then
	python3 tools/make_art.py
	node tools/render_art.js
	python3 tools/convert_art.py
fi

python3 tools/build_content.py

for f in $(find avenza -name '*.php'); do
	php -l "$f" > /dev/null
done
node --check avenza/assets/js/theme.js
python3 -c "import json; json.load(open('avenza/theme.json')); json.load(open('avenza/content/manifest.json'))"

version=$(grep -m1 '^Version:' avenza/style.css | awk '{print $2}')
php_version=$(grep -m1 "AVENZA_VERSION'" avenza/functions.php | grep -o "[0-9.]*" | head -1)
manifest_version=$(python3 -c "import json; print(json.load(open('avenza/content/manifest.json'))['version'])")
if [[ "$version" != "$php_version" || "$version" != "$manifest_version" ]]; then
	echo "Version mismatch: style.css $version, functions.php $php_version, manifest $manifest_version" >&2
	exit 1
fi

mkdir -p dist
rm -f dist/avenza.zip
zip -rqX dist/avenza.zip avenza -x '*.DS_Store' -x '*/__pycache__/*'
echo "dist/avenza.zip  version $version  $(du -h dist/avenza.zip | cut -f1)"
