#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"
PATCH_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

cp -R "$PATCH_DIR/repo/"* "$ROOT/"

while IFS= read -r file; do
  [ -n "$file" ] && rm -f "$ROOT/$file"
done < "$PATCH_DIR/DELETE_FILES.txt"

echo "Update files copied. Run:"
echo "  composer dump-autoload"
echo "  php artisan migrate:fresh --seed"
echo "  php artisan optimize:clear"
