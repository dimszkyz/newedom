#!/usr/bin/env bash
set -euo pipefail

# Jalankan dari root project Laravel, sejajar dengan artisan dan composer.json.
if [ ! -f artisan ] || [ ! -d app ]; then
  echo "ERROR: Jalankan script ini dari root project Laravel (folder yang berisi artisan)."
  exit 1
fi

run_git_mv() {
  local from="$1"
  local to="$2"

  if [ -e "$from" ] && [ ! -e "$to" ]; then
    mkdir -p "$(dirname "$to")"
    git mv "$from" "$to" 2>/dev/null || mv "$from" "$to"
    echo "RENAMED: $from -> $to"
  fi
}

replace_in_php() {
  local search="$1"
  local replace="$2"
  find app routes database config -type f \( -name '*.php' -o -name '*.blade.php' \) 2>/dev/null \
    -exec sed -i "s|$search|$replace|g" {} +
}

ensure_table_line() {
  local file="$1"
  local table="$2"
  if [ -f "$file" ]; then
    if grep -q 'protected \$table' "$file"; then
      sed -i "s|protected \\$table = '[^']*';|protected \\$table = '$table';|g" "$file"
      sed -i "s|protected \\$table = \"[^\"]*\";|protected \\$table = '$table';|g" "$file"
    else
      sed -i "/class .* extends Model/a\\    protected \\$table = '$table';" "$file"
    fi
  fi
}

cleanup_duplicates() {
  replace_in_php 'SettingsSettingsEdom' 'SettingsEdom'
  replace_in_php 'CreateSettingsSettingsEdom' 'CreateSettingsEdom'
  replace_in_php 'EditSettingsSettingsEdom' 'EditSettingsEdom'
  replace_in_php 'ListSettingsSettingsEdoms' 'ListSettingsEdoms'
  replace_in_php 'EdomQuestionQuestionCategory' 'EdomQuestionCategory'
  replace_in_php 'EdomQuestionQuestionOption' 'EdomQuestionOption'
  replace_in_php 'ProgramStudiStudi' 'ProgramStudi'
}

echo "== 1) Sinkronisasi folder Filament Edoms -> SettingsEdom =="

# Kalau folder lama masih ada, rename ke folder baru.
run_git_mv "app/Filament/Resources/Edoms" "app/Filament/Resources/SettingsEdom"

# Rename file di dalam SettingsEdom agar sama dengan nama folder/resource.
run_git_mv "app/Filament/Resources/SettingsEdom/EdomResource.php" "app/Filament/Resources/SettingsEdom/SettingsEdomResource.php"
run_git_mv "app/Filament/Resources/SettingsEdom/Pages/CreateEdom.php" "app/Filament/Resources/SettingsEdom/Pages/CreateSettingsEdom.php"
run_git_mv "app/Filament/Resources/SettingsEdom/Pages/EditEdom.php" "app/Filament/Resources/SettingsEdom/Pages/EditSettingsEdom.php"
run_git_mv "app/Filament/Resources/SettingsEdom/Pages/ListEdoms.php" "app/Filament/Resources/SettingsEdom/Pages/ListSettingsEdoms.php"
run_git_mv "app/Filament/Resources/SettingsEdom/Schemas/EdomForm.php" "app/Filament/Resources/SettingsEdom/Schemas/SettingsEdomForm.php"
run_git_mv "app/Filament/Resources/SettingsEdom/Tables/EdomsTable.php" "app/Filament/Resources/SettingsEdom/Tables/SettingsEdomsTable.php"

# Update namespace/import/resource class/page class/schema/table class.
replace_in_php 'App\\Filament\\Resources\\Edoms' 'App\\Filament\\Resources\\SettingsEdom'
replace_in_php 'Resources\\Edoms' 'Resources\\SettingsEdom'
replace_in_php 'EdomResource' 'SettingsEdomResource'
replace_in_php 'CreateEdom' 'CreateSettingsEdom'
replace_in_php 'EditEdom' 'EditSettingsEdom'
replace_in_php 'ListEdoms' 'ListSettingsEdoms'
replace_in_php 'EdomForm' 'SettingsEdomForm'
replace_in_php 'EdomsTable' 'SettingsEdomsTable'
cleanup_duplicates

echo "== 2) Sinkronisasi nama model yang umum berubah =="

# Model lama -> model baru. Bagian ini aman: hanya berjalan jika file lama masih ada.
run_git_mv "app/Models/Edom.php" "app/Models/SettingsEdom.php"
run_git_mv "app/Models/EdomCategory.php" "app/Models/EdomQuestionCategory.php"
run_git_mv "app/Models/EdomOption.php" "app/Models/EdomQuestionOption.php"
run_git_mv "app/Models/Prodi.php" "app/Models/ProgramStudi.php"
run_git_mv "app/Models/MataKuliah.php" "app/Models/Course.php"

# Update import dan pemanggilan class model.
replace_in_php 'App\\Models\\Edom;' 'App\\Models\\SettingsEdom;'
replace_in_php 'App\\Models\\EdomCategory;' 'App\\Models\\EdomQuestionCategory;'
replace_in_php 'App\\Models\\EdomOption;' 'App\\Models\\EdomQuestionOption;'
replace_in_php 'App\\Models\\Prodi;' 'App\\Models\\ProgramStudi;'
replace_in_php 'App\\Models\\MataKuliah;' 'App\\Models\\Course;'

replace_in_php 'Edom::class' 'SettingsEdom::class'
replace_in_php 'Edom::' 'SettingsEdom::'
replace_in_php 'class Edom extends Model' 'class SettingsEdom extends Model'

replace_in_php 'EdomCategory::class' 'EdomQuestionCategory::class'
replace_in_php 'EdomCategory::' 'EdomQuestionCategory::'
replace_in_php 'class EdomCategory extends Model' 'class EdomQuestionCategory extends Model'

replace_in_php 'EdomOption::class' 'EdomQuestionOption::class'
replace_in_php 'EdomOption::' 'EdomQuestionOption::'
replace_in_php 'class EdomOption extends Model' 'class EdomQuestionOption extends Model'

replace_in_php 'Prodi::class' 'ProgramStudi::class'
replace_in_php 'Prodi::' 'ProgramStudi::'
replace_in_php 'class Prodi extends Model' 'class ProgramStudi extends Model'

replace_in_php 'MataKuliah::class' 'Course::class'
replace_in_php 'MataKuliah::' 'Course::'
replace_in_php 'class MataKuliah extends Model' 'class Course extends Model'
cleanup_duplicates

echo "== 3) Pastikan protected table model sesuai SQL terbaru =="
ensure_table_line "app/Models/SettingsEdom.php" "edom_settings"
ensure_table_line "app/Models/EdomQuestionCategory.php" "edom_question_categories"
ensure_table_line "app/Models/EdomQuestionOption.php" "edom_question_options"
ensure_table_line "app/Models/ProgramStudi.php" "program_studi"
ensure_table_line "app/Models/Course.php" "courses"

echo "== 4) Rapikan autoload/cache =="
if command -v composer >/dev/null 2>&1; then
  composer dump-autoload || true
fi

if command -v php >/dev/null 2>&1; then
  php artisan optimize:clear || true
fi

echo ""
echo "Selesai. Jalankan pengecekan berikut:"
echo "  git diff --stat"
echo "  git diff --name-status"
echo "  grep -R \"Resources\\\\Edoms\\|EdomResource\\|CreateEdom\\|EditEdom\\|ListEdoms\" app/Filament || true"
echo "  php artisan optimize:clear"
echo ""
echo "Script ini fokus ke penamaan folder/file/class/model. Nama kolom SQL tidak diubah massal agar relasi seperti edom_responses.edom_id dan courses.study_program_id tidak rusak."
