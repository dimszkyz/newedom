# Apply patch cleanup penamaan Filament + schema EDOM

Patch ini merapikan sisa penamaan lama di project:

- `Edoms` → `SettingEdoms`
- `EdomResource` → `SettingEdomResource`
- `EdomCategory` / `EdomCategories` → `EdomQuestionCategory` / `EdomQuestionCategories`
- `EdomOption` / `EdomOptions` → `EdomQuestionOption` / `EdomQuestionOptions`
- `Prodi` / `Prodis` → `ProgramStudi` / `ProgramStudis`
- `MataKuliahs`, `MataKuliah`, dan `Course` dihapus
- tabel manual `courses` dan `edom_courses` dihapus
- `edom_responses` / `edom_answers` diganti menjadi `edom_response` / `edom_response_detail`

## Cara apply manual Windows

1. Extract ZIP.
2. Buka folder `repo`.
3. Copy semua isi folder `repo` ke root project Laravel kamu:
   `D:\13. bptik\newedom`
4. Pilih Replace/Timpa.
5. Hapus file/folder lama yang ada di `DELETE_FILES.txt`.
6. Jalankan:

```bash
composer dump-autoload
php artisan optimize:clear
php artisan migrate:fresh --seed
```

## Cara apply otomatis PowerShell

Dari PowerShell:

```powershell
powershell -ExecutionPolicy Bypass -File "D:\path\hasil-extract\apply_update_windows.ps1" -ProjectRoot "D:\13. bptik\newedom"
```

## Test

```bash
php artisan edom:make-token
```

Default test token memakai:

- `siakad_idmahasiswa=testing18273`
- `idtahunajaran=2026`
- `idsemester=2`
