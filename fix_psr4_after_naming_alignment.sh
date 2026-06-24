#!/usr/bin/env bash
set -u

if [[ ! -f artisan ]]; then
  echo "ERROR: Jalankan script ini dari root project Laravel (folder yang ada file artisan)."
  exit 1
fi

php_replace_all() {
  local search="$1"
  local replace="$2"
  SEARCH="$search" REPLACE="$replace" php <<'PHP'
<?php
$search = getenv('SEARCH');
$replace = getenv('REPLACE');
$root = 'app';
if (!is_dir($root)) exit(0);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    $content = file_get_contents($path);
    $new = str_replace($search, $replace, $content);
    if ($new !== $content) file_put_contents($path, $new);
}
PHP
}

php_replace_file() {
  local file="$1"
  local search="$2"
  local replace="$3"
  [[ -f "$file" ]] || return 0
  FILE="$file" SEARCH="$search" REPLACE="$replace" php <<'PHP'
<?php
$file = getenv('FILE');
$search = getenv('SEARCH');
$replace = getenv('REPLACE');
$content = file_get_contents($file);
$new = str_replace($search, $replace, $content);
if ($new !== $content) file_put_contents($file, $new);
PHP
}

ensure_use() {
  local file="$1"
  local fqcn="$2"
  [[ -f "$file" ]] || return 0
  FILE="$file" FQCN="$fqcn" php <<'PHP'
<?php
$file = getenv('FILE');
$fqcn = getenv('FQCN');
$content = file_get_contents($file);
$useLine = "use {$fqcn};";
if (str_contains($content, $useLine)) exit(0);
$content = preg_replace('/^(namespace\s+[^;]+;\s*)/m', "$1\n{$useLine}\n", $content, 1);
file_put_contents($file, $content);
PHP
}

rename_path() {
  local from="$1"
  local to="$2"
  if [[ -e "$from" && ! -e "$to" ]]; then
    mkdir -p "$(dirname "$to")"
    mv "$from" "$to"
    echo "RENAMED: $from -> $to"
  fi
}

merge_dir() {
  local from="$1"
  local to="$2"
  if [[ -d "$from" ]]; then
    mkdir -p "$to"
    shopt -s dotglob nullglob
    for item in "$from"/*; do
      mv "$item" "$to"/
    done
    shopt -u dotglob nullglob
    rmdir "$from" 2>/dev/null || true
    echo "MERGED: $from -> $to"
  fi
}

echo "== 1) Balikkan rename yang terlalu luas: SettingsEdomQuestion* -> EdomQuestion* =="
php_replace_all "SettingsEdomQuestionCategories" "EdomQuestionCategories"
php_replace_all "SettingsEdomQuestionCategory" "EdomQuestionCategory"
php_replace_all "SettingsEdomQuestionOptions" "EdomQuestionOptions"
php_replace_all "SettingsEdomQuestionOption" "EdomQuestionOption"
php_replace_all "SettingsEdomQuestions" "EdomQuestions"
php_replace_all "SettingsEdomQuestion" "EdomQuestion"

echo "== 2) Rapikan ProgramStudi: folder, file, class, namespace =="
merge_dir "app/Filament/Resources/ProgramStudi" "app/Filament/Resources/ProgramStudis"
rename_path "app/Filament/Resources/ProgramStudis/ProdiResource.php" "app/Filament/Resources/ProgramStudis/ProgramStudiResource.php"
rename_path "app/Filament/Resources/ProgramStudis/Pages/CreateProdi.php" "app/Filament/Resources/ProgramStudis/Pages/CreateProgramStudi.php"
rename_path "app/Filament/Resources/ProgramStudis/Pages/EditProdi.php" "app/Filament/Resources/ProgramStudis/Pages/EditProgramStudi.php"
rename_path "app/Filament/Resources/ProgramStudis/Pages/ListProdis.php" "app/Filament/Resources/ProgramStudis/Pages/ListProgramStudis.php"
rename_path "app/Filament/Resources/ProgramStudis/Schemas/ProdiForm.php" "app/Filament/Resources/ProgramStudis/Schemas/ProgramStudiForm.php"
rename_path "app/Filament/Resources/ProgramStudis/Tables/ProdisTable.php" "app/Filament/Resources/ProgramStudis/Tables/ProgramStudisTable.php"
php_replace_all "App\\Filament\\Resources\\ProgramStudi" "App\\Filament\\Resources\\ProgramStudis"
php_replace_all "ProdiResource" "ProgramStudiResource"
php_replace_all "CreateProdi" "CreateProgramStudi"
php_replace_all "EditProdi" "EditProgramStudi"
php_replace_all "ListProdis" "ListProgramStudis"
php_replace_all "ProdiForm" "ProgramStudiForm"
php_replace_all "ProdisTable" "ProgramStudisTable"

echo "== 3) Rapikan SettingsEdom: folder, file, class, namespace =="
merge_dir "app/Filament/Resources/Edoms" "app/Filament/Resources/SettingsEdom"
merge_dir "app/Filament/Resources/SettingsEdoms" "app/Filament/Resources/SettingsEdom"
rename_path "app/Filament/Resources/SettingsEdom/EdomResource.php" "app/Filament/Resources/SettingsEdom/SettingsEdomResource.php"
rename_path "app/Filament/Resources/SettingsEdom/Pages/CreateEdom.php" "app/Filament/Resources/SettingsEdom/Pages/CreateSettingsEdom.php"
rename_path "app/Filament/Resources/SettingsEdom/Pages/EditEdom.php" "app/Filament/Resources/SettingsEdom/Pages/EditSettingsEdom.php"
rename_path "app/Filament/Resources/SettingsEdom/Pages/ListEdoms.php" "app/Filament/Resources/SettingsEdom/Pages/ListSettingsEdoms.php"
rename_path "app/Filament/Resources/SettingsEdom/Schemas/EdomForm.php" "app/Filament/Resources/SettingsEdom/Schemas/SettingsEdomForm.php"
rename_path "app/Filament/Resources/SettingsEdom/Tables/EdomsTable.php" "app/Filament/Resources/SettingsEdom/Tables/SettingsEdomsTable.php"
php_replace_all "App\\Filament\\Resources\\SettingsEdoms" "App\\Filament\\Resources\\SettingsEdom"
php_replace_all "App\\Filament\\Resources\\Edoms" "App\\Filament\\Resources\\SettingsEdom"
php_replace_all "EdomResource" "SettingsEdomResource"
php_replace_all "CreateEdom" "CreateSettingsEdom"
php_replace_all "EditEdom" "EditSettingsEdom"
php_replace_all "ListEdoms" "ListSettingsEdoms"
php_replace_all "EdomForm" "SettingsEdomForm"
php_replace_all "EdomsTable" "SettingsEdomsTable"
# Koreksi efek samping dari penggantian EdomResource agar resource pertanyaan tidak berubah.
php_replace_all "EdomQuestionCategorySettingsEdomResource" "EdomQuestionCategoryResource"
php_replace_all "EdomQuestionOptionSettingsEdomResource" "EdomQuestionOptionResource"
php_replace_all "EdomQuestionSettingsEdomResource" "EdomQuestionResource"

echo "== 4) Rapikan model file agar class sama dengan nama file =="
if [[ -f app/Models/EdomSettings.php ]] && grep -q "class SettingsEdom" app/Models/EdomSettings.php; then
  rename_path "app/Models/EdomSettings.php" "app/Models/SettingsEdom.php"
fi
if [[ -f app/Models/Edom.php ]] && grep -q "class SettingsEdom" app/Models/Edom.php; then
  rename_path "app/Models/Edom.php" "app/Models/SettingsEdom.php"
fi
if [[ -f app/Models/Prodi.php ]] && grep -q "class ProgramStudi" app/Models/Prodi.php; then
  rename_path "app/Models/Prodi.php" "app/Models/ProgramStudi.php"
fi
if [[ -f app/Models/MataKuliah.php ]] && grep -q "class Course" app/Models/MataKuliah.php; then
  rename_path "app/Models/MataKuliah.php" "app/Models/Course.php"
fi
php_replace_file "app/Models/SettingsEdom.php" "class EdomSettings" "class SettingsEdom"
php_replace_file "app/Models/SettingsEdom.php" "protected \$table = 'edoms';" "protected \$table = 'edom_settings';"
php_replace_file "app/Models/SettingsEdom.php" 'protected $table = "edoms";' 'protected $table = "edom_settings";'
php_replace_file "app/Models/Course.php" "class MataKuliah" "class Course"
php_replace_file "app/Models/ProgramStudi.php" "class Prodi" "class ProgramStudi"
php_replace_all "App\\Models\\EdomSettings" "App\\Models\\SettingsEdom"
php_replace_all "EdomSettings::" "SettingsEdom::"

echo "== 5) Perbaiki page MataKuliah yang sekarang mengarah ke Course =="
rename_path "app/Filament/Resources/MataKuliahs/Pages/CreateMataKuliah.php" "app/Filament/Resources/MataKuliahs/Pages/CreateCourse.php"
rename_path "app/Filament/Resources/MataKuliahs/Pages/EditMataKuliah.php" "app/Filament/Resources/MataKuliahs/Pages/EditCourse.php"
php_replace_all "CreateMataKuliah" "CreateCourse"
php_replace_all "EditMataKuliah" "EditCourse"
ensure_use "app/Filament/Resources/MataKuliahs/MataKuliahResource.php" "App\\Filament\\Resources\\MataKuliahs\\Pages\\CreateCourse"
ensure_use "app/Filament/Resources/MataKuliahs/MataKuliahResource.php" "App\\Filament\\Resources\\MataKuliahs\\Pages\\EditCourse"
php_replace_all "App\\Models\\MataKuliah" "App\\Models\\Course"
php_replace_all "MataKuliah::class" "Course::class"

echo "== 6) Autoload check =="
composer dump-autoload
php artisan optimize:clear

echo ""
echo "Selesai. Jalankan:"
echo "  grep -R \"does not comply with psr-4\|Class .* not found\" storage/logs 2>/dev/null || true"
echo "  git diff --stat"
echo "  git diff --name-status"
