# Cara apply patch rename schema EDOM

Patch ini memperbaiki error `Cannot add a required argument...` dan mengganti penamaan lama:

- `Edom` / folder `Edoms` -> `SettingEdom` / folder `SettingEdoms`
- `EdomCategory` / folder `EdomCategories` -> `EdomQuestionCategory` / folder `EdomQuestionCategories`
- `EdomOption` / folder `EdomOptions` -> `EdomQuestionOption` / folder `EdomQuestionOptions`
- `EdomAnswer` -> `EdomResponseDetail`
- Mata Kuliah manual dihapus.

## Apply di root project

Root project adalah folder yang berisi `artisan`, `composer.json`, `app`, `database`.

```bash
unzip newedom-rename-schema-fix.zip -d /tmp/newedom-rename-schema-fix
cd /path/ke/newedom

cp -R /tmp/newedom-rename-schema-fix/repo/* .

while IFS= read -r file; do
  [ -n "$file" ] && rm -f "$file"
done < /tmp/newedom-rename-schema-fix/DELETE_FILES.txt

composer dump-autoload
php artisan optimize:clear
php artisan migrate:fresh --seed
```

## Test token

```bash
php artisan edom:make-token
```

Default-nya memakai:

```text
testing18273 2026 2
```

## Catatan database

Gunakan `migrate:fresh` karena tabel lama seperti `courses`, `edom_answers`, dan `edom_responses` harus hilang.
