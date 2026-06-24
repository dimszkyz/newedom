#!/usr/bin/env bash
set -euo pipefail

# Jalankan dari root project Laravel (folder yang sejajar dengan artisan dan composer.json)
if [ ! -f artisan ]; then
  echo "Error: jalankan script ini dari root project Laravel, sejajar dengan file artisan."
  exit 1
fi

rename_path() {
  local from="$1"
  local to="$2"

  if [ -e "$from" ] && [ ! -e "$to" ]; then
    mkdir -p "$(dirname "$to")"
    mv "$from" "$to"
    echo "Renamed: $from -> $to"
  fi
}

# 1. Resource kategori EDOM disesuaikan dengan tabel edom_question_categories.
rename_path app/Filament/Resources/EdomCategories app/Filament/Resources/EdomQuestionCategories
rename_path app/Filament/Resources/EdomQuestionCategories/EdomCategoryResource.php app/Filament/Resources/EdomQuestionCategories/EdomQuestionCategoryResource.php
rename_path app/Filament/Resources/EdomQuestionCategories/Schemas/EdomCategoryForm.php app/Filament/Resources/EdomQuestionCategories/Schemas/EdomQuestionCategoryForm.php
rename_path app/Filament/Resources/EdomQuestionCategories/Tables/EdomCategoriesTable.php app/Filament/Resources/EdomQuestionCategories/Tables/EdomQuestionCategoriesTable.php
rename_path app/Filament/Resources/EdomQuestionCategories/Pages/ListEdomCategories.php app/Filament/Resources/EdomQuestionCategories/Pages/ListEdomQuestionCategories.php
rename_path app/Filament/Resources/EdomQuestionCategories/Pages/CreateEdomCategory.php app/Filament/Resources/EdomQuestionCategories/Pages/CreateEdomQuestionCategory.php
rename_path app/Filament/Resources/EdomQuestionCategories/Pages/EditEdomCategory.php app/Filament/Resources/EdomQuestionCategories/Pages/EditEdomQuestionCategory.php

# 2. Resource opsi EDOM disesuaikan dengan tabel edom_question_options.
rename_path app/Filament/Resources/EdomOptions app/Filament/Resources/EdomQuestionOptions
rename_path app/Filament/Resources/EdomQuestionOptions/EdomOptionResource.php app/Filament/Resources/EdomQuestionOptions/EdomQuestionOptionResource.php
rename_path app/Filament/Resources/EdomQuestionOptions/Schemas/EdomOptionForm.php app/Filament/Resources/EdomQuestionOptions/Schemas/EdomQuestionOptionForm.php
rename_path app/Filament/Resources/EdomQuestionOptions/Tables/EdomOptionsTable.php app/Filament/Resources/EdomQuestionOptions/Tables/EdomQuestionOptionsTable.php
rename_path app/Filament/Resources/EdomQuestionOptions/Pages/ListEdomOptions.php app/Filament/Resources/EdomQuestionOptions/Pages/ListEdomQuestionOptions.php
rename_path app/Filament/Resources/EdomQuestionOptions/Pages/CreateEdomOption.php app/Filament/Resources/EdomQuestionOptions/Pages/CreateEdomQuestionOption.php
rename_path app/Filament/Resources/EdomQuestionOptions/Pages/EditEdomOption.php app/Filament/Resources/EdomQuestionOptions/Pages/EditEdomQuestionOption.php

# 3. Resource prodi disesuaikan menjadi ProgramStudi agar sama dengan istilah tabel program_studi.
rename_path app/Filament/Resources/Prodis app/Filament/Resources/ProgramStudis
rename_path app/Filament/Resources/ProgramStudis/ProdiResource.php app/Filament/Resources/ProgramStudis/ProgramStudiResource.php
rename_path app/Filament/Resources/ProgramStudis/Schemas/ProdiForm.php app/Filament/Resources/ProgramStudis/Schemas/ProgramStudiForm.php
rename_path app/Filament/Resources/ProgramStudis/Tables/ProdisTable.php app/Filament/Resources/ProgramStudis/Tables/ProgramStudisTable.php
rename_path app/Filament/Resources/ProgramStudis/Pages/ListProdis.php app/Filament/Resources/ProgramStudis/Pages/ListProgramStudis.php
rename_path app/Filament/Resources/ProgramStudis/Pages/CreateProdi.php app/Filament/Resources/ProgramStudis/Pages/CreateProgramStudi.php
rename_path app/Filament/Resources/ProgramStudis/Pages/EditProdi.php app/Filament/Resources/ProgramStudis/Pages/EditProgramStudi.php

# 4. Update namespace, import, nama class, dan referensi lama di seluruh file PHP app/Filament.
find app/Filament -type f -name '*.php' -print0 | xargs -0 sed -i \
  -e 's/App\\Filament\\Resources\\EdomCategories/App\\Filament\\Resources\\EdomQuestionCategories/g' \
  -e 's/App\\Filament\\Resources\\EdomOptions/App\\Filament\\Resources\\EdomQuestionOptions/g' \
  -e 's/App\\Filament\\Resources\\Prodis/App\\Filament\\Resources\\ProgramStudis/g' \
  -e 's/namespace App\\Filament\\Resources\\EdomCategories/namespace App\\Filament\\Resources\\EdomQuestionCategories/g' \
  -e 's/namespace App\\Filament\\Resources\\EdomOptions/namespace App\\Filament\\Resources\\EdomQuestionOptions/g' \
  -e 's/namespace App\\Filament\\Resources\\Prodis/namespace App\\Filament\\Resources\\ProgramStudis/g' \
  -e 's/EdomCategoryResource/EdomQuestionCategoryResource/g' \
  -e 's/EdomCategoryForm/EdomQuestionCategoryForm/g' \
  -e 's/EdomCategoriesTable/EdomQuestionCategoriesTable/g' \
  -e 's/ListEdomCategories/ListEdomQuestionCategories/g' \
  -e 's/CreateEdomCategory/CreateEdomQuestionCategory/g' \
  -e 's/EditEdomCategory/EditEdomQuestionCategory/g' \
  -e 's/EdomOptionResource/EdomQuestionOptionResource/g' \
  -e 's/EdomOptionForm/EdomQuestionOptionForm/g' \
  -e 's/EdomOptionsTable/EdomQuestionOptionsTable/g' \
  -e 's/ListEdomOptions/ListEdomQuestionOptions/g' \
  -e 's/CreateEdomOption/CreateEdomQuestionOption/g' \
  -e 's/EditEdomOption/EditEdomQuestionOption/g' \
  -e 's/ProdiResource/ProgramStudiResource/g' \
  -e 's/ProdiForm/ProgramStudiForm/g' \
  -e 's/ProdisTable/ProgramStudisTable/g' \
  -e 's/ListProdis/ListProgramStudis/g' \
  -e 's/CreateProdi/CreateProgramStudi/g' \
  -e 's/EditProdi/EditProgramStudi/g' \
  -e 's/class ProdiResource/class ProgramStudiResource/g' \
  -e 's/class ProdiForm/class ProgramStudiForm/g' \
  -e 's/class ProdisTable/class ProgramStudisTable/g' \
  -e 's/class ListProdis/class ListProgramStudis/g' \
  -e 's/class CreateProdi/class CreateProgramStudi/g' \
  -e 's/class EditProdi/class EditProgramStudi/g'

# 5. Perbaikan khusus agar atribut Filament memakai kolom SQL baru.
find app/Filament -type f -name '*.php' -print0 | xargs -0 sed -i \
  -e "s/recordTitleAttribute = 'nama_kategori'/recordTitleAttribute = 'name'/g" \
  -e "s/recordTitleAttribute = 'nama'/recordTitleAttribute = 'name'/g" \
  -e "s/recordTitleAttribute = 'label'/recordTitleAttribute = 'name'/g" \
  -e "s/recordTitleAttribute = 'pernyataan'/recordTitleAttribute = 'statement'/g" \
  -e 's/->nama_edom/->name/g' \
  -e 's/->nama_kategori/->name/g' \
  -e 's/->pernyataan/->statement/g' \
  -e "s/\['edom_id'\] = request()->integer('edom_id')/['edom_setting_id'] = request()->integer('edom_id')/g"

# 6. Rapikan label resource Program Studi.
if [ -f app/Filament/Resources/ProgramStudis/ProgramStudiResource.php ]; then
  sed -i \
    -e "s/protected static ?string \$navigationLabel = 'Prodi';/protected static ?string \$navigationLabel = 'Program Studi';/g" \
    -e "s/protected static ?string \$modelLabel = 'Prodi';/protected static ?string \$modelLabel = 'Program Studi';/g" \
    -e "s/protected static ?string \$pluralModelLabel = 'Prodi';/protected static ?string \$pluralModelLabel = 'Program Studi';/g" \
    -e "s/protected static ?string \$slug = 'prodi';/protected static ?string \$slug = 'program-studi';/g" \
    app/Filament/Resources/ProgramStudis/ProgramStudiResource.php
fi

# 7. Kalau ada import model lama yang ingin dibaca lebih sesuai konteks resource baru, pakai alias tanpa mengubah model asli.
if [ -f app/Filament/Resources/EdomQuestionCategories/EdomQuestionCategoryResource.php ]; then
  sed -i \
    -e 's/use App\\Models\\EdomCategory;/use App\\Models\\EdomCategory as EdomQuestionCategory;/g' \
    -e 's/protected static ?string \$model = EdomCategory::class;/protected static ?string \$model = EdomQuestionCategory::class;/g' \
    app/Filament/Resources/EdomQuestionCategories/EdomQuestionCategoryResource.php
fi

if [ -f app/Filament/Resources/EdomQuestionOptions/EdomQuestionOptionResource.php ]; then
  sed -i \
    -e 's/use App\\Models\\EdomOption;/use App\\Models\\EdomOption as EdomQuestionOption;/g' \
    -e 's/protected static ?string \$model = EdomOption::class;/protected static ?string \$model = EdomQuestionOption::class;/g' \
    app/Filament/Resources/EdomQuestionOptions/EdomQuestionOptionResource.php
fi

# 8. Pastikan referensi lama dari relation/breadcrumb mengarah ke resource baru.
find app/Filament -type f -name '*.php' -print0 | xargs -0 sed -i \
  -e 's/\\App\\Filament\\Resources\\EdomCategories\\EdomQuestionCategoryResource/\\App\\Filament\\Resources\\EdomQuestionCategories\\EdomQuestionCategoryResource/g' \
  -e 's/use App\\Filament\\Resources\\EdomCategories\\EdomQuestionCategoryResource;/use App\\Filament\\Resources\\EdomQuestionCategories\\EdomQuestionCategoryResource;/g'

# 9. Bersihkan cache Laravel supaya namespace/resource baru terbaca.
php artisan optimize:clear || true

echo "Selesai. Cek perubahan dengan: git diff --stat"
