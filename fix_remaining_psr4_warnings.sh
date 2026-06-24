#!/usr/bin/env bash
set -euo pipefail

if [ ! -f artisan ]; then
  echo "ERROR: Jalankan script ini dari root project Laravel, sejajar dengan file artisan."
  exit 1
fi

echo "== 1) Perbaiki class EdomQuestion yang tidak sengaja berubah jadi SettingsEdomQuestion =="
find app/Filament -type f -name "*.php" -print0 | xargs -0 sed -i \
  -e 's/CreateSettingsEdomQuestionCategory/CreateEdomQuestionCategory/g' \
  -e 's/EditSettingsEdomQuestionCategory/EditEdomQuestionCategory/g' \
  -e 's/CreateSettingsEdomQuestionOption/CreateEdomQuestionOption/g' \
  -e 's/EditSettingsEdomQuestionOption/EditEdomQuestionOption/g' \
  -e 's/CreateSettingsEdomQuestion/CreateEdomQuestion/g' \
  -e 's/EditSettingsEdomQuestion/EditEdomQuestion/g'

echo "== 2) Perbaiki typo namespace ProgramStudiss -> ProgramStudis =="
find app -type f -name "*.php" -print0 | xargs -0 sed -i \
  -e 's/ProgramStudiss/ProgramStudis/g' \
  -e 's/Resources\\ProgramStudiss/Resources\\ProgramStudis/g'

echo "== 3) Perbaiki class SettingsSettingsEdom* -> SettingsEdom* =="
find app -type f -name "*.php" -print0 | xargs -0 sed -i \
  -e 's/SettingsSettingsEdomResource/SettingsEdomResource/g' \
  -e 's/SettingsSettingsEdomForm/SettingsEdomForm/g' \
  -e 's/SettingsSettingsEdomsTable/SettingsEdomsTable/g' \
  -e 's/SettingsSettingsEdom/SettingsEdom/g'

echo "== 4) Pastikan nama folder/file utama ada di lokasi benar =="
# Folder ProgramStudis
if [ -d app/Filament/Resources/ProgramStudi ] && [ ! -d app/Filament/Resources/ProgramStudis ]; then
  mv app/Filament/Resources/ProgramStudi app/Filament/Resources/ProgramStudis
fi

# File ProgramStudi
[ -f app/Filament/Resources/ProgramStudis/ProdiResource.php ] && mv app/Filament/Resources/ProgramStudis/ProdiResource.php app/Filament/Resources/ProgramStudis/ProgramStudiResource.php
[ -f app/Filament/Resources/ProgramStudis/Pages/CreateProdi.php ] && mv app/Filament/Resources/ProgramStudis/Pages/CreateProdi.php app/Filament/Resources/ProgramStudis/Pages/CreateProgramStudi.php
[ -f app/Filament/Resources/ProgramStudis/Pages/EditProdi.php ] && mv app/Filament/Resources/ProgramStudis/Pages/EditProdi.php app/Filament/Resources/ProgramStudis/Pages/EditProgramStudi.php
[ -f app/Filament/Resources/ProgramStudis/Pages/ListProdis.php ] && mv app/Filament/Resources/ProgramStudis/Pages/ListProdis.php app/Filament/Resources/ProgramStudis/Pages/ListProgramStudis.php
[ -f app/Filament/Resources/ProgramStudis/Schemas/ProdiForm.php ] && mv app/Filament/Resources/ProgramStudis/Schemas/ProdiForm.php app/Filament/Resources/ProgramStudis/Schemas/ProgramStudiForm.php
[ -f app/Filament/Resources/ProgramStudis/Tables/ProdisTable.php ] && mv app/Filament/Resources/ProgramStudis/Tables/ProdisTable.php app/Filament/Resources/ProgramStudis/Tables/ProgramStudisTable.php

# Folder SettingsEdom
if [ -d app/Filament/Resources/SettingsEdoms ] && [ ! -d app/Filament/Resources/SettingsEdom ]; then
  mv app/Filament/Resources/SettingsEdoms app/Filament/Resources/SettingsEdom
fi

# File SettingsEdom
[ -f app/Filament/Resources/SettingsEdom/EdomResource.php ] && mv app/Filament/Resources/SettingsEdom/EdomResource.php app/Filament/Resources/SettingsEdom/SettingsEdomResource.php
[ -f app/Filament/Resources/SettingsEdom/Pages/CreateEdom.php ] && mv app/Filament/Resources/SettingsEdom/Pages/CreateEdom.php app/Filament/Resources/SettingsEdom/Pages/CreateSettingsEdom.php
[ -f app/Filament/Resources/SettingsEdom/Pages/EditEdom.php ] && mv app/Filament/Resources/SettingsEdom/Pages/EditEdom.php app/Filament/Resources/SettingsEdom/Pages/EditSettingsEdom.php
[ -f app/Filament/Resources/SettingsEdom/Pages/ListEdoms.php ] && mv app/Filament/Resources/SettingsEdom/Pages/ListEdoms.php app/Filament/Resources/SettingsEdom/Pages/ListSettingsEdoms.php
[ -f app/Filament/Resources/SettingsEdom/Schemas/EdomForm.php ] && mv app/Filament/Resources/SettingsEdom/Schemas/EdomForm.php app/Filament/Resources/SettingsEdom/Schemas/SettingsEdomForm.php
[ -f app/Filament/Resources/SettingsEdom/Tables/EdomsTable.php ] && mv app/Filament/Resources/SettingsEdom/Tables/EdomsTable.php app/Filament/Resources/SettingsEdom/Tables/SettingsEdomsTable.php

echo "== 5) Cek autoload =="
composer dump-autoload
php artisan optimize:clear

echo "Selesai. Cek sisa referensi bermasalah dengan:"
echo '  grep -R "SettingsEdomQuestion\|ProgramStudiss\|SettingsSettingsEdom" app || true'
echo '  git diff --stat'
echo '  git diff --name-status'
