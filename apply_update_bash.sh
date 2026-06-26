#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${1:-$(pwd)}"
PATCH_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

cp -R "$PATCH_ROOT/repo/"* "$PROJECT_ROOT/"

while IFS= read -r file; do
  [ -n "$file" ] && rm -rf "$PROJECT_ROOT/$file"
done < "$PATCH_ROOT/DELETE_FILES.txt"

echo "Done. Run:"
echo "composer dump-autoload"
echo "php artisan optimize:clear"
echo "php artisan migrate:fresh --seed"
